@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - User Monitoring')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --um-primary: #5b5cf6;
    --um-primary-dark: #4338ca;
    --um-success: #22c55e;
    --um-success-soft: rgba(34, 197, 94, .14);
    --um-info: #06b6d4;
    --um-info-soft: rgba(6, 182, 212, .14);
    --um-warning: #f59e0b;
    --um-warning-soft: rgba(245, 158, 11, .16);
    --um-danger: #ef4444;
    --um-danger-soft: rgba(239, 68, 68, .14);
    --um-purple: #8b5cf6;
    --um-dark: #1e293b;
    --um-muted: #64748b;
    --um-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(239, 68, 68, .10), transparent 28%),
      var(--um-body);
  }

  .select2-container {
    width: 100% !important;
  }

  .select2-container--bootstrap-5 .select2-selection {
    min-height: 40px;
    border-radius: .75rem;
    border-color: #dbe1ea;
    box-shadow: none;
  }

  .form-control,
  .form-select {
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .select2-container--bootstrap-5.select2-container--focus .select2-selection,
  .form-control:focus,
  .form-select:focus {
    border-color: var(--um-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .dashboard-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    color: #fff;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
      linear-gradient(135deg, #4f46e5 0%, #06b6d4 52%, #ef4444 120%);
    box-shadow: 0 1.25rem 2.5rem rgba(79, 70, 229, .22);
  }

  .dashboard-hero::before {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    right: -80px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255,255,255,.18);
  }

  .dashboard-hero::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    right: 120px;
    bottom: -120px;
    border-radius: 50%;
    background: rgba(255,255,255,.13);
  }

  .dashboard-hero > .d-flex {
    position: relative;
    z-index: 2;
  }

  .dashboard-hero h4,
  .dashboard-hero .text-muted,
  .dashboard-hero .text-muted.fw-light {
    color: #fff !important;
  }

  .hero-subtitle {
    color: rgba(255,255,255,.84) !important;
  }

  .hero-badge {
    border-radius: 999px;
    padding: .55rem .85rem;
    background: rgba(255,255,255,.18);
    color: #fff;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.22);
    font-weight: 700;
    font-size: .78rem;
  }

  .card {
    border: 0;
    border-radius: 1.15rem;
    box-shadow: 0 .5rem 1.5rem rgba(15, 23, 42, .06);
  }

  .card-header {
    border-bottom: 1px solid rgba(226, 232, 240, .8);
    background: transparent;
    padding: 1.25rem 1.35rem;
  }

  .card-body {
    padding: 1.35rem;
  }

  .btn {
    border-radius: .75rem;
    font-weight: 600;
  }

  .btn-primary {
    border-color: var(--um-primary);
    background: linear-gradient(135deg, var(--um-primary), var(--um-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--um-success);
    background: linear-gradient(135deg, var(--um-success), #16a34a);
  }

  .cashier-filter-box {
    background: linear-gradient(180deg, rgba(248, 250, 252, .98), rgba(255,255,255,.98));
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.15rem;
  }

  .filter-actions {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
  }

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: #fff;
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--um-primary), var(--um-info));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--um-dark);
    font-weight: 800;
  }

  .summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 25px;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--um-primary), var(--um-primary-dark));
  }

  .summary-icon.success {
    background: linear-gradient(135deg, #22c55e, #16a34a);
  }

  .summary-icon.info {
    background: linear-gradient(135deg, #06b6d4, #0284c7);
  }

  .summary-icon.warning {
    background: linear-gradient(135deg, #f59e0b, #f97316);
  }

  .summary-icon.danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
  }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .cashier-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .cashier-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
    transition: .2s ease;
  }

  .text-truncate-220 {
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .text-truncate-320 {
    max-width: 320px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .user-cell {
    min-width: 210px;
    white-space: normal !important;
  }

  .ip-cell {
    min-width: 150px;
  }

  .location-cell {
    min-width: 160px;
    white-space: normal !important;
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--um-muted);
  }

  .badge-soft-success {
    background: var(--um-success-soft) !important;
    color: #15803d !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-warning {
    background: var(--um-warning-soft) !important;
    color: #b45309 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-danger {
    background: var(--um-danger-soft) !important;
    color: #b91c1c !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-info {
    background: var(--um-info-soft) !important;
    color: #0369a1 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-primary {
    background: rgba(91, 92, 246, .14) !important;
    color: #4338ca !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-secondary {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .badge-soft-dark {
    background: rgba(15, 23, 42, .12) !important;
    color: #1e293b !important;
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 700;
  }

  .empty-state {
    border: 1px dashed #cbd5e1;
    border-radius: 1rem;
    padding: 2.5rem 1rem;
    background: #f8fafc;
  }

  .status-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
  }

  @media (max-width: 767.98px) {
    .dashboard-hero {
      border-radius: 1rem;
    }

    .card-body,
    .card-header {
      padding: 1rem;
    }
  }
</style>
@endpush

@section('content')
@php
  $rowCollection = method_exists($rows, 'getCollection') ? $rows->getCollection() : collect($rows);

  $totalData = method_exists($rows, 'total') ? (int) $rows->total() : (int) $rowCollection->count();

  $successCount = (int) $rowCollection->filter(function ($row) {
    return strtolower((string) ($row->status ?? '')) === 'success';
  })->count();

  $failedCount = (int) $rowCollection->filter(function ($row) {
    $value = strtolower((string) ($row->status ?? ''));

    return in_array($value, ['failed', 'error'], true);
  })->count();

  $userCount = $rowCollection
    ->pluck('user_id')
    ->filter()
    ->unique()
    ->count();

  $ipCount = $rowCollection
    ->pluck('ip_address')
    ->filter()
    ->unique()
    ->count();

  $hasFilter = request()->hasAny([
    'q',
    'from',
    'to',
    'status',
    'type',
    'app_type',
    'per_page',
    'device',
    'platform',
  ]);

  $statusBadge = function ($status) {
    $statusValue = strtolower((string) ($status ?? ''));

    return match($statusValue) {
      'success' => 'badge-soft-success',
      'failed', 'error' => 'badge-soft-danger',
      'pending' => 'badge-soft-warning',
      default => 'badge-soft-secondary',
    };
  };

  $statusIcon = function ($status) {
    $statusValue = strtolower((string) ($status ?? ''));

    return match($statusValue) {
      'success' => 'mdi-check-circle-outline',
      'failed', 'error' => 'mdi-alert-circle-outline',
      'pending' => 'mdi-clock-outline',
      default => 'mdi-help-circle-outline',
    };
  };

  $detailsToText = function ($details) {
    if (is_array($details) || is_object($details)) {
      return json_encode($details, JSON_UNESCAPED_UNICODE);
    }

    return $details ?? '-';
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> User Monitoring
        </h4>
        <div class="hero-subtitle">
          Monitoring aktivitas login user, status akses, device, platform, IP address, lokasi, token, dan informasi kegagalan.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <i class="mdi mdi-account-group-outline me-1"></i>
          {{ number_format($totalData, 0, ',', '.') }} Aktivitas
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-check-decagram-outline me-1"></i>
          {{ number_format($successCount, 0, ',', '.') }} Success
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-alert-circle-outline me-1"></i>
          {{ number_format($failedCount, 0, ',', '.') }} Failed/Error
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ now()->format('d-m-Y H:i:s') }}
        </span>
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter User Monitoring</h5>
        <small class="text-muted">
          Cari username, user ID, IP, token, user agent, failure reason, device, platform, dan periode tanggal.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterUserMonitoring">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.user-monitoring.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.user-monitoring.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterUserMonitoring">
        <div class="cashier-filter-box">
          <form method="GET" action="{{ route('monitoring.user-monitoring.index') }}" class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Dari Tanggal</label>
              <input type="date" name="from" class="form-control" value="{{ $from }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Sampai Tanggal</label>
              <input type="date" name="to" class="form-control" value="{{ $to }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select js-select2-filter" data-placeholder="Semua Status">
                <option value="">Semua</option>
                @foreach($statuses as $item)
                  <option value="{{ $item }}" @selected((string) $status === (string) $item)>
                    {{ $item }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Type</label>
              <select name="type" class="form-select js-select2-filter" data-placeholder="Semua Type">
                <option value="">Semua</option>
                @foreach($types as $item)
                  <option value="{{ $item }}" @selected((string) $type === (string) $item)>
                    {{ $item }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">App Type</label>
              <select name="app_type" class="form-select js-select2-filter" data-placeholder="Semua App Type">
                <option value="">Semua</option>
                @foreach($appTypes as $item)
                  <option value="{{ $item }}" @selected((string) $appType === (string) $item)>
                    {{ $item }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Device</label>
              <select name="device" class="form-select js-select2-filter" data-placeholder="Semua Device">
                <option value="">Semua</option>
                @foreach($devices as $item)
                  <option value="{{ $item }}" @selected((string) $device === (string) $item)>
                    {{ $item }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Platform</label>
              <select name="platform" class="form-select js-select2-filter" data-placeholder="Semua Platform">
                <option value="">Semua</option>
                @foreach($platforms as $item)
                  <option value="{{ $item }}" @selected((string) $platform === (string) $item)>
                    {{ $item }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select js-select2-filter" data-placeholder="Jumlah data">
                @foreach([5, 10, 25, 50, 100] as $size)
                  <option value="{{ $size }}" @selected((int) $perPage === $size)>
                    {{ $size }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Pencarian</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q }}"
                     placeholder="Username / user id / IP / token / user agent / failure reason">
            </div>

            <div class="col-md-6">
              <label class="form-label">&nbsp;</label>
              <div class="text-muted small pt-2">
                Filter dapat digunakan untuk audit login, aktivitas user, dan investigasi device/IP.
              </div>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.user-monitoring.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.user-monitoring.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                  <i class="mdi mdi-refresh me-1"></i> Reset
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- SUMMARY --}}
  <div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Total Aktivitas</div>
              <h4 class="mb-1">{{ number_format($totalData, 0, ',', '.') }}</h4>
              <small class="text-muted">Data sesuai filter</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-account-clock-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Success</div>
              <h4 class="mb-1">{{ number_format($successCount, 0, ',', '.') }}</h4>
              <small class="text-muted">Aktivitas berhasil</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-check-decagram-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">Failed / Error</div>
              <h4 class="mb-1">{{ number_format($failedCount, 0, ',', '.') }}</h4>
              <small class="text-muted">Aktivitas gagal/error</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-alert-circle-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3">
            <div>
              <div class="text-muted small">User / IP Unik</div>
              <h4 class="mb-1">
                {{ number_format($userCount, 0, ',', '.') }}
                /
                {{ number_format($ipCount, 0, ',', '.') }}
              </h4>
              <small class="text-muted">User ID / IP halaman ini</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-lan-connect"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Laporan Monitoring User</h5>
        <small class="text-muted">
          Total Data: <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
        </small>
      </div>

      @if($rows->count())
        <div class="pagination-info">
          Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
          dari <b>{{ number_format($rows->total(), 0, ',', '.') }}</b> data
        </div>
      @endif
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap cashier-table">
          <thead>
            <tr>
              <th>Logged At</th>
              <th>Username</th>
              <th>User ID</th>
              <th>IP Address</th>
              <th>Device</th>
              <th>Platform</th>
              <th>Status</th>
              <th>Type</th>
              <th>App Type</th>
              <th>User Agent</th>
              <th>Failure Reason</th>
              <th>Last Activity</th>
              <th>Expired At</th>
              <th>ISP</th>
              <th>City</th>
              <th>Country</th>
              <th>GPS</th>
              <th>Details</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $row)
              @php
                $detailsText = $detailsToText($row->details ?? null);
              @endphp

              <tr>
                <td class="text-nowrap">
                  <span class="badge badge-soft-secondary">
                    {{ $row->logged_at ? \Carbon\Carbon::parse($row->logged_at)->format('d M Y H:i:s') : '-' }}
                  </span>
                </td>

                <td class="user-cell">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-soft-primary">
                      <i class="mdi mdi-account-outline"></i>
                    </span>
                    <div>
                      <div class="fw-semibold">{{ $row->username ?? '-' }}</div>
                      <small class="text-muted">{{ $row->app_type ?? 'App' }}</small>
                    </div>
                  </div>
                </td>

                <td class="text-nowrap">{{ $row->user_id ?? '-' }}</td>

                <td class="ip-cell">
                  <span class="badge badge-soft-info">
                    {{ $row->ip_address ?? '-' }}
                  </span>
                </td>

                <td>{{ $row->device ?? '-' }}</td>
                <td>{{ $row->platform ?? '-' }}</td>

                <td>
                  <span class="badge {{ $statusBadge($row->status ?? '-') }}">
                    <i class="mdi {{ $statusIcon($row->status ?? '-') }} me-1"></i>
                    {{ $row->status ?? '-' }}
                  </span>
                </td>

                <td>
                  <span class="badge badge-soft-secondary">
                    {{ $row->type ?? '-' }}
                  </span>
                </td>

                <td>
                  <span class="badge badge-soft-primary">
                    {{ $row->app_type ?? '-' }}
                  </span>
                </td>

                <td>
                  <div class="text-truncate-320" title="{{ $row->user_agent }}">
                    {{ $row->user_agent ?? '-' }}
                  </div>
                </td>

                <td>
                  <div class="text-truncate-220" title="{{ $row->failure_reason }}">
                    {{ $row->failure_reason ?? '-' }}
                  </div>
                </td>

                <td class="text-nowrap">
                  {{ $row->last_activity_at ? \Carbon\Carbon::parse($row->last_activity_at)->format('d M Y H:i:s') : '-' }}
                </td>

                <td class="text-nowrap">
                  {{ $row->expired_at ? \Carbon\Carbon::parse($row->expired_at)->format('d M Y H:i:s') : '-' }}
                </td>

                <td>{{ $row->isp ?? '-' }}</td>
                <td class="location-cell">{{ $row->city ?? '-' }}</td>
                <td>{{ $row->country ?? '-' }}</td>

                <td>
                  @if(($row->gps_lat ?? null) || ($row->gps_lon ?? null))
                    <span class="badge badge-soft-info">
                      {{ $row->gps_lat . ', ' . $row->gps_lon }}
                    </span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>

                <td>
                  <div class="text-truncate-320" title="{{ $detailsText }}">
                    {{ $detailsText }}
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="18">
                  <div class="empty-state text-center text-muted">
                    <i class="mdi mdi-database-off-outline d-block mb-2" style="font-size: 2.3rem;"></i>
                    Belum ada data monitoring user.
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($rows->hasPages())
        <div class="mt-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
          <div class="pagination-info">
            Menampilkan
            <b>{{ $rows->firstItem() ?? 0 }}</b>
            -
            <b>{{ $rows->lastItem() ?? 0 }}</b>
            dari
            <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
            data
          </div>

          <div>
            {{ $rows->onEachSide(1)->links() }}
          </div>
        </div>
      @else
        <div class="mt-3 pagination-info">
          Menampilkan
          <b>{{ $rows->firstItem() ?? 0 }}</b>
          -
          <b>{{ $rows->lastItem() ?? 0 }}</b>
          dari
          <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
          data
        </div>
      @endif
    </div>
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
