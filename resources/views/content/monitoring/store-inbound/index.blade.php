@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Store Inbound')

@push('page-style')
<style>
  :root { --si-primary: #7c3aed; --si-dark: #27324b; --si-muted: #64748b; }
  body { background: #f7f7fc; }
  .store-inbound-hero { position: relative; overflow: hidden; border-radius: 1.25rem; color: #fff; background: linear-gradient(115deg, #5b21b6, #7c3aed 55%, #db2777); box-shadow: 0 1rem 2rem rgba(124, 58, 237, .2); }
  .store-inbound-hero::after { content: ''; position: absolute; right: -3rem; top: -5rem; width: 16rem; height: 16rem; border-radius: 50%; background: rgba(255,255,255,.13); }
  .store-inbound-hero > * { position: relative; z-index: 1; }
  .report-card { border: 0; border-radius: 1rem; box-shadow: 0 .35rem 1.2rem rgba(15, 23, 42, .06); }
  .form-control, .form-select { min-height: 2.55rem; border-color: #dce3ea; border-radius: .7rem; }
  .form-control:focus, .form-select:focus { border-color: var(--si-primary); box-shadow: 0 0 0 .18rem rgba(124, 58, 237, .13); }
  .filter-panel { border: 1px solid #e2e8f0; border-radius: .9rem; background: #fbfcff; }
  .summary-card { height: 100%; border: 1px solid #e2e8f0; border-radius: .95rem; background: #fff; }
  .summary-icon { width: 2.85rem; height: 2.85rem; display: inline-flex; align-items: center; justify-content: center; border-radius: .8rem; font-size: 1.3rem; }
  .summary-icon.purple { color: #6d28d9; background: #ede9fe; } .summary-icon.blue { color: #1d4ed8; background: #dbeafe; } .summary-icon.green { color: #15803d; background: #dcfce7; } .summary-icon.orange { color: #c2410c; background: #ffedd5; } .summary-icon.red { color: #b91c1c; background: #fee2e2; }
  .summary-number { color: var(--si-dark); font-weight: 800; letter-spacing: -.02em; }
  .report-table th { color: #475569; background: #f1f5f9; border-bottom: 1px solid #dbe3ec; font-size: .74rem; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
  .report-table td { vertical-align: middle; }.report-table tbody tr:hover { background: #faf5ff; }
  .table-responsive { border: 1px solid #e2e8f0; border-radius: .85rem; }
  .status-pill { display: inline-block; border-radius: 999px; padding: .3rem .55rem; font-size: .75rem; font-weight: 700; }
  .status-pill.all-passed { color: #166534; background: #dcfce7; } .status-pill.partial-passed { color: #a16207; background: #fef3c7; } .status-pill.pending { color: #1d4ed8; background: #dbeafe; } .status-pill.other { color: #475569; background: #e2e8f0; }
  .detail-value { min-height: 4.9rem; border: 1px solid #e2e8f0; border-radius: .75rem; background: #f8fafc; padding: .75rem; }.detail-label { display: block; margin-bottom: .25rem; color: var(--si-muted); font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
  .empty-state { border: 1px dashed #cbd5e1; border-radius: .8rem; background: #f8fafc; color: var(--si-muted); }
  @media (max-width: 767.98px) { .store-inbound-hero { border-radius: 1rem; } .report-card .card-body, .report-card .card-header { padding: 1rem; } }
</style>
@endpush

@section('content')
@php
  $formatDate = fn ($date) => $date ? \Carbon\Carbon::parse($date)->timezone('Asia/Jakarta')->format('d M Y H:i') : '-';
  $badgeClass = fn ($status) => match ($status) { 'all_passed' => 'all-passed', 'partial_passed' => 'partial-passed', 'pending' => 'pending', default => 'other' };
@endphp

<div class="d-flex justify-content-end mb-3"><a href="{{ route('monitoring.command-center.index') }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-left-arrow-alt me-1"></i>Kembali ke Monitoring</a></div>

<section class="store-inbound-hero p-4 p-lg-5 mb-4"><div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3"><div><div class="text-white-50 mb-1">Monitoring / <span class="text-white fw-semibold">Store Inbound</span></div><h3 class="mb-1 text-white">Laporan penerimaan barang store</h3><p class="mb-0 text-white-50">Pantau asal warehouse, dokumen penerimaan, jumlah item, dan progres quality control.</p></div><div class="d-flex flex-wrap gap-2"><span class="badge rounded-pill text-bg-light text-dark px-3 py-2"><i class="bx bx-store me-1"></i>{{ number_format($summary->total_inbounds ?? 0) }} penerimaan</span><span class="badge rounded-pill text-bg-light text-dark px-3 py-2"><i class="bx bx-time-five me-1"></i>{{ now('Asia/Jakarta')->format('d M Y H:i') }}</span></div></div></section>

<div class="card report-card mb-4"><div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-1">Filter laporan Store Inbound</h5><p class="text-muted small mb-0">Gunakan filter untuk menelusuri penerimaan store dan progres QC itemnya.</p></div><div class="card-body pt-1 px-4 pb-4"><form method="GET" action="{{ route('monitoring.store-inbound.index') }}" class="filter-panel p-3"><div class="row g-3">
  <div class="col-12 col-md-6 col-xl-3"><label class="form-label small fw-semibold">Keyword</label><input type="search" class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="Invoice, DO, catatan, produk"></div>
  <div class="col-12 col-md-6 col-xl-3"><label class="form-label small fw-semibold">Store Tujuan</label><select class="form-select" name="store_id"><option value="">Semua Store</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected($filters['store_id'] == $store->id)>{{ $store->name }}{{ $store->code ? ' · '.$store->code : '' }}</option>@endforeach</select></div>
  <div class="col-12 col-md-6 col-xl-3"><label class="form-label small fw-semibold">Warehouse Asal</label><select class="form-select" name="warehouse_source_id"><option value="">Semua Warehouse</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($filters['warehouse_source_id'] == $warehouse->id)>{{ $warehouse->name }}{{ $warehouse->code ? ' · '.$warehouse->code : '' }}</option>@endforeach</select></div>
  <div class="col-6 col-md-3 col-xl-1"><label class="form-label small fw-semibold">Status QC</label><select class="form-select" name="qc_status"><option value="">Semua</option>@foreach($qcStatuses as $status)<option value="{{ $status }}" @selected($filters['qc_status'] === $status)>{{ $qcStatusLabels[$status] ?? $status }}</option>@endforeach</select></div>
  <div class="col-6 col-md-3 col-xl-2"><label class="form-label small fw-semibold">QC Detail</label><select class="form-select" name="item_qc_status"><option value="">Semua Item</option><option value="all_checked" @selected($filters['item_qc_status'] === 'all_checked')>Semua sudah QC</option><option value="pending" @selected($filters['item_qc_status'] === 'pending')>Masih ada pending</option></select></div>
  <div class="col-6 col-md-3 col-xl-2"><label class="form-label small fw-semibold">Tanggal Mulai</label><input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] }}"></div>
  <div class="col-6 col-md-3 col-xl-2"><label class="form-label small fw-semibold">Tanggal Selesai</label><input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] }}"></div>
  <div class="col-6 col-md-3 col-xl-2"><label class="form-label small fw-semibold">Per Halaman</label><select class="form-select" name="per_page">@foreach([10,25,50,100,200] as $size)<option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>@endforeach</select></div>
  <div class="col-12 col-xl-3 d-flex align-items-end gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="bx bx-search me-1"></i>Tampilkan</button><a class="btn btn-outline-secondary" href="{{ route('monitoring.store-inbound.index') }}"><i class="bx bx-reset me-1"></i>Reset</a></div>
</div></form></div></div>

<div class="row g-3 mb-4">
@foreach([
  ['purple', 'bx-store', 'Total Inbound', $summary->total_inbounds ?? 0], ['blue', 'bx-category-alt', 'Total SKU', $summary->total_sku ?? 0], ['green', 'bx-package', 'Total Item', $summary->total_items ?? 0], ['orange', 'bx-badge-check', 'Item sudah QC', $summary->total_qc_checked_items ?? 0], ['red', 'bx-time-five', 'Item pending QC', $summary->total_qc_pending_items ?? 0],
] as [$tone, $icon, $label, $value])
<div class="col-6 col-md"><div class="summary-card p-3"><div class="d-flex gap-3 align-items-center"><span class="summary-icon {{ $tone }}"><i class="bx {{ $icon }}"></i></span><div><div class="text-muted small">{{ $label }}</div><div class="summary-number fs-4">{{ number_format($value) }}</div></div></div></div></div>
@endforeach
</div>

<div class="card report-card"><div class="card-header bg-white border-0 px-4 pt-4 d-flex flex-column flex-md-row justify-content-between gap-2"><div><h5 class="mb-1">Daftar Store Inbound</h5><p class="small text-muted mb-0">Jumlah item berasal dari detail Store Inbound; nilai mengikuti nominal pada dokumen penerimaan.</p></div><span class="text-muted small align-self-md-center">{{ $rows->total() }} data ditemukan</span></div><div class="card-body px-4 pb-4"><div class="table-responsive"><table class="table table-hover report-table mb-0"><thead><tr><th>#</th><th>Tanggal Diterima</th><th>Store Tujuan</th><th>Warehouse Asal</th><th>Dokumen</th><th>Item</th><th>Nilai</th><th>Status QC</th><th>Detail</th></tr></thead><tbody>
@forelse($rowItems as $index => $row)
<tr><td>{{ $rows->firstItem() + $index }}</td><td class="small">{{ $formatDate($row->received_date) }}</td><td><div class="fw-semibold">{{ $row->store_name }}</div><small class="text-muted">{{ $row->store_code ?: '-' }}</small></td><td><div>{{ $row->warehouse_source_name }}</div><small class="text-muted">{{ $row->warehouse_source_code ?: '-' }}</small></td><td><div class="fw-semibold">{{ $row->invoice_number ?: '-' }}</div><small class="text-muted">DO: {{ $row->delivery_order_number ?: '-' }}</small></td><td><span class="fw-bold">{{ number_format($row->total_items) }}</span><small class="text-muted d-block">{{ number_format($row->total_sku) }} SKU · {{ number_format($row->qc_checked_items) }} sudah QC</small></td><td>{{ $row->grand_total !== null ? 'Rp '.number_format($row->grand_total, 0, ',', '.') : '-' }}</td><td><span class="status-pill {{ $badgeClass($row->qc_status) }}">{{ $row->qc_status_label }}</span><small class="text-muted d-block mt-1">{{ number_format($row->qc_pending_items) }} pending</small></td><td><button class="btn btn-sm btn-outline-primary js-store-inbound-detail" type="button" data-id="{{ $row->id }}"><i class="bx bx-detail me-1"></i>Detail</button></td></tr>
@empty
<tr><td colspan="9"><div class="empty-state text-center py-5"><i class="bx bx-store fs-2 d-block mb-2"></i>Tidak ada data Store Inbound yang sesuai dengan filter.</div></td></tr>
@endforelse
</tbody></table></div>@if($rows->hasPages())<div class="d-flex justify-content-end mt-3">{{ $rows->onEachSide(1)->links() }}</div>@endif</div></div>

<div class="modal fade" id="storeInboundDetailModal" tabindex="-1" aria-labelledby="storeInboundDetailTitle" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content border-0 shadow"><div class="modal-header"><div><h5 class="modal-title" id="storeInboundDetailTitle">Detail Store Inbound</h5><small class="text-muted" id="storeInboundDetailSubtitle"></small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3 mb-4" id="storeInboundDetailMeta"></div><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Sudah QC</th><th class="text-end">Pending</th><th>Kondisi Item</th></tr></thead><tbody id="storeInboundDetailItems"></tbody></table></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
@endsection

@push('page-script')
<script>
  const storeInboundRows = @json($rowItems);
  const storeInboundById = Object.fromEntries(storeInboundRows.map(row => [row.id, row]));
  const escapeHtml = value => String(value ?? '-').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
  const formatDate = value => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value)) : '-';
  document.querySelectorAll('.js-store-inbound-detail').forEach(button => button.addEventListener('click', () => {
    const row = storeInboundById[button.dataset.id]; if (!row) return;
    document.getElementById('storeInboundDetailTitle').textContent = `Detail ${row.invoice_number || 'Store Inbound'}`;
    document.getElementById('storeInboundDetailSubtitle').textContent = `${row.store_name || '-'} · ${row.warehouse_source_name || '-'}`;
    const fields = [['Store Tujuan', row.store_name], ['Warehouse Asal', row.warehouse_source_name], ['Invoice', row.invoice_number], ['Delivery Order', row.delivery_order_number], ['Tanggal Diterima', formatDate(row.received_date)], ['PIC Penerima', row.received_by_name], ['Status QC', row.qc_status_label], ['Order ID', row.order_id], ['Catatan', row.note]];
    document.getElementById('storeInboundDetailMeta').innerHTML = fields.map(([label, value]) => `<div class="col-6 col-lg-3"><div class="detail-value"><span class="detail-label">${escapeHtml(label)}</span><span class="fw-semibold">${escapeHtml(value || '-')}</span></div></div>`).join('');
    const details = Array.isArray(row.product_details) ? row.product_details : [];
    document.getElementById('storeInboundDetailItems').innerHTML = details.length ? details.map(item => `<tr><td><div class="fw-semibold">${escapeHtml(item.product_name)}</div><small class="text-muted">${escapeHtml(item.product_code)}</small></td><td class="text-end">${Number(item.qty || 0).toLocaleString('id-ID')}</td><td class="text-end">${Number(item.qc_checked || 0).toLocaleString('id-ID')}</td><td class="text-end">${Number(item.qc_pending || 0).toLocaleString('id-ID')}</td><td>${escapeHtml(item.condition_summary)}</td></tr>`).join('') : '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada detail item.</td></tr>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('storeInboundDetailModal')).show();
  }));
</script>
@endpush
