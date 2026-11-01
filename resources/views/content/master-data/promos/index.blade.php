@extends('layouts/contentNavbarLayout')

@section('title','Master Promo & Discount - Promos')

@push('vendor-style')
<style>
  .table-responsive { overflow-x:auto }
  .col-idx{width:60px}
  .col-aksi{width:120px;text-align:center}

  /* rapihin select2 biar sejajar sama bootstrap form-select */
  .select2-container .select2-selection--multiple {
    min-height: calc(1.5em + .75rem + 2px);
    border: 1px solid #d9dee3;
    border-radius: .375rem;
    padding: .25rem .5rem;
  }
  .select2-container--default .select2-selection--multiple .select2-selection__choice{
    margin-top: .25rem;
  }
  .select2-container--default.select2-container--focus .select2-selection--multiple{
    border-color: #696cff;
    box-shadow: 0 0 0 .2rem rgba(105,108,255,.15);
  }

  .promo-notification-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1090;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(20, 24, 31, .55);
    backdrop-filter: blur(2px);
  }

  .promo-notification-backdrop.show {
    display: flex;
  }

  .promo-notification {
    width: min(440px, 100%);
    overflow: hidden;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
    animation: promoPopupIn .22s ease-out;
  }

  .promo-notification-body {
    padding: 2rem 1.75rem 1.35rem;
    text-align: center;
  }

  .promo-notification-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #fff;
    font-size: 2.5rem;
  }

  .promo-notification.success .promo-notification-icon {
    background: #28a745;
  }

  .promo-notification.error .promo-notification-icon {
    background: #dc3545;
  }

  .promo-notification.warning .promo-notification-icon {
    background: #f59e0b;
  }

  .promo-notification-title {
    margin-bottom: .65rem;
    font-size: 1.45rem;
    font-weight: 700;
  }

  .promo-notification-message {
    margin: 0;
    color: #5d6470;
    font-size: 1rem;
    line-height: 1.6;
    white-space: pre-line;
  }

  .promo-notification-footer {
    display: flex;
    justify-content: center;
    gap: .75rem;
    padding: 0 1.75rem 1.75rem;
  }

  .promo-notification-footer .btn {
    min-width: 120px;
    font-weight: 600;
  }

  @keyframes promoPopupIn {
    from {
      opacity: 0;
      transform: translateY(12px) scale(.96);
    }

    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

</style>

{{-- ✅ kalau project belum include select2, pakai CDN --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">Master Promo & Discount /</span> Promos</h4>

  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Promos</span>

      <div class="d-flex gap-2">
        <form method="GET" action="{{ route('master-data.promos.index') }}" class="d-flex gap-2">
          <input type="text" class="form-control form-control-sm" name="q" value="{{ $q ?? '' }}" placeholder="Cari nama/kode...">
          <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if(($q ?? '')!=='')
            <a href="{{ route('master-data.promos.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-promos')
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreatePromo">
          <i class="mdi mdi-plus me-1"></i> Tambah Promo
        </button>
        @endcan
      </div>
    </h5>

    <div class="table-responsive text-nowrap">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th class="col-idx">#</th>
            <th>Nama</th>
            <th>Kode</th>
            <th>Tgl Berlaku</th>
            <th>Toko</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($promos as $i => $p)
          @php
            $storeNames = [];
            if (empty($p->all_store)) {
              foreach (($p->store_id ?? []) as $sid) {
                $storeNames[] = $storeMap[$sid] ?? $sid;
              }
            }
          @endphp
          <tr>
            <td>{{ ($promos->currentPage()-1)*$promos->perPage() + $i + 1 }}</td>
            <td class="fw-medium">
              <i class="mdi mdi-sale-outline mdi-20px text-primary me-2"></i>{{ $p->name }}
            </td>
            <td><span class="badge bg-label-primary">{{ $p->code }}</span></td>
            <td>
              {{ optional($p->start_date)->format('Y-m-d') }} — {{ optional($p->end_date)->format('Y-m-d') }}
            </td>
            <td>
              @if(!empty($p->all_store))
                <span class="badge bg-label-success">Semua Cabang</span>
              @else
                {{ count($storeNames) ? implode(', ', $storeNames) : '-' }}
              @endif
            </td>
            <td class="text-center">
              <div class="dropdown">
                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  @can('update master-data-promos')
                  <a href="javascript:void(0);" class="dropdown-item btn-edit"
                     data-update-url="{{ route('master-data.promos.update', $p->id) }}"
                     data-id="{{ $p->id }}"
                     data-name="{{ $p->name }}"
                     data-code="{{ $p->code }}"
                     data-stores='@json($p->store_id ?? [])'
                     data-all-store="{{ !empty($p->all_store) ? 1 : 0 }}"
                     data-start="{{ optional($p->start_date)->format('Y-m-d') }}"
                     data-end="{{ optional($p->end_date)->format('Y-m-d') }}"
                     data-description='@json($p->description ?? "")'
                     data-bs-toggle="modal"
                     data-bs-target="#modalEditPromo">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit
                  </a>
                  @endcan

                  @can('delete master-data-promos')
                  <a href="#" class="dropdown-item text-danger btn-delete"
                     data-delete-url="{{ route('master-data.promos.destroy', $p->id) }}">
                    <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                  </a>
                  @endcan
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center text-muted">Belum ada data</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-end">
      {{ $promos->appends(['q' => $q ?? null])->links() }}
    </div>
  </div>
</div>

{{-- ================= MODAL: Create ================= --}}
@can('create master-data-promos')
<div class="modal fade" id="modalCreatePromo" tabindex="-1" aria-labelledby="modalCreatePromoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formCreatePromo" method="POST" action="{{ route('master-data.promos.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCreatePromoLabel">
            <i class="mdi mdi-sale me-1"></i> Tambah Promo
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input name="name" class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('_from')==='create' ? old('name') : '' }}" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Kode <span class="text-danger">*</span></label>
              <input name="code" class="form-control @error('code') is-invalid @enderror"
                     value="{{ old('_from')==='create' ? old('code') : '' }}" required>
            </div>

            <div class="col-md-6">
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label mb-0">Toko <span class="text-muted">(opsional)</span></label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" id="create_all_store" name="all_store" value="1"
                         @checked(old('_from')==='create' && old('all_store'))>
                  <label class="form-check-label" for="create_all_store">Semua Cabang</label>
                </div>
              </div>

              {{-- ✅ Select2 multi --}}
              <select id="create_store" name="store_id[]"
                      class="form-select js-store-select @error('store_id') is-invalid @enderror"
                      multiple
                      data-placeholder="Pilih cabang (opsional)...">
                @foreach($stores as $s)
                  <option value="{{ $s->id }}"
                    @selected(old('_from')==='create' && is_array(old('store_id')) && in_array($s->id, old('store_id')))
                  >
                    {{ $s->name }}
                  </option>
                @endforeach
              </select>

              @error('store_id') @if(old('_from')==='create') <div class="invalid-feedback d-block">{{ $message }}</div> @endif @enderror
              @error('store_id.*') @if(old('_from')==='create') <div class="invalid-feedback d-block">{{ $message }}</div> @endif @enderror
              <div class="form-text">
                Toko boleh dikosongkan. Centang Semua Cabang untuk memilih seluruh toko.
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Start Date <span class="text-danger">*</span></label>
              <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                     value="{{ old('_from')==='create' ? old('start_date') : '' }}" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">End Date <span class="text-danger">*</span></label>
              <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                     value="{{ old('_from')==='create' ? old('end_date') : '' }}" required>
            </div>

            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                        placeholder="Keterangan promo (opsional)">{{ old('_from')==='create' ? old('description') : '' }}</textarea>
            </div>
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

{{-- ================= MODAL: Edit ================= --}}
@can('update master-data-promos')
<div class="modal fade" id="modalEditPromo" tabindex="-1" aria-labelledby="modalEditPromoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formEditPromo" method="POST" action="#">
        @csrf @method('PUT')
        <input type="hidden" name="_from" value="edit">
        <input type="hidden" id="edit_id" name="id" value="{{ old('_from')==='edit' ? old('id') : '' }}">
        <input type="hidden" id="edit_update_url" name="update_url"
               value="{{ old('_from')==='edit' ? old('update_url') : '' }}">

        <div class="modal-header">
          <h5 class="modal-title" id="modalEditPromoLabel">
            <i class="mdi mdi-pencil-outline me-1"></i> Edit Promo
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama *</label>
              <input id="edit_name" name="name" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Kode *</label>
              <input id="edit_code" name="code" class="form-control" required>
            </div>

            <div class="col-md-6">
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label mb-0">Toko <span class="text-muted">(opsional)</span></label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" id="edit_all_store" name="all_store" value="1">
                  <label class="form-check-label" for="edit_all_store">Semua Cabang</label>
                </div>
              </div>

              {{-- ✅ Select2 multi --}}
              <select id="edit_store" name="store_id[]" class="form-select js-store-select" multiple data-placeholder="Pilih cabang (opsional)...">
                @foreach($stores as $s)
                  <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
              </select>
              <div class="form-text">
                Toko boleh dikosongkan. Centang Semua Cabang untuk memilih seluruh toko.
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Start Date *</label>
              <input id="edit_start" type="date" name="start_date" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">End Date *</label>
              <input id="edit_end" type="date" name="end_date" class="form-control" required>
            </div>

            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
            </div>
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


{{-- ================= POP-UP NOTIFIKASI ================= --}}
<div id="promoNotificationBackdrop"
     class="promo-notification-backdrop"
     aria-hidden="true">
  <div id="promoNotification"
       class="promo-notification success"
       role="alertdialog"
       aria-modal="true"
       aria-labelledby="promoNotificationTitle"
       aria-describedby="promoNotificationMessage">
    <div class="promo-notification-body">
      <div class="promo-notification-icon">
        <i id="promoNotificationIcon" class="mdi mdi-check"></i>
      </div>

      <h4 id="promoNotificationTitle"
          class="promo-notification-title">
        Berhasil
      </h4>

      <p id="promoNotificationMessage"
         class="promo-notification-message"></p>
    </div>

    <div class="promo-notification-footer">
      <button type="button"
              id="promoNotificationCancel"
              class="btn btn-outline-secondary d-none">
        Batal
      </button>

      <button type="button"
              id="promoNotificationConfirm"
              class="btn btn-primary">
        OK
      </button>
    </div>
  </div>
</div>

@can('delete master-data-promos')
<form id="formDeletePromo" method="POST" class="d-none">@csrf @method('DELETE')</form>
@endcan
@endsection

@push('page-script')
{{-- ✅ kalau project belum include jquery + select2, pakai CDN --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  'use strict';


  const notificationBackdrop =
    document.getElementById('promoNotificationBackdrop');
  const notificationBox =
    document.getElementById('promoNotification');
  const notificationIcon =
    document.getElementById('promoNotificationIcon');
  const notificationTitle =
    document.getElementById('promoNotificationTitle');
  const notificationMessage =
    document.getElementById('promoNotificationMessage');
  const notificationConfirm =
    document.getElementById('promoNotificationConfirm');
  const notificationCancel =
    document.getElementById('promoNotificationCancel');

  let notificationConfirmCallback = null;

  function showPromoNotification(
    type,
    title,
    message,
    options = {}
  ) {
    if (
      !notificationBackdrop ||
      !notificationBox ||
      !notificationIcon ||
      !notificationTitle ||
      !notificationMessage ||
      !notificationConfirm ||
      !notificationCancel
    ) {
      return;
    }

    const popupType = ['success', 'error', 'warning'].includes(type)
      ? type
      : 'success';

    notificationBox.classList.remove(
      'success',
      'error',
      'warning'
    );
    notificationBox.classList.add(popupType);

    notificationIcon.className =
      popupType === 'success'
        ? 'mdi mdi-check'
        : popupType === 'warning'
          ? 'mdi mdi-help-circle-outline'
          : 'mdi mdi-alert-outline';

    notificationTitle.textContent = title || 'Informasi';
    notificationMessage.textContent = message || '';

    notificationConfirm.textContent =
      options.confirmText || 'OK';
    notificationCancel.textContent =
      options.cancelText || 'Batal';

    notificationCancel.classList.toggle(
      'd-none',
      !options.showCancel
    );

    notificationConfirmCallback =
      typeof options.onConfirm === 'function'
        ? options.onConfirm
        : null;

    notificationBackdrop.classList.add('show');
    notificationBackdrop.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    setTimeout(function () {
      notificationConfirm.focus();
    }, 100);
  }

  function closePromoNotification() {
    if (!notificationBackdrop) {
      return;
    }

    notificationBackdrop.classList.remove('show');
    notificationBackdrop.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    notificationConfirmCallback = null;
  }

  notificationConfirm?.addEventListener(
    'click',
    function () {
      const callback = notificationConfirmCallback;
      closePromoNotification();

      if (callback) {
        callback();
      }
    }
  );

  notificationCancel?.addEventListener(
    'click',
    closePromoNotification
  );

  notificationBackdrop?.addEventListener(
    'click',
    function (event) {
      if (
        event.target === notificationBackdrop &&
        notificationCancel.classList.contains('d-none')
      ) {
        closePromoNotification();
      }
    }
  );

  document.addEventListener('keydown', function (event) {
    if (
      event.key === 'Escape' &&
      notificationBackdrop?.classList.contains('show')
    ) {
      closePromoNotification();
    }
  });

  const flashSuccess = @json(session('success'));
  const flashError = @json(session('error'));
  const validationErrors = @json($errors->all());
  const validationSource = @json(old('_from'));

  if (flashSuccess) {
    showPromoNotification(
      'success',
      'Berhasil',
      flashSuccess
    );
  } else if (flashError) {
    showPromoNotification(
      'error',
      'Gagal',
      flashError
    );
  } else if (
    validationErrors.length > 0 &&
    validationSource === null
  ) {
    showPromoNotification(
      'error',
      'Data Belum Valid',
      validationErrors.join('\n')
    );
  }

  document
    .querySelectorAll('[data-bs-toggle="tooltip"]')
    .forEach(function (element) {
      bootstrap.Tooltip.getOrCreateInstance(element);
    });

  const modalCreate =
    document.getElementById('modalCreatePromo');

  const modalEdit =
    document.getElementById('modalEditPromo');

  const formCreate =
    document.getElementById('formCreatePromo');

  const formEdit =
    document.getElementById('formEditPromo');

  const createAllStore =
    document.getElementById('create_all_store');

  const editAllStore =
    document.getElementById('edit_all_store');

  const createStoreSelector = '#create_store';
  const editStoreSelector = '#edit_store';

  function initSelect2(selector, parentModal) {
    const $element = $(selector);

    if (!$element.length) {
      return;
    }

    $element.select2({
      width: '100%',
      placeholder:
        $element.data('placeholder') || 'Pilih...',
      allowClear: true,
      closeOnSelect: false,
      dropdownParent: $(parentModal),
    });
  }

  initSelect2(
    createStoreSelector,
    '#modalCreatePromo'
  );

  initSelect2(
    editStoreSelector,
    '#modalEditPromo'
  );

  function getAllStoreValues(selector) {
    return $(selector)
      .find('option')
      .map(function () {
        return this.value;
      })
      .get()
      .filter(function (value) {
        return value !== '';
      });
  }

  /*
   * Saat "Semua Cabang" aktif, seluruh cabang benar-benar
   * ditampilkan sebagai pilihan terpilih pada Select2.
   * Select tetap aktif agar daftar cabang dapat terlihat.
   */
  function applyAllStoreSelection(
    checkbox,
    selector
  ) {
    const $select = $(selector);

    if (!$select.length || !checkbox) {
      return;
    }

    if (checkbox.checked) {
      const allValues =
        getAllStoreValues(selector);

      $select
        .val(allValues)
        .trigger('change');
    } else {
      // Saat "Semua Cabang" dimatikan, kosongkan seluruh pilihan cabang.
      $select
        .val(null)
        .trigger('change');
    }
  }

  createAllStore?.addEventListener(
    'change',
    function () {
      applyAllStoreSelection(
        createAllStore,
        createStoreSelector
      );
    }
  );

  editAllStore?.addEventListener(
    'change',
    function () {
      applyAllStoreSelection(
        editAllStore,
        editStoreSelector
      );
    }
  );

  /*
   * Bila user menghapus salah satu cabang secara manual,
   * status Semua Cabang otomatis dimatikan.
   */
  $(createStoreSelector).on(
    'change',
    function () {
      if (!createAllStore?.checked) {
        return;
      }

      const selected =
        $(this).val() || [];

      const allValues =
        getAllStoreValues(createStoreSelector);

      if (selected.length !== allValues.length) {
        createAllStore.checked = false;
      }
    }
  );

  $(editStoreSelector).on(
    'change',
    function () {
      if (!editAllStore?.checked) {
        return;
      }

      const selected =
        $(this).val() || [];

      const allValues =
        getAllStoreValues(editStoreSelector);

      if (selected.length !== allValues.length) {
        editAllStore.checked = false;
      }
    }
  );

  document
    .querySelectorAll('.btn-edit')
    .forEach(function (button) {
      button.addEventListener('click', function () {
        if (!formEdit) {
          return;
        }

        const updateUrl = this.dataset.updateUrl || '#';
        const editUpdateUrl =
          document.getElementById('edit_update_url');

        formEdit.reset();
        formEdit.action = updateUrl;

        if (editUpdateUrl) {
          editUpdateUrl.value = updateUrl;
        }

        const editId = document.getElementById('edit_id');
        const editName = document.getElementById('edit_name');
        const editCode = document.getElementById('edit_code');
        const editStart = document.getElementById('edit_start');
        const editEnd = document.getElementById('edit_end');
        const editDescription =
          document.getElementById('edit_description');

        if (editId) editId.value = this.dataset.id || '';
        if (editName) editName.value = this.dataset.name || '';
        if (editCode) editCode.value = this.dataset.code || '';
        if (editStart) editStart.value = this.dataset.start || '';
        if (editEnd) editEnd.value = this.dataset.end || '';

        if (editDescription) {
          try {
            editDescription.value = JSON.parse(
              this.dataset.description || '""'
            );
          } catch (error) {
            editDescription.value =
              this.dataset.description || '';
          }
        }

        const isAll = this.dataset.allStore === '1';

        let selectedStores = [];

        try {
          selectedStores = JSON.parse(
            this.dataset.stores || '[]'
          );
        } catch (error) {
          selectedStores = [];
        }

        if (editAllStore) {
          editAllStore.checked = isAll;
        }

        if (isAll) {
          $(editStoreSelector)
            .val(getAllStoreValues(editStoreSelector))
            .trigger('change');
        } else {
          $(editStoreSelector)
            .val(selectedStores)
            .trigger('change');
        }
      });
    });

  document
    .querySelectorAll('.btn-delete')
    .forEach(function (button) {
      button.addEventListener(
        'click',
        function (event) {
          event.preventDefault();

          const deleteUrl =
            this.dataset.deleteUrl || '#';

          showPromoNotification(
            'warning',
            'Hapus Promo?',
            'Data promo yang sudah dihapus tidak dapat dikembalikan.',
            {
              showCancel: true,
              confirmText: 'Ya, Hapus',
              cancelText: 'Batal',
              onConfirm: function () {
                const form =
                  document.getElementById(
                    'formDeletePromo'
                  );

                if (!form) {
                  showPromoNotification(
                    'error',
                    'Gagal',
                    'Form hapus promo tidak ditemukan.'
                  );
                  return;
                }

                form.action = deleteUrl;
                form.submit();
              }
            }
          );
        }
      );
    });

  function resetCreateForm() {
    if (!formCreate) {
      return;
    }

    formCreate.reset();
    formCreate.classList.remove('was-validated');

    formCreate
      .querySelectorAll(
        'input:not([type="hidden"]), textarea'
      )
      .forEach(function (field) {
        if (field.type !== 'checkbox') {
          field.value = '';
          field.defaultValue = '';
        }

        field.classList.remove('is-invalid');
      });

    if (createAllStore) {
      createAllStore.checked = false;
      createAllStore.defaultChecked = false;
    }

    $(createStoreSelector)
      .val(null)
      .trigger('change');

    formCreate
      .querySelectorAll(
        '.invalid-feedback, .alert-danger'
      )
      .forEach(function (element) {
        element.remove();
      });

    const submitButton =
      formCreate.querySelector(
        'button[type="submit"]'
      );

    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML =
        '<i class="mdi mdi-content-save-outline me-1"></i> Simpan';
    }
  }

  modalCreate?.addEventListener(
    'hidden.bs.modal',
    resetCreateForm
  );

  modalCreate?.addEventListener(
    'show.bs.modal',
    function () {
      const reopenedSource = @json(old('_from'));

      if (reopenedSource !== 'create') {
        resetCreateForm();
      }
    }
  );

  modalEdit?.addEventListener(
    'hidden.bs.modal',
    function () {
      if (!formEdit) {
        return;
      }

      formEdit.classList.remove('was-validated');

      formEdit
        .querySelectorAll('.is-invalid')
        .forEach(function (field) {
          field.classList.remove('is-invalid');
        });

      formEdit
        .querySelectorAll(
          '.invalid-feedback, .alert-danger'
        )
        .forEach(function (element) {
          element.remove();
        });

      formEdit.reset();
      formEdit.action = '#';

      const editUpdateUrl =
        document.getElementById('edit_update_url');

      if (editUpdateUrl) {
        editUpdateUrl.value = '';
      }

      $(editStoreSelector)
        .val(null)
        .trigger('change');
    }
  );

  formCreate?.addEventListener(
    'submit',
    function () {
      if (createAllStore?.checked) {
        applyAllStoreSelection(
          createAllStore,
          createStoreSelector
        );
      }
    }
  );

  formEdit?.addEventListener(
    'submit',
    function () {
      if (editAllStore?.checked) {
        applyAllStoreSelection(
          editAllStore,
          editStoreSelector
        );
      }
    }
  );

  const reopened = @json(old('_from'));

  if (reopened === 'create') {
    const oldAllStore =
      @json((bool) old('all_store'));

    if (createAllStore) {
      createAllStore.checked =
        oldAllStore;
    }

    if (oldAllStore) {
      $(createStoreSelector)
        .val(
          getAllStoreValues(
            createStoreSelector
          )
        )
        .trigger('change');
    }

    bootstrap.Modal
      .getOrCreateInstance(modalCreate)
      .show();

    setTimeout(function () {
      if (validationErrors.length > 0) {
        showPromoNotification(
          'error',
          'Gagal Menyimpan',
          validationErrors.join('\n')
        );
      }
    }, 300);
  } else if (reopened === 'edit') {
    const oldUpdateUrl =
      @json(old('update_url'));

    const oldStores =
      @json(old('store_id', []));

    const oldAllStore =
      @json((bool) old('all_store'));

    if (oldUpdateUrl && formEdit) {
      formEdit.action = oldUpdateUrl;

      document.getElementById(
        'edit_update_url'
      ).value = oldUpdateUrl;
    }

    if (editAllStore) {
      editAllStore.checked =
        oldAllStore;
    }

    if (oldAllStore) {
      $(editStoreSelector)
        .val(
          getAllStoreValues(
            editStoreSelector
          )
        )
        .trigger('change');
    } else {
      $(editStoreSelector)
        .val(oldStores)
        .trigger('change');
    }

    bootstrap.Modal
      .getOrCreateInstance(modalEdit)
      .show();

    setTimeout(function () {
      if (validationErrors.length > 0) {
        showPromoNotification(
          'error',
          'Gagal Memperbarui',
          validationErrors.join('\n')
        );
      }
    }, 300);
  }
});
</script>
@endpush
