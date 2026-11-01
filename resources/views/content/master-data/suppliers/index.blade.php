@extends('layouts/contentNavbarLayout')

@section('title', 'Master Partner - Suppliers')

@push('vendor-style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  .table-responsive { overflow-x:auto }
  .col-idx{width:60px}
  .col-aksi{width:120px;text-align:center}

  /* Perbesar seluruh tulisan notifikasi */
  .alert {
    font-size: 1.1rem;
    line-height: 1.6;
    font-weight: 500;
  }

  .alert > i {
    font-size: 1.35rem;
    vertical-align: middle;
  }

  .alert ul {
    font-size: 1.05rem;
    line-height: 1.65;
  }

  .invalid-feedback {
    font-size: 1rem;
    line-height: 1.5;
    font-weight: 500;
  }

  /* Pop-up notifikasi */
  .supplier-notification-backdrop {
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

  .supplier-notification-backdrop.show {
    display: flex;
  }

  .supplier-notification {
    width: min(460px, 100%);
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
    overflow: hidden;
    animation: supplierPopupIn .22s ease-out;
  }

  .supplier-notification-body {
    padding: 2rem 1.75rem 1.5rem;
    text-align: center;
  }

  .supplier-notification-icon {
    width: 74px;
    height: 74px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.7rem;
  }

  .supplier-notification.success .supplier-notification-icon {
    color: #fff;
    background: #28a745;
  }

  .supplier-notification.error .supplier-notification-icon {
    color: #fff;
    background: #dc3545;
  }

  .supplier-notification-title {
    margin-bottom: .65rem;
    font-size: 1.55rem;
    font-weight: 700;
  }

  .supplier-notification-message {
    margin: 0;
    color: #5d6470;
    font-size: 1.08rem;
    line-height: 1.65;
    white-space: pre-line;
  }

  .supplier-notification-footer {
    padding: 0 1.75rem 1.75rem;
  }

  .supplier-notification-footer .btn {
    min-width: 130px;
    font-size: 1.05rem;
    font-weight: 600;
  }



  /* Select2 agar mengikuti tampilan Bootstrap/Input Group */
  .input-group > .select2-container {
    flex: 1 1 auto;
    width: 1% !important;
    min-width: 0;
  }

  .select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #d9dee3;
    border-radius: 0 .375rem .375rem 0;
    display: flex;
    align-items: center;
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    width: 100%;
    padding-left: .875rem;
    padding-right: 2.25rem;
    color: #566a7f;
    line-height: 36px;
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
    right: .5rem;
  }

  .select2-container--default.select2-container--focus .select2-selection--single,
  .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #696cff;
    box-shadow: 0 0 0 .2rem rgba(105,108,255,.25);
  }

  .select2-dropdown {
    z-index: 2000;
    border-color: #d9dee3;
  }

  .select2-search--dropdown .select2-search__field {
    border: 1px solid #d9dee3 !important;
    border-radius: .375rem;
    padding: .5rem .75rem;
    outline: none;
  }

  .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #696cff;
  }

  .is-invalid + .select2-container .select2-selection--single,
  select.is-invalid ~ .select2-container .select2-selection--single {
    border-color: #ff3e1d;
  }

  @keyframes supplierPopupIn {
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
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header + breadcrumb --}}
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master Partner /</span> Suppliers
  </h4>

  {{-- Notifikasi ditampilkan sebagai pop-up melalui JavaScript --}}

  {{-- Card: Light Table head --}}
  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Suppliers</span>

      {{-- Form Search (GET) --}}
      <div class="d-flex gap-2">
        <form action="{{ route('master-data.suppliers.index') }}" method="GET" class="d-flex gap-2">
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm"
                 placeholder="Cari nama/telepon/negara...">
          <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if(($q ?? '')!=='')
            <a href="{{ route('master-data.suppliers.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-suppliers')
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateSupplier">
          <i class="mdi mdi-plus me-1"></i> Tambah Supplier
        </button>
        @endcan
      </div>
    </h5>

    <div class="table-responsive text-nowrap">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th class="col-idx">#</th>
            <th>Name</th>
            <th>Address</th>
            <th>Country</th>
            <th>Phone</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>

        <tbody class="table-border-bottom-0">
          @forelse($suppliers as $i => $s)
          <tr>
            <td>{{ ($suppliers->currentPage()-1)*$suppliers->perPage() + $i + 1 }}</td>
            <td class="fw-medium">
              <i class="mdi mdi-truck-outline mdi-20px text-primary me-2"></i>
              {{ $s->name ?? '-' }}
            </td>
            <td>{{ $s->address ?? '-' }}</td>
            <td>{{ $s->country ?? '-' }}</td>
            <td>
              <span class="small text-muted">
                <i class="mdi mdi-phone-outline me-1"></i>{{ $s->phone ?? '-' }}
              </span>
            </td>
            <td class="text-center">
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  @can('update master-data-suppliers')
                  <a href="javascript:void(0);" class="dropdown-item btn-edit-supplier"
                     data-id="{{ $s->id }}"
                     data-name="{{ $s->name }}"
                     data-address="{{ $s->address }}"
                     data-country="{{ $s->country }}"
                     data-phone="{{ $s->phone }}"
                     data-update-url="{{ route('master-data.suppliers.update', $s->id) }}"
                     data-bs-toggle="modal" data-bs-target="#modalEditSupplier">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit
                  </a>
                  @endcan

                  @can('delete master-data-suppliers')
                  <a href="#" class="dropdown-item text-danger btn-delete-supplier"
                     data-delete-url="{{ route('master-data.suppliers.destroy', $s->id) }}">
                    <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                  </a>
                  @endcan
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6">
              <div class="text-center py-5">
                <i class="mdi mdi-database-remove text-muted" style="font-size:4rem;"></i>
                <h5 class="mt-3 text-muted mb-1">Belum ada data supplier</h5>
                <p class="text-muted mb-3">Tambahkan supplier baru di atas.</p>
              </div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination bawa query --}}
    <div class="card-footer d-flex justify-content-end">
      {{ $suppliers->appends(['q' => $q ?? null])->links() }}
    </div>
  </div>
</div>

{{-- ================= MODAL: Tambah Supplier ================= --}}
@can('create master-data-suppliers')
<div class="modal fade" id="modalCreateSupplier" tabindex="-1" aria-labelledby="modalCreateSupplierLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <form id="formCreateSupplier" method="POST" action="{{ route('master-data.suppliers.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCreateSupplierLabel">
            <i class="mdi mdi-truck-plus-outline me-1"></i> Tambah Supplier
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
            {{-- Name --}}
            <div class="col-md-6">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="mdi mdi-account-outline"></i></span>
                <input type="text" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="Supplier name"
                       value="{{ old('_from')==='create' ? old('name') : '' }}" required>
                @error('name') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
              </div>
            </div>

            {{-- Phone (PREFIX +62, INPUT HARUS MULAI DENGAN 8, TANPA 0 DEPAN) --}}
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <div class="input-group input-group-merge">
                <!-- <span class="input-group-text"><i class="mdi mdi-phone-outline me-1"></i> +62</span> -->
                <input type="text" name="phone"
                       class="form-control js-phone-id @error('phone') is-invalid @enderror"
                       placeholder="8xxxxxxxxxx"
                       inputmode="numeric" autocomplete="tel" maxlength="30"
                       value="{{ old('_from')==='create' ? old('phone') : '' }}">
                @error('phone') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
              </div>
              <small class="text-muted">Isi tanpa 0 depan. Contoh: <b>81234567890</b> (akan tersimpan sebagai <b>+6281234567890</b>)</small>
            </div>

            {{-- Address --}}
            <div class="col-12">
              <label class="form-label">Address</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="mdi mdi-home-outline"></i></span>
                <textarea name="address" rows="2"
                          class="form-control @error('address') is-invalid @enderror"
                          placeholder="Street, City, State / Province">{{ old('_from')==='create' ? old('address') : '' }}</textarea>
                @error('address') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
              </div>
            </div>

            {{-- Country --}}
            <div class="col-md-6">
              <label class="form-label">Country <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="mdi mdi-earth"></i></span>
                <select name="country"
                        id="create_country"
                        class="form-select @error('country') is-invalid @enderror"
                        required>
                  <option value="">-- Pilih Country --</option>
                  @foreach($countries as $country)
                    <option value="{{ $country->country_name }}"
                            data-phone-code="{{ $country->phone_code }}"
                            {{ old('_from') === 'create' && old('country') === $country->country_name ? 'selected' : '' }}>
                      {{ $country->country_name }}
                      @if($country->iso2)
                        ({{ $country->iso2 }})
                      @endif
                    </option>
                  @endforeach
                </select>
                @error('country')
                  @if(old('_from') === 'create')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @endif
                @enderror
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: tekan <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button id="btnCreateSave" type="submit" class="btn btn-primary" data-bs-toggle="tooltip" title="Shortcut: Alt + Enter">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- ================= MODAL: Edit Supplier ================= --}}
@can('update master-data-suppliers')
<div class="modal fade" id="modalEditSupplier" tabindex="-1" aria-labelledby="modalEditSupplierLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <form id="formEditSupplier" method="POST" action="#">
        @csrf
        @method('PUT')
        <input type="hidden" name="_from" value="edit">
        <input type="hidden" id="edit_id" name="id" value="{{ old('_from')==='edit' ? old('id') : '' }}">
        <input type="hidden" id="edit_update_url" name="update_url"
               value="{{ old('_from')==='edit' ? old('update_url') : '' }}">

        <div class="modal-header">
          <h5 class="modal-title" id="modalEditSupplierLabel">
            <i class="mdi mdi-pencil-outline me-1"></i> Edit Supplier
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

          <div class="row g-3">
            {{-- Name --}}
            <div class="col-md-6">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="mdi mdi-account-outline"></i></span>
                <input type="text" name="name" id="edit_name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('_from')==='edit' ? old('name') : '' }}" required>
                @error('name') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
              </div>
            </div>

            {{-- Phone (PREFIX +62, INPUT HARUS MULAI DENGAN 8, TANPA 0 DEPAN) --}}
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <div class="input-group input-group-merge">
                <!-- <span class="input-group-text"><i class="mdi mdi-phone-outline me-1"></i> +62</span> -->
                <input type="text" name="phone" id="edit_phone"
                       class="form-control js-phone-id @error('phone') is-invalid @enderror"
                       value="{{ old('_from')==='edit' ? old('phone') : '' }}"
                       placeholder="8xxxxxxxxxx"
                       inputmode="numeric" autocomplete="tel" maxlength="30">
                @error('phone') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
              </div>
              <small class="text-muted">Isi tanpa 0 depan. Contoh: <b>81234567890</b></small>
            </div>

            {{-- Address --}}
            <div class="col-12">
              <label class="form-label">Address</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="mdi mdi-home-outline"></i></span>
                <textarea name="address" id="edit_address" rows="2"
                          class="form-control @error('address') is-invalid @enderror"
                          placeholder="Street, City, State / Province">{{ old('_from')==='edit' ? old('address') : '' }}</textarea>
                @error('address') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
              </div>
            </div>

            {{-- Country --}}
            <div class="col-md-6">
              <label class="form-label">Country <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="mdi mdi-earth"></i></span>
                <select name="country"
                        id="edit_country"
                        class="form-select @error('country') is-invalid @enderror"
                        required>
                  <option value="">-- Pilih Country --</option>
                  @foreach($countries as $country)
                    <option value="{{ $country->country_name }}"
                            data-phone-code="{{ $country->phone_code }}"
                            {{ old('_from') === 'edit' && old('country') === $country->country_name ? 'selected' : '' }}>
                      {{ $country->country_name }}
                      @if($country->iso2)
                        ({{ $country->iso2 }})
                      @endif
                    </option>
                  @endforeach
                </select>
                @error('country')
                  @if(old('_from') === 'edit')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @endif
                @enderror
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: tekan <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update</span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button id="btnEditUpdate" type="submit" class="btn btn-primary" data-bs-toggle="tooltip" title="Shortcut: Alt + Enter">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- ================= POP-UP NOTIFIKASI ================= --}}
<div id="supplierNotificationBackdrop"
     class="supplier-notification-backdrop"
     aria-hidden="true">
  <div id="supplierNotification"
       class="supplier-notification success"
       role="alertdialog"
       aria-modal="true"
       aria-labelledby="supplierNotificationTitle"
       aria-describedby="supplierNotificationMessage">
    <div class="supplier-notification-body">
      <div class="supplier-notification-icon">
        <i id="supplierNotificationIcon" class="mdi mdi-check"></i>
      </div>

      <h4 id="supplierNotificationTitle"
          class="supplier-notification-title">
        Berhasil
      </h4>

      <p id="supplierNotificationMessage"
         class="supplier-notification-message"></p>
    </div>

    <div class="supplier-notification-footer text-center">
      <button type="button"
              id="supplierNotificationClose"
              class="btn btn-primary">
        OK
      </button>
    </div>
  </div>
</div>

{{-- FORM DELETE tersembunyi --}}
@can('delete master-data-suppliers')
<form id="formDeleteSupplier" method="POST" action="#" class="d-none">
  @csrf
  @method('DELETE')
</form>
@endcan
@endsection

@push('page-script')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // ===================== POP-UP NOTIFIKASI =====================
  const notificationBackdrop =
    document.getElementById('supplierNotificationBackdrop');
  const notificationBox =
    document.getElementById('supplierNotification');
  const notificationIcon =
    document.getElementById('supplierNotificationIcon');
  const notificationTitle =
    document.getElementById('supplierNotificationTitle');
  const notificationMessage =
    document.getElementById('supplierNotificationMessage');
  const notificationClose =
    document.getElementById('supplierNotificationClose');

  function showSupplierNotification(type, title, message) {
    if (
      !notificationBackdrop ||
      !notificationBox ||
      !notificationIcon ||
      !notificationTitle ||
      !notificationMessage
    ) {
      return;
    }

    const isSuccess = type === 'success';

    notificationBox.classList.remove('success', 'error');
    notificationBox.classList.add(isSuccess ? 'success' : 'error');

    notificationIcon.className =
      isSuccess
        ? 'mdi mdi-check'
        : 'mdi mdi-alert-outline';

    notificationTitle.textContent =
      title || (isSuccess ? 'Berhasil' : 'Gagal');

    notificationMessage.textContent = message || '';

    notificationBackdrop.classList.add('show');
    notificationBackdrop.setAttribute('aria-hidden', 'false');

    document.body.style.overflow = 'hidden';

    setTimeout(function () {
      notificationClose?.focus();
    }, 100);
  }

  function closeSupplierNotification() {
    if (!notificationBackdrop) {
      return;
    }

    notificationBackdrop.classList.remove('show');
    notificationBackdrop.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  notificationClose?.addEventListener(
    'click',
    closeSupplierNotification
  );

  notificationBackdrop?.addEventListener('click', function (event) {
    if (event.target === notificationBackdrop) {
      closeSupplierNotification();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (
      event.key === 'Escape' &&
      notificationBackdrop?.classList.contains('show')
    ) {
      closeSupplierNotification();
    }
  });

  const flashSuccess = @json(session('success'));
  const flashError = @json(session('error'));
  const validationErrors = @json($errors->all());
  const validationSource = @json(old('_from'));

  if (flashSuccess) {
    showSupplierNotification(
      'success',
      'Berhasil',
      flashSuccess
    );
  } else if (flashError) {
    showSupplierNotification(
      'error',
      'Gagal',
      flashError
    );
  } else if (
    validationErrors.length > 0 &&
    validationSource === null
  ) {
    showSupplierNotification(
      'error',
      'Data Belum Valid',
      validationErrors.join('\n')
    );
  }

  // ===================== SELECT2 COUNTRY =====================
  function initializeCountrySelect2() {
    if (typeof window.jQuery === 'undefined' || !jQuery.fn.select2) {
      console.error('Select2 gagal dimuat. Pastikan jQuery dan Select2 tersedia.');
      return;
    }

    const $createCountry = jQuery('#create_country');
    const $editCountry = jQuery('#edit_country');

    if ($createCountry.length) {
      $createCountry.select2({
        width: '100%',
        placeholder: '-- Pilih Country --',
        allowClear: true,
        dropdownParent: jQuery('#modalCreateSupplier'),
        language: {
          noResults: function () {
            return 'Country tidak ditemukan';
          },
          searching: function () {
            return 'Mencari...';
          }
        }
      });
    }

    if ($editCountry.length) {
      $editCountry.select2({
        width: '100%',
        placeholder: '-- Pilih Country --',
        allowClear: true,
        dropdownParent: jQuery('#modalEditSupplier'),
        language: {
          noResults: function () {
            return 'Country tidak ditemukan';
          },
          searching: function () {
            return 'Mencari...';
          }
        }
      });
    }
  }

  initializeCountrySelect2();

  // Tooltip bootstrap
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

  // ===================== PHONE HELPER (ID) =====================
  // Rule:
  // - input hanya angka
  // - tidak boleh mulai dengan 0
  // - jika paste "08xxxx" -> otomatis jadi "8xxxx"
  // - jika paste "+62..." atau "62..." -> otomatis jadi "8xxxx" (bagian setelah +62)
  function normalizePhoneLocal(val) {
    if (val == null) return '';
    val = String(val).trim();
    // ambil digit saja
    val = val.replace(/\D+/g, '');

    // jika dia paste dengan 62...
    if (val.startsWith('62')) val = val.slice(2);

    // jika dia paste 0 depan (08...)
    while (val.startsWith('0')) val = val.slice(1);

    // sekarang idealnya mulai dengan 8
    return val;
  }

  function bindPhoneInput(el) {
    if (!el) return;

    // saat ketik: blok non-digit, blok leading 0
    el.addEventListener('input', function() {
      const before = this.value;
      let v = normalizePhoneLocal(before);

      // kalau user coba mulai selain 8, biarkan tapi tetap tanpa 0; (validasi final di backend)
      // kalau mau "wajib 8", uncomment baris ini:
      // if (v.length && v[0] !== '8') v = '8' + v.slice(1).replace(/^8+/, '');

      if (v !== before) this.value = v;
    });

    // cegah tombol "0" di posisi pertama
    el.addEventListener('keydown', function(e) {
      const isNumberKey = (e.key >= '0' && e.key <= '9');
      const allowedKeys = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
      if (!isNumberKey && !allowedKeys.includes(e.key)) return; // biarkan shortcut lain (ctrl/cmd) via browser

      // jika posisi awal & mengetik '0' -> blok
      const atStart = (this.selectionStart === 0 && this.selectionEnd === 0);
      if (atStart && e.key === '0') {
        e.preventDefault();
      }
    });

    // saat paste: normalisasi
    el.addEventListener('paste', function(e) {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text');
      const v = normalizePhoneLocal(text);
      document.execCommand('insertText', false, v);
    });
  }

  // bind untuk create & edit
  document.querySelectorAll('.js-phone-id').forEach(bindPhoneInput);

  // ALT+ENTER submit (Create)
  const modalCreate = document.getElementById('modalCreateSupplier');
  modalCreate?.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'Enter') { e.preventDefault(); document.getElementById('formCreateSupplier')?.requestSubmit(); }
  });

  // ALT+ENTER submit (Edit)
  const modalEdit = document.getElementById('modalEditSupplier');
  modalEdit?.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'Enter') { e.preventDefault(); document.getElementById('formEditSupplier')?.requestSubmit(); }
  });

  // Prefill Edit
  document.querySelectorAll('.btn-edit-supplier').forEach(btn => {
    btn.addEventListener('click', function() {
      const form = document.getElementById('formEditSupplier');
      const updateUrl = this.dataset.updateUrl || '#';

      form.action = updateUrl;
      document.getElementById('edit_update_url').value = updateUrl;

      document.getElementById('edit_id').value      = this.dataset.id || '';
      document.getElementById('edit_name').value    = this.dataset.name || '';
      document.getElementById('edit_address').value = this.dataset.address || '';
      const editCountryValue = this.dataset.country || '';
      jQuery('#edit_country').val(editCountryValue).trigger('change');

      // phone disimpan di DB sebagai +62xxxxxxxx / 62xxxx / dll -> tampilkan hanya local part (8xxxx)
      const rawPhone = this.dataset.phone || '';
      document.getElementById('edit_phone').value = normalizePhoneLocal(rawPhone);

      setTimeout(()=>document.getElementById('edit_name')?.focus(), 150);
    });
  });

  // Delete
  document.querySelectorAll('.btn-delete-supplier').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const url = this.dataset.deleteUrl;
      if (!url) return;
      if (!confirm('Yakin ingin menghapus supplier ini? Tindakan ini tidak dapat dibatalkan.')) return;
      const form = document.getElementById('formDeleteSupplier');
      form.action = url;
      form.submit();
    });
  });

  // Reset form create saat modal ditutup
  const formCreateSupplier =
    document.getElementById('formCreateSupplier');

  modalCreate?.addEventListener('hidden.bs.modal', function () {
    if (!formCreateSupplier) {
      return;
    }

    formCreateSupplier.reset();
    jQuery('#create_country').val(null).trigger('change');
    formCreateSupplier.classList.remove('was-validated');

    formCreateSupplier
      .querySelectorAll('input:not([type="hidden"]), textarea, select')
      .forEach(function (field) {
        field.value = '';
        field.defaultValue = '';
        field.classList.remove('is-invalid');
      });

    formCreateSupplier
      .querySelectorAll('.invalid-feedback, .alert-danger')
      .forEach(function (element) {
        element.remove();
      });

    const submitButton =
      formCreateSupplier.querySelector('button[type="submit"]');

    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML =
        '<i class="mdi mdi-content-save-outline me-1"></i> Simpan';
    }
  });

  // Bersihkan status error edit ketika modal ditutup
  const formEditSupplier =
    document.getElementById('formEditSupplier');

  modalEdit?.addEventListener('hidden.bs.modal', function () {
    if (!formEditSupplier) {
      return;
    }

    formEditSupplier.classList.remove('was-validated');

    formEditSupplier
      .querySelectorAll('.is-invalid')
      .forEach(function (field) {
        field.classList.remove('is-invalid');
      });

    formEditSupplier
      .querySelectorAll('.invalid-feedback, .alert-danger')
      .forEach(function (element) {
        element.remove();
      });
  });

  // Reopen modal saat validasi gagal
  const reopened = @json(old('_from'));
  if (reopened === 'create') {
    const m = new bootstrap.Modal(document.getElementById('modalCreateSupplier'));
    m.show();
    jQuery('#create_country').val(@json(old('country'))).trigger('change');

    setTimeout(function () {
      showSupplierNotification(
        'error',
        'Gagal Menyimpan',
        validationErrors.join('\n')
      );
    }, 300);

    setTimeout(()=>document.querySelector('#modalCreateSupplier input[name="name"]')?.focus(), 150);
  } else if (reopened === 'edit') {
    const oldUpdateUrl = @json(old('update_url'));

    if (oldUpdateUrl && formEditSupplier) {
      formEditSupplier.action = oldUpdateUrl;

      const updateUrlInput =
        document.getElementById('edit_update_url');

      if (updateUrlInput) {
        updateUrlInput.value = oldUpdateUrl;
      }
    }

    new bootstrap.Modal(
      document.getElementById('modalEditSupplier')
    ).show();
    jQuery('#edit_country').val(@json(old('country'))).trigger('change');

    setTimeout(function () {
      showSupplierNotification(
        'error',
        'Gagal Memperbarui',
        validationErrors.join('\n')
      );
    }, 300);

    setTimeout(
      ()=>document.getElementById('edit_name')?.focus(),
      150
    );
  }
});
</script>
@endpush