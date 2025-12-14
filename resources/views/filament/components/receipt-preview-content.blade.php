@php
    $sale = $sale ?? null;
@endphp

@if($sale)
<div class="p-4 bg-white rounded-lg">
    <div class="text-sm max-h-80 overflow-y-auto" style="font-family: 'Courier New', monospace; font-size: 14px;">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 16px;">
            <h1 style="font-weight: bold; font-size: 18px; text-transform: uppercase; margin: 0 0 8px 0;">STRUK PEMBAYARAN</h1>
            <p style="font-size: 14px; margin: 0 0 4px 0;">{{ config('app.name') }}</p>
            <p style="font-size: 12px; margin: 0; color: #6b7280;">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
        </div>
        
        <div style="border-top: 1px dashed #d1d5db; margin: 8px 0;"></div>
        
        <!-- Info Transaksi -->
        <div style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>No. Transaksi:</span>
                <span style="font-weight: 600;">{{ $sale->invoice_number }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>Kasir:</span>
                <span>{{ $sale->user->name ?? 'System' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>Customer:</span>
                <span>{{ $sale->customer_name ?? 'Umum' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Status:</span>
                <span style="font-weight: 600; text-transform: uppercase;">{{ $sale->status }}</span>
            </div>
        </div>
        
        <div style="border-top: 1px dashed #d1d5db; margin: 8px 0;"></div>
        
        <!-- Items -->
        <div style="margin-bottom: 16px;">
            @foreach($sale->items as $item)
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; margin-bottom: 2px;">{{ $item->product->name ?? 'Produk' }}</div>
                        <div style="font-size: 11px; color: #6b7280;">
                            {{ $item->quantity }} × Rp{{ number_format($item->unit_price, 0, ',', '.') }}
                        </div>
                    </div>
                    <div style="font-weight: 600;">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</div>
                </div>
                @if(!$loop->last)
                    <div style="border-top: 1px dashed #f3f4f6; margin: 4px 0;"></div>
                @endif
            @endforeach
        </div>
        
        <div style="border-top: 1px dashed #d1d5db; margin: 8px 0;"></div>
        
        <!-- Summary -->
        <div style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>Subtotal:</span>
                <span>Rp{{ number_format($sale->subtotal, 0, ',', '.') }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>Pajak (10%):</span>
                <span>Rp{{ number_format($sale->tax, 0, ',', '.') }}</span>
            </div>
            @if($sale->discount > 0)
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px; color: #059669;">
                    <span>Potongan:</span>
                    <span>- Rp{{ number_format($sale->discount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div style="border-top: 1px solid #d1d5db; padding-top: 4px;">
                <div style="display: flex; justify-content: space-between; font-weight: bold;">
                    <span>TOTAL:</span>
                    <span>Rp{{ number_format($sale->final_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
        
        <div style="border-top: 1px dashed #d1d5db; margin: 8px 0;"></div>
        
        <!-- Payment Info -->
        <div style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>Metode Bayar:</span>
                <span style="font-weight: 600;">{{ $sale->paymentMethod->name ?? 'Cash' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>Dibayar:</span>
                <span>Rp{{ number_format($sale->amount_paid, 0, ',', '.') }}</span>
            </div>
            @if(($sale->paymentMethod->code ?? 'cash') === 'cash')
                @php $change = $sale->amount_paid - $sale->final_total; @endphp
                @if($change > 0)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Kembali:</span>
                        <span style="font-weight: 600;">Rp{{ number_format($change, 0, ',', '.') }}</span>
                    </div>
                @endif
            @endif
            <div style="display: flex; justify-content: space-between; {{ $sale->status === 'completed' ? 'color: #059669;' : 'color: #dc2626;' }}">
                <span>Status Bayar:</span>
                <span style="font-weight: 600;">{{ $sale->status === 'completed' ? 'LUNAS' : 'BELUM LUNAS' }}</span>
            </div>
        </div>
        
        <div style="border-top: 1px dashed #d1d5db; margin: 8px 0;"></div>
        
        <!-- Footer -->
        <div style="text-align: center; font-size: 12px;">
            <p style="margin: 0 0 8px 0;">Terima kasih atas kunjungan Anda</p>
            @if($sale->status != 'completed')
                <p style="color: #dc2626; font-weight: 600; margin: 0;">*** MENUNGGU PEMBAYARAN ***</p>
            @else
                <p style="font-weight: 600; margin: 0;">*** SELAMAT MENIKMATI ***</p>
            @endif
        </div>
    </div>
</div>
@else
<div class="p-4 text-center text-red-600">
    <p>Data transaksi tidak ditemukan</p>
</div>
@endif