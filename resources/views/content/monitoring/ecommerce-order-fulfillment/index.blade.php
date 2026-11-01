@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Ecommerce Fulfillment')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --ef-primary: #5b5cf6;
    --ef-primary-dark: #4338ca;
    --ef-primary-soft: rgba(91, 92, 246, .12);
    --ef-success: #22c55e;
    --ef-success-soft: rgba(34, 197, 94, .14);
    --ef-info: #06b6d4;
    --ef-info-soft: rgba(6, 182, 212, .14);
    --ef-warning: #f59e0b;
    --ef-warning-soft: rgba(245, 158, 11, .16);
    --ef-danger: #ef4444;
    --ef-danger-soft: rgba(239, 68, 68, .14);
    --ef-purple: #8b5cf6;
    --ef-dark: #1e293b;
    --ef-muted: #64748b;
    --ef-border: #e5e7eb;
    --ef-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--ef-body);
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
    border-color: var(--ef-primary);
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
    border-color: var(--ef-primary);
    background: linear-gradient(135deg, var(--ef-primary), var(--ef-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--ef-success);
    background: linear-gradient(135deg, var(--ef-success), #16a34a);
    box-shadow: 0 .45rem 1rem rgba(34, 197, 94, .18);
  }

  .btn-outline-secondary,
  .btn-outline-primary {
    background: #fff;
    border-color: #d7deea;
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
    background: linear-gradient(90deg, var(--ef-primary), var(--ef-success));
  }

  .summary-card::after {
    content: "";
    position: absolute;
    width: 90px;
    height: 90px;
    right: -36px;
    bottom: -36px;
    border-radius: 50%;
    background: var(--ef-primary-soft);
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--ef-dark);
    font-weight: 800;
    letter-spacing: -.02em;
  }

  .summary-card .text-muted {
    color: var(--ef-muted) !important;
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
    background: linear-gradient(135deg, var(--ef-primary), var(--ef-primary-dark));
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

  .fulfillment-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .fulfillment-table tbody td {
    color: #475569;
  }

  .fulfillment-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--ef-muted);
  }

  .rfid-col {
    min-width: 380px;
    white-space: normal !important;
  }

  .product-col {
    min-width: 280px;
    white-space: normal !important;
  }

  .handler-col {
    min-width: 240px;
    white-space: normal !important;
  }

  .customer-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .store-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .rfid-box {
    max-height: 92px;
    overflow: auto;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: .75rem;
    padding: .6rem;
    font-size: .8125rem;
    line-height: 1.45;
    color: #334155;
  }

  .badge-soft-success {
    background: var(--ef-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--ef-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--ef-danger-soft) !important;
    color: #b91c1c !important;
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

  .badge-soft-info {
    background: var(--ef-info-soft) !important;
    color: #0369a1 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-primary {
    background: var(--ef-primary-soft) !important;
    color: var(--ef-primary-dark) !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .qty-success {
    color: #15803d !important;
    font-weight: 800;
  }

  .qty-warning {
    color: #b45309 !important;
    font-weight: 800;
  }

  .qty-danger {
    color: #b91c1c !important;
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
  $summary = $summary ?? (object) [
    'total_order' => 0,
    'total_product_row' => 0,
    'total_ordered_qty' => 0,
    'total_issued_qty' => 0,
    'total_remaining_qty' => 0,
  ];

  $filters = $filters ?? [];
  $exportQuery = request()->query();

  $hasFilter = request()->hasAny([
    'order_number',
    'customer_name',
    'wa_number',
    'order_status',
    'handled_type',
    'store_id',
    'warehouse_id',
    'start_date',
    'end_date',
    'per_page',
  ]);

  $statusBadge = function ($status) {
    $status = strtolower((string) $status);

    return match($status) {
      'completed', 'complete', 'success', 'done', 'paid', 'shipped', 'delivered' => 'badge-soft-success',
      'pending', 'process', 'processing', 'packed', 'waiting' => 'badge-soft-warning',
      'cancel', 'cancelled', 'failed', 'reject', 'rejected' => 'badge-soft-danger',
      default => 'badge-soft-secondary',
    };
  };

  $handlerBadge = function ($type) {
    $type = strtolower((string) $type);

    return match($type) {
      'store' => 'badge-soft-primary',
      'warehouse' => 'badge-soft-info',
      default => 'badge-soft-secondary',
    };
  };

  $totalOrder = (int) ($summary->total_order ?? 0);
  $totalProductRow = (int) ($summary->total_product_row ?? 0);
  $totalOrderedQty = (int) ($summary->total_ordered_qty ?? 0);
  $totalIssuedQty = (int) ($summary->total_issued_qty ?? 0);
  $totalRemainingQty = (int) ($summary->total_remaining_qty ?? 0);
  $fulfillmentRate = $totalOrderedQty > 0 ? round(($totalIssuedQty / $totalOrderedQty) * 100, 1) : 0;
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Ecommerce Fulfillment
        </h4>
        <div class="hero-subtitle">
          Monitoring pemenuhan order ecommerce, handler store atau warehouse, qty pesanan, qty keluar, sisa, dan RFID tag.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <span class="live-dot me-1"></span>
          Fulfillment Monitoring
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-cart-outline me-1"></i>
          {{ number_format($totalOrder, 0, ',', '.') }} Order
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-truck-check-outline me-1"></i>
          {{ number_format($fulfillmentRate, 1, ',', '.') }}% Fulfilled
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
        <h5 class="mb-1 fw-bold">Filter Ecommerce Fulfillment</h5>
        <small class="text-muted">
          Gunakan filter untuk mencari order berdasarkan customer, status, handler, store, warehouse, dan tanggal order.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#filterFulfillment">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.ecommerce-order-fulfillment.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.ecommerce-order-fulfillment.export', $exportQuery) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="filterFulfillment">
        <div class="filter-box">
          <form method="GET"
                action="{{ route('monitoring.ecommerce-order-fulfillment.index') }}"
                class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Order Number</label>
              <input type="text"
                     name="order_number"
                     value="{{ $filters['order_number'] ?? '' }}"
                     class="form-control"
                     placeholder="Cari order number">
            </div>

            <div class="col-md-3">
              <label class="form-label">Customer</label>
              <input type="text"
                     name="customer_name"
                     value="{{ $filters['customer_name'] ?? '' }}"
                     class="form-control"
                     placeholder="Nama customer">
            </div>

            <div class="col-md-2">
              <label class="form-label">No. WA</label>
              <input type="text"
                     name="wa_number"
                     value="{{ $filters['wa_number'] ?? '' }}"
                     class="form-control"
                     placeholder="08xxxx">
            </div>

            <div class="col-md-2">
              <label class="form-label">Order Status</label>
              <select name="order_status"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Status">
                <option value=""></option>
                @foreach($orderStatuses as $status)
                  <option value="{{ $status }}"
                    @selected((string)($filters['order_status'] ?? '') === (string)$status)>
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Handled Type</label>
              <select name="handled_type"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Handler">
                <option value=""></option>
                <option value="store" @selected(($filters['handled_type'] ?? '') === 'store')>Store</option>
                <option value="warehouse" @selected(($filters['handled_type'] ?? '') === 'warehouse')>Warehouse</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Store</label>
              <select name="store_id"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Store">
                <option value=""></option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}"
                    @selected((string)($filters['store_id'] ?? '') === (string)$store->id)>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Warehouse</label>
              <select name="warehouse_id"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Warehouse">
                <option value=""></option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}"
                    @selected((string)($filters['warehouse_id'] ?? '') === (string)$warehouse->id)>
                    {{ $warehouse->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Tanggal Dari</label>
              <input type="date"
                     name="start_date"
                     value="{{ $filters['start_date'] ?? '' }}"
                     class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Tanggal Sampai</label>
              <input type="date"
                     name="end_date"
                     value="{{ $filters['end_date'] ?? '' }}"
                     class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select">
                @foreach([10, 25, 50, 100] as $page)
                  <option value="{{ $page }}"
                    @selected((int)($filters['per_page'] ?? 10) === $page)>
                    {{ $page }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.ecommerce-order-fulfillment.export', $exportQuery) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.ecommerce-order-fulfillment.index') }}"
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
              <div class="text-muted small">Total Order</div>
              <h4 class="mb-1">{{ number_format($totalOrder, 0, ',', '.') }}</h4>
              <small class="text-muted">Order ecommerce</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-cart-outline"></i>
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
              <div class="text-muted small">Total Produk</div>
              <h4 class="mb-1">{{ number_format($totalProductRow, 0, ',', '.') }}</h4>
              <small class="text-muted">Baris produk order</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-package-variant"></i>
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
              <div class="text-muted small">Qty Pesanan</div>
              <h4 class="mb-1">{{ number_format($totalOrderedQty, 0, ',', '.') }}</h4>
              <small class="text-muted">Total quantity order</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-format-list-numbered"></i>
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
              <div class="text-muted small">Qty Keluar</div>
              <h4 class="mb-1">{{ number_format($totalIssuedQty, 0, ',', '.') }}</h4>
              <small class="text-muted">
                Sisa: {{ number_format($totalRemainingQty, 0, ',', '.') }}
              </small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-truck-check-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Data Ecommerce Fulfillment</h5>
        <small class="text-muted">
          Total Data: <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
        </small>
      </div>

      @if($rows->count())
        <div class="pagination-info">
          Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
          dari <b>{{ number_format($rows->total(), 0, ',', '.') }}</b> data
        </div>
      @endif
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm table-nowrap mb-0 fulfillment-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal Order</th>
              <th>Jam Order</th>
              <th>Order Number</th>
              <th>Customer</th>
              <th>No. WA</th>
              <th>Order Status</th>
              <th>Handled By</th>
              <th>Store Keluar</th>
              <th>Produk</th>
              <th class="text-end">Qty Pesanan</th>
              <th class="text-end">Qty Keluar</th>
              <th class="text-end">Sisa</th>
              <th>RFID Tag Keluar</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              @php
                $orderedQty = (int) ($row->ordered_qty ?? 0);
                $issuedQty = (int) ($row->issued_qty ?? 0);
                $remainingQty = (int) ($row->remaining_qty ?? 0);

                $remainingClass = $remainingQty <= 0 ? 'qty-success' : 'qty-danger';
                $issuedClass = $issuedQty >= $orderedQty && $orderedQty > 0 ? 'qty-success' : 'qty-warning';
              @endphp

              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td>
                  {{ $row->order_date ? \Carbon\Carbon::parse($row->order_date)->format('d-m-Y') : '-' }}
                </td>

                <td>{{ $row->order_time ?? '-' }}</td>

                <td class="fw-semibold">
                  {{ $row->order_number ?? '-' }}
                </td>

                <td class="customer-col">
                  <div class="fw-semibold">{{ $row->customer_name ?? '-' }}</div>
                </td>

                <td>{{ $row->wa_number ?? '-' }}</td>

                <td>
                  <span class="badge {{ $statusBadge($row->order_status ?? '') }}">
                    {{ $row->order_status ? ucfirst(str_replace('_', ' ', $row->order_status)) : '-' }}
                  </span>
                </td>

                <td class="handler-col">
                  <div class="fw-semibold">{{ $row->handled_by_name ?? '-' }}</div>
                  <span class="badge {{ $handlerBadge($row->handled_type ?? '') }}">
                    {{ $row->handled_type ?? '-' }}
                  </span>
                </td>

                <td class="store-col">
                  {{ $row->outbound_store_names ?? '-' }}
                </td>

                <td class="product-col">
                  <div class="fw-semibold">{{ $row->product_code ?? '-' }}</div>
                  <div>{{ $row->product_name ?? '-' }}</div>
                </td>

                <td class="text-end fw-semibold">
                  {{ number_format($orderedQty, 0, ',', '.') }}
                </td>

                <td class="text-end {{ $issuedClass }}">
                  {{ number_format($issuedQty, 0, ',', '.') }}
                </td>

                <td class="text-end {{ $remainingClass }}">
                  {{ number_format($remainingQty, 0, ',', '.') }}
                </td>

                <td class="rfid-col">
                  <div class="rfid-box">
                    {{ $row->rfid_tags ?? '-' }}
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="14" class="text-center text-muted py-4">
                  Tidak ada data fulfillment ecommerce.
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

  function initSelect2() {
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
    initSelect2();
  });
})(window.jQuery);
</script>
@endpush