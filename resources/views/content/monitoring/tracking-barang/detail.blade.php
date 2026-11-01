@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Detail Tracking Barang')

@push('page-style')
<style>
  :root {
    --dt-primary: #5b5cf6;
    --dt-primary-dark: #4338ca;
    --dt-success: #22c55e;
    --dt-success-soft: rgba(34, 197, 94, .14);
    --dt-info: #06b6d4;
    --dt-info-soft: rgba(6, 182, 212, .14);
    --dt-warning: #f59e0b;
    --dt-warning-soft: rgba(245, 158, 11, .16);
    --dt-danger: #ef4444;
    --dt-danger-soft: rgba(239, 68, 68, .14);
    --dt-purple: #8b5cf6;
    --dt-dark: #1e293b;
    --dt-muted: #64748b;
    --dt-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(6, 182, 212, .10), transparent 28%),
      var(--dt-body);
  }

  .dashboard-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    color: #fff;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
      linear-gradient(135deg, #4f46e5 0%, #06b6d4 58%, #22c55e 120%);
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

  .form-control,
  .form-select {
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: var(--dt-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .btn {
    border-radius: .75rem;
    font-weight: 600;
  }

  .btn-primary {
    border-color: var(--dt-primary);
    background: linear-gradient(135deg, var(--dt-primary), var(--dt-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--dt-success);
    background: linear-gradient(135deg, var(--dt-success), #16a34a);
  }

  .detail-product-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(226, 232, 240, .85);
  }

  .detail-product-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--dt-primary), var(--dt-info));
  }

  .filter-panel {
    background: linear-gradient(180deg, rgba(248, 250, 252, .98), rgba(255,255,255,.98));
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
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
    background: linear-gradient(90deg, var(--dt-primary), var(--dt-info));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--dt-dark);
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
    background: linear-gradient(135deg, var(--dt-primary), var(--dt-primary-dark));
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

  .accordion-item {
    border: 1px solid #e2e8f0 !important;
    border-radius: 1.1rem !important;
    overflow: hidden;
    box-shadow: 0 .35rem 1rem rgba(15, 23, 42, .04);
  }

  .accordion-button {
    gap: .75rem;
    background: linear-gradient(180deg, #fff, #f8fafc);
    color: var(--dt-dark);
    font-weight: 700;
    box-shadow: none !important;
  }

  .accordion-button:not(.collapsed) {
    color: var(--dt-primary-dark);
    background: linear-gradient(180deg, #eef2ff, #fff);
  }

  .accordion-button::after {
    flex-shrink: 0;
  }

  .accordion-body {
    background: #fff;
  }

  .item-info-box,
  .location-box,
  .qr-box {
    height: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    background: linear-gradient(180deg, #fff, #f8fafc);
  }

  .item-info-box h6,
  .location-box h6,
  .qr-box h6 {
    font-weight: 800;
    color: var(--dt-dark);
  }

  .info-row {
    display: flex;
    gap: .75rem;
    justify-content: space-between;
    padding: .45rem 0;
    border-bottom: 1px dashed #e2e8f0;
  }

  .info-row:last-child {
    border-bottom: 0;
  }

  .info-label {
    color: var(--dt-muted);
    font-size: .8rem;
    flex: 0 0 42%;
  }

  .info-value {
    color: var(--dt-dark);
    font-weight: 700;
    text-align: right;
    word-break: break-word;
  }

  .qr-box {
    border-style: dashed;
    text-align: center;
  }

  .qr-code-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: .75rem;
    box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .06);
  }

  .qr-value {
    word-break: break-all;
    font-size: 0.875rem;
    line-height: 1.4;
  }

  .location-path {
    color: var(--dt-dark);
    font-weight: 800;
    line-height: 1.4;
  }

  .location-meta {
    color: var(--dt-muted);
    font-size: .82rem;
  }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table td,
  .table th {
    vertical-align: middle;
  }

  .tracking-timeline-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .tracking-timeline-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--dt-muted);
  }

  .badge-tracking {
    font-size: .75rem;
    padding: .45rem .7rem;
    border-radius: 999px;
    font-weight: 800;
  }

  .badge-soft-success {
    background: var(--dt-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--dt-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--dt-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--dt-info-soft) !important;
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

  .detail-empty-state {
    border: 1px dashed #cbd5e1;
    border-radius: 1.1rem;
    padding: 3rem 1rem;
    background: #f8fafc;
  }

  .item-title {
    min-width: 0;
  }

  .item-title .item-rfid {
    word-break: break-all;
  }

  @media (max-width: 767.98px) {
    .dashboard-hero {
      border-radius: 1rem;
    }

    .card-body {
      padding: 1rem;
    }

    .card-header {
      padding: 1rem;
    }

    .detail-filter-form {
      width: 100%;
    }

    .detail-filter-form .col-md-4,
    .detail-filter-form .col-md-2,
    .detail-filter-form .col-md-auto {
      width: 100%;
    }

    .info-row {
      flex-direction: column;
      gap: .15rem;
    }

    .info-value {
      text-align: left;
    }
  }
</style>
@endpush

@section('content')
@php
  $pageRows = method_exists($rows, 'getCollection') ? $rows->getCollection() : collect($rows);

  $totalItems = method_exists($rows, 'total') ? (int) $rows->total() : (int) $pageRows->count();

  $soldCount = (int) $pageRows->filter(function ($row) {
    return ($row->tracking_status_code ?? '') === 'SOLD';
  })->count();

  $warehouseCount = (int) $pageRows->filter(function ($row) {
    return in_array(($row->tracking_status_code ?? ''), [
      'INBOUND_WAREHOUSE',
      'IN_WAREHOUSE',
      'STOCK_WAREHOUSE',
      'OUTBOUND_WAREHOUSE',
    ], true);
  })->count();

  $storeCount = (int) $pageRows->filter(function ($row) {
    return in_array(($row->tracking_status_code ?? ''), [
      'INBOUND_STORE',
      'STOCK_STORE',
      'DISPLAY_STORE',
    ], true);
  })->count();

  $rfidCount = (int) $pageRows->filter(function ($row) {
    return !empty($row->rfid_value);
  })->count();

  $statusBadge = function ($code) {
    return match($code) {
      'INBOUND_WAREHOUSE' => 'badge-soft-primary',
      'IN_WAREHOUSE' => 'badge-soft-warning',
      'STOCK_WAREHOUSE' => 'badge-soft-secondary',
      'OUTBOUND_WAREHOUSE' => 'badge-soft-secondary',
      'INBOUND_STORE' => 'badge-soft-info',
      'STOCK_STORE' => 'badge-soft-primary',
      'DISPLAY_STORE' => 'badge-soft-warning',
      'SOLD' => 'badge-soft-success',
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
          <span class="fw-light">Monitoring / Tracking Barang /</span> Detail
        </h4>
        <div class="hero-subtitle">
          Detail tracking item, RFID, lokasi terkini, histori lokasi, QR code, dan timeline pergerakan barang.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-package-variant-closed me-1"></i>
          {{ number_format($totalItems, 0, ',', '.') }} Item
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-barcode-scan me-1"></i>
          {{ number_format($rfidCount, 0, ',', '.') }} RFID
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-store-check-outline me-1"></i>
          {{ number_format($soldCount, 0, ',', '.') }} Sold
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ now()->format('d-m-Y H:i:s') }}
        </span>
      </div>
    </div>
  </div>

  {{-- HEADER PRODUCT + FILTER --}}
  <div class="card detail-product-card mb-4">
    <div class="card-body">
      <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-3">
        <div>
          <h5 class="mb-1 fw-bold">{{ $product->name }}</h5>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge badge-soft-primary">
              Product ID: {{ $product->id }}
            </span>

            @if(!empty($product->code))
              <span class="badge badge-soft-info">
                Code: {{ $product->code }}
              </span>
            @endif
          </div>
        </div>

        <form method="GET"
              action="{{ route('monitoring.tracking-barang.detail', $product->id) }}"
              class="row g-2 align-items-end detail-filter-form">

          <div class="col-md-4">
            <label class="form-label mb-1">Cari Item / RFID</label>
            <input
              type="text"
              name="item_id"
              value="{{ $itemId ?? '' }}"
              class="form-control form-control-sm"
              placeholder="Masukkan Item ID / RFID Value"
            >
          </div>

          <div class="col-md-2">
            <label class="form-label mb-1">Show</label>
            <select name="per_page" class="form-select form-select-sm">
              @foreach([10, 25, 50, 100] as $size)
                <option value="{{ $size }}" @selected((int) $perPage === $size)>
                  {{ $size }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-auto">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="mdi mdi-magnify me-1"></i> Cari
            </button>

            <a href="{{ route('monitoring.tracking-barang.detail', $product->id) }}"
               class="btn btn-outline-secondary btn-sm">
              <i class="mdi mdi-refresh me-1"></i> Reset
            </a>

            <a href="{{ route('monitoring.tracking-barang.index') }}"
               class="btn btn-outline-secondary btn-sm">
              <i class="mdi mdi-arrow-left me-1"></i> Kembali
            </a>
          </div>
        </form>
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
              <div class="text-muted small">Total Item</div>
              <h4 class="mb-1">{{ number_format($totalItems, 0, ',', '.') }}</h4>
              <small class="text-muted">Item produk ini</small>
            </div>
            <div class="summary-icon">
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
              <div class="text-muted small">Warehouse Flow</div>
              <h4 class="mb-1">{{ number_format($warehouseCount, 0, ',', '.') }}</h4>
              <small class="text-muted">Item di area warehouse</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-warehouse"></i>
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
              <div class="text-muted small">Store Flow</div>
              <h4 class="mb-1">{{ number_format($storeCount, 0, ',', '.') }}</h4>
              <small class="text-muted">Item di area store</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-store-outline"></i>
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
              <div class="text-muted small">Sold</div>
              <h4 class="mb-1">{{ number_format($soldCount, 0, ',', '.') }}</h4>
              <small class="text-muted">Item sudah terjual</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-cart-check"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- DETAIL LIST --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Detail Item Tracking</h5>
        <small class="text-muted">Tracking setiap item untuk produk ini.</small>
      </div>

      <div class="pagination-info">
        Total Item: <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
      </div>
    </div>

    <div class="card-body">
      <div class="accordion" id="accordionTracking">
        @forelse($rows as $index => $row)
          @php
            $collapseId = 'item-' . $index;
            $badgeClass = $statusBadge($row->tracking_status_code ?? '');
          @endphp

          <div class="accordion-item mb-3">
            <h2 class="accordion-header" id="heading-{{ $collapseId }}">
              <button
                class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#collapse-{{ $collapseId }}"
                aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                aria-controls="collapse-{{ $collapseId }}"
              >
                <div class="w-100 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 pe-3">
                  <div class="item-title">
                    <div class="fw-bold">
                      No: {{ $rows->firstItem() + $index }}
                      <span class="text-muted">|</span>
                      Item/RFID:
                      <span class="item-rfid">{{ $row->display_item_id ?? '-' }}</span>
                    </div>

                    <small class="text-muted d-block">
                      RFID Value: {{ $row->rfid_value ?? '-' }}
                      <span class="mx-1">|</span>
                      Condition: {{ $row->current_condition ?? '-' }}
                    </small>
                  </div>

                  <div class="text-md-end">
                    <span class="badge {{ $badgeClass }} badge-tracking">
                      {{ $row->tracking_status ?? '-' }}
                    </span>
                  </div>
                </div>
              </button>
            </h2>

            <div
              id="collapse-{{ $collapseId }}"
              class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
              aria-labelledby="heading-{{ $collapseId }}"
              data-bs-parent="#accordionTracking"
            >
              <div class="accordion-body">
                <div class="row g-3 mb-3">
                  {{-- ITEM INFO --}}
                  <div class="col-lg-4">
                    <div class="item-info-box">
                      <h6 class="mb-3">
                        <i class="mdi mdi-information-outline me-1"></i>
                        Informasi Item
                      </h6>

                      <div class="info-row">
                        <div class="info-label">Item ID</div>
                        <div class="info-value">{{ $row->item_id ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">Display Item</div>
                        <div class="info-value">{{ $row->display_item_id ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">Status Item</div>
                        <div class="info-value">{{ $row->item_status ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">Condition</div>
                        <div class="info-value">{{ $row->current_condition ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">RFID Value</div>
                        <div class="info-value">{{ $row->rfid_value ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">Tracking Saat Ini</div>
                        <div class="info-value">{{ $row->tracking_status ?? '-' }}</div>
                      </div>
                    </div>
                  </div>

                  {{-- LOCATION + CUSTOMER --}}
                  <div class="col-lg-4">
                    <div class="item-info-box">
                      <h6 class="mb-3">
                        <i class="mdi mdi-map-marker-path me-1"></i>
                        Lokasi & Penjualan
                      </h6>

                      <div class="info-row">
                        <div class="info-label">Warehouse ID</div>
                        <div class="info-value">{{ $row->warehouse_id ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">Store ID</div>
                        <div class="info-value">{{ $row->store_id ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">Cart ID</div>
                        <div class="info-value">{{ $row->cart_id ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">Product Price ID</div>
                        <div class="info-value">{{ $row->product_price_id ?? '-' }}</div>
                      </div>

                      <hr>

                      <div class="info-row">
                        <div class="info-label">Customer</div>
                        <div class="info-value">{{ $row->customer_name ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">Phone</div>
                        <div class="info-value">{{ $row->customer_phone ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ $row->customer_email ?? '-' }}</div>
                      </div>

                      <div class="info-row">
                        <div class="info-label">Sold At</div>
                        <div class="info-value">
                          {{ $row->sold_at ? \Carbon\Carbon::parse($row->sold_at)->format('d-m-Y H:i') : '-' }}
                        </div>
                      </div>
                    </div>
                  </div>

                  {{-- QR --}}
                  <div class="col-lg-4">
                    <div class="qr-box">
                      <h6 class="mb-3">
                        <i class="mdi mdi-qrcode me-1"></i>
                        QR Code RFID
                      </h6>

                      @if(!empty($row->rfid_value))
                        <div class="mb-3">
                          <div class="qr-code-wrap">
                            {!! QrCode::size(150)->margin(1)->generate($row->rfid_value) !!}
                          </div>
                        </div>

                        <div class="qr-value text-muted mb-3">
                          {{ $row->rfid_value }}
                        </div>

                        <a href="{{ route('monitoring.tracking-barang.qr-download', [
                            'product' => $product->id,
                            'item' => $row->item_id,
                          ]) }}"
                           class="btn btn-outline-primary btn-sm">
                          <i class="mdi mdi-download me-1"></i> Download QR
                        </a>
                      @else
                        <div class="text-muted py-4">
                          <i class="mdi mdi-qrcode-remove d-block mb-2" style="font-size: 2rem;"></i>
                          RFID belum tersedia
                        </div>
                      @endif
                    </div>
                  </div>
                </div>

                {{-- LOCATION BOX --}}
                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <div class="location-box">
                      <h6 class="mb-2">
                        <i class="mdi mdi-map-marker-radius-outline me-1"></i>
                        Current Location
                      </h6>

                      <div class="location-path">
                        {{ ($row->current_parent_location ? $row->current_parent_location . ' > ' : '') . ($row->current_location_name ?? '-') }}
                      </div>

                      <div class="location-meta mt-2">
                        Type: {{ $row->current_location_type ?? '-' }}
                        <span class="mx-1">|</span>
                        Code: {{ $row->current_location_code ?? '-' }}
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="location-box">
                      <h6 class="mb-2">
                        <i class="mdi mdi-map-clock-outline me-1"></i>
                        Last Location History
                      </h6>

                      <div class="location-path">
                        {{ ($row->last_history_parent_location ? $row->last_history_parent_location . ' > ' : '') . ($row->last_history_location_name ?? '-') }}
                      </div>

                      <div class="location-meta mt-2">
                        Type: {{ $row->last_history_location_type ?? '-' }}
                        <span class="mx-1">|</span>
                        Code: {{ $row->last_history_location_code ?? '-' }}
                        <span class="mx-1">|</span>
                        Moved At:
                        {{ $row->last_moved_at ? \Carbon\Carbon::parse($row->last_moved_at)->format('d-m-Y H:i') : '-' }}
                      </div>
                    </div>
                  </div>
                </div>

                {{-- TIMELINE --}}
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-sm tracking-timeline-table mb-0">
                    <thead>
                      <tr>
                        <th>Waktu</th>
                        <th>Event</th>
                        <th>Reference</th>
                        <th>Detail</th>
                      </tr>
                    </thead>

                    <tbody>
                      @forelse(($timelines[$row->item_id] ?? collect()) as $timeline)
                        <tr>
                          <td class="text-nowrap">
                            {{ $timeline->event_time ? \Carbon\Carbon::parse($timeline->event_time)->format('d-m-Y H:i') : '-' }}
                          </td>

                          <td class="text-nowrap">
                            <span class="badge badge-soft-info">
                              {{ $timeline->event_name ?? '-' }}
                            </span>
                          </td>

                          <td class="text-nowrap">{{ $timeline->reference_info ?? '-' }}</td>
                          <td>{{ $timeline->detail_info ?? '-' }}</td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="4" class="text-center text-muted py-4">
                            Belum ada timeline.
                          </td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="detail-empty-state text-center text-muted">
            <i class="mdi mdi-package-variant-remove d-block mb-2" style="font-size: 2.4rem;"></i>
            Tidak ada item untuk produk ini.
          </div>
        @endforelse
      </div>

      @if($rows->hasPages())
        <div class="mt-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
          <div class="pagination-info">
            Menampilkan
            <b>{{ $rows->firstItem() ?? 0 }}</b>
            sampai
            <b>{{ $rows->lastItem() ?? 0 }}</b>
            dari
            <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
            item
          </div>

          <div>
            {{ $rows->onEachSide(1)->links() }}
          </div>
        </div>
      @else
        <div class="mt-3 pagination-info">
          Menampilkan
          <b>{{ $rows->firstItem() ?? 0 }}</b>
          sampai
          <b>{{ $rows->lastItem() ?? 0 }}</b>
          dari
          <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
          item
        </div>
      @endif
    </div>
  </div>
</div>
@endsection