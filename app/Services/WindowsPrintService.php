<?php
// App/Services/WindowsPrintService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WindowsPrintService
{
    /**
     * Print ke Windows print server
     */
    public function printToWindows(string $content, string $printerName = 'BAR'): array
    {
        try {
            // Ganti dengan IP Windows Anda
            $windowsIp = config('services.print_server.ip', 'YOUR_WINDOWS_IP');
            
            $response = Http::timeout(10)->asForm()->post(
                "http://{$windowsIp}:8000/print-server.php",
                [
                    'key' => '12345',
                    'content' => $content,
                    'printer' => $printerName
                ]
            );

            $result = $response->json();
            
            if (isset($result['success']) && $result['success']) {
                Log::info("✅ Print successful to Windows: {$printerName}");
                return ['success' => true, 'message' => $result['message'] ?? 'Printed'];
            } else {
                throw new \Exception($result['error'] ?? 'Print failed');
            }
            
        } catch (\Exception $e) {
            Log::error("❌ Windows print failed: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}