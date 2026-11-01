@extends('layouts/contentNavbarLayout')

@section('title', 'Master Partner - Customers')

@push('vendor-style')
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

<style>
  .table-responsive { overflow-x: auto; }
  .col-idx { width: 60px; }
  .col-aksi { width: 110px; text-align: center; }
  .select2-container { width: 100% !important; }

  .customer-notification-backdrop {
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

  .customer-notification-backdrop.show { display: flex; }

  .customer-notification {
    width: min(440px, 100%);
    overflow: hidden;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
    animation: customerPopupIn .22s ease-out;
  }

  .customer-notification-body {
    padding: 2rem 1.75rem 1.35rem;
    text-align: center;
  }

  .customer-notification-icon {
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

  .customer-notification.success .customer-notification-icon { background: #28a745; }
  .customer-notification.error .customer-notification-icon { background: #dc3545; }
  .customer-notification.warning .customer-notification-icon { background: #f59e0b; }

  .customer-notification-title {
    margin-bottom: .65rem;
    font-size: 1.45rem;
    font-weight: 700;
  }

  .customer-notification-message {
    margin: 0;
    color: #5d6470;
    font-size: 1rem;
    line-height: 1.6;
    white-space: pre-line;
  }

  .customer-notification-footer {
    display: flex;
    justify-content: center;
    gap: .75rem;
    padding: 0 1.75rem 1.75rem;
  }

  .customer-notification-footer .btn {
    min-width: 120px;
    font-weight: 600;
  }

  @keyframes customerPopupIn {
    from { opacity: 0; transform: translateY(12px) scale(.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }


  #modalCreateCustomer .modal-dialog,
  #modalEditCustomer .modal-dialog {
    margin-top: 1rem;
    margin-bottom: 1rem;
  }

  #modalCreateCustomer .modal-content,
  #modalEditCustomer .modal-content {
    height: calc(100vh - 2rem);
    height: calc(100dvh - 2rem);
    max-height: calc(100vh - 2rem);
    max-height: calc(100dvh - 2rem);
    overflow: hidden;
  }

  #modalCreateCustomer .modal-content > form,
  #modalEditCustomer .modal-content > form {
    height: 100%;
    min-height: 0;
    display: grid;
    grid-template-rows: auto minmax(0, 1fr) auto;
  }

  #modalCreateCustomer .modal-header,
  #modalEditCustomer .modal-header,
  #modalCreateCustomer .modal-footer,
  #modalEditCustomer .modal-footer {
    flex-shrink: 0;
    background: #fff;
    position: relative;
    z-index: 2;
  }

  #modalCreateCustomer .modal-body,
  #modalEditCustomer .modal-body {
    height: auto;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: scroll !important;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 1.25rem;
  }

  @media (max-height: 720px) {
    #modalCreateCustomer .modal-dialog,
    #modalEditCustomer .modal-dialog {
      margin-top: .5rem;
      margin-bottom: .5rem;
    }

    #modalCreateCustomer .modal-content,
    #modalEditCustomer .modal-content {
      height: calc(100vh - 1rem);
      height: calc(100dvh - 1rem);
      max-height: calc(100vh - 1rem);
      max-height: calc(100dvh - 1rem);
    }

    #modalCreateCustomer .modal-header,
    #modalEditCustomer .modal-header {
      padding-top: .85rem;
      padding-bottom: .85rem;
    }

    #modalCreateCustomer .modal-footer,
    #modalEditCustomer .modal-footer {
      padding-top: .75rem;
      padding-bottom: .75rem;
    }
  }

</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master Partner /</span>
    Customers
  </h4>

  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Customers</span>

      <div class="d-flex gap-2">
        <form method="GET"
              action="{{ route('master-data.customers.index') }}"
              class="d-flex gap-2">
          <input class="form-control form-control-sm"
                 type="text"
                 name="q"
                 value="{{ $q ?? '' }}"
                 placeholder="Cari nama/email/WA...">

          <button type="submit"
                  class="btn btn-outline-secondary btn-sm"
                  data-bs-toggle="tooltip"
                  title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>

          @if(($q ?? '') !== '')
            <a href="{{ route('master-data.customers.index') }}"
               class="btn btn-outline-secondary btn-sm"
               title="Reset">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-customer')
          <button type="button"
                  class="btn btn-primary btn-sm"
                  data-bs-toggle="modal"
                  data-bs-target="#modalCreateCustomer">
            <i class="mdi mdi-plus me-1"></i>
            Tambah Customer
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
            <th>Alamat</th>
            <th>Wilayah</th>
            <th>Email</th>
            <th>Date of Birth</th>
            <th>WA Number</th>
            <th>Gender</th>
            <th>Store Registrasi</th>
            <th>Channel</th>
            <th>Referral Code</th>
            <th>Guest</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>

        <tbody class="table-border-bottom-0">
          @forelse($customers as $i => $customer)
            <tr>
              <td>
                {{
                  ($customers->currentPage() - 1)
                  * $customers->perPage()
                  + $i + 1
                }}
              </td>

              <td class="fw-medium">
                <i class="mdi mdi-account-outline mdi-20px text-primary me-2"></i>
                {{ $customer->name }}
              </td>

              <td>{{ $customer->address ?: '-' }}</td>
              <td>
                @if($customer->village || $customer->district || $customer->regency || $customer->province)
                  <div class="fw-medium">
                    {{ $customer->village?->name ?: '-' }},
                    {{ $customer->district?->name ?: '-' }}
                  </div>
                  <small class="text-muted">
                    {{ $customer->regency?->name ?: '-' }},
                    {{ $customer->province?->name ?: '-' }}
                    @if($customer->postal_code)
                      · {{ $customer->postal_code }}
                    @endif
                  </small>
                @else
                  <span class="badge bg-label-warning">Belum lengkap</span>
                @endif
              </td>
              <td>{{ $customer->email ?: '-' }}</td>

              <td>
                {{
                  $customer->date_of_birth
                    ? $customer->date_of_birth->format('d M Y')
                    : '-'
                }}
              </td>

              <td>
                <span class="badge bg-label-primary">
                  {{ $customer->wa_number }}
                </span>
              </td>

              <td>
                {{ $genders[$customer->gender] ?? ($customer->gender ?: '-') }}
              </td>

              <td>
                @php
                  $sourceStore = $registrationSourceMap->get(
                    (string) $customer->registration_source_id
                  );
                @endphp

                @if($sourceStore)
                  <div class="fw-medium">
                    {{ $sourceStore->name }}
                  </div>

                  <small class="text-muted">
                    {{ $sourceStore->code }}
                  </small>
                @elseif($customer->registration_source_id)
                  <span class="badge bg-label-secondary">
                    {{ $customer->registration_source_id }}
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>

              <td>
                <span class="badge bg-label-info">
                  {{ $customer->registration_channel ?: '-' }}
                </span>
              </td>

              <td>
                @if($customer->referral_code)
                  <span class="badge bg-label-dark"
                        style="font-family: ui-monospace, Menlo, monospace;">
                    {{ $customer->referral_code }}
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>

              <td>
                @if($customer->is_guest)
                  <span class="badge bg-label-warning">Guest</span>
                @else
                  <span class="badge bg-label-secondary">Customer</span>
                @endif
              </td>

              <td class="text-center">
                @can('update master-customer')
                  <button type="button"
                          class="btn btn-sm btn-outline-warning btn-edit-customer"
                          data-id="{{ $customer->id }}"
                          data-name="{{ $customer->name }}"
                          data-address="{{ $customer->address }}"
                          data-province-code="{{ $customer->province_code }}"
                          data-regency-code="{{ $customer->regency_code }}"
                          data-district-code="{{ $customer->district_code }}"
                          data-village-code="{{ $customer->village_code }}"
                          data-postal-code="{{ $customer->postal_code }}"
                          data-email="{{ $customer->email }}"
                          data-wa-number="{{ $customer->wa_number }}"
                          data-gender="{{ $customer->gender }}"
                          data-date-of-birth="{{ $customer->date_of_birth?->format('Y-m-d') }}"
                          data-registration-source-id="{{ $customer->registration_source_id }}"
                           data-is-guest="{{ $customer->is_guest ? 1 : 0 }}"
                          data-update-url="{{ route('master-data.customers.update', $customer->id) }}"
                          data-bs-toggle="modal"
                          data-bs-target="#modalEditCustomer">
                    <i class="mdi mdi-pencil-outline me-1"></i>
                    Edit
                  </button>
                @endcan
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="13"
                  class="text-center text-muted py-4">
                Belum ada data customer.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-end">
      {{ $customers->appends(['q' => $q ?? null])->links() }}
    </div>
  </div>
</div>

@can('create master-customer')
<div class="modal fade"
     id="modalCreateCustomer"
     tabindex="-1"
     aria-labelledby="modalCreateCustomerLabel"
     aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formCreateCustomer"
            method="POST"
            action="{{ route('master-data.customers.store') }}">
        @csrf

        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <div>
            <h5 class="modal-title"
                id="modalCreateCustomerLabel">
              <i class="mdi mdi-account-plus-outline me-1"></i>
              Tambah Customer
            </h5>
            <small class="text-muted">
              Registration Channel otomatis backoffice.
            </small>
          </div>

          <button type="button"
                  class="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"></button>
        </div>

        <div class="modal-body">
<div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">
                Nama <span class="text-danger">*</span>
              </label>
              <input type="text"
                     name="name"
                     value="{{ old('_from') === 'create' ? old('name') : '' }}"
                     class="form-control @error('name') is-invalid @enderror"
                     required>
              @error('name')
                @if(old('_from') === 'create')
                  <div class="invalid-feedback">{{ $message }}</div>
                @endif
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                Nomor WhatsApp <span class="text-danger">*</span>
              </label>
              <input type="text"
                     name="wa_number"
                     value="{{ old('_from') === 'create' ? old('wa_number') : '' }}"
                     class="form-control js-wa-number @error('wa_number') is-invalid @enderror"
                     placeholder="081234567890"
                     required>
              @error('wa_number')
                @if(old('_from') === 'create')
                  <div class="invalid-feedback">{{ $message }}</div>
                @endif
              @enderror
              <div class="form-text">
                Nomor disimpan dalam format +62.
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email"
                     name="email"
                     value="{{ old('_from') === 'create' ? old('email') : '' }}"
                     class="form-control @error('email') is-invalid @enderror">
              @error('email')
                @if(old('_from') === 'create')
                  <div class="invalid-feedback">{{ $message }}</div>
                @endif
              @enderror
            </div>

            <div class="col-md-3">
              <label class="form-label">Gender <span class="text-danger">*</span></label>
              <select name="gender"
                      required
                      aria-required="true"
                      class="form-select @error('gender') is-invalid @enderror">
                <option value="">- Pilih -</option>
                @foreach($genders as $value => $label)
                  <option value="{{ $value }}"
                          @selected(old('_from') === 'create' && old('gender') === $value)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Tanggal Lahir</label>
              <input type="date"
                     name="date_of_birth"
                     value="{{ old('_from') === 'create' ? old('date_of_birth') : '' }}"
                     max="{{ now()->format('Y-m-d') }}"
                     class="form-control @error('date_of_birth') is-invalid @enderror">
            </div>

            <div class="col-12">
              <label class="form-label">Alamat</label>
              <textarea name="address"
                        rows="3"
                        class="form-control @error('address') is-invalid @enderror"
                        placeholder="Alamat customer">{{ old('_from') === 'create' ? old('address') : '' }}</textarea>
            </div>

            <div class="col-12">
              <div class="border rounded p-3">
                <div class="fw-semibold mb-3">
                  <i class="mdi mdi-map-marker-outline me-1 text-primary"></i>
                  Wilayah Customer
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Provinsi</label>
                    <select id="create_province_code" name="province_code"
                            class="form-select js-region-select @error('province_code') is-invalid @enderror"
                            data-placeholder="- Pilih Provinsi -">
                      <option value="">- Pilih Provinsi -</option>
                      @foreach($provinces as $province)
                        <option value="{{ $province->code }}"
                                @selected(old('_from') === 'create' && old('province_code') === $province->code)>
                          {{ $province->name }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Kabupaten/Kota</label>
                    <select id="create_regency_code" name="regency_code"
                            class="form-select js-region-select @error('regency_code') is-invalid @enderror"
                            data-placeholder="- Pilih Kabupaten/Kota -" disabled>
                      <option value="">- Pilih Kabupaten/Kota -</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Kecamatan</label>
                    <select id="create_district_code" name="district_code"
                            class="form-select js-region-select @error('district_code') is-invalid @enderror"
                            data-placeholder="- Pilih Kecamatan -" disabled>
                      <option value="">- Pilih Kecamatan -</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Kelurahan/Desa</label>
                    <select id="create_village_code" name="village_code"
                            class="form-select js-region-select @error('village_code') is-invalid @enderror"
                            data-placeholder="- Pilih Kelurahan/Desa -" disabled>
                      <option value="">- Pilih Kelurahan/Desa -</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" id="create_postal_code" name="postal_code"
                           value="{{ old('_from') === 'create' ? old('postal_code') : '' }}"
                           maxlength="50" class="form-control @error('postal_code') is-invalid @enderror"
                           placeholder="Contoh: 40115">
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-8">
              <label class="form-label">
                Store Registrasi <span class="text-danger">*</span>
              </label>

              <select id="create_registration_source_id"
                      name="registration_source_id"
                      class="form-select js-store-select @error('registration_source_id') is-invalid @enderror"
                      required>
                <option value="">- Pilih Store -</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}"
                          @selected(old('_from') === 'create' && old('registration_source_id') == $store->id)>
                    {{ $store->name }} ({{ $store->code }})
                  </option>
                @endforeach
              </select>

              @error('registration_source_id')
                @if(old('_from') === 'create')
                  <div class="invalid-feedback d-block">
                    {{ $message }}
                  </div>
                @endif
              @enderror

              <div class="form-text">
                ID Store disimpan ke registration_source_id.
              </div>
            </div>

            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check form-switch mb-2">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_guest"
                       id="create_is_guest"
                       value="1"
                       @checked(old('_from') === 'create' && old('is_guest'))>
                <label class="form-check-label" for="create_is_guest">
                  Data Guest
                </label>
                <div class="form-text">
                  Setiap cabang hanya boleh memiliki 1 data guest.
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">
                Registration Channel
              </label>
              <input type="text"
                     class="form-control"
                     value="backoffice"
                     readonly>
              <div class="form-text">
                Nilai yang disimpan ke enum app_type adalah backoffice.
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button"
                  class="btn btn-outline-secondary"
                  data-bs-dismiss="modal">
            Batal
          </button>
          <button type="submit"
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

@can('update master-customer')
<div class="modal fade"
     id="modalEditCustomer"
     tabindex="-1"
     aria-labelledby="modalEditCustomerLabel"
     aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formEditCustomer"
            method="POST"
            action="#">
        @csrf
        @method('PUT')

        <input type="hidden" name="_from" value="edit">
        <input type="hidden"
               id="edit_id"
               name="id"
               value="{{ old('_from') === 'edit' ? old('id') : '' }}">
        <input type="hidden"
               id="edit_update_url"
               name="update_url"
               value="{{ old('_from') === 'edit' ? old('update_url') : '' }}">
        <div class="modal-header">
          <div>
            <h5 class="modal-title"
                id="modalEditCustomerLabel">
              <i class="mdi mdi-account-edit-outline me-1"></i>
              Edit Customer
            </h5>
            <small class="text-muted">
              Registration Channel otomatis backoffice.
            </small>
          </div>

          <button type="button"
                  class="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"></button>
        </div>

        <div class="modal-body">
<div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">
                Nama <span class="text-danger">*</span>
              </label>
              <input type="text"
                     id="edit_name"
                     name="name"
                     value="{{ old('_from') === 'edit' ? old('name') : '' }}"
                     class="form-control @error('name') is-invalid @enderror"
                     required>
            </div>

            <div class="col-md-6">
              <label class="form-label">
                Nomor WhatsApp <span class="text-danger">*</span>
              </label>
              <input type="text"
                     id="edit_wa_number"
                     name="wa_number"
                     value="{{ old('_from') === 'edit' ? old('wa_number') : '' }}"
                     class="form-control js-wa-number @error('wa_number') is-invalid @enderror"
                     required>
              <div class="form-text">
                Nomor disimpan dalam format +62.
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email"
                     id="edit_email"
                     name="email"
                     value="{{ old('_from') === 'edit' ? old('email') : '' }}"
                     class="form-control @error('email') is-invalid @enderror">
            </div>

            <div class="col-md-3">
              <label class="form-label">Gender <span class="text-danger">*</span></label>
              <select id="edit_gender"
                      name="gender"
                      required
                      aria-required="true"
                      class="form-select @error('gender') is-invalid @enderror">
                <option value="">- Pilih -</option>
                @foreach($genders as $value => $label)
                  <option value="{{ $value }}">
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Tanggal Lahir</label>
              <input type="date"
                     id="edit_date_of_birth"
                     name="date_of_birth"
                     value="{{ old('_from') === 'edit' ? old('date_of_birth') : '' }}"
                     max="{{ now()->format('Y-m-d') }}"
                     class="form-control @error('date_of_birth') is-invalid @enderror">
            </div>

            <div class="col-12">
              <label class="form-label">Alamat</label>
              <textarea id="edit_address"
                        name="address"
                        rows="3"
                        class="form-control @error('address') is-invalid @enderror"
                        placeholder="Alamat customer">{{ old('_from') === 'edit' ? old('address') : '' }}</textarea>
            </div>

            <div class="col-12">
              <div class="border rounded p-3">
                <div class="fw-semibold mb-3">
                  <i class="mdi mdi-map-marker-outline me-1 text-primary"></i>
                  Wilayah Customer
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Provinsi</label>
                    <select id="edit_province_code" name="province_code"
                            class="form-select js-region-select @error('province_code') is-invalid @enderror"
                            data-placeholder="- Pilih Provinsi -">
                      <option value="">- Pilih Provinsi -</option>
                      @foreach($provinces as $province)
                        <option value="{{ $province->code }}">{{ $province->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Kabupaten/Kota</label>
                    <select id="edit_regency_code" name="regency_code"
                            class="form-select js-region-select @error('regency_code') is-invalid @enderror"
                            data-placeholder="- Pilih Kabupaten/Kota -" disabled>
                      <option value="">- Pilih Kabupaten/Kota -</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Kecamatan</label>
                    <select id="edit_district_code" name="district_code"
                            class="form-select js-region-select @error('district_code') is-invalid @enderror"
                            data-placeholder="- Pilih Kecamatan -" disabled>
                      <option value="">- Pilih Kecamatan -</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Kelurahan/Desa</label>
                    <select id="edit_village_code" name="village_code"
                            class="form-select js-region-select @error('village_code') is-invalid @enderror"
                            data-placeholder="- Pilih Kelurahan/Desa -" disabled>
                      <option value="">- Pilih Kelurahan/Desa -</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" id="edit_postal_code" name="postal_code"
                           value="{{ old('_from') === 'edit' ? old('postal_code') : '' }}"
                           maxlength="50" class="form-control @error('postal_code') is-invalid @enderror"
                           placeholder="Contoh: 40115">
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-8">
              <label class="form-label">
                Store Registrasi <span class="text-danger">*</span>
              </label>

              <select id="edit_registration_source_id"
                      name="registration_source_id"
                      class="form-select js-store-select @error('registration_source_id') is-invalid @enderror"
                      required>
                <option value="">- Pilih Store -</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}">
                    {{ $store->name }} ({{ $store->code }})
                  </option>
                @endforeach
              </select>

              @error('registration_source_id')
                @if(old('_from') === 'edit')
                  <div class="invalid-feedback d-block">
                    {{ $message }}
                  </div>
                @endif
              @enderror

              <div class="form-text">
                ID Store disimpan ke registration_source_id.
              </div>
            </div>

            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check form-switch mb-2">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_guest"
                       id="edit_is_guest"
                       value="1"
                       @checked(old('_from') === 'edit' && old('is_guest'))>
                <label class="form-check-label" for="edit_is_guest">
                  Data Guest
                </label>
                <div class="form-text">
                  Setiap cabang hanya boleh memiliki 1 data guest.
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">
                Registration Channel
              </label>
              <input type="text"
                     class="form-control"
                     value="backoffice"
                     readonly>
              <div class="form-text">
                Nilai yang disimpan ke enum app_type adalah backoffice.
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button"
                  class="btn btn-outline-secondary"
                  data-bs-dismiss="modal">
            Batal
          </button>
          <button type="submit"
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

{{-- ================= POP-UP NOTIFIKASI ================= --}}
<div id="customerNotificationBackdrop"
     class="customer-notification-backdrop"
     aria-hidden="true">
  <div id="customerNotification"
       class="customer-notification success"
       role="alertdialog"
       aria-modal="true"
       aria-labelledby="customerNotificationTitle"
       aria-describedby="customerNotificationMessage">
    <div class="customer-notification-body">
      <div class="customer-notification-icon">
        <i id="customerNotificationIcon" class="mdi mdi-check"></i>
      </div>

      <h4 id="customerNotificationTitle"
          class="customer-notification-title">
        Berhasil
      </h4>

      <p id="customerNotificationMessage"
         class="customer-notification-message"></p>
    </div>

    <div class="customer-notification-footer">
      <button type="button"
              id="customerNotificationConfirm"
              class="btn btn-primary">
        OK
      </button>
    </div>
  </div>
</div>

@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  'use strict';


  const notificationBackdrop =
    document.getElementById('customerNotificationBackdrop');
  const notificationBox =
    document.getElementById('customerNotification');
  const notificationIcon =
    document.getElementById('customerNotificationIcon');
  const notificationTitle =
    document.getElementById('customerNotificationTitle');
  const notificationMessage =
    document.getElementById('customerNotificationMessage');
  const notificationConfirm =
    document.getElementById('customerNotificationConfirm');

  function showCustomerNotification(type, title, message) {
    if (
      !notificationBackdrop ||
      !notificationBox ||
      !notificationIcon ||
      !notificationTitle ||
      !notificationMessage ||
      !notificationConfirm
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

    notificationBackdrop.classList.add('show');
    notificationBackdrop.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    setTimeout(function () {
      notificationConfirm.focus();
    }, 100);
  }

  function closeCustomerNotification() {
    notificationBackdrop?.classList.remove('show');
    notificationBackdrop?.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  notificationConfirm?.addEventListener(
    'click',
    closeCustomerNotification
  );

  notificationBackdrop?.addEventListener('click', function (event) {
    if (event.target === notificationBackdrop) {
      closeCustomerNotification();
    }
  });

  const flashSuccess = @json(session('success'));
  const flashError = @json(session('error'));
  const validationErrors = @json($errors->all());
  const validationSource = @json(old('_from'));

  if (flashSuccess) {
    showCustomerNotification('success', 'Berhasil', flashSuccess);
  } else if (flashError) {
    showCustomerNotification('error', 'Gagal', flashError);
  } else if (
    validationErrors.length > 0 &&
    validationSource === null
  ) {
    showCustomerNotification(
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

  function initStoreSelect(selector, modalSelector) {
    const $element = $(selector);

    if (!$element.length) {
      return;
    }

    $element.select2({
      theme: 'bootstrap-5',
      width: '100%',
      placeholder: '- Pilih Store -',
      allowClear: true,
      dropdownParent: $(modalSelector),
    });
  }

  initStoreSelect(
    '#create_registration_source_id',
    '#modalCreateCustomer'
  );

  initStoreSelect(
    '#edit_registration_source_id',
    '#modalEditCustomer'
  );

  const regionUrls = {
    regencies: @json(route('master-data.customers.regencies')),
    districts: @json(route('master-data.customers.districts')),
    villages: @json(route('master-data.customers.villages')),
  };

  function initRegionSelects(modalSelector) {
    $(modalSelector)
      .find('.js-region-select')
      .each(function () {
        const $select = $(this);

        $select.select2({
          theme: 'bootstrap-5',
          width: '100%',
          placeholder: $select.data('placeholder'),
          allowClear: true,
          dropdownParent: $(modalSelector),
        });
      });
  }

  initRegionSelects('#modalCreateCustomer');
  initRegionSelects('#modalEditCustomer');

  function resetRegionSelect(element, placeholder) {
    const $element = $(element);
    $element
      .empty()
      .append(new Option(placeholder, ''))
      .prop('disabled', true)
      .val('')
      .trigger('change.select2');
  }

  async function fillRegionSelect(
    element,
    url,
    parameter,
    parentCode,
    selectedCode,
    placeholder
  ) {
    resetRegionSelect(element, placeholder);

    if (!parentCode) {
      return;
    }

    const $element = $(element);

    try {
      const response = await fetch(
        `${url}?${new URLSearchParams({ [parameter]: parentCode })}`,
        {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        }
      );

      if (!response.ok) {
        throw new Error('Gagal mengambil data wilayah.');
      }

      const payload = await response.json();

      (payload.results || []).forEach(function (row) {
        $element.append(new Option(row.text, row.id));
      });

      $element
        .prop('disabled', false)
        .val(selectedCode || '')
        .trigger('change.select2');
    } catch (error) {
      resetRegionSelect(element, placeholder);
      showCustomerNotification(
        'error',
        'Data Wilayah Gagal Dimuat',
        error.message
      );
    }
  }

  function regionElements(prefix) {
    return {
      province: document.getElementById(`${prefix}_province_code`),
      regency: document.getElementById(`${prefix}_regency_code`),
      district: document.getElementById(`${prefix}_district_code`),
      village: document.getElementById(`${prefix}_village_code`),
    };
  }

  function bindRegionCascade(prefix) {
    const fields = regionElements(prefix);

    $(fields.province).on('change.customerRegion', async function () {
      resetRegionSelect(fields.district, '- Pilih Kecamatan -');
      resetRegionSelect(fields.village, '- Pilih Kelurahan/Desa -');
      await fillRegionSelect(
        fields.regency,
        regionUrls.regencies,
        'province_code',
        this.value,
        '',
        '- Pilih Kabupaten/Kota -'
      );
    });

    $(fields.regency).on('change.customerRegion', async function () {
      resetRegionSelect(fields.village, '- Pilih Kelurahan/Desa -');
      await fillRegionSelect(
        fields.district,
        regionUrls.districts,
        'regency_code',
        this.value,
        '',
        '- Pilih Kecamatan -'
      );
    });

    $(fields.district).on('change.customerRegion', async function () {
      await fillRegionSelect(
        fields.village,
        regionUrls.villages,
        'district_code',
        this.value,
        '',
        '- Pilih Kelurahan/Desa -'
      );
    });
  }

  bindRegionCascade('create');
  bindRegionCascade('edit');

  async function hydrateRegionCascade(prefix, values) {
    const fields = regionElements(prefix);

    $(fields.province)
      .val(values.province || '')
      .trigger('change.select2');

    await fillRegionSelect(
      fields.regency,
      regionUrls.regencies,
      'province_code',
      values.province,
      values.regency,
      '- Pilih Kabupaten/Kota -'
    );

    await fillRegionSelect(
      fields.district,
      regionUrls.districts,
      'regency_code',
      values.regency,
      values.district,
      '- Pilih Kecamatan -'
    );

    await fillRegionSelect(
      fields.village,
      regionUrls.villages,
      'district_code',
      values.district,
      values.village,
      '- Pilih Kelurahan/Desa -'
    );
  }

  function normalizeWa(value) {
    let digits = String(value || '')
      .replace(/\D+/g, '');

    while (digits.startsWith('62')) {
      digits = digits.slice(2);
    }

    while (digits.startsWith('0')) {
      digits = digits.slice(1);
    }

    return digits ? '+62' + digits : '';
  }

  document
    .querySelectorAll('.js-wa-number')
    .forEach(function (input) {
      input.addEventListener('blur', function () {
        this.value = normalizeWa(this.value);
      });
    });

  const editForm =
    document.getElementById('formEditCustomer');

  document
    .querySelectorAll('.btn-edit-customer')
    .forEach(function (button) {
      button.addEventListener('click', function () {
        const updateUrl =
          this.dataset.updateUrl || '#';

        editForm.action = updateUrl;

        document.getElementById('edit_id').value =
          this.dataset.id || '';

        document.getElementById('edit_update_url').value =
          updateUrl;

        document.getElementById('edit_name').value =
          this.dataset.name || '';

        document.getElementById('edit_address').value =
          this.dataset.address || '';

        document.getElementById('edit_email').value =
          this.dataset.email || '';

        document.getElementById('edit_wa_number').value =
          this.dataset.waNumber || '';

        document.getElementById('edit_gender').value =
          this.dataset.gender || '';

        document.getElementById('edit_date_of_birth').value =
          this.dataset.dateOfBirth || '';

        document.getElementById('edit_postal_code').value =
          this.dataset.postalCode || '';

        hydrateRegionCascade('edit', {
          province: this.dataset.provinceCode || '',
          regency: this.dataset.regencyCode || '',
          district: this.dataset.districtCode || '',
          village: this.dataset.villageCode || '',
        });

        $('#edit_registration_source_id')
          .val(
            this.dataset.registrationSourceId || ''
          )
          .trigger('change');

        const editIsGuest =
          document.getElementById('edit_is_guest');

        if (editIsGuest) {
          editIsGuest.checked =
            this.dataset.isGuest === '1';
        }

        setTimeout(function () {
          document.getElementById('edit_name')?.focus();
        }, 150);
      });
    });

  const createModal =
    document.getElementById('modalCreateCustomer');

  const createForm =
    document.getElementById('formCreateCustomer');

  createModal?.addEventListener(
    'hidden.bs.modal',
    function () {
      if (!createForm) {
        return;
      }

      createForm.reset();
      createForm.classList.remove('was-validated');

      createForm
        .querySelectorAll(
          'input:not([type="hidden"]), textarea'
        )
        .forEach(function (field) {
          field.value = '';
          field.defaultValue = '';
          field.classList.remove('is-invalid');
        });

      $('#create_registration_source_id')
        .val('')
        .trigger('change');

      $('#create_province_code')
        .val('')
        .trigger('change.select2');
      resetRegionSelect(
        document.getElementById('create_regency_code'),
        '- Pilih Kabupaten/Kota -'
      );
      resetRegionSelect(
        document.getElementById('create_district_code'),
        '- Pilih Kecamatan -'
      );
      resetRegionSelect(
        document.getElementById('create_village_code'),
        '- Pilih Kelurahan/Desa -'
      );

      const createIsGuest =
        document.getElementById('create_is_guest');

      if (createIsGuest) {
        createIsGuest.checked = false;
      }

      createForm
        .querySelectorAll(
          '.invalid-feedback, .alert-danger'
        )
        .forEach(function (element) {
          element.remove();
        });
    }
  );

  const editModal =
    document.getElementById('modalEditCustomer');

  const reopened = @json(old('_from'));

  if (reopened === 'create') {
    hydrateRegionCascade('create', {
      province: @json(old('province_code')),
      regency: @json(old('regency_code')),
      district: @json(old('district_code')),
      village: @json(old('village_code')),
    });

    bootstrap.Modal
      .getOrCreateInstance(createModal)
      .show();

    setTimeout(function () {
      if (validationErrors.length > 0) {
        showCustomerNotification(
          'error',
          'Gagal Menyimpan',
          validationErrors.join('\n')
        );
      }
    }, 250);
  } else if (reopened === 'edit') {
    const oldUpdateUrl =
      @json(old('update_url'));

    if (oldUpdateUrl && editForm) {
      editForm.action = oldUpdateUrl;
      document.getElementById(
        'edit_update_url'
      ).value = oldUpdateUrl;
    }

    $('#edit_registration_source_id')
      .val(@json(old('registration_source_id')))
      .trigger('change');

    document.getElementById('edit_gender').value =
      @json(old('gender'));

    hydrateRegionCascade('edit', {
      province: @json(old('province_code')),
      regency: @json(old('regency_code')),
      district: @json(old('district_code')),
      village: @json(old('village_code')),
    });

    const editIsGuest =
      document.getElementById('edit_is_guest');

    if (editIsGuest) {
      editIsGuest.checked =
        @json((bool) old('is_guest'));
    }

    bootstrap.Modal
      .getOrCreateInstance(editModal)
      .show();

    setTimeout(function () {
      if (validationErrors.length > 0) {
        showCustomerNotification(
          'error',
          'Gagal Memperbarui',
          validationErrors.join('\n')
        );
      }
    }, 250);
  }
});
</script>
@endpush
