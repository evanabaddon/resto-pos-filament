<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Display - {{ $settings->app_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            overflow: hidden;
            background: #000;
        }

        /* Main Container - Split Screen Layout */
        .main-container {
            display: flex;
            width: 100vw;
            height: 100vh;
        }

        /* Left Sidebar - Order Summary (30%) */
        .order-sidebar {
            width: 30%;
            background: linear-gradient(180deg,
                    {{ $landingSettings?->primary_color ?? '#667eea' }}
                    0%,
                    {{ $landingSettings?->secondary_color ?? '#764ba2' }}
                    100%);
            display: flex;
            flex-direction: column;
            position: absolute;
            top: 0;
            left: 0;
            height: 100vh;
            transform: translateX(-100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10;
        }

        .order-sidebar.active {
            transform: translateX(0);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 1rem 0.75rem 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
            text-align: center;
        }

        .customer-name {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.95);
            text-align: center;
            font-weight: 500;
        }

        /* Items List */
        .items-list {
            flex: 1;
            overflow-y: auto;
            padding: 0;
            background: white;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }

        .items-list::-webkit-scrollbar {
            width: 3px;
        }

        .items-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .items-list::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 2px;
        }

        .item-card {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideInLeft 0.15s ease-out;
        }

        .item-card:last-child {
            border-bottom: none;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-8px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .item-qty {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
            min-width: 30px;
        }

        .item-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1f2937;
            line-height: 1.2;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .item-dots {
            flex: 1;
            border-bottom: 1px dotted #d1d5db;
            margin: 0 0.4rem;
            min-width: 30px;
            height: 1px;
        }

        .item-price {
            font-size: 0.85rem;
            font-weight: 700;
            color: #667eea;
            white-space: nowrap;
            flex-shrink: 0;
            text-align: right;
            min-width: 80px;
        }

        /* Right Side - Slideshow (Always Fullscreen) */
        .slideshow-section {
            width: 100%;
            height: 100vh;
            position: relative;
            background: linear-gradient(135deg,
                    {{ $landingSettings?->primary_color ?? '#667eea' }}
                    0%,
                    {{ $landingSettings?->secondary_color ?? '#764ba2' }}
                    100%);
            overflow: hidden;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .slideshow-section.with-sidebar {
            margin-left: 30%;
        }

        /* Branding Overlay - Shows behind slideshow */
        .branding-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 1;
            opacity: 0.3;
        }

        .branding-overlay img {
            max-width: 300px;
            max-height: 300px;
            margin-bottom: 1.5rem;
            filter: brightness(0) invert(1);
        }

        .branding-overlay h1 {
            font-size: 4rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        /* Slideshow */
        .slide {
            display: none;
            width: 100%;
            height: 100%;
            object-fit: contain;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 2;
        }

        .slide.active {
            display: block;
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .item-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.35rem;
            line-height: 1.2;
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-qty {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
        }

        .item-price {
            font-size: 0.95rem;
            font-weight: 700;
            color: #667eea;
        }

        /* Totals Section */
        .totals-section {
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-top: 2px solid rgba(255, 255, 255, 0.2);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.35rem 0;
            color: white;
            font-size: 0.85rem;
        }

        .total-row.subtotal {
            font-weight: 500;
            opacity: 0.9;
        }

        .total-row.tax {
            font-weight: 500;
            opacity: 0.9;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 0.35rem;
        }

        .total-row.grand-total {
            border-top: 2px solid rgba(255, 255, 255, 0.4);
            padding-top: 0.5rem;
            margin-top: 0.25rem;
            font-size: 1.25rem;
            font-weight: 700;
        }

        /* Idle Fallback Screen */
        .idle-fallback {
            display: none;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg,
                    {{ $landingSettings?->primary_color ?? '#667eea' }}
                    0%,
                    {{ $landingSettings?->secondary_color ?? '#764ba2' }}
                    100%);
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 3rem;
            flex-direction: column;
        }

        .idle-fallback.active {
            display: flex;
        }

        .idle-fallback img {
            max-width: 400px;
            max-height: 400px;
            margin-bottom: 2rem;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .idle-fallback h1 {
            font-size: 4.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .idle-fallback p {
            font-size: 2.5rem;
            opacity: 0.95;
        }

        /* Payment Success Overlay */
        .payment-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            z-index: 100;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: white;
            text-align: center;
            padding: 3rem;
        }

        .payment-overlay.active {
            display: flex;
            animation: zoomIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.5);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .payment-overlay h1 {
            font-size: 4.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }

        .payment-amount {
            font-size: 6rem;
            font-weight: 700;
            margin: 2rem 0;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .payment-method {
            font-size: 2.5rem;
            opacity: 0.95;
            margin-bottom: 3rem;
            font-weight: 500;
        }

        .payment-thank-you {
            font-size: 3.5rem;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <!-- Main Split Screen Container -->
    <div class="main-container">
        <!-- Left Sidebar - Order Summary (30%) -->
        <div id="order-sidebar" class="order-sidebar">
            <div class="sidebar-header">
                <h1>Ringkasan Pesanan</h1>
                <div class="customer-name" id="customer-name"></div>
            </div>

            <div class="items-list" id="items-list">
                <!-- Items will be dynamically inserted here -->
            </div>

            <div class="totals-section">
                <div class="total-row subtotal">
                    <span>Subtotal</span>
                    <span id="subtotal">Rp 0</span>
                </div>
                <div class="total-row tax" id="tax-row" style="display: none;">
                    <span>Pajak</span>
                    <span id="tax">Rp 0</span>
                </div>
                <div class="total-row grand-total">
                    <span>TOTAL</span>
                    <span id="total">Rp 0</span>
                </div>
            </div>
        </div>

        <!-- Right Side - Slideshow (70%) -->
        <div id="slideshow-section" class="slideshow-section">
            <!-- Branding Overlay - Shows in empty space -->
            <div class="branding-overlay">
                @if($settings->app_logo)
                    <img src="{{ asset('storage/' . $settings->app_logo) }}" alt="{{ $settings->app_name }}">
                @endif
                <h1>{{ $settings->app_name }}</h1>
            </div>

            <!-- Slideshow Images -->
            <div id="slideshow-container">
                <!-- Slides will be dynamically inserted here -->
            </div>

            <!-- Idle Fallback (No TV Config) -->
            <div id="idle-fallback" class="idle-fallback">
                @if($settings->app_logo)
                    <img src="{{ asset('storage/' . $settings->app_logo) }}" alt="{{ $settings->app_name }}">
                @endif
                <h1>{{ $settings->app_name }}</h1>
                <p>Selamat Datang</p>
            </div>
        </div>
    </div>

    <!-- Payment Success Overlay -->
    <div id="payment-overlay" class="payment-overlay">
        <h1>✅ Pembayaran Berhasil</h1>
        <div class="payment-amount" id="payment-amount">Rp 0</div>
        <div class="payment-method" id="payment-method"></div>
        <div class="payment-thank-you">Terima Kasih!</div>
    </div>

    <script>
        // State Management
        let currentState = 'idle'; // idle, sale, payment
        let slideshowInterval = null;
        let currentSlideIndex = 0;
        let slides = [];

        // Format currency
        function formatCurrency(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }

        // Load TV Config for Slideshow
        async function loadTVConfig() {
            try {
                const response = await fetch('/api/tv-config');
                const config = await response.json();

                if (config.images && config.images.length > 0) {
                    slides = config.images;
                    const duration = config.slide_duration || 10000;
                    initSlideshow(duration);
                    return true;
                } else {
                    // No TV Config, show fallback
                    showIdleFallback();
                    return false;
                }
            } catch (error) {
                console.error('Failed to load TV Config:', error);
                showIdleFallback();
                return false;
            }
        }

        // Initialize Slideshow
        function initSlideshow(duration) {
            const container = document.getElementById('slideshow-container');
            container.innerHTML = '';

            slides.forEach((imageUrl, index) => {
                const img = document.createElement('img');
                img.src = imageUrl;
                img.className = 'slide';
                if (index === 0) img.classList.add('active');
                container.appendChild(img);
            });

            // Hide fallback, show slideshow
            document.getElementById('idle-fallback').classList.remove('active');

            if (slides.length > 1) {
                slideshowInterval = setInterval(() => {
                    nextSlide();
                }, duration);
            }
        }

        // Next Slide
        function nextSlide() {
            const slideElements = document.querySelectorAll('.slide');
            if (slideElements.length === 0) return;

            slideElements[currentSlideIndex].classList.remove('active');
            currentSlideIndex = (currentSlideIndex + 1) % slides.length;
            slideElements[currentSlideIndex].classList.add('active');
        }

        // Show Idle State
        function showIdle() {
            currentState = 'idle';

            // Hide sidebar
            document.getElementById('order-sidebar').classList.remove('active');

            // Hide payment overlay
            document.getElementById('payment-overlay').classList.remove('active');

            // Make slideshow fullscreen (remove margin)
            document.getElementById('slideshow-section').classList.remove('with-sidebar');

            // Restart slideshow if available
            if (slides.length > 0) {
                if (!slideshowInterval) {
                    loadTVConfig();
                }
            } else {
                showIdleFallback();
            }
        }

        // Show Idle Fallback
        function showIdleFallback() {
            document.getElementById('idle-fallback').classList.add('active');
        }

        // Show Sale Display
        function showSale(data) {
            currentState = 'sale';

            // Hide payment overlay
            document.getElementById('payment-overlay').classList.remove('active');

            // Show sidebar
            document.getElementById('order-sidebar').classList.add('active');

            // Add margin to slideshow for sidebar
            document.getElementById('slideshow-section').classList.add('with-sidebar');

            // Keep slideshow running
            if (slides.length > 0 && !slideshowInterval) {
                loadTVConfig();
            }

            // Update customer name
            document.getElementById('customer-name').textContent = data.customerName || 'Customer';

            // Update items
            const itemsList = document.getElementById('items-list');
            itemsList.innerHTML = '';

            data.items.forEach((item, index) => {
                const itemCard = document.createElement('div');
                itemCard.className = 'item-card';
                itemCard.style.animationDelay = `${index * 0.05}s`;
                itemCard.innerHTML = `
                    <div class="item-qty">${item.quantity}x</div>
                    <div class="item-name">${item.name}</div>
                    <div class="item-dots"></div>
                    <div class="item-price">${formatCurrency(item.price * item.quantity)}</div>
                `;
                itemsList.appendChild(itemCard);
            });

            // Update totals
            document.getElementById('subtotal').textContent = formatCurrency(data.subtotal);
            document.getElementById('total').textContent = formatCurrency(data.total);

            console.log('📊 Tax Debug:', {
                tax: data.tax,
                taxType: typeof data.tax,
                taxGreaterThanZero: data.tax > 0
            });

            if (data.tax && data.tax > 0) {
                document.getElementById('tax').textContent = formatCurrency(data.tax);
                document.getElementById('tax-row').style.display = 'flex';
            } else {
                document.getElementById('tax-row').style.display = 'none';
            }
        }

        // Show Payment Success
        function showPayment(data) {
            currentState = 'payment';

            // Show payment overlay (covers everything)
            document.getElementById('payment-overlay').classList.add('active');

            // Update payment info
            document.getElementById('payment-amount').textContent = formatCurrency(data.amountPaid);
            document.getElementById('payment-method').textContent = data.paymentMethod || 'Cash';

            // Auto return to idle after 5 seconds
            setTimeout(() => {
                showIdle();
            }, 5000);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', async () => {
            await loadTVConfig();

            // Listen to Pusher events
            if (typeof Echo !== 'undefined') {
                console.log('✅ Echo is ready, listening to customer-display channel');

                Echo.channel('customer-display')
                    .listen('.App\\Events\\CustomerDisplayUpdated', (event) => {
                        console.log('📨 Customer Display Event:', event);

                        switch (event.action) {
                            case 'loaded':
                            case 'updated':
                                showSale(event);
                                break;
                            case 'paid':
                                showPayment(event);
                                break;
                            case 'idle':
                                showIdle();
                                break;
                        }
                    });
            } else {
                console.error('❌ Echo is not defined!');
            }
        });
    </script>
</body>

</html>