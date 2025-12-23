<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('fund_source')->nullable()->after('supplier_name');
            $table->foreignId('cash_session_id')->nullable()->constrained()->onDelete('set null')->after('fund_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['cash_session_id']);
            $table->dropColumn(['fund_source', 'cash_session_id']);
        });
    }
};
