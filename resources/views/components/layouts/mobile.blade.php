<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @filamentStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900 antialiased pb-20">

    <!-- Top Bar -->
    <div class="fixed top-0 left-0 right-0 bg-white/80 backdrop-blur-md border-b border-gray-200/50 z-50 px-4 py-3 flex items-center justify-between transition-all duration-300">
        <div class="font-bold text-lg text-primary-600 tracking-tight">Resto POS</div>
        <div class="text-xs font-semibold text-gray-500 bg-gray-100/50 px-2 py-1 rounded-lg">
            @if(session('table_slug'))
            {{ \App\Models\Table::where('slug', session('table_slug'))->value('name') ?? '?' }}
            @endif
        </div>
    </div>

    <!-- Content -->
    <main class="mt-16 px-0">
        {{ $slot }}
    </main>

    <!-- Bottom Nav -->
    <div class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-lg border-t border-gray-200/50 z-50 flex justify-around py-2 pb-safe shadow-[0_-5px_20px_rgba(0,0,0,0.03)]">
        <a href="{{ route('order.menu') }}" wire:navigate class="flex flex-col items-center justify-center w-full py-1 text-gray-400 hover:text-primary-600 {{ request()->routeIs('order.menu') ? 'text-primary-600' : '' }} transition-colors relative group">
            <div class="{{ request()->routeIs('order.menu') ? 'bg-primary-50' : 'group-hover:bg-gray-50' }} p-1.5 rounded-xl transition-colors">
                <x-heroicon-o-book-open class="w-6 h-6" />
            </div>
            <span class="text-[10px] font-bold mt-0.5">Menu</span>
        </a>
        <livewire:self-order.cart-counter />
    </div>

    <!-- Global Loading Overlay -->
    <div
        x-data="{ loading: false }"
        x-on:livewire:navigating.window="loading = true"
        x-on:livewire:navigated.window="loading = false"
        x-show="loading"
        x-transition.opacity.duration.300ms
        class="fixed inset-0 z-[100] bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center text-primary-600"
        style="display: none;">

        <div class="relative w-20 h-20">
            <div class="absolute inset-0 border-4 border-gray-100 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-primary-600 rounded-full border-t-transparent animate-spin"></div>
            <x-heroicon-m-heart class="absolute inset-0 m-auto w-8 h-8 animate-pulse text-red-500" />
        </div>
        <p class="mt-4 font-bold text-lg animate-pulse">Memuat...</p>
    </div>

    @filamentScripts
    @vite('resources/js/app.js')
</body>

</html>