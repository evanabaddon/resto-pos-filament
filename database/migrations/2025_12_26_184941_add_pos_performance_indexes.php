<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Add performance indexes for POS queries
     * Expected improvement: 10-20x faster queries
     */
    public function up(): void
    {
        // 1. Products table - Most frequently queried
        Schema::table('products', function (Blueprint $table) {
            // Composite index for main POS query: WHERE is_sellable = 1 AND category_id = ? AND type IN (...)
            $table->index(['is_sellable', 'category_id', 'type'], 'idx_products_pos_query');

            // Index for stock checks
            $table->index('stock', 'idx_products_stock');

            // Index for name search (LIKE queries)
            $table->index('name', 'idx_products_name');
        });

        // 2. Recipes table - For availability checks
        Schema::table('recipes', function (Blueprint $table) {
            // Composite index for recipe lookups
            $table->index(['product_id', 'ingredient_id'], 'idx_recipes_product_ingredient');
        });

        // 3. Sale Items table - For draft quantity calculations
        Schema::table('sale_items', function (Blueprint $table) {
            // Composite index for draft quantity query
            $table->index(['product_id', 'created_at'], 'idx_sale_items_product_date');
        });

        // 4. Sales table - For filtering by status
        Schema::table('sales', function (Blueprint $table) {
            // Composite index for status + date queries
            $table->index(['status', 'created_at'], 'idx_sales_status_date');

            // Index for user-specific queries
            $table->index('user_id', 'idx_sales_user');
        });

        // 5. Categories table - For category lookups
        Schema::table('categories', function (Blueprint $table) {
            // Index for name ordering
            $table->index('name', 'idx_categories_name');
        });

        // 6. Members table - For member search
        Schema::table('members', function (Blueprint $table) {
            // Index for name search
            $table->index('name', 'idx_members_name');

            // Index for phone search
            $table->index('phone', 'idx_members_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_pos_query');
            $table->dropIndex('idx_products_stock');
            $table->dropIndex('idx_products_name');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex('idx_recipes_product_ingredient');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_product_date');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_status_date');
            $table->dropIndex('idx_sales_user');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_name');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_name');
            $table->dropIndex('idx_members_phone');
        });
    }
};
