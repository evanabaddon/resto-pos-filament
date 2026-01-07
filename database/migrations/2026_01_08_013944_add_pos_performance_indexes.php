<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Products table - POS product search and filtering
        Schema::table('products', function (Blueprint $table) {
            // For POS product list query (is_sellable + name search)
            if (!$this->indexExists('products', 'idx_products_sellable_name')) {
                $table->index(['is_sellable', 'name'], 'idx_products_sellable_name');
            }

            // For category filtering
            if (!$this->indexExists('products', 'idx_products_sellable_category')) {
                $table->index(['is_sellable', 'category_id'], 'idx_products_sellable_category');
            }

            // For stock availability check
            if (!$this->indexExists('products', 'idx_products_type_stock')) {
                $table->index(['type', 'stock'], 'idx_products_type_stock');
            }
        });

        // Members table - Member search in POS
        Schema::table('members', function (Blueprint $table) {
            // For name search (prefix matching)
            if (!$this->indexExists('members', 'idx_members_name')) {
                $table->index('name', 'idx_members_name');
            }

            // For phone search
            if (!$this->indexExists('members', 'idx_members_phone')) {
                $table->index('phone', 'idx_members_phone');
            }

            // For email search
            if (!$this->indexExists('members', 'idx_members_email')) {
                $table->index('email', 'idx_members_email');
            }
        });

        // Recipes table - For availability checking
        Schema::table('recipes', function (Blueprint $table) {
            // For product recipe lookup
            if (!$this->indexExists('recipes', 'idx_recipes_product')) {
                $table->index('product_id', 'idx_recipes_product');
            }

            // For ingredient lookup
            if (!$this->indexExists('recipes', 'idx_recipes_ingredient')) {
                $table->index('ingredient_id', 'idx_recipes_ingredient');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_sellable_name');
            $table->dropIndex('idx_products_sellable_category');
            $table->dropIndex('idx_products_type_stock');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_name');
            $table->dropIndex('idx_members_phone');
            $table->dropIndex('idx_members_email');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex('idx_recipes_product');
            $table->dropIndex('idx_recipes_ingredient');
        });
    }

    /**
     * Check if index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $indexData) {
            if ($indexData['name'] === $index) {
                return true;
            }
        }

        return false;
    }
};
