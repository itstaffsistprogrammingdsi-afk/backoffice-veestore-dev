@extends('layouts/contentNavbarLayout')
@section('title', 'Monitoring Quality Control')
@push('page-style') @include('content.monitoring._monitoring-styles') @endpush
@section('content')
<div class="monitor-shell">
  <div class="monitor-hero mb-4"><h4 class="mb-2">Quality Control Terpadu</h4><p class="mb-0">Satu tampilan untuk pemeriksaan barang di Warehouse dan Store, kondisi barang, hasil QC, pelaksana, dan waktu pemeriksaan.</p></div>
  @include('content.monitoring._summary', ['items'=>[
    ['label'=>'QC Warehouse','value'=>$summary['warehouse'],'icon'=>'mdi-warehouse'],
    ['label'=>'QC Store','value'=>$summary['store'],'icon'=>'mdi-store-check-outline'],
    ['label'=>'QC ditolak','value'=>$summary['rejected'],'icon'=>'mdi-close-octagon-outline','tone'=>'danger','hint'=>'Perlu tindakan'],
    ['label'=>'QC hari ini','value'=>$summary['today'],'icon'=>'mdi-calendar-check-outline','tone'=>'success','hint'=>'Hari ini'],
  ]])
  <div class="card monitor-card"><div class="card-body"><form class="filter-panel row g-2 mb-4"><div class="col-md-10"><label class="form-label">Cari produk, kode, lokasi, atau kondisi</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Masukkan kata pencarian"></div><div class="col-md-2 d-grid align-self-end"><button class="btn btn-primary">Cari</button></div></form>
  <div class="table-responsive"><table class="table monitor-table"><thead><tr><th>Sumber</th><th>Produk</th><th>Lokasi</th><th>Jenis QC</th><th>Kondisi / Hasil</th><th>Pelaksana</th><th>Waktu</th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td><span class="status-pill {{ $row->source==='Warehouse'?'info':'success' }}">{{ $row->source }}</span></td><td><strong>{{ $row->product_name ?: '-' }}</strong><div class="small text-muted">{{ $row->product_code ?: '-' }}</div></td><td>{{ $row->location_name ?: '-' }}</td><td>{{ \Illuminate\Support\Str::headline((string)$row->qc_type) }}</td><td>{{ $row->condition_name ?: \Illuminate\Support\Str::headline((string)$row->status) ?: '-' }}<div class="small text-muted">{{ $row->note ?? '' }}</div></td><td>{{ $row->performer_name ?: '-' }}</td><td>{{ ($row->performed_at ?? $row->created_at) ? \Carbon\Carbon::parse($row->performed_at ?? $row->created_at)->format('d M Y H:i') : '-' }}</td></tr>
    @empty<tr><td colspan="7" class="monitor-empty">Belum ada aktivitas QC sesuai pencarian.</td></tr>@endforelse
  </tbody></table></div></div></div>
</div>
@endsection
