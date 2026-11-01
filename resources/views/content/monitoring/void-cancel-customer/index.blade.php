@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Void & Cancel Customer')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --vc-primary: #5b5cf6;
    --vc-primary-dark: #4338ca;
    --vc-success: #22c55e;
    --vc-info: #06b6d4;
    --vc-warning: #f59e0b;
    --vc-danger: #ef4444;
    --vc-purple: #8b5cf6;
    --vc-dark: #1e293b;
    --vc-muted: #64748b;
    --vc-body: #f6f7fb;
    --vc-border: #e5e7eb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(239, 68, 68, .10), transparent 28%),
      var(--vc-body);
  }

  .select2-container {
    width: 100% !important;
  }

  .select2-container--bootstrap-5 .select2-selection {
    min-height: 40px;
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .form-control,
  .form-select {
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .form-control:focus,
  .form-select:focus,
  .select2-container--bootstrap-5.select2-container--focus .select2-selection {
    border-color: var(--vc-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .void-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
      linear-gradient(135deg, #4338ca 0%, #ef4444 58%, #f59e0b 120%);
    color: #fff;
    box-shadow: 0 1.25rem 2.5rem rgba(67, 56, 202, .22);
  }

  .void-hero::before {
    content: "";
    position: absolute;
    width: 280px;
    height: 280px;
    right: -110px;
    top: -120px;
    border-radius: 50%;
    background: rgba(255,255,255,.18);
  }

  .void-hero::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    right: 130px;
    bottom: -120px;
    border-radius: 50%;
    background: rgba(255,255,255,.14);
  }

  .void-hero-content {
    position: relative;
    z-index: 2;
  }

  .hero-badge {
    border-radius: 999px;
    padding: .55rem .85rem;
    background: rgba(255,255,255,.18);
    color: #fff;
    border: 1px solid rgba(255,255,255,.22);
    backdrop-filter: blur(8px);
    font-size: .78rem;
    font-weight: 700;
  }

  .live-dot {
    display: inline-block;
    width: .55rem;
    height: .55rem;
    border-radius: 999px;
    background: #22c55e;
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

  .void-filter-box {
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
    font-weight: 700;
  }

  .btn-primary {
    border-color: var(--vc-primary);
    background: linear-gradient(135deg, var(--vc-primary), var(--vc-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--vc-success);
    background: linear-gradient(135deg, var(--vc-success), #16a34a);
    box-shadow: 0 .45rem 1rem rgba(34, 197, 94, .18);
  }

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    background: #fff;
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--vc-primary), var(--vc-danger));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-label {
    color: var(--vc-muted);
    font-size: .78rem;
    margin-bottom: .35rem;
  }

  .summary-value {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 900;
    color: var(--vc-dark);
    letter-spacing: -.02em;
  }

  .summary-help {
    display: block;
    margin-top: .25rem;
    font-size: .78rem;
    color: var(--vc-muted);
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
    background: linear-gradient(135deg, var(--vc-primary), var(--vc-primary-dark));
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

  .summary-icon.secondary {
    background: linear-gradient(135deg, #64748b, #475569);
  }

  .chart-card {
    height: 100%;
  }

  .chart-wrapper {
    position: relative;
    min-height: 330px;
    width: 100%;
  }

  .chart-wrapper-sm {
    position: relative;
    min-height: 290px;
    width: 100%;
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

  .void-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .76rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 900;
    border-bottom: 1px solid #dbe1ea;
  }

  .void-table tbody td {
    color: #475569;
  }

  .void-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--vc-muted);
  }

  .reason-col {
    min-width: 260px;
    white-space: normal !important;
  }

  .detail-box {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    height: 100%;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
  }

  .detail-box h6 {
    margin-bottom: 1rem;
    font-weight: 800;
    color: var(--vc-dark);
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: .45rem 0;
    border-bottom: 1px dashed #e2e8f0;
  }

  .detail-row:last-child {
    border-bottom: 0;
    padding-bottom: 0;
  }

  .detail-row .label {
    color: var(--vc-muted);
    min-width: 150px;
    font-weight: 600;
  }

  .detail-row .value {
    color: var(--vc-dark);
    font-weight: 700;
    text-align: right;
    word-break: break-word;
  }

  .modal-xl-custom {
    max-width: 1320px;
  }

  .badge-status {
    border-radius: 999px;
    padding: .42rem .68rem;
    font-weight: 800;
    font-size: .74rem;
  }

  .bg-label-success {
    background: rgba(34, 197, 94, .14) !important;
    color: #15803d !important;
  }

  .bg-label-danger {
    background: rgba(239, 68, 68, .14) !important;
    color: #b91c1c !important;
  }

  .bg-label-warning {
    background: rgba(245, 158, 11, .16) !important;
    color: #b45309 !important;
  }

  .bg-label-secondary {
    background: #f1f5f9 !important;
    color: #475569 !important;
  }

  .bg-label-primary {
    background: rgba(91, 92, 246, .12) !important;
    color: #4338ca !important;
  }

  @media (max-width: 767.98px) {
    .void-hero {
      border-radius: 1rem;
    }

    .chart-wrapper,
    .chart-wrapper-sm {
      min-height: 270px;
    }

    .card-body {
      padding: 1rem;
    }

    .modal-xl-custom {
      max-width: 96%;
    }
  }
</style>
@endpush

@php
  $totalVoidRequest = (float) ($summary->total_void_request ?? 0);
  $totalApproved = (float) ($summary->total_approved ?? 0);
  $totalRejected = (float) ($summary->total_rejected ?? 0);
  $totalRequested = (float) ($summary->total_requested ?? 0);
  $totalCancelled = (float) ($summary->total_cancelled ?? 0);
  $totalVoidedAmount = (float) ($summary->total_voided_amount ?? 0);
  $totalItemsRestored = (float) ($summary->total_items_restored ?? 0);

  $voidDailyLabels = $voidDailyLabels ?? collect();
  $voidDailyCounts = $voidDailyCounts ?? collect();
  $voidDailyAmounts = $voidDailyAmounts ?? collect();

  $voidStatusLabels = $voidStatusLabels ?? collect();
  $voidStatusCounts = $voidStatusCounts ?? collect();

  $voidStoreLabels = $voidStoreLabels ?? collect();
  $voidStoreCounts = $voidStoreCounts ?? collect();
  $voidStoreAmounts = $voidStoreAmounts ?? collect();

  $voidCashierLabels = $voidCashierLabels ?? collect();
  $voidCashierCounts = $voidCashierCounts ?? collect();
  $voidCashierAmounts = $voidCashierAmounts ?? collect();

  $hasFilter = request()->hasAny([
    'from',
    'to',
    'store_id',
    'status',
    'requested_by',
    'authorized_by',
    'q',
  ]);
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <div class="void-hero p-4 mb-4">
    <div class="void-hero-content d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1 text-white">
          <span class="fw-light">Monitoring /</span> Void & Cancel Customer
        </h4>
        <div style="color: rgba(255,255,255,.84)">
          Dashboard Holding untuk melihat pengajuan void, approval, reject, cancel, nominal void, top store, dan top kasir.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <span class="live-dot me-1"></span>
          Live Data
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-calendar-range me-1"></i>
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
        <h5 class="mb-1 fw-bold">Filter Dashboard Void & Cancel</h5>
        <small class="text-muted">
          Filter dapat dibuka/tutup untuk melihat data berdasarkan periode, store, status, kasir, atau otorisasi.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterVoidCancelCustomer"
                aria-expanded="{{ $hasFilter ? 'true' : 'false' }}"
                aria-controls="advancedFilterVoidCancelCustomer">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.void-cancel-customer.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel me-1"></i> Export Excel
        </a>

        <a href="{{ route('monitoring.void-cancel-customer.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}"
           id="advancedFilterVoidCancelCustomer">
        <div class="void-filter-box">
          <form method="GET"
                action="{{ route('monitoring.void-cancel-customer.index') }}"
                class="row g-3 align-items-end">

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Dari</label>
              <input type="date" name="from" class="form-control" value="{{ $from }}">
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Sampai</label>
              <input type="date" name="to" class="form-control" value="{{ $to }}">
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Store</label>
              <select name="store_id"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Store">
                <option value="">Semua Store</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" {{ (string) $storeId === (string) $store->id ? 'selected' : '' }}>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Status</label>
              <select name="status"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Status">
                <option value="">Semua Status</option>
                @foreach($statuses as $item)
                  <option value="{{ $item }}" {{ (string) $status === (string) $item ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $item)) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Kasir Peminta</label>
              <select name="requested_by"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Kasir">
                <option value="">Semua Kasir</option>
                @foreach($users as $user)
                  <option value="{{ $user->id }}" {{ (string) $requestedBy === (string) $user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Otorisasi Oleh</label>
              <select name="authorized_by"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua User">
                <option value="">Semua User</option>
                @foreach($users as $user)
                  <option value="{{ $user->id }}" {{ (string) $authorizedBy === (string) $user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-12 col-md-6">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q ?? '' }}"
                     placeholder="Cari transaction / visit / store / nominal / reason">
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.void-cancel-customer.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.void-cancel-customer.index') }}"
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
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Pengajuan Void</div>
            <h5 class="summary-value">{{ number_format($totalVoidRequest, 0, ',', '.') }}</h5>
            <span class="summary-help">Semua request void</span>
          </div>
          <div class="summary-icon">
            <i class="mdi mdi-file-document-alert-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Void Approved</div>
            <h5 class="summary-value">{{ number_format($totalApproved, 0, ',', '.') }}</h5>
            <span class="summary-help">Approved / Authorized</span>
          </div>
          <div class="summary-icon success">
            <i class="mdi mdi-check-circle-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Void Requested</div>
            <h5 class="summary-value">{{ number_format($totalRequested, 0, ',', '.') }}</h5>
            <span class="summary-help">Menunggu approval</span>
          </div>
          <div class="summary-icon warning">
            <i class="mdi mdi-clock-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Void Rejected</div>
            <h5 class="summary-value">{{ number_format($totalRejected, 0, ',', '.') }}</h5>
            <span class="summary-help">Void ditolak</span>
          </div>
          <div class="summary-icon danger">
            <i class="mdi mdi-close-circle-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-4">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Cancel</div>
            <h5 class="summary-value">{{ number_format($totalCancelled, 0, ',', '.') }}</h5>
            <span class="summary-help">Transaksi cancel</span>
          </div>
          <div class="summary-icon secondary">
            <i class="mdi mdi-cancel"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-4">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Nominal Void</div>
            <h5 class="summary-value">Rp {{ number_format($totalVoidedAmount, 0, ',', '.') }}</h5>
            <span class="summary-help">Total nilai transaksi void</span>
          </div>
          <div class="summary-icon info">
            <i class="mdi mdi-cash-remove"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-4">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Item Void</div>
            <h5 class="summary-value">{{ number_format($totalItemsRestored, 0, ',', '.') }}</h5>
            <span class="summary-help">Item yang direstore</span>
          </div>
          <div class="summary-icon">
            <i class="mdi mdi-package-variant-closed-remove"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Grafik Pengajuan Void per Tanggal</h5>
          <small class="text-muted">Jumlah pengajuan void dan nominal void per hari.</small>
        </div>
        <div class="card-body">
          @if(count($voidDailyLabels))
            <div class="chart-wrapper">
              <canvas id="voidDailyChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data grafik harian.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Grafik Status Void</h5>
          <small class="text-muted">Komposisi status pengajuan void.</small>
        </div>
        <div class="card-body">
          @if(count($voidStatusLabels))
            <div class="chart-wrapper">
              <canvas id="voidStatusChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data status.</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-6">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Top Store Pengajuan Void</h5>
          <small class="text-muted">Store dengan jumlah pengajuan void terbanyak.</small>
        </div>
        <div class="card-body">
          @if(count($voidStoreLabels))
            <div class="chart-wrapper-sm">
              <canvas id="voidStoreChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data store.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-6">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Top Kasir Pengajuan Void</h5>
          <small class="text-muted">Kasir yang paling sering mengajukan void.</small>
        </div>
        <div class="card-body">
          @if(count($voidCashierLabels))
            <div class="chart-wrapper-sm">
              <canvas id="voidCashierChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data kasir.</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Laporan Void & Cancel Customer</h5>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap void-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Waktu Request</th>
              <th>Waktu Approval</th>
              <th>Store</th>
              <th>Status</th>
              <th>Kasir</th>
              <th>Otorisasi</th>
              <th class="text-center">Qty</th>
              <th class="text-end">Nominal Void</th>
              <th>Reason</th>
              <th class="text-center">Detail</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              @php
                $statusClass = match(strtolower((string) $row->status)) {
                  'approved', 'authorized' => 'bg-label-success',
                  'rejected' => 'bg-label-danger',
                  'requested' => 'bg-label-warning',
                  'cancelled' => 'bg-label-secondary',
                  default => 'bg-label-primary',
                };
              @endphp

              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td>
                  {{ $row->requested_at ? \Carbon\Carbon::parse($row->requested_at)->format('d M Y H:i') : '-' }}
                </td>

                <td>
                  @if(in_array(strtolower((string) $row->status), ['approved', 'authorized'], true))
                    {{ $row->authorized_at ? \Carbon\Carbon::parse($row->authorized_at)->format('d M Y H:i') : '-' }}
                  @else
                    -
                  @endif
                </td>

                <td>{{ $row->store_name ?? '-' }}</td>

                <td>
                  <span class="badge badge-status {{ $statusClass }}">
                    {{ $row->status_label ?? '-' }}
                  </span>
                </td>

                <td>{{ $row->cashier_name ?? '-' }}</td>
                <td>{{ $row->authorized_by_name ?? '-' }}</td>

                <td class="text-center">
                  {{ number_format((float) ($row->items_restored_count ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end fw-bold">
                  Rp {{ number_format((float) ($row->voided_amount ?? 0), 0, ',', '.') }}
                </td>

                <td class="reason-col">{{ $row->reason ?? '-' }}</td>

                <td class="text-center">
                  <button type="button"
                          class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#detailModal-{{ $row->id }}">
                    <i class="mdi mdi-eye-outline me-1"></i> Detail
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center text-muted py-4">Belum ada data</td>
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
      $items = $detailItems->get($row->id, collect());
    @endphp

    <div class="modal fade" id="detailModal-{{ $row->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-xl-custom modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title mb-1">Detail Void Customer</h5>
              <div class="small text-muted">
                {{ $row->store_name ?? '-' }} | {{ $row->status_label ?? '-' }}
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <div class="detail-box">
                  <h6>Ringkasan Void</h6>

                  <div class="detail-row">
                    <div class="label">Transaction Number</div>
                    <div class="value">{{ $row->transaction_number ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Visit Code</div>
                    <div class="value">{{ $row->visit_code ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Waktu Request</div>
                    <div class="value">
                      {{ $row->requested_at ? \Carbon\Carbon::parse($row->requested_at)->format('d M Y H:i') : '-' }}
                    </div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Kasir Peminta</div>
                    <div class="value">{{ $row->cashier_name ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Otorisasi Oleh</div>
                    <div class="value">{{ $row->authorized_by_name ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Waktu Approval</div>
                    <div class="value">
                      @if(in_array(strtolower((string) $row->status), ['approved', 'authorized'], true))
                        {{ $row->authorized_at ? \Carbon\Carbon::parse($row->authorized_at)->format('d M Y H:i') : '-' }}
                      @else
                        -
                      @endif
                    </div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Status</div>
                    <div class="value">{{ $row->status_label ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Jumlah Item</div>
                    <div class="value">{{ number_format((float) ($row->items_restored_count ?? 0), 0, ',', '.') }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Nominal Void</div>
                    <div class="value">Rp {{ number_format((float) ($row->voided_amount ?? 0), 0, ',', '.') }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Points Reversed</div>
                    <div class="value">{{ number_format((float) ($row->points_reversed ?? 0), 0, ',', '.') }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Reason</div>
                    <div class="value">{{ $row->reason ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Rejection Reason</div>
                    <div class="value">{{ $row->rejection_reason ?? '-' }}</div>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="detail-box">
                  <h6>Informasi Payment & Visit</h6>

                  <div class="detail-row">
                    <div class="label">Payment ID</div>
                    <div class="value">{{ $row->payment_id ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Payment Status</div>
                    <div class="value">{{ $row->payment_status ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Payment Completed</div>
                    <div class="value">
                      {{ $row->payment_completed_at ? \Carbon\Carbon::parse($row->payment_completed_at)->format('d M Y H:i') : '-' }}
                    </div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Total Amount</div>
                    <div class="value">Rp {{ number_format((float) ($row->payment_total_amount ?? 0), 0, ',', '.') }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Paid Amount</div>
                    <div class="value">Rp {{ number_format((float) ($row->payment_paid_amount ?? 0), 0, ',', '.') }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Change Amount</div>
                    <div class="value">Rp {{ number_format((float) ($row->payment_change_amount ?? 0), 0, ',', '.') }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Visit Type</div>
                    <div class="value">{{ $row->visit_type ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Visit Status</div>
                    <div class="value">{{ $row->visit_status ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Visit Payment Status</div>
                    <div class="value">{{ $row->visit_payment_status ?? '-' }}</div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Check In</div>
                    <div class="value">
                      {{ $row->check_in_at ? \Carbon\Carbon::parse($row->check_in_at)->format('d M Y H:i') : '-' }}
                    </div>
                  </div>

                  <div class="detail-row">
                    <div class="label">Check Out</div>
                    <div class="value">
                      {{ $row->check_out_at ? \Carbon\Carbon::parse($row->check_out_at)->format('d M Y H:i') : '-' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="detail-box">
              <h6>Item Detail</h6>

              <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 60px;">No</th>
                      <th>Item ID</th>
                      <th>Kode Produk</th>
                      <th>Nama Produk</th>
                      <th>Status Item</th>
                    </tr>
                  </thead>

                  <tbody>
                    @forelse($items as $index => $item)
                      <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->item_id ?? '-' }}</td>
                        <td>{{ $item->product_code ?? '-' }}</td>
                        <td>{{ $item->product_name ?? '-' }}</td>
                        <td>{{ $item->item_status ?? '-' }}</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="5" class="text-center text-muted">
                          Tidak ada detail item
                        </td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button"
                    class="btn btn-outline-secondary btn-sm"
                    data-bs-dismiss="modal">
              Tutup
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

  function formatRupiah(value) {
    return 'Rp ' + new Intl.NumberFormat('id-ID', {
      maximumFractionDigits: 0
    }).format(Number(value || 0));
  }

  function formatNumber(value) {
    return new Intl.NumberFormat('id-ID', {
      maximumFractionDigits: 0
    }).format(Number(value || 0));
  }

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

  function tooltipStyle() {
    return {
      backgroundColor: '#0f172a',
      titleColor: '#ffffff',
      bodyColor: '#ffffff',
      padding: 12,
      cornerRadius: 10
    };
  }

  function initVoidDailyChart() {
    const canvas = document.getElementById('voidDailyChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($voidDailyLabels);
    const counts = @json($voidDailyCounts);
    const amounts = @json($voidDailyAmounts);

    if (!labels.length) {
      return;
    }

    const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 320);
    gradient.addColorStop(0, 'rgba(91, 92, 246, .28)');
    gradient.addColorStop(1, 'rgba(91, 92, 246, 0)');

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Jumlah Pengajuan Void',
            data: counts,
            backgroundColor: '#5b5cf6',
            borderColor: '#5b5cf6',
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 45,
            yAxisID: 'y'
          },
          {
            type: 'line',
            label: 'Nominal Void',
            data: amounts,
            borderColor: '#ef4444',
            backgroundColor: gradient,
            borderWidth: 3,
            tension: .38,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 7,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#ef4444',
            pointBorderWidth: 3,
            yAxisID: 'yAmount'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            ...tooltipStyle(),
            callbacks: {
              label: function (context) {
                if (context.dataset.label === 'Nominal Void') {
                  return context.dataset.label + ': ' + formatRupiah(context.raw);
                }

                return context.dataset.label + ': ' + formatNumber(context.raw);
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: '#64748b',
              callback: function (value) {
                return formatNumber(value);
              }
            }
          },
          yAmount: {
            beginAtZero: true,
            position: 'right',
            grid: {
              drawOnChartArea: false
            },
            ticks: {
              color: '#64748b',
              callback: function (value) {
                return formatRupiah(value);
              }
            }
          },
          x: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: '#64748b'
            }
          }
        }
      }
    });
  }

  function initVoidStatusChart() {
    const canvas = document.getElementById('voidStatusChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($voidStatusLabels);
    const counts = @json($voidStatusCounts);

    if (!labels.length) {
      return;
    }

    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Jumlah',
            data: counts,
            backgroundColor: chartColors,
            borderColor: '#ffffff',
            borderWidth: 3,
            hoverOffset: 8
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              pointStyle: 'circle',
              padding: 16,
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            ...tooltipStyle(),
            callbacks: {
              label: function (context) {
                return context.label + ': ' + formatNumber(context.raw);
              }
            }
          }
        }
      }
    });
  }

  function initComboBarLineChart(canvasId, labelsData, countsData, amountsData) {
    const canvas = document.getElementById(canvasId);

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    if (!labelsData.length) {
      return;
    }

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labelsData,
        datasets: [
          {
            label: 'Jumlah Pengajuan Void',
            data: countsData,
            backgroundColor: chartColors,
            borderColor: chartColors,
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 34,
            yAxisID: 'y'
          },
          {
            type: 'line',
            label: 'Nominal Void',
            data: amountsData,
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, .12)',
            borderWidth: 3,
            tension: .38,
            fill: false,
            pointRadius: 4,
            pointHoverRadius: 7,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#ef4444',
            pointBorderWidth: 3,
            yAxisID: 'yAmount'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            ...tooltipStyle(),
            callbacks: {
              label: function (context) {
                if (context.dataset.label === 'Nominal Void') {
                  return context.dataset.label + ': ' + formatRupiah(context.raw);
                }

                return context.dataset.label + ': ' + formatNumber(context.raw);
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: '#64748b',
              callback: function (value) {
                return formatNumber(value);
              }
            }
          },
          yAmount: {
            beginAtZero: true,
            position: 'right',
            grid: {
              drawOnChartArea: false
            },
            ticks: {
              color: '#64748b',
              callback: function (value) {
                return formatRupiah(value);
              }
            }
          },
          x: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: '#64748b'
            }
          }
        }
      }
    });
  }

  $(document).ready(function () {
    initFilterSelect2();

    initVoidDailyChart();
    initVoidStatusChart();

    initComboBarLineChart(
      'voidStoreChart',
      @json($voidStoreLabels),
      @json($voidStoreCounts),
      @json($voidStoreAmounts)
    );

    initComboBarLineChart(
      'voidCashierChart',
      @json($voidCashierLabels),
      @json($voidCashierCounts),
      @json($voidCashierAmounts)
    );
  });
})(window.jQuery);
</script>
@endpush