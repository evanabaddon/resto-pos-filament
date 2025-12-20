<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use BackedEnum;
use UnitEnum;

use Livewire\Features\SupportFileUploads\WithFileUploads;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use App\Models\Member;
use App\Models\Reservation;
use App\Models\Product;
use App\Models\DiscountCode;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class WhatsappCenter extends Page implements HasActions, HasForms
{
    use WithFileUploads;
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'WhatsApp Center';
    protected static ?string $title = 'WhatsApp Gateway Integration';
    protected static string|UnitEnum|null $navigationGroup = 'Super Chat';
    protected static ?int $navigationSort = 10;

    // Only show if module is enabled
    public static function shouldRegisterNavigation(): bool
    {
        return app(\App\Settings\GeneralSettings::class)->enable_wa_center;
    }

    protected string $view = 'filament.pages.whatsapp-center';

    public $isGeneratingAi = false;

    public $status = 'disconnected';
    public $qrCode = null;
    // public $chats = []; // Removed to ensure computed property logic works
    public $selectedJid = null;
    public $activeChatMessages = [];
    public $newMessage = '';

    public $attachment = null; // Temporary file upload
    public $isConnecting = false;
    public $search = '';
    // Member Status State
    public $isMember = false;
    public $memberData = null;
    public $userAvatar = null;
    public $userName = null;

    public $lastMessageCount = 0;

    public $groupParticipants = [];

    public $replyToMessage = null; // Stores the message being replied to

    // ACTIONS -----------------------------------------------------------------

    public function createMemberAction(): Action
    {
        return Action::make('createMember')
            ->label('Daftarkan Member')
            ->icon('heroicon-o-user-plus')
            ->color('success')
            ->form([
                TextInput::make('name')
                    ->label('Nama Member')
                    ->required()
                    ->default(fn() => $this->getChatName()),
                TextInput::make('phone')
                    ->label('Nomor WhatsApp')
                    ->disabled()
                    ->dehydrated() // Fix: Ensure value is sent
                    ->required()
                    ->default(fn() => $this->getCleanPhone()),
                TextInput::make('email')
                    ->email()
                    ->label('Email (Opsional)'),
            ])
            ->action(function (array $data) {
                Member::create([
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'tier_id' => \App\Models\LoyaltyTier::orderBy('min_points', 'asc')->first()?->id,
                ]);

                $this->checkMemberStatus(); // Refresh status

                Notification::make()
                    ->title('Member Created')
                    ->success()
                    ->send();
            });
    }

    public function createReservationAction(): Action
    {
        return Action::make('createReservation')
            ->label('Buat Reservasi')
            ->icon('heroicon-o-calendar-days')
            ->color('primary')
            ->form([
                TextInput::make('customer_name')
                    ->label('Nama Customer')
                    ->required()
                    ->default(fn() => $this->getChatName()),
                TextInput::make('customer_phone')
                    ->label('Nomor WhatsApp')
                    ->required()
                    ->default(fn() => $this->getCleanPhone()),
                DateTimePicker::make('reservation_date')
                    ->label('Tanggal & Jam')
                    ->required()
                    ->seconds(false)
                    ->native(false)
                    ->default(now()->addHour()),
                TextInput::make('party_size')
                    ->label('Jumlah Orang')
                    ->numeric()
                    ->required()
                    ->default(2),
                Textarea::make('special_requests')
                    ->label('Catatan Khusus'),
            ])
            ->action(function (array $data) {
                Reservation::create([
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'reservation_date' => $data['reservation_date'],
                    'party_size' => $data['party_size'],
                    'special_requests' => $data['special_requests'],
                    'status' => 'pending',
                ]);

                Notification::make()
                    ->title('Reservation Created')
                    ->success()
                    ->send();
            });
    }


    // HELPERS -----------------------------------------------------------------

    public function getCleanPhone()
    {
        return $this->selectedJid ? explode('@', $this->selectedJid)[0] : '';
    }

    public function getChatName()
    {
        $chat = collect($this->getChatsProperty())->firstWhere('remote_jid', $this->selectedJid);
        return $chat->effective_name ?? 'New Member';
    }

    public function checkMemberStatus()
    {
        if (!$this->selectedJid) {
            $this->isMember = false;
            $this->memberData = null;
            return;
        }

        $phone = $this->getCleanPhone();
        // Check fuzzy match (08... or 628...)
        $phoneSuffix = substr($phone, 2); // e.g. 8123456

        $member = Member::where('phone', 'like', "%$phoneSuffix%")->first();

        if ($member) {
            $this->isMember = true;
            $this->memberData = $member;
        } else {
            $this->isMember = false;
            $this->memberData = null;
        }
    }

    protected function getGatewayUrl()
    {
        return rtrim(env('WA_GATEWAY_URL', 'http://127.0.0.1:3000'), '/');
    }

    public function checkConnection()
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(2)->get($this->getGatewayUrl() . '/status');
            if ($response->successful()) {
                $data = $response->json();
                $this->status = $data['status'] ?? 'error';
                $this->qrCode = $data['qr'] ?? null;

                if ($this->status === 'connected' && isset($data['user'])) {
                    // Use the proxy route for the user avatar as well
                    $userJid = $data['user']['id'] ?? null;
                    $this->userAvatar = $userJid ? route('whatsapp.avatar', $userJid) : null;
                    $this->userName = $data['user']['name'] ?? null;
                }
            } else {
                $this->status = 'gateway_error';
            }
        } catch (\Exception $e) {
            Log::error("WA Gateway Connection Error: " . $e->getMessage());
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

    public function mount(?string $jid = null)
    {
        // Block access if module is disabled
        if (!app(\App\Settings\GeneralSettings::class)->enable_wa_center) {
            return redirect('/admin');
        }

        $this->checkConnection();

        // Check if jid is passed as query parameter
        if (!$jid) {
            $jid = request()->query('jid');
        }

        if ($jid) {
            $this->selectChat($jid);
        }
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

        // Mark Filament Notifications as read
        if ($user = auth()->user()) {
            foreach ($user->unreadNotifications as $notification) {
                $url = $notification->data['url'] ?? '';
                // Check if URL contains the JID, handles encoded vs decoded
                if (str_contains(urldecode($url), $jid) || str_contains($url, $jid)) {
                    $notification->markAsRead();
                }
            }
        }

        $this->refreshMessages();
    }

    public function refreshMessages()
    {
        if ($this->selectedJid) {
            $messages = \App\Models\WhatsappMessage::where('remote_jid', $this->selectedJid)
                ->orderBy('created_at', 'asc')
                ->get();

            $this->activeChatMessages = $messages;

            // Extract generic participants list for Group Mentions
            if (str_contains($this->selectedJid, '@g.us')) {
                $this->groupParticipants = [];
                foreach ($messages as $msg) {
                    if (!$msg->from_me) {
                        $rawId = data_get($msg->full_message, 'key.participant') ?? data_get($msg->full_message, 'participant');

                        // Try to find cleaner ID if it's a LID
                        if ($rawId && str_contains($rawId, '@lid')) {
                            // Some Baileys versions might populate senderPn in the key
                            $pn = data_get($msg->full_message, 'key.senderPn');
                            if ($pn) {
                                $rawId = $pn;
                            }
                        }

                        if ($rawId) {
                            // Aggressively strip everything after @ to get just the ID/Proton/Number
                            $number = explode('@', $rawId)[0];

                            $name = $msg->push_name ?? $number;
                            // Store generic mapping
                            $this->groupParticipants[$number] = [
                                'id' => $number,
                                'name' => $name,
                                'color_index' => abs(crc32($name) % 5)
                            ];
                        }
                    }
                }
                // Reset keys to be array for JSON
                $this->groupParticipants = array_values($this->groupParticipants);
            } else {
                $this->groupParticipants = [];
            }

            // Only scroll if message count changed (new message arrived)
            if ($messages->count() > $this->lastMessageCount) {
                $this->dispatch('chat-updated');
                $this->lastMessageCount = $messages->count();
            }
        }
    }

    // refreshed via refreshMessages


    public function setReplyTo($messageId)
    {
        $this->replyToMessage = \App\Models\WhatsappMessage::find($messageId);
        $this->dispatch('focus-input');
    }

    public function cancelReply()
    {
        $this->replyToMessage = null;
    }

    public function downloadMedia($messageId)
    {
        $msg = \App\Models\WhatsappMessage::find($messageId);
        if (!$msg || !$msg->full_message) {
            Notification::make()->title('Gagal')->body('Data pesan tidak ditemukan.')->danger()->send();
            return;
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(30)->post($this->getGatewayUrl() . '/chat/download-media', [
                'message' => $msg->full_message
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $decoded = base64_decode($data['media_data']);
                $mimeType = $data['mimetype'];

                $extension = match ($msg->attachment_type) {
                    'image' => 'jpg',
                    'video' => 'mp4',
                    'document' => 'pdf',
                    'audio' => 'mp3',
                    default => 'bin'
                };

                // Specific audio extension check
                if ($msg->attachment_type === 'audio') {
                    if (str_contains($mimeType, 'ogg'))
                        $extension = 'ogg';
                    elseif (str_contains($mimeType, 'mp4'))
                        $extension = 'mp4';
                }

                $filename = 'wa_manual_' . time() . '_' . Str::random(5) . '.' . $extension;
                $path = 'whatsapp-media/' . date('Y-m-d');

                if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($path);
                }

                \Illuminate\Support\Facades\Storage::disk('public')->put($path . '/' . $filename, $decoded);

                $msg->update(['attachment_path' => $path . '/' . $filename]);

                $this->refreshMessages();
                Notification::make()->title('Berhasil')->body('Media berhasil didownload.')->success()->send();
            } else {
                Notification::make()->title('Gagal')->body('Gagal mendownload dari gateway.')->danger()->send();
            }
        } catch (\Exception $e) {
            Log::error('Manual Download Error: ' . $e->getMessage());
            Notification::make()->title('Error')->body('Terjadi kesalahan: ' . $e->getMessage())->danger()->send();
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

            // Handle Key Quote/Reply
            if ($this->replyToMessage) {
                $originalMsg = $this->replyToMessage;
                // If it's a message we sent, the ID is in the database.
                // If it's from others, we need the `wa_id` or `id` from the raw data.
                // Our wa-gateway needs the `key` object to quote properly.

                // Construct the key object expected by Baileys/WA-Gateway
                $key = [
                    'remoteJid' => $originalMsg->remote_jid,
                    'fromMe' => (bool) $originalMsg->from_me,
                    'id' => $originalMsg->wa_id ?? $originalMsg->id, // Use WA ID if available
                    'participant' => $originalMsg->remote_jid // For group chats, might be different
                ];

                // If it was a group message (remote_jid ends in @g.us), participant is needed
                if (str_contains($originalMsg->remote_jid, '@g.us') && !$originalMsg->from_me) {
                    // Try to get actual participant from full_message if available
                    $fullData = $originalMsg->full_message;
                    $key['participant'] = $fullData['key']['participant'] ?? $key['participant'];
                }

                $payload['quoted'] = [
                    'key' => $key,
                    'message' => $this->newMessage // The message itself isn't strictly needed for the key, but some gateways use it
                ];
            }

            // Extract Mentions (@number)
            if (str_contains($this->selectedJid, '@g.us')) {
                preg_match_all('/@([0-9]+)/', $this->newMessage, $matches);
                if (!empty($matches[1])) {
                    $mentions = [];
                    foreach ($matches[1] as $number) {
                        $mentions[] = $number . '@s.whatsapp.net';
                    }
                    if (!empty($mentions)) {
                        $payload['mentions'] = $mentions;
                    }
                }
            }

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

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::asJson()->post($this->getGatewayUrl() . '/chat/send', $payload);

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
                    'caption' => $this->newMessage,
                    'full_message' => $this->replyToMessage ? ['quoted_status' => 'local_reply', 'quoted_id' => $this->replyToMessage->id] : null
                ]);

                $this->newMessage = '';
                $this->attachment = null; // Reset attachment
                $this->replyToMessage = null; // Reset reply
                $this->refreshMessages();
            } else {
                Notification::make()
                    ->title('Gagal mengirim pesan')
                    ->body($response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
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

        Notification::make()
            ->title('Conversation deleted')
            ->success()
            ->send();
    }

    public function logout()
    {
        try {
            Http::post($this->getGatewayUrl() . '/logout');

            // Clear all messages from database as requested
            \App\Models\WhatsappMessage::truncate();

            $this->selectedJid = null;
            $this->activeChatMessages = [];

            $this->checkConnection();
            Notification::make()->title('Logged out & Data Cleared')->success()->send();
        } catch (\Exception $e) {
        }
    }

    public function generateAiReply(\App\Services\DeepSeekService $aiService)
    {
        if (!$this->selectedJid)
            return;

        $this->isGeneratingAi = true;

        try {
            // Get last 10 messages for context
            $recentMessages = \App\Models\WhatsappMessage::where('remote_jid', $this->selectedJid)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->reverse(); // Chronological order for AI

            $chatHistory = [];
            foreach ($recentMessages as $msg) {
                // Skip media messages for now as we can't parse them easily
                $content = $msg->message ?: ($msg->caption ?: '[Media/Attachment]');

                $chatHistory[] = [
                    'role' => $msg->from_me ? 'assistant' : 'user',
                    'content' => $content
                ];
            }

            // Get sender name
            $senderName = $recentMessages->where('from_me', false)->last()->push_name ?? 'Pelanggan';

            // GATHER REAL CONTEXT
            $context = $this->getChatContext();

            $suggestion = $aiService->generateReplySuggestion($chatHistory, $senderName, $context);

            if ($suggestion) {
                $this->newMessage = $suggestion;
                Notification::make()
                    ->title('✨ AI Suggestion Generated')
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('AI Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isGeneratingAi = false;
        }
    }

    protected function getChatContext(): string
    {
        // 1. Top Products (Actual Menu)
        $topItems = SaleItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('products.is_sellable', true)
            ->where('products.name', '!=', 'Down Payment (DP)')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $menuList = $topItems->map(fn($item) => $item->product?->name)->filter()->implode(', ');
        if (empty($menuList)) {
            $menuList = Product::where('is_sellable', true)->where('name', '!=', 'Down Payment (DP)')->limit(5)->pluck('name')->implode(', ');
        }

        // 2. Active Promos
        $activePromos = DiscountCode::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->limit(3)
            ->get(['code', 'name']);

        $promoStr = $activePromos->isEmpty() ? 'Tidak ada promo aktif saat ini' : $activePromos->map(fn($p) => "{$p->name} (Kode: {$p->code})")->implode(', ');

        return "MENU UNGGULAN: {$menuList}\nPROMO AKTIF: {$promoStr}";
    }
}
