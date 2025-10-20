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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['raw', 'finished']); // bahan baku / produk jadi
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('stock', 15, 4)->default(0);
            $table->decimal('base_price', 15, 2)->nullable(); // HPP / harga beli
            $table->decimal('sell_price', 15, 2)->nullable(); // harga jual
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
