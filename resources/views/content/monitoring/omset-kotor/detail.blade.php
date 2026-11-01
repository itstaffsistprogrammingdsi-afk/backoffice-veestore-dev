@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Detail Omset Kotor Produk')

@push('page-style')
<style>
  :root{--od-primary:#5b5cf6;--od-primary-dark:#4338ca;--od-primary-soft:rgba(91,92,246,.12);--od-success:#22c55e;--od-success-soft:rgba(34,197,94,.14);--od-info:#06b6d4;--od-info-soft:rgba(6,182,212,.14);--od-warning:#f59e0b;--od-warning-soft:rgba(245,158,11,.16);--od-danger:#ef4444;--od-danger-soft:rgba(239,68,68,.14);--od-purple:#8b5cf6;--od-dark:#1e293b;--od-muted:#64748b;--od-body:#f6f7fb}
  body{background:radial-gradient(circle at top left,rgba(91,92,246,.10),transparent 30%),radial-gradient(circle at top right,rgba(34,197,94,.10),transparent 28%),var(--od-body)}
  .dashboard-hero{position:relative;overflow:hidden;border-radius:1.4rem;color:#fff;background:radial-gradient(circle at 15% 20%,rgba(255,255,255,.30),transparent 25%),linear-gradient(135deg,#4f46e5 0%,#22c55e 55%,#06b6d4 120%);box-shadow:0 1.25rem 2.5rem rgba(79,70,229,.22)}
  .dashboard-hero:before{content:"";position:absolute;width:260px;height:260px;right:-80px;top:-100px;border-radius:50%;background:rgba(255,255,255,.18)}.dashboard-hero:after{content:"";position:absolute;width:180px;height:180px;right:120px;bottom:-120px;border-radius:50%;background:rgba(255,255,255,.13)}.dashboard-hero>.d-flex{position:relative;z-index:2}.dashboard-hero h4,.dashboard-hero .text-muted,.dashboard-hero .fw-light{color:#fff!important}.hero-subtitle{color:rgba(255,255,255,.84)!important}.hero-badge{border-radius:999px;padding:.55rem .85rem;background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.22);font-weight:600;font-size:.78rem}
  .card{border:0;border-radius:1.15rem;box-shadow:0 .5rem 1.5rem rgba(15,23,42,.06)}.card-header{border-bottom:1px solid rgba(226,232,240,.8);background:transparent;padding:1.25rem 1.35rem}.card-body{padding:1.35rem}.btn{border-radius:.75rem;font-weight:600}.btn-success{border-color:var(--od-success);background:linear-gradient(135deg,var(--od-success),#16a34a)}
  .summary-card{position:relative;overflow:hidden;height:100%;border:1px solid rgba(226,232,240,.85);border-radius:1.15rem;background:#fff;transition:.22s ease}.summary-card:before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:linear-gradient(90deg,var(--od-primary),var(--od-success))}.summary-card:hover{transform:translateY(-4px);box-shadow:0 1rem 2rem rgba(15,23,42,.10)}.summary-card h4{color:var(--od-dark);font-weight:800}.summary-icon{width:50px;height:50px;border-radius:1rem;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:25px;flex-shrink:0;background:linear-gradient(135deg,var(--od-primary),var(--od-primary-dark))}.summary-icon.success{background:linear-gradient(135deg,#22c55e,#16a34a)}.summary-icon.info{background:linear-gradient(135deg,#06b6d4,#0284c7)}.summary-icon.warning{background:linear-gradient(135deg,#f59e0b,#f97316)}.summary-icon.danger{background:linear-gradient(135deg,#ec4899,#ef4444)}
  .table-responsive{border-radius:1rem;border:1px solid #e5e7eb}.table thead th,.table tbody td{vertical-align:middle}.table-nowrap th,.table-nowrap td{white-space:nowrap}.detail-table thead th{background:linear-gradient(180deg,#f8fafc,#eef2ff);color:#334155;font-size:.78rem;text-transform:uppercase;letter-spacing:.035em;font-weight:800;border-bottom:1px solid #dbe1ea}.pagination-info{font-size:.875rem;color:var(--od-muted)}.text-wrap-min{min-width:220px;white-space:normal!important}.source-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.42rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700}.source-badge.store{background:var(--od-primary-soft);color:var(--od-primary-dark)}.source-badge.ecommerce{background:var(--od-success-soft);color:#15803d}.amount-success{color:#15803d;font-weight:800}.amount-danger{color:#b91c1c;font-weight:800}.rfid-box{max-width:280px;white-space:normal!important;line-height:1.35}
  @media(max-width:767.98px){.dashboard-hero{border-radius:1rem}.card-body{padding:1rem}}
</style>
@endpush

@php
  $summary = $summary ?? (object) [];
  $totalGross = (float) ($summary->total_gross_sales ?? 0);
  $totalBase = (float) ($summary->total_base_price ?? 0);
  $totalProductDiscount = (float) ($summary->total_product_discount ?? 0);
  $totalVoucherDiscount = (float) ($summary->total_voucher_discount ?? $summary->total_order_discount ?? 0);
  $totalDiscount = (float) ($summary->total_discount_amount ?? ($totalProductDiscount + $totalVoucherDiscount));
  $totalFinal = (float) ($summary->total_final_paid ?? 0);
  $totalQty = (float) ($summary->total_qty ?? 0);
  $totalRows = (float) ($summary->total_rows ?? $rows->total() ?? 0);
  $channelBadge = fn($value) => strtolower((string)$value) === 'ecommerce' ? 'ecommerce' : 'store';
  $promo = $promo ?? 'all';
  $promoOptions = $promoOptions ?? ['all' => 'Semua Transaksi'];
  $promoLabel = $promoOptions[$promo] ?? $promo;
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1"><span class="fw-light">Monitoring / Omset Kotor /</span> Detail Produk</h4>
        <div class="hero-subtitle">Detail transaksi omset kotor produk {{ $product->name }} berdasarkan transaksi Store dan Ecommerce.</div>
      </div>
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge"><i class="mdi mdi-package-variant me-1"></i>{{ $product->code ?: $product->id }}</span>
        <span class="hero-badge"><i class="mdi mdi-calendar-range-outline me-1"></i>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</span>
        <span class="hero-badge"><i class="mdi mdi-clock-outline me-1"></i>Update: {{ $lastUpdate ?? now()->format('d-m-Y H:i:s') }}</span>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body d-flex justify-content-between flex-wrap gap-3 align-items-center">
      <div>
        <h5 class="mb-1 fw-bold">{{ $product->name }}</h5>
        <small class="text-muted">Product ID: {{ $product->id }} @if(!empty($product->code)) | Code: {{ $product->code }} @endif | Channel: {{ $channel === 'all' ? 'Store & Ecommerce' : ucfirst($channel) }} | Promo: {{ $promoLabel }}</small>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('monitoring.omset-kotor.export', array_merge(request()->query(), ['product_id' => $product->id])) }}" class="btn btn-success btn-sm"><i class="mdi mdi-file-excel me-1"></i> Export Excel</a>
        <a href="{{ route('monitoring.omset-kotor.index', request()->query()) }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-arrow-left me-1"></i> Kembali</a>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><div class="text-muted small">Omset Kotor</div><h4>Rp {{ number_format($totalGross,0,',','.') }}</h4><small class="text-muted">Gross sales</small></div><div class="summary-icon success"><i class="mdi mdi-cash-multiple"></i></div></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><div class="text-muted small">Harga Akhir</div><h4>Rp {{ number_format($totalFinal,0,',','.') }}</h4><small class="text-muted">Final paid</small></div><div class="summary-icon"><i class="mdi mdi-credit-card-check-outline"></i></div></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><div class="text-muted small">Harga Base Total</div><h4>Rp {{ number_format($totalBase,0,',','.') }}</h4><small class="text-muted">Total modal/base</small></div><div class="summary-icon info"><i class="mdi mdi-currency-usd"></i></div></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><div class="text-muted small">Total Potongan</div><h4>Rp {{ number_format($totalDiscount,0,',','.') }}</h4><small class="text-muted">Produk + voucher</small></div><div class="summary-icon danger"><i class="mdi mdi-tag-outline"></i></div></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="text-muted small">Diskon Produk</div><h4>Rp {{ number_format($totalProductDiscount,0,',','.') }}</h4></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="text-muted small">Voucher / Point</div><h4>Rp {{ number_format($totalVoucherDiscount,0,',','.') }}</h4></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="text-muted small">Total Qty</div><h4>{{ number_format($totalQty,0,',','.') }}</h4></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="text-muted small">Total Transaksi</div><h4>{{ number_format($totalRows,0,',','.') }}</h4></div></div></div>
  </div>

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div><h5 class="mb-1 fw-bold">Detail Transaksi Omset Kotor Produk</h5><small class="text-muted">Harga jual diambil dari transaksi Store/Ecommerce, harga base dari product_base_price_amount, dan harga akhir setelah diskon/voucher.</small></div>
      @if($rows->count())<div class="pagination-info">Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b> dari <b>{{ number_format($rows->total(),0,',','.') }}</b> data</div>@endif
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap detail-table">
          <thead><tr><th>No</th><th>Tanggal</th><th>Channel</th><th>No Transaksi</th><th>Customer</th><th>RFID Tag</th><th class="text-end">Qty</th><th class="text-end">Harga Base</th><th class="text-end">Harga Base Total</th><th class="text-end">Harga Jual</th><th class="text-end">Omset Kotor</th><th>Nama Diskon</th><th class="text-end">Diskon Produk</th><th>Nama Voucher</th><th class="text-end">Voucher/Point</th><th class="text-end">Total Potongan</th><th class="text-end">Harga Akhir</th></tr></thead>
          <tbody>@forelse($rows as $i=>$row)<tr><td>{{ $rows->firstItem()+$i }}</td><td>{{ $row->trx_datetime ? \Carbon\Carbon::parse($row->trx_datetime)->format('d-m-Y H:i') : '-' }}</td><td><span class="source-badge {{ $channelBadge($row->channel) }}"><i class="mdi {{ $row->channel === 'ecommerce' ? 'mdi-web' : 'mdi-store-outline' }}"></i>{{ ucfirst($row->channel) }}</span></td><td class="fw-semibold">{{ $row->transaction_number ?? '-' }}</td><td class="text-wrap-min">{{ $row->customer_name ?? '-' }}</td><td class="rfid-box">{{ $row->rfid_value ?? '-' }}</td><td class="text-end">{{ number_format((int)($row->qty ?? 0),0,',','.') }}</td><td class="text-end">Rp {{ number_format((float)($row->base_price_amount ?? 0),0,',','.') }}</td><td class="text-end">Rp {{ number_format((float)($row->base_price_total ?? 0),0,',','.') }}</td><td class="text-end">Rp {{ number_format((float)($row->selling_price ?? 0),0,',','.') }}</td><td class="text-end amount-success">Rp {{ number_format((float)($row->gross_sales ?? 0),0,',','.') }}</td><td class="text-wrap-min">{{ $row->discount_names ?: '-' }}</td><td class="text-end amount-danger">Rp {{ number_format((float)($row->product_discount_amount ?? 0),0,',','.') }}</td><td class="text-wrap-min">{{ $row->voucher_names ?: '-' }}</td><td class="text-end">Rp {{ number_format((float)($row->allocated_order_discount ?? 0),0,',','.') }}</td><td class="text-end">Rp {{ number_format((float)($row->total_discount_amount ?? (($row->product_discount_amount ?? 0)+($row->allocated_order_discount ?? 0))),0,',','.') }}</td><td class="text-end fw-bold">Rp {{ number_format((float)($row->final_paid_amount ?? 0),0,',','.') }}</td></tr>@empty<tr><td colspan="17" class="text-center text-muted py-4">Tidak ada transaksi hari ini.</td></tr>@endforelse</tbody>
          @if($rows->count())<tfoot><tr><th colspan="6" class="text-end">Total Halaman Ini</th><th class="text-end">{{ number_format((int)$rows->sum('qty'),0,',','.') }}</th><th></th><th class="text-end">Rp {{ number_format((float)$rows->sum('base_price_total'),0,',','.') }}</th><th></th><th class="text-end">Rp {{ number_format((float)$rows->sum('gross_sales'),0,',','.') }}</th><th></th><th class="text-end">Rp {{ number_format((float)$rows->sum('product_discount_amount'),0,',','.') }}</th><th></th><th class="text-end">Rp {{ number_format((float)$rows->sum('allocated_order_discount'),0,',','.') }}</th><th class="text-end">Rp {{ number_format((float)$rows->sum(fn($r)=>(float)($r->total_discount_amount ?? (($r->product_discount_amount ?? 0)+($r->allocated_order_discount ?? 0)))),0,',','.') }}</th><th class="text-end">Rp {{ number_format((float)$rows->sum('final_paid_amount'),0,',','.') }}</th></tr></tfoot>@endif
        </table>
      </div>
      @if($rows->hasPages())<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3"><div class="pagination-info">Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b> dari <b>{{ number_format($rows->total(),0,',','.') }}</b> data</div><div>{{ $rows->onEachSide(1)->links() }}</div></div>@else<div class="mt-3 pagination-info">Menampilkan <b>{{ $rows->firstItem() ?? 0 }}</b> - <b>{{ $rows->lastItem() ?? 0 }}</b> dari <b>{{ number_format($rows->total(),0,',','.') }}</b> data</div>@endif
    </div>
  </div>
</div>
@endsection
