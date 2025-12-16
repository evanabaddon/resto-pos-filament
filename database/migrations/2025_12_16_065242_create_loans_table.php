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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2); // Total pinjaman
            $table->decimal('remaining_amount', 15, 2); // Sisa tagihan
            $table->decimal('installment_amount', 15, 2); // Cicilan per bulan
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->string('start_month_year')->nullable(); // e.g. "2024-01"
            $table->timestamps();
        });

        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete(); // Link ke payroll jika potong gaji
            $table->decimal('amount', 15, 2);
            $table->date('paid_at');
            $table->string('note')->nullable(); // "Potong Gaji Januari 2024"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('loans');
    }
};
