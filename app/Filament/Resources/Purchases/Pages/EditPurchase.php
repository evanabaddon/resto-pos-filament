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

    protected $oldStatus;
    protected $oldItemsSnapshot = [];

    protected function beforeSave(): void
    {
        // 🧠 Simpan status lama dan snapshot items sebelum update
        $this->oldStatus = $this->record->status;

        // Simpan snapshot item lama (id produk & qty) untuk keperluan revert
        $this->oldItemsSnapshot = $this->record->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ];
        })->toArray();
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $oldStatus = $this->oldStatus;
        $oldItems = $this->oldItemsSnapshot;

        DB::transaction(function () use ($record, $oldStatus, $oldItems) {
            // Hitung ulang total
            $total = $record->items->sum('subtotal');
            $record->update(['total' => $total]);

            // 1. REVERT Old State (Jika status sebelumnya 'received')
            //    Kita harus mengembalikan stok berdasarkan snapshot items LAMA
            if ($oldStatus === 'received') {
                foreach ($oldItems as $itemData) {
                    // Cari produk terkait
                    $product = \App\Models\Product::find($itemData['product_id']);
                    if ($product) {
                        $product->decrement('stock', $itemData['quantity']);
                    }
                }

                // Hapus log movement lama
                StockMovement::where('notes', 'Pembelian ' . $record->invoice_number)->delete();
            }

            // 2. APPLY New State (Jika status sekarang 'received')
            //    Kita tambahkan stok berdasarkan items BARU (yang sudah tersimpan di DB)
            if ($record->status === 'received') {
                foreach ($record->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        // Stock increment is handled by StockMovement boot obsever

                        // Update harga pokok untuk SEMUA tipe produk (Retail, Raw, etc)
                        $newBasePrice = $item->price ?? 0;

                        // Logic Konversi Unit (Jika unit beli != unit stok)
                        if ($item->unit_id && $product->unit_id && $item->unit_id != $product->unit_id) {
                            try {
                                $converter = app(\App\Services\UnitConversionService::class);
                                $conversionFactor = $converter->convert(1, $item->unit_id, $product->unit_id);

                                if ($conversionFactor > 0) {
                                    $newBasePrice = $newBasePrice / $conversionFactor;
                                }
                            } catch (\Exception $e) {
                                \Log::error("Unit conversion error for Product ID {$product->id} in EditPurchase: " . $e->getMessage());
                            }
                        }

                        $product->update([
                            'base_price' => $newBasePrice,
                        ]);

                        // Catat movement baru
                        StockMovement::create([
                            'product_id' => $product->id,
                            'quantity' => $item->quantity,
                            'type' => 'increase',
                            'reason' => 'purchase',
                            'notes' => 'Pembelian ' . $record->invoice_number,
                        ]);
                    }
                }
            }
        });
    }
}
