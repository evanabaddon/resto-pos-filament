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
        $systemPrompt = "Anda adalah Asisten Bisnis Pintar untuk sebuah restoran. 
        Tugas Anda adalah memberikan analisis data, saran strategi, dan jawaban yang membantu pemilik restoran (BOS).
        Gunakan bahasa yang profesional namun ramah dan semangat.
        Berikut adalah konteks data restoran saat ini:
        {$context}";

        $messages = array_merge([
            ['role' => 'system', 'content' => $systemPrompt]
        ], $history);

        return $this->chat($messages);
    }
}
