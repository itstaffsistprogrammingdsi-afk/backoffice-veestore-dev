<style id="veestore-global-theme">
  :root {
    --vee-blue: #1d72c4;
    --vee-blue-dark: #155d9e;
    --vee-blue-hover: #104a80;
    --vee-blue-soft: #eaf4ff;
    --vee-forest: #233c34;
    --vee-forest-dark: #172b24;
    --vee-mint: #39c982;
    --vee-mint-soft: #d7f5e2;
    --vee-sand: #ffd84a;
    --vee-hero-bg: #d7f5e2;
    --vee-hero-text: #18372e;
    --vee-hero-muted: #35594b;
    --vee-ink: #1d2a35;
    --vee-canvas: #ffffff;
    --vee-surface: #ffffff;
    --vee-surface-2: #f5f9ff;
    --vee-border: #d9e4ef;
    --vee-muted: #526474;
    --vee-success: #176344;
    --vee-success-soft: #e2f7e9;
    --vee-info: #135e78;
    --vee-info-soft: #e3f4fa;
    --vee-warning: #795000;
    --vee-warning-soft: #fff3ca;
    --vee-danger: #a3352d;
    --vee-danger-soft: #ffe8e4;
    --vee-white: #ffffff;

    --bs-primary: var(--vee-blue);
    --bs-primary-rgb: 29, 114, 196;
    --bs-secondary: var(--vee-muted);
    --bs-secondary-rgb: 82, 100, 116;
    --bs-success: var(--vee-success);
    --bs-success-rgb: 23, 99, 68;
    --bs-info: var(--vee-info);
    --bs-info-rgb: 19, 94, 120;
    --bs-warning: var(--vee-warning);
    --bs-warning-rgb: 121, 80, 0;
    --bs-danger: var(--vee-danger);
    --bs-danger-rgb: 163, 53, 45;
    --bs-body-color: var(--vee-ink);
    --bs-body-bg: var(--vee-canvas);

    --hq-primary: var(--vee-blue);
    --hq-primary-dark: var(--vee-blue-dark);
    --hq-primary-soft: var(--vee-blue-soft);
    --hq-info: var(--vee-info);
    --hq-info-soft: var(--vee-info-soft);
    --hq-success: var(--vee-success);
    --hq-success-soft: var(--vee-success-soft);
    --hq-warning: var(--vee-warning);
    --hq-warning-soft: var(--vee-warning-soft);
    --hq-danger: var(--vee-danger);
    --hq-danger-soft: var(--vee-danger-soft);
    --hq-purple: var(--vee-blue);
    --hq-dark: var(--vee-ink);
    --hq-muted: var(--vee-muted);
    --hq-body: var(--vee-canvas);

    --beauty-pink: var(--vee-blue);
    --beauty-rose: var(--vee-blue-dark);
    --beauty-soft: var(--vee-blue-soft);
    --beauty-dark: var(--vee-ink);
  }

  html,
  body,
  .content-wrapper {
    color: var(--vee-ink);
    background-color: var(--vee-canvas) !important;
  }

  body {
    background-image: none !important;
  }

  a { color: var(--vee-blue-dark); }
  a:hover { color: var(--vee-blue-hover); }
  h1, h2, h3, h4, h5, h6, .text-heading { color: var(--vee-ink); }
  .text-muted { color: var(--vee-muted) !important; }

  .layout-menu.bg-menu-theme {
    color: rgba(255, 255, 255, .82);
    background: var(--vee-forest-dark) !important;
    box-shadow: none;
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
    color: rgba(255, 255, 255, .78) !important;
  }

  .layout-menu .menu-link:hover {
    color: var(--vee-white) !important;
    background: rgba(29, 114, 196, .28) !important;
  }

  .layout-menu .menu-item.active > .menu-link {
    color: var(--vee-white) !important;
    background: var(--vee-blue-dark) !important;
    box-shadow: none !important;
  }

  .layout-menu .menu-sub .menu-item.active > .menu-link {
    color: #b7dcff !important;
    background: rgba(29, 114, 196, .2) !important;
    box-shadow: none !important;
  }

  .layout-navbar,
  .navbar-detached {
    border: 1px solid var(--vee-border) !important;
    border-radius: .7rem !important;
    background: var(--vee-surface) !important;
    box-shadow: none !important;
    backdrop-filter: none;
    overflow: visible !important;
  }

  #layout-navbar::before { display: none !important; }

  .sidebar-floating-toggle {
    border-color: var(--vee-blue-dark) !important;
    background: var(--vee-blue-dark) !important;
    box-shadow: none !important;
  }

  @media (min-width: 1200px) {
    html.layout-menu-collapsed .sidebar-floating-toggle { left: 16px !important; }
  }

  @media (max-width: 767.98px) {
    .layout-navbar,
    .navbar-detached { border-radius: .7rem !important; }
  }

  .navbar-menu-search .menu-search-input-wrap,
  .input-group-text {
    border-color: var(--vee-border) !important;
    background-color: var(--vee-surface) !important;
  }

  .navbar-menu-search .menu-search-input-wrap:focus-within {
    border-color: var(--vee-blue) !important;
    box-shadow: 0 0 0 .18rem rgba(29, 114, 196, .2) !important;
  }

  .card,
  .list-group-item {
    border-color: var(--vee-border) !important;
    border-radius: .7rem;
    background-color: var(--vee-surface);
    box-shadow: none !important;
  }

  .modal-content,
  .dropdown-menu,
  .offcanvas {
    border-color: var(--vee-border) !important;
    background-color: var(--vee-surface);
    box-shadow: 0 .5rem 1.2rem rgba(26, 43, 35, .16);
  }

  .card-header,
  .card-footer,
  .modal-header,
  .modal-footer { border-color: var(--vee-border) !important; }

  .btn-primary,
  .btn-beauty {
    color: #fff !important;
    border-color: var(--vee-blue-dark) !important;
    background: var(--vee-blue-dark) !important;
    box-shadow: none !important;
  }
  .btn-primary:hover,
  .btn-beauty:hover {
    color: #fff !important;
    border-color: var(--vee-blue-hover) !important;
    background: var(--vee-blue-hover) !important;
  }

  .btn-success {
    color: #fff !important;
    border-color: var(--vee-success) !important;
    background: var(--vee-success) !important;
    box-shadow: none !important;
  }
  .btn-success:hover { color: #fff !important; border-color: #19523d !important; background: #19523d !important; }

  .btn-info {
    color: #fff !important;
    border-color: var(--vee-info) !important;
    background: var(--vee-info) !important;
    box-shadow: none !important;
  }
  .btn-info:hover { color: #fff !important; border-color: #1a4857 !important; background: #1a4857 !important; }

  .btn-warning {
    color: #fff !important;
    border-color: var(--vee-warning) !important;
    background: var(--vee-warning) !important;
    box-shadow: none !important;
  }
  .btn-warning:hover { color: #fff !important; border-color: #674506 !important; background: #674506 !important; }

  .btn-danger {
    color: #fff !important;
    border-color: var(--vee-danger) !important;
    background: var(--vee-danger) !important;
    box-shadow: none !important;
  }
  .btn-danger:hover { color: #fff !important; border-color: #7b211c !important; background: #7b211c !important; }

  .btn-outline-primary,
  .btn-outline-beauty { color: var(--vee-blue-dark) !important; border-color: var(--vee-blue) !important; background: var(--vee-surface) !important; }
  .btn-outline-success { color: var(--vee-success) !important; border-color: var(--vee-success) !important; background: var(--vee-surface) !important; }
  .btn-outline-info { color: var(--vee-info) !important; border-color: var(--vee-info) !important; background: var(--vee-surface) !important; }
  .btn-outline-warning { color: var(--vee-warning) !important; border-color: var(--vee-warning) !important; background: var(--vee-surface) !important; }
  .btn-outline-danger { color: var(--vee-danger) !important; border-color: var(--vee-danger) !important; background: var(--vee-surface) !important; }
  .btn-outline-secondary { color: var(--vee-muted) !important; border-color: #89968f !important; background: var(--vee-surface) !important; }

  .btn-outline-primary:hover,
  .btn-outline-beauty:hover { color: var(--vee-blue-dark) !important; background: var(--vee-blue-soft) !important; }
  .btn-outline-success:hover { color: var(--vee-success) !important; background: var(--vee-success-soft) !important; }
  .btn-outline-info:hover { color: var(--vee-info) !important; background: var(--vee-info-soft) !important; }
  .btn-outline-warning:hover { color: var(--vee-warning) !important; background: var(--vee-warning-soft) !important; }
  .btn-outline-danger:hover { color: var(--vee-danger) !important; background: var(--vee-danger-soft) !important; }
  .btn-outline-secondary:hover { color: var(--vee-blue-dark) !important; border-color: var(--vee-blue) !important; background: var(--vee-blue-soft) !important; }

  .btn-dark,
  .bg-dark { background: var(--vee-forest-dark) !important; }

  .form-control,
  .form-select,
  .select2-selection,
  .form-check-input { border-color: var(--vee-border) !important; }

  .form-control:focus,
  .form-select:focus,
  .select2-container--focus .select2-selection {
    border-color: var(--vee-blue-dark) !important;
    box-shadow: 0 0 0 .18rem rgba(29, 114, 196, .2) !important;
  }

  .form-check-input:checked,
  .form-check-input[type=checkbox]:indeterminate {
    border-color: var(--vee-blue-dark) !important;
    background-color: var(--vee-blue-dark) !important;
  }

  .table {
    --bs-table-striped-bg: rgba(29, 42, 53, .035);
    --bs-table-hover-bg: var(--vee-surface-2);
    --bs-table-hover-color: var(--vee-ink);
    color: var(--vee-ink);
  }
  .table thead th,
  .table-light th {
    color: var(--vee-ink) !important;
    border-color: var(--vee-border) !important;
    background: var(--vee-surface-2) !important;
  }
  .table tbody td { border-color: var(--vee-border) !important; }
  .table-hover tbody tr:hover > * { background: var(--vee-surface-2) !important; }

  .pagination .page-link { color: var(--vee-blue-dark) !important; border-color: var(--vee-border) !important; }
  .pagination .page-item.active .page-link { color: #fff !important; background: var(--vee-blue-dark) !important; border-color: var(--vee-blue-dark) !important; }

  .nav-pills .nav-link.active,
  .nav-tabs .nav-link.active {
    color: #fff !important;
    border-color: var(--vee-blue-dark) !important;
    background: var(--vee-blue-dark) !important;
  }

  .badge.bg-primary, .bg-label-primary, .badge-soft-primary { color: var(--vee-blue-dark) !important; background: var(--vee-blue-soft) !important; }
  .badge.bg-success, .bg-label-success, .badge-soft-success { color: var(--vee-success) !important; background: var(--vee-success-soft) !important; }
  .badge.bg-info, .bg-label-info, .badge-soft-info { color: var(--vee-info) !important; background: var(--vee-info-soft) !important; }
  .badge.bg-warning, .bg-label-warning, .badge-soft-warning { color: var(--vee-warning) !important; background: var(--vee-warning-soft) !important; }
  .badge.bg-danger, .bg-label-danger, .badge-soft-danger { color: var(--vee-danger) !important; background: var(--vee-danger-soft) !important; }
  .badge-soft-secondary { color: var(--vee-muted) !important; background: var(--vee-surface-2) !important; }

  .alert-success { color: var(--vee-success) !important; border-color: #c7e5d4 !important; background: var(--vee-success-soft) !important; }
  .alert-info { color: var(--vee-info) !important; border-color: #c9e0e7 !important; background: var(--vee-info-soft) !important; }
  .alert-warning { color: var(--vee-warning) !important; border-color: #ead9a8 !important; background: var(--vee-warning-soft) !important; }
  .alert-danger { color: var(--vee-danger) !important; border-color: #efc9cc !important; background: var(--vee-danger-soft) !important; }

  .progress-bar { background: var(--vee-blue) !important; }

  .dropdown-item:hover,
  .dropdown-item:focus,
  .dropdown-item.active { color: var(--vee-blue-dark) !important; background: var(--vee-blue-soft) !important; }

  .hq-hero,
  .monitor-hero,
  .veestore-index-theme [class*="-hero"] {
    color: var(--vee-hero-text) !important;
    background: var(--vee-hero-bg) !important;
    box-shadow: none !important;
  }

  .hq-hero h1, .hq-hero h2, .hq-hero h3, .hq-hero p,
  .monitor-hero h1, .monitor-hero h2, .monitor-hero h3, .monitor-hero p,
  .veestore-index-theme [class*="-hero"] h1,
  .veestore-index-theme [class*="-hero"] h2,
  .veestore-index-theme [class*="-hero"] h3,
  .veestore-index-theme [class*="-hero"] p {
    color: var(--vee-hero-text) !important;
  }

  .metric-card::before,
  [class*="summary-card"]::before { display: none !important; }

  .metric-icon,
  .summary-icon,
  .division-icon { color: var(--vee-blue-dark) !important; background: var(--vee-blue-soft) !important; }
  .metric-icon.success { color: var(--vee-success) !important; background: var(--vee-success-soft) !important; }
  .metric-icon.info { color: var(--vee-info) !important; background: var(--vee-info-soft) !important; }
  .metric-icon.warning { color: var(--vee-warning) !important; background: var(--vee-warning-soft) !important; }
  .metric-icon.danger { color: var(--vee-danger) !important; background: var(--vee-danger-soft) !important; }

  .beauty-page { background: var(--vee-canvas) !important; }
  .beauty-accent { background: var(--vee-blue) !important; }
  .beauty-card,
  .beauty-hero { border-color: var(--vee-border) !important; background-color: var(--vee-surface) !important; }

  :focus-visible { outline: 2px solid var(--vee-blue-dark); outline-offset: 2px; }
  button:not(:disabled),
  a[href],
  [role="button"]:not([aria-disabled="true"]) { cursor: pointer; }
  button:disabled,
  [aria-disabled="true"] { cursor: not-allowed; }

  ::selection { color: #fff; background: var(--vee-blue-dark); }
  ::-webkit-scrollbar-thumb { background: var(--vee-blue); }

  .layout-page .content-wrapper > .container,
  .layout-page .content-wrapper > .container-fluid { width: 100%; margin-inline: auto; }

  .content-wrapper .card,
  .content-wrapper .card-body,
  .content-wrapper .row > * { min-width: 0; }

  .content-wrapper .table-responsive { max-width: 100%; -webkit-overflow-scrolling: touch; }

  .content-wrapper img,
  .content-wrapper canvas,
  .content-wrapper svg { max-width: 100%; }

  @media (min-width: 1600px) {
    html { font-size: 15px; }
    .layout-page .content-wrapper > .container,
    .layout-page .content-wrapper > .container-fluid { max-width: 1920px; padding-inline: clamp(1.5rem, 2vw, 3rem); }
  }

  @media (min-width: 2200px) {
    html { font-size: 14px; }
    .layout-page .content-wrapper > .container,
    .layout-page .content-wrapper > .container-fluid { max-width: 2080px; padding-inline: clamp(2rem, 3vw, 4rem); }
  }

  @media (max-width: 575.98px) {
    .layout-page .content-wrapper > .container,
    .layout-page .content-wrapper > .container-fluid { padding-inline: 1rem; }
  }

  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
  }
</style>
