@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Data Customer')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('page-style')
<style>
  :root {
    --mc-primary: #5b5cf6;
    --mc-primary-dark: #4338ca;
    --mc-primary-soft: rgba(91, 92, 246, .12);
    --mc-info: #06b6d4;
    --mc-info-soft: rgba(6, 182, 212, .14);
    --mc-success: #22c55e;
    --mc-success-soft: rgba(34, 197, 94, .14);
    --mc-warning: #f59e0b;
    --mc-warning-soft: rgba(245, 158, 11, .16);
    --mc-danger: #ef4444;
    --mc-danger-soft: rgba(239, 68, 68, .14);
    --mc-pink: #ec4899;
    --mc-purple: #8b5cf6;
    --mc-dark: #1e293b;
    --mc-muted: #64748b;
    --mc-border: #e5e7eb;
    --mc-body: #f6f7fb;
    --mc-card: #ffffff;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(6, 182, 212, .10), transparent 28%),
      var(--mc-body);
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

  .select2-container--bootstrap-5.select2-container--focus .select2-selection,
  .form-control:focus,
  .form-select:focus {
    border-color: var(--mc-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .form-control,
  .form-select {
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .dashboard-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    color: #fff;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.30), transparent 25%),
      linear-gradient(135deg, #4f46e5 0%, #06b6d4 52%, #22c55e 120%);
    box-shadow: 0 1.25rem 2.5rem rgba(79, 70, 229, .22);
    border: 0;
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
    font-weight: 600;
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

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: var(--mc-card);
    transition: .22s ease;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--mc-primary), var(--mc-info));
  }

  .summary-card::after {
    content: "";
    position: absolute;
    width: 90px;
    height: 90px;
    right: -36px;
    bottom: -36px;
    border-radius: 50%;
    background: var(--mc-primary-soft);
  }

  .summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .summary-card h4 {
    color: var(--mc-dark);
    font-weight: 800;
    letter-spacing: -.02em;
  }

  .summary-card .text-muted {
    color: var(--mc-muted) !important;
  }

  .summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--mc-primary), var(--mc-primary-dark));
    color: #fff;
    font-size: 25px;
    flex-shrink: 0;
    box-shadow: 0 .65rem 1.25rem rgba(91, 92, 246, .22);
  }

  .summary-icon.success {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    box-shadow: 0 .65rem 1.25rem rgba(34, 197, 94, .22);
  }

  .summary-icon.info {
    background: linear-gradient(135deg, #06b6d4, #0284c7);
    box-shadow: 0 .65rem 1.25rem rgba(6, 182, 212, .22);
  }

  .summary-icon.warning {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    box-shadow: 0 .65rem 1.25rem rgba(245, 158, 11, .22);
  }

  .summary-icon.danger {
    background: linear-gradient(135deg, #ec4899, #ef4444);
    box-shadow: 0 .65rem 1.25rem rgba(236, 72, 153, .22);
  }

  .filter-box {
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
    font-weight: 600;
  }

  .btn-primary {
    border-color: var(--mc-primary);
    background: linear-gradient(135deg, var(--mc-primary), var(--mc-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .btn-primary:hover {
    border-color: var(--mc-primary-dark);
    background: linear-gradient(135deg, var(--mc-primary-dark), var(--mc-primary));
  }

  .btn-success {
    border-color: var(--mc-success);
    background: linear-gradient(135deg, var(--mc-success), #16a34a);
    box-shadow: 0 .45rem 1rem rgba(34, 197, 94, .18);
  }

  .btn-outline-secondary {
    background: #fff;
    border-color: #d7deea;
    color: #475569;
  }

  .btn-outline-secondary:hover {
    background: #f1f5f9;
    color: #334155;
  }

  .chart-card {
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: #fff;
    overflow: hidden;
  }

  .chart-card .card-header h5 {
    color: var(--mc-dark);
    font-weight: 800;
  }

  .chart-card .card-header small {
    color: var(--mc-muted) !important;
  }

  .chart-wrap {
    position: relative;
    min-height: 335px;
  }

  .chart-wrap-sm {
    position: relative;
    min-height: 275px;
  }

  #customerDemographicMap {
    width: 100%;
    height: 480px;
    border-radius: 1rem;
    background: #eef2ff;
  }

  .customer-map-card {
    overflow: hidden;
  }

  .customer-map-stat {
    padding: .75rem 1rem;
    border: 1px solid var(--mc-border);
    border-radius: .9rem;
    background: #fff;
  }

  .customer-map-legend {
    padding: .65rem .8rem;
    border-radius: .75rem;
    background: rgba(255, 255, 255, .96);
    box-shadow: 0 .35rem 1rem rgba(15, 23, 42, .14);
    color: #334155;
    font-size: .75rem;
    line-height: 1.65;
  }

  .customer-map-popup {
    min-width: 220px;
    color: #334155;
  }

  .customer-map-popup .popup-name {
    margin-bottom: .25rem;
    color: #0f172a;
    font-size: .95rem;
    font-weight: 700;
  }

  .customer-map-dot {
    width: 18px;
    height: 18px;
    border: 3px solid #fff;
    border-radius: 50%;
    box-shadow: 0 2px 9px rgba(15, 23, 42, .45);
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

  .customer-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
  }

  .customer-table tbody td {
    color: #475569;
  }

  .customer-table tbody tr {
    transition: .16s ease;
  }

  .customer-table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .customer-col {
    min-width: 230px;
    white-space: normal !important;
  }

  .address-col {
    min-width: 290px;
    white-space: normal !important;
  }

  .source-col {
    min-width: 200px;
    white-space: normal !important;
  }

  .pagination-info {
    font-size: .875rem;
    color: var(--mc-muted);
  }

  .badge-channel {
    font-size: .75rem;
    padding: .42rem .65rem;
    border-radius: 999px;
    font-weight: 700;
  }

  .bg-label-primary {
    background: var(--mc-primary-soft) !important;
    color: var(--mc-primary-dark) !important;
  }

  .bg-label-success {
    background: var(--mc-success-soft) !important;
    color: #15803d !important;
  }

  .bg-label-info {
    background: var(--mc-info-soft) !important;
    color: #0369a1 !important;
  }

  .bg-label-warning {
    background: var(--mc-warning-soft) !important;
    color: #b45309 !important;
  }

  .bg-label-danger {
    background: var(--mc-danger-soft) !important;
    color: #b91c1c !important;
  }

  .bg-label-secondary {
    background: #f1f5f9 !important;
    color: #475569 !important;
  }

  .mini-list {
    max-height: 420px;
    overflow: auto;
    padding-top: .25rem;
  }

  .mini-list::-webkit-scrollbar {
    width: 6px;
  }

  .mini-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
  }

  .mini-list-item {
    position: relative;
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

  .mini-list-item .fw-semibold {
    color: var(--mc-dark);
  }

  .live-dot {
    display: inline-block;
    width: .55rem;
    height: .55rem;
    border-radius: 999px;
    background: #22c55e;
    box-shadow: 0 0 0 .35rem rgba(34,197,94,.20);
    animation: pulseLive 1.4s infinite;
  }

  @keyframes pulseLive {
    0% {
      box-shadow: 0 0 0 0 rgba(34,197,94,.38);
    }

    70% {
      box-shadow: 0 0 0 .45rem rgba(34,197,94,0);
    }

    100% {
      box-shadow: 0 0 0 0 rgba(34,197,94,0);
    }
  }


  .channel-live-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: #fff;
    height: 100%;
  }

  .channel-live-card .live-card-header {
    padding: 1.15rem 1.25rem;
    border-bottom: 1px solid rgba(226, 232, 240, .8);
    background: linear-gradient(180deg, #fff, #f8fafc);
  }

  .channel-live-card .live-card-body {
    padding: 1.15rem;
    max-height: 430px;
    overflow: auto;
  }

  .store-today-item,
  .ecommerce-today-item {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    padding: .9rem 1rem;
    margin-bottom: .75rem;
    transition: .18s ease;
  }

  .store-today-item {
    cursor: pointer;
  }

  .store-today-item:hover {
    border-color: rgba(91, 92, 246, .35);
    transform: translateY(-2px);
    box-shadow: 0 .65rem 1.35rem rgba(15, 23, 42, .08);
  }

  .store-today-item .store-count {
    min-width: 54px;
    height: 54px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--mc-success-soft);
    color: #15803d;
    font-weight: 900;
    font-size: 1.15rem;
  }

  .ecommerce-today-item .ecommerce-count {
    min-width: 42px;
    height: 42px;
    border-radius: .85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--mc-info-soft);
    color: #0369a1;
    font-weight: 900;
  }

  .modal-content {
    border: 0;
    border-radius: 1.15rem;
    box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, .18);
  }

  .modal-header {
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(180deg, #fff, #f8fafc);
    border-top-left-radius: 1.15rem;
    border-top-right-radius: 1.15rem;
  }

  .modal-footer {
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    border-bottom-left-radius: 1.15rem;
    border-bottom-right-radius: 1.15rem;
  }

  .store-member-table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
  }

  .member-contact {
    color: var(--mc-muted);
    font-size: .82rem;
  }



  .demographic-insight-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
  }

  .demographic-insight-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--mc-pink), var(--mc-purple), var(--mc-info));
  }

  .demographic-score {
    width: 72px;
    height: 72px;
    border-radius: 1.15rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    color: #fff;
    background: linear-gradient(135deg, var(--mc-purple), var(--mc-info));
    box-shadow: 0 .75rem 1.5rem rgba(91, 92, 246, .18);
    flex-shrink: 0;
  }

  .insight-list {
    display: grid;
    gap: .75rem;
  }

  .insight-row {
    border: 1px solid #e2e8f0;
    border-radius: .95rem;
    background: #fff;
    padding: .8rem .9rem;
  }

  .insight-row .progress {
    height: .45rem;
    border-radius: 999px;
    background: #eef2f7;
  }

  .insight-row .progress-bar {
    border-radius: 999px;
    background: linear-gradient(90deg, var(--mc-primary), var(--mc-info));
  }

  @media (max-width: 767.98px) {
    .dashboard-hero {
      border-radius: 1rem;
    }

    .chart-wrap,
    .chart-wrap-sm {
      min-height: 280px;
    }

    .card-body {
      padding: 1rem;
    }
  }
</style>
@endpush

@section('content')
@php
  $summary = $summary ?? (object) [
    'total_customer' => 0,
    'total_today' => 0,
    'total_today_store' => 0,
    'total_today_ecommerce' => 0,
    'total_today_backoffice' => 0,
    'total_this_month' => 0,
    'total_store' => 0,
    'total_ecommerce' => 0,
    'total_backoffice' => 0,
    'total_male' => 0,
    'total_female' => 0,
    'total_unknown_gender' => 0,
    'birthday_today' => 0,
    'birthday_this_month' => 0,
    'total_with_birth_date' => 0,
    'total_without_birth_date' => 0,
    'total_with_region' => 0,
    'total_with_contact' => 0,
  ];

  $summary->total_today_store = $summary->total_today_store ?? 0;
  $summary->total_today_ecommerce = $summary->total_today_ecommerce ?? 0;
  $summary->total_today_backoffice = $summary->total_today_backoffice ?? 0;
  $summary->total_backoffice = $summary->total_backoffice ?? 0;
  $summary->total_with_birth_date = $summary->total_with_birth_date ?? 0;
  $summary->total_without_birth_date = $summary->total_without_birth_date ?? 0;
  $summary->total_with_region = $summary->total_with_region ?? 0;
  $summary->total_with_contact = $summary->total_with_contact ?? 0;

  $ageGroupChartRows = collect($ageGroupChartRows ?? []);
  $demographicCompletenessRows = collect($demographicCompletenessRows ?? []);

  $totalCustomerBase = max((int) ($summary->total_customer ?? 0), 1);
  $genderFilled = (int) ($summary->total_male ?? 0) + (int) ($summary->total_female ?? 0);
  $genderFilledPercent = round(($genderFilled / $totalCustomerBase) * 100, 1);
  $birthFilledPercent = round(((int) ($summary->total_with_birth_date ?? 0) / $totalCustomerBase) * 100, 1);
  $regionFilledPercent = round(((int) ($summary->total_with_region ?? 0) / $totalCustomerBase) * 100, 1);
  $contactFilledPercent = round(((int) ($summary->total_with_contact ?? 0) / $totalCustomerBase) * 100, 1);

  $dominantGenderLabel = (int) ($summary->total_female ?? 0) >= (int) ($summary->total_male ?? 0) ? 'Perempuan' : 'Laki-laki';
  $dominantGenderTotal = max((int) ($summary->total_female ?? 0), (int) ($summary->total_male ?? 0));
  $dominantGenderPercent = round(($dominantGenderTotal / $totalCustomerBase) * 100, 1);

  $topProvinceInsight = collect($provinceChartRows ?? [])->first();
  $topAgeInsight = $ageGroupChartRows->sortByDesc('total')->first();

  $todayStoreRows = collect($todayStoreRows ?? []);
  $todayStoreMemberRows = collect($todayStoreMemberRows ?? []);
  $todayEcommerceRows = collect($todayEcommerceRows ?? []);

  $todayStoreMembersByStore = $todayStoreMemberRows
    ->groupBy(fn ($member) => (string) ($member->store_id ?? 'unknown'))
    ->map(function ($members) {
      return $members->map(function ($member) {
        return [
          'id' => $member->id ?? null,
          'name' => $member->name ?? '-',
          'wa_number' => $member->wa_number ?? null,
          'email' => $member->email ?? null,
          'gender' => $member->gender ?? null,
          'created_at' => $member->created_at ? \Carbon\Carbon::parse($member->created_at)->format('d-m-Y H:i') : '-',
          'province_name' => $member->province_name ?? null,
          'regency_name' => $member->regency_name ?? null,
        ];
      })->values();
    });

  $exportQuery = request()->query();

  $hasFilter = request()->hasAny([
    'name',
    'email',
    'wa_number',
    'gender',
    'province_code',
    'regency_code',
    'district_code',
    'village_code',
    'postal_code',
    'start_date',
    'end_date',
    'start_birth_date',
    'end_birth_date',
    'birthday_month',
    'registration_channel',
    'registration_store_id',
    'per_page',
    'auto_refresh',
  ]);

  $monthOptions = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  <div class="dashboard-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1">
          <span class="fw-light">Monitoring /</span> Data Customer Holding
        </h4>
        <div class="hero-subtitle">
          Dashboard realtime untuk melihat pertumbuhan member, ulang tahun, lokasi customer, gender, dan sumber pendaftaran cabang.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge">
          <span class="live-dot me-1"></span>
          Live Data
        </span>

        <span class="hero-badge">
          <i class="mdi mdi-clock-outline me-1"></i>
          Update: {{ now()->format('d-m-Y H:i:s') }}
        </span>

        @if($autoRefresh)
          <span class="hero-badge">
            <i class="mdi mdi-autorenew me-1"></i>
            Auto Refresh Aktif
          </span>
        @endif
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Dashboard Customer</h5>
        <small class="text-muted">
          Filter dapat digunakan Holding untuk melihat data semua cabang atau cabang tertentu.
        </small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="collapse"
                data-bs-target="#filterDataCustomer">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('monitoring.data-customer.index') }}"
           class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>

        <a href="{{ route('monitoring.data-customer.export', $exportQuery) }}"
           class="btn btn-success btn-sm">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="filterDataCustomer">
        <div class="filter-box">
          <form method="GET"
                action="{{ route('monitoring.data-customer.index') }}"
                class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Nama Customer</label>
              <input type="text"
                     name="name"
                     value="{{ $name ?? '' }}"
                     class="form-control"
                     placeholder="Cari nama customer">
            </div>

            <div class="col-md-3">
              <label class="form-label">Email</label>
              <input type="text"
                     name="email"
                     value="{{ $email ?? '' }}"
                     class="form-control"
                     placeholder="Cari email">
            </div>

            <div class="col-md-3">
              <label class="form-label">No. WA</label>
              <input type="text"
                     name="wa_number"
                     value="{{ $waNumber ?? '' }}"
                     class="form-control"
                     placeholder="Cari nomor WA">
            </div>

            <div class="col-md-3">
              <label class="form-label">Gender</label>
              <select name="gender"
                      id="gender"
                      class="form-select js-select2-basic"
                      data-placeholder="Semua Gender">
                <option value=""></option>
                @foreach($genders as $key => $label)
                  <option value="{{ $key }}" @selected((string)($gender ?? '') === (string)$key)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Tanggal Daftar Dari</label>
              <input type="date"
                     name="start_date"
                     value="{{ $startDate ?? '' }}"
                     class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Tanggal Daftar Sampai</label>
              <input type="date"
                     name="end_date"
                     value="{{ $endDate ?? '' }}"
                     class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Tanggal Lahir Dari</label>
              <input type="date"
                     name="start_birth_date"
                     value="{{ $startBirthDate ?? '' }}"
                     class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Tanggal Lahir Sampai</label>
              <input type="date"
                     name="end_birth_date"
                     value="{{ $endBirthDate ?? '' }}"
                     class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Bulan Ulang Tahun</label>
              <select name="birthday_month"
                      id="birthday_month"
                      class="form-select js-select2-basic"
                      data-placeholder="Semua Bulan">
                <option value=""></option>
                @foreach($monthOptions as $num => $label)
                  <option value="{{ $num }}" @selected((int)($birthdayMonth ?? 0) === $num)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Registration Channel</label>
              <select name="registration_channel"
                      id="registration_channel"
                      class="form-select js-select2-basic"
                      data-placeholder="Semua Channel">
                <option value=""></option>
                @foreach($registrationChannels as $key => $label)
                  <option value="{{ $key }}" @selected((string)($registrationChannel ?? '') === (string)$key)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Cabang / Store Pendaftaran</label>
              <select name="registration_store_id"
                      id="registration_store_id"
                      class="form-select js-select2-basic"
                      data-placeholder="Semua Cabang">
                <option value=""></option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected((string)($registrationStoreId ?? '') === (string)$store->id)>
                    {{ $store->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select">
                @foreach([10, 25, 50, 100, 200] as $page)
                  <option value="{{ $page }}" @selected((int)($perPage ?? 10) === $page)>
                    {{ $page }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Provinsi</label>
              <select name="province_code"
                      id="province_code"
                      class="form-select js-select2-basic"
                      data-placeholder="Semua Provinsi">
                <option value=""></option>
                @foreach($provinces as $province)
                  <option value="{{ $province->code }}" @selected((string)($provinceCode ?? '') === (string)$province->code)>
                    {{ $province->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Kab/Kota</label>
              <select name="regency_code"
                      id="regency_code"
                      class="form-select js-select2-basic"
                      data-placeholder="Semua Kab/Kota">
                <option value=""></option>
                @if($selectedRegency)
                  <option value="{{ $selectedRegency->code }}" selected>
                    {{ $selectedRegency->name }}
                  </option>
                @endif
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Kecamatan</label>
              <select name="district_code"
                      id="district_code"
                      class="form-select js-select2-basic"
                      data-placeholder="Semua Kecamatan">
                <option value=""></option>
                @if($selectedDistrict)
                  <option value="{{ $selectedDistrict->code }}" selected>
                    {{ $selectedDistrict->name }}
                  </option>
                @endif
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Kelurahan</label>
              <select name="village_code"
                      id="village_code"
                      class="form-select js-select2-basic"
                      data-placeholder="Semua Kelurahan">
                <option value=""></option>
                @if($selectedVillage)
                  <option value="{{ $selectedVillage->code }}" selected>
                    {{ $selectedVillage->name }}
                  </option>
                @endif
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Kode Pos</label>
              <input type="text"
                     name="postal_code"
                     id="postal_code"
                     value="{{ $postalCode ?? '' }}"
                     class="form-control"
                     placeholder="Kode Pos">
            </div>

            <div class="col-md-3">
              <label class="form-label">Realtime Auto Refresh</label>
              <select name="auto_refresh" class="form-select">
                <option value="0" @selected(!$autoRefresh)>Nonaktif</option>
                <option value="1" @selected($autoRefresh)>Aktif, refresh 60 detik</option>
              </select>
            </div>

            <div class="col-12">
              <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan
                </button>

                <a href="{{ route('monitoring.data-customer.export', $exportQuery) }}"
                   class="btn btn-success btn-sm">
                  <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
                </a>

                <a href="{{ route('monitoring.data-customer.index') }}"
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
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Total Customer</div>
              <h4 class="mb-0">{{ number_format((int) $summary->total_customer) }}</h4>
              <small class="text-muted">Semua customer sesuai filter</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-account-group-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Member Baru Hari Ini</div>
              <h4 class="mb-0">{{ number_format((int) $summary->total_today) }}</h4>
              <small class="text-muted">
                Store {{ number_format((int) $summary->total_today_store) }} /
                Ecommerce {{ number_format((int) $summary->total_today_ecommerce) }} /
                Backoffice {{ number_format((int) $summary->total_today_backoffice) }}
              </small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-account-plus-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Member Baru Bulan Ini</div>
              <h4 class="mb-0">{{ number_format((int) $summary->total_this_month) }}</h4>
              <small class="text-muted">Periode bulan berjalan</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-calendar-month-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Daftar dari Backoffice</div>
              <h4 class="mb-0">{{ number_format((int) $summary->total_backoffice) }}</h4>
              <small class="text-muted">Diinput oleh admin</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-monitor-account"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Ulang Tahun Hari Ini</div>
              <h4 class="mb-0">{{ number_format((int) $summary->birthday_today) }}</h4>
              <small class="text-muted">Customer birthday hari ini</small>
            </div>
            <div class="summary-icon warning">
              <i class="mdi mdi-cake-variant-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Daftar dari Store</div>
              <h4 class="mb-0">{{ number_format((int) $summary->total_store) }}</h4>
              <small class="text-muted">Customer dari cabang</small>
            </div>
            <div class="summary-icon">
              <i class="mdi mdi-store-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Daftar dari Ecommerce</div>
              <h4 class="mb-0">{{ number_format((int) $summary->total_ecommerce) }}</h4>
              <small class="text-muted">Customer online</small>
            </div>
            <div class="summary-icon success">
              <i class="mdi mdi-cart-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Laki-laki</div>
              <h4 class="mb-0">{{ number_format((int) $summary->total_male) }}</h4>
              <small class="text-muted">Berdasarkan gender</small>
            </div>
            <div class="summary-icon info">
              <i class="mdi mdi-gender-male"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="text-muted small">Perempuan</div>
              <h4 class="mb-0">{{ number_format((int) $summary->total_female) }}</h4>
              <small class="text-muted">Berdasarkan gender</small>
            </div>
            <div class="summary-icon danger">
              <i class="mdi mdi-gender-female"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>




  {{-- DEMOGRAFI PASIEN UNTUK MANAGEMENT --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-4">
      <div class="card demographic-insight-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <div>
              <div class="text-muted small">Kualitas Data Demografi</div>
              <h5 class="mb-1 fw-bold">Kelengkapan Profil Pasien</h5>
              <small class="text-muted">Dipakai management untuk membaca segmentasi pasien.</small>
            </div>
            <div class="demographic-score">{{ $regionFilledPercent }}%</div>
          </div>

          <div class="insight-list">
            <div class="insight-row">
              <div class="d-flex justify-content-between mb-1">
                <span class="fw-semibold">Gender Terisi</span>
                <span class="text-muted small">{{ $genderFilledPercent }}%</span>
              </div>
              <div class="progress"><div class="progress-bar" style="width: {{ min($genderFilledPercent, 100) }}%"></div></div>
            </div>

            <div class="insight-row">
              <div class="d-flex justify-content-between mb-1">
                <span class="fw-semibold">Tanggal Lahir Terisi</span>
                <span class="text-muted small">{{ $birthFilledPercent }}%</span>
              </div>
              <div class="progress"><div class="progress-bar" style="width: {{ min($birthFilledPercent, 100) }}%"></div></div>
            </div>

            <div class="insight-row">
              <div class="d-flex justify-content-between mb-1">
                <span class="fw-semibold">Lokasi Terisi</span>
                <span class="text-muted small">{{ $regionFilledPercent }}%</span>
              </div>
              <div class="progress"><div class="progress-bar" style="width: {{ min($regionFilledPercent, 100) }}%"></div></div>
            </div>

            <div class="insight-row">
              <div class="d-flex justify-content-between mb-1">
                <span class="fw-semibold">Kontak Terisi</span>
                <span class="text-muted small">{{ $contactFilledPercent }}%</span>
              </div>
              <div class="progress"><div class="progress-bar" style="width: {{ min($contactFilledPercent, 100) }}%"></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Segmentasi Umur Pasien</h5>
          <small class="text-muted">Distribusi umur berdasarkan tanggal lahir yang tersedia</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm">
            <canvas id="ageGroupChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Kelengkapan Data Pasien</h5>
          <small class="text-muted">Acuan improvement data oleh cabang dan ecommerce</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm">
            <canvas id="demographicCompletenessChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-12">
      <div class="card demographic-insight-card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
          <div>
            <h5 class="mb-1 fw-bold">Insight Management Demografi Pasien</h5>
            <small class="text-muted">Ringkasan cepat untuk keputusan campaign, CRM, dan evaluasi kualitas data customer.</small>
          </div>
          <span class="badge bg-label-primary badge-channel">
            <i class="mdi mdi-chart-timeline-variant me-1"></i> Management View
          </span>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <div class="insight-row h-100">
                <div class="text-muted small mb-1">Dominan Gender</div>
                <h5 class="mb-1 fw-bold">{{ $dominantGenderLabel }}</h5>
                <div class="text-muted small">{{ number_format($dominantGenderTotal) }} pasien · {{ $dominantGenderPercent }}%</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="insight-row h-100">
                <div class="text-muted small mb-1">Dominan Umur</div>
                <h5 class="mb-1 fw-bold">{{ $topAgeInsight->label ?? '-' }}</h5>
                <div class="text-muted small">{{ number_format((int) ($topAgeInsight->total ?? 0)) }} pasien</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="insight-row h-100">
                <div class="text-muted small mb-1">Wilayah Terbesar</div>
                <h5 class="mb-1 fw-bold">{{ $topProvinceInsight->label ?? '-' }}</h5>
                <div class="text-muted small">{{ number_format((int) ($topProvinceInsight->total ?? 0)) }} pasien</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="insight-row h-100">
                <div class="text-muted small mb-1">Data Belum Lengkap</div>
                <h5 class="mb-1 fw-bold">{{ number_format((int) ($summary->total_without_birth_date ?? 0)) }}</h5>
                <div class="text-muted small">Pasien belum punya tanggal lahir</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- PETA DEMOGRAFI CUSTOMER --}}
  @php
    $customerMapCoverage = (int) $summary->total_customer > 0
      ? round(((int) $customerMapLocated / (int) $summary->total_customer) * 100, 1)
      : 0;
  @endphp
  <div class="card chart-card customer-map-card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Peta Demografi Customer</h5>
        <small class="text-muted">
          Sebaran customer berdasarkan pusat wilayah kelurahan/desa pada alamat customer.
        </small>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <div class="customer-map-stat">
          <span class="text-muted small d-block">Customer Terpetakan</span>
          <strong>{{ number_format((int) $customerMapLocated) }}</strong>
          <span class="text-muted small">/ {{ number_format((int) $summary->total_customer) }}</span>
        </div>
        <div class="customer-map-stat">
          <span class="text-muted small d-block">Cakupan Lokasi</span>
          <strong>{{ $customerMapCoverage }}%</strong>
        </div>
        <div class="customer-map-stat">
          <span class="text-muted small d-block">Koordinat Kelurahan</span>
          <strong>{{ number_format((int) $customerMapVillageLocated) }}</strong>
          <span class="text-muted small">akurat wilayah</span>
        </div>
      </div>
    </div>
    <div class="card-body pt-2">
      @if($customerMapLocated > 0)
        <div id="customerDemographicMap" aria-label="Peta sebaran lokasi customer"></div>
        <div class="alert alert-primary border-0 mt-3 mb-0 py-2">
          <i class="mdi mdi-information-outline me-1"></i>
          Semua titik memakai koordinat pusat kelurahan/desa yang tersimpan pada master wilayah.
          Titik bukan koordinat rumah customer.
        </div>
      @else
        <div class="text-center text-muted py-5">
          <i class="mdi mdi-map-marker-off-outline d-block mb-2" style="font-size:2.75rem;"></i>
          Belum ada customer dengan kelurahan/desa yang memiliki koordinat.
        </div>
      @endif
    </div>
  </div>

  {{-- MEMBER BARU HARI INI: STORE VS ECOMMERCE --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-7">
      <div class="channel-live-card">
        <div class="live-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
          <div>
            <h5 class="mb-1 fw-bold">Member Baru Hari Ini - Store</h5>
            <small class="text-muted">Klik salah satu store untuk melihat detail member baru realtime hari ini.</small>
          </div>
          <span class="badge bg-label-success badge-channel">
            <span class="live-dot me-1"></span>
            {{ number_format((int) $summary->total_today_store) }} Member Store Hari Ini
          </span>
        </div>

        <div class="live-card-body">
          @forelse($todayStoreRows as $storeRow)
            <div class="store-today-item btn-store-today"
                 data-store-id="{{ $storeRow->store_id }}"
                 data-store-name="{{ $storeRow->store_name }}"
                 data-total="{{ (int) $storeRow->total }}"
                 data-bs-toggle="modal"
                 data-bs-target="#storeTodayMemberModal">
              <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                  <div class="store-count">{{ number_format((int) $storeRow->total) }}</div>
                  <div>
                    <div class="fw-bold text-dark">{{ $storeRow->store_name }}</div>
                    <div class="text-muted small">
                      Member baru hari ini
                      @if($storeRow->latest_created_at)
                        · terakhir {{ \Carbon\Carbon::parse($storeRow->latest_created_at)->format('H:i') }}
                      @endif
                    </div>
                  </div>
                </div>
                <div class="text-end">
                  <span class="badge bg-label-primary badge-channel">
                    <i class="mdi mdi-eye-outline me-1"></i> Detail
                  </span>
                </div>
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-4">
              <i class="mdi mdi-store-off-outline d-block mb-2" style="font-size:2.25rem;"></i>
              Belum ada member baru dari store hari ini.
            </div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-xl-5">
      <div class="channel-live-card">
        <div class="live-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
          <div>
            <h5 class="mb-1 fw-bold">Member Baru Hari Ini - Ecommerce</h5>
            <small class="text-muted">Data customer online / ecommerce hari ini.</small>
          </div>
          <span class="badge bg-label-info badge-channel">
            {{ number_format((int) $summary->total_today_ecommerce) }} Member Ecommerce
          </span>
        </div>

        <div class="live-card-body">
          @forelse($todayEcommerceRows as $member)
            <div class="ecommerce-today-item">
              <div class="d-flex align-items-start gap-3">
                <div class="ecommerce-count">
                  <i class="mdi mdi-cart-outline"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="fw-bold text-dark">{{ $member->name ?? '-' }}</div>
                  <div class="member-contact">{{ $member->wa_number ?? $member->email ?? '-' }}</div>
                  <div class="text-muted small">
                    {{ $member->created_at ? \Carbon\Carbon::parse($member->created_at)->format('d-m-Y H:i') : '-' }}
                    @if($member->province_name || $member->regency_name)
                      · {{ $member->province_name ?? '-' }}{{ $member->regency_name ? ' / '.$member->regency_name : '' }}
                    @endif
                  </div>
                </div>
              </div>
            </div>
          @empty
            <div class="text-center text-muted py-4">
              <i class="mdi mdi-cart-off d-block mb-2" style="font-size:2.25rem;"></i>
              Belum ada member baru dari ecommerce hari ini.
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- CHART ROW 1 --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Trend Member Baru 14 Hari Terakhir</h5>
          <small class="text-muted">Monitoring pertumbuhan customer secara realtime</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="memberDailyChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Customer Berdasarkan Channel</h5>
          <small class="text-muted">Store vs Ecommerce</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="channelChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART ROW 2 --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-4 col-lg-6">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Customer Berdasarkan Gender</h5>
          <small class="text-muted">Distribusi gender customer</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm">
            <canvas id="genderChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4 col-lg-6">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Top Provinsi Customer</h5>
          <small class="text-muted">Provinsi dengan customer terbanyak</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm">
            <canvas id="provinceChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4 col-lg-12">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Top Kab/Kota Customer</h5>
          <small class="text-muted">Kota dengan customer terbanyak</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm">
            <canvas id="regencyChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CABANG + LIST --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-6">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Top Cabang Sumber Member</h5>
          <small class="text-muted">Cabang dengan pendaftaran customer terbanyak</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <canvas id="storeChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Member Baru Terakhir</h5>
          <small class="text-muted">10 data terbaru</small>
        </div>
        <div class="card-body mini-list">
          @forelse($newMemberRows as $member)
            <div class="mini-list-item">
              <div class="fw-semibold">{{ $member->name ?? '-' }}</div>
              <div class="text-muted small">{{ $member->wa_number ?? $member->email ?? '-' }}</div>
              <div class="small">{{ $member->registration_source_name ?? '-' }}</div>
              <div class="text-muted small">
                {{ $member->created_at ? \Carbon\Carbon::parse($member->created_at)->format('d-m-Y H:i') : '-' }}
              </div>
            </div>
          @empty
            <div class="text-muted text-center py-4">
              Belum ada data member baru.
            </div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-xl-3">
      <div class="card chart-card">
        <div class="card-header">
          <h5 class="mb-1">Ulang Tahun Bulan Ini</h5>
          <small class="text-muted">Maksimal 20 customer</small>
        </div>
        <div class="card-body mini-list">
          @forelse($birthdayRows as $member)
            <div class="mini-list-item">
              <div class="fw-semibold">{{ $member->name ?? '-' }}</div>
              <div class="text-muted small">{{ $member->wa_number ?? $member->email ?? '-' }}</div>
              <div class="small">
                {{ $member->date_of_birth ? \Carbon\Carbon::parse($member->date_of_birth)->format('d M') : '-' }}
              </div>
              <div class="text-muted small">
                {{ $member->province_name ?? '-' }}{{ $member->regency_name ? ' / '.$member->regency_name : '' }}
              </div>
            </div>
          @empty
            <div class="text-muted text-center py-4">
              Tidak ada data ulang tahun bulan ini.
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Detail Data Monitoring Customer</h5>
        <small class="text-muted">
          Total Data:
          <b>{{ number_format($rows->total(), 0) }}</b>
        </small>
      </div>

      @if($rows->count())
        <div class="pagination-info">
          Menampilkan <b>{{ $rows->firstItem() }}</b> - <b>{{ $rows->lastItem() }}</b>
          dari <b>{{ number_format($rows->total(), 0) }}</b> data
          | Halaman <b>{{ $rows->currentPage() }}</b> / <b>{{ $rows->lastPage() }}</b>
        </div>
      @endif
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm table-nowrap mb-0 customer-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Customer</th>
              <th>No. WA</th>
              <th>Gender</th>
              <th>Tanggal Lahir</th>
              <th>Channel</th>
              <th>Sumber / Cabang</th>
              <th>Tanggal Daftar</th>
              <th>Referral Code</th>
              <th>Alamat</th>
              <th>Provinsi</th>
              <th>Kab/Kota</th>
              <th>Kecamatan</th>
              <th>Kelurahan</th>
              <th>Kode Pos</th>
            </tr>
          </thead>

          <tbody>
            @forelse($rows as $i => $row)
              <tr>
                <td>{{ $rows->firstItem() + $i }}</td>

                <td class="customer-col">
                  <div class="fw-semibold">{{ $row->name ?? '-' }}</div>
                  <div class="text-muted small">{{ $row->email ?? '-' }}</div>
                </td>

                <td>{{ $row->wa_number ?? '-' }}</td>

                <td>
                  @if($row->gender === 'male')
                    Laki-laki
                  @elseif($row->gender === 'female')
                    Perempuan
                  @else
                    -
                  @endif
                </td>

                <td>
                  {{ $row->date_of_birth ? \Carbon\Carbon::parse($row->date_of_birth)->format('d-m-Y') : '-' }}
                </td>

                <td>
                  @if($row->registration_channel === 'store')
                    <span class="badge bg-label-primary badge-channel">
                      <i class="mdi mdi-store-outline me-1"></i> Store
                    </span>
                  @elseif($row->registration_channel === 'ecommerce')
                    <span class="badge bg-label-success badge-channel">
                      <i class="mdi mdi-cart-outline me-1"></i> Ecommerce
                    </span>
                  @elseif($row->registration_channel === 'backoffice')
                    <span class="badge bg-label-info badge-channel">
                      <i class="mdi mdi-monitor-account me-1"></i> Backoffice
                    </span>
                  @else
                    <span class="badge bg-label-secondary badge-channel">-</span>
                  @endif
                </td>

                <td class="source-col">
                  {{ $row->registration_source_name ?? '-' }}
                </td>

                <td>
                  {{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i') : '-' }}
                </td>

                <td>{{ $row->referral_code ?? '-' }}</td>
                <td class="address-col">{{ $row->address ?? '-' }}</td>
                <td>{{ $row->province_name ?? '-' }}</td>
                <td>{{ $row->regency_name ?? '-' }}</td>
                <td>{{ $row->district_name ?? '-' }}</td>
                <td>{{ $row->village_name ?? '-' }}</td>
                <td>{{ $row->postal_code ?? '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="15" class="text-center text-muted py-4">
                  Tidak ada data customer sesuai filter.
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
            dari <b>{{ number_format($rows->total(), 0) }}</b> data
          </div>

          <div>
            {{ $rows->onEachSide(1)->links() }}
          </div>
        </div>
      @endif
    </div>
  </div>

</div>


{{-- MODAL DETAIL MEMBER BARU STORE HARI INI --}}
<div class="modal fade" id="storeTodayMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">
            <i class="mdi mdi-store-outline me-1"></i>
            Member Baru Store Hari Ini
          </h5>
          <small class="text-muted" id="storeTodayModalSubtitle">Realtime hari ini</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
          <span class="badge bg-label-primary badge-channel" id="storeTodayModalName">Store</span>
          <span class="badge bg-label-success badge-channel" id="storeTodayModalTotal">0 member</span>
          <span class="badge bg-label-secondary badge-channel">
            <span class="live-dot me-1"></span> Live hari ini
          </span>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm mb-0 store-member-table">
            <thead>
              <tr>
                <th style="width:60px;" class="text-center">No</th>
                <th>Customer</th>
                <th>No. WA / Email</th>
                <th>Gender</th>
                <th>Lokasi</th>
                <th>Jam Daftar</th>
              </tr>
            </thead>
            <tbody id="storeTodayMemberBody">
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Belum ada data.</td>
              </tr>
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

@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@push('page-script')
<script>
(function ($) {
  'use strict';

  const initialProvince = @json($provinceCode);
  const initialRegency = @json($regencyCode);
  const initialDistrict = @json($districtCode);
  const initialVillage = @json($villageCode);
  const initialPostal = @json($postalCode);
  const autoRefresh = @json($autoRefresh);

  const channelChartRows = @json($channelChartRows);
  const genderChartRows = @json($genderChartRows);
  const ageGroupChartRows = @json($ageGroupChartRows);
  const demographicCompletenessRows = @json($demographicCompletenessRows);
  const provinceChartRows = @json($provinceChartRows);
  const regencyChartRows = @json($regencyChartRows);
  const storeChartRows = @json($storeChartRows);
  const memberDailyRows = @json($memberDailyRows);
  const todayStoreMembersByStore = @json($todayStoreMembersByStore);
  const customerMapPoints = @json($customerMapPoints);

  const $province = $('#province_code');
  const $regency = $('#regency_code');
  const $district = $('#district_code');
  const $village = $('#village_code');
  const $postal = $('#postal_code');
  const $registrationChannel = $('#registration_channel');
  const $registrationStore = $('#registration_store_id');

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

  function escapeMapHtml(value) {
    return String(value ?? '-')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function mountCustomerDemographicMap() {
    const element = document.getElementById('customerDemographicMap');
    if (!element || typeof window.L === 'undefined' || !customerMapPoints?.length) return;

    const map = L.map(element, {
      scrollWheelZoom: false,
      preferCanvas: true
    }).setView([-2.5, 118], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const channelColors = {
      store: '#ef4444',
      ecommerce: '#2563eb',
      backoffice: '#8b5cf6',
      unknown: '#64748b'
    };
    const channelLabels = {
      store: 'Store',
      ecommerce: 'Ecommerce',
      backoffice: 'Backoffice',
      unknown: 'Tidak diketahui'
    };
    const bounds = [];

    customerMapPoints.forEach(function (customer) {
      const latitude = Number(customer.latitude);
      const longitude = Number(customer.longitude);
      if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

      const channel = customer.channel || 'unknown';
      const color = channelColors[channel] || channelColors.unknown;
      const position = [latitude, longitude];
      bounds.push(position);

      const markerIcon = L.divIcon({
        className: '',
        html: '<div class="customer-map-dot" style="background:' + color + '"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 9],
        popupAnchor: [0, -10]
      });
      const marker = L.marker(position, { icon: markerIcon });

      marker.bindPopup(
        '<div class="customer-map-popup">' +
          '<div class="popup-name">' + escapeMapHtml(customer.name) + '</div>' +
          '<div><strong>Kanal:</strong> ' + escapeMapHtml(channelLabels[channel] || channel) + '</div>' +
          '<div><strong>Sumber:</strong> ' + escapeMapHtml(customer.source) + '</div>' +
          '<div><strong>WhatsApp:</strong> ' + escapeMapHtml(customer.wa_number) + '</div>' +
          '<div><strong>Email:</strong> ' + escapeMapHtml(customer.email) + '</div>' +
          '<hr class="my-2">' +
          '<div><strong>Kelurahan:</strong> ' + escapeMapHtml(customer.village) + '</div>' +
          '<div><strong>Kecamatan:</strong> ' + escapeMapHtml(customer.district) + '</div>' +
          '<div><strong>Kab/Kota:</strong> ' + escapeMapHtml(customer.regency) + '</div>' +
          '<div><strong>Provinsi:</strong> ' + escapeMapHtml(customer.province) + '</div>' +
          '<div class="mt-2 text-muted"><strong>Akurasi:</strong> ' +
            'Pusat kelurahan/desa' +
          '</div>' +
        '</div>'
      );

      marker.addTo(map);
    });

    if (bounds.length === 1) {
      map.setView(bounds[0], 13);
    } else if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
    }

    const legend = L.control({ position: 'bottomright' });
    legend.onAdd = function () {
      const div = L.DomUtil.create('div', 'customer-map-legend');
      div.innerHTML =
        '<strong>Kanal Customer</strong><br>' +
        '<span style="color:#ef4444">●</span> Store<br>' +
        '<span style="color:#2563eb">●</span> Ecommerce<br>' +
        '<span style="color:#8b5cf6">●</span> Backoffice';
      return div;
    };
    legend.addTo(map);

    setTimeout(function () {
      map.invalidateSize();
    }, 250);
  }

  function initSelect2Basic() {
    $('.js-select2-basic').each(function () {
      const $el = $(this);

      if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
      }

      $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        placeholder: $el.data('placeholder') || 'Pilih data'
      });
    });
  }

  function resetSelect($el, placeholder, disabled = true) {
    $el.html('<option value=""></option>');
    $el.prop('disabled', disabled);
    $el.attr('data-placeholder', placeholder);
    initSelect2Basic();
  }

  function fillSelect($el, items, placeholder, selectedValue = '') {
    let html = '<option value=""></option>';

    items.forEach(function (item) {
      const selected = String(item.id) === String(selectedValue) ? 'selected' : '';
      html += `<option value="${item.id}" ${selected}>${item.text}</option>`;
    });

    $el.html(html);
    $el.prop('disabled', false);
    $el.attr('data-placeholder', placeholder);
    initSelect2Basic();
  }

  function loadRegencies(provinceCode, selected = '') {
    if (!provinceCode) {
      resetSelect($regency, 'Semua Kab/Kota', true);
      resetSelect($district, 'Semua Kecamatan', true);
      resetSelect($village, 'Semua Kelurahan', true);
      $postal.val('');
      return $.Deferred().resolve().promise();
    }

    return $.get("{{ route('monitoring.data-customer.regencies') }}", {
      province_code: provinceCode
    }).done(function (res) {
      fillSelect($regency, res.results || [], 'Semua Kab/Kota', selected);

      if (!selected) {
        resetSelect($district, 'Semua Kecamatan', true);
        resetSelect($village, 'Semua Kelurahan', true);
        $postal.val('');
      }
    });
  }

  function loadDistricts(regencyCode, selected = '') {
    if (!regencyCode) {
      resetSelect($district, 'Semua Kecamatan', true);
      resetSelect($village, 'Semua Kelurahan', true);
      $postal.val('');
      return $.Deferred().resolve().promise();
    }

    return $.get("{{ route('monitoring.data-customer.districts') }}", {
      regency_code: regencyCode
    }).done(function (res) {
      fillSelect($district, res.results || [], 'Semua Kecamatan', selected);

      if (!selected) {
        resetSelect($village, 'Semua Kelurahan', true);
        $postal.val('');
      }
    });
  }

  function loadVillages(districtCode, selected = '') {
    if (!districtCode) {
      resetSelect($village, 'Semua Kelurahan', true);
      $postal.val('');
      return $.Deferred().resolve().promise();
    }

    return $.get("{{ route('monitoring.data-customer.villages') }}", {
      district_code: districtCode
    }).done(function (res) {
      fillSelect($village, res.results || [], 'Semua Kelurahan', selected);

      if (!selected) {
        $postal.val('');
      }
    });
  }

  function loadPostalCode() {
    const provinceCode = $province.val();
    const regencyCode = $regency.val();
    const districtCode = $district.val();
    const villageCode = $village.val();

    if (!villageCode) {
      $postal.val('');
      return;
    }

    $.get("{{ route('monitoring.data-customer.postal-code') }}", {
      province_code: provinceCode,
      regency_code: regencyCode,
      district_code: districtCode,
      village_code: villageCode
    }).done(function (res) {
      $postal.val(res.postal_code || '');
    });
  }

  function toggleStoreFilter() {
    const channel = $registrationChannel.val();

    if (channel === 'ecommerce') {
      $registrationStore.val('').trigger('change');
      $registrationStore.prop('disabled', true);
    } else {
      $registrationStore.prop('disabled', false);
    }
  }

  function numberId(value) {
    return Number(value || 0).toLocaleString('id-ID');
  }

  function escapeHtml(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function genderLabel(value) {
    if (value === 'male') {
      return 'Laki-laki';
    }

    if (value === 'female') {
      return 'Perempuan';
    }

    return '-';
  }

  function renderStoreTodayModal(storeId, storeName, total) {
    const rows = todayStoreMembersByStore[String(storeId)] || [];
    const tbody = document.getElementById('storeTodayMemberBody');

    document.getElementById('storeTodayModalName').textContent = storeName || 'Store';
    document.getElementById('storeTodayModalTotal').textContent = `${numberId(total || rows.length)} member`;
    document.getElementById('storeTodayModalSubtitle').textContent = `Detail member baru hari ini di ${storeName || 'Store'}`;

    if (!rows.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center text-muted py-4">Belum ada member baru untuk store ini hari ini.</td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = rows.map((member, index) => `
      <tr>
        <td class="text-center">${index + 1}</td>
        <td>
          <div class="fw-semibold">${escapeHtml(member.name || '-')}</div>
          <div class="text-muted small">ID: ${escapeHtml(member.id || '-')}</div>
        </td>
        <td>
          <div>${escapeHtml(member.wa_number || '-')}</div>
          <div class="text-muted small">${escapeHtml(member.email || '-')}</div>
        </td>
        <td>${escapeHtml(genderLabel(member.gender))}</td>
        <td>${escapeHtml(member.province_name || '-')}${member.regency_name ? ' / ' + escapeHtml(member.regency_name) : ''}</td>
        <td>
          <span class="badge bg-label-secondary badge-channel">
            ${escapeHtml(member.created_at || '-')}
          </span>
        </td>
      </tr>
    `).join('');
  }


  function renderDoughnutChart(canvasId, rows) {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label);
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
        layout: {
          padding: 8
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              pointStyle: 'circle',
              boxWidth: 8,
              boxHeight: 8,
              padding: 18,
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

  function renderHorizontalBarChart(canvasId, rows, label = 'Customer') {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label);
    const data = rows.map(item => Number(item.total || 0));

    new Chart(el, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: label,
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
        layout: {
          padding: 4
        },
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
                return `${label}: ${numberId(context.raw || 0)}`;
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

  function renderLineChart(canvasId, rows) {
    const el = document.getElementById(canvasId);

    if (!el) {
      return;
    }

    const labels = rows.map(item => item.label);
    const data = rows.map(item => Number(item.total || 0));

    const gradient = el.getContext('2d').createLinearGradient(0, 0, 0, 320);
    gradient.addColorStop(0, 'rgba(91, 92, 246, .28)');
    gradient.addColorStop(1, 'rgba(91, 92, 246, 0)');

    new Chart(el, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Member Baru',
          data: data,
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
        }]
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
                return `Member baru: ${numberId(context.raw || 0)}`;
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

  $(document).ready(function () {
    initSelect2Basic();

    if (!initialProvince) {
      resetSelect($regency, 'Semua Kab/Kota', true);
      resetSelect($district, 'Semua Kecamatan', true);
      resetSelect($village, 'Semua Kelurahan', true);
    } else {
      loadRegencies(initialProvince, initialRegency)
        .then(function () {
          return loadDistricts(initialRegency, initialDistrict);
        })
        .then(function () {
          return loadVillages(initialDistrict, initialVillage);
        })
        .then(function () {
          if (initialPostal) {
            $postal.val(initialPostal);
          } else {
            loadPostalCode();
          }
        });
    }

    toggleStoreFilter();

    $province.on('change', function () {
      loadRegencies($(this).val());
    });

    $regency.on('change', function () {
      loadDistricts($(this).val());
    });

    $district.on('change', function () {
      loadVillages($(this).val());
    });

    $village.on('change', function () {
      loadPostalCode();
    });

    $registrationChannel.on('change', function () {
      toggleStoreFilter();
    });


    document.querySelectorAll('.btn-store-today').forEach(function (button) {
      button.addEventListener('click', function () {
        renderStoreTodayModal(
          this.dataset.storeId || 'unknown',
          this.dataset.storeName || 'Store',
          Number(this.dataset.total || 0)
        );
      });
    });

    renderLineChart('memberDailyChart', memberDailyRows || []);
    renderDoughnutChart('channelChart', channelChartRows || []);
    renderDoughnutChart('genderChart', genderChartRows || []);
    renderDoughnutChart('ageGroupChart', ageGroupChartRows || []);
    renderHorizontalBarChart('demographicCompletenessChart', demographicCompletenessRows || [], 'Data Terisi');
    renderHorizontalBarChart('provinceChart', provinceChartRows || [], 'Customer');
    renderHorizontalBarChart('regencyChart', regencyChartRows || [], 'Customer');
    renderHorizontalBarChart('storeChart', storeChartRows || [], 'Customer');
    mountCustomerDemographicMap();

    if (autoRefresh) {
      setTimeout(function () {
        window.location.reload();
      }, 60000);
    }
  });
})(window.jQuery);
</script>
@endpush
