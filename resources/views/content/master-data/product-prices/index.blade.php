@extends('layouts/contentNavbarLayout')

@section('title','Master Product - Product Prices')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

<style>
  .select2-container { width: 100% !important; }

  .btn-primary {
    background: linear-gradient(90deg, #7c3aed, #9333ea);
    border: none;
  }

  .btn-primary:hover { opacity: 0.9; }

  .swal2-container { z-index: 20000 !important; }
  .swal2-popup { z-index: 20001 !important; }

  .swal2-popup.custom-price-popup {
    border-radius: 18px !important;
    padding: 1.5rem 1.5rem 1.25rem !important;
  }

  .price-duplicate-box {
    text-align: left;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 14px 16px;
    margin-top: 10px;
  }

  .price-duplicate-box .title {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 10px;
  }

  .price-duplicate-grid {
    display: grid;
    grid-template-columns: 140px 1fr;
    row-gap: 8px;
    column-gap: 10px;
    font-size: 14px;
  }

  .price-duplicate-grid .label {
    color: #6b7280;
    font-weight: 600;
  }

  .price-duplicate-grid .value {
    color: #111827;
    font-weight: 500;
    word-break: break-word;
  }

  .price-duplicate-alert {
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    background: #fff7ed;
    border: 1px solid #fdba74;
    color: #9a3412;
    font-size: 14px;
    font-weight: 600;
  }

  .swal2-confirm.btn-update-price {
    background: linear-gradient(90deg, #7c3aed, #9333ea) !important;
    border: none !important;
    border-radius: 10px !important;
    padding: 10px 18px !important;
    font-weight: 600 !important;
  }

  .price-cell { min-width: 170px; }

  /* Kolom aksi selalu satu baris; tabel akan scroll horizontal bila ruang sempit. */
  .price-action-cell {
    min-width: 292px;
    white-space: nowrap;
  }

  .price-action-group {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    flex-wrap: nowrap;
  }

  .price-action-group .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 82px;
    white-space: nowrap;
  }

  .price-action-group .btn-delete-product-price {
    min-width: 112px;
  }

  @media (max-width: 767.98px) {
    .price-action-cell { min-width: 280px; }
    .price-action-group { gap: .3rem; }
    .price-action-group .btn { padding-inline: .5rem; }
  }

  .price-main {
    font-weight: 700;
    color: #111827;
  }

  .price-sub {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
  }

  .profit-positive {
    color: #16a34a;
    font-weight: 700;
  }

  .profit-negative {
    color: #dc2626;
    font-weight: 700;
  }

  .profit-neutral {
    color: #6b7280;
    font-weight: 700;
  }

  .empty-price {
    color: #9ca3af;
    font-style: italic;
  }

  .product-title {
    font-weight: 700;
    color: #111827;
  }

  .product-code {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
  }

  .detail-table th,
  .detail-table td {
    vertical-align: middle;
  }

  .badge-soft-active {
    background: #dcfce7;
    color: #166534;
  }

  .badge-soft-expired {
    background: #fee2e2;
    color: #991b1b;
  }

  .badge-soft-upcoming {
    background: #e0f2fe;
    color: #075985;
  }

  /* ===== Form input 3 tipe harga sekaligus ===== */
  .bulk-price-section {
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    background: #f8fafc;
    padding: 16px;
  }

  .bulk-price-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
  }

  .bulk-price-card {
    position: relative;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: #fff;
    padding: 16px;
    transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
  }

  .bulk-price-card:focus-within {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, .12);
    transform: translateY(-1px);
  }

  .bulk-price-card.base { border-top: 4px solid #64748b; }
  .bulk-price-card.store { border-top: 4px solid #7c3aed; }
  .bulk-price-card.ecommerce { border-top: 4px solid #0ea5e9; }

  .bulk-price-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 12px;
  }

  .bulk-price-title strong {
    color: #111827;
    font-size: 14px;
  }

  .bulk-price-help {
    min-height: 18px;
    margin-top: 6px;
    color: #6b7280;
    font-size: 12px;
  }

  .rupiah-input .input-group-text {
    min-width: 48px;
    justify-content: center;
    font-weight: 700;
  }

  .bulk-period-box {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px dashed #d1d5db;
  }

  @media (max-width: 991.98px) {
    .bulk-price-grid { grid-template-columns: 1fr; }
  }

</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Carbon;

  $todayDate = now()->format('Y-m-d');

  function rupiah_price($value) {
      if ($value === null || $value === '') return '-';
      return 'Rp ' . number_format((float) $value, 0, ',', '.');
  }

  function date_price($value) {
      if (!$value) return '∞';
      return Carbon::parse($value)->format('d/m/Y');
  }

  function active_status_price($validFrom, $validTo) {
      $today = Carbon::today();

      if ($validFrom && Carbon::parse($validFrom)->gt($today)) {
          return [
              'label' => 'Upcoming',
              'class' => 'badge-soft-upcoming',
          ];
      }

      if ($validTo && Carbon::parse($validTo)->lt($today)) {
          return [
              'label' => 'Expired',
              'class' => 'badge-soft-expired',
          ];
      }

      return [
          'label' => 'Active',
          'class' => 'badge-soft-active',
      ];
  }
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Master Product /</span> Product Prices
  </h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali form Anda.
      <button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0">Daftar Harga Produk Aktif</h5>
        <small class="text-muted">
          1 baris per produk, dikelompokkan berdasarkan produk yang sama.
        </small>
      </div>

      @can('create master-data-product-prices')
      <button type="button"
              class="btn btn-primary btn-sm"
              data-bs-toggle="modal"
              data-bs-target="#modalCreate">
        <i class="mdi mdi-plus me-1"></i> Tambah
      </button>
      @endcan
    </div>

    <div class="card-body border-bottom">
      <form method="GET" action="{{ route('master-data.product-prices.index') }}">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Cari Produk</label>
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   class="form-control"
                   placeholder="Nama / kode produk">
          </div>

          <div class="col-md-2">
            <label class="form-label">Dari</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control">
          </div>

          <div class="col-md-2">
            <label class="form-label">Sampai</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-control">
          </div>

          <div class="col-md-3">
            <label class="form-label">Tipe Harga</label>
            <select name="price_type" class="form-select">
              <option value="">Semua</option>
              @foreach($priceTypes as $pt)
                <option value="{{ $pt }}" @selected(request('price_type') == $pt)>
                  {{ strtoupper($pt) }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">
              <i class="mdi mdi-magnify me-1"></i> 
            </button>

            <a href="{{ route('master-data.product-prices.index') }}"
               class="btn btn-outline-secondary">
              Reset
            </a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:60px">#</th>
            <th>Produk</th>
            <th class="text-end">Base</th>
            <th class="text-end">Store</th>
            <th class="text-end">Ecommerce</th>
            <th class="text-center" style="width:270px">Aksi</th>
          </tr>
        </thead>

        <tbody class="table-border-bottom-0">
          @forelse($prices as $i => $row)
            @php
              $basePriceValue = $row->base_price !== null ? (float) $row->base_price : null;
              $modalId = 'modalDetailPrice_' . str_replace('-', '_', $row->product_id);
              $details = $allPriceDetails[$row->product_id] ?? collect();

              $baseRow = $row->prices_by_type['base'] ?? null;
              $storeRow = $row->prices_by_type['store'] ?? null;
              $ecommerceRow = $row->prices_by_type['ecommerce'] ?? null;

              $representativePrice =
                  $baseRow
                  ?? $storeRow
                  ?? $ecommerceRow
                  ?? $details->first();

              $editValidFrom =
                  $representativePrice?->valid_from?->format('Y-m-d');

              $editValidTo =
                  $representativePrice?->valid_to?->format('Y-m-d');
            @endphp

            <tr>
              <td>{{ ($prices->currentPage() - 1) * $prices->perPage() + $i + 1 }}</td>

              <td>
                <div class="d-flex align-items-center">
                  <i class="mdi mdi-package-variant-closed mdi-22px text-primary me-2"></i>

                  <div>
                    <div class="product-title">{{ $row->product_name ?? '-' }}</div>
                    <div class="product-code">{{ $row->product_code ?? '-' }}</div>
                  </div>
                </div>
              </td>

              @foreach($displayTypes as $type)
                @php
                  $priceRow = $row->prices_by_type[$type] ?? null;
                  $price = $priceRow ? (float) $priceRow->price : null;
                  $diff = ($price !== null && $basePriceValue !== null) ? $price - $basePriceValue : null;
                @endphp

                <td class="text-end price-cell">
                  @if($priceRow)
                    <div class="price-main">{{ rupiah_price($price) }}</div>

                    @if($type !== 'base')
                      <div class="price-sub">
                        Selisih dari Base:
                        @if($basePriceValue === null)
                          <span class="text-muted">Base tidak ada</span>
                        @elseif($diff > 0)
                          <span class="profit-positive">+{{ rupiah_price($diff) }}</span>
                        @elseif($diff < 0)
                          <span class="profit-negative">-{{ rupiah_price(abs($diff)) }}</span>
                        @else
                          <span class="profit-neutral">{{ rupiah_price(0) }}</span>
                        @endif
                      </div>
                    @endif

                    <div class="price-sub">
                      {{ date_price($priceRow->valid_from) }} —
                      {{ date_price($priceRow->valid_to) }}
                    </div>
                  @else
                    <span class="empty-price">Tidak ada harga aktif</span>
                  @endif
                </td>
              @endforeach

              <td class="text-center price-action-cell">
                <div class="price-action-group">
                  <button type="button"
                          class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#{{ $modalId }}">
                    <i class="mdi mdi-eye-outline me-1"></i> Detail
                  </button>

                  @can('update master-data-product-prices')
                    @if($representativePrice)
                      <button type="button"
                              class="btn btn-sm btn-outline-warning btn-edit-product-price"
                              data-update-url="{{ route('master-data.product-prices.update', $representativePrice->id) }}"
                              data-id="{{ $representativePrice->id }}"
                              data-product="{{ $row->product_id }}"
                              data-base="{{ $baseRow?->price ?? '' }}"
                              data-store="{{ $storeRow?->price ?? '' }}"
                              data-ecommerce="{{ $ecommerceRow?->price ?? '' }}"
                              data-from="{{ $editValidFrom }}"
                              data-to="{{ $editValidTo }}"
                              data-bs-toggle="modal"
                              data-bs-target="#modalEdit">
                        <i class="mdi mdi-pencil-outline me-1"></i> Edit
                      </button>
                    @endif
                  @endcan

                  @can('delete master-data-product-prices')
                    @if($representativePrice)
                      <button type="button"
                              class="btn btn-sm btn-outline-danger btn-delete-product-price"
                              data-product-name="{{ $row->product_name ?? 'produk ini' }}"
                              data-delete-url="{{ route('master-data.product-prices.destroy', $representativePrice->id) }}">
                        <i class="mdi mdi-trash-can-outline me-1"></i> Hapus Data
                      </button>
                    @endif
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                Tidak ada harga aktif yang ditampilkan.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-end">
      {{ $prices->appends([
          'q'          => $q ?? request('q'),
          'price_type' => $priceType ?? request('price_type'),
          'from'       => request('from'),
          'to'         => request('to'),
        ])->links()
      }}
    </div>
  </div>
</div>

{{-- DETAIL MODAL PER PRODUK --}}
@foreach($prices as $row)
  @php
    $basePriceValue = $row->base_price !== null ? (float) $row->base_price : null;
    $modalId = 'modalDetailPrice_' . str_replace('-', '_', $row->product_id);
    $details = $allPriceDetails[$row->product_id] ?? collect();
  @endphp

  <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-1">
              <i class="mdi mdi-package-variant-closed text-primary me-1"></i>
              Detail Harga Produk
            </h5>

            <div class="text-muted small">
              {{ $row->product_name }} — {{ $row->product_code }}
            </div>
          </div>

          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <div class="border rounded p-3">
                <div class="text-muted small">Produk</div>
                <div class="fw-bold">{{ $row->product_name }}</div>
                <div class="small text-muted">{{ $row->product_code }}</div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="border rounded p-3">
                <div class="text-muted small">Tipe Harga</div>
                <div class="fw-bold">{{ $details->count() }} data</div>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table detail-table">
              <thead class="table-light">
                <tr>
                  <th style="width:60px">#</th>
                  <th>Tipe</th>
                  <th class="text-end">Harga</th>
                  <th class="text-end">Selisih Dari Base</th>
                  <th>Valid From</th>
                  <th>Expired / Valid To</th>
                  <th>Status</th>
                </tr>
              </thead>

              <tbody>
                @forelse($details as $dIndex => $detail)
                  @php
                    $detailPrice = (float) $detail->price;
                    $diff = $basePriceValue !== null ? $detailPrice - $basePriceValue : null;
                    $status = active_status_price($detail->valid_from, $detail->valid_to);
                  @endphp

                  <tr>
                    <td>{{ $dIndex + 1 }}</td>

                    <td>
                      <span class="badge bg-label-primary text-uppercase">
                        {{ $detail->price_type }}
                      </span>
                    </td>

                    <td class="text-end fw-bold">
                      {{ rupiah_price($detail->price) }}
                    </td>

                    <td class="text-end">
                      @if($detail->price_type === 'base')
                        <span class="profit-neutral">-</span>
                      @elseif($basePriceValue === null)
                        <span class="text-muted">Base tidak ada</span>
                      @elseif($diff > 0)
                        <span class="profit-positive">+{{ rupiah_price($diff) }}</span>
                      @elseif($diff < 0)
                        <span class="profit-negative">-{{ rupiah_price(abs($diff)) }}</span>
                      @else
                        <span class="profit-neutral">{{ rupiah_price(0) }}</span>
                      @endif
                    </td>

                    <td>{{ date_price($detail->valid_from) }}</td>
                    <td>{{ date_price($detail->valid_to) }}</td>

                    <td>
                      <span class="badge {{ $status['class'] }}">
                        {{ $status['label'] }}
                      </span>
                    </td>

                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted">
                      Tidak ada detail harga.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
@endforeach


@can('create master-data-product-prices')
<div class="modal fade" id="modalCreate" tabindex="-1" aria-labelledby="modalCreateLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formCreate" method="POST" action="{{ route('master-data.product-prices.store') }}">
        @csrf
        <input type="hidden" name="_from" value="create">

        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="modalCreateLabel">
              <i class="mdi mdi-tag-multiple-outline me-1"></i>
              Tambah Harga Produk
            </h5>
            <small class="text-muted">
              Isi harga Base, Store, dan Ecommerce sekaligus dalam satu kali simpan.
            </small>
          </div>

          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          @if($errors->any() && old('_from') === 'create')
            <div class="alert alert-danger">
              <div class="fw-semibold mb-1">
                Data harga belum dapat disimpan.
              </div>
              <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">
                Produk <span class="text-danger">*</span>
              </label>

              <select
                class="form-select js-select2-product @error('product_id') is-invalid @enderror"
                id="create_product"
                name="product_id"
                required>
                <option value="">- Pilih Produk -</option>
                @foreach($products as $p)
                  <option
                    value="{{ $p->id }}"
                    @selected(old('_from') === 'create' && old('product_id') == $p->id)>
                    {{ $p->name }} ({{ $p->code }})
                  </option>
                @endforeach
              </select>

              @error('product_id')
                @if(old('_from') === 'create')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @endif
              @enderror
            </div>

            <div class="col-12">
              <div class="bulk-price-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <div class="fw-bold">Harga berdasarkan tipe</div>
                    <small class="text-muted">
                      Ketiga harga wajib diisi dan akan disimpan bersamaan.
                    </small>
                  </div>
                </div>

                <div class="bulk-price-grid">
                  {{-- BASE --}}
                  <div class="bulk-price-card base">
                    <div class="bulk-price-title">
                      <strong>
                        <i class="mdi mdi-database-outline me-1"></i>
                        BASE
                      </strong>
                      <span class="badge bg-label-secondary">Dasar</span>
                    </div>

                    <label for="create_price_base" class="form-label">
                      Harga Base <span class="text-danger">*</span>
                    </label>

                    <div class="input-group rupiah-input">
                      <span class="input-group-text">Rp</span>
                      <input
                        type="number"
                        name="prices[base]"
                        id="create_price_base"
                        value="{{ old('_from') === 'create' ? old('prices.base') : '' }}"
                        min="0"
                        step="1"
                        class="form-control @error('prices.base') is-invalid @enderror"
                        placeholder="0"
                        required>
                    </div>

                    <div class="bulk-price-help">
                      Harga modal atau harga dasar produk.
                    </div>

                    @error('prices.base')
                      @if(old('_from') === 'create')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                      @endif
                    @enderror
                  </div>

                  {{-- STORE --}}
                  <div class="bulk-price-card store">
                    <div class="bulk-price-title">
                      <strong>
                        <i class="mdi mdi-store-outline me-1"></i>
                        STORE
                      </strong>
                      <span class="badge bg-label-primary">Toko</span>
                    </div>

                    <label for="create_price_store" class="form-label">
                      Harga Store <span class="text-danger">*</span>
                    </label>

                    <div class="input-group rupiah-input">
                      <span class="input-group-text">Rp</span>
                      <input
                        type="number"
                        name="prices[store]"
                        id="create_price_store"
                        value="{{ old('_from') === 'create' ? old('prices.store') : '' }}"
                        min="0"
                        step="1"
                        class="form-control @error('prices.store') is-invalid @enderror"
                        placeholder="0"
                        required>
                    </div>

                    <div class="bulk-price-help">
                      Harga jual pada toko atau outlet.
                    </div>

                    @error('prices.store')
                      @if(old('_from') === 'create')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                      @endif
                    @enderror
                  </div>

                  {{-- ECOMMERCE --}}
                  <div class="bulk-price-card ecommerce">
                    <div class="bulk-price-title">
                      <strong>
                        <i class="mdi mdi-cart-outline me-1"></i>
                        ECOMMERCE
                      </strong>
                      <span class="badge bg-label-info">Online</span>
                    </div>

                    <label for="create_price_ecommerce" class="form-label">
                      Harga Ecommerce <span class="text-danger">*</span>
                    </label>

                    <div class="input-group rupiah-input">
                      <span class="input-group-text">Rp</span>
                      <input
                        type="number"
                        name="prices[ecommerce]"
                        id="create_price_ecommerce"
                        value="{{ old('_from') === 'create' ? old('prices.ecommerce') : '' }}"
                        min="0"
                        step="1"
                        class="form-control @error('prices.ecommerce') is-invalid @enderror"
                        placeholder="0"
                        required>
                    </div>

                    <div class="bulk-price-help">
                      Harga jual untuk marketplace atau website.
                    </div>

                    @error('prices.ecommerce')
                      @if(old('_from') === 'create')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                      @endif
                    @enderror
                  </div>
                </div>

                <div class="bulk-period-box">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">
                        Berlaku dari <span class="text-danger">*</span>
                      </label>

                      <input
                        type="date"
                        name="valid_from"
                        value="{{ old('_from') === 'create' ? old('valid_from') : '' }}"
                        class="form-control js-valid-from @error('valid_from') is-invalid @enderror"
                        min="{{ $todayDate }}"
                        required>

                      @error('valid_from')
                        @if(old('_from') === 'create')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                      @enderror

                      <div class="form-text">
                        Tanggal berlaku digunakan untuk ketiga tipe harga.
                      </div>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">
                        Berlaku sampai
                      </label>

                      <input
                        type="date"
                        name="valid_to"
                        value="{{ old('_from') === 'create' ? old('valid_to') : '' }}"
                        class="form-control js-valid-to @error('valid_to') is-invalid @enderror"
                        min="{{ $todayDate }}">

                      @error('valid_to')
                        @if(old('_from') === 'create')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                      @enderror

                      <div class="form-text">
                        Kosongkan bila harga tidak memiliki batas akhir.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">
            Batal
          </button>

          <button class="btn btn-primary" type="submit" id="btnCreatePrice">
            <i class="mdi mdi-content-save-outline me-1"></i>
            Simpan 3 Harga
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan


@can('update master-data-product-prices')
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="formEdit" method="POST" action="#" novalidate>
        @csrf
        @method('PUT')

        <input type="hidden" name="_from" value="edit">
        <input type="hidden" id="edit_id">
        <input type="hidden" name="product_id" id="edit_product_id">

        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="modalEditLabel">
              <i class="mdi mdi-tag-edit-outline me-1"></i>
              Edit Harga Produk
            </h5>

            <small class="text-muted">
              Ubah harga Base, Store, dan Ecommerce sekaligus.
            </small>
          </div>

          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Produk</label>

              <select
                class="form-select js-select2-product"
                id="edit_product"
                disabled>
                <option value="">- Pilih Produk -</option>
                @foreach($products as $p)
                  <option value="{{ $p->id }}">
                    {{ $p->name }} ({{ $p->code }})
                  </option>
                @endforeach
              </select>

              <div class="form-text">
                Produk tidak dapat diubah saat mengedit harga.
              </div>
            </div>

            <div class="col-12">
              <div class="bulk-price-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <div class="fw-bold">Harga berdasarkan tipe</div>
                    <small class="text-muted">
                      Ketiga harga diperbarui dalam satu kali simpan.
                    </small>
                  </div>
                </div>

                <div class="bulk-price-grid">
                  <div class="bulk-price-card base">
                    <div class="bulk-price-title">
                      <strong>
                        <i class="mdi mdi-database-outline me-1"></i>
                        BASE
                      </strong>
                      <span class="badge bg-label-secondary">Dasar</span>
                    </div>

                    <label for="edit_price_base" class="form-label">
                      Harga Base <span class="text-danger">*</span>
                    </label>

                    <div class="input-group rupiah-input">
                      <span class="input-group-text">Rp</span>
                      <input type="number"
                             name="prices[base]"
                             id="edit_price_base"
                             min="0"
                             step="1"
                             class="form-control"
                             placeholder="0"
                             required>
                    </div>

                    <div class="bulk-price-help">
                      Harga modal atau harga dasar produk.
                    </div>
                  </div>

                  <div class="bulk-price-card store">
                    <div class="bulk-price-title">
                      <strong>
                        <i class="mdi mdi-store-outline me-1"></i>
                        STORE
                      </strong>
                      <span class="badge bg-label-primary">Toko</span>
                    </div>

                    <label for="edit_price_store" class="form-label">
                      Harga Store <span class="text-danger">*</span>
                    </label>

                    <div class="input-group rupiah-input">
                      <span class="input-group-text">Rp</span>
                      <input type="number"
                             name="prices[store]"
                             id="edit_price_store"
                             min="0"
                             step="1"
                             class="form-control"
                             placeholder="0"
                             required>
                    </div>

                    <div class="bulk-price-help">
                      Harga jual pada toko atau outlet.
                    </div>
                  </div>

                  <div class="bulk-price-card ecommerce">
                    <div class="bulk-price-title">
                      <strong>
                        <i class="mdi mdi-cart-outline me-1"></i>
                        ECOMMERCE
                      </strong>
                      <span class="badge bg-label-info">Online</span>
                    </div>

                    <label for="edit_price_ecommerce" class="form-label">
                      Harga Ecommerce <span class="text-danger">*</span>
                    </label>

                    <div class="input-group rupiah-input">
                      <span class="input-group-text">Rp</span>
                      <input type="number"
                             name="prices[ecommerce]"
                             id="edit_price_ecommerce"
                             min="0"
                             step="1"
                             class="form-control"
                             placeholder="0"
                             required>
                    </div>

                    <div class="bulk-price-help">
                      Harga jual untuk marketplace atau website.
                    </div>
                  </div>
                </div>

                <div class="bulk-period-box">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">
                        Berlaku dari <span class="text-danger">*</span>
                      </label>

                      <input type="date"
                             name="valid_from"
                             id="edit_from"
                             class="form-control js-valid-from"
                             required>

                      <div class="form-text">
                        Tanggal berlaku digunakan untuk ketiga tipe harga.
                      </div>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">
                        Berlaku sampai
                      </label>

                      <input type="date"
                             name="valid_to"
                             id="edit_to"
                             class="form-control js-valid-to">

                      <div class="form-text">
                        Kosongkan bila harga tidak memiliki batas akhir.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary"
                  type="button"
                  data-bs-dismiss="modal">
            Batal
          </button>

          <button class="btn btn-primary" type="submit">
            <i class="mdi mdi-content-save-outline me-1"></i>
            Update 3 Harga
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

<form id="formDelete" method="POST" class="d-none">
  @csrf
  @method('DELETE')
</form>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@push('page-script')
<script>
(function($) {
  'use strict';

  const todayDate = "{{ $todayDate }}";

  function initSelect2In(modalEl) {
    const $modal = $(modalEl);

    $modal.find('.js-select2-product').each(function() {
      const $el = $(this);

      if ($el.hasClass('select2-hidden-accessible')) return;

      $el.select2({
        theme: 'bootstrap-5',
        placeholder: '- Pilih Produk -',
        allowClear: true,
        dropdownParent: $modal,
        width: '100%'
      });
    });
  }

  function esc(str) {
    if (str === null || str === undefined || str === '') return '-';

    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatNumber(value) {
    const number = Number(value || 0);

    return number.toLocaleString('id-ID', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    });
  }

  function formatDateDisplay(dateValue) {
    if (!dateValue) return 'Tanpa batas';

    const value = String(dateValue);

    if (value.includes('/')) {
      return esc(value);
    }

    const parts = value.split('-');

    if (parts.length === 3) {
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    return esc(value);
  }

  function formatPeriod(validFrom, validTo) {
    return `${formatDateDisplay(validFrom)} — ${validTo ? formatDateDisplay(validTo) : 'Tanpa batas'}`;
  }

  function buildConflictHtml(res) {
    const conflicts = Array.isArray(res.conflicts) && res.conflicts.length
      ? res.conflicts
      : (res.conflict ? [res.conflict] : []);

    if (!conflicts.length) {
      return `
        <div class="price-duplicate-alert">
          Periode harga yang dipilih bentrok dengan data yang sudah ada.
        </div>
      `;
    }

    const items = conflicts.map(conflict => {
      const product = conflict.product || {};
      const productName = product.name
        ? `${esc(product.name)}${product.code ? ' (' + esc(product.code) + ')' : ''}`
        : '-';

      return `
        <div class="price-duplicate-box">
          <div class="title">
            Tipe ${esc(String(conflict.price_type || '').toUpperCase())}
          </div>

          <div class="price-duplicate-grid">
            <div class="label">Produk</div>
            <div class="value">${productName}</div>

            <div class="label">Tipe harga</div>
            <div class="value">${esc(conflict.price_type)}</div>

            <div class="label">Harga existing</div>
            <div class="value">Rp ${formatNumber(conflict.price)}</div>

            <div class="label">Periode existing</div>
            <div class="value">
              ${formatPeriod(conflict.valid_from, conflict.valid_to)}
            </div>
          </div>
        </div>
      `;
    }).join('');

    return `
      ${items}
      <div class="price-duplicate-alert">
        Ubah periode tanggal agar tidak bertabrakan dengan harga yang sudah tersimpan.
      </div>
    `;
  }

  function showConflictAlert(res) {
    return Swal.fire({
      title: 'Tidak Bisa Disimpan',
      html: buildConflictHtml(res),
      icon: 'warning',
      confirmButtonText: 'Mengerti',
      allowOutsideClick: false,
      heightAuto: false,
      customClass: {
        popup: 'custom-price-popup',
        confirmButton: 'btn-update-price'
      }
    });
  }

  function showSimpleError(message) {
    return Swal.fire({
      icon: 'error',
      title: 'Terjadi Kesalahan',
      text: message || 'Terjadi kesalahan.',
      confirmButtonText: 'Tutup',
      heightAuto: false
    });
  }

  function updateDateMin(form) {
    const validFrom =
      form.querySelector('.js-valid-from');

    const validTo =
      form.querySelector('.js-valid-to');

    if (!validFrom || !validTo) {
      return;
    }

    const isEdit =
      form.id === 'formEdit';

    /*
     * Tambah: tanggal mulai minimal hari ini.
     * Edit: tanggal lama tetap boleh digunakan dan dapat diubah.
     */
    if (isEdit) {
      validFrom.removeAttribute('min');
    } else {
      validFrom.min = todayDate;
    }

    if (validFrom.value) {
      validTo.min = validFrom.value;

      if (
        validTo.value
        && validTo.value < validFrom.value
      ) {
        validTo.value = validFrom.value;
      }
    } else if (isEdit) {
      validTo.removeAttribute('min');
    } else {
      validTo.min = todayDate;
    }
  }

  function bindDateLock(form) {
    const validFrom = form.querySelector('.js-valid-from');
    const validTo = form.querySelector('.js-valid-to');

    if (!validFrom || !validTo) return;

    updateDateMin(form);

    validFrom.addEventListener('change', function() {
      updateDateMin(form);
    });

    validTo.addEventListener('change', function() {
      updateDateMin(form);
    });
  }

  async function checkPeriod(form) {
    const formData = new FormData(form);
    const editIdEl = form.querySelector('#edit_id');

    const payload = {
      id: editIdEl ? editIdEl.value : '',
      product_id: formData.get('product_id'),
      valid_from: formData.get('valid_from'),
      valid_to: formData.get('valid_to')
    };

    const isBulkForm = form.id === 'formCreate' || form.id === 'formEdit';

    if (isBulkForm) {
      payload.prices = {
        base: formData.get('prices[base]'),
        store: formData.get('prices[store]'),
        ecommerce: formData.get('prices[ecommerce]')
      };
    }

    const response = await fetch("{{ route('master-data.product-prices.check-duplicate') }}", {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    if (!response.ok) {
      let message = 'Gagal memeriksa periode harga.';

      try {
        const errorJson = await response.json();

        if (errorJson.message) {
          message = errorJson.message;
        }

        if (errorJson.errors) {
          const firstKey = Object.keys(errorJson.errors)[0];

          if (firstKey && errorJson.errors[firstKey] && errorJson.errors[firstKey][0]) {
            message = errorJson.errors[firstKey][0];
          }
        }
      } catch (e) {}

      throw new Error(message);
    }

    return await response.json();
  }

  async function handleSubmit(form, e) {
    e.preventDefault();

    updateDateMin(form);

    const productId =
      form.querySelector('[name="product_id"]')?.value || '';

    const validFrom =
      form.querySelector('[name="valid_from"]')?.value || '';

    const prices = [
      form.querySelector('[name="prices[base]"]')?.value,
      form.querySelector('[name="prices[store]"]')?.value,
      form.querySelector('[name="prices[ecommerce]"]')?.value,
    ];

    if (!productId) {
      await showSimpleError('Produk wajib dipilih.');
      return false;
    }

    if (!validFrom) {
      await showSimpleError('Tanggal berlaku dari wajib diisi.');
      return false;
    }

    if (prices.some(value => value === null || value === '')) {
      await showSimpleError(
        'Harga Base, Store, dan Ecommerce wajib diisi.'
      );
      return false;
    }

    try {
      const res = await checkPeriod(form);

      if (res.blocked) {
        await showConflictAlert(res);
        return false;
      }

      form.submit();
      return true;
    } catch (error) {
      await showSimpleError(error.message || 'Terjadi kesalahan.');
      return false;
    }
  }

  initSelect2In('#modalCreate');
  initSelect2In('#modalEdit');

  const formCreate = document.getElementById('formCreate');
  const formEdit = document.getElementById('formEdit');

  if (formCreate) {
    bindDateLock(formCreate);

    formCreate.addEventListener('submit', function(e) {
      handleSubmit(formCreate, e);
    });
  }

  if (formEdit) {
    bindDateLock(formEdit);

    formEdit.addEventListener('submit', function(e) {
      handleSubmit(formEdit, e);
    });
  }

  const hasCreateErrors = @json($errors->any() && old('_from') === 'create');

  $('#modalCreate').on('shown.bs.modal', function() {
    initSelect2In(this);

    if (formCreate) {
      if (!hasCreateErrors) {
        formCreate.reset();
        $('#create_product').val('').trigger('change');
      } else {
        $('#create_product')
          .val(@json(old('product_id')))
          .trigger('change');
      }

      const validFrom = formCreate.querySelector('.js-valid-from');
      const validTo = formCreate.querySelector('.js-valid-to');

      if (validFrom) validFrom.min = todayDate;
      if (validTo) validTo.min = validFrom?.value || todayDate;

      updateDateMin(formCreate);
    }
  });

  $('#modalEdit').on('show.bs.modal shown.bs.modal', function() {
    initSelect2In(this);

    if (!formEdit) {
      return;
    }

    const validFrom =
      formEdit.querySelector('.js-valid-from');

    const validTo =
      formEdit.querySelector('.js-valid-to');

    /*
     * Tanggal lama harus tetap valid saat edit.
     * Bersihkan atribut dan property min yang mungkin sudah
     * dibuat browser atau JavaScript sebelumnya.
     */
    if (validFrom) {
      validFrom.removeAttribute('min');
      validFrom.min = '';
    }

    if (validTo) {
      validTo.removeAttribute('min');

      if (validFrom?.value) {
        validTo.min = validFrom.value;
      }
    }
  });

  document.addEventListener('click', function(e) {
    const editBtn =
      e.target.closest('.btn-edit-product-price');

    if (editBtn) {
      const form =
        document.getElementById('formEdit');

      form.action =
        editBtn.dataset.updateUrl || '#';

      document.getElementById('edit_id').value =
        editBtn.dataset.id || '';

      document.getElementById('edit_product_id').value =
        editBtn.dataset.product || '';

      $('#edit_product')
        .val(editBtn.dataset.product || '')
        .trigger('change');

      document.getElementById('edit_price_base').value =
        editBtn.dataset.base || '';

      document.getElementById('edit_price_store').value =
        editBtn.dataset.store || '';

      document.getElementById('edit_price_ecommerce').value =
        editBtn.dataset.ecommerce || '';

      const editFrom =
        document.getElementById('edit_from');

      const editTo =
        document.getElementById('edit_to');

      editFrom.removeAttribute('min');
      editFrom.min = '';

      editTo.removeAttribute('min');

      editFrom.value =
        editBtn.dataset.from || '';

      editTo.value =
        editBtn.dataset.to || '';

      if (editFrom.value) {
        editTo.min = editFrom.value;
      }

      form.dataset.originalFrom =
        editBtn.dataset.from || '';

      updateDateMin(form);
    }

    const deleteBtn =
      e.target.closest('.btn-delete-product-price');

    if (deleteBtn) {
      e.preventDefault();

      const productName =
        deleteBtn.dataset.productName || 'produk ini';

      Swal.fire({
        icon: 'warning',
        title: 'Hapus Semua Harga?',
        html:
          `Seluruh data harga untuk <b>${esc(productName)}</b> ` +
          'akan dihapus dan tidak dapat dikembalikan.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus Data',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        allowOutsideClick: false,
        heightAuto: false
      }).then((result) => {
        if (!result.isConfirmed) {
          return;
        }

        const form =
          document.getElementById('formDelete');

        form.action =
          deleteBtn.dataset.deleteUrl || '#';

        form.submit();
      });
    }
  });


  if (@json(old('_from')) === 'create') {
    bootstrap.Modal.getOrCreateInstance(
      document.getElementById('modalCreate')
    ).show();
  }

})(jQuery);
</script>
@endpush
