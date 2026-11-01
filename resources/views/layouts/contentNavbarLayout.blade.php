@extends('layouts/commonMaster' )

@php
/* Display elements */
$contentNavbar = true;
$containerNav = ($containerNav ?? 'container-xxl');
$isNavbar = ($isNavbar ?? true);
$isMenu = ($isMenu ?? true);
$isFlex = ($isFlex ?? false);
$isFooter = ($isFooter ?? true);
$isIndexPage = request()->route()?->getActionMethod() === 'index';

/* HTML Classes */
$navbarDetached = 'navbar-detached';

/* Content classes */
$container = ($container ?? 'container-xxl');

/* Tautan kembali ke kategori Monitoring untuk setiap halaman laporan. */
$monitoringBackLink = null;

try {
  $menuPath = resource_path('menu/verticalMenu.json');
  $menuPayload = is_file($menuPath) ? json_decode(file_get_contents($menuPath), true) : [];
  $monitoringMenu = collect($menuPayload['menu'] ?? [])
    ->first(fn (array $item) => ($item['slug'] ?? null) === 'monitoring');
  $currentPath = trim(request()->path(), '/');

  foreach (($monitoringMenu['submenu'] ?? []) as $group) {
    foreach (($group['submenu'] ?? []) as $item) {
      $itemPath = trim($item['url'] ?? '', '/');

      if ($itemPath !== '' && ($currentPath === $itemPath || str_starts_with($currentPath, $itemPath . '/'))) {
        $monitoringBackLink = [
          'url' => url('monitoring') . '?' . http_build_query(['category' => $group['slug'] ?? '']),
          'label' => $group['name'] ?? 'Monitoring',
        ];
        break 2;
      }
    }
  }
} catch (\Throwable $e) {
  // Halaman tetap dapat dirender ketika konfigurasi menu belum tersedia.
}

@endphp

@section('layoutContent')
<div class="layout-wrapper layout-content-navbar {{ $isMenu ? '' : 'layout-without-menu' }} {{ $isIndexPage ? 'veestore-index-theme' : '' }}">
  <div class="layout-container">

    @if ($isMenu)
    @include('layouts/sections/menu/verticalMenu')
    <button type="button"
            id="desktopSidebarToggle"
            class="layout-menu-toggle sidebar-floating-toggle d-none d-xl-inline-flex"
            title="Tutup sidebar"
            aria-label="Tutup sidebar"
            aria-expanded="true">
      <i class="mdi mdi-chevron-left mdi-24px" aria-hidden="true"></i>
      <span class="visually-hidden sidebar-toggle-text">Tutup sidebar</span>
    </button>
    <script>
      // Desktop/mobile fallback: do not depend on the theme's cached JS bundle
      // for the primary sidebar control.
      (function () {
        function setSidebarState(collapsed) {
          var root = document.documentElement;
          var button = document.getElementById('desktopSidebarToggle');
          root.classList.toggle('layout-menu-collapsed', collapsed);

          if (button) {
            button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            button.title = collapsed ? 'Buka sidebar' : 'Tutup sidebar';
            var icon = button.querySelector('i');
            if (icon) {
              icon.classList.toggle('mdi-chevron-left', !collapsed);
              icon.classList.toggle('mdi-chevron-right', collapsed);
            }
          }
        }

        document.addEventListener('DOMContentLoaded', function () {
          var button = document.getElementById('desktopSidebarToggle');
          if (!button) return;

          setSidebarState(localStorage.getItem('veestore.sidebar.collapsed') === 'true');

          // Capture phase prevents a stale theme bundle from handling the same click twice.
          document.addEventListener('click', function (event) {
            var toggle = event.target.closest('.layout-menu-toggle');
            if (!toggle) return;

            event.preventDefault();
            event.stopImmediatePropagation();

            // On tablet/mobile the menu is an off-canvas drawer.
            if (window.innerWidth < 1200) {
              document.documentElement.classList.toggle(
                'layout-menu-expanded',
                !document.documentElement.classList.contains('layout-menu-expanded')
              );
              return;
            }

            var collapsed = !document.documentElement.classList.contains('layout-menu-collapsed');
            setSidebarState(collapsed);
            localStorage.setItem('veestore.sidebar.collapsed', String(collapsed));
          }, true);
        });
      })();
    </script>
    @endif


    <!-- Layout page -->
    <div class="layout-page">
      <!-- BEGIN: Navbar-->
      @if ($isNavbar)
      @include('layouts/sections/navbar/navbar')
      @endif
      <!-- END: Navbar-->


      <!-- Content wrapper -->
      <div class="content-wrapper">

        <!-- Content -->
        @if ($isFlex)
        <div class="{{$container}} d-flex align-items-stretch flex-grow-1 p-0">
          @else
          <div class="{{$container}} flex-grow-1 container-p-y">
            @endif

            @if($monitoringBackLink)
              <div class="d-flex justify-content-end mb-2">
                <a href="{{ $monitoringBackLink['url'] }}" class="btn btn-outline-primary btn-sm">
                  <i class="mdi mdi-arrow-left me-1"></i>
                  Kembali ke {{ $monitoringBackLink['label'] }}
                </a>
              </div>
            @endif

            @yield('content')

          </div>
          <!-- / Content -->

          <!-- Footer -->
          @if ($isFooter)
          @include('layouts/sections/footer/footer')
          @endif
          <!-- / Footer -->
          <div class="content-backdrop fade"></div>
        </div>
        <!--/ Content wrapper -->
      </div>
      <!-- / Layout page -->
    </div>

    @if ($isMenu)
    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>
    @endif
    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>
  </div>
  <!-- / Layout wrapper -->
  @endsection
