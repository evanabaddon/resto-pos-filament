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
            $table->foreignId('split_from')->nullable()->constrained('sales')->onDelete('cascade');
            $table->integer('split_number')->nullable();
            $table->integer('split_into')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['split_from']);
            $table->dropColumn(['split_from', 'split_number', 'split_into']);
        });
    }
};
