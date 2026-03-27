<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows admin to create a product', function (): void {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($admin);

    $this->postJson('/api/admin/products', [
        'category_id' => $category->id,
        'name' => 'Bamboo Toothbrush',
        'description' => 'Sustainable bamboo toothbrush.',
        'price' => 5.49,
        'stock' => 25,
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Bamboo Toothbrush');

    expect(Product::query()->where('name', 'Bamboo Toothbrush')->exists())->toBeTrue();
});

it('prevents non admin from creating a product', function (): void {
    $user = User::factory()->customer()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/admin/products', [
        'category_id' => $category->id,
        'name' => 'Reusable Bottle',
        'price' => 19.99,
        'stock' => 20,
    ])->assertForbidden();
});
