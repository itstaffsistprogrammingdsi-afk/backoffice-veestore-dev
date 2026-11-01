@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Customer Loyalty Points')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --lp-primary: #5b5cf6;
    --lp-primary-dark: #4338ca;
    --lp-success: #22c55e;
    --lp-info: #06b6d4;
    --lp-warning: #f59e0b;
    --lp-danger: #ef4444;
    --lp-pink: #ec4899;
    --lp-purple: #8b5cf6;
    --lp-dark: #1e293b;
    --lp-muted: #64748b;
    --lp-body: #f6f7fb;
    --lp-border: #e5e7eb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--lp-body);
  }

  .select2-container {
    width: 100% !important;
  }

  .select2-container--bootstrap-5 .select2-selection {
    min-height: 40px;
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .form-control,
  .form-select {
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .form-control:focus,
  .form-select:focus,
  .select2-container--bootstrap-5.select2-container--focus .select2-selection {
    border-color: var(--lp-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .loyalty-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
      linear-gradient(135deg, #4338ca 0%, #06b6d4 55%, #22c55e 120%);
    color: #fff;
    box-shadow: 0 1.25rem 2.5rem rgba(67, 56, 202, .22);
  }

  .loyalty-hero::before {
    content: "";
    position: absolute;
    width: 280px;
    height: 280px;
    right: -110px;
    top: -120px;
    border-radius: 50%;
    background: rgba(255,255,255,.18);
  }

  .loyalty-hero::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    right: 130px;
    bottom: -120px;
    border-radius: 50%;
    background: rgba(255,255,255,.14);
  }

  .loyalty-hero-content {
    position: relative;
    z-index: 2;
  }

  .hero-badge {
    border-radius: 999px;
    padding: .55rem .85rem;
    background: rgba(255,255,255,.18);
    color: #fff;
    border: 1px solid rgba(255,255,255,.22);
    backdrop-filter: blur(8px);
    font-size: .78rem;
    font-weight: 700;
  }

  .live-dot {
    display: inline-block;
    width: .55rem;
    height: .55rem;
    border-radius: 999px;
    background: #22c55e;
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

  .loyalty-filter-box {
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
    font-weight: 700;
  }

  .btn-primary {
    border-color: var(--lp-primary);
    background: linear-gradient(135deg, var(--lp-primary), var(--lp-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--lp-success);
    background: linear-gradient(135deg, var(--lp-success), #16a34a);
    box-shadow: 0 .45rem 1rem rgba(34, 197, 94, .18);
  }

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    background: #fff;
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--lp-primary), var(--lp-info));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-label {
    color: var(--lp-muted);
    font-size: .78rem;
    margin-bottom: .35rem;
  }

  .summary-value {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 900;
    color: var(--lp-dark);
    letter-spacing: -.02em;
  }

  .summary-help {
    display: block;
    margin-top: .25rem;
    font-size: .78rem;
    color: var(--lp-muted);
  }

  .summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 25px;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--lp-primary), var(--lp-primary-dark));
    box-shadow: 0 .65rem 1.25rem rgba(91, 92, 246, .22);
  }

  .summary-icon.success {
    background: linear-gradient(135deg, #22c55e, #16a34a);
  }

  .summary-icon.info {
    background: linear-gradient(135deg, #06b6d4, #0284c7);
  }

  .summary-icon.warning {
    background: linear-gradient(135deg, #f59e0b, #f97316);
  }

  .summary-icon.danger {
    background: linear-gradient(135deg, #ec4899, #ef4444);
  }

  .chart-wrap {
    position: relative;
    min-height: 330px;
  }

  .point-in {
    color: #16a34a;
    font-weight: 800;
  }

  .point-out {
    color: #dc2626;
    font-weight: 800;
  }

  .point-balance {
    color: #4338ca;
    font-weight: 900;
  }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table {
    margin-bottom: 0;
  }

  .table th,
  .table td {
    vertical-align: middle;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
  }

  .loyalty-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .76rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 900;
    border-bottom: 1px solid #dbe1ea;
  }

  .loyalty-table tbody td {
    color: #475569;
  }

  .loyalty-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--lp-muted);
  }

  .mini-list {
    max-height: 420px;
    overflow: auto;
    padding-top: .25rem;
  }

  .mini-list-item {
    border: 1px solid transparent;
    border-radius: .95rem;
    padding: .85rem .9rem;
    margin-bottom: .65rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    transition: .18s ease;
  }

  .mini-list-item:hover {
    border-color: #dbeafe;
    transform: translateX(2px);
    box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .06);
  }

  .rank-badge {
    width: 30px;
    height: 30px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(91, 92, 246, .12);
    color: #4338ca;
    font-weight: 900;
    font-size: .8rem;
  }

  .modal-xl-custom {
    max-width: 1320px;
  }

  .modal-customer-card {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    padding: 1rem;
  }

  .customer-avatar {
    width: 50px;
    height: 50px;
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--lp-primary), var(--lp-info));
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 1.2rem;
    flex: 0 0 auto;
  }

  .detail-summary-box {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    background: #ffffff;
    height: 100%;
  }

  .detail-summary-label {
    color: #64748b;
    font-size: .76rem;
    margin-bottom: .35rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    font-weight: 700;
  }

  .detail-summary-value {
    font-weight: 900;
    color: #1e293b;
    margin: 0;
    font-size: 1.05rem;
  }

  .detail-modal-table th,
  .detail-modal-table td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .description-col {
    min-width: 320px;
    max-width: 440px;
    white-space: normal !important;
  }

  .reference-id-col {
    max-width: 190px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .mutation-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    padding: .32rem .7rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 900;
    line-height: 1;
  }

  .mutation-badge-earn { background: #dcfce7; color: #15803d; }
  .mutation-badge-redeem { background: #fee2e2; color: #b91c1c; }
  .mutation-badge-expire { background: #fef3c7; color: #b45309; }
  .mutation-badge-adjust { background: #ede9fe; color: #6d28d9; }
  .mutation-badge-referral-register { background: #dbeafe; color: #1d4ed8; }
  .mutation-badge-referral-first-tx { background: #ccfbf1; color: #0f766e; }
  .mutation-badge-void { background: #e2e8f0; color: #475569; }
  .mutation-badge-default { background: #f1f5f9; color: #64748b; }

  .mutation-row-earn td { background-color: #f0fdf4 !important; }
  .mutation-row-redeem td { background-color: #fef2f2 !important; }
  .mutation-row-expire td { background-color: #fffbeb !important; }
  .mutation-row-adjust td { background-color: #f5f3ff !important; }
  .mutation-row-referral-register td { background-color: #eff6ff !important; }
  .mutation-row-referral-first-tx td { background-color: #f0fdfa !important; }
  .mutation-row-void td { background-color: #f8fafc !important; }

  @media (max-width: 767.98px) {
    .loyalty-hero {
      border-radius: 1rem;
    }

    .chart-wrap {
      min-height: 280px;
    }

    .card-body {
      padding: 1rem;
    }

    .modal-xl-custom {
      max-width: 96%;
    }
  }
</style>
@endpush

@php
  $q = $q ?? '';
  $mutationType = $mutationType ?? '';
  $type = $type ?? '';
  $registrationChannel = $registrationChannel ?? '';
  $registrationStoreId = $registrationStoreId ?? '';

  $pointRate = $pointRate ?? [];

  $pointInIdrBase = (float) data_get($pointRate, 'in.idr_base', data_get($pointRate, 'idr_base', 10000));
  $pointInPointsPerBase = (float) data_get($pointRate, 'in.points_per_base', data_get($pointRate, 'points_per_base', 1));
  $pointInPointsPerIdr = (float) data_get($pointRate, 'in.points_per_idr', data_get($pointRate, 'points_per_idr', 0.0001));

  $pointOutPointsBase = (float) data_get($pointRate, 'out.points_base', 1);
  $pointOutIdrPerBase = (float) data_get($pointRate, 'out.idr_per_base', 50);
  $pointOutIdrPerPoint = (float) data_get($pointRate, 'out.idr_per_point', 50);

  $pointInIdrBase = $pointInIdrBase > 0 ? $pointInIdrBase : 10000;
  $pointInPointsPerBase = $pointInPointsPerBase > 0 ? $pointInPointsPerBase : 1;
  $pointInPointsPerIdr = $pointInPointsPerIdr > 0 ? $pointInPointsPerIdr : ($pointInPointsPerBase / $pointInIdrBase);

  $pointOutPointsBase = $pointOutPointsBase > 0 ? $pointOutPointsBase : 1;
  $pointOutIdrPerBase = $pointOutIdrPerBase > 0 ? $pointOutIdrPerBase : 50;
  $pointOutIdrPerPoint = $pointOutIdrPerPoint > 0 ? $pointOutIdrPerPoint : ($pointOutIdrPerBase / $pointOutPointsBase);

  $summary = $summary ?? (object) [
    'total_customer' => 0,
    'total_current_balance' => 0,
    'total_point_in' => 0,
    'total_point_out' => 0,
    'total_mutations' => 0,
    'active_point_customer' => 0,
    'total_store_customer' => 0,
    'total_ecommerce_customer' => 0,
  ];

  $summaryPointInDisplay = (float) $summary->total_point_in * $pointInPointsPerIdr;
  $summaryPointOutDisplay = (float) $summary->total_point_out * $pointOutIdrPerPoint;

  $highestBranch = collect($topBranches ?? [])->first();

  $hasFilter = request()->hasAny([
    'q',
    'mutation_type',
    'type',
    'registration_channel',
    'registration_store_id',
  ]);
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <div class="loyalty-hero p-4 mb-4">
    <div class="loyalty-hero-content d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1 text-white">
          <span class="fw-light">Monitoring /</span> Customer Loyalty Points
        </h4>
        <div style="color: rgba(255,255,255,.84)">
          Dashboard Holding untuk melihat saldo loyalty point, cabang tertinggi, performa per cabang, dan detail per member.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <span class="live-dot me-1"></span>
          Live Data
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ now()->format('d-m-Y H:i:s') }}
        </span>
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Dashboard Loyalty Point</h5>
        <small class="text-muted">
          Filter dapat dibuka/tutup untuk melihat semua cabang, cabang tertentu, mutation type, dan member tertentu.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#filterLoyaltyPointDashboard"
                aria-expanded="{{ $hasFilter ? 'true' : 'false' }}"
                aria-controls="filterLoyaltyPointDashboard">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.customer-loyalty-points-report.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel me-1"></i> Export Excel
        </a>

        <a href="{{ route('monitoring.customer-loyalty-points-report.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="filterLoyaltyPointDashboard">
        <div class="loyalty-filter-box">
          <form method="GET"
                action="{{ route('monitoring.customer-loyalty-points-report.index') }}"
                class="row g-3 align-items-end">

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Mutation Type</label>
              <select name="mutation_type"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Mutation Type">
                <option value="">Semua Mutation Type</option>
                @foreach($mutationTypes as $item)
                  <option value="{{ $item }}" {{ (string) $mutationType === (string) $item ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $item)) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Type</label>
              <select name="type"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Type">
                <option value="">Semua Type</option>
                @foreach($typeOptions as $value => $label)
                  <option value="{{ $value }}" {{ (string) $type === (string) $value ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Channel</label>
              <select name="registration_channel"
                      id="registration_channel"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Channel">
                <option value="">Semua Channel</option>
                <option value="store" {{ $registrationChannel === 'store' ? 'selected' : '' }}>Store</option>
                <option value="ecommerce" {{ $registrationChannel === 'ecommerce' ? 'selected' : '' }}>Ecommerce</option>
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Cabang / Store</label>
              <select name="registration_store_id"
                      id="registration_store_id"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Cabang">
                <option value="">Semua Cabang</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" {{ (string) $registrationStoreId === (string) $store->id ? 'selected' : '' }}>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-12 col-md-8">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q }}"
                     placeholder="Cari customer / email / no wa / description / reference / reference ID">
            </div>

            <div class="col-sm-12 col-md-4">
              <div class="filter-actions">
                <button class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.customer-loyalty-points-report.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.customer-loyalty-points-report.index') }}"
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

  {{-- SUMMARY --}}
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Customer</div>
            <h5 class="summary-value">{{ number_format((float) $summary->total_customer, 0, ',', '.') }}</h5>
            <span class="summary-help">Customer sesuai filter</span>
          </div>
          <div class="summary-icon">
            <i class="mdi mdi-account-group-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Balance Point</div>
            <h5 class="summary-value point-balance">{{ number_format((float) $summary->total_current_balance, 2, ',', '.') }}</h5>
            <span class="summary-help">Saldo point aktif</span>
          </div>
          <div class="summary-icon info">
            <i class="mdi mdi-wallet-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Point Masuk</div>
            <h5 class="summary-value point-in">{{ number_format($summaryPointInDisplay, 6, ',', '.') }}</h5>
            <span class="summary-help">Hasil konversi point masuk</span>
          </div>
          <div class="summary-icon success">
            <i class="mdi mdi-trending-up"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Point Keluar</div>
            <h5 class="summary-value point-out">Rp {{ number_format($summaryPointOutDisplay, 0, ',', '.') }}</h5>
            <span class="summary-help">Nilai rupiah point keluar</span>
          </div>
          <div class="summary-icon danger">
            <i class="mdi mdi-trending-down"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Customer Aktif Point</div>
            <h5 class="summary-value">{{ number_format((float) $summary->active_point_customer, 0, ',', '.') }}</h5>
            <span class="summary-help">Balance lebih dari 0</span>
          </div>
          <div class="summary-icon success">
            <i class="mdi mdi-account-check-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Mutasi</div>
            <h5 class="summary-value">{{ number_format((float) $summary->total_mutations, 0, ',', '.') }}</h5>
            <span class="summary-help">Jumlah transaksi mutasi</span>
          </div>
          <div class="summary-icon warning">
            <i class="mdi mdi-swap-horizontal"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Customer Store</div>
            <h5 class="summary-value">{{ number_format((float) $summary->total_store_customer, 0, ',', '.') }}</h5>
            <span class="summary-help">Terdaftar dari cabang</span>
          </div>
          <div class="summary-icon">
            <i class="mdi mdi-store-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Cabang Tertinggi</div>
            <h5 class="summary-value" style="font-size: 1rem;">
              {{ $highestBranch->branch_name ?? '-' }}
            </h5>
            <span class="summary-help">
              Balance: {{ $highestBranch ? number_format((float) $highestBranch->total_balance, 2, ',', '.') : '0,00' }}
            </span>
          </div>
          <div class="summary-icon info">
            <i class="mdi mdi-trophy-outline"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Trend Mutasi Loyalty Point 14 Hari Terakhir</h5>
          <small class="text-muted">Jumlah mutasi per hari sesuai filter.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="mutationTrendChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Mutasi Berdasarkan Type</h5>
          <small class="text-muted">Earn, redeem, expire, adjustment, dan lainnya.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="mutationTypeChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-7">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Cabang Tertinggi Berdasarkan Balance Point</h5>
          <small class="text-muted">Top 15 cabang dengan akumulasi saldo loyalty point tertinggi.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="branchBalanceChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-5">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Top Cabang Loyalty Point</h5>
          <small class="text-muted">Ringkasan per cabang untuk Holding.</small>
        </div>
        <div class="card-body mini-list">
          @forelse($topBranches as $index => $branch)
            <div class="mini-list-item">
              <div class="d-flex justify-content-between gap-3">
                <div class="d-flex gap-2">
                  <span class="rank-badge">{{ $index + 1 }}</span>
                  <div>
                    <div class="fw-bold text-dark">{{ $branch->branch_name ?? '-' }}</div>
                    <small class="text-muted">
                      {{ number_format((float) $branch->total_customer, 0, ',', '.') }} customer •
                      {{ number_format((float) $branch->total_mutations, 0, ',', '.') }} mutasi
                    </small>
                  </div>
                </div>
                <div class="text-end">
                  <div class="point-balance">{{ number_format((float) $branch->total_balance, 2, ',', '.') }}</div>
                  <small class="text-muted">balance</small>
                </div>
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-4">Belum ada data cabang.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE UTAMA --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Laporan Customer Loyalty Points per Member</h5>
        <small class="text-muted">Menampilkan customer, cabang/source, balance, point masuk, point keluar, dan detail mutasi.</small>
      </div>

      @if($rows->count())
        <div class="pagination-info">
          Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
          dari <b>{{ number_format($rows->total(), 0, ',', '.') }}</b> customer
          | Halaman <b>{{ $rows->currentPage() }}</b> / <b>{{ $rows->lastPage() }}</b>
        </div>
      @endif
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap loyalty-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Customer</th>
              <th>Email</th>
              <th>No. WA</th>
              <th>Cabang / Source</th>
              <th class="text-end">Current Balance</th>
              <th class="text-end">Point Masuk</th>
              <th class="text-end">Point Keluar</th>
              <th class="text-center">Jumlah Mutasi</th>
              <th>Tanggal Mutasi Terakhir</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              @php
                $customerId = (string) ($row->customer_id ?? '');
                $modalId = md5($customerId);
                $rowPointInDisplay = (float) ($row->total_point_in ?? 0) * $pointInPointsPerIdr;
                $rowPointOutDisplay = (float) ($row->total_point_out ?? 0) * $pointOutIdrPerPoint;
              @endphp

              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>
                <td>
                  <div class="fw-semibold text-dark">{{ $row->customer_name ?? '-' }}</div>
                  <small class="text-muted">ID: {{ $row->customer_id ?? '-' }}</small>
                </td>
                <td>{{ $row->customer_email ?? '-' }}</td>
                <td>{{ $row->customer_wa_number ?? '-' }}</td>
                <td>{{ $row->branch_name ?? '-' }}</td>
                <td class="text-end point-balance">{{ number_format((float) ($row->current_balance ?? 0), 2, ',', '.') }}</td>
                <td class="text-end point-in">{{ number_format($rowPointInDisplay, 6, ',', '.') }} point</td>
                <td class="text-end point-out">Rp {{ number_format($rowPointOutDisplay, 0, ',', '.') }}</td>
                <td class="text-center">{{ number_format((float) ($row->total_mutations ?? 0), 0, ',', '.') }}</td>
                <td class="text-nowrap">
                  {{ $row->last_mutation_at ? \Carbon\Carbon::parse($row->last_mutation_at)->format('d M Y H:i') : '-' }}
                </td>
                <td class="text-center">
                  <button type="button"
                          class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#detailModal-{{ $modalId }}">
                    <i class="mdi mdi-eye-outline me-1"></i> Detail
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center text-muted py-4">Belum ada data customer</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($rows->hasPages())
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
          <div class="pagination-info">
            Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
            dari <b>{{ number_format($rows->total(), 0, ',', '.') }}</b> customer
          </div>

          <div>{{ $rows->onEachSide(1)->links() }}</div>
        </div>
      @endif
    </div>
  </div>

  {{-- MODAL DETAIL --}}
  @foreach($rows as $row)
    @php
      $customerId = (string) ($row->customer_id ?? '');
      $modalId = md5($customerId);
      $details = $detailRows->get($row->customer_id, collect());

      $allPointInRaw = (float) $details->sum('point_in');
      $allPointOutRaw = (float) $details->sum('point_out');
      $allMutationCount = $details->count();

      $allPointInDisplay = $allPointInRaw * $pointInPointsPerIdr;
      $allPointOutDisplay = $allPointOutRaw * $pointOutIdrPerPoint;

      $firstMutationAt = $details->min('created_at');
      $lastMutationAt = $details->max('created_at');

      $customerInitial = strtoupper(mb_substr((string) ($row->customer_name ?? 'C'), 0, 1));
    @endphp

    <div class="modal fade" id="detailModal-{{ $modalId }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-xl-custom modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title mb-1">Detail Mutasi Loyalty Point</h5>
              <div class="small text-muted">Riwayat transaksi point per member.</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="modal-customer-card mb-4">
              <div class="d-flex align-items-center gap-3">
                <div class="customer-avatar">{{ $customerInitial }}</div>

                <div class="flex-grow-1">
                  <div class="fw-bold fs-5 text-dark">{{ $row->customer_name ?? '-' }}</div>
                  <div class="text-muted small">
                    {{ $row->customer_email ?? '-' }} • {{ $row->customer_wa_number ?? '-' }}
                  </div>
                  <div class="text-muted small">
                    Cabang / Source: {{ $row->branch_name ?? '-' }}
                  </div>
                  <div class="text-muted small">
                    Customer ID: {{ $row->customer_id ?? '-' }}
                  </div>
                </div>

                <div class="text-end d-none d-md-block">
                  <div class="small text-muted">Current Balance</div>
                  <div class="fs-5 point-balance">
                    {{ number_format((float) ($row->current_balance ?? 0), 2, ',', '.') }}
                  </div>
                </div>
              </div>
            </div>

            <div class="alert alert-info py-2">
              <i class="mdi mdi-information-outline me-1"></i>
              Kurs:
              <b>Point Masuk</b> Rp {{ number_format($pointInIdrBase, 0, ',', '.') }}
              =
              {{ number_format($pointInPointsPerBase, 6, ',', '.') }} point.
              <b>Point Keluar</b>
              {{ number_format($pointOutPointsBase, 6, ',', '.') }} point
              =
              Rp {{ number_format($pointOutIdrPerBase, 0, ',', '.') }}.
            </div>

            <div class="row g-3 mb-4">
              <div class="col-md-3">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Current Balance</div>
                  <h6 class="detail-summary-value point-balance">
                    {{ number_format((float) ($row->current_balance ?? 0), 2, ',', '.') }}
                  </h6>
                </div>
              </div>

              <div class="col-md-3">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Point Masuk</div>
                  <h6 class="detail-summary-value point-in">
                    {{ number_format($allPointInDisplay, 6, ',', '.') }} point
                  </h6>
                </div>
              </div>

              <div class="col-md-3">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Point Keluar</div>
                  <h6 class="detail-summary-value point-out">
                    Rp {{ number_format($allPointOutDisplay, 0, ',', '.') }}
                  </h6>
                </div>
              </div>

              <div class="col-md-3">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Jumlah Mutasi</div>
                  <h6 class="detail-summary-value">
                    {{ number_format($allMutationCount, 0, ',', '.') }}
                  </h6>
                </div>
              </div>

              <div class="col-md-6">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Mutasi Pertama</div>
                  <h6 class="detail-summary-value">
                    {{ $firstMutationAt ? \Carbon\Carbon::parse($firstMutationAt)->format('d M Y H:i') : '-' }}
                  </h6>
                </div>
              </div>

              <div class="col-md-6">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Mutasi Terakhir</div>
                  <h6 class="detail-summary-value">
                    {{ $lastMutationAt ? \Carbon\Carbon::parse($lastMutationAt)->format('d M Y H:i') : '-' }}
                  </h6>
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-sm table-bordered detail-modal-table mb-0">
                <thead class="table-light">
                  <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Type</th>
                    <th>Mutation Type</th>
                    <th class="text-end">Points Asli</th>
                    <th class="text-end">Point Masuk</th>
                    <th class="text-end">Point Keluar</th>
                    <th class="text-end">Balance After</th>
                    <th class="text-end">Used Points</th>
                    <th>Reference</th>
                    <th>Reference ID</th>
                    <th class="description-col">Description</th>
                    <th>Expired At</th>
                  </tr>
                </thead>

                <tbody>
                  @forelse($details as $index => $detail)
                    @php
                      $styleSource = strtolower(trim((string) ($detail->type ?? '')));

                      if ($styleSource === '') {
                          $styleSource = strtolower(trim((string) ($detail->mutation_type ?? '')));
                      }

                      $rowClass = match ($styleSource) {
                          'earn', 'earned', 'point_earned' => 'mutation-row-earn',
                          'redeem', 'redeemed', 'point_redeemed' => 'mutation-row-redeem',
                          'expire', 'expired', 'point_expired' => 'mutation-row-expire',
                          'adjust', 'adjustment' => 'mutation-row-adjust',
                          'referral_register' => 'mutation-row-referral-register',
                          'referral_first_tx' => 'mutation-row-referral-first-tx',
                          'void_reversal' => 'mutation-row-void',
                          default => 'mutation-row-default',
                      };

                      $badgeClass = match ($styleSource) {
                          'earn', 'earned', 'point_earned' => 'mutation-badge-earn',
                          'redeem', 'redeemed', 'point_redeemed' => 'mutation-badge-redeem',
                          'expire', 'expired', 'point_expired' => 'mutation-badge-expire',
                          'adjust', 'adjustment' => 'mutation-badge-adjust',
                          'referral_register' => 'mutation-badge-referral-register',
                          'referral_first_tx' => 'mutation-badge-referral-first-tx',
                          'void_reversal' => 'mutation-badge-void',
                          default => 'mutation-badge-default',
                      };

                      $rawPoints = (float) ($detail->points ?? 0);
                      $pointInRaw = $rawPoints > 0 ? $rawPoints : 0;
                      $pointOutRaw = $rawPoints < 0 ? abs($rawPoints) : 0;

                      $pointInDisplay = $pointInRaw * $pointInPointsPerIdr;
                      $pointOutDisplay = $pointOutRaw * $pointOutIdrPerPoint;

                      $mutationDisplay = trim((string) ($detail->mutation_type ?? ''));
                      $mutationDisplay = $mutationDisplay !== '' ? $mutationDisplay : '-';

                      $typeLabel = $detail->type_label ?? ($detail->type ?? '-');
                      $hasExpiration = (bool) ($detail->has_expiration ?? false);
                      $usedPoints = (float) ($detail->used_points ?? 0);
                    @endphp

                    <tr class="{{ $rowClass }}">
                      <td>{{ $index + 1 }}</td>

                      <td class="text-nowrap">
                        <div class="fw-semibold">
                          {{ $detail->created_at ? \Carbon\Carbon::parse($detail->created_at)->format('d M Y') : '-' }}
                        </div>
                        <small class="text-muted">
                          {{ $detail->created_at ? \Carbon\Carbon::parse($detail->created_at)->format('H:i') : '' }}
                        </small>
                      </td>

                      <td>
                        <span class="mutation-badge {{ $badgeClass }}">
                          {{ $typeLabel }}
                        </span>
                      </td>

                      <td>{{ $mutationDisplay }}</td>

                      <td class="text-end">
                        @if($rawPoints > 0)
                          <span class="point-in">+{{ number_format($rawPoints, 2, ',', '.') }}</span>
                        @elseif($rawPoints < 0)
                          <span class="point-out">{{ number_format($rawPoints, 2, ',', '.') }}</span>
                        @else
                          <span class="text-muted">0,00</span>
                        @endif
                      </td>

                      <td class="text-end point-in">
                        @if($pointInRaw > 0)
                          <div>{{ number_format($pointInDisplay, 6, ',', '.') }} point</div>
                          <small class="text-muted">dari {{ number_format($pointInRaw, 2, ',', '.') }}</small>
                        @else
                          -
                        @endif
                      </td>

                      <td class="text-end point-out">
                        @if($pointOutRaw > 0)
                          <div>Rp {{ number_format($pointOutDisplay, 0, ',', '.') }}</div>
                          <small class="text-muted">dari {{ number_format($pointOutRaw, 2, ',', '.') }} point</small>
                        @else
                          -
                        @endif
                      </td>

                      <td class="text-end point-balance">
                        {{ number_format((float) ($detail->balance_after ?? 0), 2, ',', '.') }}
                      </td>

                      <td class="text-end">
                        {{ $usedPoints > 0 ? number_format($usedPoints, 2, ',', '.') : '-' }}
                      </td>

                      <td>
                        <div class="fw-semibold">{{ $detail->ref_label ?? '-' }}</div>
                        <small class="text-muted">{{ $detail->ref ?? '-' }}</small>
                      </td>

                      <td class="reference-id-col" title="{{ $detail->ref_id ?? '-' }}">
                        {{ $detail->ref_id ?? '-' }}
                      </td>

                      <td class="description-col">
                        {{ $detail->description ?? '-' }}
                      </td>

                      <td class="text-nowrap">
                        @if($detail->expired_at)
                          <div>{{ \Carbon\Carbon::parse($detail->expired_at)->format('d M Y H:i') }}</div>
                          @if($hasExpiration)
                            <small class="text-warning">Ada masa expired</small>
                          @endif
                        @else
                          -
                        @endif
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="13" class="text-center text-muted">
                        Customer ini belum memiliki detail mutasi point.
                      </td>
                    </tr>
                  @endforelse
                </tbody>

                @if($details->count())
                  <tfoot class="table-light">
                    <tr>
                      <th colspan="4" class="text-end">Total Semua Mutasi</th>

                      <th class="text-end">
                        @php
                          $totalRawPoints = (float) $details->sum('points');
                        @endphp

                        @if($totalRawPoints > 0)
                          <span class="point-in">+{{ number_format($totalRawPoints, 2, ',', '.') }}</span>
                        @elseif($totalRawPoints < 0)
                          <span class="point-out">{{ number_format($totalRawPoints, 2, ',', '.') }}</span>
                        @else
                          0,00
                        @endif
                      </th>

                      <th class="text-end point-in">
                        {{ number_format($allPointInDisplay, 6, ',', '.') }} point
                      </th>

                      <th class="text-end point-out">
                        Rp {{ number_format($allPointOutDisplay, 0, ',', '.') }}
                      </th>

                      <th colspan="6"></th>
                    </tr>
                  </tfoot>
                @endif
              </table>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
  'use strict';

  const branchChartRows = @json($branchChartRows ?? []);
  const mutationTypeChartRows = @json($mutationTypeChartRows ?? []);
  const mutationTrendRows = @json($mutationTrendRows ?? []);

  const $registrationChannel = $('#registration_channel');
  const $registrationStore = $('#registration_store_id');

  const chartColors = [
    '#5b5cf6',
    '#06b6d4',
    '#22c55e',
    '#f59e0b',
    '#ec4899',
    '#8b5cf6',
    '#ef4444',
    '#14b8a6',
    '#f97316',
    '#64748b',
    '#0ea5e9',
    '#84cc16',
    '#a855f7',
    '#f43f5e',
    '#10b981'
  ];

  function numberId(value) {
    return Number(value || 0).toLocaleString('id-ID');
  }

  function initFilterSelect2() {
    $('.js-select2-filter').each(function () {
      const $el = $(this);

      if ($el.hasClass('select2-hidden-accessible')) {
        return;
      }

      $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: $el.data('placeholder') || 'Pilih data'
      });
    });
  }

  function toggleStoreFilter() {
    const channel = $registrationChannel.val();

    if (channel === 'ecommerce') {
      $registrationStore.val('').trigger('change');
      $registrationStore.prop('disabled', true);
    } else {
      $registrationStore.prop('disabled', false);
    }
  }

  function renderHorizontalBarChart(canvasId, rows, valueKey, labelText) {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label || item.branch_name || '-');
    const data = rows.map(item => Number(item[valueKey] || 0));

    new Chart(el, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: labelText,
          data: data,
          backgroundColor: chartColors,
          borderColor: chartColors,
          borderWidth: 1,
          borderRadius: 10,
          borderSkipped: false,
          barThickness: 18,
          maxBarThickness: 22
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return `${labelText}: ${numberId(context.raw || 0)}`;
              }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              color: '#64748b',
              font: {
                size: 11,
                weight: '600'
              }
            }
          },
          y: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: '#475569',
              font: {
                size: 11,
                weight: '600'
              }
            }
          }
        }
      }
    });
  }

  function renderDoughnutChart(canvasId, rows) {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label || '-');
    const data = rows.map(item => Number(item.total || 0));

    new Chart(el, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: chartColors,
          borderColor: '#ffffff',
          borderWidth: 3,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              pointStyle: 'circle',
              padding: 16,
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((sum, item) => sum + Number(item || 0), 0);
                const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;

                return `${context.label}: ${numberId(value)} (${percent}%)`;
              }
            }
          }
        }
      }
    });
  }

  function renderLineChart(canvasId, rows) {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label || '-');
    const data = rows.map(item => Number(item.total_mutation || 0));

    const gradient = el.getContext('2d').createLinearGradient(0, 0, 0, 320);
    gradient.addColorStop(0, 'rgba(91, 92, 246, .28)');
    gradient.addColorStop(1, 'rgba(91, 92, 246, 0)');

    new Chart(el, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Total Mutasi',
          data: data,
          borderColor: '#5b5cf6',
          backgroundColor: gradient,
          borderWidth: 3,
          tension: .38,
          fill: true,
          pointRadius: 4,
          pointHoverRadius: 7,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#5b5cf6',
          pointBorderWidth: 3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          intersect: false,
          mode: 'index'
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return `Mutasi: ${numberId(context.raw || 0)}`;
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: '#64748b',
              font: {
                size: 11,
                weight: '600'
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: '#64748b',
              font: {
                size: 11,
                weight: '600'
              }
            }
          }
        }
      }
    });
  }

  $(document).ready(function () {
    initFilterSelect2();
    toggleStoreFilter();

    $registrationChannel.on('change', function () {
      toggleStoreFilter();
    });

    renderLineChart('mutationTrendChart', mutationTrendRows || []);
    renderDoughnutChart('mutationTypeChart', mutationTypeChartRows || []);
    renderHorizontalBarChart('branchBalanceChart', branchChartRows || [], 'total_balance', 'Balance Point');
  });
})(window.jQuery);
</script>
@endpush