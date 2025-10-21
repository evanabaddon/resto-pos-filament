<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Payment Methods
        $paymentMethods = [
            ['name' => 'Cash', 'code' => 'cash'],
            ['name' => 'Transfer Bank', 'code' => 'transfer'],
            ['name' => 'Kartu Kredit', 'code' => 'credit_card'],
            ['name' => 'Kartu Debit', 'code' => 'debit_card'],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::create($method);
        }

        // Seed Expense Categories
        $categories = [
            ['name' => 'Gaji Karyawan', 'description' => 'Pembayaran gaji dan upah karyawan'],
            ['name' => 'Bahan Baku', 'description' => 'Pembelian bahan baku makanan'],
            ['name' => 'Listrik & Air', 'description' => 'Pembayaran utilitas'],
            ['name' => 'Sewa Tempat', 'description' => 'Pembayaran sewa gedung'],
            ['name' => 'Peralatan Dapur', 'description' => 'Pembelian peralatan dapur'],
            ['name' => 'Pemeliharaan', 'description' => 'Biaya perbaikan dan maintenance'],
            ['name' => 'Transportasi', 'description' => 'Biaya transportasi dan pengiriman'],
            ['name' => 'Lain-lain', 'description' => 'Pengeluaran lainnya'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::create($category);
        }
    }
}
