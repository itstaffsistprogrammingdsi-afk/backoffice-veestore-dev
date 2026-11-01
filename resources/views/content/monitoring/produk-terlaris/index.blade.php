@extends('layouts/contentNavbarLayout')

@section('title', 'Omset Produk per Bulan')

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@section('content')
@php
    $formatRupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $topProductLabels = collect($topProductChart ?? [])->pluck('product_name')->values();
    $topProductQty = collect($topProductChart ?? [])->pluck('total_qty')->map(fn ($value) => (float) $value)->values();
    $topProductNominal = collect($topProductChart ?? [])->pluck('nominal_penjualan')->map(fn ($value) => (float) $value)->values();
    $topStoreLabels = collect($topStoreChart ?? [])->pluck('store_name')->values();
    $topStoreQty = collect($topStoreChart ?? [])->pluck('total_qty')->map(fn ($value) => (float) $value)->values();
    $topStoreNominal = collect($topStoreChart ?? [])->pluck('nominal_penjualan')->map(fn ($value) => (float) $value)->values();
@endphp
<div class="card mb-2" style="background:linear-gradient(115deg,#2d2148,#7c32ef,#cf62cc);color:#fff;border:0">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h3 class="mb-1 text-white">Monitoring / Omset Produk per Bulan</h3>
            <p class="mb-0">Rekap nominal penjualan per bulan, per produk, dan per cabang.</p>
        </div>
        <div class="mt-1 mt-md-0"><span class="badge badge-light-primary p-1">Update: {{ $lastUpdate }}</span></div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="card-title mb-50">Filter Omset Produk</h4>
            <small>Filter berdasarkan periode, cabang, brand, kategori, dan produk. Kosongkan cabang untuk seluruh cabang.</small>
        </div>
        <div class="mt-1 mt-md-0">
            <a href="{{ route('monitoring.produk-terlaris.export', request()->query()) }}" class="btn btn-gradient-primary">
                <i data-feather="file-text"></i> Export Excel
            </a>
        </div>
    </div>
    <div class="card-body border-top">
        <form method="GET" action="{{ route('monitoring.produk-terlaris.index') }}">
            <div class="row">
                <div class="col-md-3 mb-1"><label>Dari</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
                <div class="col-md-3 mb-1"><label>Sampai</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
                <div class="col-md-3 mb-1"><label>Cabang</label><select name="store_id" class="form-control"><option value="">Semua Cabang</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected($filters['store_id'] === $store->id)>{{ $store->name }}{{ $store->code ? ' - '.$store->code : '' }}</option>@endforeach</select></div>
                @if($hasBrandColumn)<div class="col-md-3 mb-1"><label>Brand</label><select name="brand_id" class="form-control"><option value="">Semua Brand</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected($filters['brand_id'] === $brand->id)>{{ $brand->name }}</option>@endforeach</select></div>@endif
                <div class="col-md-3 mb-1"><label>Kategori</label><select name="category_id" class="form-control"><option value="">Semua Kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($filters['category_id'] === $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-md-3 mb-1"><label>Cari Produk</label><input name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Nama / kode / brand / kategori"></div>
                <div class="col-md-2 mb-1"><label>Tampilkan</label><select name="per_page" class="form-control">@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>@endforeach</select></div>
            </div>
            <button class="btn btn-primary" type="submit"><i data-feather="search"></i> Tampilkan</button>
            <a class="btn btn-outline-secondary" href="{{ route('monitoring.produk-terlaris.index') }}"><i data-feather="rotate-ccw"></i> Reset</a>
        </form>
    </div>
</div>

<div class="row match-height mt-2">
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><small>Total Produk</small><h3>{{ number_format($summary->total_produk ?? 0) }}</h3></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><small>Total Qty Terjual</small><h3>{{ number_format($summary->total_qty ?? 0) }}</h3></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><small>Total Transaksi</small><h3>{{ number_format($summary->total_transaksi ?? 0) }}</h3></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><small>Total Nominal Penjualan</small><h3>{{ $formatRupiah($summary->total_nominal ?? 0) }}</h3></div></div></div>
</div>

<div class="row match-height">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><div><h4 class="card-title mb-50">10 Produk Penjualan Terlaris</h4><small>Seluruh cabang, diurutkan berdasarkan qty terjual. Arahkan kursor untuk melihat omset.</small></div></div>
            <div class="card-body">@if($topProductLabels->isNotEmpty())<div style="height:360px"><canvas id="topProductChart"></canvas></div>@else<div class="text-center text-muted py-5">Belum ada data penjualan pada periode ini.</div>@endif</div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><div><h4 class="card-title mb-50">Cabang dengan Penjualan Terbanyak</h4><small>Seluruh cabang, diurutkan berdasarkan qty terjual. Tooltip menampilkan omset cabang.</small></div></div>
            <div class="card-body">@if($topStoreLabels->isNotEmpty())<div style="height:360px"><canvas id="topStoreChart"></canvas></div>@else<div class="text-center text-muted py-5">Belum ada data penjualan pada periode ini.</div>@endif</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Bulan</th><th>Produk</th><th>Brand</th><th>Kategori</th><th>Cabang</th><th>Transaksi</th><th>Qty Terjual</th><th class="text-right">Nominal Penjualan</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr><td>{{ $rows->firstItem() + $loop->index }}</td><td>{{ $row->periode_bulan }}</td><td><strong>{{ $row->product_name }}</strong><br><small>{{ $row->product_code ?: '-' }}</small></td><td>{{ $row->brand_name ?: '-' }}</td><td>{{ $row->category_name ?: '-' }}</td><td>{{ $row->cabang_name ?: '-' }}</td><td>{{ number_format($row->total_transaksi) }}</td><td><strong>{{ number_format($row->total_qty) }}</strong></td><td class="text-right"><strong>{{ $formatRupiah($row->nominal_penjualan) }}</strong></td></tr>
            @empty
                <tr><td colspan="9" class="text-center py-3">Belum ada penjualan pada filter yang dipilih.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-body">{{ $rows->links() }}</div>@endif
</div>
@endsection

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const rupiah = value => 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value || 0));
    const number = value => new Intl.NumberFormat('id-ID').format(Number(value || 0));
    const makeChart = (canvasId, labels, quantities, nominals, color) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !labels.length) return;
        new Chart(canvas, {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Qty Terjual', data: quantities, backgroundColor: color, borderRadius: 7, maxBarThickness: 28 }] },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: context => 'Qty Terjual: ' + number(context.raw), afterLabel: context => 'Omset: ' + rupiah(nominals[context.dataIndex]) } }
                },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } }
            }
        });
    };
    makeChart('topProductChart', @json($topProductLabels), @json($topProductQty), @json($topProductNominal), '#7c32ef');
    makeChart('topStoreChart', @json($topStoreLabels), @json($topStoreQty), @json($topStoreNominal), '#d940a3');
});
</script>
@endpush
