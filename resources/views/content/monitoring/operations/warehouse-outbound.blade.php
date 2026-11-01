@extends('layouts/contentNavbarLayout')
@section('title', 'Monitoring Warehouse Outbound')
@push('page-style') @include('content.monitoring._monitoring-styles') @endpush
@section('content')
<div class="monitor-shell">
  <div class="monitor-hero mb-4"><h4 class="mb-2">Warehouse Outbound & Delivery SLA</h4><p class="mb-0">Pantau barang keluar, kelengkapan dokumen, proses release, kurir, resi, dan selisih jumlah item.</p></div>
  @include('content.monitoring._summary', ['items'=>[
    ['label'=>'Total outbound','value'=>$summary['total'],'icon'=>'mdi-truck-fast-outline'],
    ['label'=>'Belum release','value'=>$summary['unreleased'],'icon'=>'mdi-timer-alert-outline','tone'=>'warning','hint'=>'Periksa'],
    ['label'=>'Tanpa resi','value'=>$summary['without_awb'],'icon'=>'mdi-barcode-off','tone'=>'danger','hint'=>'Tidak lengkap'],
    ['label'=>'Dibuat hari ini','value'=>$summary['today'],'icon'=>'mdi-calendar-today','tone'=>'info','hint'=>'Hari ini'],
  ]])
  <div class="card monitor-card"><div class="card-body"><form class="filter-panel row g-2 align-items-end mb-4">
    <div class="col-md-7"><label class="form-label">Cari dokumen, order, atau resi</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Invoice / delivery order / nomor order / resi"></div>
    <div class="col-md-3"><label class="form-label">Status release</label><select name="status" class="form-select"><option value="">Semua</option><option value="unreleased" @selected(request('status')==='unreleased')>Belum release</option><option value="released" @selected(request('status')==='released')>Sudah release</option></select></div>
    <div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="mdi mdi-filter-outline me-1"></i>Terapkan</button></div>
  </form>
  <div class="table-responsive"><table class="table monitor-table"><thead><tr><th>Dokumen</th><th>Warehouse</th><th>Order / Tipe</th><th>Item</th><th>Pengiriman</th><th>Release</th><th>Status</th></tr></thead><tbody>
    @forelse($rows as $row)<tr>
      <td><strong>{{ $row->invoice_number ?: '-' }}</strong><div class="small text-muted">{{ $row->delivery_order_number ?: 'DO belum tersedia' }}</div></td>
      <td>{{ $row->warehouse_name ?: '-' }}<div class="small text-muted">{{ $row->user_name ?: 'User tidak tercatat' }}</div></td>
      <td>{{ $row->order_number ?: '-' }}<div class="small text-muted">{{ \Illuminate\Support\Str::headline((string)$row->outbound_type) }}</div></td>
      <td><strong>{{ number_format((int)$row->quantity_item,0,',','.') }}</strong><div class="small text-muted">{{ number_format((int)$row->scanned_items,0,',','.') }} detail scan</div></td>
      <td>{{ $row->courier_name ?: 'Kurir belum dipilih' }}<div class="small text-muted">{{ $row->shipping_number ?: 'Resi belum tersedia' }}</div></td>
      <td>{{ $row->released_at ? \Carbon\Carbon::parse($row->released_at)->format('d M Y H:i') : '-' }}</td>
      <td>@if(!$row->released_at)<span class="status-pill warning">Menunggu release</span>@elseif(!$row->shipping_number)<span class="status-pill danger">Resi kosong</span>@else<span class="status-pill success">Siap dikirim</span>@endif</td>
    </tr>@empty<tr><td colspan="7" class="monitor-empty">Belum ada data outbound sesuai filter.</td></tr>@endforelse
  </tbody></table></div>{{ $rows->links() }}</div></div>
</div>
@endsection
