@extends('layouts/contentNavbarLayout')

@section('title', 'CRM - Customer Profile')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap py-3 mb-2">
    <div>
      <h4 class="mb-1"><span class="text-muted fw-light">CRM / Customer Profile /</span> {{ $customer->name }}</h4>
      <div class="text-muted">Riwayat transaksi, pembelian, dan poin pelanggan.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('reports.crm.customer-profile.export', $customer->id) }}" class="btn btn-success">
        <i class="mdi mdi-file-excel-outline me-1"></i> Export Customer
      </a>
      <a href="{{ route('reports.crm.index') }}" class="btn btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> Kembali ke CRM
      </a>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4"><div class="text-muted small">Nama Customer</div><div class="fw-semibold">{{ $customer->name ?: '-' }}</div></div>
        <div class="col-md-4"><div class="text-muted small">WhatsApp</div><div class="fw-semibold">{{ $customer->wa_number ?: '-' }}</div></div>
        <div class="col-md-4"><div class="text-muted small">Email</div><div class="fw-semibold">{{ $customer->email ?: '-' }}</div></div>
        <div class="col-md-8"><div class="text-muted small">Alamat</div><div class="fw-semibold">{{ $customer->address ?: '-' }}</div></div>
        <div class="col-md-4"><div class="text-muted small">Terdaftar</div><div class="fw-semibold">{{ optional($customer->created_at)->format('d M Y H:i') ?: '-' }}</div></div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Poin Saat Ini</div><h4 class="mb-0 text-primary">{{ number_format((int) ($pointSummary->current_balance ?? 0), 0, ',', '.') }}</h4></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Total Transaksi</div><h4 class="mb-0">{{ number_format((int) ($transactionSummary->total_transactions ?? 0), 0, ',', '.') }}</h4></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Total Belanja</div><h4 class="mb-0">Rp {{ number_format((float) ($transactionSummary->total_spending ?? 0), 0, ',', '.') }}</h4></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">Total Kunjungan POS</div><h4 class="mb-0">{{ number_format($totalVisits, 0, ',', '.') }}</h4></div></div></div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div><h5 class="mb-1">Semua Transaksi & Pembelian</h5><small class="text-muted">Menampilkan seluruh riwayat transaksi yang berhasil; halaman ini dibatasi 20 transaksi per halaman.</small></div>
      <div class="btn-group">
        <a class="btn btn-sm {{ $channel === '' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('reports.crm.customer-profile', $customer->id) }}">Semua</a>
        <a class="btn btn-sm {{ $channel === 'pos' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('reports.crm.customer-profile', [$customer->id, 'channel' => 'pos']) }}">POS</a>
        <a class="btn btn-sm {{ $channel === 'ecom' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('reports.crm.customer-profile', [$customer->id, 'channel' => 'ecom']) }}">E-commerce</a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead class="table-light"><tr><th>Tanggal</th><th>Channel</th><th>Transaksi</th><th>Store</th><th>Rincian Pembelian</th><th>Status</th><th class="text-end">Total</th></tr></thead>
        <tbody>
          @forelse($transactions as $transaction)
            <tr>
              <td class="text-nowrap">{{ $transaction->transaction_at ? \Carbon\Carbon::parse($transaction->transaction_at)->format('d M Y H:i') : '-' }}</td>
              <td><span class="badge bg-label-primary">{{ $transaction->channel }}</span></td>
              <td><div class="fw-semibold">{{ $transaction->transaction_code }}</div>@if($transaction->visit_code && $transaction->visit_code !== '-')<small class="text-muted">Visit: {{ $transaction->visit_code }}</small>@endif</td>
              <td>{{ $transaction->store_name }}</td>
              <td style="min-width:260px">{{ $transaction->purchase_details }}</td>
              <td><span class="badge bg-label-success">{{ $transaction->transaction_status }}</span></td>
              <td class="text-end text-nowrap">Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi berhasil untuk customer ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($transactions->hasPages())<div class="card-body border-top">{{ $transactions->links() }}</div>@endif
  </div>

  <div class="row g-4">
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Ringkasan Poin</h5></div><div class="card-body"><div class="d-flex justify-content-between mb-3"><span>Poin Masuk</span><strong class="text-success">+{{ number_format((int) ($pointSummary->total_point_in ?? 0), 0, ',', '.') }}</strong></div><div class="d-flex justify-content-between mb-3"><span>Poin Digunakan</span><strong class="text-danger">-{{ number_format((int) ($pointSummary->total_point_out ?? 0), 0, ',', '.') }}</strong></div><div class="d-flex justify-content-between border-top pt-3"><span>Saldo Saat Ini</span><strong class="text-primary">{{ number_format((int) ($pointSummary->current_balance ?? 0), 0, ',', '.') }} poin</strong></div></div></div></div>
    <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h5 class="mb-0">10 Mutasi Poin Terakhir</h5></div><div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Tipe</th><th>Deskripsi</th><th class="text-end">Poin</th><th class="text-end">Saldo</th></tr></thead><tbody>@forelse($pointMutations as $mutation)<tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($mutation->created_at)->format('d M Y H:i') }}</td><td>{{ $mutation->mutation_type ?: $mutation->type ?: '-' }}</td><td>{{ $mutation->description ?: $mutation->ref ?: '-' }}</td><td class="text-end {{ (int) $mutation->points >= 0 ? 'text-success' : 'text-danger' }}">{{ (int) $mutation->points >= 0 ? '+' : '' }}{{ number_format((int) $mutation->points, 0, ',', '.') }}</td><td class="text-end">{{ $mutation->balance_after === null ? '-' : number_format((int) $mutation->balance_after, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada mutasi poin.</td></tr>@endforelse</tbody></table></div></div></div>
  </div>
</div>
@endsection
