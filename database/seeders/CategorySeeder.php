<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['title' => 'Animal & Pets'],
            ['title' => 'Baby & Maternity'],
            ['title' => 'Commercial Equipment & Tools'],
            ['title' => 'Electronics'],
            ['title' => 'Fashion'],
            ['title' => 'Health & Beauty'],
            ['title' => 'Property'],
            ['title' => 'Repair & Construction'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
