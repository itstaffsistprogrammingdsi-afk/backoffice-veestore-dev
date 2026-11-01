@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Diskon')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --md-primary: #5b5cf6;
    --md-primary-dark: #4338ca;
    --md-primary-soft: rgba(91, 92, 246, .12);
    --md-success: #22c55e;
    --md-success-soft: rgba(34, 197, 94, .14);
    --md-info: #06b6d4;
    --md-info-soft: rgba(6, 182, 212, .14);
    --md-warning: #f59e0b;
    --md-warning-soft: rgba(245, 158, 11, .16);
    --md-danger: #ef4444;
    --md-danger-soft: rgba(239, 68, 68, .14);
    --md-purple: #8b5cf6;
    --md-dark: #1e293b;
    --md-muted: #64748b;
    --md-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--md-body);
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
    border-color: var(--md-primary);
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
    border-color: var(--md-primary);
    background: linear-gradient(135deg, var(--md-primary), var(--md-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--md-success);
    background: linear-gradient(135deg, var(--md-success), #16a34a);
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
    background: linear-gradient(90deg, var(--md-primary), var(--md-success));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--md-dark);
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
    background: linear-gradient(135deg, var(--md-primary), var(--md-primary-dark));
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

  .diskon-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .diskon-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--md-muted);
  }

  .description-col {
    min-width: 280px;
    max-width: 420px;
    white-space: normal !important;
  }

  .discount-name-col {
    min-width: 240px;
    white-space: normal !important;
  }

  .badge-soft-success {
    background: var(--md-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--md-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--md-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--md-info-soft) !important;
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

  .amount-danger {
    color: #b91c1c;
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
    'total_discount' => 0,
    'total_active_discount' => 0,
    'total_expired_discount' => 0,
    'total_not_started_discount' => 0,
    'total_no_period_discount' => 0,
    'total_inactive_discount' => 0,
    'total_usage_count' => 0,
  ];

  $totalDiscount = (int) ($summary->total_discount ?? 0);
  $totalActiveDiscount = (int) ($summary->total_active_discount ?? 0);
  $totalExpiredDiscount = (int) ($summary->total_expired_discount ?? 0);
  $totalNotStartedDiscount = (int) ($summary->total_not_started_discount ?? 0);
  $totalNoPeriodDiscount = (int) ($summary->total_no_period_discount ?? 0);
  $totalInactiveDiscount = (int) ($summary->total_inactive_discount ?? 0);
  $totalUsageCount = (int) ($summary->total_usage_count ?? 0);

  $hasFilter = request()->hasAny([
    'name',
    'type',
    'is_active',
    'period_status',
    'start_date',
    'end_date',
    'per_page',
  ]);

  $periodBadge = function ($periodStatus) {
    return match($periodStatus) {
      'active' => 'badge-soft-success',
      'expired' => 'badge-soft-danger',
      'not_started' => 'badge-soft-warning',
      'no_period' => 'badge-soft-info',
      default => 'badge-soft-secondary',
    };
  };

  $periodLabel = function ($periodStatus) {
    return match($periodStatus) {
      'active' => 'Active',
      'expired' => 'Expired',
      'not_started' => 'Not Started',
      'no_period' => 'No Period',
      default => 'Unknown',
    };
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Diskon
        </h4>
        <div class="hero-subtitle">
          Monitoring master diskon, periode aktif, expired, usage, limit, minimum purchase, dan relasi promo.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-ticket-percent-outline me-1"></i>
          {{ number_format($totalDiscount, 0, ',', '.') }} Diskon
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-check-circle-outline me-1"></i>
          {{ number_format($totalActiveDiscount, 0, ',', '.') }} Masih Aktif
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-alert-outline me-1"></i>
          {{ number_format($totalExpiredDiscount, 0, ',', '.') }} Expired
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
        <h5 class="mb-1 fw-bold">Filter Monitoring Diskon</h5>
        <small class="text-muted">
          Gunakan filter untuk mencari diskon berdasarkan nama, tipe, status aktif, dan status periode.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterDiskon">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.diskon.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.diskon.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterDiskon">
        <div class="filter-box">
          <form method="GET" action="{{ route('monitoring.diskon.index') }}" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Nama Diskon</label>
              <input type="text"
                     name="name"
                     value="{{ $name }}"
                     class="form-control"
                     placeholder="Nama diskon">
            </div>

            <div class="col-md-2">
              <label class="form-label">Type</label>
              <select name="type" class="form-select js-select2-filter" data-placeholder="Semua Type">
                <option value="">Semua Type</option>
                @foreach($types as $value => $label)
                  <option value="{{ $value }}" @selected((string) $type === (string) $value)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Status Aktif</label>
              <select name="is_active" class="form-select js-select2-filter" data-placeholder="Semua Status">
                <option value="">Semua Status</option>
                <option value="1" @selected((string) $isActive === '1')>Aktif</option>
                <option value="0" @selected((string) $isActive === '0')>Tidak Aktif</option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Status Periode</label>
              <select name="period_status" class="form-select js-select2-filter" data-placeholder="Semua Periode">
                <option value="">Semua Periode</option>
                <option value="active" @selected((string) $periodStatus === 'active')>Active</option>
                <option value="not_started" @selected((string) $periodStatus === 'not_started')>Not Started</option>
                <option value="expired" @selected((string) $periodStatus === 'expired')>Expired</option>
                <option value="no_period" @selected((string) $periodStatus === 'no_period')>No Period</option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Valid From</label>
              <input type="date"
                     name="start_date"
                     value="{{ $startDate }}"
                     class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Valid Until</label>
              <input type="date"
                     name="end_date"
                     value="{{ $endDate }}"
                     class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select js-select2-filter" data-placeholder="Jumlah Data">
                @foreach([10,25,50,100,200] as $size)
                  <option value="{{ $size }}" @selected((int)($perPage ?? 10) === $size)>
                    {{ $size }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-10">
              <label class="form-label">&nbsp;</label>
              <div class="text-muted small pt-2">
                Summary di bawah menghitung jumlah diskon aktif dan expired berdasarkan waktu saat ini.
              </div>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.diskon.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.diskon.index') }}"
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
              <div class="text-muted small">Total Diskon</div>
              <h4 class="mb-1">{{ number_format($totalDiscount, 0, ',', '.') }}</h4>
              <small class="text-muted">Semua data diskon</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-ticket-percent-outline"></i>
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
              <div class="text-muted small">Masih Aktif</div>
              <h4 class="mb-1">{{ number_format($totalActiveDiscount, 0, ',', '.') }}</h4>
              <small class="text-muted">Aktif berdasarkan periode</small>
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
              <div class="text-muted small">Expired</div>
              <h4 class="mb-1">{{ number_format($totalExpiredDiscount, 0, ',', '.') }}</h4>
              <small class="text-muted">Melewati valid until</small>
            </div>
            <div class="summary-icon danger">
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
              <div class="text-muted small">Belum Mulai</div>
              <h4 class="mb-1">{{ number_format($totalNotStartedDiscount, 0, ',', '.') }}</h4>
              <small class="text-muted">Valid from di masa depan</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-timer-sand"></i>
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
              <div class="text-muted small">Tanpa Periode</div>
              <h4 class="mb-1">{{ number_format($totalNoPeriodDiscount, 0, ',', '.') }}</h4>
              <small class="text-muted">Valid from dan until kosong</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-calendar-remove-outline"></i>
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
              <div class="text-muted small">Tidak Aktif</div>
              <h4 class="mb-1">{{ number_format($totalInactiveDiscount, 0, ',', '.') }}</h4>
              <small class="text-muted">Flag is_active false</small>
            </div>
            <div class="summary-icon purple">
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
              <div class="text-muted small">Total Usage</div>
              <h4 class="mb-1">{{ number_format($totalUsageCount, 0, ',', '.') }}</h4>
              <small class="text-muted">Akumulasi usage count</small>
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
        <h5 class="mb-1 fw-bold">Data Monitoring Diskon</h5>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap diskon-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Diskon</th>
              <th>Promo</th>
              <th>Type</th>
              <th class="text-end">Value</th>
              <th class="text-end">Max Discount</th>
              <th class="text-end">Min Purchase</th>
              <th>Status Periode</th>
              <th>Valid From</th>
              <th>Valid Until</th>
              <th class="text-end">Usage Limit</th>
              <th class="text-end">Usage Count</th>
              <th>Aktif</th>
              <th class="text-end">Priority</th>
              <th>Combinable</th>
              <th>Deskripsi</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              @php
                $periodStatusValue = $row->period_status_label ?? 'unknown';
              @endphp

              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td class="discount-name-col">
                  <div class="fw-semibold">{{ $row->name ?? '-' }}</div>
                </td>

                <td>{{ $row->promo_name ?? '-' }}</td>

                <td>
                  <span class="badge badge-soft-info">
                    {{ ucfirst($row->type ?? '-') }}
                  </span>
                </td>

                <td class="text-end fw-semibold">
                  @if(($row->type ?? '') === 'percentage')
                    {{ number_format((float) ($row->value ?? 0), 2, ',', '.') }}%
                  @else
                    Rp {{ number_format((float) ($row->value ?? 0), 0, ',', '.') }}
                  @endif
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->max_discount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->min_purchase ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  <span class="badge {{ $periodBadge($periodStatusValue) }}">
                    {{ $periodLabel($periodStatusValue) }}
                  </span>
                </td>

                <td class="text-nowrap">
                  {{ $row->valid_from ? \Carbon\Carbon::parse($row->valid_from)->format('d M Y H:i') : '-' }}
                </td>

                <td class="text-nowrap">
                  {{ $row->valid_until ? \Carbon\Carbon::parse($row->valid_until)->format('d M Y H:i') : '-' }}
                </td>

                <td class="text-end">
                  {{ number_format((int) ($row->usage_limit ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  {{ number_format((int) ($row->usage_count ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  @if((bool) $row->is_active)
                    <span class="badge badge-soft-success">Aktif</span>
                  @else
                    <span class="badge badge-soft-secondary">Tidak Aktif</span>
                  @endif
                </td>

                <td class="text-end">
                  {{ number_format((int) ($row->priority ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  @if((bool) $row->combinable)
                    <span class="badge badge-soft-info">Ya</span>
                  @else
                    <span class="badge badge-soft-secondary">Tidak</span>
                  @endif
                </td>

                <td class="description-col">
                  {{ $row->description ?? '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="16" class="text-center text-muted py-4">
                  Tidak ada data diskon.
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
