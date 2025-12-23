<?php

namespace App\Livewire\SelfOrder;

use App\Models\CashSession;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Table;
use App\Services\DeepSeekService;
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
                // Cari atau Buat Member Baru
                $member = Member::firstOrCreate(
                    ['phone' => $this->phone],
                    ['name' => $this->name, 'joined_at' => now()]
                );
                $memberId = $member->id;
            }

            // 1. Create Sale (Order)
            $sale = Sale::create([
                'invoice_number' => 'INV-' . date('YmdHis') . '-' . rand(100, 999),
                'customer_name' => $this->name,
                'table_number' => $tableNumber,
                'user_id' => $userId, // Assign to active cashier
                'member_id' => $memberId, // 🔹 Assign Member
                'cash_session_id' => $sessionId, // Assign to active session
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
                    'status' => 'pending', // KDS Item Status
                    'notes' => $item['note'] ?? null
                ]);
            }

            // 3. WhatsApp Notification
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
            // Use try-catch for AI generation to prevent blocking if AI is down
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

            // 3. Normalize Phone (08 -> 628)
            $phone = preg_replace('/[^0-9]/', '', $this->phone);
            if (\Illuminate\Support\Str::startsWith($phone, '08')) {
                $phone = '628' . substr($phone, 2);
            }

            // 4. Send using /chat/send (Standard WA Gateway Endpoint)
            $gatewayUrl = rtrim(env('WA_GATEWAY_URL', 'http://127.0.0.1:3000'), '/');
            $endpoint = "$gatewayUrl/chat/send";

            // Prepare Payload
            $payload = [
                'number' => $phone,
                'message' => $message
            ];

            Http::post($endpoint, $payload);
        } catch (\Exception $e) {
            // Log error but don't fail the order
            Log::error("WA Auto-Reply Failed: " . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.self-order.checkout')
            ->layout('components.layouts.mobile');
    }
}
