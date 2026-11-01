@extends('layouts/contentNavbarLayout')

@section('title','Master Product - Product Specifications')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">Master Product /</span> Product Specifications</h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if($errors->any() && old('_from') === null)
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali input Anda.
      <button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Product Specifications</span>

      <div class="d-flex gap-2">
        <form method="GET" action="{{ route('master-data.product-specifications.index') }}" class="d-flex gap-2">
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="Cari value...">
          <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if(($q ?? '')!=='')
            <a href="{{ route('master-data.product-specifications.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-product-specifications')
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
          <i class="mdi mdi-plus me-1"></i> Tambah
        </button>
        @endcan
      </div>
    </h5>

    <div class="table-responsive text-nowrap">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:60px">#</th>
            <th>Value</th>
            <th style="width:120px">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($specs as $i => $s)
          @php
            $canUpdate = auth()->user()?->can('update master-data-product-specifications');
            $canDelete = auth()->user()?->can('delete master-data-product-specifications');
          @endphp
          <tr>
            <td>{{ ($specs->currentPage()-1)*$specs->perPage() + $i + 1 }}</td>
            <td>{{ $s->value }}</td>
            <td class="text-center">
              @if(!$canUpdate && !$canDelete)
                <span class="text-muted">-</span>
              @else
              <div class="dropdown">
                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  @can('update master-data-product-specifications')
                  <a href="javascript:void(0);" class="dropdown-item btn-edit"
                     data-update-url="{{ route('master-data.product-specifications.update', $s->id) }}"
                     data-id="{{ $s->id }}"
                     data-value="{{ $s->value }}"
                     data-bs-toggle="modal" data-bs-target="#modalEdit">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit
                  </a>
                  @endcan

                  @can('delete master-data-product-specifications')
                  <a href="#" class="dropdown-item text-danger btn-delete"
                     data-delete-url="{{ route('master-data.product-specifications.destroy', $s->id) }}">
                    <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                  </a>
                  @endcan
                </div>
              </div>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="3" class="text-center text-muted">Belum ada data</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-end">
      {{ $specs->appends(['q' => $q ?? null])->links() }}
    </div>
  </div>
</div>

{{-- ========== MODAL: CREATE ========== --}}
@can('create master-data-product-specifications')
<div class="modal fade" id="modalCreate" tabindex="-1" aria-labelledby="modalCreateLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('master-data.product-specifications.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">

        <div class="modal-header">
          <h5 class="modal-title" id="modalCreateLabel">Tambah Product Specification</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          @if($errors->any() && old('_from')==='create')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal menyimpan. Periksa kembali input Anda:
              <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label">Value <span class="text-danger">*</span></label>
            <input type="text" name="value" class="form-control"
                   value="{{ old('_from')==='create' ? old('value') : '' }}" required>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- ========== MODAL: EDIT ========== --}}
@can('update master-data-product-specifications')
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEdit" method="POST"
            action="{{ old('_from')==='edit' && old('id') ? route('master-data.product-specifications.update', old('id')) : '#' }}">
        @csrf
        @method('PUT')
        <input type="hidden" id="edit_id" name="id" value="{{ old('_from')==='edit' ? old('id') : '' }}">
        <input type="hidden" name="_from" value="edit">

        <div class="modal-header">
          <h5 class="modal-title" id="modalEditLabel">Edit Product Specification</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          @if($errors->any() && old('_from')==='edit')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal menyimpan perubahan. Periksa kembali input Anda:
              <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label">Value <span class="text-danger">*</span></label>
            <input type="text" id="edit_value" name="value" class="form-control"
                   value="{{ old('_from')==='edit' ? old('value') : '' }}" required>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- FORM HAPUS --}}
@can('delete master-data-product-specifications')
<form id="formDelete" method="POST" class="d-none">@csrf @method('DELETE')</form>
@endcan
@endsection

@push('page-script')
<script>
(function() {
  'use strict';

  // Tooltip
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

  // Prefill Edit
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
      const f = document.getElementById('formEdit');
      f.action = this.dataset.updateUrl || '#';

      document.getElementById('edit_id').value    = this.dataset.id || '';
      document.getElementById('edit_value').value = this.dataset.value || '';
    });
  });

  // Hapus
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      if (!confirm('Yakin hapus data ini?')) return;
      const form = document.getElementById('formDelete');
      form.action = this.dataset.deleteUrl || '#';
      form.submit();
    });
  });

  // Reopen modal saat gagal validasi
  const reopen = @json(old('_from'));
  if (reopen === 'create') {
    new bootstrap.Modal(document.getElementById('modalCreate')).show();
  } else if (reopen === 'edit') {
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
  }
})();
</script>
@endpush
