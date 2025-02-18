<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartRequest;
use App\Http\Requests\CheckoutRequest;
use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $isBuyNow = request()->is_buy_now ?? false;

        $carts = auth()->user()->customer->carts()->where('is_buy_now', $isBuyNow)->get();
        $groupCart = $carts->groupBy('shop_id');
        $shopWiseProducts = CartRepository::ShopWiseCartProducts($groupCart);

        return $this->json('cart list', [
            'total' => $carts->count(),
            'cart_items' => $shopWiseProducts,
        ], 200);
    }
    public function storeGuest(CartRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;
        $product = ProductRepository::find($request->product_id);
        $quantity = $request->quantity ?? 1;

        if (!$product) {
            return $this->json('Product not found', [], 404);
        }

        //  Handle guest cart with session storage
        $cartSession = session()->get('guest_cart', []);

        foreach ($cartSession as &$item) {
            if ($item['product_id'] == $request->product_id && $item['is_buy_now'] == $isBuyNow) {
                $item['quantity'] += $quantity;
                session()->put('guest_cart', $cartSession);
                session()->save();
                return $this->json('Product quantity updated in guest cart', [
                    'total' => count($cartSession),
                    'cart_items' => array_values($cartSession),
                ], 200);
            }
        }

        // Add new product to guest cart
        $cartSession[] = [
            'product_id' => $product->id,
            'shop_id' => $product->shop->id,
            'is_buy_now' => $isBuyNow,
            'quantity' => $quantity,
            'size' => $request->size,
            'color' => $request->color,
            'unit' => $request->unit ?? $product->unit?->name,
        ];

        session()->put('guest_cart', $cartSession);
        session()->save();

        return $this->json('Product added to guest cart', [
            'total' => count($cartSession),
            'cart_items' => array_values($cartSession),
        ], 200);
    }
    public function indexGuest()
    {
        $isBuyNow = request()->is_buy_now ?? false;
        $guestCart = session()->get('guest_cart', []);

        //  Filter cart items based on "Buy Now" status
        $filteredGuestCart = array_filter($guestCart, function ($item) use ($isBuyNow) {
            return $item['is_buy_now'] == $isBuyNow;
        });

        return $this->json('Guest cart retrieved', [
            'total' => count($filteredGuestCart),
            'cart_items' => array_values($filteredGuestCart),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CartRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;

        $product = ProductRepository::find($request->product_id);

        $quantity = $request->quantity ?? 1;

        $customer = auth()->user()->customer;
        $cart = $customer->carts()->where('product_id', $product->id)->first();

        if ($isBuyNow) {

            $buyNowCart = $customer->carts()->where('is_buy_now', true)->first();

            if ($buyNowCart && $buyNowCart->product_id != $request->product_id) {
                $buyNowCart->delete();
            }
        }

        if (($product->quantity < $quantity) || ($product->quantity <= $cart?->quantity)) {
            return $this->json('Sorry! product cart quantity is limited. No more stock', [], 422);
        }

        // store or update cart
        CartRepository::storeOrUpdateByRequest($request, $product);

        $carts = $customer->carts()->where('is_buy_now', $isBuyNow)->get();

        $groupCart = $carts->groupBy('shop_id');
        $shopWiseProducts = CartRepository::ShopWiseCartProducts($groupCart);

        return $this->json('product added to cart', [
            'total' => $carts->count(),
            'cart_items' => $shopWiseProducts,
        ], 200);
    }


    /**
     * increase cart quantity
     */
    public function increment(CartRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;
        $product = ProductRepository::find($request->product_id);

        $customer = auth()->user()->customer;

        $cart = $customer->carts()?->where('product_id', $product->id)->where('is_buy_now', $isBuyNow)->first();

        if (! $cart) {
            return $this->json('Sorry product not found in cart', [], 422);
        }

        $quantity = $cart->quantity;

        $flashSale = $product->flashSales?->first();

        $flashSaleProduct = $flashSale?->products()->where('id', $product->id)->first();

        $productQty = $product->quantity;

        if ($flashSaleProduct) {
            $flashSaleQty = $flashSaleProduct->pivot->quantity - $flashSaleProduct->pivot->sale_quantity;

            if ($flashSaleQty > 0) {
                $productQty = $flashSaleQty;
            }
        }

        if ($productQty > $quantity) {
            $cart->update([
                'quantity' => $quantity + 1,
            ]);
        } else {
            return $this->json('Sorry! product cart quantity is limited. No more stock', [], 422);
        }

        $carts = $customer->carts()->where('is_buy_now', $isBuyNow)->get();
        $groupCart = $carts->groupBy('shop_id');
        $shopWiseProducts = CartRepository::ShopWiseCartProducts($groupCart);

        return $this->json('product quantity increased', [
            'total' => $carts->count(),
            'cart_items' => $shopWiseProducts,
        ], 200);
    }
    public function incrementGuest(CartRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;
        $product = ProductRepository::find($request->product_id);

        // ✅ Retrieve guest cart from session
        $guestCart = session()->get('guest_cart', []);

        // Find the product in the guest cart
        $cartKey = array_search($request->product_id, array_column($guestCart, 'product_id'));

        if ($cartKey === false) {
            return $this->json('Sorry, product not found in cart', [], 422);
        }

        $quantity = $guestCart[$cartKey]['quantity'];

        // Check stock availability
        $flashSale = $product->flashSales?->first();
        $flashSaleProduct = $flashSale?->products()->where('id', $product->id)->first();
        $productQty = $product->quantity;

        if ($flashSaleProduct) {
            $flashSaleQty = $flashSaleProduct->pivot->quantity - $flashSaleProduct->pivot->sale_quantity;
            if ($flashSaleQty > 0) {
                $productQty = $flashSaleQty;
            }
        }

        //  Ensure quantity does not exceed stock
        if ($productQty > $quantity) {
            $guestCart[$cartKey]['quantity'] += 1;
            session()->put('guest_cart', $guestCart);
        } else {
            return $this->json('Sorry! product cart quantity is limited. No more stock', [], 422);
        }

        //  Return updated guest cart
        $groupCart = collect($guestCart)->groupBy('shop_id');
        $shopWiseProducts = CartRepository::ShopWiseCartProducts($groupCart);

        return $this->json('Product quantity increased', [
            'total' => count($guestCart),
            'cart_items' => $shopWiseProducts,
        ], 200);
    }

    /**
     * decrease cart quantity
     * */
    public function decrement(CartRequest $request)
    {

        $isBuyNow = $request->is_buy_now ?? false;

        $product = ProductRepository::find($request->product_id);
        $customer = auth()->user()->customer;
        $cart = $customer->carts()?->where('product_id', $product->id)->where('is_buy_now', $isBuyNow)->first();

        if (! $cart) {
            return $this->json('Sorry product not found in cart', [], 422);
        }

        $message = 'product removed from cart';

        if ($cart->quantity > 1) {
            $cart->update([
                'quantity' => $cart->quantity - 1,
            ]);

            $message = 'product quantity decreased';
        } else {
            $cart->delete();
        }

        $carts = $customer->carts()->where('is_buy_now', $isBuyNow)->get();
        $groupCart = $carts->groupBy('shop_id');
        $shopWiseProducts = CartRepository::ShopWiseCartProducts($groupCart);

        return $this->json($message, [
            'total' => $carts->count(),
            'cart_items' => $shopWiseProducts,
        ], 200);
    }
    public function decrementGuest(CartRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;
        $product = ProductRepository::find($request->product_id);

        //  Retrieve guest cart from session
        $guestCart = session()->get('guest_cart', []);

        // Find the product in the guest cart
        $cartKey = array_search($request->product_id, array_column($guestCart, 'product_id'));

        if ($cartKey === false) {
            return $this->json('Sorry, product not found in cart', [], 422);
        }

        // Decrease quantity but prevent it from going below 1
        if ($guestCart[$cartKey]['quantity'] > 1) {
            $guestCart[$cartKey]['quantity'] -= 1;
            $message = 'Product quantity decreased';
        } else {
            unset($guestCart[$cartKey]); // ✅ Remove product if quantity reaches 0
            $message = 'Product removed from cart';
        }

        //  Update session storage
        session()->put('guest_cart', array_values($guestCart)); // Reindex array to prevent gaps

        // Return updated guest cart
        $groupCart = collect($guestCart)->groupBy('shop_id');
        $shopWiseProducts = CartRepository::ShopWiseCartProducts($groupCart);

        return $this->json($message, [
            'total' => count($guestCart),
            'cart_items' => $shopWiseProducts,
        ], 200);
    }

    public function checkout(CheckoutRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;

        $shopIds = $request->shop_ids ?? [];
        $customer = auth()->user()->customer;

        $carts = $customer->carts()->whereIn('shop_id', $shopIds)->where('is_buy_now', $isBuyNow)->get();

        $checkout = CartRepository::checkoutByRequest($request, $carts);

        $groupCart = $carts->groupBy('shop_id');
        $shopWiseProducts = CartRepository::ShopWiseCartProducts($groupCart);

        $message = 'Checkout information';

        $applyCoupon = false;

        if ($request->coupon_code && $checkout['coupon_discount'] > 0) {
            $applyCoupon = true;
            $message = 'Coupon applied';
        } elseif ($request->coupon_code) {
            $message = 'Coupon not applied';
        }

        return $this->json($message, [
            'checkout' => $checkout,
            'apply_coupon' => $applyCoupon,
            'checkout_items' => $shopWiseProducts,
        ]);
    }
    public function checkoutGuest(CheckoutRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;
        $shopIds = $request->shop_ids ?? [];

        //  Get guest cart from session
        $cartSession = session()->get('guest_cart', []);

        // Filter only the selected shop items
        $guestCarts = array_filter($cartSession, function ($item) use ($shopIds, $isBuyNow) {
            return in_array($item['shop_id'], $shopIds) && $item['is_buy_now'] == $isBuyNow;
        });

        if (empty($guestCarts)) {
            return response()->json(['error' => 'Guest cart is empty'], 400);
        }

        //  Process checkout for guest users
        $checkout = CartRepository::checkoutByRequest($request, collect($guestCarts));
        $groupCart = collect($guestCarts)->groupBy('shop_id');
        $shopWiseProducts = CartRepository::ShopWiseCartProducts($groupCart);

        $message = 'Checkout information';
        $applyCoupon = false;

        if ($request->coupon_code && $checkout['coupon_discount'] > 0) {
            $applyCoupon = true;
            $message = 'Coupon applied';
        } elseif ($request->coupon_code) {
            $message = 'Coupon not applied';
        }

        return response()->json([
            'message' => $message,
            'checkout' => $checkout,
            'apply_coupon' => $applyCoupon,
            'checkout_items' => $shopWiseProducts,
        ], 200);
    }
    public function destroyGuest(CartRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;

        //  Retrieve guest cart from session
        $guestCart = session()->get('guest_cart', []);

        // Find the product in the guest cart
        $cartKey = array_search($request->product_id, array_column($guestCart, 'product_id'));

        if ($cartKey === false) {
            return $this->json('Sorry, product not found in cart', [], 422);
        }

        // Remove product from cart
        unset($guestCart[$cartKey]);

        //  Update session storage (Reindex array)
        session()->put('guest_cart', array_values($guestCart));

        //  Return updated guest cart
        $groupCart = collect($guestCart)->groupBy('shop_id');
        $shopWiseProducts = CartRepository::ShopWiseCartProducts($groupCart);

        return $this->json('Product removed from cart', [
            'total' => count($guestCart),
            'cart_items' => $shopWiseProducts,
        ], 200);
    }

    public function destroy(CartRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;

        $customer = auth()->user()->customer;

        $carts = $customer->carts()->where('product_id', $request->product_id)->get();

        if ($carts->isEmpty()) {
            return $this->json('Sorry product not found in cart', [], 422);
        }

        foreach ($carts as $cart) {
            $cart->delete();
        }

        $carts = $customer->carts()->where('is_buy_now', $isBuyNow)->get();
        $groupCart = $carts->groupBy('shop_id');
        $shopWiseProducts = CartRepository::ShopWiseCartProducts($groupCart);

        return $this->json('product removed from cart', [
            'total' => $carts->count(),
            'cart_items' => $shopWiseProducts,
        ], 200);
    }
}
