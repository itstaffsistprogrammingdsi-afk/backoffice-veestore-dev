{{-- resources/views/layouts/sections/styles.blade.php --}}
<!-- BEGIN: Theme CSS-->

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="{{ asset(mix('assets/vendor/fonts/materialdesignicons.css')) }}" />

<!-- Core Vendor CSS -->
<link rel="stylesheet" href="{{ asset(mix('assets/vendor/libs/node-waves/node-waves.css')) }}" />
<link rel="stylesheet" href="{{ asset(mix('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css')) }}" />

<!-- Vendor Styles (per-page plugins: Select2, Flatpickr, dll) -->
@yield('vendor-style')
@stack('vendor-style')

<!-- Core Theme CSS -->
<link rel="stylesheet" href="{{ asset(mix('assets/vendor/css/core.css')) }}" />
<link rel="stylesheet" href="{{ asset(mix('assets/vendor/css/theme-default.css')) }}" />
<link rel="stylesheet" href="{{ asset(mix('assets/css/demo.css')) }}" />

<!-- Page Styles -->
@yield('page-style')
@stack('page-style')

<!-- Shared index-page theme -->
@include('layouts/sections/indexTheme')

<!-- Shared VeeStore backoffice visual system -->
@include('layouts/sections/globalTheme')

<!-- Helpers kecil -->
<style>
/* Pastikan plugin seperti Select2 mengikuti lebar input Materio */
.select2-container {
    width: 100% !important;
}
</style>
<!-- END: Theme CSS-->
