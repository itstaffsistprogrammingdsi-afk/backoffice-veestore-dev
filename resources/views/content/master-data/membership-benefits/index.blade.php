@extends('layouts/contentNavbarLayout')

@section('title','Loyalty & Membership - Membership Benefits')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">Loyalty & Membership /</span> Membership Benefits</h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any() && old('_from')===null)
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali form Anda.
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Benefits</span>

      <div class="d-flex gap-2">
        <form method="GET" action="{{ route('master-data.membership-benefits.index') }}" class="d-flex gap-2">
          <select name="membership_id" class="form-select form-select-sm" style="min-width:220px">
            <option value="">Semua Membership</option>
            @foreach($memberships as $m)
              <option value="{{ $m->id }}" @selected(($membershipId ?? '')==$m->id)>{{ $m->name }}</option>
            @endforeach
          </select>
          <input class="form-control form-control-sm" name="q" value="{{ $q ?? '' }}" placeholder="Cari nama/tipe/value...">
          <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Filter">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if(($membershipId ?? '')!=='' || ($q ?? '')!=='')
            <a href="{{ route('master-data.membership-benefits.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-membership-benefits')
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateBenefit">
          <i class="mdi mdi-plus me-1"></i> Tambah
        </button>
        @endcan
      </div>
    </h5>

    <div class="table-responsive text-nowrap">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:70px">#</th>
            <th>Membership</th>
            <th>Nama</th>
            <th>Value</th>
            <th>Tipe</th>
            <th class="text-center" style="width:120px;">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($benefits as $i => $b)
            <tr>
              <td>{{ ($benefits->currentPage()-1)*$benefits->perPage() + $i + 1 }}</td>
              <td class="fw-medium">
                <i class="mdi mdi-badge-account-outline mdi-20px text-primary me-2"></i>
                {{ $b->membership->name ?? '-' }}
              </td>
              <td>{{ $b->name }}</td>
              <td><span class="badge bg-label-primary">{{ $b->value ?? '-' }}</span></td>
              <td>{{ $b->type ?? '-' }}</td>
              <td class="text-center">
                <div class="dropdown">
                  <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="mdi mdi-dots-vertical"></i>
                  </button>
                  <div class="dropdown-menu">
                    @can('update master-data-membership-benefits')
                    <a href="javascript:void(0);" class="dropdown-item btn-edit"
                       data-update-url="{{ route('master-data.membership-benefits.update', $b->id) }}"
                       data-id="{{ $b->id }}"
                       data-membership="{{ $b->membership_id }}"
                       data-name="{{ e($b->name) }}"
                       data-value="{{ e($b->value) }}"
                       data-type="{{ e($b->type) }}"
                       data-bs-toggle="modal" data-bs-target="#modalEditBenefit">
                      <i class="mdi mdi-pencil-outline me-1"></i> Edit
                    </a>
                    @endcan

                    @can('delete master-data-membership-benefits')
                    <a href="#" class="dropdown-item text-danger btn-delete"
                       data-delete-url="{{ route('master-data.membership-benefits.destroy', $b->id) }}">
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

    @if($benefits->hasPages())
    <div class="card-footer d-flex justify-content-end">
      {{ $benefits->appends(['q' => $q ?? null, 'membership_id' => $membershipId ?? null])->links() }}
    </div>
    @endif
  </div>
</div>

{{-- ================= MODAL: CREATE ================= --}}
@can('create master-data-membership-benefits')
<div class="modal fade" id="modalCreateBenefit" tabindex="-1" aria-labelledby="modalCreateBenefitLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="formCreateBenefit" method="POST" action="{{ route('master-data.membership-benefits.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCreateBenefitLabel">
            <i class="mdi mdi-plus-circle-outline me-1"></i> Tambah Benefit
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && old('_from')==='create')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal menyimpan. Periksa kembali:
              <ul class="mb-0 mt-2 ps-3">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label">Membership <span class="text-danger">*</span></label>
            <select name="membership_id" class="form-select @error('membership_id') is-invalid @enderror" required>
              <option value="">Pilih Membership</option>
              @foreach($memberships as $m)
                <option value="{{ $m->id }}" @selected(old('_from')==='create' && old('membership_id')==$m->id)>{{ $m->name }}</option>
              @endforeach
            </select>
            @error('membership_id') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Nama <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('_from')==='create' ? old('name') : '' }}" required>
            @error('name') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Value</label>
            <input type="text" name="value" class="form-control @error('value') is-invalid @enderror"
                   value="{{ old('_from')==='create' ? old('value') : '' }}" placeholder="contoh: 10% / 50000 / dll">
            @error('value') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Tipe</label>
            <input type="text" name="type" class="form-control @error('type') is-invalid @enderror"
                   value="{{ old('_from')==='create' ? old('type') : '' }}" placeholder="percentage/amount/other">
            @error('type') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: tekan <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button id="btnCreateSave" class="btn btn-primary" type="submit" data-bs-toggle="tooltip" title="Shortcut: Alt + Enter">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- ================= MODAL: EDIT ================= --}}
@can('update master-data-membership-benefits')
<div class="modal fade" id="modalEditBenefit" tabindex="-1" aria-labelledby="modalEditBenefitLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="formEditBenefit" method="POST"
            action="{{ old('_from')==='edit' && old('id') ? route('master-data.membership-benefits.update', old('id')) : '#' }}">
        @csrf @method('PUT')
        <input type="hidden" name="_from" value="edit">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditBenefitLabel">
            <i class="mdi mdi-pencil-outline me-1"></i> Edit Benefit
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && old('_from')==='edit')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal update. Periksa kembali:
              <ul class="mb-0 mt-2 ps-3">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label">Membership <span class="text-danger">*</span></label>
            <select id="edit_membership_id" name="membership_id" class="form-select @error('membership_id') is-invalid @enderror" required>
              <option value="">Pilih Membership</option>
              @foreach($memberships as $m)
                <option value="{{ $m->id }}" @selected(old('_from')==='edit' && old('membership_id')==$m->id)>{{ $m->name }}</option>
              @endforeach
            </select>
            @error('membership_id') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Nama <span class="text-danger">*</span></label>
            <input id="edit_name" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('_from')==='edit' ? old('name') : '' }}" required>
            @error('name') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Value</label>
            <input id="edit_value" name="value" class="form-control @error('value') is-invalid @enderror"
                   value="{{ old('_from')==='edit' ? old('value') : '' }}">
            @error('value') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Tipe</label>
            <input id="edit_type" name="type" class="form-control @error('type') is-invalid @enderror"
                   value="{{ old('_from')==='edit' ? old('type') : '' }}">
            @error('type') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <input type="hidden" id="edit_id" name="id" value="{{ old('_from')==='edit' ? old('id') : '' }}">
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: tekan <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update</span>
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button id="btnEditUpdate" class="btn btn-primary" type="submit" data-bs-toggle="tooltip" title="Shortcut: Alt + Enter">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

<form id="formDeleteBenefit" method="POST" class="d-none">@csrf @method('DELETE')</form>
</div>

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Tooltip
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

  // ALT+ENTER submit (Create)
  document.getElementById('modalCreateBenefit')?.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'Enter') { e.preventDefault(); document.getElementById('formCreateBenefit')?.requestSubmit(); }
  });

  // ALT+ENTER submit (Edit)
  document.getElementById('modalEditBenefit')?.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'Enter') { e.preventDefault(); document.getElementById('formEditBenefit')?.requestSubmit(); }
  });

  // Prefill Edit
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
      const f = document.getElementById('formEditBenefit');
      f.action = this.dataset.updateUrl || '#';
      document.getElementById('edit_id').value             = this.dataset.id || '';
      document.getElementById('edit_membership_id').value  = this.dataset.membership || '';
      document.getElementById('edit_name').value           = this.dataset.name || '';
      document.getElementById('edit_value').value          = this.dataset.value || '';
      document.getElementById('edit_type').value           = this.dataset.type || '';
    });
  });

  // Delete
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (!confirm('Yakin hapus benefit ini?')) return;
      const form = document.getElementById('formDeleteBenefit');
      form.action = this.dataset.deleteUrl || '#';
      form.submit();
    });
  });

  // Reopen modal kalau validasi gagal
  const reopened = @json(old('_from'));
  if (reopened === 'create') {
    new bootstrap.Modal(document.getElementById('modalCreateBenefit')).show();
  } else if (reopened === 'edit') {
    new bootstrap.Modal(document.getElementById('modalEditBenefit')).show();
  }
});
</script>
@endpush
@endsection
