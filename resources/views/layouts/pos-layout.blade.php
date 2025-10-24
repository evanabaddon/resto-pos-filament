<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'POS System' }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
    <style>
        html, body {
            height: 100%;
            overflow: hidden;
        }
        
        /* Full screen styles */
        .full-screen {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            z-index: 9999 !important;
            background: white;
        }
        
        .full-screen-hidden {
            display: none !important;
        }
    </style>
    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.25s ease-out;
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col h-screen overflow-hidden">

    {{-- ========================= --}}
    {{-- 🔝 TOP NAVBAR --}}
    {{-- ========================= --}}
    <header id="top-navbar" class="flex-shrink-0 flex items-center justify-between bg-white border-b border-gray-200 px-6 py-3 shadow-sm">
        <div class="flex items-center space-x-3">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-8 w-auto">
            <h1 class="text-xl font-semibold text-gray-700">POS System</h1>
        </div>

        <div class="flex items-center space-x-4">
            {{-- Tombol Full Screen --}}
            <button id="full-screen-btn" 
                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium cursor-pointer inline-flex items-center gap-2 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
                Full Screen
            </button>

            {{-- Tombol Exit Full Screen (hidden by default) --}}
            <button id="exit-full-screen-btn" 
                    class="hidden bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium cursor-pointer inline-flex items-center gap-2 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Exit Full Screen
            </button>

            {{-- Tombol Cash Summary --}}
            <livewire:cash-summary-button />
            
            {{-- Tombol Close Shift --}}
            <livewire:close-shift-button />

            <a href="{{ route('filament.admin.pages.dashboard') }}"
            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium cursor-pointer inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>
        </div>
    </header>

    {{-- ========================= --}}
    {{-- MAIN CONTENT --}}
    {{-- ========================= --}}
    <main id="main-content" class="flex-1 overflow-hidden">
        {{ $slot }}
    </main>
    {{-- Cash Summary Modal --}}
    <livewire:cash-summary-modal />

    @livewireScripts
    <script>
        document.addEventListener('livewire:load', function () {
            const fullScreenBtn = document.getElementById('full-screen-btn');
            const exitFullScreenBtn = document.getElementById('exit-full-screen-btn');
            const topNavbar = document.getElementById('top-navbar');
            const mainContent = document.getElementById('main-content');
            let isFullScreen = false;

            // Full Screen Button
            if (fullScreenBtn) {
                fullScreenBtn.addEventListener('click', function() {
                    enterFullScreen();
                });
            }

            // Exit Full Screen Button
            if (exitFullScreenBtn) {
                exitFullScreenBtn.addEventListener('click', function() {
                    exitFullScreen();
                });
            }

            function enterFullScreen() {
                isFullScreen = true;
                
                // Sembunyikan navbar
                topNavbar.classList.add('full-screen-hidden');
                
                // Set main content ke full screen
                mainContent.classList.add('full-screen');
                
                // Toggle tombol
                fullScreenBtn.classList.add('hidden');
                exitFullScreenBtn.classList.remove('hidden');
                
                // Trigger event untuk Livewire component
                Livewire.emit('enteredFullScreen');
            }

            function exitFullScreen() {
                isFullScreen = false;
                
                // Tampilkan navbar kembali
                topNavbar.classList.remove('full-screen-hidden');
                
                // Hapus full screen dari main content
                mainContent.classList.remove('full-screen');
                
                // Toggle tombol
                fullScreenBtn.classList.remove('hidden');
                exitFullScreenBtn.classList.add('hidden');
                
                // Trigger event untuk Livewire component
                Livewire.emit('exitedFullScreen');
            }

            // Handle ESC key to exit full screen
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isFullScreen) {
                    exitFullScreen();
                }
            });

            // Close Shift Button
            const closeShiftBtn = document.getElementById('close-shift-btn');
            if (closeShiftBtn) {
                closeShiftBtn.addEventListener('click', function() {
                    Livewire.emit('closeShiftFromLayout');
                });
            }

            // Cash Summary Button
            const cashSummaryBtn = document.getElementById('cash-summary-btn');
            if (cashSummaryBtn) {
                cashSummaryBtn.addEventListener('click', function() {
                    Livewire.emit('openCashSummaryModal');
                });
            }
        });
    </script>
</body>
</html>