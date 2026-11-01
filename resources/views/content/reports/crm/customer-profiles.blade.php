@extends('layouts/contentNavbarLayout')

@section('title', 'CRM - Customer Profile')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">CRM /</span> Customer Profile</h4>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h5 class="mb-1">Daftar Customer</h5>
        <small class="text-muted">Pilih customer untuk melihat profil, semua transaksi, pembelian, dan poin.</small>
      </div>
      <form method="GET" class="d-flex gap-2" action="{{ route('reports.crm.customer-profiles') }}">
        <input class="form-control" name="q" value="{{ $q }}" placeholder="Cari nama, email, atau WhatsApp">
        <button class="btn btn-primary text-nowrap"><i class="mdi mdi-magnify"></i> Cari</button>
      </form>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Customer</th><th>WhatsApp</th><th>Email</th><th class="text-end">Poin Saat Ini</th><th>Terdaftar</th><th class="text-center">Aksi</th></tr></thead>
        <tbody>
          @forelse($customers as $customer)
            <tr>
              <td class="fw-semibold">{{ $customer->name ?: '-' }}</td>
              <td>{{ $customer->wa_number ?: '-' }}</td>
              <td>{{ $customer->email ?: '-' }}</td>
              <td class="text-end"><span class="badge bg-label-primary">{{ number_format((int) $customer->current_balance, 0, ',', '.') }} poin</span></td>
              <td class="text-nowrap">{{ optional($customer->created_at)->format('d M Y') ?: '-' }}</td>
              <td class="text-center"><a href="{{ route('reports.crm.customer-profile', $customer->id) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-account-details-outline me-1"></i> Profil</a></td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Customer tidak ditemukan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($customers->hasPages())<div class="card-body border-top">{{ $customers->links() }}</div>@endif
  </div>
</div>
@endsection
