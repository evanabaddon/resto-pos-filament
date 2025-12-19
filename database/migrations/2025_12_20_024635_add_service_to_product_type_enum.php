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
        Schema::table('products', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('raw', 'produced', 'retail', 'bar', 'service') NOT NULL DEFAULT 'raw'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('raw', 'produced', 'retail', 'bar') NOT NULL DEFAULT 'raw'");
        });
    }
};
