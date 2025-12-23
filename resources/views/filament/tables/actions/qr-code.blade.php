@php
$settings = app(\App\Settings\GeneralSettings::class);
$url = route('order.scan', $record->slug);

$extractUsername = function ($value) {
if (!$value) return '';
if (!str_contains($value, '/') && !str_contains($value, '.')) {
return ltrim($value, '@');
}
$path = parse_url(str_starts_with($value, 'http') ? $value : 'https://' . $value, PHP_URL_PATH);
return ltrim(trim($path ?? '', '/'), '@');
};

$tableName = $record->name;
if (!str_contains(strtolower($tableName), 'meja')) {
$tableName = 'Meja ' . $tableName;
}
@endphp

<div x-data="{
        isPrinting: false,
        print() {
            this.isPrinting = true;
            const content = document.getElementById('printable-qr-{{ $record->id }}').innerHTML;
            const printWindow = window.open('', '_blank', 'width=600,height=800');
            
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Cetak QR - {{ $record->name }}</title>
                        <script src='https://cdn.tailwindcss.com'></script>
                        <link href='https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap' rel='stylesheet'>
                        <style>
                            body { font-family: 'Plus Jakarta Sans', sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                            @page { margin: 0; }
                            @media print {
                                body { background: white; margin: 0; padding: 2rem; display: flex; justify-content: center; align-items: flex-start; }
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body class='bg-gray-50'>
                        <div class='max-w-xs w-full bg-white p-8 rounded-3xl border border-gray-100 shadow-sm text-center flex flex-col items-center'>
                            ${content}
                        </div>
                        <script>
                            window.onload = function() {
                                setTimeout(() => {
                                    window.print();
                                    window.close();
                                }, 1000);
                            }
                        <\/script>
                    </body>
                </html>
            `);
            printWindow.document.close();
            this.isPrinting = false;
        }
    }"
    class="flex flex-col items-center gap-6 p-2">

    <!-- Premium Card Container -->
    <div id="printable-qr-{{ $record->id }}" class="bg-white p-8 rounded-[2.5rem] shadow-2xl border border-gray-50 max-w-sm w-full text-center relative overflow-hidden group">
        <!-- Background Decoration -->
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-primary-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity"></div>

        <!-- Header Section -->
        <div class="relative z-10 mb-8">
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight leading-none uppercase">
                {{ $settings->app_name }}
            </h2>
            <div class="flex items-center justify-center gap-2 mt-3">
                <span class="h-px w-6 bg-gray-200"></span>
                <span class="text-[10px] text-primary-600 font-bold tracking-[0.2em] uppercase">Digital Order Station</span>
                <span class="h-px w-6 bg-gray-200"></span>
            </div>
        </div>

        <!-- Main QR Frame -->
        <div class="relative z-10 p-6 bg-gray-50/50 rounded-[2rem] border border-gray-100 inline-block mb-6 group-hover:bg-primary-50/30 transition-colors duration-500">
            <div class="bg-white p-5 rounded-2xl shadow-sm inline-block ring-8 ring-white/50">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)
                ->color(17, 24, 39)
                ->margin(1)
                ->generate($url) !!}
            </div>

            <div class="mt-6">
                <div class="inline-flex items-center gap-2 px-6 py-2 bg-gray-900 rounded-full shadow-lg shadow-gray-200">
                    <span class="text-white text-xs font-black uppercase tracking-widest">{{ $tableName }}</span>
                </div>
            </div>
        </div>

        <!-- Footer / Social Section -->
        <div class="relative z-10 pt-4">
            <p class="text-xs font-bold text-gray-500 mb-6 flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-phone" viewBox="0 0 16 16">
                    <path d="M11 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM5 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                    <path d="M8 14a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
                </svg>

                Scan untuk Pesan Menu
            </p>

            <!-- Social Media Badges -->
            <div class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-6">
                @if($settings->app_instagram)
                <div class="flex flex-col items-center gap-1 p-2 bg-gray-50 rounded-xl border border-gray-100">
                    <x-filament::icon icon="heroicon-o-camera" class="w-5 h-5 text-pink-500" />
                    <span class="text-[10px] font-bold text-gray-600 truncate w-full">{{ $extractUsername($settings->app_instagram) }}</span>
                </div>
                @endif

                @if($settings->app_tiktok)
                <div class="flex flex-col items-center gap-1 p-2 bg-gray-50 rounded-xl border border-gray-100">
                    <svg class="w-4 h-4 text-black" fill="currentColor" viewBox="0 0 448 512">
                        <path d="M448 209.91a210.06 210.06 0 0 1-122.77-39.25V349.38A162.55 162.55 0 1 1 185 188.31V278.2a74.62 74.62 0 1 0 52.23 71.18V0l88 0a121.18 121.18 0 0 0 1.86 22.17A122.18 122.18 0 0 0 381 102.39a121.43 121.43 0 0 0 67 20.14Z" />
                    </svg>
                    <span class="text-[10px] font-bold text-gray-600 truncate w-full">{{ $extractUsername($settings->app_tiktok) }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Control Actions -->
    <div class="flex items-center justify-center gap-4 w-full max-w-sm px-4">
        <button
            @click="print"
            :disabled="isPrinting"
            class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-2xl font-bold shadow-lg shadow-primary-200 hover:bg-primary-700 active:scale-95 transition-all disabled:opacity-50">
            <x-filament::icon icon="heroicon-o-printer" class="w-5 h-5" />
            <span x-show="!isPrinting">Cetak QR</span>
            <span x-show="isPrinting">Menyiapkan...</span>
        </button>

        <a
            href="{{ $url }}"
            target="_blank"
            class="px-6 py-3 bg-white text-gray-700 border border-gray-200 rounded-2xl font-bold shadow-sm hover:bg-gray-50 active:scale-95 transition-all flex items-center gap-2">
            <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="w-5 h-5" />
            Tes
        </a>
    </div>
</div>