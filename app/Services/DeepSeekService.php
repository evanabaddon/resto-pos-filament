<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected $apiKey;
    protected $baseUrl;
    protected $model;

    public function __construct()
    {
        $settings = app(\App\Settings\GeneralSettings::class);

        // Priority: Settings > Config > Env
        $this->apiKey = $settings->ai_api_key ?? config('services.deepseek.key', env('DEEPSEEK_API_KEY'));

        // Priority: Settings > Config > Env > Default
        $this->baseUrl = $settings->ai_base_url ?? config('services.deepseek.url', env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'));
        $this->model = $settings->ai_model ?? 'deepseek-chat';
    }

    /**
     * Send a chat request to the configured AI Service
     */
    public function chat(array $messages, array $options = [])
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            // OpenRouter Specific Headers (Safe for other providers too)
            if (str_contains($this->baseUrl, 'openrouter.ai')) {
                $headers['HTTP-Referer'] = config('app.url');
                $headers['X-Title'] = config('app.name');
            }

            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => config('services.deepseek.verify', true)
                ])
                ->timeout(120)
                ->post(rtrim($this->baseUrl, '/') . '/chat/completions', array_merge([
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                ], $options));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('AI Service API Error', [
                'url' => $this->baseUrl,
                'model' => $this->model,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('AI API returned an error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('AI Request Failed: ' . $e->getMessage());
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

        // Fetch Weather Data
        $weatherContext = "";
        if ($code = $settings->bmkg_location_code) {
            $service = app(\App\Services\BmkgWeatherService::class);
            $summary = $service->getForecastSummary($code);

            if (!empty($summary) && $summary !== "Data cuaca tidak tersedia saat ini.") {
                $weatherContext = "\n--- INFO PRAKIRAAN CUACA ---\n";
                $weatherContext .= $summary . "\n";
                $weatherContext .= "----------------------------\n";
            }
        }

        $systemPrompt = "Anda adalah {$aiName}, AI Assistant untuk bisnis '{$appName}'.
        
        PERAN & PERSONA:
        {$personaInstructions}
        
        DATA CUACA (WAJIB):
        {$weatherContext}
        
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
        7. Jika ada Permintaan Khusus, sebutkan bahwa tim akan berusaha memenuhinya.
        8. CEK INFO CUACA DI ATAS:
           - Periksa apakah tanggal reservasi ({$reservationData['date']}) ada di dalam daftar 'INFO PRAKIRAAN CUACA'.
           - Jika ADA dan HUJAN: Wajib ingatkan 'Jangan lupa bawa payung/jas hujan ya kak, hati-hati dijalan!'.
           - Jika ADA dan PANAS (Suhu > 30°C): Tawarkan menu segar kami.
           - Jika ADA dan CERAH/BERAWAN: Katakan 'Cuaca diprediksi bersahabat, pas banget buat kumpul-kumpul!'.
           - Jika TIDAK ADA di daftar cuaca, JANGAN menyebutkan soal cuaca sama sekali.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Buat konfirmasi untuk Kak {$reservationData['customer_name']}."]
        ];

        Log::info('DeepSeek Reservation Prompt:', ['system' => $systemPrompt, 'weather_context_debug' => $weatherContext]);

        $response = $this->chat($messages, ['temperature' => 0.8]);

        return $response['choices'][0]['message']['content'] ?? '';
    }
    /**
     * Generate an AI-powered stock forecast and restocking recommendation
     */
    public function forecastStock(array $consumptionData, int $forecastDays = 7)
    {
        $settings = app(\App\Settings\GeneralSettings::class);
        $aiName = $settings->ai_assistant_name ?? 'Business Assistant';
        $appName = $settings->app_name ?? 'Restoran Kami';

        $systemPrompt = "Anda adalah {$aiName}, Pakar Manajemen Inventaris untuk '{$appName}'.
        
        TUGAS:
        Diberikan data konsumsi bahan baku historis dan stok saat ini, berikan prediksi kebutuhan stok untuk {$forecastDays} hari ke depan.
        
        FORMAT OUTPUT (JSON):
        Anda WAJIB memberikan jawaban dalam format JSON murni agar bisa diparsing oleh sistem:
        {
            \"analysis\": \"Analisis singkat tentang tren konsumsi (1-2 kalimat).\",
            \"recommendations\": [
                {
                    \"product_id\": 1,
                    \"product_name\": \"Nama Produk\",
                    \"predicted_need\": 10.5,
                    \"suggested_restock\": 5.0,
                    \"urgency\": \"high|medium|low\",
                    \"reason\": \"Alasan singkat\"
                }
            ]
        }
        
        ATURAN:
        1. Predicted_need: Estimasi total pemakaian untuk {$forecastDays} hari ke depan berdasarkan rata-rata harian.
        2. Suggested_restock: (Predicted_need + buffer 20%) - Current_stock. Jika hasilnya <= 0, berikan 0.
        3. Urgency: 'high' jika stok saat ini < safety stock (buffer 20% dari predicted_need), 'medium' jika stok menipis, 'low' jika aman.
        4. Berikan data JSON SAJA tanpa penjelasan tambahan di luar JSON.";

        $userPrompt = "Berikut adalah data konsumsi untuk dianalisa:\n" . json_encode($consumptionData, JSON_PRETTY_PRINT);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        $response = $this->chat($messages, [
            'temperature' => 0.3, // Lower temperature for structured output
            'response_format' => ['type' => 'json_object'] // Ensure JSON if supported by model version
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '{}';

        // More robust JSON extraction
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $content = $matches[0];
        }

        return json_decode($content, true);
    }

    /**
     * Generate AI-powered advice for Menu Engineering matrix
     */
    public function analyzeMenuMatrix(array $matrixData)
    {
        $settings = app(\App\Settings\GeneralSettings::class);
        $aiName = $settings->ai_assistant_name ?? 'Business Assistant';
        $appName = $settings->app_name ?? 'Restoran Kami';

        $systemPrompt = "Anda adalah {$aiName}, Konsultan Strategi F&B untuk '{$appName}'.
        
        TUGAS:
        Diberikan data 'Menu Engineering Matrix' yang berisi klasifikasi Unit Unggulan, Unit Andalan, Unit Potensial, dan Unit Kurang Berkembang, berikan saran strategis untuk meningkatkan profitabilitas menu.
        
        DEFINISI:
        - UNIT UNGGULAN (High Profit, High Popularity): Pertahankan kualitas dan konsistensi.
        - UNIT ANDALAN (High Popularity, Low Profit): Cari cara kurangi biaya bahan atau naikkan harga sedikit (evaluasi porsi).
        - UNIT POTENSIAL (High Profit, Low Popularity): Perlu promosi lebih, ubah nama menu, atau pindahkan posisi di buku menu.
        - UNIT KURANG BERKEMBANG (Low Profit, Low Popularity): Pertimbangkan untuk dihapus dari menu atau re-branding total.
        
        FORMAT OUTPUT (JSON):
        {
            \"overall_analysis\": \"Analisis singkat kondisi menu saat ini.\",
            \"strategic_advice\": [
                {
                    \"product_name\": \"Nama Produk\",
                    \"category\": \"UNIT UNGGULAN|UNIT ANDALAN|UNIT POTENSIAL|UNIT KURANG BERKEMBANG\",
                    \"advice\": \"Saran spesifik dan teknis (misal: naikkan harga 10% atau kurangi porsi 15%)\"
                }
            ],
            \"top_priorities\": [\"Daftar 3 hal paling mendesak yang harus dilakukan\"]
        }
        
        ATURAN:
        1. Berikan saran yang KONKRIT dan TEKNIS.
        2. Berikan data JSON SAJA tanpa penjelasan tambahan di luar JSON.";

        // Limit matrix data to avoid token limits
        // Prioritize items with sales, then take top 50
        $limitedItems = collect($matrixData['items'])
            ->sortByDesc('popularity')
            ->take(50)
            ->values()
            ->toArray();

        $dataForAi = [
            'items' => $limitedItems,
            'averages' => $matrixData['averages'] ?? []
        ];

        $userPrompt = "Berikut adalah data Menu Matrix untuk dianalisa (Terbatas pada 50 menu terpopuler):\n" . json_encode($dataForAi, JSON_PRETTY_PRINT);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];

        $response = $this->chat($messages, [
            'temperature' => 0.4,
            'response_format' => ['type' => 'json_object']
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '{}';

        // More robust JSON extraction
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $content = $matches[0];
        }

        return json_decode($content, true);
    }
}
