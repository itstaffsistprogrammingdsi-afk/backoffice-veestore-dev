@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Product Aging')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --ag-primary: #5b5cf6;
    --ag-primary-dark: #4338ca;
    --ag-success: #22c55e;
    --ag-success-soft: rgba(34, 197, 94, .14);
    --ag-info: #06b6d4;
    --ag-info-soft: rgba(6, 182, 212, .14);
    --ag-warning: #f59e0b;
    --ag-warning-soft: rgba(245, 158, 11, .16);
    --ag-danger: #ef4444;
    --ag-danger-soft: rgba(239, 68, 68, .14);
    --ag-purple: #8b5cf6;
    --ag-dark: #1e293b;
    --ag-muted: #64748b;
    --ag-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(245, 158, 11, .10), transparent 28%),
      var(--ag-body);
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

  .form-control,
  .form-select {
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .select2-container--bootstrap-5.select2-container--focus .select2-selection,
  .form-control:focus,
  .form-select:focus {
    border-color: var(--ag-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .dashboard-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    color: #fff;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
      linear-gradient(135deg, #4f46e5 0%, #f59e0b 58%, #ef4444 120%);
    box-shadow: 0 1.25rem 2.5rem rgba(79, 70, 229, .22);
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
    border-color: var(--ag-primary);
    background: linear-gradient(135deg, var(--ag-primary), var(--ag-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--ag-success);
    background: linear-gradient(135deg, var(--ag-success), #16a34a);
  }

  .summary-card,
  .aging-top-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: #fff;
    transition: .22s ease;
  }

  .summary-card::before,
  .aging-top-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--ag-primary), var(--ag-warning));
  }

  .summary-card:hover,
  .aging-top-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--ag-dark);
    font-weight: 800;
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
    background: linear-gradient(135deg, var(--ag-primary), var(--ag-primary-dark));
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
    background: linear-gradient(135deg, #ef4444, #dc2626);
  }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table td,
  .table th {
    vertical-align: middle;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
  }

  .aging-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .aging-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .product-col {
    min-width: 260px;
    white-space: normal !important;
  }

  .location-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .badge-soft-success {
    background: var(--ag-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--ag-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--ag-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--ag-info-soft) !important;
    color: #0369a1 !important;
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

  .top-aging-list {
    display: flex;
    flex-direction: column;
    gap: .7rem;
  }

  .top-aging-item {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: .8rem;
    background: linear-gradient(180deg, #fff, #f8fafc);
  }

  .top-aging-rank {
    width: 34px;
    height: 34px;
    border-radius: .8rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(91, 92, 246, .12);
    color: #4338ca;
    font-weight: 800;
    flex-shrink: 0;
  }

  .modal-summary-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    margin-bottom: 1rem;
  }

  .modal-summary-box .label {
    font-size: .75rem;
    color: var(--ag-muted);
    margin-bottom: .25rem;
  }

  .modal-summary-box .value {
    font-weight: 800;
    color: var(--ag-dark);
  }

  .modal-pagination-info {
    font-size: .875rem;
    color: var(--ag-muted);
  }


  .summary-card .summary-card-content {
    min-width: 0;
  }

  .summary-card .summary-product-name {
    max-width: 100%;
    color: var(--ag-dark);
    font-size: .88rem;
    font-weight: 800;
    line-height: 1.25;
  }

  .summary-card .summary-location-name {
    max-width: 100%;
    color: var(--ag-muted);
    font-size: .78rem;
    line-height: 1.25;
  }

  .summary-card .summary-mini-badges {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin-top: .45rem;
  }

  .summary-card.aging-oldest-card .card-body {
    padding: 1.1rem 1.2rem;
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
  $summary = $summary ?? (object) [
    'total_product' => 0,
    'total_qty' => 0,
    'warehouse_qty' => 0,
    'store_qty' => 0,
    'max_aging_days' => 0,
    'bucket_0_30' => 0,
    'bucket_31_90' => 0,
    'bucket_91_180' => 0,
    'bucket_181_365' => 0,
    'bucket_gt_365' => 0,
  ];

  $hasFilter = request()->hasAny([
    'search',
    'scope',
    'status',
    'warehouse_id',
    'store_id',
    'aging_bucket',
  ]);

  $agingBadge = function ($days) {
    $days = (int) $days;

    if ($days > 365) {
      return 'badge-soft-danger';
    }

    if ($days > 180) {
      return 'badge-soft-warning';
    }

    if ($days > 90) {
      return 'badge-soft-info';
    }

    return 'badge-soft-success';
  };

  $scopeBadge = function ($scope) {
    return $scope === 'warehouse' ? 'badge-soft-warning' : 'badge-soft-info';
  };

  $oldestAging = $oldestAging
    ?? (($topAgingOverall ?? collect())->first()
    ?? ($topAgingStore ?? collect())->first()
    ?? ($topAgingWarehouse ?? collect())->first()
    ?? null);

  $oldestProductLabel = '-';
  $oldestLocationLabel = '-';
  $oldestScopeLabel = '-';
  $oldestAgingDays = (int) ($summary->max_aging_days ?? 0);
  $oldestQty = 0;
  $oldestCondition = '-';
  $oldestScopeValue = '';

  if ($oldestAging) {
    $oldestProductLabel = trim(
      (string) ($oldestAging->product_code ?? '-') .
      ' - ' .
      (string) ($oldestAging->product_name ?? '-')
    );

    $oldestAgingDays = (int) ($oldestAging->total_aging_days ?? $oldestAgingDays);
    $oldestQty = (int) ($oldestAging->quantity ?? 0);
    $oldestCondition = $oldestAging->condition_status ?: '-';
    $oldestScopeValue = (string) ($oldestAging->scope_type ?? '');

    if ($oldestScopeValue === 'warehouse') {
      $oldestScopeLabel = 'Warehouse';
      $oldestLocationLabel = 'Warehouse: ' . ($oldestAging->warehouse_name ?: '-');
    } elseif ($oldestScopeValue === 'store') {
      $oldestScopeLabel = 'Store';
      $oldestLocationLabel = 'Store: ' . ($oldestAging->store_name ?: '-');
    }
  }

@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i> {{ $errors->first() }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Product Aging
        </h4>
        <div class="hero-subtitle">
          Monitoring umur stok secara realtime dari warehouse dan store, termasuk produk dengan aging paling lama.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-package-variant-closed me-1"></i>
          {{ number_format((int) $summary->total_product, 0, ',', '.') }} Produk
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-counter me-1"></i>
          {{ number_format((int) $summary->total_qty, 0, ',', '.') }} Qty
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-timer-sand-complete me-1"></i>
          {{ number_format((int) $summary->max_aging_days, 0, ',', '.') }} Hari Terlama
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ $lastUpdate ?? now()->format('d-m-Y H:i:s') }}
        </span>
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Monitoring Product Aging</h5>
        <small class="text-muted">
          Gunakan filter untuk membatasi data berdasarkan produk, lokasi, scope, condition, dan kelompok umur stok.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterProductAging">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.product-aging.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.product-aging.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterProductAging">
        <div class="filter-box">
          <form method="GET" action="{{ route('monitoring.product-aging.index') }}" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Cari Produk</label>
              <input type="text"
                     name="search"
                     class="form-control"
                     value="{{ $filters['search'] ?? '' }}"
                     placeholder="Kode / nama produk">
            </div>

            <div class="col-md-2">
              <label class="form-label">Scope</label>
              <select name="scope" class="form-select js-select2-filter" data-placeholder="Semua Scope">
                <option value="all" @selected(($filters['scope'] ?? 'all') === 'all')>Semua</option>
                <option value="warehouse" @selected(($filters['scope'] ?? '') === 'warehouse')>Warehouse</option>
                <option value="store" @selected(($filters['scope'] ?? '') === 'store')>Store</option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Condition</label>
              <select name="status" class="form-select js-select2-filter" data-placeholder="Semua Condition">
                <option value="">Semua Condition</option>
                <option value="good" @selected(($filters['status'] ?? '') === 'good')>Good</option>
                <option value="bad" @selected(($filters['status'] ?? '') === 'bad')>Bad</option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Warehouse</label>
              <select name="warehouse_id" class="form-select js-select2-filter" data-placeholder="Semua Warehouse">
                <option value="">Semua Warehouse</option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" @selected(($filters['warehouse_id'] ?? '') === $warehouse->id)>
                    {{ $warehouse->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Store</label>
              <select name="store_id" class="form-select js-select2-filter" data-placeholder="Semua Store">
                <option value="">Semua Store</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected(($filters['store_id'] ?? '') === $store->id)>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Bucket Aging</label>
              <select name="aging_bucket" class="form-select js-select2-filter" data-placeholder="Semua Bucket">
                <option value="">Semua Bucket</option>
                <option value="0_30" @selected(($filters['aging_bucket'] ?? '') === '0_30')>0 - 30 hari</option>
                <option value="31_90" @selected(($filters['aging_bucket'] ?? '') === '31_90')>31 - 90 hari</option>
                <option value="91_180" @selected(($filters['aging_bucket'] ?? '') === '91_180')>91 - 180 hari</option>
                <option value="181_365" @selected(($filters['aging_bucket'] ?? '') === '181_365')>181 - 365 hari</option>
                <option value="gt_365" @selected(($filters['aging_bucket'] ?? '') === 'gt_365')>> 365 hari</option>
              </select>
            </div>

            <div class="col-md-9">
              <label class="form-label">&nbsp;</label>
              <div class="text-muted small pt-2">
                Data aging dihitung realtime sampai: <strong>{{ $asOfDate->format('d M Y H:i') }}</strong>
              </div>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.product-aging.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.product-aging.index') }}"
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
    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Total Produk</div>
              <h4 class="mb-1">{{ number_format((int) $summary->total_product, 0, ',', '.') }}</h4>
              <small class="text-muted">Produk aktif dalam aging</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-package-variant-closed"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Qty Total</div>
              <h4 class="mb-1">{{ number_format((int) $summary->total_qty, 0, ',', '.') }}</h4>
              <small class="text-muted">Store + warehouse</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-counter"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card aging-oldest-card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between gap-3">
            <div class="summary-card-content flex-grow-1">
              <div class="text-muted small">Aging Terlama</div>

              <h4 class="mb-1">
                {{ number_format($oldestAgingDays, 0, ',', '.') }} Hari
              </h4>

              <div class="summary-product-name text-truncate"
                   title="{{ $oldestProductLabel }}">
                {{ $oldestProductLabel }}
              </div>

              <div class="summary-location-name text-truncate"
                   title="{{ $oldestLocationLabel }}">
                {{ $oldestLocationLabel }}
              </div>

              <div class="summary-mini-badges">
                <span class="badge {{ $scopeBadge($oldestScopeValue) }}">
                  {{ $oldestScopeLabel }}
                </span>

                <span class="badge badge-soft-info">
                  Qty {{ number_format($oldestQty, 0, ',', '.') }}
                </span>

                <span class="badge badge-soft-secondary">
                  {{ $oldestCondition }}
                </span>
              </div>
            </div>

            <div class="summary-icon danger">
              <i class="mdi mdi-timer-sand-complete"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Qty Lokasi</div>
              <h4 class="mb-1">
                {{ number_format((int) $summary->store_qty, 0, ',', '.') }}
                /
                {{ number_format((int) $summary->warehouse_qty, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Store / Warehouse</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-store-clock-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TOP AGING REALTIME --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-6">
      <div class="card aging-top-card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Produk Aging Paling Lama di Store</h5>
          <small class="text-muted">Menampilkan store mana dan produk mana yang paling lama tertahan.</small>
        </div>
        <div class="card-body">
          <div class="top-aging-list">
            @forelse($topAgingStore as $item)
              <div class="top-aging-item">
                <div class="d-flex gap-3">
                  <div class="top-aging-rank">{{ $loop->iteration }}</div>
                  <div class="flex-grow-1">
                    <div class="fw-bold">{{ $item->product_code }} - {{ $item->product_name }}</div>
                    <div class="small text-muted">
                      Store: <strong>{{ $item->store_name ?: '-' }}</strong>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      <span class="badge {{ $agingBadge($item->total_aging_days) }}">
                        {{ number_format((int) $item->total_aging_days, 0, ',', '.') }} hari
                      </span>
                      <span class="badge badge-soft-info">
                        Qty {{ number_format((int) $item->quantity, 0, ',', '.') }}
                      </span>
                      <span class="badge badge-soft-secondary">
                        {{ $item->condition_status ?: '-' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            @empty
              <div class="text-center text-muted py-4">Belum ada data aging store.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-6">
      <div class="card aging-top-card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Produk Aging Paling Lama di Warehouse</h5>
          <small class="text-muted">Menampilkan warehouse mana dan produk mana yang paling lama tertahan.</small>
        </div>
        <div class="card-body">
          <div class="top-aging-list">
            @forelse($topAgingWarehouse as $item)
              <div class="top-aging-item">
                <div class="d-flex gap-3">
                  <div class="top-aging-rank">{{ $loop->iteration }}</div>
                  <div class="flex-grow-1">
                    <div class="fw-bold">{{ $item->product_code }} - {{ $item->product_name }}</div>
                    <div class="small text-muted">
                      Warehouse: <strong>{{ $item->warehouse_name ?: '-' }}</strong>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      <span class="badge {{ $agingBadge($item->total_aging_days) }}">
                        {{ number_format((int) $item->total_aging_days, 0, ',', '.') }} hari
                      </span>
                      <span class="badge badge-soft-info">
                        Qty {{ number_format((int) $item->quantity, 0, ',', '.') }}
                      </span>
                      <span class="badge badge-soft-secondary">
                        {{ $item->condition_status ?: '-' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            @empty
              <div class="text-center text-muted py-4">Belum ada data aging warehouse.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Monitoring Aging Produk</h5>
        <small class="text-muted">
          Data diurutkan dari aging paling lama.
        </small>
      </div>

      <div class="text-muted small">
        Total Data: <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap aging-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Kode</th>
              <th>Nama Produk</th>
              <th>Store</th>
              <th>Warehouse</th>
              <th class="text-end">Qty Store</th>
              <th class="text-end">Qty Warehouse</th>
              <th class="text-end">Qty Total</th>
              <th class="text-end">Aging Terlama</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $row)
              <tr>
                <td>{{ $rows->firstItem() + $loop->index }}</td>
                <td class="fw-semibold">{{ $row->product_code }}</td>
                <td class="product-col">{{ $row->product_name }}</td>
                <td class="location-col">{{ $row->store_names ?: '-' }}</td>
                <td class="location-col">{{ $row->warehouse_names ?: '-' }}</td>
                <td class="text-end">{{ number_format((int) $row->store_quantity, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format((int) $row->warehouse_quantity, 0, ',', '.') }}</td>
                <td class="text-end fw-bold">{{ number_format((int) $row->quantity_total, 0, ',', '.') }}</td>
                <td class="text-end">
                  <span class="badge {{ $agingBadge($row->max_aging_days) }}">
                    {{ number_format((int) $row->max_aging_days, 0, ',', '.') }} hari
                  </span>
                </td>
                <td class="text-center">
                  <button
                    type="button"
                    class="btn btn-sm btn-primary btn-detail-aging"
                    data-product-id="{{ $row->product_id }}"
                    data-product-code="{{ $row->product_code }}"
                    data-product-name="{{ $row->product_name }}"
                    data-detail-url="{{ route('monitoring.product-aging.detail', $row->product_id) }}"
                  >
                    <i class="mdi mdi-eye-outline me-1"></i> Detail
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted py-4">Belum ada data aging produk.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($rows->hasPages())
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
          <div class="text-muted small">
            Menampilkan {{ $rows->firstItem() ?? 0 }} - {{ $rows->lastItem() ?? 0 }}
            dari {{ number_format($rows->total(), 0, ',', '.') }} data
          </div>

          <div>
            {{ $rows->onEachSide(1)->links() }}
          </div>
        </div>
      @endif
    </div>
  </div>
</div>

{{-- MODAL DETAIL --}}
<div class="modal fade" id="modalDetailAging" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Detail Aging Produk</h5>
          <small class="text-muted" id="modal_product_info">-</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div id="detail_loading" class="text-center py-4 d-none">
          <div class="spinner-border spinner-border-sm me-2" role="status"></div>
          Memuat detail...
        </div>

        <div id="detail_error" class="alert alert-danger d-none mb-3"></div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="modal-summary-box">
              <div class="label">Produk</div>
              <div class="value" id="modal_product_name">-</div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="modal-summary-box">
              <div class="label">Total Detail</div>
              <div class="value" id="modal_total_rows">0</div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="modal-summary-box">
              <div class="label">Page</div>
              <div class="value" id="modal_page_info">1 / 1</div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm mb-0 table-nowrap aging-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Scope</th>
                <th>Warehouse</th>
                <th>Store</th>
                <th>Condition</th>
                <th>Expired</th>
                <th>Inbound Warehouse</th>
                <th>Inbound Store</th>
                <th>Aging Warehouse</th>
                <th>Aging Store</th>
                <th>Total Aging</th>
                <th class="text-end">Hari</th>
                <th class="text-end">Qty</th>
              </tr>
            </thead>

            <tbody id="detail_tbody">
              <tr>
                <td colspan="13" class="text-center text-muted">Belum ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="modal-pagination-info" id="modal_pagination_text">
          Menampilkan 0 - 0 dari 0 data
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_prev_modal_page">
            <i class="mdi mdi-chevron-left me-1"></i> Prev
          </button>

          <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_next_modal_page">
            Next <i class="mdi mdi-chevron-right ms-1"></i>
          </button>

          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            Tutup
          </button>
        </div>
      </div>
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
  });
})(window.jQuery);

document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('modalDetailAging');
  const modal = new bootstrap.Modal(modalEl);
  const tbody = document.getElementById('detail_tbody');
  const loading = document.getElementById('detail_loading');
  const errorBox = document.getElementById('detail_error');
  const productInfo = document.getElementById('modal_product_info');
  const modalProductName = document.getElementById('modal_product_name');
  const modalTotalRows = document.getElementById('modal_total_rows');
  const modalPageInfo = document.getElementById('modal_page_info');
  const modalPaginationText = document.getElementById('modal_pagination_text');
  const btnPrev = document.getElementById('btn_prev_modal_page');
  const btnNext = document.getElementById('btn_next_modal_page');

  const modalPerPage = 10;
  let detailRows = [];
  let currentPage = 1;

  function escapeHtml(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function buildQueryString() {
    const params = new URLSearchParams(window.location.search);
    return params.toString();
  }

  function getTotalPages() {
    return Math.max(1, Math.ceil(detailRows.length / modalPerPage));
  }

  function getPageRows() {
    const start = (currentPage - 1) * modalPerPage;
    const end = start + modalPerPage;
    return detailRows.slice(start, end);
  }

  function renderModalPagination() {
    const total = detailRows.length;
    const totalPages = getTotalPages();
    const start = total === 0 ? 0 : ((currentPage - 1) * modalPerPage) + 1;
    const end = Math.min(currentPage * modalPerPage, total);

    modalPageInfo.textContent = currentPage + ' / ' + totalPages;
    modalTotalRows.textContent = total.toLocaleString('id-ID');
    modalPaginationText.textContent = `Menampilkan ${start} - ${end} dari ${total} data`;

    btnPrev.disabled = currentPage <= 1;
    btnNext.disabled = currentPage >= totalPages;
  }

  function renderTableRows() {
    const rows = getPageRows();
    const startNumber = (currentPage - 1) * modalPerPage;

    if (!rows.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="13" class="text-center text-muted py-4">Tidak ada detail</td>
        </tr>
      `;
      renderModalPagination();
      return;
    }

    tbody.innerHTML = rows.map(function (row, index) {
      const number = startNumber + index + 1;
      const scopeLabel = row.scope_type
        ? row.scope_type.charAt(0).toUpperCase() + row.scope_type.slice(1)
        : '-';

      const scopeClass = row.scope_type === 'warehouse'
        ? 'badge-soft-warning'
        : 'badge-soft-info';

      const agingClass = Number(row.total_aging_days || 0) > 365
        ? 'badge-soft-danger'
        : (Number(row.total_aging_days || 0) > 180 ? 'badge-soft-warning' : 'badge-soft-success');

      return `
        <tr>
          <td>${number}</td>
          <td>
            <span class="badge ${scopeClass}">
              ${escapeHtml(scopeLabel)}
            </span>
          </td>
          <td>${escapeHtml(row.warehouse_name || '-')}</td>
          <td>${escapeHtml(row.store_name || '-')}</td>
          <td>${escapeHtml(row.condition_status || '-')}</td>
          <td>${row.expired_date ? escapeHtml(row.expired_date) : '-'}</td>
          <td>${row.warehouse_inbound_date ? escapeHtml(row.warehouse_inbound_date) : '-'}</td>
          <td>${row.store_inbound_date ? escapeHtml(row.store_inbound_date) : '-'}</td>
          <td>${escapeHtml(row.warehouse_aging_label || '-')}</td>
          <td>${escapeHtml(row.store_aging_label || '-')}</td>
          <td>
            <span class="badge ${agingClass}">
              ${escapeHtml(row.total_aging_label || '-')}
            </span>
            <div class="small text-muted mt-1">
              Dari ${escapeHtml(row.total_aging_start_display || '-')}
              sampai ${escapeHtml(row.as_of_now_display || '-')}
            </div>
          </td>
          <td class="text-end">${Number(row.total_aging_days || 0).toLocaleString('id-ID')}</td>
          <td class="text-end">${Number(row.quantity || 0).toLocaleString('id-ID')}</td>
        </tr>
      `;
    }).join('');

    renderModalPagination();
  }

  function resetModalState(productLabel) {
    detailRows = [];
    currentPage = 1;
    productInfo.textContent = productLabel || '-';
    modalProductName.textContent = productLabel || '-';
    modalTotalRows.textContent = '0';
    modalPageInfo.textContent = '1 / 1';
    modalPaginationText.textContent = 'Menampilkan 0 - 0 dari 0 data';
    errorBox.classList.add('d-none');
    errorBox.textContent = '';
    tbody.innerHTML = `
      <tr>
        <td colspan="13" class="text-center text-muted py-4">Memuat data...</td>
      </tr>
    `;
    btnPrev.disabled = true;
    btnNext.disabled = true;
  }

  btnPrev.addEventListener('click', function () {
    if (currentPage > 1) {
      currentPage--;
      renderTableRows();
    }
  });

  btnNext.addEventListener('click', function () {
    if (currentPage < getTotalPages()) {
      currentPage++;
      renderTableRows();
    }
  });

  document.querySelectorAll('.btn-detail-aging').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      const detailUrl = btn.getAttribute('data-detail-url');
      const productCode = btn.getAttribute('data-product-code') || '-';
      const productName = btn.getAttribute('data-product-name') || '-';
      const productLabel = productCode + ' - ' + productName;

      resetModalState(productLabel);
      loading.classList.remove('d-none');
      modal.show();

      try {
        const qs = buildQueryString();
        const url = qs ? (detailUrl + '?' + qs) : detailUrl;

        const res = await fetch(url, {
          headers: {
            'Accept': 'application/json'
          }
        });

        if (!res.ok) {
          throw new Error('Gagal memuat detail produk.');
        }

        const json = await res.json();
        detailRows = Array.isArray(json.rows) ? json.rows : [];
        currentPage = 1;

        renderTableRows();
      } catch (err) {
        detailRows = [];
        tbody.innerHTML = `
          <tr>
            <td colspan="13" class="text-center text-muted py-4">Gagal memuat detail</td>
          </tr>
        `;
        errorBox.textContent = err.message || 'Terjadi kesalahan.';
        errorBox.classList.remove('d-none');
        renderModalPagination();
      } finally {
        loading.classList.add('d-none');
      }
    });
  });
});
</script>
@endpush
