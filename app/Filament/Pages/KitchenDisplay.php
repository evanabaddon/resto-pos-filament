<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;

class KitchenDisplay extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Layar Dapur (KDS)';
    protected static ?string $title = 'Layar Dapur (KDS)';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected string $view = 'filament.pages.kitchen-display';

    protected static string $layout = 'layouts.kds-layout';

    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_kds;
    }

    public function mount(): void
    {
        $settings = app(\App\Settings\GeneralSettings::class);
        if (!$settings->enable_kds) {
            abort(403, 'KDS Module is disabled.');
        }
    }

    public $activeTab = 'kitchen'; // kitchen | bar

    public function getViewData(): array
    {
        // 1. Fetch all items matching the department and status
        $query = \App\Models\SaleItem::query()
            ->with(['product', 'sale'])
            ->where('created_at', '>=', now()->subHours(24)) // Look back 24 hours to cover late syncs
            ->whereHas('sale', function ($q) {
                // Ensure the sale itself is still relevant (e.g. not paid if that's the logic)
                // But for KDS, we usually care if items are not yet served.
            });

        if ($this->activeTab === 'ready') {
            $query->where('status', 'ready');
        } else {
            $targetTypes = match ($this->activeTab) {
                'bar' => ['bar'],
                'retail' => ['retail'],
                default => ['produced'], // kitchen
            };
            $query->whereIn('status', ['pending', 'cooking'])
                ->whereHas('product', fn($q) => $q->whereIn('type', $targetTypes));
        }

        $items = $query->oldest()->get();

        // 2. Group items into "Task Batches"
        // A batch is items from the same SALE added at the same TIME (or same second)
        $batches = $items->groupBy(function ($item) {
            return $item->sale_id . '_' . $item->created_at->format('Y-m-d H:i:s');
        })->map(function ($group) {
            $first = $group->first();
            return (object) [
                'id' => $first->sale_id . '_' . $first->created_at->timestamp,
                'sale' => $first->sale,
                'items' => $group,
                'created_at' => $first->created_at,
            ];
        })->values();

        return [
            'batches' => $batches,
        ];
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function markItemStatus($itemId, $status)
    {
        $item = \App\Models\SaleItem::find($itemId);
        if ($item) {
            $item->update(['status' => $status]);

            // If item is ready, check if the ENTIRE BATCH is now ready
            if ($status === 'ready') {
                $sale = $item->sale;

                // Define Department Types
                $targetTypes = match ($this->activeTab) {
                    'bar' => ['bar'],
                    'retail' => ['retail'],
                    default => ['produced'], // kitchen
                };

                // Check if there are any remaining items in this batch (same sale + same time) 
                // that are NOT ready or served.
                $remainingItems = $sale->items()
                    ->where('created_at', $item->created_at) // Batch grouping
                    ->whereIn('status', ['pending', 'cooking']) // Still working
                    ->whereHas('product', fn($q) => $q->whereIn('type', $targetTypes))
                    ->count();

                if ($remainingItems === 0) {
                    // 🎉 ALL ITEMS READY -> Trigger "Batch Ready" Notification
                    $this->sendBatchReadyNotification($sale, $item->created_at);
                } else {
                    // 🔔 Just this item is ready
                    $this->sendItemReadyNotification($item, $sale);
                }
            }
        }
    }

    protected function sendBatchReadyNotification($sale, $batchTimestamp)
    {
        $recipient = $sale->user ?? auth()->user();
        if (!$recipient)
            return;

        $departmentName = match ($this->activeTab) {
            'bar' => 'Bar',
            'retail' => 'Ritel',
            default => 'Dapur',
        };

        $customerInfo = $sale->customer_name ?? 'Pelanggan';
        $locationInfo = $sale->table_number ? "Meja #{$sale->table_number}" : $sale->order_type;
        $titleText = "Pesanan {$departmentName} Siap!";

        // Fetch items for specific message details
        $itemsInBatch = $sale->items()
            ->where('created_at', $batchTimestamp)
            ->get();

        if ($itemsInBatch->count() <= 2) {
            $itemNames = $itemsInBatch->map(fn($i) => $i->product_name ?? $i->product->name ?? 'Unknown Item')->implode(', ');
            $bodyText = "{$itemNames} untuk {$customerInfo} ({$locationInfo}) telah siap.";
        } else {
            $bodyText = "Sebagian pesanan {$departmentName} untuk {$customerInfo} ({$locationInfo}) telah siap.";
        }

        $this->sendNotification($recipient, $titleText, $bodyText, 'heroicon-o-check-badge', $sale->id);
        \Illuminate\Support\Facades\Log::info("KDS: Auto-Batch Ready Detected", ['sale' => $sale->id]);
    }

    protected function sendItemReadyNotification($item, $sale)
    {
        $recipient = $sale->user ?? auth()->user();
        if (!$recipient)
            return;

        $departmentName = match ($this->activeTab) {
            'bar' => 'Bar',
            'retail' => 'Ritel',
            default => 'Dapur',
        };

        $itemName = $item->product_name ?? $item->product->name ?? 'Unknown Item';
        $customerInfo = $sale->customer_name ?? 'Pelanggan';
        $locationInfo = $sale->table_number ? "Meja #{$sale->table_number}" : $sale->order_type;

        $titleText = "Pesanan {$departmentName} Siap!";
        $bodyText = "{$itemName} untuk {$customerInfo} ({$locationInfo}) sudah siap.";

        $this->sendNotification($recipient, $titleText, $bodyText, 'heroicon-o-check-circle', $sale->id);
    }

    protected function sendNotification($recipient, $title, $body, $icon, $saleId)
    {
        try {
            // 1. Toast
            \Filament\Notifications\Notification::make()
                ->title($title)
                ->body($body)
                ->icon($icon)
                ->color('success')
                ->send();

            // 2. Database
            $data = [
                'format' => 'filament',
                'title' => $title,
                'body' => $body,
                'icon' => $icon,
                'color' => 'success',
                'duration' => 'persistent',
                'status' => 'success',
                'view' => null,
                'viewData' => [],
                'sale_id' => $saleId,
                'actions' => [
                    [
                        'name' => 'Lihat',
                        'label' => 'Lihat',
                        'url' => '#',
                        'color' => 'primary',
                        'view' => 'filament::components.button.index',
                        'isOutlined' => false,
                        'isDisabled' => false,
                        'size' => 'xs',
                        'tag' => 'button',
                    ]
                ]
            ];

            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'type' => 'Filament\Notifications\DatabaseNotification',
                'notifiable_type' => get_class($recipient),
                'notifiable_id' => $recipient->id,
                'data' => json_encode($data),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('KDS: Notification Failed: ' . $e->getMessage());
        }
    }

    public function markBatchReady($saleId, $timestamp)
    {
        $sale = \App\Models\Sale::find($saleId);
        if ($sale) {
            $targetTypes = match ($this->activeTab) {
                'bar' => ['bar'],
                'retail' => ['retail'],
                default => ['produced'], // kitchen
            };

            $departmentName = match ($this->activeTab) {
                'bar' => 'Bar',
                'retail' => 'Ritel',
                default => 'Dapur',
            };

            $query = $sale->items()
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') = ?", [$timestamp])
                ->where('status', '!=', 'served')
                ->whereHas('product', fn($q) => $q->whereIn('type', $targetTypes));

            $itemsToMark = $query->get();
            $query->update(['status' => 'ready']);

            if ($itemsToMark->isEmpty())
                return;

            // NOTIFY WAITER
            $recipient = $sale->user ?? auth()->user();

            \Illuminate\Support\Facades\Log::info("KDS: Batch Ready ($departmentName)", ['recipient' => $recipient?->id, 'sale' => $sale->id, 'batch' => $timestamp]);

            if ($recipient) {
                try {
                    $customerInfo = $sale->customer_name ?? 'Pelanggan';
                    $locationInfo = $sale->table_number ? "Meja #{$sale->table_number}" : $sale->order_type;
                    $titleText = "Pesanan {$departmentName} Siap!";

                    // Mention items in batch if small, otherwise summary
                    if ($itemsToMark->count() <= 2) {
                        $itemNames = $itemsToMark->map(fn($i) => $i->product_name ?? $i->product->name ?? 'Unknown Item')->implode(', ');
                        $bodyText = "{$itemNames} untuk {$customerInfo} ({$locationInfo}) telah siap.";
                    } else {
                        $bodyText = "Sebagian pesanan {$departmentName} untuk {$customerInfo} ({$locationInfo}) telah siap.";
                    }

                    // 1. Send Flash/Toast Notification
                    \Filament\Notifications\Notification::make()
                        ->title($titleText)
                        ->body($bodyText)
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->send();

                    // 2. Manual DB Insert
                    $data = [
                        'format' => 'filament',
                        'title' => $titleText,
                        'body' => $bodyText,
                        'icon' => 'heroicon-o-check-badge',
                        'color' => 'success',
                        'duration' => 'persistent',
                        'status' => 'success',
                        'view' => null,
                        'viewData' => [],
                        'sale_id' => $sale->id,
                        'actions' => [
                            [
                                'name' => 'Lihat',
                                'label' => 'Lihat',
                                'url' => '#',
                                'color' => 'primary',
                                'view' => 'filament::components.button.index',
                                'isOutlined' => false,
                                'isDisabled' => false,
                                'size' => 'xs',
                                'tag' => 'button',
                            ]
                        ]
                    ];

                    \Illuminate\Support\Facades\DB::table('notifications')->insert([
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'type' => 'Filament\Notifications\DatabaseNotification',
                        'notifiable_type' => get_class($recipient),
                        'notifiable_id' => $recipient->id,
                        'data' => json_encode($data),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('KDS: Notification Failed: ' . $e->getMessage());
                }
            }
        }
    }

    public function markBatchServed($saleId, $timestamp)
    {
        $sale = \App\Models\Sale::find($saleId);
        if ($sale) {
            $sale->items()
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') = ?", [$timestamp])
                ->where('status', 'ready')
                ->update(['status' => 'served']);
            \Illuminate\Support\Facades\Log::info("KDS: Batch Served", ['sale' => $sale->id, 'batch' => $timestamp]);
        }
    }
}
