<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerDisplayUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $saleId;
    public $action;
    public $customerName;
    public $items;
    public $subtotal;
    public $tax;
    public $total;
    public $paymentMethod;
    public $amountPaid;

    /**
     * Create a new event instance.
     *
     * @param string $action Action type: 'loaded', 'updated', 'paid', 'idle'
     * @param int|null $saleId Sale ID (null for idle)
     * @param array $data Additional data (customerName, items, totals, etc)
     */
    public function __construct($action, $saleId = null, $data = [])
    {
        $this->action = $action;
        $this->saleId = $saleId;
        $this->customerName = $data['customerName'] ?? null;
        $this->items = $data['items'] ?? [];
        $this->subtotal = $data['subtotal'] ?? 0;
        $this->tax = $data['tax'] ?? 0;
        $this->total = $data['total'] ?? 0;
        $this->paymentMethod = $data['paymentMethod'] ?? null;
        $this->amountPaid = $data['amountPaid'] ?? 0;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('customer-display'),
        ];
    }
}
