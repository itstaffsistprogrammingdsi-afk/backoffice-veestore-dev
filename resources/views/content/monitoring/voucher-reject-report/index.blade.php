@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Voucher Reject Report')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --vr-primary: #5b5cf6;
    --vr-primary-dark: #4338ca;
    --vr-primary-soft: rgba(91, 92, 246, .12);
    --vr-success: #22c55e;
    --vr-success-soft: rgba(34, 197, 94, .14);
    --vr-info: #06b6d4;
    --vr-info-soft: rgba(6, 182, 212, .14);
    --vr-warning: #f59e0b;
    --vr-warning-soft: rgba(245, 158, 11, .16);
    --vr-danger: #ef4444;
    --vr-danger-soft: rgba(239, 68, 68, .14);
    --vr-purple: #8b5cf6;
    --vr-dark: #1e293b;
    --vr-muted: #64748b;
    --vr-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(239, 68, 68, .10), transparent 28%),
      var(--vr-body);
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
    border-color: var(--vr-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .dashboard-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    color: #fff;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
      linear-gradient(135deg, #4f46e5 0%, #ef4444 58%, #f59e0b 120%);
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
    border-color: var(--vr-primary);
    background: linear-gradient(135deg, var(--vr-primary), var(--vr-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--vr-success);
    background: linear-gradient(135deg, var(--vr-success), #16a34a);
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
    background: linear-gradient(90deg, var(--vr-primary), var(--vr-danger));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--vr-dark);
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
    background: linear-gradient(135deg, var(--vr-primary), var(--vr-primary-dark));
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

  .voucher-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .voucher-table tbody tr:hover {
    background-color: rgba(239, 68, 68, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--vr-muted);
  }

  .notes-col,
  .product-col,
  .voucher-col {
    min-width: 260px;
    white-space: normal !important;
  }

  .request-col {
    min-width: 180px;
    white-space: normal !important;
  }

  .badge-soft-success {
    background: var(--vr-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--vr-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--vr-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--vr-info-soft) !important;
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

  .period-alert {
    border: 0;
    border-radius: 1.1rem;
    background: linear-gradient(135deg, rgba(239, 68, 68, .12), rgba(91, 92, 246, .10));
    color: #0f172a;
  }

  .item-list {
    display: flex;
    flex-direction: column;
    gap: .35rem;
  }

  .item-box {
    border: 1px solid #e2e8f0;
    border-radius: .8rem;
    padding: .55rem .65rem;
    background: #fff;
  }

  .item-box small {
    color: var(--vr-muted);
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
    'total_request' => 0,
    'total_pending' => 0,
    'total_approved' => 0,
    'total_rejected' => 0,
    'total_voucher' => 0,
    'total_usage_count' => 0,
    'total_items' => 0,
  ];

  $totalRequest = (int) ($summary->total_request ?? 0);
  $totalPending = (int) ($summary->total_pending ?? 0);
  $totalApproved = (int) ($summary->total_approved ?? 0);
  $totalRejected = (int) ($summary->total_rejected ?? 0);
  $totalVoucher = (int) ($summary->total_voucher ?? 0);
  $totalUsageCount = (int) ($summary->total_usage_count ?? 0);
  $totalItems = (int) ($summary->total_items ?? 0);

  $hasFilter = request()->hasAny([
    'q',
    'store_id',
    'status',
    'from',
    'to',
    'per_page',
  ]);

  $statusBadge = function ($status) {
    return match(strtolower((string) $status)) {
      'approved' => 'badge-soft-success',
      'rejected' => 'badge-soft-danger',
      'pending' => 'badge-soft-warning',
      default => 'badge-soft-secondary',
    };
  };

  $statusLabel = function ($status) {
    return match(strtolower((string) $status)) {
      'approved' => 'Approved',
      'rejected' => 'Rejected',
      'pending' => 'Pending',
      default => $status ? ucfirst(str_replace('_', ' ', $status)) : '-',
    };
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Voucher Reject Report
        </h4>
        <div class="hero-subtitle">
          Monitoring pengajuan voucher store, status approval, voucher yang terbentuk, detail item, produk, dan catatan reject.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-file-document-outline me-1"></i>
          {{ number_format($totalRequest, 0, ',', '.') }} Request
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-alert-outline me-1"></i>
          {{ number_format($totalPending, 0, ',', '.') }} Pending
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-close-circle-outline me-1"></i>
          {{ number_format($totalRejected, 0, ',', '.') }} Rejected
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
        <h5 class="mb-1 fw-bold">Filter Monitoring Voucher Reject Report</h5>
        <small class="text-muted">
          Gunakan filter untuk mencari data berdasarkan periode, store, status, request number, voucher, produk, item ID, dan notes.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
          <button type="button"
                  class="btn btn-outline-secondary btn-sm"
                  data-bs-toggle="collapse"
                  data-bs-target="#advancedFilterVoucherReject">
            <i class="mdi mdi-filter-variant me-1"></i> Filter
          </button>

          <a href="{{ route('monitoring.voucher-reject-report.index') }}"
             class="btn btn-outline-secondary btn-sm">
            <i class="mdi mdi-refresh me-1"></i> Reset
          </a>

          <a href="{{ route('monitoring.voucher-reject-report.export', request()->query()) }}"
             class="btn btn-success btn-sm">
            <i class="mdi mdi-file-excel-outline me-1"></i> Export
          </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterVoucherReject">
        <div class="filter-box">
          <form method="GET" action="{{ route('monitoring.voucher-reject-report.index') }}" class="row g-3">

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Dari</label>
              <input type="date"
                     name="from"
                     class="form-control"
                     value="{{ $from }}">
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Sampai</label>
              <input type="date"
                     name="to"
                     class="form-control"
                     value="{{ $to }}">
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Store</label>
              <select name="store_id" class="form-select js-select2-filter" data-placeholder="Semua Store">
                <option value="">Semua Store</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected((string) $storeId === (string) $store->id)>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select js-select2-filter" data-placeholder="Semua Status">
                <option value="">Semua Status</option>
                @foreach($statuses as $item)
                  <option value="{{ $item }}" @selected((string) $status === (string) $item)>
                    {{ $statusLabel($item) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select js-select2-filter" data-placeholder="Jumlah Data">
                @foreach([10, 25, 50, 100, 200] as $size)
                  <option value="{{ $size }}" @selected((int)($perPage ?? 10) === $size)>
                    {{ $size }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-9">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q }}"
                     placeholder="Store / product / request number / voucher / notes / item id">
            </div>

            <div class="col-12">
              <div class="text-muted small">
                Summary dan tabel akan mengikuti filter periode, store, status, dan keyword.
              </div>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.voucher-reject-report.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.voucher-reject-report.index') }}"
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

  {{-- PERIODE --}}
  <div class="alert period-alert mb-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
      <div>
        <strong><i class="mdi mdi-calendar-range me-1"></i> Periode Data:</strong>
        {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
        s/d
        {{ \Carbon\Carbon::parse($to)->format('d M Y') }}

        @if($status)
          <span class="ms-md-2">
            | <strong>Status:</strong>
            <span class="badge {{ $statusBadge($status) }}">
              {{ $statusLabel($status) }}
            </span>
          </span>
        @endif
      </div>

      <div class="small text-muted">
        Data ditampilkan berdasarkan tanggal request.
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
              <div class="text-muted small">Total Request</div>
              <h4 class="mb-1">{{ number_format($totalRequest, 0, ',', '.') }}</h4>
              <small class="text-muted">Semua pengajuan voucher</small>
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
              <div class="text-muted small">Pending</div>
              <h4 class="mb-1">{{ number_format($totalPending, 0, ',', '.') }}</h4>
              <small class="text-muted">Menunggu approval</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-clock-alert-outline"></i>
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
              <div class="text-muted small">Approved</div>
              <h4 class="mb-1">{{ number_format($totalApproved, 0, ',', '.') }}</h4>
              <small class="text-muted">Request disetujui</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-check-circle-outline"></i>
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
              <div class="text-muted small">Rejected</div>
              <h4 class="mb-1">{{ number_format($totalRejected, 0, ',', '.') }}</h4>
              <small class="text-muted">Request ditolak</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-close-circle-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-4">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Total Voucher</div>
              <h4 class="mb-1">{{ number_format($totalVoucher, 0, ',', '.') }}</h4>
              <small class="text-muted">Voucher yang terhubung</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-ticket-confirmation-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-4">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Total Item</div>
              <h4 class="mb-1">{{ number_format($totalItems, 0, ',', '.') }}</h4>
              <small class="text-muted">Item dalam request</small>
            </div>
            <div class="summary-icon purple">
              <i class="mdi mdi-package-variant-closed"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-4">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Usage Count</div>
              <h4 class="mb-1">{{ number_format($totalUsageCount, 0, ',', '.') }}</h4>
              <small class="text-muted">Akumulasi pemakaian voucher</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-counter"></i>
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
        <h5 class="mb-1 fw-bold">Laporan Voucher Reject</h5>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap voucher-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal Request</th>
              <th>Request Number</th>
              <th>Store</th>
              <th>Status</th>
              <th>Total Item</th>
              <th>Detail Item / Produk</th>
              <th>Voucher</th>
              <th>Type</th>
              <th class="text-end">Value</th>
              <th class="text-end">Max Discount</th>
              <th class="text-end">Min Purchase</th>
              <th>Valid From</th>
              <th>Valid Until</th>
              <th class="text-end">Usage Limit</th>
              <th class="text-end">Usage Count</th>
              <th>Aktif</th>
              <th>Approved At</th>
              <th>Notes</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              @php
                $items = $detailItems->get($row->id, collect());
              @endphp

              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td class="text-nowrap">
                  {{ $row->request_date ? \Carbon\Carbon::parse($row->request_date)->format('d M Y H:i') : '-' }}
                </td>

                <td class="request-col">
                  <div class="fw-semibold">{{ $row->request_number ?? '-' }}</div>
                  <small class="text-muted">{{ $row->id ?? '-' }}</small>
                </td>

                <td>{{ $row->store_name ?? '-' }}</td>

                <td>
                  <span class="badge {{ $statusBadge($row->status ?? '-') }}">
                    {{ $row->status_label ?? $statusLabel($row->status ?? '-') }}
                  </span>
                </td>

                <td class="text-end">
                  {{ number_format((int) ($row->total_items ?? 0), 0, ',', '.') }}
                </td>

                <td class="product-col">
                  @if($items->count())
                    <div class="item-list">
                      @foreach($items as $item)
                        <div class="item-box">
                          <div class="fw-semibold">
                            {{ $item->product_name ?? '-' }}
                          </div>
                          <small>
                            Kode: {{ $item->product_code ?? '-' }}
                            |
                            Item ID: {{ $item->item_id ?? '-' }}
                            |
                            Status: {{ $item->item_status ?? '-' }}
                          </small>
                        </div>
                      @endforeach
                    </div>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>

                <td class="voucher-col">
                  @if($row->voucher_code)
                    <div class="fw-semibold">{{ $row->voucher_code }}</div>
                    <small class="text-muted">{{ $row->voucher_name ?? '-' }}</small>
                  @else
                    <span class="badge badge-soft-secondary">Belum ada voucher</span>
                  @endif
                </td>

                <td>
                  @if($row->voucher_type)
                    <span class="badge badge-soft-info">
                      {{ ucfirst($row->voucher_type) }}
                    </span>
                  @else
                    <span class="badge badge-soft-secondary">-</span>
                  @endif
                </td>

                <td class="text-end fw-semibold">
                  @if(($row->voucher_type ?? '') === 'percentage')
                    {{ number_format((float) ($row->voucher_value ?? 0), 2, ',', '.') }}%
                  @elseif($row->voucher_value !== null)
                    Rp {{ number_format((float) ($row->voucher_value ?? 0), 0, ',', '.') }}
                  @else
                    -
                  @endif
                </td>

                <td class="text-end">
                  @if($row->voucher_max_discount !== null)
                    Rp {{ number_format((float) ($row->voucher_max_discount ?? 0), 0, ',', '.') }}
                  @else
                    -
                  @endif
                </td>

                <td class="text-end">
                  @if($row->voucher_min_purchase !== null)
                    Rp {{ number_format((float) ($row->voucher_min_purchase ?? 0), 0, ',', '.') }}
                  @else
                    -
                  @endif
                </td>

                <td class="text-nowrap">
                  {{ $row->valid_from ? \Carbon\Carbon::parse($row->valid_from)->format('d M Y H:i') : '-' }}
                </td>

                <td class="text-nowrap">
                  {{ $row->valid_until ? \Carbon\Carbon::parse($row->valid_until)->format('d M Y H:i') : '-' }}
                </td>

                <td class="text-end">
                  {{ $row->usage_limit !== null ? number_format((int) $row->usage_limit, 0, ',', '.') : '-' }}
                </td>

                <td class="text-end">
                  {{ $row->usage_count !== null ? number_format((int) $row->usage_count, 0, ',', '.') : '-' }}
                </td>

                <td>
                  @if((bool) $row->is_active)
                    <span class="badge badge-soft-success">Aktif</span>
                  @else
                    <span class="badge badge-soft-secondary">Tidak Aktif</span>
                  @endif
                </td>

                <td class="text-nowrap">
                  {{ $row->approved_at ? \Carbon\Carbon::parse($row->approved_at)->format('d M Y H:i') : '-' }}
                </td>

                <td class="notes-col">
                  {{ $row->notes ?? '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="19" class="text-center text-muted py-4">
                  Belum ada data voucher reject report.
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
