@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Warehouse Outbound')

@push('page-style')
<style>
  :root { --wo-primary: #0f766e; --wo-dark: #17324d; --wo-muted: #64748b; }
  body { background: #f6f8fb; }
  .outbound-hero { position: relative; overflow: hidden; border-radius: 1.25rem; color: #fff; background: linear-gradient(115deg, #0f766e, #0f4c5c 58%, #2563eb); box-shadow: 0 1rem 2rem rgba(15, 118, 110, .2); }
  .outbound-hero::after { content: ''; position: absolute; right: -3rem; top: -5rem; width: 15rem; height: 15rem; border-radius: 50%; background: rgba(255,255,255,.12); }
  .outbound-hero > * { position: relative; z-index: 1; }
  .outbound-card { border: 0; border-radius: 1rem; box-shadow: 0 .35rem 1.2rem rgba(15, 23, 42, .06); }
  .form-control, .form-select { border-color: #dce3ea; border-radius: .7rem; min-height: 2.55rem; }
  .form-control:focus, .form-select:focus { border-color: var(--wo-primary); box-shadow: 0 0 0 .18rem rgba(15, 118, 110, .13); }
  .filter-panel { border: 1px solid #e2e8f0; border-radius: .9rem; background: #fbfcfe; }
  .summary-card { height: 100%; border: 1px solid #e2e8f0; border-radius: .95rem; background: #fff; }
  .summary-icon { width: 2.85rem; height: 2.85rem; display: inline-flex; align-items: center; justify-content: center; border-radius: .8rem; font-size: 1.3rem; }
  .summary-icon.teal { color: #0f766e; background: #ccfbf1; } .summary-icon.blue { color: #1d4ed8; background: #dbeafe; } .summary-icon.green { color: #15803d; background: #dcfce7; } .summary-icon.orange { color: #c2410c; background: #ffedd5; } .summary-icon.red { color: #b91c1c; background: #fee2e2; }
  .summary-number { color: var(--wo-dark); font-weight: 800; letter-spacing: -.02em; }
  .outbound-table th { color: #475569; background: #f1f5f9; border-bottom: 1px solid #dbe3ec; font-size: .74rem; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
  .outbound-table td { vertical-align: middle; }
  .outbound-table tbody tr:hover { background: #f0fdfa; }
  .table-responsive { border: 1px solid #e2e8f0; border-radius: .85rem; }
  .status-pill { display: inline-block; border-radius: 999px; padding: .3rem .55rem; font-size: .75rem; font-weight: 700; }
  .status-pill.ok { color: #166534; background: #dcfce7; } .status-pill.wait { color: #a16207; background: #fef3c7; } .status-pill.info { color: #1d4ed8; background: #dbeafe; } .status-pill.muted { color: #475569; background: #e2e8f0; }
  .detail-value { min-height: 4.9rem; border: 1px solid #e2e8f0; border-radius: .75rem; background: #f8fafc; padding: .75rem; }
  .detail-label { display: block; margin-bottom: .25rem; color: var(--wo-muted); font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
  .empty-state { border: 1px dashed #cbd5e1; border-radius: .8rem; background: #f8fafc; color: var(--wo-muted); }
  @media (max-width: 767.98px) { .outbound-hero { border-radius: 1rem; } .outbound-card .card-body, .outbound-card .card-header { padding: 1rem; } }
</style>
@endpush

@section('content')
@php
  $formatDate = fn ($date) => $date ? \Carbon\Carbon::parse($date)->timezone('Asia/Jakarta')->format('d M Y H:i') : '-';
  $exportQuery = request()->except('page');
@endphp

<div class="d-flex justify-content-end mb-3">
  <a href="{{ route('monitoring.command-center.index') }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-left-arrow-alt me-1"></i>Kembali ke Monitoring</a>
</div>

<section class="outbound-hero p-4 p-lg-5 mb-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <div class="text-white-50 mb-1">Monitoring / <span class="text-white fw-semibold">Warehouse Outbound</span></div>
      <h3 class="mb-1 text-white">Laporan pengeluaran barang warehouse</h3>
      <p class="mb-0 text-white-50">Pantau dokumen, resi, status release, QC, dan detail item dalam satu laporan.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <span class="badge rounded-pill text-bg-light text-dark px-3 py-2"><i class="bx bx-package me-1"></i>{{ number_format($summary->total_outbounds ?? 0) }} outbound</span>
      <span class="badge rounded-pill text-bg-light text-dark px-3 py-2"><i class="bx bx-time-five me-1"></i>{{ now('Asia/Jakarta')->format('d M Y H:i') }}</span>
    </div>
  </div>
</section>

<div class="card outbound-card mb-4">
  <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-column flex-md-row justify-content-between gap-3">
    <div><h5 class="mb-1">Filter laporan outbound</h5><p class="text-muted small mb-0">Filter berlaku pada tabel, ringkasan, dan file Excel.</p></div>
    <a href="{{ route('monitoring.warehouse-outbound.export', $exportQuery) }}" class="btn btn-success"><i class="bx bx-download me-1"></i>Export Excel</a>
  </div>
  <div class="card-body pt-1 px-4 pb-4">
    <form method="GET" action="{{ route('monitoring.warehouse-outbound.index') }}" class="filter-panel p-3" id="outboundFilterForm">
      <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3"><label class="form-label small fw-semibold">Keyword</label><input type="search" class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="Invoice, DO, order, resi, produk"></div>
        <div class="col-12 col-md-6 col-xl-3"><label class="form-label small fw-semibold">Warehouse</label><select class="form-select" name="warehouse_id"><option value="">Semua Warehouse</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($filters['warehouse_id'] == $warehouse->id)>{{ $warehouse->name }}{{ $warehouse->code ? ' · '.$warehouse->code : '' }}</option>@endforeach</select></div>
        <div class="col-12 col-md-6 col-xl-2"><label class="form-label small fw-semibold">Tipe Outbound</label><select class="form-select" name="outbound_type"><option value="">Semua Tipe</option>@foreach($outboundTypes as $type)<option value="{{ $type }}" @selected($filters['outbound_type'] === $type)>{{ $outboundTypeLabels[$type] ?? $type }}</option>@endforeach</select></div>
        <div class="col-12 col-md-6 col-xl-2"><label class="form-label small fw-semibold">Status Release</label><select class="form-select" name="release_status"><option value="">Semua Status</option><option value="released" @selected($filters['release_status'] === 'released')>Sudah release</option><option value="unreleased" @selected($filters['release_status'] === 'unreleased')>Belum release</option></select></div>
        <div class="col-12 col-md-6 col-xl-2"><label class="form-label small fw-semibold">Status QC</label><select class="form-select" name="qc_status"><option value="">Semua Status</option><option value="passed" @selected($filters['qc_status'] === 'passed')>QC lolos</option><option value="pending" @selected($filters['qc_status'] === 'pending')>QC belum lolos</option></select></div>
        <div class="col-12 col-md-6 col-xl-3"><label class="form-label small fw-semibold">Kurir</label><select class="form-select" name="courier_id"><option value="">Semua Kurir</option>@foreach($couriers as $courier)<option value="{{ $courier->id }}" @selected($filters['courier_id'] == $courier->id)>{{ $courier->name }}{{ $courier->code ? ' · '.$courier->code : '' }}</option>@endforeach</select></div>
        <div class="col-6 col-md-3 col-xl-2"><label class="form-label small fw-semibold">Tanggal Mulai</label><input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] }}"></div>
        <div class="col-6 col-md-3 col-xl-2"><label class="form-label small fw-semibold">Tanggal Selesai</label><input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] }}"></div>
        <div class="col-6 col-md-3 col-xl-2"><label class="form-label small fw-semibold">Per Halaman</label><select class="form-select" name="per_page">@foreach([10,25,50,100,200] as $size)<option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>@endforeach</select></div>
        <div class="col-12 col-xl-3 d-flex align-items-end gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="bx bx-search me-1"></i>Tampilkan</button><button class="btn btn-outline-success" type="submit" formaction="{{ route('monitoring.warehouse-outbound.export') }}"><i class="bx bx-download me-1"></i>Excel</button><a class="btn btn-outline-secondary" href="{{ route('monitoring.warehouse-outbound.index') }}" aria-label="Reset filter"><i class="bx bx-reset"></i></a></div>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mb-4">
  @foreach([
    ['teal', 'bx-package', 'Total Outbound', $summary->total_outbounds ?? 0],
    ['blue', 'bx-category-alt', 'Total SKU', $summary->total_sku ?? 0],
    ['green', 'bx-check-circle', 'Sudah Release', $summary->total_released ?? 0],
    ['orange', 'bx-badge-check', 'QC Lolos', $summary->total_qc_passed ?? 0],
    ['red', 'bx-receipt', 'Tanpa Resi', $summary->total_without_shipping ?? 0],
  ] as [$tone, $icon, $label, $value])
  <div class="col-6 col-md"><div class="summary-card p-3"><div class="d-flex gap-3 align-items-center"><span class="summary-icon {{ $tone }}"><i class="bx {{ $icon }}"></i></span><div><div class="text-muted small">{{ $label }}</div><div class="summary-number fs-4">{{ number_format($value) }}</div></div></div></div></div>
  @endforeach
</div>

<div class="card outbound-card">
  <div class="card-header bg-white border-0 px-4 pt-4 d-flex flex-column flex-md-row justify-content-between gap-2"><div><h5 class="mb-1">Daftar Warehouse Outbound</h5><p class="small text-muted mb-0">Tanggal aktivitas menggunakan shipment, lalu release, kemudian waktu pembuatan.</p></div><span class="text-muted small align-self-md-center">{{ $rows->total() }} data ditemukan</span></div>
  <div class="card-body px-4 pb-4">
    <div class="table-responsive">
      <table class="table table-hover outbound-table mb-0"><thead><tr><th>#</th><th>Tanggal</th><th>Warehouse / Tipe</th><th>Dokumen</th><th>Order / Referensi</th><th>Kurir / Resi</th><th class="text-center">Item</th><th>Release / QC</th><th>Detail</th></tr></thead>
      <tbody>
        @forelse($rowItems as $index => $row)
        <tr>
          <td>{{ $rows->firstItem() + $index }}</td><td class="small">{{ $formatDate($row->event_date) }}</td>
          <td><div class="fw-semibold">{{ $row->warehouse_name }}</div><small class="text-muted">{{ $row->warehouse_code ?: '-' }}</small><div class="mt-1"><span class="status-pill info">{{ $row->outbound_type_label }}</span></div></td>
          <td><div class="fw-semibold">{{ $row->invoice_number ?: '-' }}</div><small class="text-muted">DO: {{ $row->delivery_order_number ?: '-' }}</small></td>
          <td><div>{{ $row->order_number ?: '-' }}</div><small class="text-muted">{{ $row->order_ref ?: '-' }}</small></td>
          <td><div>{{ $row->courier_name ?: '-' }}</div><small class="text-muted">{{ $row->shipping_number ?: 'Belum ada resi' }}</small></td>
          <td class="text-center"><span class="fw-bold">{{ number_format($row->total_items) }}</span><br><small class="text-muted">{{ number_format($row->total_sku) }} SKU</small></td>
          <td><div><span class="status-pill {{ $row->release_status === 'released' ? 'ok' : 'wait' }}">{{ $row->release_status === 'released' ? 'Sudah release' : 'Belum release' }}</span></div><div class="mt-1"><span class="status-pill {{ $row->qc_status === 'passed' ? 'ok' : 'muted' }}">{{ $row->qc_status === 'passed' ? 'QC lolos' : 'QC belum lolos' }}</span></div></td>
          <td><button class="btn btn-sm btn-outline-primary js-outbound-detail" type="button" data-id="{{ $row->id }}"><i class="bx bx-detail me-1"></i>Detail</button></td>
        </tr>
        @empty
        <tr><td colspan="9"><div class="empty-state text-center py-5"><i class="bx bx-package fs-2 d-block mb-2"></i>Tidak ada data outbound yang sesuai dengan filter.</div></td></tr>
        @endforelse
      </tbody></table>
    </div>
    @if($rows->hasPages())<div class="d-flex justify-content-end mt-3">{{ $rows->onEachSide(1)->links() }}</div>@endif
  </div>
</div>

<div class="modal fade" id="outboundDetailModal" tabindex="-1" aria-labelledby="outboundDetailTitle" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content border-0 shadow"><div class="modal-header"><div><h5 class="modal-title" id="outboundDetailTitle">Detail Warehouse Outbound</h5><small class="text-muted" id="outboundDetailSubtitle"></small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3 mb-4" id="outboundDetailMeta"></div><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="table-light"><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Good</th><th class="text-end">Bad</th><th class="text-end">Other</th><th>Kondisi Item</th></tr></thead><tbody id="outboundDetailItems"></tbody></table></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
@endsection

@push('page-script')
<script>
  const outboundRows = @json($rowItems);
  const outboundRowsById = Object.fromEntries(outboundRows.map(row => [row.id, row]));
  const escapeHtml = value => String(value ?? '-').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
  const formatDate = value => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value)) : '-';

  document.querySelectorAll('.js-outbound-detail').forEach(button => button.addEventListener('click', () => {
    const row = outboundRowsById[button.dataset.id];
    if (!row) return;
    document.getElementById('outboundDetailTitle').textContent = `Detail ${row.invoice_number || row.order_number || 'Warehouse Outbound'}`;
    document.getElementById('outboundDetailSubtitle').textContent = `${row.warehouse_name || '-'} · ${row.outbound_type_label || '-'}`;
    const fields = [
      ['Invoice', row.invoice_number], ['Delivery Order', row.delivery_order_number], ['Order Number', row.order_number], ['Order Ref', row.order_ref],
      ['Kurir', row.courier_name], ['No. Resi', row.shipping_number], ['Released At', formatDate(row.released_at)], ['QC Passed At', formatDate(row.qc_passed_at)],
      ['PIC Outbound', row.outbound_by_name], ['PIC QC', row.qc_passed_by_name],
    ];
    document.getElementById('outboundDetailMeta').innerHTML = fields.map(([label, value]) => `<div class="col-6 col-lg-3"><div class="detail-value"><span class="detail-label">${escapeHtml(label)}</span><span class="fw-semibold">${escapeHtml(value || '-')}</span></div></div>`).join('');
    const details = Array.isArray(row.product_details) ? row.product_details : [];
    document.getElementById('outboundDetailItems').innerHTML = details.length
      ? details.map(item => `<tr><td><div class="fw-semibold">${escapeHtml(item.product_name)}</div><small class="text-muted">${escapeHtml(item.product_code)}</small></td><td class="text-end">${Number(item.qty || 0).toLocaleString('id-ID')}</td><td class="text-end">${Number(item.qty_good || 0).toLocaleString('id-ID')}</td><td class="text-end">${Number(item.qty_bad || 0).toLocaleString('id-ID')}</td><td class="text-end">${Number(item.qty_other || 0).toLocaleString('id-ID')}</td><td>${escapeHtml(item.condition_summary)}</td></tr>`).join('')
      : '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada detail item.</td></tr>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('outboundDetailModal')).show();
  }));
</script>
@endpush
