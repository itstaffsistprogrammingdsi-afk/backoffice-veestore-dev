@extends('layouts/contentNavbarLayout')

@section('title', 'Master E-commerce - Ecommerce Banners')

@push('vendor-style')
<style>
  .banner-thumb {
    width: 90px;
    height: 56px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #e6e8eb;
    background: #f5f5f9;
  }

  .banner-preview {
    width: 180px;
    height: 110px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #e6e8eb;
    display: none;
  }

  .banner-preview.show {
    display: block;
  }

  .banner-table-text {
    white-space: normal;
    min-width: 180px;
  }

  .banner-modal .modal-dialog {
    max-width: 980px;
  }

  .banner-modal .modal-body {
    max-height: calc(100vh - 190px);
    overflow-y: auto;
  }

  .banner-notification-backdrop {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(24, 22, 31, .58);
    backdrop-filter: blur(3px);
  }

  .banner-notification-backdrop.show {
    display: flex;
  }

  .banner-notification {
    width: min(460px, 100%);
    padding: 32px 34px 28px;
    border-radius: 18px;
    background: #fff;
    text-align: center;
    box-shadow: 0 24px 80px rgba(20, 20, 35, .32);
  }

  .banner-notification-icon {
    width: 74px;
    height: 74px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #fff;
    font-size: 38px;
  }

  .banner-notification.success .banner-notification-icon {
    background: #28ad4f;
  }

  .banner-notification.error .banner-notification-icon {
    background: #ea3d4f;
  }

  .banner-notification-button {
    min-width: 130px;
    margin-top: 24px;
    background: #8447e6;
    color: #fff;
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master E-commerce /</span>
    Ecommerce Banners
  </h4>

  <div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
      <h5 class="mb-0">Daftar Banner</h5>

      <div class="d-flex flex-wrap gap-2">
        <form
          method="GET"
          action="{{ route('master-data.ecommerce-banners.index') }}"
          class="d-flex gap-2">

          <input
            type="text"
            name="q"
            value="{{ $q ?? '' }}"
            class="form-control form-control-sm"
            placeholder="Cari banner...">

          <button type="submit" class="btn btn-outline-secondary btn-sm">
            <i class="mdi mdi-magnify"></i>
          </button>

          @if (($q ?? '') !== '')
            <a
              href="{{ route('master-data.ecommerce-banners.index') }}"
              class="btn btn-outline-secondary btn-sm">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-ecommerce-banners')
          <button
            type="button"
            class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#modalCreateBanner">
            <i class="mdi mdi-plus me-1"></i>
            Tambah Banner
          </button>
        @endcan
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Desktop</th>
            <th>Mobile</th>
            <th>Nama</th>
            <th>Status</th>
            <th>Ukuran</th>
            <th>Placement</th>
            <th>Urutan</th>
            <th>Periode</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          @forelse ($banners as $index => $banner)
            <tr>
              <td>
                {{
                  ($banners->currentPage() - 1)
                    * $banners->perPage()
                    + $index
                    + 1
                }}
              </td>

              <td>
                @if ($banner->image_url)
                  <img
                    src="{{ $banner->image_url }}"
                    alt="{{ $banner->alt_text ?: $banner->name }}"
                    class="banner-thumb">
                @else
                  -
                @endif
              </td>

              <td>
                @if ($banner->mobile_image_url)
                  <img
                    src="{{ $banner->mobile_image_url }}"
                    alt="{{ $banner->alt_text ?: $banner->name }}"
                    class="banner-thumb">
                @else
                  -
                @endif
              </td>

              <td class="banner-table-text">
                <div class="fw-semibold">{{ $banner->name }}</div>
                <small class="text-muted">
                  {{ $banner->description ?: '-' }}
                </small>
              </td>

              <td>
                <span class="badge {{ $banner->status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">
                  {{ $statuses[$banner->status] ?? $banner->status }}
                </span>
              </td>

              <td>
                {{ $sizes[$banner->size] ?? $banner->size }}
              </td>

              <td>{{ $banner->placement ?: '-' }}</td>
              <td>{{ $banner->display_order }}</td>

              <td class="banner-table-text">
                <small>
                  Mulai:
                  {{ $banner->starts_at?->format('d-m-Y H:i') ?? '-' }}
                  <br>
                  Selesai:
                  {{ $banner->ends_at?->format('d-m-Y H:i') ?? '-' }}
                </small>
              </td>

              <td class="text-center">
                <div class="dropdown">
                  <button
                    type="button"
                    class="btn p-0 dropdown-toggle hide-arrow"
                    data-bs-toggle="dropdown">
                    <i class="mdi mdi-dots-vertical"></i>
                  </button>

                  <div class="dropdown-menu dropdown-menu-end">
                    @can('update master-data-ecommerce-banners')
                      <button
                        type="button"
                        class="dropdown-item btn-edit-banner"
                        data-id="{{ $banner->id }}"
                        data-name="{{ $banner->name }}"
                        data-description="{{ $banner->description }}"
                        data-status="{{ $banner->status }}"
                        data-size="{{ $banner->size }}"
                        data-placement="{{ $banner->placement }}"
                        data-display-order="{{ $banner->display_order }}"
                        data-cta-label="{{ $banner->cta_label }}"
                        data-target-url="{{ $banner->target_url }}"
                        data-alt-text="{{ $banner->alt_text }}"
                        data-starts-at="{{ $banner->starts_at?->format('Y-m-d\TH:i') }}"
                        data-ends-at="{{ $banner->ends_at?->format('Y-m-d\TH:i') }}"
                        data-image-url="{{ $banner->image_url }}"
                        data-mobile-image-url="{{ $banner->mobile_image_url }}"
                        data-update-url="{{ route('master-data.ecommerce-banners.update', $banner->id) }}"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEditBanner">
                        <i class="mdi mdi-pencil-outline me-1"></i>
                        Edit
                      </button>
                    @endcan

                    @can('delete master-data-ecommerce-banners')
                      <button
                        type="button"
                        class="dropdown-item text-danger btn-delete-banner"
                        data-name="{{ $banner->name }}"
                        data-delete-url="{{ route('master-data.ecommerce-banners.destroy', $banner->id) }}">
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
              <td colspan="10" class="text-center text-muted py-4">
                Belum ada data banner.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($banners->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $banners->links() }}
      </div>
    @endif
  </div>
</div>

@php
  $createErrors = $errors->createBanner;
  $editErrors = $errors->editBanner;
@endphp

@can('create master-data-ecommerce-banners')
<div class="modal fade banner-modal" id="modalCreateBanner" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form
        id="formCreateBanner"
        method="POST"
        action="{{ route('master-data.ecommerce-banners.store') }}"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_from" value="create">

        <div class="modal-header">
          <h5 class="modal-title">Tambah Banner</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input
                type="text"
                name="name"
                class="form-control @if($createErrors->has('name')) is-invalid @endif"
                value="{{ old('_from') === 'create' ? old('name') : '' }}"
                maxlength="255"
                required>
              @if ($createErrors->has('name'))
                <div class="invalid-feedback">{{ $createErrors->first('name') }}</div>
              @endif
            </div>

            <div class="col-md-3">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select" required>
                @foreach ($statuses as $value => $label)
                  <option value="{{ $value }}" @selected(old('status', array_key_first($statuses)) === $value)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Ukuran <span class="text-danger">*</span></label>
              <select name="size" class="form-select" required>
                @foreach ($sizes as $value => $label)
                  <option value="{{ $value }}" @selected(old('size', array_key_first($sizes)) === $value)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea name="description" class="form-control" rows="3">{{ old('_from') === 'create' ? old('description') : '' }}</textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">Placement <span class="text-danger">*</span></label>
              <input type="text" name="placement" class="form-control @if($createErrors->has('placement')) is-invalid @endif" value="{{ old('placement', 'home') }}" maxlength="100" required>
              @if ($createErrors->has('placement'))
                <div class="invalid-feedback">{{ $createErrors->first('placement') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label">Urutan Tampil</label>
              <input type="number" name="display_order" class="form-control" value="{{ old('display_order', 0) }}" min="0" required>
            </div>


            <div class="col-md-6">
              <label class="form-label">Label Tombol</label>
              <input type="text" name="cta_label" class="form-control" value="{{ old('cta_label') }}" maxlength="100">
            </div>

            <div class="col-12">
              <label class="form-label">Target URL</label>
              <input type="url" name="target_url" id="create_target_url" class="form-control" value="{{ old('target_url') }}" placeholder="https://...">
            </div>

            <div class="col-12">
              <label class="form-label">Alt Text</label>
              <input type="text" name="alt_text" class="form-control" value="{{ old('alt_text') }}" maxlength="255">
            </div>

            <div class="col-md-6">
              <label class="form-label">Mulai Tayang</label>
              <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Selesai Tayang</label>
              <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Gambar Desktop <span class="text-danger">*</span></label>
              <input
                type="file"
                name="image"
                id="create_image"
                class="form-control @if($createErrors->has('image')) is-invalid @endif"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                required>
              @if ($createErrors->has('image'))
                <div class="invalid-feedback">{{ $createErrors->first('image') }}</div>
              @endif
              <img id="create_image_preview" class="banner-preview mt-2" alt="Preview desktop">
            </div>

            <div class="col-md-6">
              <label class="form-label">Gambar Mobile</label>
              <input
                type="file"
                name="mobile_image"
                id="create_mobile_image"
                class="form-control @if($createErrors->has('mobile_image')) is-invalid @endif"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
              @if ($createErrors->has('mobile_image'))
                <div class="invalid-feedback">{{ $createErrors->first('mobile_image') }}</div>
              @endif
              <img id="create_mobile_image_preview" class="banner-preview mt-2" alt="Preview mobile">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline me-1"></i>
            Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

@can('update master-data-ecommerce-banners')
<div class="modal fade banner-modal" id="modalEditBanner" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form
        id="formEditBanner"
        method="POST"
        action="{{ old('update_url', '#') }}"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <input type="hidden" name="_from" value="edit">
        <input type="hidden" name="id" id="edit_id" value="{{ old('id') }}">
        <input type="hidden" name="update_url" id="edit_update_url" value="{{ old('update_url') }}">
        <input type="hidden" name="current_image_url" id="edit_current_image_url" value="{{ old('current_image_url') }}">
        <input type="hidden" name="current_mobile_image_url" id="edit_current_mobile_image_url" value="{{ old('current_mobile_image_url') }}">

        <div class="modal-header">
          <h5 class="modal-title">Edit Banner</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input type="text" name="name" id="edit_name" class="form-control" maxlength="255" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status" id="edit_status" class="form-select" required>
                @foreach ($statuses as $value => $label)
                  <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Ukuran</label>
              <select name="size" id="edit_size" class="form-select" required>
                @foreach ($sizes as $value => $label)
                  <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">Placement <span class="text-danger">*</span></label>
              <input type="text" name="placement" id="edit_placement" class="form-control @if($editErrors->has('placement')) is-invalid @endif" maxlength="100" required>
              @if ($editErrors->has('placement'))
                <div class="invalid-feedback">{{ $editErrors->first('placement') }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label">Urutan Tampil</label>
              <input type="number" name="display_order" id="edit_display_order" class="form-control" min="0" required>
            </div>


            <div class="col-md-6">
              <label class="form-label">Label Tombol</label>
              <input type="text" name="cta_label" id="edit_cta_label" class="form-control" maxlength="100">
            </div>

            <div class="col-12">
              <label class="form-label">Target URL</label>
              <input type="url" name="target_url" id="edit_target_url" class="form-control">
            </div>

            <div class="col-12">
              <label class="form-label">Alt Text</label>
              <input type="text" name="alt_text" id="edit_alt_text" class="form-control" maxlength="255">
            </div>

            <div class="col-md-6">
              <label class="form-label">Mulai Tayang</label>
              <input type="datetime-local" name="starts_at" id="edit_starts_at" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Selesai Tayang</label>
              <input type="datetime-local" name="ends_at" id="edit_ends_at" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Ganti Gambar Desktop</label>
              <input
                type="file"
                name="image"
                id="edit_image"
                class="form-control"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
              <img id="edit_image_preview" class="banner-preview mt-2" alt="Preview desktop">
            </div>

            <div class="col-md-6">
              <label class="form-label">Ganti Gambar Mobile</label>
              <input
                type="file"
                name="mobile_image"
                id="edit_mobile_image"
                class="form-control"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

              <div class="form-check mt-2">
                <input
                  type="checkbox"
                  name="remove_mobile_image"
                  value="1"
                  id="edit_remove_mobile_image"
                  class="form-check-input">
                <label class="form-check-label" for="edit_remove_mobile_image">
                  Hapus gambar mobile lama
                </label>
              </div>

              <img id="edit_mobile_image_preview" class="banner-preview mt-2" alt="Preview mobile">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline me-1"></i>
            Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

@can('delete master-data-ecommerce-banners')
<form id="formDeleteBanner" method="POST" class="d-none">
  @csrf
  @method('DELETE')
</form>
@endcan

<div id="bannerNotificationBackdrop" class="banner-notification-backdrop">
  <div id="bannerNotification" class="banner-notification success">
    <div class="banner-notification-icon">
      <i id="bannerNotificationIcon" class="mdi mdi-check"></i>
    </div>
    <h3 id="bannerNotificationTitle">Berhasil</h3>
    <p id="bannerNotificationMessage" class="text-muted mb-0"></p>
    <ul id="bannerNotificationErrors" class="text-start mt-3 d-none"></ul>
    <button type="button" id="bannerNotificationClose" class="btn banner-notification-button">OK</button>
  </div>
</div>
@endsection

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const flashSuccess = @json(session('success'));
  const flashError = @json(session('error'));
  const validationErrors = @json($errors->all());
  const oldFrom = @json(old('_from'));

  const notificationBackdrop =
    document.getElementById('bannerNotificationBackdrop');
  const notificationBox =
    document.getElementById('bannerNotification');
  const notificationIcon =
    document.getElementById('bannerNotificationIcon');
  const notificationTitle =
    document.getElementById('bannerNotificationTitle');
  const notificationMessage =
    document.getElementById('bannerNotificationMessage');
  const notificationErrors =
    document.getElementById('bannerNotificationErrors');

  function showNotification(type, title, message, errors = []) {
    const success = type === 'success';

    notificationBox.classList.remove('success', 'error');
    notificationBox.classList.add(success ? 'success' : 'error');
    notificationIcon.className =
      success ? 'mdi mdi-check' : 'mdi mdi-close';
    notificationTitle.textContent = title;
    notificationMessage.textContent = message || '';
    notificationErrors.innerHTML = '';

    if (errors.length > 0) {
      errors.forEach(function (error) {
        const li = document.createElement('li');
        li.textContent = error;
        notificationErrors.appendChild(li);
      });
      notificationErrors.classList.remove('d-none');
    } else {
      notificationErrors.classList.add('d-none');
    }

    notificationBackdrop.classList.add('show');
    document.body.style.overflow = 'hidden';
  }

  function closeNotification() {
    notificationBackdrop.classList.remove('show');
    document.body.style.overflow = '';
  }

  document
    .getElementById('bannerNotificationClose')
    ?.addEventListener('click', closeNotification);

  function bindPreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!input || !preview) return;

    input.addEventListener('change', function () {
      const file = this.files && this.files[0];

      if (!file) return;

      if (!file.type.startsWith('image/')) {
        this.value = '';
        showNotification(
          'error',
          'File Tidak Valid',
          'File yang dipilih harus berupa gambar.'
        );
        return;
      }

      if (file.size > 3 * 1024 * 1024) {
        this.value = '';
        showNotification(
          'error',
          'Ukuran Terlalu Besar',
          'Ukuran gambar maksimal 3 MB.'
        );
        return;
      }

      const reader = new FileReader();
      reader.onload = function (event) {
        preview.src = event.target.result;
        preview.classList.add('show');
      };
      reader.readAsDataURL(file);
    });
  }

  bindPreview('create_image', 'create_image_preview');
  bindPreview('create_mobile_image', 'create_mobile_image_preview');
  bindPreview('edit_image', 'edit_image_preview');
  bindPreview('edit_mobile_image', 'edit_mobile_image_preview');

  document.querySelectorAll('.btn-edit-banner').forEach(function (button) {
    button.addEventListener('click', function () {
      const form = document.getElementById('formEditBanner');

      form.action = this.dataset.updateUrl || '#';

      document.getElementById('edit_id').value =
        this.dataset.id || '';
      document.getElementById('edit_update_url').value =
        this.dataset.updateUrl || '';
      document.getElementById('edit_name').value =
        this.dataset.name || '';
      document.getElementById('edit_description').value =
        this.dataset.description || '';
      document.getElementById('edit_status').value =
        this.dataset.status || '';
      document.getElementById('edit_size').value =
        this.dataset.size || '';
      document.getElementById('edit_placement').value =
        this.dataset.placement || '';
      document.getElementById('edit_display_order').value =
        this.dataset.displayOrder || 0;
      document.getElementById('edit_cta_label').value =
        this.dataset.ctaLabel || '';
      const editUrlInput =
        document.getElementById('edit_target_url');

      if (editUrlInput) {
        editUrlInput.disabled = false;
        editUrlInput.value =
          this.dataset.targetUrl || '';
      }
      document.getElementById('edit_alt_text').value =
        this.dataset.altText || '';
      document.getElementById('edit_starts_at').value =
        this.dataset.startsAt || '';
      document.getElementById('edit_ends_at').value =
        this.dataset.endsAt || '';

      const desktopUrl = this.dataset.imageUrl || '';
      const mobileUrl = this.dataset.mobileImageUrl || '';

      document.getElementById('edit_current_image_url').value =
        desktopUrl;
      document.getElementById('edit_current_mobile_image_url').value =
        mobileUrl;

      const desktopPreview =
        document.getElementById('edit_image_preview');
      const mobilePreview =
        document.getElementById('edit_mobile_image_preview');

      if (desktopUrl) {
        desktopPreview.src = desktopUrl;
        desktopPreview.classList.add('show');
      } else {
        desktopPreview.classList.remove('show');
      }

      if (mobileUrl) {
        mobilePreview.src = mobileUrl;
        mobilePreview.classList.add('show');
      } else {
        mobilePreview.classList.remove('show');
      }

      document.getElementById('edit_image').value = '';
      document.getElementById('edit_mobile_image').value = '';
      document.getElementById('edit_remove_mobile_image').checked = false;
    });
  });

  document.querySelectorAll('.btn-delete-banner').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!confirm(`Hapus banner "${this.dataset.name}"?`)) {
        return;
      }

      const form = document.getElementById('formDeleteBanner');
      form.action = this.dataset.deleteUrl;
      form.submit();
    });
  });

  if (validationErrors.length > 0) {
    showNotification(
      'error',
      oldFrom === 'edit'
        ? 'Gagal Memperbarui'
        : 'Gagal Menyimpan',
      'Data belum dapat diproses karena:',
      validationErrors
    );
  } else if (flashError) {
    showNotification('error', 'Gagal', flashError);
  } else if (flashSuccess) {
    showNotification('success', 'Berhasil', flashSuccess);
  }
});
</script>
@endpush
