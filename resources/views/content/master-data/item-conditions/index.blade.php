@extends('layouts/contentNavbarLayout')

@section('title', 'Master Product - Item Conditions')

@push('vendor-style')
<style>
  .table-responsive {
    overflow-x: auto;
  }

  .condition-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .condition-card-actions,
  .condition-search-form {
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  .condition-feedback-slot {
    min-height: 22px;
    margin-top: .35rem;
    font-size: .8125rem;
    line-height: 1.25;
  }

  .condition-feedback-slot .invalid-feedback,
  .condition-feedback-slot .form-text {
    display: block;
    margin: 0;
  }

  .condition-modal .modal-body {
    padding: 1rem 1.25rem .75rem;
  }

  .condition-modal .modal-footer {
    padding: .75rem 1.25rem 1rem;
    border-top: 0;
  }

  .default-condition-badge {
    vertical-align: middle;
  }

  .status-radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    padding: .25rem 0;
  }

  .status-radio-option {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
  }

  .status-radio-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .status-radio-option .status-radio-label {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    min-height: 42px;
    padding: .55rem .9rem;
    border: 1px solid #d9dee3;
    border-radius: .5rem;
    background: #fff;
    color: #566a7f;
    font-weight: 500;
    transition: all .15s ease-in-out;
  }

  .status-radio-option .status-radio-label::before {
    content: "";
    width: 17px;
    height: 17px;
    border: 2px solid #aeb7c2;
    border-radius: 50%;
    background: #fff;
    transition: all .15s ease-in-out;
  }

  .status-radio-option input[type="radio"]:checked + .status-radio-label {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), .08);
    color: var(--bs-primary);
  }

  .status-radio-option input[type="radio"]:checked + .status-radio-label::before {
    border-color: var(--bs-primary);
    box-shadow: inset 0 0 0 4px #fff;
    background: var(--bs-primary);
  }

  .status-radio-option input[type="radio"]:focus-visible + .status-radio-label {
    outline: 2px solid rgba(var(--bs-primary-rgb), .25);
    outline-offset: 2px;
  }

  .status-radio-group.is-invalid .status-radio-label {
    border-color: var(--bs-danger);
  }

  @media (max-width: 575.98px) {
    .condition-card-header,
    .condition-card-actions,
    .condition-search-form {
      width: 100%;
    }

    .condition-search-form input {
      flex: 1;
      min-width: 0;
    }

    .condition-card-actions .btn-primary {
      width: 100%;
    }
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master Product /</span>
    Item Conditions
  </h4>

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

  @if (
    $errors->any()
    && !$errors->createCondition->any()
    && !$errors->editCondition->any()
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

    <div class="card-header condition-card-header">
      <h5 class="mb-0">Daftar Item Conditions</h5>

      <div class="condition-card-actions">
        <form
          method="GET"
          action="{{ route('master-data.item-conditions.index') }}"
          class="condition-search-form">

          <input
            type="text"
            name="q"
            value="{{ $q ?? '' }}"
            class="form-control form-control-sm"
            placeholder="Cari nama..."
            autocomplete="off">

          <button
            type="submit"
            class="btn btn-outline-secondary btn-sm"
            data-bs-toggle="tooltip"
            title="Cari">

            <i class="mdi mdi-magnify"></i>
          </button>

          @if (($q ?? '') !== '')
            <a
              href="{{ route('master-data.item-conditions.index') }}"
              class="btn btn-outline-secondary btn-sm"
              data-bs-toggle="tooltip"
              title="Reset">

              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-item-conditions')
          <button
            type="button"
            class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#modalCreate">

            <i class="mdi mdi-plus me-1"></i>
            Tambah
          </button>
        @endcan
      </div>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover mb-0">

        <thead class="table-light">
          <tr>
            <th style="width: 70px;">#</th>
            <th>Nama</th>
            <th style="width: 150px;" class="text-center">Status</th>
            <th style="width: 120px;" class="text-center">Aksi</th>
          </tr>
        </thead>

        <tbody class="table-border-bottom-0">
          @forelse ($conditions as $index => $condition)
            @php
              $isGood = mb_strtolower(
                trim((string) $condition->name),
                'UTF-8'
              ) === 'good';
            @endphp

            <tr>
              <td>
                {{
                  ($conditions->currentPage() - 1)
                  * $conditions->perPage()
                  + $index
                  + 1
                }}
              </td>

              <td class="fw-medium">
                <i class="mdi mdi-tag-outline mdi-20px text-primary me-2"></i>
                {{ $condition->name }}

                @if ($isGood)
                  <span
                    class="badge bg-label-info ms-1 default-condition-badge"
                    title="Default dan terkunci">

                    Default
                  </span>
                @endif
              </td>

              <td class="text-center">
                @php
                  $statusLabel = ucwords(
                    str_replace('_', ' ', (string) $condition->status)
                  );

                  $statusClass = match (
                    mb_strtolower((string) $condition->status, 'UTF-8')
                  ) {
                    'good', 'active', 'aktif' => 'bg-label-success',
                    'bad', 'inactive', 'nonaktif', 'rusak' => 'bg-label-danger',
                    'warning', 'pending', 'menunggu' => 'bg-label-warning',
                    default => 'bg-label-secondary',
                  };
                @endphp

                <span class="badge {{ $statusClass }}">
                  {{ $statusLabel }}
                </span>
              </td>

              <td class="text-center">
                @if ($isGood)
                  <span
                    class="text-muted"
                    data-bs-toggle="tooltip"
                    title="Data default tidak dapat diubah atau dihapus">

                    <i class="mdi mdi-lock-outline mdi-20px"></i>
                  </span>
                @else
                  <div class="dropdown">

                    <button
                      type="button"
                      class="btn p-0 dropdown-toggle hide-arrow"
                      data-bs-toggle="dropdown"
                      aria-expanded="false">

                      <i class="mdi mdi-dots-vertical"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end">

                      @can('update master-data-item-conditions')
                        <button
                          type="button"
                          class="dropdown-item btn-edit"
                          data-update-url="{{
                            route(
                              'master-data.item-conditions.update',
                              $condition->id
                            )
                          }}"
                          data-id="{{ $condition->id }}"
                          data-name="{{ $condition->name }}"
                          data-status="{{ $condition->status }}"
                          data-bs-toggle="modal"
                          data-bs-target="#modalEdit">

                          <i class="mdi mdi-pencil-outline me-1"></i>
                          Edit
                        </button>
                      @endcan

                      @can('delete master-data-item-conditions')
                        <button
                          type="button"
                          class="dropdown-item text-danger btn-delete"
                          data-name="{{ $condition->name }}"
                          data-delete-url="{{
                            route(
                              'master-data.item-conditions.destroy',
                              $condition->id
                            )
                          }}">

                          <i class="mdi mdi-trash-can-outline me-1"></i>
                          Hapus
                        </button>
                      @endcan

                    </div>
                  </div>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                <i class="mdi mdi-folder-open-outline mdi-36px d-block mb-2"></i>

                @if (($q ?? '') !== '')
                  Item Condition dengan kata kunci
                  <strong>{{ $q }}</strong>
                  tidak ditemukan.
                @else
                  Belum ada data Item Condition.
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>

      </table>
    </div>

    @if ($conditions->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{
          $conditions
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

@can('create master-data-item-conditions')
  <div
    class="modal fade condition-modal"
    id="modalCreate"
    tabindex="-1"
    aria-labelledby="modalCreateLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">

        <form
          id="formCreate"
          method="POST"
          action="{{ route('master-data.item-conditions.store') }}"
          novalidate>

          @csrf

          <input type="hidden" name="_from" value="create">

          <div class="modal-header">
            <h5 class="modal-title" id="modalCreateLabel">
              <i class="mdi mdi-plus-circle-outline me-1"></i>
              Tambah Item Condition
            </h5>

            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Tutup">
            </button>
          </div>

          <div class="modal-body">
            <div class="row g-3">

              <div class="col-12">
                <label for="create_name" class="form-label">
                  Nama <span class="text-danger">*</span>
                </label>

                <div class="input-group input-group-merge">
                  <span class="input-group-text">
                    <i class="mdi mdi-tag-outline"></i>
                  </span>

                  <input
                    type="text"
                    name="name"
                    id="create_name"
                    value="{{
                      $errors->createCondition->any()
                        ? old('name')
                        : ''
                    }}"
                    class="form-control
                      @if ($errors->createCondition->has('name'))
                        is-invalid
                      @endif"
                    placeholder="Contoh: Damaged, Refurbished"
                    maxlength="150"
                    autocomplete="off"
                    required>
                </div>

                <div class="condition-feedback-slot">
                  @if ($errors->createCondition->has('name'))
                    <div class="invalid-feedback">
                      {{ $errors->createCondition->first('name') }}
                    </div>
                  @else
                    <div class="form-text">
                      Nama tidak boleh duplikat. Kondisi Good sudah menjadi default.
                    </div>
                  @endif
                </div>
              </div>

              @if (!empty($statuses))
                <div class="col-12">
                  <label id="create_status_label" class="form-label">
                    Status <span class="text-danger">*</span>
                  </label>

                  <div
                    id="create_status_group"
                    class="status-radio-group
                      @if ($errors->createCondition->has('status'))
                        is-invalid
                      @endif"
                    role="radiogroup"
                    aria-labelledby="create_status_label">

                    @foreach ($statuses as $status)
                      <label class="status-radio-option">
                        <input
                          type="radio"
                          name="status"
                          value="{{ $status }}"
                          @checked(
                            $errors->createCondition->any()
                            && old('status') === $status
                          )
                          required>
                        <span class="status-radio-label">
                          {{ ucwords(str_replace('_', ' ', $status)) }}
                        </span>
                      </label>
                    @endforeach
                  </div>

                  <div class="condition-feedback-slot">
                    @if ($errors->createCondition->has('status'))
                      <div class="invalid-feedback">
                        {{ $errors->createCondition->first('status') }}
                      </div>
                    @else
                      <div class="form-text">
                        Pilih status kondisi sesuai kebutuhan.
                      </div>
                    @endif
                  </div>
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

@can('update master-data-item-conditions')
  <div
    class="modal fade condition-modal"
    id="modalEdit"
    tabindex="-1"
    aria-labelledby="modalEditLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">

        <form
          id="formEdit"
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
            <h5 class="modal-title" id="modalEditLabel">
              <i class="mdi mdi-pencil-outline me-1"></i>
              Edit Item Condition
            </h5>

            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Tutup">
            </button>
          </div>

          <div class="modal-body">
            <div class="row g-3">

              <div class="col-12">
                <label for="edit_name" class="form-label">
                  Nama <span class="text-danger">*</span>
                </label>

                <div class="input-group input-group-merge">
                  <span class="input-group-text">
                    <i class="mdi mdi-tag-outline"></i>
                  </span>

                  <input
                    type="text"
                    name="name"
                    id="edit_name"
                    value="{{
                      $errors->editCondition->any()
                        ? old('name')
                        : ''
                    }}"
                    class="form-control
                      @if ($errors->editCondition->has('name'))
                        is-invalid
                      @endif"
                    maxlength="150"
                    autocomplete="off"
                    required>
                </div>

                <div class="condition-feedback-slot">
                  @if ($errors->editCondition->has('name'))
                    <div class="invalid-feedback">
                      {{ $errors->editCondition->first('name') }}
                    </div>
                  @else
                    <div class="form-text">
                      Nama tidak boleh sama dengan Item Condition lain.
                    </div>
                  @endif
                </div>
              </div>

              @if (!empty($statuses))
                <div class="col-12">
                  <label id="edit_status_label" class="form-label">
                    Status <span class="text-danger">*</span>
                  </label>

                  <div
                    id="edit_status_group"
                    class="status-radio-group
                      @if ($errors->editCondition->has('status'))
                        is-invalid
                      @endif"
                    role="radiogroup"
                    aria-labelledby="edit_status_label">

                    @foreach ($statuses as $status)
                      <label class="status-radio-option">
                        <input
                          type="radio"
                          name="status"
                          value="{{ $status }}"
                          @checked(
                            $errors->editCondition->any()
                            && old('status') === $status
                          )
                          required>
                        <span class="status-radio-label">
                          {{ ucwords(str_replace('_', ' ', $status)) }}
                        </span>
                      </label>
                    @endforeach
                  </div>

                  <div class="condition-feedback-slot">
                    @if ($errors->editCondition->has('status'))
                      <div class="invalid-feedback">
                        {{ $errors->editCondition->first('status') }}
                      </div>
                    @else
                      <div class="form-text">
                        Pilih status kondisi sesuai kebutuhan.
                      </div>
                    @endif
                  </div>
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

@can('delete master-data-item-conditions')
  <form
    id="formDelete"
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
    document.getElementById('modalCreate');

  const modalEditElement =
    document.getElementById('modalEdit');

  const formCreate =
    document.getElementById('formCreate');

  const formEdit =
    document.getElementById('formEdit');

  const formDelete =
    document.getElementById('formDelete');

  const createNameInput =
    document.getElementById('create_name');

  const editNameInput =
    document.getElementById('edit_name');

  const editStatusGroup =
    document.getElementById('edit_status_group');

  const createStatusGroup =
    document.getElementById('create_status_group');

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
  | Fokus modal
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
  | Isi modal edit
  |--------------------------------------------------------------------------
  */

  document
    .querySelectorAll('.btn-edit')
    .forEach(function (button) {
      button.addEventListener('click', function () {
        if (!formEdit) {
          return;
        }

        const updateUrl = this.dataset.updateUrl || '#';

        formEdit.action = updateUrl;

        if (editIdInput) {
          editIdInput.value = this.dataset.id || '';
        }

        if (editNameInput) {
          editNameInput.value = this.dataset.name || '';
          editNameInput.classList.remove('is-invalid');
        }

        if (editStatusGroup) {
          const status = this.dataset.status || '';

          editStatusGroup.classList.remove('is-invalid');

          editStatusGroup
            .querySelectorAll('input[name="status"]')
            .forEach(function (radio) {
              radio.checked = radio.value === status;
              radio.classList.remove('is-invalid');
            });
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

  function normalizeConditionName(input) {
    if (!input) {
      return;
    }

    input.value = input.value
      .trim()
      .replace(/\s+/g, ' ');
  }

  /*
  |--------------------------------------------------------------------------
  | Submit dan cegah double submit
  |--------------------------------------------------------------------------
  */

  function bindSubmit(
    form,
    input,
    processingText
  ) {
    if (!form) {
      return;
    }

    form.addEventListener('submit', function (event) {
      normalizeConditionName(input);

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

  bindSubmit(
    formCreate,
    createNameInput,
    'Menyimpan...'
  );

  bindSubmit(
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
  | Hapus
  |--------------------------------------------------------------------------
  */

  document
    .querySelectorAll('.btn-delete')
    .forEach(function (button) {
      button.addEventListener('click', function () {
        if (!formDelete) {
          alert('Form hapus tidak ditemukan.');
          return;
        }

        const conditionName =
          this.dataset.name || 'item condition ini';

        const deleteUrl =
          this.dataset.deleteUrl || '';

        if (!deleteUrl) {
          alert('URL hapus tidak ditemukan.');
          return;
        }

        const confirmed = confirm(
          `Yakin ingin menghapus Item Condition "${conditionName}"?`
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
  | Reset create modal
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
       * Setelah validasi Laravel gagal, value awal elemen form dapat
       * berisi old input. form.reset() akan kembali ke value awal tersebut,
       * sehingga value dan defaultValue perlu dikosongkan manual.
       */
      if (createNameInput) {
        createNameInput.value = '';
        createNameInput.defaultValue = '';
        createNameInput.classList.remove('is-invalid');
      }

      if (createStatusGroup) {
        createStatusGroup.classList.remove('is-invalid');

        const createStatusRadios =
          createStatusGroup.querySelectorAll('input[name="status"]');

        createStatusRadios.forEach(function (radio, index) {
          radio.checked = index === 0;
          radio.defaultChecked = index === 0;
          radio.classList.remove('is-invalid');
        });
      }

      const feedbackSlots =
        formCreate.querySelectorAll('.condition-feedback-slot');

      feedbackSlots.forEach(function (slot, index) {
        if (index === 0) {
          slot.innerHTML = `
            <div class="form-text">
              Nama tidak boleh duplikat. Kondisi Good sudah menjadi default.
            </div>
          `;
          return;
        }

        slot.innerHTML = `
          <div class="form-text">
            Pilih status kondisi sesuai kebutuhan.
          </div>
        `;
      });

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
  | Buka kembali modal create saat validasi gagal
  |--------------------------------------------------------------------------
  */

  const hasCreateError =
    @json($errors->createCondition->any());

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
  | Buka kembali modal edit saat validasi gagal
  |--------------------------------------------------------------------------
  */

  const hasEditError =
    @json($errors->editCondition->any());

  if (
    hasEditError &&
    modalEditElement &&
    formEdit &&
    typeof bootstrap !== 'undefined'
  ) {
    const oldId = @json(old('id'));
    const oldName = @json(old('name'));
    const oldStatus = @json(old('status'));
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

    if (editStatusGroup) {
      editStatusGroup.classList.remove('is-invalid');

      editStatusGroup
        .querySelectorAll('input[name="status"]')
        .forEach(function (radio) {
          radio.checked = radio.value === (oldStatus || '');
        });
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
