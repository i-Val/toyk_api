<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MembershipPlan;

class MembershipPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'title' => 'Basic Plan',
                'description' => 'Perfect for starters.',
                'price' => 500.00,
                'currency_code' => 'NGN',
                'days' => 30
            ],
            [
                'title' => 'Standard Plan',
                'description' => 'For growing businesses.',
                'price' => 2000.00,
                'currency_code' => 'NGN',
                'days' => 90
            ],
            [
                'title' => 'Premium Plan',
                'description' => 'Maximum visibility.',
                'price' => 5000.00,
                'currency_code' => 'NGN',
                'days' => 365
            ]
        ];

        foreach ($plans as $plan) {
            MembershipPlan::create($plan);
        }
    }
}
