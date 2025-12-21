<?php

namespace App\Http\Controllers\Api;

use App\Filament\Pages\WhatsappCenter;
use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsappWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $data = $request->validate([
                'status_update' => 'nullable|boolean',
                'wa_id' => 'nullable|string',
                'remote_jid' => 'nullable|string',
                'message_id' => 'nullable|string',
                'status' => 'nullable|integer',
                'from_me' => 'nullable|boolean',

                'message' => 'nullable|string',
                'push_name' => 'nullable|string',
                'conversation_name' => 'nullable|string',
                'attachment_data' => 'nullable|string',
                'attachment_type' => 'nullable|string',
                'attachment_mimetype' => 'nullable|string',
                'caption' => 'nullable|string',
                'full_message' => 'nullable|array',
                'timestamp' => 'nullable',
            ]);

            Log::info('WA Webhook Data Received:', $data);

            // Handle Status Update
            if (!empty($data['status_update']) && !empty($data['status'])) {
                // Map Baileys status to our DB string
                $statusMap = [
                    3 => 'sent',      // Delivered
                    4 => 'read',      // Read
                    5 => 'played',    // Played (Audio)
                ];
                $newStatus = $statusMap[$data['status']] ?? 'sent';

                Log::info("Updating status for {$data['message_id']} to $newStatus");

                WhatsappMessage::where('wa_id', $data['message_id'])
                    ->orWhere('id', $data['message_id']) // Fallback if using local ID
                    ->update(['status' => $newStatus]);

                return response()->json(['success' => true]);
            }

            // Extract WA ID from payload OR from full_message
            $waId = $data['wa_id'] ?? $data['full_message']['key']['id'] ?? null;

            // --- LID RESOLUTION LOGIC ---
            if (Str::contains($data['remote_jid'], '@lid')) {
                Log::info("Received message from LID: " . $data['remote_jid']);

                // STRATEGY 1: QUOTED MESSAGE LOOKUP (Strongest Link)
                // If user replies to a message, we can find the original message and its conversation
                $contextInfo = $data['full_message']['message']['extendedTextMessage']['contextInfo']
                    ?? $data['full_message']['message']['imageMessage']['contextInfo']
                    ?? $data['full_message']['message']['videoMessage']['contextInfo']
                    ?? $data['full_message']['message']['audioMessage']['contextInfo']
                    ?? $data['full_message']['message']['documentMessage']['contextInfo']
                    ?? null;

                if ($contextInfo && isset($contextInfo['stanzaId'])) {
                    $quotedId = $contextInfo['stanzaId'];
                    Log::info("LID Lookup: Found quoted stanzaId: $quotedId");

                    // Find the original message (which should have the correct Phone JID)
                    $originalMsg = WhatsappMessage::where('wa_id', $quotedId)
                        ->orWhere('id', $quotedId)
                        ->first();

                    if ($originalMsg) {
                        $targetJid = $originalMsg->remote_jid;

                        // If original was also LID (weird), try to find if it was normalized? 
                        // But usually original outbound is to Phone JID.
                        if (!Str::contains($targetJid, '@lid')) {
                            Log::info("MERGE SUCCESS (Quote): Mapped LID {$data['remote_jid']} -> {$targetJid}");
                            $data['remote_jid'] = $targetJid;
                            goto skip_name_match;
                        }
                    }
                }

                // STRATEGY 2: NAME MATCH (Weak Fallback)
                if (!empty($data['push_name'])) {
                    $existingJid = WhatsappMessage::where('push_name', $data['push_name'])
                        ->where('remote_jid', 'not like', '%@lid')
                        ->where('remote_jid', 'like', '%@s.whatsapp.net') // Ensure it is a phone number
                        ->value('remote_jid');

                    if ($existingJid) {
                        Log::info("MERGE SUCCESS (Name): Mapped LID {$data['remote_jid']} -> {$existingJid} based on name '{$data['push_name']}'");
                        $data['remote_jid'] = $existingJid;
                    } else {
                        Log::warning("LID MERGE FAILED: No existing conversation found for name '{$data['push_name']}'");
                    }
                }

                skip_name_match:
                ;
            }
            // ----------------------------

            // 1. Check by WA ID
            if ($waId && WhatsappMessage::where('wa_id', $waId)->exists()) {
                Log::info('Duplicate WA Message ignored (ID match): ' . $waId);
                return response()->json(['success' => true, 'duplicate' => true]);
            }

            // 2. Fuzzy Deduplication (same content/sender within 2 seconds)
            // Useful if WA ID is missing or for LID/PN doublet events
            $recentDuplicate = WhatsappMessage::where('remote_jid', $data['remote_jid'])
                ->where('message', $data['message'])
                ->where('created_at', '>=', now()->subSeconds(5))
                ->exists();

            if ($recentDuplicate) {
                Log::info('Duplicate WA Message ignored (Fuzzy match): ' . $data['remote_jid']);
                return response()->json(['success' => true, 'duplicate' => true]);
            }

            // Backfill push_name if missing (to prevent chat list name flickering)
            $pushName = $data['push_name'] ?? null;
            if (empty($pushName)) {
                $lastKnown = WhatsappMessage::where('remote_jid', $data['remote_jid'])
                    ->whereNotNull('push_name')
                    ->latest()
                    ->value('push_name');
                $pushName = $lastKnown;
            }

            // Handle Attachment
            $attachmentPath = null;
            $attachmentType = $data['attachment_type'] ?? null;
            $mimeType = $data['attachment_mimetype'] ?? null;
            $caption = $data['caption'] ?? null;

            $settings = app(\App\Settings\GeneralSettings::class);
            $shouldDownload = $data['from_me'] || $settings->wa_auto_download_media;

            if (!$shouldDownload) {
                Log::info("Auto-download skipped. Setting disabled.");
            } elseif (empty($data['attachment_data']) || !$attachmentType) {
                Log::info("Auto-download skipped. No data or type. Data present: " . (empty($data['attachment_data']) ? 'NO' : 'YES'));
            }

            if ($shouldDownload && !empty($data['attachment_data']) && $attachmentType) {
                try {
                    $decoded = base64_decode($data['attachment_data']);
                    $extension = match ($attachmentType) {
                        'image' => 'jpg',
                        'video' => 'mp4',
                        'document' => 'pdf',
                        default => 'bin'
                    };

                    if ($attachmentType === 'audio') {
                        if (str_contains($mimeType, 'ogg'))
                            $extension = 'ogg';
                        elseif (str_contains($mimeType, 'mp4'))
                            $extension = 'mp4';
                        elseif (str_contains($mimeType, 'mpeg') || str_contains($mimeType, 'mp3'))
                            $extension = 'mp3';
                        else
                            $extension = 'mp3'; // Default fallback
                    }

                    $filename = 'wa_' . time() . '_' . Str::random(5) . '.' . $extension;
                    $path = 'whatsapp-media/' . date('Y-m-d');

                    // Ensure directory exists
                    if (!Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->makeDirectory($path);
                    }

                    Storage::disk('public')->put($path . '/' . $filename, $decoded);
                    $attachmentPath = $path . '/' . $filename;

                    Log::info('Media saved: ' . $attachmentPath);
                } catch (\Exception $e) {
                    Log::error('Failed to save media: ' . $e->getMessage());
                }
            }

            $msg = WhatsappMessage::create([
                'wa_id' => $waId,
                'remote_jid' => $data['remote_jid'],
                'from_me' => $data['from_me'],
                'message' => $data['message'],
                'push_name' => $pushName,
                'conversation_name' => $data['conversation_name'] ?? null,
                'full_message' => $data['full_message'] ?? [],
                'status' => $data['from_me'] ? 'sent' : 'received',
                'attachment_path' => $attachmentPath,
                'attachment_type' => $attachmentType,
                'caption' => $caption
            ]);

            Log::info('WA Message Saved ID: ' . $msg->id);

            // Send Filament Database Notification
            if (!$data['from_me']) {
                $recipientUsers = \App\Models\User::all(); // Notify all admins/users

                Log::info("Sending WA Notification to " . $recipientUsers->count() . " users. Content: " . Str::limit($data['message'] ?? $caption ?? '...', 20));

                $senderName = $pushName ?? $data['remote_jid'];
                $preview = Str::limit($data['message'] ?? $caption ?? 'Mengirim lampiran', 50);

                Notification::make()
                    ->title("Pesan dari {$senderName}")
                    ->body($preview)
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->actions([
                        Action::make('reply')
                            ->button()
                            ->label('Balas')
                            ->url(WhatsappCenter::getUrl(['jid' => $data['remote_jid']]))
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($recipientUsers);

                Log::info("Notification dispatch command executed.");
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('WA Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
