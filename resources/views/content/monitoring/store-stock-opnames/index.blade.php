@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Laporan Stok Opname Store')

@push('page-style')
<style>
  :root {
    --so-primary: #5b5cf6;
    --so-primary-dark: #4338ca;
    --so-success: #22c55e;
    --so-success-soft: rgba(34, 197, 94, .14);
    --so-info: #06b6d4;
    --so-info-soft: rgba(6, 182, 212, .14);
    --so-warning: #f59e0b;
    --so-warning-soft: rgba(245, 158, 11, .16);
    --so-danger: #ef4444;
    --so-danger-soft: rgba(239, 68, 68, .14);
    --so-purple: #8b5cf6;
    --so-dark: #1e293b;
    --so-muted: #64748b;
    --so-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(245, 158, 11, .10), transparent 28%),
      var(--so-body);
  }

  .form-control,
  .form-select {
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: var(--so-primary);
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
    border-color: var(--so-primary);
    background: linear-gradient(135deg, var(--so-primary), var(--so-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--so-success);
    background: linear-gradient(135deg, var(--so-success), #16a34a);
  }

  .audit-filter-box {
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
    background: linear-gradient(90deg, var(--so-primary), var(--so-warning));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--so-dark);
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
    background: linear-gradient(135deg, var(--so-primary), var(--so-primary-dark));
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

  .audit-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .audit-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
    transition: .2s ease;
  }

  .product-col {
    min-width: 260px;
    white-space: normal !important;
  }

  .note-col {
    min-width: 220px;
    max-width: 260px;
    white-space: normal !important;
  }

  .status-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .store-col {
    min-width: 200px;
    white-space: normal !important;
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--so-muted);
  }

  .badge-soft-success {
    background: var(--so-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--so-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--so-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--so-info-soft) !important;
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
    justify-content: center;
    min-width: 58px;
    border-radius: .75rem;
    padding: .4rem .65rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-weight: 800;
    color: #334155;
  }

  .number-pill.success {
    background: var(--so-success-soft);
    color: #15803d;
    border-color: rgba(34, 197, 94, .22);
  }

  .number-pill.warning {
    background: var(--so-warning-soft);
    color: #b45309;
    border-color: rgba(245, 158, 11, .22);
  }

  .number-pill.danger {
    background: var(--so-danger-soft);
    color: #b91c1c;
    border-color: rgba(239, 68, 68, .22);
  }

  .status-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
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

  .modal-info-box {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    height: 100%;
    background: linear-gradient(180deg, #fff, #f8fafc);
  }

  .modal-info-box .label {
    font-size: .78rem;
    color: var(--so-muted);
    margin-bottom: .35rem;
  }

  .modal-info-box .value {
    color: var(--so-dark);
    font-weight: 800;
    word-break: break-word;
  }

  .rfid-modal-table th,
  .rfid-modal-table td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .rfid-modal-table thead th {
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

  $totalQtySystem = (int) $rowCollection->sum(function ($row) {
    return (int) ($row->qty_system ?? 0);
  });

  $totalQtyScan = (int) $rowCollection->sum(function ($row) {
    return (int) ($row->qty_scan ?? 0);
  });

  $totalSelisih = $totalQtyScan - $totalQtySystem;

  $balancedCount = (int) $rowCollection->filter(function ($row) {
    return ($row->status_summary ?? '') === 'Balanced';
  })->count();

  $issueCount = (int) $rowCollection->filter(function ($row) {
    return in_array(($row->status_summary ?? ''), ['Shortage', 'Surplus'], true);
  })->count();

  $totalDestroyedQty = (int) $rowCollection->sum(function ($row) {
    return (int) ($row->destroyed_qty ?? 0);
  });

  $hasFilter = request()->hasAny([
    'date_from',
    'date_to',
    'store_id',
    'search',
  ]);

  $summaryBadge = function ($status) {
    return match($status) {
      'Balanced' => 'badge-soft-success',
      'Shortage' => 'badge-soft-danger',
      'Surplus' => 'badge-soft-warning',
      default => 'badge-soft-secondary',
    };
  };

  $selisihClass = function ($value) {
    $value = (int) $value;

    if ($value < 0) {
      return 'danger';
    }

    if ($value > 0) {
      return 'warning';
    }

    return 'success';
  };

  $formatSigned = function ($value) {
    $value = (int) $value;

    return $value > 0 ? '+' . number_format($value, 0, ',', '.') : number_format($value, 0, ',', '.');
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  @if(!empty($errorMessage))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="mdi mdi-alert-circle-outline me-1"></i> {{ $errorMessage }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="mdi mdi-check-circle-outline me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="mdi mdi-alert-circle-outline me-1"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Laporan Stok Opname Store
        </h4>
        <div class="hero-subtitle">
          Monitoring hasil stok opname store, selisih qty system vs scan, kondisi barang, dan detail RFID.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-clipboard-list-outline me-1"></i>
          {{ number_format($totalData, 0, ',', '.') }} Data
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-check-decagram-outline me-1"></i>
          {{ number_format($balancedCount, 0, ',', '.') }} Balanced
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-alert-circle-outline me-1"></i>
          {{ number_format($issueCount, 0, ',', '.') }} Selisih
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
        <h5 class="mb-1 fw-bold">Filter Laporan Stok Opname Store</h5>
        <small class="text-muted">
          Cari berdasarkan produk, kode opname, alasan selisih, catatan, store, dan periode tanggal.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterStoreStockOpname">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.store-stock-opnames.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.store-stock-opnames.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterStoreStockOpname">
        <div class="audit-filter-box">
          <form method="GET"
                action="{{ route('monitoring.store-stock-opnames.index') }}"
                class="row g-3">

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Dari</label>
              <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Sampai</label>
              <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Store</label>
              <select name="store_id" class="form-select">
                <option value="">Semua Store</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected((string) $storeId === (string) $store->id)>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="search"
                     class="form-control"
                     value="{{ $search }}"
                     placeholder="Produk / kode opname / alasan / catatan">
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button class="btn btn-primary btn-sm" type="submit">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.store-stock-opnames.index') }}"
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
              <small class="text-muted">Data opname sesuai filter</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-clipboard-list-outline"></i>
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
              <div class="text-muted small">Qty System / Scan</div>
              <h4 class="mb-1">
                {{ number_format($totalQtySystem, 0, ',', '.') }}
                /
                {{ number_format($totalQtyScan, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Akumulasi halaman ini</small>
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
              <div class="text-muted small">Total Selisih</div>
              <h4 class="mb-1">{{ $formatSigned($totalSelisih) }}</h4>
              <small class="text-muted">Qty scan - qty system</small>
            </div>
            <div class="summary-icon {{ $totalSelisih === 0 ? 'success' : 'warning' }}">
              <i class="mdi mdi-scale-balance"></i>
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
              <div class="text-muted small">Qty Hancur Total</div>
              <h4 class="mb-1">{{ number_format($totalDestroyedQty, 0, ',', '.') }}</h4>
              <small class="text-muted">Barang kondisi hancur</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-package-variant-remove"></i>
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
        <h5 class="mb-1 fw-bold">Laporan Stok Opname Store</h5>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap audit-table">
          <thead>
            <tr>
              <th style="width: 60px;" class="text-center">No</th>
              <th>Waktu</th>
              <th>Gudang / Store</th>
              <th>Kode Opname</th>
              <th>Disetujui Oleh</th>
              <th>Produk</th>
              <th class="text-center">Qty System</th>
              <th class="text-center">Qty Scan</th>
              <th class="text-center">Selisih</th>
              <th>Status Summary</th>
              <th>Alasan Selisih</th>
              <th>Catatan Tambahan</th>
              <th>Kondisi Barang</th>
              <th>Ringkasan Status RFID</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $row)
              @php
                $selisih = (int) $row->selisih;
                $selisihDisplay = $selisih > 0 ? '+' . number_format($selisih, 0, ',', '.') : number_format($selisih, 0, ',', '.');
                $badgeClass = $summaryBadge($row->status_summary);
                $selisihPillClass = $selisihClass($selisih);
              @endphp

              <tr>
                <td class="text-center">{{ $rows->firstItem() + $loop->index }}</td>

                <td>
                  <span class="badge badge-soft-secondary">
                    {{ $row->report_time ?? '-' }}
                  </span>
                </td>

                <td class="store-col">
                  <span class="badge badge-soft-primary">
                    <i class="mdi mdi-store-outline me-1"></i>
                    {{ $row->store_name }}
                  </span>
                </td>

                <td class="fw-bold">{{ $row->opname_code }}</td>

                <td>
                  <div class="fw-semibold">{{ $row->approved_by_name ?? '-' }}</div>
                </td>

                <td class="product-col">
                  <div class="fw-bold text-dark">{{ $row->product_name }}</div>
                  <small class="text-muted">{{ $row->product_code }}</small>
                </td>

                <td class="text-center">
                  <span class="number-pill">
                    {{ number_format((int) $row->qty_system, 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-center">
                  <span class="number-pill info">
                    {{ number_format((int) $row->qty_scan, 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-center">
                  <span class="number-pill {{ $selisihPillClass }}">
                    {{ $selisihDisplay }}
                  </span>
                </td>

                <td>
                  <span class="badge {{ $badgeClass }}">
                    {{ $row->status_summary }}
                  </span>
                </td>

                <td class="note-col">
                  {{ $row->difference_reason ?? '-' }}
                </td>

                <td class="note-col">
                  {{ $row->additional_note ?? '-' }}
                </td>

                <td class="status-col">
                  @if(collect($row->condition_names)->isNotEmpty())
                    <div class="status-wrap">
                      @foreach($row->condition_names as $condition)
                        <span class="badge badge-soft-info">{{ $condition }}</span>
                      @endforeach
                    </div>
                  @else
                    <span class="text-muted">-</span>
                  @endif

                  @if((int) ($row->destroyed_qty ?? 0) > 0)
                    <div class="mt-2">
                      <span class="badge badge-soft-danger">
                        Hancur Total Qty: {{ number_format((int) $row->destroyed_qty, 0, ',', '.') }}
                      </span>
                    </div>
                  @endif
                </td>

                <td>
                  <div class="status-wrap">
                    <span class="badge badge-soft-success">
                      Match: {{ $row->rfid_summary['match'] }}
                    </span>
                    <span class="badge badge-soft-danger">
                      Missing: {{ $row->rfid_summary['missing'] }}
                    </span>
                    <span class="badge badge-soft-info">
                      Extra: {{ $row->rfid_summary['extra'] }}
                    </span>
                  </div>
                </td>

                <td class="text-center">
                  @if($row->show_detail_button)
                    <button type="button"
                            class="btn btn-sm btn-primary btn-rfid-detail"
                            data-url="{{ route('monitoring.store-stock-opnames.rfid-detail', $row->id) }}">
                      <i class="mdi mdi-eye-outline me-1"></i> Detail RFID
                    </button>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="15">
                  <div class="empty-state text-center text-muted">
                    <i class="mdi mdi-database-off-outline d-block mb-2" style="font-size: 2.3rem;"></i>
                    Tidak ada data stok opname store.
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

{{-- MODAL DETAIL RFID --}}
<div class="modal fade" id="rfidDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">
            <i class="mdi mdi-rfid me-1"></i> Detail RFID
          </h5>
          <small class="text-muted">Detail RFID missing dan extra berdasarkan laporan stok opname.</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <div class="fw-semibold">Produk</div>
          <div id="rfidDetailProductName" class="text-muted">-</div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <div class="modal-info-box">
              <div class="label">Alasan Selisih</div>
              <div id="rfidDetailDifferenceReason" class="value">-</div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="modal-info-box">
              <div class="label">Catatan Tambahan</div>
              <div id="rfidDetailAdditionalNote" class="value">-</div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="modal-info-box">
              <div class="label">Qty Hancur Total</div>
              <div id="rfidDetailDestroyedQty" class="value text-danger">0</div>
            </div>
          </div>
        </div>

        <div id="rfidDetailLoading" class="text-center py-4 d-none">
          <div class="spinner-border text-primary" role="status"></div>
          <div class="mt-2 text-muted">Memuat detail RFID...</div>
        </div>

        <div id="rfidDetailError" class="alert alert-danger d-none mb-3"></div>

        <div id="rfidDetailTableWrapper" class="table-responsive d-none">
          <table class="table table-bordered table-striped align-middle mb-0 rfid-modal-table">
            <thead class="text-center">
              <tr>
                <th rowspan="2" style="width: 60px;">No</th>
                <th colspan="2">RFID Missing</th>
                <th colspan="2">RFID Extra</th>
              </tr>
              <tr>
                <th>RFID</th>
                <th>Lokasi</th>
                <th>RFID</th>
                <th>Lokasi</th>
              </tr>
            </thead>

            <tbody id="rfidDetailTableBody">
              <tr>
                <td colspan="5" class="text-center text-muted py-4">Belum ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Tutup
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-script')
<script>
(function () {
  'use strict';

  const modalEl = document.getElementById('rfidDetailModal');
  const modal = new bootstrap.Modal(modalEl);

  const productNameEl = document.getElementById('rfidDetailProductName');
  const differenceReasonEl = document.getElementById('rfidDetailDifferenceReason');
  const additionalNoteEl = document.getElementById('rfidDetailAdditionalNote');
  const destroyedQtyEl = document.getElementById('rfidDetailDestroyedQty');

  const loadingEl = document.getElementById('rfidDetailLoading');
  const errorEl = document.getElementById('rfidDetailError');
  const tableWrapperEl = document.getElementById('rfidDetailTableWrapper');
  const tableBodyEl = document.getElementById('rfidDetailTableBody');

  function escapeHtml(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function resetModal() {
    productNameEl.textContent = '-';
    differenceReasonEl.textContent = '-';
    additionalNoteEl.textContent = '-';
    destroyedQtyEl.textContent = '0';

    loadingEl.classList.add('d-none');
    errorEl.classList.add('d-none');
    errorEl.textContent = '';
    tableWrapperEl.classList.add('d-none');

    tableBodyEl.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-muted py-4">Belum ada data</td>
      </tr>
    `;
  }

  async function openRfidDetail(url) {
    resetModal();
    modal.show();
    loadingEl.classList.remove('d-none');

    try {
      const response = await fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });

      const data = await response.json();

      loadingEl.classList.add('d-none');

      if (!response.ok) {
        throw new Error(data.message || 'Gagal memuat detail RFID.');
      }

      productNameEl.textContent = data.product_name || '-';
      differenceReasonEl.textContent = data.difference_reason || '-';
      additionalNoteEl.textContent = data.additional_note || '-';
      destroyedQtyEl.textContent = data.destroyed_qty ?? 0;

      const detailRows = Array.isArray(data.detail) ? data.detail : [];
      tableWrapperEl.classList.remove('d-none');

      if (detailRows.length === 0) {
        tableBodyEl.innerHTML = `
          <tr>
            <td colspan="5" class="text-center text-muted py-4">Tidak ada detail RFID</td>
          </tr>
        `;
        return;
      }

      tableBodyEl.innerHTML = detailRows.map(row => `
        <tr>
          <td class="text-center">${escapeHtml(row.no ?? '-')}</td>
          <td>
            <span class="badge badge-soft-danger">
              ${escapeHtml(row.rfid_missing ?? '-')}
            </span>
          </td>
          <td>${escapeHtml(row.missing_location ?? '-')}</td>
          <td>
            <span class="badge badge-soft-info">
              ${escapeHtml(row.rfid_extra ?? '-')}
            </span>
          </td>
          <td>${escapeHtml(row.extra_location ?? '-')}</td>
        </tr>
      `).join('');
    } catch (error) {
      loadingEl.classList.add('d-none');
      errorEl.textContent = error.message || 'Terjadi kesalahan saat mengambil detail RFID.';
      errorEl.classList.remove('d-none');
    }
  }

  document.addEventListener('click', function (event) {
    const button = event.target.closest('.btn-rfid-detail');

    if (!button) {
      return;
    }

    const url = button.getAttribute('data-url');

    if (!url) {
      return;
    }

    openRfidDetail(url);
  });
})();
</script>
@endpush
