<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\ProductType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Categories
        $categories = [
            'Electronics' => ['Mobile Phones', 'Laptops', 'Cameras'],
            'Vehicles' => ['Cars', 'Motorcycles', 'Trucks'],
            'Real Estate' => ['Houses', 'Apartments', 'Land'],
            'Jobs' => ['IT', 'Engineering', 'Sales'],
            'Services' => ['Cleaning', 'Moving', 'Repair'],
            'Fashion' => ['Men', 'Women', 'Kids']
        ];

        foreach ($categories as $parentTitle => $subCats) {
            $parent = Category::firstOrCreate([
                'title' => $parentTitle,
                'slug' => \Illuminate\Support\Str::slug($parentTitle)
            ]);

            foreach ($subCats as $subTitle) {
                Category::firstOrCreate([
                    'title' => $subTitle,
                    'slug' => \Illuminate\Support\Str::slug($subTitle),
                    'parent_id' => $parent->id
                ]);
            }
        }

        // Product Types
        $types = ['New', 'Used', 'Refurbished'];
        foreach ($types as $type) {
            ProductType::firstOrCreate(['name' => $type]);
        }

        // Pages
        \App\Models\Page::firstOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => 'About Us',
                'description' => '<p>Welcome to Toyk Market, your number one source for all things.</p>'
            ]
        );
        \App\Models\Page::firstOrCreate(
            ['slug' => 'terms'],
            [
                'title' => 'Terms of Service',
                'description' => '<p>These are the terms of service...</p>'
            ]
        );
        \App\Models\Page::firstOrCreate(
            ['slug' => 'privacy'],
            [
                'title' => 'Privacy Policy',
                'description' => '<p>We respect your privacy...</p>'
            ]
        );

        // Membership Plans
        \App\Models\MembershipPlan::firstOrCreate(
            ['title' => 'Free'],
            [
                'description' => 'Basic access to post limited ads.',
                'price' => 0,
                'currency_code' => 'USD',
                'days' => 365
            ]
        );
        \App\Models\MembershipPlan::firstOrCreate(
            ['title' => 'Premium'],
            [
                'description' => 'Boost your ads and get more visibility.',
                'price' => 19.99,
                'currency_code' => 'USD',
                'days' => 30
            ]
        );
        \App\Models\MembershipPlan::firstOrCreate(
            ['title' => 'Professional'],
            [
                'description' => 'Unlimited posting and top placement.',
                'price' => 49.99,
                'currency_code' => 'USD',
                'days' => 30
            ]
        );
    }
}
