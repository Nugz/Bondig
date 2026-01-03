<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Dairy', 'color' => '#3B82F6'],
            ['name' => 'Meat & Fish', 'color' => '#EF4444'],
            ['name' => 'Produce', 'color' => '#22C55E'],
            ['name' => 'Bread & Bakery', 'color' => '#F59E0B'],
            ['name' => 'Beverages', 'color' => '#06B6D4'],
            ['name' => 'Snacks', 'color' => '#F97316'],
            ['name' => 'Frozen', 'color' => '#8B5CF6'],
            ['name' => 'Household', 'color' => '#6B7280'],
            ['name' => 'Personal Care', 'color' => '#EC4899'],
            ['name' => 'Other', 'color' => '#9CA3AF'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['color' => $category['color']]
            );
        }
    }
}
