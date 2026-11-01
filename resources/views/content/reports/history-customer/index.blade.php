@extends('layouts/contentNavbarLayout')

@section('title','CRM - Customer History')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
.select2-container {
    width: 100% !important;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
@endpush

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4"><span class="text-muted fw-light">CRM /</span> Customer History</h4>

    {{-- FILTERS --}}
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="fw-medium">Filter</span>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.history-customer.index') }}" class="row g-3 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Dari</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Channel</label>
                    <select name="channel" id="channel-select" class="form-select">
                        <option value="pos" {{ $channel==='pos'  ? 'selected' : '' }}>Store (POS)</option>
                        <option value="ecom" {{ $channel==='ecom' ? 'selected' : '' }}>E-commerce</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 {{ $channel==='ecom' ? 'd-none' : '' }}" id="storeFilterGroup">
                    <label class="form-label">Store (POS)</label>
                    <select name="store_id" class="form-select js-select2-store"
                        {{ $channel==='ecom' ? 'disabled' : '' }}>
                        <option value="">Semua</option>
                        @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ ($storeId ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
               <!--  <div class="col-sm-6 col-md-3">
                    <label class="form-label">Customer ID</label>
                    <input type="text" class="form-control" name="customer_id" value="{{ $custId }}"
                        placeholder="Opsional">
                </div> -->
                <div class="col-sm-6 col-md-3">
                    <label class="form-label">Cari Nama/WA/Produk</label>
                    <input type="text" class="form-control" name="q" value="{{ $search }}"
                        placeholder="cth: Rani / 08xxx / Nama Produk">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary"><i class="mdi mdi-magnify me-1"></i> Tampilkan</button>
                    <a href="{{ route('reports.history-customer.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- TAB --}}
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $channel==='pos' ? 'active' : '' }}"
                href="{{ route('reports.history-customer.index', array_merge(request()->except(['page']), ['channel'=>'pos'])) }}">
                Store (POS)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $channel==='ecom' ? 'active' : '' }}"
                href="{{ route('reports.history-customer.index', array_merge(request()->except(['page']), ['channel'=>'ecom'])) }}">
                E-commerce
            </a>
        </li>
    </ul>

    {{-- CONTENT --}}
    @if($channel === 'pos')
    <div class="card">
        <h5 class="card-header">History Belanja — Store (POS)</h5>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal Bayar</th>
                        <th>Store</th>
                        <th>Customer</th>
                        <th>WA</th>
                        <th>Produk</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($rowsPos as $r)
                    <tr>
                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($r->paid_at)->format('d M Y H:i') }}</td>
                        <td>{{ $r->store_name }}</td>
                        <td>{{ $r->customer_name ?? $r->customer_id }}</td>
                        <td><span class="badge bg-label-primary">{{ $r->wa_number ?? '-' }}</span></td>
                        <td style="max-width:360px;">
                            <div class="text-truncate" title="{{ $r->product_name }}">{{ $r->product_name ?? '-' }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Belum ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="card">
        <h5 class="card-header">History Belanja — E-commerce (Cart → Payment)</h5>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Paid At</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>WA</th>
                        <th>Produk</th>
                        <th class="text-end">Qty</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($rowsEcom as $r)
                    <tr>
                        <td class="text-nowrap">
                            {{ \Carbon\Carbon::parse($r->paid_at ?? $r->cart_at)->format('d M Y H:i') }}
                        </td>
                        <td>{{ $r->order_number ?? ('#'.$r->ecommerce_order_id) }}</td>
                        <td>{{ $r->customer_name ?? $r->customer_id }}</td>
                        <td><span class="badge bg-label-primary">{{ $r->wa_number ?? '-' }}</span></td>
                        <td style="max-width:360px;">
                            <div class="text-truncate" title="{{ $r->product_name }}">{{ $r->product_name ?? '-' }}
                            </div>
                        </td>
                        <td class="text-end">{{ number_format((int)($r->qty ?? 0)) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
    @endif
</div>
@endsection

@push('page-script')
<script>
// init select2
$('.js-select2-store').select2({
    theme: 'bootstrap-5',
    placeholder: 'Semua store',
    allowClear: true,
    width: '100%'
});

// show/hide & enable/disable store filter sesuai channel
function updateStoreVisibility() {
    const ch = document.getElementById('channel-select').value;
    const grp = document.getElementById('storeFilterGroup');
    const sel = grp?.querySelector('select');
    if (!grp || !sel) return;
    if (ch === 'pos') {
        grp.classList.remove('d-none');
        sel.disabled = false;
    } else {
        grp.classList.add('d-none');
        sel.disabled = true;
        $(sel).val('').trigger('change');
    }
}
document.getElementById('channel-select').addEventListener('change', updateStoreVisibility);
updateStoreVisibility();
</script>
@endpush
