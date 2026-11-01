@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Warehouse Barrier Stock')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --wb-primary: #5b5cf6;
    --wb-primary-dark: #4338ca;
    --wb-success: #22c55e;
    --wb-success-soft: rgba(34, 197, 94, .14);
    --wb-info: #06b6d4;
    --wb-info-soft: rgba(6, 182, 212, .14);
    --wb-warning: #f59e0b;
    --wb-warning-soft: rgba(245, 158, 11, .16);
    --wb-danger: #ef4444;
    --wb-danger-soft: rgba(239, 68, 68, .14);
    --wb-purple: #8b5cf6;
    --wb-dark: #1e293b;
    --wb-muted: #64748b;
    --wb-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(6, 182, 212, .10), transparent 28%),
      var(--wb-body);
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
    border-color: var(--wb-primary);
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
    border-color: var(--wb-primary);
    background: linear-gradient(135deg, var(--wb-primary), var(--wb-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--wb-success);
    background: linear-gradient(135deg, var(--wb-success), #16a34a);
  }

  .barrier-filter-box {
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
    background: linear-gradient(90deg, var(--wb-primary), var(--wb-info));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--wb-dark);
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
    background: linear-gradient(135deg, var(--wb-primary), var(--wb-primary-dark));
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

  .barrier-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .barrier-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
    transition: .2s ease;
  }

  .product-col {
    min-width: 260px;
    white-space: normal !important;
  }

  .warehouse-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--wb-muted);
  }

  .badge-soft-success {
    background: var(--wb-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--wb-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--wb-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--wb-info-soft) !important;
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

  .number-pill {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    min-width: 64px;
    border-radius: .75rem;
    padding: .4rem .65rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-weight: 800;
    color: #334155;
  }

  .number-pill.success {
    background: var(--wb-success-soft);
    color: #15803d;
    border-color: rgba(34, 197, 94, .22);
  }

  .number-pill.warning {
    background: var(--wb-warning-soft);
    color: #b45309;
    border-color: rgba(245, 158, 11, .22);
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
  $barrierCollection = method_exists($barriers, 'getCollection') ? $barriers->getCollection() : collect($barriers);

  $totalData = method_exists($barriers, 'total') ? (int) $barriers->total() : (int) $barrierCollection->count();

  $totalStockBarrier = (int) $barrierCollection->sum(function ($row) {
    return (int) ($row->stock_barrier ?? 0);
  });

  $averageTolerance = $barrierCollection->count() > 0
    ? (float) $barrierCollection->avg(function ($row) {
        return (float) ($row->tolerance_percentage ?? 0);
      })
    : 0;

  $warehouseCount = $barrierCollection
    ->pluck('warehouse_id')
    ->filter()
    ->unique()
    ->count();

  $productCount = $barrierCollection
    ->pluck('product_id')
    ->filter()
    ->unique()
    ->count();

  $hasFilter = request()->hasAny([
    'q',
    'warehouse_id',
  ]);

  $formatTolerance = function ($value) {
    if ($value === null || $value === '') {
      return '-';
    }

    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',') . '%';
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      <i class="mdi mdi-check-circle-outline me-1"></i> {{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
      <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali form Anda.
      <ul class="mb-0 mt-2 small">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Warehouse Barrier Stock
        </h4>
        <div class="hero-subtitle">
          Pengaturan stok barier gudang untuk menjaga batas minimum stok produk di setiap warehouse.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-warehouse me-1"></i>
          {{ number_format($warehouseCount, 0, ',', '.') }} Warehouse
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-package-variant-closed me-1"></i>
          {{ number_format($productCount, 0, ',', '.') }} Produk
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-shield-check-outline me-1"></i>
          {{ number_format($totalStockBarrier, 0, ',', '.') }} Total Barrier
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
        <h5 class="mb-1 fw-bold">Filter Stok Barier Gudang</h5>
        <small class="text-muted">
          Cari berdasarkan nama/kode produk dan warehouse.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterWarehouseBarrier">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.stok-barier-gudang.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <button type="button"
                class="btn btn-primary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#modalCreate">
          <i class="mdi mdi-plus me-1"></i> Tambah
        </button>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterWarehouseBarrier">
        <div class="barrier-filter-box">
          <form method="GET"
                action="{{ route('monitoring.stok-barier-gudang.index') }}"
                class="row g-3">

            <div class="col-md-6">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $filters['q'] ?? '' }}"
                     placeholder="Nama produk / kode produk">
            </div>

            <div class="col-md-6">
              <label class="form-label">Gudang</label>
              <select name="warehouse_id"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Gudang">
                <option value="">Semua Gudang</option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" @selected(($filters['warehouse_id'] ?? '') == $warehouse->id)>
                    {{ $warehouse->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.stok-barier-gudang.index') }}"
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
              <div class="text-muted small">Total Data</div>
              <h4 class="mb-1">{{ number_format($totalData, 0, ',', '.') }}</h4>
              <small class="text-muted">Data stok barier</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-table"></i>
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
              <div class="text-muted small">Total Stok Barier</div>
              <h4 class="mb-1">{{ number_format($totalStockBarrier, 0, ',', '.') }}</h4>
              <small class="text-muted">Akumulasi halaman ini</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-shield-check-outline"></i>
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
              <div class="text-muted small">Warehouse</div>
              <h4 class="mb-1">{{ number_format($warehouseCount, 0, ',', '.') }}</h4>
              <small class="text-muted">Gudang dalam halaman ini</small>
            </div>
            <div class="summary-icon info">
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
              <div class="text-muted small">Rata-rata Toleransi</div>
              <h4 class="mb-1">{{ $formatTolerance($averageTolerance) }}</h4>
              <small class="text-muted">Berdasarkan halaman ini</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-percent-outline"></i>
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
        <h5 class="mb-1 fw-bold">Daftar Stok Barier Gudang</h5>
        <small class="text-muted">
          Total Data: <b>{{ number_format($barriers->total(), 0, ',', '.') }}</b>
        </small>
      </div>

      @if($barriers->count())
        <div class="pagination-info">
          Menampilkan <b>{{ $barriers->firstItem() }}</b> - <b>{{ $barriers->lastItem() }}</b>
          dari <b>{{ number_format($barriers->total(), 0, ',', '.') }}</b> data
        </div>
      @endif
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap barrier-table">
          <thead>
            <tr>
              <th style="width: 60px;" class="text-center">No</th>
              <th>Gudang</th>
              <th>Produk</th>
              <th class="text-end">Stok Barier</th>
              <th class="text-end">Toleransi (%)</th>
              <th class="text-center" style="width: 120px;">Aksi</th>
            </tr>
          </thead>

          <tbody>
            @forelse($barriers as $barrier)
              <tr>
                <td class="text-center">{{ $barriers->firstItem() + $loop->index }}</td>

                <td class="warehouse-col">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-soft-info">
                      <i class="mdi mdi-warehouse"></i>
                    </span>
                    <div>
                      <div class="fw-semibold">{{ $barrier->warehouse->name ?? '-' }}</div>
                      <small class="text-muted">Warehouse / Gudang</small>
                    </div>
                  </div>
                </td>

                <td class="product-col">
                  <div class="fw-bold text-dark">{{ $barrier->product->name ?? '-' }}</div>
                  <small class="text-muted">{{ $barrier->product->code ?? '-' }}</small>
                </td>

                <td class="text-end">
                  <span class="number-pill success">
                    {{ number_format((int) $barrier->stock_barrier, 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="number-pill warning">
                    {{ $formatTolerance($barrier->tolerance_percentage) }}
                  </span>
                </td>

                <td class="text-center">
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle hide-arrow"
                            type="button"
                            data-bs-toggle="dropdown">
                      <i class="mdi mdi-dots-vertical"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end">
                      <a href="#"
                         class="dropdown-item btn-edit"
                         data-action="{{ route('monitoring.stok-barier-gudang.update', $barrier->id) }}"
                         data-id="{{ $barrier->id }}"
                         data-warehouse="{{ $barrier->warehouse_id }}"
                         data-product="{{ $barrier->product_id }}"
                         data-stock="{{ $barrier->stock_barrier }}"
                         data-tol="{{ $barrier->tolerance_percentage }}"
                         data-bs-toggle="modal"
                         data-bs-target="#modalEdit">
                        <i class="mdi mdi-pencil-outline me-1"></i> Edit
                      </a>

                      <a href="#"
                         class="dropdown-item text-danger btn-delete"
                         data-action="{{ route('monitoring.stok-barier-gudang.destroy', $barrier->id) }}">
                        <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                      </a>
                    </div>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6">
                  <div class="empty-state text-center text-muted">
                    <i class="mdi mdi-database-off-outline d-block mb-2" style="font-size: 2.3rem;"></i>
                    Belum ada data stok barier gudang.
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($barriers->hasPages())
        <div class="mt-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
          <div class="pagination-info">
            Menampilkan
            <b>{{ $barriers->firstItem() ?? 0 }}</b>
            -
            <b>{{ $barriers->lastItem() ?? 0 }}</b>
            dari
            <b>{{ number_format($barriers->total(), 0, ',', '.') }}</b>
            data
          </div>

          <div>
            {{ $barriers->onEachSide(1)->links() }}
          </div>
        </div>
      @else
        <div class="mt-3 pagination-info">
          Menampilkan
          <b>{{ $barriers->firstItem() ?? 0 }}</b>
          -
          <b>{{ $barriers->lastItem() ?? 0 }}</b>
          dari
          <b>{{ number_format($barriers->total(), 0, ',', '.') }}</b>
          data
        </div>
      @endif
    </div>
  </div>
</div>

{{-- MODAL: CREATE --}}
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('monitoring.stok-barier-gudang.store') }}">
        @csrf
        <input type="hidden" name="_form" value="create">

        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0">
              <i class="mdi mdi-plus-circle-outline me-1"></i> Tambah Stok Barier
            </h5>
            <small class="text-muted">Tambahkan batas stok minimum untuk produk gudang.</small>
          </div>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Gudang <span class="text-danger">*</span></label>
            <select name="warehouse_id"
                    class="form-select js-select2-modal @error('warehouse_id') is-invalid @enderror"
                    data-placeholder="Pilih Gudang"
                    data-dropdown-parent="#modalCreate"
                    required>
              <option value=""></option>
              @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>
                  {{ $warehouse->name }}
                </option>
              @endforeach
            </select>
            @error('warehouse_id')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Produk <span class="text-danger">*</span></label>
            <select name="product_id"
                    class="form-select js-select2-modal @error('product_id') is-invalid @enderror"
                    data-placeholder="Pilih Produk"
                    data-dropdown-parent="#modalCreate"
                    required>
              <option value=""></option>
              @foreach($products as $product)
                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                  {{ $product->name }} ({{ $product->code }})
                </option>
              @endforeach
            </select>
            @error('product_id')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="text-muted d-block mt-1">Daftar dibatasi 200 produk.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Stok Barier <span class="text-danger">*</span></label>
            <input type="number"
                   name="stock_barrier"
                   min="0"
                   class="form-control @error('stock_barrier') is-invalid @enderror"
                   value="{{ old('stock_barrier') }}"
                   placeholder="Masukkan stok barier"
                   required>
            @error('stock_barrier')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-0">
            <label class="form-label">Toleransi (%)</label>
            <input type="number"
                   step="0.01"
                   min="0"
                   max="100"
                   name="tolerance_percentage"
                   class="form-control @error('tolerance_percentage') is-invalid @enderror"
                   value="{{ old('tolerance_percentage') }}"
                   placeholder="mis. 5">
            @error('tolerance_percentage')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL: EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="formEdit" method="POST" action="#">
        @csrf
        @method('PUT')
        <input type="hidden" name="_form" value="edit">
        <input type="hidden" id="edit_id" name="id" value="{{ old('id') }}">

        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0">
              <i class="mdi mdi-pencil-outline me-1"></i> Edit Stok Barier
            </h5>
            <small class="text-muted">Perbarui stok barier dan toleransi gudang.</small>
          </div>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Gudang <span class="text-danger">*</span></label>
            <select id="edit_warehouse_id"
                    name="warehouse_id"
                    class="form-select js-select2-modal @error('warehouse_id') is-invalid @enderror"
                    data-placeholder="Pilih Gudang"
                    data-dropdown-parent="#modalEdit"
                    required>
              <option value=""></option>
              @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
              @endforeach
            </select>
            @error('warehouse_id')
              @if(old('_form') === 'edit')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @endif
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Produk <span class="text-danger">*</span></label>
            <select id="edit_product_id"
                    name="product_id"
                    class="form-select js-select2-modal @error('product_id') is-invalid @enderror"
                    data-placeholder="Pilih Produk"
                    data-dropdown-parent="#modalEdit"
                    required>
              <option value=""></option>
              @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
              @endforeach
            </select>
            @error('product_id')
              @if(old('_form') === 'edit')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @endif
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Stok Barier <span class="text-danger">*</span></label>
            <input id="edit_stock_barrier"
                   type="number"
                   name="stock_barrier"
                   min="0"
                   class="form-control @error('stock_barrier') is-invalid @enderror"
                   value="{{ old('_form') === 'edit' ? old('stock_barrier') : '' }}"
                   required>
            @error('stock_barrier')
              @if(old('_form') === 'edit')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @endif
            @enderror
          </div>

          <div class="mb-0">
            <label class="form-label">Toleransi (%)</label>
            <input id="edit_tolerance_percentage"
                   type="number"
                   step="0.01"
                   min="0"
                   max="100"
                   name="tolerance_percentage"
                   class="form-control @error('tolerance_percentage') is-invalid @enderror"
                   value="{{ old('_form') === 'edit' ? old('tolerance_percentage') : '' }}">
            @error('tolerance_percentage')
              @if(old('_form') === 'edit')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @endif
            @enderror
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<form id="formDelete" method="POST" class="d-none">
  @csrf
  @method('DELETE')
</form>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
  'use strict';

  function initHeaderFilterSelect2() {
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

  function initModalSelect2(scope) {
    $(scope).find('.js-select2-modal').each(function () {
      const $el = $(this);

      if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
      }

      $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        dropdownParent: $($el.data('dropdown-parent')),
        placeholder: $el.data('placeholder') || 'Pilih data'
      });
    });
  }

  $(document).ready(function () {
    initHeaderFilterSelect2();

    const ttNodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    ttNodes.forEach(el => new bootstrap.Tooltip(el));

    $('#modalCreate').on('shown.bs.modal', function () {
      initModalSelect2(this);
    });

    $('#modalEdit').on('shown.bs.modal', function () {
      initModalSelect2(this);
    });

    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', function () {
        const form = document.getElementById('formEdit');

        form.action = this.dataset.action || '#';

        document.getElementById('edit_id').value = this.dataset.id || '';
        document.getElementById('edit_stock_barrier').value = this.dataset.stock || 0;
        document.getElementById('edit_tolerance_percentage').value = this.dataset.tol || '';

        $('#edit_warehouse_id').val(this.dataset.warehouse || '').trigger('change');
        $('#edit_product_id').val(this.dataset.product || '').trigger('change');
      });
    });

    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();

        if (!confirm('Yakin hapus stok barier gudang ini?')) {
          return;
        }

        const form = document.getElementById('formDelete');
        form.action = this.dataset.action || '#';
        form.submit();
      });
    });

    const oldForm = @json(old('_form'));

    if (oldForm === 'create') {
      const modal = new bootstrap.Modal(document.getElementById('modalCreate'));
      modal.show();

      $('#modalCreate').on('shown.bs.modal', function () {
        initModalSelect2(this);

        const oldWarehouse = @json(old('warehouse_id'));
        const oldProduct = @json(old('product_id'));

        if (oldWarehouse) {
          $(this).find('select[name="warehouse_id"]').val(oldWarehouse).trigger('change');
        }

        if (oldProduct) {
          $(this).find('select[name="product_id"]').val(oldProduct).trigger('change');
        }
      });
    }

    if (oldForm === 'edit') {
      const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
      modal.show();

      const editId = @json(old('id'));

      if (editId) {
        const routeTpl = @json(route('monitoring.stok-barier-gudang.update', ':id'));
        document.getElementById('formEdit').action = routeTpl.replace(':id', editId);
      }

      $('#modalEdit').on('shown.bs.modal', function () {
        initModalSelect2(this);

        const oldWarehouse = @json(old('warehouse_id'));
        const oldProduct = @json(old('product_id'));

        if (oldWarehouse) {
          $('#edit_warehouse_id').val(oldWarehouse).trigger('change');
        }

        if (oldProduct) {
          $('#edit_product_id').val(oldProduct).trigger('change');
        }
      });
    }
  });
})(window.jQuery);
</script>
@endpush
