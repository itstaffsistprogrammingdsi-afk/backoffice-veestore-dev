@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - WMS Stock Mutation')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --wm-primary: #5b5cf6;
    --wm-primary-dark: #4338ca;
    --wm-success: #22c55e;
    --wm-success-soft: rgba(34, 197, 94, .14);
    --wm-info: #06b6d4;
    --wm-info-soft: rgba(6, 182, 212, .14);
    --wm-warning: #f59e0b;
    --wm-warning-soft: rgba(245, 158, 11, .16);
    --wm-danger: #ef4444;
    --wm-danger-soft: rgba(239, 68, 68, .14);
    --wm-purple: #8b5cf6;
    --wm-dark: #1e293b;
    --wm-muted: #64748b;
    --wm-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(6, 182, 212, .10), transparent 28%),
      var(--wm-body);
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
    border-color: var(--wm-primary);
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

  .btn {
    border-radius: .75rem;
    font-weight: 600;
  }

  .btn-primary {
    border-color: var(--wm-primary);
    background: linear-gradient(135deg, var(--wm-primary), var(--wm-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--wm-success);
    background: linear-gradient(135deg, var(--wm-success), #16a34a);
  }

  .mutation-filter-box {
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
    background: linear-gradient(90deg, var(--wm-primary), var(--wm-info));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--wm-dark);
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
    background: linear-gradient(135deg, var(--wm-primary), var(--wm-primary-dark));
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

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .mutation-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .mutation-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
    transition: .2s ease;
  }

  .warehouse-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .ref-col {
    min-width: 230px;
    white-space: normal !important;
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--wm-muted);
  }

  .badge-soft-success {
    background: var(--wm-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--wm-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--wm-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--wm-info-soft) !important;
    color: #0369a1 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-primary {
    background: rgba(91, 92, 246, .14) !important;
    color: #4338ca !important;
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

  .badge-soft-dark {
    background: rgba(15, 23, 42, .12) !important;
    color: #1e293b !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .number-pill {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    min-width: 58px;
    border-radius: .75rem;
    padding: .4rem .65rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-weight: 800;
    color: #334155;
  }

  .number-pill.info {
    background: var(--wm-info-soft);
    color: #0369a1;
    border-color: rgba(6, 182, 212, .22);
  }

  .number-pill.success {
    background: var(--wm-success-soft);
    color: #15803d;
    border-color: rgba(34, 197, 94, .22);
  }

  .detail-box {
    background: linear-gradient(180deg, #fff, #f8fafc);
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    height: 100%;
  }

  .detail-box .label {
    font-size: .78rem;
    color: var(--wm-muted);
    margin-bottom: .35rem;
  }

  .detail-box .value {
    color: var(--wm-dark);
    font-weight: 800;
    word-break: break-word;
  }

  .modal-content {
    border: 0;
    border-radius: 1.15rem;
    box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, .18);
  }

  .modal-header {
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(180deg, #fff, #f8fafc);
    border-top-left-radius: 1.15rem;
    border-top-right-radius: 1.15rem;
  }

  .modal-footer {
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    border-bottom-left-radius: 1.15rem;
    border-bottom-right-radius: 1.15rem;
  }

  .product-modal-table th,
  .product-modal-table td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .product-modal-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
  }

  .empty-state {
    border: 1px dashed #cbd5e1;
    border-radius: 1rem;
    padding: 2.5rem 1rem;
    background: #f8fafc;
  }

  @media (max-width: 767.98px) {
    .dashboard-hero {
      border-radius: 1rem;
    }

    .card-body,
    .card-header {
      padding: 1rem;
    }
  }
</style>
@endpush

@section('content')
@php
  $rowCollection = method_exists($rows, 'getCollection') ? $rows->getCollection() : collect($rows);

  $totalData = method_exists($rows, 'total') ? (int) $rows->total() : (int) $rowCollection->count();

  $totalSku = (int) $rowCollection->sum(function ($row) {
    return (int) ($row->total_sku ?? 0);
  });

  $totalItems = (int) $rowCollection->sum(function ($row) {
    return (int) ($row->total_items ?? 0);
  });

  $warehouseCount = $rowCollection
    ->pluck('warehouse_name')
    ->filter()
    ->unique()
    ->count();

  $typeCount = $rowCollection
    ->pluck('outbound_type')
    ->filter()
    ->unique()
    ->count();

  $hasFilter = request()->hasAny([
    'q',
    'warehouse_id',
    'outbound_type',
    'date_from',
    'date_to',
  ]);

  $typeBadge = function ($type) {
    return match((string) $type) {
      'ecommerce' => 'badge-soft-info',
      'destroy' => 'badge-soft-danger',
      'transfer' => 'badge-soft-warning',
      'return_to_supplier' => 'badge-soft-secondary',
      'store' => 'badge-soft-primary',
      default => 'badge-soft-dark',
    };
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> WMS Stock Mutation
        </h4>
        <div class="hero-subtitle">
          Monitoring mutasi stok WMS berdasarkan warehouse, outbound type, courier, PIC, SKU, dan jumlah item.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-swap-horizontal-bold me-1"></i>
          {{ number_format($totalData, 0, ',', '.') }} Mutasi
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-package-variant-closed me-1"></i>
          {{ number_format($totalSku, 0, ',', '.') }} SKU
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-counter me-1"></i>
          {{ number_format($totalItems, 0, ',', '.') }} Item
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
        <h5 class="mb-1 fw-bold">Filter Mutasi Stock WMS</h5>
        <small class="text-muted">
          Cari berdasarkan invoice, delivery order, order number, resi, warehouse, type, dan produk.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterMutationWms">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.mutasi-stock-wms') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.mutasi-stock-wms.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterMutationWms">
        <div class="mutation-filter-box">
          <form method="GET"
                action="{{ route('monitoring.mutasi-stock-wms') }}"
                class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Warehouse</label>
              <select name="warehouse_id"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Warehouse">
                <option value="">Semua Warehouse</option>
                @foreach($warehouses as $w)
                  <option value="{{ $w->id }}" @selected(($filters['warehouse_id'] ?? '') == $w->id)>
                    {{ $w->name }} @if($w->code) ({{ $w->code }}) @endif
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Outbound Type</label>
              <select name="outbound_type"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Type">
                <option value="">Semua Type</option>
                @foreach($outboundTypes as $value => $label)
                  <option value="{{ $value }}" @selected(($filters['outbound_type'] ?? '') == $value)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Tanggal Dari</label>
              <input type="date"
                     name="date_from"
                     value="{{ $filters['date_from'] ?? '' }}"
                     class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Tanggal Sampai</label>
              <input type="date"
                     name="date_to"
                     value="{{ $filters['date_to'] ?? '' }}"
                     class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Cari</label>
              <input
                type="text"
                name="q"
                value="{{ $filters['q'] ?? '' }}"
                class="form-control"
                placeholder="Invoice / DO / Order / Resi / Produk"
              >
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.mutasi-stock-wms.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.mutasi-stock-wms') }}"
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
              <div class="text-muted small">Total Mutasi</div>
              <h4 class="mb-1">{{ number_format($totalData, 0, ',', '.') }}</h4>
              <small class="text-muted">Data sesuai filter</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-swap-horizontal-bold"></i>
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
              <div class="text-muted small">Total SKU</div>
              <h4 class="mb-1">{{ number_format($totalSku, 0, ',', '.') }}</h4>
              <small class="text-muted">Akumulasi halaman ini</small>
            </div>
            <div class="summary-icon info">
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
              <h4 class="mb-1">{{ number_format($totalItems, 0, ',', '.') }}</h4>
              <small class="text-muted">Item keluar / mutasi</small>
            </div>
            <div class="summary-icon success">
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
              <div class="text-muted small">Warehouse / Type</div>
              <h4 class="mb-1">
                {{ number_format($warehouseCount, 0, ',', '.') }}
                /
                {{ number_format($typeCount, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Gudang / type halaman ini</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-warehouse"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Daftar Mutasi Stock WMS</h5>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap mutation-table">
          <thead>
            <tr>
              <th style="width: 60px;" class="text-center">No</th>
              <th>Tanggal</th>
              <th>Kode Warehouse</th>
              <th>Warehouse</th>
              <th>Outbound Type</th>
              <th>Courier</th>
              <th>PIC/User</th>
              <th class="text-end">SKU</th>
              <th class="text-end">Item</th>
              <th class="text-center" style="width: 120px;">Aksi</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $row)
              <tr>
                <td class="text-center">{{ $rows->firstItem() + $loop->index }}</td>

                <td>
                  <span class="badge badge-soft-secondary">
                    {{ $row->event_date ? \Carbon\Carbon::parse($row->event_date)->format('d/m/Y H:i') : '-' }}
                  </span>
                </td>

                <td>
                  @if($row->warehouse_code)
                    <span class="badge badge-soft-primary">{{ $row->warehouse_code }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>

                <td class="warehouse-col">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-soft-info">
                      <i class="mdi mdi-warehouse"></i>
                    </span>
                    <div>
                      <div class="fw-semibold">{{ $row->warehouse_name ?? '-' }}</div>
                      <small class="text-muted">Warehouse</small>
                    </div>
                  </div>
                </td>

                <td>
                  <span class="badge {{ $typeBadge($row->outbound_type) }}">
                    {{ $row->outbound_type_label ?? '-' }}
                  </span>
                </td>

                <td>{{ $row->courier_name ?: '-' }}</td>
                <td>{{ $row->user_name ?: '-' }}</td>

                <td class="text-end">
                  <span class="number-pill info">
                    {{ number_format((int) ($row->total_sku ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="number-pill success">
                    {{ number_format((int) ($row->total_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-center">
                  <button type="button"
                          class="btn btn-sm btn-primary btn-detail"
                          data-id="{{ $row->id }}"
                          data-bs-toggle="modal"
                          data-bs-target="#modalProducts">
                    <i class="mdi mdi-package-variant me-1"></i> Detail
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10">
                  <div class="empty-state text-center text-muted">
                    <i class="mdi mdi-database-off-outline d-block mb-2" style="font-size: 2.3rem;"></i>
                    Belum ada data mutasi stock WMS.
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($rows->hasPages())
        <div class="mt-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
          <div class="pagination-info">
            Menampilkan
            <b>{{ $rows->firstItem() ?? 0 }}</b>
            -
            <b>{{ $rows->lastItem() ?? 0 }}</b>
            dari
            <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
            data
          </div>

          <div>
            {{ $rows->onEachSide(1)->links() }}
          </div>
        </div>
      @else
        <div class="mt-3 pagination-info">
          Menampilkan
          <b>{{ $rows->firstItem() ?? 0 }}</b>
          -
          <b>{{ $rows->lastItem() ?? 0 }}</b>
          dari
          <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
          data
        </div>
      @endif
    </div>
  </div>
</div>

{{-- MODAL DETAIL PRODUK --}}
<div class="modal fade" id="modalProducts" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">
            <i class="mdi mdi-package-variant me-1"></i>
            Detail Produk Mutasi Stock WMS
          </h5>
          <small class="text-muted">Daftar produk dan qty dalam mutasi stock WMS.</small>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-12">
            <div class="detail-box">
              <div class="label">Referensi</div>
              <div class="value" id="mutationReferenceInfo">-</div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm mb-0 product-modal-table">
            <thead>
              <tr>
                <th>Kode Produk</th>
                <th>Nama Produk</th>
                <th class="text-end">Qty</th>
              </tr>
            </thead>
            <tbody id="mutationProductsBody">
              <tr>
                <td colspan="3" class="text-center text-muted py-4">Belum ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">
          Tutup
        </button>
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

  const rows = @json($rowItems);

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

  function escapeHtml(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function buildReferenceHtml(row) {
    const references = [];

    if (row.invoice_number) {
      references.push(['Invoice', row.invoice_number]);
    }

    if (row.delivery_order_number) {
      references.push(['DO', row.delivery_order_number]);
    }

    if (row.order_number) {
      references.push(['Order', row.order_number]);
    }

    if (row.shipping_number) {
      references.push(['Resi', row.shipping_number]);
    }

    if (references.length === 0) {
      return '<span class="text-muted">-</span>';
    }

    return references.map(item => `
      <span class="badge badge-soft-secondary me-1 mb-1">
        ${escapeHtml(item[0])}: ${escapeHtml(item[1])}
      </span>
    `).join('');
  }

  function renderProductDetails(row) {
    const tbody = document.getElementById('mutationProductsBody');
    const referenceInfo = document.getElementById('mutationReferenceInfo');

    tbody.innerHTML = `
      <tr>
        <td colspan="3" class="text-center text-muted py-4">Memuat data...</td>
      </tr>
    `;
    referenceInfo.innerHTML = '<span class="text-muted">-</span>';

    if (!row) {
      tbody.innerHTML = `
        <tr>
          <td colspan="3" class="text-center text-muted py-4">Data tidak ditemukan</td>
        </tr>
      `;
      return;
    }

    referenceInfo.innerHTML = buildReferenceHtml(row);

    if (!Array.isArray(row.product_details) || row.product_details.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="3" class="text-center text-muted py-4">Tidak ada produk</td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = row.product_details.map(item => `
      <tr>
        <td>
          <span class="badge badge-soft-primary">
            ${escapeHtml(item.product_code ?? '-')}
          </span>
        </td>
        <td>${escapeHtml(item.product_name ?? '-')}</td>
        <td class="text-end">
          <span class="number-pill success">
            ${Number(item.qty ?? 0).toLocaleString('id-ID')}
          </span>
        </td>
      </tr>
    `).join('');
  }

  $(document).ready(function () {
    initFilterSelect2();

    const tooltipNodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipNodes.forEach(el => new bootstrap.Tooltip(el));

    document.querySelectorAll('.btn-detail').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const row = rows.find(r => String(r.id) === String(id));
        renderProductDetails(row);
      });
    });
  });
})(window.jQuery);
</script>
@endpush
