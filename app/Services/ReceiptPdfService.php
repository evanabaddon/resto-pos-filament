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

        // Calculate dynamic height based on content
        $baseHeight = 200; // Base height untuk header, footer, dll (dalam points)
        $itemHeight = 25; // Estimasi tinggi per item (dalam points)
        $itemCount = $sale->items->count();

        // Calculate total height
        $calculatedHeight = $baseHeight + ($itemCount * $itemHeight);

        // Minimum height 283pt (10cm), maximum 1134pt (40cm)
        $height = max(283, min($calculatedHeight, 1134));

        // Set paper size untuk struk thermal
        // Width: 72mm = 204 points
        // Height: Dynamic based on content
        $pdf->setPaper([0, 0, 204, $height], 'portrait');

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
