@extends('layouts/contentNavbarLayout')

@section('title', 'Master Partner - Partners')

@section('page-style')
<style>
  .table-responsive { overflow-x: auto; }
  .col-idx { width: 60px; }
  .col-aksi { width: 120px; text-align: center; }

  /* Pop-up notifikasi seperti Supplier */
  .partner-notification-backdrop {
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

  .partner-notification-backdrop.show {
    display: flex;
  }

  .partner-notification {
    width: min(460px, 100%);
    overflow: hidden;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
    animation: partnerPopupIn .22s ease-out;
  }

  .partner-notification-body {
    padding: 2rem 1.75rem 1.5rem;
    text-align: center;
  }

  .partner-notification-icon {
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

  .partner-notification.success .partner-notification-icon {
    background: #28a745;
  }

  .partner-notification.error .partner-notification-icon {
    background: #dc3545;
  }

  .partner-notification.warning .partner-notification-icon {
    background: #f59e0b;
  }

  .partner-notification-title {
    margin-bottom: .65rem;
    font-size: 1.55rem;
    font-weight: 700;
  }

  .partner-notification-message {
    margin: 0;
    color: #5d6470;
    font-size: 1.08rem;
    line-height: 1.65;
    white-space: pre-line;
  }

  .partner-notification-footer {
    display: flex;
    justify-content: center;
    gap: .75rem;
    padding: 0 1.75rem 1.75rem;
  }

  .partner-notification-footer .btn {
    min-width: 130px;
    font-size: 1.05rem;
    font-weight: 600;
  }

  @keyframes partnerPopupIn {
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
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master Partner /</span> Partners
  </h4>
<div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Partners</span>

      <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2" action="{{ route('master-data.partners.index') }}">
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm"
                 placeholder="Cari nama...">
          <button type="submit" class="btn btn-outline-secondary btn-sm" title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if(($q ?? '')!=='')
            <a href="{{ route('master-data.partners.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreatePartner">
          <i class="mdi mdi-plus me-1"></i> Tambah Partner
        </button>
      </div>
    </h5>

    <div class="table-responsive text-nowrap">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th class="col-idx">#</th>
            <th>Nama</th>
            <th>Dibuat</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($items as $i => $p)
          <tr>
            <td>{{ ($items->currentPage()-1)*$items->perPage() + $i + 1 }}</td>
            <td class="fw-medium">
              <i class="mdi mdi-account-multiple-outline mdi-20px text-primary me-2"></i>{{ $p->name }}
            </td>
            <td class="text-muted small">{{ optional($p->created_at)->format('Y-m-d H:i') }}</td>
            <td class="text-center">
              <div class="dropdown">
                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  <a href="javascript:void(0);" class="dropdown-item btn-edit-partner"
                     data-id="{{ $p->id }}"
                     data-name="{{ $p->name }}"
                     data-update-url="{{ route('master-data.partners.update', $p->id) }}"
                     data-bs-toggle="modal" data-bs-target="#modalEditPartner">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit
                  </a>

                  <a href="#" class="dropdown-item text-danger btn-delete-partner"
                     data-delete-url="{{ route('master-data.partners.destroy', $p->id) }}">
                    <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                  </a>
                </div>
              </div>
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
      {{ $items->appends(['q' => $q ?? null])->links() }}
    </div>
  </div>
</div>

{{-- ================= MODAL: CREATE ================= --}}
<div class="modal fade" id="modalCreatePartner" tabindex="-1" aria-labelledby="modalCreatePartnerLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formCreatePartner" method="POST" action="{{ route('master-data.partners.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCreatePartnerLabel">
            <i class="mdi mdi-account-plus-outline me-1"></i> Tambah Partner
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
<div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('_from')==='create' ? old('name') : '' }}" required>
            @error('name') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button id="btnCreateSave" type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ================= MODAL: EDIT ================= --}}
<div class="modal fade" id="modalEditPartner" tabindex="-1" aria-labelledby="modalEditPartnerLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEditPartner" method="POST" action="#">
        @csrf @method('PUT')
        <input type="hidden" name="_from" value="edit">
        <input type="hidden" id="edit_id" name="id" value="{{ old('_from')==='edit' ? old('id') : '' }}">
        <input type="hidden" id="edit_update_url" name="update_url"
               value="{{ old('_from')==='edit' ? old('update_url') : '' }}">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditPartnerLabel">
            <i class="mdi mdi-pencil-outline me-1"></i> Edit Partner
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
<div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input id="edit_name" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('_from')==='edit' ? old('name') : '' }}" required>
            @error('name') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update</span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button id="btnEditUpdate" type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


{{-- ================= POP-UP NOTIFIKASI ================= --}}
<div id="partnerNotificationBackdrop"
     class="partner-notification-backdrop"
     aria-hidden="true">
  <div id="partnerNotification"
       class="partner-notification success"
       role="alertdialog"
       aria-modal="true"
       aria-labelledby="partnerNotificationTitle"
       aria-describedby="partnerNotificationMessage">
    <div class="partner-notification-body">
      <div class="partner-notification-icon">
        <i id="partnerNotificationIcon" class="mdi mdi-check"></i>
      </div>

      <h4 id="partnerNotificationTitle"
          class="partner-notification-title">
        Berhasil
      </h4>

      <p id="partnerNotificationMessage"
         class="partner-notification-message"></p>
    </div>

    <div class="partner-notification-footer text-center">
      <button type="button"
              id="partnerNotificationCancel"
              class="btn btn-outline-secondary me-2 d-none">
        Batal
      </button>

      <button type="button"
              id="partnerNotificationConfirm"
              class="btn btn-primary">
        OK
      </button>
    </div>
  </div>
</div>

{{-- FORM delete --}}
<form id="formDeletePartner" method="POST" class="d-none">
  @csrf @method('DELETE')
</form>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const notificationBackdrop =
    document.getElementById('partnerNotificationBackdrop');
  const notificationBox =
    document.getElementById('partnerNotification');
  const notificationIcon =
    document.getElementById('partnerNotificationIcon');
  const notificationTitle =
    document.getElementById('partnerNotificationTitle');
  const notificationMessage =
    document.getElementById('partnerNotificationMessage');
  const notificationConfirm =
    document.getElementById('partnerNotificationConfirm');
  const notificationCancel =
    document.getElementById('partnerNotificationCancel');

  let confirmCallback = null;

  function showPartnerNotification(type, title, message, options = {}) {
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

    notificationBox.classList.remove('success', 'error', 'warning');
    notificationBox.classList.add(popupType);

    notificationIcon.className =
      popupType === 'success'
        ? 'mdi mdi-check'
        : popupType === 'warning'
          ? 'mdi mdi-help-circle-outline'
          : 'mdi mdi-alert-outline';

    notificationTitle.textContent = title || 'Informasi';
    notificationMessage.textContent = message || '';

    notificationConfirm.textContent = options.confirmText || 'OK';
    notificationCancel.textContent = options.cancelText || 'Batal';
    notificationCancel.classList.toggle('d-none', !options.showCancel);

    confirmCallback =
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

  function closePartnerNotification() {
    if (!notificationBackdrop) return;

    notificationBackdrop.classList.remove('show');
    notificationBackdrop.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    confirmCallback = null;
  }

  notificationConfirm?.addEventListener('click', function () {
    const callback = confirmCallback;
    closePartnerNotification();

    if (callback) {
      callback();
    }
  });

  notificationCancel?.addEventListener(
    'click',
    closePartnerNotification
  );

  notificationBackdrop?.addEventListener('click', function (event) {
    if (
      event.target === notificationBackdrop &&
      notificationCancel?.classList.contains('d-none')
    ) {
      closePartnerNotification();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (
      event.key === 'Escape' &&
      notificationBackdrop?.classList.contains('show')
    ) {
      closePartnerNotification();
    }
  });

  const flashSuccess = @json(session('success'));
  const flashError = @json(session('error'));
  const validationErrors = @json($errors->all());
  const validationSource = @json(old('_from'));

  if (flashSuccess) {
    showPartnerNotification('success', 'Berhasil', flashSuccess);
  } else if (flashError) {
    showPartnerNotification('error', 'Gagal', flashError);
  } else if (
    validationErrors.length > 0 &&
    validationSource === null
  ) {
    showPartnerNotification(
      'error',
      'Data Belum Valid',
      validationErrors.join('\n')
    );
  }

  document
    .querySelectorAll('[data-bs-toggle="tooltip"]')
    .forEach(function (element) {
      new bootstrap.Tooltip(element);
    });

  const modalCreate = document.getElementById('modalCreatePartner');
  const modalEdit = document.getElementById('modalEditPartner');

  modalCreate?.addEventListener('keydown', function (event) {
    if (event.altKey && event.key === 'Enter') {
      event.preventDefault();
      document.getElementById('formCreatePartner')?.requestSubmit();
    }
  });

  modalEdit?.addEventListener('keydown', function (event) {
    if (event.altKey && event.key === 'Enter') {
      event.preventDefault();
      document.getElementById('formEditPartner')?.requestSubmit();
    }
  });

  document.querySelectorAll('.btn-edit-partner').forEach(function (button) {
    button.addEventListener('click', function () {
      const form = document.getElementById('formEditPartner');
      const updateUrl = this.dataset.updateUrl || '';

      if (!form || !updateUrl) {
        showPartnerNotification(
          'error',
          'URL Tidak Ditemukan',
          'URL update partner tidak tersedia.'
        );
        return;
      }

      form.action = updateUrl;

      document.getElementById('edit_update_url').value = updateUrl;
      document.getElementById('edit_id').value = this.dataset.id || '';
      document.getElementById('edit_name').value =
        this.dataset.name || '';

      setTimeout(function () {
        document.getElementById('edit_name')?.focus();
      }, 150);
    });
  });

  document.querySelectorAll('.btn-delete-partner').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();

      const deleteUrl = this.dataset.deleteUrl || '';

      if (!deleteUrl) {
        showPartnerNotification(
          'error',
          'URL Tidak Ditemukan',
          'URL hapus partner tidak tersedia.'
        );
        return;
      }

      showPartnerNotification(
        'warning',
        'Hapus Partner?',
        'Data partner yang sudah dihapus tidak dapat dikembalikan.',
        {
          showCancel: true,
          confirmText: 'Ya, Hapus',
          cancelText: 'Batal',
          onConfirm: function () {
            const form = document.getElementById('formDeletePartner');
            form.action = deleteUrl;
            form.submit();
          }
        }
      );
    });
  });

  const reopened = @json(old('_from'));

  if (reopened === 'create') {
    new bootstrap.Modal(
      document.getElementById('modalCreatePartner')
    ).show();

    setTimeout(function () {
      showPartnerNotification(
        'error',
        'Gagal Menyimpan',
        validationErrors.join('\n')
      );
    }, 300);
  } else if (reopened === 'edit') {
    const oldUpdateUrl = @json(old('update_url'));
    const editForm = document.getElementById('formEditPartner');

    if (oldUpdateUrl && editForm) {
      editForm.action = oldUpdateUrl;
      document.getElementById('edit_update_url').value = oldUpdateUrl;
    }

    new bootstrap.Modal(
      document.getElementById('modalEditPartner')
    ).show();

    setTimeout(function () {
      showPartnerNotification(
        'error',
        'Gagal Memperbarui',
        validationErrors.join('\n')
      );
    }, 300);
  }
});
</script>
@endsection
