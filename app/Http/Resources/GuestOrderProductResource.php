<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestOrderProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Load only necessary relationships
        $this->loadMissing(['brand']);

        // ✅ Calculate Price
        $price = $this->pivot->price ?? ($this->discount_price > 0 ? $this->discount_price : $this->price);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand?->name ?? null,
            'thumbnail' => $this->thumbnail,
            'price' => (float) $price,
            'discount_price' => (float) ($this->discount_price > 0 ? $this->discount_price : 0),
            'order_qty' => (int) $this->pivot->quantity,
            'color' => $this->pivot->color ?? null,
            'size' => $this->pivot->size ?? null,
            'unit' => $this->pivot->unit ?? null,
            'average_rating' => $this->averageRating ?? null,
            'total_reviews' => $this->reviews->count() ?? 0,
        ];
    }
}
