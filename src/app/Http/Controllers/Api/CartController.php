<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Cart::query()
            ->with('product.category')
            ->where('user_id', request()->user()->id)
            ->get();

        $total = $items->sum(fn (Cart $item): float => (float) ($item->quantity * $item->product->price));

        return response()->json([
            'data' => CartResource::collection($items),
            'meta' => [
                'total_amount' => $total,
            ],
        ]);
    }

    public function store(StoreCartItemRequest $request): CartResource
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));

        if ($request->integer('quantity') > $product->stock) {
            response()->json(['message' => 'Requested quantity exceeds available stock.'], 422)->throwResponse();
        }

        $cart = Cart::query()->updateOrCreate(
            [
                'user_id' => request()->user()->id,
                'product_id' => $product->id,
            ],
            [
                'quantity' => $request->integer('quantity'),
            ]
        );

        $cart->load('product.category');

        return new CartResource($cart);
    }

    public function update(UpdateCartItemRequest $request, Cart $cart): CartResource
    {
        abort_if($cart->user_id !== request()->user()->id, 403, 'Forbidden cart access.');

        if ($request->integer('quantity') > $cart->product->stock) {
            response()->json(['message' => 'Requested quantity exceeds available stock.'], 422)->throwResponse();
        }

        $cart->update([
            'quantity' => $request->integer('quantity'),
        ]);

        $cart->load('product.category');

        return new CartResource($cart);
    }

    public function destroy(Cart $cart): JsonResponse
    {
        abort_if($cart->user_id !== request()->user()->id, 403, 'Forbidden cart access.');

        $cart->delete();

        return response()->json([
            'message' => 'Cart item removed.',
        ]);
    }
}
