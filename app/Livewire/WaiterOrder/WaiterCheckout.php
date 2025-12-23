<?php

namespace App\Livewire\WaiterOrder;

use App\Models\CashSession;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use App\Services\DeepSeekService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class WaiterCheckout extends Component
{
    public $name = '';
    public $phone = ''; // Optional
    public $tableNumber = ''; // Optional

    public $cartItems = [];
    public $subtotal = 0;
    public $tax = 0;
    public $total = 0;

    public function mount()
    {
        $userId = auth()->id();
        $this->cartItems = \Illuminate\Support\Facades\Cache::get('waiter_cart_' . $userId, []);

        if (empty($this->cartItems)) {
            return redirect()->route('waiter.order');
        }

        $this->subtotal = collect($this->cartItems)->sum(fn($item) => $item['price'] * $item['qty']);

        $settings = app(\App\Settings\GeneralSettings::class);
        $taxRate = $settings->enable_tax ? ($settings->tax_percentage / 100) : 0;

        $this->tax = $this->subtotal * $taxRate;
        $this->total = $this->subtotal + $this->tax;
    }

    public function placeOrder()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'tableNumber' => 'nullable|string|max:10'
        ]);

        // DB Transaction
        DB::transaction(function () {
            // Find Active Cash Session (Latest Open)
            $activeSession = CashSession::where('status', 'open')
                ->latest()
                ->first();

            $userId = $activeSession ? $activeSession->user_id : auth()->id(); // Assign to active cashier or current user
            $sessionId = $activeSession ? $activeSession->id : null;

            // 🔹 Handle Member Registration / Lookup
            $memberId = null;
            if ($this->phone) {
                // Normalize Phone Number
                $normalizedPhone = preg_replace('/[^0-9]/', '', $this->phone);
                if (str_starts_with($normalizedPhone, '08')) {
                    $normalizedPhone = '62' . substr($normalizedPhone, 1);
                } elseif (str_starts_with($normalizedPhone, '8')) {
                    $normalizedPhone = '62' . $normalizedPhone;
                }

                // Cari atau Buat Member Baru
                $member = Member::where('phone', $normalizedPhone)->first();

                if (!$member) {
                    $defaultTier = \App\Models\LoyaltyTier::orderBy('min_points', 'asc')->first();

                    $member = Member::create([
                        'name' => $this->name,
                        'phone' => $normalizedPhone,
                        'joined_at' => now(),
                        'tier_id' => $defaultTier ? $defaultTier->id : 1,
                    ]);
                }
                $memberId = $member->id;
            }

            // 1. Create Sale (Order)
            $sale = Sale::create([
                'invoice_number' => 'INV-W-' . date('YmdHis') . '-' . rand(100, 999), // W for Waiter
                'customer_name' => $this->name,
                'table_number' => $this->tableNumber ?: 'Take Away / Unset',
                'user_id' => $userId,
                'member_id' => $memberId,
                'cash_session_id' => $sessionId,
                'order_type' => 'dine_in',
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'final_total' => $this->total,
                'total' => $this->total,
                'status' => 'draft', // KDS Pending
                'payment_method' => 'cashier',
                'is_paid' => false,
                'is_tax_reported' => false
            ]);

            // 2. Create Sale Items
            foreach ($this->cartItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['price'] * $item['qty'],
                    'status' => 'pending',
                    'notes' => $item['note'] ?? null
                ]);
            }

            // 3. Auto Print to Kitchen/Bar
            try {
                $printService = new \App\Services\OrderPrintService();
                $printService->printOrderByProductType($sale);
            } catch (\Exception $e) {
                Log::error("Waiter Order Auto Print Failed: " . $e->getMessage());
            }

            // 4. Notify POS Users
            $recipients = \App\Models\User::whereIn('role', [
                \App\Enums\UserRole::SuperAdmin,
                \App\Enums\UserRole::Admin,
                \App\Enums\UserRole::Cashier,
                \App\Enums\UserRole::Waiter
            ])->get();

            $tableInfo = $this->tableNumber ? "Meja {$this->tableNumber}" : "Tanpa Meja";

            Notification::make()
                ->title('New Waiter Order')
                ->body("Order #{$sale->invoice_number} dari {$this->name} ({$tableInfo})")
                ->success()
                ->actions([
                    Action::make('view')
                        ->button()
                        ->url(route('filament.admin.pages.pos', ['sale_id' => $sale->id]), shouldOpenInNewTab: true),
                ])
                ->sendToDatabase($recipients);

            // 5. WhatsApp Notification
            if ($this->phone) {
                $this->sendWhatsAppNotification($sale);
            }
        });

        $this->clearCart();
        $this->dispatch('cart-updated');
        session()->flash('success_waiter', true);

        return redirect()->route('waiter.order')->with('order_placed', 'Pesanan berhasil dibuat!');
    }

    protected function clearCart()
    {
        $userId = auth()->id();
        \Illuminate\Support\Facades\Cache::forget('waiter_cart_' . $userId);
        $this->cartItems = [];
    }

    protected function sendWhatsAppNotification($sale)
    {
        try {
            // 1. Prepare Data
            $tableName = $this->tableNumber ? "Meja #{$this->tableNumber}" : "Take Away / Pending";
            $items = collect($this->cartItems)->map(fn($item) => "{$item['qty']}x {$item['name']}")->toArray();

            $orderData = [
                'customer_name' => $this->name,
                'invoice_number' => $sale->invoice_number,
                'table_number' => $tableName,
                'total_formatted' => 'Rp ' . number_format($this->total, 0, ',', '.'),
                'items' => $items
            ];

            // 2. Generate Message (Ideally via DeepSeek or Template)
            // Simplified for now
            $message = "Halo *{$this->name}*! 👋\n\n";
            $message .= "Pesanan Anda telah kami input.\n";
            if ($this->tableNumber) {
                $message .= "*{$tableName}*\n";
            }
            $message .= "No. Invoice: *{$sale->invoice_number}*\n";
            $message .= "Total: Rp " . number_format($this->total, 0, ',', '.') . "\n\n";
            $message .= "Mohon tunggu sebentar ya, terima kasih!";

            // 3. Normalize Phone
            $phone = preg_replace('/[^0-9]/', '', $this->phone);
            if (\Illuminate\Support\Str::startsWith($phone, '08')) {
                $phone = '628' . substr($phone, 2);
            }

            // 4. Send
            $gatewayUrl = rtrim(env('WA_GATEWAY_URL', 'http://127.0.0.1:3000'), '/');
            $endpoint = "$gatewayUrl/chat/send";

            $payload = [
                'number' => $phone,
                'message' => $message
            ];

            Http::post($endpoint, $payload);
        } catch (\Exception $e) {
            Log::error("WA Auto-Reply Failed: " . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.waiter-order.checkout')
            ->layout('components.layouts.waiter');
    }
}
