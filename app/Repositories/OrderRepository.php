<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Enums\DeductionType;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\OrderRequest;
use App\Models\AdminCoupon;
use App\Models\GeneraleSetting;
use App\Models\Order;
use App\Models\OrderGift;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shop;
use App\Services\NotificationServices;

class OrderRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return Order::class;
    }

    public static function getShopSales($shopId)
    {
        return self::query()->withoutGlobalScopes()->where('shop_id', $shopId)->get();
    }

    /**
     * Store new order from cart
     */
    public static function storeByrequestFromCart(OrderRequest $request, $paymentMethod, $carts): Payment
    {
        $totalPayableAmount = 0;

        $payment = Payment::create([
            'amount' => $totalPayableAmount,
            'payment_method' => $request->payment_method,
        ]);

        $shopProducts = $carts->groupBy('shop_id');

        foreach ($shopProducts as $shopId => $cartProducts) {

            $shop = Shop::find($shopId);

            $newCartProducts = [];

            $giftProcucts = [];

            foreach ($cartProducts as $carProduct) {
                if ($carProduct->gift && $carProduct->address_id && $request->address_id != $carProduct->address_id) {
                    $giftProcucts[] = $carProduct;
                } else {
                    $newCartProducts[] = $carProduct;
                }
            }

            foreach ($giftProcucts as $giftProduct) {

                $getCartAmounts = self::getCartWiseAmounts($shop, $giftProduct, $request->coupon_code);

                $order = self::createNewOrder($request, $shop, $paymentMethod, $getCartAmounts);

                $totalPayableAmount += $getCartAmounts['payableAmount'];

                $payment->orders()->attach($order->id);

                $giftProduct->product->decrement('quantity', $giftProduct->quantity);

                $order->products()->attach($giftProduct->product->id, [
                    'quantity' => $giftProduct->quantity,
                    'color' => $giftProduct->color,
                    'size' => $giftProduct->size,
                    'unit' => $giftProduct->unit,
                    'is_gift' => true,
                ]);

                $orderGift = OrderGift::create([
                    'order_id' => $order->id,
                    'gift_id' => $giftProduct->gift_id,
                    'address_id' => $giftProduct->address_id,
                    'sender_name' => $giftProduct->gift_sender_name,
                    'receiver_name' => $giftProduct->gift_receiver_name,
                    'price' => $giftProduct->gift->price,
                    'note' => $giftProduct->gift_note,
                ]);

                $order->update([
                    'order_gift_id' => $orderGift->id,
                    'address_id' => $giftProduct->address_id,
                    'instruction' => $giftProduct->gift_note,
                ]);
            }

            $getCartAmounts = self::getCartWiseAmounts($shop, collect($newCartProducts), $request->coupon_code);

            $order = self::createNewOrder($request, $shop, $paymentMethod, $getCartAmounts);

            $totalPayableAmount += $getCartAmounts['payableAmount'];
            $payment->orders()->attach($order->id);

            foreach ($newCartProducts as $cart) {

                $cart->product->decrement('quantity', $cart->quantity);

                $product = $cart->product;
                $price = $product->discount_price > 0 ? $product->discount_price : $product->price;

                $flashsale = $product->flashSales?->first();
                $flashsaleProduct = null;
                $quantity = 0;

                $saleQty = $cart->quantity;

                if ($flashsale) {
                    $flashsaleProduct = $flashsale?->products()->where('id', $product->id)->first();

                    $quantity = $flashsaleProduct?->pivot->quantity - $flashsaleProduct->pivot->sale_quantity;

                    if ($quantity == 0) {
                        $flashsaleProduct = null;
                    } else {
                        $price = $flashsaleProduct->pivot->price;
                        $saleQty += $flashsaleProduct->pivot->sale_quantity;

                        $flashsale->products()->updateExistingPivot($product->id, [
                            'sale_quantity' => $saleQty,
                        ]);
                    }
                }

                $sizePrice = $product->sizes()?->where('id', $cart->size)->first()?->pivot?->price ?? 0;
                $price = $price + $sizePrice;

                $colorPrice = $product->colors()?->where('id', $cart->color)->first()?->pivot?->price ?? 0;
                $price = $price + $colorPrice;

                $size = $product->sizes()?->where('id', $cart->size)->first();
                $color = $product->colors()?->where('id', $cart->color)->first();

                // calculate vat taxes
                $priceTaxAmount = 0;
                foreach ($product->vatTaxes ?? [] as $tax) {
                    if ($tax->percentage > 0) {
                        $priceTaxAmount += ($price * ($tax->percentage / 100));
                    }
                }

                $price += $priceTaxAmount;

                $order->products()->attach($product->id, [
                    'quantity' => $cart->quantity,
                    'color' => $color?->name,
                    'size' => $size?->name,
                    'unit' => $cart->unit,
                    'is_gift' => $cart->gift_id ? true : false,
                    'price' => $price,
                ]);

                if ($cart->gift_id) {
                    $orderGift = OrderGift::create([
                        'order_id' => $order->id,
                        'gift_id' => $cart->gift_id,
                        'address_id' => $cart->address_id,
                        'sender_name' => $cart->gift_sender_name,
                        'receiver_name' => $cart->gift_receiver_name,
                        'price' => $cart->gift?->price,
                        'note' => $cart->gift_note,
                    ]);

                    $order->update([
                        'order_gift_id' => $orderGift->id,
                    ]);
                }
            }
        }

        $payment->update([
            'amount' => $totalPayableAmount,
        ]);

        $isBuyNow = $request->is_buy_now ?? false;
        $customer = auth()->user()->customer;

        $customer->carts()->whereIn('shop_id', $request->shop_ids)->where('is_buy_now', $isBuyNow)->delete();

        return $payment;
    }
    public static function storeByRequestFromGuestCart(OrderRequest $request, $paymentMethod, $cartItems): Payment
    {
        $totalPayableAmount = 0;

        // ✅ Create Payment Record
        $payment = Payment::create([
            'amount' => 0,
            'payment_method' => $paymentMethod,
        ]);

        //  Group products by shop
        $shopProducts = $cartItems->groupBy('shop_id');

        foreach ($shopProducts as $shopId => $cartProducts) {
            $shop = Shop::find($shopId);
            $giftProducts = [];
            $regularProducts = [];

            // Separate gift and regular products
            foreach ($cartProducts as $cartProduct) {
                if (isset($cartProduct['is_gift']) && $cartProduct['is_gift']) {
                    $giftProducts[] = $cartProduct;
                } else {
                    $regularProducts[] = $cartProduct;
                }
            }

            // Process Gift Products (if any)
            foreach ($giftProducts as $giftProduct) {
                $cartAmounts = self::getCartWiseAmounts($shop, collect([$giftProduct]), $request->coupon_code);
                $order = self::createNewOrder($request, $shop, $paymentMethod, $cartAmounts);
                $totalPayableAmount += $cartAmounts['payableAmount'];

                $payment->orders()->attach($order->id);

                //  Attach Gift Product to Order
                $product = Product::find($giftProduct['product_id']);
                if ($product) {
                    $product->decrement('quantity', $giftProduct['quantity']);
                }

                $order->products()->attach($product->id, [
                    'quantity' => $giftProduct['quantity'],
                    'color' => $giftProduct['color'] ?? null,
                    'size' => $giftProduct['size'] ?? null,
                    'unit' => $giftProduct['unit'] ?? null,
                    'is_gift' => true,
                    'price' => $giftProduct['price'] ?? 0,
                ]);

                //  Store Gift Record
                OrderGift::create([
                    'order_id' => $order->id,
                    'gift_id' => $giftProduct['gift_id'] ?? null,
                    'address_id' => $giftProduct['address_id'] ?? null,
                    'sender_name' => $giftProduct['gift_sender_name'] ?? '',
                    'receiver_name' => $giftProduct['gift_receiver_name'] ?? '',
                    'price' => $giftProduct['gift_price'] ?? 0,
                    'note' => $giftProduct['gift_note'] ?? '',
                ]);
            }

            //  Process Regular Products
            if (!empty($regularProducts)) {
                $cartAmounts = self::getCartWiseAmounts($shop, collect($regularProducts), $request->coupon_code);
                $order = self::createNewOrder($request, $shop, $paymentMethod, $cartAmounts,$request->email);
                $totalPayableAmount += $cartAmounts['payableAmount'];

                $payment->orders()->attach($order->id);

                // Attach Regular Products to Order
                foreach ($regularProducts as $cartItem) {
                    $product = Product::find($cartItem['product_id']);
                    if ($product) {
                        $product->decrement('quantity', $cartItem['quantity']);

                        // ⚡ Handle Flash Sale Quantity
                        $flashsale = $product->flashSales?->first();
                        if ($flashsale) {
                            $flashsaleProduct = $flashsale->products()->where('id', $product->id)->first();
                            if ($flashsaleProduct) {
                                $flashsale->products()->updateExistingPivot($product->id, [
                                    'sale_quantity' => $flashsaleProduct->pivot->sale_quantity + $cartItem['quantity'],
                                ]);
                            }
                        }

                        // Attach Product to Order
                        $order->products()->attach($product->id, [
                            'quantity' => $cartItem['quantity'],
                            'color' => $cartItem['color'] ?? null,
                            'size' => $cartItem['size'] ?? null,
                            'unit' => $cartItem['unit'] ?? null,
                            'is_gift' => $cartItem['is_gift'] ?? false,
                            'price' => $cartItem['price'] ?? 0,
                        ]);

                        //  Handle Gift (if any)
                        if (isset($cartItem['gift_id'])) {
                            OrderGift::create([
                                'order_id' => $order->id,
                                'gift_id' => $cartItem['gift_id'],
                                'address_id' => $cartItem['address_id'] ?? null,
                                'sender_name' => $cartItem['gift_sender_name'] ?? '',
                                'receiver_name' => $cartItem['gift_receiver_name'] ?? '',
                                'price' => $cartItem['gift_price'] ?? 0,
                                'note' => $cartItem['gift_note'] ?? '',
                            ]);
                        }
                    }
                }
            }
        }

        //  Update Payment Amount
        $payment->update([
            'amount' => $totalPayableAmount,
        ]);

        return $payment;
    }

    private static function createNewOrder($request, $shop, $paymentMethod, $getCartAmounts,$email)
    {
        $lastOrderId = self::query()->max('id');

        $order = self::create([
            'email' => $email ?? auth()->user()?->email, // ✅ Use guest email if provided
            'phone' => $request->phone,
            'shop_id' => $shop->id,
            'order_code' => str_pad($lastOrderId + 1, 6, '0', STR_PAD_LEFT),
            'prefix' => $shop->prefix ?? 'RC',
            'customer_id' => auth()->user()->customer->id??null,
            'coupon_id' => $getCartAmounts['coupon'],
            'delivery_charge' => $getCartAmounts['deliveryCharge'],
            'payable_amount' => $getCartAmounts['payableAmount'],
            'total_amount' => $getCartAmounts['totalAmount'],
            'tax_amount' => $getCartAmounts['totalTaxAmount'],
            'coupon_discount' => $getCartAmounts['discount'],
            'payment_method' => $paymentMethod->value,
            'order_status' => OrderStatus::PENDING->value,
            'address_id' => $request->address_id,
            'instruction' => $request->note,
            'payment_status' => PaymentStatus::PENDING->value,
            'referral_code' => $request->referral_code??null,

        ]);

        return $order;
    }

    private static function getCartWiseAmounts(Shop $shop, $products, $couponCode = null): array
    {
        $totalAmount = 0;
        $discount = 0;
        $giftCharge = 0;
        $coupon = null;
        $totalTaxAmount = 0;

        $orderQty = collect($products)->sum(fn($cart) => is_array($cart) ? $cart['quantity'] : $cart->quantity);
        $deliveryCharge = getDeliveryCharge($orderQty);

        foreach ($products as $cart) {
            // Handle cart as object or array
            $product = is_array($cart) ? Product::find($cart['product_id']) : $cart->product;
            $quantity = is_array($cart) ? $cart['quantity'] : $cart->quantity;
            $sizeId = is_array($cart) ? ($cart['size'] ?? null) : ($cart->size ?? null);
            $colorId = is_array($cart) ? ($cart['color'] ?? null) : ($cart->color ?? null);
            $isGift = is_array($cart) ? ($cart['is_gift'] ?? false) : ($cart->gift ?? false);

            // Base Price with Flash Sale Check
            $price = $product->discount_price > 0 ? $product->discount_price : $product->price;

            $flashsale = $product->flashSales?->first();
            if ($flashsale) {
                $flashsaleProduct = $flashsale->products()->where('id', $product->id)->first();
                if ($flashsaleProduct && ($flashsaleProduct->pivot->quantity - $flashsaleProduct->pivot->sale_quantity) > 0) {
                    $price = $flashsaleProduct->pivot->price;
                }
            }

            // Add Size and Color Prices
            $sizePrice = $product->sizes()?->where('id', $sizeId)->first()?->pivot?->price ?? 0;
            $colorPrice = $product->colors()?->where('id', $colorId)->first()?->pivot?->price ?? 0;
            $price += ($sizePrice + $colorPrice);

            // Add VAT Taxes
            $taxAmount = 0;
            foreach ($product->vatTaxes ?? [] as $tax) {
                if ($tax->percentage > 0) {
                    $taxAmount += $price * ($tax->percentage / 100);
                }
            }
            $price += $taxAmount;
            $totalTaxAmount += $taxAmount * $quantity;

            //  Add Gift Charges
            if ($isGift) {
                $giftCharge += is_array($cart) ? ($cart['gift_price'] ?? 0) : ($cart->gift->price ?? 0);
            }

            //  Accumulate Total Amount
            $totalAmount += ($price * $quantity);
        }

        // Add Gift Charge
        $totalAmount += $giftCharge;

        // Add Order Base VAT
        $orderBaseTax = VatTaxRepository::getOrderBaseTax();
        if ($orderBaseTax && $orderBaseTax->deduction == DeductionType::EXCLUSIVE->value && $orderBaseTax->percentage > 0) {
            $vatTaxAmount = $totalAmount * ($orderBaseTax->percentage / 100);
            $totalTaxAmount += $vatTaxAmount;
        }

        //  Get Coupon Discount
        $couponDiscount = self::getCouponDiscount($totalAmount, $shop->id, $couponCode);
        if ($couponDiscount['total_discount_amount'] > 0) {
            $discount += $couponDiscount['total_discount_amount'];
            $coupon = $couponDiscount['coupon'];
        }

        //  Calculate Final Payable Amount
        $payableAmount = ($totalAmount + $deliveryCharge + $totalTaxAmount) - $discount;

        // Return Calculated Amounts
        return [
            'totalAmount' => $totalAmount,
            'totalTaxAmount' => $totalTaxAmount,
            'payableAmount' => $payableAmount,
            'discount' => $discount,
            'deliveryCharge' => $deliveryCharge,
            'coupon' => $coupon?->id,
            'giftCharge' => $giftCharge,
        ];
    }

    /**
     * Creates a new order based on the provided order, generates a new order code,
     * and associates it with the corresponding shop orders and products.
     *
     * @param  Order  $order  The original order to be used as a base for the new order
     * @return Order The newly created order
     */
    public static function reOrder(Order $order): Order
    {
        $lastOrderId = self::query()->max('id');

        $newOrder = self::create([
            'shop_id' => $order->shop_id,
            'order_code' => str_pad($lastOrderId + 1, 6, '0', STR_PAD_LEFT),
            'prefix' => 'RC',
            'customer_id' => $order->customer_id,
            'coupon_id' => $order->coupon_id ?? null,
            'delivery_charge' => $order->delivery_charge,
            'payable_amount' => $order->payable_amount,
            'total_amount' => $order->total_amount,
            'tax_amount' => $order->tax_amount,
            'discount' => $order->discount,
            'payment_method' => $order->payment_method,
            'order_status' => OrderStatus::PENDING->value,
            'address_id' => $order->address_id,
            'instruction' => $order->instruction,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);

        foreach ($order->products as $product) {

            $qty = $product->pivot->quantity;

            $product->decrement('quantity', $qty);

            $newOrder->products()->attach($product->id, [
                'quantity' => $product->pivot->quantity,
                'color' => $product->pivot->color ?? null,
                'size' => $product->pivot->size ?? null,
                'unit' => $product->pivot->unit ?? null,
                'price' => $product->pivot->price,
            ]);
        }

        return $order;
    }

    /**
     * Get applied coupon orders
     *
     * @param  mixed  $coupon
     * @return collection
     */
    public static function getAppliedCouponOrders($coupon)
    {
        return auth()->user()->customer?->orders()?->where('coupon_id', $coupon->id)->get();
    }

    /**
     * Get coupon discount
     *
     * @param  mixed  $totalAmount
     * @param  mixed  $shopId
     * @param  mixed  $couponCode
     * @return array
     */
    public static function getCouponDiscount($totalAmount, $shopId, $couponCode = null)
    {
        $totalOrderAmount = 0;
        $totalDiscountAmount = 0;
        $coupon = null;

        if ($couponCode) {
            $shop = Shop::find($shopId);
            $coupon = $shop->coupons()->where('code', $couponCode)->Active()->isValid()->first();

            if (! $coupon) {
                $coupon = AdminCoupon::where('shop_id', $shopId)->whereHas('coupon', function ($query) use ($couponCode) {
                    $query->where('code', $couponCode)->Active()->isValid();
                })->first()?->coupon;
            }

            if ($coupon) {
                $discount = self::getCouponDiscountAmount($coupon, $totalAmount);

                $totalOrderAmount += $discount['total_amount'];
                $totalDiscountAmount += $discount['discount_amount'];
            }
        } else {

            $collectedCoupons = CouponRepository::getCollectedCoupons($shopId);

            foreach ($collectedCoupons as $collectedCoupon) {

                $discount = self::getCouponDiscountAmount($collectedCoupon, $totalAmount);

                $totalOrderAmount += $discount['total_amount'];

                if ($discount['discount_amount'] > 0) {
                    $coupon = $collectedCoupon;
                    $totalDiscountAmount += $discount['discount_amount'];
                    break;
                }
            }
        }

        return [
            'total_order_amount' => $totalOrderAmount,
            'total_discount_amount' => $totalDiscountAmount,
            'coupon' => $coupon,
        ];
    }

    /**
     * Get coupon discount amount
     *
     * @param  mixed  $coupon
     * @param  mixed  $totalAmount
     * @return array
     */
    private static function getCouponDiscountAmount($coupon, $totalAmount)
    {
        $appliedOrders = self::getAppliedCouponOrders($coupon);

        $amount = $coupon->type->value == DiscountType::PERCENTAGE->value ? ($totalAmount * $coupon->discount) / 100 : $coupon->discount;

        $couponDiscount = 0;
        if ($appliedOrders->count() < ($coupon->limit_for_user ?? 500) && $coupon->min_amount <= $totalAmount) {
            $couponDiscount = $amount;
            if ($coupon->max_discount_amount && $coupon->max_discount_amount < $amount) {
                $couponDiscount = $coupon->max_discount_amount;
            }
        }

        return [
            'total_amount' => $totalAmount,
            'discount_amount' => (float) round($couponDiscount ?? 0, 2),
        ];
    }

    /**
     * Order status update from rider
     */
    public static function OrderStatusUpdateFromRider(Order $order, $driverOrder, $orderStatus)
    {
        if ($orderStatus == OrderStatus::PROCESSING->value) {
            $driverOrder->update(['is_accept' => true]);
        }

        $order->update([
            'order_status' => ($orderStatus == 'deliveredAndPaid') ? OrderStatus::DELIVERED->value : $orderStatus,
        ]);

        if ($orderStatus == OrderStatus::PICKUP->value) {
            $order->update([
                'pick_date' => now(),
                'order_status' => OrderStatus::ON_THE_WAY->value,
            ]);
        }

        $paymentMethod = $order->payment_method->value;

        $isDelivery = false;
        if ($paymentMethod != PaymentMethod::CASH->value && $orderStatus == OrderStatus::DELIVERED->value) {
            $isDelivery = true;
        }

        if (($orderStatus == 'deliveredAndPaid') || $isDelivery) {

            $driverOrder->update(['is_completed' => true]);

            if ($paymentMethod == PaymentMethod::CASH->value) {
                $driverOrder->update(['cash_collect' => true]);

                $totalCashCollected = $driverOrder->driver->total_cash_collected + $order->payable_amount;

                $driverOrder->driver->update([
                    'total_cash_collected' => $totalCashCollected,
                ]);
            }

            $generaleSetting = GeneraleSetting::first();

            $commission = 0;

            if ($generaleSetting?->commission_charge != 'monthly') {

                if ($generaleSetting?->commission_type != 'fixed') {
                    $commission = $order->total_amount * $generaleSetting->commission / 100;
                } else {
                    $commission = $generaleSetting->commission ?? 0;
                }
            }

            $order->update([
                'delivery_date' => now(),
                'delivered_at' => now(),
                'payment_status' => PaymentStatus::PAID->value,
                'admin_commission' => $commission,
            ]);

            $wallet = $order->shop->user->wallet;

            WalletRepository::updateByRequest($wallet, $order->total_amount, 'credit');

            TransactionRepository::storeByRequest($wallet, $commission, 'debit', true, true, 'admin commission added', 'order commision added in admin wallet');

            $driverWallet = DriverRepository::getWallet($driverOrder->driver);

            $deliveryCharge = $order->delivery_charge;

            WalletRepository::updateByRequest($driverWallet, $deliveryCharge, 'credit');
        }

        $message = "Hello {$order->customer->user->name}. Your order status is {$orderStatus}. OrderID: {$order->prefix}{$order->order_code}";

        $title = 'Order Status Update';

        if ($order->customer->user->devices->count() > 0) {

            $deviceKeys = $order->customer->user->devices->pluck('key')->toArray();
            try {
                NotificationServices::sendNotification($message, $deviceKeys, $title);
            } catch (\Exception $e) {
            }
        }

        NotificationRepository::storeByRequest((object) [
            'title' => $title,
            'content' => $message,
            'user_id' => $order->customer->user_id,
            'url' => $order->id,
            'type' => 'order',
            'icon' => null,
            'is_read' => false,
        ]);
    }
}
