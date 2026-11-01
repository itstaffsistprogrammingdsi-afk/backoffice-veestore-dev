@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Pemasukan Realtime')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --ir-primary: #5b5cf6;
    --ir-primary-dark: #4338ca;
    --ir-primary-soft: rgba(91, 92, 246, .12);
    --ir-success: #22c55e;
    --ir-success-soft: rgba(34, 197, 94, .14);
    --ir-info: #06b6d4;
    --ir-info-soft: rgba(6, 182, 212, .14);
    --ir-warning: #f59e0b;
    --ir-warning-soft: rgba(245, 158, 11, .16);
    --ir-danger: #ef4444;
    --ir-danger-soft: rgba(239, 68, 68, .14);
    --ir-purple: #8b5cf6;
    --ir-dark: #1e293b;
    --ir-muted: #64748b;
    --ir-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--ir-body);
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
    border-color: var(--ir-primary);
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
    border-color: var(--ir-primary);
    background: linear-gradient(135deg, var(--ir-primary), var(--ir-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
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
    background: linear-gradient(90deg, var(--ir-primary), var(--ir-success));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--ir-dark);
    font-weight: 800;
    letter-spacing: -.02em;
  }

  .summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--ir-primary), var(--ir-primary-dark));
    color: #fff;
    font-size: 25px;
    flex-shrink: 0;
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

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .income-table thead th,
  .method-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .income-table tbody tr:hover,
  .method-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .amount-success {
    color: #15803d !important;
    font-weight: 800;
  }

  .badge-soft-success {
    background: var(--ir-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--ir-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--ir-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-secondary,
  .badge-soft-info {
    background: var(--ir-info-soft) !important;
    color: #0369a1 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--ir-muted);
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
  $summary = $summary ?? (object) [
    'total_transactions' => 0,
    'total_stores' => 0,
    'total_customers' => 0,
    'total_amount' => 0,
    'total_paid' => 0,
    'total_change' => 0,
  ];

  $totalTransactions = (float) ($summary->total_transactions ?? 0);
  $totalStores = (float) ($summary->total_stores ?? 0);
  $totalCustomers = (float) ($summary->total_customers ?? 0);
  $totalAmount = (float) ($summary->total_amount ?? 0);
  $totalPaid = (float) ($summary->total_paid ?? 0);
  $totalChange = (float) ($summary->total_change ?? 0);

  $hasFilter = request()->hasAny([
    'keyword',
    'store_id',
    'payment_method_id',
    'payment_status',
    'start_date',
    'end_date',
    'per_page',
    'auto_refresh',
  ]);

  $statusBadge = function ($status) {
    return match($status) {
      'paid' => 'badge-soft-success',
      'pending' => 'badge-soft-warning',
      'failed' => 'badge-soft-danger',
      'cancelled' => 'badge-soft-secondary',
      default => 'badge-soft-secondary',
    };
  };
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Pemasukan Realtime
        </h4>
        <div class="hero-subtitle">
          Pantau pemasukan realtime berdasarkan metode pembayaran, store, transaksi, customer, dan nominal pembayaran.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <span class="live-dot me-1"></span>
          Live Income
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-cash-multiple me-1"></i>
          Rp {{ number_format($totalAmount, 0, ',', '.') }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-calendar-range-outline me-1"></i>
          {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ now()->format('d-m-Y H:i:s') }}
        </span>

        @if($autoRefresh)
          <span class="hero-badge">
            <i class="mdi mdi-autorenew me-1"></i>
            Auto Refresh Aktif
          </span>
        @endif
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Pemasukan Realtime</h5>
        <small class="text-muted">
          Default menampilkan transaksi paid hari ini. Ubah filter untuk melihat periode, store, atau metode tertentu.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="submit"
                form="payment-income-realtime-filter"
                formaction="{{ route('monitoring.payment-income-realtime.export') }}"
                class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </button>

        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#filterIncomeRealtime">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.payment-income-realtime.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="filterIncomeRealtime">
        <div class="filter-box">
          <form method="GET"
                id="payment-income-realtime-filter"
                action="{{ route('monitoring.payment-income-realtime.index') }}"
                class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Keyword</label>
              <input type="text"
                     name="keyword"
                     class="form-control"
                     value="{{ $keyword ?? '' }}"
                     placeholder="Store / Customer / Txn / Source">
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

            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="payment_status"
                      class="form-select js-select2-filter"
                      data-placeholder="Semua Status">
                <option value="">Semua Status</option>
                <option value="paid" @selected($paymentStatus === 'paid')>Paid</option>
                <option value="pending" @selected($paymentStatus === 'pending')>Pending</option>
                <option value="failed" @selected($paymentStatus === 'failed')>Failed</option>
                <option value="cancelled" @selected($paymentStatus === 'cancelled')>Cancelled</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Tanggal Mulai</label>
              <input type="date"
                     name="start_date"
                     class="form-control"
                     value="{{ $startDate }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Tanggal Selesai</label>
              <input type="date"
                     name="end_date"
                     class="form-control"
                     value="{{ $endDate }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select">
                @foreach([10,25,50,100,200] as $size)
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

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.payment-income-realtime.index') }}"
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
              <div class="text-muted small">Total Pemasukan</div>
              <h4 class="mb-0">Rp {{ number_format($totalAmount, 0, ',', '.') }}</h4>
              <small class="text-muted">Berdasarkan status filter</small>
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
              <div class="text-muted small">Total Transaksi</div>
              <h4 class="mb-0">{{ number_format($totalTransactions, 0, ',', '.') }}</h4>
              <small class="text-muted">Jumlah transaksi</small>
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
              <div class="text-muted small">Total Paid</div>
              <h4 class="mb-0">Rp {{ number_format($totalPaid, 0, ',', '.') }}</h4>
              <small class="text-muted">Nominal dibayar</small>
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
              <h4 class="mb-0">Rp {{ number_format($totalChange, 0, ',', '.') }}</h4>
              <small class="text-muted">Change amount</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-cash-refund"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Grafik Pemasukan per Jam</h5>
          <small class="text-muted">Menampilkan pemasukan dan jumlah transaksi berdasarkan jam.</small>
        </div>
        <div class="card-body">
          @if(count($hourLabels))
            <div class="chart-wrapper">
              <canvas id="hourIncomeChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Belum ada data grafik.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Komposisi Metode</h5>
          <small class="text-muted">Metode pembayaran yang menghasilkan pemasukan.</small>
        </div>
        <div class="card-body">
          @if(count($methodLabels))
            <div class="chart-wrapper-sm">
              <canvas id="methodIncomeChart"></canvas>
            </div>
          @else
            <div class="text-center text-muted py-5">Belum ada data metode.</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- METHOD SUMMARY --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-1 fw-bold">Pemasukan Berdasarkan Metode Pembayaran</h5>
      <small class="text-muted">Ringkasan nominal masuk per metode pembayaran.</small>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm table-nowrap mb-0 method-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Metode Pembayaran</th>
              <th class="text-end">Jumlah Transaksi</th>
              <th class="text-end">Total Pemasukan</th>
              <th>Pertama Masuk</th>
              <th>Terakhir Masuk</th>
            </tr>
          </thead>
          <tbody>
            @forelse($methodSummaries as $index => $method)
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                  <span class="badge badge-soft-info">{{ $method->payment_method_name ?? '-' }}</span>
                </td>
                <td class="text-end">{{ number_format((int) ($method->total_transactions ?? 0), 0, ',', '.') }}</td>
                <td class="text-end fw-bold amount-success">
                  Rp {{ number_format((float) ($method->total_amount ?? 0), 0, ',', '.') }}
                </td>
                <td>{{ $method->first_used_at ? \Carbon\Carbon::parse($method->first_used_at)->format('d-m-Y H:i') : '-' }}</td>
                <td>{{ $method->last_used_at ? \Carbon\Carbon::parse($method->last_used_at)->format('d-m-Y H:i') : '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Belum ada data metode pembayaran.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- TRANSACTION TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Detail Transaksi Pemasukan</h5>
        <small class="text-muted">
          Total data: <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
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
        <table class="table table-bordered table-striped table-sm table-nowrap mb-0 income-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Waktu Masuk</th>
              <th>Store</th>
              <th>Customer</th>
              <th>Visit</th>
              <th>Transaction No</th>
              <th>Metode</th>
              <th>Status</th>
              <th class="text-end">Total</th>
              <th class="text-end">Paid</th>
              <th class="text-end">Change</th>
              <th>Source</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $row)
              @php
                $badgeClass = $statusBadge($row->payment_status);
              @endphp

              <tr>
                <td>{{ $rows->firstItem() + $loop->index }}</td>

                <td>
                  {{ $row->income_at ? \Carbon\Carbon::parse($row->income_at)->format('d-m-Y H:i:s') : '-' }}
                </td>

                <td>
                  <div class="fw-semibold">{{ $row->store_name ?? '-' }}</div>
                  <small class="text-muted">{{ $row->store_code ?? '-' }}</small>
                </td>

                <td>{{ $row->customer_name ?? 'Guest / Non Member' }}</td>

                <td>
                  <span class="badge badge-soft-info">{{ $row->visit_code ?? '-' }}</span>
                </td>

                <td class="fw-semibold">{{ $row->transaction_number ?? '-' }}</td>

                <td>{{ $row->payment_methods ?? '-' }}</td>

                <td>
                  <span class="badge {{ $badgeClass }}">
                    {{ $row->payment_status ? ucfirst($row->payment_status) : '-' }}
                  </span>
                </td>

                <td class="text-end fw-bold amount-success">
                  Rp {{ number_format((float) ($row->total_amount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->paid_amount ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->change_amount ?? 0), 0, ',', '.') }}
                </td>

                <td>
                  <span class="badge badge-soft-secondary">{{ $row->payment_source ?? '-' }}</span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="12" class="text-center text-muted py-4">
                  Belum ada data pemasukan.
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
  'use strict';

  const autoRefresh = @json($autoRefresh);

  const chartColors = {
    primary: '#5b5cf6',
    success: '#22c55e',
    info: '#06b6d4',
    warning: '#f59e0b',
    danger: '#ef4444',
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

  function initHourIncomeChart() {
    const canvas = document.getElementById('hourIncomeChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($hourLabels);
    const amounts = @json($hourAmounts);
    const counts = @json($hourCounts);

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
            label: 'Pemasukan',
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
            position: 'bottom'
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                if (context.dataset.label === 'Pemasukan') {
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

  function initMethodIncomeChart() {
    const canvas = document.getElementById('methodIncomeChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($methodLabels);
    const amounts = @json($methodAmounts);

    if (!labels.length) {
      return;
    }

    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: amounts,
          backgroundColor: [
            chartColors.primary,
            chartColors.success,
            chartColors.info,
            chartColors.warning,
            chartColors.danger,
            '#8b5cf6',
            '#14b8a6',
            '#64748b'
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
            position: 'bottom'
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return context.label + ': ' + formatRupiah(context.raw);
              }
            }
          }
        }
      }
    });
  }

  $(document).ready(function () {
    initFilterSelect2();
    initHourIncomeChart();
    initMethodIncomeChart();

    if (autoRefresh) {
      setInterval(function () {
        window.location.reload();
      }, 60000);
    }
  });
})(window.jQuery);
</script>
@endpush
