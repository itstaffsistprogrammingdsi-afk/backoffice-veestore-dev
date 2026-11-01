<style>
/* Tema bersama untuk seluruh halaman daftar/index Backoffice. */
.veestore-index-theme {
    --index-primary: #7367f0;
    --index-primary-dark: #5f54d9;
    --index-primary-soft: #f0edff;
    --index-text: #25324b;
    --index-muted: #718096;
    --index-border: #e5e9f2;
    --index-surface: #ffffff;
    --index-background: #f7f8fc;
    --index-radius: 16px;
    --index-shadow: 0 8px 28px rgba(32, 44, 69, .07);
}

.veestore-index-theme .content-wrapper {
    background:
        radial-gradient(circle at 95% 0, rgba(115, 103, 240, .07), transparent 24rem),
        var(--index-background);
}

/* Beberapa halaman lama memiliki container-xxl kedua di dalam layout. */
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
    position: relative;
    overflow: hidden;
    margin: 0 0 1.35rem !important;
    padding: 1.15rem 1.35rem !important;
    color: var(--index-text);
    font-size: 1.45rem;
    font-weight: 700;
    letter-spacing: -.015em;
    border: 1px solid #e5e2ff;
    border-radius: var(--index-radius);
    background:
        radial-gradient(circle at 88% 20%, rgba(115, 103, 240, .16), transparent 13rem),
        linear-gradient(135deg, #fff 10%, #f7f5ff 100%);
    box-shadow: 0 7px 24px rgba(56, 48, 118, .06);
}

.veestore-index-theme h4.py-3::after,
.veestore-index-theme .container-p-y > h4:first-child::after {
    position: absolute;
    top: 0;
    right: 1.5rem;
    width: 72px;
    height: 100%;
    content: "";
    opacity: .35;
    background:
        linear-gradient(135deg, transparent 45%, rgba(115, 103, 240, .17) 46%, rgba(115, 103, 240, .17) 54%, transparent 55%);
    pointer-events: none;
}

.veestore-index-theme h4 .text-muted,
.veestore-index-theme h4 .fw-light {
    color: #98a2b3 !important;
    font-weight: 500 !important;
}

.veestore-index-theme .card {
    position: relative;
    overflow: visible;
    border: 1px solid var(--index-border);
    border-radius: var(--index-radius);
    background: var(--index-surface);
    box-shadow: var(--index-shadow);
}

.veestore-index-theme .card:not(.monitor-metric)::before {
    position: absolute;
    z-index: 1;
    top: -1px;
    right: 24px;
    left: 24px;
    height: 3px;
    content: "";
    border-radius: 0 0 999px 999px;
    background: linear-gradient(90deg, transparent, rgba(115, 103, 240, .7), transparent);
}

.veestore-index-theme .card-header {
    min-height: 68px;
    padding: 1rem 1.25rem;
    color: var(--index-text);
    border-bottom: 1px solid var(--index-border);
    border-radius: var(--index-radius) var(--index-radius) 0 0;
    background: linear-gradient(180deg, #fff, #fdfdff);
    font-weight: 700;
}

.veestore-index-theme .card-header > :first-child {
    position: relative;
}

.veestore-index-theme .card-header h5,
.veestore-index-theme h5.card-header {
    color: var(--index-text);
    font-weight: 700;
}

.veestore-index-theme .card-body {
    padding: 1.25rem;
}

.veestore-index-theme .card-footer {
    min-height: 62px;
    padding: .85rem 1.25rem;
    border-top: 1px solid var(--index-border);
    border-radius: 0 0 var(--index-radius) var(--index-radius);
    background: #fff;
}

.veestore-index-theme .filter-box,
.veestore-index-theme .filter-panel,
.veestore-index-theme [class*="filter-box"],
.veestore-index-theme [class*="filter-panel"] {
    border: 1px solid #e4e7f2 !important;
    border-radius: 12px !important;
    background: #fafbff !important;
}

.veestore-index-theme .form-label {
    margin-bottom: .4rem;
    color: #5d6880;
    font-size: .78rem;
    font-weight: 700;
}

.veestore-index-theme .form-control,
.veestore-index-theme .form-select,
.veestore-index-theme .select2-selection {
    min-height: 42px;
    border-color: #dce1eb;
    border-radius: 10px;
    background-color: #fff;
}

.veestore-index-theme .form-control:focus,
.veestore-index-theme .form-select:focus {
    border-color: var(--index-primary);
    box-shadow: 0 0 0 .2rem rgba(115, 103, 240, .12);
}

.veestore-index-theme .btn {
    min-height: 40px;
    border-radius: 10px;
    font-weight: 600;
    letter-spacing: .01em;
    box-shadow: none;
    transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease;
}

.veestore-index-theme .btn:hover {
    transform: translateY(-1px);
}

.veestore-index-theme .btn-sm {
    min-height: 34px;
    border-radius: 9px;
}

.veestore-index-theme .btn-primary {
    border-color: var(--index-primary);
    background: linear-gradient(135deg, var(--index-primary), #8f5de7);
    box-shadow: 0 6px 16px rgba(115, 103, 240, .24);
}

.veestore-index-theme .btn-primary:hover,
.veestore-index-theme .btn-primary:focus {
    border-color: var(--index-primary-dark);
    background: linear-gradient(135deg, var(--index-primary-dark), #7b4ed2);
}

.veestore-index-theme .btn-outline-secondary {
    color: #667085;
    border-color: #d5dae5;
    background: #fff;
}

.veestore-index-theme .btn-outline-secondary:hover {
    color: var(--index-primary);
    border-color: #c9c2ff;
    background: var(--index-primary-soft);
}

.veestore-index-theme .table-responsive {
    border-radius: 0 0 var(--index-radius) var(--index-radius);
    scrollbar-color: #b6bdca transparent;
    scrollbar-width: thin;
}

.veestore-index-theme .table {
    margin-bottom: 0;
    color: #344054;
}

.veestore-index-theme .table > :not(caption) > * > * {
    padding: .9rem 1rem;
    border-bottom-color: #edf0f5;
    vertical-align: middle;
}

.veestore-index-theme .table thead th {
    color: #667085;
    border-top: 0;
    border-bottom: 1px solid #dfe3ec;
    background: linear-gradient(180deg, #fafbfe, #f4f6fb);
    font-size: .74rem;
    font-weight: 800;
    letter-spacing: .025em;
    text-transform: uppercase;
    white-space: nowrap;
}

.veestore-index-theme .table tbody tr {
    transition: background-color .15s ease;
}

.veestore-index-theme .table tbody tr:hover {
    background: #fafaff;
}

.veestore-index-theme .table tbody tr:last-child td {
    border-bottom: 0;
}

.veestore-index-theme .table tbody tr:only-child td[colspan] {
    height: 175px;
    padding: 2rem !important;
    color: #7b8498;
    font-size: .92rem;
    text-align: center;
    background:
        radial-gradient(circle at center, rgba(115, 103, 240, .04), transparent 9rem);
}

.veestore-index-theme .table tbody tr:only-child td[colspan]::before {
    display: block;
    width: 54px;
    height: 54px;
    margin: 0 auto .8rem;
    content: "";
    border: 1px solid #ddd8ff;
    border-radius: 16px;
    background-color: var(--index-primary-soft);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='28' viewBox='0 0 24 24' fill='none' stroke='%237367f0' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 7.5h18v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'/%3E%3Cpath d='M8 7.5V5a4 4 0 0 1 8 0v2.5'/%3E%3Cpath d='M9 13h6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
}

.veestore-index-theme .badge,
.veestore-index-theme [class*="status-pill"] {
    border-radius: 999px;
    font-weight: 700;
}

.veestore-index-theme .alert {
    border: 1px solid transparent;
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(32, 44, 69, .04);
}

.veestore-index-theme .dropdown-menu {
    padding: .45rem;
    border: 1px solid var(--index-border);
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(32, 44, 69, .14);
}

.veestore-index-theme .dropdown-item {
    padding: .6rem .75rem;
    border-radius: 8px;
}

.veestore-index-theme .input-group {
    border-radius: 10px;
}

.veestore-index-theme .input-group .form-control:not(:first-child),
.veestore-index-theme .input-group .form-select:not(:first-child) {
    border-left-color: #e4e7ee;
}

.veestore-index-theme .pagination {
    margin-bottom: 0;
    gap: .25rem;
}

.veestore-index-theme .page-link {
    min-width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 9px !important;
    color: #667085;
    background: #f4f5f8;
}

.veestore-index-theme .page-item.active .page-link {
    color: #fff;
    background: var(--index-primary);
    box-shadow: 0 4px 12px rgba(115, 103, 240, .24);
}

.veestore-index-theme .modal-content {
    overflow: hidden;
    border: 0;
    border-radius: 16px;
    box-shadow: 0 24px 60px rgba(32, 44, 69, .22);
}

.veestore-index-theme .modal-header,
.veestore-index-theme .modal-footer {
    padding: 1rem 1.25rem;
    border-color: var(--index-border);
    background: #fff;
}

.veestore-index-theme .modal-title {
    color: var(--index-text);
    font-weight: 700;
}

.veestore-index-theme .modal-body {
    padding: 1.25rem;
}

.veestore-index-theme .text-muted {
    color: var(--index-muted) !important;
}

@media (prefers-reduced-motion: no-preference) {
    .veestore-index-theme .content-wrapper > .container-xxl {
        animation: indexContentIn .28s ease-out both;
    }

    @keyframes indexContentIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
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
        padding: 1rem !important;
        font-size: 1.2rem;
    }

    .veestore-index-theme h4.py-3::after,
    .veestore-index-theme .container-p-y > h4:first-child::after {
        display: none;
    }

    .veestore-index-theme .card-header,
    .veestore-index-theme .card-body,
    .veestore-index-theme .card-footer {
        padding-right: 1rem;
        padding-left: 1rem;
    }

    .veestore-index-theme .table > :not(caption) > * > * {
        padding: .8rem;
    }
}
</style>
