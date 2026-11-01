@extends('layouts/contentNavbarLayout')

@section('title', 'Master Location - Stores')

@push('vendor-style')
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
  .table-responsive {
    overflow-x: auto;
  }

  .store-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .store-header-actions,
  .store-search-form {
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  /*
   * Modal mengikuti tinggi viewport.
   * Header dan footer tetap terlihat.
   * Scroll hanya berada di dalam modal-body.
   */
  #modalCreateStore,
  #modalEditStore {
    overflow: hidden !important;
    padding: 10px 0 !important;
  }

  .store-modal-dialog {
    width: calc(100% - 20px);
    max-width: 800px;
    margin: 0 auto;
  }

  .store-modal-dialog .modal-content {
    display: flex !important;
    flex-direction: column !important;
    height: calc(100vh - 20px) !important;
    max-height: calc(100vh - 20px) !important;
    overflow: hidden !important;
  }

  .store-modal-dialog .modal-header,
  .store-modal-dialog .modal-footer {
    flex: 0 0 auto !important;
    background: #fff;
    position: relative;
    z-index: 5;
  }

  .store-modal-dialog .modal-header {
    border-bottom: 1px solid #e9ecef;
  }

  .store-modal-dialog .modal-footer {
    border-top: 1px solid #e9ecef;
  }

  .store-modal-dialog .modal-body {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    max-height: none !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-gutter: stable;
    padding-bottom: 1.5rem;
  }

  .store-modal-dialog .modal-body::-webkit-scrollbar {
    width: 10px;
  }

  .store-modal-dialog .modal-body::-webkit-scrollbar-track {
    background: #f5f5f9;
  }

  .store-modal-dialog .modal-body::-webkit-scrollbar-thumb {
    background: rgba(105, 108, 255, .5);
    border-radius: 999px;
    border: 2px solid #f5f5f9;
  }

  #mapCreate,
  #mapEdit {
    width: 100%;
    height: clamp(160px, 24vh, 210px);
    min-height: 160px;
    border: 1px solid #d9dee3;
    border-radius: 12px;
    background: #f5f5f5;
  }

  .store-map-help {
    min-height: 21px;
  }

  .store-feedback-slot {
    min-height: 22px;
    margin-top: .35rem;
    font-size: .8125rem;
    line-height: 1.25;
  }

  .store-feedback-slot .invalid-feedback,
  .store-feedback-slot .form-text {
    display: block;
    margin: 0;
  }

  .coordinate-group .form-control {
    background-color: #f5f5f9;
  }

  .leaflet-control-attribution {
    font-size: 0;
    opacity: 0;
  }

  @media (max-width: 575.98px) {
    #modalCreateStore,
    #modalEditStore {
      padding: 5px 0 !important;
    }

    .store-card-header,
    .store-header-actions,
    .store-search-form {
      width: 100%;
    }

    .store-search-form input {
      flex: 1;
      min-width: 0;
    }

    .store-header-actions .btn-primary {
      width: 100%;
    }

    .store-modal-dialog {
      width: calc(100% - 10px);
      margin: 0 auto;
    }

    .store-modal-dialog .modal-content {
      height: calc(100vh - 10px) !important;
      max-height: calc(100vh - 10px) !important;
      border-radius: .625rem;
    }

    .store-modal-dialog .modal-header,
    .store-modal-dialog .modal-footer {
      padding-left: 1rem;
      padding-right: 1rem;
    }

    .store-modal-dialog .modal-body {
      padding: 1rem;
      padding-bottom: 1.25rem;
    }

    #mapCreate,
    #mapEdit {
      height: 165px;
      min-height: 165px;
    }
  }

  /* Pop-up notifikasi Store */
  .store-notification-backdrop {
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

  .store-notification-backdrop.show {
    display: flex;
  }

  .store-notification {
    width: min(460px, 100%);
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
    overflow: hidden;
    animation: storePopupIn .22s ease-out;
  }

  .store-notification-body {
    padding: 2rem 1.75rem 1.5rem;
    text-align: center;
  }

  .store-notification-icon {
    width: 74px;
    height: 74px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.7rem;
  }

  .store-notification.success .store-notification-icon {
    color: #fff;
    background: #28a745;
  }

  .store-notification.error .store-notification-icon {
    color: #fff;
    background: #dc3545;
  }

  .store-notification-title {
    margin-bottom: .65rem;
    font-size: 1.55rem;
    font-weight: 700;
  }

  .store-notification-message {
    margin: 0;
    color: #5d6470;
    font-size: 1.08rem;
    line-height: 1.65;
    white-space: pre-line;
  }

  .store-notification-footer {
    padding: 0 1.75rem 1.75rem;
  }

  .store-notification-footer .btn {
    min-width: 130px;
    font-size: 1.05rem;
    font-weight: 600;
  }

  @keyframes storePopupIn {
    from {
      opacity: 0;
      transform: translateY(12px) scale(.96);
    }
    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }


  .store-image-thumb{width:58px;height:58px;object-fit:cover;border-radius:12px;border:1px solid #e6e8eb;background:#f5f5f9}
  .store-image-preview{width:140px;height:140px;object-fit:cover;border-radius:12px;border:1px solid #e6e8eb;background:#f5f5f9;display:none}
  .store-image-preview.show{display:block}
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master Location /</span>
    Stores
  </h4>

  {{-- Notifikasi ditampilkan sebagai pop-up melalui JavaScript --}}

  <div class="card">

    <div class="card-header store-card-header">
      <h5 class="mb-0">Daftar Stores</h5>

      <div class="store-header-actions">
        <form
          method="GET"
          action="{{ route('master-data.stores.index') }}"
          class="store-search-form">

          <input
            type="text"
            name="q"
            value="{{ $q ?? '' }}"
            class="form-control form-control-sm"
            placeholder="Cari nama, kode, alamat, kode pos, telepon..."
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
              href="{{ route('master-data.stores.index') }}"
              class="btn btn-outline-secondary btn-sm"
              data-bs-toggle="tooltip"
              title="Reset">

              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-stores')
          <button
            type="button"
            class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#modalCreateStore">

            <i class="mdi mdi-plus me-1"></i>
            Tambah Store
          </button>
        @endcan
      </div>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table table-hover mb-0">

        <thead class="table-light">
          <tr>
            <th style="width: 60px;">#</th>
            <th style="width: 90px;">Foto</th>
            <th>Nama</th>
            <th>Kode</th>
            <th>Telepon</th>
            <th>Nama Kontak</th>
            <th>Telepon Kontak</th>
            <th>Email Kontak</th>
            <th>Alamat</th>
            <th>Kode Pos</th>
            <th class="text-center" style="width: 120px;">Aksi</th>
          </tr>
        </thead>

        <tbody class="table-border-bottom-0">
          @forelse ($stores as $index => $store)
            @php
              $lat = $store->latitude
                ?? (property_exists($store, 'lat') ? $store->lat : null);

              $lng = $store->longitude
                ?? (property_exists($store, 'lng') ? $store->lng : null);
            @endphp

            <tr>
              <td>
                {{
                  ($stores->currentPage() - 1)
                  * $stores->perPage()
                  + $index
                  + 1
                }}
              </td>

              <td>
                @if (!empty($store->image_url))
                  <img src="{{ $store->image_url }}"
                       alt="{{ $store->name }}"
                       class="store-image-thumb">
                @else
                  <div class="store-image-thumb d-flex align-items-center justify-content-center text-muted">
                    <i class="mdi mdi-image-outline mdi-24px"></i>
                  </div>
                @endif
              </td>

              <td class="fw-medium">
                <i class="mdi mdi-store-outline mdi-20px text-primary me-2"></i>
                {{ $store->name }}
              </td>

              <td>
                <span class="badge bg-label-primary">
                  {{ $store->code }}
                </span>
              </td>

              <td>{{ $store->phone ?: '-' }}</td>
              <td>{{ $store->contact_name ?: '-' }}</td>
              <td>{{ $store->contact_phone ?: '-' }}</td>
              <td>{{ $store->contact_email ?: '-' }}</td>

              <td>
                <span title="{{ $store->address }}">
                  {{ $store->address }}
                </span>

                @if ($lat !== null && $lng !== null)
                  <a
                    class="ms-2"
                    target="_blank"
                    rel="noopener noreferrer"
                    href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}">

                    <i class="mdi mdi-map-marker-outline"></i>
                    Maps
                  </a>
                @endif
              </td>

              <td>{{ $store->postal_code ?: '-' }}</td>

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

                    @can('update master-data-stores')
                      <button
                        type="button"
                        class="dropdown-item btn-edit-store"
                        data-id="{{ $store->id }}"
                        data-name="{{ $store->name }}"
                        data-address="{{ $store->address }}"
                        data-postal-code="{{ $store->postal_code ?? '' }}"
                        data-phone="{{ $store->phone ?? '' }}"
                        data-contact-name="{{ $store->contact_name ?? '' }}"
                        data-contact-phone="{{ $store->contact_phone ?? '' }}"
                        data-contact-email="{{ $store->contact_email ?? '' }}"
                        data-image-url="{{ $store->image_url ?? '' }}"
                        data-lat="{{ $lat ?? '' }}"
                        data-lng="{{ $lng ?? '' }}"
                        data-update-url="{{
                          route('master-data.stores.update', $store->id)
                        }}"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEditStore">

                        <i class="mdi mdi-pencil-outline me-1"></i>
                        Edit
                      </button>
                    @endcan

                    @can('delete master-data-stores')
                      <button
                        type="button"
                        class="dropdown-item text-danger btn-delete-store"
                        data-name="{{ $store->name }}"
                        data-delete-url="{{
                          route('master-data.stores.destroy', $store->id)
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
              <td colspan="11" class="text-center text-muted py-4">
                <i class="mdi mdi-store-off-outline mdi-36px d-block mb-2"></i>

                @if (($q ?? '') !== '')
                  Store dengan kata kunci
                  <strong>{{ $q }}</strong>
                  tidak ditemukan.
                @else
                  Belum ada data store.
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>

      </table>
    </div>

    @if ($stores->hasPages())
      <div class="card-footer d-flex justify-content-end">
        {{ $stores->links() }}
      </div>
    @endif

  </div>
</div>

{{-- ========================================================= --}}
{{-- MODAL CREATE --}}
{{-- ========================================================= --}}

@can('create master-data-stores')
  <div
    class="modal fade"
    id="modalCreateStore"
    tabindex="-1"
    aria-labelledby="modalCreateStoreLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg store-modal-dialog">
      <div class="modal-content">

        <form
          id="formCreateStore"
          method="POST"
          action="{{ route('master-data.stores.store') }}"
          enctype="multipart/form-data"
          novalidate>

          @csrf

          <input type="hidden" name="_from" value="create">

          <div class="modal-header">
            <h5 class="modal-title" id="modalCreateStoreLabel">
              <i class="mdi mdi-store-plus-outline me-1"></i>
              Tambah Store
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

              <div class="col-md-6">
                <label for="create_name" class="form-label">
                  Nama <span class="text-danger">*</span>
                </label>

                <input
                  type="text"
                  name="name"
                  id="create_name"
                  value="{{
                    $errors->createStore->any()
                      ? old('name')
                      : ''
                  }}"
                  class="form-control
                    @if ($errors->createStore->has('name'))
                      is-invalid
                    @endif"
                  maxlength="150"
                  autocomplete="off"
                  required>

                <div class="store-feedback-slot">
                  @if ($errors->createStore->has('name'))
                    <div class="invalid-feedback">
                      {{ $errors->createStore->first('name') }}
                    </div>
                  @else
                    <div class="form-text">Maksimal 150 karakter.</div>
                  @endif
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Kode</label>

                <input
                  type="text"
                  class="form-control"
                  value="Auto (VEES.x)"
                  readonly>

                <div class="store-feedback-slot">
                  <div class="form-text">
                    Kode dibuat otomatis saat disimpan.
                  </div>
                </div>
              </div>

              
              <div class="col-md-6">
                <label for="create_phone" class="form-label">Telepon</label>
                <input type="text"
                       name="phone"
                       id="create_phone"
                       value="{{ $errors->createStore->any() ? old('phone') : '' }}"
                       class="form-control @if($errors->createStore->has('phone')) is-invalid @endif"
                       maxlength="255"
                       placeholder="Contoh: 081234567890"
                       autocomplete="tel">
                <div class="store-feedback-slot">
                  @if($errors->createStore->has('phone'))
                    <div class="invalid-feedback">{{ $errors->createStore->first('phone') }}</div>
                  @else
                    <div class="form-text">Nomor telepon store.</div>
                  @endif
                </div>
              </div>

              <div class="col-md-6">
                <label for="create_image" class="form-label">Gambar Store</label>
                <input type="file"
                       name="image"
                       id="create_image"
                       class="form-control @if($errors->createStore->has('image')) is-invalid @endif"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <div class="store-feedback-slot">
                  @if($errors->createStore->has('image'))
                    <div class="invalid-feedback">{{ $errors->createStore->first('image') }}</div>
                  @else
                    <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                  @endif
                </div>
                <img id="create_image_preview"
                     class="store-image-preview mt-2"
                     alt="Preview gambar store">
              </div>

              <div class="col-md-4">
                <label for="create_contact_name" class="form-label">Nama Kontak <span class="text-danger">*</span></label>
                <input type="text" name="contact_name" id="create_contact_name"
                       value="{{ $errors->createStore->any() ? old('contact_name') : '' }}"
                       class="form-control @if($errors->createStore->has('contact_name')) is-invalid @endif"
                       maxlength="255" autocomplete="name" required>
                @if($errors->createStore->has('contact_name'))
                  <div class="invalid-feedback">{{ $errors->createStore->first('contact_name') }}</div>
                @endif
              </div>

              <div class="col-md-4">
                <label for="create_contact_phone" class="form-label">Telepon Kontak <span class="text-danger">*</span></label>
                <input type="text" name="contact_phone" id="create_contact_phone"
                       value="{{ $errors->createStore->any() ? old('contact_phone') : '' }}"
                       class="form-control @if($errors->createStore->has('contact_phone')) is-invalid @endif"
                       maxlength="255" autocomplete="tel" required>
                @if($errors->createStore->has('contact_phone'))
                  <div class="invalid-feedback">{{ $errors->createStore->first('contact_phone') }}</div>
                @endif
              </div>

              <div class="col-md-4">
                <label for="create_contact_email" class="form-label">Email Kontak</label>
                <input type="email" name="contact_email" id="create_contact_email"
                       value="{{ $errors->createStore->any() ? old('contact_email') : '' }}"
                       class="form-control @if($errors->createStore->has('contact_email')) is-invalid @endif"
                       maxlength="255" autocomplete="email">
                @if($errors->createStore->has('contact_email'))
                  <div class="invalid-feedback">{{ $errors->createStore->first('contact_email') }}</div>
                @endif
              </div>

<div class="col-12">
                <label class="form-label">
                  Lokasi Store
                </label>

                <div id="mapCreate"></div>

                <small
                  id="create_geohelp"
                  class="text-muted d-block mt-1 store-map-help">

                  Klik peta atau geser pin. Alamat dan kode pos akan terisi otomatis.
                </small>
              </div>

              <div class="col-12">
                <label for="create_address" class="form-label">
                  Alamat <span class="text-danger">*</span>
                </label>

                <textarea
                  name="address"
                  id="create_address"
                  rows="3"
                  class="form-control
                    @if ($errors->createStore->has('address'))
                      is-invalid
                    @endif"
                  required>{{
                    $errors->createStore->any()
                      ? old('address')
                      : ''
                  }}</textarea>

                <div class="store-feedback-slot">
                  @if ($errors->createStore->has('address'))
                    <div class="invalid-feedback">
                      {{ $errors->createStore->first('address') }}
                    </div>
                  @else
                    <div class="form-text">
                      Terisi otomatis dari lokasi peta dan tetap dapat diedit.
                    </div>
                  @endif
                </div>
              </div>

              <div class="col-md-6">
                <label for="create_postal_code" class="form-label">
                  Kode Pos
                </label>

                <input
                  type="text"
                  name="postal_code"
                  id="create_postal_code"
                  value="{{
                    $errors->createStore->any()
                      ? old('postal_code')
                      : ''
                  }}"
                  class="form-control
                    @if ($errors->createStore->has('postal_code'))
                      is-invalid
                    @endif"
                  maxlength="20"
                  placeholder="Terisi otomatis dari peta">

                <div class="store-feedback-slot">
                  @if ($errors->createStore->has('postal_code'))
                    <div class="invalid-feedback">
                      {{ $errors->createStore->first('postal_code') }}
                    </div>
                  @else
                    <div class="form-text">
                      Diperoleh dari data reverse geocoding OpenStreetMap.
                    </div>
                  @endif
                </div>
              </div>

              <div class="col-md-6 d-flex align-items-end">
                <button
                  type="button"
                  class="btn btn-outline-primary w-100"
                  id="btnCreateCurrentLocation">

                  <i class="mdi mdi-crosshairs-gps me-1"></i>
                  Gunakan Lokasi Saat Ini
                </button>
              </div>

              <div class="col-md-6 coordinate-group">
                <label for="create_lat" class="form-label">
                  Latitude
                </label>

                <input
                  type="text"
                  name="lat"
                  id="create_lat"
                  value="{{
                    $errors->createStore->any()
                      ? old('lat')
                      : ''
                  }}"
                  class="form-control"
                  readonly>
              </div>

              <div class="col-md-6 coordinate-group">
                <label for="create_lng" class="form-label">
                  Longitude
                </label>

                <input
                  type="text"
                  name="lng"
                  id="create_lng"
                  value="{{
                    $errors->createStore->any()
                      ? old('lng')
                      : ''
                  }}"
                  class="form-control"
                  readonly>
              </div>

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

@can('update master-data-stores')
  <div
    class="modal fade"
    id="modalEditStore"
    tabindex="-1"
    aria-labelledby="modalEditStoreLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg store-modal-dialog">
      <div class="modal-content">

        <form
          id="formEditStore"
          method="POST"
          action="#"
          enctype="multipart/form-data"
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

          <input type="hidden"
                 id="edit_current_image_url"
                 name="current_image_url"
                 value="{{ old('current_image_url') }}">

          <div class="modal-header">
            <h5 class="modal-title" id="modalEditStoreLabel">
              <i class="mdi mdi-pencil-outline me-1"></i>
              Edit Store
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

              <div class="col-md-6">
                <label for="edit_name" class="form-label">
                  Nama <span class="text-danger">*</span>
                </label>

                <input
                  type="text"
                  id="edit_name"
                  name="name"
                  value="{{
                    $errors->editStore->any()
                      ? old('name')
                      : ''
                  }}"
                  class="form-control
                    @if ($errors->editStore->has('name'))
                      is-invalid
                    @endif"
                  maxlength="150"
                  autocomplete="off"
                  required>

                <div class="store-feedback-slot">
                  @if ($errors->editStore->has('name'))
                    <div class="invalid-feedback">
                      {{ $errors->editStore->first('name') }}
                    </div>
                  @else
                    <div class="form-text">Maksimal 150 karakter.</div>
                  @endif
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Kode</label>

                <input
                  type="text"
                  class="form-control"
                  value="Tidak dapat diubah"
                  readonly>

                <div class="store-feedback-slot">
                  <div class="form-text">
                    Kode store tetap menggunakan kode sebelumnya.
                  </div>
                </div>
              </div>

              
              <div class="col-md-6">
                <label for="edit_phone" class="form-label">Telepon</label>
                <input type="text"
                       id="edit_phone"
                       name="phone"
                       value="{{ $errors->editStore->any() ? old('phone') : '' }}"
                       class="form-control @if($errors->editStore->has('phone')) is-invalid @endif"
                       maxlength="255"
                       placeholder="Contoh: 081234567890"
                       autocomplete="tel">
                <div class="store-feedback-slot">
                  @if($errors->editStore->has('phone'))
                    <div class="invalid-feedback">{{ $errors->editStore->first('phone') }}</div>
                  @else
                    <div class="form-text">Nomor telepon store.</div>
                  @endif
                </div>
              </div>

              <div class="col-md-6">
                <label for="edit_image" class="form-label">Gambar Store</label>
                <input type="file"
                       id="edit_image"
                       name="image"
                       class="form-control @if($errors->editStore->has('image')) is-invalid @endif"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <div class="store-feedback-slot">
                  @if($errors->editStore->has('image'))
                    <div class="invalid-feedback">{{ $errors->editStore->first('image') }}</div>
                  @else
                    <div class="form-text">Pilih gambar baru untuk mengganti gambar lama.</div>
                  @endif
                </div>
                <img id="edit_image_preview"
                     class="store-image-preview mt-2"
                     alt="Preview gambar store">
              </div>

              <div class="col-md-4">
                <label for="edit_contact_name" class="form-label">Nama Kontak <span class="text-danger">*</span></label>
                <input type="text" name="contact_name" id="edit_contact_name"
                       value="{{ $errors->editStore->any() ? old('contact_name') : '' }}"
                       class="form-control @if($errors->editStore->has('contact_name')) is-invalid @endif"
                       maxlength="255" autocomplete="name" required>
                @if($errors->editStore->has('contact_name'))
                  <div class="invalid-feedback">{{ $errors->editStore->first('contact_name') }}</div>
                @endif
              </div>

              <div class="col-md-4">
                <label for="edit_contact_phone" class="form-label">Telepon Kontak <span class="text-danger">*</span></label>
                <input type="text" name="contact_phone" id="edit_contact_phone"
                       value="{{ $errors->editStore->any() ? old('contact_phone') : '' }}"
                       class="form-control @if($errors->editStore->has('contact_phone')) is-invalid @endif"
                       maxlength="255" autocomplete="tel" required>
                @if($errors->editStore->has('contact_phone'))
                  <div class="invalid-feedback">{{ $errors->editStore->first('contact_phone') }}</div>
                @endif
              </div>

              <div class="col-md-4">
                <label for="edit_contact_email" class="form-label">Email Kontak</label>
                <input type="email" name="contact_email" id="edit_contact_email"
                       value="{{ $errors->editStore->any() ? old('contact_email') : '' }}"
                       class="form-control @if($errors->editStore->has('contact_email')) is-invalid @endif"
                       maxlength="255" autocomplete="email">
                @if($errors->editStore->has('contact_email'))
                  <div class="invalid-feedback">{{ $errors->editStore->first('contact_email') }}</div>
                @endif
              </div>

<div class="col-12">
                <label class="form-label">Lokasi Store</label>

                <div id="mapEdit"></div>

                <small
                  id="edit_geohelp"
                  class="text-muted d-block mt-1 store-map-help">

                  Klik peta atau geser pin. Alamat dan kode pos akan diperbarui otomatis.
                </small>
              </div>

              <div class="col-12">
                <label for="edit_address" class="form-label">
                  Alamat <span class="text-danger">*</span>
                </label>

                <textarea
                  id="edit_address"
                  name="address"
                  rows="3"
                  class="form-control
                    @if ($errors->editStore->has('address'))
                      is-invalid
                    @endif"
                  required>{{
                    $errors->editStore->any()
                      ? old('address')
                      : ''
                  }}</textarea>

                <div class="store-feedback-slot">
                  @if ($errors->editStore->has('address'))
                    <div class="invalid-feedback">
                      {{ $errors->editStore->first('address') }}
                    </div>
                  @else
                    <div class="form-text">
                      Otomatis berubah ketika lokasi peta dipindahkan.
                    </div>
                  @endif
                </div>
              </div>

              <div class="col-md-6">
                <label for="edit_postal_code" class="form-label">
                  Kode Pos
                </label>

                <input
                  type="text"
                  id="edit_postal_code"
                  name="postal_code"
                  value="{{
                    $errors->editStore->any()
                      ? old('postal_code')
                      : ''
                  }}"
                  class="form-control
                    @if ($errors->editStore->has('postal_code'))
                      is-invalid
                    @endif"
                  maxlength="20"
                  placeholder="Terisi otomatis dari peta">

                <div class="store-feedback-slot">
                  @if ($errors->editStore->has('postal_code'))
                    <div class="invalid-feedback">
                      {{ $errors->editStore->first('postal_code') }}
                    </div>
                  @else
                    <div class="form-text">
                      Diperoleh dari lokasi peta dan tetap dapat diedit.
                    </div>
                  @endif
                </div>
              </div>

              <div class="col-md-6 d-flex align-items-end">
                <button
                  type="button"
                  class="btn btn-outline-primary w-100"
                  id="btnEditCurrentLocation">

                  <i class="mdi mdi-crosshairs-gps me-1"></i>
                  Gunakan Lokasi Saat Ini
                </button>
              </div>

              <div class="col-md-6 coordinate-group">
                <label for="edit_lat" class="form-label">
                  Latitude
                </label>

                <input
                  type="text"
                  id="edit_lat"
                  name="lat"
                  value="{{
                    $errors->editStore->any()
                      ? old('lat')
                      : ''
                  }}"
                  class="form-control"
                  readonly>
              </div>

              <div class="col-md-6 coordinate-group">
                <label for="edit_lng" class="form-label">
                  Longitude
                </label>

                <input
                  type="text"
                  id="edit_lng"
                  name="lng"
                  value="{{
                    $errors->editStore->any()
                      ? old('lng')
                      : ''
                  }}"
                  class="form-control"
                  readonly>
              </div>

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

@can('delete master-data-stores')
  <form
    id="formDeleteStore"
    method="POST"
    class="d-none">

    @csrf
    @method('DELETE')
  </form>
@endcan


{{-- ========================================================= --}}
{{-- POP-UP NOTIFIKASI STORE --}}
{{-- ========================================================= --}}
<div
  id="storeNotificationBackdrop"
  class="store-notification-backdrop"
  aria-hidden="true">

  <div
    id="storeNotification"
    class="store-notification success"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="storeNotificationTitle"
    aria-describedby="storeNotificationMessage">

    <div class="store-notification-body">
      <div class="store-notification-icon">
        <i id="storeNotificationIcon" class="mdi mdi-check"></i>
      </div>

      <h4
        id="storeNotificationTitle"
        class="store-notification-title">
        Berhasil
      </h4>

      <p
        id="storeNotificationMessage"
        class="store-notification-message"></p>
    </div>

    <div class="store-notification-footer text-center">
      <button
        type="button"
        id="storeNotificationClose"
        class="btn btn-primary">
        OK
      </button>
    </div>
  </div>
</div>

@endsection

@push('vendor-script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  'use strict';


  /*
  |--------------------------------------------------------------------------
  | Pop-up Notifikasi Store
  |--------------------------------------------------------------------------
  */

  const notificationBackdrop =
    document.getElementById('storeNotificationBackdrop');

  const notificationBox =
    document.getElementById('storeNotification');

  const notificationIcon =
    document.getElementById('storeNotificationIcon');

  const notificationTitle =
    document.getElementById('storeNotificationTitle');

  const notificationMessage =
    document.getElementById('storeNotificationMessage');

  const notificationClose =
    document.getElementById('storeNotificationClose');

  function showStoreNotification(type, title, message) {
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

    notificationIcon.className = isSuccess
      ? 'mdi mdi-check'
      : 'mdi mdi-alert-outline';

    notificationTitle.textContent =
      title || (isSuccess ? 'Berhasil' : 'Gagal');

    notificationMessage.textContent = message || '';

    notificationBackdrop.classList.add('show');
    notificationBackdrop.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    window.setTimeout(function () {
      notificationClose?.focus();
    }, 100);
  }

  function closeStoreNotification() {
    if (!notificationBackdrop) {
      return;
    }

    notificationBackdrop.classList.remove('show');
    notificationBackdrop.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  notificationClose?.addEventListener(
    'click',
    closeStoreNotification
  );

  notificationBackdrop?.addEventListener('click', function (event) {
    if (event.target === notificationBackdrop) {
      closeStoreNotification();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (
      event.key === 'Escape' &&
      notificationBackdrop?.classList.contains('show')
    ) {
      closeStoreNotification();
    }
  });

  const flashSuccess = @json(session('success'));
  const flashError = @json(session('error'));
  const generalValidationErrors = @json($errors->all());

  if (flashSuccess) {
    showStoreNotification('success', 'Berhasil', flashSuccess);
  } else if (flashError) {
    showStoreNotification('error', 'Gagal', flashError);
  } else if (
    generalValidationErrors.length > 0 &&
    !@json($errors->createStore->any()) &&
    !@json($errors->editStore->any())
  ) {
    showStoreNotification(
      'error',
      'Data Belum Valid',
      generalValidationErrors.join('\n')
    );
  }

  /*
  |--------------------------------------------------------------------------
  | Paksa tinggi area scroll di dalam modal
  |--------------------------------------------------------------------------
  | Mengukur tinggi header dan footer secara langsung agar modal-body
  | selalu memiliki area scroll, meskipun theme Bootstrap memiliki CSS lain.
  */

  function resizeStoreModal(modalElement) {
    if (!modalElement) {
      return;
    }

    const dialog = modalElement.querySelector('.store-modal-dialog');
    const content = modalElement.querySelector('.modal-content');
    const header = modalElement.querySelector('.modal-header');
    const body = modalElement.querySelector('.modal-body');
    const footer = modalElement.querySelector('.modal-footer');

    if (!dialog || !content || !header || !body || !footer) {
      return;
    }

    const outerGap = window.innerWidth <= 575.98 ? 10 : 20;
    const availableHeight = Math.max(
      window.innerHeight - outerGap,
      420
    );

    dialog.style.height = `${availableHeight}px`;
    dialog.style.maxHeight = `${availableHeight}px`;

    content.style.height = `${availableHeight}px`;
    content.style.maxHeight = `${availableHeight}px`;

    const bodyHeight = Math.max(
      availableHeight
        - header.getBoundingClientRect().height
        - footer.getBoundingClientRect().height,
      180
    );

    body.style.height = `${bodyHeight}px`;
    body.style.maxHeight = `${bodyHeight}px`;
    body.style.overflowY = 'auto';
    body.style.overflowX = 'hidden';
  }

  function resizeAllStoreModals() {
    resizeStoreModal(
      document.getElementById('modalCreateStore')
    );

    resizeStoreModal(
      document.getElementById('modalEditStore')
    );
  }

  window.addEventListener('resize', resizeAllStoreModals);
  window.addEventListener('orientationchange', resizeAllStoreModals);

  const defaultCenter = [-2.5489, 118.0149];
  const defaultZoom = 5;

  let mapCreate = null;
  let markerCreate = null;
  let mapEdit = null;
  let markerEdit = null;

  const createModal =
    document.getElementById('modalCreateStore');

  const editModal =
    document.getElementById('modalEditStore');

  const createForm =
    document.getElementById('formCreateStore');

  const editForm =
    document.getElementById('formEditStore');

  const deleteForm =
    document.getElementById('formDeleteStore');

  const createPhoneInput = document.getElementById('create_phone');
  const editPhoneInput = document.getElementById('edit_phone');
  const createImageInput = document.getElementById('create_image');
  const editImageInput = document.getElementById('edit_image');
  const createImagePreview = document.getElementById('create_image_preview');
  const editImagePreview = document.getElementById('edit_image_preview');
  const editCurrentImageUrlInput = document.getElementById('edit_current_image_url');

  function bindStoreImagePreview(input, preview) {
    if (!input || !preview) return;

    input.addEventListener('change', function () {
      const file = this.files && this.files[0];
      if (!file) return;

      if (!file.type || !file.type.startsWith('image/')) {
        this.value = '';
        showStoreNotification('error', 'File Tidak Valid', 'File yang dipilih harus berupa gambar.');
        return;
      }

      if (file.size > 2 * 1024 * 1024) {
        this.value = '';
        showStoreNotification('error', 'Ukuran Terlalu Besar', 'Ukuran gambar maksimal 2 MB.');
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

  bindStoreImagePreview(createImageInput, createImagePreview);
  bindStoreImagePreview(editImageInput, editImagePreview);


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
  | Helper
  |--------------------------------------------------------------------------
  */

  function debounce(callback, delay = 450) {
    let timeoutId;

    return function (...args) {
      window.clearTimeout(timeoutId);

      timeoutId = window.setTimeout(
        function () {
          callback(...args);
        },
        delay
      );
    };
  }

  function setHelp(mode, message, className = 'text-muted') {
    const element = document.getElementById(
      mode === 'create'
        ? 'create_geohelp'
        : 'edit_geohelp'
    );

    if (!element) {
      return;
    }

    element.className =
      `${className} d-block mt-1 store-map-help`;

    element.textContent = message;
  }

  function getFields(mode) {
    return {
      address: document.getElementById(
        mode === 'create'
          ? 'create_address'
          : 'edit_address'
      ),

      postalCode: document.getElementById(
        mode === 'create'
          ? 'create_postal_code'
          : 'edit_postal_code'
      ),

      lat: document.getElementById(
        mode === 'create'
          ? 'create_lat'
          : 'edit_lat'
      ),

      lng: document.getElementById(
        mode === 'create'
          ? 'create_lng'
          : 'edit_lng'
      ),
    };
  }

  function setLatLng(mode, lat, lng) {
    const fields = getFields(mode);

    if (fields.lat) {
      fields.lat.value = Number(lat).toFixed(7);
    }

    if (fields.lng) {
      fields.lng.value = Number(lng).toFixed(7);
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Reverse geocoding
  |--------------------------------------------------------------------------
  | Nominatim mengembalikan display_name dan address.postcode.
  |--------------------------------------------------------------------------
  */

  async function reverseGeocode(lat, lng) {
    const query = new URLSearchParams({
      lat: String(lat),
      lon: String(lng),
      format: 'jsonv2',
      zoom: '18',
      addressdetails: '1',
      'accept-language': 'id',
    });

    const response = await fetch(
      `https://nominatim.openstreetmap.org/reverse?${query.toString()}`,
      {
        method: 'GET',
        headers: {
          Accept: 'application/json',
        },
      }
    );

    if (!response.ok) {
      throw new Error(
        `Reverse geocoding gagal dengan status ${response.status}.`
      );
    }

    const data = await response.json();

    return {
      address:
        typeof data.display_name === 'string'
          ? data.display_name
          : '',

      postalCode:
        data.address && data.address.postcode
          ? String(data.address.postcode)
          : '',
    };
  }

  const fillLocationFields = debounce(
    async function (mode, lat, lng) {
      const fields = getFields(mode);

      setHelp(
        mode,
        'Mencari alamat dan kode pos dari lokasi peta...'
      );

      try {
        const result = await reverseGeocode(lat, lng);

        if (fields.address && result.address) {
          fields.address.value = result.address;
          fields.address.classList.remove('is-invalid');
        }

        /*
         * Kode pos selalu disinkronkan dengan hasil map.
         * Jika Nominatim tidak memiliki postcode, input dikosongkan.
         */
        if (fields.postalCode) {
          fields.postalCode.value = result.postalCode || '';
          fields.postalCode.classList.remove('is-invalid');
        }

        if (result.address && result.postalCode) {
          setHelp(
            mode,
            'Alamat dan kode pos berhasil terisi otomatis.',
            'text-success'
          );

          return;
        }

        if (result.address && !result.postalCode) {
          setHelp(
            mode,
            'Alamat berhasil ditemukan, tetapi kode pos tidak tersedia. Silakan isi kode pos secara manual.',
            'text-warning'
          );

          return;
        }

        setHelp(
          mode,
          'Alamat tidak ditemukan. Silakan isi alamat dan kode pos secara manual.',
          'text-warning'
        );
      } catch (error) {
        console.error(error);

        setHelp(
          mode,
          'Gagal mengambil alamat dan kode pos. Periksa koneksi internet atau isi secara manual.',
          'text-danger'
        );
      }
    },
    450
  );

  /*
  |--------------------------------------------------------------------------
  | Marker
  |--------------------------------------------------------------------------
  */

  function attachMarkerDrag(mode, marker) {
    marker.on('dragend', function (event) {
      const point = event.target.getLatLng();

      setLatLng(mode, point.lat, point.lng);
      fillLocationFields(mode, point.lat, point.lng);
    });
  }

  function placeMarker(
    mode,
    lat,
    lng,
    zoom = 17,
    shouldReverseGeocode = true
  ) {
    const map = mode === 'create'
      ? mapCreate
      : mapEdit;

    if (!map) {
      return;
    }

    const point = L.latLng(
      Number(lat),
      Number(lng)
    );

    map.setView(point, zoom);

    if (mode === 'create') {
      if (markerCreate) {
        markerCreate.remove();
      }

      markerCreate = L
        .marker(point, { draggable: true })
        .addTo(map);

      attachMarkerDrag('create', markerCreate);
    } else {
      if (markerEdit) {
        markerEdit.remove();
      }

      markerEdit = L
        .marker(point, { draggable: true })
        .addTo(map);

      attachMarkerDrag('edit', markerEdit);
    }

    setLatLng(mode, point.lat, point.lng);

    if (shouldReverseGeocode) {
      fillLocationFields(
        mode,
        point.lat,
        point.lng
      );
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Init maps
  |--------------------------------------------------------------------------
  */

  function createMap(elementId, mode) {
    const element = document.getElementById(elementId);

    if (!element) {
      return null;
    }

    const map = L
      .map(element)
      .setView(defaultCenter, defaultZoom);

    L.tileLayer(
      'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors',
      }
    ).addTo(map);

    map.attributionControl.setPrefix(false);

    map.on('click', function (event) {
      placeMarker(
        mode,
        event.latlng.lat,
        event.latlng.lng,
        17,
        true
      );
    });

    return map;
  }

  mapCreate = createMap('mapCreate', 'create');
  mapEdit = createMap('mapEdit', 'edit');

  /*
  |--------------------------------------------------------------------------
  | Browser location
  |--------------------------------------------------------------------------
  */

  function requestCurrentLocation(mode) {
    if (!navigator.geolocation) {
      setHelp(
        mode,
        'Browser tidak mendukung akses lokasi. Silakan pilih lokasi pada peta.',
        'text-warning'
      );

      return;
    }

    setHelp(
      mode,
      'Meminta izin lokasi dari browser...'
    );

    navigator.geolocation.getCurrentPosition(
      function (position) {
        placeMarker(
          mode,
          position.coords.latitude,
          position.coords.longitude,
          17,
          true
        );
      },
      function (error) {
        let message =
          'Gagal mengambil lokasi. Silakan pilih lokasi pada peta.';

        if (error.code === error.PERMISSION_DENIED) {
          message =
            'Izin lokasi ditolak. Silakan pilih lokasi pada peta.';
        } else if (error.code === error.POSITION_UNAVAILABLE) {
          message =
            'Lokasi tidak tersedia. Silakan pilih lokasi pada peta.';
        } else if (error.code === error.TIMEOUT) {
          message =
            'Permintaan lokasi timeout. Silakan pilih lokasi pada peta.';
        }

        setHelp(mode, message, 'text-warning');
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
      }
    );
  }

  document
    .getElementById('btnCreateCurrentLocation')
    ?.addEventListener('click', function () {
      requestCurrentLocation('create');
    });

  document
    .getElementById('btnEditCurrentLocation')
    ?.addEventListener('click', function () {
      requestCurrentLocation('edit');
    });

  /*
  |--------------------------------------------------------------------------
  | Modal create
  |--------------------------------------------------------------------------
  */

  createModal?.addEventListener(
    'shown.bs.modal',
    function () {
      resizeStoreModal(createModal);

      window.setTimeout(function () {
        resizeStoreModal(createModal);
        mapCreate?.invalidateSize();

        const fields = getFields('create');
        const lat = Number.parseFloat(fields.lat?.value || '');
        const lng = Number.parseFloat(fields.lng?.value || '');

        if (
          Number.isFinite(lat)
          && Number.isFinite(lng)
        ) {
          placeMarker(
            'create',
            lat,
            lng,
            16,
            !fields.address?.value
          );
        }
      }, 150);
    }
  );

  createModal?.addEventListener(
    'hidden.bs.modal',
    function () {
      if (!createForm) {
        return;
      }

      createForm.reset();
      createForm.classList.remove('was-validated');

      /*
       * Setelah validasi Laravel gagal, atribut value/defaultValue
       * dapat berisi old input. form.reset() akan mengembalikan nilai
       * tersebut, jadi field create perlu dikosongkan secara manual.
       */
      const createFieldIds = [
        'create_name',
        'create_address',
        'create_postal_code',
        'create_lat',
        'create_lng',
      ];

      createFieldIds.forEach(function (fieldId) {
        const field = document.getElementById(fieldId);

        if (!field) {
          return;
        }

        field.value = '';
        field.defaultValue = '';
        field.classList.remove('is-invalid');
      });

      createForm
        .querySelectorAll('.is-invalid')
        .forEach(function (field) {
          field.classList.remove('is-invalid');
        });

      const feedbackSlots =
        createForm.querySelectorAll('.store-feedback-slot');

      feedbackSlots.forEach(function (slot, index) {
        const messages = [
          'Maksimal 150 karakter.',
          'Kode dibuat otomatis saat disimpan.',
          'Terisi otomatis dari lokasi peta dan tetap dapat diedit.',
          'Diperoleh dari data reverse geocoding OpenStreetMap.',
        ];

        if (messages[index]) {
          slot.innerHTML = `
            <div class="form-text">
              ${messages[index]}
            </div>
          `;
        }
      });

      const submitButton =
        createForm.querySelector('button[type="submit"]');

      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = `
          <i class="mdi mdi-content-save-outline me-1"></i>
          Simpan
        `;
      }

      if (markerCreate) {
        markerCreate.remove();
        markerCreate = null;
      }

      mapCreate?.setView(defaultCenter, defaultZoom);
      mapCreate?.invalidateSize();

      setHelp(
        'create',
        'Klik peta atau geser pin. Alamat dan kode pos akan terisi otomatis.'
      );

      const modalBody =
        createModal.querySelector('.modal-body');

      if (modalBody) {
        modalBody.scrollTop = 0;
      }
    }
  );

  /*
  |--------------------------------------------------------------------------
  | Prefill edit
  |--------------------------------------------------------------------------
  */

  document
    .querySelectorAll('.btn-edit-store')
    .forEach(function (button) {
      button.addEventListener('click', function () {
        if (!editForm) {
          return;
        }

        const updateUrl =
          this.dataset.updateUrl || '#';

        editForm.action = updateUrl;

        document.getElementById('edit_id').value =
          this.dataset.id || '';

        document.getElementById('edit_update_url').value =
          updateUrl;

        document.getElementById('edit_name').value =
          this.dataset.name || '';

        document.getElementById('edit_phone').value =
          this.dataset.phone || '';

        document.getElementById('edit_contact_name').value =
          this.dataset.contactName || '';

        document.getElementById('edit_contact_phone').value =
          this.dataset.contactPhone || '';

        document.getElementById('edit_contact_email').value =
          this.dataset.contactEmail || '';

        document.getElementById('edit_address').value =
          this.dataset.address || '';

        document.getElementById('edit_postal_code').value =
          this.dataset.postalCode || '';

        document.getElementById('edit_lat').value =
          this.dataset.lat || '';

        document.getElementById('edit_lng').value =
          this.dataset.lng || '';
      });
    });

  editModal?.addEventListener(
    'shown.bs.modal',
    function () {
      resizeStoreModal(editModal);

      window.setTimeout(function () {
        resizeStoreModal(editModal);
        mapEdit?.invalidateSize();

        const fields = getFields('edit');
        const lat = Number.parseFloat(fields.lat?.value || '');
        const lng = Number.parseFloat(fields.lng?.value || '');

        if (
          Number.isFinite(lat)
          && Number.isFinite(lng)
        ) {
          /*
           * Saat membuka edit, jangan menimpa alamat dan kode pos
           * yang sudah tersimpan. Reverse geocode baru berjalan
           * ketika pengguna mengklik peta atau menggeser marker.
           */
          placeMarker(
            'edit',
            lat,
            lng,
            16,
            false
          );
        } else {
          mapEdit?.setView(defaultCenter, defaultZoom);
        }
      }, 150);
    }
  );

  /*
  |--------------------------------------------------------------------------
  | Submit
  |--------------------------------------------------------------------------
  */

  function bindSubmit(
    form,
    processingText
  ) {
    if (!form) {
      return;
    }

    form.addEventListener('submit', function (event) {
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
          aria-hidden="true">
        </span>
        ${processingText}
      `;
    });
  }

  bindSubmit(createForm, 'Menyimpan...');
  bindSubmit(editForm, 'Memperbarui...');

  /*
  |--------------------------------------------------------------------------
  | Alt + Enter
  |--------------------------------------------------------------------------
  */

  createModal?.addEventListener(
    'keydown',
    function (event) {
      if (event.altKey && event.key === 'Enter') {
        event.preventDefault();
        createForm?.requestSubmit();
      }
    }
  );

  editModal?.addEventListener(
    'keydown',
    function (event) {
      if (event.altKey && event.key === 'Enter') {
        event.preventDefault();
        editForm?.requestSubmit();
      }
    }
  );

  /*
  |--------------------------------------------------------------------------
  | Hapus
  |--------------------------------------------------------------------------
  */

  document
    .querySelectorAll('.btn-delete-store')
    .forEach(function (button) {
      button.addEventListener('click', function () {
        if (!deleteForm) {
          showStoreNotification(
            'error',
            'Gagal',
            'Form hapus tidak ditemukan.'
          );
          return;
        }

        const storeName =
          this.dataset.name || 'store ini';

        const deleteUrl =
          this.dataset.deleteUrl || '';

        if (!deleteUrl) {
          showStoreNotification(
            'error',
            'Gagal',
            'URL hapus tidak ditemukan.'
          );
          return;
        }

        if (
          !confirm(
            `Yakin ingin menghapus store "${storeName}"?`
          )
        ) {
          return;
        }

        deleteForm.action = deleteUrl;
        deleteForm.submit();
      });
    });

  /*
  |--------------------------------------------------------------------------
  | Reopen create validation
  |--------------------------------------------------------------------------
  */

  const hasCreateError =
    @json($errors->createStore->any());

  if (
    hasCreateError
    && createModal
    && typeof bootstrap !== 'undefined'
  ) {
    bootstrap.Modal
      .getOrCreateInstance(createModal)
      .show();

    window.setTimeout(function () {
      showStoreNotification(
        'error',
        'Gagal Menyimpan',
        @json($errors->createStore->all()).join('\n')
      );
    }, 300);
  }

  /*
  |--------------------------------------------------------------------------
  | Reopen edit validation
  |--------------------------------------------------------------------------
  */

  const hasEditError =
    @json($errors->editStore->any());

  if (
    hasEditError
    && editModal
    && editForm
    && typeof bootstrap !== 'undefined'
  ) {
    const oldUpdateUrl =
      @json(old('update_url'));

    if (oldUpdateUrl) {
      editForm.action = oldUpdateUrl;
    }

    bootstrap.Modal
      .getOrCreateInstance(editModal)
      .show();

    window.setTimeout(function () {
      showStoreNotification(
        'error',
        'Gagal Memperbarui',
        @json($errors->editStore->all()).join('\n')
      );
    }, 300);
  }
});
</script>
@endpush
