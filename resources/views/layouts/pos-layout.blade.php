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
            overflow: hidden; /* hilangkan scroll browser */
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
    <header class="flex-shrink-0 flex items-center justify-between bg-white border-b border-gray-200 px-6 py-3 shadow-sm">
        <div class="flex items-center space-x-3">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-8 w-auto">
            <h1 class="text-xl font-semibold text-gray-700">POS System</h1>
        </div>

        <div class="flex items-center space-x-4">
            <livewire:close-shift-button />

            <a href="{{ route('filament.admin.pages.dashboard') }}"
                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium cursor-pointer inline-block">
                    Dashboard
            </a>
        </div>
    </header>

    {{-- ========================= --}}
    {{-- MAIN CONTENT --}}
    {{-- ========================= --}}
    <main class="flex-1 overflow-hidden">
        {{ $slot }}
    </main>

    @livewireScripts
    <script>
        document.addEventListener('livewire:load', function () {
            const btn = document.getElementById('close-shift-btn');
            btn.addEventListener('click', function() {
                Livewire.emit('closeShiftFromLayout');
            });
        });
    </script>
</body>
</html>
