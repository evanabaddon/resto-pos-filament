{{-- resource/views/layouts/pos-layout.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $title ?? 'POS System' }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
    <style>
        html,
        body {
            height: 100%;
            overflow: hidden;
            /* Prevent native scroll */
            touch-action: manipulation;
            /* Improve touch response */
            -webkit-tap-highlight-color: transparent;
        }

        /* Smooth Fade In */
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Font adjustment */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        @media (max-width: 1024px) {
            header {
                padding: 0.75rem 1rem;
            }

            button,
            [role="button"] {
                min-height: 44px;
                min-width: 44px;
            }
        }
    </style>
</head>

<body class="bg-slate-50 flex flex-col h-screen overflow-hidden antialiased text-slate-800">

    {{-- ========================= --}}
    {{-- 🔝 TOP NAVBAR (Glass) --}}
    {{-- ========================= --}}
    <header
        class="flex-shrink-0 flex items-center justify-between bg-white/80 backdrop-blur-md border-b border-slate-200/60 px-6 py-3 shadow-sm z-30 relative">
        <div class="flex items-center space-x-3">
            <div
                class="h-9 w-9 bg-gradient-to-br from-violet-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-violet-200">
                <span class="text-white font-black text-lg">P</span>
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-800 leading-tight">POS System</h1>
                <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase">Restaurant Edition</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Tombol Cash Summary --}}
            <livewire:cash-summary-button />

            {{-- Tombol Close Shift --}}
            <livewire:close-shift-button />

            <div class="hidden sm:block pl-3 border-l border-slate-200">
                <a href="{{ route('filament.admin.pages.dashboard') }}"
                    class="group bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 px-4 py-2 rounded-full text-sm font-bold cursor-pointer inline-flex items-center gap-2 transition-all active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 text-slate-400 group-hover:text-slate-600 transition-colors" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>
    </header>

    {{-- ========================= --}}
    {{-- MAIN CONTENT --}}
    {{-- ========================= --}}
    <main class="flex-1 overflow-hidden">
        {{ $slot }}
    </main>

    {{-- ========================= --}}
    {{-- 🔔 TOAST NOTIFICATIONS --}}
    {{-- ========================= --}}
    @include('livewire.pos-notification')

    {{-- Cash Summary Modal --}}
    <livewire:cash-summary-modal />

    @livewireScripts
    <script>
        document.addEventListener('livewire:load', function () {
            // Close Shift Button
            const closeShiftBtn = document.getElementById('close-shift-btn');
            if (closeShiftBtn) {
                closeShiftBtn.addEventListener('click', function () {
                    Livewire.emit('closeShiftFromLayout');
                });
            }

            // Cash Summary Button
            const cashSummaryBtn = document.getElementById('cash-summary-btn');
            if (cashSummaryBtn) {
                cashSummaryBtn.addEventListener('click', function () {
                    Livewire.emit('openCashSummaryModal');
                });
            }
        });
    </script>
</body>

</html>