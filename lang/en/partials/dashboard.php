<?php

return [
    // Headings
    'critical_stock_title' => 'Critical Prepared Stock (Live Kitchen Scale)',
    'low_stock_alert_title' => 'Raw Material Stock Alert',
    'peak_hours_heatmap' => 'Peak Hours Heatmap',
    'revenue_trend' => 'Daily Revenue Trend',
    'best_selling_drink' => 'Best Selling Drinks',
    'best_selling_food' => 'Best Selling Food',

    // Status & Labels
    'all_stock_safe' => 'Safe Stock Condition',
    'no_critical_items_desc' => 'All prepared items are above minimum stock levels.',
    'all_stock_safe_title' => 'Stock is Aman',
    'all_stock_safe_desc' => 'All raw material stocks are above the alert limit.',
    'view_all_raw_materials' => 'View All Raw Materials',

    'level_low_suffix' => ' (Low)',
    'level_critical_suffix' => ' (Critical)',

    'stock_value' => 'Stock Value',
    'price_per_unit' => 'Price/Unit',
    'raw_material_name' => 'Raw Material Name',
    'stock' => 'Stock',
    'unit' => 'Unit',
    'stock_level' => 'Stock Level',
    'level_critical' => 'Critical (< 5)',
    'level_low' => 'Low (5-10)',
    'level_out' => 'Out of Stock (0)',
    'out_of_stock_text' => 'Out of Stock',
    'low_stock_count_desc' => 'There are :count raw materials needing attention.',

    // Charts
    'revenue_and_transactions' => 'Revenue vs Transaction Count',
    'revenue' => 'Revenue',
    'transaction_count' => 'Transactions',
    'no_data_chart' => 'No Data Available',
    'date_label' => 'Date',
    'sold_quantity' => 'Quantity Sold',
    'sold_unit_tooltip' => 'unit',

    // Peak Hours
    'peak_hours_desc' => 'Transaction density by hour and day',
    'day_monday' => 'Monday',
    'day_tuesday' => 'Tuesday',
    'day_wednesday' => 'Wednesday',
    'day_thursday' => 'Thursday',
    'day_friday' => 'Friday',
    'day_saturday' => 'Saturday',
    'day_sunday' => 'Sunday',
    'operational_hours' => 'Operational Hours',
    'day_label' => 'Day',
    'quiet' => 'Quiet',
    'normal' => 'Normal',
    'busy' => 'Busy',
    'very_busy' => 'Very Busy',

    // Filters
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'last_7_days' => 'Last 7 Days',
    'last_30_days' => 'Last 30 Days',

    // Critical Widget Extra
    'items' => 'Items',
    'ready_stock_label' => 'Ready Stock',
    'current_stock_label' => 'Current Stock',
    'already_cooked' => 'Already cooked',
    'minimum_stock_label' => 'Min Stock',
    'produced_item_badge' => 'Prepared Item',
    'cook_more_alert' => 'Please cook more!',
    'restock_recommendation' => 'Rec. Restock:',
    'for_3_days' => '(for 3 days)',
    'cook_more_btn' => 'Cook More',
    'record_production_modal_title' => 'Record Production of',
    'current_ready_stock' => 'Current Ready Stock:',
    'quantity_placeholder' => 'Enter quantity cooked/prepared',
    'stock_deduction_info' => 'Ingredients will be automatically deducted from Raw Materials stock.',
    'save_production_btn' => 'Save Production',
    'reset_waste_btn' => 'Reset / Waste',
    'reset_stock_confirm_title' => 'Confirm Reset Stock',
    'warning' => 'WARNING',
    'waste_stock_warning' => 'This will reset the prepared stock of',
    'reset_to_zero_warning' => 'The stock will be set to 0.',
    'ingredients_not_returned' => 'Ingredients used will NOT be returned to inventory.',
    'action_cannot_undo' => 'This action cannot be undone.',
    'confirm_reset_btn' => 'Yes, Reset Stock',
    'auto_refresh_info' => 'Data updates automatically every 5 minutes',

    'cancel' => 'Cancel',

    // Tooltips (LowStockWidget)
    'price_per_unit_tooltip' => 'Purchase price per unit',
    'stock_value_tooltip' => 'Stock × Price/Unit',

    // Widget DB Messages
    'production_note_db' => 'Periodic production (Manual)',
    'production_movement_note_db' => 'Production Output',
    'ingredient_movement_note_db' => 'Ingredient usage for :product qty :quantity',
    'insufficient_stock_error' => 'Insufficient stock for ingredient :ingredient. Required: :required :unit, Available: :current',
    'production_recorded_title' => 'Production Recorded',
    'production_success_body' => 'Added :quantity to prepared stock',
    'production_failed_title' => 'Production Failed',
    'stock_empty_title' => 'Stock Empty',
    'stock_empty_body' => 'Stock is already 0',
    'reset_stock_note_db' => 'Manual Reset/Waste',
    'stock_reset_title' => 'Stock Reset',
    'stock_reset_body' => 'Stock for :product has been reset from :stock to 0',
    'create_purchase' => 'Create Purchase',
    'ai_suggestion_title' => 'AI Daily Suggestions',

    // Revenue Overview Widget
    'today_revenue' => 'Today\'s Revenue',
    'average_value' => 'Average Value: :value',
    'popular_payment' => 'Most Popular Payment',
    'most_frequent' => 'Most Frequent',
    'total_transactions' => 'Total Transactions',
    'from_yesterday' => '% from yesterday',
];
