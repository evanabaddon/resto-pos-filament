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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            $table->string('order_type')->nullable()->after('customer_name');
            $table->decimal('subtotal', 15, 2)->default(0)->after('order_type');
            $table->decimal('tax', 15, 2)->default(0)->after('subtotal');
            $table->decimal('discount', 15, 2)->default(0)->after('tax');
            $table->decimal('final_total', 15, 2)->default(0)->after('discount');
            $table->string('status')->default('paid')->after('final_total');
            $table->text('note')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id', 'order_type', 'subtotal', 'tax',
                'discount', 'final_total', 'status', 'note',
            ]);
        });
    }
};
