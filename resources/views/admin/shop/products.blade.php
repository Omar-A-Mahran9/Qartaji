@extends('layouts.app')

@section('header-title', __('Shop Products'))

@section('content')
    <div class="container-fluid">

        <div class="card">
            <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
                <h4 class="m-0">{{ __('Shop Details') }}</h4>
            </div>
            <div class="card-body">
                @include('admin.shop.header-nav')
            </div>
        </div>

        <div class="my-3 card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-lg border-left-right">
                        <thead>
                            <tr>
                                <th class="text-center">{{ __('SL') }}</th>
                                <th>{{ __('Thumbnail') }}</th>
                                <th>{{ __('Product Name') }}</th>
                                <th class="text-center">{{ __('Price') }}</th>
                                <th class="text-center">{{ __('Discount Price') }}</th>
                                <th class="text-center">{{ __('Verify Status') }}</th>
                                @hasPermission('admin.product.show')
                                    <th>{{ __('Action') }}</th>
                                @endhasPermission
                            </tr>
                        </thead>
                        @forelse ($products as $key => $product)
                            <tr>
                                <td class="text-center">{{ ++$key }}</td>
                                <td>
                                    <img src="{{ $product->thumbnail }}" width="50">
                                </td>

                                <td>{{ $product->name }}</td>

                                <td class="text-center">
                                    {{ showCurrency($product->price) }}
                                </td>

                                <td class="text-center">
                                    {{ showCurrency($product->discount_price) }}
                                </td>

                                <td class="text-center" style="min-width: 110px">
                                    @if ($product->is_approve)
                                        <span class="status-approved">
                                            <i class="fa fa-check-circle text-success"></i> {{ __('Approved') }}
                                        </span>
                                    @else
                                        <span class="status-pending">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            {{ __('Pending') }}
                                        </span>
                                    @endif
                                </td>
                                @hasPermission('admin.product.show')
                                    <td class="text-center" style="width: 80px">
                                        <a href="{{ route('admin.product.show', $product->id) }}"
                                            class="svg-bg circleIcon" data-bs-toggle="tooltip"
                                            data-bs-placement="left" data-bs-title="{{ __('Product Details') }}">
                                            <img src="{{ asset('assets/icons-admin/eye.svg') }}" alt="view" loading="lazy" />
                                        </a>
                                    </td>
                                @endhasPermission
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="text-center">{{ __('No Data Found') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="my-3">
            {{ $products->links() }}
        </div>

    </div>
@endsection
