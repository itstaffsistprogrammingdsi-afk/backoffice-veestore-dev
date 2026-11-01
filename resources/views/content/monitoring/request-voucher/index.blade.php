@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Request Voucher')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --vrq-primary: #5b5cf6;
    --vrq-primary-dark: #4338ca;
    --vrq-primary-soft: rgba(91, 92, 246, .12);
    --vrq-info: #06b6d4;
    --vrq-info-soft: rgba(6, 182, 212, .14);
    --vrq-success: #22c55e;
    --vrq-success-soft: rgba(34, 197, 94, .14);
    --vrq-warning: #f59e0b;
    --vrq-warning-soft: rgba(245, 158, 11, .16);
    --vrq-danger: #ef4444;
    --vrq-danger-soft: rgba(239, 68, 68, .14);
    --vrq-purple: #8b5cf6;
    --vrq-dark: #1e293b;
    --vrq-muted: #64748b;
    --vrq-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(245, 158, 11, .10), transparent 28%),
      var(--vrq-body);
  }

  .select2-container { width: 100% !important; }

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
    border-color: var(--vrq-primary);
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

  .dashboard-hero::before,
  .dashboard-hero::after {
    content: "";
    position: absolute;
    border-radius: 999px;
    background: rgba(255,255,255,.16);
  }

  .dashboard-hero::before {
    width: 260px;
    height: 260px;
    right: -80px;
    top: -100px;
  }

  .dashboard-hero::after {
    width: 180px;
    height: 180px;
    right: 120px;
    bottom: -120px;
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

  .hero-subtitle { color: rgba(255,255,255,.84) !important; }

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
    border-bottom: 1px solid rgba(226, 232, 240, .85);
    background: transparent;
    padding: 1.15rem 1.25rem;
  }

  .card-body { padding: 1.25rem; }

  .btn {
    border-radius: .75rem;
    font-weight: 700;
  }

  .btn-primary {
    border-color: var(--vrq-primary);
    background: linear-gradient(135deg, var(--vrq-primary), var(--vrq-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
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
    background: linear-gradient(90deg, var(--vrq-primary), var(--vrq-warning));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--vrq-dark);
    font-weight: 900;
    letter-spacing: -.03em;
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
    background: linear-gradient(135deg, var(--vrq-primary), var(--vrq-primary-dark));
  }

  .summary-icon.success { background: linear-gradient(135deg, #22c55e, #16a34a); }
  .summary-icon.info { background: linear-gradient(135deg, #06b6d4, #0284c7); }
  .summary-icon.warning { background: linear-gradient(135deg, #f59e0b, #f97316); }
  .summary-icon.danger { background: linear-gradient(135deg, #ef4444, #dc2626); }

  .chart-wrap { min-height: 300px; position: relative; }
  .chart-wrap-sm { min-height: 255px; position: relative; }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .request-table thead th,
  .detail-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .76rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 900;
    border-bottom: 1px solid #dbe1ea;
  }

  .request-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .notes-col,
  .product-col {
    min-width: 260px;
    white-space: normal !important;
  }

  .source-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .badge-soft-primary,
  .badge-soft-success,
  .badge-soft-info,
  .badge-soft-warning,
  .badge-soft-danger,
  .badge-soft-secondary {
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 800;
  }

  .badge-soft-primary { background: var(--vrq-primary-soft); color: var(--vrq-primary-dark); }
  .badge-soft-success { background: var(--vrq-success-soft); color: #15803d; }
  .badge-soft-info { background: var(--vrq-info-soft); color: #0369a1; }
  .badge-soft-warning { background: var(--vrq-warning-soft); color: #b45309; }
  .badge-soft-danger { background: var(--vrq-danger-soft); color: #b91c1c; }
  .badge-soft-secondary { background: #f1f5f9; color: #475569; }

  .number-pill {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    min-width: 58px;
    border-radius: .75rem;
    padding: .4rem .65rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-weight: 900;
    color: #334155;
  }

  .number-pill.success {
    background: var(--vrq-success-soft);
    color: #15803d;
    border-color: rgba(34, 197, 94, .22);
  }

  .number-pill.warning {
    background: var(--vrq-warning-soft);
    color: #b45309;
    border-color: rgba(245, 158, 11, .22);
  }

  .number-pill.danger {
    background: var(--vrq-danger-soft);
    color: #b91c1c;
    border-color: rgba(239, 68, 68, .22);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--vrq-muted);
  }

  .empty-state {
    border: 1px dashed #cbd5e1;
    border-radius: 1rem;
    padding: 2.5rem 1rem;
    background: #f8fafc;
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

  @media (max-width: 767.98px) {
    .dashboard-hero { border-radius: 1rem; }
    .card-body, .card-header { padding: 1rem; }
    .chart-wrap, .chart-wrap-sm { min-height: 260px; }
  }
</style>
@endpush

@section('content')
@php
  $summary = $summary ?? (object) [
    'total_request' => 0,
    'total_pending' => 0,
    'total_approved' => 0,
    'total_rejected' => 0,
    'total_items' => 0,
    'total_store' => 0,
    'total_voucher_created' => 0,
    'total_usage' => 0,
  ];

  $hasFilter = request()->hasAny(['q', 'status', 'store_id', 'from', 'to', 'per_page', 'auto_refresh']);

  $statusBadge = function ($status) {
    return match((string) $status) {
      'pending' => 'badge-soft-warning',
      'approved' => 'badge-soft-success',
      'rejected' => 'badge-soft-danger',
      default => 'badge-soft-secondary',
    };
  };

  $voucherTypeLabel = function ($type) {
    return match((string) $type) {
      'percentage' => 'Percentage',
      'fixed' => 'Fixed',
      'nominal' => 'Nominal',
      'free_shipping' => 'Free Shipping',
      default => $type ?: '-',
    };
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Request Voucher
        </h4>
        <div class="hero-subtitle">
          Monitoring realtime aktivitas request voucher dari cabang, status approval, voucher yang terbentuk, item produk, dan usage voucher.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <span class="live-dot me-1"></span>
          Live Monitoring
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-calendar-range me-1"></i>
          {{ \Carbon\Carbon::parse($from)->format('d M Y') }} - {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ now()->format('d-m-Y H:i:s') }}
        </span>

        @if($autoRefresh)
          <span class="hero-badge">
            <i class="mdi mdi-autorenew me-1"></i>
            Auto Refresh 60 Detik
          </span>
        @endif
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Monitoring Request Voucher</h5>
        <small class="text-muted">
          Filter berdasarkan periode, store, status request, nomor request, voucher, dan user request.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#filterRequestVoucher">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.request-voucher.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.request-voucher.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="filterRequestVoucher">
        <div class="filter-box">
          <form method="GET"
                action="{{ route('monitoring.request-voucher.index') }}"
                class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Dari</label>
              <input type="date" name="from" value="{{ $from }}" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Sampai</label>
              <input type="date" name="to" value="{{ $to }}" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Store</label>
              <select name="store_id" class="form-select js-select2" data-placeholder="Semua Store">
                <option value=""></option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected((string) $storeId === (string) $store->id)>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select js-select2" data-placeholder="Semua Status">
                <option value=""></option>
                @foreach($statuses as $key => $label)
                  <option value="{{ $key }}" @selected((string) $status === (string) $key)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Pencarian</label>
              <input type="text"
                     name="q"
                     value="{{ $q }}"
                     class="form-control"
                     placeholder="Request number / store / voucher / user / notes">
            </div>

            <div class="col-md-2">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select">
                @foreach([10, 25, 50, 100, 200] as $size)
                  <option value="{{ $size }}" @selected((int) $perPage === $size)>
                    {{ $size }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Realtime Auto Refresh</label>
              <select name="auto_refresh" class="form-select">
                <option value="0" @selected(!$autoRefresh)>Nonaktif</option>
                <option value="1" @selected($autoRefresh)>Aktif, refresh 60 detik</option>
              </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.request-voucher.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.request-voucher.index') }}"
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
    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Total Request</div>
              <h4 class="mb-1">{{ number_format((int) $summary->total_request, 0, ',', '.') }}</h4>
              <small class="text-muted">Semua request sesuai filter</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-ticket-percent-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Pending</div>
              <h4 class="mb-1">{{ number_format((int) $summary->total_pending, 0, ',', '.') }}</h4>
              <small class="text-muted">Menunggu approval</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-clock-alert-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Approved / Rejected</div>
              <h4 class="mb-1">
                {{ number_format((int) $summary->total_approved, 0, ',', '.') }}
                /
                {{ number_format((int) $summary->total_rejected, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Status keputusan request</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-check-decagram-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Voucher / Usage</div>
              <h4 class="mb-1">
                {{ number_format((int) $summary->total_voucher_created, 0, ',', '.') }}
                /
                {{ number_format((int) $summary->total_usage, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Voucher dibuat / digunakan</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-ticket-confirmation-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-7">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Trend Request Voucher</h5>
          <small class="text-muted">Jumlah request per hari berdasarkan status.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <div id="chartDaily"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-5">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Status Request</h5>
          <small class="text-muted">Komposisi pending, approved, dan rejected.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm">
            <div id="chartStatus"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TOP STORE --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-1 fw-bold">Top Store Aktivitas Request Voucher</h5>
      <small class="text-muted">Store dengan request voucher terbanyak pada periode filter.</small>
    </div>
    <div class="card-body">
      <div class="chart-wrap">
        <div id="chartStore"></div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Detail Aktivitas Request Voucher</h5>
        <small class="text-muted">
          Total Data:
          <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap request-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal Request</th>
              <th>Request Number</th>
              <th>Store</th>
              <th>Status</th>
              <th>Requested By</th>
              <th>Approved By</th>
              <th>Approved At</th>
              <th>Voucher</th>
              <th>Type</th>
              <th class="text-end">Value</th>
              <th class="text-end">Max Disc.</th>
              <th class="text-end">Min Purchase</th>
              <th>Valid</th>
              <th class="text-end">Usage</th>
              <th class="text-end">Items</th>
              <th>Notes</th>
              <th class="text-center">Detail</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $row)
              @php
                $items = $detailItems->get($row->id, collect());
              @endphp

              <tr>
                <td>{{ $rows->firstItem() + $loop->index }}</td>

                <td>
                  <span class="badge badge-soft-secondary">
                    {{ $row->request_date ? \Carbon\Carbon::parse($row->request_date)->format('d-m-Y') : '-' }}
                  </span>
                </td>

                <td class="fw-bold">{{ $row->request_number ?? '-' }}</td>

                <td class="source-col">
                  <span class="badge badge-soft-primary">
                    <i class="mdi mdi-store-outline me-1"></i>
                    {{ $row->store_name ?? '-' }}
                  </span>
                </td>

                <td>
                  <span class="badge {{ $statusBadge($row->status) }}">
                    {{ $row->status_label ?? '-' }}
                  </span>
                </td>

                <td>{{ $row->requested_by_name ?? '-' }}</td>
                <td>{{ $row->approved_by_name ?? '-' }}</td>

                <td>
                  {{ $row->approved_at ? \Carbon\Carbon::parse($row->approved_at)->format('d-m-Y H:i') : '-' }}
                </td>

                <td class="source-col">
                  @if($row->voucher_id)
                    <div class="fw-bold text-dark">{{ $row->voucher_code ?? '-' }}</div>
                    <small class="text-muted">{{ $row->voucher_name ?? '-' }}</small>
                  @else
                    <span class="badge badge-soft-secondary">Belum ada voucher</span>
                  @endif
                </td>

                <td>{{ $voucherTypeLabel($row->voucher_type) }}</td>

                <td class="text-end">
                  {{ number_format((float) ($row->voucher_value ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp{{ number_format((float) ($row->voucher_max_discount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp{{ number_format((float) ($row->voucher_min_purchase ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  <small>
                    {{ $row->valid_from ? \Carbon\Carbon::parse($row->valid_from)->format('d-m-Y') : '-' }}
                    -
                    {{ $row->valid_until ? \Carbon\Carbon::parse($row->valid_until)->format('d-m-Y') : '-' }}
                  </small>
                </td>

                <td class="text-end">
                  <span class="number-pill success">
                    {{ number_format((int) ($row->usage_count ?? 0), 0, ',', '.') }}
                    /
                    {{ number_format((int) ($row->usage_limit ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="text-end">
                  <span class="number-pill">
                    {{ number_format((int) ($row->total_items ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td class="notes-col">{{ $row->notes ?? '-' }}</td>

                <td class="text-center">
                  <button type="button"
                          class="btn btn-sm btn-primary btn-detail"
                          data-bs-toggle="modal"
                          data-bs-target="#modalDetailVoucher"
                          data-request-id="{{ $row->id }}"
                          data-request-number="{{ $row->request_number ?? '-' }}"
                          data-store-name="{{ $row->store_name ?? '-' }}"
                          data-status-label="{{ $row->status_label ?? '-' }}"
                          data-request-date="{{ $row->request_date ? \Carbon\Carbon::parse($row->request_date)->format('d-m-Y') : '-' }}"
                          data-requested-by="{{ $row->requested_by_name ?? '-' }}"
                          data-approved-by="{{ $row->approved_by_name ?? '-' }}"
                          data-approved-at="{{ $row->approved_at ? \Carbon\Carbon::parse($row->approved_at)->format('d-m-Y H:i') : '-' }}"
                          data-voucher-code="{{ $row->voucher_code ?? '-' }}"
                          data-voucher-name="{{ $row->voucher_name ?? '-' }}"
                          data-notes="{{ $row->notes ?? '-' }}">
                    <i class="mdi mdi-eye-outline me-1"></i> Detail
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="18">
                  <div class="empty-state text-center text-muted">
                    <i class="mdi mdi-database-off-outline d-block mb-2" style="font-size:2.3rem;"></i>
                    Belum ada data request voucher sesuai filter.
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
      @endif
    </div>
  </div>
</div>

{{-- MODAL DETAIL --}}
<div class="modal fade" id="modalDetailVoucher" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">
            <i class="mdi mdi-ticket-percent-outline me-1"></i>
            Detail Request Voucher
          </h5>
          <small class="text-muted" id="modalRequestSubtitle">-</small>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100">
              <div class="text-muted small">Request Number</div>
              <div class="fw-bold text-dark" id="modalRequestNumber">-</div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100">
              <div class="text-muted small">Store</div>
              <div class="fw-bold text-dark" id="modalStoreName">-</div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100">
              <div class="text-muted small">Status</div>
              <div class="fw-bold text-dark" id="modalStatus">-</div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100">
              <div class="text-muted small">Tanggal Request</div>
              <div class="fw-bold text-dark" id="modalRequestDate">-</div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100">
              <div class="text-muted small">Requested By</div>
              <div class="fw-bold text-dark" id="modalRequestedBy">-</div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100">
              <div class="text-muted small">Approved By</div>
              <div class="fw-bold text-dark" id="modalApprovedBy">-</div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100">
              <div class="text-muted small">Approved At</div>
              <div class="fw-bold text-dark" id="modalApprovedAt">-</div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100">
              <div class="text-muted small">Voucher</div>
              <div class="fw-bold text-dark" id="modalVoucher">-</div>
            </div>
          </div>

          <div class="col-md-12">
            <div class="border rounded-3 p-3">
              <div class="text-muted small">Notes</div>
              <div class="fw-bold text-dark" id="modalNotes">-</div>
            </div>
          </div>
        </div>

        <h6 class="fw-bold mb-2">Detail Item Request</h6>

        <div class="table-responsive">
          <table class="table table-sm table-striped table-bordered mb-0 detail-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Kode Produk</th>
                <th>Nama Produk</th>
                <th>Item ID</th>
                <th>Status Item</th>
                <th>Created At</th>
              </tr>
            </thead>

            <tbody id="modalDetailItemsBody">
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Belum ada detail.</td>
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush

@push('page-script')
<script>
(function ($) {
  'use strict';

  const autoRefresh = @json($autoRefresh);
  const statusRows = @json($statusChartRows);
  const storeRows = @json($storeChartRows);
  const dailyRows = @json($dailyRows);
  const detailItems = @json($detailItems);

  const colors = ['#5b5cf6', '#f59e0b', '#22c55e', '#ef4444', '#06b6d4', '#8b5cf6', '#64748b'];

  function numberId(value) {
    return Number(value || 0).toLocaleString('id-ID');
  }

  function escapeHtml(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function emptyChart(el, message) {
    if (el) {
      el.innerHTML = `<div class="empty-state text-center text-muted">${message}</div>`;
    }
  }

  function mountStatusChart() {
    const el = document.querySelector('#chartStatus');
    if (!el) return;

    if (!Array.isArray(statusRows) || statusRows.length === 0) {
      emptyChart(el, 'Belum ada data status.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'donut', height: 255, toolbar: { show: false } },
      labels: statusRows.map(r => r.label),
      series: statusRows.map(r => Number(r.total || 0)),
      colors: ['#f59e0b', '#22c55e', '#ef4444', '#5b5cf6'],
      legend: { position: 'bottom' },
      dataLabels: { enabled: true },
      tooltip: { y: { formatter: v => numberId(v) + ' request' } }
    }).render();
  }

  function mountDailyChart() {
    const el = document.querySelector('#chartDaily');
    if (!el) return;

    if (!Array.isArray(dailyRows) || dailyRows.length === 0) {
      emptyChart(el, 'Belum ada trend request pada periode ini.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'line', height: 300, toolbar: { show: false } },
      stroke: { width: [3, 3, 3], curve: 'smooth' },
      series: [
        { name: 'Pending', data: dailyRows.map(r => Number(r.pending_request || 0)) },
        { name: 'Approved', data: dailyRows.map(r => Number(r.approved_request || 0)) },
        { name: 'Rejected', data: dailyRows.map(r => Number(r.rejected_request || 0)) }
      ],
      colors: ['#f59e0b', '#22c55e', '#ef4444'],
      xaxis: { categories: dailyRows.map(r => r.label) },
      dataLabels: { enabled: false },
      tooltip: { y: { formatter: v => numberId(v) + ' request' } },
      legend: { position: 'bottom' }
    }).render();
  }

  function mountStoreChart() {
    const el = document.querySelector('#chartStore');
    if (!el) return;

    if (!Array.isArray(storeRows) || storeRows.length === 0) {
      emptyChart(el, 'Belum ada aktivitas request voucher per store.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'bar', height: 310, toolbar: { show: false } },
      plotOptions: { bar: { horizontal: true, borderRadius: 8, barHeight: '65%' } },
      series: [{ name: 'Request', data: storeRows.map(r => Number(r.total_request || 0)) }],
      xaxis: { categories: storeRows.map(r => r.label) },
      colors: ['#5b5cf6'],
      dataLabels: { enabled: false },
      tooltip: { y: { formatter: v => numberId(v) + ' request' } }
    }).render();
  }

  function renderDetailModal(button) {
    const requestId = button.getAttribute('data-request-id');
    const items = detailItems[requestId] || [];

    $('#modalRequestSubtitle').text(button.getAttribute('data-request-number') || '-');
    $('#modalRequestNumber').text(button.getAttribute('data-request-number') || '-');
    $('#modalStoreName').text(button.getAttribute('data-store-name') || '-');
    $('#modalStatus').text(button.getAttribute('data-status-label') || '-');
    $('#modalRequestDate').text(button.getAttribute('data-request-date') || '-');
    $('#modalRequestedBy').text(button.getAttribute('data-requested-by') || '-');
    $('#modalApprovedBy').text(button.getAttribute('data-approved-by') || '-');
    $('#modalApprovedAt').text(button.getAttribute('data-approved-at') || '-');

    const voucherCode = button.getAttribute('data-voucher-code') || '-';
    const voucherName = button.getAttribute('data-voucher-name') || '-';
    $('#modalVoucher').text(voucherCode + ' - ' + voucherName);
    $('#modalNotes').text(button.getAttribute('data-notes') || '-');

    const body = document.getElementById('modalDetailItemsBody');

    if (!items.length) {
      body.innerHTML = `
        <tr>
          <td colspan="6" class="text-center text-muted py-4">Tidak ada detail item.</td>
        </tr>
      `;
      return;
    }

    body.innerHTML = items.map((item, index) => `
      <tr>
        <td>${index + 1}</td>
        <td>
          <span class="badge badge-soft-primary">
            ${escapeHtml(item.product_code || '-')}
          </span>
        </td>
        <td class="product-col">${escapeHtml(item.product_name || '-')}</td>
        <td>${escapeHtml(item.item_id || '-')}</td>
        <td>${escapeHtml(item.item_status || '-')}</td>
        <td>${item.created_at ? new Date(item.created_at).toLocaleString('id-ID') : '-'}</td>
      </tr>
    `).join('');
  }

  $(document).ready(function () {
    $('.js-select2').select2({
      theme: 'bootstrap-5',
      width: '100%',
      allowClear: true,
      placeholder: function () {
        return $(this).data('placeholder') || 'Pilih data';
      }
    });

    const tooltipNodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipNodes.forEach(el => new bootstrap.Tooltip(el));

    mountDailyChart();
    mountStatusChart();
    mountStoreChart();

    document.addEventListener('click', function (event) {
      const button = event.target.closest('.btn-detail');

      if (!button) {
        return;
      }

      renderDetailModal(button);
    });

    if (autoRefresh) {
      setTimeout(function () {
        window.location.reload();
      }, 60000);
    }
  });
})(window.jQuery);
</script>
@endpush
