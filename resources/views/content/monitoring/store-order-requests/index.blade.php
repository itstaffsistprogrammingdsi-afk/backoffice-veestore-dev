@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Store Order Requests')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --sor-primary: #5b5cf6;
    --sor-primary-dark: #4338ca;
    --sor-primary-soft: rgba(91, 92, 246, .12);
    --sor-success: #22c55e;
    --sor-success-soft: rgba(34, 197, 94, .14);
    --sor-info: #06b6d4;
    --sor-info-soft: rgba(6, 182, 212, .14);
    --sor-warning: #f59e0b;
    --sor-warning-soft: rgba(245, 158, 11, .16);
    --sor-danger: #ef4444;
    --sor-danger-soft: rgba(239, 68, 68, .14);
    --sor-purple: #8b5cf6;
    --sor-dark: #1e293b;
    --sor-muted: #64748b;
    --sor-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--sor-body);
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
    border-color: var(--sor-primary);
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
    border-color: var(--sor-primary);
    background: linear-gradient(135deg, var(--sor-primary), var(--sor-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--sor-success);
    background: linear-gradient(135deg, var(--sor-success), #16a34a);
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
    background: linear-gradient(90deg, var(--sor-primary), var(--sor-success));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--sor-dark);
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
    background: linear-gradient(135deg, var(--sor-primary), var(--sor-primary-dark));
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

  .table td,
  .table th {
    vertical-align: middle;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
  }

  .store-order-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .store-order-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--sor-muted);
  }

  .product-name-col,
  .note-col {
    min-width: 240px;
    white-space: normal !important;
  }

  .store-col,
  .warehouse-col {
    min-width: 200px;
    white-space: normal !important;
  }

  .badge-soft-success {
    background: var(--sor-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--sor-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--sor-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--sor-info-soft) !important;
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
    'total_orders' => 0,
    'total_rows' => 0,
    'total_requested_qty' => 0,
    'total_approved_qty' => 0,
    'total_received_qty' => 0,
    'total_requested_amount' => 0,
    'total_approved_amount' => 0,
    'total_received_amount' => 0,
  ];

  $totalOrders = (int) ($summary->total_orders ?? 0);
  $totalRows = (int) ($summary->total_rows ?? 0);
  $totalRequestedQty = (int) ($summary->total_requested_qty ?? 0);
  $totalApprovedQty = (int) ($summary->total_approved_qty ?? 0);
  $totalReceivedQty = (int) ($summary->total_received_qty ?? 0);
  $totalRequestedAmount = (float) ($summary->total_requested_amount ?? 0);

  $hasFilter = request()->hasAny([
    'store_id',
    'warehouse_id',
    'start_date',
    'end_date',
    'status',
    'keyword',
    'per_page',
  ]);

  $statusBadge = function ($status) {
    $status = strtolower((string) $status);

    return match($status) {
      'approved', 'approve', 'completed', 'received', 'done', 'success' => 'badge-soft-success',
      'pending', 'request', 'requested', 'process', 'processing' => 'badge-soft-warning',
      'rejected', 'reject', 'cancelled', 'cancel', 'failed' => 'badge-soft-danger',
      default => 'badge-soft-info',
    };
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Store Order Requests
        </h4>
        <div class="hero-subtitle">
          Monitoring permintaan barang dari store ke WMS berdasarkan tanggal request, status, warehouse, produk, dan quantity.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-calendar-range-outline me-1"></i>
          {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-file-document-outline me-1"></i>
          {{ number_format($totalOrders, 0, ',', '.') }} Order
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
        <h5 class="mb-1 fw-bold">Filter Store Order Requests</h5>
        <small class="text-muted">
          Default menampilkan data hari ini. Gunakan filter untuk melihat periode, store, warehouse, status, atau keyword tertentu.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterStoreOrderRequests">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.store-order-requests.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.store-order-requests.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterStoreOrderRequests">
        <div class="filter-box">
          <form method="GET"
                action="{{ route('monitoring.store-order-requests.index') }}"
                class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Store</label>
              <select name="store_id" class="form-select js-select2-filter" data-placeholder="Semua Store">
                <option value=""></option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected((string) $storeId === (string) $store->id)>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Warehouse</label>
              <select name="warehouse_id" class="form-select js-select2-filter" data-placeholder="Semua Warehouse">
                <option value=""></option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" @selected((string) $warehouseId === (string) $warehouse->id)>
                    {{ $warehouse->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Tanggal Mulai</label>
              <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Tanggal Selesai</label>
              <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Status</label>
              <input type="text"
                     name="status"
                     value="{{ $status }}"
                     class="form-control"
                     placeholder="approved / pending">
            </div>

            <div class="col-md-4">
              <label class="form-label">Keyword</label>
              <input type="text"
                     name="keyword"
                     value="{{ $keyword }}"
                     class="form-control"
                     placeholder="Order / Produk / Note">
            </div>

            <div class="col-md-2">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select">
                @foreach([10,25,50,100,200] as $size)
                  <option value="{{ $size }}" @selected((int)($perPage ?? 10) === $size)>
                    {{ $size }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">&nbsp;</label>
              <div class="text-muted small pt-2">
                Data default hanya menampilkan request hari ini berdasarkan requested_at atau created_at.
              </div>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.store-order-requests.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.store-order-requests.index') }}"
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
              <h4 class="mb-1">{{ number_format($totalOrders, 0, ',', '.') }}</h4>
              <small class="text-muted">Nomor order unik</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-file-document-outline"></i>
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
              <h4 class="mb-1">{{ number_format($totalRows, 0, ',', '.') }}</h4>
              <small class="text-muted">Baris produk request</small>
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
              <div class="text-muted small">Qty Request</div>
              <h4 class="mb-1">{{ number_format($totalRequestedQty, 0, ',', '.') }}</h4>
              <small class="text-muted">Total qty diminta</small>
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
              <div class="text-muted small">Nilai Request</div>
              <h4 class="mb-1">Rp {{ number_format($totalRequestedAmount, 0, ',', '.') }}</h4>
              <small class="text-muted">Qty request x harga</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-cash-multiple"></i>
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
        <h5 class="mb-1 fw-bold">Data Permintaan Barang Store ke WMS</h5>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap store-order-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal Request</th>
              <th>Order Number</th>
              <th>Kode Store</th>
              <th>Store</th>
              <th>Warehouse</th>
              <th>Status</th>
              <th>Kode Produk</th>
              <th>Nama Produk</th>
              <th class="text-end">Harga</th>
              <th class="text-end">Qty Request</th>
              <th class="text-end">Qty Approve</th>
              <th class="text-end">Qty Receive</th>
              <th class="text-end">Total Request</th>
              <th>Shipping Number</th>
              <th>Note</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td>
                  {{ $row->requested_at
                    ? \Carbon\Carbon::parse($row->requested_at)->format('d-m-Y H:i')
                    : ($row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i') : '-')
                  }}
                </td>

                <td class="fw-semibold">
                  {{ $row->order_number ?? '-' }}
                </td>

                <td>
                  <span class="badge badge-soft-info">
                    {{ $row->store_code ?? '-' }}
                  </span>
                </td>

                <td class="store-col">
                  {{ $row->store_name ?? '-' }}
                </td>

                <td class="warehouse-col">
                  {{ $row->warehouse_name ?? '-' }}
                </td>

                <td>
                  <span class="badge {{ $statusBadge($row->status ?? '') }}">
                    {{ $row->status ? ucfirst(str_replace('_', ' ', $row->status)) : '-' }}
                  </span>
                </td>

                <td>{{ $row->product_code ?? '-' }}</td>

                <td class="product-name-col">
                  {{ $row->product_name ?? '-' }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->product_price ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end fw-semibold">
                  {{ number_format((int) ($row->requested_qty ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  {{ number_format((int) ($row->approved_qty ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  {{ number_format((int) ($row->received_qty ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end amount-success">
                  Rp {{ number_format((float) ($row->requested_total ?? 0), 0, ',', '.') }}
                </td>

                <td>{{ $row->shipping_number ?? '-' }}</td>

                <td class="note-col">
                  {{ $row->note ?? '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="16" class="text-center text-muted py-4">
                  Tidak ada data permintaan barang hari ini.
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
