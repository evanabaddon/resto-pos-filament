{{-- resource/views/layouts/pos-layout.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
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
        @inject('settings', 'App\Settings\GeneralSettings')
        <div class="flex items-center space-x-3">
            <div
                class="h-9 w-9 bg-gradient-to-br from-violet-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-violet-200">
                <span class="text-white font-black text-lg">{{ substr($settings->app_name ?? 'P', 0, 1) }}</span>
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-800 leading-tight">{{ $settings->app_name ?? 'POS System' }}
                </h1>
                <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase">Restaurant Edition</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Tombol Cash Summary --}}
            <livewire:cash-summary-button />

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
        const PosSound = {
            ctx: null,

            init() {
                if (!this.ctx) {
                    this.ctx = new (window.AudioContext || window.webkitAudioContext)();
                }
            },

            play(type) {
                // User gesture interaction is required to start audio context
                this.init();
                if (this.ctx.state === 'suspended') {
                    this.ctx.resume();
                }

                const now = this.ctx.currentTime;

                if (type === 'click') {
                    this.playTone(800, 'sine', 0.1, 0.05);
                }
                else if (type === 'add') {
                    // Soft pop
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);

                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(400, now);
                    osc.frequency.exponentialRampToValueAtTime(600, now + 0.1);

                    gain.gain.setValueAtTime(0.1, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.1);

                    osc.start(now);
                    osc.stop(now + 0.15);
                }
                else if (type === 'success') {
                    // Success Chime (Major Arpeggio: C5 - E5 - G5)
                    this.playTone(523.25, 'sine', 0.1, 0.1, 0);       // C5
                    this.playTone(659.25, 'sine', 0.1, 0.1, 0.1);     // E5
                    this.playTone(783.99, 'sine', 0.2, 0.1, 0.2);     // G5
                }
                else if (type === 'error') {
                    // Error Buzz (Sawtooth drop)
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);

                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(150, now);
                    osc.frequency.linearRampToValueAtTime(100, now + 0.3);

                    gain.gain.setValueAtTime(0.2, now);
                    gain.gain.linearRampToValueAtTime(0.01, now + 0.3);

                    osc.start(now);
                    osc.stop(now + 0.35);
                }
            },

            playTone(freq, type, duration, volume, delay = 0) {
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.connect(gain);
                gain.connect(this.ctx.destination);

                const now = this.ctx.currentTime + delay;

                osc.type = type;
                osc.frequency.setValueAtTime(freq, now);

                gain.gain.setValueAtTime(volume, now);
                gain.gain.exponentialRampToValueAtTime(0.01, now + duration);

                osc.start(now);
                osc.stop(now + duration + 0.05);
            }
        };

        window.PosSound = PosSound;
    </script>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered:', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed:', registrationError);
                    });
            });
        }
    </script>

    <script src="/js/offline-pos.js"></script>

    <script>
        document.addEventListener('livewire:init', function () {


            // Cash Summary Button
            const cashSummaryBtn = document.getElementById('cash-summary-btn');
            if (cashSummaryBtn) {
                cashSummaryBtn.addEventListener('click', function () {
                    Livewire.dispatch('openCashSummaryModal');
                });
            }

            // Listen for reload page event
            Livewire.on('reload-page', () => {
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            });
        });
    </script>
</body>

</html>