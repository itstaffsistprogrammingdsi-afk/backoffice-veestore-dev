@extends('layouts/contentNavbarLayout')

@section('title','Master E-commerce - Payment Methods')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master E-commerce /</span> Payment Methods
  </h4>

  {{-- Flash --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if($errors->any() && old('_from')===null)
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali form Anda.
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Payment Methods</span>

      <div class="d-flex gap-2">
        <form method="GET" action="{{ route('master-data.payment-methods.index') }}" class="d-flex gap-2">
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="Cari nama...">
          <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if(($q ?? '')!=='')
            <a href="{{ route('master-data.payment-methods.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-payment-methods')
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
            <th style="width:70px">#</th>
            <th>Nama</th>
            <th class="text-center" style="width:120px">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($methods as $i => $m)
          <tr>
            <td>{{ ($methods->currentPage()-1)*$methods->perPage() + $i + 1 }}</td>
            <td class="fw-medium">
              <i class="mdi mdi-credit-card-outline mdi-20px text-primary me-2"></i>{{ $m->name }}
            </td>
            <td class="text-center">
              <div class="dropdown">
                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  @can('update master-data-payment-methods')
                  <a href="javascript:void(0);" class="dropdown-item btn-edit"
                     data-update-url="{{ route('master-data.payment-methods.update', $m->id) }}"
                     data-id="{{ $m->id }}" data-name="{{ e($m->name) }}"
                     data-bs-toggle="modal" data-bs-target="#modalEdit">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit
                  </a>
                  @endcan

                  @can('delete master-data-payment-methods')
                  <a href="#" class="dropdown-item text-danger btn-delete"
                     data-delete-url="{{ route('master-data.payment-methods.destroy', $m->id) }}">
                    <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                  </a>
                  @endcan
                </div>
              </div>
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

    @if($methods->hasPages())
    <div class="card-footer d-flex justify-content-end">
      {{ $methods->appends(['q' => $q ?? null])->links() }}
    </div>
    @endif
  </div>
</div>

{{-- ========== MODAL: CREATE ========== --}}
@can('create master-data-payment-methods')
<div class="modal fade" id="modalCreate" tabindex="-1" aria-labelledby="modalCreateLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="formCreate" method="POST" action="{{ route('master-data.payment-methods.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCreateLabel">
            <i class="mdi mdi-plus-circle-outline me-1"></i> Tambah Payment Method
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && old('_from')==='create')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal menyimpan. Periksa kembali:
              <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label">Nama <span class="text-danger">*</span></label>
            <input type="text" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('_from')==='create' ? old('name') : '' }}" required autofocus>
            @error('name') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button id="btnCreateSave" class="btn btn-primary" type="submit" data-bs-toggle="tooltip" title="Shortcut: Alt + Enter">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- ========== MODAL: EDIT ========== --}}
@can('update master-data-payment-methods')
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="formEdit" method="POST" action="#">
        @csrf @method('PUT')
        <input type="hidden" name="_from" value="edit">
        <input type="hidden" id="edit_id" name="id" value="{{ old('_from')==='edit' ? old('id') : '' }}">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditLabel">
            <i class="mdi mdi-pencil-outline me-1"></i> Edit Payment Method
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && old('_from')==='edit')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal update. Periksa kembali:
              <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label">Nama <span class="text-danger">*</span></label>
            <input id="edit_name" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('_from')==='edit' ? old('name') : '' }}" required>
            @error('name') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update</span>
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button id="btnEditUpdate" class="btn btn-primary" type="submit" data-bs-toggle="tooltip" title="Shortcut: Alt + Enter">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- FORM Delete --}}
@can('delete master-data-payment-methods')
<form id="formDelete" method="POST" class="d-none">@csrf @method('DELETE')</form>
@endcan
</div>

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

  // ALT+ENTER (Create)
  document.getElementById('modalCreate')?.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'Enter') { e.preventDefault(); document.getElementById('formCreate')?.requestSubmit(); }
  });

  // ALT+ENTER (Edit)
  document.getElementById('modalEdit')?.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'Enter') { e.preventDefault(); document.getElementById('formEdit')?.requestSubmit(); }
  });

  // Prefill Edit
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
      const f = document.getElementById('formEdit');
      f.action = this.dataset.updateUrl || '#';
      document.getElementById('edit_id').value   = this.dataset.id   || '';
      document.getElementById('edit_name').value = this.dataset.name || '';
    });
  });

  // Delete
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      if (!confirm('Yakin hapus payment method ini?')) return;
      const form = document.getElementById('formDelete');
      form.action = this.dataset.deleteUrl || '#';
      form.submit();
    });
  });

  // Reopen modal jika validasi gagal
  const reopened = @json(old('_from'));
  if (reopened === 'create') new bootstrap.Modal(document.getElementById('modalCreate')).show();
  else if (reopened === 'edit') new bootstrap.Modal(document.getElementById('modalEdit')).show();
});
</script>
@endpush
@endsection
