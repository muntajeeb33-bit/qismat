<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();

        DB::table('plans')->upsert([
            ['name' => 'Free', 'code' => 'free', 'price_minor' => 0, 'currency' => 'INR', 'duration_days' => 3650, 'features' => json_encode([]), 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gold', 'code' => 'gold', 'price_minor' => 0, 'currency' => 'INR', 'duration_days' => 30, 'features' => json_encode([]), 'active' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Premium', 'code' => 'premium', 'price_minor' => 0, 'currency' => 'INR', 'duration_days' => 30, 'features' => json_encode([]), 'active' => false, 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['name', 'price_minor', 'currency', 'duration_days', 'features', 'active', 'updated_at']);
    }
}
