{{-- resources/views/layouts/sections/scripts.blade.php --}}

<!-- BEGIN: Vendor JS-->
<script src="{{ asset(mix('assets/vendor/libs/jquery/jquery.js')) }}"></script>
<script src="{{ asset(mix('assets/vendor/libs/popper/popper.js')) }}"></script>
<script src="{{ asset(mix('assets/vendor/js/bootstrap.js')) }}"></script>
{{-- Bootstrap framework bundle (Modal/Dropdown/Tooltip/Toast). Wajib ada:
     tanpa ini seluruh data-bs-toggle="modal" di semua menu mati. --}}
<script src="{{ asset('assets/vendor/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset(mix('assets/vendor/libs/node-waves/node-waves.js')) }}"></script>
<script src="{{ asset(mix('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')) }}"></script>
<script src="{{ asset(mix('assets/vendor/js/menu.js')) }}"></script>


{{-- Per-page vendor plugins (contoh: Select2, Flatpickr, dll) --}}
@yield('vendor-script')
@stack('vendor-script')
<!-- END: Vendor JS-->

<!-- BEGIN: Theme JS-->
<script src="{{ asset(mix('assets/js/main.js')) }}"></script>
<!-- END: Theme JS-->

<script>
  // Hindari modal berada di bawah backdrop akibat stacking context layout.
  document.querySelectorAll('.modal').forEach(function (modalElement) {
    if (modalElement.parentElement !== document.body) {
      document.body.appendChild(modalElement);
    }
  });
</script>

<!-- Pricing Modal JS (opsional) -->
@stack('pricing-script')

<!-- BEGIN: Page JS (inits khusus halaman) -->
@yield('page-script')
@stack('page-script')
<!-- END: Page JS-->
