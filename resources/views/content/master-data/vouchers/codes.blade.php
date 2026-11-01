@extends('layouts/contentNavbarLayout')

@section('title', 'Voucher Codes - ' . $voucher->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 mb-3">
    <div>
      <h4 class="mb-1"><span class="text-muted fw-light">Master Promo & Discount / Voucher /</span> Kode Voucher</h4>
      <div class="text-muted">{{ $voucher->name }} <span class="badge bg-label-primary ms-1">{{ $voucher->code }}</span></div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('master-data.vouchers.index') }}"><i class="mdi mdi-arrow-left me-1"></i>Kembali</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
  @endif

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Daftar Unique Code</span>
      <div class="d-flex gap-2">
        <span class="badge bg-label-success">{{ number_format($usedCodesCount) }} digunakan</span>
        <span class="badge bg-label-secondary">{{ number_format($codes->total()) }} kode</span>
      </div>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table mb-0">
        <thead class="table-light"><tr><th>#</th><th>Kode</th><th>Berakhir</th><th>Penggunaan</th><th>Status Kode</th><th class="text-center">Aksi</th></tr></thead>
        <tbody>
          @forelse($codes as $index => $voucherCode)
          <tr>
            <td>{{ $codes->firstItem() + $index }}</td>
            <td><span class="badge bg-label-primary fs-6">{{ $voucherCode->code }}</span></td>
            <td>{{ optional($voucherCode->expires_at)->format('d M Y H:i') ?: '-' }}</td>
            <td>
              @if($voucherCode->usages_count > 0)
                <span class="badge bg-label-success"><i class="mdi mdi-check-circle-outline me-1"></i>Digunakan</span>
                <small class="text-muted d-block mt-1">{{ number_format($voucherCode->usages_count) }} kali</small>
              @else
                <span class="badge bg-label-secondary">Belum digunakan</span>
              @endif
            </td>
            <td>{!! $voucherCode->is_active ? '<span class="badge bg-label-success">Aktif</span>' : '<span class="badge bg-label-danger">Nonaktif</span>' !!}</td>
            <td class="text-center">
              @can('update master-data-vouchers')
              <form method="POST" action="{{ route('master-data.vouchers.codes.status', [$voucher->id, $voucherCode->id]) }}" class="d-inline">
                @csrf @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $voucherCode->is_active ? 0 : 1 }}">
                <button class="btn btn-sm {{ $voucherCode->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">
                  <i class="mdi {{ $voucherCode->is_active ? 'mdi-close-circle-outline' : 'mdi-check-circle-outline' }} me-1"></i>{{ $voucherCode->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
              </form>
              @endcan
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center text-muted py-5">Belum ada kode voucher.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($codes->hasPages())<div class="card-footer d-flex justify-content-end">{{ $codes->links() }}</div>@endif
  </div>
</div>
@endsection
