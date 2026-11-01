@extends('layouts/contentNavbarLayout')

@section('title','Master Product - Products')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
/* ===== Table layout & spacing ===== */
.table-responsive { overflow-x: auto }
.table.products-table { table-layout: fixed }
.table.products-table th,
.table.products-table td { vertical-align: middle }

/* Lebar kolom */
.col-idx   { width: 60px }
.col-name  { width: 360px }
.col-code  { width: 140px }
.col-unit  { width: 100px }
.col-wg    { width: 120px; text-align: right }
.col-cat   { width: 160px }
.col-brand { width: 160px }
.col-spec  { width: 180px }
.col-desc  { width: 420px }
.col-aksi  { width: 120px; text-align: center }

/* Truncate elegan */
.line-clamp-2, .line-clamp-3 {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.line-clamp-2 { -webkit-line-clamp: 2 }
.line-clamp-3 { -webkit-line-clamp: 3 }

/* Nama produk: ikon + teks */
.product-name {
  display: grid;
  grid-template-columns: 24px 1fr;
  gap: .5rem;
  align-items: start;
}
.product-name .name-text { word-break: break-word }

/* Badge kode */
.badge-code {
  display: inline-block;
  max-width: 100%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Select2 full width & di dalam modal */
.select2-container { width: 100% !important }

/* ===== Kartu gambar tersimpan ===== */
.stored-image-card {
  position: relative;
  overflow: hidden;
  height: 100%;
  border: 2px solid #e7e7e7;
  border-radius: .75rem;
  background: #fff;
  transition: border-color .2s ease, box-shadow .2s ease, opacity .2s ease, transform .2s ease;
}

.stored-image-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 .35rem 1rem rgba(34,48,62,.12);
}

.stored-image-card.is-main {
  border-color: #8c57ff;
  box-shadow: 0 0 0 3px rgba(140,87,255,.12);
}

.stored-image-card.is-active:not(.is-main) {
  border-color: #71dd37;
}

.stored-image-card.is-deleted {
  border-color: #ff4c51;
  opacity: .72;
}

.stored-image-media {
  position: relative;
  overflow: hidden;
  height: 150px;
  background: #f5f5f7;
}

.stored-image-media img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: filter .2s ease, transform .2s ease;
}

.stored-image-card:hover .stored-image-media img {
  transform: scale(1.02);
}

.stored-image-card.is-deleted .stored-image-media img {
  filter: grayscale(1);
}

.stored-image-statuses {
  position: absolute;
  top: .55rem;
  left: .55rem;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: .35rem;
}

.stored-image-status {
  display: none;
  align-items: center;
  gap: .25rem;
  padding: .3rem .5rem;
  border-radius: .4rem;
  color: #fff;
  font-size: .7rem;
  font-weight: 600;
  line-height: 1;
  box-shadow: 0 .15rem .45rem rgba(34,48,62,.2);
}

.stored-image-card.is-main .status-main {
  display: inline-flex;
  background: #8c57ff;
}

.stored-image-card.is-active .status-active {
  display: inline-flex;
  background: #56ca00;
}

.stored-image-card.is-deleted .status-delete {
  display: inline-flex;
  background: #ff4c51;
}

.stored-image-delete {
  position: absolute;
  top: .55rem;
  right: .55rem;
  z-index: 5;
  display: inline-flex;
  width: 34px;
  height: 34px;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: .5rem;
  background: rgba(255,255,255,.94);
  color: #ff4c51;
  font-size: 1.25rem;
  box-shadow: 0 .15rem .5rem rgba(34,48,62,.18);
  transition: color .2s ease, background .2s ease, transform .2s ease;
}

.stored-image-delete:hover {
  color: #fff;
  background: #ff4c51;
  transform: scale(1.06);
}

.stored-image-card.is-deleted .stored-image-delete {
  color: #fff;
  background: #ff4c51;
}

.stored-image-delete-overlay {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: none;
  align-items: center;
  justify-content: center;
  background: rgba(255,76,81,.2);
}

.stored-image-card.is-deleted .stored-image-delete-overlay {
  display: flex;
}

.stored-image-delete-overlay span {
  padding: .45rem .7rem;
  border-radius: .45rem;
  background: rgba(255,76,81,.94);
  color: #fff;
  font-size: .75rem;
  font-weight: 600;
}

.stored-image-options {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .5rem;
  padding: .65rem;
}

.stored-image-option {
  position: relative;
  display: flex;
  min-height: 38px;
  align-items: center;
  justify-content: center;
  gap: .35rem;
  margin: 0;
  padding: .45rem .5rem;
  border: 1px solid #d9dee3;
  border-radius: .5rem;
  background: #fff;
  color: #566a7f;
  font-size: .75rem;
  font-weight: 500;
  cursor: pointer;
  user-select: none;
  transition: color .2s ease, background .2s ease, border-color .2s ease;
}

.stored-image-option:hover {
  color: #8c57ff;
  border-color: #8c57ff;
}

.stored-image-option.is-selected.option-main {
  color: #8c57ff;
  border-color: #8c57ff;
  background: rgba(140,87,255,.1);
}

.stored-image-option.is-selected.option-active {
  color: #45a800;
  border-color: #71dd37;
  background: rgba(113,221,55,.12);
}

.stored-image-option input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

.stored-image-option i {
  font-size: 1.1rem;
}

.stored-image-card.is-deleted .stored-image-options {
  pointer-events: none;
  opacity: .45;
}


/* Preview gambar baru sebelum disimpan */
.upload-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 12px;
  margin-top: 12px;
}
.upload-preview-item {
  position: relative;
  overflow: hidden;
  border: 1px solid #e7e7e7;
  border-radius: .625rem;
  background: #fff;
}
.upload-preview-item img {
  display: block;
  width: 100%;
  height: 130px;
  object-fit: cover;
}
.upload-preview-meta {
  padding: 8px 10px;
  font-size: 12px;
}
.upload-preview-name {
  display: block;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.upload-preview-size {
  color: #8592a3;
}
.upload-preview-remove {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 50%;
  background: rgba(255,255,255,.92);
  color: #dc3545;
  box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.upload-preview-empty {
  padding: 14px;
  border: 1px dashed #d9dee3;
  border-radius: .625rem;
  color: #8592a3;
  text-align: center;
}

/* Responsive tweak */
@media (max-width: 1400px) {
  .col-name { width: 300px }
  .col-desc { width: 360px }
}
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">Master Product /</span> Products</h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if($errors->any() && old('_from') === null)
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali input Anda.
      <button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card">
    <h5 class="card-header d-flex align-items-center justify-content-between">
      <span>Daftar Produk</span>

      <div class="d-flex gap-2">
        <form method="GET" action="{{ route('master-data.products.index') }}" class="d-flex gap-2">
  {{-- Keyword --}}
  <input type="text"
         name="q"
         value="{{ $q ?? '' }}"
         class="form-control form-control-sm"
         placeholder="Cari nama/kode/kategori...">

  {{-- Filter Kategori --}}
  <!-- <select name="category_id" class="form-select form-select-sm" style="max-width: 220px;">
    <option value="">Semua kategori</option>
    @foreach($categories as $cat)
      <option value="{{ $cat->id }}"
        {{ isset($categoryId) && (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>
        {{ $cat->name }}
      </option>
    @endforeach
  </select> -->

  {{-- Tombol Cari --}}
  <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Cari">
    <i class="mdi mdi-magnify"></i>
  </button>

  {{-- Reset (clear q + kategori) --}}
  @if(($q ?? '') !== '' || !empty($categoryId))
    <a href="{{ route('master-data.products.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
      <i class="mdi mdi-refresh"></i>
    </a>
  @endif
</form>


        @can('create master-data-products')
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
          <i class="mdi mdi-plus me-1"></i> Tambah
        </button>
        @endcan
      </div>
    </h5>

    <div class="table-responsive text-nowrap">
      <table class="table products-table mb-0">
        <thead class="table-light">
          <tr>
            <th class="col-idx">#</th>
            <th class="col-name">Nama</th>
            <th class="col-code">Kode</th>
            <th class="col-unit">Unit</th>
            <th class="col-wg">Berat (g)</th>
            <th class="col-cat">Kategori</th>
            <th class="col-brand">Brand</th>
            <th class="col-spec">Specification</th>
            <th class="col-desc">Deskripsi</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse($products as $i => $p)
          @php
            $weightFormatted = null;
            if (!is_null($p->weight_g)) {
              $wf = number_format((float)$p->weight_g, 3, '.', ',');
              $wf = rtrim(rtrim($wf, '0'), '.');
              $weightFormatted = $wf;
            }

            $imgsForDataset = ($p->images ?? collect())->map(function($img){
              return [
                'id'      => (string)$img->id,
                'url'     => (string)$img->image_url,
                'active'  => ((string)$img->status_active === 'active'),
                'is_main' => (bool)$img->is_main,
              ];
            })->values();

            $weightRaw = is_null($p->weight_g) ? '' : number_format((float)$p->weight_g, 3, '.', '');
            $canUpdate = auth()->user()?->can('update master-data-products');
            $canDelete = auth()->user()?->can('delete master-data-products');
          @endphp
          <tr>
            <td class="col-idx">{{ ($products->currentPage()-1)*$products->perPage() + $i + 1 }}</td>

            <td class="col-name">
              <div class="product-name">
                <i class="mdi mdi-package-variant-closed mdi-20px text-primary"></i>
                <span class="name-text line-clamp-2" title="{{ $p->name }}">{{ $p->name }}</span>
              </div>
            </td>

            <td class="col-code">
              <span class="badge bg-label-primary badge-code" title="{{ $p->code }}">{{ $p->code }}</span>
            </td>

            <td class="col-unit">{{ $p->unit->name ?? '-' }}</td>

            <td class="col-wg">{{ $weightFormatted ?? '-' }}</td>

            <td class="col-cat">
              <span class="text-truncate d-inline-block" style="max-width:100%;" title="{{ $p->category->name ?? '-' }}">
                {{ $p->category->name ?? '-' }}
              </span>
            </td>

            <td class="col-brand">
              <span class="text-truncate d-inline-block" style="max-width:100%;" title="{{ $p->brand->name ?? '-' }}">
                {{ $p->brand->name ?? '-' }}
              </span>
            </td>

            <td class="col-spec">
              <span class="text-truncate d-inline-block" style="max-width:100%;" title="{{ $p->specification->value ?? '-' }}">
                {{ $p->specification->value ?? '-' }}
              </span>
            </td>

            <td class="col-desc">
              <div class="line-clamp-3" title="{{ $p->description }}">{{ $p->description }}</div>
            </td>

            <td class="col-aksi">
              @if(!$canUpdate && !$canDelete)
                <span class="text-muted">-</span>
              @else
              <div class="dropdown">
                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="mdi mdi-dots-vertical"></i>
                </button>
                <div class="dropdown-menu">
                  @can('update master-data-products')
                  <a href="javascript:void(0);" class="dropdown-item btn-edit"
                     data-update-url="{{ route('master-data.products.update', $p->id) }}"
                     data-id="{{ $p->id }}"
                     data-name="{{ $p->name }}"
                     data-code="{{ $p->code }}"
                     data-unit="{{ $p->unit_id }}"
                      data-category="{{ $p->category_id }}"
                      data-brand="{{ $p->brand_id }}"
                      data-specification="{{ $p->specification_id }}"
                     data-weight="{{ $weightRaw }}"
                     data-description="{{ e($p->description) }}"
                     data-images='@json($imgsForDataset)'
                     data-bs-toggle="modal" data-bs-target="#modalEdit">
                    <i class="mdi mdi-pencil-outline me-1"></i> Edit
                  </a>
                  @endcan

                  @can('delete master-data-products')
                  <a href="#" class="dropdown-item text-danger btn-delete"
                     data-delete-url="{{ route('master-data.products.destroy', $p->id) }}">
                    <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
                  </a>
                  @endcan
                </div>
              </div>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="10" class="text-center text-muted">Belum ada data</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-end">
      {{ $products->appends(['q' => $q ?? null])->links() }}
    </div>
  </div>
</div>

{{-- ================= MODAL: CREATE ================= --}}
@can('create master-data-products')
<div class="modal fade" id="modalCreate" tabindex="-1" aria-labelledby="modalCreateLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formCreate" method="POST" action="{{ route('master-data.products.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_from" value="create">

        <div class="modal-header">
          <h5 class="modal-title" id="modalCreateLabel"><i class="mdi mdi-package-variant-closed-plus me-1"></i> Tambah Produk</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          @if($errors->any() && old('_from')==='create')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal menyimpan. Periksa kembali input Anda:
              <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
              </ul>
            </div>
          @endif

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="{{ old('_from')==='create' ? old('name') : '' }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kode <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control" value="{{ old('_from')==='create' ? old('code') : '' }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Unit <span class="text-danger">*</span></label>
              <select id="create_unit" name="unit_id" class="form-select js-select2" required>
                <option value="">- Pilih -</option>
                @foreach($units as $u)
                  <option value="{{ $u->id }}" {{ old('_from')==='create' && old('unit_id')==$u->id ? 'selected':'' }}>{{ $u->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kategori <span class="text-danger">*</span></label>
              <select id="create_category" name="category_id" class="form-select js-select2" required>
                <option value="">- Pilih -</option>
                @foreach($categories as $c)
                  <option value="{{ $c->id }}" {{ old('_from')==='create' && old('category_id')==$c->id ? 'selected':'' }}>{{ $c->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Specification</label>
              <select id="create_specification" name="specification_id" class="form-select js-select2">
                <option value="">- Tidak ada -</option>
                @foreach($specifications as $s)
                  <option value="{{ $s->id }}" {{ old('_from')==='create' && old('specification_id')==$s->id ? 'selected':'' }}>
                    {{ $s->value }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Brand</label>
              <select id="create_brand" name="brand_id" class="form-select js-select2">
                <option value="">- Tidak ada -</option>
                @foreach($brands as $b)
                  <option value="{{ $b->id }}" {{ old('_from')==='create' && old('brand_id')==$b->id ? 'selected':'' }}>
                    {{ $b->name }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Berat (Gram) --}}
            <div class="col-md-6">
              <label class="form-label">Berat (Gram) <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" name="weight_g" class="form-control" min="0" step="0.001" required
                       value="{{ old('_from')==='create' ? old('weight_g') : '' }}" placeholder="0.000">
                <span class="input-group-text">g</span>
              </div>
              <div class="form-text">Hingga 3 angka di belakang koma.</div>
            </div>

            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea name="description" class="form-control" rows="3">{{ old('_from')==='create' ? old('description') : '' }}</textarea>
            </div>

            <div class="col-12">
              <label for="create_images" class="form-label">
                Gambar Produk
                <span class="text-muted">(bisa pilih lebih dari 1)</span>
              </label>

              <input
                type="file"
                name="images[]"
                id="create_images"
                class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                multiple>

              <div class="form-text">
                Pilih beberapa gambar sekaligus. Format JPG, JPEG, PNG, atau WEBP.
                Maksimal 3MB per gambar dan maksimal 10 gambar.
              </div>

              @if(old('_from') === 'create')
                @error('images')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror

                @error('images.*')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              @endif

              <div id="create_images_preview" class="upload-preview-grid">
                <div class="upload-preview-empty">
                  Preview gambar yang dipilih akan tampil di sini.
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
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
@can('update master-data-products')
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formEdit" method="POST"
            action="{{ old('_from')==='edit' && old('id') ? route('master-data.products.update', old('id')) : '#' }}"
            enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" id="edit_id" name="id" value="{{ old('_from')==='edit' ? old('id') : '' }}">
        <input type="hidden" name="_from" value="edit">

        <div class="modal-header">
          <h5 class="modal-title" id="modalEditLabel"><i class="mdi mdi-pencil-outline me-1"></i> Edit Produk</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          @if($errors->any() && old('_from')==='edit')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal menyimpan perubahan. Periksa kembali input Anda:
              <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
              </ul>
            </div>
          @endif

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input id="edit_name" name="name" class="form-control" value="{{ old('_from')==='edit' ? old('name') : '' }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kode <span class="text-danger">*</span></label>
              <input id="edit_code" name="code" class="form-control" value="{{ old('_from')==='edit' ? old('code') : '' }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Unit <span class="text-danger">*</span></label>
              <select id="edit_unit" name="unit_id" class="form-select js-select2" required>
                <option value="">- Pilih -</option>
                @foreach($units as $u)
                <option value="{{ $u->id }}" {{ old('_from')==='edit' && old('unit_id')==$u->id ? 'selected':'' }}>
                  {{ $u->name }}
                </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kategori <span class="text-danger">*</span></label>
              <select id="edit_category" name="category_id" class="form-select js-select2" required>
                <option value="">- Pilih -</option>
                @foreach($categories as $c)
                <option value="{{ $c->id }}" {{ old('_from')==='edit' && old('category_id')==$c->id ? 'selected':'' }}>
                  {{ $c->name }}
                </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Specification</label>
              <select id="edit_specification" name="specification_id" class="form-select js-select2">
                <option value="">- Tidak ada -</option>
                @foreach($specifications as $s)
                <option value="{{ $s->id }}" {{ old('_from')==='edit' && old('specification_id')==$s->id ? 'selected':'' }}>
                  {{ $s->value }}
                </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Brand</label>
              <select id="edit_brand" name="brand_id" class="form-select js-select2">
                <option value="">- Tidak ada -</option>
                @foreach($brands as $b)
                <option value="{{ $b->id }}" {{ old('_from')==='edit' && old('brand_id')==$b->id ? 'selected':'' }}>
                  {{ $b->name }}
                </option>
                @endforeach
              </select>
            </div>

            {{-- Berat (Gram) --}}
            <div class="col-md-6">
              <label class="form-label">Berat (Gram) <span class="text-danger">*</span></label>
              <div class="input-group">
                <input id="edit_weight_g" name="weight_g" type="number" class="form-control" min="0" step="0.001" required
                       value="{{ old('_from')==='edit' ? old('weight_g') : '' }}" placeholder="0.000">
                <span class="input-group-text">g</span>
              </div>
              <div class="form-text">Hingga 3 angka di belakang koma.</div>
            </div>

            <div class="col-12">
              <label class="form-label">Deskripsi</label>
              <textarea id="edit_description" name="description" class="form-control" rows="3">{{ old('_from')==='edit' ? old('description') : '' }}</textarea>
            </div>

            <div class="col-12">
              <label for="edit_images" class="form-label">
                Tambah Gambar Baru
                <span class="text-muted">(bisa pilih lebih dari 1)</span>
              </label>

              <input
                type="file"
                name="images[]"
                id="edit_images"
                class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                multiple>

              <div class="form-text">
                Pilih beberapa gambar sekaligus. Format JPG, JPEG, PNG, atau WEBP.
                Maksimal 3MB per gambar dan maksimal 10 gambar.
              </div>

              @if(old('_from') === 'edit')
                @error('images')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror

                @error('images.*')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              @endif

              <div id="edit_images_preview" class="upload-preview-grid">
                <div class="upload-preview-empty">
                  Preview gambar baru yang dipilih akan tampil di sini.
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label mb-0">Gambar Tersimpan</label>
                <small class="text-muted">
                  Klik <strong>Main</strong> atau <strong>Aktif</strong>. Gunakan ikon
                  <i class="mdi mdi-trash-can-outline text-danger"></i> untuk menandai gambar yang akan dihapus.
                </small>
              </div>
              <div id="edit_images_list" class="row g-3 mt-1"></div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update</span>
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

@can('delete master-data-products')
<form id="formDelete" method="POST" class="d-none">@csrf @method('DELETE')</form>
@endcan
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush
@push('page-script')
<script>
(function($) {
  'use strict';

  function initSelect2In(modalEl) {
    const $m = $(modalEl);
    $m.find('.js-select2').each(function() {
      const $el = $(this);
      if ($el.hasClass('select2-hidden-accessible')) return;
      $el.select2({
        theme: 'bootstrap-5',
        placeholder: '- Pilih -',
        allowClear: true,
        dropdownParent: $m,
        width: '100%'
      });
    });
  }
  initSelect2In('#modalCreate');
  initSelect2In('#modalEdit');
  $('#modalCreate').on('shown.bs.modal', function(){ initSelect2In(this); });
  $('#modalEdit').on('shown.bs.modal',  function(){ initSelect2In(this); });

  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

  const modalCreate = document.getElementById('modalCreate');
  const modalEdit   = document.getElementById('modalEdit');
  modalCreate?.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'Enter') { e.preventDefault(); document.getElementById('formCreate')?.requestSubmit(); }
  });
  modalEdit?.addEventListener('keydown', e => {
    if (e.altKey && e.key === 'Enter') { e.preventDefault(); document.getElementById('formEdit')?.requestSubmit(); }
  });

  /*
  |--------------------------------------------------------------------------
  | Multiple image upload + preview
  |--------------------------------------------------------------------------
  */
  const maxImageBytes = 3 * 1024 * 1024;
  const maxImageCount = 10;
  const allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp'];

  function formatFileSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
  }

  function createMultipleImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!input || !preview) return null;

    let selectedFiles = [];

    function syncInputFiles() {
      const transfer = new DataTransfer();
      selectedFiles.forEach(file => transfer.items.add(file));
      input.files = transfer.files;
    }

    function renderPreview() {
      preview.innerHTML = '';

      if (selectedFiles.length === 0) {
        preview.innerHTML = `
          <div class="upload-preview-empty">
            Preview gambar yang dipilih akan tampil di sini.
          </div>
        `;
        return;
      }

      selectedFiles.forEach((file, index) => {
        const objectUrl = URL.createObjectURL(file);
        const item = document.createElement('div');

        item.className = 'upload-preview-item';
        item.innerHTML = `
          <img src="${objectUrl}" alt="Preview ${index + 1}">
          <button
            type="button"
            class="upload-preview-remove"
            data-index="${index}"
            title="Hapus dari pilihan">
            <i class="mdi mdi-close"></i>
          </button>
          <div class="upload-preview-meta">
            <span class="upload-preview-name" title="${file.name}">
              ${file.name}
            </span>
            <span class="upload-preview-size">
              ${formatFileSize(file.size)}
            </span>
          </div>
        `;

        const image = item.querySelector('img');
        image.addEventListener('load', () => URL.revokeObjectURL(objectUrl), {
          once: true
        });

        preview.appendChild(item);
      });

      preview.querySelectorAll('.upload-preview-remove').forEach(button => {
        button.addEventListener('click', function() {
          const index = Number(this.dataset.index);
          selectedFiles.splice(index, 1);
          syncInputFiles();
          renderPreview();
        });
      });
    }

    input.addEventListener('change', function() {
      const incomingFiles = Array.from(input.files || []);
      const validFiles = [];
      const errors = [];

      incomingFiles.forEach(file => {
        if (!allowedImageTypes.includes(file.type)) {
          errors.push(`${file.name}: format tidak didukung.`);
          return;
        }

        if (file.size > maxImageBytes) {
          errors.push(`${file.name}: ukuran lebih dari 3MB.`);
          return;
        }

        validFiles.push(file);
      });

      if (selectedFiles.length + validFiles.length > maxImageCount) {
        errors.push(`Maksimal ${maxImageCount} gambar dalam satu kali simpan.`);
        validFiles.splice(
          Math.max(0, maxImageCount - selectedFiles.length)
        );
      }

      validFiles.forEach(file => {
        const duplicate = selectedFiles.some(existing =>
          existing.name === file.name &&
          existing.size === file.size &&
          existing.lastModified === file.lastModified
        );

        if (!duplicate) {
          selectedFiles.push(file);
        }
      });

      syncInputFiles();
      renderPreview();

      if (errors.length > 0) {
        alert(errors.join('\n'));
      }
    });

    return {
      reset() {
        selectedFiles = [];
        input.value = '';
        renderPreview();
      }
    };
  }

  const createImagePreview = createMultipleImagePreview(
    'create_images',
    'create_images_preview'
  );

  const editImagePreview = createMultipleImagePreview(
    'edit_images',
    'edit_images_preview'
  );

  const oldDeleteIds = @json(old('_from') === 'edit' ? old('delete_image_ids', []) : []);
  const oldActiveIds = @json(old('_from') === 'edit' ? old('active_image_ids', []) : []);
  const oldMainId    = @json(old('_from') === 'edit' ? old('is_main_image_id') : null);

  $('#modalCreate').on('hidden.bs.modal', function() {
    createImagePreview?.reset();
  });

  $('#modalEdit').on('hidden.bs.modal', function() {
    editImagePreview?.reset();
  });

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function updateStoredImageCard(card) {
    if (!card) return;

    const mainInput   = card.querySelector('.stored-main-input');
    const activeInput = card.querySelector('.stored-active-input');
    const deleteInput = card.querySelector('.stored-delete-input');
    const mainOption  = card.querySelector('.option-main');
    const activeOption = card.querySelector('.option-active');

    const deleted = deleteInput?.checked === true;
    const main    = mainInput?.checked === true;
    const active  = activeInput?.checked === true;

    card.classList.toggle('is-deleted', deleted);
    card.classList.toggle('is-main', main && !deleted);
    card.classList.toggle('is-active', active && !deleted);

    mainOption?.classList.toggle('is-selected', main && !deleted);
    activeOption?.classList.toggle('is-selected', active && !deleted);

    if (mainInput) mainInput.disabled = deleted;
    if (activeInput) activeInput.disabled = deleted;

    const trashIcon = card.querySelector('.stored-image-delete i');
    if (trashIcon) {
      trashIcon.className = deleted
        ? 'mdi mdi-delete-restore-outline'
        : 'mdi mdi-trash-can-outline';
    }

    const deleteButton = card.querySelector('.stored-image-delete');
    if (deleteButton) {
      deleteButton.title = deleted
        ? 'Batalkan hapus gambar'
        : 'Hapus gambar';
      deleteButton.setAttribute(
        'aria-label',
        deleted ? 'Batalkan hapus gambar' : 'Hapus gambar'
      );
    }
  }

  function refreshStoredImageCards(container) {
    container?.querySelectorAll('.stored-image-card').forEach(updateStoredImageCard);
  }

  function bindStoredImageEvents(container) {
    if (!container) return;

    container.querySelectorAll('.stored-main-input').forEach(input => {
      input.addEventListener('change', function() {
        /*
         * Main harus aktif. Saat gambar dipilih sebagai Main,
         * checkbox Aktif otomatis ikut dipilih.
         */
        const card = this.closest('.stored-image-card');
        const activeInput = card?.querySelector('.stored-active-input');
        if (this.checked && activeInput) activeInput.checked = true;

        refreshStoredImageCards(container);
      });
    });

    container.querySelectorAll('.stored-active-input').forEach(input => {
      input.addEventListener('change', function() {
        const card = this.closest('.stored-image-card');
        const mainInput = card?.querySelector('.stored-main-input');

        /*
         * Gambar Main tidak boleh dinonaktifkan.
         */
        if (!this.checked && mainInput?.checked) {
          this.checked = true;
          alert('Gambar utama harus tetap aktif.');
        }

        updateStoredImageCard(card);
      });
    });

    container.querySelectorAll('.stored-image-delete').forEach(button => {
      button.addEventListener('click', function() {
        const card = this.closest('.stored-image-card');
        if (!card) return;

        const deleteInput = card.querySelector('.stored-delete-input');
        const mainInput = card.querySelector('.stored-main-input');
        const activeInput = card.querySelector('.stored-active-input');

        if (!deleteInput) return;

        const willDelete = !deleteInput.checked;

        if (willDelete) {
          card.dataset.wasMain = mainInput?.checked ? '1' : '0';
          card.dataset.wasActive = activeInput?.checked ? '1' : '0';

          deleteInput.checked = true;
          if (mainInput) mainInput.checked = false;
          if (activeInput) activeInput.checked = false;
        } else {
          deleteInput.checked = false;

          if (mainInput && card.dataset.wasMain === '1') {
            /*
             * Radio Main hanya boleh satu.
             */
            container.querySelectorAll('.stored-main-input').forEach(other => {
              other.checked = false;
            });
            mainInput.checked = true;
          }

          if (activeInput && card.dataset.wasActive === '1') {
            activeInput.checked = true;
          }
        }

        refreshStoredImageCards(container);
      });
    });

    refreshStoredImageCards(container);
  }

  function renderStoredImages(images) {
    const container = document.getElementById('edit_images_list');
    if (!container) return;

    container.innerHTML = '';

    if (!Array.isArray(images) || images.length === 0) {
      container.innerHTML = `
        <div class="col-12">
          <div class="upload-preview-empty">
            <i class="mdi mdi-image-off-outline mdi-36px d-block mb-2"></i>
            Belum ada gambar tersimpan.
          </div>
        </div>
      `;
      return;
    }

    const oldActive = Array.isArray(oldActiveIds)
      ? oldActiveIds.map(String)
      : [];

    const oldDelete = Array.isArray(oldDeleteIds)
      ? oldDeleteIds.map(String)
      : [];

    let mainSelectedId = null;

    if (oldMainId !== null && oldMainId !== undefined && String(oldMainId) !== '') {
      mainSelectedId = String(oldMainId);
    } else {
      const currentMain = images.find(image => image.is_main === true);
      mainSelectedId = currentMain ? String(currentMain.id) : null;
    }

    images.forEach(image => {
      const imageId = String(image.id);

      const checkedMain = mainSelectedId === imageId;
      const checkedActive = oldActive.length > 0
        ? oldActive.includes(imageId)
        : image.active === true;
      const checkedDelete = oldDelete.includes(imageId);

      const column = document.createElement('div');
      column.className = 'col-6 col-md-4 col-lg-3';

      column.innerHTML = `
        <div class="stored-image-card" data-image-id="${escapeHtml(imageId)}">
          <div class="stored-image-media">
            <img
              src="${escapeHtml(image.url)}"
              alt="Gambar produk"
              loading="lazy">

            <div class="stored-image-statuses">
              <span class="stored-image-status status-main">
                <i class="mdi mdi-star"></i>
                Gambar Utama
              </span>

              <span class="stored-image-status status-active">
                <i class="mdi mdi-check-circle"></i>
                Aktif
              </span>

              <span class="stored-image-status status-delete">
                <i class="mdi mdi-trash-can"></i>
                Akan Dihapus
              </span>
            </div>

            <button
              type="button"
              class="stored-image-delete"
              title="Hapus gambar"
              aria-label="Hapus gambar">
              <i class="mdi mdi-trash-can-outline"></i>
            </button>

            <div class="stored-image-delete-overlay">
              <span>
                <i class="mdi mdi-trash-can-outline me-1"></i>
                Akan Dihapus
              </span>
            </div>
          </div>

          <div class="stored-image-options">
            <label class="stored-image-option option-main">
              <input
                class="stored-main-input"
                type="radio"
                name="is_main_image_id"
                value="${escapeHtml(imageId)}"
                ${checkedMain ? 'checked' : ''}>

              <i class="mdi mdi-star-outline"></i>
              Main
            </label>

            <label class="stored-image-option option-active">
              <input
                class="stored-active-input"
                type="checkbox"
                name="active_image_ids[]"
                value="${escapeHtml(imageId)}"
                ${checkedActive ? 'checked' : ''}>

              <i class="mdi mdi-check-circle-outline"></i>
              Aktif
            </label>
          </div>

          <input
            class="stored-delete-input d-none"
            type="checkbox"
            name="delete_image_ids[]"
            value="${escapeHtml(imageId)}"
            ${checkedDelete ? 'checked' : ''}>
        </div>
      `;

      container.appendChild(column);
    });

    bindStoredImageEvents(container);
  }

  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
      editImagePreview?.reset();

      const form = document.getElementById('formEdit');
      if (form) form.action = this.dataset.updateUrl || '#';

      document.getElementById('edit_id').value          = this.dataset.id || '';
      document.getElementById('edit_name').value        = this.dataset.name || '';
      document.getElementById('edit_code').value        = this.dataset.code || '';
      document.getElementById('edit_description').value = this.dataset.description || '';
      document.getElementById('edit_weight_g').value    = this.dataset.weight || '';

      const unitVal = this.dataset.unit || '';
      const catVal  = this.dataset.category || '';
      const brandVal = this.dataset.brand || '';
      const specVal = this.dataset.specification || '';

      $('#edit_unit').val(unitVal).trigger('change');
      $('#edit_category').val(catVal).trigger('change');
      $('#edit_brand').val(brandVal).trigger('change');
      $('#edit_specification').val(specVal).trigger('change');

      let images = [];

      try {
        images = JSON.parse(this.dataset.images || '[]');
      } catch (error) {
        console.error('Data gambar produk tidak valid:', error);
      }

      renderStoredImages(images);
    });
  });

  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      if (!confirm('Yakin hapus produk ini?')) return;
      const form = document.getElementById('formDelete');
      form.action = this.dataset.deleteUrl || '#';
      form.submit();
    });
  });

  const reopen = @json(old('_from'));
  if (reopen === 'create') new bootstrap.Modal(document.getElementById('modalCreate')).show();
  else if (reopen === 'edit') {
    const m = new bootstrap.Modal(document.getElementById('modalEdit'));
    m.show();
    $('#modalEdit').one('shown.bs.modal', function() {
      $('#edit_unit').trigger('change');
      $('#edit_category').trigger('change');
      $('#edit_brand').trigger('change');
      $('#edit_specification').trigger('change');
    });
  }
})(window.jQuery);
</script>
@endpush
