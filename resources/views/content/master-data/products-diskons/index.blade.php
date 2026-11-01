@extends('layouts/contentNavbarLayout')

@section('title', 'Master Promo & Discount - Products Diskon')

@section('page-style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
  height: 38px; padding: 5px 8px; border: 1px solid var(--bs-border-color); border-radius: .375rem;
}
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 26px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; right: 8px; }
.select2-container .select2-dropdown { z-index: 2000 !important; } /* dropdown di atas modal */
.table td, .table th { vertical-align: top; }
.select2-container--default .select2-selection--multiple {
    min-height: 38px;
    border: 1px solid var(--bs-border-color);
    border-radius: .375rem;
    padding: 3px;
}
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">Master Promo & Discount /</span> Produk Diskon</h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any() && old('_from')===null)
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali form Anda.
      <ul class="mb-0 mt-2 small">@foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Relasi Produk & Diskon</span>

      <div class="d-flex gap-2">
        <form method="GET" action="{{ route('master-data.products-diskons.index') }}" class="d-flex gap-2">
          <select name="discount_id" class="form-select form-select-sm js-select2" style="min-width:240px" data-placeholder="Semua Diskon">
            <option value=""></option>
            @foreach($discounts as $d)
              <option value="{{ $d->id }}" @selected(($filters['discount_id'] ?? '')==$d->id)>{{ $d->name }}</option>
            @endforeach
          </select>

          <select name="store_id" class="form-select form-select-sm js-select2" style="min-width:220px" data-placeholder="Semua Cabang">
            <option value=""></option>
            @foreach($stores as $s)
              <option value="{{ $s->id }}" @selected(($filters['store_id'] ?? '')==$s->id)>{{ $s->name }}</option>
            @endforeach
          </select>

          <input type="text" class="form-control form-control-sm" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari produk (kode/nama)..." style="min-width:240px">
          <button class="btn btn-outline-secondary btn-sm" type="submit" data-bs-toggle="tooltip" title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if(($filters['q'] ?? '')!=='' || ($filters['discount_id'] ?? '')!=='' || ($filters['store_id'] ?? '')!=='')
            <a href="{{ route('master-data.products-diskons.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>

        @can('create master-data-product-discounts')
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
          <i class="mdi mdi-plus me-1"></i> Tambah
        </button>
        @endcan
      </div>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:70px">#</th>
            <th>Diskon</th>
            <th>Produk</th>
            <th>Berlaku Untuk</th>
            <th>Cabang</th>
            <th class="text-center" style="width:120px">Aksi</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">

@php
$grouped = $rows->groupBy('discount_id');
$no = $rows->firstItem();
@endphp

@forelse($grouped as $discountId => $items)

{{-- HEADER PROMO --}}
<tr class="table-primary">
  <td colspan="6">
      <strong>
          <i class="mdi mdi-tag-outline me-1"></i>
          {{ $items->first()->discount_name }}
      </strong>
      <span class="text-muted ms-2">
          {{ $items->first()->discount_type }}
          @if(!is_null($items->first()->discount_value))
            ({{ $items->first()->discount_value }})
          @endif
      </span>
  </td>
</tr>

{{-- LIST PRODUK --}}
@foreach($items as $r)
<tr>

<td style="width:70px">{{ $no++ }}</td>

<td class="text-muted">
  <small>{{ $r->discount_name }}</small>
</td>

<td>
  <div class="fw-medium">{{ $r->product_name }}</div>
  <div class="small text-muted">{{ $r->product_code }}</div>
</td>

<td>
  <span class="badge bg-label-primary">{{ $r->applied_to }}</span>
</td>

<td>{{ $r->store_name ?? '-' }}</td>

<td class="text-center">

<div class="dropdown">
<button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
<i class="mdi mdi-dots-vertical"></i>
</button>

<div class="dropdown-menu dropdown-menu-end">

{{-- DETAIL --}}
<a href="#" class="dropdown-item btn-detail"
data-bs-toggle="modal"
data-bs-target="#modalDetail"
data-discount-name="{{ $r->discount_name }}"
data-discount-type="{{ $r->discount_type }}"
data-discount-value="{{ $r->discount_value }}"
data-product-name="{{ $r->product_name }}"
data-product-code="{{ $r->product_code }}"
data-applied-to="{{ $r->applied_to }}"
data-store-name="{{ $r->store_name ?? '-' }}"
>
<i class="mdi mdi-eye-outline me-1"></i> Detail
</a>

@can('update master-data-product-discounts')
<!-- <a href="#"
class="dropdown-item btn-edit"
data-bs-toggle="modal"
data-bs-target="#modalEdit"
data-update-url="{{ route('master-data.products-diskons.update') }}"
data-discount-id="{{ $r->discount_id }}"
data-product-id="{{ $r->product_id }}"
data-applied-to="{{ $r->applied_to }}"
data-store-id="{{ $r->store_id_single }}"
>
<i class="mdi mdi-pencil-outline me-1"></i> Edit
</a> -->
@endcan

@can('delete master-data-product-discounts')
<div class="dropdown-divider"></div>

<a href="#"
class="dropdown-item text-danger btn-delete"
data-delete-url="{{ route('master-data.products-diskons.destroy') }}"
data-discount-id="{{ $r->discount_id }}"
data-product-id="{{ $r->product_id }}"
data-store-id="{{ $r->store_id_single }}"
>
<i class="mdi mdi-trash-can-outline me-1"></i> Hapus
</a>
@endcan

</div>
</div>

</td>

</tr>
@endforeach

@empty

<tr>
<td colspan="6" class="text-center text-muted">
Belum ada data
</td>
</tr>

@endforelse

</tbody>
      </table>
    </div>

    @if($rows->hasPages())
    <div class="card-footer d-flex justify-content-end">
      {{ $rows->appends(request()->query())->links() }}
    </div>
    @endif
  </div>
</div>

{{-- ========== MODAL: CREATE ========== --}}
@can('create master-data-product-discounts')
<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('master-data.products-diskons.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-plus-circle-outline me-1"></i> Tambah Produk Diskon</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Diskon <span class="text-danger">*</span></label>
            <select name="discount_id" class="form-select js-select2 @error('discount_id') is-invalid @enderror"
                    data-placeholder="Pilih Diskon" data-dropdown-parent="#modalCreate" required>
              <option value=""></option>
              @foreach($discounts as $d)
                <option value="{{ $d->id }}" @selected(old('_from')==='create' && old('discount_id')==$d->id)>{{ $d->name }}</option>
              @endforeach
            </select>
            @error('discount_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
<div class="mb-3">
<label class="form-label">Kategori Produk</label>

<select id="filter_category"
        class="form-select js-select2"
        data-dropdown-parent="#modalCreate">

<option value="">Semua Kategori</option>

@foreach($categories as $c)
<option value="{{ $c->id }}">
{{ $c->name }}
</option>
@endforeach

</select>
</div>
       <div class="mb-3">
<label class="form-label">Produk <span class="text-danger">*</span></label>
<input type="hidden" name="all_products" id="all_products" value="{{ old('_from') === 'create' ? (old('all_products') ? '1' : '0') : '0' }}">
{{-- CHECK ALL --}}
<div class="form-check mb-2">
  <input class="form-check-input" type="checkbox" id="checkAllProducts"
         @checked(old('_from') === 'create' && old('all_products'))>
  <label class="form-check-label" for="checkAllProducts">
    Pilih Semua Produk
  </label>
</div>

<div id="all-products-notice" class="alert alert-info py-2 px-3 mb-2 d-none" role="status">
  <i class="mdi mdi-information-outline me-1"></i>
  Semua produk aktif akan ditambahkan saat data disimpan. Daftar produk tidak perlu dimuat ke formulir.
</div>

<select name="product_id[]"
        id="product_select"
        class="form-select js-select2 @error('product_id') is-invalid @enderror"
        data-placeholder="Pilih Produk"
        data-dropdown-parent="#modalCreate"
        multiple
        required>

<option value=""></option>

@foreach($products as $p)
<option value="{{ $p->id }}"
data-category="{{ $p->category_id }}"
@if(old('_from')==='create' && is_array(old('product_id')) && in_array($p->id, old('product_id')))
selected
@endif
>
{{ $p->name }} ({{ $p->code }})
</option>
@endforeach

</select>

@error('product_id')
<div class="invalid-feedback d-block">{{ $message }}</div>
@enderror

</div>

          @php($appliedToOptions = $appliedToOptions ?? [])
          <div class="mb-3">
            <label class="form-label">Berlaku Untuk <span class="text-danger">*</span></label>
            <select id="applied_to" name="applied_to" class="form-select @error('applied_to') is-invalid @enderror"
                    data-dropdown-parent="#modalCreate" required>
              <option value="">Pilih</option>
              @forelse($appliedToOptions as $opt)
                <option value="{{ $opt }}" @selected(old('_from')==='create' && old('applied_to')==$opt)>{{ $opt }}</option>
              @empty
                <option value="all"   @selected(old('_from')==='create' && old('applied_to')=='all')>all</option>
                <option value="store" @selected(old('_from')==='create' && old('applied_to')=='store')>store</option>
              @endforelse
            </select>
            @error('applied_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>

          <div class="mb-0" id="wrap-store" style="display:none;">
            <label class="form-label">Cabang</label>
            <select id="store_id" name="store_id" class="form-select js-select2 @error('store_id') is-invalid @enderror"
                    data-placeholder="Pilih Cabang" data-dropdown-parent="#modalCreate">
              <option value=""></option>
              @foreach($stores as $s)
                <option value="{{ $s->id }}" @selected(old('_from')==='create' && old('store_id')==$s->id)>{{ $s->name }}</option>
              @endforeach
            </select>
            @error('store_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit" id="btnCreateSave">
            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

{{-- ========== MODAL: DETAIL ========== --}}
<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="mdi mdi-eye-outline me-1"></i> Detail Produk Diskon</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Produk</dt>       <dd class="col-sm-8" id="det_product"></dd>
          <dt class="col-sm-4">Diskon</dt>       <dd class="col-sm-8" id="det_discount"></dd>
          <dt class="col-sm-4">Tipe / Nilai</dt> <dd class="col-sm-8" id="det_type_value"></dd>
          <dt class="col-sm-4">Berlaku Untuk</dt><dd class="col-sm-8" id="det_applied"></dd>
          <dt class="col-sm-4">Cabang</dt>       <dd class="col-sm-8" id="det_store"></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

{{-- ========== MODAL: EDIT ========== --}}
@can('update master-data-product-discounts')
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form id="formEditMap" method="POST" action="{{ route('master-data.products-diskons.update') }}">
        @csrf @method('PUT')
        <input type="hidden" name="_from" value="edit">

        {{-- original keys (identifikasi baris lama) --}}
        <input type="hidden" name="original_discount_id" id="edit_original_discount_id" value="">
        <input type="hidden" name="original_product_id"  id="edit_original_product_id"  value="">
        <input type="hidden" name="original_store_id"    id="edit_original_store_id"    value="">

        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-pencil-outline me-1"></i> Edit Produk Diskon</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && old('_from')==='edit')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal update. Periksa kembali:
              <ul class="mb-0 mt-2 ps-3">@foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label">Diskon <span class="text-danger">*</span></label>
            <select id="edit_discount_id" name="discount_id" class="form-select js-select2"
                    data-placeholder="Pilih Diskon" data-dropdown-parent="#modalEdit" required>
              <option value=""></option>
              @foreach($discounts as $d)
                <option value="{{ $d->id }}" @selected(old('_from')==='edit' && old('discount_id')==$d->id)>{{ $d->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Produk <span class="text-danger">*</span></label>
            <select id="edit_product_id" name="product_id" class="form-select js-select2"
                    data-placeholder="Pilih Produk" data-dropdown-parent="#modalEdit" required>
              <option value=""></option>
              @foreach($products as $p)
                <option value="{{ $p->id }}" @selected(old('_from')==='edit' && old('product_id')==$p->id)>{{ $p->name }} ({{ $p->code }})</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Berlaku Untuk <span class="text-danger">*</span></label>
            <select id="edit_applied_to" name="applied_to" class="form-select" data-dropdown-parent="#modalEdit" required>
              <option value="">Pilih</option>
              @forelse(($appliedToOptions ?? []) as $opt)
                <option value="{{ $opt }}" @selected(old('_from')==='edit' && old('applied_to')==$opt)>{{ $opt }}</option>
              @empty
                <option value="all"   @selected(old('_from')==='edit' && old('applied_to')=='all')>all</option>
                <option value="store" @selected(old('_from')==='edit' && old('applied_to')=='store')>store</option>
              @endforelse
            </select>
          </div>

          <div class="mb-0" id="wrap-store-edit" style="display:none;">
            <label class="form-label">Cabang</label>
            <select id="edit_store_id" name="store_id" class="form-select js-select2"
                    data-placeholder="Pilih Cabang" data-dropdown-parent="#modalEdit">
              <option value=""></option>
              @foreach($stores as $s)
                <option value="{{ $s->id }}" @selected(old('_from')==='edit' && old('store_id')==$s->id)>{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <span class="text-muted small me-auto d-none d-md-inline">Tip: <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Update</span>
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit" id="btnEditUpdate">
            <i class="mdi mdi-content-save-outline me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

@can('delete master-data-product-discounts')
<form id="formDeleteMap" method="POST" class="d-none" action="{{ route('master-data.products-diskons.destroy') }}">
  @csrf @method('DELETE')
  <input type="hidden" name="discount_id" value="">
  <input type="hidden" name="product_id"  value="">
  <input type="hidden" name="store_id"    value="">
</form>
@endcan
@endsection

{{-- Flag untuk auto-open modal create --}}
@php($shouldOpenCreate = $errors->any() && old('_from')==='create')

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

const baseOpts = {
    width: 'resolve',
    allowClear: true,
    placeholder: function () {
        return this.element.dataset.placeholder || 'Pilih';
    },
    language: {
        noResults: () => 'Tidak ada hasil',
        searching: () => 'Mencari…'
    }
};

function initSelect2(ctx) {
    $(ctx).find('.js-select2').each(function () {

        const $el = $(this);

        if ($el.hasClass('select2-hidden-accessible')) {
            try { $el.select2('destroy'); } catch(e){}
        }

        const opts = {...baseOpts};

        const dpSelector = $el.attr('data-dropdown-parent');

        if (dpSelector) {
            opts.dropdownParent = $(dpSelector);
        } else {
            const $modal = $el.closest('.modal');
            if ($modal.length) opts.dropdownParent = $modal;
        }

        $el.select2(opts);
    });
}

initSelect2(document);

['modalCreate','modalEdit','modalDetail'].forEach(id=>{
    const m = document.getElementById(id);
    if (m) m.addEventListener('shown.bs.modal', ()=> initSelect2(m));
});


/* ===================================
   STORE TOGGLE
=================================== */

function toggleStore() {

    const applied = document.getElementById('applied_to')?.value || '';
    const wrap = document.getElementById('wrap-store');

    if (!wrap) return;

    if (applied.toLowerCase() === 'store') {
        wrap.style.display = '';
    } else {
        wrap.style.display = 'none';
        $('#store_id').val(null).trigger('change');
    }
}

const selApplied = document.getElementById('applied_to');

if (selApplied) {
    selApplied.addEventListener('change', toggleStore);
    toggleStore();
}

function toggleStoreEdit() {

    const applied = document.getElementById('edit_applied_to')?.value || '';
    const wrap = document.getElementById('wrap-store-edit');

    if (!wrap) return;

    if (applied.toLowerCase() === 'store') {
        wrap.style.display = '';
    } else {
        wrap.style.display = 'none';
        $('#edit_store_id').val(null).trigger('change');
    }
}

const selAppliedEdit = document.getElementById('edit_applied_to');

if (selAppliedEdit) {
    selAppliedEdit.addEventListener('change', toggleStoreEdit);
}


/* ===================================
   DETAIL MODAL
=================================== */

document.querySelectorAll('.btn-detail').forEach(btn=>{
    btn.addEventListener('click', function(){

        const prod = (this.dataset.productName || '-') +
        (this.dataset.productCode ? ` [${this.dataset.productCode}]` : '');

        document.getElementById('det_product').textContent = prod;
        document.getElementById('det_discount').textContent = this.dataset.discountName || '-';
        document.getElementById('det_type_value').textContent =
            `${this.dataset.discountType || '-'} / ${this.dataset.discountValue || '-'}`;
        document.getElementById('det_applied').textContent = this.dataset.appliedTo || '-';
        document.getElementById('det_store').textContent = this.dataset.storeName || '-';

    });
});


/* ===================================
   DELETE
=================================== */

document.querySelectorAll('.btn-delete').forEach(btn=>{
    btn.addEventListener('click', function(e){

        e.preventDefault();

        if (!confirm('Hapus relasi diskon-produk ini?')) return;

        const form = document.getElementById('formDeleteMap');

        form.action = this.dataset.deleteUrl || '#';

        form.querySelector('input[name="discount_id"]').value = this.dataset.discountId || '';
        form.querySelector('input[name="product_id"]').value = this.dataset.productId || '';
        form.querySelector('input[name="store_id"]').value = this.dataset.storeId || '';

        form.submit();
    });
});


/* ===================================
   ALT ENTER SUBMIT
=================================== */

document.getElementById('modalCreate')?.addEventListener('keydown', e=>{
    if (e.altKey && e.key === 'Enter') {
        e.preventDefault();
        document.querySelector('#modalCreate form')?.requestSubmit();
    }
});

document.getElementById('modalEdit')?.addEventListener('keydown', e=>{
    if (e.altKey && e.key === 'Enter') {
        e.preventDefault();
        document.querySelector('#modalEdit form')?.requestSubmit();
    }
});


/* ===================================
   AUTO OPEN CREATE MODAL
=================================== */

const shouldOpenCreate = @json($shouldOpenCreate);

if (shouldOpenCreate) {
    new bootstrap.Modal(document.getElementById('modalCreate')).show();
}


/* ===================================
   PRODUCT FILTER SYSTEM
=================================== */

const productSelect = $('#product_select');
const categoryFilter = $('#filter_category');
const checkAllProducts = $('#checkAllProducts');

/* simpan semua produk */

let allProducts = [];

$('#product_select option').each(function(){

    const val = $(this).val();

    if(val !== ''){

        allProducts.push({
            id: val,
            text: $(this).text(),
            category: $(this).data('category')
        });

    }

});


/* filter kategori */

categoryFilter.on('change', function(){

    const categoryId = $(this).val();

    let filtered = [];

    if(!categoryId){
        filtered = allProducts;
    }else{
        filtered = allProducts.filter(p => p.category == categoryId);
    }

    productSelect.empty();

    filtered.forEach(function(p){

        productSelect.append(new Option(p.text, p.id, false, false));

    });

    productSelect.trigger('change');

    checkAllProducts.prop('checked', false);

});


/* Pilih semua tanpa mengirim/memuat seluruh ID produk di browser. */
function setAllProductsMode(enabled) {

    $('#all_products').val(enabled ? '1' : '0');

    // Kosongkan pilihan yang mungkin sudah dipilih agar Select2 tidak merender
    // ratusan tag produk. Produk akan dipilih oleh server ketika formulir dikirim.
    if (enabled) {
        productSelect.val(null);
    }

    productSelect.prop('disabled', enabled).trigger('change.select2');
    categoryFilter.prop('disabled', enabled).trigger('change.select2');
    $('#all-products-notice').toggleClass('d-none', !enabled);
}

checkAllProducts.on('change', function(){
    setAllProductsMode(this.checked);
});

// Pertahankan kondisi saat kembali ke form setelah validasi gagal.
setAllProductsMode(checkAllProducts.is(':checked'));


});
</script>
@endsection
