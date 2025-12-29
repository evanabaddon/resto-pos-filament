<?php

namespace App\Jobs;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use App\Services\OrderPrintService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class PrintOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sale;
    protected $newItems;
    protected $isUpdate;

    /**
     * Create a new job instance.
     *
     * @param Sale $sale
     * @param array $newItems Only if this is an update print
     * @param bool $isUpdate
     */
    public function __construct(Sale $sale, array $newItems = [], bool $isUpdate = false)
    {
        $this->sale = $sale;
        $this->newItems = $newItems;
        $this->isUpdate = $isUpdate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("🖨️ PrintOrderJob Processing: Sale #{$this->sale->invoice_number}");

            $printService = new OrderPrintService();

            if ($this->isUpdate && !empty($this->newItems)) {
                $printService->printNewItemsOnly($this->sale, $this->newItems);
            } else {
                $printService->printOrderByProductType($this->sale);
            }

            Log::info("✅ PrintOrderJob Completed");

        } catch (\Exception $e) {
            Log::error("❌ PrintOrderJob Failed: " . $e->getMessage());
            // Optional: Notification facade or DB logging for failure
        }
    }
}
