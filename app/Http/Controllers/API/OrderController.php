<?php

namespace App\Http\Controllers\API;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\ProductApproveEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderDetailsResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Referral;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders with status filter and pagination options.
     *
     * @param Request $request The HTTP request
     * @return Some_Return_Value json Response
     *
     * @throws Some_Exception_Class If something goes wrong
     */
    public function index(Request $request)
    {
        $orderStatus = $request->order_status;

        $page = $request->page;
        $perPage = $request->per_page;
        $skip = ($page * $perPage) - $perPage;

        $customer = auth()->user()->customer;
        $orders = $customer->orders()
            ->when($orderStatus, function ($query) use ($orderStatus) {
                return $query->where('order_status', $orderStatus);
            })->latest('id');

        $total = $orders->count();

        // paginate
        $orders = $orders->when($perPage && $page, function ($query) use ($perPage, $skip) {
            return $query->skip($skip)->take($perPage);
        })->get();

        // return
        return $this->json('orders', [
            'total' => $total,
            'status_wise_orders' => [
                'all' => $customer->orders()->count(),
                'pending' => $customer->orders()->where('order_status', OrderStatus::PENDING->value)->count(),
                'confirm' => $customer->orders()->where('order_status', OrderStatus::CONFIRM->value)->count(),
                'processing' => $customer->orders()->where('order_status', OrderStatus::PROCESSING->value)->count(),
                'on_the_way' => $customer->orders()->where('order_status', OrderStatus::ON_THE_WAY->value)->count(),
                'delivered' => $customer->orders()->where('order_status', OrderStatus::DELIVERED->value)->count(),
                'cancelled' => $customer->orders()->where('order_status', OrderStatus::CANCELLED->value)->count(),
            ],
            'orders' => OrderResource::collection($orders),
        ]);
    }

    public function indexGuest(Request $request)
    {
        $orderStatus = $request->order_status;
        $page = $request->page ?? 1;
        $perPage = $request->per_page ?? 10;
        $skip = ($page * $perPage) - $perPage;

        // ✅ 1. Retrieve Orders by Email
        if (!$request->email) {
            return $this->json('Please provide an email to retrieve orders.', [], 422);
        }

        $guestOrders = Order::where('email', $request->email)
            ->when($orderStatus, function ($query) use ($orderStatus) {
                return $query->where('order_status', $orderStatus);
            })->latest('id');

        // ✅ 2. Paginate Results
        $total = $guestOrders->count();
        $paginatedOrders = $guestOrders->skip($skip)->take($perPage)->get();

        // ✅ 3. Status-Wise Count
        $statusWiseOrders = [
            'all' => Order::where('email', $request->email)->count(),
            'pending' => Order::where('email', $request->email)->where('order_status', OrderStatus::PENDING->value)->count(),
            'confirm' => Order::where('email', $request->email)->where('order_status', OrderStatus::CONFIRM->value)->count(),
            'processing' => Order::where('email', $request->email)->where('order_status', OrderStatus::PROCESSING->value)->count(),
            'on_the_way' => Order::where('email', $request->email)->where('order_status', OrderStatus::ON_THE_WAY->value)->count(),
            'delivered' => Order::where('email', $request->email)->where('order_status', OrderStatus::DELIVERED->value)->count(),
            'cancelled' => Order::where('email', $request->email)->where('order_status', OrderStatus::CANCELLED->value)->count(),
        ];

        // ✅ 4. Return Response
        return $this->json('Guest orders retrieved successfully.', [
            'total' => $total,
            'status_wise_orders' => $statusWiseOrders,
            'orders' => OrderResource::collection($paginatedOrders),
        ]);
    }


    /**
     * Store a newly created order in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(OrderRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;

        $carts = auth()->user()->customer->carts()->whereIn('shop_id', $request->shop_ids)->where('is_buy_now', $isBuyNow)->get();

        if ($carts->isEmpty()) {
            return $this->json('Sorry shop cart is empty', [], 422);
        }

        $toUpper = strtoupper($request->payment_method);
        $paymentMethods = PaymentMethod::cases();

        $paymentMethod = $paymentMethods[array_search($toUpper, array_column(PaymentMethod::cases(), 'name'))];

        // Store the order
        $payment = OrderRepository::storeByrequestFromCart($request, $paymentMethod, $carts);
        // Check if user was referred
        $referral = Referral::where('referred_user_id', auth()->id())
            ->where('rewarded', false)
            ->first();

        if ($referral) {
            $referrerWallet = Wallet::where('user_id', $referral->referrer_id)->first();
            if ($referrerWallet) {
                // Add 10 dinars reward to the referrer’s wallet
                $referrerWallet->increment('balance', 10);
            }
            // Mark referral as rewarded
            $referral->update(['rewarded' => true]);
        }
        $paymentUrl = null;
        if ($paymentMethod->name != 'CASH') {
            $paymentUrl = route('order.payment', ['payment' => $payment, 'gateway' => $request->payment_method]);
        }

        // $message = "New order placed!";
        // try {
        //     ProductApproveEvent::dispatch($message);
        // } catch (\Throwable $th) {}

        // $data = (object) [
        //     'title' => $message,
        //     'content' => 'Your product approved from admin',
        //     'url' => '/shop/orders/90/show',
        //     'icon' => 'bi-bag-check-fill',
        //     'type' => 'success',
        //     'shop_id' => $product->shop_id,
        // ];

        // store notification
        // NotificationRepository::storeByRequest($data);

        return $this->json('Order created successfully', [
            'order_payment_url' => $paymentUrl,
        ]);
    }
    private function rewardReferrer($referralCode, $customerId = null)
    {
        // Find the referrer using the referral code
        $referrer = User::where('referral_code', $referralCode)->first();

        if (!$referrer) {
            return response()->json(['message' => 'Referrer not found'], 404);
        }

        // Get or create the referrer's wallet
        $wallet = Wallet::firstOrCreate(['user_id' => $referrer->id], ['balance' => 0]);

        // Reward the referrer (e.g., 10 dinars)
        $wallet->increment('balance', 10);

        return response()->json(['message' => 'Referral reward given to referrer']);
    }
    public function storeGuest(OrderRequest $request)
    {
        $isBuyNow = $request->is_buy_now ?? false;

        // ✅ Retrieve cart items from the request
        $cartItems = collect($request->input('cart_items', []));

        // ✅ Filter by shop IDs
        $filteredCarts = $cartItems->whereIn('shop_id', $request->shop_ids)->values();

        if ($filteredCarts->isEmpty()) {
            return $this->json('Sorry, shop cart is empty', [], 422);
        }

        // ✅ Validate Payment Method
        $toUpper = strtoupper($request->payment_method);
        $paymentMethods = PaymentMethod::cases();
        $paymentMethod = collect($paymentMethods)->firstWhere('name', $toUpper);

        if (!$paymentMethod) {
            return $this->json('Invalid payment method', [], 422);
        }

        // ✅ Create Order for Guest User
        $payment = OrderRepository::storeByRequestFromGuestCart(
            $request,
            $paymentMethod,
            $filteredCarts
        );


        // ✅ Generate Payment URL if needed
        $paymentUrl = null;
        if ($paymentMethod->name !== 'CASH') {
            $paymentUrl = route('order.payment', [
                'payment' => $payment,
                'gateway' => $request->payment_method,
            ]);
        }

        // ✅ Return Response
        return $this->json('Order created successfully', [
            'message' => 'Order placed successfully. Create an account to track your orders and earn rewards.',
            'order_payment_url' => $paymentUrl,
        ]);
    }

    /**
     * Again order
     */
    public function reOrder(Request $request)
    {
        // Validate the request
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        // Find the order
        $order = Order::find($request->order_id);

        if ($order->order_status->value == OrderStatus::DELIVERED->value) {

            // Check product quantity
            foreach ($order->products as $product) {
                if ($product->quantity < $product->pivot->quantity) {
                    return $this->json('Sorry, your product quantity out of stock', [], 422);
                }
            }

            // create payment
            $paymentMethod = $order->payments()?->latest('id')->first()->payment_method ?? 'cash';
            $payment = Payment::create([
                'amount' => $order->payable_amount,
                'payment_method' => $paymentMethod,
            ]);

            // re-order
            $order = OrderRepository::reOrder($order);

            // attach payment to order
            $payment->orders()->attach($order->id);

            // payment url
            $paymentUrl = null;
            if ($paymentMethod != 'cash') {
                $paymentUrl = route('order.payment', ['payment' => $payment, 'gateway' => $payment->payment_method]);
            }

            // return
            return $this->json('Re-order created successfully', [
                'order_payment_url' => $paymentUrl,
                'order' => OrderResource::make($order),
            ]);
        }

        return $this->json('Sorry, You can not  re-order because order is not delivered', [], 422);
    }

    /**
     * Show the order details.
     *
     * @param Request $request The request object
     */
    public function show(Request $request)
    {
        // Validate the request
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        // Find the order
        $order = Order::find($request->order_id);

        return $this->json('order details', [
            'order' => OrderDetailsResource::make($order),
        ]);
    }
    public function showGuest(Request $request)
    {
        // ✅ 1. Validate Input
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'email' => 'required|email',
        ]);

        $orderId = $request->order_id;

        // ✅ 2. Search Order by `email`
        $order = Order::where('id', $orderId)
            ->where('email', $request->email)
            ->first();

        // ✅ 3. Return Error if Not Found
        if (!$order) {
            return $this->json('Order not found for this email.', [], 404);
        }

        // ✅ 4. Return Order Details
        return $this->json('Guest order details retrieved successfully.', [
            'order' => OrderDetailsResource::make($order),
        ]);
    }


    /**
     * Cancel the order.
     */
    public function cancel(Request $request)
    {
        // Validate the request
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        // Find the order
        $order = Order::find($request->order_id);

        if ($order->order_status->value == OrderStatus::PENDING->value) {

            // update order status
            $order->update([
                'order_status' => OrderStatus::CANCELLED->value,
            ]);

            foreach ($order->products as $product) {
                $qty = $product->pivot->quantity;

                $product->update(['quantity' => $product->quantity + $qty]);

                $flashsale = $product->flashSales?->first();
                $flashsaleProduct = null;

                if ($flashsale) {
                    $flashsaleProduct = $flashsale?->products()->where('id', $product->id)->first();

                    if ($flashsaleProduct && $product->pivot?->price) {
                        if ($flashsaleProduct->pivot->sale_quantity >= $qty && ($product->pivot?->price == $flashsaleProduct->pivot->price)) {
                            $flashsale->products()->updateExistingPivot($product->id, [
                                'sale_quantity' => $flashsaleProduct->pivot->sale_quantity - $qty,
                            ]);
                        }
                    }
                }
            }

            return $this->json('Order cancelled successfully', [
                'order' => OrderResource::make($order),
            ]);
        }

        return $this->json('Sorry, order cannot be cancelled because it is not pending', [], 422);
    }

    public function cancelGuest(Request $request)
    {
        // ✅ 1. Validate the Request
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'email' => 'required|email',
        ]);

        $orderId = $request->order_id;

        // ✅ 2. Retrieve the Order from Database using `email`
        $order = Order::where('id', $orderId)
            ->where('email', $request->email)
            ->first();

        // ✅ 3. Return Error if Order Not Found
        if (!$order) {
            return $this->json('Order not found for this email.', [], 404);
        }


        if ($order->order_status->value == OrderStatus::PENDING->value) {

            // update order status
            $order->update([
                'order_status' => OrderStatus::CANCELLED->value,
            ]);

            foreach ($order->products as $product) {
                $qty = $product->pivot->quantity;

                $product->update(['quantity' => $product->quantity + $qty]);

                $flashsale = $product->flashSales?->first();
                $flashsaleProduct = null;

                if ($flashsale) {
                    $flashsaleProduct = $flashsale?->products()->where('id', $product->id)->first();

                    if ($flashsaleProduct && $product->pivot?->price) {
                        if ($flashsaleProduct->pivot->sale_quantity >= $qty && ($product->pivot?->price == $flashsaleProduct->pivot->price)) {
                            $flashsale->products()->updateExistingPivot($product->id, [
                                'sale_quantity' => $flashsaleProduct->pivot->sale_quantity - $qty,
                            ]);
                        }
                    }
                }
            }

            return $this->json('Order cancelled successfully', [
                'order' => OrderResource::make($order),
            ]);
        }

        return $this->json('Sorry, order cannot be cancelled because it is not pending', [], 422);

    }

    public function payment(Order $order, $paymentMethod = null)
    {
        if ($paymentMethod != 'cash' && $paymentMethod != null) {

            $payment = Payment::create([
                'amount' => $order->payable_amount,
                'payment_method' => $paymentMethod,
            ]);

            $payment->orders()->attach($order->id);

            $paymentUrl = route('order.payment', ['payment' => $payment, 'gateway' => $payment->payment_method]);

            return $this->json('Payment created', [
                'order_payment_url' => $paymentUrl,
            ]);

            // $payment = $order->payments()?->first();

            // if ($payment->payment_method != $paymentMethod) {

            //     $order->update([
            //         'payment_method' => $paymentMethod,
            //     ]);

            //     $orders = $payment->orders()->where('order_status', '!=', OrderStatus::CANCELLED->value)->where('payment_status', PaymentStatus::PENDING->value)->get();

            //     $payment->update([
            //         'payment_method' => $paymentMethod,
            //         'amount' => $orders->sum('payable_amount'),
            //     ]);

            //     $payment->orders()->sync($orders);

            //     $paymentUrl = route('order.payment', ['payment' => $payment, 'gateway' => $payment->payment_method]);

            //     return $this->json('Payment created', [
            //         'order_payment_url' => $paymentUrl,
            //         'order' => OrderResource::make($order),
            //     ]);
            // }

            // $payment = Payment::create([
            //     'amount' => $order->payable_amount,
            //     'payment_method' => $paymentMethod,
            // ]);
        }

        return $this->json('Sorry, You can not  re-payment because payment is CASH', [], 422);
    }

    public function paymentGuest(Request $request)
    {
        // ✅ 1. Validate Input
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'email' => 'required|email',
            'payment_method' => 'required|string',
        ]);

        $paymentMethod = strtolower($request->payment_method);
        $orderId = $request->order_id;

        // ✅ 2. Retrieve Order by `email` and `order_id`
        $order = Order::where('id', $orderId)
            ->where('email', $request->email)
            ->first();

        // ✅ 3. Return Error if Order Not Found
        if (!$order) {
            return $this->json('Order not found for this email.', [], 404);
        }

        // ✅ 4. Prevent Re-payment if Payment Method is CASH
        if ($paymentMethod === 'cash') {
            return $this->json('Sorry, you cannot re-pay because payment is CASH.', [], 422);
        }

        // ✅ 5. Create a Payment Record
        $payment = Payment::create([
            'amount' => $order->payable_amount,
            'payment_method' => $paymentMethod,
        ]);

        // ✅ 6. Link Payment to Order
        $payment->orders()->attach($order->id);

        //  Generate Payment URL
        $paymentUrl = route('order.payment', [
            'payment' => $payment->id,
            'gateway' => $payment->payment_method,
        ]);

        // Return Payment URL
        return $this->json('Payment created successfully.', [
            'order_payment_url' => $paymentUrl,
        ]);
    }

}


