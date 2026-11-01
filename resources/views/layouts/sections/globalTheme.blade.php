<style id="veestore-global-theme">
  :root {
    --vee-pink: #ec4899;
    --vee-pink-dark: #be185d;
    --vee-pink-soft: #fce7f3;
    --vee-purple: #7c3aed;
    --vee-purple-dark: #581c87;
    --vee-purple-soft: #f3e8ff;
    --vee-black: #17111f;
    --vee-black-soft: #2a2033;
    --vee-white: #ffffff;
    --vee-canvas: #fbf8fc;
    --vee-border: #eadfec;
    --vee-muted: #75687d;

    --bs-primary: var(--vee-purple);
    --bs-primary-rgb: 124, 58, 237;
    --bs-secondary: var(--vee-black-soft);
    --bs-secondary-rgb: 42, 32, 51;
    --bs-success: var(--vee-purple);
    --bs-success-rgb: 124, 58, 237;
    --bs-info: var(--vee-pink);
    --bs-info-rgb: 236, 72, 153;
    --bs-warning: #d946ef;
    --bs-warning-rgb: 217, 70, 239;
    --bs-danger: var(--vee-pink-dark);
    --bs-danger-rgb: 190, 24, 93;
    --bs-body-color: var(--vee-black);
    --bs-body-bg: var(--vee-canvas);

    --hq-primary: var(--vee-purple);
    --hq-primary-dark: var(--vee-purple-dark);
    --hq-primary-soft: rgba(124, 58, 237, .12);
    --hq-info: var(--vee-pink);
    --hq-info-soft: rgba(236, 72, 153, .13);
    --hq-success: var(--vee-purple);
    --hq-success-soft: rgba(124, 58, 237, .12);
    --hq-warning: #d946ef;
    --hq-warning-soft: rgba(217, 70, 239, .13);
    --hq-danger: var(--vee-pink-dark);
    --hq-danger-soft: rgba(190, 24, 93, .12);
    --hq-purple: var(--vee-pink);
    --hq-dark: var(--vee-black);
    --hq-muted: var(--vee-muted);
    --hq-body: var(--vee-canvas);

    --beauty-pink: var(--vee-pink);
    --beauty-rose: var(--vee-purple);
    --beauty-soft: #fdf4ff;
    --beauty-dark: var(--vee-black);
  }

  html,
  body,
  .content-wrapper {
    color: var(--vee-black);
    background-color: var(--vee-canvas) !important;
  }

  body {
    background-image:
      radial-gradient(circle at 8% 0, rgba(236, 72, 153, .07), transparent 25%),
      radial-gradient(circle at 92% 4%, rgba(124, 58, 237, .07), transparent 27%) !important;
  }

  a { color: var(--vee-purple); }
  a:hover { color: var(--vee-pink-dark); }
  h1, h2, h3, h4, h5, h6, .text-heading { color: var(--vee-black); }
  .text-muted { color: var(--vee-muted) !important; }

  .layout-menu.bg-menu-theme {
    color: rgba(255, 255, 255, .78);
    background: linear-gradient(180deg, #120d18 0%, #211529 58%, #2b1330 100%) !important;
    box-shadow: .8rem 0 2.2rem rgba(23, 17, 31, .15);
  }

  .layout-menu .app-brand,
  .layout-menu .menu-inner-shadow {
    background: transparent !important;
  }

  .layout-menu .app-brand-text,
  .layout-menu .app-brand-logo,
  .layout-menu .menu-header-text {
    color: var(--vee-white) !important;
  }

  .layout-menu .app-brand-logo-image {
    display: block;
    width: 2rem;
    height: 2rem;
    object-fit: contain;
  }

  .layout-menu .menu-link,
  .layout-menu .menu-toggle-icon {
    color: rgba(255, 255, 255, .72) !important;
  }

  .layout-menu .menu-link:hover {
    color: var(--vee-white) !important;
    background: rgba(236, 72, 153, .12) !important;
  }

  .layout-menu .menu-item.active > .menu-link {
    color: var(--vee-white) !important;
    background: linear-gradient(135deg, var(--vee-pink), var(--vee-purple)) !important;
    box-shadow: 0 .55rem 1.25rem rgba(124, 58, 237, .28) !important;
  }

  .layout-menu .menu-sub .menu-item.active > .menu-link {
    color: #fbcfe8 !important;
    background: rgba(236, 72, 153, .13) !important;
    box-shadow: none !important;
  }

  .layout-navbar,
  .navbar-detached {
    border: 1px solid var(--vee-border) !important;
    border-radius: 999px !important;
    background: rgba(255, 255, 255, .94) !important;
    box-shadow:
      0 .75rem 2rem rgba(23, 17, 31, .08),
      inset 0 1px 0 rgba(255, 255, 255, .95) !important;
    backdrop-filter: blur(16px);
    overflow: visible !important;
  }

  #layout-navbar::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    border-radius: inherit;
    background: linear-gradient(90deg, rgba(236, 72, 153, .025), #fff 38%, rgba(124, 58, 237, .035));
    pointer-events: none;
  }

  .sidebar-floating-toggle {
    border-color: #fff !important;
    background: linear-gradient(135deg, var(--vee-pink), var(--vee-purple)) !important;
    box-shadow: 0 .45rem 1.1rem rgba(124, 58, 237, .30) !important;
  }

  @media (min-width: 1200px) {
    html.layout-menu-collapsed .sidebar-floating-toggle {
      left: 16px !important;
    }
  }

  @media (max-width: 767.98px) {
    .layout-navbar,
    .navbar-detached {
      border-radius: 1.1rem !important;
    }
  }

  .navbar-menu-search .menu-search-input-wrap,
  .input-group-text {
    border-color: var(--vee-border) !important;
    background-color: #fff !important;
  }

  .navbar-menu-search .menu-search-input-wrap:focus-within {
    border-color: var(--vee-pink) !important;
    box-shadow: 0 0 0 .2rem rgba(236, 72, 153, .13) !important;
  }

  .card,
  .modal-content,
  .dropdown-menu,
  .offcanvas,
  .list-group-item {
    border-color: var(--vee-border) !important;
    background-color: var(--vee-white);
    box-shadow: 0 .5rem 1.5rem rgba(23, 17, 31, .065);
  }

  .card-header,
  .card-footer,
  .modal-header,
  .modal-footer {
    border-color: var(--vee-border) !important;
  }

  .btn-primary,
  .btn-success,
  .btn-info,
  .btn-danger,
  .btn-warning,
  .btn-beauty {
    color: #fff !important;
    border-color: transparent !important;
    background: linear-gradient(135deg, var(--vee-pink), var(--vee-purple)) !important;
    box-shadow: 0 .45rem 1rem rgba(124, 58, 237, .22) !important;
  }

  .btn-primary:hover,
  .btn-success:hover,
  .btn-info:hover,
  .btn-danger:hover,
  .btn-warning:hover,
  .btn-beauty:hover {
    color: #fff !important;
    background: linear-gradient(135deg, var(--vee-purple), var(--vee-pink-dark)) !important;
    transform: translateY(-1px);
  }

  .btn-outline-primary,
  .btn-outline-success,
  .btn-outline-info,
  .btn-outline-danger,
  .btn-outline-warning,
  .btn-outline-secondary {
    color: var(--vee-purple) !important;
    border-color: #d8b4fe !important;
    background: #fff !important;
  }

  .btn-outline-primary:hover,
  .btn-outline-success:hover,
  .btn-outline-info:hover,
  .btn-outline-danger:hover,
  .btn-outline-warning:hover,
  .btn-outline-secondary:hover {
    color: #fff !important;
    border-color: transparent !important;
    background: linear-gradient(135deg, var(--vee-pink), var(--vee-purple)) !important;
  }

  .btn-dark,
  .bg-dark { background: var(--vee-black) !important; }

  .form-control,
  .form-select,
  .select2-selection,
  .form-check-input {
    border-color: var(--vee-border) !important;
  }

  .form-control:focus,
  .form-select:focus,
  .select2-container--focus .select2-selection {
    border-color: var(--vee-pink) !important;
    box-shadow: 0 0 0 .18rem rgba(236, 72, 153, .14) !important;
  }

  .form-check-input:checked,
  .form-check-input[type=checkbox]:indeterminate {
    border-color: var(--vee-purple) !important;
    background-color: var(--vee-purple) !important;
  }

  .table { --bs-table-striped-bg: rgba(124, 58, 237, .035); }
  .table thead th,
  .table-light th {
    color: var(--vee-black) !important;
    border-color: var(--vee-border) !important;
    background: linear-gradient(180deg, #fff, #faf5ff) !important;
  }
  .table tbody td { border-color: #f1e9f2 !important; }
  .table-hover tbody tr:hover > * { background: #fdf2f8 !important; }

  .pagination .page-link {
    color: var(--vee-purple) !important;
    border-color: var(--vee-border) !important;
  }
  .pagination .page-item.active .page-link {
    color: #fff !important;
    background: linear-gradient(135deg, var(--vee-pink), var(--vee-purple)) !important;
  }

  .nav-pills .nav-link.active,
  .nav-tabs .nav-link.active {
    color: #fff !important;
    border-color: transparent !important;
    background: linear-gradient(135deg, var(--vee-pink), var(--vee-purple)) !important;
  }

  .badge.bg-primary, .badge.bg-success, .badge.bg-info,
  .badge.bg-warning, .badge.bg-danger,
  .bg-label-primary, .bg-label-success, .bg-label-info,
  .bg-label-warning, .bg-label-danger {
    color: var(--vee-purple-dark) !important;
    background: var(--vee-purple-soft) !important;
  }

  .badge.bg-danger,
  .badge-soft-danger,
  .bg-label-danger {
    color: var(--vee-pink-dark) !important;
    background: var(--vee-pink-soft) !important;
  }

  .badge-soft-primary, .badge-soft-success, .badge-soft-info,
  .badge-soft-warning, .badge-soft-secondary {
    color: var(--vee-purple-dark) !important;
    background: var(--vee-purple-soft) !important;
  }
  .alert-success, .alert-info, .alert-warning, .alert-danger {
    color: var(--vee-black) !important;
    border-color: #e9d5ff !important;
    background: linear-gradient(135deg, #fdf2f8, #faf5ff) !important;
  }

  .progress-bar {
    background: linear-gradient(90deg, var(--vee-pink), var(--vee-purple)) !important;
  }

  .dropdown-item:hover,
  .dropdown-item:focus,
  .dropdown-item.active {
    color: var(--vee-purple-dark) !important;
    background: var(--vee-purple-soft) !important;
  }

  .hq-hero,
  .monitor-hero,
  .veestore-index-theme [class*="-hero"] {
    color: #fff !important;
    background:
      radial-gradient(circle at 15% 18%, rgba(255,255,255,.22), transparent 28%),
      linear-gradient(135deg, var(--vee-black) 0%, var(--vee-purple) 54%, var(--vee-pink) 115%) !important;
    box-shadow: 0 1rem 2.3rem rgba(88, 28, 135, .20) !important;
  }

  .metric-card::before,
  [class*="summary-card"]::before {
    background: linear-gradient(90deg, var(--vee-pink), var(--vee-purple)) !important;
  }

  .metric-icon,
  .metric-icon.success,
  .metric-icon.info,
  .metric-icon.warning,
  .metric-icon.danger,
  .summary-icon,
  .division-icon {
    color: #fff !important;
    background: linear-gradient(135deg, var(--vee-pink), var(--vee-purple)) !important;
  }

  .beauty-page {
    background:
      radial-gradient(900px 520px at 8% 8%, rgba(236, 72, 153, .18), transparent 58%),
      radial-gradient(800px 480px at 92% 12%, rgba(124, 58, 237, .18), transparent 56%),
      linear-gradient(135deg, #fff 0%, #fdf4ff 46%, #fff 100%) !important;
  }

  .beauty-accent {
    background: linear-gradient(90deg, var(--vee-pink), var(--vee-purple), var(--vee-black)) !important;
  }

  .beauty-card,
  .beauty-hero {
    border-color: #ead3eb !important;
    background-color: rgba(255, 255, 255, .9) !important;
  }

  ::selection { color: #fff; background: var(--vee-pink); }
  ::-webkit-scrollbar-thumb { background: linear-gradient(var(--vee-pink), var(--vee-purple)); }

  /*
   * Large-display layout
   *
   * A TV/4K screen has considerably more CSS pixels than a laptop, so pages
   * that opt in to `container-fluid` can otherwise look disproportionately
   * large and spread out.  Keep the reading area at a useful maximum width,
   * while retaining the normal Bootstrap behaviour on laptops and tablets.
   */
  .layout-page .content-wrapper > .container,
  .layout-page .content-wrapper > .container-fluid {
    width: 100%;
    margin-inline: auto;
  }

  .content-wrapper .card,
  .content-wrapper .card-body,
  .content-wrapper .row > * {
    min-width: 0;
  }

  .content-wrapper .table-responsive {
    max-width: 100%;
    -webkit-overflow-scrolling: touch;
  }

  .content-wrapper img,
  .content-wrapper canvas,
  .content-wrapper svg {
    max-width: 100%;
  }

  @media (min-width: 1600px) {
    /* Prevent the interface from feeling zoomed-in on wide desktop/TV views. */
    html { font-size: 15px; }

    .layout-page .content-wrapper > .container,
    .layout-page .content-wrapper > .container-fluid {
      max-width: 1920px;
      padding-inline: clamp(1.5rem, 2vw, 3rem);
    }
  }

  @media (min-width: 2200px) {
    /* 4K and larger: preserve comfortable information density. */
    html { font-size: 14px; }

    .layout-page .content-wrapper > .container,
    .layout-page .content-wrapper > .container-fluid {
      max-width: 2080px;
      padding-inline: clamp(2rem, 3vw, 4rem);
    }
  }

  @media (max-width: 575.98px) {
    .layout-page .content-wrapper > .container,
    .layout-page .content-wrapper > .container-fluid {
      padding-inline: 1rem;
    }
  }
</style>
