@extends('layouts/contentNavbarLayout')

@section('title','Master Promo & Discount - Diskons')

@push('vendor-style')
<style>
  .table-responsive { overflow-x:auto }
  .col-idx{width:70px}
  .col-aksi{width:120px;text-align:center}

  .discount-notification-backdrop {
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

  .discount-notification-backdrop.show {
    display: flex;
  }

  .discount-notification {
    width: min(440px, 100%);
    overflow: hidden;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
    animation: discountPopupIn .22s ease-out;
  }

  .discount-notification-body {
    padding: 2rem 1.75rem 1.35rem;
    text-align: center;
  }

  .discount-notification-icon {
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

  .discount-notification.success .discount-notification-icon {
    background: #28a745;
  }

  .discount-notification.error .discount-notification-icon {
    background: #dc3545;
  }

  .discount-notification.warning .discount-notification-icon {
    background: #f59e0b;
  }

  .discount-notification-title {
    margin-bottom: .65rem;
    font-size: 1.45rem;
    font-weight: 700;
  }

  .discount-notification-message {
    margin: 0;
    color: #5d6470;
    font-size: 1rem;
    line-height: 1.6;
    white-space: pre-line;
  }

  .discount-notification-footer {
    display: flex;
    justify-content: center;
    gap: .75rem;
    padding: 0 1.75rem 1.75rem;
  }

  .discount-notification-footer .btn {
    min-width: 120px;
    font-weight: 600;
  }

  @keyframes discountPopupIn {
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
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">Master Promo & Discount /</span> Diskons</h4>

  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Diskon</span>
      <div class="d-flex gap-2">
        <form method="GET" action="{{ route('master-data.diskons.index') }}" class="d-flex gap-2">
          <select name="promo_id" class="form-select form-select-sm" style="min-width:220px">
            <option value="">Semua Promo</option>
            @foreach($promos as $p)
              <option value="{{ $p->id }}" @selected(($promoId ?? '')==$p->id)>{{ $p->name }} @if($p->code) ({{ $p->code }}) @endif</option>
            @endforeach
          </select>
          <select name="type" class="form-select form-select-sm">
            <option value="">Semua Tipe</option>
            <option value="fixed"      @selected(($type ?? '')==='fixed')>Fixed</option>
            <option value="percentage" @selected(($type ?? '')==='percentage')>Percentage</option>
          </select>
          <select name="active" class="form-select form-select-sm">
            <option value="">Semua Status</option>
            <option value="1" @selected(($active ?? '')==='1')>Aktif</option>
            <option value="0" @selected(($active ?? '')==='0')>Nonaktif</option>
          </select>
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="Cari nama/desc...">
          <button class="btn btn-outline-secondary btn-sm" type="submit" data-bs-toggle="tooltip" title="Cari"><i class="mdi mdi-magnify"></i></button>
          @if(($q ?? '')!=='' || ($promoId ?? '')!=='' || ($type ?? '')!=='' || ($active ?? '')!=='')
            <a href="{{ route('master-data.diskons.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="mdi mdi-refresh"></i></a>
          @endif
        </form>

        @can('create master-data-discounts')
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
          <i class="mdi mdi-plus me-1"></i> Tambah
        </button>
        @endcan
      </div>
    </h5>

    <div class="table-responsive text-nowrap">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th class="col-idx">#</th>
            <th>Promo</th>
            <th>Nama</th>
            <th>Tipe</th>
            <th>Nilai</th>
            <th>Min. Belanja</th>
            <th>Periode</th>
            <th>Prioritas</th>
            <th>Bisa DiGabung</th>
            <th>Status</th>
            <th class="col-aksi text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($discounts as $i => $d)
          <tr>
            <td>{{ $discounts->firstItem() + $i }}</td>

           

            <td class="fw-medium">
              <i class="mdi mdi-sale-outline mdi-20px text-primary me-2"></i>{{ $d->promo->name ?? '-' }}
            </td>
            <td>{{ $d->name }}</td>
            <td><span class="badge bg-label-info">{{ ucfirst($d->type) }}</span></td>
            <td>
              @if($d->type==='fixed')
                Rp {{ number_format($d->value, 0, ',', '.') }}
              @else
                {{ rtrim(rtrim(number_format($d->value, 2, ',', '.'), '0'), ',') }}%
                @if(!is_null($d->max_discount))
                  <span class="small text-muted d-block">Max Rp {{ number_format($d->max_discount, 0, ',', '.') }}</span>
                @endif
              @endif
            </td>
            <td>Rp {{ number_format($d->min_purchase ?? 0, 0, ',', '.') }}</td>
            <td class="small">
              <div>{{ optional($d->valid_from)->format('Y-m-d H:i') ?? '-' }}</div>
              <div>— {{ optional($d->valid_until)->format('Y-m-d H:i') ?? '-' }}</div>
            </td>
            <td>{{ $d->priority ?? 100 }}</td>
            <td>{!! $d->combinable ? '<span class="badge bg-label-primary">Ya</span>' : '<span class="badge bg-label-secondary">Tidak</span>' !!}</td>
            <td>{!! $d->is_active ? '<span class="badge bg-label-success">Aktif</span>' : '<span class="badge bg-label-danger">Nonaktif</span>' !!}</td>
             <td class="text-center">
              <div class="dropdown">
                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="mdi mdi-dots-vertical"></i></button>
                <div class="dropdown-menu">
                  @can('update master-data-discounts')
                  <a href="#" class="dropdown-item btn-edit"
                     data-action="{{ route('master-data.diskons.update', $d->id) }}"
                     data-promo="{{ $d->promo_id }}"
                     data-name="{{ e($d->name) }}"
                     data-description='@json($d->description ?? "")'
                     data-type="{{ $d->type }}"
                     data-value="{{ $d->value }}"
                     data-max_discount="{{ $d->max_discount }}"
                     data-min_purchase="{{ $d->min_purchase }}"
                     data-valid_from="{{ optional($d->valid_from)->format('Y-m-d\TH:i') }}"
                     data-valid_until="{{ optional($d->valid_until)->format('Y-m-d\TH:i') }}"
                     data-usage_limit="{{ $d->usage_limit }}"
                     data-priority="{{ $d->priority }}"
                     data-combinable="{{ $d->combinable ? 1 : 0 }}"
                     data-is_active="{{ $d->is_active ? 1 : 0 }}"
                     data-bs-toggle="modal" data-bs-target="#modalEdit">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit
                  </a>
                  @endcan
                  @can('delete master-data-discounts')
                  <a href="#" class="dropdown-item text-danger btn-delete"
                     data-action="{{ route('master-data.diskons.destroy', $d->id) }}">
                    <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                  </a>
                  @endcan
                </div>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="11" class="text-center text-muted">Belum ada data</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($discounts->hasPages())
    <div class="card-footer d-flex justify-content-end">
      {{ $discounts->appends([
        'q' => $q ?? null,
        'promo_id' => $promoId ?? null,
        'type' => $type ?? null,
        'active' => $active ?? null,
      ])->links() }}
    </div>
    @endif
  </div>
</div>

{{-- ================= MODAL: CREATE ================= --}}
@can('create master-data-discounts')
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('master-data.diskons.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-ticket-percent-outline me-1"></i> Tambah Diskon</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && old('_from')==='create')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal menyimpan. Periksa kembali:
              <ul class="mb-0 mt-2 ps-3">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
            </div>
          @endif

          @php($p='create')
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Promo </label>
              <select name="promo_id" id="{{ $p }}_promo_id" class="form-select">
                <option value="">Pilih Promo</option>
                @foreach($promos as $pr)
                  <option value="{{ $pr->id }}" @selected(old('_from')==='create' && old('promo_id')==$pr->id)>{{ $pr->name }} @if($pr->code) ({{ $pr->code }}) @endif</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input name="name" id="{{ $p }}_name" class="form-control @error('name') is-invalid @enderror"
                     value="{{ old('_from')==='create' ? old('name') : '' }}" required>
              @error('name') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea name="description" id="{{ $p }}_description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('_from')==='create' ? old('description') : '' }}</textarea>
              @error('description') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Tipe <span class="text-danger">*</span></label>
              <select name="type" id="{{ $p }}_type" class="form-select @error('type') is-invalid @enderror" required>
                <option value="fixed"      @selected(old('_from')==='create' ? old('type')==='fixed' : true)>Fixed</option>
                <option value="percentage" @selected(old('_from')==='create' && old('type')==='percentage')>Percentage</option>
              </select>
              @error('type') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Nilai <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" name="value" id="{{ $p }}_value"
                     class="form-control @error('value') is-invalid @enderror" value="{{ old('_from')==='create' ? old('value') : '' }}" required>
              @error('value') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4" id="{{ $p }}_max_discount_wrap" style="display:none">
              <label class="form-label">Maks. Diskon</label>
              <input type="number" step="0.01" min="0" name="max_discount" id="{{ $p }}_max_discount"
                     class="form-control @error('max_discount') is-invalid @enderror" value="{{ old('_from')==='create' ? old('max_discount') : '' }}">
              @error('max_discount') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Min. Belanja</label>
              <input type="number" step="0.01" min="0" name="min_purchase" id="{{ $p }}_min_purchase"
                     class="form-control @error('min_purchase') is-invalid @enderror" value="{{ old('_from')==='create' ? old('min_purchase') : '' }}">
              @error('min_purchase') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Berlaku Dari</label>
              <input type="datetime-local" name="valid_from" id="{{ $p }}_valid_from"
                     class="form-control @error('valid_from') is-invalid @enderror" value="{{ old('_from')==='create' ? old('valid_from') : '' }}">
              @error('valid_from') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Berlaku Sampai</label>
              <input type="datetime-local" name="valid_until" id="{{ $p }}_valid_until"
                     class="form-control @error('valid_until') is-invalid @enderror" value="{{ old('_from')==='create' ? old('valid_until') : '' }}">
              @error('valid_until') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Batas Penggunaan</label>
              <input type="number" min="1" name="usage_limit" id="{{ $p }}_usage_limit"
                     class="form-control @error('usage_limit') is-invalid @enderror" value="{{ old('_from')==='create' ? old('usage_limit') : '' }}">
              @error('usage_limit') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Prioritas</label>
              <input type="number" min="0" name="priority" id="{{ $p }}_priority"
                     class="form-control @error('priority') is-invalid @enderror" value="{{ old('_from')==='create' ? old('priority') : '100' }}">
              @error('priority') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4 d-flex align-items-end gap-4">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="combinable" id="{{ $p }}_combinable" value="1" @checked(old('_from')==='create' ? old('combinable') : false)>
                <label class="form-check-label" for="{{ $p }}_combinable">Bisa digabung</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" id="{{ $p }}_is_active" value="1" @checked(old('_from')==='create' ? (bool)old('is_active') : true)>
                <label class="form-check-label" for="{{ $p }}_is_active">Aktif</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" id="btnCreateSave" data-bs-toggle="tooltip" title="Shortcut: Alt + Enter">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- ================= MODAL: EDIT ================= --}}
@can('update master-data-discounts')
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formEdit" method="POST" action="#">
        @csrf @method('PUT')
        <input type="hidden" name="_from" value="edit">
        <input type="hidden" id="edit_update_url" name="update_url"
               value="{{ old('_from')==='edit' ? old('update_url') : '' }}">
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-pencil-outline me-1"></i> Edit Diskon</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && old('_from')==='edit')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal update. Periksa kembali:
              <ul class="mb-0 mt-2 ps-3">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
            </div>
          @endif

          @php($p='edit')
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Promo</label>
              <select name="promo_id" id="{{ $p }}_promo_id" class="form-select">
                <option value="">Pilih Promo</option>
                @foreach($promos as $pr)
                  <option value="{{ $pr->id }}" @selected(old('_from')==='edit' && old('promo_id')==$pr->id)>{{ $pr->name }} @if($pr->code) ({{ $pr->code }}) @endif</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama *</label>
              <input id="{{ $p }}_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('_from')==='edit' ? old('name') : '' }}" required>
              @error('name') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea id="{{ $p }}_description" name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('_from')==='edit' ? old('description') : '' }}</textarea>
              @error('description') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Tipe *</label>
              <select id="{{ $p }}_type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                <option value="fixed"      @selected(old('_from')==='edit' && old('type')==='fixed')>Fixed</option>
                <option value="percentage" @selected(old('_from')==='edit' && old('type')==='percentage')>Percentage</option>
              </select>
              @error('type') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Nilai *</label>
              <input type="number" step="0.01" min="0" id="{{ $p }}_value" name="value"
                     class="form-control @error('value') is-invalid @enderror" value="{{ old('_from')==='edit' ? old('value') : '' }}" required>
              @error('value') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4" id="{{ $p }}_max_discount_wrap" style="display:none">
              <label class="form-label">Maks. Diskon</label>
              <input type="number" step="0.01" min="0" id="{{ $p }}_max_discount" name="max_discount"
                     class="form-control @error('max_discount') is-invalid @enderror" value="{{ old('_from')==='edit' ? old('max_discount') : '' }}">
              @error('max_discount') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Min. Belanja</label>
              <input type="number" step="0.01" min="0" id="{{ $p }}_min_purchase" name="min_purchase"
                     class="form-control @error('min_purchase') is-invalid @enderror" value="{{ old('_from')==='edit' ? old('min_purchase') : '' }}">
              @error('min_purchase') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Berlaku Dari</label>
              <input type="datetime-local" id="{{ $p }}_valid_from" name="valid_from"
                     class="form-control @error('valid_from') is-invalid @enderror" value="{{ old('_from')==='edit' ? old('valid_from') : '' }}">
              @error('valid_from') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Berlaku Sampai</label>
              <input type="datetime-local" id="{{ $p }}_valid_until" name="valid_until"
                     class="form-control @error('valid_until') is-invalid @enderror" value="{{ old('_from')==='edit' ? old('valid_until') : '' }}">
              @error('valid_until') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label">Batas Penggunaan</label>
              <input type="number" min="1" id="{{ $p }}_usage_limit" name="usage_limit"
                     class="form-control @error('usage_limit') is-invalid @enderror" value="{{ old('_from')==='edit' ? old('usage_limit') : '' }}">
              @error('usage_limit') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label">Prioritas</label>
              <input type="number" min="0" id="{{ $p }}_priority" name="priority"
                     class="form-control @error('priority') is-invalid @enderror" value="{{ old('_from')==='edit' ? old('priority') : '100' }}">
              @error('priority') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-4 d-flex align-items-end gap-4">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="{{ $p }}_combinable" name="combinable" value="1" @checked(old('_from')==='edit' ? (bool)old('combinable') : false)>
                <label class="form-check-label" for="{{ $p }}_combinable">Bisa digabung</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="{{ $p }}_is_active" name="is_active" value="1" @checked(old('_from')==='edit' ? (bool)old('is_active') : false)>
                <label class="form-check-label" for="{{ $p }}_is_active">Aktif</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update</span>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" id="btnEditUpdate" data-bs-toggle="tooltip" title="Shortcut: Alt + Enter">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan


{{-- ================= POP-UP NOTIFIKASI ================= --}}
<div id="discountNotificationBackdrop"
     class="discount-notification-backdrop"
     aria-hidden="true">
  <div id="discountNotification"
       class="discount-notification success"
       role="alertdialog"
       aria-modal="true"
       aria-labelledby="discountNotificationTitle"
       aria-describedby="discountNotificationMessage">
    <div class="discount-notification-body">
      <div class="discount-notification-icon">
        <i id="discountNotificationIcon" class="mdi mdi-check"></i>
      </div>

      <h4 id="discountNotificationTitle"
          class="discount-notification-title">
        Berhasil
      </h4>

      <p id="discountNotificationMessage"
         class="discount-notification-message"></p>
    </div>

    <div class="discount-notification-footer">
      <button type="button"
              id="discountNotificationCancel"
              class="btn btn-outline-secondary d-none">
        Batal
      </button>

      <button type="button"
              id="discountNotificationConfirm"
              class="btn btn-primary">
        OK
      </button>
    </div>
  </div>
</div>

{{-- FORM DELETE --}}
@can('delete master-data-discounts')
<form id="formDelete" method="POST" class="d-none">@csrf @method('DELETE')</form>
@endcan
@endsection

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const notificationBackdrop =
    document.getElementById('discountNotificationBackdrop');
  const notificationBox =
    document.getElementById('discountNotification');
  const notificationIcon =
    document.getElementById('discountNotificationIcon');
  const notificationTitle =
    document.getElementById('discountNotificationTitle');
  const notificationMessage =
    document.getElementById('discountNotificationMessage');
  const notificationConfirm =
    document.getElementById('discountNotificationConfirm');
  const notificationCancel =
    document.getElementById('discountNotificationCancel');

  let notificationConfirmCallback = null;

  function showDiscountNotification(type, title, message, options = {}) {
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

  function closeDiscountNotification() {
    if (!notificationBackdrop) return;

    notificationBackdrop.classList.remove('show');
    notificationBackdrop.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    notificationConfirmCallback = null;
  }

  notificationConfirm?.addEventListener('click', function () {
    const callback = notificationConfirmCallback;
    closeDiscountNotification();

    if (callback) {
      callback();
    }
  });

  notificationCancel?.addEventListener(
    'click',
    closeDiscountNotification
  );

  notificationBackdrop?.addEventListener('click', function (event) {
    if (
      event.target === notificationBackdrop &&
      notificationCancel.classList.contains('d-none')
    ) {
      closeDiscountNotification();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (
      event.key === 'Escape' &&
      notificationBackdrop?.classList.contains('show')
    ) {
      closeDiscountNotification();
    }
  });

  const flashSuccess = @json(session('success'));
  const flashError = @json(session('error'));
  const validationErrors = @json($errors->all());
  const validationSource = @json(old('_from'));

  if (flashSuccess) {
    showDiscountNotification('success', 'Berhasil', flashSuccess);
  } else if (flashError) {
    showDiscountNotification('error', 'Gagal', flashError);
  } else if (
    validationErrors.length > 0 &&
    validationSource === null
  ) {
    showDiscountNotification(
      'error',
      'Data Belum Valid',
      validationErrors.join('\n')
    );
  }

  function hookType(prefix) {
    const typeEl = document.getElementById(prefix + '_type');
    const wrap = document.getElementById(prefix + '_max_discount_wrap');
    if (!typeEl || !wrap) return;
    const toggle = () => { wrap.style.display = (typeEl.value === 'percentage') ? 'block' : 'none'; };
    toggle();
    typeEl.addEventListener('change', toggle);
  }
  hookType('create'); hookType('edit');

  // Tooltip
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

  // ALT+ENTER (Create/Edit)
  const modalCreate = document.getElementById('modalCreate');
  const modalEdit   = document.getElementById('modalEdit');
  const formCreate  = modalCreate?.querySelector('form');
  const formEdit    = document.getElementById('formEdit');
  modalCreate?.addEventListener('keydown', e => { if (e.altKey && e.key === 'Enter') { e.preventDefault(); modalCreate.querySelector('form')?.requestSubmit(); }});
  modalEdit?.addEventListener('keydown',   e => { if (e.altKey && e.key === 'Enter') { e.preventDefault();   modalEdit.querySelector('form')?.requestSubmit();   }});


  function resetCreateDiscountForm() {
    if (!formCreate) {
      return;
    }

    formCreate.reset();
    formCreate.classList.remove('was-validated');

    formCreate
      .querySelectorAll('.is-invalid')
      .forEach(function (field) {
        field.classList.remove('is-invalid');
      });

    formCreate
      .querySelectorAll('.invalid-feedback, .alert-danger')
      .forEach(function (element) {
        element.remove();
      });

    const defaults = {
      create_promo_id: '',
      create_name: '',
      create_description: '',
      create_type: 'fixed',
      create_value: '',
      create_max_discount: '',
      create_min_purchase: '',
      create_valid_from: '',
      create_valid_until: '',
      create_usage_limit: '',
      create_priority: '100'
    };

    Object.entries(defaults).forEach(function ([id, value]) {
      const field = document.getElementById(id);

      if (field) {
        field.value = value;
      }
    });

    const combinable =
      document.getElementById('create_combinable');
    const isActive =
      document.getElementById('create_is_active');

    if (combinable) {
      combinable.checked = false;
    }

    if (isActive) {
      isActive.checked = true;
    }

    document
      .getElementById('create_type')
      ?.dispatchEvent(new Event('change'));

    const submitButton =
      formCreate.querySelector('button[type="submit"]');

    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML =
        '<i class="mdi mdi-content-save-outline me-1"></i> Simpan';
    }
  }

  modalCreate?.addEventListener(
    'show.bs.modal',
    function () {
      /*
       * Saat modal dibuka ulang karena validasi gagal,
       * old input harus tetap ditampilkan.
       * Pembukaan normal selalu dimulai dari form kosong.
       */
      if (validationSource !== 'create') {
        resetCreateDiscountForm();
      }
    }
  );

  modalCreate?.addEventListener(
    'hidden.bs.modal',
    function () {
      resetCreateDiscountForm();
    }
  );

  modalEdit?.addEventListener(
    'hidden.bs.modal',
    function () {
      if (!formEdit) {
        return;
      }

      formEdit.reset();
      formEdit.action = '#';
      formEdit.classList.remove('was-validated');

      const updateUrlInput =
        document.getElementById('edit_update_url');

      if (updateUrlInput) {
        updateUrlInput.value = '';
      }

      formEdit
        .querySelectorAll('.is-invalid')
        .forEach(function (field) {
          field.classList.remove('is-invalid');
        });

      formEdit
        .querySelectorAll('.invalid-feedback, .alert-danger')
        .forEach(function (element) {
          element.remove();
        });

      document
        .getElementById('edit_type')
        ?.dispatchEvent(new Event('change'));
    }
  );

  // Isi modal Edit
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const f = formEdit;

      if (!f) {
        return;
      }

      f.reset();
      f.action = btn.dataset.action;

      const updateUrlInput =
        document.getElementById('edit_update_url');

      if (updateUrlInput) {
        updateUrlInput.value = btn.dataset.action || '';
      }

      const setVal = (id, val, isCheck = false) => {
        const el = document.getElementById('edit_' + id);
        if (!el) return;
        if (isCheck) { el.checked = (val === '1' || val === 1 || val === true); }
        else { el.value = (val ?? ''); }
      };

      setVal('promo_id',     btn.dataset.promo);
      setVal('name',         btn.dataset.name);
      let editDescription = '';

      try {
        editDescription = JSON.parse(
          btn.dataset.description || '""'
        );
      } catch (error) {
        editDescription = btn.dataset.description || '';
      }

      setVal('description',  editDescription);
      setVal('type',         btn.dataset.type);
      setVal('value',        btn.dataset.value);
      setVal('max_discount', btn.dataset.max_discount);
      setVal('min_purchase', btn.dataset.min_purchase);
      setVal('valid_from',   btn.dataset.valid_from);
      setVal('valid_until',  btn.dataset.valid_until);
      setVal('usage_limit',  btn.dataset.usage_limit);
      setVal('priority',     btn.dataset.priority);
      setVal('combinable',   btn.dataset.combinable, true);
      setVal('is_active',    btn.dataset.is_active,  true);

      // tampilkan/ sembunyikan max_discount sesuai tipe
      document.getElementById('edit_type').dispatchEvent(new Event('change'));
      setTimeout(()=>document.getElementById('edit_name')?.focus(), 120);
    });
  });

  // Hapus
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const deleteUrl = btn.dataset.action || '#';

      showDiscountNotification(
        'warning',
        'Hapus Diskon?',
        'Data diskon yang sudah dihapus tidak dapat dikembalikan.',
        {
          showCancel: true,
          confirmText: 'Ya, Hapus',
          cancelText: 'Batal',
          onConfirm: function () {
            const form = document.getElementById('formDelete');

            if (!form) {
              showDiscountNotification(
                'error',
                'Gagal',
                'Form hapus diskon tidak ditemukan.'
              );
              return;
            }

            form.action = deleteUrl;
            form.submit();
          }
        }
      );
    });
  });

  // Reopen modal saat validasi gagal
  const reopened = @json(old('_from'));
  if (reopened === 'create') {
    const m = new bootstrap.Modal(
      document.getElementById('modalCreate')
    );
    m.show();

    setTimeout(function () {
      document.getElementById('create_name')?.focus();

      if (validationErrors.length > 0) {
        showDiscountNotification(
          'error',
          'Gagal Menyimpan',
          validationErrors.join('\n')
        );
      }
    }, 250);
  } else if (reopened === 'edit') {
    const oldUpdateUrl = @json(old('update_url'));
    const editForm = document.getElementById('formEdit');

    if (oldUpdateUrl && editForm) {
      editForm.action = oldUpdateUrl;

      const updateUrlInput =
        document.getElementById('edit_update_url');

      if (updateUrlInput) {
        updateUrlInput.value = oldUpdateUrl;
      }
    }

    const m = new bootstrap.Modal(
      document.getElementById('modalEdit')
    );
    m.show();

    setTimeout(function () {
      document.getElementById('edit_name')?.focus();

      if (validationErrors.length > 0) {
        showDiscountNotification(
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
