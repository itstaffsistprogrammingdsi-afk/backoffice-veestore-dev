@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Tracking Barang')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --tb-primary: #5b5cf6;
    --tb-primary-dark: #4338ca;
    --tb-success: #22c55e;
    --tb-success-soft: rgba(34, 197, 94, .14);
    --tb-info: #06b6d4;
    --tb-info-soft: rgba(6, 182, 212, .14);
    --tb-warning: #f59e0b;
    --tb-warning-soft: rgba(245, 158, 11, .16);
    --tb-danger: #ef4444;
    --tb-danger-soft: rgba(239, 68, 68, .14);
    --tb-purple: #8b5cf6;
    --tb-dark: #1e293b;
    --tb-muted: #64748b;
    --tb-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(6, 182, 212, .10), transparent 28%),
      var(--tb-body);
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
    border-color: var(--tb-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .dashboard-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    color: #fff;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
      linear-gradient(135deg, #4f46e5 0%, #06b6d4 58%, #22c55e 120%);
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
    font-weight: 700;
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

  .tracking-filter-box {
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
    border-color: var(--tb-primary);
    background: linear-gradient(135deg, var(--tb-primary), var(--tb-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--tb-success);
    background: linear-gradient(135deg, var(--tb-success), #16a34a);
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
    background: linear-gradient(90deg, var(--tb-primary), var(--tb-info));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--tb-dark);
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
    background: linear-gradient(135deg, var(--tb-primary), var(--tb-primary-dark));
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

  .tracking-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .tracking-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
    transition: .2s ease;
  }

  .product-cell {
    min-width: 260px;
    white-space: normal !important;
  }

  .product-id-cell {
    min-width: 220px;
    white-space: normal !important;
  }

  .last-update-cell {
    min-width: 150px;
  }

  .aksi-cell {
    min-width: 120px;
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--tb-muted);
  }

  .badge-soft-success {
    background: var(--tb-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--tb-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--tb-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--tb-info-soft) !important;
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

  .count-pill {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    min-width: 42px;
    border-radius: .75rem;
    padding: .35rem .55rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-weight: 800;
    color: #334155;
  }

  .count-pill.success {
    background: var(--tb-success-soft);
    color: #15803d;
    border-color: rgba(34, 197, 94, .22);
  }

  .count-pill.info {
    background: var(--tb-info-soft);
    color: #0369a1;
    border-color: rgba(6, 182, 212, .22);
  }

  .count-pill.warning {
    background: var(--tb-warning-soft);
    color: #b45309;
    border-color: rgba(245, 158, 11, .22);
  }

  .count-pill.danger {
    background: var(--tb-danger-soft);
    color: #b91c1c;
    border-color: rgba(239, 68, 68, .22);
  }

  .status-legend {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
  }

  .status-legend .badge {
    font-size: .74rem;
  }

  @media (max-width: 767.98px) {
    .dashboard-hero {
      border-radius: 1rem;
    }

    .card-body {
      padding: 1rem;
    }

    .card-header {
      padding: 1rem;
    }
  }
</style>
@endpush

@section('content')
@php
  $pageRows = method_exists($rows, 'getCollection') ? $rows->getCollection() : collect($rows);

  $totalProductData = method_exists($rows, 'total') ? (int) $rows->total() : (int) $pageRows->count();

  $summaryTotalItems = (int) $pageRows->sum(function ($row) {
    return (int) ($row->total_items ?? 0);
  });

  $summaryWarehouseItems = (int) $pageRows->sum(function ($row) {
    return (int) ($row->inbound_warehouse_items ?? 0)
      + (int) ($row->in_warehouse_items ?? 0)
      + (int) ($row->stock_warehouse_items ?? 0)
      + (int) ($row->outbound_warehouse_items ?? 0);
  });

  $summaryStoreItems = (int) $pageRows->sum(function ($row) {
    return (int) ($row->inbound_store_items ?? 0)
      + (int) ($row->stock_store_items ?? 0)
      + (int) ($row->display_store_items ?? 0);
  });

  $summarySoldItems = (int) $pageRows->sum(function ($row) {
    return (int) ($row->sold_items ?? 0);
  });

  $hasFilter = request()->hasAny([
    'keyword',
    'product_id',
    'item_id',
    'store_id',
    'warehouse_id',
    'tracking_status',
    'per_page',
  ]);

  $trackingStatusLabel = function ($status) {
    return match((string) $status) {
      'inbound_warehouse' => 'Inbound Warehouse',
      'in_warehouse' => 'In Warehouse',
      'stock_warehouse' => 'Stock Warehouse',
      'outbound_warehouse' => 'Outbound Warehouse',
      'inbound_store' => 'Inbound Store',
      'stock_store' => 'Stock Store',
      'display_store' => 'Display Store',
      'sold' => 'Sold',
      default => 'All Tracking Status',
    };
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Tracking Barang
        </h4>
        <div class="hero-subtitle">
          Monitoring perjalanan barang per produk dari warehouse, store, display, sampai sold berdasarkan item dan RFID.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-package-variant-closed me-1"></i>
          {{ number_format($totalProductData, 0, ',', '.') }} Produk
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-counter me-1"></i>
          {{ number_format($summaryTotalItems, 0, ',', '.') }} Item
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-store-outline me-1"></i>
          {{ $trackingStatusLabel($trackingStatus ?? '') }}
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
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Tracking Barang</h5>
        <small class="text-muted">
          Gunakan filter untuk mencari produk, item/RFID, customer, store, warehouse, dan status tracking.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterTrackingBarang">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.tracking-barang.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.tracking-barang.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterTrackingBarang">
        <div class="tracking-filter-box">
          <form method="GET" action="{{ route('monitoring.tracking-barang.index') }}" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Keyword</label>
              <input
                type="text"
                name="keyword"
                value="{{ $keyword ?? '' }}"
                class="form-control"
                placeholder="Nama produk / kode produk / customer"
              >
            </div>

            <div class="col-md-3">
              <label class="form-label">Produk</label>
              <select
                name="product_id"
                class="form-select js-select2-filter"
                data-placeholder="- Semua Produk -"
              >
                <option value="">- Semua Produk -</option>
                @foreach($products as $product)
                  <option
                    value="{{ $product->id }}"
                    {{ (string) ($productId ?? '') === (string) $product->id ? 'selected' : '' }}
                  >
                    {{ $product->name }}
                    @if(!empty($product->code))
                      - {{ $product->code }}
                    @endif
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Item ID / RFID</label>
              <input
                type="text"
                name="item_id"
                value="{{ $itemId ?? '' }}"
                class="form-control"
                placeholder="UUID Item / RFID"
              >
            </div>

            <div class="col-md-2">
              <label class="form-label">Store</label>
              <select name="store_id" class="form-select js-select2-filter" data-placeholder="- Semua Store -">
                <option value="">- Semua Store -</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" {{ (string) ($storeId ?? '') === (string) $store->id ? 'selected' : '' }}>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Warehouse</label>
              <select name="warehouse_id" class="form-select js-select2-filter" data-placeholder="- Semua Warehouse -">
                <option value="">- Semua Warehouse -</option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" {{ (string) ($warehouseId ?? '') === (string) $warehouse->id ? 'selected' : '' }}>
                    {{ $warehouse->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Tracking Status</label>
              <select name="tracking_status" class="form-select js-select2-filter" data-placeholder="All Tracking Status">
                <option value="">All</option>
                <option value="inbound_warehouse" {{ ($trackingStatus ?? '') === 'inbound_warehouse' ? 'selected' : '' }}>Inbound Warehouse</option>
                <option value="in_warehouse" {{ ($trackingStatus ?? '') === 'in_warehouse' ? 'selected' : '' }}>In Warehouse</option>
                <option value="stock_warehouse" {{ ($trackingStatus ?? '') === 'stock_warehouse' ? 'selected' : '' }}>Stock Warehouse</option>
                <option value="outbound_warehouse" {{ ($trackingStatus ?? '') === 'outbound_warehouse' ? 'selected' : '' }}>Outbound Warehouse</option>
                <option value="inbound_store" {{ ($trackingStatus ?? '') === 'inbound_store' ? 'selected' : '' }}>Inbound Store</option>
                <option value="stock_store" {{ ($trackingStatus ?? '') === 'stock_store' ? 'selected' : '' }}>Stock Store</option>
                <option value="display_store" {{ ($trackingStatus ?? '') === 'display_store' ? 'selected' : '' }}>Display Store</option>
                <option value="sold" {{ ($trackingStatus ?? '') === 'sold' ? 'selected' : '' }}>Sold</option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Show</label>
              <select name="per_page" class="form-select js-select2-filter" data-placeholder="Pilih jumlah data">
                @foreach([10, 25, 50, 100] as $size)
                  <option value="{{ $size }}" {{ (int) ($perPage ?? 10) === $size ? 'selected' : '' }}>
                    {{ $size }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-7">
              <label class="form-label">&nbsp;</label>
              <div class="text-muted small pt-2">
                Filter berdasarkan produk, item/RFID, store, warehouse, status tracking, dan jumlah data tampil.
              </div>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.tracking-barang.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.tracking-barang.index') }}"
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
              <h4 class="mb-1">{{ number_format($totalProductData, 0, ',', '.') }}</h4>
              <small class="text-muted">Produk sesuai filter</small>
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
              <div class="text-muted small">Total Item</div>
              <h4 class="mb-1">{{ number_format($summaryTotalItems, 0, ',', '.') }}</h4>
              <small class="text-muted">Akumulasi item halaman ini</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-counter"></i>
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
              <div class="text-muted small">Warehouse Flow</div>
              <h4 class="mb-1">{{ number_format($summaryWarehouseItems, 0, ',', '.') }}</h4>
              <small class="text-muted">Inbound / stock / outbound WH</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-warehouse"></i>
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
              <div class="text-muted small">Store & Sold</div>
              <h4 class="mb-1">
                {{ number_format($summaryStoreItems, 0, ',', '.') }}
                /
                {{ number_format($summarySoldItems, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Store flow / sold</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-store-check-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- STATUS LEGEND --}}
  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div>
          <h6 class="mb-1 fw-bold">Status Tracking</h6>
          <small class="text-muted">Ringkasan warna status untuk membaca tabel lebih cepat.</small>
        </div>

        <div class="status-legend">
          <span class="badge badge-soft-info">Inbound WH</span>
          <span class="badge badge-soft-warning">In / Stock WH</span>
          <span class="badge badge-soft-secondary">Outbound WH</span>
          <span class="badge badge-soft-info">Inbound Store</span>
          <span class="badge badge-soft-success">Stock / Display Store</span>
          <span class="badge badge-soft-danger">Sold</span>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Data Tracking Barang per Produk</h5>
        <small class="text-muted">
          Total Data: <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap tracking-table">
          <thead>
            <tr>
              <th style="width: 60px;" class="text-center">No</th>
              <th>Product</th>
              <th>Product ID</th>
              <th class="text-end">Total Item</th>
              <th class="text-end">Inbound WH</th>
              <th class="text-end">In WH</th>
              <th class="text-end">Stock WH</th>
              <th class="text-end">Outbound WH</th>
              <th class="text-end">Inbound Store</th>
              <th class="text-end">Stock Store</th>
              <th class="text-end">Display Store</th>
              <th class="text-end">Sold</th>
              <th>Last Update</th>
              <th style="width: 120px;" class="text-center">Aksi</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              <tr>
                <td class="text-center">{{ $rows->firstItem() + $i }}</td>

                <td class="product-cell">
                  <div class="fw-bold text-dark">{{ $row->product_name ?? '-' }}</div>
                  <small class="text-muted">
                    Tracking product movement
                  </small>
                </td>

                <td class="product-id-cell">
                  <small class="text-muted">{{ $row->product_id ?? '-' }}</small>
                </td>

                <td class="text-end">
                  <span class="count-pill">
                    {{ number_format((int) ($row->total_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="count-pill info">
                    {{ number_format((int) ($row->inbound_warehouse_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="count-pill warning">
                    {{ number_format((int) ($row->in_warehouse_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="count-pill warning">
                    {{ number_format((int) ($row->stock_warehouse_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="count-pill">
                    {{ number_format((int) ($row->outbound_warehouse_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="count-pill info">
                    {{ number_format((int) ($row->inbound_store_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="count-pill success">
                    {{ number_format((int) ($row->stock_store_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="count-pill success">
                    {{ number_format((int) ($row->display_store_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="count-pill danger">
                    {{ number_format((int) ($row->sold_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="last-update-cell">
                  @if($row->last_item_update)
                    <span class="badge badge-soft-secondary">
                      {{ \Carbon\Carbon::parse($row->last_item_update)->format('d-m-Y H:i') }}
                    </span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>

                <td class="text-center aksi-cell">
                  <a
                    href="{{ route('monitoring.tracking-barang.detail', [
                      'product' => $row->product_id,
                      'per_page' => $perPage ?? 10,
                      'item_id' => $itemId ?? '',
                    ]) }}"
                    class="btn btn-sm btn-primary"
                  >
                    <i class="mdi mdi-eye-outline me-1"></i> Detail
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="14" class="text-center text-muted py-4">
                  Belum ada data tracking barang.
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
</script>
@endpush
