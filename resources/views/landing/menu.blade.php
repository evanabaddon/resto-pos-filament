<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Menu - {{ $settings->seo_title ?? config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|playfair-display:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary-color: {
                    {
                    !empty($settings->primary_color) ? $settings->primary_color : '#D4AF37'
                }
            }

            ;

            --secondary-color: {
                    {
                    !empty($settings->secondary_color) ? $settings->secondary_color : '#1A1A1A'
                }
            }

            ;
        }

        .text-theme-primary {
            color: var(--primary-color) !important;
        }

        .bg-theme-primary {
            background-color: var(--primary-color) !important;
        }

        .text-theme-secondary {
            color: var(--secondary-color) !important;
        }

        .bg-theme-secondary {
            background-color: var(--secondary-color) !important;
        }

        .font-heading {
            font-family: 'Playfair Display', serif;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }
    </style>
</head>

<body class="antialiased text-slate-800 bg-slate-50"
    style="--primary-color: {{ !empty($settings->primary_color) ? $settings->primary_color : '#D4AF37' }}; --secondary-color: {{ !empty($settings->secondary_color) ? $settings->secondary_color : '#1A1A1A' }};">

    <!-- Navbar -->
    <nav class="fixed w-full z-50 bg-white/90 backdrop-blur-md shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="{{ route('landing') }}" class="flex-shrink-0 flex items-center gap-3">
                    @if(app(\App\Settings\GeneralSettings::class)->app_logo)
                    <img class="h-10 w-auto" src="{{ asset('storage/' . app(\App\Settings\GeneralSettings::class)->app_logo) }}" alt="Logo">
                    @endif
                    <span class="font-heading font-bold text-2xl text-theme-secondary">{{ app(\App\Settings\GeneralSettings::class)->app_name }}</span>
                </a>

                <div class="flex items-center space-x-8">
                    <a href="{{ route('landing') }}" class="text-sm font-semibold uppercase tracking-wider text-slate-600 hover:text-primary transition-colors">{{ __('messages.landing.nav.back_to_home') }}</a>

                    <!-- Language Switcher -->
                    <div class="flex items-center space-x-1 ml-2 border-l border-slate-200 pl-4">
                        <a href="{{ route('lang.switch', 'en') }}" class="text-xs font-bold {{ app()->getLocale() == 'en' ? 'text-theme-primary' : 'text-slate-400 hover:text-slate-600' }} transition-colors">EN</a>
                        <span class="text-slate-300">|</span>
                        <a href="{{ route('lang.switch', 'id') }}" class="text-xs font-bold {{ app()->getLocale() == 'id' ? 'text-theme-primary' : 'text-slate-400 hover:text-slate-600' }} transition-colors">ID</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="pt-32 pb-16 bg-white text-center">
        <h1 class="font-heading text-5xl font-bold text-theme-secondary mb-4">{{ __('messages.landing.menu.our_menu') }}</h1>
        <p class="text-slate-500 text-lg max-w-2xl mx-auto">{{ __('messages.landing.menu.explore_desc') }}</p>
    </header>

    <!-- Menu Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-32">
        @foreach($categories as $category)
        @if($category->products->isNotEmpty())
        <div id="category-{{ $category->id }}" class="mb-16">
            <div class="flex items-center mb-10">
                <h2 class="font-heading text-3xl font-bold text-theme-secondary mr-6">{{ $category->name }}</h2>
                <div class="flex-grow h-px bg-slate-200"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                @foreach($category->products as $product)
                <div class="group flex gap-4">
                    <div class="flex-shrink-0 w-24 h-24 rounded-xl overflow-hidden bg-slate-100 shadow-sm">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-baseline mb-1">
                            <h3 class="font-bold text-lg text-theme-secondary group-hover:text-primary transition-colors">{{ $product->name }}</h3>
                            <span class="font-bold text-primary ml-2">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
                        </div>
                        <!-- Description removed as per previous request -->
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endforeach
    </main>

    <!-- Footer -->
    <footer class="bg-theme-secondary text-white py-12 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-600 text-sm">
                &copy; {{ date('Y') }} {{ app(\App\Settings\GeneralSettings::class)->app_name }}. {!! __('messages.landing.footer.crafted_by') !!}
            </p>
        </div>
    </footer>

</body>

</html>