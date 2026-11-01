@extends('layouts/contentNavbarLayout')

@section('title', 'Master Partner - Couriers')

@push('vendor-style')
<style>
  .table-responsive { overflow-x:auto }
  .col-idx{width:60px}
  .col-aksi{width:120px;text-align:center}

  /* Popup notifikasi */
  .courier-notification-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(20, 24, 31, .58);
    backdrop-filter: blur(2px);
  }

  .courier-notification-backdrop.show {
    display: flex;
  }

  .courier-notification {
    width: min(460px, 100%);
    overflow: hidden;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
    animation: courierPopupIn .22s ease-out;
  }

  .courier-notification-body {
    padding: 2rem 1.75rem 1.5rem;
    text-align: center;
  }

  .courier-notification-icon {
    width: 74px;
    height: 74px;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #fff;
    font-size: 2.7rem;
  }

  .courier-notification.success .courier-notification-icon {
    background: #28a745;
  }

  .courier-notification.error .courier-notification-icon {
    background: #dc3545;
  }

  .courier-notification.warning .courier-notification-icon {
    background: #ff9800;
  }

  .courier-notification-title {
    margin-bottom: .65rem;
    font-size: 1.55rem;
    font-weight: 700;
  }

  .courier-notification-message {
    margin: 0;
    color: #5d6470;
    font-size: 1.08rem;
    line-height: 1.65;
    white-space: pre-line;
  }

  .courier-notification-footer {
    padding: 0 1.75rem 1.75rem;
    text-align: center;
  }

  .courier-notification-footer .btn {
    min-width: 130px;
    font-size: 1.05rem;
    font-weight: 600;
  }

  @keyframes courierPopupIn {
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
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master Partner /</span> Couriers
  </h4>

  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Couriers</span>

      <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2" action="{{ route('master-data.couriers.index') }}">
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm"
                 placeholder="Cari (name/code/phone)...">
          <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if(($q ?? '')!=='')
            <a href="{{ route('master-data.couriers.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-couriers')
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateCourier">
          <i class="mdi mdi-plus me-1"></i> Tambah Courier
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
            <th>Kontak</th>
            <th>Service</th>
            <th>Status</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($couriers as $i => $c)
          <tr>
            <td>{{ ($couriers->currentPage()-1)*$couriers->perPage() + $i + 1 }}</td>
            <td class="fw-medium">
              <i class="mdi mdi-truck-delivery-outline mdi-20px text-primary me-2"></i>{{ $c->name }}
            </td>
            <td><span class="badge bg-label-primary">{{ $c->code }}</span></td>
            <td class="small text-muted">
              <div><i class="mdi mdi-phone me-1"></i>{{ $c->phone ?: '-' }}</div>
              <div><i class="mdi mdi-account me-1"></i>{{ $c->contact_person ?: '-' }}</div>
              <div><i class="mdi mdi-email-outline me-1"></i>{{ $c->email ?: '-' }}</div>
            </td>
            <td>{{ $c->service_type ?: '-' }}</td>
            <td>
              @if($c->status === 'active')
                <span class="badge bg-success">active</span>
              @else
                <span class="badge bg-secondary">non_active</span>
              @endif
            </td>
            <td class="text-center">
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  @can('update master-data-couriers')
                  <a href="javascript:void(0);" class="dropdown-item btn-edit-courier"
                     data-id="{{ $c->id }}" data-name="{{ $c->name }}"
                     data-phone="{{ $c->phone }}"
                     data-contact_person="{{ $c->contact_person }}"
                     data-email="{{ $c->email }}"
                     data-service_type="{{ $c->service_type }}"
                     data-status="{{ $c->status }}"
                     data-update-url="{{ route('master-data.couriers.update', $c->id) }}"
                     data-bs-toggle="modal" data-bs-target="#modalEditCourier">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit
                  </a>
                  @endcan

                  @can('delete master-data-couriers')
                  <a href="#" class="dropdown-item text-danger btn-delete-courier"
                     data-delete-url="{{ route('master-data.couriers.destroy', $c->id) }}">
                    <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                  </a>
                  @endcan
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
      {{ $couriers->appends(['q' => $q ?? null])->links() }}
    </div>
  </div>
</div>

{{-- ================= MODAL: CREATE ================= --}}
@can('create master-data-couriers')
<div class="modal fade" id="modalCreateCourier" tabindex="-1" aria-labelledby="modalCreateCourierLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formCreateCourier" method="POST" action="{{ route('master-data.couriers.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">

        {{-- hidden real phone (akan berisi +62xxxx) --}}
        <input type="hidden" id="create_phone" name="phone"
               value="{{ old('_from')==='create' ? old('phone') : '' }}">

        <div class="modal-header">
          <h5 class="modal-title" id="modalCreateCourierLabel">
            <i class="mdi mdi-truck-plus-outline me-1"></i> Tambah Courier
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

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name"
                     class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('_from')==='create' ? old('name') : '' }}" required>
              @error('name') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Phone <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">+62</span>
                {{-- field yang dilihat user: cukup ketik 89343 (tanpa 0) --}}
                <input id="create_phone_display" type="text" inputmode="numeric" autocomplete="off"
                       class="form-control @error('phone') is-invalid @enderror"
                       placeholder="contoh: 8123456789"
                       required>
              </div>
              @error('phone') @if(old('_from')==='create') <div class="invalid-feedback d-block">{{ $message }}</div> @endif @enderror
              <div class="form-text">Ketik tanpa angka <b>0</b> di depan. Contoh: <code>8123456789</code></div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Contact Person</label>
              <input type="text" name="contact_person"
                     class="form-control @error('contact_person') is-invalid @enderror"
                     value="{{ old('_from')==='create' ? old('contact_person') : '' }}">
              @error('contact_person') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email"
                     class="form-control @error('email') is-invalid @enderror"
                     value="{{ old('_from')==='create' ? old('email') : '' }}">
              @error('email') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-3">
              <label class="form-label">Service Type</label>
              <select name="service_type" class="form-select @error('service_type') is-invalid @enderror">
                <option value="">- Pilih -</option>
                @foreach($serviceTypes as $st)
                  <option value="{{ $st }}" @selected(old('_from')==='create' && old('service_type')===$st)>{{ $st }}</option>
                @endforeach
              </select>
              @error('service_type') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-3">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach($statuses as $st)
                  <option value="{{ $st }}" @selected(old('_from')==='create' ? old('status')===$st : $st==='active')>{{ $st }}</option>
                @endforeach
              </select>
              @error('status') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
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

{{-- ================= MODAL: EDIT ================= --}}
@can('update master-data-couriers')
<div class="modal fade" id="modalEditCourier" tabindex="-1" aria-labelledby="modalEditCourierLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formEditCourier" method="POST" action="#">
        @csrf @method('PUT')
        <input type="hidden" name="_from" value="edit">
        <input type="hidden" id="edit_id" name="id" value="{{ old('_from')==='edit' ? old('id') : '' }}">
        <input type="hidden" id="edit_update_url" name="update_url"
               value="{{ old('_from')==='edit' ? old('update_url') : '' }}">

        {{-- hidden real phone (akan berisi +62xxxx) --}}
        <input type="hidden" id="edit_phone" name="phone"
               value="{{ old('_from')==='edit' ? old('phone') : '' }}">

        <div class="modal-header">
          <h5 class="modal-title" id="modalEditCourierLabel">
            <i class="mdi mdi-pencil-outline me-1"></i> Edit Courier
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

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name *</label>
              <input id="edit_name" name="name"
                     class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('_from')==='edit' ? old('name') : '' }}" required>
              @error('name') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Phone <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">+62</span>
                {{-- field yang dilihat user --}}
                <input id="edit_phone_display" type="text" inputmode="numeric" autocomplete="off"
                       class="form-control @error('phone') is-invalid @enderror"
                       placeholder="contoh: 8123456789"
                       required>
              </div>
              @error('phone') @if(old('_from')==='edit') <div class="invalid-feedback d-block">{{ $message }}</div> @endif @enderror
              <div class="form-text">Ketik tanpa angka <b>0</b> di depan. Contoh: <code>8123456789</code></div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Contact Person</label>
              <input id="edit_contact_person" name="contact_person"
                     class="form-control @error('contact_person') is-invalid @enderror"
                     value="{{ old('_from')==='edit' ? old('contact_person') : '' }}">
              @error('contact_person') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input id="edit_email" name="email" type="email"
                     class="form-control @error('email') is-invalid @enderror"
                     value="{{ old('_from')==='edit' ? old('email') : '' }}">
              @error('email') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-3">
              <label class="form-label">Service Type</label>
              <select id="edit_service_type" name="service_type" class="form-select @error('service_type') is-invalid @enderror">
                <option value="">- Pilih -</option>
                @foreach($serviceTypes as $st)
                  <option value="{{ $st }}" @selected(old('_from')==='edit' && old('service_type')===$st)>{{ $st }}</option>
                @endforeach
              </select>
              @error('service_type') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-3">
              <label class="form-label">Status *</label>
              <select id="edit_status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach($statuses as $st)
                  <option value="{{ $st }}" @selected(old('_from')==='edit' ? old('status')===$st : false)>{{ $st }}</option>
                @endforeach
              </select>
              @error('status') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update</span>
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


{{-- ================= POPUP NOTIFIKASI ================= --}}
<div id="courierNotificationBackdrop"
     class="courier-notification-backdrop"
     aria-hidden="true">
  <div id="courierNotification"
       class="courier-notification success"
       role="alertdialog"
       aria-modal="true"
       aria-labelledby="courierNotificationTitle"
       aria-describedby="courierNotificationMessage">

    <div class="courier-notification-body">
      <div class="courier-notification-icon">
        <i id="courierNotificationIcon" class="mdi mdi-check"></i>
      </div>

      <h4 id="courierNotificationTitle"
          class="courier-notification-title">
        Berhasil
      </h4>

      <p id="courierNotificationMessage"
         class="courier-notification-message"></p>
    </div>

    <div class="courier-notification-footer">
      <button type="button"
              id="courierNotificationClose"
              class="btn btn-primary">
        OK
      </button>
    </div>
  </div>
</div>

{{-- FORM delete --}}
@can('delete master-data-couriers')
<form id="formDeleteCourier" method="POST" class="d-none">
  @csrf @method('DELETE')
</form>
@endcan
@endsection

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // ===================== POPUP NOTIFIKASI =====================
  const notificationBackdrop =
    document.getElementById('courierNotificationBackdrop');
  const notificationBox =
    document.getElementById('courierNotification');
  const notificationIcon =
    document.getElementById('courierNotificationIcon');
  const notificationTitle =
    document.getElementById('courierNotificationTitle');
  const notificationMessage =
    document.getElementById('courierNotificationMessage');
  const notificationClose =
    document.getElementById('courierNotificationClose');

  function showCourierNotification(type, title, message) {
    if (
      !notificationBackdrop ||
      !notificationBox ||
      !notificationIcon ||
      !notificationTitle ||
      !notificationMessage
    ) {
      return;
    }

    notificationBox.classList.remove('success', 'error', 'warning');
    notificationBox.classList.add(type || 'success');

    if (type === 'error') {
      notificationIcon.className = 'mdi mdi-alert-outline';
    } else if (type === 'warning') {
      notificationIcon.className = 'mdi mdi-alert-circle-outline';
    } else {
      notificationIcon.className = 'mdi mdi-check';
    }

    notificationTitle.textContent =
      title || (type === 'error' ? 'Gagal' : 'Berhasil');

    notificationMessage.textContent = message || '';

    notificationBackdrop.classList.add('show');
    notificationBackdrop.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    setTimeout(function () {
      notificationClose?.focus();
    }, 100);
  }

  function closeCourierNotification() {
    if (!notificationBackdrop) {
      return;
    }

    notificationBackdrop.classList.remove('show');
    notificationBackdrop.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  notificationClose?.addEventListener(
    'click',
    closeCourierNotification
  );

  notificationBackdrop?.addEventListener('click', function (event) {
    if (event.target === notificationBackdrop) {
      closeCourierNotification();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (
      event.key === 'Escape' &&
      notificationBackdrop?.classList.contains('show')
    ) {
      closeCourierNotification();
    }
  });

  const flashSuccess = @json(session('success'));
  const flashError = @json(session('error'));
  const validationErrors = @json($errors->all());
  const validationSource = @json(old('_from'));

  if (flashSuccess) {
    showCourierNotification(
      'success',
      'Berhasil',
      flashSuccess
    );
  } else if (flashError) {
    showCourierNotification(
      'error',
      'Gagal',
      flashError
    );
  } else if (
    validationErrors.length > 0 &&
    validationSource === null
  ) {
    showCourierNotification(
      'error',
      'Data Belum Valid',
      validationErrors.join('\n')
    );
  }

  // Tooltip
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

  // ====== PHONE HELPERS (LOCAL <-> E164) ======
  function digitsOnly(v) {
    return (v || '').toString().replace(/\D/g, '');
  }

  // input user boleh paste: 0xxxx / 62xxxx / +62xxxx / xxxx -> jadi local tanpa 0
  function toLocalIndo(v) {
    let d = digitsOnly(v);

    // buang prefix 62 jika ada
    if (d.startsWith('62')) d = d.slice(2);

    // buang leading 0 (user input 08xxx)
    while (d.startsWith('0')) d = d.slice(1);

    return d;
  }

  // local "8123" -> "+628123"
  function toE164FromLocal(localDigits) {
    const local = toLocalIndo(localDigits);
    return local ? ('+62' + local) : '';
  }

  // set display & hidden
  function syncPhone(displayEl, hiddenEl) {
    const local = toLocalIndo(displayEl.value);
    displayEl.value = local;
    hiddenEl.value = toE164FromLocal(local);
  }

  // init from hidden value (+62xxxx) -> display local
  function initDisplayFromHidden(displayEl, hiddenEl) {
    const local = toLocalIndo(hiddenEl.value);
    displayEl.value = local;
    hiddenEl.value  = toE164FromLocal(local);
  }

  // ====== ALT+ENTER submit (Create/Edit) ======
  const modalCreate = document.getElementById('modalCreateCourier');
  const modalEdit   = document.getElementById('modalEditCourier');
  modalCreate?.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'Enter') { e.preventDefault(); document.getElementById('formCreateCourier')?.requestSubmit(); }
  });
  modalEdit?.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'Enter') { e.preventDefault(); document.getElementById('formEditCourier')?.requestSubmit(); }
  });

  // ====== CREATE: phone binding ======
  const createPhoneDisplay = document.getElementById('create_phone_display');
  const createPhoneHidden  = document.getElementById('create_phone');
  if (createPhoneDisplay && createPhoneHidden) {
    // init dari old('phone') jika ada
    initDisplayFromHidden(createPhoneDisplay, createPhoneHidden);

    createPhoneDisplay.addEventListener('input', () => syncPhone(createPhoneDisplay, createPhoneHidden));
    document.getElementById('formCreateCourier')?.addEventListener('submit', () => syncPhone(createPhoneDisplay, createPhoneHidden));
  }

  // ====== EDIT: phone binding ======
  const editPhoneDisplay = document.getElementById('edit_phone_display');
  const editPhoneHidden  = document.getElementById('edit_phone');
  if (editPhoneDisplay && editPhoneHidden) {
    // init dari old('phone') jika ada
    initDisplayFromHidden(editPhoneDisplay, editPhoneHidden);

    editPhoneDisplay.addEventListener('input', () => syncPhone(editPhoneDisplay, editPhoneHidden));
    document.getElementById('formEditCourier')?.addEventListener('submit', () => syncPhone(editPhoneDisplay, editPhoneHidden));
  }

  // ====== Prefill Edit (FIX: phone tampil) ======
  document.querySelectorAll('.btn-edit-courier').forEach(btn => {
    btn.addEventListener('click', function () {
      const form = document.getElementById('formEditCourier');
      const updateUrl = this.dataset.updateUrl || '#';

      form.action = updateUrl;
      document.getElementById('edit_update_url').value = updateUrl;

      document.getElementById('edit_id').value             = this.dataset.id   || '';
      document.getElementById('edit_name').value           = this.dataset.name || '';
      document.getElementById('edit_contact_person').value = this.dataset.contact_person || '';
      document.getElementById('edit_email').value          = this.dataset.email || '';
      document.getElementById('edit_service_type').value   = this.dataset.service_type || '';
      document.getElementById('edit_status').value         = this.dataset.status || 'active';

      // phone: dataset.phone bisa "+62...", "62...", "08...", atau "8..."
      const rawPhone = this.dataset.phone || '';
      if (editPhoneDisplay && editPhoneHidden) {
        editPhoneDisplay.value = toLocalIndo(rawPhone);
        editPhoneHidden.value  = toE164FromLocal(editPhoneDisplay.value);
      }

      setTimeout(()=>document.getElementById('edit_name')?.focus(), 150);
    });
  });

  // ====== Hapus ======
  document.querySelectorAll('.btn-delete-courier').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (!confirm('Yakin hapus courier ini?')) return;
      const form = document.getElementById('formDeleteCourier');
      form.action = this.dataset.deleteUrl || '#';
      form.submit();
    });
  });

  // ====== RESET CREATE SAAT MODAL DITUTUP ======
  const formCreateCourier =
    document.getElementById('formCreateCourier');

  modalCreate?.addEventListener('hidden.bs.modal', function () {
    if (!formCreateCourier) return;

    formCreateCourier.reset();
    formCreateCourier.classList.remove('was-validated');

    formCreateCourier
      .querySelectorAll('input:not([type="hidden"]), select')
      .forEach(function (field) {
        field.classList.remove('is-invalid');
      });

    const createName =
      formCreateCourier.querySelector('input[name="name"]');

    const createContact =
      formCreateCourier.querySelector('input[name="contact_person"]');

    const createEmail =
      formCreateCourier.querySelector('input[name="email"]');

    const createService =
      formCreateCourier.querySelector('select[name="service_type"]');

    const createStatus =
      formCreateCourier.querySelector('select[name="status"]');

    [createName, createContact, createEmail].forEach(function (field) {
      if (!field) return;
      field.value = '';
      field.defaultValue = '';
    });

    if (createPhoneDisplay) {
      createPhoneDisplay.value = '';
      createPhoneDisplay.defaultValue = '';
    }

    if (createPhoneHidden) {
      createPhoneHidden.value = '';
      createPhoneHidden.defaultValue = '';
    }

    if (createService) {
      createService.value = '';
      Array.from(createService.options).forEach(function (option) {
        option.defaultSelected = option.value === '';
      });
    }

    if (createStatus) {
      createStatus.value = 'active';
      Array.from(createStatus.options).forEach(function (option) {
        option.defaultSelected = option.value === 'active';
      });
    }

    formCreateCourier
      .querySelectorAll('.invalid-feedback, .alert-danger')
      .forEach(function (element) {
        element.remove();
      });

    const submitButton =
      formCreateCourier.querySelector('button[type="submit"]');

    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML =
        '<i class="mdi mdi-content-save-outline me-1"></i> Simpan';
    }
  });

  // ====== RESET EDIT SAAT MODAL DITUTUP ======
  const formEditCourier =
    document.getElementById('formEditCourier');

  modalEdit?.addEventListener('hidden.bs.modal', function () {
    if (!formEditCourier) return;

    formEditCourier.classList.remove('was-validated');

    formEditCourier
      .querySelectorAll('.is-invalid')
      .forEach(function (field) {
        field.classList.remove('is-invalid');
      });

    formEditCourier
      .querySelectorAll('.invalid-feedback, .alert-danger')
      .forEach(function (element) {
        element.remove();
      });

    const submitButton =
      formEditCourier.querySelector('button[type="submit"]');

    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML =
        '<i class="mdi mdi-content-save-outline me-1"></i> Update';
    }
  });

  // ====== Reopen modal saat validasi gagal ======
  const reopened = @json(old('_from'));
  if (reopened === 'create') {
    const m = new bootstrap.Modal(document.getElementById('modalCreateCourier'));
    m.show();

    setTimeout(function () {
      showCourierNotification(
        'error',
        'Gagal Menyimpan',
        validationErrors.join('\n')
      );
    }, 300);

    setTimeout(()=>document.querySelector('#modalCreateCourier input[name="name"]')?.focus(), 150);
  } else if (reopened === 'edit') {
    const oldUpdateUrl = @json(old('update_url'));

    if (oldUpdateUrl && formEditCourier) {
      formEditCourier.action = oldUpdateUrl;

      const updateUrlInput =
        document.getElementById('edit_update_url');

      if (updateUrlInput) {
        updateUrlInput.value = oldUpdateUrl;
      }
    }

    new bootstrap.Modal(
      document.getElementById('modalEditCourier')
    ).show();

    setTimeout(function () {
      showCourierNotification(
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