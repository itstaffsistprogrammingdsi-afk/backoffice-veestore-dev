@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Stok Mendekati Kedaluwarsa')

@push('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  .expiry-page { --expiry-primary: #7142dd; --expiry-dark: #292040; --expiry-soft: #f3efff; --expiry-border: #e9e2f7; --expiry-muted: #71809b; }
  .expiry-page .select2-container { width: 100% !important; }
  .expiry-page .select2-container--bootstrap-5 .select2-selection { min-height: 42px; border-radius: .75rem; border-color: #ded8eb; }
  .expiry-hero { position: relative; overflow: hidden; padding: 1.55rem; border-radius: 1.15rem; color: #fff; background: linear-gradient(115deg, #282040, #7240dd 58%, #c23cc1); box-shadow: 0 .9rem 2rem rgba(79, 43, 137, .20); }
  .expiry-hero::after { content: ''; position: absolute; width: 290px; height: 290px; border: 42px solid rgba(255,255,255,.10); right: -95px; top: -155px; border-radius: 50%; }
  .expiry-hero > * { position: relative; z-index: 1; }
  .expiry-eyebrow { font-size: .72rem; letter-spacing: .09em; font-weight: 800; opacity: .72; }
  .expiry-hero p { color: rgba(255,255,255,.79); }
  .expiry-range { padding: .5rem .75rem; border: 1px solid rgba(255,255,255,.22); border-radius: 999px; background: rgba(255,255,255,.12); font-size: .8rem; font-weight: 600; }
  .expiry-card { border: 1px solid var(--expiry-border); border-radius: 1rem; box-shadow: 0 .25rem .95rem rgba(39, 27, 67, .045); }
  .expiry-filter { background: linear-gradient(180deg, #fff, #fcfbff); }
  .expiry-filter label { margin-bottom: .4rem; font-size: .78rem; font-weight: 700; color: #58647a; }
  .expiry-filter .form-control, .expiry-filter .form-select { min-height: 42px; border-radius: .75rem; border-color: #ded8eb; }
  .expiry-filter .form-control:focus, .expiry-filter .form-select:focus { border-color: var(--expiry-primary); box-shadow: 0 0 0 .18rem rgba(113,66,221,.12); }
  .expiry-btn { border: 0; border-radius: .75rem; font-weight: 700; background: linear-gradient(120deg, #7445df, #a63ed2); box-shadow: 0 .45rem 1rem rgba(113,66,221,.20); }
  .expiry-kpi { position: relative; min-height: 126px; overflow: hidden; border: 1px solid var(--expiry-border); border-radius: 1rem; background: #fff; transition: transform .2s ease, box-shadow .2s ease; }
  .expiry-kpi:hover { transform: translateY(-3px); box-shadow: 0 .85rem 1.6rem rgba(42, 27, 76, .09); }
  .expiry-kpi::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 4px; background: var(--kpi-color, var(--expiry-primary)); }
  .expiry-kpi .icon { width: 43px; height: 43px; display: grid; place-items: center; border-radius: .8rem; color: var(--kpi-color, var(--expiry-primary)); background: var(--kpi-soft, var(--expiry-soft)); font-size: 1.35rem; }
  .expiry-kpi .number { font-size: 1.45rem; font-weight: 800; color: var(--expiry-dark); }
  .expiry-kpi .hint { color: var(--expiry-muted); font-size: .77rem; }
  .expiry-chart { min-height: 325px; }
  .expiry-chart-empty { min-height: 245px; display: grid; place-items: center; color: var(--expiry-muted); text-align: center; }
  .expiry-table th { white-space: nowrap; background: #f8f6fe; color: #596176; border-bottom-width: 1px; font-size: .72rem; letter-spacing: .03em; text-transform: uppercase; }
  .expiry-table td { vertical-align: middle; }
  .expiry-table tbody tr:hover { background: #faf8ff; }
  .scope-chip, .status-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .55rem; border-radius: 999px; font-size: .73rem; font-weight: 700; white-space: nowrap; }
  .scope-store { color: #236a9b; background: #e8f6ff; } .scope-warehouse { color: #75459a; background: #f4eaff; }
  .status-expired { color: #bf3034; background: #ffeaeb; } .status-critical { color: #a65100; background: #fff1dc; } .status-warning { color: #776000; background: #fff8d9; } .status-safe { color: #197153; background: #e5f9ee; }
  .expiry-product { min-width: 210px; } .expiry-location { min-width: 150px; }
  @media (max-width: 767.98px) { .expiry-hero { padding: 1.25rem; } .expiry-range { width: 100%; text-align: center; } }
</style>
@endpush

@section('content')
@php
  $summary = $summary ?? (object) [];
  $format = fn ($value) => number_format((int) $value, 0, ',', '.');
@endphp

<div class="container-xxl flex-grow-1 container-p-y expiry-page">
  <section class="expiry-hero mb-4">
    <div class="d-flex align-items-end justify-content-between gap-3 flex-wrap">
      <div>
        <div class="expiry-eyebrow">INVENTORY EARLY WARNING</div>
        <h4 class="mb-1 mt-1 text-white">Stok Mendekati Kedaluwarsa</h4>
        <p class="mb-0">Pantau produk yang perlu diprioritaskan sebelum tanggal kedaluwarsa di seluruh Store dan Warehouse.</p>
      </div>
      <div class="expiry-range"><i class="mdi mdi-calendar-range me-1"></i> {{ \Carbon\Carbon::parse($filters['from_date'])->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($filters['to_date'])->translatedFormat('d M Y') }}</div>
    </div>
  </section>

  <div class="card expiry-card expiry-filter mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
        <div>
          <h5 class="mb-1">Filter stok kedaluwarsa</h5>
          <div class="small text-muted">Pilih rentang tanggal dan lokasi untuk melihat stok yang memerlukan tindak lanjut.</div>
        </div>
        <a href="{{ route('monitoring.stok-mendekati-kedaluwarsa.index') }}" class="btn btn-sm btn-outline-secondary"><i class="mdi mdi-refresh me-1"></i>Reset</a>
      </div>
      <form method="GET" action="{{ route('monitoring.stok-mendekati-kedaluwarsa.index') }}" class="row g-3 align-items-end">
        <div class="col-sm-6 col-lg-2">
          <label>Mulai tanggal</label>
          <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] }}">
        </div>
        <div class="col-sm-6 col-lg-2">
          <label>Sampai tanggal</label>
          <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] }}">
        </div>
        <div class="col-sm-6 col-lg-2">
          <label>Lokasi stok</label>
          <select name="scope" id="expiryScope" class="form-select">
            <option value="all" @selected($filters['scope'] === 'all')>Semua lokasi</option>
            <option value="store" @selected($filters['scope'] === 'store')>Store</option>
            <option value="warehouse" @selected($filters['scope'] === 'warehouse')>Warehouse</option>
          </select>
        </div>
        <div class="col-sm-6 col-lg-3" id="storeFilterWrap">
          <label>Store</label>
          <select name="store_id" class="form-select js-expiry-select" data-placeholder="Semua Store">
            <option value="">Semua Store</option>
            @foreach($stores as $store)
              <option value="{{ $store->id }}" @selected($filters['store_id'] === $store->id)>{{ $store->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-6 col-lg-3" id="warehouseFilterWrap">
          <label>Warehouse</label>
          <select name="warehouse_id" class="form-select js-expiry-select" data-placeholder="Semua Warehouse">
            <option value="">Semua Warehouse</option>
            @foreach($warehouses as $warehouse)
              <option value="{{ $warehouse->id }}" @selected($filters['warehouse_id'] === $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-8 col-lg-6">
          <label>Cari produk</label>
          <input type="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Cari kode atau nama produk">
        </div>
        <div class="col-md-4 col-lg-2 d-grid">
          <button class="btn btn-primary expiry-btn" type="submit"><i class="mdi mdi-magnify me-1"></i>Tampilkan</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="expiry-kpi card-body" style="--kpi-color:#7044dc;--kpi-soft:#f1edff"><div class="d-flex justify-content-between gap-2"><div><div class="hint">Total stok terpantau</div><div class="number">{{ $format($summary->total_quantity ?? 0) }}</div><div class="hint">{{ $format($summary->total_product ?? 0) }} produk · {{ $format($summary->total_location ?? 0) }} lokasi</div></div><div class="icon"><i class="mdi mdi-package-variant-closed"></i></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="expiry-kpi card-body" style="--kpi-color:#dc3e44;--kpi-soft:#ffebed"><div class="d-flex justify-content-between gap-2"><div><div class="hint">Sudah kedaluwarsa</div><div class="number">{{ $format($summary->expired_quantity ?? 0) }}</div><div class="hint">Butuh penanganan segera</div></div><div class="icon"><i class="mdi mdi-alert-octagon-outline"></i></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="expiry-kpi card-body" style="--kpi-color:#e58214;--kpi-soft:#fff1dd"><div class="d-flex justify-content-between gap-2"><div><div class="hint">Kedaluwarsa ≤ 7 hari</div><div class="number">{{ $format($summary->urgent_quantity ?? 0) }}</div><div class="hint">Prioritas tertinggi</div></div><div class="icon"><i class="mdi mdi-clock-alert-outline"></i></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="expiry-kpi card-body" style="--kpi-color:#9a7a00;--kpi-soft:#fff8d9"><div class="d-flex justify-content-between gap-2"><div><div class="hint">Kedaluwarsa 8–30 hari</div><div class="number">{{ $format($summary->upcoming_quantity ?? 0) }}</div><div class="hint">Siapkan rencana tindak lanjut</div></div><div class="icon"><i class="mdi mdi-calendar-clock-outline"></i></div></div></div></div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-6"><div class="card expiry-card h-100"><div class="card-header bg-transparent border-bottom-0 pt-4 px-4"><h5 class="mb-1">Produk prioritas jual dari Store</h5><div class="small text-muted">Maks. 10 produk dengan stok terbesar pada filter aktif. Arahkan ke grafik untuk melihat tanggal kedaluwarsa terdekat.</div></div><div class="card-body pt-1"><div id="storeExpiryChart" class="expiry-chart"></div></div></div></div>
    <div class="col-xl-6"><div class="card expiry-card h-100"><div class="card-header bg-transparent border-bottom-0 pt-4 px-4"><h5 class="mb-1">Produk prioritas jual dari Warehouse</h5><div class="small text-muted">Maks. 10 produk dengan stok terbesar pada filter aktif. Arahkan ke grafik untuk melihat tanggal kedaluwarsa terdekat.</div></div><div class="card-body pt-1"><div id="warehouseExpiryChart" class="expiry-chart"></div></div></div></div>
  </div>

  <div class="card expiry-card">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center gap-3 flex-wrap py-3 px-4">
      <div><h5 class="mb-1">Daftar Stok Mendekati Kedaluwarsa</h5><div class="small text-muted">Urut berdasarkan tanggal kedaluwarsa yang paling dekat.</div></div>
      <span class="badge bg-label-primary">{{ $format($rows->total()) }} data</span>
    </div>
    <div class="table-responsive">
      <table class="table expiry-table mb-0">
        <thead><tr><th class="ps-4">No.</th><th>Produk</th><th>Lokasi</th><th>Tipe</th><th>Tanggal Kedaluwarsa</th><th>Sisa Hari</th><th>Status</th><th class="text-end pe-4">Qty</th></tr></thead>
        <tbody>
          @forelse($rows as $row)
            @php
              $days = (int) $row->days_to_expiry;
              [$status, $statusClass, $statusIcon] = match (true) {
                $days < 0 => ['Kedaluwarsa', 'status-expired', 'mdi-alert-octagon-outline'],
                $days <= 7 => ['Kritis', 'status-critical', 'mdi-clock-alert-outline'],
                $days <= 30 => ['Perlu perhatian', 'status-warning', 'mdi-calendar-alert-outline'],
                default => ['Terjadwal', 'status-safe', 'mdi-calendar-check-outline'],
              };
            @endphp
            <tr>
              <td class="ps-4">{{ $rows->firstItem() + $loop->index }}</td>
              <td class="expiry-product"><div class="fw-semibold">{{ $row->product_name }}</div><small class="text-muted">{{ $row->product_code ?: '—' }}</small></td>
              <td class="expiry-location fw-medium">{{ $row->location_name }}</td>
              <td><span class="scope-chip {{ $row->scope_type === 'store' ? 'scope-store' : 'scope-warehouse' }}"><i class="mdi {{ $row->scope_type === 'store' ? 'mdi-store-outline' : 'mdi-warehouse' }}"></i>{{ $row->scope_type === 'store' ? 'Store' : 'Warehouse' }}</span></td>
              <td>{{ \Carbon\Carbon::parse($row->expired_date)->translatedFormat('d M Y') }}</td>
              <td class="fw-semibold">{{ $days < 0 ? abs($days) . ' hari lewat' : $days . ' hari' }}</td>
              <td><span class="status-chip {{ $statusClass }}"><i class="mdi {{ $statusIcon }}"></i>{{ $status }}</span></td>
              <td class="text-end pe-4 fw-bold">{{ $format($row->quantity) }}</td>
            </tr>
          @empty
            <tr><td colspan="8" class="py-5 text-center text-muted"><i class="mdi mdi-package-variant-closed-remove d-block fs-2 mb-2"></i>Tidak ada stok dengan tanggal kedaluwarsa pada filter ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($rows->hasPages())
      <div class="card-body border-top d-flex justify-content-between align-items-center gap-3 flex-wrap"><small class="text-muted">Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $format($rows->total()) }} data</small>{{ $rows->onEachSide(1)->links() }}</div>
    @endif
  </div>
</div>
@endsection

@push('vendor-script')
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@push('page-script')
<script>
  (function ($) {
    'use strict';

    function toggleLocationFilters() {
      const scope = $('#expiryScope').val();
      $('#storeFilterWrap').toggle(scope !== 'warehouse');
      $('#warehouseFilterWrap').toggle(scope !== 'store');
    }

    $('.js-expiry-select').each(function () {
      const $select = $(this);
      $select.select2({ theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: $select.data('placeholder') || 'Pilih data' });
    });
    $('#expiryScope').on('change', toggleLocationFilters);
    toggleLocationFilters();

    const formatQty = value => new Intl.NumberFormat('id-ID').format(Number(value || 0));
    const escapeHtml = value => $('<div>').text(String(value || '')).html();
    const formatDate = value => {
      if (!value) return '-';

      const [year, month, day] = String(value).split('-');
      return year && month && day ? `${day}/${month}/${year}` : value;
    };
    const chartOptions = (rows, color, label) => ({
      chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
      series: [{ name: label, data: rows.map(row => Number(row.quantity || 0)) }],
      colors: [color],
      plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: '58%' } },
      dataLabels: { enabled: false },
      xaxis: { categories: rows.map(row => row.product_code ? `${row.product_code} - ${row.product_name}` : row.product_name), labels: { formatter: formatQty } },
      grid: { borderColor: '#eeeaf6', strokeDashArray: 4 },
      tooltip: {
        custom: ({ dataPointIndex }) => {
          const row = rows[dataPointIndex] || {};
          const product = row.product_code ? `${row.product_code} - ${row.product_name}` : row.product_name;

          return `<div class="px-3 py-2"><div class="fw-semibold">${escapeHtml(product)}</div><div>Stok: ${formatQty(row.quantity)} qty</div><div>Kedaluwarsa terdekat: ${formatDate(row.nearest_expired_date)}</div></div>`;
        }
      },
      noData: { text: 'Tidak ada data pada filter ini', align: 'center', verticalAlign: 'middle', style: { color: '#71809b' } }
    });

    const storeRows = @json($storeChart);
    const warehouseRows = @json($warehouseChart);
    const storeTarget = document.querySelector('#storeExpiryChart');
    const warehouseTarget = document.querySelector('#warehouseExpiryChart');
    if (storeTarget && typeof ApexCharts !== 'undefined') new ApexCharts(storeTarget, chartOptions(storeRows, '#3b9bd6', 'Qty Store')).render();
    if (warehouseTarget && typeof ApexCharts !== 'undefined') new ApexCharts(warehouseTarget, chartOptions(warehouseRows, '#7b4add', 'Qty Warehouse')).render();
  })(window.jQuery);
</script>
@endpush
