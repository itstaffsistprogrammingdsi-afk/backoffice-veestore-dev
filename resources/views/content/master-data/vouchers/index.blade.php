@extends('layouts/contentNavbarLayout')

@section('title', 'Master Promo & Discount - Vouchers')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
  .select2-container {
    width: 100% !important;
  }

  .modal-body {
    max-height: calc(100vh - 220px);
    overflow: auto;
  }

  .select2-results__option[aria-selected=true] {
    background-color: rgba(105, 108, 255, .12);
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">Master Promo & Discount /</span> Vouchers</h4>

  {{-- Flash --}}
  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
    <button class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif
  @if($errors->any() && old('_from')===null)
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali form Anda.
    <button class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Vouchers</span>
      <div class="d-flex gap-2">
        <form method="GET" action="{{ route('master-data.vouchers.index') }}" class="d-flex gap-2">
          <input type="text" name="q" class="form-control form-control-sm" value="{{ $q ?? '' }}"
            placeholder="Cari nama / kode...">
          <button class="btn btn-outline-secondary btn-sm" type="submit" data-bs-toggle="tooltip" title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if(($q ?? '')!=='')
          <a href="{{ route('master-data.vouchers.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
            <i class="mdi mdi-refresh"></i>
          </a>
          @endif
        </form>

        @can('create master-data-vouchers')
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
          <i class="mdi mdi-plus me-1"></i> Tambah
        </button>
        @endcan
      </div>
    </h5>

    <div class="table-responsive text-nowrap">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:60px;">#</th>
            <th>Kode</th>
            <th>Nama</th>
            <th>Tipe</th>
            <th>Nilai</th>
            <th>Min. Belanja</th>
            <th>Context</th>
            <th>Distribusi</th>
            <th>Penggunaan</th>
            <th>Status</th>
            <th class="text-center" style="width:120px;">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($vouchers as $i => $v)
          @php
          $key = $v->id ?? $v->uuid ?? $v->code;

          $contextsArr = [];
          if (method_exists($v, 'contexts') && $v->relationLoaded('contexts')) {
          $contextsArr = $v->contexts->pluck('context')->all();
          } elseif (isset($v->contexts) && is_iterable($v->contexts)) {
          $contextsArr = collect($v->contexts)->pluck('context')->all();
          } elseif (!empty($voucherContextsMap[$v->id] ?? [])) {
          $contextsArr = (array) $voucherContextsMap[$v->id];
          }
          $contextsCsv = implode(',', $contextsArr);
          @endphp

          <tr>
            <td>{{ $vouchers->firstItem() + $i }}</td>
            <td class="fw-medium"><span class="badge bg-label-primary">{{ $v->code }}</span></td>
            <td>{{ $v->name }}</td>
            <td><span class="badge bg-label-info">{{ ucfirst($v->type) }}</span></td>
            <td>
              @if($v->type === 'fixed')
              Rp {{ number_format($v->value, 0, ',', '.') }}
              @else
              {{ rtrim(rtrim(number_format($v->value, 2, ',', '.'), '0'), ',') }}%
              @if(!is_null($v->max_discount))
              <span class="text-muted small d-block">Max Rp {{ number_format($v->max_discount, 0, ',', '.') }}</span>
              @endif
              @endif
            </td>

            <td>
              @if($v->distribution_type === 'unique_code')
                <span class="badge bg-label-warning"><i class="mdi mdi-barcode me-1"></i>Unique Code</span>
                <small class="text-muted d-block mt-1">{{ number_format($v->voucher_codes_count ?? 0) }} kode dibuat</small>
              @else
                <span class="badge bg-label-primary"><i class="mdi mdi-earth me-1"></i>Public</span>
              @endif
            </td>
            <td>
              <div class="fw-semibold">{{ number_format($v->usages_count ?? $v->usage_count ?? 0) }}{{ $v->usage_limit ? ' / '.number_format($v->usage_limit) : ' / ∞' }}</div>
              <small class="text-muted">Terpakai / batas voucher</small>
            </td>
            <td>Rp {{ number_format($v->min_purchase ?? 0, 0, ',', '.') }}</td>

            <td>
              @if(!empty($contextsArr))
              @foreach($contextsArr as $c)
              <span class="badge bg-label-secondary me-1 mb-1">{{ $c }}</span>
              @endforeach
              @else
              <span class="text-muted">-</span>
              @endif
            </td>

            <td>
              {!! $v->is_active
              ? '<span class="badge bg-label-success">Aktif</span>'
              : '<span class="badge bg-label-danger">Nonaktif</span>' !!}
            </td>

            <td class="text-center">
              <div class="dropdown">
                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  @can('update master-data-vouchers')
                  <a href="#" class="dropdown-item btn-edit" data-bs-toggle="modal" data-bs-target="#modalEdit"
                    data-action="{{ route('master-data.vouchers.update', $key) }}"
                    data-code="{{ e($v->code) }}"
                    data-name="{{ e($v->name) }}"
                    data-description="{{ e($v->description) }}"
                    data-type="{{ $v->type }}"
                    data-value="{{ $v->value }}"
                    data-max_discount="{{ $v->max_discount }}"
                    data-min_purchase="{{ $v->min_purchase }}"
                    data-usage_limit="{{ $v->usage_limit }}"
                    data-distribution_type="{{ $v->distribution_type ?? 'public' }}"
                    data-valid_from="{{ optional($v->valid_from)->format('Y-m-d\TH:i') }}"
                    data-valid_until="{{ optional($v->valid_until)->format('Y-m-d\TH:i') }}"
                    data-is_active="{{ $v->is_active ? 1 : 0 }}"
                    data-contexts="{{ e($contextsCsv) }}">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit
                  </a>
                  @endcan

                  @if(($v->distribution_type ?? 'public') === 'unique_code')
                  <a href="{{ route('master-data.vouchers.codes', $key) }}" class="dropdown-item">
                    <i class="mdi mdi-barcode-scan me-1"></i> Kelola Kode
                  </a>
                  <a href="#" class="dropdown-item btn-print-codes"
                    data-bs-toggle="modal" data-bs-target="#modalPrintCodes"
                    data-action="{{ route('master-data.vouchers.print-codes', $key) }}"
                    data-name="{{ e($v->name) }}" data-code="{{ e($v->code) }}"
                    data-code-count="{{ $v->voucher_codes_count ?? 0 }}"
                    data-usage-limit="{{ $v->usage_limit ?? 0 }}"
                    data-valid-from="{{ optional($v->valid_from)->format('d M Y H:i') ?: '-' }}"
                    data-valid-until="{{ optional($v->valid_until)->format('d M Y H:i') ?: '-' }}">
                    <i class="mdi mdi-printer-outline me-1"></i> Cetak Voucher
                  </a>
                  @endif

                  @can('delete master-data-vouchers')
                  <a href="#" class="dropdown-item text-danger btn-delete"
                    data-action="{{ route('master-data.vouchers.destroy', $key) }}">
                    <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                  </a>
                  @endcan
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="11" class="text-center py-5 text-muted">
              <i class="mdi mdi-ticket-percent-outline d-block mb-2" style="font-size:48px;opacity:.35;"></i>
              Belum ada data voucher
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($vouchers->hasPages())
    <div class="card-footer d-flex justify-content-end">
      {{ $vouchers->appends(['q' => $q ?? null])->links() }}
    </div>
    @endif
  </div>
</div>

{{-- ===================== MODAL CREATE ===================== --}}
@can('create master-data-vouchers')
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('master-data.vouchers.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-ticket-percent-outline me-1"></i> Tambah Voucher</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && old('_from')==='create')
          <div class="alert alert-danger">
            <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal menyimpan. Periksa kembali:
            <ul class="mb-0 mt-2 ps-3">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
          </div>
          @endif

          @php($p = 'create')
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input type="text" name="name" id="{{ $p }}_name" class="form-control"
                value="{{ old('_from')==='create' ? old('name') : '' }}" required>
            </div>

            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea name="description" id="{{ $p }}_description" class="form-control"
                rows="2">{{ old('_from')==='create' ? old('description') : '' }}</textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Tipe <span class="text-danger">*</span></label>
              <select name="type" id="{{ $p }}_type" class="form-select" required>
                <option value="fixed" @selected(old('_from')==='create' ? old('type')==='fixed' : true)>Fixed</option>
                <option value="percentage" @selected(old('_from')==='create' && old('type')==='percentage' )>Percentage</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Nilai <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" name="value" id="{{ $p }}_value"
                class="form-control" value="{{ old('_from')==='create' ? old('value') : '' }}" required>
            </div>
            <div class="col-md-4" id="{{ $p }}_max_discount_wrap" style="display:none">
              <label class="form-label">Maks. Diskon</label>
              <input type="number" step="0.01" min="0" name="max_discount" id="{{ $p }}_max_discount"
                class="form-control" value="{{ old('_from')==='create' ? old('max_discount') : '' }}">
            </div>

            <div class="col-md-4">
              <label class="form-label">Min. Belanja</label>
              <input type="number" step="0.01" min="0" name="min_purchase" id="{{ $p }}_min_purchase"
                class="form-control" value="{{ old('_from')==='create' ? old('min_purchase') : '' }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Berlaku Dari</label>
              <input type="datetime-local" name="valid_from" id="{{ $p }}_valid_from" class="form-control"
                value="{{ old('_from')==='create' ? old('valid_from') : '' }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Berlaku Sampai</label>
              <input type="datetime-local" name="valid_until" id="{{ $p }}_valid_until" class="form-control"
                value="{{ old('_from')==='create' ? old('valid_until') : '' }}">
            </div>

            <div class="col-md-4">
              <label class="form-label">Batas Penggunaan</label>
              <input type="number" min="1" name="usage_limit" id="{{ $p }}_usage_limit"
                class="form-control" value="{{ old('_from')==='create' ? old('usage_limit') : '' }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Tipe Distribusi <span class="text-danger">*</span></label>
              <select name="distribution_type" id="{{ $p }}_distribution_type" class="form-select" required>
                @foreach(($distributionTypes ?? ['public', 'unique_code']) as $distributionType)
                  <option value="{{ $distributionType }}" @selected(old('_from')==='create' ? old('distribution_type') === $distributionType : $distributionType === 'public')>
                    {{ $distributionType === 'unique_code' ? 'Unique Code' : 'Public' }}
                  </option>
                @endforeach
              </select>
              <div class="form-text">Sistem otomatis membuat kode sejumlah batas penggunaan.</div>
            </div>

            {{-- CONTEXTS MULTI SELECT (CREATE) + REQUIRED --}}
            <div class="col-md-8">
              <label class="form-label">Context <span class="text-danger">*</span></label>
              <select name="contexts[]" id="{{ $p }}_contexts" class="form-select js-contexts" required
                data-placeholder="Pilih context...">
                @foreach(($availableContexts ?? []) as $ctx)
                <option value="{{ $ctx }}" @selected(old('_from')==='create' && collect(old('contexts', []))->contains($ctx))>
                  {{ $ctx }}
                </option>
                @endforeach
              </select>
             <!--  <select name="contexts[]" id="{{ $p }}_contexts" class="form-select js-contexts" multiple required
                data-placeholder="Pilih context...">
                @foreach(($availableContexts ?? []) as $ctx)
                <option value="{{ $ctx }}" @selected(old('_from')==='create' && collect(old('contexts', []))->contains($ctx))>
                  {{ $ctx }}
                </option>
                @endforeach
              </select> -->
              <div class="form-text">Wajib pilih minimal 1 context.</div>
            </div>

            {{-- ✅ STATUS (CREATE) default NONAKTIF + selalu kirim 0/1 --}}
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active" id="{{ $p }}_is_active"
                  value="1" @checked(old('_from')==='create' ? (int)old('is_active')===1 : false)>
                <label class="form-check-label" for="{{ $p }}_is_active">Aktif</label>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" id="btnCreateSave"><i class="mdi mdi-content-save-outline me-1"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- ===================== MODAL EDIT ===================== --}}
@can('update master-data-vouchers')
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formEdit" method="POST" action="#">
        @csrf @method('PUT')
        <input type="hidden" name="_from" value="edit">
        <input type="hidden" name="edit_action" id="edit_action"
          value="{{ old('_from')==='edit' ? old('edit_action') : '' }}">

        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-pencil-outline me-1"></i> Edit Voucher</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && old('_from')==='edit')
          <div class="alert alert-danger">
            <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal update. Periksa kembali:
            <ul class="mb-0 mt-2 ps-3">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
          </div>
          @endif

          @php($p = 'edit')
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input type="text" name="name" id="{{ $p }}_name" class="form-control"
                value="{{ old('_from')==='edit' ? old('name') : '' }}" required>
            </div>

            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea name="description" id="{{ $p }}_description" class="form-control"
                rows="2">{{ old('_from')==='edit' ? old('description') : '' }}</textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Tipe <span class="text-danger">*</span></label>
              <select name="type" id="{{ $p }}_type" class="form-select" required>
                <option value="fixed" @selected(old('_from')==='edit' && old('type')==='fixed' )>Fixed</option>
                <option value="percentage" @selected(old('_from')==='edit' && old('type')==='percentage' )>Percentage</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Nilai <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" name="value" id="{{ $p }}_value"
                class="form-control" value="{{ old('_from')==='edit' ? old('value') : '' }}" required>
            </div>
            <div class="col-md-4" id="{{ $p }}_max_discount_wrap" style="display:none">
              <label class="form-label">Maks. Diskon</label>
              <input type="number" step="0.01" min="0" name="max_discount" id="{{ $p }}_max_discount"
                class="form-control" value="{{ old('_from')==='edit' ? old('max_discount') : '' }}">
            </div>

            <div class="col-md-4">
              <label class="form-label">Min. Belanja</label>
              <input type="number" step="0.01" min="0" name="min_purchase" id="{{ $p }}_min_purchase"
                class="form-control" value="{{ old('_from')==='edit' ? old('min_purchase') : '' }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Berlaku Dari</label>
              <input type="datetime-local" name="valid_from" id="{{ $p }}_valid_from" class="form-control"
                value="{{ old('_from')==='edit' ? old('valid_from') : '' }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Berlaku Sampai</label>
              <input type="datetime-local" name="valid_until" id="{{ $p }}_valid_until" class="form-control"
                value="{{ old('_from')==='edit' ? old('valid_until') : '' }}">
            </div>

            <div class="col-md-4">
              <label class="form-label">Batas Penggunaan</label>
              <input type="number" min="1" name="usage_limit" id="{{ $p }}_usage_limit"
                class="form-control" value="{{ old('_from')==='edit' ? old('usage_limit') : '' }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Tipe Distribusi <span class="text-danger">*</span></label>
              <select name="distribution_type" id="{{ $p }}_distribution_type" class="form-select" required>
                @foreach(($distributionTypes ?? ['public', 'unique_code']) as $distributionType)
                  <option value="{{ $distributionType }}" @selected(old('_from')==='edit' && old('distribution_type') === $distributionType)>
                    {{ $distributionType === 'unique_code' ? 'Unique Code' : 'Public' }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- CONTEXTS MULTI SELECT (EDIT) + REQUIRED --}}
            <div class="col-md-8">
              <label class="form-label">Context <span class="text-danger">*</span></label>
              <select name="contexts[]" id="{{ $p }}_contexts" class="form-select js-contexts"  required
                data-placeholder="Pilih context...">
                @foreach(($availableContexts ?? []) as $ctx)
                <option value="{{ $ctx }}" @selected(old('_from')==='edit' ? (collect(old('contexts', []))->contains($ctx)) : false)>
                  {{ $ctx }}
                </option>
                @endforeach
              </select>
             <!--  <select name="contexts[]" id="{{ $p }}_contexts" class="form-select js-contexts" multiple required
                data-placeholder="Pilih context...">
                @foreach(($availableContexts ?? []) as $ctx)
                <option value="{{ $ctx }}" @selected(old('_from')==='edit' ? (collect(old('contexts', []))->contains($ctx)) : false)>
                  {{ $ctx }}
                </option>
                @endforeach
              </select> -->
              <div class="form-text">Wajib pilih minimal 1 context.</div>
            </div>

            {{-- ✅ STATUS (EDIT) + selalu kirim 0/1 --}}
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active" id="{{ $p }}_is_active"
                  value="1" @checked(old('_from')==='edit' ? (int)old('is_active')===1 : false)>
                <label class="form-check-label" for="{{ $p }}_is_active">Aktif</label>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update</span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" id="btnEditSave"><i class="mdi mdi-content-save-outline me-1"></i> Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- ===================== MODAL PRINT UNIQUE CODES ===================== --}}
@can('update master-data-vouchers')
<div class="modal fade" id="modalPrintCodes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formPrintCodes" method="POST" action="#" target="_blank">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-printer-outline me-1"></i>Cetak Voucher Unique Code</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="rounded p-3 mb-3 bg-label-primary">
            <div class="small text-muted">Voucher</div>
            <div class="fw-semibold" id="printVoucherName">-</div>
            <div class="small" id="printVoucherCode">-</div>
          </div>
          <div class="row g-2 small text-muted mb-3">
            <div class="col-6">Berlaku dari<br><strong class="text-body" id="printValidFrom">-</strong></div>
            <div class="col-6">Berakhir pada<br><strong class="text-body" id="printValidUntil">-</strong></div>
          </div>
          <label class="form-label" for="printQuantity">Jumlah voucher yang dicetak <span class="text-danger">*</span></label>
          <input class="form-control" type="number" id="printQuantity" name="quantity" min="1" max="100" value="1" required>
          <div class="form-text">Cetak kode yang sudah dibuat otomatis sesuai batas penggunaan.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary"><i class="mdi mdi-printer me-1"></i>Cetak Voucher</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

@can('delete master-data-vouchers')
<form id="formDelete" class="d-none" method="POST">@csrf @method('DELETE')</form>
@endcan
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@push('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Tooltip
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

    // ALT+ENTER submit
    document.getElementById('modalCreate')?.addEventListener('keydown', e => {
      if (e.altKey && e.key === 'Enter') {
        e.preventDefault();
        document.querySelector('#modalCreate form')?.requestSubmit();
      }
    });
    document.getElementById('modalEdit')?.addEventListener('keydown', e => {
      if (e.altKey && e.key === 'Enter') {
        e.preventDefault();
        document.querySelector('#modalEdit form')?.requestSubmit();
      }
    });

    // Toggle max_discount by type
    function hookType(prefix) {
      const typeEl = document.getElementById(prefix + '_type');
      const wrap = document.getElementById(prefix + '_max_discount_wrap');
      if (!typeEl || !wrap) return;
      const toggle = () => {
        wrap.style.display = (typeEl.value === 'percentage') ? 'block' : 'none';
      };
      toggle();
      typeEl.addEventListener('change', toggle);
    }
    hookType('create');
    hookType('edit');

    // Init Select2 contexts (checklist-like)
    function initContextsSelect(modalId) {
      const modal = document.querySelector(modalId);
      if (!modal || !window.jQuery) return;

      jQuery(modal).find('.js-contexts').each(function() {
        const $el = jQuery(this);
        if ($el.hasClass('select2-hidden-accessible')) return;

        $el.select2({
          theme: 'bootstrap-5',
          width: '100%',
          closeOnSelect: false,
          placeholder: $el.data('placeholder') || 'Pilih context...',
          dropdownParent: jQuery(modal)
        });
      });
    }
    initContextsSelect('#modalCreate');
    initContextsSelect('#modalEdit');
    jQuery('#modalCreate').on('shown.bs.modal', function() {
      initContextsSelect('#modalCreate');
    });
    jQuery('#modalEdit').on('shown.bs.modal', function() {
      initContextsSelect('#modalEdit');
    });

    // Helper: set multi-select values (select2 friendly), buat option kalau belum ada
    function setMultiSelectValues(selectEl, values) {
      if (!selectEl) return;
      const wanted = Array.isArray(values) ?
        values :
        ((values || '').split(',').map(s => s.trim()).filter(Boolean));

      wanted.forEach(v => {
        if (![...selectEl.options].some(o => o.value === v)) {
          const opt = document.createElement('option');
          opt.value = v;
          opt.textContent = v;
          selectEl.appendChild(opt);
        }
      });

      [...selectEl.options].forEach(o => {
        o.selected = wanted.includes(o.value);
      });

      if (window.jQuery && jQuery(selectEl).hasClass('select2-hidden-accessible')) {
        jQuery(selectEl).trigger('change');
      }
    }

    // EDIT fill
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', () => {
        const f = document.getElementById('formEdit');
        f.action = btn.dataset.action || '#';
        document.getElementById('edit_action').value = f.action;

        const setVal = (id, val, isCheck = false) => {
          const el = document.getElementById('edit_' + id);
          if (!el) return;
          if (isCheck) el.checked = (val === '1' || val === 1 || val === true);
          else el.value = (val ?? '');
        };

        setVal('name', btn.dataset.name);
        setVal('description', btn.dataset.description);
        setVal('type', btn.dataset.type);
        setVal('value', btn.dataset.value);
        setVal('max_discount', btn.dataset.max_discount);
        setVal('min_purchase', btn.dataset.min_purchase);
        setVal('usage_limit', btn.dataset.usage_limit);
        setVal('distribution_type', btn.dataset.distribution_type || 'public');
        setVal('valid_from', btn.dataset.valid_from || '');
        setVal('valid_until', btn.dataset.valid_until || '');
        setVal('is_active', btn.dataset.is_active, true);

        // contexts
        setMultiSelectValues(document.getElementById('edit_contexts'), btn.dataset.contexts || '');

        // update visibility
        document.getElementById('edit_type').dispatchEvent(new Event('change'));
        setTimeout(() => document.getElementById('edit_name')?.focus(), 140);
      });
    });

    // PRINT UNIQUE CODES
    document.querySelectorAll('.btn-print-codes').forEach(btn => {
      btn.addEventListener('click', () => {
        const form = document.getElementById('formPrintCodes');
        form.action = btn.dataset.action || '#';
        document.getElementById('printVoucherName').textContent = btn.dataset.name || '-';
        document.getElementById('printVoucherCode').textContent = btn.dataset.code || '-';
        document.getElementById('printValidFrom').textContent = btn.dataset.validFrom || '-';
        document.getElementById('printValidUntil').textContent = btn.dataset.validUntil || '-';
        const available = Number(btn.dataset.codeCount || 0);
        const usageLimit = Number(btn.dataset.usageLimit || 0);
        document.getElementById('printQuantity').max = Math.max(1, available);
        // Default to the voucher limit; never ask to print more codes than exist.
        document.getElementById('printQuantity').value = Math.max(1, Math.min(usageLimit || available, available));
      });
    });

    // DELETE
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        if (!confirm('Yakin hapus voucher ini?')) return;
        const f = document.getElementById('formDelete');
        f.action = btn.dataset.action || '#';
        f.submit();
      });
    });

    // Reopen modal saat validasi gagal
    const reopened = @json(old('_from'));
    if (reopened === 'create') {
      new bootstrap.Modal(document.getElementById('modalCreate')).show();
      setTimeout(() => document.getElementById('create_name')?.focus(), 140);
    } else if (reopened === 'edit') {
      const editModal = new bootstrap.Modal(document.getElementById('modalEdit'));
      const f = document.getElementById('formEdit');
      const actionOld = @json(old('edit_action'));
      if (actionOld) f.action = actionOld;
      editModal.show();
      setTimeout(() => document.getElementById('edit_name')?.focus(), 140);

      const oldContexts = @json(old('contexts', []));
      if (Array.isArray(oldContexts) && oldContexts.length) {
        setMultiSelectValues(document.getElementById('edit_contexts'), oldContexts);
      }
    }
  });
</script>
@endpush
