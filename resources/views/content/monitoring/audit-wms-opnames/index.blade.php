@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Audit WMS Stock Opname')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --aw-primary: #5b5cf6;
    --aw-primary-dark: #4338ca;
    --aw-success: #22c55e;
    --aw-success-soft: rgba(34, 197, 94, .14);
    --aw-info: #06b6d4;
    --aw-info-soft: rgba(6, 182, 212, .14);
    --aw-warning: #f59e0b;
    --aw-warning-soft: rgba(245, 158, 11, .16);
    --aw-danger: #ef4444;
    --aw-danger-soft: rgba(239, 68, 68, .14);
    --aw-purple: #8b5cf6;
    --aw-dark: #1e293b;
    --aw-muted: #64748b;
    --aw-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(245, 158, 11, .10), transparent 28%),
      var(--aw-body);
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
    border-color: var(--aw-primary);
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
    border-color: var(--aw-primary);
    background: linear-gradient(135deg, var(--aw-primary), var(--aw-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--aw-success);
    background: linear-gradient(135deg, var(--aw-success), #16a34a);
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
    background: linear-gradient(90deg, var(--aw-primary), var(--aw-warning));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--aw-dark);
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
    background: linear-gradient(135deg, var(--aw-primary), var(--aw-primary-dark));
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

  .warehouse-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .rfid-col {
    min-width: 260px;
    max-width: 360px;
    white-space: normal !important;
  }

  .condition-col {
    min-width: 180px;
    max-width: 260px;
    white-space: normal !important;
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--aw-muted);
  }

  .badge-soft-success {
    background: var(--aw-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--aw-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--aw-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--aw-info-soft) !important;
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
    background: var(--aw-success-soft);
    color: #15803d;
    border-color: rgba(34, 197, 94, .22);
  }

  .number-pill.warning {
    background: var(--aw-warning-soft);
    color: #b45309;
    border-color: rgba(245, 158, 11, .22);
  }

  .number-pill.danger {
    background: var(--aw-danger-soft);
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

  $totalSystem = (int) $rowCollection->sum(function ($row) {
    return (int) ($row->qty_system ?? 0);
  });

  $totalScanned = (int) $rowCollection->sum(function ($row) {
    return (int) ($row->qty_scanned ?? 0);
  });

  $totalMissing = (int) $rowCollection->sum(function ($row) {
    return (int) ($row->qty_missing ?? 0);
  });

  $totalExtra = (int) $rowCollection->sum(function ($row) {
    return (int) ($row->qty_extra ?? 0);
  });

  $totalDiff = $totalScanned - $totalSystem;

  $balancedCount = (int) $rowCollection->filter(function ($row) {
    return ($row->status_label ?? '') === 'Balanced';
  })->count();

  $issueCount = (int) $rowCollection->filter(function ($row) {
    return in_array(($row->status_label ?? ''), ['Over', 'Shortage'], true);
  })->count();

  $hasFilter = request()->hasAny([
    'warehouse_id',
    'q',
    'from',
    'to',
  ]);

  $statusBadge = function ($status) {
    return match($status) {
      'Balanced' => 'badge-soft-success',
      'Over' => 'badge-soft-warning',
      'Shortage' => 'badge-soft-danger',
      default => 'badge-soft-secondary',
    };
  };

  $diffClass = function ($value) {
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

    return $value > 0
      ? '+' . number_format($value, 0, ',', '.')
      : number_format($value, 0, ',', '.');
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Audit WMS Stock Opname
        </h4>
        <div class="hero-subtitle">
          Monitoring hasil stock opname warehouse, selisih qty system vs scanned, RFID missing, dan RFID extra.
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
        <h5 class="mb-1 fw-bold">Filter Audit WMS Stock Opname</h5>
        <small class="text-muted">
          Cari berdasarkan warehouse, produk, kode opname, RFID, status, dan periode tanggal.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterAuditWms">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.audit-wms-opnames.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.audit-wms-opnames.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterAuditWms">
        <div class="filter-box">
          <form method="GET"
                action="{{ route('monitoring.audit-wms-opnames.index') }}"
                class="row g-3">

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Dari</label>
              <input type="date" name="from" class="form-control" value="{{ $from ?? '' }}">
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Sampai</label>
              <input type="date" name="to" class="form-control" value="{{ $to ?? '' }}">
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Warehouse</label>
              <select name="warehouse_id"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Warehouse">
                <option value="">Semua Warehouse</option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" @selected((string) ($warehouseId ?? '') === (string) $warehouse->id)>
                    {{ $warehouse->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q ?? '' }}"
                     placeholder="Produk / kode opname / RFID / status">
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button class="btn btn-primary btn-sm" type="submit">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.audit-wms-opnames.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.audit-wms-opnames.index') }}"
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
              <div class="text-muted small">Qty System / Scanned</div>
              <h4 class="mb-1">
                {{ number_format($totalSystem, 0, ',', '.') }}
                /
                {{ number_format($totalScanned, 0, ',', '.') }}
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
              <h4 class="mb-1">{{ $formatSigned($totalDiff) }}</h4>
              <small class="text-muted">Scanned - system</small>
            </div>
            <div class="summary-icon {{ $totalDiff === 0 ? 'success' : 'warning' }}">
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
              <div class="text-muted small">Missing / Extra</div>
              <h4 class="mb-1">
                {{ number_format($totalMissing, 0, ',', '.') }}
                /
                {{ number_format($totalExtra, 0, ',', '.') }}
              </h4>
              <small class="text-muted">RFID missing / extra</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-rfid-off"></i>
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
        <h5 class="mb-1 fw-bold">Audit WMS Stock Opname</h5>
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
              <th>Tanggal Dibuat</th>
              <th>Tanggal Selesai</th>
              <th>Warehouse</th>
              <th>Kode Opname</th>
              <th>Disetujui Oleh</th>
              <th>Status Opname</th>
              <th>Produk</th>
              <th class="text-center">Pending</th>
              <th class="text-center">Qty System</th>
              <th class="text-center">Qty Scanned</th>
              <th class="text-center">Match</th>
              <th class="text-center">Missing</th>
              <th class="text-center">Extra</th>
              <th class="text-center">Selisih</th>
              <th>Status Summary</th>
              <th>Kondisi Barang</th>
              <th>RFID List</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $row)
              @php
                $diff = (int) ($row->qty_diff ?? 0);
                $diffDisplay = $formatSigned($diff);
                $diffPillClass = $diffClass($diff);
              @endphp

              <tr>
                <td class="text-center">{{ $rows->firstItem() + $loop->index }}</td>

                <td>
                  <span class="badge badge-soft-secondary">
                    {{ $row->tanggal_dibuat ? \Carbon\Carbon::parse($row->tanggal_dibuat)->format('d-m-Y H:i') : '-' }}
                  </span>
                </td>

                <td>
                  <span class="badge badge-soft-secondary">
                    {{ $row->tanggal_selesai ? \Carbon\Carbon::parse($row->tanggal_selesai)->format('d-m-Y H:i') : '-' }}
                  </span>
                </td>

                <td class="warehouse-col">
                  <span class="badge badge-soft-primary">
                    <i class="mdi mdi-warehouse me-1"></i>
                    {{ $row->warehouse_name ?? '-' }}
                  </span>
                </td>

                <td class="fw-bold">{{ $row->opname_code ?? '-' }}</td>

                <td>
                  <div class="fw-semibold">{{ $row->approved_by_name ?? '-' }}</div>
                </td>

                <td>
                  <span class="badge badge-soft-info">
                    {{ $row->opname_status ?? '-' }}
                  </span>
                </td>

                <td class="product-col">
                  <div class="fw-bold text-dark">{{ $row->product_name ?? '-' }}</div>
                  <small class="text-muted">{{ $row->product_code ?? '-' }}</small>
                </td>

                <td class="text-center">
                  <span class="number-pill">
                    {{ number_format((int) ($row->qty_pending ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-center">
                  <span class="number-pill">
                    {{ number_format((int) ($row->qty_system ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-center">
                  <span class="number-pill info">
                    {{ number_format((int) ($row->qty_scanned ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-center">
                  <span class="number-pill success">
                    {{ number_format((int) ($row->qty_match ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-center">
                  <span class="number-pill danger">
                    {{ number_format((int) ($row->qty_missing ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-center">
                  <span class="number-pill warning">
                    {{ number_format((int) ($row->qty_extra ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-center">
                  <span class="number-pill {{ $diffPillClass }}">
                    {{ $diffDisplay }}
                  </span>
                </td>

                <td>
                  <span class="badge {{ $statusBadge($row->status_label ?? '-') }}">
                    {{ $row->status_label ?? '-' }}
                  </span>
                </td>

                <td class="condition-col">
                  {{ $row->item_conditions ?? '-' }}
                </td>

                <td class="rfid-col">
                  {{ $row->rfid_list ?? '-' }}
                </td>

                <td class="text-center">
                  @if(((int) ($row->qty_missing ?? 0) > 0) || ((int) ($row->qty_extra ?? 0) > 0))
                    <button type="button"
                            class="btn btn-sm btn-primary btn-rfid-detail"
                            data-header-id="{{ $row->opname_id }}"
                            data-product-id="{{ $row->product_id }}"
                            data-product-name="{{ $row->product_name ?? '-' }}">
                      <i class="mdi mdi-eye-outline me-1"></i> Detail
                    </button>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="19">
                  <div class="empty-state text-center text-muted">
                    <i class="mdi mdi-database-off-outline d-block mb-2" style="font-size: 2.3rem;"></i>
                    Tidak ada data audit WMS stock opname.
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
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">
            <i class="mdi mdi-rfid me-1"></i> Detail RFID Missing & Extra
          </h5>
          <small class="text-muted" id="rfidDetailProductName">-</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div id="rfidDetailLoading" class="text-center py-4 d-none">
          <div class="spinner-border text-primary" role="status"></div>
          <div class="mt-2 text-muted">Memuat detail RFID...</div>
        </div>

        <div id="rfidDetailError" class="alert alert-danger d-none mb-3"></div>

        <div id="rfidDetailTableWrapper" class="table-responsive d-none">
          <table class="table table-bordered table-striped align-middle mb-0 rfid-modal-table">
            <thead class="text-center">
              <tr>
                <th style="width: 60px;">No</th>
                <th>RFID Missing</th>
                <th>RFID Extra</th>
              </tr>
            </thead>

            <tbody id="rfidDetailTableBody">
              <tr>
                <td colspan="3" class="text-center text-muted py-4">Belum ada data</td>
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

  $(document).ready(function () {
    initFilterSelect2();

    const tooltipNodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipNodes.forEach(el => new bootstrap.Tooltip(el));

    const modalEl = document.getElementById('rfidDetailModal');
    const modal = new bootstrap.Modal(modalEl);

    const productNameEl = document.getElementById('rfidDetailProductName');
    const loadingEl = document.getElementById('rfidDetailLoading');
    const errorEl = document.getElementById('rfidDetailError');
    const tableWrapperEl = document.getElementById('rfidDetailTableWrapper');
    const tableBodyEl = document.getElementById('rfidDetailTableBody');

    function resetModal() {
      productNameEl.textContent = '-';

      loadingEl.classList.add('d-none');
      errorEl.classList.add('d-none');
      errorEl.textContent = '';
      tableWrapperEl.classList.add('d-none');

      tableBodyEl.innerHTML = `
        <tr>
          <td colspan="3" class="text-center text-muted py-4">Belum ada data</td>
        </tr>
      `;
    }

    async function openRfidDetail(headerId, productId, productName) {
      resetModal();
      modal.show();
      loadingEl.classList.remove('d-none');
      productNameEl.textContent = productName || '-';

      try {
        const detailUrl = @json(route('monitoring.audit-wms-opnames.detail'));

        const params = new URLSearchParams({
          headerId: headerId || '',
          productId: productId || ''
        });

        const response = await fetch(detailUrl + '?' + params.toString(), {
          method: 'GET',
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

        tableWrapperEl.classList.remove('d-none');

        if (!Array.isArray(data) || data.length === 0) {
          tableBodyEl.innerHTML = `
            <tr>
              <td colspan="3" class="text-center text-muted py-4">Tidak ada detail RFID</td>
            </tr>
          `;
          return;
        }

        tableBodyEl.innerHTML = data.map(row => `
          <tr>
            <td class="text-center">${escapeHtml(row.no ?? '-')}</td>
            <td>
              <span class="badge badge-soft-danger">
                ${escapeHtml(row.rfid_missing ?? '-')}
              </span>
            </td>
            <td>
              <span class="badge badge-soft-info">
                ${escapeHtml(row.rfid_extra ?? '-')}
              </span>
            </td>
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

      openRfidDetail(
        button.getAttribute('data-header-id'),
        button.getAttribute('data-product-id'),
        button.getAttribute('data-product-name')
      );
    });
  });
})(window.jQuery);
</script>
@endpush
