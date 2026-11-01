@extends('layouts/contentNavbarLayout')
@section('title', 'Fulfillment & Delivery SLA')
@push('page-style') @include('content.monitoring._monitoring-styles') @endpush
@section('content')
<div class="monitor-shell">
  <div class="monitor-hero mb-4"><h4 class="mb-2">Fulfillment & Delivery SLA</h4><p class="mb-0">Pantau order belum diproses, belum mendapat Store/Warehouse penanggung jawab, tidak mempunyai resi, atau melewati batas 24 jam.</p></div>
  @include('content.monitoring._summary', ['items'=>[
    ['label'=>'Order pending','value'=>$summary['pending'],'icon'=>'mdi-cart-clock','tone'=>'warning','hint'=>'Antrean'],
    ['label'=>'Belum ada handler','value'=>$summary['without_handler'],'icon'=>'mdi-account-question-outline','tone'=>'danger','hint'=>'Assign'],
    ['label'=>'Tanpa resi','value'=>$summary['without_awb'],'icon'=>'mdi-barcode-off','tone'=>'danger','hint'=>'Dokumen'],
    ['label'=>'Melewati 24 jam','value'=>$summary['over_24h'],'icon'=>'mdi-clock-alert-outline','tone'=>'warning','hint'=>'SLA'],
  ]])
  <div class="card monitor-card"><div class="table-responsive"><table class="table monitor-table mb-0"><thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Penanggung Jawab</th><th>Resi</th><th>Nilai</th><th>Umur Order</th></tr></thead><tbody>
  @forelse($ecommerce as $row)<tr><td><strong>{{ $row->order_number }}</strong><div class="small text-muted">{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') }}</div></td><td>{{ $row->customer_name ?: '-' }}</td><td><span class="status-pill info">{{ \Illuminate\Support\Str::headline((string)$row->status) }}</span></td><td>{{ $row->store_name ?: $row->warehouse_name ?: 'Belum ditentukan' }}</td><td>{{ $row->shipping_number ?: 'Belum tersedia' }}</td><td>Rp{{ number_format((float)$row->grand_total,0,',','.') }}</td><td>{{ \Carbon\Carbon::parse($row->created_at)->diffForHumans(null,true) }}</td></tr>
  @empty<tr><td colspan="7" class="monitor-empty">Belum ada order Ecommerce.</td></tr>@endforelse
  </tbody></table></div><div class="card-body">{{ $ecommerce->links() }}</div></div>
</div>
@endsection
