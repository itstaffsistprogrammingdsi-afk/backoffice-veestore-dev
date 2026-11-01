@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Warehouse Stock')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
    .select2-container {
        width: 100% !important;
    }

    .table-nowrap th,
    .table-nowrap td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .stock-filter-box {
        background: #f8f9fa;
        border: 1px dashed #d9dee3;
        border-radius: 0.75rem;
        padding: 1rem;
    }

    .stock-table tbody tr:hover {
        background-color: rgba(115, 103, 240, 0.04);
        transition: 0.2s ease;
    }

    .filter-actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">Monitoring /</span> Warehouse Stock
    </h4>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            <i class="mdi mdi-check-circle-outline me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible" role="alert">
            <i class="mdi mdi-alert-circle-outline me-1"></i> Terjadi kesalahan. Periksa kembali filter Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <h5 class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <span>Laporan Stok Warehouse</span>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button"
                    class="btn btn-outline-secondary btn-sm"
                    data-bs-toggle="collapse"
                    data-bs-target="#advancedFilterWms">
                    <i class="mdi mdi-filter-variant me-1"></i> Filter
                </button>

                <a href="{{ route('monitoring.stok-wms') }}"
                    class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-refresh me-1"></i> Reset
                </a>
            </div>
        </h5>

        <div class="px-4 pb-3">
            <div class="collapse {{ request()->hasAny(['warehouse_id', 'min']) ? 'show' : '' }}"
                id="advancedFilterWms">
                <div class="stock-filter-box">
                    <form method="GET" action="{{ route('monitoring.stok-wms') }}" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Cari</label>
                            <input type="text"
                                name="q"
                                class="form-control"
                                value="{{ $filters['q'] ?? '' }}"
                                placeholder="Warehouse / kode produk / nama produk">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Warehouse</label>
                            <select name="warehouse_id" class="form-select js-select2-filter" data-placeholder="Semua Warehouse">
                                <option value="">Semua Warehouse</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected(($filters['warehouse_id'] ?? '') == $warehouse->id)>
                                        {{ $warehouse->name }} @if($warehouse->code) ({{ $warehouse->code }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Minimal Stok Tersedia</label>
                            <input type="number"
                                name="min"
                                class="form-control"
                                min="0"
                                value="{{ $filters['min'] ?? 0 }}">
                        </div>

                        <div class="col-12">
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="mdi mdi-magnify me-1"></i> Tampilkan
                                </button>

                                <a href="{{ route('monitoring.stok-wms.export', request()->query()) }}"
                                    class="btn btn-success btn-sm">
                                    <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                                </a>

                                <a href="{{ route('monitoring.stok-wms') }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="mdi mdi-refresh me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table mb-0 table-nowrap stock-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Warehouse</th>
                        <th>Kode Produk</th>
                        <th>Nama Produk</th>
                        <th>Satuan</th>
                        <th class="text-end">Stok Real</th>
                        <th class="text-end">Dipesan</th>
                        <th class="text-end">Stok Tersedia</th>
                       <th class="text-end">Good</th>
<th class="text-end">Good After Repair</th>
<th class="text-end">Bad</th>
<th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $rows->firstItem() + $loop->index }}</td>
                            <td>
                                <i class="mdi mdi-warehouse mdi-20px text-primary me-1"></i>
                                {{ $row->warehouse_name }}
                                @if(!empty($row->warehouse_code))
                                    <span class="text-muted">({{ $row->warehouse_code }})</span>
                                @endif
                            </td>
                            <td class="fw-medium">{{ $row->product_code }}</td>
                            <td>{{ $row->product_name }}</td>
                            <td>{{ $row->unit_name ?: '-' }}</td>
                            <td class="text-end fw-medium">{{ number_format($row->qty_real) }}</td>
                            <td class="text-end text-warning">
                                @if((int) $row->qty_reserved > 0)
                                    <button type="button" class="btn btn-sm btn-link p-0 text-warning fw-medium btn-reservation-detail"
                                        data-location-id="{{ $row->warehouse_id }}" data-product-id="{{ $row->product_id }}">
                                        {{ number_format($row->qty_reserved) }} <i class="mdi mdi-information-outline"></i>
                                    </button>
                                @else
                                    0
                                @endif
                            </td>
                            <td class="text-end text-success fw-medium">{{ number_format($row->qty_available) }}</td>
                            <td class="text-end">{{ number_format($row->qty_good) }}</td>
<td class="text-end">{{ number_format($row->qty_good_after_repair) }}</td>
<td class="text-end">{{ number_format($row->qty_bad) }}</td>
<td class="text-end fw-medium">{{ number_format($row->total_qty) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted">Belum ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="text-muted small">
                Menampilkan {{ $rows->firstItem() ?? 0 }} - {{ $rows->lastItem() ?? 0 }}
                dari {{ $rows->total() }} data
            </div>
            <div>
                {{ $rows->links() }}
            </div>
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

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
    'use strict';

    function initFilterSelect2() {
        $('.js-select2-filter').each(function () {
            const $el = $(this);

            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }

            $el.select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: $el.data('placeholder') || 'Pilih data'
            });
        });
    }

    $(document).ready(function () {
        initFilterSelect2();

        const ttNodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        ttNodes.forEach(el => new bootstrap.Tooltip(el));

        document.querySelectorAll('.btn-reservation-detail').forEach(function (button) {
            button.addEventListener('click', function () {
                const params = new URLSearchParams({
                    warehouse_id: button.dataset.locationId,
                    product_id: button.dataset.productId
                });
                const body = document.getElementById('reservationDetailBody');
                body.innerHTML = '<div class="text-center text-muted py-4">Memuat detail pesanan…</div>';
                new bootstrap.Modal(document.getElementById('reservationDetailModal')).show();

                fetch('{{ route('monitoring.stok-wms.reservations') }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
                })
                .then(function (response) {
                    if (!response.ok) throw new Error('Gagal memuat detail pesanan.');
                    return response.json();
                })
                .then(function (data) {
                    const rows = (data.rows || []).map(function (row) {
                        return '<tr><td>' + escapeHtml(row.order_number || '-') + '</td>' +
                            '<td>' + escapeHtml(row.order_type || '-') + '</td>' +
                            '<td>' + escapeHtml(row.ordered_for || '-') + '</td>' +
                            '<td>' + escapeHtml(row.ordered_by || '-') + '</td>' +
                            '<td class="text-end">' + Number(row.remaining_quantity || 0).toLocaleString('id-ID') + '</td></tr>';
                    }).join('');
                    body.innerHTML = '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>No. Pesanan</th><th>Tujuan</th><th>Untuk</th><th>Dipesan Oleh</th><th class="text-end">Sisa Dipesan</th></tr></thead><tbody>' +
                        (rows || '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada reservasi aktif.</td></tr>') +
                        '</tbody></table></div>';
                })
                .catch(function (error) {
                    body.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message || 'Gagal memuat detail pesanan.') + '</div>';
                });
            });
        });
    });

    function escapeHtml(value) {
        return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
})(window.jQuery);
</script>
@endpush
