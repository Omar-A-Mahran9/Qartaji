@extends('layouts.app')
@section('header-title', __('Order Details'))

@section('content')

    <div class="row my-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 py-3">
                    <h4 class="card-title mb-0">{{ __('Order Details') }}</h4>
                    <a href="{{ route('shop.download-invoice', $order->id) }}" class="btn btn-primary py-2.5">
                        <img src="{{ asset('assets/icons-admin/download-alt.svg') }}" alt="icon" loading="lazy"
                            width="20" />
                        {{ __('Download Invoice') }}
                    </a>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-3 flex-wrap align-items-center">
                        <div class="flex-grow-1">
                            <div class="order-item">
                                <label class="label">{{ __('Order Id') }}:</label>
                                <span class="value">#{{ $order->prefix . $order->order_code }}</span>
                            </div>
                            <div class="order-item">
                                <label class="label">{{ __('Payment Status') }}:</label>
                                <span class="value">{{ $order->payment_status }}</span>
                            </div>
                            <div class="order-item">
                                <label class="label">{{ __('Payment Method') }}:</label>
                                <span class="value">{{ $order->payment_method }}</span>
                            </div>
                        </div>

                        <div class="item-divider"></div>

                        <div class="flex-grow-1">
                            <div class="order-item">
                                <label class="label">{{ __('Order Status') }}:</label>
                                <span class="value">{{ $order->order_status }}</span>
                            </div>
                            <div class="order-item">
                                <label class="label">{{ __('Order Date') }}:</label>
                                <span class="value">{{ $order->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="order-item">
                                <label class="label">{{ __('Delivery Date') }}:</label>
                                <span
                                    class="value">
                                    {{ $order->delivery_date ? Carbon\Carbon::parse($order->delivery_date)->format('M d, Y') : 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mt-4 mb-0">
                        <table class="table border-left-right">
                            <thead>
                                <tr>
                                    <th>{{ __('SL') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    @if ($businessModel == 'multi')
                                        <th>{{ __('Shop') }}</th>
                                    @endif
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Size') }}</th>
                                    <th>{{ __('Color') }}</th>
                                    <th>{{ __('Price') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->products as $key => $product)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            <div class="d-flex gap-1 align-items-center">
                                                <img src="{{ $product->thumbnail }}" alt="" width="40"
                                                    height="40" loading="lazy">
                                                <span>{{ $product->name }}</span>
                                            </div>
                                        </td>
                                        @if ($businessModel == 'multi')
                                            <td>{{ $product->shop?->name }}</td>
                                        @endif
                                        <td>{{ $product->pivot->quantity }}</td>
                                        <td>{{ $product->pivot->size ?? 'N/A' }}</td>
                                        <td>{{ $product->pivot->color ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $price =
                                                    $product->pivot->price > 0
                                                        ? $product->pivot->price
                                                        : ($product->discount_price > 0
                                                            ? $product->discount_price
                                                            : $product->price);
                                            @endphp
                                            {{ showCurrency($price) }}
                                        </td>
                                        <td class="text-end">
                                            {{ showCurrency($product->pivot->quantity * $price) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="max-300 ms-auto d-flex flex-column gap-1">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>{{ __('Sub Total') }}</div>
                            <div>{{ showCurrency($order->total_amount) }}</div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>{{ __('Coupon Discount') }}</div>
                            <div>{{ showCurrency($order->coupon_discount) }}</div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>{{ __('Delivery Charge') }}</div>
                            <div>{{ showCurrency($order->delivery_charge) }}</div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>{{ __('VAT & Tax') }}</div>
                            <div>{{ showCurrency($order->tax_amount) }}</div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-2 border-top pt-1 mt-1">
                            <div class="fw-bold">{{ __('Grand Total') }}</div>
                            <div class="fw-bold">{{ showCurrency($order->payable_amount) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!--##### Customer Info #####-->
            <div class="mt-3 card">
                <h5 class="fz-16 border-bottom px-3 py-12 m-0">{{ __('Customer Info') }}</h5>

                <div class="border-bottom px-3 py-2 d-flex  align-items-center gap-3">
                    <span class="text-color">{{ __('Name') }}: </span>
                    <span class="fw-medium">{{ $order->customer?->user?->name }}</span>
                </div>
                <div class="px-3 py-2 d-flex  align-items-center gap-3">
                    <span class="text-color">{{ __('Phone') }}: </span>
                    <span class="fw-medium">{{ $order->customer?->user?->phone }}</span>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <!--##### Order & Shipping Info #####-->
            <div class="card">
                <h5 class="fz-18 border-bottom p-3 m-0">{{ __('Order & Shipping Info') }}</h5>

                <div class="px-3 py-12 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                    <div class="text-color">{{ __('Change Order Status') }}</div>
                    <div class="dropdown">
                        <a class="btn border text-start dropdown-toggle" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $order->order_status->value }}
                        </a>

                        @hasPermission(['shop.order.status.change'])
                            <ul class="dropdown-menu order-status">
                                @foreach ($orderStatus as $satus)
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('shop.order.status.change', $order->id) }}?status={{ $satus->value }}">
                                            {{ __($satus->value) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endhasPermission
                    </div>
                </div>

                <div class="border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">
                    <div class="text-color">{{ __('Payment Status') }}</div>
                    <div class="d-flex align-items-center gap-1">
                        <span>{{ $order->payment_status }}</span>
                        @hasPermission('shop.order.payment.status.toggle')
                            <label class="switch mb-0">
                                <a href="{{ route('shop.order.payment.status.toggle', $order->id) }}">
                                    <input type="checkbox" {{ $order->payment_status->value == 'Paid' ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </a>
                            </label>
                        @endhasPermission
                    </div>
                </div>
            </div>

            <!--##### Shipping Address #####-->
            <div class="card mt-3">
                <h5 class="fz-18 border-bottom p-3 m-0">{{ __('Shipping Address') }}</h5>

                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Name') }}: </span>
                    <span class="fw-medium">{{ $order->address?->name }}</span>
                </div>
                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Phone') }}: </span>
                    <span class="fw-medium">{{ $order->address?->phone }}</span>
                </div>
                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Address Type') }}: </span>
                    <span class="fw-medium">{{ $order->address?->address_type }}</span>
                </div>
                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Area') }}: </span>
                    <span class="fw-medium">{{ $order->address?->area }}</span>
                </div>
                <div class="d-flex gap-2 border-bottom align-items-center justify-content-between flex-wrap px-3 py-12">
                    <div>
                        <span class="text-color">{{ __('Road No') }}: </span>
                        <span class="fw-medium">{{ $order->address?->road_no }}</span>,
                    </div>
                    <div>
                        <span class="text-color">{{ __('Flat No') }}: </span>
                        <span class="fw-medium">{{ $order->address?->flat_no }}</span>,
                    </div>
                    <div>
                        <span class="text-color">{{ __('House No') }}: </span>
                        <span class="fw-medium">{{ $order->address?->house_no }}</span>
                    </div>
                </div>

                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Post Code') }}: </span>
                    <span class="fw-medium">{{ $order->address?->post_code }}</span>
                </div>
                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Address Line') }}: </span>
                    <span class="fw-medium">{{ $order->address?->address_line }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Address Line') }} 2: </span>
                    <span class="fw-medium">{{ $order->address?->address_line2 }}</span>
                </div>
            </div>

        </div>
    </div>

@endsection
@push('css')
    <style>
        .dropdown-menu.order-status {
            min-width: 200px;
            padding: 8px;
            border: 1px solid #e5e5e5;
            box-shadow: 0 0 10px #e5e5e5;
        }

        .dropdown-menu.order-status .dropdown-item {
            border-bottom: 1px solid #f1f1f1;
        }

        .app-theme-dark .dropdown-menu.order-status {
            border: 1px solid #343a40;
            box-shadow: 0 0 10px #343a40;
        }
        .app-theme-dark .dropdown-menu.order-status .dropdown-item {
            border-bottom: 1px solid #343a40;
        }

        .max-300 {
            max-width: 340px;
        }

        .min-w-200 {
            min-width: 200px;
            display: inline;
        }

        .item-divider {
            height: 80px;
            width: 1px;
            background: #e5e5e5;
            margin: 0 20px;
        }

        .app-theme-dark .item-divider {
            background: #343a40;
        }

        .order-item {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .order-item:last-child {
            margin-bottom: 0;
        }

        .order-item .label {
            color: #687387;
            line-height: 22px;
        }

        .app-theme-dark .order-item .label {
            color: #8f96a6;
        }

        .order-item .value {
            line-height: 22px;
            font-weight: 500;
            color: #000;
        }

        .app-theme-dark .order-item .value {
            color: #fff;
        }

        @media (max-width: 768px) {
            .item-divider {
                display: none;
            }
        }
    </style>
@endpush
