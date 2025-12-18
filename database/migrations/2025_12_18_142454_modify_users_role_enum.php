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
        if (Schema::hasColumn('users', 'role')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'waiter', 'super_admin') NOT NULL DEFAULT 'admin'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'cashier', 'waiter', 'super_admin'])->default('admin')->after('email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            // Check if we can revert to the old enum, or just leave it. 
            // Ideally we try to revert but if data exists with 'super_admin' it might fail.
            // For now, let's just reverse the enum definition to the original one if possible.
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'waiter') NOT NULL DEFAULT 'admin'");
        }
    }
};
