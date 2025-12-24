<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppService
{
    protected string $gatewayUrl;

    public function __construct()
    {
        $this->gatewayUrl = rtrim(env('WA_GATEWAY_URL', 'http://127.0.0.1:3000'), '/');
    }

    /**
     * Send a WhatsApp message via the internal gateway.
     */
    public function sendMessage(string $phone, string $message): bool
    {
        try {
            // 1. Normalize Phone (08 -> 628)
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (Str::startsWith($phone, '08')) {
                $phone = '628' . substr($phone, 2);
            } elseif (Str::startsWith($phone, '8')) {
                $phone = '628' . $phone;
            }

            // 2. Prepare Payload
            $payload = [
                'number' => $phone,
                'message' => $message
            ];

            // 3. Send using /chat/send
            $endpoint = "{$this->gatewayUrl}/chat/send";
            $response = Http::timeout(15)->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp message sent successfully to {$phone}");
                return true;
            }

            Log::error("WhatsApp API Error to {$phone}: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp Service Exception: " . $e->getMessage());
            return false;
        }
    }
}
