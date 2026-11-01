@php
$containerNav = $containerNav ?? 'container-fluid';
$navbarDetached = $navbarDetached ?? '';
$isDetached = ($navbarDetached === 'navbar-detached');
$user = auth()->user();

/*
|--------------------------------------------------------------------------
| MENU SEARCH DATA
|--------------------------------------------------------------------------
| Form pencarian menu di navbar membaca resources/menu/verticalMenu.json
| atau resources/menu/menu.json. Data dibuat recursive dari semua submenu.
*/
$menuSearchItems = [];

try {
    $menuFiles = [
        resource_path('menu/verticalMenu.json'),
        resource_path('menu/menu.json'),
    ];

    $menuPayload = null;

    foreach ($menuFiles as $menuFile) {
        if (\Illuminate\Support\Facades\File::exists($menuFile)) {
            $menuPayload = json_decode(\Illuminate\Support\Facades\File::get($menuFile), true);
            break;
        }
    }

    $collectMenuItems = function ($items, $parent = '') use (&$collectMenuItems, &$menuSearchItems) {
        foreach (($items ?? []) as $item) {
            $name = $item['name'] ?? '';
            $url = $item['url'] ?? null;
            $slug = $item['slug'] ?? '';
            $icon = $item['icon'] ?? 'mdi mdi-circle-small';
            $currentPath = trim($parent . ' / ' . $name, ' /');

            if (!empty($url) && $url !== 'javascript:void(0);' && $url !== '#') {
                $menuSearchItems[] = [
                    'name' => $name,
                    'path' => $currentPath,
                    'url' => \Illuminate\Support\Str::startsWith($url, ['http://', 'https://', '/'])
                        ? url($url)
                        : url($url),
                    'slug' => $slug,
                    'icon' => $icon,
                    'keywords' => strtolower($name . ' ' . $currentPath . ' ' . $slug . ' ' . $url),
                ];
            }

            if (!empty($item['submenu']) && is_array($item['submenu'])) {
                $collectMenuItems($item['submenu'], $currentPath);
            }
        }
    };

    $collectMenuItems($menuPayload['menu'] ?? []);
} catch (\Throwable $e) {
    $menuSearchItems = [];
}


// Data awal dirender server-side, kemudian disinkronkan berkala tanpa reload.
$notifications = is_array($notifications ?? null) ? $notifications : [];
$notificationCount = (int) ($notificationCount ?? 0);
$notificationStatusUrl = route('notifications.summary');
$notificationMarkReadUrl = route('notifications.mark-read');

$profileRoles = collect();
$profilePermissionsCount = 0;
$profileStores = collect();
$profileWarehouses = collect();

if ($user) {
    try {
        $profileRoles = $user->getRoleNames();
        $profilePermissionsCount = $user->getAllPermissions()->count();
        $profileStores = $user->stores()->orderBy('name')->pluck('name');
        $profileWarehouses = $user->warehouses()->orderBy('name')->pluck('name');
    } catch (\Throwable $e) {
        // Profil tetap dapat dibuka apabila salah satu relasi belum tersedia.
    }
}
@endphp


@push('page-style')
<style>
    .navbar-menu-search {
        position: relative;
        z-index: 1100;
        width: min(100%, 620px);
        min-width: 280px;
        max-width: 620px;
    }

    .navbar-menu-search .menu-search-input-wrap {
        min-height: 44px;
        border-radius: 999px;
        border: 1px solid rgba(226, 232, 240, .95);
        background: rgba(248, 250, 252, .9);
        transition: .18s ease;
    }

    #layout-navbar {
        min-height: 72px;
        padding-top: .75rem;
        padding-bottom: .75rem;
    }

    #layout-navbar .navbar-nav-right {
        width: 100%;
        min-width: 0;
        gap: 1rem;
    }

    .navbar-search-zone {
        min-width: 0;
    }

    .navbar-actions {
        flex-shrink: 0;
        gap: .1rem;
        min-height: 52px;
        padding: .3rem .4rem;
        border: 1px solid #eadfec;
        border-radius: 999px;
        background: linear-gradient(135deg, #fff, #fdf4ff);
    }

    .navbar-actions > .nav-item {
        display: flex;
        align-items: center;
    }

    .navbar-action-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        color: #2a2033;
        transition: background .18s ease, color .18s ease, transform .18s ease;
    }

    #navbar-notifications {
        margin-left: .1rem;
        border-right: 1px solid #eadfec;
        border-radius: 999px 0 0 999px;
    }

    #notification-badge {
        top: 1px !important;
        right: -1px !important;
        left: auto !important;
        min-width: 19px;
        height: 19px;
        padding: 0 .3rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        font-size: .66rem;
        line-height: 1;
        transform: none !important;
    }

    .navbar-action-button:hover,
    .navbar-action-button:focus-visible {
        color: #7c3aed;
        background: #f3e8ff;
        transform: translateY(-1px);
    }

    .navbar-user-trigger {
        display: flex;
        align-items: center;
        gap: .65rem;
        min-width: 0;
        min-height: 44px;
        padding: .15rem .25rem .15rem .55rem !important;
        border-radius: 999px;
        transition: background .18s ease;
    }

    .navbar-user-trigger:hover,
    .navbar-user-trigger:focus-visible {
        background: #fce7f3;
    }

    .navbar-user-copy {
        max-width: 150px;
        line-height: 1.1;
    }

    .navbar-user-name,
    .navbar-user-role {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .navbar-user-name {
        color: #17111f;
        font-size: .8rem;
        font-weight: 800;
    }

    .navbar-user-role {
        margin-top: .22rem;
        color: #75687d;
        font-size: .68rem;
    }

    .navbar-user-chevron {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        color: #9b8ca3;
        font-size: 1rem;
    }

    .navbar-actions .avatar {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
    }

    .navbar-actions .avatar.avatar-online::after {
        right: -1px;
        bottom: 1px;
        border: 2px solid #fff;
    }

    .navbar-actions .avatar img {
        width: 38px !important;
        height: 38px !important;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px rgba(236, 72, 153, .18);
    }

    .navbar-menu-search .menu-search-input-wrap:focus-within {
        border-color: #5b5cf6;
        background: #fff;
        box-shadow: 0 0 0 .2rem rgba(91, 92, 246, .12);
    }

    .navbar-menu-search .menu-search-input {
        min-width: 0;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        font-size: .875rem;
    }

    .navbar-menu-search-results {
        position: absolute;
        top: calc(100% + .65rem);
        left: 0;
        width: min(520px, 92vw);
        max-height: 420px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 1.2rem 2.5rem rgba(15, 23, 42, .16);
        z-index: 1090;
        display: none;
    }

    .navbar-menu-search-results.show {
        display: block;
    }

    .navbar-menu-search-results .search-header {
        padding: .85rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(180deg, #fff, #f8fafc);
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .navbar-menu-search-results .search-item {
        display: block;
        cursor: pointer;
        padding: .85rem 1rem;
        color: #334155;
        text-decoration: none;
        border-bottom: 1px solid #f1f5f9;
        transition: .15s ease;
    }

    .navbar-menu-search-results .search-item:hover,
    .navbar-menu-search-results .search-item.active {
        background: rgba(91, 92, 246, .08);
        color: #4338ca;
    }

    .navbar-menu-search-results .search-icon {
        width: 38px;
        height: 38px;
        border-radius: .85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(91, 92, 246, .12);
        color: #4338ca;
        flex-shrink: 0;
    }

    .navbar-menu-search-results .search-path {
        font-size: .76rem;
        color: #64748b;
        margin-top: .15rem;
    }

    .navbar-menu-search-results .search-empty {
        padding: 1.25rem;
        color: #64748b;
        text-align: center;
    }

    .navbar-menu-search-results mark {
        padding: 0;
        background: rgba(245, 158, 11, .24);
        color: inherit;
        border-radius: .2rem;
    }

    .navbar-search-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1040;
        display: none;
    }

    .navbar-search-backdrop.show {
        display: block;
    }

    body.modal-open .navbar-search-backdrop,
    body.modal-open .navbar-menu-search-results {
        display: none !important;
    }

    .navbar-sidebar-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: .75rem;
        transition: .18s ease;
    }

    .navbar-sidebar-toggle:hover {
        background: rgba(91, 92, 246, .10);
        color: #5b5cf6;
    }

    .sidebar-floating-toggle {
        position: fixed;
        top: 22px;
        left: 238px;
        z-index: 1090;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        padding: 0;
        color: #fff;
        border: 4px solid #f5f5fa;
        border-radius: 50%;
        background: linear-gradient(135deg, #7367f0, #9b51e0);
        box-shadow: 0 6px 18px rgba(115, 103, 240, .34);
        transition: left .3s ease, transform .18s ease, box-shadow .18s ease;
    }

    .sidebar-floating-toggle:hover,
    .sidebar-floating-toggle:focus-visible {
        color: #fff;
        transform: scale(1.08);
        box-shadow: 0 8px 22px rgba(115, 103, 240, .45);
    }

    .sidebar-floating-toggle:focus-visible {
        outline: 3px solid rgba(115, 103, 240, .25);
        outline-offset: 2px;
    }

    @media (min-width: 1200px) {
        #layout-menu {
            transition: transform .3s ease, visibility .3s ease;
        }

        #layout-navbar {
            transition: padding-left .3s ease;
        }

        #layout-menu .app-brand > .layout-menu-toggle {
            display: none !important;
        }

        html.layout-menu-collapsed #layout-menu {
            visibility: hidden;
            pointer-events: none;
            transform: translateX(-100%) !important;
        }

        .layout-menu-collapsed .sidebar-floating-toggle {
            left: 18px;
        }

        html.layout-menu-collapsed #layout-navbar {
            padding-left: 76px !important;
        }

        .layout-menu-collapsed .container-xxl {
            max-width: 100%;
        }
    }

    .account-profile-modal .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 1.25rem;
        box-shadow: 0 1.5rem 4rem rgba(15, 23, 42, .22);
    }

    .account-profile-cover {
        min-height: 118px;
        background:
            radial-gradient(circle at 18% 20%, rgba(255, 255, 255, .32), transparent 24%),
            linear-gradient(135deg, #5b5cf6, #8b5cf6 52%, #06b6d4);
    }

    .account-profile-avatar {
        position: absolute;
        z-index: 2;
        bottom: -46px;
        left: 50%;
        width: 92px;
        height: 92px;
        transform: translateX(-50%);
        border: 5px solid #fff;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 .65rem 1.5rem rgba(15, 23, 42, .18);
        object-fit: cover;
    }

    .account-profile-modal .modal-body {
        padding-top: 62px !important;
    }

    .account-profile-info {
        padding: .9rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: .9rem;
        background: #f8fafc;
    }

    .account-profile-info .profile-label {
        margin-bottom: .2rem;
        color: #64748b;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .account-profile-info .profile-value {
        color: #1e293b;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    @media (max-width: 991.98px) {
        .navbar-menu-search {
            width: 100%;
            min-width: 180px;
            max-width: none;
            margin-right: .75rem;
        }

        .navbar-menu-search-results {
            left: 0;
            right: 0;
            width: 92vw;
        }

        .navbar-actions { padding: .25rem; }
    }

    @media (max-width: 575.98px) {
        .navbar-menu-search {
            display: none;
        }

        #layout-navbar { min-height: 64px; }
    }
</style>
@endpush

@if($isDetached)
<nav class="layout-navbar {{ $containerNav }} navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    @else
    <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
        <div class="{{ $containerNav }}">
            @endif

            {{-- Brand (khusus navbar-full) --}}
            @if(isset($navbarFull))
            <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
                <a href="{{ url('/') }}" class="app-brand-link gap-2">
                    <span class="app-brand-logo demo">
                        @include('_partials.macros', ['height' => 20])
                    </span>
                    <span class="app-brand-text demo menu-text fw-semibold ms-1">
                        {{ config('variables.templateName') }}
                    </span>
                </a>
                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                    <i class="mdi menu-toggle-icon d-xl-block align-middle mdi-20px"></i>
                </a>
            </div>
            @endif

            {{-- Toggle sidebar (jika ada menu) --}}
            @if(!isset($navbarHideToggle))
            <div
                class="layout-menu-toggle navbar-sidebar-toggle navbar-nav me-3 d-xl-none">
                <a class="nav-item nav-link p-0"
                   href="javascript:void(0)"
                   title="Buka/tutup sidebar"
                   aria-label="Buka atau tutup sidebar">
                    <i class="mdi mdi-menu mdi-24px"></i>
                </a>
            </div>
            @endif

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                {{-- Search Menu --}}
                <div class="navbar-nav navbar-search-zone align-items-center flex-grow-1 me-3">
                    <div class="nav-item navbar-menu-search">
                        <div class="menu-search-input-wrap d-flex align-items-center px-3">
                            <i class="mdi mdi-magnify mdi-22px lh-0 text-muted me-2"></i>
                            <input type="text"
                                   id="navbarMenuSearchInput"
                                   class="form-control menu-search-input"
                                   placeholder="Cari menu..."
                                   autocomplete="off"
                                   aria-label="Cari menu">
                            <button type="button"
                                    id="navbarMenuSearchClear"
                                    class="btn btn-sm btn-icon btn-text-secondary d-none"
                                    aria-label="Clear search">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>

                        <div class="navbar-menu-search-results" id="navbarMenuSearchResults">
                            <div class="search-header d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-bold">Pencarian Menu</div>
                                    <small class="text-muted" id="navbarMenuSearchCount">Ketik minimal 2 huruf, pilih dengan ↑ ↓ lalu Enter</small>
                                </div>
                                <span class="badge bg-label-primary">Keyboard</span>
                            </div>

                            <div id="navbarMenuSearchList">
                                <div class="search-empty">
                                    Ketik minimal 2 huruf, pilih menu dengan tombol ↑ / ↓, lalu tekan Enter.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="navbar-nav navbar-actions flex-row align-items-center ms-auto">

                    {{-- Notifications bell --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link navbar-action-button dropdown-toggle hide-arrow p-0 position-relative"
                           href="javascript:void(0);"
                           id="navbar-notifications"
                           role="button"
                           data-bs-toggle="dropdown"
                           aria-expanded="false"
                           aria-haspopup="true"
                           aria-label="Notifications">
                            <i class="mdi mdi-bell-outline mdi-24px"></i>
                            <span id="notification-badge"
                                  class="badge bg-danger rounded-pill position-absolute translate-middle top-0 start-100 {{ $notificationCount > 0 ? '' : 'd-none' }}">
                                {{ $notificationCount }}
                            </span>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end mt-3 py-0"
                            aria-labelledby="navbar-notifications"
                            style="min-width:340px; max-width:380px;">
                            <li class="dropdown-header py-3 d-flex align-items-center justify-content-between">
                                <h6 class="mb-0">Notifications</h6>
                                <span id="notification-header-count" class="badge bg-label-primary">
                                    {{ $notificationCount }}
                                </span>
                            </li>
                            <li><div class="dropdown-divider my-0"></div></li>

                            <li>
                                <ul id="notification-items" class="list-unstyled mb-0">
                                    @forelse($notifications as $n)
                                        <li class="notification-item" data-notification-key="{{ $n['key'] ?? '' }}">
                                            <a class="dropdown-item py-3 text-wrap" href="{{ $n['open_url'] ?? $n['url'] ?? 'javascript:void(0);' }}">
                                                <div class="d-flex align-items-start">
                                                    <i class="mdi {{ $n['icon'] ?? 'mdi-information-outline' }} me-2 mdi-20px"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-medium">{{ $n['title'] ?? 'Notification' }}</div>
                                                        @if(!empty($n['subtitle']))
                                                            <small class="text-muted d-block">{{ $n['subtitle'] }}</small>
                                                        @endif
                                                        @if(!empty($n['time']))
                                                            <small class="text-muted">{{ $n['time'] }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @empty
                                        <li id="notification-empty">
                                            <span class="dropdown-item text-muted py-3">Tidak ada notifikasi</span>
                                        </li>
                                    @endforelse
                                </ul>
                            </li>

                            <li><div class="dropdown-divider my-0"></div></li>
                            <li>
                                <a class="dropdown-item text-center py-3"
                                   href="{{ route('reports.retur-cabang.index') }}">
                                    Lihat semua
                                </a>
                            </li>
                        </ul>
                    </li>
                    {{-- /Notifications bell --}}

                    {{-- User --}}
                    <li class="nav-item navbar-dropdown dropdown-user dropdown">
                        <a class="nav-link navbar-user-trigger dropdown-toggle hide-arrow" href="javascript:void(0);"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="navbar-user-copy d-none d-lg-block text-end">
                                <span class="navbar-user-name">{{ $user->name ?? 'User' }}</span>
                                <span class="navbar-user-role">{{ session('user_role_name') ?: 'Backoffice' }}</span>
                            </span>
                            <div class="avatar avatar-online">
                                <img src="{{ asset('assets/img/avatars/1.png') }}" alt="{{ $user->name ?? 'User' }}"
                                    class="w-px-40 h-auto rounded-circle" />
                            </div>
                            <i class="mdi mdi-chevron-down navbar-user-chevron d-none d-lg-inline-flex"></i>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
                            <li>
                                <button type="button"
                                        class="dropdown-item pb-2 mb-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#loggedInAccountProfileModal">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-2 pe-1">
                                            <div class="avatar avatar-online">
                                                <img src="{{ asset('assets/img/avatars/1.png') }}"
                                                    alt="{{ $user->name ?? 'User' }}"
                                                    class="w-px-40 h-auto rounded-circle" />
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ $user->name ?? 'User' }}</h6>
                                            <small class="text-muted">{{ $user->email ?? '' }}</small>
                                            <small class="d-block text-primary mt-1">
                                                <i class="mdi mdi-account-eye-outline me-1"></i>Lihat Profil
                                            </small>
                                        </div>
                                    </div>
                                </button>
                            </li>

                            <li>
                                <div class="dropdown-divider my-1"></div>
                            </li>

                            <!--  <li>
                                <a class="dropdown-item" href="javascript:void(0);">
                                    <i class="mdi mdi-account-outline me-1 mdi-20px"></i>
                                    <span class="align-middle">My Profile</span>
                                </a>
                            </li> -->
                            <!--  <li>
                                <a class="dropdown-item" href="javascript:void(0);">
                                    <i class="mdi mdi-cog-outline me-1 mdi-20px"></i>
                                    <span class="align-middle">Settings</span>
                                </a>
                            </li> -->

                            <li>
                                <div class="dropdown-divider my-1"></div>
                            </li>

                            <li>
                                <form action="{{ route('logout') }}" method="POST" id="logout-form">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="mdi mdi-power me-1 mdi-20px"></i>
                                        <span class="align-middle">Log Out</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    {{-- /User --}}
                </ul>
            </div>

            @if(!$isDetached)
        </div>
        @endif
    </nav>

@if($user)
<div class="modal fade account-profile-modal"
     id="loggedInAccountProfileModal"
     tabindex="-1"
     aria-labelledby="loggedInAccountProfileModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="account-profile-cover position-relative">
                <button type="button"
                        class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                        data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                <img src="{{ asset('assets/img/avatars/1.png') }}"
                     alt="{{ $user->name }}"
                     class="account-profile-avatar">
            </div>

            <div class="modal-body px-4 pb-4 pt-0">
                <div class="text-center">
                    <h4 class="mb-1" id="loggedInAccountProfileModalLabel">
                        {{ $user->name }}
                    </h4>
                    <div class="text-muted">{{ $user->email }}</div>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                        @forelse($profileRoles as $role)
                            <span class="badge bg-label-primary">{{ $role }}</span>
                        @empty
                            <span class="badge bg-label-secondary">Role belum ditentukan</span>
                        @endforelse
                        <span class="badge bg-label-success">
                            <i class="mdi mdi-check-circle-outline me-1"></i>Akun Aktif
                        </span>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-sm-6">
                        <div class="account-profile-info h-100">
                            <div class="profile-label">Username</div>
                            <div class="profile-value">{{ $user->username ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="account-profile-info h-100">
                            <div class="profile-label">Jumlah Permission</div>
                            <div class="profile-value">
                                {{ number_format($profilePermissionsCount) }} permission
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="account-profile-info h-100">
                            <div class="profile-label">Terdaftar Sejak</div>
                            <div class="profile-value">
                                {{ $user->created_at?->format('d M Y, H:i') ?? '-' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="account-profile-info h-100">
                            <div class="profile-label">Email Terverifikasi</div>
                            <div class="profile-value">
                                {{ $user->email_verified_at ? $user->email_verified_at->format('d M Y, H:i') : 'Belum diverifikasi' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="account-profile-info mt-3">
                    <div class="profile-label">Akses Aplikasi</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach([
                            'Backoffice' => $user->backoffice_access,
                            'POS / Store' => $user->store_access,
                            'Warehouse' => $user->wms_access,
                            'Ecommerce' => $user->ecommerce_access,
                        ] as $application => $hasAccess)
                            <span class="badge {{ $hasAccess ? 'bg-label-success' : 'bg-label-secondary' }}">
                                <i class="mdi {{ $hasAccess ? 'mdi-check-circle-outline' : 'mdi-minus-circle-outline' }} me-1"></i>
                                {{ $application }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="row g-3 mt-0">
                    <div class="col-sm-6">
                        <div class="account-profile-info h-100">
                            <div class="profile-label">Store</div>
                            <div class="profile-value">
                                {{ $profileStores->isNotEmpty() ? $profileStores->join(', ') : 'Tidak dibatasi ke store' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="account-profile-info h-100">
                            <div class="profile-label">Warehouse</div>
                            <div class="profile-value">
                                {{ $profileWarehouses->isNotEmpty() ? $profileWarehouses->join(', ') : 'Tidak dibatasi ke warehouse' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endif


<div class="navbar-search-backdrop" id="navbarSearchBackdrop"></div>

@push('page-script')
<script>
(function () {
    'use strict';

    window.__navbarMenuSearchVersion = 'v5-click-and-keyboard-navigation';

    function initNavbarMenuSearch() {
        const rawMenuItems = @json($menuSearchItems);

        const input = document.getElementById('navbarMenuSearchInput');
        const resultBox = document.getElementById('navbarMenuSearchResults');
        const resultList = document.getElementById('navbarMenuSearchList');
        const resultCount = document.getElementById('navbarMenuSearchCount');
        const clearBtn = document.getElementById('navbarMenuSearchClear');
        const backdrop = document.getElementById('navbarSearchBackdrop');

        let activeIndex = -1;
        let currentResults = [];
        let isNavigating = false;
        let lastKeyword = null;

        if (!input || !resultBox || !resultList) {
            return;
        }

        function escapeHtml(text) {
            return String(text ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function normalize(text) {
            return String(text ?? '')
                .toLowerCase()
                .trim()
                .replace(/\s+/g, ' ');
        }

        function isValidUrl(url) {
            const cleanUrl = String(url || '').trim();

            return cleanUrl !== ''
                && cleanUrl !== '#'
                && cleanUrl !== 'javascript:void(0);'
                && cleanUrl !== 'javascript:void(0)';
        }

        function makeAbsoluteUrl(url) {
            const cleanUrl = String(url || '').trim();

            if (!isValidUrl(cleanUrl)) {
                return '';
            }

            try {
                return new URL(cleanUrl, window.location.origin).href;
            } catch (error) {
                return cleanUrl;
            }
        }

        function highlight(text, keyword) {
            const safeText = escapeHtml(text);
            const q = normalize(keyword);

            if (!q) {
                return safeText;
            }

            const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            return safeText.replace(new RegExp('(' + escaped + ')', 'ig'), '<mark>$1</mark>');
        }

        function collectSidebarMenuItems() {
            const sidebarItems = [];
            const sidebarLinks = document.querySelectorAll('.layout-menu a[href], #layout-menu a[href], .menu-inner a[href]');

            sidebarLinks.forEach(function (link) {
                const href = link.getAttribute('href');
                const url = makeAbsoluteUrl(href);

                if (!url) {
                    return;
                }

                const name = normalize(link.textContent).replace(/\s+/g, ' ');

                if (!name) {
                    return;
                }

                const iconEl = link.querySelector('i');
                const icon = iconEl ? iconEl.className : 'mdi mdi-circle-small';

                const parents = [];
                let node = link.closest('.menu-item');

                while (node) {
                    const parentText = node.querySelector(':scope > .menu-link .menu-title, :scope > .menu-link div, :scope > .menu-link span');

                    if (parentText) {
                        const label = normalize(parentText.textContent);

                        if (label && !parents.includes(label)) {
                            parents.unshift(label);
                        }
                    }

                    const parentMenu = node.parentElement ? node.parentElement.closest('.menu-item') : null;
                    node = parentMenu;
                }

                const cleanName = name.replace(/\s+/g, ' ').trim();
                const path = parents.length ? parents.join(' / ') : cleanName;

                sidebarItems.push({
                    name: cleanName,
                    path: path,
                    url: url,
                    slug: '',
                    icon: icon,
                    keywords: normalize(cleanName + ' ' + path + ' ' + url)
                });
            });

            return sidebarItems;
        }

        function uniqueMenuItems(items) {
            const map = new Map();

            items.forEach(function (item) {
                const url = makeAbsoluteUrl(item.url);
                const name = String(item.name || '').trim();
                const path = String(item.path || name).trim();

                if (!url || !name) {
                    return;
                }

                const key = normalize(url + '|' + name);

                if (!map.has(key)) {
                    map.set(key, {
                        name: name,
                        path: path,
                        url: url,
                        slug: String(item.slug || ''),
                        icon: String(item.icon || 'mdi mdi-circle-small'),
                        keywords: normalize([
                            name,
                            path,
                            item.slug || '',
                            item.keywords || '',
                            url
                        ].join(' '))
                    });
                }
            });

            return Array.from(map.values());
        }

        const menuItems = uniqueMenuItems([
            ...(Array.isArray(rawMenuItems) ? rawMenuItems : []),
            ...collectSidebarMenuItems()
        ]);

        function showResults() {
            resultBox.classList.add('show');

            if (backdrop) {
                backdrop.classList.add('show');
            }
        }

        function hideResults() {
            resultBox.classList.remove('show');

            if (backdrop) {
                backdrop.classList.remove('show');
            }

            activeIndex = -1;
        }

        function setActive(index) {
            const items = resultList.querySelectorAll('.search-item');

            items.forEach(item => item.classList.remove('active'));

            if (!items.length) {
                activeIndex = -1;
                return;
            }

            if (index < 0) {
                index = items.length - 1;
            }

            if (index >= items.length) {
                index = 0;
            }

            activeIndex = index;
            items[activeIndex].classList.add('active');
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        }

        function renderEmpty(message) {
            resultList.innerHTML = `
                <div class="search-empty">
                    ${escapeHtml(message)}
                </div>
            `;
        }

        function goToMenu(url) {
            const targetUrl = makeAbsoluteUrl(url);

            if (!targetUrl || isNavigating) {
                return;
            }

            isNavigating = true;
            hideResults();
            input.blur();

            // Pakai assign + fallback href agar tetap pindah halaman
            // walaupun ada script template lain yang menahan event klik.
            try {
                window.location.assign(targetUrl);
            } catch (error) {
                window.location.href = targetUrl;
            }

            setTimeout(function () {
                if (window.location.href !== targetUrl) {
                    window.location.href = targetUrl;
                }
            }, 80);
        }

        function renderResults(keyword, forceRender = false) {
            const q = normalize(keyword);

            if (!forceRender && q === lastKeyword) {
                return;
            }

            lastKeyword = q;

            if (clearBtn) {
                clearBtn.classList.toggle('d-none', q.length === 0);
            }

            if (q.length < 2) {
                currentResults = [];

                if (resultCount) {
                    resultCount.textContent = 'Ketik minimal 2 huruf';
                }

                renderEmpty('Ketik minimal 2 huruf, pilih menu dengan tombol ↑ / ↓, lalu tekan Enter.');
                showResults();
                return;
            }

            currentResults = menuItems
                .filter(function (item) {
                    return normalize(item.keywords).includes(q)
                        || normalize(item.name).includes(q)
                        || normalize(item.path).includes(q)
                        || normalize(item.url).includes(q);
                })
                .slice(0, 15);

            if (resultCount) {
                resultCount.textContent = currentResults.length
                    ? currentResults.length + ' menu ditemukan'
                    : 'Tidak ada menu';
            }

            if (!currentResults.length) {
                renderEmpty('Menu tidak ditemukan.');
                showResults();
                return;
            }

            resultList.innerHTML = currentResults.map(function (item, index) {
                const url = makeAbsoluteUrl(item.url);

                return `
                    <a class="search-item"
                       href="javascript:void(0);"
                       role="option"
                       aria-selected="${index === activeIndex ? 'true' : 'false'}"
                       data-url="${escapeHtml(url)}"
                       data-index="${index}">
                        <div class="d-flex align-items-center gap-3">
                            <span class="search-icon">
                                <i class="${escapeHtml(item.icon || 'mdi mdi-circle-small')}"></i>
                            </span>

                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold">${highlight(item.name || '-', q)}</div>
                                <div class="search-path">${highlight(item.path || '-', q)}</div>
                            </div>

                            <i class="mdi mdi-arrow-right text-muted"></i>
                        </div>
                    </a>
                `;
            }).join('');

            setActive(0);
            showResults();
        }

        function refreshSearch(forceRender = false) {
            renderResults(input.value, forceRender);
        }

        input.addEventListener('input', function () {
            refreshSearch(true);
        });

        input.addEventListener('keyup', function () {
            refreshSearch(false);
        });

        input.addEventListener('change', function () {
            refreshSearch(true);
        });

        input.addEventListener('paste', function () {
            setTimeout(function () {
                refreshSearch(true);
            }, 0);
        });

        input.addEventListener('focus', function () {
            refreshSearch(true);
        });

        input.addEventListener('keydown', function (event) {
            const items = resultList.querySelectorAll('.search-item');

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                setActive(activeIndex + 1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(activeIndex - 1);
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();

                if (items.length && activeIndex >= 0 && items[activeIndex]) {
                    goToMenu(items[activeIndex].dataset.url || items[activeIndex].getAttribute('href'));
                }

                return;
            }

            if (event.key === 'Escape') {
                hideResults();
            }
        });

        resultList.addEventListener('click', function (event) {
            const item = event.target.closest('.search-item');

            if (!item || !resultList.contains(item)) {
                return;
            }

            event.preventDefault();
            goToMenu(item.dataset.url || item.getAttribute('href'));
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                input.value = '';
                refreshSearch(true);
                input.focus();
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                hideResults();
            });
        }

        document.addEventListener('click', function (event) {
            if (isNavigating) {
                return;
            }

            const isInside = event.target.closest('.navbar-menu-search');

            if (!isInside) {
                hideResults();
            }
        });

        setInterval(function () {
            if (document.activeElement === input) {
                refreshSearch(false);
            }
        }, 250);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavbarMenuSearch);
    } else {
        initNavbarMenuSearch();
    }
})();
</script>
<script>
(function () {
    function updateSidebarToggleState() {
        const toggle = document.getElementById('desktopSidebarToggle');
        if (!toggle || !window.Helpers) return;

        const collapsed = window.Helpers.isCollapsed();
        const label = collapsed ? 'Buka sidebar' : 'Tutup sidebar';
        const icon = toggle.querySelector('i');
        const text = toggle.querySelector('.sidebar-toggle-text');

        toggle.title = label;
        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

        if (text) text.textContent = label;
        if (icon) {
            icon.classList.toggle('mdi-chevron-left', !collapsed);
            icon.classList.toggle('mdi-chevron-right', collapsed);
        }
    }

    function initSidebarToggle() {
        document.querySelectorAll('.layout-menu-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                window.setTimeout(function () {
                    if (window.innerWidth < 1200 || !window.Helpers) return;

                    updateSidebarToggleState();
                }, 450);
            });
        });

        updateSidebarToggleState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarToggle);
    } else {
        initSidebarToggle();
    }
})();
</script>
@endpush


@push('page-script')
<script>
(function () {
    'use strict';

    const statusUrl = @json($notificationStatusUrl);
    let lastSignature = '';
    let lastTotal = Number(@json($notificationCount)) || 0;
    let pollingTimer = null;
    let inFlight = false;
    let failures = 0;
    let nextPollAt = 0;
    let stopped = false;

    function schedulePoll() {
        clearTimeout(pollingTimer);
        if (stopped || document.hidden) return;
        pollingTimer = setTimeout(fetchNotificationStatus, Math.max(0, nextPollAt - Date.now()));
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setBadge(count) {
        const badge = document.getElementById('notification-badge');
        const headerCount = document.getElementById('notification-header-count');
        const normalized = Math.max(0, Number(count) || 0);

        if (badge) {
            badge.textContent = normalized;
            badge.classList.toggle('d-none', normalized === 0);
        }

        if (headerCount) {
            headerCount.textContent = normalized;
        }
    }

    function updateMenuBadges(summary) {
        const counts = summary.menu_counts || {};
        const branchCount = Number(summary.branch_operations_count || 0);

        document.querySelectorAll('.js-menu-notification-badge').forEach(badge => {
            const key = badge.dataset.notificationMenu;
            const count = key === 'branch-operations'
                ? branchCount
                : Number(counts[key] || 0);

            badge.textContent = count;
            badge.classList.toggle('d-none', count === 0);
        });
    }

    function announceNewNotification(summary) {
        const newest = summary.notifications?.[0];
        if (!newest || typeof bootstrap === 'undefined') return;

        let container = document.getElementById('realtime-notification-toasts');
        if (!container) {
            container = document.createElement('div');
            container.id = 'realtime-notification-toasts';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '2000';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'toast border-0 shadow';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="toast-header">
                <i class="mdi ${escapeHtml(newest.icon)} me-2 text-primary"></i>
                <strong class="me-auto">Notifikasi Baru</strong>
                <small>sekarang</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                <a href="${escapeHtml(newest.open_url || newest.url)}" class="text-body text-decoration-none">
                    <strong class="d-block">${escapeHtml(newest.title)}</strong>
                    <span class="text-muted">${escapeHtml(newest.subtitle)}</span>
                </a>
            </div>`;

        container.appendChild(toast);
        const instance = bootstrap.Toast.getOrCreateInstance(toast, {
            delay: 7000
        });
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
        instance.show();
    }

    function renderNotifications(summary) {
        const items = document.getElementById('notification-items');
        if (!items) return;

        const notifications = Array.isArray(summary.notifications)
            ? summary.notifications
            : [];

        if (notifications.length === 0) {
            items.innerHTML =
                '<li id="notification-empty">' +
                '<span class="dropdown-item text-muted py-3">Tidak ada notifikasi operasional</span>' +
                '</li>';
        } else {
            items.innerHTML = notifications.map(notification => `
                <li class="notification-item"
                    data-notification-key="${escapeHtml(notification.key)}">
                    <a class="dropdown-item py-3 text-wrap"
                       href="${escapeHtml(notification.open_url || notification.url)}">
                        <div class="d-flex align-items-start gap-2">
                            <span class="avatar-initial rounded bg-label-${escapeHtml(notification.severity || 'primary')} p-2">
                                <i class="mdi ${escapeHtml(notification.icon || 'mdi-information-outline')} mdi-20px"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="fw-medium">${escapeHtml(notification.title)}</div>
                                    <span class="badge bg-danger rounded-pill">${Number(notification.count || 0)}</span>
                                </div>
                                <small class="text-muted d-block">${escapeHtml(notification.subtitle)}</small>
                                <small class="text-muted">${escapeHtml(notification.time || '')}</small>
                            </div>
                        </div>
                    </a>
                </li>
            `).join('');
        }

        setBadge(summary.total_count);
        updateMenuBadges(summary);
    }

    async function fetchNotificationStatus() {
        if (stopped || document.hidden || inFlight) return;
        if (Date.now() < nextPollAt) { schedulePoll(); return; }
        inFlight = true;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(statusUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                cache: 'no-store',
                signal: controller.signal
            });

            if (response.status === 401 || response.status === 403) stopped = true;
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const payload = await response.json();
            if (!payload.success) throw new Error('Ringkasan tidak tersedia');
            failures = 0;
            const summary = payload.data || {};
            const signature = JSON.stringify({
                total: summary.total_count,
                counts: summary.menu_counts,
                items: (summary.notifications || []).map(n => [n.key, n.fingerprint])
            });

            if (signature !== lastSignature) {
                const nextTotal = Number(summary.total_count || 0);
                if (lastSignature !== '' && nextTotal > lastTotal) {
                    announceNewNotification(summary);
                }
                renderNotifications(summary);
                lastSignature = signature;
                lastTotal = nextTotal;
            }
        } catch (error) {
            failures = Math.min(failures + 1, 4);
            console.warn('Gagal mengambil status notifikasi:', error);
        } finally {
            clearTimeout(timeout);
            inFlight = false;
            nextPollAt = Date.now() + Math.min(120000, 30000 * Math.pow(2, failures)) + Math.random() * 3000;
            schedulePoll();
        }
    }

    document.addEventListener('click', async function (event) {
        const link = event.target.closest('#notification-items a.dropdown-item');
        if (!link) return;

        const item = link.closest('[data-notification-key]');
        const key = item?.dataset.notificationKey;
        const targetUrl = link.href;
        // The server open endpoint already records the read before redirecting.
        if (targetUrl.includes('/notifications/open/')) return;
        if (!key || !targetUrl || targetUrl === window.location.href) return;

        event.preventDefault();

        try {
            await fetch(@json($notificationMarkReadUrl), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ key }),
                keepalive: true
            });
        } catch (error) {
            console.warn('Gagal menandai notifikasi sebagai sudah dibaca:', error);
        }

        window.location.assign(targetUrl);
    });

    function startPolling() {
        // Initial values are already rendered by the server.
        nextPollAt = Date.now() + 30000 + Math.random() * 3000;
        schedulePoll();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startPolling);
    } else {
        startPolling();
    }

    window.addEventListener('focus', schedulePoll);
    document.addEventListener('visibilitychange', function () {
        schedulePoll();
    });
})();
</script>
@endpush
