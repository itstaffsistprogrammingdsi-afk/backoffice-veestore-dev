@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Promo Usage')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --mp-primary: #5b5cf6;
    --mp-primary-dark: #4338ca;
    --mp-primary-soft: rgba(91, 92, 246, .12);
    --mp-success: #22c55e;
    --mp-success-soft: rgba(34, 197, 94, .14);
    --mp-info: #06b6d4;
    --mp-info-soft: rgba(6, 182, 212, .14);
    --mp-warning: #f59e0b;
    --mp-warning-soft: rgba(245, 158, 11, .16);
    --mp-danger: #ef4444;
    --mp-danger-soft: rgba(239, 68, 68, .14);
    --mp-purple: #8b5cf6;
    --mp-dark: #1e293b;
    --mp-muted: #64748b;
    --mp-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--mp-body);
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
    border-color: var(--mp-primary);
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
    border-color: var(--mp-primary);
    background: linear-gradient(135deg, var(--mp-primary), var(--mp-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--mp-success);
    background: linear-gradient(135deg, var(--mp-success), #16a34a);
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
    background: linear-gradient(90deg, var(--mp-primary), var(--mp-success));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--mp-dark);
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
    background: linear-gradient(135deg, var(--mp-primary), var(--mp-primary-dark));
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

  .promo-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .promo-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--mp-muted);
  }

  .customer-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .product-col {
    min-width: 260px;
    white-space: normal !important;
  }

  .voucher-col {
    min-width: 230px;
    white-space: normal !important;
  }

  .badge-soft-success {
    background: var(--mp-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--mp-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--mp-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--mp-info-soft) !important;
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

  .period-alert {
    border: 0;
    border-radius: 1.1rem;
    background: linear-gradient(135deg, rgba(6, 182, 212, .12), rgba(91, 92, 246, .10));
    color: #0f172a;
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
    'total_usage' => 0,
    'total_order' => 0,
    'total_qty' => 0,
    'total_product_discount' => 0,
    'total_discount' => 0,
    'total_discount_usage' => 0,
    'total_checkout_voucher' => 0,
    'total_shipping_voucher' => 0,
  ];

  $totalUsage = (int) ($summary->total_usage ?? 0);
  $totalOrder = (int) ($summary->total_order ?? 0);
  $totalQty = (int) ($summary->total_qty ?? 0);
  $totalProductDiscount = (float) ($summary->total_product_discount ?? 0);
  $totalDiscount = (float) ($summary->total_discount ?? 0);
  $totalDiscountUsage = (int) ($summary->total_discount_usage ?? 0);
  $totalCheckoutVoucher = (int) ($summary->total_checkout_voucher ?? 0);
  $totalShippingVoucher = (int) ($summary->total_shipping_voucher ?? 0);

  $hasFilter = request()->hasAny([
    'q',
    'from',
    'to',
    'store_id',
    'promo_id',
    'discount_id',
    'voucher_id',
    'order_status',
    'per_page',
  ]);

  $statusBadge = function ($status) {
    $status = strtolower((string) $status);

    return match($status) {
      'paid', 'success', 'completed', 'complete', 'done', 'delivered', 'settled' => 'badge-soft-success',
      'pending', 'waiting', 'process', 'processing', 'created', 'new' => 'badge-soft-warning',
      'cancel', 'canceled', 'cancelled', 'failed', 'expired', 'reject', 'rejected' => 'badge-soft-danger',
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
          <span class="fw-light">Monitoring /</span> Promo Usage
        </h4>
        <div class="hero-subtitle">
          Monitoring penggunaan promo, diskon produk, voucher checkout, voucher shipping, customer, produk, dan status order.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-ticket-percent-outline me-1"></i>
          {{ number_format($totalUsage, 0, ',', '.') }} Usage
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-cart-outline me-1"></i>
          {{ number_format($totalOrder, 0, ',', '.') }} Order
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-sale-outline me-1"></i>
          Rp {{ number_format($totalDiscount, 0, ',', '.') }}
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
        <h5 class="mb-1 fw-bold">Filter Monitoring Promo Usage</h5>
        <small class="text-muted">
          Gunakan filter untuk mencari penggunaan promo berdasarkan periode, store, promo, diskon, voucher, status order, dan keyword.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
          <button type="button"
                  class="btn btn-outline-secondary btn-sm"
                  data-bs-toggle="collapse"
                  data-bs-target="#advancedFilterPromoUsage">
            <i class="mdi mdi-filter-variant me-1"></i> Filter
          </button>

          <a href="{{ route('monitoring.promo-usage.index') }}"
             class="btn btn-outline-secondary btn-sm">
            <i class="mdi mdi-refresh me-1"></i> Reset
          </a>

          <a href="{{ route('monitoring.promo-usage.export', request()->query()) }}"
             class="btn btn-success btn-sm">
            <i class="mdi mdi-file-excel-outline me-1"></i> Export
          </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterPromoUsage">
        <div class="filter-box">
          <form method="GET" action="{{ route('monitoring.promo-usage.index') }}" class="row g-3">

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
              <label class="form-label">Promo</label>
              <select name="promo_id" class="form-select js-select2-filter" data-placeholder="Semua Promo">
                <option value="">Semua Promo</option>
                @foreach($promos as $promo)
                  <option value="{{ $promo->id }}" @selected((string) $promoId === (string) $promo->id)>
                    {{ $promo->name }}{{ $promo->code ? ' (' . $promo->code . ')' : '' }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Diskon</label>
              <select name="discount_id" class="form-select js-select2-filter" data-placeholder="Semua Diskon">
                <option value="">Semua Diskon</option>
                @foreach($discounts as $discount)
                  <option value="{{ $discount->id }}" @selected((string) $discountId === (string) $discount->id)>
                    {{ $discount->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Voucher</label>
              <select name="voucher_id" class="form-select js-select2-filter" data-placeholder="Semua Voucher">
                <option value="">Semua Voucher</option>
                @foreach($vouchers as $voucher)
                  <option value="{{ $voucher->id }}" @selected((string) $voucherId === (string) $voucher->id)>
                    {{ $voucher->name }}{{ $voucher->code ? ' (' . $voucher->code . ')' : '' }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Status Order</label>
              <select name="order_status" class="form-select js-select2-filter" data-placeholder="Semua Status">
                <option value="">Semua Status</option>
                @foreach($orderStatuses as $status)
                  <option value="{{ $status }}" @selected((string) $orderStatus === (string) $status)>
                    {{ ucfirst($status) }}
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

            <div class="col-md-6">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q }}"
                     placeholder="Order / customer / email / produk / diskon / promo / voucher">
            </div>

            <div class="col-md-6">
              <label class="form-label">&nbsp;</label>
              <div class="text-muted small pt-2">
                Summary dan tabel akan mengikuti filter periode, store, promo, diskon, voucher, status order, dan pencarian.
              </div>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.promo-usage.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.promo-usage.index') }}"
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
      </div>

      <div class="small text-muted">
        Data ditampilkan berdasarkan tanggal order dibuat.
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
              <div class="text-muted small">Total Usage</div>
              <h4 class="mb-1">{{ number_format($totalUsage, 0, ',', '.') }}</h4>
              <small class="text-muted">Baris penggunaan promo</small>
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
              <div class="text-muted small">Total Order</div>
              <h4 class="mb-1">{{ number_format($totalOrder, 0, ',', '.') }}</h4>
              <small class="text-muted">Order unik yang memakai promo</small>
            </div>
            <div class="summary-icon success">
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
              <h4 class="mb-1">{{ number_format($totalQty, 0, ',', '.') }}</h4>
              <small class="text-muted">Qty produk terlibat</small>
            </div>
            <div class="summary-icon info">
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
              <div class="text-muted small">Total Diskon</div>
              <h4 class="mb-1">Rp {{ number_format($totalDiscount, 0, ',', '.') }}</h4>
              <small class="text-muted">Akumulasi diskon detail</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-sale-outline"></i>
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
              <div class="text-muted small">Diskon Produk</div>
              <h4 class="mb-1">Rp {{ number_format($totalProductDiscount, 0, ',', '.') }}</h4>
              <small class="text-muted">Akumulasi product discount</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-tag-minus-outline"></i>
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
              <div class="text-muted small">Usage Diskon</div>
              <h4 class="mb-1">{{ number_format($totalDiscountUsage, 0, ',', '.') }}</h4>
              <small class="text-muted">Menggunakan discount snapshot</small>
            </div>
            <div class="summary-icon purple">
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
              <div class="text-muted small">Voucher Checkout</div>
              <h4 class="mb-1">{{ number_format($totalCheckoutVoucher, 0, ',', '.') }}</h4>
              <small class="text-muted">Order dengan voucher checkout</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-ticket-confirmation-outline"></i>
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
              <div class="text-muted small">Voucher Shipping</div>
              <h4 class="mb-1">{{ number_format($totalShippingVoucher, 0, ',', '.') }}</h4>
              <small class="text-muted">Order dengan voucher shipping</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-truck-fast-outline"></i>
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
        <h5 class="mb-1 fw-bold">Laporan Penggunaan Promo & Voucher</h5>
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
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap promo-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal Pakai</th>
              <th>Order Number</th>
              <th>Customer</th>
              <th>Email</th>
              <th>WA Number</th>
              <th>Store Promo</th>
              <th>Diskon</th>
              <th>Promo</th>
              <th>Kode Promo</th>
              <th>Voucher Checkout</th>
              <th>Voucher Shipping</th>
              <th>Produk</th>
              <th>Kode Produk</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Harga Produk</th>
              <th class="text-end">Diskon Produk</th>
              <th class="text-end">Total Diskon</th>
              <th class="text-end">Grand Total Order</th>
              <th>Status Order</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td class="text-nowrap">
                  {{ $row->order_created_at ? \Carbon\Carbon::parse($row->order_created_at)->format('d M Y H:i') : '-' }}
                </td>

                <td>
                  <span class="fw-semibold">{{ $row->order_number ?? '-' }}</span>
                </td>

                <td class="customer-col">
                  <div class="fw-semibold">{{ $row->customer_name ?? '-' }}</div>
                </td>

                <td>{{ $row->email ?? '-' }}</td>

                <td>{{ $row->wa_number ?? '-' }}</td>

                <td>{{ $row->store_name ?? '-' }}</td>

                <td>
                  @if($row->discount_name)
                    <span class="badge badge-soft-info">{{ $row->discount_name }}</span>
                  @else
                    <span class="badge badge-soft-secondary">-</span>
                  @endif
                </td>

                <td>{{ $row->promo_name ?? '-' }}</td>

                <td>
                  @if($row->promo_code)
                    <span class="badge badge-soft-success">{{ $row->promo_code }}</span>
                  @else
                    <span class="badge badge-soft-secondary">-</span>
                  @endif
                </td>

                <td class="voucher-col">
                  {{ $row->checkout_voucher_label ?? '-' }}
                </td>

                <td class="voucher-col">
                  {{ $row->shipping_voucher_label ?? '-' }}
                </td>

                <td class="product-col">
                  <div class="fw-semibold">{{ $row->product_name ?? '-' }}</div>
                </td>

                <td>{{ $row->product_code ?? '-' }}</td>

                <td class="text-end">
                  {{ number_format((int) ($row->quantity ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->product_price ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end amount-danger">
                  Rp {{ number_format((float) ($row->product_discount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end amount-danger">
                  Rp {{ number_format((float) ($row->total_discount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end amount-success">
                  Rp {{ number_format((float) ($row->grand_total ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  <span class="badge {{ $statusBadge($row->order_status ?? '-') }}">
                    {{ ucfirst($row->order_status ?? '-') }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="20" class="text-center text-muted py-4">
                  Belum ada data promo usage.
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
