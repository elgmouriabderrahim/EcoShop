<?php

use App\Jobs\SendOrderConfirmationEmailJob;
use App\Jobs\UpdateOrderStockJob;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

it('creates order from cart and dispatches queued jobs', function (): void {
    Queue::fake();

    $user = User::factory()->customer()->create();
    $product = Product::factory()->create(['stock' => 30, 'price' => 12.50]);

    Cart::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/orders')
        ->assertCreated()
        ->assertJsonPath('message', 'Order placed successfully.')
        ->assertJsonPath('data.status', 'pending');

    expect(Cart::query()->where('user_id', $user->id)->exists())->toBeFalse();

    Queue::assertPushed(SendOrderConfirmationEmailJob::class);
    Queue::assertPushed(UpdateOrderStockJob::class);
});
