<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->load(['reviews', 'orders', 'sizes', 'colors', 'unit', 'brand', 'shop', 'flashSales', 'vatTaxes']);

        $favorite = false;
        $user = Auth::guard('api')->user();

        if ($user && $user->customer) {
            $favoriteIDs = $user->customer?->favorites()?->pluck('product_id')->toArray() ?: [];
            $favorite = in_array($this->id, $favoriteIDs);
        }

        $discountPercentage = $this->getDiscountPercentage($this->price, $this->discount_price);

        $totalSold = $this->orders->sum('pivot.quantity');

        $flashsale = $this->flashSales?->first();
        $flashsaleProduct = null;
        $quantity = null;

        if ($flashsale) {
            $flashsaleProduct = $flashsale?->products()->where('id', $this->id)->first();

            $quantity = $flashsaleProduct?->pivot->quantity - $flashsaleProduct->pivot->sale_quantity;

            if ($quantity == 0) {
                $quantity = null;
                $flashsaleProduct = null;
            } else {
                $discountPercentage = $flashsale?->pivot->discount;
            }
        }

        $price = $this->price;
        $discountPrice = $flashsaleProduct ? $flashsaleProduct->pivot->price : $this->discount_price;

        // calculate vat taxes
        $priceTaxAmount = 0;
        $discountTaxAmount = 0;
        foreach ($this->vatTaxes ?? [] as $tax) {
            if ($tax->percentage > 0) {
                $priceTaxAmount += $price * ($tax->percentage / 100);
                $discountPrice > 0 ? $discountTaxAmount += $discountPrice * ($tax->percentage / 100) : null;
            }
        }

        $price = $price + $priceTaxAmount;
        $discountPrice = $discountPrice + $discountTaxAmount;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'thumbnail' => $this->thumbnail,
            'price' => (float) number_format($price, 2, '.', ''),
            'discount_price' => (float) number_format($discountPrice, 2, '.', ''),
            'discount_percentage' => (float) number_format($discountPercentage, 2, '.', ''),
            'rating' => (float) $this->averageRating > 0 ? $this->averageRating : 5.0,
            'total_reviews' => (string) Number::abbreviate($this->reviews->count(), maxPrecision: 2),
            'total_sold' => (string) number_format($totalSold, 0, '.', ','),
            'quantity' => (int) ($flashsaleProduct ? $quantity : $this->quantity),
            'is_favorite' => (bool) $favorite,
            'sizes' => SizeResource::collection($this->sizes),
            'colors' => ColorResource::collection($this->colors),
            'unit' => $this->unit ? UnitResource::make($this->unit) : null,
            'brand' => $this->brand?->name ?? null,
            'shop' => ProductShopResource::make($this->shop),
        ];
    }
}
