@extends('layouts/contentNavbarLayout')
@section('title', 'AI Analytics')

@push('page-style')
<style>
.ai-hero{border-radius:1.4rem;color:#fff;background:radial-gradient(circle at 15% 15%,rgba(255,255,255,.25),transparent 28%),linear-gradient(135deg,#312e81,#6d28d9 52%,#0891b2);box-shadow:0 1.25rem 2.5rem rgba(79,70,229,.22)}
.ai-card{border:0;border-radius:1.1rem;box-shadow:0 .5rem 1.5rem rgba(15,23,42,.07)}.ai-metric{font-size:1.65rem;font-weight:800;color:#1e293b}.ai-label{font-size:.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;font-weight:700}
.risk,.status-pill{display:inline-flex;padding:.35rem .6rem;border-radius:999px;font-size:.75rem;font-weight:800}.risk-critical{background:#fee2e2;color:#b91c1c}.risk-high{background:#ffedd5;color:#c2410c}.risk-medium{background:#fef3c7;color:#a16207}.risk-low{background:#dcfce7;color:#15803d}.status-pill{background:#eef2ff;color:#4338ca}.chart-wrap{height:310px}
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
 @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
 @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
 <div class="ai-hero p-4 mb-4"><div>
  <div><h4 class="text-white mb-1"><i class="mdi mdi-robot-outline me-2"></i>AI Analytics</h4><div class="text-white-50">Forecast penjualan dan rekomendasi restock yang dikontrol melalui backoffice.</div></div>
 </div></div>
 @if(!$latestRun)
  <div class="alert alert-info">Belum ada hasil analisis yang tersedia.</div>
 @elseif((int) data_get($latestRun->metrics, 'eligible_series', 0) === 0)
  <div class="alert alert-warning"><b>Data belum cukup untuk prediksi.</b> Ditemukan {{ data_get($latestRun->metrics, 'total_series', 0) }} seri produk, tetapi belum ada yang memiliki minimal {{ data_get($latestRun->metrics, 'minimum_sales_days', 7) }} hari penjualan berbeda. Sistem tidak menerbitkan forecast atau rekomendasi restock agar tidak menyesatkan.</div>
 @endif

 <div class="row g-4 mb-4">
  @foreach([['Rekomendasi',$summary->total ?? 0,'text-primary'],['Risiko Kritis',$summary->critical ?? 0,'text-danger'],['Risiko Tinggi',$summary->high ?? 0,'text-warning'],['Saran Restock',$summary->recommended_units ?? 0,'text-success']] as $metric)
  <div class="col-md-6 col-xl-3"><div class="card ai-card h-100"><div class="card-body"><div class="ai-label">{{ $metric[0] }}</div><div class="ai-metric {{ $metric[2] }}">{{ number_format($metric[1],0,',','.') }}</div><small class="text-muted">{{ $metric[0]==='Saran Restock' ? 'Total unit' : 'Model aktif' }}</small></div></div></div>
  @endforeach
 </div>

 <div class="row g-4 mb-4">
  <div class="col-xl-8"><div class="card ai-card h-100"><div class="card-header"><h5 class="mb-1">Forecast 7 Hari</h5><small class="text-muted">Prediksi unit dan omzet seluruh channel.</small></div><div class="card-body">@if($forecastDaily->isNotEmpty())<div class="chart-wrap"><canvas id="forecastChart"></canvas></div>@else<div class="text-center text-muted py-5">Forecast belum diterbitkan karena histori penjualan belum memenuhi syarat minimum.</div>@endif</div></div></div>
  <div class="col-xl-4"><div class="card ai-card h-100"><div class="card-header"><h5 class="mb-1">Model Aktif</h5></div><div class="card-body">@if($latestRun)<dl class="row mb-0"><dt class="col-5">Model</dt><dd class="col-7">{{ $latestRun->model_name }}</dd><dt class="col-5">Versi</dt><dd class="col-7">{{ $latestRun->model_version }}</dd><dt class="col-5">Training</dt><dd class="col-7">{{ optional($latestRun->training_from)->format('d M Y') }} – {{ optional($latestRun->training_to)->format('d M Y') }}</dd><dt class="col-5">Seri layak</dt><dd class="col-7">{{ data_get($latestRun->metrics, 'eligible_series', 0) }} / {{ data_get($latestRun->metrics, 'total_series', 0) }}</dd><dt class="col-5">Selesai</dt><dd class="col-7">{{ optional($latestRun->completed_at)->format('d M Y H:i') }}</dd><dt class="col-5">Status</dt><dd class="col-7"><span class="badge bg-success">{{ $latestRun->status }}</span></dd></dl>@else<div class="text-muted text-center py-5">Belum ada model aktif.</div>@endif</div></div></div>
 </div>

 <div class="card ai-card"><div class="card-header"><form method="GET" class="row g-2 align-items-end">
  <div class="col-md-4"><label class="form-label">Store</label><select name="store_id" class="form-select"><option value="">Semua Store</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected(($filters['store_id'] ?? '')===$store->id)>{{ $store->name }}</option>@endforeach</select></div>
  <div class="col-md-2"><label class="form-label">Risiko</label><select name="risk_level" class="form-select"><option value="">Semua</option>@foreach(['critical'=>'Kritis','high'=>'Tinggi','medium'=>'Sedang','low'=>'Rendah'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['risk_level'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></div>
  <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['recommended','reviewed','approved','rejected'] as $value)<option value="{{ $value }}" @selected(($filters['status'] ?? '')===$value)>{{ ucfirst($value) }}</option>@endforeach</select></div>
  <div class="col-md-4"><button class="btn btn-primary"><i class="mdi mdi-filter me-1"></i>Filter</button> <a href="{{ route('ai-analytics.index') }}" class="btn btn-outline-secondary">Reset</a></div>
 </form></div><div class="card-body"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Store / Produk</th><th class="text-end">Stok</th><th class="text-end">Prediksi 7 Hari</th><th class="text-end">Safety</th><th class="text-end">Rekomendasi</th><th>Risiko</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
 @forelse($recommendations as $row)<tr><td><b>{{ $row->store->name ?? '-' }}</b><br><small class="text-muted">{{ $row->product->code ?? '-' }} · {{ $row->product->name ?? '-' }}</small></td><td class="text-end">{{ $row->current_stock }} <small class="text-muted">(-{{ $row->reserved_stock }})</small></td><td class="text-end">{{ number_format((float)$row->forecast_qty_7d,1,',','.') }}</td><td class="text-end">{{ $row->safety_stock }}</td><td class="text-end fw-bold">{{ $row->recommended_qty }}</td><td><span class="risk risk-{{ $row->risk_level }}">{{ strtoupper($row->risk_level) }}</span></td><td><span class="status-pill">{{ ucfirst($row->status) }}</span></td><td><form method="POST" action="{{ route('ai-analytics.recommendations.review',$row) }}" class="d-flex gap-1">@csrf @method('PATCH')<input type="hidden" name="review_notes" value="Ditinjau dari dashboard AI"><button name="status" value="approved" class="btn btn-sm btn-success" title="Setujui"><i class="mdi mdi-check"></i></button><button name="status" value="rejected" class="btn btn-sm btn-outline-danger" title="Tolak"><i class="mdi mdi-close"></i></button></form></td></tr>
 @empty<tr><td colspan="8" class="text-center text-muted py-5">Belum ada rekomendasi untuk filter ini.</td></tr>@endforelse
 </tbody></table></div><div class="mt-3">{{ $recommendations->links() }}</div></div></div>
</div>
@endsection

@push('vendor-script')<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>@endpush
@push('page-script')<script>
const rows=@json($forecastDaily);const el=document.getElementById('forecastChart');if(el&&rows.length){new Chart(el,{type:'line',data:{labels:rows.map(r=>new Date(r.forecast_date).toLocaleDateString('id-ID',{day:'2-digit',month:'short'})),datasets:[{label:'Prediksi Unit',data:rows.map(r=>Number(r.predicted_qty||0)),borderColor:'#6d28d9',backgroundColor:'rgba(109,40,217,.13)',fill:true,tension:.35,yAxisID:'y'},{label:'Prediksi Omzet',data:rows.map(r=>Number(r.predicted_revenue||0)),borderColor:'#0891b2',tension:.35,yAxisID:'y1'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true},y1:{beginAtZero:true,position:'right',grid:{drawOnChartArea:false},ticks:{callback:v=>'Rp '+new Intl.NumberFormat('id-ID',{notation:'compact'}).format(v)}}}}});}
</script>@endpush
