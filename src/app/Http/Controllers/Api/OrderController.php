<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->with(['orderItems.product.category'])
            ->where('user_id', request()->user()->id)
            ->latest('id')
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function store(): JsonResponse
    {
        $user = request()->user();

        $cartItems = Cart::query()
            ->with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty.',
            ], 422);
        }

        foreach ($cartItems as $item) {
            if ($item->quantity > $item->product->stock) {
                return response()->json([
                    'message' => "Insufficient stock for product {$item->product->name}.",
                ], 422);
            }
        }

        $order = DB::transaction(function () use ($user, $cartItems) {
            $total = $cartItems->sum(fn ($item): float => (float) ($item->quantity * $item->product->price));

            $order = Order::query()->create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => $total,
            ]);

            foreach ($cartItems as $item) {
                $order->orderItems()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->product->price,
                ]);
            }

            Cart::query()->where('user_id', $user->id)->delete();

            return $order;
        });

        $order->load(['user', 'orderItems.product.category']);

        event(new OrderPlaced($order));

        return response()->json([
            'message' => 'Order placed successfully.',
            'data' => new OrderResource($order),
        ], 201);
    }
}
