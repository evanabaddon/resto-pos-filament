<x-filament-panels::page>
    <div class="fixed inset-0 top-[4rem] sm:top-[4.5rem] md:static bg-gray-50/20 dark:bg-gray-950/20 antialiased overflow-hidden rounded-xl md:h-[calc(100vh-12rem)] md:min-h-[600px]">
        <div class="flex flex-col h-full max-w-5xl mx-auto px-0 md:px-4 relative">

            {{-- Header (Mobile Friendly) --}}
            <div class="sm:hidden px-4 py-3 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-800 flex items-center justify-between z-10">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/30">
                        <x-heroicon-s-sparkles class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Tanya Bos (AI)</h2>
                        <div class="flex items-center space-x-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span class="text-[10px] text-gray-500">Online & Siap Analisis</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Chat History Container --}}
            <div
                id="chat-container"
                class="flex-1 overflow-y-auto px-4 py-6 space-y-6 sm:space-y-8 custom-scrollbar scroll-smooth"
                x-data="{ 
                    scrollToBottom() { 
                        this.$el.scrollTo({ top: this.$el.scrollHeight, behavior: 'smooth' }); 
                    } 
                }"
                x-init="scrollToBottom(); $watch('window.Livewire', () => setTimeout(scrollToBottom, 100))">
                @foreach($messages as $message)
                <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }} animate-in {{ $message['role'] === 'user' ? 'slide-in-from-right-4' : 'slide-in-from-left-4' }} duration-500">
                    <div class="flex flex-col {{ $message['role'] === 'user' ? 'items-end' : 'items-start' }} max-w-[90%] sm:max-w-[80%] md:max-w-[70%] lg:max-w-[65%]">

                        {{-- Role & Timestamp (Hidden on tight mobile) --}}
                        <div class="flex items-center space-x-2 mb-1.5 px-1 opacity-70">
                            @if($message['role'] === 'assistant')
                            <span class="text-[10px] font-black uppercase tracking-tighter text-primary-600 dark:text-primary-400">AI ASSISTANT</span>
                            @else
                            <span class="text-[10px] font-black uppercase tracking-tighter text-gray-500 dark:text-gray-400">BOS</span>
                            @endif
                        </div>

                        {{-- Chat Bubble --}}
                        <div class="relative group">
                            <div @class([ 'px-4 py-3 sm:px-6 sm:py-4 rounded-[1.5rem] shadow-sm transition-all duration-300' , 'bg-gradient-to-br from-primary-600 to-indigo-700 text-white rounded-tr-none'=> $message['role'] === 'user',
                                'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-100 dark:border-gray-700/50 rounded-tl-none' => $message['role'] === 'assistant',
                                ])>
                                <div class="text-[14px] sm:text-[15px] leading-relaxed prose prose-sm dark:prose-invert max-w-none prose-p:my-1 prose-strong:text-inherit">
                                    @php
                                    $content = e($message['content']);
                                    $content = preg_replace('/\*\*(.*?)\*\*/', '<strong class="font-bold underline decoration-primary-300/30">$1</strong>', $content);
                                    $content = nl2br($content);
                                    @endphp
                                    {!! $content !!}
                                </div>
                            </div>

                            {{-- Subtle Shadow Depth --}}
                            @if($message['role'] === 'assistant')
                            <div class="absolute -z-10 inset-0 bg-black/5 rounded-[1.5rem] blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- AI Typing Indicator (Sleeker) --}}
                <div wire:loading wire:target="sendMessage" class="flex justify-start animate-in fade-in slide-in-from-left-4 duration-300">
                    <div class="flex flex-col items-start max-w-[80%]">
                        <div class="flex items-center space-x-2 mb-1.5 px-2">
                            <span class="text-[10px] font-bold text-gray-400 animate-pulse uppercase tracking-widest">Berpikir...</span>
                        </div>
                        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-[1.5rem] rounded-tl-none px-6 py-4 border border-gray-100 dark:border-gray-700/50 shadow-sm">
                            <div class="flex space-x-2 items-center">
                                <div class="w-2 h-2 bg-primary-500 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-primary-400 rounded-full animate-bounce [animation-delay:-0.1s]"></div>
                                <div class="w-2 h-2 bg-primary-300 rounded-full animate-bounce [animation-delay:-0.2s]"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Input & Controls Section --}}
            <div class="px-4 pb-4 sm:pb-6 pt-2 bg-white/20 dark:bg-gray-950/20 backdrop-blur-sm border-t border-gray-100 dark:border-gray-800/50">
                <div class="max-w-4xl mx-auto space-y-4">

                    {{-- Quick Actions (Horizontal Scroller) --}}
                    <div class="flex overflow-x-auto pb-1 gap-3 no-scrollbar touch-pan-x">
                        @php
                        $quickActions = [
                        ['icon' => '📊', 'label' => 'Analisis Penjualan', 'msg' => 'Bagaimana performa penjualan 30 hari terakhir?'],
                        ['icon' => '🔥', 'label' => 'Menu Terlaris', 'msg' => 'Tampilkan top 5 menu terlaris dan berikan strategi peningkatannya.'],
                        ['icon' => '⚠️', 'label' => 'Cek Stok', 'msg' => 'Item apa saja yang stoknya kritis hari ini?'],
                        ['icon' => '💡', 'label' => 'Ide Promo', 'msg' => 'Berikan ide promo pendek untuk jam sepi restoran.'],
                        ];
                        @endphp
                        @foreach($quickActions as $action)
                        <button
                            wire:click="$set('userMessage', '{{ $action['msg'] }}')"
                            wire:loading.attr="disabled"
                            class="flex-none flex items-center space-x-2 px-4 py-2 rounded-xl bg-white/90 dark:bg-gray-800/90 border border-gray-200 dark:border-gray-700 hover:border-primary-500 transition-all shadow-sm active:scale-95 disabled:opacity-50">
                            <span class="text-sm">{{ $action['icon'] }}</span>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $action['label'] }}</span>
                        </button>
                        @endforeach
                    </div>

                    {{-- Main Input Bar --}}
                    <div class="relative flex items-end space-x-2">
                        <div class="relative flex-1 group">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-primary-600 to-indigo-600 rounded-[1.5rem] blur opacity-15 group-focus-within:opacity-40 transition duration-500"></div>

                            <div class="relative bg-white dark:bg-gray-900 rounded-[1.5rem] border border-gray-200 dark:border-gray-800 focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 shadow-xl overflow-hidden flex items-center transition-all duration-300">
                                <textarea
                                    rows="1"
                                    x-data="{ 
                                        resize() { 
                                            this.$el.style.height = '0px'; 
                                            this.$el.style.height = this.$el.scrollHeight + 'px' 
                                        } 
                                    }"
                                    x-init="resize()"
                                    @input="resize()"
                                    wire:model="userMessage"
                                    wire:keydown.enter.prevent="sendMessage"
                                    placeholder="Tanya Bos di sini..."
                                    class="flex-1 bg-transparent border-none focus:ring-0 focus:outline-none outline-none text-[15px] py-4 px-6 text-gray-800 dark:text-gray-100 placeholder-gray-400 resize-none max-h-32 sm:max-h-48 scrollbar-hide rounded-[1.5rem]"
                                    wire:loading.attr="disabled"></textarea>

                                @if(!empty($messages))
                                <button
                                    wire:click="clearChat"
                                    class="pr-4 py-4 text-gray-300 hover:text-danger-500 transition-colors"
                                    title="Clear Chat">
                                    <x-heroicon-s-trash class="w-5 h-5" />
                                </button>
                                @endif
                            </div>
                        </div>

                        <button
                            wire:click="sendMessage"
                            wire:loading.attr="disabled"
                            class="flex-shrink-0 w-14 h-14 flex items-center justify-center rounded-full bg-primary-600 hover:bg-primary-700 text-white transition-all transform active:scale-95 shadow-lg shadow-primary-500/40 disabled:opacity-50">
                            <x-heroicon-s-paper-airplane class="w-6 h-6" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Custom Scrollbar Sleek */
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.2);
            border-radius: 20px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.4);
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Mobile Specific Tweaks */
        @media (max-width: 640px) {
            .fi-main-ctn {
                padding: 0 !important;
            }

            .fi-content {
                padding: 0 !important;
            }
        }
    </style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const scrollContainer = document.getElementById('chat-container');
            const scrollToBottom = () => {
                if (scrollContainer) {
                    scrollContainer.scrollTo({
                        top: scrollContainer.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            };

            scrollToBottom();

            Livewire.hook('request', ({
                component,
                respond
            }) => {
                respond(() => {
                    setTimeout(scrollToBottom, 50);
                });
            });
        });
    </script>
</x-filament-panels::page>