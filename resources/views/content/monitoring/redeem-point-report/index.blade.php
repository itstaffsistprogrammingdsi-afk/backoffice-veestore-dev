@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Redeem Point Report')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --rp-primary: #5b5cf6;
    --rp-primary-dark: #4338ca;
    --rp-primary-soft: rgba(91, 92, 246, .12);
    --rp-success: #22c55e;
    --rp-success-soft: rgba(34, 197, 94, .14);
    --rp-info: #06b6d4;
    --rp-info-soft: rgba(6, 182, 212, .14);
    --rp-warning: #f59e0b;
    --rp-warning-soft: rgba(245, 158, 11, .16);
    --rp-danger: #ef4444;
    --rp-danger-soft: rgba(239, 68, 68, .14);
    --rp-purple: #8b5cf6;
    --rp-dark: #1e293b;
    --rp-muted: #64748b;
    --rp-border: #e5e7eb;
    --rp-body: #f6f7fb;
    --rp-card: #ffffff;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--rp-body);
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
    border-color: var(--rp-primary);
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
    border: 0;
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

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: var(--rp-card);
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--rp-primary), var(--rp-success));
  }

  .summary-card::after {
    content: "";
    position: absolute;
    width: 90px;
    height: 90px;
    right: -36px;
    bottom: -36px;
    border-radius: 50%;
    background: var(--rp-primary-soft);
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--rp-dark);
    font-weight: 800;
    letter-spacing: -.02em;
  }

  .summary-card .text-muted {
    color: var(--rp-muted) !important;
  }

  .summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--rp-primary), var(--rp-primary-dark));
    color: #fff;
    font-size: 25px;
    flex-shrink: 0;
    box-shadow: 0 .65rem 1.25rem rgba(91, 92, 246, .22);
  }

  .summary-icon.success {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    box-shadow: 0 .65rem 1.25rem rgba(34, 197, 94, .22);
  }

  .summary-icon.info {
    background: linear-gradient(135deg, #06b6d4, #0284c7);
    box-shadow: 0 .65rem 1.25rem rgba(6, 182, 212, .22);
  }

  .summary-icon.warning {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    box-shadow: 0 .65rem 1.25rem rgba(245, 158, 11, .22);
  }

  .summary-icon.danger {
    background: linear-gradient(135deg, #ec4899, #ef4444);
    box-shadow: 0 .65rem 1.25rem rgba(236, 72, 153, .22);
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
    border-color: var(--rp-primary);
    background: linear-gradient(135deg, var(--rp-primary), var(--rp-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-primary:hover {
    border-color: var(--rp-primary-dark);
    background: linear-gradient(135deg, var(--rp-primary-dark), var(--rp-primary));
  }

  .btn-success {
    border-color: var(--rp-success);
    background: linear-gradient(135deg, var(--rp-success), #16a34a);
    box-shadow: 0 .45rem 1rem rgba(34, 197, 94, .18);
  }

  .btn-outline-secondary {
    background: #fff;
    border-color: #d7deea;
    color: #475569;
  }

  .btn-outline-secondary:hover {
    background: #f1f5f9;
    color: #334155;
  }

  .chart-card {
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: #fff;
    overflow: hidden;
  }

  .chart-card .card-header h5 {
    color: var(--rp-dark);
    font-weight: 800;
  }

  .chart-wrap {
    position: relative;
    min-height: 330px;
  }

  .mini-list {
    max-height: 420px;
    overflow: auto;
    padding-top: .25rem;
  }

  .mini-list::-webkit-scrollbar {
    width: 6px;
  }

  .mini-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
  }

  .mini-list-item {
    position: relative;
    border: 1px solid transparent;
    border-radius: .95rem;
    padding: .85rem .9rem;
    margin-bottom: .65rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    transition: .18s ease;
  }

  .mini-list-item:hover {
    border-color: #dbeafe;
    transform: translateX(2px);
    box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .06);
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

  .redeem-table thead th,
  .detail-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .redeem-table tbody td,
  .detail-table tbody td {
    color: #475569;
  }

  .redeem-table tbody tr,
  .detail-table tbody tr {
    transition: .16s ease;
  }

  .redeem-table tbody tr:hover,
  .detail-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .customer-col,
  .desc-col {
    min-width: 250px;
    white-space: normal !important;
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--rp-muted);
  }

  .badge-soft-success {
    background: var(--rp-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-primary {
    background: var(--rp-primary-soft) !important;
    color: var(--rp-primary-dark) !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--rp-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .modal-content {
    border: 0;
    border-radius: 1.15rem;
    overflow: hidden;
  }

  .modal-header {
    background: linear-gradient(135deg, #4f46e5, #22c55e);
    color: #fff;
    border-bottom: 0;
  }

  .modal-header .text-muted {
    color: rgba(255,255,255,.82) !important;
  }

  .modal-header .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
  }

  @media (max-width: 767.98px) {
    .dashboard-hero {
      border-radius: 1rem;
    }

    .chart-wrap {
      min-height: 280px;
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
    'total_customer' => 0,
    'total_mutations' => 0,
    'total_redeem_points' => 0,
    'total_redeem_today' => 0,
    'total_redeem_this_month' => 0,
    'latest_redeem_at' => null,
  ];

  $exportQuery = request()->query();

  $hasFilter = request()->hasAny([
    'q',
    'customer_id',
    'from',
    'to',
    'per_page',
  ]);
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Redeem Point Report
        </h4>
        <div class="hero-subtitle">
          Dashboard monitoring customer yang melakukan redeem point berdasarkan periode, customer, referral, ref, dan deskripsi mutasi.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <span class="live-dot me-1"></span>
          Live Data
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-calendar-range-outline me-1"></i>
          {{ \Carbon\Carbon::parse($from)->format('d M Y') }} - {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
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
        <h5 class="mb-1 fw-bold">Filter Redeem Point Report</h5>
        <small class="text-muted">
          Gunakan filter untuk mencari customer, nomor WA, email, referral, ref transaksi, atau deskripsi redeem.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#filterRedeemPoint">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.redeem-point-report.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.redeem-point-report.export', $exportQuery) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="filterRedeemPoint">
        <div class="filter-box">
          <form method="GET"
                action="{{ route('monitoring.redeem-point-report.index') }}"
                class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Dari Tanggal</label>
              <input type="date"
                     name="from"
                     class="form-control"
                     value="{{ $from }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Sampai Tanggal</label>
              <input type="date"
                     name="to"
                     class="form-control"
                     value="{{ $to }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Customer</label>
              <select name="customer_id"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Customer">
                <option value=""></option>
                @foreach($customers as $customer)
                  <option value="{{ $customer->id }}" @selected((string) $customerId === (string) $customer->id)>
                    {{ $customer->name }}
                    @if($customer->wa_number)
                      - {{ $customer->wa_number }}
                    @elseif($customer->email)
                      - {{ $customer->email }}
                    @endif
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select">
                @foreach([10, 25, 50, 100, 200] as $page)
                  <option value="{{ $page }}" @selected((int)($perPage ?? 10) === $page)>
                    {{ $page }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-12">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q ?? '' }}"
                     placeholder="Cari nama customer / email / WA / referral code / ref / description / mutation ID">
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.redeem-point-report.export', $exportQuery) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.redeem-point-report.index') }}"
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
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Customer Redeem</div>
              <h4 class="mb-0">{{ number_format((int) $summary->total_customer, 0, ',', '.') }}</h4>
              <small class="text-muted">Customer sesuai filter</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-account-group-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Mutasi Redeem</div>
              <h4 class="mb-0">{{ number_format((int) $summary->total_mutations, 0, ',', '.') }}</h4>
              <small class="text-muted">Jumlah transaksi redeem</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-swap-horizontal-bold"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Redeem Points</div>
              <h4 class="mb-0">{{ number_format((float) $summary->total_redeem_points, 0, ',', '.') }}</h4>
              <small class="text-muted">Akumulasi point redeem</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-star-circle-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Redeem Hari Ini</div>
              <h4 class="mb-0">{{ number_format((float) $summary->total_redeem_today, 0, ',', '.') }}</h4>
              <small class="text-muted">Point redeem hari ini</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-calendar-today-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Redeem Bulan Ini</div>
              <h4 class="mb-0">{{ number_format((float) $summary->total_redeem_this_month, 0, ',', '.') }}</h4>
              <small class="text-muted">Periode bulan berjalan</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-calendar-month-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Redeem Terakhir</div>
              <h4 class="mb-0" style="font-size: 1.15rem;">
                {{ $summary->latest_redeem_at ? \Carbon\Carbon::parse($summary->latest_redeem_at)->format('d M Y') : '-' }}
              </h4>
              <small class="text-muted">
                {{ $summary->latest_redeem_at ? \Carbon\Carbon::parse($summary->latest_redeem_at)->format('H:i') : 'Belum ada data' }}
              </small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-clock-check-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART + LATEST --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Trend Redeem Point 14 Hari Terakhir</h5>
          <small class="text-muted">Total point yang diredeem per hari</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="dailyRedeemChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Top Customer Redeem</h5>
          <small class="text-muted">Customer dengan redeem point terbesar</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="topCustomerChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-12">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Redeem Terbaru</h5>
          <small class="text-muted">10 mutasi redeem point terakhir sesuai filter</small>
        </div>
        <div class="card-body mini-list">
          <div class="row g-3">
            @forelse($latestRows as $latest)
              <div class="col-md-6 col-xl-4">
                <div class="mini-list-item h-100 mb-0">
                  <div class="d-flex justify-content-between gap-2">
                    <div>
                      <div class="fw-semibold">{{ $latest->customer_name ?? '-' }}</div>
                      <div class="text-muted small">
                        {{ $latest->customer_wa_number ?? $latest->customer_email ?? '-' }}
                      </div>
                    </div>
                    <span class="badge-soft-success">
                      {{ number_format((float) ($latest->redeem_points ?? 0), 0, ',', '.') }}
                    </span>
                  </div>

                  <div class="small mt-2">
                    Ref: <b>{{ $latest->ref ?? '-' }}</b>
                  </div>

                  <div class="text-muted small">
                    {{ $latest->description ?? '-' }}
                  </div>

                  <div class="text-muted small mt-1">
                    {{ $latest->created_at ? \Carbon\Carbon::parse($latest->created_at)->format('d-m-Y H:i') : '-' }}
                  </div>
                </div>
              </div>
            @empty
              <div class="col-12">
                <div class="text-muted text-center py-4">
                  Belum ada data redeem point.
                </div>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Laporan Redeem Point</h5>
        <small class="text-muted">
          Total Customer:
          <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
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
        <table class="table table-bordered table-striped table-sm table-nowrap mb-0 redeem-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Customer</th>
              <th>Current Balance</th>
              <th>Total Mutation</th>
              <th>Total Redeem Points</th>
              <th>Last Redeem At</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td class="customer-col">
                  <div class="fw-semibold">{{ $row->customer_name ?? '-' }}</div>
                  <div class="text-muted small">
                    {{ $row->customer_email ?? '-' }}
                    @if($row->customer_wa_number)
                      | {{ $row->customer_wa_number }}
                    @endif
                    @if($row->customer_referral_code)
                      | Ref: {{ $row->customer_referral_code }}
                    @endif
                  </div>
                </td>

                <td>
                  <span class="badge-soft-primary">
                    {{ number_format((float) ($row->current_balance ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td>{{ number_format((float) ($row->total_mutations ?? 0), 0, ',', '.') }}</td>

                <td>
                  <span class="badge-soft-success">
                    {{ number_format((float) ($row->total_redeem_points ?? 0), 0, ',', '.') }}
                  </span>
                </td>

                <td>
                  {{ $row->last_redeem_at ? \Carbon\Carbon::parse($row->last_redeem_at)->format('d M Y H:i') : '-' }}
                </td>

                <td class="text-center">
                  <button type="button"
                          class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#detailModal-{{ $row->customer_id }}">
                    <i class="mdi mdi-eye-outline me-1"></i> Detail
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  Belum ada data redeem point sesuai filter.
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

  {{-- MODAL DETAIL --}}
  @foreach($rows as $row)
    @php
      $mutations = $detailMutations->get($row->customer_id, collect());
    @endphp

    <div class="modal fade" id="detailModal-{{ $row->customer_id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title mb-1 text-white">Detail Mutasi Redeem Point</h5>
              <div class="small text-muted">
                {{ $row->customer_name ?? '-' }}
                @if($row->customer_email)
                  | {{ $row->customer_email }}
                @endif
                @if($row->customer_wa_number)
                  | {{ $row->customer_wa_number }}
                @endif
              </div>
            </div>

            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3 mb-4">
              <div class="col-md-3">
                <label class="form-label">Customer</label>
                <input type="text"
                       class="form-control"
                       value="{{ $row->customer_name ?? '-' }}"
                       readonly>
              </div>

              <div class="col-md-3">
                <label class="form-label">Current Balance</label>
                <input type="text"
                       class="form-control"
                       value="{{ number_format((float) ($row->current_balance ?? 0), 0, ',', '.') }}"
                       readonly>
              </div>

              <div class="col-md-3">
                <label class="form-label">Total Mutation</label>
                <input type="text"
                       class="form-control"
                       value="{{ number_format((float) ($row->total_mutations ?? 0), 0, ',', '.') }}"
                       readonly>
              </div>

              <div class="col-md-3">
                <label class="form-label">Total Redeem Points</label>
                <input type="text"
                       class="form-control"
                       value="{{ number_format((float) ($row->total_redeem_points ?? 0), 0, ',', '.') }}"
                       readonly>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm mb-0 table-nowrap detail-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Mutation ID</th>
                    <th>Type</th>
                    <th>Mutation Type</th>
                    <th>Redeem Points</th>
                    <th>Points</th>
                    <th>Used Points</th>
                    <th>Balance After</th>
                    <th>Ref</th>
                    <th>Ref ID</th>
                    <th>Expired At</th>
                    <th>Description</th>
                  </tr>
                </thead>

                <tbody>
                  @forelse($mutations as $index => $mutation)
                    <tr>
                      <td>{{ $index + 1 }}</td>

                      <td>
                        {{ $mutation->created_at ? \Carbon\Carbon::parse($mutation->created_at)->format('d M Y H:i') : '-' }}
                      </td>

                      <td>{{ $mutation->id ?? '-' }}</td>

                      <td>
                        <span class="badge-soft-success">
                          {{ $mutation->type ?? '-' }}
                        </span>
                      </td>

                      <td>{{ $mutation->mutation_type ?? '-' }}</td>

                      <td>
                        <span class="badge-soft-success">
                          {{ number_format((float) ($mutation->redeem_points ?? 0), 0, ',', '.') }}
                        </span>
                      </td>

                      <td>{{ number_format((float) ($mutation->points ?? 0), 0, ',', '.') }}</td>

                      <td>{{ number_format((float) ($mutation->used_points ?? 0), 0, ',', '.') }}</td>

                      <td>
                        <span class="badge-soft-primary">
                          {{ number_format((float) ($mutation->balance_after ?? 0), 0, ',', '.') }}
                        </span>
                      </td>

                      <td>{{ $mutation->ref ?? '-' }}</td>

                      <td>{{ $mutation->ref_id ?? '-' }}</td>

                      <td>
                        {{ $mutation->expired_at ? \Carbon\Carbon::parse($mutation->expired_at)->format('d M Y H:i') : '-' }}
                      </td>

                      <td class="desc-col">{{ $mutation->description ?? '-' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="13" class="text-center text-muted py-4">
                        Belum ada data mutasi.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
              <i class="mdi mdi-close me-1"></i> Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  @endforeach

</div>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
  'use strict';

  const dailyChartRows = @json($dailyChartRows);
  const topCustomerRows = @json($topCustomerRows);

  const chartColors = [
    '#5b5cf6',
    '#06b6d4',
    '#22c55e',
    '#f59e0b',
    '#ec4899',
    '#8b5cf6',
    '#ef4444',
    '#14b8a6',
    '#f97316',
    '#64748b'
  ];

  function numberId(value) {
    return Number(value || 0).toLocaleString('id-ID');
  }

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

  function renderLineChart(canvasId, rows) {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label);
    const data = rows.map(item => Number(item.total || 0));

    const gradient = el.getContext('2d').createLinearGradient(0, 0, 0, 320);
    gradient.addColorStop(0, 'rgba(91, 92, 246, .28)');
    gradient.addColorStop(1, 'rgba(91, 92, 246, 0)');

    new Chart(el, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Redeem Points',
          data: data,
          borderColor: '#5b5cf6',
          backgroundColor: gradient,
          borderWidth: 3,
          tension: .38,
          fill: true,
          pointRadius: 4,
          pointHoverRadius: 7,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#5b5cf6',
          pointBorderWidth: 3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
          padding: 6
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return `Redeem Points: ${numberId(context.raw || 0)}`;
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: '#64748b',
              font: {
                size: 11,
                weight: '600'
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: '#64748b',
              font: {
                size: 11,
                weight: '600'
              }
            }
          }
        }
      }
    });
  }

  function renderHorizontalBarChart(canvasId, rows, label = 'Redeem Points') {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label);
    const data = rows.map(item => Number(item.total || 0));

    new Chart(el, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: label,
          data: data,
          backgroundColor: chartColors,
          borderColor: chartColors,
          borderWidth: 1,
          borderRadius: 10,
          borderSkipped: false,
          barThickness: 18,
          maxBarThickness: 22
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        layout: {
          padding: 4
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return `${label}: ${numberId(context.raw || 0)}`;
              }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: '#64748b',
              font: {
                size: 11,
                weight: '600'
              }
            }
          },
          y: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: '#475569',
              font: {
                size: 11,
                weight: '600'
              }
            }
          }
        }
      }
    });
  }

  $(document).ready(function () {
    initSelect2();
    renderLineChart('dailyRedeemChart', dailyChartRows);
    renderHorizontalBarChart('topCustomerChart', topCustomerRows, 'Redeem Points');
  });
})(window.jQuery);
</script>
@endpush