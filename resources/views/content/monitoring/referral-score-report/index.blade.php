@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Referral Score Report')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  :root {
    --rf-primary: #5b5cf6;
    --rf-primary-dark: #4338ca;
    --rf-success: #22c55e;
    --rf-info: #06b6d4;
    --rf-warning: #f59e0b;
    --rf-danger: #ef4444;
    --rf-pink: #ec4899;
    --rf-purple: #8b5cf6;
    --rf-dark: #1e293b;
    --rf-muted: #64748b;
    --rf-body: #f6f7fb;
    --rf-border: #e5e7eb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(34, 197, 94, .10), transparent 28%),
      var(--rf-body);
  }

  .select2-container {
    width: 100% !important;
  }

  .select2-container--bootstrap-5 .select2-selection {
    min-height: 40px;
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .form-control,
  .form-select {
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .form-control:focus,
  .form-select:focus,
  .select2-container--bootstrap-5.select2-container--focus .select2-selection {
    border-color: var(--rf-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .referral-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
      linear-gradient(135deg, #4338ca 0%, #06b6d4 52%, #22c55e 120%);
    color: #fff;
    box-shadow: 0 1.25rem 2.5rem rgba(67, 56, 202, .22);
  }

  .referral-hero::before {
    content: "";
    position: absolute;
    width: 280px;
    height: 280px;
    right: -110px;
    top: -120px;
    border-radius: 50%;
    background: rgba(255,255,255,.18);
  }

  .referral-hero::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    right: 130px;
    bottom: -120px;
    border-radius: 50%;
    background: rgba(255,255,255,.14);
  }

  .referral-hero-content {
    position: relative;
    z-index: 2;
  }

  .hero-badge {
    border-radius: 999px;
    padding: .55rem .85rem;
    background: rgba(255,255,255,.18);
    color: #fff;
    border: 1px solid rgba(255,255,255,.22);
    backdrop-filter: blur(8px);
    font-size: .78rem;
    font-weight: 700;
  }

  .live-dot {
    display: inline-block;
    width: .55rem;
    height: .55rem;
    border-radius: 999px;
    background: #22c55e;
    animation: pulseLive 1.4s infinite;
  }

  @keyframes pulseLive {
    0% { box-shadow: 0 0 0 0 rgba(34,197,94,.38); }
    70% { box-shadow: 0 0 0 .45rem rgba(34,197,94,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
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

  .referral-filter-box {
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

  .btn {
    border-radius: .75rem;
    font-weight: 700;
  }

  .btn-primary {
    border-color: var(--rf-primary);
    background: linear-gradient(135deg, var(--rf-primary), var(--rf-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-success {
    border-color: var(--rf-success);
    background: linear-gradient(135deg, var(--rf-success), #16a34a);
    box-shadow: 0 .45rem 1rem rgba(34, 197, 94, .18);
  }

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    background: #fff;
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--rf-primary), var(--rf-info));
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-label {
    color: var(--rf-muted);
    font-size: .78rem;
    margin-bottom: .35rem;
  }

  .summary-value {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 900;
    color: var(--rf-dark);
    letter-spacing: -.02em;
  }

  .summary-help {
    display: block;
    margin-top: .25rem;
    font-size: .78rem;
    color: var(--rf-muted);
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
    background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-dark));
    box-shadow: 0 .65rem 1.25rem rgba(91, 92, 246, .22);
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
    background: linear-gradient(135deg, #ec4899, #ef4444);
  }

  .chart-wrap {
    position: relative;
    min-height: 330px;
  }

  .score-text {
    color: #4338ca;
    font-weight: 900;
  }

  .success-text {
    color: #16a34a;
    font-weight: 800;
  }

  .pending-text {
    color: #d97706;
    font-weight: 800;
  }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table {
    margin-bottom: 0;
  }

  .table th,
  .table td {
    vertical-align: middle;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
  }

  .referral-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .76rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 900;
    border-bottom: 1px solid #dbe1ea;
  }

  .referral-table tbody td {
    color: #475569;
  }

  .referral-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--rf-muted);
  }

  .email-col,
  .source-col {
    min-width: 220px;
    white-space: normal !important;
  }

  .member-col {
    min-width: 230px;
    white-space: normal !important;
  }

  .mini-list {
    max-height: 420px;
    overflow: auto;
    padding-top: .25rem;
  }

  .mini-list-item {
    border: 1px solid transparent;
    border-radius: .95rem;
    padding: .85rem .9rem;
    margin-bottom: .65rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    transition: .18s ease;
  }

  .mini-list-item:hover {
    border-color: #dbeafe;
    transform: translateX(2px);
    box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .06);
  }

  .rank-badge {
    width: 30px;
    height: 30px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(91, 92, 246, .12);
    color: #4338ca;
    font-weight: 900;
    font-size: .8rem;
  }

  .badge-status {
    border-radius: 999px;
    padding: .42rem .68rem;
    font-weight: 800;
    font-size: .74rem;
  }

  .badge-success-soft {
    background: rgba(34, 197, 94, .14);
    color: #15803d;
  }

  .badge-warning-soft {
    background: rgba(245, 158, 11, .16);
    color: #b45309;
  }

  .badge-info-soft {
    background: rgba(6, 182, 212, .14);
    color: #0369a1;
  }

  .badge-secondary-soft {
    background: #f1f5f9;
    color: #475569;
  }

  @media (max-width: 767.98px) {
    .referral-hero {
      border-radius: 1rem;
    }

    .chart-wrap {
      min-height: 280px;
    }

    .card-body {
      padding: 1rem;
    }
  }
</style>
@endpush

@php
  $q = $q ?? '';
  $status = $status ?? '';
  $referrerId = $referrerId ?? '';

  $summary = $summary ?? (object) [
    'total_referral' => 0,
    'total_referrer' => 0,
    'total_referee' => 0,
    'total_success' => 0,
    'total_pending' => 0,
    'total_other_status' => 0,
    'total_score_row' => 0,
  ];

  $topReferrer = collect($topReferrers ?? [])->first();

  function referralStatusClass($statusValue) {
      $text = strtolower((string) $statusValue);

      if (in_array($text, ['success', 'sukses', 'completed', 'complete', 'approved', 'converted', 'done'], true)) {
          return 'badge-success-soft';
      }

      if (in_array($text, ['pending', 'waiting', 'process', 'processing', 'registered'], true)) {
          return 'badge-warning-soft';
      }

      if (in_array($text, ['new', 'active'], true)) {
          return 'badge-info-soft';
      }

      return 'badge-secondary-soft';
  }
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <div class="referral-hero p-4 mb-4">
    <div class="referral-hero-content d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1 text-white">
          <span class="fw-light">Monitoring /</span> Referral Score Report
        </h4>
        <div style="color: rgba(255,255,255,.84)">
          Dashboard Holding untuk melihat performa referral, referrer terbaik, kode referral terpakai, status referral, dan score per member.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <span class="live-dot me-1"></span>
          Live Data
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-calendar-range me-1"></i>
          {{ \Carbon\Carbon::parse($from)->format('d M Y') }} - {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
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
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Dashboard Referral</h5>
        <small class="text-muted">
          Filter periode, status, referrer, atau pencarian nama/email/kode referral.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#advancedFilterReferralScore">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.referral-score-report.export', request()->query()) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel me-1"></i> Export Excel
        </a>

        <a href="{{ route('monitoring.referral-score-report.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ request()->hasAny(['q', 'status', 'referrer_id', 'from', 'to']) ? 'show' : '' }}"
           id="advancedFilterReferralScore">
        <div class="referral-filter-box">
          <form method="GET"
                action="{{ route('monitoring.referral-score-report.index') }}"
                class="row g-3 align-items-end">

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
              <select name="status" class="form-select js-select2-filter" data-placeholder="Semua Status">
                <option value="">Semua Status</option>
                @foreach($statuses as $item)
                  <option value="{{ $item }}" {{ (string) $status === (string) $item ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $item)) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-6 col-md-3">
              <label class="form-label">Referrer</label>
              <select name="referrer_id" class="form-select js-select2-filter" data-placeholder="Semua Referrer">
                <option value="">Semua Referrer</option>
                @foreach($referrers as $referrer)
                  <option value="{{ $referrer->id }}" {{ (string) $referrerId === (string) $referrer->id ? 'selected' : '' }}>
                    {{ $referrer->name }}{{ $referrer->email ? ' - ' . $referrer->email : '' }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-sm-12 col-md-8">
              <label class="form-label">Cari</label>
              <input type="text"
                     name="q"
                     class="form-control"
                     value="{{ $q }}"
                     placeholder="Nama referrer / email / referee / code used / status">
            </div>

            <div class="col-sm-12 col-md-4">
              <div class="filter-actions">
                <button class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.referral-score-report.export', request()->query()) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.referral-score-report.index') }}"
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
    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Referral</div>
            <h5 class="summary-value">{{ number_format((int) $summary->total_referral, 0, ',', '.') }}</h5>
            <span class="summary-help">Semua referral sesuai filter</span>
          </div>
          <div class="summary-icon">
            <i class="mdi mdi-account-switch-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Referrer</div>
            <h5 class="summary-value">{{ number_format((int) $summary->total_referrer, 0, ',', '.') }}</h5>
            <span class="summary-help">Member yang mengajak referral</span>
          </div>
          <div class="summary-icon info">
            <i class="mdi mdi-account-star-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Total Referee</div>
            <h5 class="summary-value">{{ number_format((int) $summary->total_referee, 0, ',', '.') }}</h5>
            <span class="summary-help">Member yang direferensikan</span>
          </div>
          <div class="summary-icon success">
            <i class="mdi mdi-account-plus-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Referral Success</div>
            <h5 class="summary-value success-text">{{ number_format((int) $summary->total_success, 0, ',', '.') }}</h5>
            <span class="summary-help">Status sukses / approved / converted</span>
          </div>
          <div class="summary-icon success">
            <i class="mdi mdi-check-decagram-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Referral Pending</div>
            <h5 class="summary-value pending-text">{{ number_format((int) $summary->total_pending, 0, ',', '.') }}</h5>
            <span class="summary-help">Status pending / waiting / process</span>
          </div>
          <div class="summary-icon warning">
            <i class="mdi mdi-timer-sand"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Status Lainnya</div>
            <h5 class="summary-value">{{ number_format((int) $summary->total_other_status, 0, ',', '.') }}</h5>
            <span class="summary-help">Selain success dan pending</span>
          </div>
          <div class="summary-icon danger">
            <i class="mdi mdi-alert-circle-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Top Referrer</div>
            <h5 class="summary-value" style="font-size: 1rem;">
              {{ $topReferrer->referrer_name ?? '-' }}
            </h5>
            <span class="summary-help">
              {{ $topReferrer ? number_format((int) $topReferrer->total_referral, 0, ',', '.') : 0 }} referral
            </span>
          </div>
          <div class="summary-icon info">
            <i class="mdi mdi-trophy-outline"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center gap-3">
          <div>
            <div class="summary-label">Score Top Referrer</div>
            <h5 class="summary-value score-text">
              {{ $topReferrer ? number_format((float) $topReferrer->score, 2, ',', '.') : '0,00' }}
            </h5>
            <span class="summary-help">Score dari tabel referrer_scores</span>
          </div>
          <div class="summary-icon">
            <i class="mdi mdi-star-circle-outline"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Trend Referral Per Hari</h5>
          <small class="text-muted">Jumlah referral berdasarkan periode filter.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="referralTrendChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Referral Berdasarkan Status</h5>
          <small class="text-muted">Distribusi status referral.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="statusChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-7">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Top Referrer Berdasarkan Jumlah Referral</h5>
          <small class="text-muted">Member dengan jumlah referral terbanyak.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="topReferrerChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-5">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Top Kode Referral</h5>
          <small class="text-muted">Kode referral yang paling sering digunakan.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="topCodeChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- TOP REFERRERS --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-7">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Leaderboard Referrer</h5>
          <small class="text-muted">Ranking member berdasarkan total referral dan score.</small>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm referral-table table-nowrap">
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Referrer</th>
                  <th class="text-center">Referral</th>
                  <th class="text-center">Success</th>
                  <th class="text-center">Pending</th>
                  <th class="text-end">Score</th>
                  <th>Referral Terakhir</th>
                </tr>
              </thead>
              <tbody>
                @forelse($topReferrers as $index => $item)
                  <tr>
                    <td><span class="rank-badge">{{ $index + 1 }}</span></td>
                    <td class="member-col">
                      <div class="fw-bold text-dark">{{ $item->referrer_name ?? '-' }}</div>
                      <small class="text-muted">
                        {{ $item->referrer_email ?? '-' }} • {{ $item->referrer_wa_number ?? '-' }}
                      </small>
                    </td>
                    <td class="text-center">{{ number_format((int) $item->total_referral, 0, ',', '.') }}</td>
                    <td class="text-center success-text">{{ number_format((int) $item->total_success, 0, ',', '.') }}</td>
                    <td class="text-center pending-text">{{ number_format((int) $item->total_pending, 0, ',', '.') }}</td>
                    <td class="text-end score-text">{{ number_format((float) $item->score, 2, ',', '.') }}</td>
                    <td>
                      {{ $item->last_referral_at ? \Carbon\Carbon::parse($item->last_referral_at)->format('d M Y H:i') : '-' }}
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                      Belum ada data leaderboard referrer.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-5">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Referral Terbaru</h5>
          <small class="text-muted">10 aktivitas referral terakhir.</small>
        </div>
        <div class="card-body mini-list">
          @forelse($latestReferralRows as $item)
            <div class="mini-list-item">
              <div class="d-flex justify-content-between gap-3">
                <div>
                  <div class="fw-bold text-dark">{{ $item->referrer_name ?? '-' }}</div>
                  <small class="text-muted">
                    Mengajak: {{ $item->referee_name ?? '-' }}
                  </small>
                  <div class="small text-muted">
                    Code: {{ $item->code_used ?? '-' }}
                  </div>
                </div>
                <div class="text-end">
                  <span class="badge badge-status {{ referralStatusClass($item->status ?? '') }}">
                    {{ $item->status ? ucfirst(str_replace('_', ' ', $item->status)) : '-' }}
                  </span>
                  <div class="small text-muted mt-1">
                    {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M H:i') : '-' }}
                  </div>
                </div>
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-4">Belum ada referral terbaru.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE DETAIL --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Detail Monitoring Referral Score</h5>
        <small class="text-muted">
          Total Data: <b>{{ number_format($rows->total(), 0, ',', '.') }}</b>
        </small>
      </div>

      @if($rows->count())
        <div class="pagination-info">
          Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
          dari <b>{{ number_format($rows->total(), 0, ',', '.') }}</b> data
          | Halaman <b>{{ $rows->currentPage() }}</b> / <b>{{ $rows->lastPage() }}</b>
        </div>
      @endif
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm mb-0 table-nowrap referral-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal</th>
              <th>Code Used</th>
              <th>Status</th>
              <th>Referrer</th>
              <th>Email Referrer</th>
              <th>WA Referrer</th>
              <th>Referee</th>
              <th>Email Referee</th>
              <th>WA Referee</th>
              <th class="text-end">Score</th>
              <th>Score Source</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td class="text-nowrap">
                  {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') : '-' }}
                </td>

                <td>
                  <span class="fw-bold text-dark">{{ $row->code_used ?? '-' }}</span>
                </td>

                <td>
                  <span class="badge badge-status {{ referralStatusClass($row->status ?? '') }}">
                    {{ $row->status ? ucfirst(str_replace('_', ' ', $row->status)) : '-' }}
                  </span>
                </td>

                <td class="member-col">
                  <div class="fw-bold text-dark">{{ $row->referrer_name ?? '-' }}</div>
                  <small class="text-muted">ID: {{ $row->referrer_id ?? '-' }}</small>
                </td>

                <td class="email-col">{{ $row->referrer_email ?? '-' }}</td>
                <td>{{ $row->referrer_wa_number ?? '-' }}</td>

                <td class="member-col">
                  <div class="fw-bold text-dark">{{ $row->referee_name ?? '-' }}</div>
                  <small class="text-muted">ID: {{ $row->referee_id ?? '-' }}</small>
                </td>

                <td class="email-col">{{ $row->referee_email ?? '-' }}</td>
                <td>{{ $row->referee_wa_number ?? '-' }}</td>

                <td class="text-end score-text">
                  {{ number_format((float) ($row->score ?? 0), 2, ',', '.') }}
                </td>

                <td class="source-col">{{ $row->score_source ?? '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="12" class="text-center text-muted py-4">
                  Belum ada data referral score.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($rows->hasPages())
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
          <div class="pagination-info">
            Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
            dari <b>{{ number_format($rows->total(), 0, ',', '.') }}</b> data
          </div>

          <div>
            {{ $rows->onEachSide(1)->links() }}
          </div>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
  'use strict';

  const trendRows = @json($trendRows ?? []);
  const statusChartRows = @json($statusChartRows ?? []);
  const topReferrerChartRows = @json($topReferrerChartRows ?? []);
  const topCodeRows = @json($topCodeRows ?? []);

  const chartColors = [
    '#5b5cf6',
    '#06b6d4',
    '#22c55e',
    '#f59e0b',
    '#ec4899',
    '#8b5cf6',
    '#ef4444',
    '#14b8a6',
    '#f97316',
    '#64748b'
  ];

  function numberId(value) {
    return Number(value || 0).toLocaleString('id-ID');
  }

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

  function renderLineChart(canvasId, rows) {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label || '-');
    const totalData = rows.map(item => Number(item.total_referral || 0));
    const successData = rows.map(item => Number(item.total_success || 0));
    const pendingData = rows.map(item => Number(item.total_pending || 0));

    const gradient = el.getContext('2d').createLinearGradient(0, 0, 0, 320);
    gradient.addColorStop(0, 'rgba(91, 92, 246, .28)');
    gradient.addColorStop(1, 'rgba(91, 92, 246, 0)');

    new Chart(el, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Total Referral',
            data: totalData,
            borderColor: '#5b5cf6',
            backgroundColor: gradient,
            borderWidth: 3,
            tension: .38,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 7,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#5b5cf6',
            pointBorderWidth: 3
          },
          {
            label: 'Success',
            data: successData,
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, .12)',
            borderWidth: 2,
            tension: .38,
            fill: false,
            pointRadius: 3
          },
          {
            label: 'Pending',
            data: pendingData,
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, .12)',
            borderWidth: 2,
            tension: .38,
            fill: false,
            pointRadius: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          intersect: false,
          mode: 'index'
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return `${context.dataset.label}: ${numberId(context.raw || 0)}`;
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: '#64748b',
              font: {
                size: 11,
                weight: '600'
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: '#64748b',
              font: {
                size: 11,
                weight: '600'
              }
            }
          }
        }
      }
    });
  }

  function renderDoughnutChart(canvasId, rows) {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label || '-');
    const data = rows.map(item => Number(item.total || 0));

    new Chart(el, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: chartColors,
          borderColor: '#ffffff',
          borderWidth: 3,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              pointStyle: 'circle',
              padding: 16,
              color: '#475569',
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((sum, item) => sum + Number(item || 0), 0);
                const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;

                return `${context.label}: ${numberId(value)} (${percent}%)`;
              }
            }
          }
        }
      }
    });
  }

  function renderHorizontalBarChart(canvasId, rows, valueKey, labelText) {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label || '-');
    const data = rows.map(item => Number(item[valueKey] || 0));

    new Chart(el, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: labelText,
          data: data,
          backgroundColor: chartColors,
          borderColor: chartColors,
          borderWidth: 1,
          borderRadius: 10,
          borderSkipped: false,
          barThickness: 18,
          maxBarThickness: 22
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
              label: function (context) {
                return `${labelText}: ${numberId(context.raw || 0)}`;
              }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            grid: {
              color: 'rgba(148, 163, 184, .18)',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: '#64748b',
              font: {
                size: 11,
                weight: '600'
              }
            }
          },
          y: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              color: '#475569',
              font: {
                size: 11,
                weight: '600'
              }
            }
          }
        }
      }
    });
  }

  $(document).ready(function () {
    initFilterSelect2();

    renderLineChart('referralTrendChart', trendRows || []);
    renderDoughnutChart('statusChart', statusChartRows || []);
    renderHorizontalBarChart('topReferrerChart', topReferrerChartRows || [], 'total_referral', 'Referral');
    renderHorizontalBarChart('topCodeChart', topCodeRows || [], 'total_referral', 'Referral');
  });
})(window.jQuery);
</script>
@endpush