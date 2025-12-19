<x-filament-panels::page>
    <div class="flex flex-col space-y-6 h-[calc(100vh-14rem)] antialiased">
        {{-- Chat History Section --}}
        <div class="flex-1 overflow-y-auto px-4 py-6 rounded-2xl bg-white/40 dark:bg-gray-900/40 border border-gray-200/50 dark:border-gray-800/50 backdrop-blur-xl shadow-inner custom-scrollbar scroll-smooth" id="chat-container">
            <div class="max-w-4xl mx-auto space-y-8 pb-4">
                @foreach($messages as $message)
                <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }} group transition-all duration-300">
                    <div class="flex flex-col {{ $message['role'] === 'user' ? 'items-end' : 'items-start' }} max-w-[85%] sm:max-w-[75%]">
                        {{-- Role Label --}}
                        <div class="flex items-center space-x-2 mb-2 px-1">
                            @if($message['role'] === 'assistant')
                            <div class="w-1.5 h-1.5 rounded-full bg-primary-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">AI Business Assistant</span>
                            @else
                            <span class="text-[10px] font-bold uppercase tracking-widest text-primary-600 dark:text-primary-400">Owner</span>
                            @endif
                        </div>

                        {{-- Message Bubble --}}
                        <div class="relative px-5 py-4 shadow-xl transition-all duration-300 {{ $message['role'] === 'user' 
                                ? 'bg-gradient-to-br from-primary-600 to-primary-700 text-white rounded-[1.5rem] rounded-tr-none hover:shadow-primary-500/20' 
                                : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-[1.5rem] rounded-tl-none border border-gray-100 dark:border-gray-700/50 hover:shadow-black/5' }}">

                            <div class="text-[14px] leading-[1.6] select-text">
                                @php
                                // Simple markdown-to-html conversion for bold and lists
                                $content = e($message['content']);
                                $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
                                $content = nl2br($content);
                                @endphp
                                {!! $content !!}
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- Thinking Indicator (Wire Loading) --}}
                <div wire:loading wire:target="sendMessage, processAiResponse" class="flex justify-start">
                    <div class="flex flex-col items-start max-w-[80%] animate-in fade-in slide-in-from-left-2 duration-300">
                        <div class="flex items-center space-x-2 mb-2 px-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-pulse"></div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400 italic">Asisten sedang menganalisis...</span>
                        </div>
                        <div class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-md rounded-[1.5rem] rounded-tl-none px-6 py-4 border border-gray-100 dark:border-gray-700/50 shadow-lg">
                            <div class="flex space-x-1.5 items-center h-4">
                                <div class="w-2 h-2 bg-primary-500 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                                <div class="w-2 h-2 bg-primary-400 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                                <div class="w-2 h-2 bg-primary-600 rounded-full animate-bounce"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input and Quick Actions --}}
        <div class="max-w-4xl mx-auto w-full space-y-4">
            {{-- Quick Actions --}}
            <div class="flex overflow-x-auto pb-2 gap-3 custom-scrollbar no-scrollbar">
                @php
                $quickActions = [
                ['icon' => '📊', 'label' => 'Analisis Penjualan', 'msg' => 'Bagaimana performa penjualan 30 hari terakhir?'],
                ['icon' => '🔥', 'label' => 'Menu Terlaris', 'msg' => 'Tampilkan top 5 menu terlaris dan berikan strategi peningkatannya.'],
                ['icon' => '⚠️', 'label' => 'Cek Stok', 'msg' => 'Bahan apa saja yang stoknya kritis?'],
                ['icon' => '💡', 'label' => 'Ide Promo', 'msg' => 'Berikan ide promo kreatif untuk menaikkan transaksi di jam sepi.'],
                ];
                @endphp
                @foreach($quickActions as $action)
                <button
                    wire:click="$set('userMessage', '{{ $action['msg'] }}')"
                    wire:loading.attr="disabled"
                    class="flex-none flex items-center space-x-2 px-5 py-2.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-950/30 transition-all duration-300 shadow-sm active:scale-95 group disabled:opacity-50 disabled:grayscale">
                    <span class="text-sm group-hover:scale-125 transition-transform duration-300">{{ $action['icon'] }}</span>
                    <span class="text-[12px] font-bold text-gray-700 dark:text-gray-300">{{ $action['label'] }}</span>
                </button>
                @endforeach
            </div>

            {{-- Main Input Container --}}
            <div class="relative group">
                <div class="absolute -inset-0.5 bg-gradient-to-r from-primary-600 via-indigo-500 to-primary-600 rounded-2xl blur opacity-20 group-focus-within:opacity-40 transition duration-500 animate-gradient-x"></div>

                <div class="relative bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-2xl flex items-center p-2.5 backdrop-blur-md">
                    <input
                        type="text"
                        wire:model="userMessage"
                        wire:keydown.enter="sendMessage"
                        placeholder="Ketik pesan untuk analisa bisnis..."
                        class="flex-1 bg-transparent border-none focus:ring-0 text-[15px] py-4 px-5 text-gray-800 dark:text-gray-100 placeholder-gray-400 font-medium"
                        wire:loading.attr="disabled"
                        wire:target="sendMessage">

                    <div class="flex items-center space-x-3 pr-2">
                        @if(!empty($messages))
                        <button
                            wire:click="clearChat"
                            wire:loading.attr="disabled"
                            class="p-3 rounded-xl text-gray-400 hover:text-danger-500 hover:bg-danger-50 dark:hover:bg-danger-900/20 transition-all duration-200"
                            title="Bersihkan Chat">
                            <x-heroicon-o-trash class="w-6 h-6" />
                        </button>
                        @endif

                        <button
                            wire:click="sendMessage"
                            wire:loading.attr="disabled"
                            wire:target="sendMessage"
                            class="flex items-center justify-center w-14 h-14 rounded-xl bg-primary-600 hover:bg-primary-700 text-white transition-all transform active:scale-95 disabled:opacity-50 disabled:grayscale shadow-lg shadow-primary-500/30 group/btn">
                            <x-heroicon-s-chevron-right class="w-8 h-8 group-hover/btn:translate-x-0.5 transition-transform" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.02);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.2);
            border-radius: 10px;
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

        @keyframes gradient-x {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .animate-gradient-x {
            background-size: 200% 200%;
            animation: gradient-x 15s ease infinite;
        }
    </style>

    <script>
        document.addEventListener('livewire:navigated', () => {
            const container = document.getElementById('chat-container');

            const scrollToBottom = () => {
                if (container) {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            }

            // Scroll on load
            setTimeout(scrollToBottom, 100);

            // Listen for message updates
            Livewire.hook('request', ({
                component,
                respond
            }) => {
                respond(() => {
                    setTimeout(scrollToBottom, 50);
                });
            });
        });

        // Fallback for initial load
        window.addEventListener('load', () => {
            const container = document.getElementById('chat-container');
            if (container) container.scrollTop = container.scrollHeight;
        });
    </script>
</x-filament-panels::page>