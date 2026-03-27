<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Organic Food',
            'Zero Waste',
            'Eco Cleaning',
            'Sustainable Home',
            'Natural Beauty',
        ];

        foreach ($categories as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }
    }
}
