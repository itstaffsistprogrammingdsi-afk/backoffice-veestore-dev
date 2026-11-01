@extends('layouts/contentNavbarLayout')

@section('title','Ecommerce Orders')

@push('page-style')
<style>
  .table thead th {
    white-space: nowrap;
  }

  .filter-box {
    background: #f8f9fa;
    border: 1px dashed #d9dee3;
    border-radius: 0.75rem;
    padding: 1rem;
  }

  .table td,
  .table th {
    vertical-align: middle;
  }

  .order-stock-row {
    position: relative;
  }

  .order-stock-row > td:first-child {
    border-left: 4px solid transparent;
  }

  .order-stock-ready {
    background: rgba(34, 197, 94, .035);
  }

  .order-stock-ready > td:first-child {
    border-left-color: #22c55e;
  }

  .order-stock-issue {
    background: rgba(239, 68, 68, .055);
  }

  .order-stock-issue > td:first-child {
    border-left-color: #ef4444;
  }

  .order-stock-unassigned {
    background: rgba(245, 158, 11, .055);
  }

  .order-stock-unassigned > td:first-child {
    border-left-color: #f59e0b;
  }

  .order-stock-final > td:first-child {
    border-left-color: #94a3b8;
  }

  .handler-health-badge {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    margin-top: .35rem;
    padding: .28rem .55rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
  }

  /*
   * Jangan biarkan menu aksi membuat scrollbar vertikal di dalam tabel.
   * Scroll horizontal tetap dipertahankan untuk layar tablet dan mobile.
   */
  @media (min-width: 992px) {
    .orders-table-responsive {
      overflow: visible;
    }
  }

  #modalEdit .modal-content {
    max-height: calc(100vh - 3.5rem);
    overflow: hidden;
  }

  #modalEdit #formEdit {
    display: flex;
    flex-direction: column;
    min-height: 0;
    max-height: calc(100vh - 3.5rem);
  }

  #modalEdit .modal-header,
  #modalEdit .modal-footer {
    flex-shrink: 0;
    background: #fff;
    z-index: 2;
  }

  #modalEdit .modal-body {
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
    scrollbar-gutter: stable;
  }

  #modalEdit .modal-body::-webkit-scrollbar {
    width: 8px;
  }

  #modalEdit .modal-body::-webkit-scrollbar-thumb {
    background: #c8c2d1;
    border-radius: 999px;
  }

  #modalEdit .modal-body::-webkit-scrollbar-track {
    background: #f4f1f7;
  }

  @media (max-width: 575.98px) {
    #modalEdit .modal-content,
    #modalEdit #formEdit {
      max-height: calc(100vh - 1rem);
    }
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Ecommerce /</span> Orders
  </h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <i class="mdi mdi-check-circle-outline me-1"></i>
      {{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i>
      {{ session('error') }}
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i>
      Periksa kembali input Anda.
      <ul class="mb-0 mt-2 ps-3">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($stockIssue)
    <div class="alert alert-danger d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="fw-bold">
          <i class="mdi mdi-package-variant-remove me-1"></i>
          Menampilkan order dengan stok handler tidak mencukupi
        </div>
        <small>Gunakan menu Edit Orders untuk memindahkan handler ke Store atau Warehouse yang berstatus Siap.</small>
      </div>
      <a href="{{ route('ecommerce.orders.index') }}" class="btn btn-sm btn-outline-danger">
        Tampilkan Semua Order
      </a>
    </div>
  @endif

  <div class="card mb-3">
    <h5 class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>Filter Ecommerce Orders</div>

      <div class="d-flex gap-2 flex-wrap">
        <form method="GET"
              action="{{ route('ecommerce.orders.index') }}"
              class="d-flex gap-2 flex-wrap align-items-center">

          <input type="text"
                 name="q"
                 class="form-control form-control-sm"
                 value="{{ $q ?? '' }}"
                 placeholder="Cari order / produk / ID"
                 style="min-width: 280px;">

          <input type="hidden" name="from" value="{{ $from }}">
          <input type="hidden" name="to" value="{{ $to }}">
          <input type="hidden" name="status" value="{{ $status }}">
          <input type="hidden" name="handler_type" value="{{ $handlerType }}">
          <input type="hidden" name="store_id" value="{{ $storeId }}">
          <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
          @if($stockIssue)
            <input type="hidden" name="stock_issue" value="1">
          @endif

          <button type="button"
                  class="btn btn-outline-secondary btn-sm"
                  data-bs-toggle="collapse"
                  data-bs-target="#advancedFilterOrders">
            <i class="mdi mdi-filter-variant me-1"></i> Filter
          </button>

          <button type="submit"
                  class="btn btn-outline-secondary btn-sm"
                  title="Cari">
            <i class="mdi mdi-magnify"></i>
          </button>

          <a href="{{ route('ecommerce.orders.index') }}"
             class="btn btn-outline-secondary btn-sm">
            <i class="mdi mdi-refresh me-1"></i> Reset
          </a>
        </form>
      </div>
    </h5>

    <div class="px-4 pb-3">
      <div class="collapse {{ request()->hasAny(['q', 'from', 'to', 'status', 'handler_type', 'store_id', 'warehouse_id', 'stock_issue']) ? 'show' : '' }}"
           id="advancedFilterOrders">
        <div class="filter-box">
          <form method="GET" action="{{ route('ecommerce.orders.index') }}" class="row g-3">
            <div class="col-sm-6 col-md-3">
              <label class="form-label">Dari</label>
              <input type="date" name="from" class="form-control" value="{{ $from }}">
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Sampai</label>
              <input type="date" name="to" class="form-control" value="{{ $to }}">
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="">Semua Status</option>
                @foreach($statuses as $item)
                  <option value="{{ $item }}" {{ (string)$status === (string)$item ? 'selected' : '' }}>
                    {{ ucfirst($item) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Handler Type</label>
              <select name="handler_type" id="filter_handler_type" class="form-select">
                <option value="">Semua Handler</option>
                <option value="store" {{ (string)$handlerType === 'store' ? 'selected' : '' }}>Store</option>
                <option value="warehouse" {{ (string)$handlerType === 'warehouse' ? 'selected' : '' }}>Warehouse</option>
              </select>
            </div>

            <div class="col-sm-6 col-md-3" id="filter_store_wrap">
              <label class="form-label">Store</label>
              <select name="store_id" class="form-select">
                <option value="">Semua Store</option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" {{ (string)$storeId === (string)$store->id ? 'selected' : '' }}>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3" id="filter_warehouse_wrap">
              <label class="form-label">Warehouse</label>
              <select name="warehouse_id" class="form-select">
                <option value="">Semua Warehouse</option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" {{ (string)$warehouseId === (string)$warehouse->id ? 'selected' : '' }}>
                    {{ $warehouse->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-12 col-md-6">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q }}"
                     placeholder="Order number / product / ID">
            </div>

            <div class="col-12">
              <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       id="filter_stock_issue"
                       name="stock_issue"
                       value="1"
                       @checked($stockIssue)>
                <label class="form-check-label" for="filter_stock_issue">
                  Hanya tampilkan order dengan stok handler tidak mencukupi
                </label>
              </div>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">
              <button class="btn btn-primary">
                <i class="mdi mdi-magnify me-1"></i> Tampilkan
              </button>

              <a href="{{ route('ecommerce.orders.index') }}"
                 class="btn btn-outline-secondary">
                <i class="mdi mdi-refresh me-1"></i> Reset
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      Orders Ecommerce
    </div>

    <div class="table-responsive orders-table-responsive">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th width="70">#</th>
            <th>Order</th>
            <th>Tanggal Order</th>
            <th>Status</th>
            <th>Handled By</th>
            <th>Kondisi Handler</th>
            <th>Total</th>
            <th width="120" class="text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          @forelse($orders as $i => $order)
            @php
              $statusColor = [
                'pending'   => 'secondary',
                'paid'      => 'success',
                'processed' => 'info',
                'shipped'   => 'primary',
                'received'  => 'dark',
                'cancelled' => 'danger',
                'packing'   => 'warning',
              ][$order->status] ?? 'secondary';

              $hasHandler = (bool) ($order->handled_by_store_id || $order->handled_by_warehouse_id);
              $hasStockIssue = in_array((string) $order->id, $stockIssueOrderIds, true);
              $isFinal = in_array($order->status, ['shipped', 'received', 'cancelled'], true);

              if (!$hasHandler) {
                $handlerHealth = 'unassigned';
                $handlerHealthLabel = 'Belum ada handler';
                $handlerHealthIcon = 'mdi-account-question-outline';
                $handlerHealthClass = 'bg-label-warning text-warning';
              } elseif ($hasStockIssue) {
                $handlerHealth = 'issue';
                $handlerHealthLabel = 'Kendala stok';
                $handlerHealthIcon = 'mdi-package-variant-remove';
                $handlerHealthClass = 'bg-label-danger text-danger';
              } elseif ($isFinal) {
                $handlerHealth = 'final';
                $handlerHealthLabel = 'Order final';
                $handlerHealthIcon = 'mdi-check-decagram-outline';
                $handlerHealthClass = 'bg-label-secondary text-secondary';
              } else {
                $handlerHealth = 'ready';
                $handlerHealthLabel = 'Stok mencukupi';
                $handlerHealthIcon = 'mdi-package-variant-closed-check';
                $handlerHealthClass = 'bg-label-success text-success';
              }
            @endphp

            <tr class="order-stock-row order-stock-{{ $handlerHealth }}">
              <td>{{ $orders->firstItem() + $i }}</td>

              <td>
                <div class="fw-medium">{{ $order->order_number }}</div>
                @if($stockIssue)
                  <span class="badge bg-label-danger mt-1">
                    <i class="mdi mdi-alert-outline me-1"></i>Perlu ganti handler
                  </span>
                @endif
              </td>

              <td>
                {{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d-m-Y H:i') : '-' }}
              </td>

              <td>
                <span class="badge bg-label-{{ $statusColor }}">
                  {{ $order->status }}
                </span>
              </td>

              <td>
                @if($order->store)
                  <div>Store : {{ $order->store->name }}</div>
                @endif

                @if($order->warehouse)
                  <div>Warehouse : {{ $order->warehouse->name }}</div>
                @endif

                @if(!$order->store && !$order->warehouse)
                  <span class="text-muted">-</span>
                @endif
              </td>

              <td>
                <span class="handler-health-badge {{ $handlerHealthClass }}">
                  <i class="mdi {{ $handlerHealthIcon }}"></i>
                  {{ $handlerHealthLabel }}
                </span>
                @if($handlerHealth === 'issue')
                  <div class="small text-danger mt-1">Pindahkan ke lokasi berstatus Siap</div>
                @elseif($handlerHealth === 'unassigned')
                  <div class="small text-warning mt-1">Tentukan Store atau Warehouse</div>
                @endif
              </td>

              <td>Rp {{ number_format($order->grand_total) }}</td>

              <td class="text-center">
                <div class="dropdown">
                  <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="mdi mdi-dots-vertical"></i>
                  </button>

                  <div class="dropdown-menu">
                    <a href="javascript:void(0);"
                       class="dropdown-item btn-detail"
                       data-id="{{ $order->id }}"
                       data-bs-toggle="modal"
                       data-bs-target="#modalProducts">
                      <i class="mdi mdi-package-variant me-1"></i>
                      Detail Produk
                    </a>

                    @if(strtolower((string) $order->status) === 'paid')
                      <a href="javascript:void(0);"
                         class="dropdown-item btn-edit"
                         data-id="{{ $order->id }}"
                         data-order="{{ $order->order_number }}"
                         data-status="{{ $order->status }}"
                         data-store="{{ $order->handled_by_store_id }}"
                         data-warehouse="{{ $order->handled_by_warehouse_id }}"
                         data-bs-toggle="modal"
                         data-bs-target="#modalEdit">
                        <i class="mdi mdi-pencil-outline me-1"></i>
                        Edit Orders
                      </a>
                    @endif
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted">Belum ada data</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($orders->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="text-muted small">
          Menampilkan {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} data
        </div>
        {{ $orders->links() }}
      </div>
    @endif
  </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" id="formEdit">
        @csrf
        @method('PUT')

        <div class="modal-header">
          <h5 class="modal-title">
            <i class="mdi mdi-pencil-outline me-1"></i>
            Edit Order
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="order_id">

          <div class="mb-3">
            <label class="form-label">Order</label>
            <input type="text" id="order_number" class="form-control" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
              @foreach($statuses as $statusOption)
                <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Ditangani Oleh <span class="text-danger">*</span></label>
            <select id="handler_type" name="handler_type" class="form-select">
              <option value="store">Store</option>
              <option value="warehouse">Warehouse</option>
            </select>
            <small class="text-muted">Backoffice dapat memindahkan handler selama stok lokasi mencukupi.</small>
          </div>

          <div class="mb-3" id="store_wrap">
            <label class="form-label">Store</label>
            <select name="store_id" id="store_id" class="form-select">
              <option value="">Pilih Store</option>
              @foreach($stores as $store)
                <option value="{{ $store->id }}">{{ $store->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3" id="warehouse_wrap">
            <label class="form-label">Warehouse</label>
            <select name="warehouse_id" id="warehouse_id" class="form-select">
              <option value="">Pilih Warehouse</option>
              @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
              @endforeach
            </select>
          </div>

          <hr>

          <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
            <h6 class="mb-0">Ketersediaan Produk Order</h6>
            <span class="badge bg-label-primary" id="selectedLocationBadge">Pilih lokasi</span>
          </div>

          <div class="table-responsive">
            <table class="table table-sm">
              <thead class="table-light">
                <tr>
                  <th>Produk</th>
                  <th>Qty</th>
                  <th>Stok Tersedia</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="stockTable">
                <tr>
                  <td colspan="4" class="text-center text-muted">Pilih Store atau Warehouse</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
              <div>
                <h6 class="mb-1">Rekomendasi Lokasi Handler</h6>
                <small class="text-muted">Lokasi hijau memiliki seluruh stok yang dibutuhkan order.</small>
              </div>
            </div>
            <div id="locationAvailability" class="row g-2">
              <div class="col-12 text-center text-muted py-3">Memuat ketersediaan lokasi...</div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button"
                  class="btn btn-outline-secondary"
                  data-bs-dismiss="modal">
            Batal
          </button>

          <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline me-1"></i>
            Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL DETAIL PRODUK --}}
<div class="modal fade" id="modalProducts" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="mdi mdi-package-variant me-1"></i>
          Produk Order
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead class="table-light">
              <tr>
                <th>Produk</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Diskon</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody id="orderProductsBody"></tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="mdi mdi-close me-1"></i>Tutup
        </button>
      </div>

    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
const orders = @json($orders->items());
const stockUrlTemplate = @json(route('ecommerce.orders.stock', ['id' => '__ORDER_ID__']));
const updateUrlTemplate = @json(route('ecommerce.orders.update', ['id' => '__ORDER_ID__']));

let currentOrderId = null;
let currentOrderProducts = [];
let currentStockPayload = { products: [], stores: [], warehouses: [] };

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function toggleHandler() {
  const type = document.getElementById('handler_type').value;

  document.getElementById('store_wrap').style.display =
    type === 'store' ? '' : 'none';

  document.getElementById('warehouse_wrap').style.display =
    type === 'warehouse' ? '' : 'none';
}

function toggleFilterHandler() {
  const type = document.getElementById('filter_handler_type')?.value;

  const storeWrap = document.getElementById('filter_store_wrap');
  const warehouseWrap = document.getElementById('filter_warehouse_wrap');

  if (!storeWrap || !warehouseWrap) return;

  if (type === 'store') {
    storeWrap.style.display = '';
    warehouseWrap.style.display = 'none';
  } else if (type === 'warehouse') {
    storeWrap.style.display = 'none';
    warehouseWrap.style.display = '';
  } else {
    storeWrap.style.display = '';
    warehouseWrap.style.display = '';
  }
}

async function loadStock() {
  if (!currentOrderId) return;

  const handlerType = document.getElementById('handler_type').value;
  const storeId = document.getElementById('store_id').value;
  const warehouseId = document.getElementById('warehouse_id').value;

  const params = new URLSearchParams();
  params.append('handler_type', handlerType);

  if (storeId) params.append('store_id', storeId);
  if (warehouseId) params.append('warehouse_id', warehouseId);

  const tbody = document.getElementById('stockTable');
  tbody.innerHTML = '<tr><td colspan="4" class="text-center">Memuat stok...</td></tr>';

  try {
    const url = stockUrlTemplate.replace('__ORDER_ID__', encodeURIComponent(currentOrderId));
    const res = await fetch(`${url}?${params.toString()}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }

    currentStockPayload = await res.json();
    currentOrderProducts = Array.isArray(currentStockPayload.products)
      ? currentStockPayload.products
      : [];
    renderStock();
    renderLocationAvailability();
  } catch (e) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-danger text-center">Gagal memuat data stok</td></tr>';
    document.getElementById('locationAvailability').innerHTML =
      '<div class="col-12 text-center text-danger py-3">Ketersediaan lokasi tidak dapat dimuat.</div>';
    console.error(e);
  }
}

function renderStock() {
  const tbody = document.getElementById('stockTable');
  tbody.innerHTML = '';

  if (!Array.isArray(currentOrderProducts) || currentOrderProducts.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="4" class="text-center text-muted">Tidak ada produk</td></tr>';
    return;
  }

  const handlerType = document.getElementById('handler_type').value;
  const select = document.getElementById(handlerType === 'store' ? 'store_id' : 'warehouse_id');
  const selectedName = select.options[select.selectedIndex]?.text || 'Pilih lokasi';
  document.getElementById('selectedLocationBadge').textContent = selectedName;

  currentOrderProducts.forEach(p => {
    const stock = Number(p.selected_stock ?? 0);
    const required = Number(p.qty ?? 0);
    const enough = stock >= required;

    tbody.innerHTML += `
      <tr>
        <td class="fw-medium">${escapeHtml(p.product_name ?? '-')}</td>
        <td>${required}</td>
        <td>${stock}</td>
        <td>
          <span class="badge ${enough ? 'bg-label-success' : 'bg-label-danger'}">
            ${enough ? 'Mencukupi' : `Kurang ${required - stock}`}
          </span>
        </td>
      </tr>
    `;
  });
}

function renderLocationAvailability() {
  const handlerType = document.getElementById('handler_type').value;
  const locations = handlerType === 'warehouse'
    ? (currentStockPayload.warehouses ?? [])
    : (currentStockPayload.stores ?? []);
  const container = document.getElementById('locationAvailability');
  const selectedId = document.getElementById(handlerType === 'store' ? 'store_id' : 'warehouse_id').value;

  if (!Array.isArray(locations) || locations.length === 0) {
    container.innerHTML = '<div class="col-12 text-center text-muted py-3">Belum ada lokasi tersedia.</div>';
    return;
  }

  container.innerHTML = locations.map(location => {
    const selected = String(location.id) === String(selectedId);
    const ready = Boolean(location.can_fulfill);

    return `
      <div class="col-md-6">
        <button type="button"
                class="btn w-100 text-start border p-3 ${selected ? 'border-primary bg-label-primary' : 'bg-white'}"
                data-location-id="${escapeHtml(location.id)}"
                ${ready ? '' : 'disabled'}
                title="${ready ? 'Gunakan lokasi ini sebagai handler' : 'Stok belum mencukupi'}">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="fw-bold">${escapeHtml(location.name)}</div>
              <small class="text-muted">
                ${Number(location.fulfilled_products ?? 0)}/${Number(location.product_count ?? 0)} produk terpenuhi
                · ${Number(location.total_stock ?? 0)} item
              </small>
            </div>
            <span class="badge ${ready ? 'bg-label-success' : 'bg-label-danger'}">
              ${ready ? 'Siap' : 'Kurang stok'}
            </span>
          </div>
        </button>
      </div>
    `;
  }).join('');

  container.querySelectorAll('[data-location-id]:not([disabled])').forEach(button => {
    button.addEventListener('click', async function () {
      const select = document.getElementById(handlerType === 'store' ? 'store_id' : 'warehouse_id');
      select.value = this.dataset.locationId;
      await loadStock();
    });
  });
}

document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', async function () {
    const id = this.dataset.id;
    const order = this.dataset.order;
    const status = this.dataset.status;
    const store = this.dataset.store;
    const warehouse = this.dataset.warehouse;

    currentOrderId = id;

    document.getElementById('order_id').value = id;
    document.getElementById('order_number').value = order;
    document.getElementById('status').value = status;
    document.getElementById('formEdit').action =
      updateUrlTemplate.replace('__ORDER_ID__', encodeURIComponent(id));

    if (store) {
      document.getElementById('handler_type').value = 'store';
      document.getElementById('store_id').value = store;
      document.getElementById('warehouse_id').value = '';
    } else {
      document.getElementById('handler_type').value = 'warehouse';
      document.getElementById('warehouse_id').value = warehouse || '';
      document.getElementById('store_id').value = '';
    }

    toggleHandler();
    await loadStock();
  });
});

document.getElementById('handler_type').addEventListener('change', async function () {
  const type = this.value;

  if (type === 'store') {
    document.getElementById('warehouse_id').value = '';
  } else {
    document.getElementById('store_id').value = '';
  }

  toggleHandler();
  await loadStock();
});

document.getElementById('store_id').addEventListener('change', async function () {
  if (document.getElementById('handler_type').value === 'store') {
    await loadStock();
  }
});

document.getElementById('warehouse_id').addEventListener('change', async function () {
  if (document.getElementById('handler_type').value === 'warehouse') {
    await loadStock();
  }
});

document.querySelectorAll('.btn-detail').forEach(btn => {
  btn.addEventListener('click', function () {
    const id = this.dataset.id;
    const order = orders.find(o => o.id == id);
    const tbody = document.getElementById('orderProductsBody');

    tbody.innerHTML = '';

    if (!order || !order.details || order.details.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada produk</td></tr>';
      return;
    }

    order.details.forEach(item => {
      tbody.innerHTML += `
        <tr>
          <td>${item.product ? item.product.name : '-'}</td>
          <td>${item.quantity ?? 0}</td>
          <td>Rp ${Number(item.product_price ?? 0).toLocaleString()}</td>
          <td>Rp ${Number(item.total_discount ?? 0).toLocaleString()}</td>
          <td>Rp ${Number(item.total_price ?? 0).toLocaleString()}</td>
        </tr>
      `;
    });
  });
});

document.querySelectorAll('#modalProducts, #modalEdit').forEach(modalElement => {
  modalElement.addEventListener('show.bs.modal', function () {
    const searchInput = document.getElementById('navbarMenuSearchInput');
    const searchResults = document.getElementById('navbarMenuSearchResults');
    const searchBackdrop = document.getElementById('navbarSearchBackdrop');

    searchInput?.blur();
    searchResults?.classList.remove('show');
    searchBackdrop?.classList.remove('show');
  });
});

const modalEdit = document.getElementById('modalEdit');

modalEdit.addEventListener('hidden.bs.modal', function () {
  document.getElementById('formEdit').reset();
  document.getElementById('stockTable').innerHTML =
    '<tr><td colspan="4" class="text-center text-muted">Pilih Store atau Warehouse</td></tr>';
  document.getElementById('locationAvailability').innerHTML =
    '<div class="col-12 text-center text-muted py-3">Memuat ketersediaan lokasi...</div>';
  document.getElementById('selectedLocationBadge').textContent = 'Pilih lokasi';
  currentOrderId = null;
  currentOrderProducts = [];
  currentStockPayload = { products: [], stores: [], warehouses: [] };
  toggleHandler();
});

document.addEventListener('DOMContentLoaded', function () {
  toggleHandler();
  toggleFilterHandler();

  const filterHandlerType = document.getElementById('filter_handler_type');
  if (filterHandlerType) {
    filterHandlerType.addEventListener('change', toggleFilterHandler);
  }
});
</script>
@endsection
