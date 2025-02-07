<div class="app-sidebar">
    <div class="scrollbar-sidebar">
        <div class="branding-logo">
            @php
                $request = request();

                $shop = generaleSetting('shop');
                $rootShop = generaleSetting('rootShop');
                $isAdmin = $shop->id == $rootShop->id ? true : false;

                $url = $isAdmin ? route('admin.dashboard.index') : route('shop.dashboard.index');
            @endphp
            <a href="{{ $url }}">
                <img src="{{ $generaleSetting?->logo ?? asset('assets/logo.png') }}" alt="logo" loading="lazy" />
            </a>
        </div>
        <div class="branding-logo-forMobile">
            <a href="{{ $generaleSetting?->logo ?? asset('assets/logo.png') }}"></a>
        </div>
        <div class="app-sidebar-inner">
            <ul class="vertical-nav-menu">
                @if ($isAdmin)
                    @include('layouts.partials.admin-menu')
                @else
                    @include('layouts.partials.shop-menu')
                @endif
            </ul>
        </div>
        <div class="sideBarfooter">
            <button type="button" class="fullbtn hite-icon" onclick="toggleFullScreen(document.body)">
                <img src="{{ asset('assets/icons-admin/expand.svg') }}" alt="icon" loading="lazy" />
            </button>
            @if ($isAdmin)
                @hasPermission('admin.generale-setting.index')
                    <a href="{{ route('admin.generale-setting.index') }}" class="fullbtn hite-icon">
                    <img src="{{ asset('assets/icons-admin/settings.svg') }}" alt="icon" loading="lazy" />
                    </a>
                @endhasPermission

                @hasPermission('admin.profile.index')
                    <a href="{{ route('admin.profile.index') }}" class="fullbtn hite-icon">
                        <img src="{{ asset('assets/icons-admin/user-circle.svg') }}" alt="">
                    </a>
                @endhasPermission
            @else
                @hasPermission('shop.profile.index')
                    <a href="{{ route('shop.profile.index') }}" class="fullbtn hite-icon">
                        <img src="{{ asset('assets/icons-admin/user-circle.svg') }}" alt="">
                    </a>
                @endhasPermission
            @endif

            <a href="javascript:void(0)" class="fullbtn hite-icon logout">
                <img src="{{ asset('assets/icons-admin/log-out.svg') }}" alt="icon" loading="lazy" />
            </a>
        </div>
    </div>
</div>
