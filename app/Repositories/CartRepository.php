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
                if (is_array($cart)) {
                    $cart = (object)$cart;
                }

                // Ensure $cart->product is an Eloquent model
                if (!isset($cart->product) || is_array($cart->product)) {
                    if (isset($cart->product_id)) {
                        $cart->product = \App\Models\Product::find($cart->product_id); // ✅ Fetch product from DB
                    }
                }
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

                if (isset($cart->gift_id) && $cart->gift_id) {
                    //  Ensure $cart->gift is an Eloquent model
                    if (!isset($cart->gift) || is_array($cart->gift)) {
                        $cart->gift = \App\Models\Gift::find($cart->gift_id); // ✅ Fetch gift from DB
                    }
                    $gift = [
                        'id' => $cart->gift_id,
                        'cart_id' => $cart->id,
                        'name' => $cart->gift->name,
                        'thumbnail' => $cart->gift->thumbnail,
                        'price' => (float)$cart->gift->price,
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

                $productArray[] = (object)[
                    'id' => $product->id,
                    'quantity' => (int)$cart->quantity,
                    'name' => $product->name,
                    'thumbnail' => $product->thumbnail,
                    'brand' => $product->brand?->name ?? null,
                    'price' => (float)number_format($mainPrice, 2, '.', ''),
                    'discount_price' => (float)number_format($discountPrice, 2, '.', ''),
                    'discount_percentage' => (float)number_format($discountPercentage, 2, '.', ''),
                    'rating' => (float)$product->averageRating,
                    'total_reviews' => (string)Number::abbreviate($product->reviews->count(), maxPrecision: 2),
                    'total_sold' => (string)number_format($totalSold, 0, '.', ','),
                    'color' => $color ? ColorResource::make($color) : null,
                    'size' => $size ? SizeResource::make($size) : null,
                    'unit' => $cart->unit,
                    'gift' => $gift,
                ];
            }
            if (!empty($products) && isset($products[0])) {
                //  Convert $products[0] to an object if it's an array
                if (is_array($products[0])) {
                    $products[0] = (object) $products[0];
                }

                // Ensure $products[0]->shop is an Eloquent model
                if (!isset($products[0]->shop) || is_array($products[0]->shop)) {
                    if (isset($products[0]->shop_id)) {
                        $products[0]->shop = \App\Models\Shop::find($products[0]->shop_id); // ✅ Fetch shop from DB
                    }
                }}

            $shop = $products[0]?->shop;
            $hasGift = $shop?->gifts()?->isActive()->count() > 0 ? true : false;
            $shopWiseProducts[] = (object)[
                'shop_id' => $key,
                'shop_name' => $shop->name,
                'shop_logo' => $shop->logo,
                'shop_rating' => (float)$shop->averageRating,
                'has_gift' => (bool)$hasGift,
                'products' => $productArray,

            ];
        }

        return $shopWiseProducts;
    }

    /**
     * Store or update cart by request.
     */
    public static function storeOrUpdateByRequest(CartRequest $request, Product $product)
    {
        $size = $request->size;
        $color = $request->color;
        $unit = $request->unit ?? $product->unit?->name;

        $isBuyNow = $request->is_buy_now ?? false;

        // Check if user is authenticated
        $customer = auth()->check() ? auth()->user()->customer : null;

        if ($customer) {
            // Fetch existing cart item for authenticated user
            $cart = $customer->carts()?->where('product_id', $product->id)->where('is_buy_now', $isBuyNow)->first();

            if ($cart) {
                // Update existing cart item
                $cart->update([
                    'quantity' => $isBuyNow ? 1 : $cart->quantity + 1,
                    'size' => $request->size ?? $cart->size,
                    'color' => $request->color ?? $cart->color,
                    'unit' => $request->unit ?? $cart->unit,
                ]);

                return $cart;
            }

            // Create a new cart entry for the authenticated user
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
        } else {
            // Handle guest cart using session storage
            $guestCart = session()->get('guest_cart', []);

            // Check if product is already in guest cart
            foreach ($guestCart as &$item) {
                if ($item['product_id'] == $request->product_id && $item['is_buy_now'] == $isBuyNow) {
                    $item['quantity'] += $request->quantity ?? 1;
                    session()->put('guest_cart', $guestCart);
                    return $item; // Return the updated cart item
                }
            }

            // Add new product to guest cart
            $cartItem = [
                'product_id' => $request->product_id,
                'shop_id' => $product->shop->id,
                'is_buy_now' => $isBuyNow,
                'quantity' => $request->quantity ?? 1,
                'size' => $size,
                'color' => $color,
                'unit' => $unit,
            ];

            $guestCart[] = $cartItem;
            session()->put('guest_cart', $guestCart);

            return $cartItem; // Return the newly added item
        }
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

        if (!$carts->isEmpty()) {
            foreach ($carts as &$cart) { //  Use reference to modify directly

                //  Convert $cart from array to object
                if (is_array($cart)) {
                    $cart = (object)$cart;
                }

                //  Ensure $cart->product exists
                if (!isset($cart->product) || !$cart->product) {
                    if (isset($cart->product_id)) {
                        $cart->product = \App\Models\Product::find($cart->product_id);
                    }
                }

                if (!isset($cart->product) || !$cart->product) {
                    \Log::error("Missing product for cart item", ['cart' => (array)$cart]);
                    continue; // Skip invalid cart item
                }

                //  Ensure cart has quantity
                if (!isset($cart->quantity)) {
                    \Log::error("Missing quantity for cart item", ['cart' => (array)$cart]);
                    continue; // Skip invalid cart item
                }

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

                $sizePrice = $product->sizes()?->where('id', $cart->size ?? null)->first()?->pivot?->price ?? 0;
                $price += $sizePrice;

                $colorPrice = $product->colors()?->where('id', $cart->color ?? null)->first()?->pivot?->price ?? 0;
                $price += $colorPrice;

                foreach ($product->vatTaxes ?? [] as $tax) {
                    if ($tax->percentage > 0) {
                        $taxAmount += $price * ($tax->percentage / 100);
                    }
                }
                $price += $taxAmount;

                //  Ensure shop exists before accessing
                if (!isset($product->shop)) {
                    \Log::error("Missing shop for product", ['product' => (array)$product]);
                    continue; // Skip item without a shop
                }

                $shop = $product->shop;

                //  Get shop-wise total amount
                if (array_key_exists($shop->id, $shopWiseTotalAmount)) {
                    $shopWiseTotalAmount[$shop->id] += ($price * $cart->quantity);
                } else {
                    $shopWiseTotalAmount[$shop->id] = $price * $cart->quantity;
                }

                //  Now `$cart->quantity` will always exist
                $totalAmount += $price * $cart->quantity;

                if ($cart->gift ?? false) {
                    $giftCharge += $cart->gift->price ?? 0;
                }
            }

            $groupCarts = collect($carts)->groupBy('shop_id');

            //  Get delivery charge
            $deliveryCharge = 0;
            foreach ($groupCarts as $shopId => $shopCarts) {
                $productQty = 0;

                foreach ($shopCarts as $cart) {
                    if (is_array($cart)) {
                        $cart = (object)$cart;
                    }
                    if (($cart->address ?? false) && ($cart->gift ?? false)) {
                        $deliveryCharge += getDeliveryCharge($cart->quantity);
                    } else {
                        $productQty += $cart->quantity;
                    }
                }
                if ($productQty > 0) {
                    $deliveryCharge += getDeliveryCharge($productQty);
                }
            }

            //  Prepare data for coupon discount
            $products = collect([]);
            foreach ($carts as $cart) {
                if (is_array($cart)) {
                    $cart = (object)$cart;
                }
                $products->push([
                    'id' => $cart->product_id,
                    'quantity' => (int)$cart->quantity,
                    'shop_id' => $cart->shop_id,
                ]);
            }

            $array = (object)[
                'coupon_code' => $request->coupon_code,
                'products' => $products,
            ];

            //  Get coupon discount
            $getDiscount = CouponRepository::getCouponDiscount($array);
            $couponDiscount = $getDiscount['discount_amount'] ?? 0;

            $totalAmount += $giftCharge;
            $payableAmount = $totalAmount + $deliveryCharge - $couponDiscount;
        }

        //  Get order base tax
        $orderBaseTax = VatTaxRepository::getOrderBaseTax();
        foreach ($shopWiseTotalAmount as $shopId => $subtotal) {
            if ($orderBaseTax && $orderBaseTax->deduction == DeductionType::EXCLUSIVE->value && $orderBaseTax->percentage > 0) {
                $vatTaxAmount = $subtotal * ($orderBaseTax->percentage / 100);
                $totalOrderTaxAmount += $vatTaxAmount;
            }
        }

        $payableAmount += $totalOrderTaxAmount;

        return [
            'total_amount' => (float)round($totalAmount, 2),
            'delivery_charge' => (float)round($deliveryCharge, 2),
            'coupon_discount' => (float)round($couponDiscount, 2),
            'order_tax_amount' => (float)round($totalOrderTaxAmount, 2),
            'payable_amount' => (float)round($payableAmount, 2),
            'gift_charge' => (float)round($giftCharge, 2),
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
