@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Produk Slowmoving')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --sl-primary: #5b5cf6;
    --sl-primary-dark: #4338ca;
    --sl-success: #22c55e;
    --sl-success-soft: rgba(34, 197, 94, .14);
    --sl-info: #06b6d4;
    --sl-info-soft: rgba(6, 182, 212, .14);
    --sl-warning: #f59e0b;
    --sl-warning-soft: rgba(245, 158, 11, .16);
    --sl-danger: #ef4444;
    --sl-danger-soft: rgba(239, 68, 68, .14);
    --sl-purple: #8b5cf6;
    --sl-dark: #1e293b;
    --sl-muted: #64748b;
    --sl-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(245, 158, 11, .10), transparent 28%),
      var(--sl-body);
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
    border-color: var(--sl-primary);
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
    border-color: var(--sl-primary);
    background: linear-gradient(135deg, var(--sl-primary), var(--sl-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--sl-success);
    background: linear-gradient(135deg, var(--sl-success), #16a34a);
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
    background: linear-gradient(90deg, var(--sl-primary), var(--sl-warning));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--sl-dark);
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
    background: linear-gradient(135deg, var(--sl-primary), var(--sl-primary-dark));
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

  .nav-slow-mode {
    gap: .5rem;
  }

  .nav-slow-mode .nav-link,
  .slow-tab .nav-link {
    border-radius: 999px;
    font-weight: 700;
  }

  .nav-slow-mode .nav-link.active,
  .slow-tab .nav-link.active {
    background: linear-gradient(135deg, var(--sl-primary), var(--sl-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .20);
  }

  .chart-box {
    min-height: 320px;
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

  .product-slow-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .product-slow-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .product-col {
    min-width: 260px;
    white-space: normal !important;
  }

  .location-col {
    min-width: 240px;
    white-space: normal !important;
  }

  .badge-soft-success {
    background: var(--sl-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--sl-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--sl-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--sl-info-soft) !important;
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
  $mode = $mode ?? 'store';
  $baseRoute = 'monitoring.product-slowmoving.index';

  $titleMode = $mode === 'warehouse' ? 'Warehouse' : 'Store';

  $selectedLocation = $mode === 'warehouse'
    ? ($filters['warehouse_id'] ?? '')
    : ($filters['store_id'] ?? '');

  $locationLabel = $mode === 'warehouse' ? 'Warehouse' : 'Store';

  $summary = $summary ?? [
    'slow_count' => 0,
    'dead_count' => 0,
    'slow_qty' => 0,
    'dead_qty' => 0,
    'total_qty' => 0,
    'slow_total_nominal' => 0,
    'dead_total_nominal' => 0,
    'total_nominal' => 0,
  ];

  $chartStatusData = ($chartStatus ?? collect())->map(function ($row) {
    return [
      'label' => (string) $row->label,
      'value' => (int) $row->value,
      'nominal' => (float) ($row->nominal ?? 0),
    ];
  })->values();

  $chartProductsSlowData = ($chartProductsSlow ?? collect())->map(function ($row) {
    return [
      'label' => trim((string) ($row->product_code ?? '-') . ' - ' . (string) ($row->product_name ?? '-')),
      'value' => (int) $row->quantity,
      'nominal' => (float) ($row->total_nominal ?? 0),
    ];
  })->values();

  $chartProductsDeadData = ($chartProductsDead ?? collect())->map(function ($row) {
    return [
      'label' => trim((string) ($row->product_code ?? '-') . ' - ' . (string) ($row->product_name ?? '-')),
      'value' => (int) $row->quantity,
      'nominal' => (float) ($row->total_nominal ?? 0),
    ];
  })->values();

  $formatLocation = function ($row) {
    $location = $row->room_name ?? '-';

    if (!empty($row->rack_name)) {
      $location .= ' / ' . $row->rack_name;
    }

    if (!empty($row->layer_name)) {
      $location .= ' / ' . $row->layer_name;
    }

    return $location;
  };

  $conditionBadge = function ($condition) {
    return strtolower((string) $condition) === 'good' ? 'badge-soft-success' : 'badge-soft-danger';
  };

  $stockBadge = function ($stock) {
    $stock = strtolower((string) $stock);

    return match($stock) {
      'display' => 'badge-soft-info',
      'stock' => 'badge-soft-primary',
      'warehouse' => 'badge-soft-warning',
      default => 'badge-soft-secondary',
    };
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Produk Slowmoving
        </h4>
        <div class="hero-subtitle">
          Monitoring barang slow moving dan deadstock yang belum terjual, lengkap dengan harga produk dan estimasi total omset.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-package-variant-closed me-1"></i>
          {{ number_format((int) ($summary['total_qty'] ?? 0), 0, ',', '.') }} Qty
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-chart-timeline-variant me-1"></i>
          {{ number_format((int) ($summary['slow_qty'] ?? 0), 0, ',', '.') }} Slow
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-cash-multiple me-1"></i>
          Rp {{ number_format((float) ($summary['total_nominal'] ?? 0), 0, ',', '.') }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ $lastUpdate ?? now()->format('d-m-Y H:i:s') }}
        </span>
      </div>
    </div>
  </div>

  {{-- MODE TAB --}}
  <div class="card mb-4">
    <div class="card-body">
      <ul class="nav nav-pills nav-slow-mode flex-wrap">
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

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Produk Slowmoving - {{ $titleMode }}</h5>
        <small class="text-muted">
          Pilih {{ $locationLabel }} dan periode tanggal untuk menampilkan barang slow moving serta deadstock.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterProductSlow">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route($baseRoute, ['mode' => $mode]) }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse show" id="advancedFilterProductSlow">
        <div class="filter-box">
          <form method="GET"
                action="{{ route($baseRoute) }}"
                class="row g-3">

            <input type="hidden" name="mode" value="{{ $mode }}">

            @if($mode === 'store')
              <div class="col-md-4">
                <label class="form-label">Store</label>
                <select name="store_id"
                        class="form-select js-select2-filter"
                        data-placeholder="Pilih Store"
                        required>
                  <option value=""></option>
                  @foreach($stores as $store)
                    <option value="{{ $store->id }}"
                      @selected((string)($filters['store_id'] ?? '') === (string)$store->id)>
                      {{ $store->name }}
                    </option>
                  @endforeach
                </select>
              </div>
            @else
              <div class="col-md-4">
                <label class="form-label">Warehouse</label>
                <select name="warehouse_id"
                        class="form-select js-select2-filter"
                        data-placeholder="Pilih Warehouse"
                        required>
                  <option value=""></option>
                  @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}"
                      @selected((string)($filters['warehouse_id'] ?? '') === (string)$warehouse->id)>
                      {{ $warehouse->name }}
                    </option>
                  @endforeach
                </select>
              </div>
            @endif

            <div class="col-md-4">
              <label class="form-label">Tanggal Dari</label>
              <input type="date"
                     name="start_date"
                     class="form-control"
                     value="{{ $filters['start_date'] ?? '' }}"
                     required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Tanggal Sampai</label>
              <input type="date"
                     name="end_date"
                     class="form-control"
                     value="{{ $filters['end_date'] ?? '' }}"
                     required>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                @if(!empty($selectedLocation))
                  <a href="{{ route('monitoring.product-slowmoving.export-slow', request()->query()) }}"
                     class="btn btn-success btn-sm">
                    <i class="mdi mdi-file-excel-outline me-1"></i> Export Slow Moving
                  </a>

                  <a href="{{ route('monitoring.product-slowmoving.export-dead', request()->query()) }}"
                     class="btn btn-success btn-sm">
                    <i class="mdi mdi-file-excel-outline me-1"></i> Export Deadstock
                  </a>
                @endif

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
  </div>

  {{-- SUMMARY --}}
  <div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Total Qty</div>
              <h4 class="mb-1">{{ number_format((int) ($summary['total_qty'] ?? 0), 0, ',', '.') }}</h4>
              <small class="text-muted">Slow moving + deadstock</small>
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
              <div class="text-muted small">Total Omset Belum Terjual</div>
              <h4 class="mb-1">Rp {{ number_format((float) ($summary['total_nominal'] ?? 0), 0, ',', '.') }}</h4>
              <small class="text-muted">Harga × qty barang belum terjual</small>
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
              <div class="text-muted small">Slow Moving</div>
              <h4 class="mb-1">{{ number_format((int) ($summary['slow_qty'] ?? 0), 0, ',', '.') }}</h4>
              <small class="text-muted">
                Rp {{ number_format((float) ($summary['slow_total_nominal'] ?? 0), 0, ',', '.') }}
              </small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-chart-timeline-variant"></i>
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
              <div class="text-muted small">Deadstock</div>
              <h4 class="mb-1">{{ number_format((int) ($summary['dead_qty'] ?? 0), 0, ',', '.') }}</h4>
              <small class="text-muted">
                Rp {{ number_format((float) ($summary['dead_total_nominal'] ?? 0), 0, ',', '.') }}
              </small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-package-variant-closed-remove"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0 fw-bold">Komposisi Slow Moving vs Deadstock</h5>
        </div>
        <div class="card-body chart-box">
          <canvas id="chartSlowDead"></canvas>
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0 fw-bold">
            Top Produk <span id="chartTopProductTitle">Slow Moving</span>
            <small class="text-muted">(Akumulasi per Produk)</small>
          </h5>
        </div>
        <div class="card-body chart-box">
          <canvas id="chartTopProduct"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- DATA TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Data Produk Slowmoving - {{ $titleMode }}</h5>
        <small class="text-muted">
          Harga mengikuti price type:
          <b>{{ $mode === 'warehouse' ? 'base' : 'store' }}</b>
        </small>
      </div>
    </div>

    @if(empty($selectedLocation))
      <div class="card-body">
        <div class="alert alert-info mb-0">
          Silakan pilih <strong>{{ $locationLabel }}</strong> terlebih dahulu.
        </div>
      </div>
    @else
      <div class="card-body tab-content pt-0">
        <ul class="nav nav-pills slow-tab mb-3" role="tablist">
          <li class="nav-item">
            <button class="nav-link active"
                    data-bs-toggle="tab"
                    data-bs-target="#tabSlow"
                    type="button"
                    role="tab">
              Slow Moving
            </button>
          </li>

          <li class="nav-item">
            <button class="nav-link"
                    data-bs-toggle="tab"
                    data-bs-target="#tabDead"
                    type="button"
                    role="tab">
              Deadstock
            </button>
          </li>
        </ul>

        <div class="tab-pane fade show active" id="tabSlow" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm mb-0 table-nowrap product-slow-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Kode Produk</th>
                  <th>Nama Produk</th>
                  <th>Kondisi</th>
                  <th>Status Stock</th>
                  <th>Lokasi</th>
                  <th>Price Type</th>
                  <th class="text-end">Harga</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Total Omset</th>
                </tr>
              </thead>

              <tbody>
                @forelse($slow as $row)
                  @php
                    $condition = $row->item_condition ?? $row->status ?? '-';
                    $location = $formatLocation($row);
                  @endphp

                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->product_code ?? '-' }}</td>
                    <td class="product-col">{{ $row->product_name ?? '-' }}</td>
                    <td>
                      <span class="badge {{ $conditionBadge($condition) }}">
                        {{ $condition }}
                      </span>
                    </td>
                    <td>
                      <span class="badge {{ $stockBadge($row->stock_status ?? '-') }}">
                        {{ $row->stock_status ?? '-' }}
                      </span>
                    </td>
                    <td class="location-col">{{ $location }}</td>
                    <td>
                      <span class="badge badge-soft-success">
                        {{ ucfirst($row->price_type ?? '-') }}
                      </span>
                    </td>
                    <td class="text-end amount-primary">
                      Rp {{ number_format((float) ($row->product_price ?? 0), 0, ',', '.') }}
                    </td>
                    <td class="text-end fw-semibold">
                      {{ number_format((int) ($row->quantity ?? 0), 0, ',', '.') }}
                    </td>
                    <td class="text-end amount-success">
                      Rp {{ number_format((float) ($row->total_nominal ?? 0), 0, ',', '.') }}
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                      Belum ada data slow moving.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="tab-pane fade" id="tabDead" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm mb-0 table-nowrap product-slow-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Kode Produk</th>
                  <th>Nama Produk</th>
                  <th>Kondisi</th>
                  <th>Status Stock</th>
                  <th>Lokasi</th>
                  <th>Price Type</th>
                  <th class="text-end">Harga</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Total Omset</th>
                </tr>
              </thead>

              <tbody>
                @forelse($dead as $row)
                  @php
                    $condition = $row->item_condition ?? $row->status ?? '-';
                    $location = $formatLocation($row);
                  @endphp

                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $row->product_code ?? '-' }}</td>
                    <td class="product-col">{{ $row->product_name ?? '-' }}</td>
                    <td>
                      <span class="badge {{ $conditionBadge($condition) }}">
                        {{ $condition }}
                      </span>
                    </td>
                    <td>
                      <span class="badge {{ $stockBadge($row->stock_status ?? '-') }}">
                        {{ $row->stock_status ?? '-' }}
                      </span>
                    </td>
                    <td class="location-col">{{ $location }}</td>
                    <td>
                      <span class="badge badge-soft-success">
                        {{ ucfirst($row->price_type ?? '-') }}
                      </span>
                    </td>
                    <td class="text-end amount-primary">
                      Rp {{ number_format((float) ($row->product_price ?? 0), 0, ',', '.') }}
                    </td>
                    <td class="text-end fw-semibold">
                      {{ number_format((int) ($row->quantity ?? 0), 0, ',', '.') }}
                    </td>
                    <td class="text-end amount-success">
                      Rp {{ number_format((float) ($row->total_nominal ?? 0), 0, ',', '.') }}
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                      Belum ada data deadstock.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif
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

  const chartStatus = @json($chartStatusData);
  const chartProductsSlow = @json($chartProductsSlowData);
  const chartProductsDead = @json($chartProductsDeadData);

  function makeChart(ctx, config) {
    if (!ctx) {
      return null;
    }

    return new Chart(ctx, config);
  }

  const palette = [
    'rgba(91, 92, 246, 0.75)',
    'rgba(245, 158, 11, 0.75)',
    'rgba(239, 68, 68, 0.75)',
    'rgba(34, 197, 94, 0.75)',
    'rgba(6, 182, 212, 0.75)',
    'rgba(139, 92, 246, 0.75)',
    'rgba(249, 115, 22, 0.75)',
    'rgba(100, 116, 139, 0.75)'
  ];

  const paletteBorder = [
    'rgba(91, 92, 246, 1)',
    'rgba(245, 158, 11, 1)',
    'rgba(239, 68, 68, 1)',
    'rgba(34, 197, 94, 1)',
    'rgba(6, 182, 212, 1)',
    'rgba(139, 92, 246, 1)',
    'rgba(249, 115, 22, 1)',
    'rgba(100, 116, 139, 1)'
  ];

  makeChart(document.getElementById('chartSlowDead'), {
    type: 'doughnut',
    data: {
      labels: chartStatus.map(item => item.label),
      datasets: [{
        data: chartStatus.map(item => item.value),
        backgroundColor: [
          'rgba(245, 158, 11, 0.75)',
          'rgba(239, 68, 68, 0.75)'
        ],
        borderColor: [
          'rgba(245, 158, 11, 1)',
          'rgba(239, 68, 68, 1)'
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

  const chartTopProduct = makeChart(document.getElementById('chartTopProduct'), {
    type: 'bar',
    data: {
      labels: chartProductsSlow.map(item => item.label),
      datasets: [{
        label: 'Qty',
        data: chartProductsSlow.map(item => item.value),
        backgroundColor: chartProductsSlow.map((_, index) => palette[index % palette.length]),
        borderColor: chartProductsSlow.map((_, index) => paletteBorder[index % paletteBorder.length]),
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

  function updateTopProductChart(type) {
    const source = type === 'dead' ? chartProductsDead : chartProductsSlow;
    const titleEl = document.getElementById('chartTopProductTitle');

    if (titleEl) {
      titleEl.textContent = type === 'dead' ? 'Deadstock' : 'Slow Moving';
    }

    if (!chartTopProduct) {
      return;
    }

    chartTopProduct.data.labels = source.map(item => item.label);
    chartTopProduct.data.datasets[0].data = source.map(item => item.value);
    chartTopProduct.data.datasets[0].backgroundColor = source.map((_, index) => palette[index % palette.length]);
    chartTopProduct.data.datasets[0].borderColor = source.map((_, index) => paletteBorder[index % paletteBorder.length]);
    chartTopProduct.update();
  }

  $(document).ready(function () {
    initFilterSelect2();

    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
      tab.addEventListener('shown.bs.tab', function (event) {
        const target = event.target.getAttribute('data-bs-target');

        if (target === '#tabDead') {
          updateTopProductChart('dead');
        } else {
          updateTopProductChart('slow');
        }
      });
    });

    const ttNodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    ttNodes.forEach(el => new bootstrap.Tooltip(el));
  });
})(window.jQuery);
</script>
@endpush