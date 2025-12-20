<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use BackedEnum;
use UnitEnum;

use Livewire\Features\SupportFileUploads\WithFileUploads;

class WhatsappCenter extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'WhatsApp Center';
    protected static ?string $title = 'WhatsApp Gateway Integration';
    protected static string|UnitEnum|null $navigationGroup = 'Communication';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.whatsapp-center';

    public $status = 'disconnected';
    public $qrCode = null;
    // public $chats = []; // Removed to ensure computed property logic works
    public $selectedJid = null;
    public $activeChatMessages = [];
    public $newMessage = '';
    public $attachment = null; // Temporary file upload
    public $isConnecting = false;
    public $search = '';
    public $userAvatar = null;
    public $userName = null;

    // ... (getChatsProperty and startNewChat remain unchanged) ...

    protected function getGatewayUrl()
    {
        return rtrim(env('WA_GATEWAY_URL', 'http://127.0.0.1:3000'), '/');
    }

    public function checkConnection()
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(2)->get($this->getGatewayUrl() . '/status');
            if ($response->successful()) {
                $data = $response->json();
                $this->status = $data['status'] ?? 'error';
                $this->qrCode = $data['qr'] ?? null;

                if ($this->status === 'connected' && isset($data['user'])) {
                    $this->userAvatar = $data['user']['avatar'] ?? null;
                    $this->userName = $data['user']['name'] ?? null;
                }
            } else {
                $this->status = 'gateway_error';
            }
        } catch (\Exception $e) {
            $this->status = 'offline'; // Node likely not running
            $this->qrCode = null;
        }
    }

    // ... [getChatsProperty and startNewChat same as before] ...

    public function sendMessage()
    {
        if (!$this->selectedJid || (empty($this->newMessage) && !$this->attachment))
            return;

        try {
            $payload = [
                'number' => str_replace('@s.whatsapp.net', '', $this->selectedJid),
                'message' => $this->newMessage
            ];

            // Handle Attachment Sending
            if ($this->attachment) {
                $path = $this->attachment->getRealPath();
                $mime = $this->attachment->getMimeType();
                $base64 = base64_encode(file_get_contents($path));

                $type = 'document';
                if (str_starts_with($mime, 'image/'))
                    $type = 'image';
                elseif (str_starts_with($mime, 'video/'))
                    $type = 'video';
                elseif (str_starts_with($mime, 'audio/'))
                    $type = 'audio';

                $payload['media_data'] = $base64;
                $payload['media_type'] = $type;
                $payload['caption'] = $this->newMessage;
            }

            $response = \Illuminate\Support\Facades\Http::post($this->getGatewayUrl() . '/chat/send', $payload);

            if ($response->successful()) {
                // Find recipient name to preserve chat title in the list
                $recipientName = \App\Models\WhatsappMessage::where('remote_jid', $this->selectedJid)
                    ->where('from_me', false)
                    ->whereNotNull('push_name')
                    ->latest()
                    ->value('push_name');

                // Determining storage path for local DB immediately
                $localAttachmentPath = null;
                if ($this->attachment) {
                    $localAttachmentPath = $this->attachment->store('whatsapp-media/' . date('Y-m-d'), 'public');
                }

                \App\Models\WhatsappMessage::create([
                    'remote_jid' => $this->selectedJid,
                    'from_me' => true,
                    'message' => $this->newMessage,
                    'push_name' => $recipientName,
                    'status' => 'sent',
                    'attachment_path' => $localAttachmentPath,
                    'attachment_type' => $payload['media_type'] ?? null,
                    'caption' => $this->newMessage
                ]);

                $this->newMessage = '';
                $this->attachment = null; // Reset attachment
                $this->refreshMessages();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Gagal mengirim pesan')
                    ->body($response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error Connection')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // ... [deleteConversation] ...

    public function logout()
    {
        try {
            \Illuminate\Support\Facades\Http::post($this->getGatewayUrl() . '/logout');

            // Clear all messages from database as requested
            \App\Models\WhatsappMessage::truncate();

            $this->selectedJid = null;
            $this->activeChatMessages = [];

            $this->checkConnection();
            \Filament\Notifications\Notification::make()->title('Logged out & Data Cleared')->success()->send();
        } catch (\Exception $e) {
        }
    }
}
