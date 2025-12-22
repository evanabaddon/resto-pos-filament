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
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'waiter', 'super_admin', 'accountant', 'inventory', 'kitchen') NOT NULL DEFAULT 'admin'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Creating a down migration that reverts the ENUM type. 
        // Note: This effectively drops support for the new roles, which may cause data issues if used.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'waiter', 'super_admin') NOT NULL DEFAULT 'admin'");
    }
};
