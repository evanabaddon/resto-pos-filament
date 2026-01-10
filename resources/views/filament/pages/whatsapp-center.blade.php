<x-filament-panels::page class="h-full">
    <div wire:poll.5s="pollState" class="flex flex-col h-[calc(100vh-8rem)] -m-6 relative">
        {{-- Loading Indicator --}}
        <div wire:loading.delay class="absolute top-2 right-2 z-50">
            <div class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs flex items-center gap-2 shadow-lg animate-fade-in">
                <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Syncing...</span>
            </div>
        </div>


        {{-- MAIN LAYOUT --}}
        <div
            class="flex flex-1 overflow-hidden bg-white dark:bg-gray-900 shadow-xl border-t border-gray-200 dark:border-gray-800">

            {{-- LEFT SIDEBAR (30% WIDTH) --}}
            <div
                class="w-full md:w-[350px] flex flex-col border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 z-10">

                {{-- SIDEBAR HEADER --}}
                <div
                    class="h-16 px-4 flex items-center justify-between bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="relative" x-data="{ 
                            jid: '{{ $userName }}', 
                            showFallback: false,
                            init() {
                                if (window.failedAvatars && window.failedAvatars.has(this.jid)) {
                                    this.showFallback = true;
                                }
                            },
                            handleError() {
                                this.showFallback = true;
                                if (!window.failedAvatars) window.failedAvatars = new Set();
                                window.failedAvatars.add(this.jid);
                            }
                        }">
                            <div
                                class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center overflow-hidden">
                                @if($userAvatar && $status === 'connected')
                                    <template x-if="!showFallback">
                                        <img src="{{ $userAvatar }}?v=3" class="w-full h-full object-cover"
                                            x-on:error="handleError()">
                                    </template>

                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-lg font-medium"
                                        style="background-color: {{ '#' . substr(md5($userName ?? 'user'), 0, 6) }}"
                                        x-show="showFallback">
                                        {{ strtoupper(substr($userName ?? 'U', 0, 1)) }}
                                    </div>
                                @else
                                    <x-heroicon-s-user class="w-6 h-6 text-gray-500 dark:text-gray-400" />
                                @endif
                            </div>
                            <div @class([
                                'absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white dark:border-gray-800',
                                'bg-green-500' => $status === 'connected',
                                'bg-yellow-500' => $status === 'scanning' || $status === 'connecting',
                                'bg-red-500' => $status === 'disconnected' || $status === 'offline',
                            ])></div>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-semibold text-gray-700 dark:text-gray-200 capitalize text-sm">
                                {{ $userName ?? str_replace('_', ' ', $status) }}
                            </span>
                            @if($userName && $status === 'connected')
                                <span class="text-[10px] text-gray-500">Online v2</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-2">
                        @if($status === 'connected')
                            <button wire:click="mountAction('syncAvatars')" title="Sync All Avatars"
                                class="p-2 rounded-full text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">
                                <x-heroicon-o-arrow-path class="w-5 h-5" />
                            </button>
                            <button wire:click="mountAction('logout')" title="Disconnect & Clear Data"
                                class="p-2 rounded-full text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <x-heroicon-o-power class="w-5 h-5" />
                            </button>
                        @else
                            {{-- Force Reset Button when stuck or disconnected --}}
                            <button wire:click="mountAction('forceReset')" title="Force Reset & Clear Storage"
                                class="p-2 rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <x-heroicon-o-trash class="w-5 h-5" />
                            </button>

                            <button wire:click="checkConnection" title="Refresh Status"
                                class="p-2 rounded-full text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                <x-heroicon-o-arrow-path
                                    class="w-5 h-5 {{ $status === 'connecting' ? 'animate-spin' : '' }}" />
                            </button>
                        @endif
                    </div>
                </div>

                {{-- SEARCH BAR (Visual Only) --}}
                <div class="p-2 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
                        </div>
                        <input type="text" wire:model.live.debounce.500ms="search"
                            class="block w-full pl-10 pr-3 py-1.5 border-none rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-200 placeholder-gray-500 text-sm focus:ring-0"
                            placeholder="{{ __('messages.search_chat_placeholder') }}">
                    </div>
                </div>

                {{-- CHAT LIST --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    @if($this->chats->count() > 0)
                        @foreach($this->chats as $chat)
                            <div wire:click="selectChat('{{ $chat->remote_jid }}')"
                                class="group flex items-center gap-3 p-3 cursor-pointer transition border-b border-gray-100 dark:border-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-800 {{ $selectedJid === $chat->remote_jid ? 'bg-gray-100 dark:bg-gray-800' : '' }}">

                                {{-- Avatar --}}
                                <div class="relative shrink-0" x-data="{ 
                                    jid: '{{ $chat->remote_jid }}',
                                    showFallback: false,
                                    init() {
                                        if (!window.failedAvatars) window.failedAvatars = new Set();
                                        if (window.failedAvatars.has(this.jid)) {
                                            this.showFallback = true;
                                        }
                                    },
                                    handleError() {
                                        this.showFallback = true;
                                        if (!window.failedAvatars) window.failedAvatars = new Set();
                                        window.failedAvatars.add(this.jid);
                                        // Silently handle missing avatars
                                    }
                                }">
                                    @php
                                        $avatarFilename = str_replace(['@', '.'], '_', $chat->remote_jid) . '.jpg';
                                        $avatarPath = public_path("storage/avatars/{$avatarFilename}");
                                        $avatarExists = file_exists($avatarPath);
                                    @endphp
                                    
                                    @if($avatarExists)
                                        <template x-if="!showFallback">
                                            <img src="/storage/avatars/{{ $avatarFilename }}"
                                                class="w-12 h-12 rounded-full object-cover bg-gray-200 dark:bg-gray-700" loading="lazy"
                                                x-on:error="handleError()">
                                        </template>
                                    @endif

                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-lg font-medium shadow-sm select-none"
                                        style="background-color: {{ '#' . substr(md5($chat->remote_jid), 0, 6) }}"
                                        x-show="{{ $avatarExists ? 'showFallback' : 'true' }}">
                                        {{ strtoupper(substr($chat->effective_name ?? $chat->push_name ?? $chat->remote_jid, 0, 1)) }}
                                    </div>
                                </div>

                                {{-- Chat Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-baseline mb-0.5">
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                            {{ $chat->effective_name ?? $chat->push_name ?? str_replace('@s.whatsapp.net', '',
                                            $chat->remote_jid) }}
                                        </h3>
                                        @if(($chat->effective_name ?? $chat->push_name) && ($chat->effective_name ?? $chat->push_name) !== $chat->remote_jid)
                                            <span class="text-[10px] text-gray-400 dark:text-gray-500 shrink-0 ml-2">
                                                ~{{ str_replace('@s.whatsapp.net', '', $chat->remote_jid) }}
                                            </span>
                                        @endif
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 shrink-0 ml-auto">
                                            {{ \Carbon\Carbon::parse($chat->last_message_time ?? now())->format('H:i') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center mt-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate min-w-0 flex-1 pr-2">
                                            @if($chat->from_me)
                                                <span class="text-gray-400 font-bold">You:</span>
                                            @endif
                                            {{ $chat->message ?? ($chat->caption ? '📷 ' . $chat->caption : 'Media') }}
                                        </p>

                                        @if($chat->unread_count > 0)
                                            <span
                                                class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-green-500 rounded-full shrink-0">
                                                {{ $chat->unread_count }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        @if($search)
                            <div class="p-4 text-center flex flex-col items-center">
                                <p class="text-sm text-gray-500 mb-2">{{ __('messages.no_chats_found') }}</p>
                                @if(preg_match('/^[0-9]+$/', $search) && strlen($search) >= 5)
                                    <x-filament::button wire:click="startNewChat('{{ $search }}')" size="sm" icon="heroicon-m-plus">
                                        {{ __('messages.new_chat') }}: {{ $search }}
                                    </x-filament::button>
                                @endif
                            </div>
                        @else
                            <div class="p-8 text-center text-gray-400 text-sm">
                                {{ __('messages.no_conversations_yet') }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- RIGHT CONTENT (70% WIDTH) --}}
            <div class="hidden md:flex flex-1 flex-col h-full bg-[#efeae2] dark:bg-[#0b141a] relative" x-data="{ 
                    isDragging: false,
                    handleDrop(e) {
                        this.isDragging = false;
                        let files = e.dataTransfer.files;
                        if (files.length > 0) {
                            this.uploadFile(files[0]);
                        }
                    },
                    handlePaste(e) {
                        // 1. Files always take priority (Copied files from Finder/Explorer)
                        if (e.clipboardData.files.length > 0) {
                            e.preventDefault();
                            this.uploadFile(e.clipboardData.files[0]);
                            return;
                        }

                        // 2. Fallback to Items (Screenshots / Raw Data)
                        let item = null;
                        if (e.clipboardData.items) {
                            for (let i = 0; i < e.clipboardData.items.length; i++) {
                                if (e.clipboardData.items[i].type.indexOf('image') !== -1) {
                                    item = e.clipboardData.items[i];
                                    break;
                                }
                            }
                        }

                        if (item) {
                            const blob = item.getAsFile();
                            if (blob) {
                                e.preventDefault();
                                
                                // FORCE valid file structure for raw blobs (Screenshots)
                                // We slice to ensure the mime-type is correctly picked up by the server's finfo
                                const filename = `screenshot-${new Date().getTime()}.png`;
                                const freshBlob = blob.slice(0, blob.size, 'image/png');
                                const newFile = new File([freshBlob], filename, { type: 'image/png' });
                                
                                this.uploadFile(newFile);
                            }
                        }
                    },
                    uploadFile(file) {
                        // 1. Ensure we have an active chat to upload to
                        if (!$wire.selectedJid) {
                            new FilamentNotification()
                                .title('{{ __('messages.warning') }}')
                                .body('{{ __('messages.select_conversation_first') }}')
                                .warning()
                                .send();
                            return;
                        }

                        // 2. Perform upload
                        console.log('Starting upload for file:', file.name, file.size);

                        $wire.upload('attachment', file, (uploadedFilename) => {
                            // Success
                            console.log('Upload successful:', uploadedFilename);
                            new FilamentNotification()
                                .title('{{ __('messages.file_ready') }}')
                                .body('{{ __('messages.click_to_send') }}')
                                .success()
                                .send();
                        }, (error) => {
                            // Failure
                            console.error('LIVEWIRE UPLOAD ERROR:', error);
                            
                            let errorMsg = error || 'Terjadi kesalahan saat mengunggah.';
                            if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                                errorMsg += ' Cek apakah APP_URL di .env sudah sesuai.';
                            }

                            new FilamentNotification()
                                .title('Gagal Mengunggah')
                                .body(errorMsg)
                                .danger()
                                .send();
                        });
                    }
                 }" x-on:livewire-upload-error.window="
                    new FilamentNotification()
                        .title('{{ __('messages.upload_failed') }}')
                        .body($event.detail.error || 'Terjadi kesalahan saat mengunggah file. Pastikan server mengizinkan file besar.')
                        .danger()
                        .send();
                  " x-on:dragover.prevent="isDragging = true" x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="handleDrop($event)" x-on:paste.window="handlePaste($event)">

                {{-- LOADING OVERLAY --}}
                <div wire:loading.flex wire:target="selectChat"
                    class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-gray-50/80 dark:bg-gray-900/80 backdrop-blur-sm transition-opacity duration-300">
                    <x-filament::loading-indicator class="w-10 h-10 text-primary-500" />
                    <span class="mt-3 text-sm font-medium text-gray-600 dark:text-gray-300 animate-pulse">Memuat
                        percakapan...</span>
                </div>

                {{-- DRAG OVERLAY --}}
                <div x-show="isDragging" x-transition.opacity
                    class="absolute inset-0 z-50 bg-primary-500/10 backdrop-blur-sm border-4 border-primary-500 border-dashed m-4 rounded-xl flex flex-col items-center justify-center pointer-events-none">
                    <x-heroicon-o-arrow-up-tray class="w-20 h-20 text-primary-600 animate-bounce" />
                    <h3 class="text-xl font-bold text-primary-700 mt-4">Drop file here to send</h3>
                </div>

                {{-- CHAT BACKGROUND PATTERN --}}
                <div class="absolute inset-0 opacity-40 dark:opacity-10 pointer-events-none"
                    style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-repeat: repeat;">
                </div>

                {{-- 1. DISCONNECTED / QR STATE --}}
                @if($status !== 'connected')
                    <div
                        class="flex-1 flex flex-col items-center justify-center p-8 z-10 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm">
                        <h3 class="text-xl font-bold mb-4 text-gray-800 dark:text-white">
                            {{ __('messages.wa_disconnected') }}</h3>

                        @if($status === 'offline')
                            <div class="p-4 rounded-lg bg-red-50 text-red-600 mb-4 text-center">
                                <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto mb-2" />
                                <p class="font-bold">Gateway Service Offline</p>
                            </div>
                        @else
                            @if($qrCode)
                                <div class="bg-white p-2 rounded-lg shadow-lg mb-4 animate-in fade-in zoom-in duration-300">
                                    <img src="{{ $qrCode }}" alt="QR Code" class="w-72 h-72">
                                </div>
                                <p class="text-gray-600 dark:text-gray-300 font-medium">{{ __('messages.scan_qr') }}</p>
                                <p class="text-sm text-gray-500 mt-2">{{ __('messages.scan_qr_instruction') }}</p>
                            @else
                                <div
                                    class="w-72 h-72 flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse mb-4">
                                    <x-heroicon-o-qr-code class="w-16 h-16 text-gray-400 mb-2" />
                                    <span class="text-gray-500 text-sm">Generating QR...</span>
                                </div>
                            @endif
                        @endif

                        <x-filament::button wire:click="checkConnection" color="gray" class="mt-6">
                            {{ __('messages.refresh_status') }}
                        </x-filament::button>
                    </div>

                    {{-- 2. CHAT INTERFACE (CONNECTED & SELECTED) --}}
                @elseif($selectedJid)
                    @php
                        $activeChat = $this->chats->where('remote_jid', $selectedJid)->first();
                    @endphp

                    {{-- CHAT HEADER --}}
                    <div
                        class="h-16 px-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shrink-0 z-20">
                        <div class="flex items-center gap-3 cursor-pointer">
                            {{-- Avatar Header --}}
                            <div class="relative shrink-0" x-data="{ showFallback: false }">
                                <img src="{{ route('whatsapp.avatar', $selectedJid) }}"
                                    class="w-10 h-10 rounded-full object-cover bg-gray-200 dark:bg-gray-700" loading="lazy"
                                    x-show="!showFallback" x-on:error="showFallback = true">

                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-lg font-medium shadow-sm select-none"
                                    style="background-color: {{ '#' . substr(md5($selectedJid), 0, 6) }}"
                                    x-show="showFallback" style="display: none;">
                                    {{ strtoupper(substr($activeChat->effective_name ?? $activeChat->push_name ?? 'U', 0, 1)) }}
                                </div>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-semibold text-gray-800 dark:text-gray-100 text-sm">
                                    {{ $activeChat->effective_name ?? $activeChat->push_name ?? 'Unknown' }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $selectedJid }}
                                    </span>
                                    @if($isMember && $memberData)
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800">
                                            Member {{ $memberData->tier->name ?? '' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            {{-- Actions --}}
                            <button wire:click="refreshMessages"
                                class="p-2 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full transition">
                                <x-heroicon-o-arrow-path class="w-5 h-5" />
                            </button>
                            <x-filament::dropdown placement="bottom-end">
                                <x-slot name="trigger">
                                    <button
                                        class="p-2 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full transition">
                                        <x-heroicon-o-ellipsis-vertical class="w-5 h-5" />
                                    </button>
                                </x-slot>
                                <x-filament::dropdown.list>
                                    @if(!$isMember)
                                        <x-filament::dropdown.list.item wire:click="mountAction('createMember')"
                                            icon="heroicon-o-user-plus">
                                            {{ __('messages.register_member') }}
                                        </x-filament::dropdown.list.item>
                                    @endif

                                    <x-filament::dropdown.list.item wire:click="mountAction('createReservation')"
                                        icon="heroicon-o-calendar-days">
                                        {{ __('messages.create_reservation') }}
                                    </x-filament::dropdown.list.item>

                                    <x-filament::dropdown.list.item wire:click="deleteConversation" color="danger"
                                        icon="heroicon-o-trash">
                                        {{ __('messages.clear_chat') }}
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        </div>
                    </div>

                    {{-- MESSAGES SCROLL AREA --}}
                    <div class="flex-1 overflow-y-auto p-4 space-y-2 z-10 custom-scrollbar relative" id="chat-container"
                        wire:key="chat-container-{{ $selectedJid }}" x-data x-init="
                            $nextTick(() => $el.scrollTop = $el.scrollHeight); 
                            setTimeout(() => $el.scrollTop = $el.scrollHeight, 100);
                            setTimeout(() => $el.scrollTop = $el.scrollHeight, 300);
                        " x-on:chat-updated.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)">

                        @php $lastDate = null; @endphp
                        @forelse($activeChatMessages as $msg)
                            {{-- DATE DIVIDER --}}
                            @php
                                $msgDate = $msg->created_at->format('Y-m-d');
                                $displayDate = $msg->created_at->isToday() ? __('messages.today') : ($msg->created_at->isYesterday() ? __('messages.yesterday') : $msg->created_at->format('d M Y'));
                            @endphp

                            @if($msgDate !== $lastDate)
                                <div class="flex justify-center my-4 sticky top-0 z-10">
                                    <span
                                        class="bg-white/90 dark:bg-gray-800/90 text-gray-500 text-[11px] font-medium px-3 py-1 rounded-lg shadow-sm backdrop-blur-sm uppercase tracking-wide">
                                        {{ $displayDate }}
                                    </span>
                                </div>
                                @php $lastDate = $msgDate; @endphp
                            @endif

                            {{-- MESSAGE BUBBLE --}}
                            <div class="flex w-full {{ $msg->from_me ? 'justify-end' : 'justify-start' }} group mb-1">
                                <div
                                    class="relative max-w-[70%] sm:max-w-[60%] text-sm rounded-lg shadow-sm px-2 py-1.5 pr-8
                                                    {{ $msg->from_me ? 'bg-[#d9fdd3] dark:bg-[#005c4b] rounded-tr-none' : 'bg-white dark:bg-[#202c33] rounded-tl-none' }}">

                                    {{-- REPLY BUTTON (Visible on Hover) --}}
                                    <button wire:click="setReplyTo('{{ $msg->id }}')"
                                        class="absolute right-1 top-1 opacity-0 group-hover:opacity-100 transition p-1 hover:bg-black/10 dark:hover:bg-white/10 rounded-full text-gray-500 hover:text-purple-500 z-20">
                                        <x-heroicon-m-arrow-uturn-left class="w-3 h-3" />
                                    </button>

                                    {{-- Group Sender Name --}}
                                    @if(!$msg->from_me && str_contains($msg->remote_jid, '@g.us'))
                                        @php
                                            $senderJid = data_get($msg->full_message, 'key.participant') ?? data_get($msg->full_message, 'participant');
                                            $senderName = $msg->push_name ?? ($senderJid ? str_replace('@s.whatsapp.net', '', $senderJid) : 'Unknown');
                                            // Colorize based on sender for visual distinction
                                            $colorIndex = crc32($senderName) % 5;
                                            $colors = ['text-orange-500', 'text-pink-500', 'text-purple-500', 'text-blue-500', 'text-teal-500'];
                                            $senderColor = $colors[abs($colorIndex)];
                                        @endphp
                                        <p class="text-[11px] font-bold {{ $senderColor }} mb-0.5 leading-tight cursor-pointer hover:underline"
                                            wire:click="$set('newMessage', '@' . '{{ $senderJid ? str_replace('@s.whatsapp.net', '', $senderJid) : '' }} ')">
                                            {{ $senderName }}
                                        </p>
                                    @endif

                                    <div class="text-gray-800 dark:text-gray-100 leading-relaxed break-words relative">
                                        {{-- QUOTED MESSAGE PREVIEW --}}
                                        @php
                                            $fullMsg = $msg->full_message ?? [];
                                            $contextInfo = $fullMsg['message']['extendedTextMessage']['contextInfo']
                                                ?? $fullMsg['message']['imageMessage']['contextInfo']
                                                ?? $fullMsg['message']['videoMessage']['contextInfo']
                                                ?? $fullMsg['message']['documentMessage']['contextInfo']
                                                ?? $fullMsg['message']['audioMessage']['contextInfo']
                                                ?? null;

                                            $quotedMsg = $contextInfo['quotedMessage'] ?? null;
                                        @endphp

                                        @if($quotedMsg)
                                                            <div
                                                                class="mb-1.5 p-1.5 rounded-md bg-black/5 dark:bg-white/10 border-l-4 border-purple-500 text-xs text-gray-600 dark:text-gray-300 select-none cursor-pointer opacity-80">
                                                                <p class="font-bold text-[10px] text-purple-600 dark:text-purple-400 mb-0.5">
                                                                    @php
                                                                        $participant = $contextInfo['participant'] ?? '';
                                                                        $isMe = str_contains($participant, auth()->user()->phone ?? 'xx-xx') || (isset($contextInfo['isMe']) && $contextInfo['isMe']);
                                                                        $participantName = $isMe ? __('messages.you') : str_replace('@s.whatsapp.net', '', $participant);
                                                                    @endphp
                                                                    {{ $participantName }}
                                                                </p>
                                                                <p class="line-clamp-2">
                                                                    {{
                                            $quotedMsg['conversation']
                                            ?? $quotedMsg['extendedTextMessage']['text']
                                            ?? (isset($quotedMsg['imageMessage']) ? '📷 Photo' : null)
                                            ?? (isset($quotedMsg['videoMessage']) ? '🎥 Video' : null)
                                            ?? (isset($quotedMsg['audioMessage']) ? '🎵 Audio' : null)
                                            ?? (isset($quotedMsg['documentMessage']) ? '📄 Document' : null)
                                            ?? '...'
                                                                                                            }}
                                                                </p>
                                                            </div>
                                        @endif

                                        @if($msg->attachment_type)
                                            @if(!$msg->attachment_path)
                                                {{-- MEDIA NOT DOWNLOADED PLACEHOLDER --}}
                                                <div
                                                    class="flex items-center gap-3 p-3 bg-gray-100 dark:bg-gray-700/50 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 mb-2">
                                                    <div
                                                        class="w-10 h-10 flex items-center justify-center bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-full">
                                                        @if($msg->attachment_type === 'image') <x-heroicon-o-camera class="w-6 h-6" />
                                                        @elseif($msg->attachment_type === 'video') <x-heroicon-o-video-camera
                                                            class="w-6 h-6" />
                                                        @elseif($msg->attachment_type === 'audio') <x-heroicon-o-microphone
                                                            class="w-6 h-6" />
                                                        @else <x-heroicon-o-document-arrow-down class="w-6 h-6" />
                                                        @endif
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p
                                                            class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                            {{ $msg->attachment_type }} {{ __('messages.not_downloaded') }}
                                                        </p>
                                                        <button wire:click="downloadMedia({{ $msg->id }})" wire:loading.attr="disabled"
                                                            class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1 mt-0.5">
                                                            <span wire:loading.remove
                                                                wire:target="downloadMedia({{ $msg->id }})">{{ __('messages.download_now') }}</span>
                                                            <span wire:loading wire:target="downloadMedia({{ $msg->id }})"
                                                                class="flex items-center gap-1">
                                                                <svg class="animate-spin h-3 w-3 text-current"
                                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                        stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor"
                                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                    </path>
                                                                </svg>
                                                                {{ __('messages.downloading') }}
                                                            </span>
                                                        </button>
                                                    </div>
                                                </div>
                                            @elseif($msg->attachment_type === 'image')
                                                <div class="rounded-lg overflow-hidden mb-1 relative group-hover:brightness-95 transition cursor-pointer"
                                                    onclick="window.open('{{ Storage::url($msg->attachment_path) }}', '_blank')">
                                                    <img src="{{ Storage::url($msg->attachment_path) }}"
                                                        class="max-w-full md:max-w-sm object-cover">
                                                </div>
                                            @elseif($msg->attachment_type === 'video')
                                                <div class="rounded-lg overflow-hidden mb-1 bg-black flex items-center justify-center">
                                                    <video controls class="max-w-full md:max-w-sm max-h-[500px]">
                                                        <source src="{{ Storage::url($msg->attachment_path) }}">
                                                    </video>
                                                </div>
                                            @elseif($msg->attachment_type === 'audio')
                                                <div class="flex items-center gap-2 p-2 min-w-[200px]">
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center shrink-0">
                                                        <x-heroicon-s-microphone class="w-5 h-5 text-gray-500 dark:text-gray-300" />
                                                    </div>
                                                    <audio controls class="h-8 max-w-[200px]" preload="metadata">
                                                        <source src="{{ Storage::url($msg->attachment_path) }}">
                                                    </audio>
                                                </div>
                                            @elseif($msg->attachment_type === 'document')
                                                <a href="{{ Storage::url($msg->attachment_path) }}" target="_blank"
                                                    class="flex items-center gap-3 p-3 bg-gray-100 dark:bg-gray-700/50 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-600">
                                                    <div
                                                        class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-500 rounded">
                                                        <x-heroicon-s-document class="w-5 h-5" />
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-xs font-bold truncate">{{ $msg->caption ?? 'Document' }}</p>
                                                        <p class="text-[10px] opacity-60">Klik untuk melihat</p>
                                                    </div>
                                                </a>
                                            @endif

                                            @if($msg->caption)
                                                <p class="whitespace-pre-wrap mt-1">{{ $msg->caption }}</p>
                                            @endif
                                        @else
                                            <p class="whitespace-pre-wrap">{{ $msg->message }}</p>
                                        @endif
                                    </div>

                                    <div class="flex justify-end items-center gap-1 mt-0.5 select-none">
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                            {{ $msg->created_at->format('H:i') }}
                                        </span>
                                        @if($msg->from_me)
                                            @if($msg->status === 'read')
                                                {{-- READ: Double Blue Tick --}}
                                                <div class="flex relative">
                                                    <x-heroicon-m-check class="w-3 h-3 text-blue-500 relative -right-1" />
                                                    <x-heroicon-m-check class="w-3 h-3 text-blue-500" />
                                                </div>
                                            @elseif($msg->status === 'sent')
                                                {{-- SENT/DELIVERED: Double Gray Tick (approximated for now) --}}
                                                <div class="flex relative">
                                                    <x-heroicon-m-check class="w-3 h-3 text-gray-400 relative -right-1" />
                                                    <x-heroicon-m-check class="w-3 h-3 text-gray-400" />
                                                </div>
                                            @else
                                                {{-- PENDING: Clock --}}
                                                <x-heroicon-o-clock class="w-3 h-3 text-gray-400" />
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div
                                class="flex flex-col items-center justify-center h-full text-center p-8 bg-white/50 dark:bg-gray-900/50 rounded-lg m-4">
                                <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-full mb-4">
                                    <x-heroicon-o-chat-bubble-left-right class="w-8 h-8 text-gray-400" />
                                </div>
                                <p class="text-gray-500 font-medium">Belum ada pesan di sini.</p>
                                <p class="text-xs text-gray-400">Kirim pesan pertama Anda sekarang!</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- INPUT AREA --}}
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 shrink-0 z-20" x-data="{ isUploading: false }"
                        x-on:focus-input.window="$refs.messageInput.focus()">

                        {{-- Reply Context Preview --}}
                        @if($replyToMessage)
                            <div
                                class="absolute bottom-20 left-4 right-4 bg-white dark:bg-gray-700 p-3 rounded-xl shadow-lg border-l-4 border-purple-500 flex justify-between items-center animate-slide-up z-30">
                                <div class="flex flex-col overflow-hidden min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="text-xs font-bold text-purple-600 dark:text-purple-400">
                                            Replying to
                                            {{ $replyToMessage->from_me ? 'You' : $replyToMessage->push_name ?? 'Contact' }}
                                        </p>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 truncate">
                                        {{ $replyToMessage->message ?? $replyToMessage->caption ?? ($replyToMessage->attachment_type ? ucfirst($replyToMessage->attachment_type) : 'Message') }}
                                    </p>
                                </div>
                                <button wire:click="cancelReply"
                                    class="p-1 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-full transition text-gray-500">
                                    <x-heroicon-m-x-mark class="w-5 h-5" />
                                </button>
                            </div>
                        @endif

                        {{-- Attachment Preview --}}
                        @if($attachment)
                            <div
                                class="absolute bottom-20 left-4 right-4 bg-white dark:bg-gray-700 p-3 rounded-xl shadow-lg border border-gray-200 dark:border-gray-600 flex justify-between items-center animate-slide-up">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div
                                        class="w-10 h-10 bg-gray-100 dark:bg-gray-600 rounded-lg flex items-center justify-center shrink-0">
                                        @if(Str::startsWith($attachment->getMimeType(), 'image'))
                                            <img src="{{ $attachment->temporaryUrl() }}"
                                                class="w-full h-full object-cover rounded-lg">
                                        @else
                                            <x-heroicon-o-document class="w-6 h-6 text-gray-500" />
                                        @endif
                                    </div>
                                    <div class="flex flex-col overflow-hidden">
                                        <p class="text-sm font-medium truncate w-full">
                                            {{ $attachment->getClientOriginalName() }}
                                        </p>
                                        <p class="text-xs text-gray-500">{{ number_format($attachment->getSize() / 1024, 1) }}
                                            KB</p>
                                    </div>
                                </div>
                                <button wire:click="$set('attachment', null)"
                                    class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-full transition text-red-500">
                                    <x-heroicon-m-x-mark class="w-5 h-5" />
                                </button>
                            </div>
                        @endif

                        <form wire:submit="sendMessage" class="flex gap-2 items-center">
                            {{-- FILE INPUT --}}
                            <input type="file" wire:model="attachment" class="hidden" x-ref="fileInput"
                                accept="image/*,video/*,audio/*,application/pdf">

                            <button type="button" @click="$refs.fileInput.click()"
                                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                                <x-heroicon-o-paper-clip class="w-6 h-6 transform -rotate-45" />
                            </button>

                            {{-- AI GENERATE BUTTON --}}
                            <button type="button" wire:click="generateAiReply"
                                class="w-10 h-10 flex items-center justify-center text-purple-500 hover:bg-purple-50 hover:text-purple-700 dark:hover:bg-gray-700 dark:text-purple-400 dark:hover:text-purple-200 transition relative rounded-full"
                                title="Generate AI Reply" {{ $status !== 'connected' || $isGeneratingAi ? 'disabled' : '' }}>
                                <x-heroicon-o-sparkles class="w-6 h-6 {{ $isGeneratingAi ? 'animate-pulse' : '' }}" />
                                <div wire:loading wire:target="generateAiReply"
                                    class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-full">
                                    <x-filament::loading-indicator class="w-10 h-10 text-purple-600" />
                                </div>
                            </button>


                            {{-- TEXT INPUT --}}
                            <div class="flex-1 relative">
                                <textarea wire:ignore x-data="{ 
                                            content: $wire.entangle('newMessage', true),
                                            resize() { 
                                                $el.style.height = 'auto'; 
                                                $el.style.height = $el.scrollHeight + 'px';
                                            } 
                                        }" x-model="content" x-ref="messageInput"
                                    x-init="$watch('content', () => $nextTick(() => resize())); resize()" @input="resize()"
                                    @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); $wire.sendMessage(content); content = ''; $el.style.height = 'auto'; }"
                                    class="w-full rounded-lg border-none bg-white dark:bg-gray-700 py-3 pl-4 pr-10 focus:ring-0 shadow-sm placeholder-gray-400 text-gray-800 dark:text-gray-100 resize-none overflow-y-auto min-h-[48px] max-h-[120px]"
                                    rows="1" placeholder="{{ __('messages.type_message') }}" {{ $status !== 'connected' ? 'disabled' : '' }}></textarea>
                            </div>

                            {{-- SEND BUTTON --}}
                            <button type="submit"
                                class="p-3 bg-[#005c4b] hover:bg-[#004f40] text-white rounded-full shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                                wire:loading.attr="disabled" wire:target="sendMessage">
                                <x-heroicon-m-paper-airplane class="w-5 h-5" wire:loading.remove
                                    wire:target="sendMessage" />
                                <x-filament::loading-indicator class="w-5 h-5 text-white" wire:loading
                                    wire:target="sendMessage" />
                            </button>
                        </form>
                    </div>

                    {{-- 3. EMPTY STATE (WELCOME SCREEN) --}}
                @else
                    <div
                        class="flex-1 flex flex-col items-center justify-center text-center p-8 border-b-[6px] border-b-[#43c960]">
                        <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-full mb-6">
                            <x-heroicon-o-device-phone-mobile class="w-20 h-20 text-gray-400" />
                        </div>
                        <h2 class="text-2xl font-light text-gray-700 dark:text-gray-200 mb-2">
                            {{ __('messages.wa_welcome_title') }}
                        </h2>
                        <p class="text-sm text-gray-500 max-w-md">
                            {!! __('messages.wa_welcome_desc') !!}
                        </p>
                        <div class="mt-8 flex gap-2 text-xs text-gray-400">
                            <x-heroicon-m-lock-closed class="w-4 h-4" /> {{ __('messages.encrypted_locally') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- CUSTOM SCROLLBAR CSS --}}
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.5);
            border-radius: 20px;
            transition: background-color 0.2s ease;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(156, 163, 175, 0.7);
        }

        /* Smooth transitions for all interactive elements */
        .group {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Loading skeleton animation */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Slide up animation for messages */
        .animate-slide-up {
            animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Fade in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        /* Smooth hover effects */
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .hover-lift:hover {
            transform: translateY(-1px);
        }

        /* Wire loading indicator */
        [wire\:loading] {
            opacity: 0.6;
            pointer-events: none;
            transition: opacity 0.15s ease;
        }

        /* Smooth color transitions */
        * {
            transition-property: background-color, border-color, color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }

        /* Override for elements that shouldn't transition */
        input, textarea, select, button, a, [role="button"] {
            transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
        }

        /* Spin animation for loading spinner */
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>
</x-filament-panels::page>