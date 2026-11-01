@extends('layouts/contentNavbarLayout')

@section('title', 'Branch Operations - Request Label')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
  .table thead th { white-space: nowrap; }
  .select2-container { width: 100% !important; }

</style>
@endpush

@section('content')
@php
  $labelRequestStatus = [
    'pending' => ['label' => 'Pending', 'class' => 'bg-label-warning'],
    'approved' => ['label' => 'Approved', 'class' => 'bg-label-success'],
    'rejected' => ['label' => 'Rejected', 'class' => 'bg-label-danger'],
    'printed' => ['label' => 'Printed', 'class' => 'bg-label-info'],
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Branch Operations /</span> Request Label
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
      <form method="GET" action="{{ route('branch-operations.request-label.index') }}" class="row g-3 align-items-end">
        <div class="col-sm-6 col-md-3">
          <label class="form-label">Dari</label>
          <input type="date" name="from" class="form-control" value="{{ $from }}">
        </div>

        <div class="col-sm-6 col-md-3">
          <label class="form-label">Sampai</label>
          <input type="date" name="to" class="form-control" value="{{ $to }}">
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label">Store</label>
          <select name="store_id" class="form-select js-select2-filter" data-placeholder="Semua Store">
            <option value="">Semua Store</option>
            @foreach($stores as $store)
              <option value="{{ $store->id }}" {{ (string)$storeId === (string)$store->id ? 'selected' : '' }}>
                {{ $store->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select js-select2-filter" data-placeholder="Semua Status">
            <option value="">Semua Status</option>
            @foreach($statuses as $item)
              <option value="{{ $item }}" {{ (string)$status === (string)$item ? 'selected' : '' }}>
                @switch($item)
                  @case('pending')
                    Pending
                    @break
                  @case('approved')
                    Approved
                    @break
                  @case('rejected')
                    Rejected
                    @break
                  @case('printed')
                    Printed
                    @break
                  @default
                    {{ ucfirst(str_replace('_', ' ', $item)) }}
                @endswitch
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label">Requested By</label>
          <select name="requested_by" class="form-select js-select2-filter" data-placeholder="Semua User">
            <option value="">Semua User</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}" {{ (string)$requestedBy === (string)$user->id ? 'selected' : '' }}>
                {{ $user->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-sm-6 col-md-6">
          <label class="form-label">Cari</label>
          <input
            type="text"
            name="q"
            class="form-control"
            value="{{ $q }}"
            placeholder="Request ID / store / requested by / reason"
          >
        </div>

        <div class="col-12 d-flex gap-2 flex-wrap">
          <button class="btn btn-primary">
            <i class="mdi mdi-magnify me-1"></i> Tampilkan
          </button>

          <a href="{{ route('branch-operations.request-label.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-refresh me-1"></i> Reset
          </a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <h5 class="card-header">Riwayat Request Label</h5>

    <div class="table-responsive">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th>Tanggal Request</th>
            <th>Request ID</th>
            <th>Store</th>
            <th>Requested By</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Total Item</th>
            <th>Total Printed</th>
            <th>Approved By</th>
            <th>Approved At</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($rows as $row)
            @php
              $statusKey = strtolower((string) $row->status);
              $statusMeta = $labelRequestStatus[$statusKey] ?? [
                'label' => $row->status ?: '-',
                'class' => 'bg-label-secondary',
              ];
            @endphp
            <tr>
              <td class="text-nowrap">
                {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') : '-' }}
              </td>
              <td>{{ $row->id }}</td>
              <td>{{ $row->store_name ?? '-' }}</td>
              <td>{{ $row->requested_by_name ?? '-' }}</td>
              <td>{{ $row->reason ?? '-' }}</td>
              <td><span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span></td>
              <td class="text-center">{{ $row->total_items ?? 0 }}</td>
              <td class="text-center">{{ $row->total_printed ?? 0 }}</td>
              <td>{{ $row->approved_by_name ?? '-' }}</td>
              <td class="text-nowrap">
                {{ $row->approved_at ? \Carbon\Carbon::parse($row->approved_at)->format('d M Y H:i') : '-' }}
              </td>
              <td class="text-center">
                <div class="dropdown">
                  <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" type="button" aria-label="Aksi request label">
                    <i class="mdi mdi-dots-vertical"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end">
                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#detailModal-{{ $row->id }}">
                      <i class="mdi mdi-eye-outline me-1"></i> View
                    </button>

                    @if($canDecide && $statusKey === 'pending')
                      <div class="dropdown-divider"></div>
                      <form method="POST" action="{{ route('management.request-labels.approve', $row->id) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="from_branch" value="1">
                        <button type="submit" class="dropdown-item" onclick="return confirm('Setujui request label ini?')">
                          <i class="mdi mdi-check-outline me-1"></i> Approve
                        </button>
                      </form>
                      <form method="POST" action="{{ route('management.request-labels.reject', $row->id) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="from_branch" value="1">
                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Tolak request label ini?')">
                          <i class="mdi mdi-close-circle-outline me-1"></i> Reject
                        </button>
                      </form>
                    @endif
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="text-center text-muted">Belum ada request label pada filter ini.</td>
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
              <h5 class="modal-title mb-1">Detail Request Label</h5>
              <div class="small text-muted">
                Request #{{ $row->id }} | {{ $row->store_name ?? '-' }} | {{ $row->requested_by_name ?? '-' }}
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Item ID</th>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Item Status</th>
                    <th>Print Index</th>
                    <th>Printed By</th>
                    <th>Printed At</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($items as $item)
                    <tr>
                      <td>{{ $item->item_id ?? '-' }}</td>
                      <td>{{ $item->product_code ?? '-' }}</td>
                      <td>{{ $item->product_name ?? '-' }}</td>
                      <td>{{ $item->item_status ?? '-' }}</td>
                      <td>{{ $item->print_index ?? '-' }}</td>
                      <td>{{ $item->printed_by_name ?? '-' }}</td>
                      <td class="text-nowrap">
                        {{ $item->printed_at ? \Carbon\Carbon::parse($item->printed_at)->format('d M Y H:i') : '-' }}
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="7" class="text-center text-muted">Belum ada item request</td>
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
