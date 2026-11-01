@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Cashier Shift Report Detail')

@push('page-style')
<style>
  :root {
    --cs-primary: #5b5cf6;
    --cs-primary-dark: #4338ca;
    --cs-primary-soft: rgba(91, 92, 246, .12);
    --cs-success: #22c55e;
    --cs-success-soft: rgba(34, 197, 94, .14);
    --cs-info: #06b6d4;
    --cs-info-soft: rgba(6, 182, 212, .14);
    --cs-warning: #f59e0b;
    --cs-warning-soft: rgba(245, 158, 11, .16);
    --cs-danger: #ef4444;
    --cs-danger-soft: rgba(239, 68, 68, .14);
    --cs-purple: #8b5cf6;
    --cs-dark: #1e293b;
    --cs-muted: #64748b;
    --cs-border: #e5e7eb;
    --cs-body: #f6f7fb;
    --cs-card: #ffffff;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--cs-body);
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

  .btn-success {
    border-color: var(--cs-success);
    background: linear-gradient(135deg, var(--cs-success), #16a34a);
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
    background: var(--cs-card);
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--cs-primary), var(--cs-success));
  }

  .summary-card::after {
    content: "";
    position: absolute;
    width: 90px;
    height: 90px;
    right: -36px;
    bottom: -36px;
    border-radius: 50%;
    background: var(--cs-primary-soft);
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--cs-dark);
    font-weight: 800;
    letter-spacing: -.02em;
  }

  .summary-card .text-muted {
    color: var(--cs-muted) !important;
  }

  .summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--cs-primary), var(--cs-primary-dark));
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

  .info-box {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    height: 100%;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
  }

  .info-label {
    color: var(--cs-muted);
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .035em;
    margin-bottom: .35rem;
  }

  .info-value {
    color: var(--cs-dark);
    font-weight: 700;
    word-break: break-word;
  }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table {
    margin-bottom: 0;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .transaction-table thead th,
  .item-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .transaction-table tbody td,
  .item-table tbody td {
    color: #475569;
  }

  .transaction-table tbody tr:hover,
  .item-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .customer-col,
  .note-col,
  .product-name-col {
    min-width: 240px;
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
    background: var(--cs-success-soft) !important;
    color: #15803d !important;
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

  .badge-soft-warning {
    background: var(--cs-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--cs-info-soft) !important;
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

    .card-body {
      padding: 1rem;
    }
  }
</style>
@endpush

@section('content')
@php
  $totalSales = (float) ($session->total_sales ?? 0);
  $totalTransactions = (float) ($session->total_transactions ?? 0);
  $openingCash = (float) ($session->opening_cash ?? 0);
  $expectedCash = (float) ($session->expected_cash ?? 0);
  $actualCash = (float) ($session->actual_cash ?? 0);
  $difference = (float) ($session->difference ?? 0);

  $statusClass = match(strtolower((string) ($session->status ?? ''))) {
    'open' => 'badge-soft-success',
    'closed' => 'badge-soft-secondary',
    default => 'badge-soft-warning',
  };

  $paymentStatusClass = function ($status) {
    return match(strtolower((string) ($status ?? ''))) {
      'paid', 'success', 'completed', 'settlement' => 'badge-soft-success',
      'pending', 'waiting', 'process' => 'badge-soft-warning',
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
          <span class="fw-light">Monitoring /</span> Cashier Shift Report Detail
        </h4>
        <div class="hero-subtitle">
          Detail sesi kasir, transaksi customer, total sales, kas sistem, kas fisik, dan rincian pembelian.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-store-outline me-1"></i>
          {{ $session->store_name ?? '-' }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-account-outline me-1"></i>
          {{ $session->cashier_name ?? '-' }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ now()->format('d-m-Y H:i:s') }}
        </span>
      </div>
    </div>
  </div>

  {{-- ACTION --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Informasi Cashier Session</h5>
        <small class="text-muted">
          Session Number: <b>{{ $session->session_number ?? '-' }}</b>
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('monitoring.cashier-shift-report.detail-export', ['cashierSession' => $session->id] + request()->query()) }}"
           class="btn btn-sm btn-success text-nowrap">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Detail Excel
        </a>

        <a href="{{ route('monitoring.cashier-shift-report.index', request()->query()) }}"
           class="btn btn-sm btn-outline-secondary text-nowrap">
          <i class="mdi mdi-arrow-left me-1"></i> Kembali
        </a>
      </div>
    </div>

    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <div class="info-box">
            <div class="info-label">Store</div>
            <div class="info-value">
              <i class="mdi mdi-store-outline text-primary me-1"></i>
              {{ $session->store_name ?? '-' }}
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <div class="info-label">Kasir</div>
            <div class="info-value">
              <i class="mdi mdi-account-outline text-info me-1"></i>
              {{ $session->cashier_name ?? '-' }}
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <div class="info-label">Session Number</div>
            <div class="info-value">{{ $session->session_number ?? '-' }}</div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <div class="info-label">Status</div>
            <div class="info-value">
              <span class="badge {{ $statusClass }}">
                {{ $session->status ? ucfirst($session->status) : '-' }}
              </span>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <div class="info-label">Opened At</div>
            <div class="info-value">
              {{ $session->opened_at ? \Carbon\Carbon::parse($session->opened_at)->format('d-m-Y H:i:s') : '-' }}
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <div class="info-label">Closed At</div>
            <div class="info-value">
              {{ $session->closed_at ? \Carbon\Carbon::parse($session->closed_at)->format('d-m-Y H:i:s') : '-' }}
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="info-box">
            <div class="info-label">Durasi Session</div>
            <div class="info-value">
              @if($session->opened_at && $session->closed_at)
                {{ \Carbon\Carbon::parse($session->opened_at)->diffForHumans(\Carbon\Carbon::parse($session->closed_at), true) }}
              @elseif($session->opened_at && !$session->closed_at)
                Masih berjalan sejak {{ \Carbon\Carbon::parse($session->opened_at)->format('d-m-Y H:i') }}
              @else
                -
              @endif
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="info-box">
            <div class="info-label">Note Open</div>
            <div class="info-value">{{ $session->note_open ?: '-' }}</div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="info-box">
            <div class="info-label">Note Close</div>
            <div class="info-value">{{ $session->note_close ?: '-' }}</div>
          </div>
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
              <div class="text-muted small">Total Sales</div>
              <h4 class="mb-0">Rp {{ number_format($totalSales, 0, ',', '.') }}</h4>
              <small class="text-muted">Omset pada session ini</small>
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
              <div class="text-muted small">Total Transactions</div>
              <h4 class="mb-0">{{ number_format($totalTransactions, 0, ',', '.') }}</h4>
              <small class="text-muted">Jumlah transaksi</small>
            </div>
            <div class="summary-icon info">
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
              <div class="text-muted small">Opening Cash</div>
              <h4 class="mb-0">Rp {{ number_format($openingCash, 0, ',', '.') }}</h4>
              <small class="text-muted">Saldo awal kasir</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-cash-register"></i>
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
              <div class="text-muted small">Difference</div>
              <h4 class="mb-0 {{ $difference != 0 ? 'amount-danger' : 'amount-success' }}">
                Rp {{ number_format($difference, 0, ',', '.') }}
              </h4>
              <small class="text-muted">Selisih kas</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-alert-circle-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-6">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Expected Cash / System Cash</div>
              <h4 class="mb-0">Rp {{ number_format($expectedCash, 0, ',', '.') }}</h4>
              <small class="text-muted">Kas seharusnya menurut sistem</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-calculator-variant-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-6">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Actual Cash / Physical Cash</div>
              <h4 class="mb-0">Rp {{ number_format($actualCash, 0, ',', '.') }}</h4>
              <small class="text-muted">Kas fisik saat closing</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-wallet-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TRANSACTION TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Detail Semua Transaksi Kasir</h5>
        <small class="text-muted">
          Daftar transaksi pada session kasir ini beserta customer, status pembayaran, total amount, paid, dan kembalian.
        </small>
      </div>

      <div class="text-muted small">
        Total transaksi: <b>{{ number_format($transactions->count(), 0, ',', '.') }}</b>
      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm table-nowrap mb-0 transaction-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Transaction Number</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Source</th>
              <th class="text-end">Total Amount</th>
              <th class="text-end">Tax</th>
              <th class="text-end">Paid</th>
              <th class="text-end">Change Amount</th>
              <th>Completed At</th>
              <th>Catatan</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>

          <tbody>
            @forelse($transactions as $trx)
              @php
                $trxStatusClass = $paymentStatusClass($trx->payment_status);
              @endphp

              <tr>
                <td>{{ $loop->iteration }}</td>

                <td class="fw-semibold">
                  {{ $trx->transaction_number ?: '-' }}
                </td>

                <td class="customer-col">
                  <div class="fw-semibold">{{ $trx->customer_name ?: 'Guest / Non Member' }}</div>
                  <div class="text-muted small">Payment ID: {{ $trx->payment_id ?? '-' }}</div>
                </td>

                <td>
                  <span class="badge {{ $trxStatusClass }}">
                    {{ $trx->payment_status ? ucfirst(str_replace('_', ' ', $trx->payment_status)) : '-' }}
                  </span>
                </td>

                <td>
                  <span class="badge badge-soft-info">
                    {{ $trx->source ?: '-' }}
                  </span>
                </td>

                <td class="text-end fw-bold amount-success">
                  Rp {{ number_format((float) ($trx->total_amount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($trx->tax_amount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($trx->paid_amount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($trx->change_amount ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  {{ $trx->completed_at ? \Carbon\Carbon::parse($trx->completed_at)->format('d-m-Y H:i:s') : '-' }}
                </td>

                <td class="note-col">
                  {{ $trx->payment_note ?: ($trx->store_payment_note ?: '-') }}
                </td>

                <td class="text-center">
                  <button type="button"
                          class="btn btn-sm btn-outline-primary btn-detail-items text-nowrap"
                          data-url="{{ route('monitoring.cashier-shift-report.transaction-items', $trx->payment_id) }}"
                          data-transaction="{{ $trx->transaction_number ?: '-' }}"
                          data-customer="{{ $trx->customer_name ?: 'Guest / Non Member' }}">
                    <i class="mdi mdi-format-list-bulleted-square me-1"></i>
                    Detail Pembelian
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="12" class="text-center text-muted py-4">
                  Belum ada transaksi pada session ini.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- MODAL DETAIL PEMBELIAN --}}
<div class="modal fade" id="modalDetailPembelian" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0 text-white">Detail Pembelian Customer</h5>
          <small class="text-muted text-nowrap" id="modal_transaction_info">-</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div id="modal_loading" class="text-center py-4 d-none">
          <div class="spinner-border spinner-border-sm me-2" role="status"></div>
          Memuat detail pembelian...
        </div>

        <div id="modal_error" class="alert alert-danger d-none"></div>

        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <div class="info-box">
              <div class="info-label">Transaction Number</div>
              <div class="info-value" id="trx_number">-</div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="info-box">
              <div class="info-label">Customer</div>
              <div class="info-value" id="trx_customer">-</div>
            </div>
          </div>

          <div class="col-md-2">
            <div class="info-box">
              <div class="info-label">Status</div>
              <div class="info-value" id="trx_status">-</div>
            </div>
          </div>

          <div class="col-md-2">
            <div class="info-box">
              <div class="info-label">Paid</div>
              <div class="info-value" id="trx_paid">0</div>
            </div>
          </div>

          <div class="col-md-2">
            <div class="info-box">
              <div class="info-label">Change</div>
              <div class="info-value" id="trx_change">0</div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm table-nowrap mb-0 item-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Kode Produk</th>
                <th>Nama Produk</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Harga</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>

            <tbody id="modal_items_tbody">
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  Belum ada data.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="mdi mdi-close me-1"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('modalDetailPembelian');
  const modal = new bootstrap.Modal(modalEl);

  const loading = document.getElementById('modal_loading');
  const errorBox = document.getElementById('modal_error');
  const tbody = document.getElementById('modal_items_tbody');

  const info = document.getElementById('modal_transaction_info');
  const trxNumber = document.getElementById('trx_number');
  const trxCustomer = document.getElementById('trx_customer');
  const trxStatus = document.getElementById('trx_status');
  const trxPaid = document.getElementById('trx_paid');
  const trxChange = document.getElementById('trx_change');

  function formatNumber(value) {
    const number = Number(value || 0);
    return new Intl.NumberFormat('id-ID').format(number);
  }

  function formatRupiah(value) {
    return 'Rp ' + formatNumber(value || 0);
  }

  function escapeHtml(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function resetModal(transactionNumber, customerName) {
    info.textContent = transactionNumber + ' / ' + customerName;
    trxNumber.textContent = '-';
    trxCustomer.textContent = '-';
    trxStatus.textContent = '-';
    trxPaid.textContent = 'Rp 0';
    trxChange.textContent = 'Rp 0';

    loading.classList.remove('d-none');
    errorBox.classList.add('d-none');
    errorBox.textContent = '';

    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-muted py-4">Memuat data...</td>
      </tr>
    `;
  }

  document.querySelectorAll('.btn-detail-items').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      const url = btn.getAttribute('data-url');
      const transactionNumber = btn.getAttribute('data-transaction') || '-';
      const customerName = btn.getAttribute('data-customer') || '-';

      resetModal(transactionNumber, customerName);
      modal.show();

      try {
        const response = await fetch(url, {
          headers: {
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error('Gagal memuat detail pembelian.');
        }

        const json = await response.json();
        const transaction = json.transaction || null;
        const items = json.items || [];

        trxNumber.textContent = transaction?.transaction_number || '-';
        trxCustomer.textContent = transaction?.customer_name || customerName || '-';
        trxStatus.innerHTML = transaction?.status
          ? `<span class="badge badge-soft-success">${escapeHtml(transaction.status)}</span>`
          : '-';
        trxPaid.textContent = formatRupiah(transaction?.paid_amount || 0);
        trxChange.textContent = formatRupiah(transaction?.change_amount || 0);

        if (!items.length) {
          tbody.innerHTML = `
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Belum ada rincian item.</td>
            </tr>
          `;
        } else {
          tbody.innerHTML = items.map(function (item, index) {
            return `
              <tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(item.product_code || '-')}</td>
                <td class="product-name-col">${escapeHtml(item.product_name || '-')}</td>
                <td class="text-end">${formatNumber(item.qty || 0)}</td>
                <td class="text-end">${formatRupiah(item.price || 0)}</td>
                <td class="text-end fw-bold amount-success">${formatRupiah(item.subtotal || 0)}</td>
              </tr>
            `;
          }).join('');
        }
      } catch (error) {
        errorBox.textContent = error.message || 'Terjadi kesalahan.';
        errorBox.classList.remove('d-none');

        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td>
          </tr>
        `;
      } finally {
        loading.classList.add('d-none');
      }
    });
  });
});
</script>
@endpush