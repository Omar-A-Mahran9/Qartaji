<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Enums\DeductionType;
use App\Http\Requests\CartRequest;
use App\Http\Requests\GiftRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\ColorResource;
use App\Http\Resources\SizeResource;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Number;

class CartRepository extends Repository
{
    public static function model()
    {
        return Cart::class;
    }

    public static function ShopWiseCartProducts($groupCart)
    {
        $shopWiseProducts = collect([]);
        foreach ($groupCart as $key => $products) {
            $productArray = collect([]);

            foreach ($products as $cart) {

                $product = $cart->product;

                $discountPercentage = $product->getDiscountPercentage($product->price, $product->discount_price);

                $totalSold = $product->orders->sum('pivot.quantity');

                $flashsale = $product->flashSales?->first();
                $flashsaleProduct = null;
                $quantity = null;

                if ($flashsale) {
                    $flashsaleProduct = $flashsale?->products()->where('id', $product->id)->first();

                    $quantity = $flashsaleProduct?->pivot->quantity - $flashsaleProduct->pivot->sale_quantity;

                    if ($quantity == 0) {
                        $quantity = null;
                        $flashsaleProduct = null;
                    } else {
                        $discountPercentage = $flashsale?->pivot->discount;
                    }
                }

                $gift = null;

                if ($cart->gift_id) {
                    $gift = [
                        'id' => $cart->gift_id,
                        'cart_id' => $cart->id,
                        'name' => $cart->gift->name,
                        'thumbnail' => $cart->gift->thumbnail,
                        'price' => (float) $cart->gift->price,
                        'receiver_name' => $cart->gift_receiver_name,
                        'sender_name' => $cart->gift_sender_name,
                        'note' => $cart->gift_note,
                        'address' => $cart->address ? AddressResource::make($cart->address) : null,
                    ];
                }

                $size = $product->sizes()?->where('id', $cart->size)->first();
                $color = $product->colors()?->where('id', $cart->color)->first();

                $sizePrice = $size?->pivot?->price ?? 0;
                $colorPrice = $color?->pivot?->price ?? 0;
                $extraPrice = $sizePrice + $colorPrice;

                $discountPrice = $product->discount_price > 0 ? ($product->discount_price + $extraPrice) : 0;
                if ($flashsaleProduct) {
                    $discountPrice = $flashsaleProduct->pivot->price + $extraPrice;
                }

                $mainPrice = $product->price + $extraPrice;

                // calculate vat taxes
                $priceTaxAmount = 0;
                $discountTaxAmount = 0;
                foreach ($product->vatTaxes ?? [] as $tax) {
                    if ($tax->percentage > 0) {
                        $priceTaxAmount += $mainPrice * ($tax->percentage / 100);
                        $discountPrice > 0 ? $discountTaxAmount += $discountPrice * ($tax->percentage / 100) : null;
                    }
                }

                $mainPrice += $priceTaxAmount;
                $discountPrice > 0 ? $discountPrice += $discountTaxAmount : null;

                if ($discountPrice > 0) {
                    $discountPercentage = ($mainPrice - $discountPrice) / $mainPrice * 100;
                }

                $productArray[] = (object) [
                    'id' => $product->id,
                    'quantity' => (int) $cart->quantity,
                    'name' => $product->name,
                    'thumbnail' => $product->thumbnail,
                    'brand' => $product->brand?->name ?? null,
                    'price' => (float) number_format($mainPrice, 2, '.', ''),
                    'discount_price' => (float) number_format($discountPrice, 2, '.', ''),
                    'discount_percentage' => (float) number_format($discountPercentage, 2, '.', ''),
                    'rating' => (float) $product->averageRating,
                    'total_reviews' => (string) Number::abbreviate($product->reviews->count(), maxPrecision: 2),
                    'total_sold' => (string) number_format($totalSold, 0, '.', ','),
                    'color' => $color ? ColorResource::make($color) : null,
                    'size' => $size ? SizeResource::make($size) : null,
                    'unit' => $cart->unit,
                    'gift' => $gift,
                ];
            }

            $shop = $products[0]?->shop;
            $hasGift = $shop?->gifts()?->isActive()->count() > 0 ? true : false;
            $shopWiseProducts[] = (object) [
                'shop_id' => $key,
                'shop_name' => $shop->name,
                'shop_logo' => $shop->logo,
                'shop_rating' => (float) $shop->averageRating,
                'has_gift' => (bool) $hasGift,
                'products' => $productArray,

            ];
        }

        return $shopWiseProducts;
    }

    /**
     * Store or update cart by request.
     */
    public static function storeOrUpdateByRequest(CartRequest $request, Product $product): Cart
    {
        $size = $request->size;
        $color = $request->color;
        $unit = $request->unit ?? $product->unit?->name;

        $isBuyNow = $request->is_buy_now ?? false;

        $customer = auth()->user()->customer;

        $cart = $customer->carts()?->where('product_id', $product->id)->where('is_buy_now', $isBuyNow)->first();

        if ($cart) {
            $cart->update([
                'quantity' => $isBuyNow ? 1 : $cart->quantity + 1,
                'size' => $request->size ?? $cart->size,
                'color' => $request->color ?? $cart->color,
                'unit' => $request->unit ?? $cart->unit,
            ]);

            return $cart;
        }

        return self::create([
            'product_id' => $request->product_id,
            'shop_id' => $product->shop->id,
            'is_buy_now' => $isBuyNow,
            'customer_id' => $customer->id,
            'quantity' => $request->quantity ?? 1,
            'size' => $size,
            'color' => $color,
            'unit' => $unit,
        ]);
    }

    public static function checkoutByRequest($request, $carts)
    {
        $totalAmount = 0;
        $deliveryCharge = 0;
        $giftCharge = 0;
        $couponDiscount = 0;
        $payableAmount = 0;
        $taxAmount = 0;

        $shopWiseTotalAmount = [];
        $totalOrderTaxAmount = 0;

        if (! $carts->isEmpty()) {

            foreach ($carts as $cart) {

                $product = $cart->product;
                $flashsale = $product->flashSales?->first();
                $flashsaleProduct = null;
                $quantity = null;

                $price = $product->discount_price > 0 ? $product->discount_price : $product->price;

                if ($flashsale) {
                    $flashsaleProduct = $flashsale?->products()->where('id', $product->id)->first();

                    $quantity = $flashsaleProduct?->pivot->quantity - $flashsaleProduct->pivot->sale_quantity;

                    if ($quantity == 0) {
                        $quantity = null;
                        $flashsaleProduct = null;
                    } else {
                        $price = $flashsaleProduct->pivot->price;
                    }
                }

                $sizePrice = $product->sizes()?->where('id', $cart->size)->first()?->pivot?->price ?? 0;
                $price = $price + $sizePrice;

                $colorPrice = $product->colors()?->where('id', $cart->color)->first()?->pivot?->price ?? 0;
                $price = $price + $colorPrice;

                foreach ($product->vatTaxes ?? [] as $tax) {
                    if ($tax->percentage > 0) {
                        $taxAmount += $price * ($tax->percentage / 100);
                    }
                }
                $price += $taxAmount;

                // get shop wise total amount
                $shop = $product->shop;
                if (array_key_exists($shop->id, $shopWiseTotalAmount)) {
                    $currentAmount = $shopWiseTotalAmount[$shop->id];
                    $shopWiseTotalAmount[$shop->id] = $currentAmount + ($price * $cart->quantity);
                } else {
                    $shopWiseTotalAmount[$shop->id] = $price * $cart->quantity;
                }

                $totalAmount += $price * $cart->quantity;

                if ($cart->gift) {
                    $giftCharge += $cart->gift->price;
                }
            }

            $groupCarts = $carts->groupBy('shop_id');

            // get delivery charge
            $deliveryCharge = 0;
            foreach ($groupCarts as $shopId => $shopCarts) {

                $productQty = 0;

                foreach ($shopCarts as $cart) {
                    if ($cart->address && $cart->gift) {
                        $deliveryCharge += getDeliveryCharge($cart->quantity);
                    } else {
                        $productQty += $cart->quantity;
                    }
                }

                if ($productQty > 0) {
                    $deliveryCharge += getDeliveryCharge($productQty);
                }
            }

            // generate array for get discount
            $products = collect([]);
            foreach ($carts as $cart) {
                $products->push([
                    'id' => $cart->product_id,
                    'quantity' => (int) $cart->quantity,
                    'shop_id' => $cart->shop_id,
                ]);
            }
            $array = (object) [
                'coupon_code' => $request->coupon_code,
                'products' => $products,
            ];

            // get coupon discount
            $getDiscount = CouponRepository::getCouponDiscount($array);

            $couponDiscount = $getDiscount['discount_amount'];

            $totalAmount += $giftCharge;

            $payableAmount = $totalAmount + $deliveryCharge - $couponDiscount;
        }

        // get order base tax
        $orderBaseTax = VatTaxRepository::getOrderBaseTax();
        foreach ($shopWiseTotalAmount as $shopId => $subtotal) {
            if ($orderBaseTax && $orderBaseTax->deduction == DeductionType::EXCLUSIVE->value && $orderBaseTax->percentage > 0) {
                $vatTaxAmount = $subtotal * ($orderBaseTax->percentage / 100);
                $totalOrderTaxAmount += $vatTaxAmount;
            }
        }

        $payableAmount += $totalOrderTaxAmount;

        return [
            'total_amount' => (float) round($totalAmount, 2),
            'delivery_charge' => (float) round($deliveryCharge, 2),
            'coupon_discount' => (float) round($couponDiscount, 2),
            'order_tax_amount' => (float) round($totalOrderTaxAmount, 2),
            'payable_amount' => (float) round($payableAmount, 2),
            'gift_charge' => (float) round($giftCharge, 2),
            // 'product_tax_amount' => (float) round($taxAmount, 2),
        ];
    }

    public static function giftAddToCart(GiftRequest $request, Cart $cart): Cart
    {
        $cart->update([
            'gift_id' => $request->gift_id,
            'gift_receiver_name' => $request->receiver_name,
            'gift_sender_name' => $request->sender_name ?? auth()->user()->name,
            'gift_note' => $request->note,
            'address_id' => $request->address_id,
        ]);

        return $cart;
    }

    public static function giftDeleteToCart($request)
    {
        $cart = self::find($request->cart_id);

        if ($cart) {
            $cart->update([
                'gift_id' => null,
                'gift_receiver_name' => null,
                'gift_sender_name' => null,
                'gift_note' => null,
                'address_id' => null,
            ]);
        }

        return $cart;
    }
}
