@extends('layouts/contentNavbarLayout')

@section('title', 'Promo Terpadu')

@push('vendor-style')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
      <h4 class="mb-1"><span class="text-muted fw-light">Master Data /</span> Promo</h4>
      <p class="mb-0 text-muted">Atur promo, nilai diskon, dan produk sasaran dalam satu kali simpan.</p>
    </div>
    @can('view master-data-vouchers')
      <a href="{{ route('master-data.vouchers.index') }}" class="btn btn-outline-primary">
        <i class="mdi mdi-ticket-percent-outline me-1"></i> Kelola Voucher
      </a>
    @endcan
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card mb-4 border-primary border-opacity-25">
    <div class="card-body py-3">
      <div class="row g-3 align-items-center">
        <div class="col-md-4"><i class="mdi mdi-numeric-1-circle-outline text-primary me-1"></i><b>Detail promo</b><div class="small text-muted ms-4">Nama, periode, dan cabang</div></div>
        <div class="col-md-4"><i class="mdi mdi-numeric-2-circle-outline text-primary me-1"></i><b>Aturan diskon</b><div class="small text-muted ms-4">Nominal atau persentase</div></div>
        <div class="col-md-4"><i class="mdi mdi-numeric-3-circle-outline text-primary me-1"></i><b>Produk sasaran</b><div class="small text-muted ms-4">Pilih produk atau semua produk</div></div>
      </div>
    </div>
  </div>

  @if(auth()->user()?->can('create master-data-promos')
      && auth()->user()?->can('create master-data-discounts')
      && auth()->user()?->can('create master-data-product-discounts'))
  <form method="POST" action="{{ route('master-data.promo-setup.store') }}" class="card mb-4">
    @csrf
    <div class="card-header"><h5 class="mb-0">Buat Promo Baru</h5></div>
    <div class="card-body">
      <h6 class="text-primary mb-3">1. Detail Promo</h6>
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label">Nama Promo <span class="text-danger">*</span></label>
          <input type="text" name="promo_name" class="form-control @error('promo_name') is-invalid @enderror" value="{{ old('promo_name') }}" placeholder="Contoh: Promo Akhir Tahun" required>
          @error('promo_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">Kode Promo <span class="text-danger">*</span></label>
          <input type="text" name="promo_code" class="form-control @error('promo_code') is-invalid @enderror" value="{{ old('promo_code') }}" placeholder="AKHIRTAHUN25" required>
          @error('promo_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
          <label class="form-label">Mulai <span class="text-danger">*</span></label>
          <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
          @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
          <label class="form-label">Berakhir <span class="text-danger">*</span></label>
          <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
          @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-12">
          <label class="form-label">Catatan</label>
          <input type="text" name="promo_description" class="form-control" value="{{ old('promo_description') }}" placeholder="Opsional">
        </div>
        <div class="col-md-12">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="all_stores" id="allStores" value="1" @checked(old('all_stores', true))>
            <label class="form-check-label" for="allStores">Berlaku di semua cabang</label>
          </div>
          <div id="storeSelection">
            <label class="form-label">Pilih Cabang <span class="text-danger">*</span></label>
            <select name="store_ids[]" class="form-select js-select2 @error('store_ids') is-invalid @enderror" multiple data-placeholder="Pilih satu atau beberapa cabang">
              @foreach($stores as $store)
                <option value="{{ $store->id }}" @selected(in_array($store->id, old('store_ids', [])))>{{ $store->name }}{{ $store->code ? ' — '.$store->code : '' }}</option>
              @endforeach
            </select>
            @error('store_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>

      <hr class="my-4">
      <h6 class="text-primary mb-3">2. Aturan Diskon</h6>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Nama Diskon</label>
          <input type="text" name="discount_name" class="form-control @error('discount_name') is-invalid @enderror" value="{{ old('discount_name') }}" placeholder="Otomatis memakai nama promo bila dikosongkan">
          @error('discount_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipe <span class="text-danger">*</span></label>
          <select name="discount_type" id="discountType" class="form-select @error('discount_type') is-invalid @enderror" required>
            <option value="percentage" @selected(old('discount_type') === 'percentage')>Persentase (%)</option>
            <option value="fixed" @selected(old('discount_type') === 'fixed')>Nominal (Rp)</option>
          </select>
          @error('discount_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
          <label class="form-label">Nilai <span class="text-danger">*</span></label>
          <input type="number" name="discount_value" class="form-control @error('discount_value') is-invalid @enderror" value="{{ old('discount_value') }}" min="0" step="0.01" required>
          @error('discount_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3" id="maxDiscountWrap">
          <label class="form-label">Maks. Diskon (Rp)</label>
          <input type="number" name="max_discount" class="form-control @error('max_discount') is-invalid @enderror" value="{{ old('max_discount') }}" min="0" step="0.01" placeholder="Opsional">
          @error('max_discount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">Min. Belanja (Rp)</label>
          <input type="number" name="min_purchase" class="form-control @error('min_purchase') is-invalid @enderror" value="{{ old('min_purchase', 0) }}" min="0" step="0.01">
          @error('min_purchase')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      <hr class="my-4">
      <h6 class="text-primary mb-3">3. Produk Sasaran</h6>
      <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" name="all_products" id="allProducts" value="1" @checked(old('all_products'))>
        <label class="form-check-label" for="allProducts">Terapkan ke semua produk aktif</label>
      </div>
      <div id="productSelection">
        <label class="form-label">Pilih Produk <span class="text-danger">*</span></label>
        <select name="product_ids[]" class="form-select js-select2 @error('product_ids') is-invalid @enderror" multiple data-placeholder="Cari nama atau kode produk">
          @foreach($products as $product)
            <option value="{{ $product->id }}" @selected(in_array($product->id, old('product_ids', [])))>{{ $product->name }}{{ $product->code ? ' — '.$product->code : '' }}</option>
          @endforeach
        </select>
        <div class="form-text">Menampilkan hingga 500 produk. Gunakan Semua Produk bila cakupannya lebih luas.</div>
        @error('product_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="card-footer d-flex justify-content-end">
      <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i> Simpan Promo, Diskon & Produk</button>
    </div>
  </form>
  @else
    <div class="alert alert-warning">Anda belum memiliki semua izin untuk membuat promo terpadu.</div>
  @endif

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Promo Terbaru</h5>
      @can('view master-data-promos')<a href="{{ route('master-data.promos.index') }}" class="btn btn-sm btn-outline-secondary">Riwayat Promo</a>@endcan
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>Promo</th><th>Periode</th><th>Cakupan Cabang</th></tr></thead>
        <tbody>
          @forelse($recentPromos as $promo)
            <tr>
              <td><div class="fw-semibold">{{ $promo->name }}</div><small class="text-muted">{{ $promo->code }}</small></td>
              <td>{{ $promo->start_date?->format('d M Y') }} — {{ $promo->end_date?->format('d M Y') }}</td>
              <td><span class="badge bg-label-{{ $promo->all_store ? 'success' : 'info' }}">{{ $promo->all_store ? 'Semua Cabang' : 'Cabang Tertentu' }}</span></td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada promo.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('vendor-script')
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush

@push('page-script')
<script>
  $(function () {
    $('.js-select2').select2({ theme: 'bootstrap-5', width: '100%' });

    const syncStores = () => $('#storeSelection').toggle(!$('#allStores').is(':checked'));
    const syncProducts = () => $('#productSelection').toggle(!$('#allProducts').is(':checked'));
    const syncDiscountType = () => $('#maxDiscountWrap').toggle($('#discountType').val() === 'percentage');

    $('#allStores').on('change', syncStores);
    $('#allProducts').on('change', syncProducts);
    $('#discountType').on('change', syncDiscountType);
    syncStores();
    syncProducts();
    syncDiscountType();
  });
</script>
@endpush
