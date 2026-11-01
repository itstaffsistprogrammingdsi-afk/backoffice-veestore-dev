@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Cashier Shift Report')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
    :root {
        --cs-primary: #5b5cf6;
        --cs-primary-dark: #4338ca;
        --cs-primary-soft: rgba(91, 92, 246, .12);
        --cs-success: #22c55e;
        --cs-success-soft: rgba(34, 197, 94, .14);
        --cs-info: #06b6d4;
        --cs-info-soft: rgba(6, 182, 212, .14);
        --cs-warning: #f59e0b;
        --cs-warning-soft: rgba(245, 158, 11, .16);
        --cs-danger: #ef4444;
        --cs-danger-soft: rgba(239, 68, 68, .14);
        --cs-purple: #8b5cf6;
        --cs-dark: #1e293b;
        --cs-muted: #64748b;
        --cs-border: #e5e7eb;
        --cs-body: #f6f7fb;
        --cs-card: #ffffff;
    }

    body {
        background:
            radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
            radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
            var(--cs-body);
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container--bootstrap-5 .select2-selection {
        min-height: 40px;
        border-radius: .75rem;
        border-color: #dbe1ea;
        box-shadow: none;
    }

    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .form-control:focus,
    .form-select:focus {
        border-color: var(--cs-primary);
        box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
    }

    .form-control,
    .form-select {
        border-radius: .75rem;
        border-color: #dbe1ea;
    }

    .dashboard-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.4rem;
        color: #fff;
        background:
            radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
            linear-gradient(135deg, #4f46e5 0%, #22c55e 55%, #06b6d4 120%);
        box-shadow: 0 1.25rem 2.5rem rgba(79, 70, 229, .22);
        border: 0;
    }

    .dashboard-hero::before {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        right: -80px;
        top: -100px;
        border-radius: 50%;
        background: rgba(255,255,255,.18);
    }

    .dashboard-hero::after {
        content: "";
        position: absolute;
        width: 180px;
        height: 180px;
        right: 120px;
        bottom: -120px;
        border-radius: 50%;
        background: rgba(255,255,255,.13);
    }

    .dashboard-hero > .d-flex {
        position: relative;
        z-index: 2;
    }

    .dashboard-hero h4,
    .dashboard-hero .text-muted,
    .dashboard-hero .text-muted.fw-light {
        color: #fff !important;
    }

    .hero-subtitle {
        color: rgba(255,255,255,.84) !important;
    }

    .hero-badge {
        border-radius: 999px;
        padding: .55rem .85rem;
        background: rgba(255,255,255,.18);
        color: #fff;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.22);
        font-weight: 600;
        font-size: .78rem;
    }

    .live-dot {
        display: inline-block;
        width: .55rem;
        height: .55rem;
        border-radius: 999px;
        background: #22c55e;
        box-shadow: 0 0 0 .35rem rgba(34,197,94,.20);
        animation: pulseLive 1.4s infinite;
    }

    @keyframes pulseLive {
        0% { box-shadow: 0 0 0 0 rgba(34,197,94,.38); }
        70% { box-shadow: 0 0 0 .45rem rgba(34,197,94,0); }
        100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }

    .card {
        border: 0;
        border-radius: 1.15rem;
        box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .06);
    }

    .card-header {
        border-bottom: 1px solid rgba(226, 232, 240, .8);
        background: transparent;
        padding: 1.25rem 1.35rem;
    }

    .card-body {
        padding: 1.35rem;
    }

    .summary-card {
        position: relative;
        overflow: hidden;
        height: 100%;
        border: 1px solid rgba(226, 232, 240, .85);
        border-radius: 1.15rem;
        background: var(--cs-card);
        transition: .22s ease;
    }

    .summary-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: linear-gradient(90deg, var(--cs-primary), var(--cs-success));
    }

    .summary-card::after {
        content: "";
        position: absolute;
        width: 90px;
        height: 90px;
        right: -36px;
        bottom: -36px;
        border-radius: 50%;
        background: var(--cs-primary-soft);
    }

    .summary-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
    }

    .summary-card h4 {
        color: var(--cs-dark);
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .summary-card .text-muted {
        color: var(--cs-muted) !important;
    }

    .summary-icon {
        width: 50px;
        height: 50px;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--cs-primary), var(--cs-primary-dark));
        color: #fff;
        font-size: 25px;
        flex-shrink: 0;
        box-shadow: 0 .65rem 1.25rem rgba(91, 92, 246, .22);
    }

    .summary-icon.success {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        box-shadow: 0 .65rem 1.25rem rgba(34, 197, 94, .22);
    }

    .summary-icon.info {
        background: linear-gradient(135deg, #06b6d4, #0284c7);
        box-shadow: 0 .65rem 1.25rem rgba(6, 182, 212, .22);
    }

    .summary-icon.warning {
        background: linear-gradient(135deg, #f59e0b, #f97316);
        box-shadow: 0 .65rem 1.25rem rgba(245, 158, 11, .22);
    }

    .summary-icon.danger {
        background: linear-gradient(135deg, #ec4899, #ef4444);
        box-shadow: 0 .65rem 1.25rem rgba(236, 72, 153, .22);
    }

    .filter-box {
        background: linear-gradient(180deg, rgba(248, 250, 252, .98), rgba(255,255,255,.98));
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.15rem;
    }

    .filter-actions {
        display: flex;
        gap: .6rem;
        flex-wrap: wrap;
    }

    .btn {
        border-radius: .75rem;
        font-weight: 600;
    }

    .btn-primary {
        border-color: var(--cs-primary);
        background: linear-gradient(135deg, var(--cs-primary), var(--cs-purple));
        box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
    }

    .btn-success {
        border-color: var(--cs-success);
        background: linear-gradient(135deg, var(--cs-success), #16a34a);
        box-shadow: 0 .45rem 1rem rgba(34, 197, 94, .18);
    }

    .btn-outline-secondary {
        background: #fff;
        border-color: #d7deea;
        color: #475569;
    }

    .table-responsive {
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
    }

    .table {
        margin-bottom: 0;
    }

    .table-nowrap th,
    .table-nowrap td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .cashier-table thead th {
        background: linear-gradient(180deg, #f8fafc, #eef2ff);
        color: #334155;
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .035em;
        font-weight: 800;
        border-bottom: 1px solid #dbe1ea;
    }

    .cashier-table tbody td {
        color: #475569;
    }

    .cashier-table tbody tr {
        transition: .16s ease;
    }

    .cashier-table tbody tr:hover {
        background-color: rgba(91, 92, 246, .055);
    }

    .store-col,
    .cashier-col,
    .session-col {
        min-width: 210px;
        white-space: normal !important;
    }

    .pagination-info {
        font-size: .875rem;
        color: var(--cs-muted);
    }

    .amount-danger {
        color: #b91c1c !important;
        font-weight: 800;
    }

    .amount-success {
        color: #15803d !important;
        font-weight: 800;
    }

    .badge-soft-success {
        background: var(--cs-success-soft) !important;
        color: #15803d !important;
        border-radius: 999px;
        padding: .42rem .65rem;
        font-weight: 700;
    }

    .badge-soft-secondary {
        background: #f1f5f9 !important;
        color: #475569 !important;
        border-radius: 999px;
        padding: .42rem .65rem;
        font-weight: 700;
    }

    .badge-soft-warning {
        background: var(--cs-warning-soft) !important;
        color: #b45309 !important;
        border-radius: 999px;
        padding: .42rem .65rem;
        font-weight: 700;
    }

    @media (max-width: 767.98px) {
        .dashboard-hero {
            border-radius: 1rem;
        }

        .card-body {
            padding: 1rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $todaySummary = $todaySummary ?? (object) [
        'today_omzet' => 0,
        'today_transactions' => 0,
        'today_sessions' => 0,
        'today_open_sessions' => 0,
        'today_closed_sessions' => 0,
        'today_cash_difference' => 0,
    ];

    $summary = $summary ?? (object) [
        'total_omzet' => 0,
        'total_transactions' => 0,
        'total_sessions' => 0,
        'total_open_sessions' => 0,
        'total_closed_sessions' => 0,
        'total_cash_difference' => 0,
    ];

    $autoRefresh = $autoRefresh ?? false;

    $hasFilter = request()->hasAny([
        'search',
        'store_id',
        'cashier_id',
        'shift_id',
        'status',
        'date_from',
        'date_to',
        'auto_refresh',
    ]);

    $dateFrom = $filters['date_from'] ?? now()->toDateString();
    $dateTo = $filters['date_to'] ?? now()->toDateString();
    $exportQuery = request()->query();
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

    <div class="dashboard-hero p-4 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <h4 class="mb-1">
                    <span class="fw-light">Monitoring /</span> Cashier Shift Report
                </h4>
                <div class="hero-subtitle">
                    Dashboard realtime untuk memantau shift kasir, omset hari ini, transaksi, status sesi, kas sistem, kas fisik, dan selisih kas.
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="hero-badge">
                    <span class="live-dot me-1"></span>
                    Live Omset Hari Ini
                </span>

                <span class="hero-badge">
                    <i class="mdi mdi-cash-register me-1"></i>
                    Rp {{ number_format((float) ($todaySummary->today_omzet ?? 0), 0, ',', '.') }}
                </span>

                <span class="hero-badge">
                    <i class="mdi mdi-clock-outline me-1"></i>
                    Update: {{ now()->format('d-m-Y H:i:s') }}
                </span>

                @if($autoRefresh)
                    <span class="hero-badge">
                        <i class="mdi mdi-autorenew me-1"></i>
                        Auto Refresh Aktif
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card mb-4">
        <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <h5 class="mb-1 fw-bold">Filter Cashier Shift Report</h5>
                <small class="text-muted">
                    Filter laporan berdasarkan store, kasir, status sesi, dan periode tanggal.
                </small>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-bs-toggle="collapse"
                        data-bs-target="#advancedFilterCashier">
                    <i class="mdi mdi-filter-variant me-1"></i> Filter
                </button>

                <a href="{{ route('monitoring.cashier-shift-report.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-refresh me-1"></i> Reset
                </a>

                <a href="{{ route('monitoring.cashier-shift-report.export', $exportQuery) }}"
                   class="btn btn-success btn-sm">
                    <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterCashier">
                <div class="filter-box">
                    <form method="GET"
                          action="{{ route('monitoring.cashier-shift-report.index') }}"
                          class="row g-3">

                        <div class="col-md-3">
                            <label class="form-label">Cari</label>
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   value="{{ $filters['search'] ?? '' }}"
                                   placeholder="Store / cashier / session / status">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Store</label>
                            <select name="store_id"
                                    class="form-select js-select2-filter"
                                    data-placeholder="Semua Store">
                                <option value=""></option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" @selected(($filters['store_id'] ?? '') == $store->id)>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Cashier</label>
                            <select name="cashier_id"
                                    class="form-select js-select2-filter"
                                    data-placeholder="Semua Cashier">
                                <option value=""></option>
                                @foreach($cashiers as $cashier)
                                    <option value="{{ $cashier->id }}" @selected(($filters['cashier_id'] ?? '') == $cashier->id)>
                                        {{ $cashier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Shift</label>
                            <select name="shift_id"
                                    class="form-select js-select2-filter"
                                    data-placeholder="Semua Shift">
                                <option value=""></option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}" @selected(($filters['shift_id'] ?? '') == $shift->id)>
                                        {{ $shift->store_name }} · {{ $shift->name }}{{ $shift->code ? ' (' . $shift->code . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status"
                                    class="form-select js-select2-filter"
                                    data-placeholder="Semua Status">
                                <option value=""></option>
                                <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
                                <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Closed</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date"
                                   name="date_from"
                                   class="form-control"
                                   value="{{ $dateFrom }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date"
                                   name="date_to"
                                   class="form-control"
                                   value="{{ $dateTo }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Realtime Auto Refresh</label>
                            <select name="auto_refresh" class="form-select">
                                <option value="0" @selected(!$autoRefresh)>Nonaktif</option>
                                <option value="1" @selected($autoRefresh)>Aktif, refresh 60 detik</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="mdi mdi-magnify me-1"></i> Tampilkan
                                </button>

                                <a href="{{ route('monitoring.cashier-shift-report.index') }}"
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="mdi mdi-refresh me-1"></i> Reset
                                </a>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- SUMMARY HARI INI --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="text-muted small">Omset Hari Ini</div>
                            <h4 class="mb-0">Rp {{ number_format((float) ($todaySummary->today_omzet ?? 0), 0, ',', '.') }}</h4>
                            <small class="text-muted">Realtime {{ now()->format('d M Y') }}</small>
                        </div>
                        <div class="summary-icon success">
                            <i class="mdi mdi-cash-multiple"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="text-muted small">Transaksi Hari Ini</div>
                            <h4 class="mb-0">{{ number_format((float) ($todaySummary->today_transactions ?? 0), 0, ',', '.') }}</h4>
                            <small class="text-muted">Total transaksi kasir</small>
                        </div>
                        <div class="summary-icon info">
                            <i class="mdi mdi-receipt-text-outline"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="text-muted small">Session Open Hari Ini</div>
                            <h4 class="mb-0">{{ number_format((float) ($todaySummary->today_open_sessions ?? 0), 0, ',', '.') }}</h4>
                            <small class="text-muted">Kasir masih aktif</small>
                        </div>
                        <div class="summary-icon warning">
                            <i class="mdi mdi-cash-register"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="text-muted small">Selisih Kas Hari Ini</div>
                            <h4 class="mb-0 {{ ($todaySummary->today_cash_difference ?? 0) != 0 ? 'amount-danger' : 'amount-success' }}">
                                Rp {{ number_format((float) ($todaySummary->today_cash_difference ?? 0), 0, ',', '.') }}
                            </h4>
                            <small class="text-muted">System vs fisik</small>
                        </div>
                        <div class="summary-icon danger">
                            <i class="mdi mdi-alert-circle-outline"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SUMMARY FILTER --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="text-muted small">Omset Sesuai Filter</div>
                            <h4 class="mb-0">Rp {{ number_format((float) ($summary->total_omzet ?? 0), 0, ',', '.') }}</h4>
                            <small class="text-muted">Periode yang dipilih</small>
                        </div>
                        <div class="summary-icon">
                            <i class="mdi mdi-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="text-muted small">Total Session</div>
                            <h4 class="mb-0">{{ number_format((float) ($summary->total_sessions ?? 0), 0, ',', '.') }}</h4>
                            <small class="text-muted">Jumlah sesi kasir</small>
                        </div>
                        <div class="summary-icon info">
                            <i class="mdi mdi-counter"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="text-muted small">Session Closed</div>
                            <h4 class="mb-0">{{ number_format((float) ($summary->total_closed_sessions ?? 0), 0, ',', '.') }}</h4>
                            <small class="text-muted">Sesi sudah closing</small>
                        </div>
                        <div class="summary-icon success">
                            <i class="mdi mdi-lock-check-outline"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <div class="text-muted small">Selisih Kas Filter</div>
                            <h4 class="mb-0 {{ ($summary->total_cash_difference ?? 0) != 0 ? 'amount-danger' : 'amount-success' }}">
                                Rp {{ number_format((float) ($summary->total_cash_difference ?? 0), 0, ',', '.') }}
                            </h4>
                            <small class="text-muted">Akumulasi selisih</small>
                        </div>
                        <div class="summary-icon danger">
                            <i class="mdi mdi-scale-balance"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- LAPORAN OMZET HARIAN PER SHIFT --}}
    <div class="card mb-4">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h5 class="mb-1 fw-bold">Laporan Omzet Harian per Shift</h5>
                <small class="text-muted">Ringkasan omzet per tanggal, Store, dan Shift Name.</small>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm table-nowrap mb-0 cashier-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Store</th>
                            <th>Shift Name</th>
                            <th class="text-end">Kasir</th>
                            <th class="text-end">Sesi</th>
                            <th class="text-end">Trx</th>
                            <th class="text-end">Omzet</th>
                            <th class="text-end">Kembalian</th>
                            <th class="text-end">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyShiftRows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row->period_label }}</td>
                                <td>{{ $row->store_name }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $row->shift_name }}</div>
                                    @if($row->shift_code || $row->shift_start_time)
                                        <small class="text-muted">
                                            {{ $row->shift_code ?: 'Shift' }}{{ $row->shift_start_time ? ' · ' . \Carbon\Carbon::parse($row->shift_start_time)->format('H:i') : '' }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((int) $row->total_cashiers, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int) $row->total_sessions, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int) $row->total_transactions, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold amount-success">Rp {{ number_format((float) $row->total_omzet, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format((float) $row->total_change_amount, 0, ',', '.') }}</td>
                                <td class="text-end {{ (float) $row->total_cash_difference != 0 ? 'amount-danger' : 'amount-success' }}">Rp {{ number_format((float) $row->total_cash_difference, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Belum ada laporan omzet per shift sesuai filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($dailyShiftRows->hasPages())
                <div class="mt-3">{{ $dailyShiftRows->onEachSide(1)->links() }}</div>
            @endif
        </div>
    </div>

    {{-- DETAIL SESSION --}}
    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h5 class="mb-1 fw-bold">Laporan Cashier Shift</h5>
                <small class="text-muted">
                    Detail sesi kasir beserta Shift Name, total transaksi, omzet, kas sistem, kas fisik, dan selisih.
                </small>
            </div>

            @if($rows->count())
                <div class="pagination-info">
                    Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
                    dari <b>{{ number_format($rows->total(), 0, ',', '.') }}</b> data
                    | Halaman <b>{{ $rows->currentPage() }}</b> / <b>{{ $rows->lastPage() }}</b>
                </div>
            @endif
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm table-nowrap mb-0 cashier-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Tanggal</th>
                            <th>Store</th>
                            <th>Kasir</th>
                            <th>Shift Name</th>
                            <th>Status</th>
                            <th>Session</th>
                            <th>Open</th>
                            <th>Close</th>
                            <th class="text-end">Trx</th>
                            <th class="text-end">Omzet</th>
                            <th class="text-end">Kembalian</th>
                            <th class="text-end">Open Cash</th>
                            <th class="text-end">System Cash</th>
                            <th class="text-end">Physical Cash</th>
                            <th class="text-end">Selisih</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>

                                <td class="fw-semibold">
                                    {{ $row->period_label ?? '-' }}
                                </td>

                                <td class="store-col">
                                    <div class="fw-semibold">
                                        <i class="mdi mdi-store-outline text-primary me-1"></i>
                                        {{ $row->store_name ?? '-' }}
                                    </div>
                                </td>

                                <td class="cashier-col">
                                    <div class="fw-semibold">
                                        <i class="mdi mdi-account-outline text-info me-1"></i>
                                        {{ $row->cashier_name ?? '-' }}
                                    </div>
                                </td>

                                <td class="session-col">
                                    <div class="fw-semibold">{{ $row->shift_name ?: 'Tanpa Shift' }}</div>
                                    @if($row->shift_code || $row->shift_start_time)
                                        <small class="text-muted">
                                            {{ $row->shift_code ?: 'Shift' }}{{ $row->shift_start_time ? ' · ' . \Carbon\Carbon::parse($row->shift_start_time)->format('H:i') : '' }}
                                        </small>
                                    @endif
                                </td>

                                <td>
                                    @if($row->cashier_status === 'open')
                                        <span class="badge badge-soft-success">
                                            <i class="mdi mdi-lock-open-outline me-1"></i> Open
                                        </span>
                                    @elseif($row->cashier_status === 'closed')
                                        <span class="badge badge-soft-secondary">
                                            <i class="mdi mdi-lock-check-outline me-1"></i> Closed
                                        </span>
                                    @else
                                        <span class="badge badge-soft-warning">
                                            {{ ucfirst($row->cashier_status ?? '-') }}
                                        </span>
                                    @endif
                                </td>

                                <td class="session-col">
                                    {{ $row->session_number ?: '-' }}
                                </td>

                                <td>
                                    {{ $row->opened_at ? \Carbon\Carbon::parse($row->opened_at)->format('d-m-Y H:i') : '-' }}
                                </td>

                                <td>
                                    {{ $row->closed_at ? \Carbon\Carbon::parse($row->closed_at)->format('d-m-Y H:i') : '-' }}
                                </td>

                                <td class="text-end">
                                    {{ number_format((float) ($row->total_transactions ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-end fw-bold amount-success">
                                    Rp {{ number_format((float) ($row->omzet ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    Rp {{ number_format((float) ($row->total_change_amount ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    Rp {{ number_format((float) ($row->opening_cash ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    Rp {{ number_format((float) ($row->closing_cash_system ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    Rp {{ number_format((float) ($row->closing_cash_physical ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-end {{ ($row->cash_difference ?? 0) != 0 ? 'amount-danger' : 'amount-success' }}">
                                    Rp {{ number_format((float) ($row->cash_difference ?? 0), 0, ',', '.') }}
                                </td>

                                <td class="text-center">
                                    <a href="{{ route('monitoring.cashier-shift-report.detail', $row->cashier_session_id) }}"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="mdi mdi-eye-outline me-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="17" class="text-center text-muted py-4">
                                    Belum ada data cashier shift sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rows->hasPages())
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                    <div class="pagination-info">
                        Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
                        dari <b>{{ number_format($rows->total(), 0, ',', '.') }}</b> data
                    </div>

                    <div>
                        {{ $rows->onEachSide(1)->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
    'use strict';

    const autoRefresh = @json($autoRefresh);

    function initFilterSelect2() {
        $('.js-select2-filter').each(function () {
            const $el = $(this);

            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }

            $el.select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: $el.data('placeholder') || 'Pilih data'
            });
        });
    }

    $(document).ready(function () {
        initFilterSelect2();

        const ttNodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        ttNodes.forEach(el => new bootstrap.Tooltip(el));

        if (autoRefresh) {
            setInterval(function () {
                window.location.reload();
            }, 60000);
        }
    });
})(window.jQuery);
</script>
@endpush
