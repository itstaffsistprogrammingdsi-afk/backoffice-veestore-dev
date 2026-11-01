@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Stok per Cabang')

@push('page-style')
<style>
  .stock-filter-box { background: #f8f9fa; border: 1px dashed #d9dee3; border-radius: .75rem; padding: 1rem; }
  .filter-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
  .stock-table th, .stock-table td { vertical-align: middle; white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">Monitoring /</span> Stok per Cabang</h4>

  <div class="card">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1">Laporan Stok per Cabang</h5>
        <small class="text-muted">Pantau stok produk di setiap cabang.</small>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#advancedFilterStokCabang">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>
        <a href="{{ route('monitoring.stok-percabang') }}" class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>
        <a href="{{ route('monitoring.stok-percabang.export', request()->query()) }}" class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ request()->hasAny(['store_id', 'q', 'min']) ? 'show' : '' }}" id="advancedFilterStokCabang">
        <div class="stock-filter-box">
          <form method="GET" action="{{ route('monitoring.stok-percabang') }}" class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Cabang</label>
              <select name="store_id" class="form-select">
                <option value="">Semua Cabang</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected(($filters['store_id'] ?? '') == $store->id)>
                    {{ $store->name }}@if($store->code) ({{ $store->code }})@endif
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Cari</label>
              <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cabang / kode produk / nama produk">
            </div>
            <div class="col-md-4">
              <label class="form-label">Minimal Stok Tersedia</label>
              <input type="number" name="min" min="0" class="form-control" value="{{ $filters['min'] ?? 0 }}">
            </div>
            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="mdi mdi-magnify me-1"></i> Tampilkan</button>
                <a href="{{ route('monitoring.stok-percabang.export', request()->query()) }}" class="btn btn-success btn-sm"><i class="mdi mdi-file-excel-outline me-1"></i> Export Excel</a>
                <a href="{{ route('monitoring.stok-percabang') }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-refresh me-1"></i> Reset</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover mb-0 stock-table">
        <thead class="table-light">
          <tr>
            <th>No</th><th>Cabang</th><th>Kode Produk</th><th>Nama Produk</th><th>Satuan</th>
            <th class="text-end">Stok Gudang</th><th class="text-end">Display</th><th class="text-end">Stok Real</th><th class="text-end">Dipesan</th><th class="text-end">Stok Tersedia</th><th class="text-end">Good</th>
            <th class="text-end">Good Setelah Perbaikan</th><th class="text-end">Bad</th><th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $row)
            <tr>
              <td>{{ $rows->firstItem() + $loop->index }}</td>
              <td>{{ $row->store_name }}@if($row->store_code) <span class="text-muted">({{ $row->store_code }})</span>@endif</td>
              <td>{{ $row->product_code }}</td><td>{{ $row->product_name }}</td><td>{{ $row->unit_name ?: '-' }}</td>
              <td class="text-end">{{ number_format($row->qty_store) }}</td><td class="text-end">{{ number_format($row->qty_display) }}</td>
              <td class="text-end fw-semibold">{{ number_format($row->qty_real) }}</td><td class="text-end text-warning">
                @if((int) $row->qty_reserved > 0)
                  <button type="button" class="btn btn-sm btn-link p-0 text-warning fw-medium btn-reservation-detail"
                    data-location-id="{{ $row->store_id }}" data-product-id="{{ $row->product_id }}">
                    {{ number_format($row->qty_reserved) }} <i class="mdi mdi-information-outline"></i>
                  </button>
                @else
                  0
                @endif
              </td><td class="text-end text-success fw-semibold">{{ number_format($row->qty_available) }}</td>
              <td class="text-end">{{ number_format($row->qty_good) }}</td><td class="text-end">{{ number_format($row->qty_good_after_repair) }}</td>
              <td class="text-end">{{ number_format($row->qty_bad) }}</td><td class="text-end fw-semibold">{{ number_format($row->total_qty) }}</td>
            </tr>
          @empty
            <tr><td colspan="14" class="text-center text-muted py-4">Belum ada data stok.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <small class="text-muted">Menampilkan {{ $rows->firstItem() ?? 0 }} - {{ $rows->lastItem() ?? 0 }} dari {{ number_format($rows->total()) }} data</small>
      {{ $rows->links() }}
    </div>
  </div>
</div>

<div class="modal fade" id="reservationDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Detail Stok Dipesan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="reservationDetailBody"></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>
@endsection

@push('page-script')
<script>
(function () {
  'use strict';
  const escapeHtml = function (value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  };

  document.querySelectorAll('.btn-reservation-detail').forEach(function (button) {
    button.addEventListener('click', function () {
      const params = new URLSearchParams({ store_id: button.dataset.locationId, product_id: button.dataset.productId });
      const body = document.getElementById('reservationDetailBody');
      body.innerHTML = '<div class="text-center text-muted py-4">Memuat detail pesanan…</div>';
      new bootstrap.Modal(document.getElementById('reservationDetailModal')).show();

      fetch('{{ route('monitoring.stok-percabang.reservations') }}?' + params.toString(), {
        headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
      })
      .then(function (response) {
        if (!response.ok) throw new Error('Gagal memuat detail pesanan.');
        return response.json();
      })
      .then(function (data) {
        const rows = (data.rows || []).map(function (row) {
          return '<tr><td>' + escapeHtml(row.order_number || '-') + '</td><td>' + escapeHtml(row.order_type || '-') + '</td><td>' +
            escapeHtml(row.ordered_for || '-') + '</td><td>' + escapeHtml(row.ordered_by || '-') + '</td><td class="text-end">' +
            Number(row.remaining_quantity || 0).toLocaleString('id-ID') + '</td></tr>';
        }).join('');
        body.innerHTML = '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>No. Pesanan</th><th>Tujuan</th><th>Untuk</th><th>Dipesan Oleh</th><th class="text-end">Sisa Dipesan</th></tr></thead><tbody>' +
          (rows || '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada reservasi aktif.</td></tr>') + '</tbody></table></div>';
      })
      .catch(function (error) {
        body.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message || 'Gagal memuat detail pesanan.') + '</div>';
      });
    });
  });
})();
</script>
@endpush
