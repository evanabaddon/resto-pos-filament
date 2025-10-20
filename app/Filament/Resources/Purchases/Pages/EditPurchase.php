<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Models\StockMovement;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Purchases\PurchaseResource;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        // 🧠 Simpan status lama sebelum update
        $this->oldStatus = $this->record->status;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $oldStatus = $this->oldStatus; // ambil status lama yang disimpan di beforeSave

        DB::transaction(function () use ($record, $oldStatus) {
            // Hitung ulang total
            $total = $record->items->sum('subtotal');
            $record->update(['total' => $total]);

            // 🧠 Jika sebelumnya "received" dan sekarang "draft" → kembalikan stok
            if ($oldStatus === 'received' && $record->status === 'draft') {
                foreach ($record->items as $item) {
                    $item->product->decrement('stock', $item->quantity);
                }
            }

            // 🧹 Hapus StockMovement lama agar tidak double
            StockMovement::where('notes', 'Pembelian ' . $record->invoice_number)->delete();

            // 📦 Jika sekarang "received" → tambah stok & buat stock movement baru
            if ($record->status === 'received') {
                foreach ($record->items as $item) {
                    $product = $item->product;
                    $product->increment('stock', $item->quantity);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'quantity' => $item->quantity,
                        'type' => 'increase',
                        'reason' => 'purchase',
                        'notes' => 'Pembelian ' . $record->invoice_number,
                    ]);
                }
            }
        });
    }
}
