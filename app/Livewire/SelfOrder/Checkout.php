<?php

namespace App\Livewire\SelfOrder;

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

class Checkout extends Component
{
    public $name = '';
    public $phone = ''; // WhatsApp
    public $cartItems = [];
    public $subtotal = 0;
    public $tax = 0;
    public $total = 0;
    public $tableId;

    public function mount()
    {
        $this->tableId = session('table_id');
        if (!$this->tableId) {
            return redirect()->route('order.menu');
        }

        $this->cartItems = session()->get('cart', []);

        if (empty($this->cartItems)) {
            return redirect()->route('order.menu');
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
        ]);

        // DB Transaction
        DB::transaction(function () {
            // Get Table Info
            $table = Table::find($this->tableId);
            $tableNumber = $table ? $table->name : 'Unknown';

            // Find Active Cash Session (Latest Open)
            $activeSession = CashSession::where('status', 'open')
                ->latest()
                ->first();

            $userId = $activeSession ? $activeSession->user_id : null;
            $sessionId = $activeSession ? $activeSession->id : null;

            // 🔹 Handle Member Registration / Lookup
            $memberId = null;
            if ($this->phone && $this->name) {
                // Feature: Normalize Phone Number (08 -> 62)
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
                        'tier_id' => $defaultTier ? $defaultTier->id : 1, // Fallback to 1
                    ]);
                }
                $memberId = $member->id;
            }

            // Prepare data for OrderService
            $saleData = [
                'cash_session_id' => $sessionId,
                'user_id' => $userId,
                'invoice_number' => 'INV-' . date('YmdHis') . '-' . rand(100, 999),
                'customer_name' => $this->name,
                'table_number' => $tableNumber,
                'order_type' => 'Dine In',
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'discount' => 0,
                'final_total' => $this->total,
                'member_id' => $memberId,
            ];

            $items = collect($this->cartItems)->map(function ($item) {
                return [
                    'product_id' => $item['id'],
                    'name' => $item['name'],
                    'quantity' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['qty'],
                    'notes' => $item['note'] ?? '',
                ];
            })->toArray();

            // Use OrderService to handle stock deduction
            $orderService = new \App\Services\OrderService();
            $sale = $orderService->processOrder($saleData, $items, false);

            // 3. Auto Print to Kitchen/Bar
            try {
                $printService = new \App\Services\OrderPrintService();
                $printService->printOrderByProductType($sale);
            } catch (\Exception $e) {
                Log::error("Self Order Auto Print Failed: " . $e->getMessage());
            }

            // 4. Notify POS Users (Database Notification)
            $recipients = \App\Models\User::whereIn('role', [
                \App\Enums\UserRole::SuperAdmin,
                \App\Enums\UserRole::Admin,
                \App\Enums\UserRole::Cashier,
                \App\Enums\UserRole::Waiter
            ])->get();

            Notification::make()
                ->title('New Self Order')
                ->body("Order #{$sale->invoice_number} dari Meja {$tableNumber}")
                ->success() // Or info
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

        session()->forget('cart');
        $this->dispatch('cart-updated');
        session()->flash('success_order', true);

        // Redirect to a simple status page or stay here with success message
        // For MVP, simple success state
        return redirect()->route('order.menu')->with('order_placed', 'Pesanan berhasil dibuat! Mohon tunggu.');
    }

    protected function sendWhatsAppNotification($sale)
    {
        try {
            // 1. Prepare Data for AI
            $tableName = $sale->table_number ?? 'Meja #' . $this->tableId;
            $items = collect($this->cartItems)->map(fn($item) => "{$item['qty']}x {$item['name']}")->toArray();

            $orderData = [
                'customer_name' => $this->name,
                'invoice_number' => $sale->invoice_number,
                'table_number' => $tableName,
                'total_formatted' => 'Rp ' . number_format($this->total, 0, ',', '.'),
                'items' => $items
            ];

            // 2. Generate AI Message
            $aiService = app(DeepSeekService::class);
            try {
                $message = $aiService->generateOrderConfirmation($orderData);
            } catch (\Exception $e) {
                Log::error("AI Generation Failed: " . $e->getMessage());
                $message = "";
            }

            // Fallback if AI fails or returns empty
            if (empty($message)) {
                $message = "Halo *{$this->name}*! 👋\n\n";
                $message .= "Pesanan Anda di *{$tableName}* telah kami terima.\n";
                $message .= "No. Invoice: *{$sale->invoice_number}*\n";
                $message .= "Total: Rp " . number_format($this->total, 0, ',', '.') . "\n\n";
                $message .= "Mohon tunggu sebentar ya, terima kasih!";
            }

            // 3. Send via centralized WhatsAppService
            app(\App\Services\WhatsAppService::class)->sendMessage($this->phone, $message);
        } catch (\Exception $e) {
            Log::error("WA Auto-Reply Failed: " . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.self-order.checkout')
            ->layout('components.layouts.mobile');
    }
}
