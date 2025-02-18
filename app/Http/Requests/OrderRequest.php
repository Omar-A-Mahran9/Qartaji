<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isGuest = !auth()->check(); // ✅ True if user is NOT logged in

        return [
            // 🛒 Guest Cart Items
            'cart_items' => $isGuest ? 'required|array' : 'nullable|array',
            'cart_items.*.product_id' => $isGuest ? 'required|exists:products,id' : 'nullable|exists:products,id',
            'cart_items.*.quantity' => $isGuest ? 'required|integer|min:1' : 'nullable|integer|min:1',
            'cart_items.*.shop_id' => $isGuest ? 'required|exists:shops,id' : 'nullable|exists:shops,id',
            'cart_items.*.price' => 'nullable|numeric|min:0',
            'cart_items.*.is_gift' => 'nullable|boolean',

            // 📧 Guest Information (Only Required for Guests)
            'email' => $isGuest ? 'required|email' : 'nullable|email',
            'phone' => $isGuest ? 'required|string|max:15' : 'nullable|string|max:15',

            // 🏪 Common Order Fields
            'shop_ids' => 'required|array',
            'shop_ids.*' => 'required|exists:shops,id',
            'address_id' => 'required|exists:addresses,id',

            // 💳 Payment & Coupons
            'note' => 'nullable|string',
            'payment_method' => 'required|string',
            'coupon_code' => 'nullable|string|max:50',
        ];
    }


    public function messages(): array
    {
        $request = request();
        if ($request->is('api/*')) {
            $lan ='en';
            app()->setLocale($lan);
        }

        return [
            // Guest Information
            'email.required' => __('The email field is required for guests.'),
            'phone.required' => __('The phone field is required for guests.'),

            // Cart Items
            'cart_items.required' => __('The cart items are required for guests.'),
            'cart_items.*.product_id.required' => __('The product ID is required.'),
            'cart_items.*.quantity.required' => __('The quantity is required.'),
            'cart_items.*.shop_id.required' => __('The shop ID is required.'),

            // Common Fields
            'shop_ids.required' => __('The shop field is required.'),
            'address_id.required' => __('The address field is required.'),
            'payment_method.required' => __('The payment method field is required.'),
        ];
    }
}
