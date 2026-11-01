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
  // Samakan seluruh ApexCharts dengan palette global VeeStore.
  (function () {
    window.VeeStoreChartPalette = ['#ec4899', '#7c3aed', '#17111f', '#be185d', '#a855f7', '#f9a8d4'];

    if (typeof window.ApexCharts !== 'function' || typeof Proxy === 'undefined') return;

    const OriginalApexCharts = window.ApexCharts;
    const palette = window.VeeStoreChartPalette;
    const colorMap = {
      '#5b5cf6': '#7c3aed', '#4f46e5': '#7c3aed', '#4338ca': '#581c87',
      '#6d28d9': '#7c3aed', '#8b5cf6': '#a855f7', '#696cff': '#7c3aed',
      '#06b6d4': '#ec4899', '#0284c7': '#be185d', '#2563eb': '#7c3aed',
      '#22c55e': '#7c3aed', '#16a34a': '#581c87', '#71dd37': '#7c3aed',
      '#f59e0b': '#ec4899', '#f97316': '#be185d', '#ef4444': '#be185d',
      '#dc2626': '#9d174d', '#ec4899': '#ec4899', '#db2777': '#be185d',
      '#14b8a6': '#a855f7', '#0f766e': '#581c87'
    };

    function remap(value, key) {
      if (Array.isArray(value)) {
        if (key === 'colors' || key === 'colorStops') {
          return value.map((item, index) => typeof item === 'string'
            ? palette[index % palette.length]
            : remap(item, key));
        }
        return value.map(item => remap(item));
      }

      if (value && typeof value === 'object') {
        Object.keys(value).forEach(childKey => {
          value[childKey] = remap(value[childKey], childKey);
        });
        return value;
      }

      if (typeof value === 'string') {
        return colorMap[value.toLowerCase()] || value;
      }

      return value;
    }

    window.ApexCharts = new Proxy(OriginalApexCharts, {
      construct(Target, args, NewTarget) {
        const options = remap(args[1] || {});
        if (!Array.isArray(options.colors) || options.colors.length === 0) {
          options.colors = palette;
        }
        return Reflect.construct(Target, [args[0], options], NewTarget);
      }
    });
  })();
</script>

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
