<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Loyalty Tiers (e.g., Sedulur, Sedulur Tinetes)
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Sedulur'
            $table->integer('min_points')->default(0);
            $table->integer('min_visits')->default(0); // Alternative condition
            $table->text('benefit_description')->nullable();
            $table->timestamps();
        });

        // Seed default tiers
        DB::table('loyalty_tiers')->insert([
            [
                'name' => 'Tamu',
                'min_points' => 0,
                'min_visits' => 0,
                'benefit_description' => 'Level awal untuk pelanggan baru.',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Sedulur',
                'min_points' => 0,
                'min_visits' => 1, // Require registration/first visit
                'benefit_description' => 'Sudah terdaftar. Dapat mengumpulkan Poin Rasa.',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Sedulur Tinetes',
                'min_points' => 300,
                'min_visits' => 10,
                'benefit_description' => 'Pelanggan setia. Prioritas reservasi & benefit spesial.',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // 2. Members
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique(); // Main ID
            $table->string('email')->nullable();

            // Loyalty Status
            $table->integer('points_balance')->default(0);
            $table->decimal('total_spend', 15, 2)->default(0);
            $table->integer('total_visits')->default(0);
            $table->timestamp('last_visit_at')->nullable();

            $table->foreignId('tier_id')->constrained('loyalty_tiers')->cascadeOnDelete();

            $table->timestamps();
        });

        // 3. Loyalty Rewards (Catalog)
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Gratis Wedhang Jahe'
            $table->integer('points_required');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('members');
        Schema::dropIfExists('loyalty_tiers');
    }
};
