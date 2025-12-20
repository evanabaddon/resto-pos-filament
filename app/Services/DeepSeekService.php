<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.key', env('DEEPSEEK_API_KEY'));
        $this->baseUrl = config('services.deepseek.url', env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'));
    }

    /**
     * Send a chat request to DeepSeek
     */
    public function chat(array $messages, array $options = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->withOptions([
                    'verify' => config('services.deepseek.verify', true)
                ])
                ->timeout(60)
                ->post($this->baseUrl . '/chat/completions', array_merge([
                    'model' => 'deepseek-chat',
                    'messages' => $messages,
                    'temperature' => 0.7,
                ], $options));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('DeepSeek API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('DeepSeek API returned an error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('DeepSeek Request Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Specific helper for business analysis
     */
    public function analyzeBusiness(array $history, string $context = '')
    {
        $settings = app(\App\Settings\GeneralSettings::class);
        $aiName = $settings->ai_assistant_name ?? 'Asisten Pintar';

        $systemPrompt = "Anda adalah {$aiName}, Business Intelligence AI untuk restoran.
        Peran: Berikan analisis tajam, strategi profit, dan efisiensi operasional.
        Gaya: Profesional, to-the-point, tanpa basa-basi (hemat token).
        Konteks Restoran:
        {$context}";

        $messages = array_merge([
            ['role' => 'system', 'content' => $systemPrompt]
        ], $history);

        return $this->chat($messages);
    }

    /**
     * Generate a personalized WhatsApp message for a member
     */
    public function generatePersonalizedMessage(array $memberData, array $companyData = [], ?string $customPrompt = null)
    {
        $settings = app(\App\Settings\GeneralSettings::class);
        $aiName = $settings->ai_assistant_name ?? 'Admin';

        $defaultPrompt = "Anda adalah {$aiName}, CRM Specialist untuk sebuah restoran bernama '{app_name}'. 
        Tugas Anda adalah merangkai pesan WhatsApp yang SANGAT PERSONAL, hangat, dan otentik sebagai representasi dari {$aiName}.
        Gunakan data member untuk personalisasi, gunakan banyak EMOJI, dan pastikan gaya bahasa santai tapi sopan.";

        $systemPrompt = $customPrompt ?: $defaultPrompt;

        // Perform basic variable replacement in system prompt
        $replacements = [
            '{app_name}' => $companyData['app_name'] ?? 'Restoran Kami',
            '{program_name}' => $companyData['program_name'] ?? 'Member',
            '{available_promos}' => count($companyData['available_promos'] ?? []) > 0 ? 'Promo Aktif' : 'Penawaran Spesial',
            '{ai_name}' => $aiName,
        ];

        $systemPrompt = str_replace(array_keys($replacements), array_values($replacements), $systemPrompt);

        $userPrompt = "Buatlah pesan WhatsApp personal untuk pelanggan dengan data berikut:\n" .
            "MEMBER DATA: " . json_encode($memberData, JSON_PRETTY_PRINT) . "\n" .
            "BUSINESS CONTEXT: " . json_encode($companyData, JSON_PRETTY_PRINT);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        return $this->chat($messages);
    }
}
