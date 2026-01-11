<?php

return [
    'ai_nav_label' => 'AI Business Assistant',
    'ai_intelligence' => 'AI Intelligence',
    'ai_title' => 'AI Business Assistant',
    'online_status' => 'Online',
    'thinking' => 'Thinking...',
    'type_placeholder' => 'Type your message (Enter to send)...',
    'clear_chat' => 'Clear Chat',
    'ai_welcome' => 'Hello! I am :name, your personal business assistant. I can analyze sales, monitor stock, or suggest promo ideas. How can I help you today?',
    'ai_connection_error' => 'Sorry, I am having trouble connecting to the AI brain. Please try again.',
    'ai_error_prefix' => 'Error: ',

    // Quick Actions
    'analyze_sales' => 'Analyze Sales',
    'analyze_sales_msg' => 'Analyze sales trends for the last 30 days and provide insights.',
    'best_selling_menu' => 'Best Sellers',
    'best_selling_menu_msg' => 'What are the top 5 best selling items and suggestions to boost them?',
    'check_stock' => 'Check Stock',
    'check_stock_msg' => 'Are there any critical stock items I need to worry about?',
    'promo_ideas' => 'Promo Ideas',
    'promo_ideas_msg' => 'Give me 3 creative promo ideas for this weekend based on our data.',

    // Widget Prompts & Context
    'ai_prompt_system' => 'You are a professional restaurant business analyst. Provide concise, actionable advice based on data.',
    'ai_prompt_user' => 'Here is the restaurant status for today: :context. Please provide a brief summary and 3 specific recommendations to improve sales or efficiency today.',
    'ai_default_advice' => 'Currently there is not enough data to provide specific advice. Focus on maintaining excellent service and checking stock levels.',
    'ai_error_advice' => 'AI is currently unavailable. Please check internet connection or API settings.',
    'ai_daily_revenue_context' => 'Revenue today: :amount. Critical stock items: :count.',
    'ai_critical_retail' => 'Critical Retail Items: :list.',
    'ai_critical_products' => 'Low Stock Products: :list.',
    'ai_critical_ingredients' => 'Critical Ingredients: :list.',

    // Context Strings (for System Prompt)
    'context_analysis_header' => 'ANALYSIS DATA (LAST :days DAYS):',
    'context_total_orders' => 'Total Orders',
    'context_total_revenue' => 'Total Revenue',
    'context_avg_transaction' => 'Average per Transaction',
    'context_top_menu_header' => 'TOP 5 BEST SELLING MENU:',
    'context_no_sales_data' => 'No sales data yet.',
    'context_inventory_header' => 'INVENTORY & STOCK:',
    'context_critical_retail_count' => 'Critical Retail Items (< 10): :count items',
    'context_critical_retail_list' => 'Critical Retail',
    'context_critical_ingredient_list' => 'CRITICAL RAW MATERIALS',
    'context_remaining' => 'Remaining',
];
