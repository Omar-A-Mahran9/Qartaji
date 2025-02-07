@extends('layouts.app')

@section('header-title', __('Verification OTP Settings'))

@section('content')
    <div class="page-title">
        <div class="d-flex gap-2 align-items-center">
            <i class="fa-solid fa-unlock"></i> {{ __('Verification OTP Settings') }}
        </div>
    </div>

    <div class="row">
        <div class="col-xl-9">
            <form action="{{ route('admin.verification.update') }}" method="POST">
                @csrf

                <!--######## Basic Information ##########-->
                <div class="card mt-4">
                    <div class="card-body">

                        <div class="d-flex align-items-center gap-2 border-bottom pb-2">
                            <i class="bi bi-briefcase-fill"></i>
                            <h5 class="mb-0">{{ __('Registration') }}</h5>
                        </div>

                        <div class="border rounded p-2 d-flex align-items-center justify-content-between gap-2 flex-wrap mt-3"
                            style="max-width: 400px">
                            <span>
                                {{ __('Customer Registration OTP Verify') }}
                            </span>
                            <label class="switch mb-0" data-bs-toggle="tooltip" data-bs-placement="left"
                                data-bs-title="Show/Hide">
                                <input id="toggle" type="checkbox" {{ $verifyManage?->register_otp ? 'checked' : '' }}
                                    name="register_otp">
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="row">
                            <div class="col-lg-4 mt-4">
                                <label class="form-label">
                                    {{ __('Register OTP Send Method') }}
                                </label>
                                <div class="d-flex flex-wrap align-items-center gap-5 border rounded fw-medium"
                                    style="padding: 10px;">
                                    <div class="flex-grow-1 d-flex align-items-center gap-1">
                                        <input type="radio" name="register_otp_type" value="phone"
                                            class="form-check-input m-0" id="single"
                                            {{ $verifyManage?->register_otp_type == 'phone' ? 'checked' : '' }}>
                                        <label for="single" class="m-0 cursor-pointer">
                                            {{ __('Phone') }}
                                        </label>
                                    </div>

                                    <div class="flex-grow-1 d-flex align-items-center gap-1">
                                        <input type="radio" name="register_otp_type" value="email"
                                            class="form-check-input m-0" id="emailRegisterOTP"
                                            {{ $verifyManage?->register_otp_type == 'email' ? 'checked' : '' }}>
                                        <label for="emailRegisterOTP" class="m-0 cursor-pointer">
                                            {{ __('Email') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 mt-4">
                                <label class="form-label">
                                    {{ __('Forget Password OTP Send Method') }}
                                </label>
                                <div class="d-flex flex-wrap align-items-center gap-5 border rounded fw-medium"
                                    style="padding: 10px;">
                                    <div class="flex-grow-1 d-flex align-items-center gap-1">
                                        <input type="radio" name="forgot_otp_type" value="phone"
                                            class="form-check-input m-0" id="forgetOTPPhone"
                                            {{ $verifyManage?->forgot_otp_type == 'phone' ? 'checked' : '' }}>
                                        <label for="forgetOTPPhone" class="m-0 cursor-pointer">
                                            {{ __('Phone') }}
                                        </label>
                                    </div>

                                    <div class="flex-grow-1 d-flex align-items-center gap-1">
                                        <input type="radio" name="forgot_otp_type" value="email"
                                            class="form-check-input m-0" id="forgetOTPEmail"
                                            {{ $verifyManage?->forgot_otp_type == 'email' ? 'checked' : '' }}>
                                        <label for="forgetOTPEmail" class="m-0 cursor-pointer">
                                            {{ __('Email') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @hasPermission('admin.verification.update')
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary py-2 px-3">
                                    {{ __('Save And Update') }}
                                </button>
                            </div>
                        @endhasPermission

                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
