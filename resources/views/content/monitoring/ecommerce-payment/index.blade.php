@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Ecommerce Payment')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --ep-primary: #5b5cf6;
    --ep-primary-dark: #4338ca;
    --ep-primary-soft: rgba(91, 92, 246, .12);
    --ep-success: #22c55e;
    --ep-success-soft: rgba(34, 197, 94, .14);
    --ep-info: #06b6d4;
    --ep-info-soft: rgba(6, 182, 212, .14);
    --ep-warning: #f59e0b;
    --ep-warning-soft: rgba(245, 158, 11, .16);
    --ep-danger: #ef4444;
    --ep-danger-soft: rgba(239, 68, 68, .14);
    --ep-purple: #8b5cf6;
    --ep-dark: #1e293b;
    --ep-muted: #64748b;
    --ep-border: #e5e7eb;
    --ep-body: #f6f7fb;
    --ep-card: #ffffff;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--ep-body);
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
    border-color: var(--ep-primary);
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
    border-color: var(--ep-primary);
    background: linear-gradient(135deg, var(--ep-primary), var(--ep-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--ep-success);
    background: linear-gradient(135deg, var(--ep-success), #16a34a);
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
    background: var(--ep-card);
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--ep-primary), var(--ep-success));
  }

  .summary-card::after {
    content: "";
    position: absolute;
    width: 90px;
    height: 90px;
    right: -36px;
    bottom: -36px;
    border-radius: 50%;
    background: var(--ep-primary-soft);
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--ep-dark);
    font-weight: 800;
    letter-spacing: -.02em;
  }

  .summary-card .text-muted {
    color: var(--ep-muted) !important;
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
    background: linear-gradient(135deg, var(--ep-primary), var(--ep-primary-dark));
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

  .chart-card {
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: #fff;
    overflow: hidden;
  }

  .chart-card .card-header h5 {
    color: var(--ep-dark);
    font-weight: 800;
  }

  .chart-card .card-header small {
    color: var(--ep-muted) !important;
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

  .ecommerce-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .ecommerce-table tbody td {
    color: #475569;
  }

  .ecommerce-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--ep-muted);
  }

  .address-col {
    min-width: 340px;
    white-space: normal !important;
  }

  .shipping-col {
    min-width: 280px;
    white-space: normal !important;
  }

  .payment-method-col {
    min-width: 300px;
    white-space: normal !important;
  }

  .customer-col {
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

  .amount-text {
    white-space: nowrap;
  }

  .badge-soft-success {
    background: var(--ep-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--ep-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--ep-warning-soft) !important;
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
    background: var(--ep-info-soft) !important;
    color: #0369a1 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .product-modal-summary-card {
    border: 1px solid #eef0f4;
    border-radius: .95rem;
    height: 100%;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
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

@section('content')
@php
  $dashboardSummary = $dashboardSummary ?? (object) [
    'total_order' => 0,
    'total_qty' => 0,
    'total_sub_total' => 0,
    'total_discount' => 0,
    'total_grand_total' => 0,
    'total_paid_amount' => 0,
    'total_outstanding' => 0,
    'average_order_value' => 0,
  ];

  $chartOmzetDaily = $chartOmzetDaily ?? collect();
  $chartPaymentStatus = $chartPaymentStatus ?? collect();
  $chartPaymentMethod = $chartPaymentMethod ?? collect();

  $startDate = $startDate ?: now()->toDateString();
  $endDate = $endDate ?: now()->toDateString();

  $chartOmzetDailyData = $chartOmzetDaily->map(function ($row) {
    return [
      'tanggal' => !empty($row->tanggal) ? \Carbon\Carbon::parse($row->tanggal)->format('d M') : '-',
      'total_omset' => (float) ($row->total_omset ?? 0),
      'total_paid' => (float) ($row->total_paid ?? 0),
      'total_order' => (int) ($row->total_order ?? 0),
    ];
  })->values();

  $chartPaymentStatusData = $chartPaymentStatus->map(function ($row) {
    return [
      'label' => (string) ($row->label ?? '-'),
      'total_order' => (int) ($row->total_order ?? 0),
      'total_omset' => (float) ($row->total_omset ?? 0),
    ];
  })->values();

  $chartPaymentMethodData = $chartPaymentMethod->map(function ($row) {
    return [
      'label' => (string) ($row->label ?? '-'),
      'total_order' => (int) ($row->total_order ?? 0),
      'total_omset' => (float) ($row->total_omset ?? 0),
    ];
  })->values();

  $hasFilter = request()->hasAny([
    'customer_name',
    'email',
    'wa_number',
    'order_number',
    'order_status',
    'payment_status',
    'payment_method_id',
    'start_date',
    'end_date',
    'per_page',
  ]);

  $statusBadge = function ($status) {
    $status = strtolower((string) $status);

    return match($status) {
      'paid', 'success', 'settlement', 'completed', 'capture' => 'badge-soft-success',
      'pending', 'waiting', 'process' => 'badge-soft-warning',
      'failed', 'cancel', 'cancelled', 'expired', 'deny' => 'badge-soft-danger',
      default => 'badge-soft-secondary',
    };
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Ecommerce Payment
        </h4>
        <div class="hero-subtitle">
          Monitoring pembayaran ecommerce, status order, metode pembayaran, nominal order, customer, pengiriman, dan produk yang dibeli.
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
          Rp {{ number_format((float) $dashboardSummary->total_grand_total, 0, ',', '.') }}
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
        <h5 class="mb-1 fw-bold">Filter Ecommerce Payment</h5>
        <small class="text-muted">
          Gunakan filter untuk mencari berdasarkan customer, email, nomor WA, order number, status, metode pembayaran, dan periode.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterEcommercePayment">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.ecommerce-payment.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.ecommerce-payment.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterEcommercePayment">
        <div class="filter-box">
          <form method="GET" action="{{ route('monitoring.ecommerce-payment.index') }}" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Nama Customer</label>
              <input type="text"
                     name="customer_name"
                     value="{{ $customerName }}"
                     class="form-control"
                     placeholder="Nama customer">
            </div>

            <div class="col-md-3">
              <label class="form-label">Email</label>
              <input type="text"
                     name="email"
                     value="{{ $email }}"
                     class="form-control"
                     placeholder="Email customer">
            </div>

            <div class="col-md-2">
              <label class="form-label">No. WA</label>
              <input type="text"
                     name="wa_number"
                     value="{{ $waNumber }}"
                     class="form-control"
                     placeholder="08xxxx">
            </div>

            <div class="col-md-2">
              <label class="form-label">Order Number</label>
              <input type="text"
                     name="order_number"
                     value="{{ $orderNumber }}"
                     class="form-control"
                     placeholder="ORD...">
            </div>

            <div class="col-md-2">
              <label class="form-label">Order Status</label>
              <select name="order_status" class="form-select js-select2-filter" data-placeholder="Semua Order Status">
                <option value=""></option>
                @foreach($orderStatuses as $status)
                  <option value="{{ $status }}" @selected((string) $orderStatus === (string) $status)>
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Payment Status</label>
              <select name="payment_status" class="form-select js-select2-filter" data-placeholder="Semua Payment Status">
                <option value=""></option>
                @foreach($paymentStatuses as $status)
                  <option value="{{ $status }}" @selected((string) $paymentStatus === (string) $status)>
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Payment Method</label>
              <select name="payment_method_id" class="form-select js-select2-filter" data-placeholder="Semua Payment Method">
                <option value=""></option>
                @foreach($paymentMethods as $pm)
                  <option value="{{ $pm->id }}" @selected((string) $paymentMethod === (string) $pm->id)>
                    {{ $pm->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Tanggal Dari</label>
              <input type="date"
                     name="start_date"
                     value="{{ $startDate }}"
                     class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Tanggal Sampai</label>
              <input type="date"
                     name="end_date"
                     value="{{ $endDate }}"
                     class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Show</label>
              <select name="per_page" class="form-select">
                @foreach([10, 25, 50, 100] as $size)
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

                <a href="{{ route('monitoring.ecommerce-payment.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.ecommerce-payment.index') }}"
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
              <div class="text-muted small">Total Omset</div>
              <h4 class="mb-1 amount-text">
                Rp {{ number_format((float) $dashboardSummary->total_grand_total, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Grand total ecommerce</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-cash-multiple"></i>
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
              <div class="text-muted small">Total Paid</div>
              <h4 class="mb-1 amount-text">
                Rp {{ number_format((float) $dashboardSummary->total_paid_amount, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Pembayaran diterima</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-credit-card-check-outline"></i>
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
              <div class="text-muted small">Total Order</div>
              <h4 class="mb-1">{{ number_format((int) $dashboardSummary->total_order, 0, ',', '.') }}</h4>
              <small class="text-muted">Order ecommerce</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-cart-outline"></i>
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
              <div class="text-muted small">Total Qty</div>
              <h4 class="mb-1">{{ number_format((int) $dashboardSummary->total_qty, 0, ',', '.') }}</h4>
              <small class="text-muted">Qty produk terjual</small>
            </div>
            <div class="summary-icon warning">
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
              <div class="text-muted small">Sub Total</div>
              <h4 class="mb-1 amount-text">
                Rp {{ number_format((float) $dashboardSummary->total_sub_total, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Sebelum diskon</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-receipt-text-outline"></i>
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
              <div class="text-muted small">Diskon</div>
              <h4 class="mb-1 amount-text">
                Rp {{ number_format((float) $dashboardSummary->total_discount, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Total diskon</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-tag-outline"></i>
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
              <div class="text-muted small">Outstanding</div>
              <h4 class="mb-1 amount-text {{ (float) $dashboardSummary->total_outstanding > 0 ? 'amount-danger' : 'amount-success' }}">
                Rp {{ number_format((float) $dashboardSummary->total_outstanding, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Grand total - paid</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-alert-circle-outline"></i>
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
              <div class="text-muted small">Avg Order Value</div>
              <h4 class="mb-1 amount-text">
                Rp {{ number_format((float) $dashboardSummary->average_order_value, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Rata-rata order</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-chart-line"></i>
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
          <h5 class="mb-1">Omset Ecommerce per Tanggal</h5>
          <small class="text-muted">Perbandingan omset, paid, dan order berdasarkan tanggal.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrapper">
            <canvas id="chartOmzetDaily"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Payment Status</h5>
          <small class="text-muted">Komposisi status pembayaran ecommerce.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrapper-sm">
            <canvas id="chartPaymentStatus"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Omset Berdasarkan Payment Method</h5>
          <small class="text-muted">Top metode pembayaran berdasarkan nilai omset.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrapper">
            <canvas id="chartPaymentMethod"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Laporan Transaksi Customer Ecommerce</h5>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap ecommerce-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal Order</th>
              <th>Order Number</th>
              <th>Produk</th>
              <th>Customer</th>
              <th>Email</th>
              <th>No. WA</th>
              <th>Order Status</th>
              <th>Payment Status</th>
              <th>Payment Method</th>
              <th>Transaction Ref</th>
              <th>Metode Pengiriman</th>
              <th class="text-end">Biaya Pengiriman</th>
              <th>No. Resi</th>
              <th class="text-end">Total Qty</th>
              <th class="text-end">Sub Total</th>
              <th class="text-end">Diskon</th>
              <th class="text-end">Grand Total</th>
              <th class="text-end">Paid Amount</th>
              <th>Alamat</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td>
                  {{ $row->order_created_at ? \Carbon\Carbon::parse($row->order_created_at)->format('d-m-Y H:i') : '-' }}
                </td>

                <td class="fw-semibold">{{ $row->order_number ?? '-' }}</td>

                <td>
                  <button type="button"
                          class="btn btn-sm btn-outline-primary js-show-products"
                          data-url="{{ route('monitoring.ecommerce-payment.products', $row->id) }}">
                    <i class="mdi mdi-package-variant-closed me-1"></i>
                    Lihat Produk
                  </button>
                </td>

                <td class="customer-col">
                  <div class="fw-semibold">{{ $row->customer_name ?? '-' }}</div>
                </td>

                <td>{{ $row->email ?? '-' }}</td>
                <td>{{ $row->wa_number ?? '-' }}</td>

                <td>
                  <span class="badge {{ $statusBadge($row->order_status ?? '') }}">
                    {{ $row->order_status ? ucfirst(str_replace('_', ' ', $row->order_status)) : '-' }}
                  </span>
                </td>

                <td>
                  <span class="badge {{ $statusBadge($row->payment_status ?? '') }}">
                    {{ $row->payment_status ? ucfirst(str_replace('_', ' ', $row->payment_status)) : '-' }}
                  </span>
                </td>

                <td class="payment-method-col">
                  {{ $row->payment_method_descriptions ?? $row->payment_methods ?? '-' }}

                  @if(!empty($row->midtrans_payment_types) && $row->midtrans_payment_types !== '-')
                    <div class="text-muted small">
                      Midtrans: {{ $row->midtrans_payment_types }}
                    </div>
                  @endif
                </td>

                <td>{{ $row->transaction_refs ?? '-' }}</td>

                <td class="shipping-col">
                  <div class="fw-semibold">
                    {{ $row->shipping_method_label ?? '-' }}
                  </div>

                  <div class="text-muted small">
                    Type: {{ $row->shipping_method_type ?? '-' }}

                    @if(!empty($row->shipping_duration) && $row->shipping_duration !== '-')
                      | Estimasi: {{ $row->shipping_duration }}
                    @endif
                  </div>

                  @if(!empty($row->shipping_courier_code) && $row->shipping_courier_code !== '-')
                    <div class="text-muted small">
                      Courier Code: {{ $row->shipping_courier_code }}
                    </div>
                  @endif
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->shipping_price ?? 0), 0, ',', '.') }}
                </td>

                <td>{{ $row->shipping_number ?? '-' }}</td>

                <td class="text-end">
                  {{ number_format((int) ($row->total_qty ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->sub_total ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->discount_total ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end fw-bold amount-success">
                  Rp {{ number_format((float) ($row->grand_total ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end fw-bold">
                  Rp {{ number_format((float) ($row->paid_amount ?? 0), 0, ',', '.') }}
                </td>

                <td class="address-col">
                  {{ $row->address_detail ?? '-' }},
                  {{ $row->village_name ?? '-' }},
                  {{ $row->district_name ?? '-' }},
                  {{ $row->regency_name ?? '-' }},
                  {{ $row->province_name ?? '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="20" class="text-center text-muted py-4">
                  Tidak ada data transaksi ecommerce.
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

  {{-- MODAL PRODUK --}}
  <div class="modal fade" id="modalOrderProducts" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-1 text-white">Produk yang Dibeli</h5>
            <div class="text-muted small" id="modalOrderProductsSubtitle">-</div>
          </div>

          <button type="button"
                  class="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div id="modalOrderProductsLoading" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="text-muted mt-2">Memuat data produk...</div>
          </div>

          <div id="modalOrderProductsContent">
            <div class="row g-3 mb-4">
              <div class="col-md-3">
                <div class="card product-modal-summary-card">
                  <div class="card-body">
                    <div class="text-muted small">Total Item</div>
                    <h5 class="mb-0" id="modalTotalItem">0</h5>
                  </div>
                </div>
              </div>

              <div class="col-md-3">
                <div class="card product-modal-summary-card">
                  <div class="card-body">
                    <div class="text-muted small">Total Qty</div>
                    <h5 class="mb-0" id="modalTotalQty">0</h5>
                  </div>
                </div>
              </div>

              <div class="col-md-3">
                <div class="card product-modal-summary-card">
                  <div class="card-body">
                    <div class="text-muted small">Total Diskon</div>
                    <h5 class="mb-0" id="modalTotalDiscount">Rp 0</h5>
                  </div>
                </div>
              </div>

              <div class="col-md-3">
                <div class="card product-modal-summary-card">
                  <div class="card-body">
                    <div class="text-muted small">Total Produk</div>
                    <h5 class="mb-0" id="modalTotalPrice">Rp 0</h5>
                  </div>
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-sm table-nowrap mb-0 ecommerce-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Diskon</th>
                    <th class="text-end">Total</th>
                  </tr>
                </thead>
                <tbody id="modalOrderProductsTable">
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                      Belum ada data produk.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div id="modalOrderProductsError" class="alert alert-danger d-none mb-0">
            Gagal memuat data produk.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button"
                  class="btn btn-outline-secondary"
                  data-bs-dismiss="modal">
            <i class="mdi mdi-close me-1"></i> Tutup
          </button>
        </div>
      </div>
    </div>
  </div>

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

  function formatRupiah(value) {
    return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
  }

  function formatNumber(value) {
    return Number(value || 0).toLocaleString('id-ID');
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function resetProductModal() {
    $('#modalOrderProductsSubtitle').text('-');
    $('#modalTotalItem').text('0');
    $('#modalTotalQty').text('0');
    $('#modalTotalDiscount').text('Rp 0');
    $('#modalTotalPrice').text('Rp 0');

    $('#modalOrderProductsTable').html(`
      <tr>
        <td colspan="7" class="text-center text-muted py-4">
          Belum ada data produk.
        </td>
      </tr>
    `);

    $('#modalOrderProductsError')
      .addClass('d-none')
      .text('Gagal memuat data produk.');
  }

  function showProductLoading(isLoading) {
    if (isLoading) {
      $('#modalOrderProductsLoading').removeClass('d-none');
      $('#modalOrderProductsContent').addClass('d-none');
      $('#modalOrderProductsError').addClass('d-none');
    } else {
      $('#modalOrderProductsLoading').addClass('d-none');
      $('#modalOrderProductsContent').removeClass('d-none');
    }
  }

  function loadOrderProducts(url) {
    resetProductModal();
    showProductLoading(true);

    const modalEl = document.getElementById('modalOrderProducts');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    $.ajax({
      url: url,
      method: 'GET',
      dataType: 'json',
      success: function (response) {
        showProductLoading(false);

        if (!response || !response.success) {
          $('#modalOrderProductsError')
            .removeClass('d-none')
            .text(response?.message || 'Gagal memuat data produk.');
          return;
        }

        const order = response.order || {};
        const summary = response.summary || {};
        const products = response.products || [];

        $('#modalOrderProductsSubtitle').text(
          'Order: ' + (order.order_number || '-') +
          ' | Customer: ' + (order.customer_name || '-') +
          ' | Status: ' + (order.order_status || '-')
        );

        $('#modalTotalItem').text(formatNumber(summary.total_item || 0));
        $('#modalTotalQty').text(formatNumber(summary.total_qty || 0));
        $('#modalTotalDiscount').text(formatRupiah(summary.total_discount || 0));
        $('#modalTotalPrice').text(formatRupiah(summary.total_price || 0));

        if (!products.length) {
          $('#modalOrderProductsTable').html(`
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                Tidak ada data produk.
              </td>
            </tr>
          `);
          return;
        }

        let html = '';

        products.forEach(function (item, index) {
          html += `
            <tr>
              <td>${index + 1}</td>
              <td>${escapeHtml(item.product_code || '-')}</td>
              <td>${escapeHtml(item.product_name || '-')}</td>
              <td class="text-end">${formatNumber(item.quantity || 0)}</td>
              <td class="text-end">${formatRupiah(item.unit_price || 0)}</td>
              <td class="text-end">${formatRupiah(item.total_discount || 0)}</td>
              <td class="text-end fw-bold amount-success">${formatRupiah(item.total_price || 0)}</td>
            </tr>
          `;
        });

        $('#modalOrderProductsTable').html(html);
      },
      error: function () {
        showProductLoading(false);
        $('#modalOrderProductsError')
          .removeClass('d-none')
          .text('Gagal memuat data produk.');
      }
    });
  }

  function initChartOmzetDaily() {
    const canvas = document.getElementById('chartOmzetDaily');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const chartOmzetDaily = @json($chartOmzetDailyData);

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: chartOmzetDaily.map(item => item.tanggal),
        datasets: [
          {
            label: 'Order',
            data: chartOmzetDaily.map(item => item.total_order),
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
            label: 'Omset',
            data: chartOmzetDaily.map(item => item.total_omset),
            borderColor: chartColors.success,
            backgroundColor: 'rgba(34, 197, 94, .12)',
            borderWidth: 3,
            tension: .35,
            pointRadius: 4,
            pointHoverRadius: 6,
            yAxisID: 'yAmount'
          },
          {
            type: 'line',
            label: 'Paid',
            data: chartOmzetDaily.map(item => item.total_paid),
            borderColor: chartColors.info,
            backgroundColor: 'rgba(6, 182, 212, .12)',
            borderWidth: 3,
            tension: .35,
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
              pointStyle: 'circle'
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
                if (context.dataset.label === 'Order') {
                  return 'Order: ' + formatNumber(context.raw);
                }

                return context.dataset.label + ': ' + formatRupiah(context.raw);
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false
            }
          },
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0,
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
              callback: function (value) {
                return formatRupiah(value);
              }
            }
          }
        }
      }
    });
  }

  function initChartPaymentStatus() {
    const canvas = document.getElementById('chartPaymentStatus');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const chartPaymentStatus = @json($chartPaymentStatusData);

    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: chartPaymentStatus.map(item => item.label),
        datasets: [{
          data: chartPaymentStatus.map(item => item.total_order),
          backgroundColor: [
            chartColors.success,
            chartColors.warning,
            chartColors.danger,
            chartColors.info,
            chartColors.primary,
            chartColors.purple
          ],
          borderWidth: 2,
          borderColor: '#ffffff',
          hoverOffset: 6
        }]
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
              pointStyle: 'circle'
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
                return context.label + ': ' + formatNumber(context.raw) + ' order';
              }
            }
          }
        }
      }
    });
  }

  function initChartPaymentMethod() {
    const canvas = document.getElementById('chartPaymentMethod');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const chartPaymentMethod = @json($chartPaymentMethodData);

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: chartPaymentMethod.map(item => item.label),
        datasets: [{
          label: 'Omset',
          data: chartPaymentMethod.map(item => item.total_omset),
          backgroundColor: chartColors.primary,
          borderColor: chartColors.primary,
          borderWidth: 1,
          borderRadius: 10,
          borderSkipped: false,
          maxBarThickness: 42
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: chartColors.dark,
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return 'Omset: ' + formatRupiah(context.raw);
              }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return formatRupiah(value);
              }
            }
          },
          y: {
            grid: {
              display: false
            }
          }
        }
      }
    });
  }

  $(document).ready(function () {
    initFilterSelect2();
    initChartOmzetDaily();
    initChartPaymentStatus();
    initChartPaymentMethod();

    $(document).on('click', '.js-show-products', function () {
      const url = $(this).data('url');

      if (!url) {
        return;
      }

      loadOrderProducts(url);
    });

    const ttNodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    ttNodes.forEach(el => new bootstrap.Tooltip(el));
  });
})(window.jQuery);
</script>
@endpush
