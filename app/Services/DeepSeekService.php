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
        Gunakan data member untuk personalisasi, gunakan banyak EMOJI, dan pastikan gaya bahasa santai tapi sopan.
        
        WAJIB: Akhiri setiap pesan dengan signature: '- {$aiName}'";

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
    /**
     * Generate a contextual reply suggestion for WhatsApp
     */
    public function generateReplySuggestion(array $chatHistory, string $senderName, string $context = '')
    {
        $settings = app(\App\Settings\GeneralSettings::class);
        $aiName = $settings->ai_assistant_name ?? 'Business Assistant';
        $appName = $settings->app_name ?? 'Restoran Kami';
        $programName = $settings->loyalty_program_name ?? 'Member';

        // Persona & Tone Instructions from Settings
        $personaInstructions = $settings->ai_crm_system_prompt ?? '';

        // Variable replacement for persona
        $replacements = [
            '{app_name}' => $appName,
            '{program_name}' => $programName,
            '{ai_name}' => $aiName,
            '{available_promos}' => 'Promo Aktif', // Simplified for general replies
        ];
        $personaInstructions = str_replace(array_keys($replacements), array_values($replacements), $personaInstructions);

        $systemPrompt = "Anda adalah {$aiName}, AI Assistant untuk bisnis '{$appName}'.
        
        KNOWLEDGE BASE (DATA ASLI):
        {$context}

        PERAN & PERSONA:
        {$personaInstructions}
        
        TUGAS SAAT INI: 
        Berikan draf balasan WhatsApp yang tepat, membantu, dan solutif untuk pelanggan bernama '{$senderName}'.

        ATURAN PENTING:
        1. JANGAN PERNAH mengarang menu atau fitur yang tidak ada di KNOWLEDGE BASE di atas.
        2. CEK RESERVASI: Jika pelanggan bertanya tentang ketersediaan (besok, nanti malam, jam 7), bandingkan dengan 'WAKTU SISTEM SAAT INI' dan 'RESERVASI MENDATANG' di KNOWLEDGE BASE. 
           - Jika slot waktu tersebut sudah ada di daftar 'Terisi', katakan bahwa jam tersebut sudah penuh dan tawarkan jam lain.
           - Jika tidak ada di daftar 'Terisi', katakan bahwa slot tersebut KEMUNGKINAN tersedia dan arahkan untuk mengisi data reservasi.
        3. Gunakan bahasa Indonesia yang natural dan ramah sesuai persona di atas.
        4. Jika pelanggan bertanya hal teknis (stok, harga, lokasi), jawablah berdasarkan KNOWLEDGE BASE. Jika tidak ada datanya, jawab dengan sopan bahwa Anda akan mengeceknya dengan tim.
        5. Akhiri balasan dengan signature nama Anda: '- {$aiName}'.
        6. Berikan isi balasan SAJA, tanpa pembuka 'Ini balasan untuk pelanggan:'.
        7. Maksimal 2-3 kalimat agar ringkas.";

        $messages = array_merge([
            ['role' => 'system', 'content' => $systemPrompt]
        ], $chatHistory);

        $response = $this->chat($messages, ['temperature' => 0.7]); // Slightly lower temp for accuracy

        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Generate an AI-powered reservation confirmation message
     */
    public function generateReservationConfirmation(array $reservationData, string $template = '')
    {
        $settings = app(\App\Settings\GeneralSettings::class);
        $aiName = $settings->ai_assistant_name ?? 'Admin';
        $appName = $settings->app_name ?? 'Restoran Kami';
        $programName = $settings->loyalty_program_name ?? 'Member';

        // Persona & Tone Instructions from Settings
        $personaInstructions = $settings->ai_crm_system_prompt ?? '';

        // Variable replacement for persona
        $replacements = [
            '{app_name}' => $appName,
            '{program_name}' => $programName,
            '{ai_name}' => $aiName,
        ];
        $personaInstructions = str_replace(array_keys($replacements), array_values($replacements), $personaInstructions);

        $systemPrompt = "Anda adalah {$aiName}, AI Assistant untuk bisnis '{$appName}'.
        
        PERAN & PERSONA:
        {$personaInstructions}
        
        TUGAS SAAT INI: 
        Buatlah pesan WhatsApp konfirmasi reservasi yang SANGAT RAMAH, PERSONAL, dan ANTUSIAS.
        Gunakan data reservasi berikut sebagai inti informasi, tetapi rangkai dengan gaya bahasa Anda sendiri sesuai persona di atas.

        DATA RESERVASI:
        - Nama: {$reservationData['customer_name']}
        - Tanggal: {$reservationData['date']}
        - Jam: {$reservationData['time']}
        - Jumlah Tamu: {$reservationData['guests']}
        - Pre-Order Menu: " . ($reservationData['preorder_items'] ?? '-') . "
        - Permintaan Khusus: " . ($reservationData['special_requests'] ?? '-') . "

        TEMPLATE REFERENSI (Opsional - gunakan sebagai inspirasi data):
        {$template}

        ATURAN PENTING:
        1. Jangan hanya menyalin template. Buatlah terasa lebih mengalir dan 'manusiawi'.
        2. Gunakan EMOJI yang relevan agar suasana ceria.
        3. Pastikan informasi Tanggal, Jam, dan Jumlah Tamu TERTERA JELAS.
        4. Jika ada Pre-Order Menu, sebutkan kembali menu yang dipesan agar pelanggan yakin.
        5. Akhiri balasan dengan signature nama Anda: '- {$aiName}'.
        6. Berikan isi balasan SAJA.
        7. Jika ada Permintaan Khusus, sebutkan bahwa tim akan berusaha memenuhinya.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Buat konfirmasi untuk Kak {$reservationData['customer_name']}."]
        ];

        $response = $this->chat($messages, ['temperature' => 0.8]);

        return $response['choices'][0]['message']['content'] ?? '';
    }
}
