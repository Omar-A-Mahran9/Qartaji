<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->load('brand', 'reviews');

        $review = $this->reviews()->where('customer_id', auth()->user()->customer?->id)->where('product_id', $this->id)->where('order_id', $request->order_id)->first();

        $price = $this->pivot->price > 0 ? $this->pivot->price : ($this->discount_price > 0 ? $this->discount_price : $this->price);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand?->name ?? null,
            'thumbnail' => $this->thumbnail,
            'price' => (float) $price,
            'discount_price' => (float) $this->discount_price > 0 ? $price : 0,
            'order_qty' => (int) $this->pivot->quantity,
            'color' => $this->pivot->color ?? null,
            'size' => $this->pivot->size ?? null,
            'rating' => $review ? (float) $review->rating : null,
            'unit' => $this->pivot->unit ?? null,
        ];
    }
}
