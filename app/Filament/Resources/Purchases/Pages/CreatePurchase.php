<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Purchases\PurchaseResource;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected static ?string $title = 'Tambah Pembelian';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $items = $data['items'] ?? $data['items_data'] ?? [];
        $data['total'] = collect($items)->sum(fn($i) => $i['subtotal']);
        return $data;
    }

    protected function afterCreate(): void
    {
        // Recalculate total setelah items tersimpan
        $total = $this->record->items->sum('subtotal');
        $this->record->update(['total' => $total]);

        // Kalau status "received" → tambahkan stok + update HPP produk retail
        if ($this->record->status === 'received') {
            DB::transaction(function () {
                foreach ($this->record->items as $item) {
                    $product = $item->product;

                    // Note: Stock increment is automatically handled by StockMovement boot observer

                    // Update harga pokok untuk SEMUA tipe produk (termasuk Raw Material)
                    // Ini penting agar HPP produk jadi (Produce) yang menggunakan bahan ini ikut terupdate
                    $product->update([
                        'base_price' => $item->price ?? $item->purchase_price ?? 0,
                    ]);

                    // Catat pergerakan stok
                    StockMovement::create([
                        'product_id' => $product->id,
                        'quantity' => $item->quantity,
                        'type' => 'increase',
                        'reason' => 'purchase',
                        'notes' => 'Pembelian ' . $this->record->invoice_number,
                    ]);
                }
            });
        }
    }
}
