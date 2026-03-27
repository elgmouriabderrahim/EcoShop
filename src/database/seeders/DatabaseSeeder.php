<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        User::factory()->admin()->create([
            'full_name' => 'Admin User',
            'email' => 'admin@ecoshop.test',
        ]);

        User::factory()->customer()->create([
            'full_name' => 'Customer User',
            'email' => 'customer@ecoshop.test',
        ]);
    }
}
