<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Services\DeepSeekService;
use App\Settings\GeneralSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReEngagementCommand extends Command
{
    protected $signature = 'loyalty:re-engage {--dry-run : Run without sending messages}';

    protected $description = 'Re-engage inactive members (>30 days) with personalized WhatsApp messages';

    public function handle(DeepSeekService $aiService, GeneralSettings $settings): int
    {
        $this->info('🔍 Finding inactive members...');

        // Find members who haven't visited in >30 days
        $inactiveMembers = Member::where('last_visit_at', '<', now()->subDays(30))
            ->where(function ($query) {
                // Either never contacted, or last contacted >7 days ago (don't spam)
                $query->whereNull('last_contacted_at')
                    ->orWhere('last_contacted_at', '<', now()->subDays(7));
            })
            ->get();

        if ($inactiveMembers->isEmpty()) {
            $this->info('✅ No inactive members found.');
            return self::SUCCESS;
        }

        $this->info(sprintf('📊 Found %d inactive members', $inactiveMembers->count()));

        $sent = 0;
        $failed = 0;

        foreach ($inactiveMembers as $member) {
            $daysSinceVisit = $member->last_visit_at?->diffInDays(now()) ?? 999;

            $this->line(sprintf(
                '  • %s (Last visit: %d days ago)',
                $member->name,
                $daysSinceVisit
            ));

            // Generate personalized message using AI
            $message = $this->generateReEngagementMessage($member, $aiService, $settings);

            if ($this->option('dry-run')) {
                $this->comment('    [DRY RUN] Would send: ' . substr($message, 0, 100) . '...');
                continue;
            }

            try {
                // Queue WhatsApp message
                $this->sendWhatsAppMessage($member->phone, $message);

                // Update last_contacted_at
                $member->update(['last_contacted_at' => now()]);

                $sent++;
                $this->info('    ✅ Message queued');
            } catch (\Exception $e) {
                $failed++;
                $this->error('    ❌ Failed: ' . $e->getMessage());
                Log::error('Re-engagement failed', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->newLine();
        $this->info(sprintf('✅ Completed: %d sent, %d failed', $sent, $failed));

        return self::SUCCESS;
    }

    private function generateReEngagementMessage(Member $member, DeepSeekService $aiService, GeneralSettings $settings): string
    {
        $daysSinceVisit = $member->last_visit_at?->diffInDays(now()) ?? 999;
        $appName = $settings->app_name ?? 'Resto';
        $aiName = $settings->ai_assistant_name ?? 'Sarah';
        $programName = $settings->loyalty_program_name ?? 'Member';

        // Get CRM persona from settings
        $personaInstructions = $settings->ai_crm_system_prompt ?? '';

        // Variable replacement for persona
        $replacements = [
            '{app_name}' => $appName,
            '{program_name}' => $programName,
            '{ai_name}' => $aiName,
            '{available_promos}' => 'Promo Aktif', // Simplified for re-engagement
        ];
        $personaInstructions = str_replace(array_keys($replacements), array_values($replacements), $personaInstructions);

        // Note: Promo model not implemented yet, using empty array for now
        $promos = [];

        $systemPrompt = "Anda adalah {$aiName}, AI Assistant untuk '{$appName}'.

PERAN & PERSONA:
{$personaInstructions}

TUGAS SAAT INI:
Buatlah pesan WhatsApp re-engagement yang HANGAT, PERSONAL, dan MENGUNDANG untuk member yang sudah {$daysSinceVisit} hari tidak berkunjung.

DATA MEMBER:
- Nama: {$member->name}
- Terakhir berkunjung: {$daysSinceVisit} hari yang lalu
- Program: {$programName}

ATURAN PENTING:
1. Gunakan gaya bahasa sesuai PERSONA di atas.
2. Tunjukkan bahwa kami merindukan kehadiran mereka (jangan pushy).
3. " . (!empty($promos) ? "Sebutkan promo aktif: " . implode(', ', $promos) : "Tidak perlu menyebutkan promo spesifik.") . "
4. Gunakan EMOJI yang relevan untuk suasana hangat.
5. Akhiri dengan signature '- {$aiName}'.
6. Berikan isi pesan SAJA, tanpa pembuka 'Berikut pesan untuk...' atau sejenisnya.
7. Maksimal 3-4 kalimat agar tidak terlalu panjang.";

        $userPrompt = "Buat pesan re-engagement untuk {$member->name} yang sudah {$daysSinceVisit} hari tidak berkunjung.";

        try {
            // DeepSeekService chat method expects array of messages
            $response = $aiService->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ]);
            // Extract message content from API response
            return $response['choices'][0]['message']['content'] ?? throw new \Exception('Invalid AI response format');
        } catch (\Exception $e) {
            // Fallback message if AI fails
            return sprintf(
                "Halo %s! 👋\n\n" .
                "Sudah lama tidak bertemu, kami kangen sama kamu! 🥺\n\n" .
                "Yuk mampir lagi ke %s. %s\n\n" .
                "Ditunggu ya! 😊\n\n" .
                "Salam,\n%s",
                $member->name,
                $appName,
                !empty($promos) ? 'Ada promo spesial: ' . implode(', ', $promos) . ' 🎉' : 'Tim kami siap melayani kamu!',
                $aiName
            );
        }
    }

    private function sendWhatsAppMessage(string $phone, string $message): void
    {
        // Queue WhatsApp message via your WhatsApp service
        // This assumes you have a WhatsApp integration

        // Example using a job queue:
        // \App\Jobs\SendWhatsAppMessage::dispatch($phone, $message);

        // For now, just log it (replace with actual implementation)
        Log::info('WhatsApp Re-engagement queued', [
            'phone' => $phone,
            'message' => $message
        ]);
    }
}
