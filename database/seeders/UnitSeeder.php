<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Unit::create(['name' => 'Kilogram', 'symbol' => 'kg']);
        Unit::create(['name' => 'Gram', 'symbol' => 'g', 'base_unit_id' => 1, 'conversion_rate' => 1000]);
        Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    }
}
