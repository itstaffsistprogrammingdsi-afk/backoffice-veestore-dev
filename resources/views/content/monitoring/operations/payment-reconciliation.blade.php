@extends('layouts/contentNavbarLayout')
@section('title', 'Rekonsiliasi Pembayaran')
@push('page-style') @include('content.monitoring._monitoring-styles') @endpush
@section('content')
<div class="monitor-shell">
  <div class="monitor-hero mb-4"><h4 class="mb-2">Rekonsiliasi Order & Pembayaran</h4><p class="mb-0">Mencocokkan nilai order dengan pembayaran untuk menemukan referensi hilang, nominal berbeda, pembayaran pending, dan transaksi void.</p></div>
  @include('content.monitoring._summary', ['items'=>[
    ['label'=>'Total order Ecommerce','value'=>$summary['orders'],'icon'=>'mdi-cart-outline'],
    ['label'=>'Order tanpa payment','value'=>$summary['without_payment'],'icon'=>'mdi-credit-card-off-outline','tone'=>'danger','hint'=>'Exception'],
    ['label'=>'Payment pending','value'=>$summary['pending_payment'],'icon'=>'mdi-credit-card-clock-outline','tone'=>'warning','hint'=>'Pending'],
    ['label'=>'Payment void','value'=>$summary['voided'],'icon'=>'mdi-cancel','tone'=>'danger','hint'=>'Audit'],
  ]])
  <div class="card monitor-card"><div class="card-body"><form class="filter-panel row g-2 align-items-end mb-4"><div class="col-md-8"><label class="form-label">Cari order, transaksi, atau customer</label><input name="q" value="{{ request('q') }}" class="form-control"></div><div class="col-md-2"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="exception_only" value="1" id="exceptionOnly" @checked(request('exception_only'))><label class="form-check-label" for="exceptionOnly">Hanya masalah</label></div></div><div class="col-md-2 d-grid"><button class="btn btn-primary">Terapkan</button></div></form>
  <div class="table-responsive"><table class="table monitor-table"><thead><tr><th>Order</th><th>Customer</th><th>Nilai Order</th><th>Payment</th><th>Nilai Payment</th><th>Status</th><th>Hasil Rekonsiliasi</th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td><strong>{{ $row->order_number }}</strong><div class="small text-muted">{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') }}</div></td><td>{{ $row->customer_name ?: '-' }}</td><td>Rp{{ number_format((float)$row->grand_total,0,',','.') }}</td><td>{{ $row->transaction_number ?: '-' }}<div class="small text-muted">{{ $row->payment_method_name ?: '-' }}</div></td><td>Rp{{ number_format((float)$row->total_amount,0,',','.') }}</td><td>{{ \Illuminate\Support\Str::headline((string)($row->payment_status ?: $row->order_status)) }}</td><td><span class="status-pill {{ $row->reconciliation_status==='Sesuai'?'success':'danger' }}">{{ $row->reconciliation_status }}</span></td></tr>
    @empty<tr><td colspan="7" class="monitor-empty">Tidak ada data yang sesuai.</td></tr>@endforelse
  </tbody></table></div>{{ $rows->links() }}</div></div>
</div>
@endsection
