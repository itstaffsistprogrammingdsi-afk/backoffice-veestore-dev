@extends('layouts/contentNavbarLayout')

@section('title', 'Master Data - RFID Tags')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">Master Data /</span> RFID Tags
    </h4>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
        <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali form Anda.
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card">
        <h5 class="card-header d-flex align-items-center justify-content-between">
            <span>Daftar RFID Tags</span>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('master-data.rfid-tags.index') }}" class="d-flex gap-2">
                    <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm"
                        placeholder="Cari (serial/value)…">
                    <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip"
                        title="Cari">
                        <i class="mdi mdi-magnify"></i>
                    </button>
                </form>
                <!--  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#modalCreateTag">
                    <i class="mdi mdi-plus me-1"></i> Tambah Tag
                </button> -->
            </div>
        </h5>

        <div class="table-responsive text-nowrap">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Serial Number</th>
                        <th>Value (UID)</th>
                        <!-- <th class="text-center" style="width:120px;">Aksi</th> -->
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($tags as $i => $t)
                    <tr>
                        <td>{{ ($tags->currentPage()-1)*$tags->perPage() + $i + 1 }}</td>
                        <td class="fw-medium">
                            <i class="mdi mdi-rfid mdi-20px text-primary me-2"></i>{{ $t->serial_number }}
                        </td>
                        <td><span class="badge bg-label-primary">{{ $t->value }}</span></td>
                        <!--   <td class="text-center">
                            <div class="dropdown">
                                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                      <a href="javascript:void(0);" class="dropdown-item btn-edit-tag"
                                        data-id="{{ $t->id }}" data-serial="{{ $t->serial_number }}"
                                        data-value="{{ $t->value }}"
                                        data-update-url="{{ route('master-data.rfid-tags.update', $t->id) }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEditTag">
                                        <i class="mdi mdi-pencil-outline me-1"></i> Edit
                                    </a>
                                    <a href="#" class="dropdown-item text-danger btn-delete-tag"
                                        data-delete-url="{{ route('master-data.rfid-tags.destroy', $t->id) }}">
                                        <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                                    </a>
                                </div>
                            </div> -->
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">Belum ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-end">
            {{ $tags->appends(['q' => $q ?? null])->links() }}
        </div>
    </div>
</div>

{{-- MODAL: Create --}}
<div class="modal fade" id="modalCreateTag" tabindex="-1" aria-labelledby="modalCreateTagLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="formCreateTag" method="POST" action="{{ route('master-data.rfid-tags.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateTagLabel">
                        <i class="mdi mdi-rfid me-1"></i> Tambah RFID Tag
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Serial Number <span class="text-danger">*</span></label>
                        <input type="text" name="serial_number" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value (UID) <span class="text-danger">*</span></label>
                        <input type="text" name="value" class="form-control" placeholder="Contoh: 04A3B2C1…" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="text-muted small me-auto d-none d-md-inline">Tip: tekan <kbd>Alt</kbd> +
                        <kbd>Enter</kbd> untuk Simpan</span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button id="btnCreateSave" type="submit" class="btn btn-primary" data-bs-toggle="tooltip"
                        title="Shortcut: Alt + Enter">
                        <i class="mdi mdi-content-save-outline me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: Edit --}}
<div class="modal fade" id="modalEditTag" tabindex="-1" aria-labelledby="modalEditTagLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditTag" method="POST" action="#">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditTagLabel">
                        <i class="mdi mdi-pencil-outline me-1"></i> Edit RFID Tag
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Serial Number <span class="text-danger">*</span></label>
                        <input id="edit_serial_number" name="serial_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value (UID) <span class="text-danger">*</span></label>
                        <input id="edit_value" name="value" class="form-control" required>
                    </div>
                    <input type="hidden" id="edit_id" name="id">
                </div>
                <div class="modal-footer">
                    <span class="text-muted small me-auto d-none d-md-inline">Tip: tekan <kbd>Alt</kbd> +
                        <kbd>Enter</kbd> untuk Update</span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button id="btnEditUpdate" type="submit" class="btn btn-primary" data-bs-toggle="tooltip"
                        title="Shortcut: Alt + Enter">
                        <i class="mdi mdi-content-save-outline me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- FORM delete --}}
<form id="formDeleteTag" method="POST" class="d-none">@csrf @method('DELETE')</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi tooltip Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

    // ALT+ENTER submit (Create)
    const modalCreate = document.getElementById('modalCreateTag');
    if (modalCreate) {
        modalCreate.addEventListener('keydown', function(e) {
            if (e.altKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('formCreateTag').requestSubmit();
            }
        });
    }

    // ALT+ENTER submit (Edit)
    const modalEdit = document.getElementById('modalEditTag');
    if (modalEdit) {
        modalEdit.addEventListener('keydown', function(e) {
            if (e.altKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('formEditTag').requestSubmit();
            }
        });
    }

    // Isi modal edit
    document.querySelectorAll('.btn-edit-tag').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = document.getElementById('formEditTag');
            form.action = this.dataset.updateUrl || '#';
            document.getElementById('edit_id').value = this.dataset.id || '';
            document.getElementById('edit_serial_number').value = this.dataset.serial || '';
            document.getElementById('edit_value').value = this.dataset.value || '';
        });
    });

    // Hapus
    document.querySelectorAll('.btn-delete-tag').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('Yakin hapus tag ini?')) return;
            const form = document.getElementById('formDeleteTag');
            form.action = this.dataset.deleteUrl || '#';
            form.submit();
        });
    });
});
</script>
@endsection