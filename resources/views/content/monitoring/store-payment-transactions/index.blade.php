@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Store Payment Transactions')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --sp-primary: #5b5cf6;
    --sp-primary-dark: #4338ca;
    --sp-primary-soft: rgba(91, 92, 246, .12);
    --sp-success: #22c55e;
    --sp-success-soft: rgba(34, 197, 94, .14);
    --sp-info: #06b6d4;
    --sp-info-soft: rgba(6, 182, 212, .14);
    --sp-warning: #f59e0b;
    --sp-warning-soft: rgba(245, 158, 11, .16);
    --sp-danger: #ef4444;
    --sp-danger-soft: rgba(239, 68, 68, .14);
    --sp-purple: #8b5cf6;
    --sp-dark: #1e293b;
    --sp-muted: #64748b;
    --sp-border: #e5e7eb;
    --sp-body: #f6f7fb;
    --sp-card: #ffffff;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--sp-body);
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
    border-color: var(--sp-primary);
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
    border-color: var(--sp-primary);
    background: linear-gradient(135deg, var(--sp-primary), var(--sp-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--sp-success);
    background: linear-gradient(135deg, var(--sp-success), #16a34a);
    box-shadow: 0 .45rem 1rem rgba(34, 197, 94, .18);
  }

  .btn-outline-secondary,
  .btn-outline-primary {
    background: #fff;
    border-color: #d7deea;
  }

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: var(--sp-card);
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--sp-primary), var(--sp-success));
  }

  .summary-card::after {
    content: "";
    position: absolute;
    width: 90px;
    height: 90px;
    right: -36px;
    bottom: -36px;
    border-radius: 50%;
    background: var(--sp-primary-soft);
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--sp-dark);
    font-weight: 800;
    letter-spacing: -.02em;
  }

  .summary-card .text-muted {
    color: var(--sp-muted) !important;
  }

  .summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--sp-primary), var(--sp-primary-dark));
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

  .chart-card {
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: #fff;
    overflow: hidden;
  }

  .chart-card .card-header h5 {
    color: var(--sp-dark);
    font-weight: 800;
  }

  .chart-card .card-header small {
    color: var(--sp-muted) !important;
  }

  .chart-wrapper {
    position: relative;
    height: 330px;
    min-height: 330px;
    width: 100%;
  }

  .chart-wrapper-sm {
    position: relative;
    height: 290px;
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

  .table td,
  .table th {
    vertical-align: middle;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .payment-table thead th,
  .summary-table thead th,
  .summary-modal-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .payment-table tbody td,
  .summary-table tbody td,
  .summary-modal-table tbody td {
    color: #475569;
  }

  .payment-table tbody tr:hover,
  .summary-table tbody tr:hover,
  .summary-modal-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--sp-muted);
  }

  .product-list-box {
    min-width: 260px;
    white-space: normal !important;
  }

  .product-list-box .product-item {
    display: inline-flex;
    align-items: center;
    margin: 0 .25rem .25rem 0;
    padding: .25rem .55rem;
    border-radius: 999px;
    background: var(--sp-primary-soft);
    color: var(--sp-primary-dark);
    font-weight: 600;
    font-size: .78rem;
    white-space: normal;
  }

  .store-col,
  .customer-col,
  .voucher-col,
  .discount-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .amount-success {
    color: #15803d !important;
    font-weight: 800;
  }

  .amount-danger {
    color: #b91c1c !important;
    font-weight: 800;
  }

  .badge-soft-success {
    background: var(--sp-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--sp-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--sp-warning-soft) !important;
    color: #b45309 !important;
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

  .badge-soft-info {
    background: var(--sp-info-soft) !important;
    color: #0369a1 !important;
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

    .chart-wrapper,
    .chart-wrapper-sm {
      height: 260px;
      min-height: 260px;
    }

    .card-body {
      padding: 1rem;
    }
  }
</style>
@endpush

@php
  $summaryGrandTotal = (float) ($summaryGrandTotal ?? 0);
  $summaryGrandTransaction = (int) ($summaryGrandTransaction ?? 0);

  $dashboardTotalTransactions = (float) ($summaryDashboard->total_transactions ?? 0);
  $dashboardTotalStores = (float) ($summaryDashboard->total_stores ?? 0);
  $dashboardTotalCustomers = (float) ($summaryDashboard->total_customers ?? 0);
  $dashboardTotalAmount = (float) ($summaryDashboard->total_amount ?? 0);
  $dashboardTotalPaid = (float) ($summaryDashboard->total_paid ?? 0);
  $dashboardTotalChange = (float) ($summaryDashboard->total_change ?? 0);

  $paymentMethodLabels = $paymentMethodLabels ?? collect();
  $paymentMethodCounts = $paymentMethodCounts ?? collect();
  $paymentMethodAmounts = $paymentMethodAmounts ?? collect();

  $dailyLabels = $dailyLabels ?? collect();
  $dailyCounts = $dailyCounts ?? collect();
  $dailyAmounts = $dailyAmounts ?? collect();

  $hasFilter = request()->hasAny([
    'store_id',
    'payment_method_id',
    'payment_status',
    'start_date',
    'end_date',
    'keyword',
    'per_page',
  ]);

  $exportQuery = request()->query();

  $statusBadge = function ($status) {
    return match($status) {
      'pending' => 'badge-soft-warning',
      'paid' => 'badge-soft-success',
      'failed' => 'badge-soft-danger',
      'cancelled' => 'badge-soft-secondary',
      default => 'badge-soft-secondary',
    };
  };
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Store Payment Transactions
        </h4>
        <div class="hero-subtitle">
          Dashboard monitoring transaksi pembayaran store, metode pembayaran, nominal transaksi, customer, voucher, dan produk yang dibeli.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <span class="live-dot me-1"></span>
          Live Data
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-calendar-range-outline me-1"></i>
          {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-cash-multiple me-1"></i>
          Rp {{ number_format($dashboardTotalAmount, 0, ',', '.') }}
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
        <h5 class="mb-1 fw-bold">Filter Store Payment Transactions</h5>
        <small class="text-muted">
          Gunakan filter untuk mencari berdasarkan store, customer, transaksi, produk, voucher, metode, dan status pembayaran.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterStorePayment">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.store-payment-transactions.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.store-payment-transactions.export', $exportQuery) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>

        <button type="button"
                class="btn btn-outline-primary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#paymentMethodSummaryModal">
          <i class="mdi mdi-chart-box-outline me-1"></i> Ringkasan Metode
        </button>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterStorePayment">
        <div class="filter-box">
          <form method="GET"
                action="{{ route('monitoring.store-payment-transactions.index') }}"
                class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Keyword</label>
              <input type="text"
                     name="keyword"
                     class="form-control"
                     value="{{ $keyword ?? '' }}"
                     placeholder="Store / Customer / Txn / Produk / Voucher">
            </div>

            <div class="col-md-3">
              <label class="form-label">Store</label>
              <select name="store_id"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Store">
                <option value=""></option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected((string) $storeId === (string) $store->id)>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Tanggal Mulai</label>
              <input type="date"
                     name="start_date"
                     value="{{ $startDate }}"
                     class="form-control"
                     required>
            </div>

            <div class="col-md-2">
              <label class="form-label">Tanggal Selesai</label>
              <input type="date"
                     name="end_date"
                     value="{{ $endDate }}"
                     class="form-control"
                     required>
            </div>

            <div class="col-md-2">
              <label class="form-label">Status</label>
              <select name="payment_status"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Status">
                <option value=""></option>
                <option value="pending" @selected($paymentStatus === 'pending')>Pending</option>
                <option value="paid" @selected($paymentStatus === 'paid')>Paid</option>
                <option value="failed" @selected($paymentStatus === 'failed')>Failed</option>
                <option value="cancelled" @selected($paymentStatus === 'cancelled')>Cancelled</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Metode Pembayaran</label>
              <select name="payment_method_id"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Metode">
                <option value=""></option>
                @foreach($paymentMethods as $method)
                  <option value="{{ $method->id }}" @selected((string) $paymentMethodId === (string) $method->id)>
                    {{ $method->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Show</label>
              <select name="per_page" class="form-select">
                @foreach([10,25,50,100] as $size)
                  <option value="{{ $size }}" @selected((int) $perPage === $size)>
                    {{ $size }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.store-payment-transactions.export', $exportQuery) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.store-payment-transactions.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                  <i class="mdi mdi-refresh me-1"></i> Reset
                </a>

                <button type="button"
                        class="btn btn-outline-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#paymentMethodSummaryModal">
                  <i class="mdi mdi-chart-box-outline me-1"></i> Ringkasan Metode
                </button>
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
              <div class="text-muted small">Total Transaksi</div>
              <h4 class="mb-0">{{ number_format($dashboardTotalTransactions, 0, ',', '.') }}</h4>
              <small class="text-muted">Transaksi sesuai filter</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-receipt-text-outline"></i>
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
              <div class="text-muted small">Total Nominal</div>
              <h4 class="mb-0">Rp {{ number_format($dashboardTotalAmount, 0, ',', '.') }}</h4>
              <small class="text-muted">Total amount transaksi</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-cash-multiple"></i>
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
              <div class="text-muted small">Total Paid</div>
              <h4 class="mb-0">Rp {{ number_format($dashboardTotalPaid, 0, ',', '.') }}</h4>
              <small class="text-muted">Nominal dibayar customer</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-credit-card-check-outline"></i>
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
              <div class="text-muted small">Total Kembalian</div>
              <h4 class="mb-0">Rp {{ number_format($dashboardTotalChange, 0, ',', '.') }}</h4>
              <small class="text-muted">Change amount</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-cash-refund"></i>
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
              <div class="text-muted small">Metode Digunakan</div>
              <h4 class="mb-0">{{ number_format($paymentMethodSummaries->count(), 0, ',', '.') }}</h4>
              <small class="text-muted">Jenis metode pembayaran</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-wallet-outline"></i>
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
              <div class="text-muted small">Total Store</div>
              <h4 class="mb-0">{{ number_format($dashboardTotalStores, 0, ',', '.') }}</h4>
              <small class="text-muted">Store transaksi</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-store-outline"></i>
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
              <div class="text-muted small">Total Customer</div>
              <h4 class="mb-0">{{ number_format($dashboardTotalCustomers, 0, ',', '.') }}</h4>
              <small class="text-muted">Customer unik</small>
            </div>
            <div class="summary-icon success">
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
              <div class="text-muted small">Total Detail Metode</div>
              <h4 class="mb-0">Rp {{ number_format($summaryGrandTotal, 0, ',', '.') }}</h4>
              <small class="text-muted">{{ number_format($summaryGrandTransaction, 0, ',', '.') }} detail pembayaran</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-chart-box-outline"></i>
            </div>
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
          <h5 class="mb-1">Grafik Transaksi per Hari</h5>
          <small class="text-muted">Jumlah transaksi dan nominal transaksi harian.</small>
        </div>
        <div class="card-body">
          @if(count($dailyLabels))
            <div class="chart-wrapper">
              <canvas id="dailyPaymentChart"></canvas>
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
          <h5 class="mb-1">Komposisi Metode Pembayaran</h5>
          <small class="text-muted">Perbandingan penggunaan metode pembayaran.</small>
        </div>
        <div class="card-body">
          @if(count($paymentMethodLabels))
            <div class="chart-wrapper-sm">
              <canvas id="paymentMethodDoughnutChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data metode pembayaran.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-12">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Grafik Metode Pembayaran</h5>
          <small class="text-muted">Jumlah transaksi dan total nominal per metode pembayaran.</small>
        </div>
        <div class="card-body">
          @if(count($paymentMethodLabels))
            <div class="chart-wrapper">
              <canvas id="paymentMethodBarChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data metode pembayaran.</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- PAYMENT METHOD SUMMARY --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Dashboard Detail Metode Pembayaran</h5>
        <small class="text-muted">
          Ringkasan metode pembayaran dan detail penggunaan oleh transaksi.
        </small>
      </div>

      <button type="button"
              class="btn btn-outline-primary btn-sm"
              data-bs-toggle="modal"
              data-bs-target="#paymentMethodSummaryModal">
        <i class="mdi mdi-chart-box-outline me-1"></i> Lihat Ringkasan
      </button>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm table-nowrap mb-0 summary-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Metode Pembayaran</th>
              <th class="text-end">Jumlah Transaksi</th>
              <th class="text-end">Total Digunakan</th>
              <th>Penggunaan Pertama</th>
              <th>Penggunaan Terakhir</th>
              <th class="text-center">Detail Transaksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($paymentMethodSummaries as $index => $summary)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                  <span class="badge badge-soft-info">
                    {{ $summary->payment_method_name ?? '-' }}
                  </span>
                </td>
                <td class="text-end">{{ number_format((int) ($summary->total_transactions ?? 0), 0, ',', '.') }}</td>
                <td class="text-end fw-bold amount-success">Rp {{ number_format((float) ($summary->total_amount ?? 0), 0, ',', '.') }}</td>
                <td>
                  {{ $summary->first_used_at ? \Carbon\Carbon::parse($summary->first_used_at)->format('d M Y H:i') : '-' }}
                </td>
                <td>
                  {{ $summary->last_used_at ? \Carbon\Carbon::parse($summary->last_used_at)->format('d M Y H:i') : '-' }}
                </td>
                <td class="text-center">
                  <button type="button"
                          class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#paymentMethodDetailModal-{{ $summary->payment_method_id }}">
                    <i class="mdi mdi-eye-outline me-1"></i> Detail
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Tidak ada data metode pembayaran.</td>
              </tr>
            @endforelse
          </tbody>

          @if($paymentMethodSummaries->count())
            <tfoot class="table-light">
              <tr>
                <th colspan="2" class="text-end">Grand Total</th>
                <th class="text-end">{{ number_format($summaryGrandTransaction, 0, ',', '.') }}</th>
                <th class="text-end">Rp {{ number_format($summaryGrandTotal, 0, ',', '.') }}</th>
                <th colspan="3"></th>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>

  {{-- MAIN TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Data Transaksi Pembayaran Store</h5>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap payment-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Store</th>
              <th>Visit</th>
              <th>Customer</th>
              <th>Check In</th>
              <th>Check Out</th>
              <th>Transaction No</th>
              <th>Payment Method</th>
              <th class="text-end">Total</th>
              <th class="text-end">Tax</th>
              <th class="text-end">Paid</th>
              <th class="text-end">Change</th>
              <th>Status Payment</th>
              <th>Voucher</th>
              <th class="text-end">Voucher Discount</th>
              <th>Diskon Digunakan</th>
              <th>Produk Dibeli</th>
              <th class="text-center">Jml Produk</th>
              <th>Source</th>
              <th>Completed At</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $row)
              @php
                $badgeClass = $statusBadge($row->payment_status);
                $productDetails = json_decode($row->product_details ?? '[]', true) ?? [];
              @endphp

              <tr>
                <td>{{ $rows->firstItem() + $loop->index }}</td>

                <td class="store-col">
                  <div class="fw-semibold">{{ $row->store_name ?? '-' }}</div>
                  <small class="text-muted d-block">{{ $row->store_code ?? '-' }}</small>
                </td>

                <td>
                  <span class="badge badge-soft-info">{{ $row->visit_code ?? '-' }}</span>
                </td>

                <td class="customer-col">
                  <div class="fw-semibold">{{ $row->customer_name ?? 'Guest / Non Member' }}</div>
                </td>

                <td>{{ $row->check_in_at ? \Carbon\Carbon::parse($row->check_in_at)->format('d-m-Y H:i') : '-' }}</td>

                <td>{{ $row->check_out_at ? \Carbon\Carbon::parse($row->check_out_at)->format('d-m-Y H:i') : '-' }}</td>

                <td class="fw-semibold">{{ $row->transaction_number ?? '-' }}</td>

                <td>
                  <span class="badge badge-soft-primary">
                    {{ $row->payment_methods ?? '-' }}
                  </span>
                </td>

                <td class="text-end fw-bold amount-success">
                  Rp {{ number_format((float) ($row->total_amount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->tax_amount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->paid_amount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->change_amount ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  <span class="badge {{ $badgeClass }}">
                    {{ $row->payment_status ? ucfirst($row->payment_status) : '-' }}
                  </span>
                </td>

                <td class="voucher-col">
                  {{ $row->voucher_name ?? '-' }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->voucher_discount ?? 0), 0, ',', '.') }}
                </td>

                <td class="discount-col">
                  {{ $row->used_discounts ?? '-' }}
                </td>

                <td class="product-list-box">
                  @if(count($productDetails))
                    @foreach($productDetails as $product)
                      <span class="product-item">
                        {{ $product['product_name'] ?? '-' }}
                      </span>
                    @endforeach
                  @else
                    -
                  @endif
                </td>

                <td class="text-center">
                  {{ number_format((int) ($row->total_products ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  <span class="badge badge-soft-secondary">
                    {{ $row->payment_source ?? '-' }}
                  </span>
                </td>

                <td>
                  {{ $row->completed_at ? \Carbon\Carbon::parse($row->completed_at)->format('d-m-Y H:i') : '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="20" class="text-center text-muted py-4">
                  Tidak ada data transaksi.
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

{{-- MODAL RINGKASAN --}}
<div class="modal fade" id="paymentMethodSummaryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-1 text-white">Ringkasan Metode Pembayaran</h5>
          <div class="small text-muted">
            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0 summary-modal-table">
            <thead>
              <tr>
                <th style="width: 60px;">No</th>
                <th>Metode Pembayaran</th>
                <th class="text-end">Jumlah Transaksi</th>
                <th class="text-end">Total Digunakan</th>
              </tr>
            </thead>
            <tbody>
              @forelse($paymentMethodSummaries as $index => $summary)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td>{{ $summary->payment_method_name ?? '-' }}</td>
                  <td class="text-end">{{ number_format((int) $summary->total_transactions, 0, ',', '.') }}</td>
                  <td class="text-end fw-bold amount-success">Rp {{ number_format((float) $summary->total_amount, 0, ',', '.') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">
                    Tidak ada data ringkasan.
                  </td>
                </tr>
              @endforelse
            </tbody>

            @if($paymentMethodSummaries->count())
              <tfoot class="table-light">
                <tr>
                  <th colspan="2" class="text-end">Grand Total</th>
                  <th class="text-end">{{ number_format($summaryGrandTransaction, 0, ',', '.') }}</th>
                  <th class="text-end">Rp {{ number_format($summaryGrandTotal, 0, ',', '.') }}</th>
                </tr>
              </tfoot>
            @endif
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
          <i class="mdi mdi-close me-1"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

{{-- MODAL DETAIL METODE --}}
@foreach($paymentMethodSummaries as $summary)
  @php
    $detailRows = $paymentMethodUsageDetails->get($summary->payment_method_id, collect());
  @endphp

  <div class="modal fade" id="paymentMethodDetailModal-{{ $summary->payment_method_id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-1 text-white">Detail Penggunaan {{ $summary->payment_method_name ?? '-' }}</h5>
            <div class="small text-muted">
              Total {{ number_format((int) ($summary->total_transactions ?? 0), 0, ',', '.') }} transaksi |
              Rp {{ number_format((float) ($summary->total_amount ?? 0), 0, ',', '.') }}
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm table-nowrap mb-0 summary-modal-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Completed At</th>
                  <th>Transaction No</th>
                  <th>Visit</th>
                  <th>Store</th>
                  <th>Customer</th>
                  <th>Status</th>
                  <th class="text-end">Nominal Metode</th>
                  <th class="text-end">Total Transaksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($detailRows as $i => $detail)
                  @php
                    $detailStatusClass = $statusBadge($detail->payment_status);
                  @endphp
                  <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                      {{ $detail->completed_at ? \Carbon\Carbon::parse($detail->completed_at)->format('d M Y H:i') : '-' }}
                    </td>
                    <td class="fw-semibold">{{ $detail->transaction_number ?? '-' }}</td>
                    <td>{{ $detail->visit_code ?? '-' }}</td>
                    <td>
                      {{ $detail->store_name ?? '-' }}
                      @if($detail->store_code)
                        <div class="small text-muted">{{ $detail->store_code }}</div>
                      @endif
                    </td>
                    <td>{{ $detail->customer_name ?? 'Guest / Non Member' }}</td>
                    <td>
                      <span class="badge {{ $detailStatusClass }}">
                        {{ $detail->payment_status ? ucfirst($detail->payment_status) : '-' }}
                      </span>
                    </td>
                    <td class="text-end fw-bold amount-success">
                      Rp {{ number_format((float) ($detail->payment_method_amount ?? 0), 0, ',', '.') }}
                    </td>
                    <td class="text-end">
                      Rp {{ number_format((float) ($detail->total_amount ?? 0), 0, ',', '.') }}
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                      Tidak ada detail transaksi.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
            <i class="mdi mdi-close me-1"></i> Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
@endforeach
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
  'use strict';

  const chartColors = {
    primary: '#5b5cf6',
    info: '#06b6d4',
    success: '#22c55e',
    warning: '#f59e0b',
    danger: '#ef4444',
    purple: '#8b5cf6',
    dark: '#0f172a',
    muted: '#64748b'
  };

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

  function initDailyPaymentChart() {
    const canvas = document.getElementById('dailyPaymentChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($dailyLabels);
    const counts = @json($dailyCounts);
    const amounts = @json($dailyAmounts);

    if (!labels.length) {
      return;
    }

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Jumlah Transaksi',
            data: counts,
            backgroundColor: chartColors.primary,
            borderColor: chartColors.primary,
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 42,
            yAxisID: 'y'
          },
          {
            type: 'line',
            label: 'Nominal Transaksi',
            data: amounts,
            borderColor: chartColors.success,
            backgroundColor: 'rgba(34, 197, 94, .12)',
            borderWidth: 3,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
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
              pointStyle: 'circle',
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            backgroundColor: chartColors.dark,
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                if (context.dataset.label === 'Nominal Transaksi') {
                  return context.dataset.label + ': ' + formatRupiah(context.raw);
                }

                return context.dataset.label + ': ' + formatNumber(context.raw);
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
              color: chartColors.muted,
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
              color: chartColors.muted,
              font: {
                size: 11,
                weight: '600'
              },
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
              color: chartColors.muted,
              font: {
                size: 11,
                weight: '600'
              },
              callback: function (value) {
                return formatRupiah(value);
              }
            }
          }
        }
      }
    });
  }

  function initPaymentMethodDoughnutChart() {
    const canvas = document.getElementById('paymentMethodDoughnutChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($paymentMethodLabels);
    const counts = @json($paymentMethodCounts);

    if (!labels.length) {
      return;
    }

    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Jumlah Transaksi',
            data: counts,
            backgroundColor: [
              chartColors.primary,
              chartColors.info,
              chartColors.success,
              chartColors.warning,
              chartColors.danger,
              chartColors.purple,
              '#14b8a6',
              '#f97316',
              '#64748b'
            ],
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 6
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '64%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              pointStyle: 'circle',
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            backgroundColor: chartColors.dark,
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return context.label + ': ' + formatNumber(context.raw) + ' transaksi';
              }
            }
          }
        }
      }
    });
  }

  function initPaymentMethodBarChart() {
    const canvas = document.getElementById('paymentMethodBarChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($paymentMethodLabels);
    const counts = @json($paymentMethodCounts);
    const amounts = @json($paymentMethodAmounts);

    if (!labels.length) {
      return;
    }

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Jumlah Transaksi',
            data: counts,
            backgroundColor: chartColors.primary,
            borderColor: chartColors.primary,
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 42,
            yAxisID: 'y'
          },
          {
            type: 'line',
            label: 'Total Digunakan',
            data: amounts,
            borderColor: chartColors.success,
            backgroundColor: 'rgba(34, 197, 94, .12)',
            borderWidth: 3,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
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
              pointStyle: 'circle',
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            backgroundColor: chartColors.dark,
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                if (context.dataset.label === 'Total Digunakan') {
                  return context.dataset.label + ': ' + formatRupiah(context.raw);
                }

                return context.dataset.label + ': ' + formatNumber(context.raw);
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
              color: chartColors.muted,
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
              color: chartColors.muted,
              font: {
                size: 11,
                weight: '600'
              },
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
              color: chartColors.muted,
              font: {
                size: 11,
                weight: '600'
              },
              callback: function (value) {
                return formatRupiah(value);
              }
            }
          }
        }
      }
    });
  }

  $(document).ready(function () {
    initFilterSelect2();
    initDailyPaymentChart();
    initPaymentMethodDoughnutChart();
    initPaymentMethodBarChart();
  });
})(window.jQuery);
</script>
@endpush