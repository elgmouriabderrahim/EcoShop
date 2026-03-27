<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class UpdateOrderStockJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::query()->with('orderItems.product')->find($this->orderId);

        if (! $order || $order->status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($order): void {
            foreach ($order->orderItems as $item) {
                $product = $item->product;

                if (! $product) {
                    continue;
                }

                $product->decrement('stock', $item->quantity);
            }

            $order->update(['status' => 'confirmed']);
        });
    }
}
