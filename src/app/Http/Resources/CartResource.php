<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'product' => new ProductResource($this->whenLoaded('product')),
            'line_total' => (float) ($this->quantity * $this->product->price),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
