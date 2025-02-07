<!--- Dashboard --->
<li>
    <a class="menu {{ $request->routeIs('admin.dashboard.*') ? 'active' : '' }}"
        href="{{ route('admin.dashboard.index') }}">
        <span>
            <i class="fa-solid fa-house menu-icon"></i>
            {{ __('Dashboard') }}
        </span>
    </a>
</li>

@hasPermission(['admin.banner.index', 'admin.ad.index', 'shop.voucher.index', 'admin.customerNotification.index'])
    <li class="menu-divider">
        <span class="menu-title">{{ __('Marketing Promotions') }}</span>
    </li>
@endhasPermission

@hasPermission('admin.banner.index')
    <!--- banner--->
    <li>
        <a class="menu {{ $request->routeIs('admin.banner.*') ? 'active' : '' }}" href="{{ route('admin.banner.index') }}">
            <span>
                <i class="fa-solid fa-image menu-icon"></i>
                {{ __('Banner') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission('admin.ad.index')
    <!--- ads--->
    <li>
        <a class="menu {{ $request->routeIs('admin.ad.*') ? 'active' : '' }}" href="{{ route('admin.ad.index') }}">
            <span>
                <i class="fa-solid fa-photo-film menu-icon"></i>
                {{ __('Ads') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission('shop.voucher.index')
    <!--- Coupon discount--->
    <li>
        <a class="menu {{ $request->routeIs('shop.voucher.*') ? 'active' : '' }}"
            href="{{ route('shop.voucher.index') }}">
            <span>
                <i class="fa-solid fa-ticket menu-icon"></i>
                {{ __('Promo Code') }}
            </span>
        </a>
    </li>
@endhasPermission

@php
    use App\Enums\OrderStatus;
    $orderStatuses = OrderStatus::cases();
@endphp

@hasPermission('admin.customerNotification.index')
    <!--- notification--->
    <li>
        <a class="menu {{ $request->routeIs('admin.customerNotification.*') ? 'active' : '' }}"
            href="{{ route('admin.customerNotification.index') }}">
            <span>
                <i class="fa-solid fa-bell menu-icon"></i>
                {{ __('Notifications') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission(['admin.order.index', 'admin.review.index'])
    <li class="menu-divider">
        <span class="menu-title">{{ __('Order Handling') }}</span>
    </li>
@endhasPermission

@hasPermission(['admin.order.index'])
    <!--- Orders --->
    <li>
        <a class="menu {{ request()->routeIs('admin.order.*') ? 'active' : '' }}" data-bs-toggle="collapse"
            href="#ordersMenu">
            <span>
                <i class="fa-solid fa-cart-shopping menu-icon"></i>
                {{ __('All Orders') }}
            </span>
            <img src="{{ asset('assets/icons/arrowDown.svg') }}" alt="" class="downIcon">
        </a>
        <div class="collapse dropdownMenuCollapse {{ $request->routeIs('admin.order.*') ? 'show' : '' }}" id="ordersMenu">
            <div class="listBar">
                <a href="{{ route('admin.order.index') }}"
                    class="subMenu hasCount {{ request()->url() === route('admin.order.index') ? 'active' : '' }}">
                    {{ __('All') }} <span class="count statusAll">{{ $allOrders > 99 ? '99+' : $allOrders }}</span>
                </a>
                @foreach ($orderStatuses as $status)
                    <a href="{{ route('admin.order.index', str_replace(' ', '_', $status->value)) }}"
                        class="subMenu hasCount {{ request()->url() === route('admin.order.index', str_replace(' ', '_', $status->value)) ? 'active' : '' }}">
                        <span>{{ __($status->value) }}</span>
                        <span
                            class="count status{{ Str::camel($status->value) }}">{{ ${Str::camel($status->value)} > 99 ? '99+' : ${Str::camel($status->value)} }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </li>
@endhasPermission

@hasPermission('admin.review.index')
    <!--- reviews --->
    <li>
        <a class="menu {{ $request->routeIs('admin.review.*') ? 'active' : '' }}"
            href="{{ route('admin.review.index') }}">
            <span>
                <i class="fa-regular fa-star-half-stroke menu-icon"></i>
                {{ __('Reviews') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission(['shop.pos.index', 'shop.pos.draft', 'shop.pos.sales'])
    <li class="menu-divider">
        <span class="menu-title">{{ __('POS Management') }}</span>
    </li>
@endhasPermission

@hasPermission('shop.pos.index')
    <!--- POS--->
    <li>
        <a class="menu {{ $request->routeIs('shop.pos.index') ? 'active' : '' }}" href="{{ route('shop.pos.index') }}">
            <span>
                <i class="fa-solid fa-store menu-icon"></i>
                {{ __('POS') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission('shop.pos.draft')
    <!--- Draft --->
    <li>
        <a class="menu {{ $request->routeIs('shop.pos.draft') ? 'active' : '' }}" href="{{ route('shop.pos.draft') }}">
            <span>
                <i class="fa-brands fa-firstdraft menu-icon"></i>
                {{ __('Draft') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission('shop.pos.sales')
    <!--- POS Sales--->
    <li>
        <a class="menu {{ $request->routeIs('shop.pos.sales') ? 'active' : '' }}" href="{{ route('shop.pos.sales') }}">
            <span>
                <i class="fa-solid fa-cart-shopping menu-icon"></i>
                {{ __('POS Sales') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission(['shop.brand.index', 'shop.color.index', 'shop.size.index', 'shop.unit.index'])
    <li class="menu-divider">
        <span class="menu-title">{{ __('Product Variants') }}</span>
    </li>
@endhasPermission

@hasPermission('shop.brand.index')
    <!--- brand --->
    <li>
        <a class="menu {{ $request->routeIs('shop.brand.*') ? 'active' : '' }}" href="{{ route('shop.brand.index') }}">
            <span>
                <i class="fa-solid fa-star menu-icon"></i>
                {{ __('Brand') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission('shop.color.index')
    <!--- color--->
    <li>
        <a class="menu {{ $request->routeIs('shop.color.*') ? 'active' : '' }}" href="{{ route('shop.color.index') }}">
            <span>
                <i class="fa-solid fa-palette menu-icon"></i>
                {{ __('Color') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission('shop.size.index')
    <!--- size--->
    <li>
        <a class="menu {{ $request->routeIs('shop.size.*') ? 'active' : '' }}" href="{{ route('shop.size.index') }}">
            <span>
                <i class="fa-solid fa-list-ol menu-icon"></i>
                {{ __('Sizes') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission('shop.unit.index')
    <!--- unit--->
    <li>
        <a class="menu {{ $request->routeIs('shop.unit.*') ? 'active' : '' }}" href="{{ route('shop.unit.index') }}">
            <span>
                <i class="fa-brands fa-unity menu-icon"></i>
                {{ __('Unit') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission(['shop.category.index', 'shop.subcategory.index', 'shop.product.index', 'shop.gift.index'])
    <li class="menu-divider">
        <span class="menu-title">{{ __('Product Management') }}</span>
    </li>
@endhasPermission

@hasPermission(['shop.category.index', 'shop.subcategory.index'])
    <!--- categories--->
    <li>
        <a class="menu {{ request()->routeIs('shop.category.*', 'shop.subcategory.*') ? 'active' : '' }}"
            data-bs-toggle="collapse" href="#categoryMenu">
            <span>
                <i class="fa-solid fa-border-all menu-icon"></i>
                {{ __('Categories') }}
            </span>
            <img src="{{ asset('assets/icons/arrowDown.svg') }}" alt="" class="downIcon">
        </a>
        <div class="collapse dropdownMenuCollapse {{ $request->routeIs('shop.category.*', 'shop.subcategory.*') ? 'show' : '' }}"
            id="categoryMenu">
            <div class="listBar">
                @hasPermission('shop.category.index')
                    <a href="{{ route('shop.category.index') }}"
                        class="subMenu hasCount {{ request()->routeIs('shop.category.*') ? 'active' : '' }}">
                        {{ __('Category') }}
                    </a>
                @endhasPermission
                @hasPermission('shop.subcategory.index')
                    <!--- sub categories--->
                    <a href="{{ route('shop.subcategory.index') }}"
                        class="subMenu hasCount {{ request()->routeIs('shop.subcategory.*') ? 'active' : '' }}">
                        {{ __('Sub Category') }}
                    </a>
                @endhasPermission
            </div>
        </div>
    </li>
@endhasPermission

@hasPermission('shop.product.index')
    <!--- Products--->
    <li>
        <a class="menu {{ $request->routeIs('shop.product.*') ? 'active' : '' }}"
            href="{{ route('shop.product.index') }}">
            <span>
                <i class="fa-brands fa-codepen menu-icon"></i>
                {{ __('Products') }}
            </span>
        </a>
    </li>
@endhasPermission

{{-- @hasPermission('shop.gift.index')
    <!--- gift--->
    <li>
        <a class="menu {{ $request->routeIs('shop.gift.*') ? 'active' : '' }}" href="{{ route('shop.gift.index') }}">
            <span>
                <i class="fa-solid fa-gift menu-icon"></i>
                {{ __('Gift') }}
            </span>
        </a>
    </li>
@endhasPermission --}}

@hasPermission(['admin.user.index', 'admin.rider.index','admin.customer.index','admin.employee.index','admin.role.index'])
    <li class="menu-divider">
        <span class="menu-title">{{ __('User Supervision') }}</span>
    </li>
@endhasPermission

@hasPermission('admin.user.index')
<!--- rider --->
<li>
    <a class="menu {{ $request->routeIs('admin.rider.*') ? 'active' : '' }}" href="{{ route('admin.rider.index') }}">
        <span>
            <i class="bi bi-bicycle menu-icon"></i>
            {{ __('Riders') }}
        </span>
    </a>
</li>
@endhasPermission

@hasPermission('admin.customer.index')
<!--- customers --->
<li>
    <a class="menu {{ $request->routeIs('admin.customer.*') ? 'active' : '' }}"
        href="{{ route('admin.customer.index') }}">
        <span>
            <i class="fa-solid fa-user menu-icon"></i>
            {{ __('Customers') }}
        </span>
    </a>
</li>
@endhasPermission

@hasPermission('admin.employee.index')
<!--- employee --->
<li>
    <a class="menu {{ $request->routeIs('admin.employee.*') ? 'active' : '' }}"
        href="{{ route('admin.employee.index') }}">
        <span>
            <i class="fa-solid fa-users-gear menu-icon"></i>
            {{ __('Employees') }}
        </span>
    </a>
</li>
@endhasPermission

@hasPermission('admin.role.index')
<!--- roles and permissions --->
<li>
    <a class="menu {{ $request->routeIs('admin.role.*') ? 'active' : '' }}" href="{{ route('admin.role.index') }}">
        <span>
            <i class="fa-solid fa-key menu-icon"></i>
            {{ __('Roles & Permissions') }}
        </span>
    </a>
</li>
@endhasPermission

@hasPermission(['admin.generale-setting.index','admin.business-setting.index','admin.socialLink.index','admin.themeColor.index', 'admin.deliveryCharge.index','admin.ticketissuetype.index','admin.legalpage.index', 'admin.contactUs.index','admin.pusher.index', 'admin.mailConfig.index','admin.paymentGateway.index','admin.sms-gateway.index','admin.firebase.index'])
    <li class="menu-divider">
        <span class="menu-title">{{ __('Business Administration') }}</span>
    </li>
@endhasPermission

@hasPermission(['admin.generale-setting.index', 'admin.business-setting.index', 'admin.socialLink.index','admin.themeColor.index', 'admin.deliveryCharge.index','admin.ticketissuetype.index'])
    <!--- Settings --->
    <li>
        <a class="menu {{ request()->routeIs('admin.generale-setting.*', 'admin.business-setting.*', 'admin.socialLink.*', 'admin.themeColor.*', 'admin.deliveryCharge.*', 'admin.ticketissuetype.*') ? 'active' : '' }}"
            data-bs-toggle="collapse" href="#setings">
            <span>
                <i class="bi bi-gear-fill menu-icon"></i>
                {{ __('Buisness Settings') }}
            </span>
            <img src="{{ asset('assets/icons/arrowDown.svg') }}" alt="" class="downIcon">
        </a>
        <div class="collapse dropdownMenuCollapse {{ $request->routeIs('admin.generale-setting.*', 'admin.business-setting.*', 'admin.socialLink.*', 'admin.themeColor.*', 'admin.deliveryCharge.*', 'admin.ticketissuetype.*') ? 'show' : '' }}"
            id="setings">
            <div class="listBar">
                @hasPermission('admin.generale-setting.index')
                    <a href="{{ route('admin.generale-setting.index') }}"
                        class="subMenu {{ request()->routeIs('admin.generale-setting.index') ? 'active' : '' }}">
                        {{ __('General Settings') }}
                    </a>
                @endhasPermission

                @hasPermission('admin.business-setting.index')
                    <a href="{{ route('admin.business-setting.index') }}"
                        class="subMenu {{ request()->routeIs('admin.business-setting.*') ? 'active' : '' }}">
                        {{ __('Business Setup') }}
                    </a>
                @endhasPermission

                @hasPermission('admin.deliveryCharge.index')
                    <a href="{{ route('admin.deliveryCharge.index') }}"
                        class="subMenu {{ request()->routeIs('admin.deliveryCharge.*') ? 'active' : '' }}">
                        {{ __('Delivery Charge') }}
                    </a>
                @endhasPermission

                @hasPermission('admin.themeColor.index')
                    <a href="{{ route('admin.themeColor.index') }}"
                        class="subMenu {{ request()->routeIs('admin.themeColor.*') ? 'active' : '' }}">
                        {{ __('Theme Colors') }}
                    </a>
                @endhasPermission

                @hasPermission('admin.socialLink.index')
                    <a href="{{ route('admin.socialLink.index') }}"
                        class="subMenu {{ request()->routeIs('admin.socialLink.index') ? 'active' : '' }}">
                        {{ __('Social Links') }}
                    </a>
                @endhasPermission

                @hasPermission('admin.ticketissuetype.index')
                    <a href="{{ route('admin.ticketissuetype.index') }}"
                        class="subMenu {{ request()->routeIs('admin.ticketissuetype.index') ? 'active' : '' }}">
                        {{ __('Ticket Issue Types') }}
                    </a>
                @endhasPermission
            </div>
        </div>
    </li>
@endhasPermission

@use(App\Models\LegalPage)
@hasPermission(['admin.legalpage.index', 'admin.contactUs.index'])
    <!--- legal pages --->
    <li>
        <a class="menu {{ request()->routeIs('admin.legalpage.*', 'admin.contactUs.*') ? 'active' : '' }}"
            data-bs-toggle="collapse" href="#legalPages">
            <span>
                <i class="fa-solid fa-bookmark menu-icon"></i>
                {{ __('Legal Pages') }}
            </span>
            <img src="{{ asset('assets/icons/arrowDown.svg') }}" alt="" class="downIcon">
        </a>
        <div class="collapse dropdownMenuCollapse {{ $request->routeIs('admin.legalpage.*', 'admin.contactUs.*') ? 'show' : '' }}"
            id="legalPages">
            <div class="listBar">
                @foreach (LegalPage::all() as $legalPage)
                    <a href="{{ route('admin.legalpage.index', $legalPage->slug) }}"
                        class="subMenu {{ request()->fullUrl() === route('admin.legalpage.edit', $legalPage->slug) || request()->fullUrl() === route('admin.legalpage.index', $legalPage->slug) ? 'active' : '' }}"
                        title="{{ $legalPage->title }}">
                        {{ __($legalPage->title) }}
                    </a>
                @endforeach

                @hasPermission('admin.contactUs.index')
                    <a href="{{ route('admin.contactUs.index') }}"
                        class="subMenu {{ request()->routeIs('admin.contactUs.*') ? 'active' : '' }}">
                        {{ __('Contact Us') }}
                    </a>
                @endhasPermission
            </div>
        </div>
    </li>
@endhasPermission

@hasPermission(['admin.pusher.index', 'admin.mailConfig.index', 'admin.paymentGateway.index', 'admin.sms-gateway.index','admin.firebase.index'])
    <li>
        <a class="menu {{ request()->routeIs('admin.pusher.*', 'admin.mailConfig.*', 'admin.paymentGateway.*', 'admin.sms-gateway.*', 'admin.firebase.*') ? 'active' : '' }}"
            data-bs-toggle="collapse" href="#thirdpartConfig" title="Third Party configuration">
            <span>
                <i class="bi bi-boxes menu-icon"></i>
                {{ __('3rd Party Configaration') }}
            </span>
            <img src="{{ asset('assets/icons/arrowDown.svg') }}" alt="" class="downIcon">
        </a>
        <div class="collapse dropdownMenuCollapse {{ $request->routeIs('admin.pusher.*', 'admin.mailConfig.*', 'admin.paymentGateway.*', 'admin.sms-gateway.*', 'admin.firebase.*') ? 'show' : '' }}"
            id="thirdpartConfig">
            <div class="listBar">
                @hasPermission('admin.paymentGateway.index')
                    <a href="{{ route('admin.paymentGateway.index') }}"
                        class="subMenu {{ request()->routeIs('admin.paymentGateway.*') ? 'active' : '' }}">
                        {{ __('Payment Gateway') }}
                    </a>
                @endhasPermission

                @hasPermission('admin.sms-gateway.index')
                    <a href="{{ route('admin.sms-gateway.index') }}"
                        class="subMenu {{ request()->routeIs('admin.sms-gateway.*') ? 'active' : '' }}">
                        {{ __('SMS Gateway') }}
                    </a>
                @endhasPermission

                @hasPermission('admin.pusher.index')
                    <a href="{{ route('admin.pusher.index') }}"
                        class="subMenu {{ request()->routeIs('admin.pusher.*') ? 'active' : '' }}">
                        {{ __('Pusher Setup') }}
                    </a>
                @endhasPermission

                @hasPermission('admin.mailConfig.index')
                    <a href="{{ route('admin.mailConfig.index') }}"
                        class="subMenu {{ request()->routeIs('admin.mailConfig.*') ? 'active' : '' }}">
                        {{ __('Mail Config') }}
                    </a>
                @endhasPermission

                @hasPermission('admin.firebase.index')
                    <a href="{{ route('admin.firebase.index') }}"
                        class="subMenu {{ request()->routeIs('admin.firebase.*') ? 'active' : '' }}">
                        {{ __('Firebase Notification') }}
                    </a>
                @endhasPermission
            </div>
        </div>
    </li>
@endhasPermission

@hasPermission(['admin.language.index'])
    <li class="menu-divider">
        <span class="menu-title">{{ __('Language Settings') }}</span>
    </li>
    <!--- Languages --->
    <li>
        <a href="{{ route('admin.language.index') }}"
            class="menu {{ request()->routeIs('admin.language.*') ? 'active' : '' }}">
            <span>
                <i class="fa-solid fa-language menu-icon"></i>
                {{ __('Languages') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission(['shop.bulk-product-import.index', 'shop.bulk-product-export.index', 'shop.gallery.index'])
<li class="menu-divider">
    <span class="menu-title">{{ __('Import / Export') }}</span>
</li>
@endhasPermission

@hasPermission('shop.bulk-product-export.index')
<li>
    <a class="menu {{ $request->routeIs('shop.bulk-product-export.*') ? 'active' : '' }}"
        href="{{ route('shop.bulk-product-export.index') }}">
        <span>
            <i class="fa-solid fa-download menu-icon"></i>
            {{ __('Bulk Export') }}
        </span>
    </a>
</li>
@endhasPermission

@hasPermission('shop.bulk-product-import.index')
<li>
    <a class="menu {{ $request->routeIs('shop.bulk-product-import.*') ? 'active' : '' }}"
        href="{{ route('shop.bulk-product-import.index') }}">
        <span>
            <i class="fa-solid fa-upload menu-icon"></i>
            {{ __('Bulk Import') }}
        </span>
    </a>
</li>
@endhasPermission

@hasPermission('shop.gallery.index')
<!--- gallery --->
<li>
    <a class="menu {{ $request->routeIs('shop.gallery.*') ? 'active' : '' }}"
        href="{{ route('shop.gallery.index') }}">
        <span>
            <i class="fa-solid fa-file-image menu-icon"></i>
            {{ __('Gallery Import') }}
        </span>
    </a>
</li>
@endhasPermission

@hasPermission(['admin.supportTicket.index', 'admin.support.index'])
    <li class="menu-divider">
        <span class="menu-title">{{ __('Assistance/ Support') }}</span>
    </li>
@endhasPermission
@hasPermission(['admin.supportTicket.index'])
    <!--- Help Requests --->
    <li>
        <a href="{{ route('admin.supportTicket.index') }}"
            class="menu {{ request()->routeIs('admin.supportTicket.*') ? 'active' : '' }}">
            <span>
                <i class="bi bi-ticket-perforated menu-icon"></i>
                {{ __('Help Requests') }}
            </span>
        </a>
    </li>
@endhasPermission

@hasPermission(['admin.support.index'])
    <!--- Help Notes --->
    <li>
        <a href="{{ route('admin.support.index') }}"
            class="menu {{ request()->routeIs('admin.support.*') ? 'active' : '' }}">
            <span>
                <i class="bi bi-chat-right-text menu-icon"></i>
                {{ __('Help Notes') }}
            </span>
        </a>
    </li>
@endhasPermission

