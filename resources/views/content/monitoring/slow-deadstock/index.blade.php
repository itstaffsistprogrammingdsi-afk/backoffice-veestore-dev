@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Slow Moving & Deadstock')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
    .select2-container {
        width: 100% !important;
    }

    .table-nowrap th,
    .table-nowrap td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .slowdead-filter-box {
        background: #f8f9fa;
        border: 1px dashed #d9dee3;
        border-radius: 0.75rem;
        padding: 1rem;
    }

    .slowdead-table tbody tr:hover {
        background-color: rgba(115, 103, 240, 0.04);
        transition: 0.2s ease;
    }

    .filter-actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .summary-chip {
        display: inline-flex;
        align-items: center;
        padding: .4rem .75rem;
        border-radius: 999px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        font-size: .875rem;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">Monitoring /</span> Slow Moving & Deadstock
    </h4>

    <div class="card">
        <h5 class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <span>Laporan Slow Moving & Deadstock</span>

            <div class="d-flex gap-2 flex-wrap">
                <form method="GET" action="{{ route('monitoring.slow-deadstock.index') }}"
                    class="d-flex gap-2 flex-wrap align-items-center">

                    <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        data-bs-toggle="collapse"
                        data-bs-target="#advancedFilterSlowDead">
                        <i class="mdi mdi-filter-variant me-1"></i> Filter
                    </button>

                    <button type="submit" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" title="Tampilkan">
                        <i class="mdi mdi-magnify"></i>
                    </button>

                    <a href="{{ route('monitoring.slow-deadstock.index') }}"
                        class="btn btn-outline-secondary btn-sm">
                        <i class="mdi mdi-refresh me-1"></i> Reset
                    </a>
                </form>
            </div>
        </h5>

        <div class="px-4 pb-3">
            <div class="collapse show" id="advancedFilterSlowDead">
                <div class="slowdead-filter-box">
                    <form method="GET" action="{{ route('monitoring.slow-deadstock.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Warehouse</label>
                            <select name="warehouse_id"
                                class="form-select js-select2-filter"
                                data-placeholder="Pilih Warehouse"
                                required>
                                <option value=""></option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected((string)($filters['warehouse_id'] ?? '') === (string)$warehouse->id)>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Dari</label>
                            <input type="date"
                                name="start_date"
                                class="form-control"
                                value="{{ $filters['start_date'] ?? '' }}"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Sampai</label>
                            <input type="date"
                                name="end_date"
                                class="form-control"
                                value="{{ $filters['end_date'] ?? '' }}"
                                required>
                        </div>

                        <div class="col-12">
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="mdi mdi-magnify me-1"></i> Tampilkan
                                </button>

                                @if(!empty($filters['warehouse_id']))
                                    <a href="{{ route('monitoring.slow-deadstock.export-slow', request()->query()) }}"
                                        class="btn btn-success btn-sm">
                                        <i class="mdi mdi-file-excel-outline me-1"></i> Export Slow Moving
                                    </a>

                                    <a href="{{ route('monitoring.slow-deadstock.export-dead', request()->query()) }}"
                                        class="btn btn-success btn-sm">
                                        <i class="mdi mdi-file-excel-outline me-1"></i> Export Deadstock
                                    </a>
                                @endif

                                <a href="{{ route('monitoring.slow-deadstock.index') }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="mdi mdi-refresh me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if(empty($filters['warehouse_id']))
            <div class="px-4 pb-4">
                <div class="alert alert-info mb-0">
                    Silakan pilih <strong>Warehouse</strong> terlebih dahulu.
                </div>
            </div>
        @else
            <div class="px-4 pb-3 d-flex flex-wrap gap-2">
                <span class="summary-chip">
                    <i class="mdi mdi-warehouse me-1"></i>
                    Warehouse dipilih
                </span>
                <span class="summary-chip">
                    <i class="mdi mdi-calendar-range me-1"></i>
                    {{ $filters['start_date'] ?? '-' }} s/d {{ $filters['end_date'] ?? '-' }}
                </span>
                <span class="summary-chip">
                    <i class="mdi mdi-chart-timeline-variant me-1"></i>
                    Slow Moving: {{ number_format($slow->count()) }}
                </span>
                <span class="summary-chip">
                    <i class="mdi mdi-package-variant-closed-remove me-1"></i>
                    Deadstock: {{ number_format($dead->count()) }}
                </span>
            </div>

            <div class="card-body tab-content p-0">
                <div class="px-4 pb-3">
                    <ul class="nav nav-pills" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active"
                                data-bs-toggle="tab"
                                data-bs-target="#tabSlow"
                                type="button"
                                role="tab">
                                Slow Moving
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link"
                                data-bs-toggle="tab"
                                data-bs-target="#tabDead"
                                type="button"
                                role="tab">
                                Deadstock
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-pane fade show active" id="tabSlow" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table class="table mb-0 table-nowrap slowdead-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">No</th>
                                    <th>Kode Produk</th>
                                    <th>Nama Produk</th>
                                    <th>Status</th>
                                    <th>Lokasi</th>
                                    <th class="text-end">Qty</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @forelse($slow as $row)
                                    @php
                                        $statusClass = strtolower($row->status ?? '') === 'good' ? 'success' : 'danger';
                                        $location = $row->room_name ?? '-';

                                        if (!empty($row->rack_name)) {
                                            $location .= ' / ' . $row->rack_name;
                                        }

                                        if (!empty($row->layer_name)) {
                                            $location .= ' / ' . $row->layer_name;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-medium">{{ $row->product_code ?? '-' }}</td>
                                        <td>{{ $row->product_name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-label-{{ $statusClass }}">
                                                {{ $row->status ?? '-' }}
                                            </span>
                                        </td>
                                        <td>{{ $location }}</td>
                                        <td class="text-end fw-medium">{{ number_format((int) ($row->quantity ?? 0)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada data slow moving</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tabDead" role="tabpanel">
                    <div class="table-responsive text-nowrap">
                        <table class="table mb-0 table-nowrap slowdead-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">No</th>
                                    <th>Kode Produk</th>
                                    <th>Nama Produk</th>
                                    <th>Status</th>
                                    <th>Lokasi</th>
                                    <th class="text-end">Qty</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @forelse($dead as $row)
                                    @php
                                        $statusClass = strtolower($row->status ?? '') === 'good' ? 'success' : 'danger';
                                        $location = $row->room_name ?? '-';

                                        if (!empty($row->rack_name)) {
                                            $location .= ' / ' . $row->rack_name;
                                        }

                                        if (!empty($row->layer_name)) {
                                            $location .= ' / ' . $row->layer_name;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-medium">{{ $row->product_code ?? '-' }}</td>
                                        <td>{{ $row->product_name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-label-{{ $statusClass }}">
                                                {{ $row->status ?? '-' }}
                                            </span>
                                        </td>
                                        <td>{{ $location }}</td>
                                        <td class="text-end fw-medium">{{ number_format((int) ($row->quantity ?? 0)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada data deadstock</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
    'use strict';

    function initFilterSelect2() {
        $('.js-select2-filter').each(function () {
            const $el = $(this);

            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }

            $el.select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: $el.data('placeholder') || 'Pilih data'
            });
        });
    }

    $(document).ready(function () {
        initFilterSelect2();

        const ttNodes = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        ttNodes.forEach(el => new bootstrap.Tooltip(el));
    });
})(window.jQuery);
</script>
@endpush