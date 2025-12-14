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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('month_year'); // Format: YYYY-MM
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->integer('total_attendance_days')->default(0);
            $table->integer('total_overtime_minutes')->default(0);
            $table->decimal('overtime_amount', 15, 2)->default(0);
            $table->decimal('deductions', 15, 2)->default(0); // Late penalty etc
            $table->decimal('total_payout', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, paid
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
