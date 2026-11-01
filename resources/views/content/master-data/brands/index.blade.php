@extends('layouts/contentNavbarLayout')

@section('title', 'Master Product - Brands')

@push('vendor-style')
<style>
  .table-responsive { overflow-x: auto; }
  .col-idx { width: 60px; }
  .col-logo { width: 90px; text-align: center; }
  .col-aksi { width: 120px; text-align: center; }
  .brand-card-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
  .brand-card-actions { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
  .brand-search-form { display: flex; align-items: center; gap: .5rem; }
  .brand-feedback-slot { min-height: 22px; margin-top: .35rem; font-size: .8125rem; line-height: 1.25; }
  .brand-feedback-slot .invalid-feedback, .brand-feedback-slot .form-text { display: block; margin: 0; }
  .brand-modal .modal-body { padding: 1rem 1.25rem .75rem; }
  .brand-modal .modal-footer { padding: .75rem 1.25rem 1rem; border-top: 0; }
  .brand-logo-thumb { width: 52px; height: 52px; object-fit: cover; border-radius: 10px; border: 1px solid #e6e8eb; background: #f8f9fa; }
  .brand-logo-preview { width: 120px; height: 120px; object-fit: cover; border-radius: 12px; border: 1px solid #e6e8eb; background: #f8f9fa; display: none; }
  .brand-logo-preview.show { display: block; }
  @media (max-width: 575.98px) {
    .brand-card-header, .brand-card-actions, .brand-search-form { width: 100%; }
    .brand-search-form input { flex: 1; min-width: 0; }
    .brand-card-actions .btn-primary { width: 100%; }
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master Product /</span> Brands
  </h4>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      <div class="d-flex align-items-center">
        <i class="mdi mdi-check-circle-outline me-2"></i>
        <span>{{ session('success') }}</span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      <div class="d-flex align-items-center">
        <i class="mdi mdi-alert-circle-outline me-2"></i>
        <span>{{ session('error') }}</span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif

  @if ($errors->any() && !$errors->createBrand->any() && !$errors->editBrand->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
      <div class="d-flex">
        <i class="mdi mdi-alert-circle-outline me-2 mt-1"></i>
        <div>
          <div class="fw-semibold mb-1">Proses tidak dapat dilanjutkan.</div>
          <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $message)
              <li>{{ $message }}</li>
            @endforeach
          </ul>
        </div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif

  <div class="card">
    <div class="card-header brand-card-header">
      <h5 class="mb-0">Daftar Brands</h5>
      <div class="brand-card-actions">
        <form method="GET" action="{{ route('master-data.brands.index') }}" class="brand-search-form">
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="Cari brand..." autocomplete="off">
          <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Cari brand">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if (($q ?? '') !== '')
            <a href="{{ route('master-data.brands.index') }}" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Reset pencarian">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>
        @can('create master-data-brands')
          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateBrand">
            <i class="mdi mdi-plus me-1"></i> Tambah Brand
          </button>
        @endcan
      </div>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th class="col-idx">#</th>
            <th class="col-logo">Logo</th>
            <th>Brand Name</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($brands as $index => $brand)
            <tr>
              <td>{{ ($brands->currentPage() - 1) * $brands->perPage() + $index + 1 }}</td>
              <td class="text-center">
                @if ($brand->image_url)
                  <img src="{{ $brand->image_url }}" alt="{{ $brand->name }}" class="brand-logo-thumb">
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td class="fw-medium">
                <i class="mdi mdi-tag-outline mdi-20px text-primary me-2"></i>
                {{ $brand->name }}
              </td>
              <td class="text-center">
                <div class="dropdown">
                  <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="mdi mdi-dots-vertical"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end">
                    @can('update master-data-brands')
                      <button type="button" class="dropdown-item btn-edit-brand"
                        data-id="{{ $brand->id }}"
                        data-name="{{ $brand->name }}"
                        data-logo="{{ $brand->image_url }}"
                        data-update-url="{{ route('master-data.brands.update', $brand->id) }}"
                        data-bs-toggle="modal" data-bs-target="#modalEditBrand">
                        <i class="mdi mdi-pencil-outline me-1"></i> Edit
                      </button>
                    @endcan
                    @can('delete master-data-brands')
                      <button type="button" class="dropdown-item text-danger btn-delete-brand"
                        data-name="{{ $brand->name }}"
                        data-delete-url="{{ route('master-data.brands.destroy', $brand->id) }}">
                        <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                      </button>
                    @endcan
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                <i class="mdi mdi-folder-open-outline mdi-36px d-block mb-2"></i>
                @if (($q ?? '') !== '')
                  Brand dengan kata kunci <strong>{{ $q }}</strong> tidak ditemukan.
                @else
                  Belum ada data brand.
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($brands->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $brands->appends(['q' => $q ?? null])->links() }}
      </div>
    @endif
  </div>
</div>

@can('create master-data-brands')
  <div class="modal fade brand-modal" id="modalCreateBrand" tabindex="-1" aria-labelledby="modalCreateBrandLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">
        <form id="formCreateBrand" method="POST" action="{{ route('master-data.brands.store') }}" enctype="multipart/form-data" novalidate>
          @csrf
          <input type="hidden" name="_from" value="create">
          <div class="modal-header">
            <h5 class="modal-title" id="modalCreateBrandLabel"><i class="mdi mdi-plus me-1"></i> Tambah Brand</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <label for="create_name" class="form-label">Nama Brand <span class="text-danger">*</span></label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="mdi mdi-tag-outline"></i></span>
              <input type="text" name="name" id="create_name" value="{{ $errors->createBrand->any() ? old('name') : '' }}"
                class="form-control @if ($errors->createBrand->has('name')) is-invalid @endif"
                placeholder="Contoh: Nike, Adidas" maxlength="255" autocomplete="off" required>
            </div>
            <div class="brand-feedback-slot">
              @if ($errors->createBrand->has('name'))
                <div class="invalid-feedback">{{ $errors->createBrand->first('name') }}</div>
              @else
                <div class="form-text">Nama brand tidak boleh duplikat.</div>
              @endif
            </div>
            <label for="create_logo" class="form-label mt-2">Logo (opsional)</label>
            <input type="file" name="logo" id="create_logo" class="form-control @if ($errors->createBrand->has('logo')) is-invalid @endif" accept=".jpg,.jpeg,.png,.webp">
            <div class="brand-feedback-slot">
              @if ($errors->createBrand->has('logo'))
                <div class="invalid-feedback">{{ $errors->createBrand->first('logo') }}</div>
              @else
                <div class="form-text">Format JPG/PNG/WEBP, maksimal 2 MB.</div>
              @endif
            </div>
            <img id="create_logo_preview" class="brand-logo-preview mt-2" alt="Preview logo">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i> Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endcan

@can('update master-data-brands')
  <div class="modal fade brand-modal" id="modalEditBrand" tabindex="-1" aria-labelledby="modalEditBrandLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">
        <form id="formEditBrand" method="POST" action="#" enctype="multipart/form-data" novalidate>
          @csrf
          @method('PUT')
          <input type="hidden" name="_from" value="edit">
          <input type="hidden" id="edit_id" name="id" value="{{ old('id') }}">
          <input type="hidden" id="edit_update_url" name="update_url" value="{{ old('update_url') }}">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEditBrandLabel"><i class="mdi mdi-pencil-outline me-1"></i> Edit Brand</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <label for="edit_name" class="form-label">Nama Brand <span class="text-danger">*</span></label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="mdi mdi-tag-outline"></i></span>
              <input type="text" name="name" id="edit_name" value="{{ $errors->editBrand->any() ? old('name') : '' }}"
                class="form-control @if ($errors->editBrand->has('name')) is-invalid @endif"
                placeholder="Nama brand" maxlength="255" autocomplete="off" required>
            </div>
            <div class="brand-feedback-slot">
              @if ($errors->editBrand->has('name'))
                <div class="invalid-feedback">{{ $errors->editBrand->first('name') }}</div>
              @else
                <div class="form-text">Nama brand tidak boleh duplikat.</div>
              @endif
            </div>
            <label for="edit_logo" class="form-label mt-2">Logo (opsional, kosongkan bila tidak diubah)</label>
            <input type="file" name="logo" id="edit_logo" class="form-control @if ($errors->editBrand->has('logo')) is-invalid @endif" accept=".jpg,.jpeg,.png,.webp">
            <div class="brand-feedback-slot">
              @if ($errors->editBrand->has('logo'))
                <div class="invalid-feedback">{{ $errors->editBrand->first('logo') }}</div>
              @endif
            </div>
            <div class="mt-2">
              <div class="form-text mb-1">Logo saat ini:</div>
              <img id="edit_logo_current" class="brand-logo-thumb" alt="Logo saat ini">
            </div>
            <img id="edit_logo_preview" class="brand-logo-preview mt-2" alt="Preview logo baru">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i> Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endcan

@can('delete master-data-brands')
  <form id="formDeleteBrand" method="POST" class="d-none">@csrf @method('DELETE')</form>
@endcan

@endsection

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  'use strict';
  const modalCreateEl = document.getElementById('modalCreateBrand');
  const modalEditEl = document.getElementById('modalEditBrand');
  const formCreate = document.getElementById('formCreateBrand');
  const formEdit = document.getElementById('formEditBrand');
  const formDelete = document.getElementById('formDeleteBrand');

  function bindPreview(inputId, imgId) {
    const input = document.getElementById(inputId);
    const img = document.getElementById(imgId);
    if (!input || !img) return;
    input.addEventListener('change', function () {
      const file = this.files && this.files[0];
      if (!file) { img.classList.remove('show'); img.removeAttribute('src'); return; }
      img.src = URL.createObjectURL(file);
      img.classList.add('show');
    });
  }
  bindPreview('create_logo', 'create_logo_preview');
  bindPreview('edit_logo', 'edit_logo_preview');

  document.querySelectorAll('.btn-edit-brand').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!formEdit) return;
      formEdit.action = this.dataset.updateUrl || '#';
      document.getElementById('edit_id').value = this.dataset.id || '';
      document.getElementById('edit_name').value = this.dataset.name || '';
      document.getElementById('edit_update_url').value = this.dataset.updateUrl || '#';
      const current = document.getElementById('edit_logo_current');
      if (current) {
        if (this.dataset.logo) { current.src = this.dataset.logo; current.style.display = ''; }
        else { current.removeAttribute('src'); current.style.display = 'none'; }
      }
      const prev = document.getElementById('edit_logo_preview');
      if (prev) { prev.classList.remove('show'); prev.removeAttribute('src'); }
      const fileInput = document.getElementById('edit_logo');
      if (fileInput) fileInput.value = '';
    });
  });

  document.querySelectorAll('.btn-delete-brand').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!formDelete) return;
      if (!confirm(`Yakin ingin menghapus brand "${this.dataset.name || 'ini'}"?`)) return;
      formDelete.action = this.dataset.deleteUrl || '';
      formDelete.submit();
    });
  });

  if (@json($errors->createBrand->any()) && modalCreateEl && typeof bootstrap !== 'undefined') {
    bootstrap.Modal.getOrCreateInstance(modalCreateEl).show();
  }
  if (@json($errors->editBrand->any()) && modalEditEl && formEdit && typeof bootstrap !== 'undefined') {
    formEdit.action = @json(old('update_url')) || '#';
    document.getElementById('edit_id').value = @json(old('id')) || '';
    document.getElementById('edit_name').value = @json(old('name')) || '';
    document.getElementById('edit_update_url').value = @json(old('update_url')) || '';
    bootstrap.Modal.getOrCreateInstance(modalEditEl).show();
  }
});
</script>
@endpush
