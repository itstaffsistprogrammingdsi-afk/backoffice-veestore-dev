@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Stok Produk Fisik')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --ps-primary: #5b5cf6;
    --ps-primary-dark: #4338ca;
    --ps-primary-soft: rgba(91, 92, 246, .12);
    --ps-success: #22c55e;
    --ps-success-soft: rgba(34, 197, 94, .14);
    --ps-info: #06b6d4;
    --ps-info-soft: rgba(6, 182, 212, .14);
    --ps-warning: #f59e0b;
    --ps-warning-soft: rgba(245, 158, 11, .16);
    --ps-danger: #ef4444;
    --ps-danger-soft: rgba(239, 68, 68, .14);
    --ps-purple: #8b5cf6;
    --ps-dark: #1e293b;
    --ps-muted: #64748b;
    --ps-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--ps-body);
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
    border-color: var(--ps-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
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
    border-color: var(--ps-primary);
    background: linear-gradient(135deg, var(--ps-primary), var(--ps-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--ps-success);
    background: linear-gradient(135deg, var(--ps-success), #16a34a);
  }

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: #fff;
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--ps-primary), var(--ps-success));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--ps-dark);
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
    background: linear-gradient(135deg, var(--ps-primary), var(--ps-primary-dark));
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

  .summary-icon.purple {
    background: linear-gradient(135deg, #8b5cf6, #6d28d9);
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

  .physical-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .physical-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--ps-muted);
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
    background: var(--ps-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--ps-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--ps-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--ps-info-soft) !important;
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

  .amount-success {
    color: #15803d;
    font-weight: 800;
  }

  .amount-primary {
    color: #4338ca;
    font-weight: 800;
  }

  .nav-stock-mode {
    gap: .5rem;
  }

  .nav-stock-mode .nav-link {
    border-radius: 999px;
    font-weight: 700;
  }

  .nav-stock-mode .nav-link.active {
    background: linear-gradient(135deg, var(--ps-primary), var(--ps-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .20);
  }

  .chart-box {
    min-height: 320px;
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
  $mode = $mode ?? 'all';

  $titleMode = match($mode) {
    'store' => 'Store',
    'warehouse' => 'Warehouse',
    default => 'All Stok',
  };

  $summary = $summary ?? (object) [
    'total_qty' => 0,
    'total_store' => 0,
    'total_warehouse' => 0,
    'total_store_stock' => 0,
    'total_store_display' => 0,
    'total_warehouse_stock' => 0,
    'total_good' => 0,
    'total_good_after_repair' => 0,
    'total_bad' => 0,
    'total_nominal' => 0,
  ];

  $baseRoute = 'monitoring.physical-stocks.index';

  $hasFilter = request()->hasAny([
    'q',
    'location_key',
    'min',
    'per_page',
  ]);

  $chartLocationData = ($chartByLocation ?? collect())->map(function ($row) {
    return [
      'label' => ucfirst((string) $row->location_type) . ' - ' . (string) $row->location_name,
      'value' => (int) $row->total_qty,
      'nominal' => (float) ($row->total_nominal ?? 0),
    ];
  })->values();

  $chartProductData = ($chartByProduct ?? collect())->map(function ($row) {
    return [
      'label' => trim((string) ($row->product_code ?? '-') . ' - ' . (string) ($row->product_name ?? '-')),
      'value' => (int) $row->total_qty,
      'nominal' => (float) ($row->total_nominal ?? 0),
    ];
  })->values();
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Stok Produk Fisik
        </h4>
        <div class="hero-subtitle">
          Monitoring akumulasi stok fisik store dan warehouse, harga produk sesuai price type, serta total nominal persediaan.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-package-variant-closed me-1"></i>
          {{ number_format((int) $summary->total_qty, 0, ',', '.') }} Qty
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-store-outline me-1"></i>
          {{ number_format((int) $summary->total_store, 0, ',', '.') }} Store
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-cash-multiple me-1"></i>
          Rp {{ number_format((float) $summary->total_nominal, 0, ',', '.') }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ $lastUpdate ?? now()->format('d-m-Y H:i:s') }}
        </span>
      </div>
    </div>
  </div>

  {{-- MODE --}}
  <div class="card mb-4">
    <div class="card-body">
      <ul class="nav nav-pills nav-stock-mode flex-wrap">
        <li class="nav-item">
          <a class="nav-link {{ $mode === 'all' ? 'active' : '' }}"
             href="{{ route($baseRoute, ['mode' => 'all']) }}">
            <i class="mdi mdi-chart-donut me-1"></i> All Stok
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ $mode === 'store' ? 'active' : '' }}"
             href="{{ route($baseRoute, ['mode' => 'store']) }}">
            <i class="mdi mdi-store-outline me-1"></i> Store
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ $mode === 'warehouse' ? 'active' : '' }}"
             href="{{ route($baseRoute, ['mode' => 'warehouse']) }}">
            <i class="mdi mdi-warehouse me-1"></i> Warehouse
          </a>
        </li>
      </ul>
    </div>
  </div>

  {{-- SUMMARY --}}
  <div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Total Stok Fisik</div>
              <h4 class="mb-1">{{ number_format((int) $summary->total_qty, 0, ',', '.') }}</h4>
              <small class="text-muted">Total semua qty item</small>
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
              <div class="text-muted small">Total Nominal</div>
              <h4 class="mb-1">Rp {{ number_format((float) $summary->total_nominal, 0, ',', '.') }}</h4>
              <small class="text-muted">Harga produk × qty</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-cash-multiple"></i>
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
              <div class="text-muted small">Total Store</div>
              <h4 class="mb-1">{{ number_format((int) $summary->total_store, 0, ',', '.') }}</h4>
              <small class="text-muted">
                Stock: {{ number_format((int) $summary->total_store_stock, 0, ',', '.') }}
                |
                Display: {{ number_format((int) $summary->total_store_display, 0, ',', '.') }}
              </small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-store-outline"></i>
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
              <div class="text-muted small">Total Warehouse</div>
              <h4 class="mb-1">{{ number_format((int) $summary->total_warehouse, 0, ',', '.') }}</h4>
              <small class="text-muted">
                Warehouse stock: {{ number_format((int) $summary->total_warehouse_stock, 0, ',', '.') }}
              </small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-warehouse"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-5">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0 fw-bold">Diagram Total Store vs Warehouse</h5>
        </div>
        <div class="card-body chart-box">
          <canvas id="chartStockType"></canvas>
        </div>
      </div>
    </div>

    <div class="col-xl-7">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0 fw-bold">Top Lokasi Berdasarkan Stok</h5>
        </div>
        <div class="card-body chart-box">
          <canvas id="chartLocation"></canvas>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0 fw-bold">Top Produk Berdasarkan Total Stok</h5>
        </div>
        <div class="card-body chart-box">
          <canvas id="chartProduct"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- FILTER + TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Data Akumulasi Stok Fisik - {{ $titleMode }}</h5>
        <small class="text-muted">
          Total Data: <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <form method="GET"
              action="{{ route($baseRoute) }}"
              class="d-flex gap-2 flex-wrap align-items-center">

          <input type="hidden" name="mode" value="{{ $mode }}">

          <input type="text"
                 name="q"
                 value="{{ $filters['q'] ?? '' }}"
                 class="form-control form-control-sm"
                 placeholder="Cari lokasi / kode produk / nama produk"
                 style="min-width: 300px;">

          <input type="hidden" name="location_key" value="{{ $filters['location_key'] ?? '' }}">
          <input type="hidden" name="min" value="{{ $filters['min'] ?? 0 }}">
          <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? 10 }}">

          <button type="button"
                  class="btn btn-outline-secondary btn-sm"
                  data-bs-toggle="collapse"
                  data-bs-target="#advancedFilterPhysicalStock">
            <i class="mdi mdi-filter-variant me-1"></i> Filter
          </button>

          <button type="submit"
                  class="btn btn-primary btn-sm"
                  data-bs-toggle="tooltip"
                  title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>

          <a href="{{ route($baseRoute, ['mode' => $mode]) }}"
             class="btn btn-outline-secondary btn-sm">
            <i class="mdi mdi-refresh me-1"></i> Reset
          </a>

          <a href="{{ route('monitoring.physical-stocks.export', request()->query()) }}"
             class="btn btn-success btn-sm">
            <i class="mdi mdi-file-excel-outline me-1"></i> Export
          </a>
        </form>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterPhysicalStock">
        <div class="filter-box">
          <form method="GET"
                action="{{ route($baseRoute) }}"
                class="row g-3">

            <input type="hidden" name="mode" value="{{ $mode }}">

            <div class="col-md-3">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $filters['q'] ?? '' }}"
                     placeholder="Lokasi / kode produk / nama produk">
            </div>

            <div class="col-md-3">
              <label class="form-label">Lokasi</label>
              <select name="location_key"
                      class="form-select js-select2-location"
                      data-placeholder="Semua Lokasi">
                <option value="">Semua Lokasi</option>
                @foreach($locations as $location)
                  <option value="{{ $location->key }}"
                          @selected(($filters['location_key'] ?? '') === $location->key)>
                    {{ $location->label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Minimal Total Stok</label>
              <input type="number"
                     name="min"
                     class="form-control"
                     min="0"
                     value="{{ $filters['min'] ?? 0 }}">
            </div>

            <div class="col-md-2">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select js-select2-location" data-placeholder="Jumlah Data">
                @foreach([10, 25, 50, 100, 200] as $size)
                  <option value="{{ $size }}" @selected((int)($filters['per_page'] ?? 10) === $size)>
                    {{ $size }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">&nbsp;</label>
              <div class="text-muted small pt-2">
                Harga mengikuti price type lokasi.
              </div>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.physical-stocks.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route($baseRoute, ['mode' => $mode]) }}"
                   class="btn btn-outline-secondary btn-sm">
                  <i class="mdi mdi-refresh me-1"></i> Reset
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap physical-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Tipe</th>
              <th>Lokasi</th>
              <th>Kode Produk</th>
              <th>Nama Produk</th>
              <th>Satuan</th>
              <th>Price Type</th>
              <th class="text-end">Harga Produk</th>
              <th class="text-end">Store Stock</th>
              <th class="text-end">Store Display</th>
              <th class="text-end">Warehouse</th>
              <th class="text-end">Good</th>
              <th class="text-end">Good After Repair</th>
              <th class="text-end">Bad</th>
              <th class="text-end">Total Qty</th>
              <th class="text-end">Total Nominal</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $row)
              <tr>
                <td>{{ $rows->firstItem() + $loop->index }}</td>

                <td>
                  @if($row->location_type === 'store')
                    <span class="badge badge-soft-info">
                      <i class="mdi mdi-store-outline me-1"></i> Store
                    </span>
                  @elseif($row->location_type === 'warehouse')
                    <span class="badge badge-soft-warning">
                      <i class="mdi mdi-warehouse me-1"></i> Warehouse
                    </span>
                  @else
                    <span class="badge badge-soft-secondary">-</span>
                  @endif
                </td>

                <td class="location-col">
                  <div class="fw-semibold">{{ $row->location_name ?: '-' }}</div>
                  @if(!empty($row->location_code))
                    <small class="text-muted">{{ $row->location_code }}</small>
                  @endif
                </td>

                <td class="fw-semibold">{{ $row->product_code ?: '-' }}</td>

                <td class="product-col">
                  {{ $row->product_name ?: '-' }}
                </td>

                <td>{{ $row->unit_name ?: '-' }}</td>

                <td>
                  @if($row->price_type)
                    <span class="badge badge-soft-success">
                      {{ ucfirst($row->price_type) }}
                    </span>
                  @else
                    <span class="badge badge-soft-secondary">-</span>
                  @endif
                </td>

                <td class="text-end amount-primary">
                  Rp {{ number_format((float) ($row->product_price ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">{{ number_format((int) $row->qty_store_stock, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format((int) $row->qty_store_display, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format((int) $row->qty_warehouse_stock, 0, ',', '.') }}</td>

                <td class="text-end">{{ number_format((int) $row->qty_good, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format((int) $row->qty_good_after_repair, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format((int) $row->qty_bad, 0, ',', '.') }}</td>

                <td class="text-end fw-semibold">
                  {{ number_format((int) $row->total_qty, 0, ',', '.') }}
                </td>

                <td class="text-end amount-success">
                  Rp {{ number_format((float) ($row->total_nominal ?? 0), 0, ',', '.') }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="16" class="text-center text-muted py-4">
                  Belum ada data stok fisik.
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function ($) {
  'use strict';

  function initSelect2Location() {
    $('.js-select2-location').each(function () {
      const $el = $(this);

      if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
      }

      $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: $el.data('placeholder') || 'Semua Lokasi'
      });
    });
  }

  const summary = @json($summary);
  const chartByLocation = @json($chartLocationData);
  const chartByProduct = @json($chartProductData);

  function makeChart(ctx, config) {
    if (!ctx) {
      return null;
    }

    return new Chart(ctx, config);
  }

  const palette = [
    'rgba(91, 92, 246, 0.75)',
    'rgba(34, 197, 94, 0.75)',
    'rgba(6, 182, 212, 0.75)',
    'rgba(245, 158, 11, 0.75)',
    'rgba(239, 68, 68, 0.75)',
    'rgba(139, 92, 246, 0.75)',
    'rgba(14, 165, 233, 0.75)',
    'rgba(16, 185, 129, 0.75)',
    'rgba(249, 115, 22, 0.75)',
    'rgba(100, 116, 139, 0.75)'
  ];

  const paletteBorder = [
    'rgba(91, 92, 246, 1)',
    'rgba(34, 197, 94, 1)',
    'rgba(6, 182, 212, 1)',
    'rgba(245, 158, 11, 1)',
    'rgba(239, 68, 68, 1)',
    'rgba(139, 92, 246, 1)',
    'rgba(14, 165, 233, 1)',
    'rgba(16, 185, 129, 1)',
    'rgba(249, 115, 22, 1)',
    'rgba(100, 116, 139, 1)'
  ];

  makeChart(document.getElementById('chartStockType'), {
    type: 'doughnut',
    data: {
      labels: ['Store', 'Warehouse'],
      datasets: [{
        data: [
          Number(summary.total_store || 0),
          Number(summary.total_warehouse || 0)
        ],
        backgroundColor: [
          'rgba(34, 197, 94, 0.75)',
          'rgba(6, 182, 212, 0.75)'
        ],
        borderColor: [
          'rgba(34, 197, 94, 1)',
          'rgba(6, 182, 212, 1)'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  });

  makeChart(document.getElementById('chartLocation'), {
    type: 'bar',
    data: {
      labels: chartByLocation.map(item => item.label),
      datasets: [{
        label: 'Total Stok',
        data: chartByLocation.map(item => item.value),
        backgroundColor: chartByLocation.map((_, index) => palette[index % palette.length]),
        borderColor: chartByLocation.map((_, index) => paletteBorder[index % paletteBorder.length]),
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        x: {
          beginAtZero: true
        }
      }
    }
  });

  makeChart(document.getElementById('chartProduct'), {
    type: 'bar',
    data: {
      labels: chartByProduct.map(item => item.label),
      datasets: [{
        label: 'Total Stok',
        data: chartByProduct.map(item => item.value),
        backgroundColor: chartByProduct.map((_, index) => palette[index % palette.length]),
        borderColor: chartByProduct.map((_, index) => paletteBorder[index % paletteBorder.length]),
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

  $(document).ready(function () {
    initSelect2Location();

    const ttNodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    ttNodes.forEach(el => new bootstrap.Tooltip(el));
  });
})(window.jQuery);
</script>
@endpush