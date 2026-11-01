@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Omset Kotor')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root{--ok-primary:#5b5cf6;--ok-primary-dark:#4338ca;--ok-primary-soft:rgba(91,92,246,.12);--ok-success:#22c55e;--ok-success-soft:rgba(34,197,94,.14);--ok-info:#06b6d4;--ok-info-soft:rgba(6,182,212,.14);--ok-warning:#f59e0b;--ok-warning-soft:rgba(245,158,11,.16);--ok-danger:#ef4444;--ok-danger-soft:rgba(239,68,68,.14);--ok-purple:#8b5cf6;--ok-dark:#1e293b;--ok-muted:#64748b;--ok-body:#f6f7fb}
  body{background:radial-gradient(circle at top left,rgba(91,92,246,.10),transparent 30%),radial-gradient(circle at top right,rgba(34,197,94,.10),transparent 28%),var(--ok-body)}
  .select2-container{width:100%!important}.select2-container--bootstrap-5 .select2-selection{min-height:40px;border-radius:.75rem;border-color:#dbe1ea;box-shadow:none}.form-control,.form-select{border-radius:.75rem;border-color:#dbe1ea}.select2-container--bootstrap-5.select2-container--focus .select2-selection,.form-control:focus,.form-select:focus{border-color:var(--ok-primary);box-shadow:0 0 0 .18rem rgba(91,92,246,.14)}
  .dashboard-hero{position:relative;overflow:hidden;border-radius:1.4rem;color:#fff;background:radial-gradient(circle at 15% 20%,rgba(255,255,255,.30),transparent 25%),linear-gradient(135deg,#4f46e5 0%,#22c55e 55%,#06b6d4 120%);box-shadow:0 1.25rem 2.5rem rgba(79,70,229,.22)}
  .dashboard-hero:before{content:"";position:absolute;width:260px;height:260px;right:-80px;top:-100px;border-radius:50%;background:rgba(255,255,255,.18)}.dashboard-hero:after{content:"";position:absolute;width:180px;height:180px;right:120px;bottom:-120px;border-radius:50%;background:rgba(255,255,255,.13)}.dashboard-hero>.d-flex{position:relative;z-index:2}.dashboard-hero h4,.dashboard-hero .text-muted,.dashboard-hero .fw-light{color:#fff!important}.hero-subtitle{color:rgba(255,255,255,.84)!important}.hero-badge{border-radius:999px;padding:.55rem .85rem;background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.22);font-weight:600;font-size:.78rem}
  .card{border:0;border-radius:1.15rem;box-shadow:0 .5rem 1.5rem rgba(15,23,42,.06)}.card-header{border-bottom:1px solid rgba(226,232,240,.8);background:transparent;padding:1.25rem 1.35rem}.card-body{padding:1.35rem}.filter-box{background:linear-gradient(180deg,rgba(248,250,252,.98),rgba(255,255,255,.98));border:1px solid #e2e8f0;border-radius:1rem;padding:1.15rem}.filter-actions{display:flex;gap:.6rem;flex-wrap:wrap}.btn{border-radius:.75rem;font-weight:600}.btn-primary{border-color:var(--ok-primary);background:linear-gradient(135deg,var(--ok-primary),var(--ok-purple));box-shadow:0 .45rem 1rem rgba(91,92,246,.22)}.btn-success{border-color:var(--ok-success);background:linear-gradient(135deg,var(--ok-success),#16a34a)}
  .summary-card{position:relative;overflow:hidden;height:100%;border:1px solid rgba(226,232,240,.85);border-radius:1.15rem;background:#fff;transition:.22s ease}.summary-card:before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:linear-gradient(90deg,var(--ok-primary),var(--ok-success))}.summary-card:hover{transform:translateY(-4px);box-shadow:0 1rem 2rem rgba(15,23,42,.10)}.summary-card h4{color:var(--ok-dark);font-weight:800}.summary-icon{width:50px;height:50px;border-radius:1rem;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:25px;flex-shrink:0;background:linear-gradient(135deg,var(--ok-primary),var(--ok-primary-dark))}.summary-icon.success{background:linear-gradient(135deg,#22c55e,#16a34a)}.summary-icon.info{background:linear-gradient(135deg,#06b6d4,#0284c7)}.summary-icon.warning{background:linear-gradient(135deg,#f59e0b,#f97316)}.summary-icon.danger{background:linear-gradient(135deg,#ec4899,#ef4444)}
  .chart-wrapper{position:relative;height:360px;min-height:360px;width:100%}.table-responsive{border-radius:1rem;border:1px solid #e5e7eb}.table thead th,.table tbody td{vertical-align:middle}.table-nowrap th,.table-nowrap td{white-space:nowrap}.omset-table thead th{background:linear-gradient(180deg,#f8fafc,#eef2ff);color:#334155;font-size:.78rem;text-transform:uppercase;letter-spacing:.035em;font-weight:800;border-bottom:1px solid #dbe1ea}.pagination-info{font-size:.875rem;color:var(--ok-muted)}.product-col,.text-wrap-min{min-width:220px;white-space:normal!important}.source-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.42rem .65rem;border-radius:999px;font-size:.75rem;font-weight:700}.source-badge.store{background:var(--ok-primary-soft);color:var(--ok-primary-dark)}.source-badge.ecommerce{background:var(--ok-success-soft);color:#15803d}.amount-success{color:#15803d;font-weight:800}.amount-danger{color:#b91c1c;font-weight:800}.cell-muted{color:#94a3b8;font-weight:700}
  @media(max-width:767.98px){.dashboard-hero{border-radius:1rem}.chart-wrapper{height:300px;min-height:300px}.card-body{padding:1rem}}
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
  $totalProducts = (float) ($summary->total_products ?? 0);
  $totalCustomers = (float) ($summary->total_customers ?? 0);

  $chartLabels = [];$chartGrossSales=[];$chartProductDiscount=[];$chartVoucherDiscount=[];$chartFinalPaid=[];
  foreach ($rows as $chartRow) {
    if ($groupBy === 'daily') {$label = \Carbon\Carbon::parse($chartRow->trx_date)->format('d-m-Y').' - '.ucfirst($chartRow->channel);} 
    elseif ($groupBy === 'monthly') {$label = ($chartRow->period_month ?? '-').' - '.ucfirst($chartRow->channel);} 
    else {$label = trim(($chartRow->product_name ?? '-').' - '.ucfirst($chartRow->channel));}
    $chartLabels[]=$label;
    $chartGrossSales[]=(float)($chartRow->total_gross_sales ?? 0);
    $chartProductDiscount[]=(float)($chartRow->total_product_discount ?? 0);
    $chartVoucherDiscount[]=(float)($chartRow->total_voucher_discount ?? $chartRow->total_order_discount ?? 0);
    $chartFinalPaid[]=(float)($chartRow->total_final_paid ?? 0);
  }
  $hasFilter = request()->hasAny(['q','channel','product_id','customer_id','date_from','date_to','group_by','per_page','promo']);
  $promo = $promo ?? 'all';
  $promoOptions = $promoOptions ?? ['all' => 'Semua Transaksi'];
  $isPureMode = $promo === 'none';
  $channelBadge = fn($value) => strtolower((string)$value) === 'ecommerce' ? 'ecommerce' : 'store';
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1"><span class="fw-light">Monitoring /</span> Omset Kotor</h4>
        <div class="hero-subtitle">Monitoring omset kotor dari transaksi Store dan Ecommerce berdasarkan produk, channel, customer, diskon, voucher, dan harga akhir.</div>
      </div>
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge"><i class="mdi mdi-calendar-range-outline me-1"></i>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</span>
        <span class="hero-badge"><i class="mdi mdi-cash-multiple me-1"></i>Rp {{ number_format($totalGross,0,',','.') }}</span>
        @if($isPureMode)<span class="hero-badge"><i class="mdi mdi-tag-off-outline me-1"></i>Harga Asli (Tanpa Promo)</span>@endif
        <span class="hero-badge"><i class="mdi mdi-clock-outline me-1"></i>Update: {{ $lastUpdate ?? now()->format('d-m-Y H:i:s') }}</span>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div><h5 class="mb-1 fw-bold">Filter Omset Kotor</h5><small class="text-muted">Default menampilkan data hari ini. Gunakan filter untuk melihat channel, produk, customer, periode, dan tampilan laporan.</small></div>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#advancedFilterOmsetKotor"><i class="mdi mdi-filter-variant me-1"></i> Filter</button>
        <a href="{{ route('monitoring.omset-kotor.index') }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-refresh me-1"></i> Reset</a>
        <a href="{{ route('monitoring.omset-kotor.export', request()->query()) }}" class="btn btn-success btn-sm"><i class="mdi mdi-file-excel-outline me-1"></i> Export</a>
      </div>
    </div>
    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterOmsetKotor">
        <div class="filter-box">
          <form method="GET" action="{{ route('monitoring.omset-kotor.index') }}" class="row g-3">
            <div class="col-sm-6 col-md-2"><label class="form-label">Channel</label><select name="channel" class="form-select js-select2-filter" data-placeholder="Semua Channel"><option value="all" @selected($channel==='all')>All</option><option value="store" @selected($channel==='store')>Store</option><option value="ecommerce" @selected($channel==='ecommerce')>Ecommerce</option></select></div>
            <div class="col-sm-6 col-md-3"><label class="form-label">Produk</label><select name="product_id" class="form-select js-select2-filter" data-placeholder="Semua Produk"><option value=""></option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string)$productId===(string)$product->id)>{{ $product->name }} @if($product->code) - {{ $product->code }} @endif</option>@endforeach</select></div>
            <div class="col-sm-6 col-md-3"><label class="form-label">Customer / Member</label><select name="customer_id" class="form-select js-select2-filter" data-placeholder="Semua Customer"><option value=""></option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string)$customerId===(string)$customer->id)>{{ $customer->name }}</option>@endforeach</select></div>
            <div class="col-sm-6 col-md-2"><label class="form-label">Dari</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control"></div>
            <div class="col-sm-6 col-md-2"><label class="form-label">Sampai</label><input type="date" name="date_to" value="{{ $dateTo }}" class="form-control"></div>
            <div class="col-sm-6 col-md-2"><label class="form-label">Tampilan</label><select name="group_by" class="form-select js-select2-filter"><option value="product" @selected($groupBy==='product')>Per Produk</option><option value="daily" @selected($groupBy==='daily')>Harian</option><option value="monthly" @selected($groupBy==='monthly')>Bulanan</option></select></div>
            <div class="col-sm-6 col-md-4"><label class="form-label">Promo / Diskon</label><select name="promo" class="form-select js-select2-filter" data-placeholder="Semua Transaksi">@foreach($promoOptions as $value=>$label)<option value="{{ $value }}" @selected($promo===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-sm-6 col-md-2"><label class="form-label">Show</label><select name="per_page" class="form-select js-select2-filter">@foreach([10,25,50,100,200] as $size)<option value="{{ $size }}" @selected((int)($perPage ?? 10)===$size)>{{ $size }}</option>@endforeach</select></div>
            <div class="col-sm-12 col-md-8"><label class="form-label">Cari</label><input type="text" name="q" class="form-control" value="{{ $q ?? request('q') }}" placeholder="Produk / kode produk / customer / no transaksi / channel"></div>
            <div class="col-12"><div class="filter-actions"><button class="btn btn-primary btn-sm"><i class="mdi mdi-magnify me-1"></i> Tampilkan</button><a href="{{ route('monitoring.omset-kotor.export', request()->query()) }}" class="btn btn-success btn-sm"><i class="mdi mdi-file-excel me-1"></i> Export Excel</a><a href="{{ route('monitoring.omset-kotor.index') }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-refresh me-1"></i> Reset</a></div></div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><div class="text-muted small">Omset Kotor</div><h4>Rp {{ number_format($totalGross,0,',','.') }}</h4><small class="text-muted">Gross sales</small></div><div class="summary-icon success"><i class="mdi mdi-cash-multiple"></i></div></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><div class="text-muted small">Harga Akhir</div><h4>Rp {{ number_format($totalFinal,0,',','.') }}</h4><small class="text-muted">Final paid</small></div><div class="summary-icon"><i class="mdi mdi-credit-card-check-outline"></i></div></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><div class="text-muted small">Total Diskon</div><h4>Rp {{ number_format($totalDiscount,0,',','.') }}</h4><small class="text-muted">Produk + voucher</small></div><div class="summary-icon danger"><i class="mdi mdi-tag-outline"></i></div></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="d-flex justify-content-between gap-3"><div><div class="text-muted small">Total Qty</div><h4>{{ number_format($totalQty,0,',','.') }}</h4><small class="text-muted">Qty terjual</small></div><div class="summary-icon warning"><i class="mdi mdi-format-list-numbered"></i></div></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="text-muted small">Diskon Produk</div><h4>Rp {{ number_format($totalProductDiscount,0,',','.') }}</h4></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="text-muted small">Voucher / Point</div><h4>Rp {{ number_format($totalVoucherDiscount,0,',','.') }}</h4></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="text-muted small">Total Produk</div><h4>{{ number_format($totalProducts,0,',','.') }}</h4></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card summary-card"><div class="card-body"><div class="text-muted small">Total Customer</div><h4>{{ number_format($totalCustomers,0,',','.') }}</h4></div></div></div>
  </div>

  <div class="card mb-4"><div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2"><div><h5 class="mb-1 fw-bold">Grafik Omset Kotor</h5><small class="text-muted">Grafik berdasarkan data yang sedang tampil.</small></div><div class="small text-muted">Periode: <b>{{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }}</b> s/d <b>{{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}</b></div></div><div class="card-body">@if(count($chartLabels))<div class="chart-wrapper"><canvas id="omsetKotorChart"></canvas></div>@else<div class="text-center text-muted py-5">Tidak ada data untuk ditampilkan pada grafik.</div>@endif</div></div>

  <div class="card"><div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2"><div><h5 class="mb-1 fw-bold">Laporan Omset Kotor {{ ucfirst($groupBy) }}</h5><small class="text-muted">Total Data: <b>{{ number_format($rows->total(),0,',','.') }}</b></small></div>@if($rows->count())<div class="pagination-info">Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b> dari <b>{{ number_format($rows->total(),0,',','.') }}</b> data</div>@endif</div>
    <div class="card-body"><div class="table-responsive">
      @if($groupBy === 'product')
      <table class="table table-bordered table-striped table-sm mb-0 table-nowrap omset-table"><thead><tr><th>No</th><th>Channel</th><th>Kode Produk</th><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Omset Kotor</th><th class="text-end">Diskon Produk</th><th class="text-end">Voucher/Point</th><th class="text-end">Total Potongan</th><th class="text-end">Harga Akhir</th><th class="text-center">Aksi</th></tr></thead><tbody>@forelse($rows as $i=>$row)<tr><td>{{ $rows->firstItem()+$i }}</td><td><span class="source-badge {{ $channelBadge($row->channel) }}"><i class="mdi {{ $row->channel === 'ecommerce' ? 'mdi-web' : 'mdi-store-outline' }}"></i>{{ ucfirst($row->channel) }}</span></td><td>{{ $row->product_code }}</td><td class="product-col">{{ $row->product_name }}</td><td class="text-end">{{ number_format((int)$row->total_qty,0,',','.') }}</td><td class="text-end amount-success">Rp {{ number_format((float)$row->total_gross_sales,0,',','.') }}</td><td class="text-end amount-danger">Rp {{ number_format((float)$row->total_product_discount,0,',','.') }}</td><td class="text-end">Rp {{ number_format((float)($row->total_voucher_discount ?? $row->total_order_discount ?? 0),0,',','.') }}</td><td class="text-end">Rp {{ number_format((float)($row->total_discount_amount ?? (($row->total_product_discount ?? 0)+($row->total_voucher_discount ?? $row->total_order_discount ?? 0))),0,',','.') }}</td><td class="text-end fw-bold">Rp {{ number_format((float)$row->total_final_paid,0,',','.') }}</td><td class="text-center"><a href="{{ route('monitoring.omset-kotor.detail',['product'=>$row->product_id,'channel'=>$row->channel,'customer_id'=>$customerId,'q'=>$q ?? request('q'),'date_from'=>$dateFrom,'date_to'=>$dateTo,'per_page'=>$perPage ?? 10,'promo'=>$promo]) }}" class="btn btn-sm btn-info"><i class="mdi mdi-eye me-1"></i> Detail</a></td></tr>@empty<tr><td colspan="11" class="text-center text-muted py-4">Tidak ada data omset kotor hari ini.</td></tr>@endforelse</tbody></table>
      @else
      <table class="table table-bordered table-striped table-sm mb-0 table-nowrap omset-table"><thead><tr><th>No</th><th>{{ $groupBy === 'daily' ? 'Tanggal' : 'Bulan' }}</th><th>Channel</th><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Omset Kotor</th><th class="text-end">Diskon Produk</th><th class="text-end">Voucher/Point</th><th class="text-end">Total Potongan</th><th class="text-end">Harga Akhir</th></tr></thead><tbody>@forelse($rows as $i=>$row)<tr><td>{{ $rows->firstItem()+$i }}</td><td>{{ $groupBy === 'daily' ? \Carbon\Carbon::parse($row->trx_date)->format('d-m-Y') : $row->period_month }}</td><td><span class="source-badge {{ $channelBadge($row->channel) }}">{{ ucfirst($row->channel) }}</span></td><td class="product-col">{{ $row->product_name }}</td><td class="text-end">{{ number_format((int)$row->total_qty,0,',','.') }}</td><td class="text-end amount-success">Rp {{ number_format((float)$row->total_gross_sales,0,',','.') }}</td><td class="text-end amount-danger">Rp {{ number_format((float)$row->total_product_discount,0,',','.') }}</td><td class="text-end">Rp {{ number_format((float)($row->total_voucher_discount ?? $row->total_order_discount ?? 0),0,',','.') }}</td><td class="text-end">Rp {{ number_format((float)($row->total_discount_amount ?? (($row->total_product_discount ?? 0)+($row->total_voucher_discount ?? $row->total_order_discount ?? 0))),0,',','.') }}</td><td class="text-end fw-bold">Rp {{ number_format((float)$row->total_final_paid,0,',','.') }}</td></tr>@empty<tr><td colspan="10" class="text-center text-muted py-4">Tidak ada data omset kotor hari ini.</td></tr>@endforelse</tbody></table>
      @endif
    </div>@if($rows->hasPages())<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3"><div class="pagination-info">Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b> dari <b>{{ number_format($rows->total(),0,',','.') }}</b> data</div><div>{{ $rows->onEachSide(1)->links() }}</div></div>@endif</div>
  </div>
</div>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('page-script')
<script>
(function($){'use strict';function rupiah(v){return 'Rp '+new Intl.NumberFormat('id-ID',{maximumFractionDigits:0}).format(Number(v||0));}function angka(v){return new Intl.NumberFormat('id-ID',{maximumFractionDigits:0}).format(Number(v||0));}$('.js-select2-filter').each(function(){const $el=$(this);if($el.hasClass('select2-hidden-accessible')){$el.select2('destroy');}$el.select2({theme:'bootstrap-5',width:'100%',allowClear:true,placeholder:$el.data('placeholder')||'Pilih data'});});const canvas=document.getElementById('omsetKotorChart');if(canvas&&typeof Chart!=='undefined'){const labels=@json($chartLabels);if(labels.length){new Chart(canvas,{type:'bar',data:{labels:labels,datasets:[{type:'bar',label:'Omset Kotor',data:@json($chartGrossSales),backgroundColor:'#5b5cf6',borderRadius:8,maxBarThickness:42},{type:'bar',label:'Diskon Produk',data:@json($chartProductDiscount),backgroundColor:'#ef4444',borderRadius:8,maxBarThickness:42},{type:'bar',label:'Voucher / Point',data:@json($chartVoucherDiscount),backgroundColor:'#f59e0b',borderRadius:8,maxBarThickness:42},{type:'line',label:'Harga Akhir',data:@json($chartFinalPaid),borderColor:'#22c55e',backgroundColor:'rgba(34,197,94,.12)',borderWidth:3,tension:.35,pointRadius:4,fill:false}]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom',labels:{usePointStyle:true}},tooltip:{backgroundColor:'#0f172a',titleColor:'#fff',bodyColor:'#fff',padding:12,cornerRadius:10,callbacks:{label:function(ctx){return ctx.dataset.label+': '+rupiah(ctx.raw);}}}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{callback:function(v){return rupiah(v);}}}}}});}}})(window.jQuery);
</script>
@endpush
