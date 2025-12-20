<x-filament-panels::page class="h-full">
    <div wire:poll.2s="pollState" class="flex flex-col h-[calc(100vh-8rem)] -m-6">

        {{-- MAIN LAYOUT --}}
        <div class="flex flex-1 overflow-hidden bg-white dark:bg-gray-900 shadow-xl border-t border-gray-200 dark:border-gray-800">

            {{-- LEFT SIDEBAR (30% WIDTH) --}}
            <div class="w-full md:w-[350px] flex flex-col border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 z-10">
                
                {{-- SIDEBAR HEADER --}}
                <div class="h-16 px-4 flex items-center justify-between bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center overflow-hidden">
                                @if($userAvatar && $status === 'connected')
                                    <img src="{{ $userAvatar }}" class="w-full h-full object-cover">
                                @else
                                    <x-heroicon-s-user class="w-6 h-6 text-gray-500 dark:text-gray-400"/>
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
                                <span class="text-[10px] text-gray-500">Online</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        @if($status === 'connected')
                            <button wire:click="logout" title="Disconnect" class="p-2 rounded-full text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                <x-heroicon-o-power class="w-5 h-5" />
                            </button>
                        @else
                            <button wire:click="checkConnection" title="Refresh Status" class="p-2 rounded-full text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                <x-heroicon-o-arrow-path class="w-5 h-5 {{ $status === 'connecting' ? 'animate-spin' : '' }}" />
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
                            placeholder="Cari atau mulai chat baru">
                    </div>
                </div>

                {{-- CHAT LIST --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar" wire:poll.5s>
                    @forelse($this->chats as $chat)
                        <div wire:click="selectChat('{{ $chat->remote_jid }}')" 
                             class="group flex items-center gap-3 p-3 cursor-pointer transition border-b border-gray-100 dark:border-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-800 {{ $selectedJid === $chat->remote_jid ? 'bg-gray-100 dark:bg-gray-800' : '' }}">
                            
                            {{-- Avatar --}}
                            {{-- Avatar --}}
                            <div class="relative shrink-0">
                                <img src="http://127.0.0.1:3000/avatar/{{ $chat->remote_jid }}" 
                                     class="w-12 h-12 rounded-full object-cover bg-gray-200 dark:bg-gray-700" 
                                     loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                
                                <div class="w-12 h-12 rounded-full flex hidden items-center justify-center text-white text-lg font-medium shadow-sm select-none"
                                     style="background-color: {{ '#' . substr(md5($chat->remote_jid), 0, 6) }}">
                                    {{ strtoupper(substr($chat->effective_name ?? $chat->push_name ?? $chat->remote_jid, 0, 1)) }}
                                </div>
                            </div>

                            {{-- Chat Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-baseline mb-0.5">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                        {{ $chat->effective_name ?? $chat->push_name ?? str_replace('@s.whatsapp.net', '', $chat->remote_jid) }}
                                    </h3>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500 shrink-0">
                                        {{-- Using Carbon directly here if possible, else parsing string --}}
                                        {{ \Carbon\Carbon::parse($chat->last_message_time ?? now())->format('H:i') }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate pr-2">
                                        @if(str_contains($chat->remote_jid, '@g.us'))
                                            <span class="font-bold text-gray-600 dark:text-gray-300">
                                                {{ $chat->push_name ? explode(' ', $chat->push_name)[0] : 'User' }}:
                                            </span>
                                        @endif
                                        {{ $chat->message ?? 'Media' }}
                                    </p>
                                    
                                    @if($chat->unread_count > 0)
                                        <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-green-500 rounded-full">
                                            {{ $chat->unread_count }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        @if($search)
                             <div class="p-4 text-center flex flex-col items-center">
                                <p class="text-sm text-gray-500 mb-2">Tidak ada chat ditemukan.</p>
                                @if(preg_match('/^[0-9]+$/', $search) && strlen($search) >= 5)
                                    <x-filament::button wire:click="startNewChat('{{ $search }}')" size="sm" icon="heroicon-m-plus">
                                        Chat Baru: {{ $search }}
                                    </x-filament::button>
                                @endif
                             </div>
                        @else
                            <div class="p-8 text-center text-gray-400 text-sm">
                                Belum ada percakapan.
                            </div>
                        @endif
                    @endforelse
                </div>
            </div>

            {{-- RIGHT CONTENT (70% WIDTH) --}}
            <div class="hidden md:flex flex-1 flex-col h-full bg-[#efeae2] dark:bg-[#0b141a] relative"
                 x-data="{ 
                    isDragging: false,
                    handleDrop(e) {
                        this.isDragging = false;
                        let files = e.dataTransfer.files;
                        if (files.length > 0) {
                            this.uploadFile(files[0]);
                        }
                    },
                    handlePaste(e) {
                        if (e.clipboardData && e.clipboardData.files.length > 0) {
                            e.preventDefault();
                            let file = e.clipboardData.files[0];
                            
                            // Generate a safer file object
                            const type = file.type || 'image/png';
                            const ext = type.split('/')[1] || 'png';
                            const filename = `paste-${new Date().getTime()}.${ext}`;

                            // Recreate file to ensure name and type are explicit
                            const newFile = new File([file.slice(0, file.size, type)], filename, { type: type });
                            
                            this.uploadFile(newFile);
                        }
                    },
                    uploadFile(file) {
                        $wire.upload('attachment', file, (uploadedFilename) => {
                            // Success
                        }, (error) => {
                            console.error('Upload Error:', error);
                            new FilamentNotification()
                                .title('Upload Gagal')
                                .body(error?.message || 'Gagal memproses file. Pastikan format didukung.')
                                .danger()
                                .send();
                        });
                    }
                 }"
                 x-on:dragover.prevent="isDragging = true"
                 x-on:dragleave.prevent="isDragging = false"
                 x-on:drop.prevent="handleDrop($event)"
                 x-on:paste.window="handlePaste($event)">
                
                {{-- DRAG OVERLAY --}}
                <div x-show="isDragging" 
                     x-transition.opacity
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
                    <div class="flex-1 flex flex-col items-center justify-center p-8 z-50 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm">
                        <h3 class="text-xl font-bold mb-4 text-gray-800 dark:text-white">WhatsApp Disconnected</h3>
                        
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
                                <p class="text-gray-600 dark:text-gray-300 font-medium">Scan QR Code dengan WhatsApp Anda</p>
                                <p class="text-sm text-gray-500 mt-2">Menu > Perangkat Tertaut > Tautkan Perangkat</p>
                            @else
                                <div class="w-72 h-72 flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse mb-4">
                                    <x-heroicon-o-qr-code class="w-16 h-16 text-gray-400 mb-2" />
                                    <span class="text-gray-500 text-sm">Generating QR...</span>
                                </div>
                            @endif
                        @endif

                        <x-filament::button wire:click="checkConnection" color="gray" class="mt-6">
                            Refresh Status
                        </x-filament::button>
                    </div>

                {{-- 2. CHAT INTERFACE (CONNECTED & SELECTED) --}}
                @elseif($selectedJid)
                    @php
                        $activeChat = $this->chats->where('remote_jid', $selectedJid)->first();
                    @endphp

                    {{-- CHAT HEADER --}}
                    <div class="h-16 px-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shrink-0 z-20">
                        <div class="flex items-center gap-3 cursor-pointer">
                            {{-- Avatar Header --}}
                            <div class="relative shrink-0">
                                <img src="http://127.0.0.1:3000/avatar/{{ $selectedJid }}" 
                                     class="w-10 h-10 rounded-full object-cover bg-gray-200 dark:bg-gray-700" 
                                     loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                
                                <div class="w-10 h-10 rounded-full flex hidden items-center justify-center text-white text-lg font-medium shadow-sm select-none"
                                     style="background-color: {{ '#' . substr(md5($selectedJid), 0, 6) }}">
                                    {{ strtoupper(substr($activeChat->effective_name ?? $activeChat->push_name ?? 'U', 0, 1)) }}
                                </div>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-semibold text-gray-800 dark:text-gray-100 text-sm">
                                    {{ $activeChat->effective_name ?? $activeChat->push_name ?? 'Unknown' }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $selectedJid }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <button wire:click="refreshMessages" class="p-2 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full transition">
                                <x-heroicon-o-arrow-path class="w-5 h-5" />
                            </button>
                            <x-filament::dropdown placement="bottom-end">
                                <x-slot name="trigger">
                                    <button class="p-2 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full transition">
                                        <x-heroicon-o-ellipsis-vertical class="w-5 h-5" />
                                    </button>
                                </x-slot>
                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item wire:click="deleteConversation" color="danger" icon="heroicon-o-trash">
                                        Bersihkan Chat
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        </div>
                    </div>

                    {{-- MESSAGES SCROLL AREA --}}
                    <div class="flex-1 overflow-y-auto p-4 space-y-2 z-10 custom-scrollbar relative" 
                         id="chat-container" 
                         x-data 
                         x-init="$el.scrollTop = $el.scrollHeight"
                         x-on:chat-updated.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                        
                        @php $lastDate = null; @endphp
                        @forelse($activeChatMessages as $msg)
                            {{-- DATE DIVIDER --}}
                            @php
                                $msgDate = $msg->created_at->format('Y-m-d');
                                $displayDate = $msg->created_at->isToday() ? 'Hari Ini' : ($msg->created_at->isYesterday() ? 'Kemarin' : $msg->created_at->format('d M Y'));
                            @endphp
                            
                            @if($msgDate !== $lastDate)
                                <div class="flex justify-center my-4 sticky top-0 z-10">
                                    <span class="bg-white/90 dark:bg-gray-800/90 text-gray-500 text-[11px] font-medium px-3 py-1 rounded-lg shadow-sm backdrop-blur-sm uppercase tracking-wide">
                                        {{ $displayDate }}
                                    </span>
                                </div>
                                @php $lastDate = $msgDate; @endphp
                            @endif

                            {{-- MESSAGE BUBBLE --}}
                            <div class="flex w-full {{ $msg->from_me ? 'justify-end' : 'justify-start' }} group mb-1">
                                <div class="relative max-w-[70%] sm:max-w-[60%] text-sm rounded-lg shadow-sm px-2 py-1.5
                                    {{ $msg->from_me ? 'bg-[#d9fdd3] dark:bg-[#005c4b] rounded-tr-none' : 'bg-white dark:bg-[#202c33] rounded-tl-none' }}">
                                    
                                    @if(!$msg->from_me && str_contains($msg->remote_jid, '@g.us'))
                                        <p class="text-[11px] font-bold text-orange-500 mb-0.5 leading-tight">
                                            {{ $msg->push_name ?? substr($msg->remote_jid, 0, 5) }}
                                        </p>
                                    @endif

                                    <div class="text-gray-800 dark:text-gray-100 leading-relaxed break-words relative">
                                        @if($msg->attachment_type)
                                            @if($msg->attachment_type === 'image')
                                                <div class="rounded-lg overflow-hidden mb-1 relative group-hover:brightness-95 transition cursor-pointer" onclick="window.open('{{ Storage::url($msg->attachment_path) }}', '_blank')">
                                                    <img src="{{ Storage::url($msg->attachment_path) }}" class="max-w-full md:max-w-sm object-cover">
                                                </div>
                                            @elseif($msg->attachment_type === 'video')
                                                <div class="rounded-lg overflow-hidden mb-1 bg-black">
                                                    <video controls class="max-w-full md:max-w-sm max-h-[300px]">
                                                        <source src="{{ Storage::url($msg->attachment_path) }}">
                                                    </video>
                                                </div>
                                            @elseif($msg->attachment_type === 'audio')
                                                <div class="flex items-center gap-2 p-2 min-w-[200px]">
                                                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center shrink-0">
                                                        <x-heroicon-s-microphone class="w-5 h-5 text-gray-500 dark:text-gray-300" />
                                                    </div>
                                                    <audio controls class="h-8 max-w-[200px]" preload="metadata">
                                                        <source src="{{ Storage::url($msg->attachment_path) }}">
                                                    </audio>
                                                </div>
                                            @elseif($msg->attachment_type === 'document')
                                                <a href="{{ Storage::url($msg->attachment_path) }}" target="_blank" 
                                                   class="flex items-center gap-3 p-3 bg-gray-100 dark:bg-gray-700/50 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-600">
                                                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-500 rounded">
                                                        <x-heroicon-s-document class="w-5 h-5" />
                                                    </div>
                                                    <div class="overflow-hidden">
                                                        <p class="truncate font-medium text-xs">{{ basename($msg->attachment_path) }}</p>
                                                        <p class="text-[10px] text-gray-500">Document</p>
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
                            <div class="flex flex-col items-center justify-center h-full text-center p-8 bg-white/50 dark:bg-gray-900/50 rounded-lg m-4">
                                <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-full mb-4">
                                    <x-heroicon-o-chat-bubble-left-right class="w-8 h-8 text-gray-400" />
                                </div>
                                <p class="text-gray-500 font-medium">Belum ada pesan di sini.</p>
                                <p class="text-xs text-gray-400">Kirim pesan pertama Anda sekarang!</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- INPUT AREA --}}
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 shrink-0 z-20" x-data="{ isUploading: false }">
                        
                        {{-- Attachment Preview --}}
                        @if($attachment)
                            <div class="absolute bottom-20 left-4 right-4 bg-white dark:bg-gray-700 p-3 rounded-xl shadow-lg border border-gray-200 dark:border-gray-600 flex justify-between items-center animate-slide-up">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="w-10 h-10 bg-gray-100 dark:bg-gray-600 rounded-lg flex items-center justify-center shrink-0">
                                        @if(Str::startsWith($attachment->getMimeType(), 'image'))
                                            <img src="{{ $attachment->temporaryUrl() }}" class="w-full h-full object-cover rounded-lg">
                                        @else
                                            <x-heroicon-o-document class="w-6 h-6 text-gray-500" />
                                        @endif
                                    </div>
                                    <div class="flex flex-col overflow-hidden">
                                        <p class="text-sm font-medium truncate w-full">{{ $attachment->getClientOriginalName() }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($attachment->getSize() / 1024, 1) }} KB</p>
                                    </div>
                                </div>
                                <button wire:click="$set('attachment', null)" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-full transition text-red-500">
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

                            {{-- TEXT INPUT --}}
                            <div class="flex-1 relative">
                                <input wire:model="newMessage" type="text" 
                                    class="w-full rounded-lg border-none bg-white dark:bg-gray-700 py-3 pl-4 pr-10 focus:ring-0 shadow-sm placeholder-gray-400 text-gray-800 dark:text-gray-100"
                                    placeholder="Ketik pesan..."
                                    {{ $status !== 'connected' ? 'disabled' : '' }}>
                            </div>

                            {{-- SEND BUTTON --}}
                            <button type="submit" 
                                    class="p-3 bg-[#005c4b] hover:bg-[#004f40] text-white rounded-full shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                                    wire:loading.attr="disabled" wire:target="sendMessage">
                                <x-heroicon-m-paper-airplane class="w-5 h-5" wire:loading.remove wire:target="sendMessage" />
                                <x-filament::loading-indicator class="w-5 h-5 text-white" wire:loading wire:target="sendMessage" />
                            </button>
                        </form>
                    </div>

                {{-- 3. EMPTY STATE (WELCOME SCREEN) --}}
                @else
                    <div class="flex-1 flex flex-col items-center justify-center text-center p-8 border-b-[6px] border-b-[#43c960]">
                        <div class="bg-gray-100 dark:bg-gray-800 p-6 rounded-full mb-6">
                            <x-heroicon-o-device-phone-mobile class="w-20 h-20 text-gray-400" />
                        </div>
                        <h2 class="text-2xl font-light text-gray-700 dark:text-gray-200 mb-2">WhatsApp Web for Resto POS</h2>
                        <p class="text-sm text-gray-500 max-w-md">
                            Kirim dan terima pesan WhatsApp langsung dari dashboard admin Anda. <br>
                            Pilih percakapan di sebelah kiri untuk mulai mengobrol.
                        </p>
                        <div class="mt-8 flex gap-2 text-xs text-gray-400">
                            <x-heroicon-m-lock-closed class="w-4 h-4" /> End-to-end encrypted locally
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
        }
        .animate-slide-up {
            animation: slideUp 0.2s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-filament-panels::page>