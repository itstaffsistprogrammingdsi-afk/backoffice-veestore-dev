<style>
/* Tema bersama untuk halaman daftar legacy Backoffice. */
.veestore-index-theme {
    --index-primary: var(--vee-blue);
    --index-primary-dark: var(--vee-blue-dark);
    --index-primary-soft: var(--vee-blue-soft);
    --index-text: var(--vee-ink);
    --index-muted: var(--vee-muted);
    --index-border: var(--vee-border);
    --index-surface: var(--vee-surface);
    --index-background: var(--vee-canvas);
    --index-radius: .65rem;
}

.veestore-index-theme .content-wrapper {
    background: var(--index-background);
}

.veestore-index-theme .content-wrapper > .container-xxl > .container-xxl {
    width: 100%;
    max-width: none;
    padding: 0;
}

.veestore-index-theme .container-p-y {
    padding-top: 1.5rem !important;
    padding-bottom: 1.75rem !important;
}

.veestore-index-theme h4.py-3,
.veestore-index-theme .container-p-y > h4:first-child {
    margin: 0 0 1.25rem !important;
    padding: 1rem 1.25rem !important;
    border: 1px solid var(--vee-hero-bg);
    border-radius: var(--index-radius);
    background: var(--vee-hero-bg);
    color: var(--vee-hero-text);
    font-size: 1.3rem;
    font-weight: 700;
}

.veestore-index-theme h4 .text-muted,
.veestore-index-theme h4 .fw-light {
    color: var(--vee-hero-muted) !important;
    font-weight: 500 !important;
}

.veestore-index-theme .card {
    border: 1px solid var(--index-border);
    border-radius: var(--index-radius);
    background: var(--index-surface);
    box-shadow: none !important;
}

.veestore-index-theme .card::before {
    display: none !important;
}

.veestore-index-theme .card-header {
    min-height: 60px;
    padding: .9rem 1.1rem;
    border-bottom: 1px solid var(--index-border);
    border-radius: var(--index-radius) var(--index-radius) 0 0;
    background: var(--index-surface);
    color: var(--index-text);
    font-weight: 700;
}

.veestore-index-theme .card-header h5,
.veestore-index-theme h5.card-header {
    color: var(--index-text);
    font-weight: 700;
}

.veestore-index-theme .card-body {
    padding: 1.1rem;
}

.veestore-index-theme .card-footer {
    min-height: 54px;
    padding: .75rem 1.1rem;
    border-top: 1px solid var(--index-border);
    border-radius: 0 0 var(--index-radius) var(--index-radius);
    background: var(--index-surface);
}

.veestore-index-theme .filter-box,
.veestore-index-theme .filter-panel,
.veestore-index-theme [class*="filter-box"],
.veestore-index-theme [class*="filter-panel"] {
    border: 1px solid var(--index-border) !important;
    border-radius: var(--index-radius) !important;
    background: var(--vee-surface-2) !important;
}

.veestore-index-theme .form-label {
    margin-bottom: .4rem;
    color: var(--index-muted);
    font-size: .8rem;
    font-weight: 700;
}

.veestore-index-theme .form-control,
.veestore-index-theme .form-select,
.veestore-index-theme .select2-selection {
    min-height: 42px;
    border-color: var(--index-border);
    border-radius: .45rem;
    background-color: var(--index-surface);
}

.veestore-index-theme .form-control:focus,
.veestore-index-theme .form-select:focus {
    border-color: var(--index-primary-dark);
    box-shadow: 0 0 0 .18rem rgba(29, 114, 196, .2);
}

.veestore-index-theme .btn {
    min-height: 40px;
    border-radius: .45rem;
    font-weight: 600;
    box-shadow: none;
    transition: background-color .15s ease, border-color .15s ease, color .15s ease;
}

.veestore-index-theme .btn-sm {
    min-height: 34px;
}

.veestore-index-theme .btn-primary {
    border-color: var(--index-primary-dark);
    background: var(--index-primary-dark);
}

.veestore-index-theme .btn-primary:hover,
.veestore-index-theme .btn-primary:focus {
    border-color: var(--vee-blue-hover);
    background: var(--vee-blue-hover);
}

.veestore-index-theme .btn-outline-secondary {
    color: var(--index-muted);
    border-color: #8fa3b5;
    background: var(--index-surface);
}

.veestore-index-theme .btn-outline-secondary:hover {
    color: var(--index-primary-dark);
    border-color: var(--index-primary);
    background: var(--index-primary-soft);
}

.veestore-index-theme .table-responsive {
    border-radius: 0 0 var(--index-radius) var(--index-radius);
    scrollbar-color: #9db3c6 transparent;
    scrollbar-width: thin;
}

.veestore-index-theme .table {
    margin-bottom: 0;
    color: var(--index-text);
}

.veestore-index-theme .table > :not(caption) > * > * {
    padding: .8rem .9rem;
    border-bottom-color: var(--index-border);
    vertical-align: middle;
}

.veestore-index-theme .table thead th {
    color: var(--index-muted);
    border-top: 0;
    border-bottom: 1px solid var(--index-border);
    background: var(--vee-surface-2);
    font-size: .74rem;
    font-weight: 700;
    text-transform: uppercase;
    white-space: nowrap;
}

.veestore-index-theme .table tbody tr:last-child td {
    border-bottom: 0;
}

.veestore-index-theme .table tbody tr:only-child td[colspan] {
    height: 160px;
    padding: 2rem !important;
    color: var(--index-muted);
    font-size: .9rem;
    text-align: center;
}

.veestore-index-theme .table tbody tr:only-child td[colspan]::before {
    display: block;
    width: 48px;
    height: 48px;
    margin: 0 auto .7rem;
    content: "";
    border: 1px solid var(--index-border);
    border-radius: .55rem;
    background-color: var(--index-primary-soft);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='28' viewBox='0 0 24 24' fill='none' stroke='%23155d9e' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 7.5h18v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/%3E%3Cpath d='M8 7.5V5a4 4 0 0 1 8 0v2.5'/%3E%3Cpath d='M9 13h6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
}

.veestore-index-theme .badge,
.veestore-index-theme [class*="status-pill"] {
    border-radius: .35rem;
    font-weight: 700;
}

.veestore-index-theme .alert {
    border: 1px solid var(--index-border);
    border-radius: .55rem;
    box-shadow: none;
}

.veestore-index-theme .dropdown-menu {
    padding: .4rem;
    border: 1px solid var(--index-border);
    border-radius: .55rem;
    box-shadow: 0 .65rem 1.5rem rgba(25, 44, 62, .14);
}

.veestore-index-theme .dropdown-item {
    padding: .6rem .75rem;
    border-radius: .35rem;
}

.veestore-index-theme .pagination {
    margin-bottom: 0;
    gap: .25rem;
}

.veestore-index-theme .page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    border: 1px solid var(--index-border);
    border-radius: .4rem !important;
    color: var(--index-muted);
    background: var(--index-surface);
}

.veestore-index-theme .page-item.active .page-link {
    color: #fff;
    border-color: var(--index-primary-dark);
    background: var(--index-primary-dark);
}

.veestore-index-theme .modal-content {
    overflow: hidden;
    border: 1px solid var(--index-border);
    border-radius: .65rem;
    box-shadow: 0 1rem 2.5rem rgba(25, 44, 62, .2);
}

.veestore-index-theme .modal-header,
.veestore-index-theme .modal-footer {
    padding: .9rem 1.1rem;
    border-color: var(--index-border);
    background: var(--index-surface);
}

.veestore-index-theme .modal-title {
    color: var(--index-text);
    font-weight: 700;
}

.veestore-index-theme .modal-body {
    padding: 1.1rem;
}

.veestore-index-theme .text-muted {
    color: var(--index-muted) !important;
}

@media (max-width: 991.98px) {
    .veestore-index-theme .card-header {
        align-items: stretch !important;
    }

    .veestore-index-theme .card-header form,
    .veestore-index-theme .card-header .d-flex {
        max-width: 100%;
    }
}

@media (max-width: 575.98px) {
    .veestore-index-theme .container-p-y {
        padding-top: 1rem !important;
    }

    .veestore-index-theme h4.py-3,
    .veestore-index-theme .container-p-y > h4:first-child {
        padding: .9rem 1rem !important;
        font-size: 1.15rem;
    }

    .veestore-index-theme .card-header,
    .veestore-index-theme .card-body,
    .veestore-index-theme .card-footer {
        padding-right: .9rem;
        padding-left: .9rem;
    }

    .veestore-index-theme .table > :not(caption) > * > * {
        padding: .7rem;
    }
}
</style>
