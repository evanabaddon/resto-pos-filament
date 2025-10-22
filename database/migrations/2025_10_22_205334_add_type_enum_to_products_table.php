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
            // Hapus kolom type lama jika ada
            if (Schema::hasColumn('products', 'type')) {
                $table->dropColumn('type');
            }
            
            // Tambahkan kolom type baru dengan enum yang lengkap
            $table->enum('type', ['raw', 'produced', 'retail', 'bar'])->default('raw');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
