@extends('layouts/contentNavbarLayout')

@section('title', 'Branch Operations - Request Voucher')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
  .table thead th { white-space: nowrap; }
  .select2-container { width: 100% !important; }

  .dropdown-menu .dropdown-item {
    padding: 0.55rem 1rem;
    font-size: 14px;
  }

  .dropdown-menu .dropdown-item i {
    font-size: 18px;
  }

  .dropdown-menu {
    min-width: 180px;
    border: 0;
    border-radius: 10px;
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Branch Operations /</span> Request Voucher
  </h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card mb-3">
    <div class="card-header">
      <span class="fw-medium">Filter</span>
    </div>

    <div class="card-body">
      <form method="GET" action="{{ route('branch-operations.request-voucher.index') }}" class="row g-3 align-items-end">
        <div class="col-sm-6 col-md-3">
          <label class="form-label">Dari</label>
          <input type="date" name="from" class="form-control" value="{{ $from }}">
        </div>

        <div class="col-sm-6 col-md-3">
          <label class="form-label">Sampai</label>
          <input type="date" name="to" class="form-control" value="{{ $to }}">
        </div>

        <div class="col-sm-6 col-md-3">
          <label class="form-label">Store</label>
          <select name="store_id" class="form-select js-select2-filter" data-placeholder="Semua Store">
            <option value="">Semua Store</option>
            @foreach($stores as $store)
              <option value="{{ $store->id }}" {{ (string) $storeId === (string) $store->id ? 'selected' : '' }}>
                {{ $store->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-sm-6 col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select js-select2-filter" data-placeholder="Semua Status">
            <option value="">Semua Status</option>
            @foreach($statuses as $item)
              <option value="{{ $item }}" {{ (string) $status === (string) $item ? 'selected' : '' }}>
                {{ ucfirst($item) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Cari</label>
          <input
            type="text"
            name="q"
            class="form-control"
            value="{{ $q }}"
            placeholder="Request ID / Request Number / Store / Voucher Code / Voucher Name / Notes"
          >
        </div>

        <div class="col-12 d-flex gap-2 flex-wrap">
          <button class="btn btn-primary" type="submit">
            <i class="mdi mdi-magnify me-1"></i> Tampilkan
          </button>

          <a href="{{ route('branch-operations.request-voucher.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-refresh me-1"></i> Reset
          </a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <h5 class="card-header">Kelola Request Voucher</h5>

    <div class="table-responsive">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th>Tanggal Request</th>
            <th>Request Number</th>
            <th>Request ID</th>
            <th>Store</th>
            <th>Requested By</th>
            <th>Notes</th>
            <th>Status</th>
            <th>Total Item</th>
            <th>Voucher</th>
            <th>Approved By</th>
            <th>Approved At</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>

        <tbody class="table-border-bottom-0">
          @forelse($rows as $row)
            <tr>
              <td class="text-nowrap">
                {{ $row->request_date ? \Carbon\Carbon::parse($row->request_date)->format('d M Y H:i') : '-' }}
              </td>

              <td>{{ $row->request_number ?? '-' }}</td>
              <td>{{ $row->id }}</td>
              <td>{{ $row->store_name ?? '-' }}</td>
              <td>{{ $row->requested_by_name ?? '-' }}</td>
              <td>{{ $row->notes ?? '-' }}</td>

              <td>
                @if((string) $row->status === 'pending')
                  <span class="badge bg-label-warning">Pending</span>
                @elseif((string) $row->status === 'approved')
                  <span class="badge bg-label-success">Approved</span>
                @elseif((string) $row->status === 'rejected')
                  <span class="badge bg-label-danger">Rejected</span>
                @else
                  <span class="badge bg-label-secondary">{{ $row->status_label ?? '-' }}</span>
                @endif
              </td>

              <td class="text-center">{{ $row->total_items ?? 0 }}</td>

              <td>
                @if($row->voucher_code)
                  <div>{{ $row->voucher_code }}</div>
                  <small class="text-muted">{{ $row->voucher_name }}</small>
                @else
                  -
                @endif
              </td>

              <td>{{ $row->approved_by_name ?? '-' }}</td>

              <td class="text-nowrap">
                {{ $row->approved_at ? \Carbon\Carbon::parse($row->approved_at)->format('d M Y H:i') : '-' }}
              </td>

              <td class="text-center">
                <div class="dropdown">
                  <button
                    type="button"
                    class="btn p-0 border-0 bg-transparent text-muted"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <i class="mdi mdi-dots-vertical fs-5"></i>
                  </button>

                  <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                      <button
                        type="button"
                        class="dropdown-item d-flex align-items-center gap-2"
                        data-bs-toggle="modal"
                        data-bs-target="#detailModal-{{ $row->id }}"
                      >
                        <i class="mdi mdi-eye-outline"></i>
                        <span>View</span>
                      </button>
                    </li>

                    @if((string) $row->status === 'pending')
                      <li>
                        <form method="POST" action="{{ route('branch-operations.request-voucher.approve', $row->id) }}">
                          @csrf
                          @method('PUT')

                          <button
                            type="submit"
                            class="dropdown-item d-flex align-items-center gap-2 text-success"
                            onclick="return confirm('Yakin ingin approve request ini?')"
                          >
                            <i class="mdi mdi-check"></i>
                            <span>Approve</span>
                          </button>
                        </form>
                      </li>

                      <li>
                        <form method="POST" action="{{ route('branch-operations.request-voucher.reject', $row->id) }}">
                          @csrf
                          @method('PUT')

                          <button
                            type="submit"
                            class="dropdown-item d-flex align-items-center gap-2 text-danger"
                            onclick="return confirm('Yakin ingin reject request ini?')"
                          >
                            <i class="mdi mdi-close-circle-outline"></i>
                            <span>Reject</span>
                          </button>
                        </form>
                      </li>
                    @endif
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="12" class="text-center text-muted">Belum ada data</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($rows->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="text-muted small">
          Menampilkan {{ $rows->firstItem() ?? 0 }} - {{ $rows->lastItem() ?? 0 }} dari {{ $rows->total() }} data
        </div>
        {{ $rows->links() }}
      </div>
    @endif
  </div>

  @foreach($rows as $row)
    @php
      $items = $detailItems->get($row->id, collect());
    @endphp

    <div class="modal fade" id="detailModal-{{ $row->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title mb-1">Detail Request Voucher</h5>
              <div class="small text-muted">
                Request #{{ $row->request_number ?? '-' }} | {{ $row->store_name ?? '-' }} | {{ $row->requested_by_name ?? '-' }}
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label">Request Number</label>
                <input type="text" class="form-control" value="{{ $row->request_number ?? '-' }}" readonly>
              </div>

              <div class="col-md-4">
                <label class="form-label">Store</label>
                <input type="text" class="form-control" value="{{ $row->store_name ?? '-' }}" readonly>
              </div>

              <div class="col-md-4">
                <label class="form-label">Status</label>
                <input type="text" class="form-control" value="{{ $row->status_label ?? '-' }}" readonly>
              </div>

              <div class="col-md-4">
                <label class="form-label">Tanggal Request</label>
                <input
                  type="text"
                  class="form-control"
                  value="{{ $row->request_date ? \Carbon\Carbon::parse($row->request_date)->format('d M Y H:i') : '-' }}"
                  readonly
                >
              </div>

              <div class="col-md-4">
                <label class="form-label">Voucher Code</label>
                <input type="text" class="form-control" value="{{ $row->voucher_code ?? '-' }}" readonly>
              </div>

              <div class="col-md-4">
                <label class="form-label">Voucher Name</label>
                <input type="text" class="form-control" value="{{ $row->voucher_name ?? '-' }}" readonly>
              </div>

              <div class="col-md-6">
                <label class="form-label">Valid From</label>
                <input
                  type="text"
                  class="form-control"
                  value="{{ $row->valid_from ? \Carbon\Carbon::parse($row->valid_from)->format('d M Y H:i') : '-' }}"
                  readonly
                >
              </div>

              <div class="col-md-6">
                <label class="form-label">Valid Until</label>
                <input
                  type="text"
                  class="form-control"
                  value="{{ $row->valid_until ? \Carbon\Carbon::parse($row->valid_until)->format('d M Y H:i') : '-' }}"
                  readonly
                >
              </div>

              <div class="col-md-12">
                <label class="form-label">Notes</label>
                <textarea class="form-control" rows="2" readonly>{{ $row->notes ?? '-' }}</textarea>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                  <tr>
                    <th>No</th>
                    <th>Item ID</th>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Item Status</th>
                  </tr>
                </thead>

                <tbody>
                  @forelse($items as $index => $item)
                    <tr>
                      <td>{{ $index + 1 }}</td>
                      <td>{{ $item->item_id ?? '-' }}</td>
                      <td>{{ $item->product_code ?? '-' }}</td>
                      <td>{{ $item->product_name ?? '-' }}</td>
                      <td>{{ $item->item_status ?? '-' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-muted">Belum ada item request</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>
  @endforeach
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
  });
})(window.jQuery);
</script>
@endpush