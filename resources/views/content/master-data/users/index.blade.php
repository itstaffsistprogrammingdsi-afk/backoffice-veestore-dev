@extends('layouts/contentNavbarLayout')

@section('title','User Management - Users')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4"><span class="text-muted fw-light">User Management /</span> Users</h4>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible">
        <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali form Anda.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card">
        <h5 class="card-header d-flex align-items-center justify-content-between">
            <span>Daftar Users</span>
            <form method="GET" action="{{ route('master-data.users.index') }}" class="d-flex gap-2">
                <input class="form-control form-control-sm" type="text" name="q" value="{{ $q ?? '' }}"
                    placeholder="Cari nama/username/email...">
                <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Cari">
                    <i class="mdi mdi-magnify"></i>
                </button>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#modalCreate">
                    <i class="mdi mdi-plus me-1"></i> Tambah
                </button>
            </form>
        </h5>

        <div class="table-responsive text-nowrap">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px">#</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th style="min-width:260px">Access System</th>
                        <th style="width:160px">Dibuat</th>
                        <th class="text-center" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($users as $i => $u)
                    <tr>
                        <td>{{ ($users->currentPage()-1)*$users->perPage() + $i + 1 }}</td>
                        <td class="fw-medium">
                            <i class="mdi mdi-account-outline mdi-20px text-primary me-2"></i>{{ $u->name }}
                        </td>
                        <td>{{ $u->username }}</td>
                        <td>{{ $u->email }}</td>
                        <td>
                            @php $hasAny = $u->wms_access || $u->backoffice_access || $u->store_access; @endphp
                            @if ($hasAny)
                            <div class="d-flex flex-wrap gap-1">
                                @if($u->wms_access)
                                <span class="badge bg-info">
                                    <i class="mdi mdi-warehouse me-1"></i>WMS
                                    @if($u->warehouses->count())
                                    <span class="ms-1">({{ $u->warehouses->pluck('name')->join(', ') }})</span>
                                    @endif
                                </span>
                                @endif
                                @if($u->backoffice_access)
                                <span class="badge bg-success">
                                    <i class="mdi mdi-office-building-outline me-1"></i>Backoffice
                                </span>
                                @endif
                                @if($u->store_access)
                                <span class="badge bg-warning text-dark">
                                    <i class="mdi mdi-storefront-outline me-1"></i>Store
                                    @if($u->stores->count())
                                    <span class="ms-1">({{ $u->stores->pluck('name')->join(', ') }})</span>
                                    @endif
                                </span>
                                @endif
                            </div>
                            @else
                            <span class="badge bg-secondary">No Access</span>
                            @endif
                        </td>
                        <td>{{ optional($u->created_at)->format('Y-m-d H:i') }}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="javascript:void(0);" class="dropdown-item btn-edit"
                                        data-update-url="{{ route('master-data.users.update', $u->id) }}"
                                        data-id="{{ $u->id }}" data-name="{{ $u->name }}"
                                        data-username="{{ $u->username }}" data-email="{{ $u->email }}"
                                        data-wms="{{ $u->wms_access ? 1 : 0 }}"
                                        data-backoffice="{{ $u->backoffice_access ? 1 : 0 }}"
                                        data-store="{{ $u->store_access ? 1 : 0 }}"
                                        data-warehouses="{{ $u->warehouses->pluck('id')->implode(',') }}"
                                        data-stores="{{ $u->stores->pluck('id')->implode(',') }}" data-bs-toggle="modal"
                                        data-bs-target="#modalEdit">
                                        <i class="mdi mdi-pencil-outline me-1"></i> Edit
                                    </a>
                                    <a href="#" class="dropdown-item text-danger btn-delete"
                                        data-delete-url="{{ route('master-data.users.destroy', $u->id) }}">
                                        <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-end">
            {{ $users->links() }}
        </div>
    </div>
</div>

{{-- =============== MODAL: Create =============== --}}
<div class="modal fade" id="modalCreate" tabindex="-1" aria-labelledby="modalCreateLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formCreateUser" method="POST" action="{{ route('master-data.users.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateLabel">
                        <i class="mdi mdi-account-plus-outline me-1"></i> Tambah User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" minlength="6" required>
                        </div>

                        {{-- Access --}}
                        <div class="col-12">
                            <label class="form-label d-block">Access</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input type="hidden" name="wms_access" value="0">
                                    <input class="form-check-input" type="checkbox" id="create_wms_access"
                                        name="wms_access" value="1">
                                    <label class="form-check-label" for="create_wms_access">WMS</label>
                                </div>
                                <div class="form-check">
                                    <input type="hidden" name="backoffice_access" value="0">
                                    <input class="form-check-input" type="checkbox" id="create_backoffice_access"
                                        name="backoffice_access" value="1">
                                    <label class="form-check-label" for="create_backoffice_access">Backoffice</label>
                                </div>
                                <div class="form-check">
                                    <input type="hidden" name="store_access" value="0">
                                    <input class="form-check-input" type="checkbox" id="create_store_access"
                                        name="store_access" value="1">
                                    <label class="form-check-label" for="create_store_access">Store</label>
                                </div>
                            </div>
                        </div>

                        {{-- Warehouses (WMS) --}}
                        <div class="col-12" id="wrap_create_warehouses" style="display:none;">
                            <label class="form-label">Warehouses (WMS)</label>

                            <!--    <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="create_wh_all">
                                <label class="form-check-label" for="create_wh_all">Pilih semua gudang</label>
                            </div> -->

                            <div class="row gy-2" id="create_wh_list">
                                @foreach($warehouses as $wh)
                                <div class="col-sm-6 col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input wh-ck-create" type="radio"
                                            id="create_wh_{{ $wh->id }}" name="warehouse_ids[]" value="{{ $wh->id }}">
                                        <label class="form-check-label"
                                            for="create_wh_{{ $wh->id }}">{{ $wh->name }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="form-text">Centang gudang yang dapat diakses user.</div>
                        </div>

                        {{-- Stores (Store) --}}
                        <div class="col-12" id="wrap_create_stores" style="display:none;">
                            <label class="form-label">Stores (Store)</label>

                            <!--  <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="create_store_all">
                                <label class="form-check-label" for="create_store_all">Pilih semua toko</label>
                            </div> -->

                            <div class="row gy-2" id="create_store_list">
                                @foreach($stores as $st)
                                <div class="col-sm-6 col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input store-ck-create" type="radio"
                                            id="create_store_{{ $st->id }}" name="store_ids[]" value="{{ $st->id }}">
                                        <label class="form-check-label"
                                            for="create_store_{{ $st->id }}">{{ $st->name }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="form-text">Centang toko yang dapat diakses user.</div>
                        </div>
                        {{-- /Stores --}}
                    </div>
                </div>

                <div class="modal-footer">
                    <span class="text-muted small me-auto d-none d-md-inline">
                        Tip: tekan <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan
                    </span>
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                    <button id="btnCreateSave" class="btn btn-primary" type="submit" data-bs-toggle="tooltip"
                        title="Shortcut: Alt + Enter">
                        <i class="mdi mdi-content-save-outline me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- =============== MODAL: Edit =============== --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditUser" method="POST" action="#">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditLabel">
                        <i class="mdi mdi-pencil-outline me-1"></i> Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama *</label>
                            <input id="edit_name" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username *</label>
                            <input id="edit_username" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input id="edit_email" name="email" class="form-control" type="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password (kosongkan jika tidak diubah)</label>
                            <input id="edit_password" name="password" class="form-control" type="password"
                                minlength="6">
                        </div>

                        {{-- Access --}}
                        <div class="col-12">
                            <label class="form-label d-block">Access</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input type="hidden" name="wms_access" value="0">
                                    <input class="form-check-input" type="checkbox" id="edit_wms_access"
                                        name="wms_access" value="1">
                                    <label class="form-check-label" for="edit_wms_access">WMS</label>
                                </div>
                                <div class="form-check">
                                    <input type="hidden" name="backoffice_access" value="0">
                                    <input class="form-check-input" type="checkbox" id="edit_backoffice_access"
                                        name="backoffice_access" value="1">
                                    <label class="form-check-label" for="edit_backoffice_access">Backoffice</label>
                                </div>
                                <div class="form-check">
                                    <input type="hidden" name="store_access" value="0">
                                    <input class="form-check-input" type="checkbox" id="edit_store_access"
                                        name="store_access" value="1">
                                    <label class="form-check-label" for="edit_store_access">Store</label>
                                </div>
                            </div>
                        </div>

                        {{-- Warehouses (WMS) --}}
                        <div class="col-12" id="wrap_edit_warehouses" style="display:none;">
                            <label class="form-label">Warehouses (WMS)</label>

                            <!--  <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="edit_wh_all">
                                <label class="form-check-label" for="edit_wh_all">Pilih semua gudang</label>
                            </div> -->

                            <div class="row gy-2" id="edit_wh_list">
                                @foreach($warehouses as $wh)
                                <div class="col-sm-6 col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input wh-ck-edit" type="radio"
                                            id="edit_wh_{{ $wh->id }}" name="warehouse_ids[]" value="{{ $wh->id }}">
                                        <label class="form-check-label"
                                            for="edit_wh_{{ $wh->id }}">{{ $wh->name }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Stores (Store) --}}
                        <div class="col-12" id="wrap_edit_stores" style="display:none;">
                            <label class="form-label">Stores (Store)</label>

                            <!--  <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="edit_store_all">
                                <label class="form-check-label" for="edit_store_all">Pilih semua toko</label>
                            </div> -->

                            <div class="row gy-2" id="edit_store_list">
                                @foreach($stores as $st)
                                <div class="col-sm-6 col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input store-ck-edit" type="radio"
                                            id="edit_store_{{ $st->id }}" name="store_ids[]" value="{{ $st->id }}">
                                        <label class="form-check-label"
                                            for="edit_store_{{ $st->id }}">{{ $st->name }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        {{-- /Stores --}}

                        <input type="hidden" id="edit_id" name="id">
                    </div>
                </div>

                <div class="modal-footer">
                    <span class="text-muted small me-auto d-none d-md-inline">
                        Tip: tekan <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update
                    </span>
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                    <button id="btnEditUpdate" class="btn btn-primary" type="submit" data-bs-toggle="tooltip"
                        title="Shortcut: Alt + Enter">
                        <i class="mdi mdi-content-save-outline me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="formDelete" method="POST" class="d-none">@csrf @method('DELETE')</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

    // ----- CREATE: toggle gudang
    const ckCreateWms = document.getElementById('create_wms_access');
    const wrapCreateWh = document.getElementById('wrap_create_warehouses');
    const createWhAll = document.getElementById('create_wh_all');

    function toggleCreateWh() {
        wrapCreateWh.style.display = ckCreateWms.checked ? 'block' : 'none';
        if (!ckCreateWms.checked) {
            document.querySelectorAll('.wh-ck-create').forEach(c => c.checked = false);
            if (createWhAll) createWhAll.checked = false;
        }
    }
    if (ckCreateWms) ckCreateWms.addEventListener('change', toggleCreateWh);
    toggleCreateWh();
    if (createWhAll) createWhAll.addEventListener('change', function() {
        document.querySelectorAll('.wh-ck-create').forEach(c => c.checked = this.checked);
    });

    // ----- CREATE: toggle stores
    const ckCreateStore = document.getElementById('create_store_access');
    const wrapCreateSt = document.getElementById('wrap_create_stores');
    const createStoreAll = document.getElementById('create_store_all');

    function toggleCreateSt() {
        wrapCreateSt.style.display = ckCreateStore.checked ? 'block' : 'none';
        if (!ckCreateStore.checked) {
            document.querySelectorAll('.store-ck-create').forEach(c => c.checked = false);
            if (createStoreAll) createStoreAll.checked = false;
        }
    }
    if (ckCreateStore) ckCreateStore.addEventListener('change', toggleCreateSt);
    toggleCreateSt();
    if (createStoreAll) createStoreAll.addEventListener('change', function() {
        document.querySelectorAll('.store-ck-create').forEach(c => c.checked = this.checked);
    });

    // ----- EDIT: prefill + toggle + pilih semua
    const ckEditWms = document.getElementById('edit_wms_access');
    const wrapEditWh = document.getElementById('wrap_edit_warehouses');
    const editWhAll = document.getElementById('edit_wh_all');

    const ckEditStore = document.getElementById('edit_store_access');
    const wrapEditSt = document.getElementById('wrap_edit_stores');
    const editStoreAll = document.getElementById('edit_store_all');

    // Prefill Edit modal
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const f = document.getElementById('formEditUser');
            f.action = this.dataset.updateUrl || '#';

            // field dasar
            document.getElementById('edit_id').value = this.dataset.id || '';
            document.getElementById('edit_name').value = this.dataset.name || '';
            document.getElementById('edit_username').value = this.dataset.username || '';
            document.getElementById('edit_email').value = this.dataset.email || '';
            document.getElementById('edit_password').value = '';

            // access
            ckEditWms.checked = (this.dataset.wms === '1');
            document.getElementById('edit_backoffice_access').checked = (this.dataset
                .backoffice === '1');
            ckEditStore.checked = (this.dataset.store === '1');

            // WMS list show/hide + checked
            wrapEditWh.style.display = ckEditWms.checked ? 'block' : 'none';
            const selWh = (this.dataset.warehouses || '').split(',').filter(Boolean);
            const whCk = document.querySelectorAll('.wh-ck-edit');
            whCk.forEach(c => c.checked = selWh.includes(c.value));
            editWhAll.checked = selWh.length > 0 && selWh.length === whCk.length;

            // STORE list show/hide + checked
            wrapEditSt.style.display = ckEditStore.checked ? 'block' : 'none';
            const selSt = (this.dataset.stores || '').split(',').filter(Boolean);
            const stCk = document.querySelectorAll('.store-ck-edit');
            stCk.forEach(c => c.checked = selSt.includes(c.value));
            editStoreAll.checked = selSt.length > 0 && selSt.length === stCk.length;
        });
    });

    // Toggle saat user klik checkbox ACCESS di modal edit
    if (ckEditWms) ckEditWms.addEventListener('change', function() {
        wrapEditWh.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
            document.querySelectorAll('.wh-ck-edit').forEach(c => c.checked = false);
            if (editWhAll) editWhAll.checked = false;
        }
    });
    if (ckEditStore) ckEditStore.addEventListener('change', function() {
        wrapEditSt.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
            document.querySelectorAll('.store-ck-edit').forEach(c => c.checked = false);
            if (editStoreAll) editStoreAll.checked = false;
        }
    });

    // Pilih semua (edit)
    if (editWhAll) editWhAll.addEventListener('change', function() {
        document.querySelectorAll('.wh-ck-edit').forEach(c => c.checked = this.checked);
    });
    if (editStoreAll) editStoreAll.addEventListener('change', function() {
        document.querySelectorAll('.store-ck-edit').forEach(c => c.checked = this.checked);
    });

    // Delete
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('Yakin hapus user ini?')) return;
            const form = document.getElementById('formDelete');
            form.action = this.dataset.deleteUrl || '#';
            form.submit();
        });
    });

    // Shortcut Alt+Enter
    const modalCreate = document.getElementById('modalCreate');
    if (modalCreate) {
        modalCreate.addEventListener('keydown', e => {
            if (e.altKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('formCreateUser').requestSubmit();
            }
        });
    }
    const modalEdit = document.getElementById('modalEdit');
    if (modalEdit) {
        modalEdit.addEventListener('keydown', e => {
            if (e.altKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('formEditUser').requestSubmit();
            }
        });
    }
});
</script>
@endsection