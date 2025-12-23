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
        // Update null values to 0 first
        \DB::table('cash_sessions')->whereNull('cash_out')->update(['cash_out' => 0]);

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->decimal('cash_out', 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->decimal('cash_out', 15, 2)->nullable()->default(null)->change();
        });
    }
};
