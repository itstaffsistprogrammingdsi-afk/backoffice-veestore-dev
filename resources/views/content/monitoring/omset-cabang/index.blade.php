@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Omset Cabang')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --oc-primary: #5b5cf6;
    --oc-primary-dark: #4338ca;
    --oc-primary-soft: rgba(91, 92, 246, .12);
    --oc-success: #22c55e;
    --oc-success-soft: rgba(34, 197, 94, .14);
    --oc-info: #06b6d4;
    --oc-info-soft: rgba(6, 182, 212, .14);
    --oc-warning: #f59e0b;
    --oc-warning-soft: rgba(245, 158, 11, .16);
    --oc-danger: #ef4444;
    --oc-danger-soft: rgba(239, 68, 68, .14);
    --oc-purple: #8b5cf6;
    --oc-dark: #1e293b;
    --oc-muted: #64748b;
    --oc-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--oc-body);
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
    border-color: var(--oc-primary);
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
    border-color: var(--oc-primary);
    background: linear-gradient(135deg, var(--oc-primary), var(--oc-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--oc-success);
    background: linear-gradient(135deg, var(--oc-success), #16a34a);
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
    background: linear-gradient(90deg, var(--oc-primary), var(--oc-success));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--oc-dark);
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
    color: #fff;
    font-size: 25px;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--oc-primary), var(--oc-primary-dark));
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

  .chart-wrapper {
    position: relative;
    height: 360px;
    min-height: 360px;
    width: 100%;
  }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table thead th,
  .table tbody td {
    vertical-align: middle;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
  }

  .omset-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .omset-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--oc-muted);
  }

  .source-badge {
    display: inline-block;
    padding: .35rem .65rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 800;
    background: #f1f5f9;
    color: #475569;
  }

  .source-badge.store {
    background: var(--oc-primary-soft);
    color: var(--oc-primary-dark);
  }

  .source-badge.ecommerce {
    background: var(--oc-success-soft);
    color: #15803d;
  }

  .text-wrap-min {
    min-width: 190px;
    white-space: normal !important;
  }

  .text-wrap-order {
    min-width: 240px;
    white-space: normal !important;
  }

  .cell-muted {
    color: #94a3b8;
    font-weight: 700;
  }

  .amount-success {
    color: #15803d;
    font-weight: 800;
  }

  .amount-info {
    color: #0369a1;
    font-weight: 800;
  }

  @media (max-width: 767.98px) {
    .chart-wrapper {
      height: 300px;
      min-height: 300px;
    }

    .dashboard-hero {
      border-radius: 1rem;
    }

    .card-body {
      padding: 1rem;
    }
  }
</style>
@endpush

@php
  $chartLabels = collect($chartRows ?? [])->map(fn ($row) => $row->store_name ?? '-')->values();
  $chartEcommerce = collect($chartRows ?? [])->map(fn ($row) => (float) ($row->total_omset_kotor_ecommerce ?? 0))->values();
  $chartStore = collect($chartRows ?? [])->map(fn ($row) => (float) ($row->total_omset_kotor_store ?? 0))->values();
  $chartBase = collect($chartRows ?? [])->map(fn ($row) => (float) ($row->total_harga_base ?? 0))->values();
  $chartQty = collect($chartRows ?? [])->map(fn ($row) => (float) ($row->total_barang_keluar ?? 0))->values();

  $summary = $summary ?? (object) [
    'total_cabang' => 0,
    'total_produk' => 0,
    'total_customer' => 0,
    'total_store_order' => 0,
    'total_barang_diambil' => 0,
    'total_barang_keluar' => 0,
    'total_barang_diterima' => 0,
    'total_sisa_belum_keluar' => 0,
    'total_harga_order_store' => 0,
    'total_harga_base' => 0,
    'total_omset_kotor_store' => 0,
    'total_omset_kotor_ecommerce' => 0,
  ];

  $totalCabang = (float) ($summary->total_cabang ?? 0);
  $totalProduk = (float) ($summary->total_produk ?? 0);
  $totalCustomer = (float) ($summary->total_customer ?? 0);
  $totalOrder = (float) ($summary->total_store_order ?? 0);
  $totalBarangDiambil = (float) ($summary->total_barang_diambil ?? 0);
  $totalBarangKeluar = (float) ($summary->total_barang_keluar ?? 0);
  $totalBarangDiterima = (float) ($summary->total_barang_diterima ?? 0);
  $totalSisa = (float) ($summary->total_sisa_belum_keluar ?? 0);
  $totalHargaBase = (float) ($summary->total_harga_base ?? 0);
  $totalOmsetStore = (float) ($summary->total_omset_kotor_store ?? 0);
  $totalOmsetEcommerce = (float) ($summary->total_omset_kotor_ecommerce ?? 0);

  $hasFilter = request()->hasAny([
    'store_id',
    'product_id',
    'date_from',
    'date_to',
    'per_page',
    'q',
  ]);
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Omset Cabang
        </h4>
        <div class="hero-subtitle">
          Omset Store dihitung dari transaksi POS yang sudah dibayar. Penjualan Ecommerce menampilkan asal pengambilan barang dari Store atau Warehouse tanpa menambah omzet Store.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-calendar-range-outline me-1"></i>
          {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-store-outline me-1"></i>
          {{ number_format($totalCabang, 0, ',', '.') }} Cabang
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-cash-multiple me-1"></i>
          Rp {{ number_format($totalOmsetStore + $totalOmsetEcommerce, 0, ',', '.') }}
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
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Omset Cabang</h5>
        <small class="text-muted">
          Default menampilkan data hari ini. Pilih Ecommerce untuk melihat seluruh penjualan Ecommerce dan sumber fulfillment-nya.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#filterOmsetCabang">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.omset-cabang.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.omset-cabang.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel me-1"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="filterOmsetCabang">
        <div class="filter-box">
          <form method="GET" action="{{ route('monitoring.omset-cabang.index') }}" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Cabang / Sumber Penjualan</label>
              <select name="store_id" class="form-select js-select2" data-placeholder="Semua Cabang & Ecommerce">
                <option value=""></option>
                <option value="__ecommerce__" @selected($storeId === '__ecommerce__')>Ecommerce</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected((string) $storeId === (string) $store->id)>
                    {{ $store->name }} @if($store->code) - {{ $store->code }} @endif
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Produk</label>
              <select name="product_id" class="form-select js-select2" data-placeholder="Semua Produk">
                <option value=""></option>
                @foreach($products as $product)
                  <option value="{{ $product->id }}" @selected((string) $productId === (string) $product->id)>
                    {{ $product->name }} @if($product->code) - {{ $product->code }} @endif
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Dari</label>
              <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Sampai</label>
              <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label">Show</label>
              <select name="per_page" class="form-select js-select2">
                @foreach([10,25,50,100,200] as $size)
                  <option value="{{ $size }}" @selected((int) $perPage === $size)>
                    {{ $size }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-10">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q }}"
                     placeholder="Cari cabang, customer, kode cabang, produk, kode produk, nomor order, atau sumber order">
            </div>

            <div class="col-md-2 d-flex align-items-end">
              <button class="btn btn-primary w-100">
                <i class="mdi mdi-magnify me-1"></i> Tampilkan
              </button>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">
              <a href="{{ route('monitoring.omset-cabang.index') }}"
                 class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-refresh me-1"></i> Reset
              </a>
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
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="text-muted small">Total Cabang</div>
            <h4 class="mb-1">{{ number_format($totalCabang, 0, ',', '.') }}</h4>
            <small class="text-muted">Cabang aktif transaksi</small>
          </div>
          <div class="summary-icon">
            <i class="mdi mdi-store-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="text-muted small">Total Customer</div>
            <h4 class="mb-1">{{ number_format($totalCustomer, 0, ',', '.') }}</h4>
            <small class="text-muted">Customer unik</small>
          </div>
          <div class="summary-icon info">
            <i class="mdi mdi-account-group-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="text-muted small">Barang Keluar</div>
            <h4 class="mb-1">{{ number_format($totalBarangKeluar, 0, ',', '.') }}</h4>
            <small class="text-muted">Total qty keluar</small>
          </div>
          <div class="summary-icon warning">
            <i class="mdi mdi-package-variant-closed"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="text-muted small">Total Harga Base</div>
            <h4 class="mb-1">Rp {{ number_format($totalHargaBase, 0, ',', '.') }}</h4>
            <small class="text-muted">Modal dasar</small>
          </div>
          <div class="summary-icon danger">
            <i class="mdi mdi-cash-lock-open-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="text-muted small">Omset Store</div>
            <h4 class="mb-1">Rp {{ number_format($totalOmsetStore, 0, ',', '.') }}</h4>
            <small class="text-muted">Store order</small>
          </div>
          <div class="summary-icon success">
            <i class="mdi mdi-cash-register"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="text-muted small">Omset Ecommerce</div>
            <h4 class="mb-1">Rp {{ number_format($totalOmsetEcommerce, 0, ',', '.') }}</h4>
            <small class="text-muted">Ecommerce order</small>
          </div>
          <div class="summary-icon info">
            <i class="mdi mdi-web"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="text-muted small">Total Omset</div>
            <h4 class="mb-1">Rp {{ number_format($totalOmsetStore + $totalOmsetEcommerce, 0, ',', '.') }}</h4>
            <small class="text-muted">Store + Ecommerce</small>
          </div>
          <div class="summary-icon success">
            <i class="mdi mdi-cash-multiple"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="text-muted small">Sisa Belum Keluar</div>
            <h4 class="mb-1">{{ number_format($totalSisa, 0, ',', '.') }}</h4>
            <small class="text-muted">Qty belum keluar</small>
          </div>
          <div class="summary-icon warning">
            <i class="mdi mdi-alert-circle-outline"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Grafik Omset Tertinggi per Cabang</h5>
        <small class="text-muted">
          Omset penjualan dipisahkan antara Store POS dan Ecommerce; penjualan Ecommerce tidak menjadi omzet Store fulfillment.
        </small>
      </div>

      <div class="text-muted small">
        Top {{ count($chartLabels) }} Cabang
      </div>
    </div>

    <div class="card-body">
      @if(count($chartLabels))
        <div class="chart-wrapper">
          <canvas id="omsetCabangChart"></canvas>
        </div>
      @else
        <div class="text-center text-muted py-5">
          Tidak ada data grafik hari ini.
        </div>
      @endif
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Laporan Omset Cabang</h5>
        <small class="text-muted">
          Omset Store hanya dari transaksi POS berbayar. Kolom fulfillment menjelaskan barang Ecommerce keluar dari lokasi mana.
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
        <table class="table table-bordered table-sm table-nowrap mb-0 omset-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Cabang / Kanal</th>
              <th>Sumber Penjualan</th>
              <th>Store Pengambilan Ecommerce</th>
              <th>Warehouse Pengambilan Ecommerce</th>
              <th>Customer</th>
              <th>Kode Produk</th>
              <th>Produk</th>
              <th>No Order</th>
              <th class="text-end">Qty Terjual</th>
              <th class="text-end">Keluar Store untuk Ecommerce</th>
              <th class="text-end">Keluar Warehouse untuk Ecommerce</th>
              <th class="text-end">Harga Awal</th>
              <th class="text-end">Diskon Produk</th>
              <th class="text-end">Voucher / Promo</th>
              <th class="text-end">Harga Akhir</th>
              <th class="text-end">Omset Store POS</th>
              <th class="text-end">Omset Ecommerce</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              @php
                $source = strtolower((string) ($row->order_source ?? ''));
                $isEcommerce = str_contains($source, 'ecommerce');
                $isStore = str_contains($source, 'store');
                $sourceClass = $isEcommerce ? 'ecommerce' : ($isStore ? 'store' : '');
              @endphp

              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>
                <td>{{ $row->store_name ?? '-' }}</td>

                <td>
                  <span class="source-badge {{ $sourceClass }}">
                    {{ $row->order_source ?? '-' }}
                  </span>
                </td>

                <td class="text-wrap-min">{{ $row->fulfillment_store_names ?: '-' }}</td>
                <td class="text-wrap-min">{{ $row->fulfillment_warehouse_names ?: '-' }}</td>

                <td class="text-wrap-min">
                  {{ $row->customer_name ?: '-' }}
                </td>

                <td>{{ $row->product_code ?? '-' }}</td>

                <td class="text-wrap-min">
                  {{ $row->product_name ?? '-' }}
                </td>

                <td class="text-wrap-order">
                  {{ $row->order_number ?: '-' }}
                </td>

                <td class="text-end">
                  {{ number_format((float) ($row->total_barang_diterima ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  {{ number_format((float) ($row->total_barang_keluar_store_ecommerce ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  {{ number_format((float) ($row->total_barang_keluar_warehouse_ecommerce ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->total_harga_awal ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end text-danger">
                  Rp {{ number_format((float) ($row->total_diskon_produk ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end text-danger">
                  Rp {{ number_format((float) ($row->total_voucher_promo ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end amount-info">
                  Rp {{ number_format((float) ($row->total_harga_akhir ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end">
                  Rp {{ number_format((float) ($row->total_omset_kotor_store ?? 0), 0, ',', '.') }}
                </td>

                <td class="text-end amount-success">
                  Rp {{ number_format((float) ($row->total_omset_kotor_ecommerce ?? 0), 0, ',', '.') }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="18" class="text-center text-muted py-4">
                  Tidak ada penjualan Store POS atau Ecommerce pada filter ini.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($rows->hasPages())
        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="pagination-info">
            Menampilkan <b>{{ $rows->firstItem() ?? 0 }}</b>
            sampai <b>{{ $rows->lastItem() ?? 0 }}</b>
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

  function initSelect2() {
    $('.js-select2').each(function () {
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

  function initOmsetCabangChart() {
    const canvas = document.getElementById('omsetCabangChart');

    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = @json($chartLabels);
    const ecommerce = @json($chartEcommerce);
    const store = @json($chartStore);
    const base = @json($chartBase);
    const qty = @json($chartQty);

    if (!labels.length) {
      return;
    }

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Omset Ecommerce',
            data: ecommerce,
            backgroundColor: '#22c55e',
            borderColor: '#22c55e',
            borderWidth: 1,
            borderRadius: 10,
            maxBarThickness: 44
          },
          {
            label: 'Omset Store',
            data: store,
            backgroundColor: '#5b5cf6',
            borderColor: '#5b5cf6',
            borderWidth: 1,
            borderRadius: 10,
            maxBarThickness: 44
          },
          {
            label: 'Total Harga Base',
            data: base,
            backgroundColor: '#06b6d4',
            borderColor: '#06b6d4',
            borderWidth: 1,
            borderRadius: 10,
            maxBarThickness: 44
          },
          {
            type: 'line',
            label: 'Qty Keluar',
            data: qty,
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, .12)',
            borderWidth: 3,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
            yAxisID: 'yQty'
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
            backgroundColor: '#0f172a',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                if (context.dataset.label === 'Qty Keluar') {
                  return context.dataset.label + ': ' + formatNumber(context.raw);
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
              callback: function (value) {
                return formatRupiah(value);
              }
            }
          },
          yQty: {
            beginAtZero: true,
            position: 'right',
            grid: {
              drawOnChartArea: false
            },
            ticks: {
              callback: function (value) {
                return formatNumber(value);
              }
            }
          }
        }
      }
    });
  }

  $(document).ready(function () {
    initSelect2();
    initOmsetCabangChart();
  });
})(window.jQuery);
</script>
@endpush
