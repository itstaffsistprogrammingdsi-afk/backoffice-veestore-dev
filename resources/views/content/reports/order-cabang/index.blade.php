@extends('layouts/contentNavbarLayout')

@section('title','Branch Operations - Branch Orders')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
  #tblItems .select2-container,
  #tblItemsEdit .select2-container {
    width: 100% !important;
  }

  .modal .select2-container--open .select2-dropdown {
    z-index: 200001 !important;
  }

  #modalCreate .modal-content,
  #modalEdit .modal-content {
    overflow: visible;
  }

  #modalCreate .table-responsive,
  #modalEdit .table-responsive {
    overflow: visible !important;
  }

  .select2-results__options {
    max-height: 280px;
  }

  .apv-over-approved {
    background: #fff3f3;
  }

  .apv-req-over {
    background: #fffaf0;
  }

  .small-muted {
    font-size: .85rem;
    color: #6c757d;
  }

  .apv-product-box {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .apv-product-thumb {
    width: 44px;
    height: 44px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    flex: 0 0 44px;
  }

  .apv-product-placeholder {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 12px;
    flex: 0 0 44px;
  }

  #modalApprove .select2-container {
    width: 100% !important;
  }

  /* Approval memakai modal scrollable. Jangan biarkan isi kartu keluar dari
     dialog saat daftar produk panjang. Dropdown Select2 tetap aman karena
     dirender langsung pada elemen modal. */
  #modalApprove .modal-content {
    height: 100%;
    max-height: 100%;
    overflow: hidden;
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 1rem 3rem rgba(35, 24, 64, .22);
  }

  /* Form adalah flex container agar area daftar produk mengambil ruang yang
     tersisa dan bisa discroll tanpa menyembunyikan tombol di footer. */
  #modalApprove .modal-dialog {
    height: calc(100vh - 2rem);
    height: calc(100dvh - 2rem);
    margin-top: 1rem;
    margin-bottom: 1rem;
  }

  #modalApprove form {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
  }

  #modalApprove .modal-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #eee9fa;
    background: linear-gradient(110deg, #faf7ff, #fff);
  }

  #modalApprove .modal-body {
    flex: 1 1 auto;
    min-height: 0;
    padding: 1.25rem;
    background: #fbfaff;
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  #modalApprove .modal-footer {
    flex: 0 0 auto;
    padding: .9rem 1.25rem;
    border-top: 1px solid #eee9fa;
    background: #fff;
  }

  .apv-product-block {
    border: 1px solid #e8e2f4;
    border-radius: .85rem;
    padding: 1rem;
    margin-bottom: .9rem;
    background: #fff;
    box-shadow: 0 .15rem .45rem rgba(47, 32, 78, .04);
  }

  .apv-product-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding-bottom: .75rem;
    margin-bottom: .8rem;
    border-bottom: 1px dashed #e6e0f1;
  }

  .apv-line {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) 120px 72px 36px;
    gap: .65rem;
    align-items: start;
    padding: .65rem;
    margin-bottom: .5rem;
    border: 1px solid #eeeaf5;
    border-radius: .65rem;
    background: #fdfcff;
  }

  .apv-line-head {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) 120px 72px 36px;
    gap: .65rem;
    align-items: center;
    padding: 0 .65rem;
  }

  .apv-wh-col {
    min-width: 200px;
  }

  .apv-qty-col {
    width: 120px;
  }

  .apv-stock-col {
    width: 72px;
  }

  .apv-del-col {
    width: 36px;
  }

  .apv-total-wrap {
    min-width: 125px;
    padding: .45rem .65rem;
    border-radius: .6rem;
    background: #f5efff;
  }

  .apv-add-line {
    margin-top: .15rem;
  }

  .apv-line.apv-over-approved .apv-stock,
  .apv-line.apv-over-approved .apv-approved {
    color: #dc3545;
  }

  @media (max-width: 767.98px) {
    #modalApprove .modal-body { padding: 1rem; }
    .apv-product-header { align-items: flex-start; flex-direction: column; }
    .apv-line,
    .apv-line-head { grid-template-columns: minmax(0, 1fr) 90px 32px; }
    .apv-stock-col { display: none; }
    .apv-stock-info { font-size: .75rem; }
  }
</style>
@endpush

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@section('content')
@php
$statusList = [
'draft',
'requested',
'approved',
'rejected',
'processing',
'packing',
'shipped',
'received',
'cancelled',
'partial_approved',
'partial_received',
];

$statusLabel = [
'draft' => 'Draft',
'requested' => 'Requested',
'approved' => 'Approved',
'rejected' => 'Rejected',
'processing' => 'Processing',
'packing' => 'Packing',
'shipped' => 'Shipped',
'received' => 'Received',
'cancelled' => 'Cancelled',
'partial_approved' => 'Partial Approved',
'partial_received' => 'Partial Received',
];

$statusBadge = [
'draft' => 'bg-secondary',
'requested' => 'bg-info',
'approved' => 'bg-success',
'rejected' => 'bg-danger',
'processing' => 'bg-primary',
'packing' => 'bg-warning text-dark',
'shipped' => 'bg-dark',
'received' => 'bg-success',
'cancelled' => 'bg-secondary',
'partial_approved' => 'bg-warning text-dark',
'partial_received' => 'bg-success',
];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">Branch Operations /</span> Branch Orders</h4>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible">
    <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  @if($errors->any())
  <div class="alert alert-danger alert-dismissible">
    <i class="mdi mdi-alert-circle-outline me-1"></i> {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  <div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
      <span>Filter</span>
    </div>
    <div class="card-body">
      <form class="row g-2" method="GET" action="{{ route('reports.order-cabang.index') }}">
        <div class="col-sm-6 col-md-3">
          <label class="form-label">Cari</label>
          <input class="form-control form-control-sm" name="q" value="{{ $q ?? '' }}" placeholder="No. Order...">
        </div>

        <div class="col-sm-6 col-md-3">
          <label class="form-label">Status</label>
          <select class="form-select form-select-sm" name="status">
            <option value="" @selected(($status ?? 'requested' )==='' )>Semua</option>
            @foreach($statusList as $st)
            <option value="{{ $st }}" @selected(($status ?? 'requested' )===$st)>
              {{ $statusLabel[$st] ?? ucfirst($st) }}
            </option>
            @endforeach
          </select>
        </div>

        <div class="col-sm-6 col-md-3">
          <label class="form-label">Dari</label>
          <input type="date" class="form-control form-control-sm" name="from" value="{{ $from }}">
        </div>

        <div class="col-sm-6 col-md-3">
          <label class="form-label">Sampai</label>
          <input type="date" class="form-control form-control-sm" name="to" value="{{ $to }}">
        </div>

        <div class="col-12">
          <button class="btn btn-outline-secondary btn-sm" type="submit">
            <i class="mdi mdi-magnify me-1"></i> Tampilkan
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <h5 class="card-header">Daftar Branch Orders</h5>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:80px">Tanggal</th>
            <th>No. Order</th>
            <th>Cabang</th>
            <th>Gudang</th>
            <th class="text-center">Jumlah Barang</th>
            <th>Status</th>
            <th class="text-center" style="width:160px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($orders as $o)
          <tr>
            <td>{{ optional($o->date)->format('Y-m-d') }}</td>
            <td class="fw-medium">
              <a href="javascript:void(0)" class="text-primary btn-view" data-id="{{ $o->id }}">{{ $o->order_number }}</a>
            </td>
            <td>{{ $o->store->name ?? '-' }}</td>
            <td>{{ $o->warehouse->name ?? '-' }}</td>
            <td class="text-center">
              <span class="badge bg-label-primary">
                {{ number_format((int) ($o->details_sum_requested_qty ?? 0)) }} barang
              </span>
            </td>
            <td>
              @php $st = strtolower($o->status); @endphp
              <span class="badge {{ $statusBadge[$st] ?? 'bg-label-secondary' }}">
                {{ $statusLabel[$st] ?? ucfirst($o->status) }}
              </span>
            </td>
            <td class="text-center">
              <div class="dropdown">
                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" type="button">
                  <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item btn-view" href="javascript:void(0)" data-id="{{ $o->id }}">
                    <i class="mdi mdi-eye-outline me-1"></i> View
                  </a>

                  @if($o->isEditable())
                  <a class="dropdown-item btn-edit" href="javascript:void(0)" data-id="{{ $o->id }}"
                    data-update-url="{{ route('reports.order-cabang.update',$o->id) }}">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit
                  </a>
                  <a class="dropdown-item" href="{{ route('reports.order-cabang.request',$o->id) }}"
                    onclick="event.preventDefault(); document.getElementById('req-{{ $o->id }}').submit();">
                    <i class="mdi mdi-send-outline me-1"></i> Request
                  </a>
                  <form id="req-{{ $o->id }}" class="d-none" method="POST"
                    action="{{ route('reports.order-cabang.request',$o->id) }}">
                    @csrf @method('PATCH')
                  </form>
                  @endif

                  @if(in_array(strtolower($o->status), ['requested', 'approved', 'partial_approved'], true))
                  @if(strtolower($o->status)==='requested')
                  <a class="dropdown-item btn-approve" href="javascript:void(0)"
                    data-id="{{ $o->id }}"
                    data-approve-url="{{ route('reports.order-cabang.approve',$o->id) }}">
                    <i class="mdi mdi-check-outline me-1"></i> Approve
                  </a>
                  @endif
                  <a class="dropdown-item text-danger"
                    href="{{ route('reports.order-cabang.reject',$o->id) }}"
                    onclick="event.preventDefault(); document.getElementById('rej-{{ $o->id }}').submit();">
                    <i class="mdi mdi-close-circle-outline me-1"></i> Reject
                  </a>
                  <form id="rej-{{ $o->id }}" class="d-none" method="POST"
                    action="{{ route('reports.order-cabang.reject',$o->id) }}">
                    @csrf @method('PATCH')
                  </form>
                  @endif

                  @if(in_array(strtolower($o->status), ['approved', 'partial_approved', 'processing', 'packing', 'shipped', 'partial_received'], true))
                  <a class="dropdown-item btn-receive" href="javascript:void(0)"
                    data-id="{{ $o->id }}"
                    data-receive-url="{{ route('reports.order-cabang.receive', $o->id) }}">
                    <i class="mdi mdi-package-variant-closed-check me-1"></i> Terima Barang
                  </a>
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
    <div class="card-footer d-flex justify-content-end">{{ $orders->links() }}</div>
  </div>
</div>

{{-- ===== MODAL: CREATE ===== --}}
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formCreate" method="POST" action="{{ route('reports.order-cabang.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-plus me-1"></i> Tambah Order</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Cabang</label>
              <select class="form-select" name="store_id" required>
                <option value="">- Pilih -</option>
                @foreach($stores as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Gudang</label>
              <select class="form-select" name="warehouse_id" required>
                <option value="">- Pilih -</option>
                @foreach($warehouses as $w)
                <option value="{{ $w->id }}">{{ $w->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tanggal</label>
              <input type="date" class="form-control" name="date" value="{{ now()->toDateString() }}" required>
            </div>
          </div>

          <hr>
          <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0">Detail Item</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddRow">
              <i class="mdi mdi-plus"></i> Tambah Baris
            </button>
          </div>

          <div class="table-responsive mt-2">
            <table class="table" id="tblItems">
              <thead class="table-light">
                <tr>
                  <th style="width:45%">Produk</th>
                  <th style="width:20%">Qty Diminta</th>
                  <th>Catatan</th>
                  <th style="width:50px"></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="form-text">Minimal 1 baris item.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ===== MODAL: EDIT ===== --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formEdit" method="POST" action="#">
        @csrf @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-pencil-outline me-1"></i> Edit Order</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Cabang</label>
              <select class="form-select" id="edit_store" name="store_id" required>
                @foreach($stores as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Gudang</label>
              <select class="form-select" id="edit_warehouse" name="warehouse_id" required>
                @foreach($warehouses as $w)
                <option value="{{ $w->id }}">{{ $w->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tanggal</label>
              <input type="date" class="form-control" id="edit_date" name="date" required>
            </div>
          </div>

          <hr>
          <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0">Detail Item</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddRowEdit">
              <i class="mdi mdi-plus"></i> Tambah Baris
            </button>
          </div>

          <div class="table-responsive mt-2">
            <table class="table" id="tblItemsEdit">
              <thead class="table-light">
                <tr>
                  <th style="width:45%">Produk</th>
                  <th style="width:20%">Qty Diminta</th>
                  <th>Catatan</th>
                  <th style="width:50px"></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ===== MODAL: VIEW ===== --}}
<div class="modal fade" id="modalView" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="mdi mdi-eye-outline me-1"></i> Detail Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

{{-- ===== MODAL: RECEIVE ===== --}}
<div class="modal fade" id="modalReceive" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formReceive" method="POST" action="#">
        @csrf @method('PATCH')
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-package-variant-closed-check me-1"></i> Terima Barang</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small mb-3" id="receiveOrderInfo"></p>
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light"><tr><th>Produk</th><th class="text-end">Disetujui</th><th class="text-end">Sudah Diterima</th><th style="width:170px">Qty Diterima</th></tr></thead>
              <tbody id="receiveItems"></tbody>
            </table>
          </div>
          <div class="form-text mt-2">Simpan qty yang benar-benar diterima. Nilai <strong>Dipesan</strong> akan berkurang sesuai penerimaan.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit"><i class="mdi mdi-content-save-outline me-1"></i>Simpan Penerimaan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ===== MODAL: APPROVE ===== --}}
<div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formApprove" method="POST" action="#">
        @csrf @method('PATCH')
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-check-outline me-1"></i> Approval</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-medium" id="apv_order_number"></div>
              <small class="text-muted" id="apv_store_wh"></small>
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="button" id="btnApproveAll">
              Setujui = Qty Diminta
            </button>
          </div>

          <div class="small-muted mt-2 mb-2">
            Pilih warehouse per produk. Satu produk bisa di-split ke beberapa warehouse dengan qty masing-masing.
          </div>

          <div id="apv_tbody"></div>
        </div>

        <div class="modal-footer">
          <span class="me-auto text-danger small d-none" id="apv_warn">
            Ada nilai <b>Disetujui</b> melebihi stok. Mohon sesuaikan.
          </span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-success" type="submit" id="btnDoApprove">
            <i class="mdi mdi-check-outline me-1"></i> Approve
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<template id="rowTpl">
  <tr>
    <td>
      <select class="form-select js-p" name="items[@idx@][product_id]" required>
        <option value="">- Pilih Produk -</option>
        @foreach($products as $p)
        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
        @endforeach
      </select>
    </td>
    <td><input type="number" step="1" min="1" class="form-control" name="items[@idx@][requested_qty]" required></td>
    <td><input type="text" class="form-control" name="items[@idx@][note]" placeholder="Catatan"></td>
    <td>
      <button class="btn btn-icon btn-text-secondary btn-sm btnDelRow" type="button">
        <i class="mdi mdi-close"></i>
      </button>
    </td>
  </tr>
</template>
@endsection

@push('page-script')
<script>
  (function() {
    var tplHtml = document.getElementById('rowTpl').innerHTML;
    var $modalCreate = $('#modalCreate');
    var $modalEdit = $('#modalEdit');
    var $modalApprove = $('#modalApprove');
    var $modalReceive = $('#modalReceive');
    var baseUrl = "{{ url('reports/order-cabang') }}";
    var warehouses = @json($warehouses->map(function($w) {
      return ['id' => $w->id, 'name' => $w->name];
    })->values()->all());

    function isUUID(v) {
      return /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(String(v || '').trim());
    }

    function escHtml(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function warehouseOptions(selectedId) {
      var html = '<option value="">- Pilih Warehouse -</option>';
      (warehouses || []).forEach(function(w) {
        var selected = String(selectedId || '') === String(w.id) ? ' selected' : '';
        html += '<option value="' + escHtml(w.id) + '"' + selected + '>' + escHtml(w.name) + '</option>';
      });
      return html;
    }

    function initProductSelect(ctx) {
      var $ctx = $(ctx);
      $ctx.find('select.js-p').each(function() {
        var $el = $(this);
        if (typeof $.fn.select2 !== 'function') return;
        if ($el.data('select2')) $el.select2('destroy');
        $el.select2({
          theme: 'bootstrap-5',
          width: 'style',
          placeholder: '- Pilih Produk -',
          allowClear: true,
          dropdownParent: $el.closest('.modal')
        }).on('select2:open', function() {
          var $container = $el.next('.select2');
          var w = $container.outerWidth();
          $('.select2-container--open .select2-dropdown').css({
            width: w,
            minWidth: w
          });
        });
      });
    }

    function initApproveWarehouseSelects(ctx) {
      var $ctx = $(ctx);
      $ctx.find('select.apv-wh-select').each(function() {
        var $el = $(this);
        if (typeof $.fn.select2 !== 'function') return;
        if ($el.data('select2')) $el.select2('destroy');
        $el.select2({
          theme: 'bootstrap-5',
          width: '100%',
          placeholder: '- Pilih Warehouse -',
          allowClear: true,
          dropdownParent: $modalApprove
        }).on('select2:open', function() {
          var $container = $el.next('.select2');
          var w = $container.outerWidth();
          $('.select2-container--open .select2-dropdown').css({
            width: w,
            minWidth: w
          });
        });
      });
    }

    function addRow(tbodySel, idx) {
      var $tbody = $(tbodySel);
      if (!$tbody.length) return;
      var html = tplHtml.replace(/@idx@/g, idx);
      $tbody.append(html);
      initProductSelect($tbody.find('tr').last());
    }

    var cIdx = 0;
    $('#btnAddRow').on('click', function() {
      addRow('#tblItems tbody', cIdx++);
    });
    $('#tblItems tbody').on('click', '.btnDelRow', function() {
      $(this).closest('tr').remove();
    });
    $modalCreate.on('shown.bs.modal', function() {
      if ($('#tblItems tbody tr').length === 0) $('#btnAddRow').trigger('click');
      else initProductSelect('#tblItems');
    });

    var eIdx = 0;
    $('#btnAddRowEdit').on('click', function() {
      addRow('#tblItemsEdit tbody', eIdx++);
    });
    $('#tblItemsEdit tbody').on('click', '.btnDelRow', function() {
      $(this).closest('tr').remove();
    });

    document.querySelectorAll('.btn-edit').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = btn.dataset.id;
        fetch(baseUrl + '/' + id, {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        })
        .then(function(r) {
          if (!r.ok) throw new Error('Gagal memuat data edit. HTTP ' + r.status);
          return r.json();
        })
        .then(function(data) {
          document.getElementById('formEdit').action = btn.dataset.updateUrl;
          document.getElementById('edit_store').value = data.store_id || '';
          document.getElementById('edit_warehouse').value = data.warehouse_id || '';
          document.getElementById('edit_date').value = (data.date || '').substring(0, 10);

          var $tb = $('#tblItemsEdit tbody');
          $tb.html('');
          eIdx = 0;

          (data.details || []).forEach(function(d) {
            addRow('#tblItemsEdit tbody', eIdx++);
            var $last = $('#tblItemsEdit tbody').find('tr').last();
            $last.find('select.js-p').val(d.product_id).trigger('change');
            $last.find('input[name$="[requested_qty]"]').val(d.requested_qty ?? 1);
            $last.find('input[name$="[note]"]').val(d.note || '');
          });

          if (!data.details || !data.details.length) addRow('#tblItemsEdit tbody', eIdx++);
          new bootstrap.Modal(document.getElementById('modalEdit')).show();
        })
        .catch(function(err) {
          console.error(err);
          alert(err.message || 'Gagal membuka modal edit.');
        });
      });
    });

    document.querySelectorAll('.btn-view').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = btn.dataset.id;
        fetch(baseUrl + '/' + id, {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        })
        .then(function(r) {
          if (!r.ok) throw new Error('Gagal memuat detail order. HTTP ' + r.status);
          return r.json();
        })
        .then(function(data) {
          var rows = (data.details || []).map(function(d) {
            var whName = (d.detail_warehouse && d.detail_warehouse.name) ? d.detail_warehouse.name : '-';

            return '<tr>' +
              '<td>' + escHtml((d.product && d.product.name) ? d.product.name : d.product_id) + '</td>' +
              '<td>' + escHtml(whName) + '</td>' +
              '<td class="text-end">' + (d.requested_qty ?? '-') + '</td>' +
              '<td class="text-end">' + (d.approved_qty ?? '-') + '</td>' +
              '<td>' + escHtml(d.note || '') + '</td>' +
              '</tr>';
          }).join('');

          document.getElementById('viewBody').innerHTML =
            '<div class="mb-2">' +
            '<div class="fw-medium">' + escHtml(data.order_number || '') + '</div>' +
            '<div class="text-muted small">' + escHtml(((data.store && data.store.name) || '-') + ' → ' + ((data.warehouse && data.warehouse.name) || '-')) + '</div>' +
            '<div class="text-muted small">Tanggal: ' + escHtml(data.date || '-') + '</div>' +
            '<div class="text-muted small">Status: ' + escHtml(data.status || '-') + '</div>' +
            '</div>' +
            '<div class="table-responsive"><table class="table"><thead class="table-light">' +
            '<tr><th>Produk</th><th>Warehouse</th><th class="text-end">Diminta</th><th class="text-end">Disetujui</th><th>Catatan</th></tr>' +
            '</thead><tbody>' +
            (rows || '<tr><td colspan="5" class="text-center text-muted">Tidak ada detail</td></tr>') +
            '</tbody></table></div>';

          new bootstrap.Modal(document.getElementById('modalView')).show();
        })
        .catch(function(err) {
          console.error(err);
          alert(err.message || 'Gagal membuka detail order.');
        });
      });
    });

    document.querySelectorAll('.btn-receive').forEach(function(btn) {
      btn.addEventListener('click', function() {
        fetch(baseUrl + '/' + btn.dataset.id, {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        })
        .then(function(r) {
          if (!r.ok) throw new Error('Gagal memuat detail penerimaan. HTTP ' + r.status);
          return r.json();
        })
        .then(function(data) {
          document.getElementById('formReceive').action = btn.dataset.receiveUrl;
          document.getElementById('receiveOrderInfo').textContent =
            (data.order_number || '-') + ' · ' + ((data.store && data.store.name) || 'Cabang');

          document.getElementById('receiveItems').innerHTML = (data.details || []).map(function(d) {
            var approved = Number(d.approved_qty || 0);
            var received = Number(d.received_qty || 0);
            return '<tr>' +
              '<td>' + escHtml((d.product && d.product.name) || d.product_id || '-') + '</td>' +
              '<td class="text-end">' + approved + '</td>' +
              '<td class="text-end">' + received + '</td>' +
              '<td><input class="form-control text-end" type="number" min="0" max="' + approved + '" name="received_qty[' + escHtml(d.id) + ']" value="' + received + '" required></td>' +
              '</tr>';
          }).join('') || '<tr><td colspan="4" class="text-center text-muted">Tidak ada detail order.</td></tr>';

          new bootstrap.Modal(document.getElementById('modalReceive')).show();
        })
        .catch(function(err) {
          console.error(err);
          alert(err.message || 'Gagal membuka penerimaan.');
        });
      });
    });

    const $apvTbody = $('#apv_tbody');
    const $btnDoApprove = $('#btnDoApprove');
    const $warn = $('#apv_warn');

    function lineHtml(detailId, selectedWh) {
      return '<div class="apv-line">' +
        '<div class="apv-wh-col">' +
          '<select class="form-select apv-wh-select" name="detail_warehouse_id[' + detailId + '][]">' +
            warehouseOptions(selectedWh) +
          '</select>' +
          '<div class="small-muted mt-1 apv-stock-info">Pilih warehouse</div>' +
        '</div>' +
        '<div class="apv-qty-col">' +
          '<input type="number" min="0" step="1" class="form-control text-end apv-approved" name="approved_qty[' + detailId + '][]" value="" disabled>' +
        '</div>' +
        '<div class="apv-stock-col text-end apv-stock">0</div>' +
        '<div class="apv-del-col">' +
          '<button type="button" class="btn btn-icon btn-text-secondary btn-sm apv-line-del"><i class="mdi mdi-close"></i></button>' +
        '</div>' +
      '</div>';
    }

    function getProductTotal($block) {
      let total = 0;
      $block.find('.apv-approved').each(function() {
        const v = parseInt($(this).val() || 0, 10);
        if (!Number.isNaN(v)) total += v;
      });
      return total;
    }

    function refreshProductState($block) {
      const requested = parseInt($block.data('requested') || 0, 10);
      const total = getProductTotal($block);
      $block.find('.apv-total').text(total);

      let blockOver = false;
      $block.find('.apv-line').each(function() {
        const $line = $(this);
        const stock = parseInt($line.find('.apv-stock').text() || 0, 10);
        const approved = parseInt($line.find('.apv-approved').val() || 0, 10);
        const lineOver = approved > stock;
        $line.toggleClass('apv-over-approved', lineOver);
        if (lineOver) blockOver = true;
      });

      const reqOver = total > requested;
      $block.toggleClass('apv-req-over', reqOver);
      $block.find('.apv-total-wrap').toggleClass('text-danger', reqOver);

      return { total: total, reqOver: reqOver, blockOver: blockOver };
    }

    function setApproveButtonState() {
      let hasOver = false;
      let totalApproved = 0;
      let hasAnyApprovedLine = false;

      $apvTbody.find('.apv-product-block').each(function() {
        const $block = $(this);
        const st = refreshProductState($block);
        totalApproved += st.total;
        if (st.blockOver || st.reqOver) hasOver = true;

        $block.find('.apv-line').each(function() {
          const $line = $(this);
          const wh = String($line.find('.apv-wh-select').val() || '').length > 0;
          const qty = parseInt($line.find('.apv-approved').val() || 0, 10);
          if (wh && qty > 0) hasAnyApprovedLine = true;
        });
      });

      const canApprove = hasAnyApprovedLine && !hasOver && totalApproved > 0;
      $btnDoApprove.prop('disabled', !canApprove);
      $warn.toggleClass('d-none', !hasOver);
    }

    function resetLineStock($line) {
      $line.removeClass('apv-over-approved');
      $line.find('.apv-stock').text('0');
      $line.find('.apv-approved').val('').attr('max', 0).prop('disabled', true);
      $line.find('.apv-stock-info').text('Pilih warehouse');
    }

    function updateLineStock($line, warehouseId) {
      const $block = $line.closest('.apv-product-block');
      const pid = String($block.data('product-id') || '').trim();
      const requested = parseInt($block.data('requested'), 10) || 0;

      if (!warehouseId || !pid) {
        resetLineStock($line);
        setApproveButtonState();
        return;
      }

      const params = new URLSearchParams();
      params.set('warehouse_id', String(warehouseId));
      params.set('product_ids', pid);

      const url = "{{ route('reports.order-cabang.stock') }}" + '?' + params.toString();

      $line.find('.apv-stock').text('0');
      $line.find('.apv-approved').val('').attr('max', 0).prop('disabled', true);
      $line.find('.apv-stock-info').text('Memuat...');

      fetch(url, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      })
      .then(function(r) {
        if (!r.ok) throw new Error('Gagal memuat stok. HTTP ' + r.status);
        return r.json();
      })
      .then(function(res) {
        const stocks = res.stocks || {};
        const stock = parseInt(stocks[pid] ?? 0, 10);

        $line.find('.apv-stock').text(stock);
        $line.find('.apv-stock-info').text(res.warehouse_name ? ('Stok ' + res.warehouse_name) : 'Stok dimuat');

        const $inp = $line.find('.apv-approved');
        $inp.attr('max', stock).prop('disabled', false);

        if (($inp.val() ?? '') === '') {
          const remaining = Math.max(requested - getProductTotal($block), 0);
          $inp.val(Math.min(remaining, stock));
        }

        setApproveButtonState();
      })
      .catch(function(err) {
        console.error(err);
        resetLineStock($line);
        $line.find('.apv-stock-info').text('Gagal memuat stok');
        setApproveButtonState();
      });
    }

    function addLineToBlock($block, selectedWh) {
      const detailId = $block.data('detail-id');
      const $line = $(lineHtml(detailId, selectedWh || ''));
      $block.find('.apv-lines').append($line);
      initApproveWarehouseSelects($line);
      return $line;
    }

    function applyApproveAll() {
      $apvTbody.find('.apv-product-block').each(function() {
        const $block = $(this);
        const requested = parseInt($block.data('requested'), 10) || 0;
        let remaining = requested;

        $block.find('.apv-line').each(function() {
          const $line = $(this);
          const hasWarehouse = String($line.find('.apv-wh-select').val() || '').length > 0;
          const stock = parseInt($line.find('.apv-stock').text() || 0, 10);

          if (!hasWarehouse) {
            $line.find('.apv-approved').val('');
            return;
          }

          const qty = Math.max(Math.min(remaining, stock), 0);
          $line.find('.apv-approved').val(qty);
          remaining -= qty;
        });
      });

      setApproveButtonState();
    }

    function buildApproveRows(data) {
      return (data.details || []).map(function(d) {
        const requested = d.requested_qty ?? 0;
        const pid = (d.product && d.product.id) ? d.product.id : (d.product_id || '');
        const pname = (d.product && d.product.name) ? d.product.name : pid;
        const imageUrl = (d.product && d.product.image_url) ? d.product.image_url : '';
        const selectedWh = d.warehouse_id || d.detail_warehouse_id || '';

        const imageHtml = imageUrl
          ? '<img src="' + escHtml(imageUrl) + '" alt="' + escHtml(pname) + '" class="apv-product-thumb">'
          : '<div class="apv-product-placeholder">-</div>';

        return '<div class="apv-product-block" data-product-id="' + escHtml(pid) + '" data-requested="' + requested + '" data-detail-id="' + escHtml(d.id) + '">' +
          '<div class="apv-product-header">' +
            '<div class="apv-product-box">' +
              imageHtml +
              '<div><div class="fw-medium">' + escHtml(pname) + '</div></div>' +
            '</div>' +
            '<div class="text-end apv-total-wrap">' +
              '<div class="small-muted">Diminta: <b>' + requested + '</b></div>' +
              '<div class="small-muted">Total disetujui: <b class="apv-total">0</b></div>' +
            '</div>' +
          '</div>' +
          '<div class="apv-line-head small-muted mb-1">' +
            '<div class="apv-wh-col">Warehouse</div>' +
            '<div class="apv-qty-col">Disetujui</div>' +
            '<div class="apv-stock-col text-end">Stok</div>' +
            '<div class="apv-del-col"></div>' +
          '</div>' +
          '<div class="apv-lines">' + lineHtml(d.id, selectedWh) + '</div>' +
          '<button type="button" class="btn btn-sm btn-outline-primary apv-add-line">' +
            '<i class="mdi mdi-plus"></i> Tambah Warehouse' +
          '</button>' +
        '</div>';
      }).join('');
    }

    function openApproveModal(data, approveUrl) {
      const f = document.getElementById('formApprove');
      f.action = approveUrl || '#';

      document.getElementById('apv_order_number').textContent = data.order_number || '';
      document.getElementById('apv_store_wh').textContent =
        ((data.store && data.store.name) || '-') + ' → Split Warehouse';

      $apvTbody.html(buildApproveRows(data));
      initApproveWarehouseSelects('#apv_tbody');

      $apvTbody.find('.apv-product-block').each(function() {
        const $block = $(this);
        const $firstLine = $block.find('.apv-line').first();
        const selectedWh = $firstLine.find('.apv-wh-select').val();
        if (selectedWh) {
          updateLineStock($firstLine, selectedWh);
        } else {
          resetLineStock($firstLine);
        }
      });

      document.getElementById('btnApproveAll').onclick = function() {
        applyApproveAll();
      };

      setApproveButtonState();
      new bootstrap.Modal(document.getElementById('modalApprove')).show();
    }

    document.querySelectorAll('.btn-approve').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = btn.dataset.id;

        fetch(baseUrl + '/' + id, {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin'
        })
        .then(function(r) {
          if (!r.ok) throw new Error('Gagal memuat data approve. HTTP ' + r.status);
          return r.text();
        })
        .then(function(text) {
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('Response bukan JSON:', text);
            throw new Error('Response show order bukan JSON. Cek method show() controller.');
          }
        })
        .then(function(data) {
          openApproveModal(data, btn.dataset.approveUrl);
        })
        .catch(function(err) {
          console.error(err);
          alert(err.message || 'Modal approve gagal dibuka.');
        });
      });
    });

    $apvTbody.on('change', '.apv-wh-select', function() {
      const $line = $(this).closest('.apv-line');
      updateLineStock($line, $(this).val());
    });

    $apvTbody.on('click', '.apv-add-line', function() {
      const $block = $(this).closest('.apv-product-block');
      addLineToBlock($block);
      setApproveButtonState();
    });

    $apvTbody.on('click', '.apv-line-del', function() {
      const $block = $(this).closest('.apv-product-block');
      const $line = $(this).closest('.apv-line');

      if ($block.find('.apv-line').length <= 1) {
        $line.find('.apv-wh-select').val('').trigger('change');
        $line.find('.apv-approved').val('').prop('disabled', true);
      } else {
        $line.remove();
      }

      setApproveButtonState();
    });

    $apvTbody.on('input', '.apv-approved', function() {
      setApproveButtonState();
    });

    document.getElementById('formApprove').addEventListener('keydown', function(e) {
      if (e.key === 'Enter') e.preventDefault();
    });

    $modalApprove.on('hidden.bs.modal', function() {
      const f = document.getElementById('formApprove');
      f.action = '#';
      $('#apv_tbody').html('');
      $('#apv_order_number').text('');
      $('#apv_store_wh').text('');
      $('#apv_warn').addClass('d-none');
      $('#btnDoApprove').prop('disabled', true);
    });
  })();
</script>
@endpush
