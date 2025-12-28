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
        // WhatsApp Messages - Most queried table
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (!Schema::hasIndex('whatsapp_messages', 'idx_wa_remote_jid')) {
                $table->index('remote_jid', 'idx_wa_remote_jid');
            }
            if (!Schema::hasIndex('whatsapp_messages', 'idx_wa_created_at')) {
                $table->index('created_at', 'idx_wa_created_at');
            }
            if (!Schema::hasIndex('whatsapp_messages', 'idx_wa_jid_created')) {
                $table->index(['remote_jid', 'created_at'], 'idx_wa_jid_created');
            }
            if (!Schema::hasIndex('whatsapp_messages', 'idx_wa_from_status')) {
                $table->index(['from_me', 'status'], 'idx_wa_from_status');
            }
        });

        // Sale Items - For product sales analysis
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasIndex('sale_items', 'idx_sale_items_product')) {
                $table->index('product_id', 'idx_sale_items_product');
            }
            if (!Schema::hasIndex('sale_items', 'idx_sale_items_sale')) {
                $table->index('sale_id', 'idx_sale_items_sale');
            }
        });

        // Stock Movements - For inventory tracking
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasIndex('stock_movements', 'idx_stock_movements_product')) {
                $table->index('product_id', 'idx_stock_movements_product');
            }
            if (!Schema::hasIndex('stock_movements', 'idx_stock_movements_created')) {
                $table->index('created_at', 'idx_stock_movements_created');
            }
            if (!Schema::hasIndex('stock_movements', 'idx_stock_product_created')) {
                $table->index(['product_id', 'created_at'], 'idx_stock_product_created');
            }
        });

        // Sales - For reporting
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasIndex('sales', 'idx_sales_created')) {
                $table->index('created_at', 'idx_sales_created');
            }
            if (!Schema::hasIndex('sales', 'idx_sales_member')) {
                $table->index('member_id', 'idx_sales_member');
            }
            if (!Schema::hasIndex('sales', 'idx_sales_user')) {
                $table->index('user_id', 'idx_sales_user');
            }
            if (!Schema::hasIndex('sales', 'idx_sales_status_created')) {
                $table->index(['status', 'created_at'], 'idx_sales_status_created');
            }
        });

        // Productions - For production tracking
        Schema::table('productions', function (Blueprint $table) {
            if (!Schema::hasIndex('productions', 'idx_productions_product')) {
                $table->index('product_id', 'idx_productions_product');
            }
            if (!Schema::hasIndex('productions', 'idx_productions_created')) {
                $table->index('created_at', 'idx_productions_created');
            }
            if (!Schema::hasIndex('productions', 'idx_productions_user')) {
                $table->index('user_id', 'idx_productions_user');
            }
        });

        // Purchases - For purchase history
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasIndex('purchases', 'idx_purchases_created')) {
                $table->index('created_at', 'idx_purchases_created');
            }
        });

        // Purchase Items
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasIndex('purchase_items', 'idx_purchase_items_purchase')) {
                $table->index('purchase_id', 'idx_purchase_items_purchase');
            }
            if (!Schema::hasIndex('purchase_items', 'idx_purchase_items_product')) {
                $table->index('product_id', 'idx_purchase_items_product');
            }
        });

        // Reservations
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasIndex('reservations', 'idx_reservations_date')) {
                $table->index('reservation_date', 'idx_reservations_date');
            }
            if (!Schema::hasIndex('reservations', 'idx_reservations_status')) {
                $table->index('status', 'idx_reservations_status');
            }
            if (!Schema::hasIndex('reservations', 'idx_reservations_status_date')) {
                $table->index(['status', 'reservation_date'], 'idx_reservations_status_date');
            }
        });

        // Members - For CRM
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasIndex('members', 'idx_members_phone')) {
                $table->index('phone', 'idx_members_phone');
            }
            if (!Schema::hasIndex('members', 'idx_members_tier')) {
                $table->index('tier_id', 'idx_members_tier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropIndex('idx_wa_remote_jid');
            $table->dropIndex('idx_wa_created_at');
            $table->dropIndex('idx_wa_jid_created');
            $table->dropIndex('idx_wa_from_status');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_product');
            $table->dropIndex('idx_sale_items_sale');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_product');
            $table->dropIndex('idx_stock_movements_created');
            $table->dropIndex('idx_stock_product_created');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_created');
            $table->dropIndex('idx_sales_member');
            $table->dropIndex('idx_sales_user');
            $table->dropIndex('idx_sales_status_created');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropIndex('idx_productions_product');
            $table->dropIndex('idx_productions_created');
            $table->dropIndex('idx_productions_user');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('idx_purchases_created');
            $table->dropIndex('idx_purchases_supplier');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_items_purchase');
            $table->dropIndex('idx_purchase_items_product');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('idx_reservations_date');
            $table->dropIndex('idx_reservations_status');
            $table->dropIndex('idx_reservations_status_date');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_phone');
            $table->dropIndex('idx_members_tier');
        });
    }
};
