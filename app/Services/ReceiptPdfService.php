<?php

namespace App\Services;

use App\Models\Sale;
use App\Settings\GeneralSettings;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptPdfService
{
    /**
     * Generate PDF untuk struk pembayaran
     */
    public function generatePdf(Sale $sale): \Barryvdh\DomPDF\PDF
    {
        // Load relations
        $sale->load(['items.product', 'paymentMethod', 'user']);

        // Get settings
        $settings = app(GeneralSettings::class);

        // Generate PDF
        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'settings' => $settings
        ]);

        // Set paper size untuk struk thermal
        // Width: 72mm = 204 points
        // Height: Minimal untuk thermal receipt (sekitar 10cm = 283 points)
        // DomPDF akan extend jika content lebih panjang
        $pdf->setPaper([0, 0, 204, 283], 'portrait');

        return $pdf;
    }

    /**
     * Download PDF
     */
    public function download(Sale $sale)
    {
        $pdf = $this->generatePdf($sale);
        $filename = "struk-{$sale->invoice_number}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Stream PDF (view in browser)
     */
    public function stream(Sale $sale)
    {
        $pdf = $this->generatePdf($sale);
        $filename = "struk-{$sale->invoice_number}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Save PDF to storage
     */
    public function save(Sale $sale, string $path = null): string
    {
        $pdf = $this->generatePdf($sale);

        if (!$path) {
            $path = storage_path("app/receipts/struk-{$sale->invoice_number}.pdf");
        }

        // Ensure directory exists
        $directory = dirname($path);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdf->save($path);

        return $path;
    }
}
