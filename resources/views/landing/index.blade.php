<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $settings->seo_title ?? config('app.name') }}</title>
    <meta name="description" content="{{ $settings->seo_description }}">
    <meta name="keywords" content="{{ $settings->seo_keywords }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $settings->seo_title }}">
    <meta property="og:description" content="{{ $settings->seo_description }}">
    @if($settings->hero_image)
    <meta property="og:image" content="{{ asset('storage/' . $settings->hero_image) }}">
    @endif

    <!-- Favicon -->
    @if(app(\App\Settings\GeneralSettings::class)->app_favicon)
    <link rel="icon" href="{{ asset('storage/' . app(\App\Settings\GeneralSettings::class)->app_favicon) }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|playfair-display:400,500,600,700,800&display=swap"
        rel="stylesheet" />

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

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .font-heading {
            font-family: 'Playfair Display', serif;
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        /* Smooth reveal animation */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
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

        ::-webkit-scrollbar-thumb:hover {
            opacity: 0.8;
        }
    </style>
</head>

<!-- Theme Styles -->
<style>
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* Explicit Theme Classes */
    .text-theme-primary {
        color: var(--primary-color) !important;
    }

    .bg-theme-primary {
        background-color: var(--primary-color) !important;
    }

    .border-theme-primary {
        border-color: var(--primary-color) !important;
    }

    .text-theme-secondary {
        color: var(--secondary-color) !important;
    }

    .bg-theme-secondary {
        background-color: var(--secondary-color) !important;
    }

    .border-theme-secondary {
        border-color: var(--secondary-color) !important;
    }
</style>
</head>

<body class="antialiased text-slate-800 bg-slate-50 selection:bg-theme-primary selection:text-white"
    style="--primary-color: {{ !empty($settings->primary_color) ? $settings->primary_color : '#D4AF37' }}; --secondary-color: {{ !empty($settings->secondary_color) ? $settings->secondary_color : '#1A1A1A' }};">

    <!-- Navbar -->
    <nav class="fixed w-full z-50 transition-all duration-300 bg-transparent py-4" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <a href="#" class="flex-shrink-0 flex items-center gap-3 group">
                    @if(app(\App\Settings\GeneralSettings::class)->app_logo)
                    <img class="h-10 w-auto transform transition group-hover:scale-105"
                        src="{{ asset('storage/' . app(\App\Settings\GeneralSettings::class)->app_logo) }}"
                        alt="{{ app(\App\Settings\GeneralSettings::class)->app_name }}">
                    @else
                    <span
                        class="logo-text font-heading font-bold text-2xl tracking-tight text-theme-primary group-hover:text-white transition-colors drop-shadow-md">
                        {{ app(\App\Settings\GeneralSettings::class)->app_name }}
                    </span>
                    @endif
                </a>


                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#home"
                        class="nav-link text-sm font-bold uppercase tracking-wider text-theme-primary hover:text-white transition-colors drop-shadow-sm">{{ __('messages.landing.nav.home') }}</a>
                    <a href="#about"
                        class="nav-link text-sm font-bold uppercase tracking-wider text-theme-primary hover:text-white transition-colors drop-shadow-sm">{{ __('messages.landing.nav.story') }}</a>
                    <a href="#menu"
                        class="nav-link text-sm font-bold uppercase tracking-wider text-theme-primary hover:text-white transition-colors drop-shadow-sm">{{ __('messages.landing.nav.menu') }}</a>
                    <a href="#reservation"
                        class="px-6 py-2.5 bg-theme-primary text-white rounded-full font-medium hover:bg-white hover:text-theme-primary transition-all duration-300 shadow-lg shadow-black/20 hover:shadow-black/30 transform hover:-translate-y-0.5">
                        {{ __('messages.landing.nav.book_table') }}
                    </a>

                    <!-- Language Switcher -->
                    <div class="flex items-center space-x-1 ml-2 border-l border-white/20 pl-4 transition-colors" id="lang-separator">
                        <a href="{{ route('lang.switch', 'en') }}"
                            class="lang-link text-xs font-bold {{ app()->getLocale() == 'en' ? 'text-theme-primary' : 'text-white/70 hover:text-white' }} transition-colors">EN</a>
                        <span class="text-white/30 lang-divider transition-colors">|</span>
                        <a href="{{ route('lang.switch', 'id') }}"
                            class="lang-link text-xs font-bold {{ app()->getLocale() == 'id' ? 'text-theme-primary' : 'text-white/70 hover:text-white' }} transition-colors">ID</a>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn"
                        class="text-theme-primary focus:outline-none p-2 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="hidden md:hidden bg-white border-t border-slate-100 absolute w-full shadow-xl" id="mobile-menu">
            <div class="px-4 pt-2 pb-6 space-y-2">
                <a href="#home"
                    class="block px-3 py-3 text-base font-medium text-slate-800 hover:bg-slate-50 hover:text-primary rounded-lg transition-colors">{{ __('messages.landing.nav.home') }}</a>
                <a href="#about"
                    class="block px-3 py-3 text-base font-medium text-slate-800 hover:bg-slate-50 hover:text-primary rounded-lg transition-colors">{{ __('messages.landing.nav.story') }}</a>
                <a href="#menu"
                    class="block px-3 py-3 text-base font-medium text-slate-800 hover:bg-slate-50 hover:text-primary rounded-lg transition-colors">{{ __('messages.landing.nav.menu') }}</a>
                <a href="#reservation"
                    class="block w-full text-center mt-4 px-5 py-3 bg-[var(--primary-color)] text-white rounded-lg font-bold shadow-md hover:opacity-90 transition-opacity">
                    {{ __('messages.landing.nav.book_table') }}
                </a>

                <div class="flex justify-center items-center space-x-4 mt-4 pt-4 border-t border-slate-100">
                    <a href="{{ route('lang.switch', 'en') }}"
                        class="px-4 py-2 rounded-lg text-sm font-bold {{ app()->getLocale() == 'en' ? 'bg-slate-100 text-theme-primary' : 'text-slate-500 hover:bg-slate-50' }}">English</a>
                    <a href="{{ route('lang.switch', 'id') }}"
                        class="px-4 py-2 rounded-lg text-sm font-bold {{ app()->getLocale() == 'id' ? 'bg-slate-100 text-theme-primary' : 'text-slate-500 hover:bg-slate-50' }}">Indonesia</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="relative h-screen flex items-center justify-center overflow-hidden">
        <!-- Parallax Background -->
        <div class="absolute inset-0 z-0">
            @if($settings->hero_image)
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-1000 scale-105"
                style="background-image: url('{{ asset('storage/' . $settings->hero_image) }}'); transform: scale(1.1);">
            </div>
            @else
            <div class="absolute inset-0 bg-theme-secondary"></div>
            @endif
            <!-- Dynamic Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/70"></div>
        </div>

        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white mt-16">
            <div class="reveal active transition-delay-100">
                <span
                    class="inline-block py-1 px-3 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-bold uppercase tracking-widest mb-6 text-primary">
                    {{ __('messages.landing.hero.welcome', ['app_name' => app(\App\Settings\GeneralSettings::class)->app_name]) }}
                </span>
            </div>

            <h1
                class="reveal font-heading text-5xl md:text-7xl lg:text-8xl font-bold mb-8 leading-tight tracking-tight drop-shadow-2xl">
                {{ $settings->hero_title }}
            </h1>

            <p
                class="reveal text-lg md:text-2xl text-slate-200 mb-12 max-w-2xl mx-auto font-light leading-relaxed drop-shadow-md">
                {{ $settings->hero_description }}
            </p>

            <div class="reveal flex flex-col sm:flex-row gap-5 justify-center">
                <a href="#reservation"
                    class="group relative px-8 py-4 bg-primary text-white text-lg font-semibold rounded-full overflow-hidden shadow-2xl transition hover:shadow-primary/50">
                    <span class="relative z-10">{{ __('messages.landing.hero.reserve_table') }}</span>
                    <div
                        class="absolute inset-0 h-full w-full scale-0 rounded-full transition-all duration-300 group-hover:scale-100 group-hover:bg-white/20">
                    </div>
                </a>
                <a href="#menu"
                    class="px-8 py-4 bg-white/5 backdrop-blur-sm border border-white/30 text-white text-lg font-semibold rounded-full hover:bg-white/10 transition hover:border-white/50">
                    {{ __('messages.landing.hero.explore_menu') }}
                </a>
            </div>
        </div>

        <!-- Scroll Down Indicator -->
        <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce text-white/50">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3">
                </path>
            </svg>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-32 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <!-- Text Content -->
                <div class="reveal">
                    <span
                        class="text-theme-primary font-bold tracking-widest uppercase text-xs mb-2 block">{{ __('messages.landing.about.philosophy') }}</span>
                    <h2 class="font-heading text-5xl font-bold text-theme-secondary mb-8 leading-tight">
                        {!! nl2br(e($settings->about_us_title)) !!}</h2>
                    <div class="h-1 w-20 bg-theme-primary mb-8"></div>

                    <p class="text-slate-600 text-lg leading-relaxed mb-8">
                        {{ $settings->about_us_text }}
                    </p>

                    <div class="grid grid-cols-2 gap-8 mt-12">
                        <div>
                            <h3 class="font-heading text-3xl font-bold text-slate-900 mb-2">{{ $settings->stats_years }}
                            </h3>
                            <p class="text-sm text-slate-500 uppercase tracking-wide">
                                {{ __('messages.landing.about.years_experience') }}
                            </p>
                        </div>
                        <div>
                            <h3 class="font-heading text-3xl font-bold text-slate-900 mb-2">
                                {{ $settings->stats_customers }}
                            </h3>
                            <p class="text-sm text-slate-500 uppercase tracking-wide">
                                {{ __('messages.landing.about.happy_customers') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Image Grid -->
                <div class="reveal relative">
                    <div class="absolute -top-10 -right-10 w-72 h-72 bg-primary/10 rounded-full blur-3xl z-0"></div>
                    <div class="relative z-10 grid grid-cols-2 gap-4">
                        <div class="space-y-4 mt-8">
                            <div class="h-64 rounded-2xl bg-slate-200 overflow-hidden shadow-lg">
                                <img src="{{ $settings->about_image_1 ? asset('storage/' . $settings->about_image_1) : 'https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&q=80' }}"
                                    class="w-full h-full object-cover hover:scale-110 transition duration-700"
                                    alt="Interior">
                            </div>
                            <div class="h-48 rounded-2xl bg-slate-200 overflow-hidden shadow-lg">
                                <img src="{{ $settings->about_image_2 ? asset('storage/' . $settings->about_image_2) : 'https://images.unsplash.com/photo-1600891964599-f61ba0e24092?auto=format&fit=crop&q=80' }}"
                                    class="w-full h-full object-cover hover:scale-110 transition duration-700"
                                    alt="Chef">
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="h-48 rounded-2xl bg-slate-200 overflow-hidden shadow-lg">
                                <img src="{{ $settings->about_image_3 ? asset('storage/' . $settings->about_image_3) : 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&q=80' }}"
                                    class="w-full h-full object-cover hover:scale-110 transition duration-700"
                                    alt="Food">
                            </div>
                            <div class="h-64 rounded-2xl bg-slate-200 overflow-hidden shadow-lg">
                                <img src="{{ $settings->about_image_4 ? asset('storage/' . $settings->about_image_4) : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80' }}"
                                    class="w-full h-full object-cover hover:scale-110 transition duration-700"
                                    alt="Setting">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Menu Highlights -->
    <section id="menu" class="py-32 bg-theme-secondary relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-20 reveal">
                <span
                    class="text-theme-primary font-bold tracking-widest uppercase text-xs">{{ __('messages.landing.menu.kicker') }}</span>
                <h2 class="font-heading text-5xl font-bold text-white mt-3 mb-6">
                    {{ __('messages.landing.menu.title') }}
                </h2>
                <div class="w-24 h-1 bg-theme-primary mx-auto rounded-full opacity-50"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                @foreach($featuredProducts as $product)
                <div
                    class="reveal group bg-white/5 backdrop-blur-sm rounded-3xl shadow-xl hover:shadow-theme-primary/20 transition-all duration-500 overflow-hidden hover:-translate-y-2 border border-white/10">
                    <div class="relative h-64 overflow-hidden">
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/0 transition z-10"></div>
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                            class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700 ease-in-out">
                        <div
                            class="absolute top-4 right-4 z-20 bg-theme-secondary/90 backdrop-blur-md px-4 py-1.5 rounded-full text-sm font-bold shadow-lg text-white border border-white/10">
                            Rp {{ number_format($product->sell_price, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="p-8">
                        <div class="text-xs font-bold text-theme-primary mb-2 uppercase tracking-wide">
                            {{ $product->category->name ?? __('messages.landing.menu.special') }}
                        </div>
                        <h3
                            class="font-heading font-bold text-2xl text-white mb-3 group-hover:text-theme-primary transition-colors">
                            {{ $product->name }}
                        </h3>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="text-center mt-20 reveal">
                <a href="{{ route('landing.menu') }}"
                    class="inline-flex items-center justify-center px-8 py-4 border-2 border-theme-primary text-theme-primary text-lg font-bold rounded-full hover:bg-theme-primary hover:text-white transition-all duration-300 group">
                    <span>{{ __('messages.landing.menu.view_full') }}</span>
                    <svg class="w-5 h-5 ml-2 transform group-hover:translate-x-1 transition" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Reservation Section -->
    <section id="reservation" class="py-32 relative overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 z-10"></div>
            <img src="{{ !empty($settings->reservation_image) ? asset('storage/' . $settings->reservation_image) : 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&q=80' }}"
                class="w-full h-full object-cover">
        </div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20">
            <div
                class="glass-card rounded-[2.5rem] p-8 md:p-16 shadow-2xl border border-white/10 backdrop-blur-xl bg-white/95">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                    <!-- Form Side -->
                    <div>
                        <div class="mb-10">
                            <span
                                class="text-primary font-bold tracking-widest uppercase text-xs">{{ __('messages.landing.reservation.kicker') }}</span>
                            <h2 class="font-heading text-4xl font-bold text-slate-900 mt-2">
                                {{ __('messages.landing.reservation.title') }}
                            </h2>
                            <p class="text-slate-500 mt-4">{{ __('messages.landing.reservation.description') }}</p>
                        </div>

                        <form id="reservationForm" class="space-y-6">
                            @csrf
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">{{ __('messages.landing.reservation.name') }}</label>
                                <input type="text" name="customer_name" required
                                    class="w-full px-5 py-4 rounded-xl bg-slate-50 border-slate-200 focus:border-primary focus:ring-primary transition shadow-sm"
                                    placeholder="John Doe">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">{{ __('messages.landing.reservation.phone') }}</label>
                                    <input type="tel" name="customer_phone" required
                                        class="w-full px-5 py-4 rounded-xl bg-slate-50 border-slate-200 focus:border-primary focus:ring-primary transition shadow-sm"
                                        placeholder="0812...">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">{{ __('messages.landing.reservation.guests') }}</label>
                                    <select name="party_size"
                                        class="w-full px-5 py-4 rounded-xl bg-slate-50 border-slate-200 focus:border-primary focus:ring-primary transition shadow-sm">
                                        @foreach(range(1, 10) as $size)
                                        <option value="{{ $size }}">{{ $size }}
                                            {{ __('messages.landing.reservation.guest') }}
                                        </option>
                                        @endforeach
                                        <option value="11">{{ __('messages.landing.reservation.more_than_10') }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">{{ __('messages.landing.reservation.date_time') }}</label>
                                <input type="datetime-local" name="reservation_date" required
                                    class="w-full px-5 py-4 rounded-xl bg-slate-50 border-slate-200 focus:border-primary focus:ring-primary transition shadow-sm">
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">{{ __('messages.landing.reservation.special_request') }}</label>
                                <textarea name="special_requests" rows="3"
                                    class="w-full px-5 py-4 rounded-xl bg-slate-50 border-slate-200 focus:border-primary focus:ring-primary transition shadow-sm"
                                    placeholder="{{ __('messages.landing.reservation.placeholder_request') }}"></textarea>
                            </div>

                            <button type="submit" id="btnSubmit"
                                class="w-full py-5 bg-theme-secondary text-white font-bold text-lg rounded-xl hover:bg-theme-primary transition-all duration-300 shadow-xl shadow-black/20 hover:shadow-black/40 transform hover:-translate-y-1">
                                {{ __('messages.landing.reservation.confirm_btn') }}
                            </button>
                        </form>

                        <div id="formMessage" class="hidden mt-6 p-4 rounded-xl text-center text-sm font-medium"></div>
                    </div>

                    <!-- Info Side -->
                    <div
                        class="hidden lg:flex flex-col justify-between h-full bg-slate-50 rounded-3xl p-10 border border-slate-100">
                        <div>
                            <h3 class="font-heading text-2xl font-bold text-slate-900 mb-6">
                                {{ __('messages.landing.contact.info_title') }}
                            </h3>
                            <div class="space-y-6">
                                <div class="flex items-start">
                                    <div
                                        class="w-10 h-10 rounded-full bg-white border border-slate-100 flex items-center justify-center text-primary shadow-sm flex-shrink-0 mr-4">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ __('messages.landing.contact.address') }}
                                        </p>
                                        <p class="text-slate-500 text-sm mt-1">
                                            {{ app(\App\Settings\GeneralSettings::class)->company_address }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div
                                        class="w-10 h-10 rounded-full bg-white border border-slate-100 flex items-center justify-center text-primary shadow-sm flex-shrink-0 mr-4">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ __('messages.landing.contact.phone') }}
                                        </p>
                                        <p class="text-slate-500 text-sm mt-1">
                                            {{ app(\App\Settings\GeneralSettings::class)->company_phone }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div
                                        class="w-10 h-10 rounded-full bg-white border border-slate-100 flex items-center justify-center text-primary shadow-sm flex-shrink-0 mr-4">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ __('messages.landing.contact.email') }}
                                        </p>
                                        <p class="text-slate-500 text-sm mt-1">
                                            {{ app(\App\Settings\GeneralSettings::class)->company_email }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="relative overflow-hidden rounded-2xl h-48 mt-10">
                            <img src="{{ $settings->contact_image ? asset('storage/' . $settings->contact_image) : 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&q=80' }}"
                                class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                <div class="text-center text-white">
                                    <p class="font-heading font-bold text-2xl">
                                        {{ app(\App\Settings\GeneralSettings::class)->operational_start_hour }}:00 -
                                        {{ app(\App\Settings\GeneralSettings::class)->operational_end_hour }}:00
                                    </p>
                                    <p class="text-sm uppercase tracking-widest mt-1 opacity-80">
                                        {{ __('messages.landing.contact.opening_hours') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-theme-secondary text-white pt-24 pb-12 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 lg:gap-24">
                <div class="col-span-1 md:col-span-2">
                    <span
                        class="font-heading font-bold text-3xl mb-8 block text-white">{{ app(\App\Settings\GeneralSettings::class)->app_name }}</span>
                    <p class="text-slate-300 mb-8 max-w-sm leading-relaxed text-lg">
                        {{ $settings->footer_description ?? \Illuminate\Support\Str::limit($settings->about_us_text, 150) }}
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
                        <li><a href="#home"
                                class="hover:text-theme-primary transition">{{ __('messages.landing.nav.home') }}</a></li>
                        <li><a href="#about"
                                class="hover:text-theme-primary transition">{{ __('messages.landing.nav.story') }}</a></li>
                        <li><a href="#menu"
                                class="hover:text-theme-primary transition">{{ __('messages.landing.nav.menu') }}</a></li>
                        <li><a href="#reservation"
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

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });

        // Sticky Navbar & Scroll Reveal
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('navbar');
            const navLinks = document.querySelectorAll('.nav-link');
            const logoText = document.querySelector('.logo-text');
            const langLinks = document.querySelectorAll('.lang-link');

            if (window.scrollY > 50) {
                nav.classList.add('glass-nav', 'shadow-sm');
                nav.classList.remove('bg-transparent', 'py-4');

                // Update text colors for sticky state (dark text)
                navLinks.forEach(link => {
                    link.classList.remove('text-theme-primary', 'hover:text-white', 'drop-shadow-sm');
                    link.classList.add('text-theme-secondary', 'hover:text-theme-primary');
                });

                if (logoText) {
                    logoText.classList.remove('text-theme-primary', 'hover:text-white', 'drop-shadow-md');
                    logoText.classList.add('text-theme-secondary', 'hover:text-theme-primary');
                }

                langLinks.forEach(link => {
                    if (!link.classList.contains('text-theme-primary')) {
                        link.classList.remove('text-white/70', 'hover:text-white');
                        link.classList.add('text-slate-500', 'hover:text-slate-800');
                    }
                });

                // Update divider and separator
                const langDivider = document.querySelector('.lang-divider');
                const langSeparator = document.getElementById('lang-separator');
                if (langDivider) {
                    langDivider.classList.remove('text-white/30');
                    langDivider.classList.add('text-slate-300');
                }
                if (langSeparator) {
                    langSeparator.classList.remove('border-white/20');
                    langSeparator.classList.add('border-slate-200');
                }

                // Mobile button update
                if (btn) {
                    btn.classList.remove('text-theme-primary', 'hover:bg-white/10');
                    btn.classList.add('text-theme-secondary', 'hover:bg-slate-100');
                }

            } else {
                nav.classList.remove('glass-nav', 'shadow-sm');
                nav.classList.add('bg-transparent', 'py-4');

                // Update text colors for transparent state (primary text)
                navLinks.forEach(link => {
                    link.classList.add('text-theme-primary', 'hover:text-white', 'drop-shadow-sm');
                    link.classList.remove('text-theme-secondary', 'hover:text-theme-primary');
                });

                if (logoText) {
                    logoText.classList.add('text-theme-primary', 'hover:text-white', 'drop-shadow-md');
                    logoText.classList.remove('text-theme-secondary', 'hover:text-theme-primary');
                }

                langLinks.forEach(link => {
                    if (!link.classList.contains('text-theme-primary')) {
                        link.classList.add('text-white/70', 'hover:text-white');
                        link.classList.remove('text-slate-500', 'hover:text-slate-800');
                    }
                });

                // Update divider and separator
                const langDivider = document.querySelector('.lang-divider');
                const langSeparator = document.getElementById('lang-separator');
                if (langDivider) {
                    langDivider.classList.add('text-white/30');
                    langDivider.classList.remove('text-slate-300');
                }
                if (langSeparator) {
                    langSeparator.classList.add('border-white/20');
                    langSeparator.classList.remove('border-slate-200');
                }

                // Mobile button update
                if (btn) {
                    btn.classList.add('text-theme-primary', 'hover:bg-white/10');
                    btn.classList.remove('text-theme-secondary', 'hover:bg-slate-100');
                }
            }

            // Scroll Reveal Logic
            const reveals = document.querySelectorAll('.reveal');

            for (let i = 0; i < reveals.length; i++) {
                const windowHeight = window.innerHeight;
                const elementTop = reveals[i].getBoundingClientRect().top;
                const elementVisible = 150;

                if (elementTop < windowHeight - elementVisible) {
                    reveals[i].classList.add('active');
                }
            }
        });

        // Trigger reveal on load
        window.dispatchEvent(new Event('scroll'));

        // Reservation Form Handling
        document.getElementById('reservationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const btn = document.getElementById('btnSubmit');
            const msg = document.getElementById('formMessage');

            // Reset message
            msg.classList.add('hidden');
            msg.classList.remove('bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');

            // Loading state
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline shadow-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';

            try {
                const formData = new FormData(form);
                const response = await fetch("{{ route('landing.reservation.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    msg.innerHTML = data.message;
                    msg.classList.add('bg-green-100', 'text-green-800');
                    form.reset();
                } else {
                    msg.innerHTML = data.message || 'Something went wrong.';
                    msg.classList.add('bg-red-100', 'text-red-800');
                }
            } catch (error) {
                msg.innerHTML = 'Network error. Please try again.';
                msg.classList.add('bg-red-100', 'text-red-800');
            } finally {
                msg.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = 'Confirm Reservation';
            }
        });
    </script>
</body>

</html>