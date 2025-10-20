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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // contoh: kilogram, gram, pcs
            $table->string('symbol')->nullable(); // kg, g, pcs
            $table->foreignId('base_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('conversion_rate', 10, 4)->nullable(); // 1 base = X unit ini
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
