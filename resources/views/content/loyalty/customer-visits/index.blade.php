@extends('layouts/contentNavbarLayout')

@section('title','Loyalty & Membership - Visit Customer')

@push('page-style')
<style>
  .visit-filter-box {
    padding: 1rem;
    border: 1px dashed #d9dee3;
    border-radius: .75rem;
    background: #f8f9fa;
  }

  .visit-filter-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
  }

  .visit-table th,
  .visit-table td {
    vertical-align: middle;
    white-space: nowrap;
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4"><span class="text-muted fw-light">Loyalty & Membership /</span> Visit Customer</h4>

    <div class="card">
        <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h5 class="mb-1">Daftar Visit Customer</h5>
                <small class="text-muted">Pantau riwayat kunjungan customer di setiap cabang.</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#advancedFilterVisit" aria-expanded="{{ request()->hasAny(['wa', 'store_id', 'date_from', 'date_to']) ? 'true' : 'false' }}" aria-controls="advancedFilterVisit">
                    <i class="mdi mdi-filter-variant me-1"></i> Filter
                </button>
                <a href="{{ route('loyalty.customer-visits.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-refresh me-1"></i> Reset
                </a>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="collapse {{ request()->hasAny(['wa', 'store_id', 'date_from', 'date_to']) ? 'show' : '' }}" id="advancedFilterVisit">
                <div class="visit-filter-box">
                    <form method="GET" action="{{ route('loyalty.customer-visits.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="text" class="form-control" name="wa" value="{{ $wa ?? '' }}" placeholder="Cari nomor WhatsApp">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cabang</label>
                            <select name="store_id" class="form-select">
                                <option value="all" {{ ($storeId ?? 'all') === 'all' ? 'selected' : '' }}>Semua Cabang</option>
                                @foreach($stores as $s)
                                <option value="{{ $s->id }}" {{ ($storeId ?? '') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo ?? '' }}">
                        </div>
                        <div class="col-12">
                            <div class="visit-filter-actions">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="mdi mdi-magnify me-1"></i> Tampilkan</button>
                                <a href="{{ route('loyalty.customer-visits.index') }}" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-refresh me-1"></i> Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0 visit-table">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Tanggal</th>
                        <th>Cabang</th>
                        <th>Customer</th>
                        <th>WA Number</th>
                        <th>Kode Visit</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $i => $v)
                    <tr>
                        <td>{{ ($visits->currentPage()-1)*$visits->perPage() + $i + 1 }}</td>

                        {{-- Tanggal utama: visit_at = COALESCE(check_in_at, created_at) --}}
                        <td>{{ $v->visit_at ? \Carbon\Carbon::parse($v->visit_at)->format('d M Y H:i') : '-' }}</td>

                        <td>{{ $v->store_name }}</td>
                        <td>{{ $v->customer_name }}</td>
                        <td><span class="badge bg-label-primary">{{ $v->wa_number ?? '-' }}</span></td>
                        <td>{{ $v->visit_code ?? '-' }}</td>

                        <td>{{ $v->check_in_at  ? \Carbon\Carbon::parse($v->check_in_at)->format('d M Y H:i')  : '-' }}</td>
                        <td>{{ $v->check_out_at ? \Carbon\Carbon::parse($v->check_out_at)->format('d M Y H:i') : '-' }}</td>

                        <td>
                            @php
                                $status = strtolower((string) $v->status);
                                $cls = match($status) {
                                    'active'   => 'bg-label-success',
                                    'inactive' => 'bg-label-secondary',
                                    default    => 'bg-label-info',
                                };
                            @endphp
                            <span class="badge {{ $cls }}">{{ $v->status }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">Belum ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <small class="text-muted">Menampilkan {{ $visits->firstItem() ?? 0 }} - {{ $visits->lastItem() ?? 0 }} dari {{ number_format($visits->total()) }} data</small>
            {{ $visits->links() }}
        </div>
    </div>
</div>
@endsection
