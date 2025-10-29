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
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->text('content');
            $table->string('printer')->default('BAR');
            $table->string('division')->default('general');
            $table->integer('sale_id')->nullable();
            $table->string('type')->default('order');
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->integer('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
