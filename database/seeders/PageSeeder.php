<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'description' => '<p>Welcome to Toyk Market, your number one source for all things. We\'re dedicated to giving you the very best of product, with a focus on dependability, customer service and uniqueness.</p>'
            ],
            [
                'title' => 'Terms and Conditions',
                'slug' => 'terms-and-conditions',
                'description' => '<p>These terms and conditions outline the rules and regulations for the use of Toyk Market\'s Website.</p>'
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'description' => '<p>At Toyk Market, accessible from toykmarket.com, one of our main priorities is the privacy of our visitors.</p>'
            ]
        ];

        foreach ($pages as $page) {
            Page::create($page);
        }
    }
}