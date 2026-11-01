@extends('layouts/contentNavbarLayout')

@section('title', 'Supplier Products')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css">
@endpush

@push('page-style')
<style>
  :root {
    --sp-primary: #5b5cf6;
    --sp-info: #06b6d4;
    --sp-success: #22c55e;
    --sp-warning: #f59e0b;
    --sp-danger: #ef4444;
    --sp-dark: #1e293b;
    --sp-muted: #64748b;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91,92,246,.09), transparent 28%),
      radial-gradient(circle at top right, rgba(6,182,212,.08), transparent 26%),
      #f6f7fb;
  }

  .supplier-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.35rem;
    padding: 1.6rem;
    color: #fff;
    background:
      radial-gradient(circle at 12% 20%, rgba(255,255,255,.27), transparent 24%),
      linear-gradient(135deg, #4f46e5, #06b6d4 58%, #22c55e 120%);
    box-shadow: 0 1.25rem 2.5rem rgba(79,70,229,.20);
  }

  .supplier-hero::after {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: -70px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
  }

  .supplier-hero > * {
    position: relative;
    z-index: 2;
  }

  .hero-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: .55rem .8rem;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.22);
    color: #fff;
    font-size: .8rem;
    font-weight: 700;
  }

  .card {
    border: 0;
    border-radius: 1.15rem;
    box-shadow: 0 .5rem 1.5rem rgba(15,23,42,.06);
  }

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226,232,240,.9);
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto;
    height: 4px;
    background: linear-gradient(90deg, var(--sp-primary), var(--sp-info));
  }

  .summary-icon {
    width: 52px;
    height: 52px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    color: #fff;
    font-size: 26px;
    background: linear-gradient(135deg, var(--sp-primary), #8b5cf6);
  }

  .summary-icon.success { background: linear-gradient(135deg, #22c55e, #16a34a); }
  .summary-icon.info { background: linear-gradient(135deg, #06b6d4, #0284c7); }
  .summary-icon.warning { background: linear-gradient(135deg, #f59e0b, #f97316); }
  .summary-icon.danger { background: linear-gradient(135deg, #ec4899, #ef4444); }

  .filter-box {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.15rem;
    background: linear-gradient(180deg, #f8fafc, #fff);
  }

  .form-control,
  .form-select,
  .select2-container--bootstrap-5 .select2-selection {
    min-height: 40px;
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .select2-container { width: 100% !important; }

  .btn {
    border-radius: .75rem;
    font-weight: 700;
  }

  .chart-card {
    height: 100%;
    border: 1px solid rgba(226,232,240,.9);
  }

  .chart-wrap {
    position: relative;
    min-height: 320px;
  }

  .chart-wrap-sm {
    position: relative;
    min-height: 275px;
  }

  .insight-card {
    height: 100%;
    border: 1px solid #e2e8f0;
    background: linear-gradient(180deg, #fff, #f8fafc);
  }

  .insight-item {
    height: 100%;
    border: 1px solid #e2e8f0;
    border-radius: .95rem;
    padding: .9rem;
    background: #fff;
  }

  .supplier-table thead th {
    white-space: nowrap;
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-size: .76rem;
    font-weight: 800;
  }

  .supplier-table tbody tr:hover {
    background: rgba(91,92,246,.05);
  }

  .product-cell {
    min-width: 250px;
    white-space: normal;
  }

  .badge-stock {
    border-radius: 999px;
    padding: .42rem .68rem;
    font-weight: 700;
  }

  @media (max-width: 767.98px) {
    .supplier-hero { border-radius: 1rem; }
    .chart-wrap, .chart-wrap-sm { min-height: 260px; }
  }
</style>
@endpush

@section('content')
@php
  $summary = $summary ?? (object) [
    'total_relations' => 0,
    'total_suppliers' => 0,
    'total_products' => 0,
    'total_qty_inbound' => 0,
    'total_stock' => 0,
    'products_with_stock' => 0,
    'products_without_stock' => 0,
  ];

  $exportQuery = request()->except('page');
  $hasFilter = request()->hasAny([
    'supplier_id', 'product_id', 'q', 'date_from',
    'date_to', 'has_stock', 'per_page'
  ]);
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  <div class="supplier-hero mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1 text-white">
          <span class="fw-light">Reports /</span> Supplier Products
        </h4>
        <div style="color:rgba(255,255,255,.84)">
          Dashboard demografi supplier, produk, histori inbound, dan kondisi stok saat ini.
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <span class="hero-badge">
          <i class="mdi mdi-chart-box-outline me-1"></i> Management View
        </span>
        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          {{ now()->format('d-m-Y H:i:s') }}
        </span>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Supplier Products</h5>
        <small class="text-muted">Semua ringkasan, grafik, tabel, dan export mengikuti filter aktif.</small>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#supplierProductFilter">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>
        <a href="{{ route('reports.supplier-products.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>
        <a href="{{ route('reports.supplier-products.export', $exportQuery) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="supplierProductFilter">
        <div class="filter-box">
          <form method="GET"
                action="{{ route('reports.supplier-products.index') }}"
                class="row g-3">

            <div class="col-md-3">
              <label for="filterSupplier" class="form-label">Supplier</label>
              <select id="filterSupplier"
                      name="supplier_id"
                      class="form-select js-select2"
                      data-placeholder="Semua supplier">
                <option value=""></option>
                @foreach($suppliers as $supplier)
                  <option value="{{ $supplier->id }}"
                          @selected((string)$supplierId === (string)$supplier->id)>
                    {{ $supplier->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label for="filterProduct" class="form-label">Produk</label>
              <select id="filterProduct"
                      name="product_id"
                      class="form-select js-select2"
                      data-placeholder="Semua produk">
                <option value=""></option>
                @foreach($products as $product)
                  <option value="{{ $product->id }}"
                          @selected((string)$productId === (string)$product->id)>
                    {{ $product->name }}{{ $product->code ? ' ('.$product->code.')' : '' }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label for="filterKeyword" class="form-label">Pencarian</label>
              <input id="filterKeyword"
                     type="text"
                     name="q"
                     value="{{ $keyword }}"
                     class="form-control"
                     placeholder="Produk, kode, supplier, negara">
            </div>

            <div class="col-md-3">
              <label for="dateRange" class="form-label">Periode Penerimaan</label>
              <input id="dateRange"
                     type="text"
                     class="form-control"
                     placeholder="Mulai — Selesai"
                     autocomplete="off">
              <input type="hidden" name="date_from" id="date_from" value="{{ $dateFrom }}">
              <input type="hidden" name="date_to" id="date_to" value="{{ $dateTo }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Per Halaman</label>
              <select name="per_page" class="form-select">
                @foreach([10, 25, 50, 100, 200] as $page)
                  <option value="{{ $page }}" @selected((int)$perPage === $page)>
                    {{ $page }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">
              <div class="form-check mb-2">
                <input class="form-check-input"
                       type="checkbox"
                       value="1"
                       id="hasStock"
                       name="has_stock"
                       @checked($hasStock)>
                <label class="form-check-label" for="hasStock">
                  Hanya yang masih ada stok
                </label>
              </div>
            </div>

            <div class="col-12 d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="mdi mdi-magnify me-1"></i> Tampilkan
              </button>
              <a href="{{ route('reports.supplier-products.export', $exportQuery) }}"
                 class="btn btn-success btn-sm">
                <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
              </a>
              <a href="{{ route('reports.supplier-products.index') }}"
                 class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-refresh me-1"></i> Reset
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    @foreach([
      ['Total Supplier', $summary->total_suppliers, 'mdi-truck-outline', ''],
      ['Total Produk', $summary->total_products, 'mdi-package-variant-closed', 'info'],
      ['Relasi Supplier-Produk', $summary->total_relations, 'mdi-link-variant', 'warning'],
      ['Total Qty Inbound', $summary->total_qty_inbound, 'mdi-warehouse', 'success'],
      ['Stok Saat Ini', $summary->total_stock, 'mdi-package-check', 'success'],
      ['Produk Masih Stok', $summary->products_with_stock, 'mdi-check-circle-outline', 'info'],
      ['Produk Stok Habis', $summary->products_without_stock, 'mdi-alert-circle-outline', 'danger'],
    ] as [$label, $value, $icon, $class])
      <div class="col-sm-6 col-xl-3">
        <div class="card summary-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-3">
              <div>
                <div class="text-muted small">{{ $label }}</div>
                <h4 class="mb-0 fw-bold">{{ number_format((int)$value) }}</h4>
              </div>
              <div class="summary-icon {{ $class }}">
                <i class="mdi {{ $icon }}"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="card insight-card mb-4">
    <div class="card-header">
      <h5 class="mb-1 fw-bold">Insight Demografi Supplier Products</h5>
      <small class="text-muted">Ringkasan cepat untuk evaluasi supplier dan distribusi produk.</small>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="insight-item">
            <div class="text-muted small mb-1">Supplier dengan Produk Terbanyak</div>
            <h5 class="fw-bold mb-1">{{ $topSupplier->label ?? '-' }}</h5>
            <div class="text-muted small">
              {{ number_format((int)($topSupplier->total_products ?? 0)) }} produk
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="insight-item">
            <div class="text-muted small mb-1">Produk dengan Supplier Terbanyak</div>
            <h5 class="fw-bold mb-1">{{ $topProduct->label ?? '-' }}</h5>
            <div class="text-muted small">
              {{ number_format((int)($topProduct->total_suppliers ?? 0)) }} supplier
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="insight-item">
            <div class="text-muted small mb-1">Negara Supplier Dominan</div>
            <h5 class="fw-bold mb-1">{{ $topCountry->label ?? '-' }}</h5>
            <div class="text-muted small">
              {{ number_format((int)($topCountry->total ?? 0)) }} supplier
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Tren Qty Penerimaan</h5>
          <small class="text-muted">Per bulan berdasarkan histori inbound.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap"><canvas id="trendChart"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Status Stok Produk</h5>
          <small class="text-muted">Masih tersedia dibanding stok habis.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap"><canvas id="stockChart"></canvas></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-6">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Top Supplier</h5>
          <small class="text-muted">Supplier berdasarkan jumlah produk.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm"><canvas id="supplierChart"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-xl-6">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Top Produk</h5>
          <small class="text-muted">Produk berdasarkan jumlah supplier.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm"><canvas id="productChart"></canvas></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-12">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Demografi Negara Supplier</h5>
          <small class="text-muted">Distribusi supplier berdasarkan negara.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm"><canvas id="countryChart"></canvas></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-1 fw-bold">Data Supplier Products</h5>
        <small class="text-muted">
          Menampilkan {{ number_format($rows->firstItem() ?? 0) }}–{{ number_format($rows->lastItem() ?? 0) }}
          dari {{ number_format($rows->total()) }} data.
        </small>
      </div>
      <a href="{{ route('reports.supplier-products.export', $exportQuery) }}"
         class="btn btn-success btn-sm">
        <i class="mdi mdi-file-excel-outline me-1"></i> Export
      </a>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle supplier-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Supplier</th>
              <th>Negara</th>
              <th>Produk</th>
              <th class="text-end">Qty Inbound</th>
              <th class="text-end">Stok</th>
              <th>Terakhir Diterima</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $index => $row)
              <tr>
                <td>{{ ($rows->currentPage() - 1) * $rows->perPage() + $index + 1 }}</td>
                <td>
                  <div class="fw-bold">{{ $row->supplier_name }}</div>
                  <div class="text-muted small">{{ $row->supplier_phone ?: '-' }}</div>
                </td>
                <td>{{ $row->supplier_country ?: '-' }}</td>
                <td class="product-cell">
                  <div class="fw-semibold">{{ $row->product_name }}</div>
                  <div class="text-muted small">{{ $row->product_codes ?: '-' }}</div>
                </td>
                <td class="text-end">{{ number_format((int)$row->qty_inbound) }}</td>
                <td class="text-end">
                  @if((int)$row->stok_now > 0)
                    <span class="badge bg-label-success badge-stock">
                      {{ number_format((int)$row->stok_now) }}
                    </span>
                  @else
                    <span class="badge bg-label-danger badge-stock">Habis</span>
                  @endif
                </td>
                <td>
                  {{ $row->last_inbound_date
                    ? \Illuminate\Support\Carbon::parse($row->last_inbound_date)->format('d-m-Y')
                    : '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  <i class="mdi mdi-database-off-outline d-block mb-2" style="font-size:2.5rem"></i>
                  Tidak ada data supplier products.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3">
        {{ $rows->links() }}
      </div>
    </div>
  </div>

  @if($suppliersForProduct->isNotEmpty() && ($productId || $keyword))
    <div class="card">
      <div class="card-header">
        <h5 class="mb-1 fw-bold">Barang Ini Ada di Supplier Mana Saja?</h5>
        <small class="text-muted">Berdasarkan histori penerimaan barang.</small>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Produk</th>
              <th>Supplier</th>
              <th class="text-end">Total Qty Dibeli</th>
              <th>Terakhir Diterima</th>
            </tr>
          </thead>
          <tbody>
            @foreach($suppliersForProduct as $supplierProduct)
              <tr>
                <td>{{ $supplierProduct->product_name }}</td>
                <td>{{ $supplierProduct->supplier_name }}</td>
                <td class="text-end">{{ number_format((int)$supplierProduct->total_qty) }}</td>
                <td>
                  {{ $supplierProduct->last_inbound
                    ? \Illuminate\Support\Carbon::parse($supplierProduct->last_inbound)->format('d-m-Y')
                    : '-' }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  $('.js-select2').select2({
    theme: 'bootstrap-5',
    width: '100%',
    allowClear: true,
    placeholder: function () {
      return this.dataset.placeholder || 'Pilih';
    },
    language: {
      noResults: function () { return 'Tidak ada hasil'; },
      searching: function () { return 'Mencari…'; }
    }
  });

  const dateRange = document.getElementById('dateRange');
  const dateFrom = document.getElementById('date_from');
  const dateTo = document.getElementById('date_to');

  if (dateFrom.value && dateTo.value) {
    dateRange.value = `${dateFrom.value} — ${dateTo.value}`;
  }

  const picker = new Litepicker({
    element: dateRange,
    singleMode: false,
    numberOfMonths: 2,
    numberOfColumns: 2,
    autoApply: true,
    format: 'YYYY-MM-DD',
    dropdowns: { months: true, years: true }
  });

  if (dateFrom.value && dateTo.value) {
    picker.setDateRange(dateFrom.value, dateTo.value);
  }

  picker.on('selected', function (start, end) {
    if (start) dateFrom.value = start.format('YYYY-MM-DD');
    if (end) dateTo.value = end.format('YYYY-MM-DD');
  });

  dateRange.addEventListener('dblclick', function () {
    dateRange.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    picker.clearSelection();
  });

  const supplierRows = @json($supplierChartRows);
  const productRows = @json($productChartRows);
  const countryRows = @json($countryChartRows);
  const stockRows = @json($stockChartRows);
  const trendRows = @json($trendRows);

  const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { precision: 0 }
      }
    }
  };

  new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
      labels: trendRows.map(row => row.period),
      datasets: [{
        label: 'Qty Inbound',
        data: trendRows.map(row => Number(row.total_qty || 0)),
        borderWidth: 3,
        tension: .35,
        fill: true
      }]
    },
    options: commonOptions
  });

  new Chart(document.getElementById('stockChart'), {
    type: 'doughnut',
    data: {
      labels: stockRows.map(row => row.label),
      datasets: [{
        data: stockRows.map(row => Number(row.total || 0)),
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });

  new Chart(document.getElementById('supplierChart'), {
    type: 'bar',
    data: {
      labels: supplierRows.map(row => row.label),
      datasets: [{
        label: 'Jumlah Produk',
        data: supplierRows.map(row => Number(row.total_products || 0)),
        borderWidth: 0
      }]
    },
    options: {
      ...commonOptions,
      indexAxis: 'y'
    }
  });

  new Chart(document.getElementById('productChart'), {
    type: 'bar',
    data: {
      labels: productRows.map(row => row.label),
      datasets: [{
        label: 'Jumlah Supplier',
        data: productRows.map(row => Number(row.total_suppliers || 0)),
        borderWidth: 0
      }]
    },
    options: {
      ...commonOptions,
      indexAxis: 'y'
    }
  });

  new Chart(document.getElementById('countryChart'), {
    type: 'bar',
    data: {
      labels: countryRows.map(row => row.label),
      datasets: [{
        label: 'Jumlah Supplier',
        data: countryRows.map(row => Number(row.total || 0)),
        borderWidth: 0
      }]
    },
    options: commonOptions
  });
});
</script>
@endpush
