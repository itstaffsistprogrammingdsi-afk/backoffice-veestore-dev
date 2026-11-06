@php
use Illuminate\Support\Str;

// Ambil array menu dari provider/komposer view
$menus = is_array($menuData[0]->menu ?? null) ? $menuData[0]->menu : [];

/**
 * Penentu aktif (rekursif).
 * Cocokkan:
 *  1) route name via slug (mendukung string/array & prefix match)
 *  2) path/url (mendukung exact & prefix dengan Request::is)
 *  3) propagasi ke parent bila child aktif
 */
$matchItem = function ($item) use (&$matchItem) {
    $currentRoute = Route::currentRouteName();
    $currentPath  = trim(request()->path(), '/');

    // 1) slug sebagai route name (string/array)
    if (isset($item->slug)) {
        $slugs = is_array($item->slug) ? $item->slug : [$item->slug];
        foreach ($slugs as $slug) {
            if ($slug === null || $slug === '') continue;
            if ($currentRoute === $slug || Str::startsWith($currentRoute, $slug)) {
                return true;
            }
        }
    }

    // 2) url/path
    if (isset($item->url) && $item->url !== null) {
        $pattern = trim($item->url, '/'); // contoh: 'master-data/products'
        if ($pattern === '') {
            if ($currentPath === '') return true; // root '/'
        } else {
            // Beberapa menu (misalnya CRM dashboard) hanya boleh aktif pada
            // URL persisnya, bukan pada halaman anak milik CRM yang lain.
            $exactUrlMatch = !empty($item->exact_url_match);
            if (request()->is($pattern) || (!$exactUrlMatch && request()->is($pattern.'/*'))) return true;
        }
    }

    // 3) cek anak (rekursif)
    if (isset($item->submenu) && is_iterable($item->submenu)) {
        foreach ($item->submenu as $child) {
            if ($matchItem($child)) return true;
        }
    }
    return false;
};

$canSeeItem = function ($item) use (&$canSeeItem) {
    $user = auth()->user();

    if (!$user) {
        return false;
    }

    if (isset($item->submenu) && is_iterable($item->submenu)) {
        foreach ($item->submenu as $child) {
            if ($canSeeItem($child)) {
                return true;
            }
        }
    }

    $slug = (string) ($item->slug ?? '');

    return $slug !== '' && $user->can((string) ($item->permission ?? "view {$slug}"));
};
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  <div class="app-brand demo">
    <a href="{{ url('/') }}" class="app-brand-link">
      <span class="app-brand-logo demo me-1">
        <img src="{{ asset('assets/img/logo.png') }}"
             alt="Logo Master Data & Report"
             class="app-brand-logo-image">
      </span>
      <span class="app-brand-text demo menu-text fw-semibold ms-2">Master Data & Report</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="mdi menu-toggle-icon d-xl-block align-middle mdi-20px"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach ($menus as $menu)
      @continue(!$canSeeItem($menu))
      {{-- Header --}}
      @if (isset($menu->menuHeader))
        <li class="menu-header fw-medium mt-4">
          <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
        </li>
      @else
        @php
          $hasSub      = isset($menu->submenu) && is_iterable($menu->submenu);
          $showSubmenu = $hasSub && empty($menu->hide_submenu);
          $isActive    = $matchItem($menu);
          $isLandingPage = !empty($menu->landing_page);
          $isLandingRoute = $isLandingPage
            && isset($menu->url)
            && request()->is(trim($menu->url, '/'));
          $showBackToLanding = $isLandingPage
            && !empty($menu->hide_submenu)
            && $isActive
            && !$isLandingRoute;
          $activeClass = $isActive ? (($showSubmenu || $showBackToLanding) && !$isLandingRoute ? 'active open' : 'active') : '';
          $linkClass   = $showSubmenu && !$isLandingPage ? 'menu-link menu-toggle' : 'menu-link';
          $href        = isset($menu->url) ? url($menu->url) : 'javascript:void(0);';
        @endphp

        <li class="menu-item {{ $activeClass }}">
          <a href="{{ $href }}"
             class="{{ $linkClass }}"
             @if(isset($menu->slug)) data-menu-slug="{{ $menu->slug }}" @endif
             @if (!empty($menu->target)) target="_blank" @endif>
            @isset($menu->icon)<i class="{{ $menu->icon }}"></i>@endisset
            <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
            @isset($menu->badge)
              <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
            @endisset
            @php
              $menuSlug = (string) ($menu->slug ?? '');
              $menuNotificationCount = $menuSlug === 'branch-operations'
                ? (int) ($branchOperationsNotificationCount ?? 0)
                : (int) ($menuNotificationCounts[$menuSlug] ?? 0);
            @endphp
            @if($menuSlug !== '' && ($menuSlug === 'branch-operations' || array_key_exists($menuSlug, $menuNotificationCounts ?? [])))
              <span class="badge bg-danger rounded-pill ms-auto js-menu-notification-badge {{ $menuNotificationCount > 0 ? '' : 'd-none' }}"
                    data-notification-menu="{{ $menuSlug }}">
                {{ $menuNotificationCount }}
              </span>
            @endif
          </a>

          @if($showSubmenu)
            @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu, 'matchItem' => $matchItem, 'canSeeItem' => $canSeeItem])
          @endif

          @if($showBackToLanding)
            <ul class="menu-sub">
              <li class="menu-item">
                <a href="{{ url($menu->url) }}" class="menu-link">
                  <i class="menu-icon tf-icons mdi mdi-arrow-left"></i>
                  <div>Semua Monitoring</div>
                </a>
              </li>
            </ul>
          @endif
        </li>
      @endif
    @endforeach
  </ul>
</aside>
