<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Services\DeepSeekService;
use App\Settings\GeneralSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReEngagementCommand extends Command
{
    protected $signature = 'loyalty:re-engage 
                            {--member-id= : Re-engage a specific member}
                            {--dry-run : Run without sending messages}';

    protected $description = 'Re-engage inactive members (>30 days) with personalized WhatsApp messages';

    public function handle(DeepSeekService $aiService, GeneralSettings $settings, \App\Services\WhatsAppService $waService): int
    {
        $this->info('🔍 Finding inactive members...');

        $query = Member::query();

        if ($this->option('member-id')) {
            $query->where('id', $this->option('member-id'));
        } else {
            // Find members who haven't visited in >30 days
            $query->where('last_visit_at', '<', now()->subDays(30))
                ->where(function ($q) {
                    // Either never contacted, or last contacted >7 days ago (don't spam)
                    $q->whereNull('last_contacted_at')
                        ->orWhere('last_contacted_at', '<', now()->subDays(7));
                });
        }

        $inactiveMembers = $query->get();

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
                // Send WhatsApp message
                $success = $waService->sendMessage($member->phone, $message);

                if ($success) {
                    // Update last_contacted_at
                    $member->update(['last_contacted_at' => now()]);
                    $sent++;
                    $this->info('    ✅ Message sent');
                } else {
                    $failed++;
                    $this->error('    ❌ Gateway returned error');
                }
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
Buatlah pesan WhatsApp menyapa (re-engagement) yang SANGAT HANGAT, TULUS, dan MENYENTUH untuk member yang sudah {$daysSinceVisit} hari tidak berkunjung.

DATA MEMBER:
- Nama: {$member->name}
- Terakhir berkunjung: {$daysSinceVisit} hari yang lalu
- Program: {$programName}

ATURAN PENTING:
1. GUNAKAN GAYA BAHASA sesuai PERSONA di atas.
2. FOKUS UTAMA: Menanyakan kabar pelanggan dengan tulus (Authentic relationship building).
3. HINDARI HARD SELLING: Jangan langsung memaksa datang atau jualan menu secara agresif.
4. Tunjukkan bahwa kami rindu kehadiran mereka tanpa kesan 'mengejar'.
5. Gunakan EMOJI yang relevan untuk menciptakan suasana akrab.
6. Akhiri dengan signature '- {$aiName}'.
7. Berikan isi pesan SAJA, tanpa pembuka/penjelasan apapun.
8. Maksimal 3-4 kalimat agar terasa ringan dan tidak membebani.";

        $userPrompt = "Tanyakan kabar {$member->name} yang sudah {$daysSinceVisit} hari tidak berkunjung dengan sangat halus.";

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
                    "Apa kabar? Semoga sehat dan bahagia selalu ya. �\n\n" .
                    "Tiba-tiba terpikir, sudah cukup lama ya sejak terakhir kita bertemu di %s. Kami cuma mau menyapa dan bilang kalau kami rindu kehadiran Kakak. 🥺✨\n\n" .
                    "Kapan-kapan kalau ada waktu luang, mampir menyapa ya. Ditunggu ceritanya!\n\n" .
                    "Salam hangat,\n%s",
                $member->name,
                $appName,
                $aiName
            );
        }
    }
}
