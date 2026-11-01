@extends('layouts/contentNavbarLayout')

@section('title', 'Master Product - Units')

@push('vendor-style')
<style>
  .table-responsive {
    overflow-x: auto;
  }

  .col-idx {
    width: 60px;
  }

  .col-aksi {
    width: 120px;
    text-align: center;
  }

  .unit-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .unit-card-actions {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
  }

  .unit-search-form {
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  .unit-feedback-slot {
    min-height: 22px;
    margin-top: .35rem;
    font-size: .8125rem;
    line-height: 1.25;
  }

  .unit-feedback-slot .invalid-feedback,
  .unit-feedback-slot .form-text {
    display: block;
    margin: 0;
  }

  .unit-modal .modal-body {
    padding: 1rem 1.25rem .75rem;
  }

  .unit-modal .modal-footer {
    padding: .75rem 1.25rem 1rem;
    border-top: 0;
  }

  @media (max-width: 575.98px) {
    .unit-card-header,
    .unit-card-actions,
    .unit-search-form {
      width: 100%;
    }

    .unit-search-form input {
      flex: 1;
      min-width: 0;
    }

    .unit-card-actions .btn-primary {
      width: 100%;
    }
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master Product /</span> Units
  </h4>

  {{-- Flash success --}}
  @if (session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      <div class="d-flex align-items-center">
        <i class="mdi mdi-check-circle-outline me-2"></i>
        <span>{{ session('success') }}</span>
      </div>

      <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
        aria-label="Tutup">
      </button>
    </div>
  @endif

  {{-- Flash error, termasuk unit masih digunakan --}}
  @if (session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      <div class="d-flex align-items-center">
        <i class="mdi mdi-alert-circle-outline me-2"></i>
        <span>{{ session('error') }}</span>
      </div>

      <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
        aria-label="Tutup">
      </button>
    </div>
  @endif

  {{-- Error umum yang bukan berasal dari modal create/edit --}}
  @if (
    $errors->any()
    && !$errors->createUnit->any()
    && !$errors->editUnit->any()
  )
    <div class="alert alert-danger alert-dismissible" role="alert">
      <div class="d-flex">
        <i class="mdi mdi-alert-circle-outline me-2 mt-1"></i>

        <div>
          <div class="fw-semibold mb-1">
            Proses tidak dapat dilanjutkan.
          </div>

          <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $message)
              <li>{{ $message }}</li>
            @endforeach
          </ul>
        </div>
      </div>

      <button
        type="button"
        class="btn-close"
        data-bs-dismiss="alert"
        aria-label="Tutup">
      </button>
    </div>
  @endif

  <div class="card">

    <div class="card-header unit-card-header">
      <h5 class="mb-0">Daftar Units</h5>

      <div class="unit-card-actions">

        <form
          method="GET"
          action="{{ route('master-data.units.index') }}"
          class="unit-search-form">

          <input
            type="text"
            name="q"
            value="{{ $q ?? '' }}"
            class="form-control form-control-sm"
            placeholder="Cari unit..."
            autocomplete="off">

          <button
            type="submit"
            class="btn btn-outline-secondary btn-sm"
            data-bs-toggle="tooltip"
            title="Cari unit">

            <i class="mdi mdi-magnify"></i>
          </button>

          @if (($q ?? '') !== '')
            <a
              href="{{ route('master-data.units.index') }}"
              class="btn btn-outline-secondary btn-sm"
              data-bs-toggle="tooltip"
              title="Reset pencarian">

              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-units')
          <button
            type="button"
            class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#modalCreateUnit">

            <i class="mdi mdi-plus me-1"></i>
            Tambah Unit
          </button>
        @endcan
      </div>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover mb-0">

        <thead class="table-light">
          <tr>
            <th class="col-idx">#</th>
            <th>Unit Name</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>

        <tbody class="table-border-bottom-0">
          @forelse ($units as $index => $unit)
            <tr>
              <td>
                {{
                  ($units->currentPage() - 1)
                  * $units->perPage()
                  + $index
                  + 1
                }}
              </td>

              <td class="fw-medium">
                <i class="mdi mdi-ruler-square mdi-20px text-primary me-2"></i>
                {{ $unit->name }}
              </td>

              <td class="text-center">
                <div class="dropdown">

                  <button
                    type="button"
                    class="btn p-0 dropdown-toggle hide-arrow"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">

                    <i class="mdi mdi-dots-vertical"></i>
                  </button>

                  <div class="dropdown-menu dropdown-menu-end">

                    @can('update master-data-units')
                      <button
                        type="button"
                        class="dropdown-item btn-edit-unit"
                        data-id="{{ $unit->id }}"
                        data-name="{{ $unit->name }}"
                        data-update-url="{{
                          route('master-data.units.update', $unit->id)
                        }}"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEditUnit">

                        <i class="mdi mdi-pencil-outline me-1"></i>
                        Edit
                      </button>
                    @endcan

                    @can('delete master-data-units')
                      <button
                        type="button"
                        class="dropdown-item text-danger btn-delete-unit"
                        data-name="{{ $unit->name }}"
                        data-delete-url="{{
                          route('master-data.units.destroy', $unit->id)
                        }}">

                        <i class="mdi mdi-trash-can-outline me-1"></i>
                        Hapus
                      </button>
                    @endcan

                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="text-center text-muted py-4">
                <i class="mdi mdi-folder-open-outline mdi-36px d-block mb-2"></i>

                @if (($q ?? '') !== '')
                  Unit dengan kata kunci
                  <strong>{{ $q }}</strong>
                  tidak ditemukan.
                @else
                  Belum ada data unit.
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>

      </table>
    </div>

    @if ($units->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{
          $units
            ->appends(['q' => $q ?? null])
            ->links()
        }}
      </div>
    @endif

  </div>
</div>

{{-- ========================================================= --}}
{{-- MODAL CREATE --}}
{{-- ========================================================= --}}

@can('create master-data-units')
  <div
    class="modal fade unit-modal"
    id="modalCreateUnit"
    tabindex="-1"
    aria-labelledby="modalCreateUnitLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">

        <form
          id="formCreateUnit"
          method="POST"
          action="{{ route('master-data.units.store') }}"
          novalidate>

          @csrf

          <input type="hidden" name="_from" value="create">

          <div class="modal-header">
            <h5 class="modal-title" id="modalCreateUnitLabel">
              <i class="mdi mdi-plus me-1"></i>
              Tambah Unit
            </h5>

            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Tutup">
            </button>
          </div>

          <div class="modal-body">

            <label for="create_name" class="form-label">
              Nama Unit <span class="text-danger">*</span>
            </label>

            <div class="input-group input-group-merge">
              <span class="input-group-text">
                <i class="mdi mdi-ruler-square"></i>
              </span>

              <input
                type="text"
                name="name"
                id="create_name"
                value="{{
                  $errors->createUnit->any()
                    ? old('name')
                    : ''
                }}"
                class="form-control
                  @if ($errors->createUnit->has('name'))
                    is-invalid
                  @endif"
                placeholder="Contoh: kg, pcs, box"
                maxlength="50"
                autocomplete="off"
                required>
            </div>

            <div class="unit-feedback-slot">
              @if ($errors->createUnit->has('name'))
                <div class="invalid-feedback">
                  {{ $errors->createUnit->first('name') }}
                </div>
              @else
                <div class="form-text">
                  Nama unit disimpan dalam huruf kecil dan tidak boleh duplikat.
                </div>
              @endif
            </div>

          </div>

          <div class="modal-footer">
            <span class="text-muted small me-auto d-none d-md-inline">
              Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan
            </span>

            <button
              type="button"
              class="btn btn-outline-secondary"
              data-bs-dismiss="modal">

              Batal
            </button>

            <button
              id="btnCreateSave"
              type="submit"
              class="btn btn-primary">

              <i class="mdi mdi-content-save-outline me-1"></i>
              Simpan
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
@endcan

{{-- ========================================================= --}}
{{-- MODAL EDIT --}}
{{-- ========================================================= --}}

@can('update master-data-units')
  <div
    class="modal fade unit-modal"
    id="modalEditUnit"
    tabindex="-1"
    aria-labelledby="modalEditUnitLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">

        <form
          id="formEditUnit"
          method="POST"
          action="#"
          novalidate>

          @csrf
          @method('PUT')

          <input type="hidden" name="_from" value="edit">

          <input
            type="hidden"
            id="edit_id"
            name="id"
            value="{{ old('id') }}">

          <input
            type="hidden"
            id="edit_update_url"
            name="update_url"
            value="{{ old('update_url') }}">

          <div class="modal-header">
            <h5 class="modal-title" id="modalEditUnitLabel">
              <i class="mdi mdi-pencil-outline me-1"></i>
              Edit Unit
            </h5>

            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Tutup">
            </button>
          </div>

          <div class="modal-body">

            <label for="edit_name" class="form-label">
              Nama Unit <span class="text-danger">*</span>
            </label>

            <div class="input-group input-group-merge">
              <span class="input-group-text">
                <i class="mdi mdi-ruler-square"></i>
              </span>

              <input
                type="text"
                name="name"
                id="edit_name"
                value="{{
                  $errors->editUnit->any()
                    ? old('name')
                    : ''
                }}"
                class="form-control
                  @if ($errors->editUnit->has('name'))
                    is-invalid
                  @endif"
                placeholder="Nama unit"
                maxlength="50"
                autocomplete="off"
                required>
            </div>

            <div class="unit-feedback-slot">
              @if ($errors->editUnit->has('name'))
                <div class="invalid-feedback">
                  {{ $errors->editUnit->first('name') }}
                </div>
              @else
                <div class="form-text">
                  Nama unit disimpan dalam huruf kecil dan tidak boleh duplikat.
                </div>
              @endif
            </div>

          </div>

          <div class="modal-footer">
            <span class="text-muted small me-auto d-none d-md-inline">
              Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update
            </span>

            <button
              type="button"
              class="btn btn-outline-secondary"
              data-bs-dismiss="modal">

              Batal
            </button>

            <button
              id="btnEditUpdate"
              type="submit"
              class="btn btn-primary">

              <i class="mdi mdi-content-save-outline me-1"></i>
              Update
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
@endcan

{{-- ========================================================= --}}
{{-- FORM DELETE --}}
{{-- ========================================================= --}}

@can('delete master-data-units')
  <form
    id="formDeleteUnit"
    method="POST"
    class="d-none">

    @csrf
    @method('DELETE')
  </form>
@endcan

@endsection

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  const modalCreateElement =
    document.getElementById('modalCreateUnit');

  const modalEditElement =
    document.getElementById('modalEditUnit');

  const formCreate =
    document.getElementById('formCreateUnit');

  const formEdit =
    document.getElementById('formEditUnit');

  const formDelete =
    document.getElementById('formDeleteUnit');

  const createNameInput =
    document.getElementById('create_name');

  const editNameInput =
    document.getElementById('edit_name');

  const editIdInput =
    document.getElementById('edit_id');

  const editUpdateUrlInput =
    document.getElementById('edit_update_url');

  /*
  |--------------------------------------------------------------------------
  | Tooltip
  |--------------------------------------------------------------------------
  */

  if (typeof bootstrap !== 'undefined') {
    document
      .querySelectorAll('[data-bs-toggle="tooltip"]')
      .forEach(function (element) {
        bootstrap.Tooltip.getOrCreateInstance(element);
      });
  }

  /*
  |--------------------------------------------------------------------------
  | Fokus input
  |--------------------------------------------------------------------------
  */

  modalCreateElement?.addEventListener(
    'shown.bs.modal',
    function () {
      createNameInput?.focus();
    }
  );

  modalEditElement?.addEventListener(
    'shown.bs.modal',
    function () {
      editNameInput?.focus();
      editNameInput?.select();
    }
  );

  /*
  |--------------------------------------------------------------------------
  | Prefill edit
  |--------------------------------------------------------------------------
  */

  document
    .querySelectorAll('.btn-edit-unit')
    .forEach(function (button) {
      button.addEventListener('click', function () {
        if (!formEdit) {
          return;
        }

        const id = this.dataset.id || '';
        const name = this.dataset.name || '';
        const updateUrl = this.dataset.updateUrl || '#';

        formEdit.action = updateUrl;

        if (editIdInput) {
          editIdInput.value = id;
        }

        if (editNameInput) {
          editNameInput.value = name;
          editNameInput.classList.remove('is-invalid');
        }

        if (editUpdateUrlInput) {
          editUpdateUrlInput.value = updateUrl;
        }
      });
    });

  /*
  |--------------------------------------------------------------------------
  | Normalisasi input
  |--------------------------------------------------------------------------
  */

  function normalizeUnitInput(input) {
    if (!input) {
      return;
    }

    input.value = input.value
      .trim()
      .replace(/\s+/g, ' ')
      .toLowerCase();
  }

  /*
  |--------------------------------------------------------------------------
  | Submit dan cegah double click
  |--------------------------------------------------------------------------
  */

  function bindFormSubmit(
    form,
    input,
    processingText
  ) {
    if (!form) {
      return;
    }

    form.addEventListener('submit', function (event) {
      normalizeUnitInput(input);

      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
        form.classList.add('was-validated');
        return;
      }

      const submitButton = form.querySelector(
        'button[type="submit"]'
      );

      if (!submitButton) {
        return;
      }

      if (submitButton.disabled) {
        event.preventDefault();
        return;
      }

      submitButton.disabled = true;

      submitButton.innerHTML = `
        <span
          class="spinner-border spinner-border-sm me-1"
          role="status"
          aria-hidden="true">
        </span>
        ${processingText}
      `;
    });
  }

  bindFormSubmit(
    formCreate,
    createNameInput,
    'Menyimpan...'
  );

  bindFormSubmit(
    formEdit,
    editNameInput,
    'Memperbarui...'
  );

  /*
  |--------------------------------------------------------------------------
  | ALT + ENTER
  |--------------------------------------------------------------------------
  */

  modalCreateElement?.addEventListener(
    'keydown',
    function (event) {
      if (event.altKey && event.key === 'Enter') {
        event.preventDefault();
        formCreate?.requestSubmit();
      }
    }
  );

  modalEditElement?.addEventListener(
    'keydown',
    function (event) {
      if (event.altKey && event.key === 'Enter') {
        event.preventDefault();
        formEdit?.requestSubmit();
      }
    }
  );

  /*
  |--------------------------------------------------------------------------
  | Hapus unit
  |--------------------------------------------------------------------------
  */

  document
    .querySelectorAll('.btn-delete-unit')
    .forEach(function (button) {
      button.addEventListener('click', function () {
        if (!formDelete) {
          alert('Form hapus tidak ditemukan.');
          return;
        }

        const unitName =
          this.dataset.name || 'unit ini';

        const deleteUrl =
          this.dataset.deleteUrl || '';

        if (!deleteUrl) {
          alert('URL hapus tidak ditemukan.');
          return;
        }

        const confirmed = confirm(
          `Yakin ingin menghapus unit "${unitName}"?`
        );

        if (!confirmed) {
          return;
        }

        formDelete.action = deleteUrl;
        formDelete.submit();
      });
    });

  /*
  |--------------------------------------------------------------------------
  | Reset modal create ketika ditutup
  |--------------------------------------------------------------------------
  */

  modalCreateElement?.addEventListener(
    'hidden.bs.modal',
    function () {
      if (!formCreate) {
        return;
      }

      formCreate.reset();
      formCreate.classList.remove('was-validated');

      /*
       * form.reset() mengembalikan input ke value awal dari Blade.
       * Setelah validasi gagal, value awal tersebut berisi old('name').
       * Karena itu value dan defaultValue harus dikosongkan manual.
       */
      if (createNameInput) {
        createNameInput.value = '';
        createNameInput.defaultValue = '';
        createNameInput.classList.remove('is-invalid');
      }

      const feedbackSlot =
        formCreate.querySelector('.unit-feedback-slot');

      if (feedbackSlot) {
        feedbackSlot.innerHTML = `
          <div class="form-text">
            Nama unit disimpan dalam huruf kecil dan tidak boleh duplikat.
          </div>
        `;
      }

      const submitButton =
        formCreate.querySelector('button[type="submit"]');

      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = `
          <i class="mdi mdi-content-save-outline me-1"></i>
          Simpan
        `;
      }
    }
  );

  /*
  |--------------------------------------------------------------------------
  | Buka ulang modal create saat validasi gagal
  |--------------------------------------------------------------------------
  */

  const hasCreateError =
    @json($errors->createUnit->any());

  if (
    hasCreateError &&
    modalCreateElement &&
    typeof bootstrap !== 'undefined'
  ) {
    bootstrap.Modal
      .getOrCreateInstance(modalCreateElement)
      .show();
  }

  /*
  |--------------------------------------------------------------------------
  | Buka ulang modal edit saat validasi gagal
  |--------------------------------------------------------------------------
  */

  const hasEditError =
    @json($errors->editUnit->any());

  if (
    hasEditError &&
    modalEditElement &&
    formEdit &&
    typeof bootstrap !== 'undefined'
  ) {
    const oldId = @json(old('id'));
    const oldName = @json(old('name'));
    const oldUpdateUrl = @json(old('update_url'));

    if (oldUpdateUrl) {
      formEdit.action = oldUpdateUrl;
    }

    if (editIdInput) {
      editIdInput.value = oldId || '';
    }

    if (editNameInput) {
      editNameInput.value = oldName || '';
    }

    if (editUpdateUrlInput) {
      editUpdateUrlInput.value = oldUpdateUrl || '';
    }

    bootstrap.Modal
      .getOrCreateInstance(modalEditElement)
      .show();
  }
});
</script>
@endpush
