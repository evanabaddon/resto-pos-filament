<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if($settings->seo_google_verification)
    <meta name="google-site-verification" content="{{ $settings->seo_google_verification }}" />
    @endif

    <title>Menu - {{ $settings->seo_title ?? config('app.name') }}</title>
    <meta name="description" content="{{ $settings->seo_description ?? 'Explore our delicious menu.' }}">
    <meta name="keywords" content="{{ $settings->seo_keywords ?? 'restaurant, menu, food' }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|playfair-display:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Menu - {{ $settings->seo_title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $settings->seo_description ?? 'Explore our delicious menu.' }}">
    @if($settings->hero_image)
    <meta property="og:image" content="{{ asset('storage/' . $settings->hero_image) }}">
    @endif

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="Menu - {{ $settings->seo_title ?? config('app.name') }}">
    <meta property="twitter:description" content="{{ $settings->seo_description ?? 'Explore our delicious menu.' }}">
    @if($settings->hero_image)
    <meta property="twitter:image" content="{{ asset('storage/' . $settings->hero_image) }}">
    @endif

    <!-- Favicon -->
    @if(app(\App\Settings\GeneralSettings::class)->app_favicon)
    <link rel="icon" href="{{ asset('storage/' . app(\App\Settings\GeneralSettings::class)->app_favicon) }}">
    @endif

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
    <nav class="fixed w-full z-50 transition-all duration-300 bg-white/90 backdrop-blur-md shadow-sm py-2" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="{{ route('landing') }}" class="flex-shrink-0 flex items-center gap-3 group">
                    @if(app(\App\Settings\GeneralSettings::class)->app_logo)
                    <img class="h-10 w-auto transform transition group-hover:scale-110"
                        src="{{ asset('storage/' . app(\App\Settings\GeneralSettings::class)->app_logo) }}"
                        alt="{{ app(\App\Settings\GeneralSettings::class)->app_name }}">
                    @else
                    <span
                        class="logo-text font-heading font-bold text-2xl tracking-tight text-theme-secondary group-hover:text-theme-primary transition-colors drop-shadow-[0_1px_1px_rgba(0,0,0,0.1)]">
                        {{ app(\App\Settings\GeneralSettings::class)->app_name }}
                    </span>
                    @endif
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('landing') }}"
                        class="nav-link text-sm font-bold uppercase tracking-wider text-theme-secondary hover:text-theme-primary transition-colors">{{ __('messages.landing.nav.back_to_home') }}</a>

                    <!-- Language Switcher -->
                    <div class="flex items-center space-x-1 ml-2 border-l border-slate-200 pl-4">
                        <a href="{{ route('lang.switch', 'en') }}"
                            class="lang-link text-xs font-bold {{ app()->getLocale() == 'en' ? 'text-theme-secondary' : 'text-slate-500 hover:text-slate-700' }} transition-colors">EN</a>
                        <span class="text-slate-400">|</span>
                        <a href="{{ route('lang.switch', 'id') }}"
                            class="lang-link text-xs font-bold {{ app()->getLocale() == 'id' ? 'text-theme-secondary' : 'text-slate-500 hover:text-slate-700' }} transition-colors">ID</a>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn"
                        class="text-theme-secondary focus:outline-none p-2 rounded-lg hover:bg-slate-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white/95 backdrop-blur-xl border-t border-slate-100 shadow-xl">
            <div class="px-4 pt-2 pb-6 space-y-2">
                <a href="{{ route('landing') }}"
                    class="block px-3 py-3 rounded-xl text-base font-bold text-slate-800 hover:bg-slate-50 hover:text-theme-primary transition">{{ __('messages.landing.nav.back_to_home') }}</a>

                <div class="flex justify-center items-center space-x-4 mt-4 pt-4 border-t border-slate-100">
                    <a href="{{ route('lang.switch', 'en') }}" class="px-4 py-2 rounded-lg text-sm font-bold {{ app()->getLocale() == 'en' ? 'bg-slate-100 text-theme-primary' : 'text-slate-500 hover:bg-slate-50' }}">English</a>
                    <a href="{{ route('lang.switch', 'id') }}" class="px-4 py-2 rounded-lg text-sm font-bold {{ app()->getLocale() == 'id' ? 'bg-slate-100 text-theme-primary' : 'text-slate-500 hover:bg-slate-50' }}">Indonesia</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="pt-32 pb-16 bg-theme-secondary text-center">
        <h1 class="font-heading text-5xl font-bold text-white mb-4 px-4">{{ __('messages.landing.menu.our_menu') }}</h1>
        <p class="text-slate-300 text-lg max-w-2xl mx-auto px-4">{{ __('messages.landing.menu.explore_desc') }}</p>
    </header>

    <!-- Menu Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-32">
        @foreach($categories as $category)
        @if($category->products->isNotEmpty())
        <div id="category-{{ $category->id }}" class="mb-8 mt-8">
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
    <footer class="bg-theme-secondary text-white pt-24 pb-12 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 lg:gap-24">
                <div class="col-span-1 md:col-span-2">
                    <span
                        class="font-heading font-bold text-3xl mb-8 block text-white">{{ app(\App\Settings\GeneralSettings::class)->app_name }}</span>
                    <p class="text-slate-300 mb-8 max-w-sm leading-relaxed text-lg">
                        {{ app(\App\Settings\LandingPageSettings::class)->footer_description ?? \Illuminate\Support\Str::limit(app(\App\Settings\LandingPageSettings::class)->about_us_text, 150) }}
                    </p>
                    <div class="flex space-x-5">
                        @if(app(\App\Settings\GeneralSettings::class)->app_instagram)
                        <a href="https://instagram.com/{{ app(\App\Settings\GeneralSettings::class)->app_instagram }}"
                            class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-slate-300 hover:text-white hover:bg-theme-primary transition duration-300">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.047-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.067-.06-1.407-.06-4.123v-.08c0-2.643.012-2.987.06-4.043.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772 4.902 4.902 0 011.772-1.153c.636-.247 1.363-.416 2.427-.465 1.067-.047 1.407-.06 4.123-.06h.08zm1.658 5.45c-2.69 0-4.87 2.18-4.87 4.87s2.18 4.87 4.87 4.87 4.87-2.18 4.87-4.87-2.18-4.87-4.87-4.87zm0 1.545c1.83 0 3.325 1.493 3.325 3.325 0 1.83-1.494 3.326-3.325 3.326-1.832 0-3.325-1.495-3.325-3.325 0-1.832 1.493-3.325 3.325-3.325zm5.727-.3c-.33 0-.597.26-.597.597 0 .33.267.597.597.597.33 0 .596-.264.596-.597 0-.332-.266-.597-.596-.597z"
                                    clip-rule="evenodd" />
                            </svg>
                        </a>
                        @endif

                        @if(app(\App\Settings\GeneralSettings::class)->app_tiktok)
                        <a href="{{ 'https://tiktok.com/@' . app(\App\Settings\GeneralSettings::class)->app_tiktok }}"
                            class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-slate-300 hover:text-white hover:bg-theme-primary transition duration-300">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z" />
                            </svg>
                        </a>
                        @endif
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-lg mb-6 text-white">{{ __('messages.landing.footer.quick_links') }}</h4>
                    <ul class="space-y-4 text-slate-300">
                        <li><a href="{{ route('landing') }}#home"
                                class="hover:text-theme-primary transition">{{ __('messages.landing.nav.home') }}</a></li>
                        <li><a href="{{ route('landing') }}#about"
                                class="hover:text-theme-primary transition">{{ __('messages.landing.nav.story') }}</a></li>
                        <li><a href="#"
                                class="hover:text-theme-primary transition">{{ __('messages.landing.nav.menu') }}</a></li>
                        <li><a href="{{ route('landing') }}#reservation"
                                class="hover:text-theme-primary transition">{{ __('messages.landing.nav.reservations') }}</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-lg mb-6 text-white">{{ __('messages.landing.contact.visit_us') }}</h4>
                    <p class="text-slate-300 leading-relaxed mb-4">
                        {{ app(\App\Settings\GeneralSettings::class)->company_address }}
                    </p>
                    <a href="{{ $settings->google_maps_url }}" target="_blank"
                        class="text-theme-primary hover:text-white transition text-sm font-bold uppercase tracking-widest">{{ __('messages.landing.contact.get_directions') }}
                        &rarr;</a>
                </div>
            </div>

            <div
                class="border-t border-white/10 mt-20 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-center">
                <p class="text-slate-400 text-sm">
                    &copy; {{ date('Y') }} {{ app(\App\Settings\GeneralSettings::class)->app_name }}.
                    {!! __('messages.landing.footer.crafted_by') !!}
                </p>
                <div class="flex space-x-6 text-sm text-slate-400">
                    <a href="#"
                        class="hover:text-white transition">{{ __('messages.landing.contact.privacy_policy') }}</a>
                    <a href="#"
                        class="hover:text-white transition">{{ __('messages.landing.contact.terms_of_service') }}</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Mobile Menu Toggle
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');

        if (btn && menu) {
            btn.addEventListener('click', () => {
                menu.classList.toggle('hidden');
            });
        }

        // Sticky Navbar Logic Removed - Navbar is always sticky on this page
        // The script below is simplified to remove transparency toggling
        window.addEventListener('scroll', () => {
            // Keeps the event listener structure if needed for other scroll events, but empty for nav color
        });

        // Trigger reveal on load
        window.dispatchEvent(new Event('scroll'));
    </script>

</body>

</html>