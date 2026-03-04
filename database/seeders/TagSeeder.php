<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run()
    {
        $tags = [
            // Psychology
            ['name' => 'Revenge Trade', 'category' => 'psychology', 'color' => '#dc3545'],
            ['name' => 'FOMO', 'category' => 'psychology', 'color' => '#ffc107'],
            ['name' => 'Emotional Decision', 'category' => 'psychology', 'color' => '#fd7e14'],
            ['name' => 'Disciplined Entry', 'category' => 'psychology', 'color' => '#28a745'],
            ['name' => 'Patient Wait', 'category' => 'psychology', 'color' => '#20c997'],

            // Strategy
            ['name' => 'Perfect Setup', 'category' => 'strategy', 'color' => '#28a745'],
            ['name' => 'Against Plan', 'category' => 'strategy', 'color' => '#dc3545'],
            ['name' => 'Early Entry', 'category' => 'strategy', 'color' => '#ffc107'],
            ['name' => 'Late Entry', 'category' => 'strategy', 'color' => '#fd7e14'],
            ['name' => 'Textbook Trade', 'category' => 'strategy', 'color' => '#0d6efd'],

            // Market
            ['name' => 'High Volatility', 'category' => 'market', 'color' => '#6f42c1'],
            ['name' => 'Low Volume', 'category' => 'market', 'color' => '#6c757d'],
            ['name' => 'News Event', 'category' => 'market', 'color' => '#d63384'],
            ['name' => 'Earnings', 'category' => 'market', 'color' => '#0dcaf0'],
            ['name' => 'After Hours', 'category' => 'market', 'color' => '#212529'],

            // Execution
            ['name' => 'Slippage', 'category' => 'execution', 'color' => '#dc3545'],
            ['name' => 'Perfect Fill', 'category' => 'execution', 'color' => '#28a745'],
            ['name' => 'Partial Fill', 'category' => 'execution', 'color' => '#ffc107'],

            // Mistakes
            ['name' => 'Oversized Position', 'category' => 'mistake', 'color' => '#dc3545'],
            ['name' => 'Missed Stop Loss', 'category' => 'mistake', 'color' => '#dc3545'],
            ['name' => 'No Exit Plan', 'category' => 'mistake', 'color' => '#fd7e14'],

            // Success
            ['name' => 'Great Risk/Reward', 'category' => 'success', 'color' => '#28a745'],
            ['name' => 'Followed Plan', 'category' => 'success', 'color' => '#20c997'],
            ['name' => 'Cut Loss Quick', 'category' => 'success', 'color' => '#0d6efd'],
        ];

        foreach ($tags as $tag) {
            DB::connection('mysql')->table('tags')->insert([
                'name' => $tag['name'],
                'slug' => Str::slug($tag['name']),
                'category' => $tag['category'],
                'color' => $tag['color'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
