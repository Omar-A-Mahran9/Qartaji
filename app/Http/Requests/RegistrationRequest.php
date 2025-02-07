<?php

namespace App\Http\Requests;

use App\Models\VerifyManage;
use App\Rules\EmailRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

class RegistrationRequest extends FormRequest
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
        $verifyManage = Cache::rememberForever('verify_manage', function () {
            return VerifyManage::first();
        });

        $emailRequired = 'nullable';

        if ($verifyManage?->register_otp && ($verifyManage->register_otp_type == 'email' || $verifyManage->forgot_otp_type == 'email')) {
            $emailRequired = 'required';
        }

        return [
            'name' => 'required|string|max:200',
            'phone' => 'required|unique:users,phone',
            'email' => [$emailRequired, 'email', new EmailRule, 'unique:users,email'],
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        $request = request();
        if ($request->is('api/*')) {
            $lan = $request->header('accept-language') ?? 'en';
            app()->setLocale($lan);
        }

        return [
            'name.required' => __('The name field is required.'),
            'phone.required' => __('The phone field is required.'),
            'phone.unique' => __('The phone has already been taken.'),
            'password.required' => __('The password field is required.'),
            'password.min' => __('The password must be at least 6 characters.'),
            'email.required' => __('The email field is required.'),
            'email.email' => __('The email must be a valid email address.'),
            'email.unique' => __('The email has already been taken.'),
        ];
    }
}
