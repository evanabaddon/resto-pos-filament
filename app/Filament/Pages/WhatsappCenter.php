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

    public function checkConnection()
    {
        try {
            // Assume Node service runs on port 3000 locally
            $response = \Illuminate\Support\Facades\Http::timeout(2)->get('http://127.0.0.1:3000/status');
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
    public function getChatsProperty()
    {
        // Fetch the latest message for each remote_jid
        // logical efficient query for chat list
        return \App\Models\WhatsappMessage::query()
            ->select('whatsapp_messages.*')
            ->selectRaw('(SELECT COUNT(*) FROM whatsapp_messages as wm2 WHERE wm2.remote_jid = whatsapp_messages.remote_jid AND wm2.from_me = 0 AND wm2.status = "received") as unread_count')
            // Subquery to get the most relevant name (Contact Name or Group Subject)
            ->selectRaw("
                CASE 
                    WHEN whatsapp_messages.remote_jid LIKE '%@g.us' THEN 
                        COALESCE(
                            (SELECT conversation_name FROM whatsapp_messages as wm_group WHERE wm_group.remote_jid = whatsapp_messages.remote_jid AND wm_group.conversation_name IS NOT NULL ORDER BY id DESC LIMIT 1),
                            (SELECT push_name FROM whatsapp_messages as wm_name WHERE wm_name.remote_jid = whatsapp_messages.remote_jid AND wm_name.from_me = 0 AND wm_name.push_name IS NOT NULL ORDER BY id DESC LIMIT 1),
                            whatsapp_messages.push_name
                        )
                    ELSE
                        COALESCE(
                            (SELECT push_name FROM whatsapp_messages as wm_name WHERE wm_name.remote_jid = whatsapp_messages.remote_jid AND wm_name.from_me = 0 AND wm_name.push_name IS NOT NULL ORDER BY id DESC LIMIT 1),
                            conversation_name,
                            whatsapp_messages.push_name
                        )
                END as effective_name
            ")
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('whatsapp_messages')
                    ->groupBy('remote_jid');
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('push_name', 'like', "%{$this->search}%")
                        ->orWhere('conversation_name', 'like', "%{$this->search}%")
                        ->orWhere('remote_jid', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('created_at')
            ->get();
    }

    public function startNewChat($number)
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', $number);
        if (strlen($cleanNumber) < 5)
            return;

        $jid = $cleanNumber . '@s.whatsapp.net';
        $this->selectChat($jid);
        $this->search = '';
    }

    public function pollState()
    {
        $this->checkConnection();
        $this->refreshMessages();
    }

    public function mount()
    {
        $this->checkConnection();
    }

    public function selectChat($jid)
    {
        $this->selectedJid = $jid;
        $this->newMessage = '';

        // Mark messages as read
        \App\Models\WhatsappMessage::where('remote_jid', $jid)
            ->where('from_me', false)
            ->where('status', 'received')
            ->update(['status' => 'read']);

        $this->refreshMessages();
    }

    public $lastMessageCount = 0;

    public function refreshMessages()
    {
        if ($this->selectedJid) {
            $messages = \App\Models\WhatsappMessage::where('remote_jid', $this->selectedJid)
                ->orderBy('created_at', 'asc')
                ->get();

            $this->activeChatMessages = $messages;

            // Only scroll if message count changed (new message arrived)
            if ($messages->count() > $this->lastMessageCount) {
                $this->dispatch('chat-updated');
                $this->lastMessageCount = $messages->count();
            }
        }
    }

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

            // Assume Node service runs on port 3000
            $response = \Illuminate\Support\Facades\Http::post('http://127.0.0.1:3000/chat/send', $payload);

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

    public function deleteConversation()
    {
        if (!$this->selectedJid)
            return;

        \App\Models\WhatsappMessage::where('remote_jid', $this->selectedJid)->delete();

        $this->selectedJid = null;
        $this->activeChatMessages = [];

        \Filament\Notifications\Notification::make()
            ->title('Conversation deleted')
            ->success()
            ->send();
    }

    public function logout()
    {
        try {
            \Illuminate\Support\Facades\Http::post('http://127.0.0.1:3000/logout');

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
