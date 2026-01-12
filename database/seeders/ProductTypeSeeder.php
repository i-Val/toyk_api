<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductType;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Sell'],
            ['name' => 'Rent'],
            ['name' => 'Exchange'],
            ['name' => 'Give Away'],
        ];

        foreach ($types as $type) {
            ProductType::create($type);
        }
    }
}
