<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Menu Engineering Matrix</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Analisis Profitabilitas vs Popularitas (30 Hari Terakhir)</p>
            </div>

            @if($lastGeneratedAt)
            <div class="flex items-center gap-4">
                <x-filament::button
                    wire:click="exportPdf"
                    icon="heroicon-m-document-arrow-down"
                    color="gray"
                    size="sm"
                    wire:loading.attr="disabled">
                    Export PDF
                </x-filament::button>

                <div class="flex items-center gap-2 text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-full">
                    <x-heroicon-m-clock class="w-4 h-4" />
                    Dianalisa: {{ $lastGeneratedAt }}
                    <button wire:click="generateMatrix"
                        wire:loading.attr="disabled"
                        class="ml-2 hover:text-primary-500 transition-colors disabled:opacity-50 group">
                        <x-heroicon-m-arrow-path class="w-4 h-4 group-hover:rotate-180 transition-transform duration-500"
                            wire:loading.class="animate-spin"
                            wire:target="generateMatrix" />
                    </button>
                </div>
            </div>
            @endif
        </div>

        @if(!$matrixData || empty($matrixData['items']))
        <div class="flex flex-col items-center justify-center py-12 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
            <x-heroicon-o-chart-bar-square class="w-16 h-16 text-gray-300 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Belum Ada Analisis</h3>
            <p class="text-sm text-gray-500 mb-6 text-center max-w-xs">Scan performa menu Anda untuk melihat klasifikasi Stars, Plowhorses, Puzzles, dan Dogs.</p>
            <x-filament::button wire:click="generateMatrix" size="lg" icon="heroicon-m-sparkles" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generateMatrix">Generate Analisis Sekarang</span>
                <span wire:loading wire:target="generateMatrix">Sedang Menganalisa...</span>
            </x-filament::button>
        </div>
        @else
        <!-- AI Insight Section -->
        @if($aiAdvice)
        <div class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-900/10 dark:to-gray-900 p-6 rounded-2xl border border-indigo-100 dark:border-indigo-500/20 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-indigo-500 rounded-lg text-white">
                    <x-heroicon-m-sparkles class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="font-bold text-indigo-900 dark:text-indigo-400">AI Strategic Insights</h3>
                    <p class="text-xs text-indigo-700/60 dark:text-indigo-400/60">{{ app(\App\Settings\GeneralSettings::class)->ai_assistant_name }} Analysis</p>
                </div>
            </div>

            <div class="prose prose-sm dark:prose-invert max-w-none">
                <p class="text-gray-700 dark:text-gray-300 italic">"{{ $aiAdvice['overall_analysis'] ?? 'AI sedang menganalisis matrix Anda...' }}"</p>

                @if(isset($aiAdvice['top_priorities']))
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($aiAdvice['top_priorities'] as $priority)
                    <span class="bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-400 px-3 py-1 rounded-lg text-xs font-bold border border-indigo-200 dark:border-indigo-500/30">
                        📍 {{ $priority }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach(collect($aiAdvice['strategic_advice'] ?? [])->take(4) as $advice)
                <div class="bg-white/50 dark:bg-gray-800/50 p-4 rounded-xl border border-white dark:border-gray-700">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $advice['product_name'] }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-black 
                                    {{ match($advice['category'] ?? '') {
                                        'UNIT UNGGULAN' => 'bg-emerald-100 text-emerald-700',
                                        'UNIT ANDALAN' => 'bg-amber-100 text-amber-700',
                                        'UNIT POTENSIAL' => 'bg-indigo-100 text-indigo-700',
                                        default => 'bg-rose-100 text-rose-700'
                                    } }}">
                            {{ $advice['category'] }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">{{ $advice['advice'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Matrix Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @php
            $grouped = collect($matrixData['items'])->groupBy('category');
            @endphp

            @foreach(['UNIT UNGGULAN' => ['bg-emerald-500', 'Unit Unggulan', 'Kinerja tinggi & potensi tinggi'],
            'UNIT ANDALAN' => ['bg-amber-500', 'Unit Andalan', 'Kinerja tinggi tapi pertumbuhan rendah'],
            'UNIT POTENSIAL' => ['bg-indigo-500', 'Unit Potensial', 'Potensi tinggi tapi kinerja belum optimal'],
            'UNIT KURANG BERKEMBANG' => ['bg-rose-500', 'Unit Kurang Berkembang', 'Kinerja & potensi rendah']] as $cat => $style)
            <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-16 h-16 {{ $style[0] }} opacity-5 -mr-4 -mt-4 rounded-full transition-transform group-hover:scale-150"></div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-2 h-2 rounded-full {{ $style[0] }}"></div>
                    <h4 class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $style[1] }}</h4>
                </div>
                <div class="text-2xl font-black text-gray-900 dark:text-white">{{ $grouped->get($cat)?->count() ?: 0 }}</div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">{{ $style[2] }}</p>
            </div>
            @endforeach
        </div>

        <!-- Matrix Table -->
        @php
        $items = collect($matrixData['items'])->sortByDesc('popularity');
        $maxPop = $items->max('popularity') ?: 1;
        @endphp
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto overflow-y-hidden">
                <table class="w-full table-auto divide-y divide-gray-200 dark:divide-white/5 text-left">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Produk</th>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Kategori</th>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Popularitas (Qty)</th>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Margin (Untung)</th>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">HPP & Jual</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($items as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900 dark:text-white">{{ $item['name'] }}</span>
                                    <span class="text-[10px] text-gray-400 uppercase tracking-widest">{{ $item['type'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                        {{ match($item['category']) {
                                            'UNIT UNGGULAN' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                            'UNIT ANDALAN' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                            'UNIT POTENSIAL' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                                            default => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                        } }}">
                                    {{ $item['category'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold">{{ number_format($item['popularity'], 0) }}</span>
                                    <div class="w-16 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                        @php
                                        $width = min(100, ($item['popularity'] / $maxPop) * 100);
                                        @endphp
                                        <div class="h-full bg-primary-500" style="width: {{ $width }}%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                                    Rp {{ number_format($item['margin'], 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-[10px] text-gray-500">
                                    <div>HPP: Rp{{ number_format($item['cogs'], 0, ',', '.') }}</div>
                                    <div class="font-bold text-gray-900 dark:text-white">Jual: Rp{{ number_format($item['sell_price'], 0, ',', '.') }}</div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>