@extends('layouts/contentNavbarLayout')

@section('title','Branch Operations - Branch Returns')

@push('vendor-style')
<style>
.table thead th {
    white-space: nowrap
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis
}

/* Rapiin header detail (2 kolom) */
.kv dt {
    font-weight: 600;
    color: #6c757d
}

.kv dd {
    margin-bottom: .5rem
}

/* Ratain label+value agar tidak sempit di layar kecil */
@media (max-width:576px) {

    .kv dt,
    .kv dd {
        flex: 0 0 100%;
        max-width: 100%
    }
}
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4"><span class="text-muted fw-light">Branch Operations /</span> Branch Returns</h4>

    {{-- FILTERS --}}
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="fw-medium">Filter</span>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.retur-cabang.index') }}" class="row g-3 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Dari</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Status</label>
                    @php
                    $statusList = [
                    '' => 'Semua',
                    'requested' => 'Requested',
                    'pending_approval' => 'Pending Approval',
                    'approved' => 'Approved',
                    ];
                    @endphp
                    <select name="status" class="form-select">
                        @foreach($statusList as $val => $label)
                        <option value="{{ $val }}" {{ $status===$val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" name="q" class="form-control" value="{{ $q }}"
                        placeholder="No retur / Store / Gudang">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary"><i class="mdi mdi-magnify me-1"></i> Tampilkan</button>
                    <a href="{{ route('reports.retur-cabang.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- LIST --}}
    <div class="card">
        <h5 class="card-header">Daftar Branch Returns</h5>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Retur</th>
                        <th>Store</th>
                        <th>Warehouse</th>
                        <th>Status</th>
                        <th class="text-center" style="width:160px">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($returns as $r)
                    @php $st = strtolower($r->status); @endphp
                    <tr>
                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</td>
                        <td class="fw-medium">
                            <a href="javascript:void(0)" class="text-primary btn-view" data-id="{{ $r->id }}">
                                {{ $r->return_number }}
                            </a>
                        </td>
                        <td>{{ $r->store_name ?? $r->store_id }}</td>
                        <td>{{ $r->warehouse_name ?? $r->warehouse_id }}</td>
                        <td>
                            <span class="badge
                @if(in_array($st,['approved','received','close'])) bg-success
                @elseif($st==='rejected') bg-danger
                @elseif($st==='pending_approval' || $st==='requested') bg-info
                @else bg-warning @endif">
                                {{ ucfirst(str_replace('_',' ',$r->status)) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item btn-view" href="javascript:void(0)" data-id="{{ $r->id }}">
                                        <i class="mdi mdi-eye-outline me-1"></i> View
                                    </a>

                                    @if(in_array($st,['pending_approval','requested']))
                                    <a class="dropdown-item btn-approve" href="javascript:void(0)"
                                        data-id="{{ $r->id }}"
                                        data-approve-url="{{ route('reports.retur-cabang.approve',$r->id) }}">
                                        <i class="mdi mdi-check-outline me-1"></i> Approve
                                    </a>

                                    <a class="dropdown-item text-danger"
                                        href="{{ route('reports.retur-cabang.reject',$r->id) }}"
                                        onclick="event.preventDefault(); document.getElementById('rej-{{ $r->id }}').submit();">
                                        <i class="mdi mdi-close-circle-outline me-1"></i> Reject
                                    </a>
                                    <form id="rej-{{ $r->id }}" class="d-none" method="POST"
                                        action="{{ route('reports.retur-cabang.reject',$r->id) }}">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($returns,'links'))
        <div class="card-footer d-flex justify-content-end">{{ $returns->links() }}</div>
        @endif
    </div>
</div>

{{-- Modal: VIEW --}}
<div class="modal fade" id="returDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="mdi mdi-eye-outline me-1"></i> Detail Retur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailBody" class="text-muted">Memuat...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: APPROVE --}}
<div class="modal fade" id="returApproveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formApprove" method="POST" action="#">
                @csrf
                @method('PATCH')

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="mdi mdi-check-outline me-1"></i> Approval Retur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-7">
                            <div class="fw-medium" id="apv_return_number"></div>
                            <small class="text-muted d-block" id="apv_store_wh"></small>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Warehouse Penampung <span class="text-danger">*</span></label>
                            <select class="form-select" id="apv_warehouse_id" name="warehouse_id" required>
                                <option value="">- Pilih Warehouse -</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Akan disimpan ke header retur.</div>
                        </div>

                        {{-- CATATAN STORE --}}
                        <div class="col-md-12">
                            <label class="form-label">Catatan Store</label>
                            <textarea
                                class="form-control"
                                id="apv_note_store"
                                rows="3"
                                readonly
                                placeholder="-"
                            ></textarea>
                            <div class="form-text">Catatan dari store, hanya untuk dibaca saat approval.</div>
                        </div>

                      
                    </div>

                    <hr class="my-3">

                    <h6 class="mb-2">Detail Item</h6>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-end" style="width:160px">Qty Req</th>
                                </tr>
                            </thead>
                            <tbody id="apv_tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-success" type="submit">
                        <i class="mdi mdi-check-outline me-1"></i> Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('page-script')
<script>
// ===== VIEW =====
document.querySelectorAll('.btn-view').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const body = document.getElementById('detailBody');
        body.innerHTML = 'Memuat...';

        try {
            const res = await fetch("{{ url('reports/retur-cabang') }}/" + id);
            const data = await res.json();
            const h = data.header || {};
            const d = data.details || [];

            const headerHTML = `
                <dl class="row kv mb-3">
                  <dt class="col-sm-4">No. Retur</dt>         <dd class="col-sm-8">${h.return_number ?? '-'}</dd>
                  <dt class="col-sm-4">Status</dt>            <dd class="col-sm-8">${h.status ?? '-'}</dd>
                  <dt class="col-sm-4">Store</dt>             <dd class="col-sm-8">${h.store_name ?? h.store_id ?? '-'}</dd>
                  <dt class="col-sm-4">Warehouse</dt>         <dd class="col-sm-8">${h.warehouse_name ?? h.warehouse_id ?? '-'}</dd>
                  <dt class="col-sm-4">Dibuat</dt>            <dd class="col-sm-8">${h.created_at ?? '-'}</dd>
                  <dt class="col-sm-4">Approved</dt>          <dd class="col-sm-8">${h.approved_at ?? '-'} ${h.approved_by_name ? '(' + h.approved_by_name + ')' : ''}</dd>
                  <dt class="col-sm-4">Catatan Store</dt>     <dd class="col-sm-8">${h.note_store ?? '-'}</dd>
                  <dt class="col-sm-4">Catatan Warehouse</dt> <dd class="col-sm-8">${h.note_warehouse ?? '-'}</dd>
                </dl>`;

            const tableHTML = `
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead class="table-light">
                      <tr>
                        <th>Produk</th>
                        <th class="text-end">Qty Req</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${
                        d.length
                          ? d.map(x => `
                              <tr>
                                <td>${x.product_name ?? x.product_id ?? '-'}</td>
                                <td class="text-end">${x.qty_requested ?? 0}</td>
                              </tr>`).join('')
                          : `<tr><td colspan="2" class="text-center text-muted">Tidak ada detail</td></tr>`
                      }
                    </tbody>
                  </table>
                </div>`;

            body.innerHTML = headerHTML + tableHTML;
        } catch (e) {
            body.innerHTML = '<div class="text-danger">Gagal memuat detail.</div>';
        }

        new bootstrap.Modal(document.getElementById('returDetailModal')).show();
    });
});

// ===== APPROVE =====
document.querySelectorAll('.btn-approve').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const f = document.getElementById('formApprove');
        f.action = btn.dataset.approveUrl || '#';

        try {
            const res = await fetch("{{ url('reports/retur-cabang') }}/" + id);
            const data = await res.json();
            const h = data.header || {};
            const d = data.details || [];

            document.getElementById('apv_return_number').textContent = h.return_number || '';
            document.getElementById('apv_store_wh').textContent =
                (h.store_name || h.store_id || '-') + ' → ' + (h.warehouse_name || h.warehouse_id || '-');

            const whSel = document.getElementById('apv_warehouse_id');
            if (whSel) whSel.value = h.warehouse_id || '';

            const noteStoreEl = document.getElementById('apv_note_store');
            if (noteStoreEl) noteStoreEl.value = h.note_store || '';

            const noteWarehouseEl = document.getElementById('apv_note_warehouse');
            if (noteWarehouseEl) noteWarehouseEl.value = h.note_warehouse || '';

            const tb = document.getElementById('apv_tbody');
            tb.innerHTML = (d || []).length
                ? d.map(x => `
                    <tr>
                        <td>${x.product_name ?? x.product_id ?? '-'}</td>
                        <td class="text-end">${x.qty_requested ?? 0}</td>
                    </tr>`).join('')
                : `<tr><td colspan="2" class="text-center text-muted">Tidak ada detail</td></tr>`;

            f.addEventListener('keydown', e => {
                if (e.key === 'Enter') e.preventDefault();
            }, { once: true });

            new bootstrap.Modal(document.getElementById('returApproveModal')).show();
        } catch (e) {
            alert('Gagal memuat data approval.');
        }
    });
});

document.getElementById('returApproveModal')
    .addEventListener('input', (e) => {
        if (e.target.matches('#apv_tbody input[type="number"]')) {
            const max = parseInt(e.target.getAttribute('max') || '0', 10);
            let val = parseInt(e.target.value || '0', 10);
            if (val > max) val = max;
            if (val < 0) val = 0;
            e.target.value = val;
        }
    });
</script>
@endpush