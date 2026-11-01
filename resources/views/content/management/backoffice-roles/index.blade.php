@extends('layouts/contentNavbarLayout')

@section('title', 'Management - Roles (Backoffice)')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Management /</span> Roles (Backoffice)
  </h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      <i class="mdi mdi-check-circle-outline me-1"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
      <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali form Anda.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <div>
        <span>Daftar Roles</span>
      </div>

      <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2" action="{{ route('management.roles-backoffice.index') }}">
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm"
                 placeholder="Cari (name/guard_name)…">
          <button type="submit" class="btn btn-outline-secondary btn-sm" title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if(($q ?? '')!=='')
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('management.roles-backoffice.index') }}" title="Reset">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                data-bs-target="#modalCreateRole">
          <i class="mdi mdi-plus me-1"></i> Tambah Role
        </button>
      </div>
    </h5>

    <div class="table-responsive text-nowrap">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:60px;">#</th>
            <th>Nama</th>
            <th>Guard</th>
            <th class="d-none d-md-table-cell" style="width:200px;">Dibuat</th>
            <th class="text-center" style="width:140px;">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($roles as $i => $r)
            <tr>
              <td>{{ ($roles->currentPage()-1)*$roles->perPage() + $i + 1 }}</td>
              <td class="fw-medium">
                <i class="mdi mdi-shield-account-outline mdi-20px text-primary me-2"></i>{{ $r->name }}
                <div class="small text-muted d-md-none">Guard: {{ $r->guard_name }}</div>
              </td>
              <td class="d-none d-md-table-cell"><span class="badge bg-label-primary">{{ $r->guard_name }}</span></td>
              <td class="d-none d-md-table-cell">{{ optional($r->created_at)->format('Y-m-d H:i') }}</td>
              <td class="text-center">
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="mdi mdi-dots-vertical"></i>
                  </button>
                  <div class="dropdown-menu">
                    <a href="javascript:void(0);" class="dropdown-item btn-edit-role"
                       data-id="{{ $r->id }}"
                       data-name="{{ $r->name }}"
                       data-update-url="{{ route('management.roles-backoffice.update', $r->id) }}"
                       data-bs-toggle="modal" data-bs-target="#modalEditRole">
                      <i class="mdi mdi-pencil-outline me-1"></i> Edit
                    </a>
                    <a href="#" class="dropdown-item text-danger btn-delete-role"
                       data-delete-url="{{ route('management.roles-backoffice.destroy', $r->id) }}">
                      <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                    </a>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted">Belum ada data</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-end">
      {{ $roles->appends(['q' => $q ?? null])->links() }}
    </div>
  </div>
</div>

{{-- MODAL: Create --}}
<div class="modal fade" id="modalCreateRole" tabindex="-1" aria-labelledby="modalCreateRoleLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formCreateRole" method="POST" action="{{ route('management.roles-backoffice.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalCreateRoleLabel">
            <i class="mdi mdi-shield-plus-outline me-1"></i> Tambah Role
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Nama Role <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required placeholder="mis. admin">
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">
            Tip: tekan <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk <strong>Simpan</strong>
          </span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button id="btnCreateSubmit" type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL: Edit --}}
<div class="modal fade" id="modalEditRole" tabindex="-1" aria-labelledby="modalEditRoleLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEditRole" method="POST" action="#">
        @csrf @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditRoleLabel">
            <i class="mdi mdi-pencil-outline me-1"></i> Edit Role
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Nama Role <span class="text-danger">*</span></label>
          <input type="text" id="edit_name" name="name" class="form-control" required>
          <input type="hidden" id="edit_id" name="id">
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">
            Tip: tekan <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk <strong>Update</strong>
          </span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button id="btnEditSubmit" type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- FORM hapus --}}
<form id="formDeleteRole" method="POST" class="d-none">
  @csrf @method('DELETE')
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Isi data ke modal edit
  document.querySelectorAll('.btn-edit-role').forEach(btn => {
    btn.addEventListener('click', function(){
      const form = document.getElementById('formEditRole');
      form.action = this.dataset.updateUrl || '#';
      document.getElementById('edit_id').value = this.dataset.id || '';
      document.getElementById('edit_name').value = this.dataset.name || '';
    });
  });

  // Hapus
  document.querySelectorAll('.btn-delete-role').forEach(btn => {
    btn.addEventListener('click', function(e){
      e.preventDefault();
      if(!confirm('Yakin hapus role ini?')) return;
      const form = document.getElementById('formDeleteRole');
      form.action = this.dataset.deleteUrl || '#';
      form.submit();
    });
  });

  // ====== SHORTCUT Alt+Enter ======
  // Create
  const modalCreate = document.getElementById('modalCreateRole');
  modalCreate?.addEventListener('keydown', function(e){
    if (e.altKey && e.key === 'Enter') {
      e.preventDefault();
      document.getElementById('formCreateRole')?.requestSubmit();
    }
  });

  // Edit
  const modalEdit = document.getElementById('modalEditRole');
  modalEdit?.addEventListener('keydown', function(e){
    if (e.altKey && e.key === 'Enter') {
      e.preventDefault();
      document.getElementById('formEditRole')?.requestSubmit();
    }
  });
});
</script>
@endsection
