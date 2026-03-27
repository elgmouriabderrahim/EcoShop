<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Category::query()->each(function (Category $category): void {
            Product::factory()->count(8)->create([
                'category_id' => $category->id,
            ]);
        });
    }
}
