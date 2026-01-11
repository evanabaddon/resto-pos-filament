<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. CASH (Default & Protected)
        PaymentMethod::updateOrCreate(
            ['code' => 'cash'], // Unique identifier
            [
                'name' => 'Tunai',
                'is_active' => true,
            ]
        );

        // 2. QRIS (Example of another method)
        PaymentMethod::updateOrCreate(
            ['code' => 'qris'],
            [
                'name' => 'QRIS',
                'is_active' => true,
            ]
        );

        // 3. TRANSFER
        PaymentMethod::updateOrCreate(
            ['code' => 'transfer'],
            [
                'name' => 'Transfer Bank',
                'is_active' => true,
            ]
        );
    }
}
