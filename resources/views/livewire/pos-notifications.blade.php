<div
    x-data="{ open: false }"
    x-on:play-notification-sound.window="if(window.PosSound) window.PosSound.play('success')"
    class="relative"
    wire:poll.5s>
    <!-- Bell Button -->
    <button
        @click="open = !open"
        @click.away="open = false"
        class="relative p-2 text-slate-400 hover:text-slate-600 transition-colors rounded-full hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <!-- Badge -->
        @if($unreadCount > 0)
        <div class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></div>
        @endif

        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
    </button>

    <!-- Dropdown Panel -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-slate-100 z-50 overflow-hidden"
        style="display: none;">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 class="font-bold text-slate-800 text-sm">Notifikasi</h3>
            @if($this->unreadCount > 0)
            <button
                wire:click="markAllAsRead"
                class="text-[10px] uppercase font-bold text-violet-600 hover:text-violet-700 hover:underline">
                Mark all read
            </button>
            @endif
        </div>

        <!-- List -->
        <div class="max-h-[300px] overflow-y-auto">
            @forelse($notifications as $notification)
            <div class="p-4 border-b border-slate-50 hover:bg-slate-50 transition-colors {{ $notification->read_at ? 'opacity-60' : '' }}">
                <div class="flex items-start gap-3">
                    <!-- Icon (Simplified) -->
                    <div class="flex-shrink-0 text-green-500 mt-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 line-clamp-1">
                            {{ $notification->data['title'] ?? 'Notifikasi' }}
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">
                            {{ $notification->data['body'] ?? '' }}
                        </p>

                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>

                            <!-- ACTIONS BUTTONS -->
                            @if(isset($notification->data['actions']) && is_array($notification->data['actions']))
                            <div class="flex gap-2">
                                @foreach($notification->data['actions'] as $action)
                                @php
                                $actionName = $action['name'] ?? 'Action';
                                $actionLabel = $action['label'] ?? $actionName;
                                $saleId = $notification->data['sale_id'] ?? null;
                                @endphp

                                @if($actionName === 'Lihat' && $saleId)
                                <button
                                    type="button"
                                    class="text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 px-3 py-1 rounded-md shadow-sm transition-all active:scale-95"
                                    @click="
                                                        $dispatch('saleLoaded', { saleId: '{{ $saleId }}' }); 
                                                        $wire.markAsRead('{{ $notification->id }}');
                                                        open = false;
                                                    ">
                                    {{ $actionLabel }}
                                </button>
                                @else
                                <a
                                    href="{{ $action['url'] ?? '#' }}"
                                    class="text-xs font-bold text-white bg-slate-500 hover:bg-slate-600 px-3 py-1 rounded-md shadow-sm transition-all active:scale-95"
                                    wire:click="markAsRead('{{ $notification->id }}')">
                                    {{ $actionLabel }}
                                </a>
                                @endif
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Read Indicator -->
                    @if(!$notification->read_at)
                    <div class="w-1.5 h-1.5 rounded-full bg-violet-500 mt-1.5"></div>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mx-auto mb-2 opacity-50">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <p class="text-xs">Tidak ada notifikasi baru</p>
            </div>
            @endforelse
        </div>
    </div>
</div>