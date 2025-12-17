{{-- resource/views/layouts/kds-layout.blade.php --}}
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
    <title>{{ $title ?? 'KDS System' }}</title>
    @vite(['resources/css/app.css', 'resources/css/filament/admin/theme.css'])
    @livewireStyles
    <style>
        html,
        body {
            height: 100%;
            overflow: hidden;
            touch-action: manipulation;
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

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
    </style>
    @filamentStyles
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
                class="hidden sm:flex h-9 w-9 bg-gradient-to-br from-green-600 to-emerald-600 rounded-xl items-center justify-center shadow-lg shadow-green-200">
                <span class="text-white font-black text-lg">K</span>
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-800 leading-tight">Kitchen Display System</h1>
                <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase">{{ $settings->app_name ?? 'Resto POS' }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Custom POS Notifications (Waiters use this, but KDS might need to hear sounds) --}}
            <livewire:pos-notifications />

            <div class="pl-3 border-l border-slate-200 flex items-center gap-2">
                <a href="{{ route('filament.admin.pages.pos') }}"
                    class="group bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 px-4 py-2 rounded-full text-sm font-bold cursor-pointer inline-flex items-center gap-2 transition-all active:scale-95">
                    <x-heroicon-o-shopping-cart class="h-4 w-4 text-slate-400 group-hover:text-slate-600 transition-colors" />
                    POS
                </a>
                <a href="{{ route('filament.admin.pages.dashboard') }}"
                    class="group bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 p-2 rounded-full text-sm font-bold cursor-pointer inline-flex items-center transition-all active:scale-95"
                    title="Dashboard">
                    <x-heroicon-o-home class="h-5 w-5 text-slate-400 group-hover:text-slate-600 transition-colors" />
                </a>
            </div>
        </div>
    </header>

    {{-- ========================= --}}
    {{-- MAIN CONTENT --}}
    {{-- ========================= --}}
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
        {{ $slot }}
    </main>

    {{-- ========================= --}}
    {{-- 🔔 NOTIFICATIONS --}}
    {{-- ========================= --}}
    @livewire('notifications')

    @livewireScripts

    <script>
        const PosSound = {
            ctx: null,

            init() {
                if (!this.ctx) {
                    this.ctx = new(window.AudioContext || window.webkitAudioContext)();
                }
                if (this.ctx.state === 'suspended') {
                    this.ctx.resume().catch(e => console.warn('Audio resume failed', e));
                }
            },

            play(type) {
                try {
                    this.init();
                    if (this.ctx.state !== 'running') return;

                    const now = this.ctx.currentTime;
                    if (type === 'click') {
                        this.playTone(800, 'sine', 0.1, 0.05);
                    } else if (type === 'add') {
                        this.playTone(400, 'triangle', 0.15, 0.1);
                    } else if (type === 'success') {
                        this.playTone(523.25, 'sine', 0.1, 0.1, 0);
                        this.playTone(659.25, 'sine', 0.1, 0.1, 0.1);
                        this.playTone(783.99, 'sine', 0.2, 0.1, 0.2);
                    }
                } catch (e) {
                    console.error(e);
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

        ['click', 'keydown', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => {
                if (window.PosSound && window.PosSound.ctx && window.PosSound.ctx.state === 'suspended') {
                    window.PosSound.ctx.resume();
                } else if (window.PosSound && !window.PosSound.ctx) {
                    window.PosSound.init();
                }
            }, {
                once: true
            });
        });
    </script>

    @filamentScripts
    @vite('resources/js/app.js')
</body>

</html>