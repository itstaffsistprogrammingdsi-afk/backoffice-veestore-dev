@extends('layouts/contentNavbarLayout')

@section('title', 'Insight Supplier')

@push('page-style')
@include('content.monitoring._monitoring-styles')
<style>
  .supplier-score{width:48px;height:48px;border-radius:50%;display:grid;place-items:center;font-weight:800;background:#eef2ff;color:#4f46e5}
  .risk-bar{height:8px;border-radius:99px;background:#eef0f5;overflow:hidden}.risk-bar>span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#f59e0b,#ef4444)}
  .trend-column{min-width:52px;flex:1;text-align:center}.trend-bars{height:120px;display:flex;align-items:flex-end;justify-content:center;gap:4px}.trend-bar{width:12px;border-radius:5px 5px 0 0;min-height:3px}
  .legend-dot{width:9px;height:9px;border-radius:50%;display:inline-block}
  .supplier-risk-highlight{min-width:220px;padding:1rem;border-radius:1rem;background:#fff;color:#241433;box-shadow:0 .5rem 1.25rem rgba(30,12,48,.16)}
  .supplier-risk-highlight__label{display:block;margin-bottom:.3rem;color:#665877;font-size:.75rem;font-weight:700;letter-spacing:.02em}
  .supplier-risk-highlight__name{display:block;color:#241433;font-size:1rem}
  .supplier-risk-highlight__stats{margin-top:.25rem;color:#665877;font-size:.8rem}
</style>
@endpush

@section('content')
<div class="monitor-shell">
  <div class="monitor-hero mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
      <div class="small text-uppercase fw-bold opacity-75 mb-1">Supplier Performance</div>
      <h4 class="mb-2">Insight Kualitas Supplier</h4>
      <p class="mb-0">Menilai kualitas barang yang diterima, hasil QC, dan produk asal supplier yang sering masuk proses retur Store.</p>
    </div>
    @if($topRisk)
      <div class="supplier-risk-highlight">
        <small class="supplier-risk-highlight__label">Supplier paling perlu diperiksa</small>
        <strong class="supplier-risk-highlight__name">{{ $topRisk->supplier_name }}</strong>
        <div class="supplier-risk-highlight__stats">{{ number_format((int)$topRisk->damaged_items,0,',','.') }} rusak · {{ number_format((int)$topRisk->returned_items,0,',','.') }} retur</div>
      </div>
    @endif
  </div>

  <div class="card monitor-card mb-4">
    <div class="card-body">
      <form method="GET" class="filter-panel row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Supplier</label>
          <select name="supplier_id" class="form-select">
            <option value="">Semua supplier</option>
            @foreach($suppliers as $supplier)
              <option value="{{ $supplier->id }}" @selected($filters['supplier_id']===$supplier->id)>{{ $supplier->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Cari supplier / produk</label>
          <input name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Nama atau kode produk">
        </div>
        <div class="col-md-2"><label class="form-label">Dari tanggal</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Sampai tanggal</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="mdi mdi-filter-outline me-1"></i>Terapkan</button></div>
      </form>
    </div>
  </div>

  @include('content.monitoring._summary', ['items'=>[
    ['label'=>'Supplier dipantau','value'=>(int)($summary->suppliers_monitored ?? 0),'icon'=>'mdi-truck-delivery-outline'],
    ['label'=>'Total barang diterima','value'=>(int)($summary->total_items ?? 0),'icon'=>'mdi-package-variant-closed'],
    ['label'=>'Rusak / QC reject','value'=>(int)($summary->damaged_items ?? 0),'icon'=>'mdi-package-variant-remove','tone'=>'danger','hint'=>$damageRate.'%'],
    ['label'=>'Pernah diretur Store','value'=>(int)($summary->returned_items ?? 0),'icon'=>'mdi-keyboard-return','tone'=>'warning','hint'=>'Investigasi'],
  ]])

  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card monitor-card h-100">
        <div class="card-header">
          <h5 class="mb-1">Peringkat risiko supplier</h5>
          <small class="text-muted">Diurutkan berdasarkan barang rusak dan barang yang pernah diretur.</small>
        </div>
        <div class="table-responsive">
          <table class="table monitor-table mb-0">
            <thead><tr><th>Supplier</th><th>Inbound</th><th>Diterima</th><th>Rusak / Reject</th><th>Retur Store</th><th>Rasio</th><th>Rekomendasi</th></tr></thead>
            <tbody>
            @forelse($supplierRows as $row)
              @php
                $issueCount=(int)$row->damaged_items+(int)$row->returned_items;
                $riskRate=(float)$row->damage_rate+(float)$row->return_rate;
                $tone=$riskRate>=10?'danger':($riskRate>=3?'warning':'success');
              @endphp
              <tr>
                <td><strong>{{ $row->supplier_name }}</strong><div class="small text-muted">{{ $row->supplier_country ?: 'Negara tidak tercatat' }} · {{ $row->supplier_phone ?: 'Telepon tidak tercatat' }}</div></td>
                <td>{{ number_format((int)$row->total_inbounds,0,',','.') }}<div class="small text-muted">{{ $row->last_inbound_at ? \Carbon\Carbon::parse($row->last_inbound_at)->format('d M Y') : '-' }}</div></td>
                <td>{{ number_format((int)$row->total_items,0,',','.') }}</td>
                <td><strong class="text-danger">{{ number_format((int)$row->damaged_items,0,',','.') }}</strong><div class="small text-muted">{{ number_format((float)$row->damage_rate,2,',','.') }}%</div></td>
                <td><strong class="text-warning">{{ number_format((int)$row->returned_items,0,',','.') }}</strong><div class="small text-muted">{{ number_format((float)$row->return_rate,2,',','.') }}%</div></td>
                <td style="min-width:120px"><div class="risk-bar"><span style="width:{{ min(100,$riskRate) }}%"></span></div><small class="text-muted">{{ number_format($riskRate,2,',','.') }}% sinyal risiko</small></td>
                <td>
                  @if($riskRate>=10)<span class="status-pill danger">Evaluasi segera</span>
                  @elseif($riskRate>=3)<span class="status-pill warning">Pantau ketat</span>
                  @elseif($issueCount>0)<span class="status-pill info">Periksa sampel</span>
                  @else<span class="status-pill success">Baik</span>@endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="monitor-empty">Belum ada barang supplier pada periode yang dipilih.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-body">{{ $supplierRows->links() }}</div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card monitor-card h-100">
        <div class="card-header"><h5 class="mb-1">Tren masalah per bulan</h5><small class="text-muted">Dibandingkan dengan jumlah barang yang diterima.</small></div>
        <div class="card-body">
          @php($maxTrend=max(1,(int)$monthlyTrend->max('received_items')))
          @if($monthlyTrend->isNotEmpty())
            <div class="d-flex gap-2 overflow-auto pb-2">
              @foreach($monthlyTrend as $trend)
                <div class="trend-column">
                  <div class="trend-bars">
                    <span class="trend-bar bg-danger" title="Rusak {{ $trend->damaged_items }}" style="height:{{ max(3,((int)$trend->damaged_items/$maxTrend)*110) }}px"></span>
                    <span class="trend-bar bg-warning" title="Retur {{ $trend->returned_items }}" style="height:{{ max(3,((int)$trend->returned_items/$maxTrend)*110) }}px"></span>
                    <span class="trend-bar bg-primary opacity-50" title="Diterima {{ $trend->received_items }}" style="height:{{ max(3,((int)$trend->received_items/$maxTrend)*110) }}px"></span>
                  </div>
                  <small class="text-muted">{{ \Carbon\Carbon::createFromFormat('Y-m',$trend->period)->format('M y') }}</small>
                </div>
              @endforeach
            </div>
            <div class="d-flex flex-wrap gap-3 mt-3 small">
              <span><i class="legend-dot bg-danger me-1"></i>Rusak/QC reject</span>
              <span><i class="legend-dot bg-warning me-1"></i>Retur Store</span>
              <span><i class="legend-dot bg-primary opacity-50 me-1"></i>Diterima</span>
            </div>
          @else
            <div class="monitor-empty">Belum ada tren pada periode ini.</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="card monitor-card mb-4">
    <div class="card-header"><h5 class="mb-1">Produk supplier paling bermasalah</h5><small class="text-muted">Gunakan daftar ini untuk menentukan produk yang perlu sampling QC lebih banyak atau dibicarakan dengan supplier.</small></div>
    <div class="table-responsive">
      <table class="table monitor-table mb-0">
        <thead><tr><th>Supplier</th><th>Produk</th><th>Diperiksa</th><th>Rusak / Reject</th><th>Retur Store</th><th>Kondisi</th><th>Catatan QC</th></tr></thead>
        <tbody>
        @forelse($productRisks as $row)
          <tr>
            <td><strong>{{ $row->supplier_name }}</strong></td>
            <td>{{ $row->product_name }}<div class="small text-muted">{{ $row->product_code ?: '-' }}</div></td>
            <td>{{ number_format((int)$row->total_items,0,',','.') }}</td>
            <td><span class="status-pill danger">{{ number_format((int)$row->damaged_items,0,',','.') }}</span></td>
            <td><span class="status-pill warning">{{ number_format((int)$row->returned_items,0,',','.') }}</span></td>
            <td>{{ $row->damage_conditions ?: '-' }}</td>
            <td><small>{{ $row->latest_note ?: '-' }}</small></td>
          </tr>
        @empty
          <tr><td colspan="7" class="monitor-empty"><i class="mdi mdi-check-decagram-outline fs-1 text-success"></i><div>Belum ada produk bermasalah pada periode ini.</div></td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="alert alert-info mb-0">
    <i class="mdi mdi-information-outline me-1"></i>
    <strong>Cara membaca insight:</strong> rusak/QC reject menunjukkan masalah saat pemeriksaan Warehouse. Retur Store menunjukkan barang asal supplier pernah masuk proses retur, tetapi penyebab akhirnya tetap perlu diperiksa karena dapat berasal dari pengiriman, penyimpanan, Store, atau customer.
  </div>
</div>
@endsection
