@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard - Analytics Kantor Pusat')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endsection

@section('page-style')
<style>
  :root {
    --hq-primary: #5b5cf6;
    --hq-primary-dark: #4338ca;
    --hq-primary-soft: rgba(91, 92, 246, .12);
    --hq-info: #06b6d4;
    --hq-info-soft: rgba(6, 182, 212, .14);
    --hq-success: #22c55e;
    --hq-success-soft: rgba(34, 197, 94, .14);
    --hq-warning: #f59e0b;
    --hq-warning-soft: rgba(245, 158, 11, .16);
    --hq-danger: #ef4444;
    --hq-danger-soft: rgba(239, 68, 68, .14);
    --hq-purple: #8b5cf6;
    --hq-dark: #1e293b;
    --hq-muted: #64748b;
    --hq-body: #f6f7fb;
  }

  body {
    background:
      radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%),
      radial-gradient(circle at top right, rgba(6, 182, 212, .10), transparent 28%),
      var(--hq-body);
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
    border-color: var(--hq-primary);
    box-shadow: 0 0 0 .18rem rgba(91, 92, 246, .14);
  }

  .hq-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.4rem;
    color: #fff;
    background:
      radial-gradient(circle at 15% 20%, rgba(255,255,255,.28), transparent 25%),
      linear-gradient(135deg, #4f46e5 0%, #06b6d4 52%, #22c55e 125%);
    box-shadow: 0 1.25rem 2.5rem rgba(79, 70, 229, .22);
  }

  .hq-hero::before,
  .hq-hero::after {
    content: "";
    position: absolute;
    border-radius: 999px;
    background: rgba(255,255,255,.16);
  }

  .hq-hero::before {
    width: 260px;
    height: 260px;
    right: -70px;
    top: -110px;
  }

  .hq-hero::after {
    width: 180px;
    height: 180px;
    right: 140px;
    bottom: -120px;
  }

  .hq-hero > .row {
    position: relative;
    z-index: 2;
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
    border-bottom: 1px solid rgba(226, 232, 240, .85);
    background: transparent;
    padding: 1.15rem 1.25rem;
  }

  .card-body {
    padding: 1.25rem;
  }

  .filter-box {
    background: linear-gradient(180deg, rgba(248, 250, 252, .98), rgba(255,255,255,.98));
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.15rem;
  }

  .btn {
    border-radius: .75rem;
    font-weight: 700;
  }

  .btn-primary {
    border-color: var(--hq-primary);
    background: linear-gradient(135deg, var(--hq-primary), var(--hq-purple));
    box-shadow: 0 .45rem 1rem rgba(91, 92, 246, .22);
  }

  .metric-card {
    position: relative;
    overflow: hidden;
    min-height: 150px;
    border: 1px solid rgba(226, 232, 240, .85);
    transition: .22s ease;
  }

  .metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 1rem 2rem rgba(15, 23, 42, .10);
  }

  .metric-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--hq-primary), var(--hq-info));
  }

  .metric-title {
    color: var(--hq-muted);
    font-size: .78rem;
    font-weight: 700;
  }

  .metric-value {
    color: var(--hq-dark);
    font-size: 1.55rem;
    font-weight: 900;
    letter-spacing: -.03em;
  }

  .metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 24px;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--hq-primary), var(--hq-primary-dark));
  }

  .metric-icon.success { background: linear-gradient(135deg, #22c55e, #16a34a); }
  .metric-icon.info { background: linear-gradient(135deg, #06b6d4, #0284c7); }
  .metric-icon.warning { background: linear-gradient(135deg, #f59e0b, #f97316); }
  .metric-icon.danger { background: linear-gradient(135deg, #ef4444, #dc2626); }

  .badge-soft-primary,
  .badge-soft-success,
  .badge-soft-info,
  .badge-soft-warning,
  .badge-soft-danger,
  .badge-soft-secondary {
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 800;
  }

  .badge-soft-primary { background: var(--hq-primary-soft); color: var(--hq-primary-dark); }
  .badge-soft-success { background: var(--hq-success-soft); color: #15803d; }
  .badge-soft-info { background: var(--hq-info-soft); color: #0369a1; }
  .badge-soft-warning { background: var(--hq-warning-soft); color: #b45309; }
  .badge-soft-danger { background: var(--hq-danger-soft); color: #b91c1c; }
  .badge-soft-secondary { background: #f1f5f9; color: #475569; }

  .chart-wrap {
    position: relative;
    min-height: 330px;
  }

  .chart-wrap-sm {
    position: relative;
    min-height: 270px;
  }

  .section-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .section-kicker {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    margin-bottom: .35rem;
    color: var(--hq-primary-dark);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  .demographic-card {
    border: 1px solid #e2e8f0;
  }

  .demographic-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
  }

  .demographic-stat {
    min-height: 105px;
    padding: .9rem;
    border: 1px solid #e2e8f0;
    border-radius: .9rem;
    background: linear-gradient(145deg, #fff, #f8fafc);
  }

  .demographic-stat .value {
    margin-top: .3rem;
    color: var(--hq-dark);
    font-size: 1.25rem;
    font-weight: 900;
    line-height: 1.2;
  }

  .demographic-insight {
    display: flex;
    gap: .75rem;
    padding: .85rem;
    border-radius: .9rem;
    background: #eef2ff;
    color: #334155;
  }

  .demographic-insight i {
    color: var(--hq-primary);
    font-size: 1.25rem;
    flex-shrink: 0;
  }

  .insight-item {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    background: linear-gradient(180deg, #fff, #f8fafc);
    margin-bottom: .75rem;
  }

  .insight-label {
    display: inline-block;
    margin-bottom: .2rem;
    color: var(--hq-muted);
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
  }

  .insight-action {
    margin-top: .7rem;
    padding: .65rem .75rem;
    border-radius: .7rem;
    color: #334155;
    background: #eef2ff;
    font-size: .82rem;
    line-height: 1.45;
  }

  .insight-icon {
    width: 38px;
    height: 38px;
    border-radius: .9rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .division-card {
    border: 1px solid #e2e8f0;
    background: linear-gradient(145deg, #fff, #f8fafc);
  }

  .division-icon {
    width: 46px;
    height: 46px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
  }

  .ops-stat {
    padding: .8rem;
    border: 1px solid #e2e8f0;
    border-radius: .9rem;
    background: #fff;
  }

  .ops-stat .value {
    color: var(--hq-dark);
    font-size: 1.15rem;
    font-weight: 900;
  }

  .action-row {
    border-left: 4px solid #cbd5e1;
    padding: .85rem 1rem;
    border-radius: .8rem;
    background: #f8fafc;
    margin-bottom: .7rem;
  }

  .action-row.danger { border-left-color: var(--hq-danger); background: rgba(239, 68, 68, .055); }
  .action-row.warning { border-left-color: var(--hq-warning); background: rgba(245, 158, 11, .065); }
  .action-row.info { border-left-color: var(--hq-info); background: rgba(6, 182, 212, .055); }

  #storeMap {
    height: 470px;
    border-radius: 1rem;
    background: #e8edf5;
    z-index: 1;
  }

  .map-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .65rem;
    margin-bottom: .85rem;
  }

  .map-summary-item {
    padding: .75rem .85rem;
    border: 1px solid #e2e8f0;
    border-radius: .85rem;
    background: #fff;
  }

  .map-summary-item strong {
    display: block;
    color: var(--hq-dark);
    font-size: 1.05rem;
  }

  .customer-map-legend {
    padding: .65rem .75rem;
    border-radius: .75rem;
    background: rgba(255,255,255,.94);
    box-shadow: 0 .35rem 1rem rgba(15,23,42,.14);
    color: #334155;
    font-size: .72rem;
    line-height: 1.65;
  }

  .customer-map-legend i {
    display: inline-block;
    width: .62rem;
    height: .62rem;
    margin-right: .35rem;
    border-radius: 999px;
  }

  @media (max-width: 991.98px) {
    .map-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }

  @media (max-width: 575.98px) {
    .map-summary { grid-template-columns: 1fr; }
    #storeMap { height: 390px; }
  }

  .geo-rank {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    gap: .75rem;
    align-items: center;
    padding: .75rem 0;
    border-bottom: 1px solid #eef2f7;
  }

  .geo-rank:last-child { border-bottom: 0; }

  .location-directory {
    padding: .9rem;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: #f8fafc;
  }

  .location-list-scroll {
    max-height: 430px;
    overflow-y: auto;
    padding-right: .35rem;
    scrollbar-width: thin;
  }

  .location-list-item {
    border-bottom: 1px solid #e8edf4 !important;
    transition: background .18s ease, transform .18s ease;
  }

  .location-list-item:hover,
  .location-list-item:focus {
    outline: 0;
    border-radius: .8rem;
    background: #eef2ff !important;
    transform: translateX(2px);
  }

  .location-directory .nav-pills {
    padding: .25rem;
    border-radius: .8rem;
    background: #e9eef7;
  }

  .geo-rank-number {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: .7rem;
    background: var(--hq-primary-soft);
    color: var(--hq-primary-dark);
    font-weight: 900;
  }

  .geo-bar {
    height: 6px;
    overflow: hidden;
    border-radius: 999px;
    background: #e2e8f0;
    margin-top: .35rem;
  }

  .geo-bar > span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--hq-primary), var(--hq-info));
  }

  .geo-insight {
    border-radius: .9rem;
    padding: .85rem;
    background: linear-gradient(135deg, rgba(91,92,246,.08), rgba(6,182,212,.07));
    border: 1px solid rgba(91,92,246,.13);
  }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table thead th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .76rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 900;
    border-bottom: 1px solid #dbe1ea;
    white-space: nowrap;
  }

  .table tbody td {
    vertical-align: middle;
    color: #475569;
  }

  .table tbody tr:hover {
    background-color: rgba(91, 92, 246, .055);
  }

  .mini-list {
    max-height: 390px;
    overflow: auto;
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

  .stock-monitor-card {
    overflow: hidden;
  }

  .stock-monitor-card .card-header {
    min-height: 92px;
  }

  .stock-monitor-card .card-body {
    display: flex;
    flex-direction: column;
    min-height: 0;
  }

  .stock-monitor-note {
    display: flex;
    align-items: flex-start;
    gap: .55rem;
    margin-bottom: .85rem;
    padding: .65rem .75rem;
    border: 1px solid #fde7bd;
    border-radius: .75rem;
    background: #fffaf0;
    color: #8a5208;
    font-size: .78rem;
    line-height: 1.4;
  }

  .stock-monitor-table {
    max-height: 390px;
    overflow: auto;
    scrollbar-width: thin;
  }

  .stock-monitor-table .table {
    min-width: 560px;
  }

  .stock-monitor-table thead {
    position: sticky;
    top: 0;
    z-index: 2;
    box-shadow: 0 1px 0 #dbe1ea;
  }

  .stock-monitor-table tbody td {
    padding-top: .72rem;
    padding-bottom: .72rem;
  }

  .stock-location-name {
    max-width: 165px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .stock-product-name {
    max-width: 210px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 700;
    color: #334155;
  }

  .stock-monitor-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding-top: .75rem;
    color: var(--hq-muted);
    font-size: .76rem;
  }

  .store-today-item {
    cursor: pointer;
  }

  .empty-state {
    border: 1px dashed #cbd5e1;
    border-radius: 1rem;
    padding: 2rem 1rem;
    background: #f8fafc;
  }

  @media (max-width: 767.98px) {
    .hq-hero { border-radius: 1rem; }
    .card-body, .card-header { padding: 1rem; }
    .chart-wrap, .chart-wrap-sm { min-height: 280px; }
    .section-heading { flex-direction: column; }
    .stock-monitor-card .card-header { min-height: auto; }
    .stock-monitor-table { max-height: 340px; }
  }
</style>
@endsection

@section('content')
@php
  $salesSummary = $salesSummary ?? (object) [
    'store_order_count' => 0,
    'ecommerce_order_count' => 0,
    'store_gmv' => 0,
    'ecommerce_gmv' => 0,
  ];

  $newMemberTodaySummary = $newMemberTodaySummary ?? (object) [
    'total_today' => 0,
    'store_today' => 0,
    'ecommerce_today' => 0,
  ];

  $memberSummary = $memberSummary ?? (object) [
    'total_member' => 0,
    'total_store' => 0,
    'total_ecommerce' => 0,
    'birthday_today' => 0,
  ];

  $stockOpname = $stockOpname ?? [
    'warehouse_total' => 0,
    'store_total' => 0,
    'warehouse_issue' => 0,
    'store_issue' => 0,
  ];

  $wms = $wms ?? [
    'sku' => 0,
    'on_hand' => 0,
    'warehouse_stock_qty' => 0,
    'store_stock_qty' => 0,
    'inbound' => 0,
    'outbound' => 0,
    'by_wh' => collect(),
  ];

  $visitSummary = $visitSummary ?? [
    'total' => 0,
    'unique_customer' => 0,
  ];

  $periodComparison = $periodComparison ?? [
    'gmv_previous' => 0, 'orders_previous' => 0,
    'gmv_growth' => 0, 'orders_growth' => 0,
  ];
  $warehouseOps = $warehouseOps ?? [
    'inbound' => 0, 'outbound' => 0, 'pending_inbound' => 0,
    'pending_outbound' => 0, 'staging' => 0, 'qc_issues' => 0,
    'backlog' => 0, 'flow_ratio' => 0,
  ];
  $storeOps = $storeOps ?? [
    'orders' => 0, 'pending_orders' => 0, 'returns' => 0,
    'open_sessions' => 0, 'transactions' => 0, 'visits' => 0,
    'visit_conversion' => 0,
  ];
  $ecommerceOps = $ecommerceOps ?? [
    'orders' => 0, 'pending_orders' => 0, 'processing_orders' => 0,
    'shipped_orders' => 0, 'completed_orders' => 0, 'cancelled_orders' => 0,
    'completion_rate' => 0, 'cancellation_rate' => 0,
  ];
  $actionItems = $actionItems ?? [];
  $customerGeography = $customerGeography ?? [
    'total' => 0, 'located' => 0, 'coverage_rate' => 0,
    'provinces' => collect(), 'regencies' => collect(),
    'top_province' => null, 'top_regency' => null, 'insights' => [],
  ];
  $customerGenderRows = collect($customerGenderRows ?? []);
  $customerAgeRows = collect($customerAgeRows ?? []);
  $customerChannelRows = collect($customerChannelRows ?? []);
  $branchRevenueAchievement = collect($branchRevenueAchievement ?? []);

  $demographicTotal = (int) $customerGenderRows->sum('total');
  $knownGenderTotal = (int) $customerGenderRows
    ->where('label', '!=', 'Belum diketahui')
    ->sum('total');
  $knownAgeTotal = (int) $customerAgeRows
    ->where('label', '!=', 'Belum diketahui')
    ->sum('total');
  $dominantGender = $customerGenderRows
    ->where('label', '!=', 'Belum diketahui')
    ->sortByDesc('total')
    ->first();
  $dominantAge = $customerAgeRows
    ->where('label', '!=', 'Belum diketahui')
    ->sortByDesc('total')
    ->first();
  $dominantChannel = $customerChannelRows->sortByDesc('total')->first();
  $genderCoverage = $demographicTotal > 0 ? round(($knownGenderTotal / $demographicTotal) * 100, 1) : 0;
  $ageCoverage = $demographicTotal > 0 ? round(($knownAgeTotal / $demographicTotal) * 100, 1) : 0;
  $warehouseBarrierConfigured = $lowStockWarehouse->contains(fn ($row) => (bool) ($row->barrier_configured ?? false));
  $storeBarrierConfigured = $lowStockStore->contains(fn ($row) => (bool) ($row->barrier_configured ?? false));
  $branchRevenueTotal = (float) $branchRevenueAchievement->sum('revenue');
  $branchRevenueAverage = $branchRevenueAchievement->count() > 0
    ? $branchRevenueTotal / $branchRevenueAchievement->count()
    : 0;
  $activeRevenueBranches = $branchRevenueAchievement->where('revenue', '>', 0)->count();

  $periodLabel = \Carbon\Carbon::parse($from)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($to)->format('d M Y');
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  {{-- HERO --}}
  <div class="hq-hero p-4 mb-4">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <h3 class="mb-2 text-white">
          Dashboard Analytics Kantor Pusat
        </h3>
        <div class="text-white-50 mb-3">
          Ringkasan realtime harian dari penjualan, member baru, store, ecommerce, WMS, stock opname, dan stok rendah.
        </div>

        <div class="d-flex flex-wrap gap-2">
          <span class="hero-badge">
            <span class="live-dot me-1"></span> Live Monitoring
          </span>

          <span class="hero-badge">
            <i class="mdi mdi-calendar-range me-1"></i> {{ $periodLabel }}
          </span>

          @if(!empty($roleName))
            <span class="hero-badge">
              <i class="mdi mdi-shield-account-outline me-1"></i> {{ $roleName }}
            </span>
          @endif

          @if($autoRefresh)
            <span class="hero-badge">
              <i class="mdi mdi-autorenew me-1"></i> Auto Refresh 60 Detik
            </span>
          @endif
        </div>
      </div>

      <div class="col-lg-5">
        <div class="row g-3">
          <div class="col-6">
            <div class="hero-badge w-100 d-block">
              <div class="small opacity-75">Tanggal</div>
              <div class="fw-bold" id="currentDate">-</div>
            </div>
          </div>

          <div class="col-6">
            <div class="hero-badge w-100 d-block">
              <div class="small opacity-75">Jam Realtime</div>
              <div class="fw-bold fs-5" id="currentTime">00:00:00</div>
            </div>
          </div>

          <div class="col-6">
            <div class="hero-badge w-100 d-block">
              <div class="small opacity-75">Cabang Aktif</div>
              <div class="fw-bold fs-5">{{ number_format($storesCount, 0, ',', '.') }}</div>
            </div>
          </div>

          <div class="col-6">
            <div class="hero-badge w-100 d-block">
              <div class="small opacity-75">Gudang Aktif</div>
              <div class="fw-bold fs-5">{{ number_format($warehousesCount, 0, ',', '.') }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Dashboard Pusat</h5>
        <small class="text-muted">Gunakan filter untuk melihat performa periode, cabang, gudang, dan channel tertentu.</small>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-secondary btn-sm"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#hqFilter">
          <i class="mdi mdi-filter-variant me-1"></i> Filter
        </button>

        <a href="{{ route('dashboard-analytics') }}" class="btn btn-outline-secondary btn-sm">
          <i class="mdi mdi-refresh me-1"></i> Reset
        </a>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="collapse show" id="hqFilter">
        <div class="filter-box">
          <form method="GET" action="{{ route('dashboard-analytics') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
              <label class="form-label">Dari</label>
              <input type="date" name="from" class="form-control" value="{{ $from }}">
            </div>

            <div class="col-md-2">
              <label class="form-label">Sampai</label>
              <input type="date" name="to" class="form-control" value="{{ $to }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Cabang / Store</label>
              <select name="store_id" class="form-select js-select2" data-placeholder="Semua Store">
                <option value=""></option>
                @foreach($stores as $store)
                  <option value="{{ $store->id }}" @selected((string)($storeId ?? '') === (string)$store->id)>
                    {{ $store->name }} @if(isset($store->code) && $store->code) ({{ $store->code }}) @endif
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Gudang / Warehouse</label>
              <select name="warehouse_id" class="form-select js-select2" data-placeholder="Semua Warehouse">
                <option value=""></option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" @selected((string)($warehouseId ?? '') === (string)$warehouse->id)>
                    {{ $warehouse->name }} @if(isset($warehouse->code) && $warehouse->code) ({{ $warehouse->code }}) @endif
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label">Channel</label>
              <select name="channel" class="form-select js-select2" data-placeholder="Semua Channel">
                <option value="all" @selected(($channel ?? 'all') === 'all')>Semua</option>
                <option value="store" @selected(($channel ?? 'all') === 'store')>Store</option>
                <option value="ecommerce" @selected(($channel ?? 'all') === 'ecommerce')>Ecommerce</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Realtime Auto Refresh</label>
              <select name="auto_refresh" class="form-select">
                <option value="0" @selected(!$autoRefresh)>Nonaktif</option>
                <option value="1" @selected($autoRefresh)>Aktif, refresh 60 detik</option>
              </select>
            </div>

            <div class="col-md-9">
              <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">
                  <i class="mdi mdi-magnify me-1"></i> Tampilkan Dashboard
                </button>

                <a class="btn btn-outline-secondary"
                   href="{{ route('dashboard-analytics', ['from' => now()->toDateString(), 'to' => now()->toDateString()]) }}">
                  Hari Ini
                </a>

                <a class="btn btn-outline-secondary"
                   href="{{ route('dashboard-analytics', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]) }}">
                  Bulan Ini
                </a>

                <a class="btn btn-outline-secondary"
                   href="{{ route('dashboard-analytics', ['from' => now()->subDays(6)->toDateString(), 'to' => now()->toDateString()]) }}">
                  7 Hari
                </a>

                <a class="btn btn-outline-secondary"
                   href="{{ route('dashboard-analytics', ['from' => now()->subDays(29)->toDateString(), 'to' => now()->toDateString()]) }}">
                  30 Hari
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- KPI --}}
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card metric-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <div class="metric-title">GMV Periode</div>
              <div class="metric-value">Rp{{ number_format($gmv, 0, ',', '.') }}</div>
              <small class="text-muted">Store Rp{{ number_format((float)($salesSummary->store_gmv ?? 0), 0, ',', '.') }} • Ecommerce Rp{{ number_format((float)($salesSummary->ecommerce_gmv ?? 0), 0, ',', '.') }}</small>
            </div>
            <div class="metric-icon">
              <i class="mdi mdi-cash-multiple"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card metric-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <div class="metric-title">Order / Buyer</div>
              <div class="metric-value">{{ number_format($orderCount, 0, ',', '.') }} / {{ number_format($buyers, 0, ',', '.') }}</div>
              <small class="text-muted">AOV Rp{{ number_format($aov, 0, ',', '.') }}</small>
            </div>
            <div class="metric-icon info">
              <i class="mdi mdi-cart-check"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card metric-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <div class="metric-title">Member Baru Hari Ini</div>
              <div class="metric-value">{{ number_format((int)($newMemberTodaySummary->total_today ?? 0), 0, ',', '.') }}</div>
              <small class="text-muted">Store {{ number_format((int)($newMemberTodaySummary->store_today ?? 0), 0, ',', '.') }} • Ecommerce {{ number_format((int)($newMemberTodaySummary->ecommerce_today ?? 0), 0, ',', '.') }}</small>
            </div>
            <div class="metric-icon success">
              <i class="mdi mdi-account-plus-outline"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card metric-card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <div class="metric-title">WMS On Hand</div>
              <div class="metric-value">{{ number_format((int)($wms['on_hand'] ?? 0), 0, ',', '.') }}</div>
              <small class="text-muted">SKU {{ number_format((int)($wms['sku'] ?? 0), 0, ',', '.') }} • In/Out {{ number_format((int)($wms['inbound'] ?? 0), 0, ',', '.') }}/{{ number_format((int)($wms['outbound'] ?? 0), 0, ',', '.') }}</small>
            </div>
            <div class="metric-icon warning">
              <i class="mdi mdi-warehouse"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CUSTOMER DEMOGRAPHICS --}}
  <section class="mb-4" aria-labelledby="customerDemographicTitle">
    <div class="section-heading">
      <div>
        <div class="section-kicker"><i class="mdi mdi-account-group-outline"></i> Customer insight</div>
        <h4 id="customerDemographicTitle" class="mb-1 fw-bold">Profil Demografi Customer</h4>
        <p class="text-muted mb-0">Komposisi customer yang terdaftar pada periode {{ $periodLabel }}.</p>
      </div>
      <span class="badge badge-soft-primary">
        {{ number_format($demographicTotal, 0, ',', '.') }} customer dianalisis
      </span>
    </div>

    <div class="row g-4">
      <div class="col-xl-4 col-lg-6">
        <div class="card demographic-card h-100">
          <div class="card-header">
            <h5 class="mb-1 fw-bold">Komposisi Gender</h5>
            <small class="text-muted">Persentase berdasarkan data profil customer.</small>
          </div>
          <div class="card-body">
            <div class="chart-wrap-sm"><div id="chartCustomerGender"></div></div>
            <div class="demographic-insight mt-3">
              <i class="mdi mdi-information-outline"></i>
              <div>
                <strong>{{ data_get($dominantGender, 'label', 'Belum tersedia') }}</strong> merupakan segmen terbesar.
                Kelengkapan data gender <strong>{{ number_format($genderCoverage, 1, ',', '.') }}%</strong>.
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-5 col-lg-6">
        <div class="card demographic-card h-100">
          <div class="card-header">
            <h5 class="mb-1 fw-bold">Kelompok Usia</h5>
            <small class="text-muted">Distribusi umur untuk membantu penentuan target komunikasi.</small>
          </div>
          <div class="card-body">
            <div class="chart-wrap-sm"><div id="chartCustomerAge"></div></div>
            <div class="demographic-insight mt-3">
              <i class="mdi mdi-target-account"></i>
              <div>
                Fokus usia terbesar adalah <strong>{{ data_get($dominantAge, 'label', 'belum tersedia') }}</strong>.
                Kelengkapan tanggal lahir <strong>{{ number_format($ageCoverage, 1, ',', '.') }}%</strong>.
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-lg-12">
        <div class="card demographic-card h-100">
          <div class="card-header">
            <h5 class="mb-1 fw-bold">Ringkasan Akuisisi</h5>
            <small class="text-muted">Sumber dan konsentrasi customer.</small>
          </div>
          <div class="card-body">
            <div class="demographic-summary mb-3">
              <div class="demographic-stat">
                <small class="text-muted">Channel utama</small>
                <div class="value">{{ data_get($dominantChannel, 'label', '-') }}</div>
                <small>{{ number_format((int) data_get($dominantChannel, 'total', 0), 0, ',', '.') }} customer</small>
              </div>
              <div class="demographic-stat">
                <small class="text-muted">Provinsi utama</small>
                <div class="value">{{ data_get($customerGeography, 'top_province.province_name', '-') }}</div>
                <small>{{ number_format((int) data_get($customerGeography, 'top_province.total', 0), 0, ',', '.') }} customer</small>
              </div>
              <div class="demographic-stat">
                <small class="text-muted">Kota utama</small>
                <div class="value">{{ data_get($customerGeography, 'top_regency.regency_name', '-') }}</div>
                <small>{{ number_format((int) data_get($customerGeography, 'top_regency.total', 0), 0, ',', '.') }} customer</small>
              </div>
              <div class="demographic-stat">
                <small class="text-muted">Coverage lokasi</small>
                <div class="value">{{ number_format((float) data_get($customerGeography, 'coverage_rate', 0), 1, ',', '.') }}%</div>
                <small>profil dapat dipetakan</small>
              </div>
            </div>
            <div id="chartCustomerChannel"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- SCORECARD PER DIVISI --}}
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-3">
    <div>
      <h4 class="mb-1 fw-bold">Kesehatan Operasional per Divisi</h4>
      <p class="text-muted mb-0">Ringkasan alur kerja Warehouse, Store, dan E-commerce pada periode terpilih.</p>
    </div>
    <small class="text-muted">
      Pembanding: {{ \Carbon\Carbon::parse($periodComparison['previous_from'])->format('d M') }}
      - {{ \Carbon\Carbon::parse($periodComparison['previous_to'])->format('d M Y') }}
    </small>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-xl-4">
      <div class="card division-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-3">
            <div class="division-icon bg-label-primary"><i class="mdi mdi-warehouse"></i></div>
            <div>
              <h5 class="mb-0 fw-bold">Warehouse</h5>
              <small class="text-muted">Arus barang dan kesiapan stok</small>
            </div>
          </div>
          <span class="badge badge-soft-{{ $warehouseOps['backlog'] > 0 ? 'warning' : 'success' }}">
            {{ $warehouseOps['backlog'] > 0 ? $warehouseOps['backlog'].' backlog' : 'Terkendali' }}
          </span>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Inbound</small><div class="value">{{ number_format($warehouseOps['inbound'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Outbound</small><div class="value">{{ number_format($warehouseOps['outbound'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Flow Ratio</small><div class="value">{{ number_format($warehouseOps['flow_ratio'], 1, ',', '.') }}%</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Pending In</small><div class="value">{{ number_format($warehouseOps['pending_inbound'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Pending Out</small><div class="value">{{ number_format($warehouseOps['pending_outbound'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">QC Issue</small><div class="value text-{{ $warehouseOps['qc_issues'] ? 'danger' : 'success' }}">{{ number_format($warehouseOps['qc_issues'], 0, ',', '.') }}</div></div></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card division-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-3">
            <div class="division-icon bg-label-info"><i class="mdi mdi-storefront-outline"></i></div>
            <div>
              <h5 class="mb-0 fw-bold">Store / Cabang</h5>
              <small class="text-muted">Kasir, kunjungan, dan order cabang</small>
            </div>
          </div>
          <span class="badge badge-soft-{{ $storeOps['pending_orders'] > 0 ? 'warning' : 'success' }}">
            {{ $storeOps['pending_orders'] > 0 ? $storeOps['pending_orders'].' pending' : 'Terkendali' }}
          </span>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Order</small><div class="value">{{ number_format($storeOps['orders'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Transaksi</small><div class="value">{{ number_format($storeOps['transactions'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Kunjungan</small><div class="value">{{ number_format($storeOps['visits'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Konversi</small><div class="value">{{ number_format($storeOps['visit_conversion'], 1, ',', '.') }}%</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Retur</small><div class="value">{{ number_format($storeOps['returns'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Sesi Aktif</small><div class="value">{{ number_format($storeOps['open_sessions'], 0, ',', '.') }}</div></div></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card division-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-3">
            <div class="division-icon bg-label-success"><i class="mdi mdi-shopping-outline"></i></div>
            <div>
              <h5 class="mb-0 fw-bold">E-commerce</h5>
              <small class="text-muted">Pembayaran dan fulfillment order</small>
            </div>
          </div>
          <span class="badge badge-soft-{{ $ecommerceOps['cancellation_rate'] >= 5 ? 'danger' : 'success' }}">
            Cancel {{ number_format($ecommerceOps['cancellation_rate'], 1, ',', '.') }}%
          </span>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Order</small><div class="value">{{ number_format($ecommerceOps['orders'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Pending</small><div class="value">{{ number_format($ecommerceOps['pending_orders'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Diproses</small><div class="value">{{ number_format($ecommerceOps['processing_orders'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Dikirim</small><div class="value">{{ number_format($ecommerceOps['shipped_orders'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Selesai</small><div class="value">{{ number_format($ecommerceOps['completed_orders'], 0, ',', '.') }}</div></div></div>
            <div class="col-4"><div class="ops-stat"><small class="text-muted">Completion</small><div class="value">{{ number_format($ecommerceOps['completion_rate'], 1, ',', '.') }}%</div></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ACTION CENTER --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-5">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Pusat Tindakan Prioritas</h5>
          <small class="text-muted">Masalah lintas divisi diurutkan dari yang paling mendesak.</small>
        </div>
        <div class="card-body">
          @forelse($actionItems as $item)
            <div class="action-row {{ $item['severity'] }}">
              <div class="d-flex justify-content-between gap-2 mb-1">
                <strong>{{ $item['title'] }}</strong>
                <span class="badge badge-soft-{{ $item['severity'] }}">{{ $item['area'] }}</span>
              </div>
              <small class="text-muted">{{ $item['detail'] }}</small>
            </div>
          @empty
            <div class="empty-state text-center">
              <i class="mdi mdi-check-decagram-outline text-success fs-2"></i>
              <div class="fw-bold mt-2">Tidak ada perhatian kritis</div>
              <small class="text-muted">Indikator utama pada periode ini berada dalam kondisi terkendali.</small>
            </div>
          @endforelse
        </div>
      </div>
    </div>
    <div class="col-xl-7">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Distribusi Status Order</h5>
          <small class="text-muted">Membandingkan posisi order Store dan E-commerce dalam alur operasional.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap"><div id="chartOrderStatuses"></div></div>
        </div>
      </div>
    </div>
  </div>

  {{-- STORE GEOGRAPHY --}}
  <div class="card mb-4 overflow-hidden">
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <div class="division-icon bg-label-danger"><i class="mdi mdi-map-marker-radius-outline"></i></div>
          <div>
            <h5 class="mb-0 fw-bold">Sebaran Store dan Warehouse</h5>
            <small class="text-muted">Lingkaran menunjukkan Store, sedangkan penanda kotak menunjukkan Warehouse. Klik titik untuk melihat detail lokasi.</small>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <span class="badge badge-soft-primary">
          {{ number_format((int)$storeMap['mapped'], 0, ',', '.') }} store terpetakan
        </span>
        <span class="badge badge-soft-info">
          {{ number_format((int)$warehouseMap['mapped'], 0, ',', '.') }} warehouse terpetakan
        </span>
      </div>
    </div>

    <div class="card-body">
      <div class="row g-4">
        <div class="col-xl-8">
          <div class="map-summary">
            <div class="map-summary-item">
              <small class="text-muted">Total Store</small>
              <strong>{{ number_format((int)$storeMap['total'], 0, ',', '.') }}</strong>
            </div>
            <div class="map-summary-item">
              <small class="text-muted">Store Terpetakan</small>
              <strong class="text-success">{{ number_format((int)$storeMap['mapped'], 0, ',', '.') }}</strong>
            </div>
            <div class="map-summary-item">
              <small class="text-muted">Total Warehouse</small>
              <strong>{{ number_format((int)$warehouseMap['total'], 0, ',', '.') }}</strong>
            </div>
            <div class="map-summary-item">
              <small class="text-muted">Warehouse Terpetakan</small>
              <strong class="text-info">{{ number_format((int)$warehouseMap['mapped'], 0, ',', '.') }}</strong>
            </div>
          </div>
          <div id="storeMap"></div>
          <div class="d-flex gap-2 align-items-start mt-2 text-muted">
            <i class="mdi mdi-information-outline mt-1"></i>
            <small>Lokasi memakai koordinat `geom` pada Master Store dan Master Warehouse. Data tanpa koordinat tetap terlihat pada daftar dengan status “Belum dipetakan”.</small>
          </div>
        </div>

        <div class="col-xl-4">
          <div class="location-directory">
            <ul class="nav nav-pills nav-fill mb-3" role="tablist">
              <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#mapStoreList" type="button">
                  Store <span class="badge bg-white text-primary ms-1">{{ $storeMap['total'] }}</span>
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#mapWarehouseList" type="button">
                  Warehouse <span class="badge bg-white text-info ms-1">{{ $warehouseMap['total'] }}</span>
                </button>
              </li>
            </ul>

            <div class="input-group input-group-sm mb-3">
              <span class="input-group-text bg-white"><i class="mdi mdi-magnify"></i></span>
              <input id="mapLocationSearch" type="search" class="form-control"
                     placeholder="Cari store atau warehouse...">
            </div>

            <div class="tab-content p-0 shadow-none bg-transparent">
              <div class="tab-pane fade show active" id="mapStoreList">
                <div class="mb-2">
                  <h6 class="mb-0 fw-bold">Daftar Store</h6>
                  <small class="text-muted">Klik nama untuk fokus ke titik pada peta.</small>
                </div>
                <div class="location-list-scroll">
          @forelse($storeMap['points'] as $store)
            <button type="button"
                    class="geo-rank location-list-item w-100 border-0 bg-transparent text-start"
                    data-location-type="store"
                    data-location-id="{{ $store->id }}"
                    data-location-name="{{ mb_strtolower($store->name) }}">
              <span class="geo-rank-number">{{ $loop->iteration }}</span>
              <div class="text-truncate">
                <div class="fw-bold text-dark text-truncate">{{ $store->name }}</div>
                <small class="text-muted">
                  {{ number_format((int)$store->customer_count, 0, ',', '.') }} customer &bull;
                  {{ number_format((int)$store->item_count, 0, ',', '.') }} item &bull;
                  {{ number_format((int)$store->visit_count, 0, ',', '.') }} kunjungan
                </small>
              </div>
              @if($store->latitude !== null && $store->longitude !== null)
                <span class="badge badge-soft-success">Terpetakan</span>
              @else
                <span class="badge badge-soft-warning">Belum dipetakan</span>
              @endif
            </button>
          @empty
            <div class="empty-state text-center text-muted">Belum ada data store.</div>
          @endforelse
                </div>
              </div>

              <div class="tab-pane fade" id="mapWarehouseList">
                <div class="mb-2">
                  <h6 class="mb-0 fw-bold">Daftar Warehouse</h6>
                  <small class="text-muted">Klik nama untuk fokus ke titik pada peta.</small>
                </div>
                <div class="location-list-scroll">
          @forelse($warehouseMap['points'] as $warehouse)
            <button type="button"
                    class="geo-rank location-list-item w-100 border-0 bg-transparent text-start"
                    data-location-type="warehouse"
                    data-location-id="{{ $warehouse->id }}"
                    data-location-name="{{ mb_strtolower($warehouse->name) }}">
              <span class="geo-rank-number bg-label-info">{{ $loop->iteration }}</span>
              <div class="text-truncate">
                <div class="fw-bold text-dark text-truncate">{{ $warehouse->name }}</div>
                <small class="text-muted">
                  {{ number_format((int)$warehouse->item_count, 0, ',', '.') }} item &bull;
                  {{ number_format((int)$warehouse->inbound_count, 0, ',', '.') }} inbound &bull;
                  {{ number_format((int)$warehouse->outbound_count, 0, ',', '.') }} outbound
                </small>
              </div>
              @if($warehouse->latitude !== null && $warehouse->longitude !== null)
                <span class="badge badge-soft-info">Terpetakan</span>
              @else
                <span class="badge badge-soft-warning">Belum dipetakan</span>
              @endif
            </button>
          @empty
            <div class="empty-state text-center text-muted">Belum ada data warehouse.</div>
          @endforelse
                </div>
              </div>
            </div>
            <div id="mapLocationEmpty" class="empty-state text-center text-muted d-none">
              Lokasi tidak ditemukan.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- KESIMPULAN --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-start gap-3">
          <div>
            <h5 class="mb-1 fw-bold">Insight dan Rekomendasi</h5>
            <small class="text-muted">Baca berurutan: kondisi yang terjadi, dampaknya, lalu tindakan yang disarankan.</small>
          </div>
          @php
            $attentionInsightCount = collect($insights)
              ->whereIn('type', ['danger', 'warning'])
              ->count();
          @endphp
          <span class="badge badge-soft-{{ $attentionInsightCount > 0 ? 'warning' : 'success' }}">
            {{ $attentionInsightCount > 0 ? $attentionInsightCount.' perlu perhatian' : 'Semua terkendali' }}
          </span>
        </div>

        <div class="card-body">
          @forelse($insights as $insight)
            <div class="insight-item">
              <div class="d-flex gap-3">
                <div class="insight-icon bg-label-{{ $insight['type'] ?? 'primary' }}">
                  <i class="mdi mdi-lightbulb-on-outline"></i>
                </div>
                <div>
                  <div class="fw-bold text-dark">{{ $insight['title'] ?? '-' }}</div>
                  <div class="mt-2">
                    <span class="insight-label">1. Kondisi</span>
                    <div class="text-muted">{{ $insight['message'] ?? '-' }}</div>
                  </div>
                  @if(!empty($insight['impact']))
                    <div class="mt-2">
                      <span class="insight-label">2. Dampak</span>
                      <div class="text-dark">{{ $insight['impact'] }}</div>
                    </div>
                  @endif
                  @if(!empty($insight['action']))
                    <div class="insight-action">
                      <span class="insight-label">{{ !empty($insight['impact']) ? '3.' : '2.' }} Tindakan disarankan</span>
                      <div><i class="mdi mdi-arrow-right-circle-outline me-1"></i>{{ $insight['action'] }}</div>
                    </div>
                  @endif
                </div>
              </div>
            </div>
          @empty
            <div class="empty-state text-center text-muted">Belum ada kesimpulan.</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Operasional Hari Ini</h5>
          <small class="text-muted">Stock opname, kunjungan, dan birthday customer.</small>
        </div>

        <div class="card-body">
          <div class="row g-3">
            <div class="col-6">
              <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Stock Opname WH</div>
                <h4 class="mb-0">{{ number_format((int)($stockOpname['warehouse_total'] ?? 0), 0, ',', '.') }}</h4>
                <span class="badge badge-soft-danger mt-2">Issue {{ number_format((int)($stockOpname['warehouse_issue'] ?? 0), 0, ',', '.') }}</span>
              </div>
            </div>

            <div class="col-6">
              <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Stock Opname Store</div>
                <h4 class="mb-0">{{ number_format((int)($stockOpname['store_total'] ?? 0), 0, ',', '.') }}</h4>
                <span class="badge badge-soft-danger mt-2">Issue {{ number_format((int)($stockOpname['store_issue'] ?? 0), 0, ',', '.') }}</span>
              </div>
            </div>

            <div class="col-6">
              <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Kunjungan</div>
                <h4 class="mb-0">{{ number_format((int)($visitSummary['total'] ?? 0), 0, ',', '.') }}</h4>
                <span class="badge badge-soft-info mt-2">Unique {{ number_format((int)($visitSummary['unique_customer'] ?? 0), 0, ',', '.') }}</span>
              </div>
            </div>

            <div class="col-6">
              <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Birthday</div>
                <h4 class="mb-0">{{ number_format((int)($memberSummary->birthday_today ?? 0), 0, ',', '.') }}</h4>
                <span class="badge badge-soft-warning mt-2">Hari ini</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CHART ROW --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Trend GMV & Order</h5>
          <small class="text-muted">Pergerakan penjualan berdasarkan hari.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <div id="chartOrdersDaily"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Kontribusi Channel</h5>
          <small class="text-muted">Store vs Ecommerce berdasarkan GMV.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap">
            <div id="chartChannelSales"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- STORE / MEMBER ROW --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2">
          <div>
            <h5 class="mb-1 fw-bold">Pencapaian Omzet per Cabang</h5>
            <small class="text-muted">Omzet aktual dan performa relatif terhadap cabang tertinggi (benchmark 100%).</small>
          </div>
        </div>

        <div class="card-body">
          <div class="row g-2 mb-3">
            <div class="col-sm-4">
              <div class="ops-stat h-100">
                <small class="text-muted">Total omzet cabang</small>
                <div class="value">Rp{{ number_format($branchRevenueTotal, 0, ',', '.') }}</div>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="ops-stat h-100">
                <small class="text-muted">Rata-rata per cabang</small>
                <div class="value">Rp{{ number_format($branchRevenueAverage, 0, ',', '.') }}</div>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="ops-stat h-100">
                <small class="text-muted">Cabang bertransaksi</small>
                <div class="value">{{ $activeRevenueBranches }} / {{ $branchRevenueAchievement->count() }}</div>
              </div>
            </div>
          </div>
          <div class="chart-wrap">
            <div id="chartBranchRevenueAchievement"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Member Baru Hari Ini per Store</h5>
          <small class="text-muted">Klik store untuk melihat data member realtime hari ini.</small>
        </div>

        <div class="card-body mini-list">
          @forelse($newMemberTodayByStore as $store)
            <div class="mini-list-item store-today-item"
                 data-store-id="{{ $store->registration_store_id }}"
                 data-store-name="{{ $store->store_name }}">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                  <div class="fw-bold text-dark">{{ $store->store_name }}</div>
                  <small class="text-muted">Member baru hari ini</small>
                </div>
                <span class="badge badge-soft-success">{{ number_format((int)$store->total_member, 0, ',', '.') }}</span>
              </div>
            </div>
          @empty
            <div class="empty-state text-center text-muted">
              Belum ada member baru dari store hari ini.
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- MEMBER & WMS --}}
  <div class="row g-4 mb-4">
    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Trend Member Baru</h5>
          <small class="text-muted">Store dan Ecommerce berdasarkan tanggal.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm">
            <div id="chartMemberDaily"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Sebaran Stok Warehouse</h5>
          <small class="text-muted">Jumlah item per warehouse.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm">
            <div id="chartWmsByWarehouse"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1 fw-bold">Kunjungan Store</h5>
          <small class="text-muted">Top store berdasarkan kunjungan.</small>
        </div>
        <div class="card-body">
          <div class="chart-wrap-sm">
            <div id="chartVisitsPerStore"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- LOW STOCK --}}
  <div class="row g-4">
    <div class="col-xl-6">
      <div class="card stock-monitor-card h-100">
        <div class="card-header d-flex align-items-start justify-content-between gap-3">
          <div>
            <h5 class="mb-1 fw-bold">Stok Prioritas Warehouse</h5>
            <small class="text-muted">Produk yang perlu dipantau atau segera dipasok.</small>
          </div>
          <span class="badge badge-soft-danger flex-shrink-0">
            {{ number_format($lowStockWarehouse->count(), 0, ',', '.') }} produk
          </span>
        </div>

        <div class="card-body">
          @unless($warehouseBarrierConfigured)
            <div class="stock-monitor-note">
              <i class="mdi mdi-alert-circle-outline fs-6"></i>
              <span>Barrier belum diatur. Daftar diurutkan dari stok aktif paling sedikit.</span>
            </div>
          @endunless

          <div class="table-responsive stock-monitor-table">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <th>Warehouse</th>
                  <th>Produk</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Barrier</th>
                </tr>
              </thead>
              <tbody>
                @forelse($lowStockWarehouse->take(20) as $row)
                  <tr>
                    <td><div class="stock-location-name" title="{{ $row->warehouse_name ?? '-' }}">{{ $row->warehouse_name ?? '-' }}</div></td>
                    <td><div class="stock-product-name" title="{{ $row->product_name ?? '-' }}">{{ $row->product_name ?? '-' }}</div></td>
                    <td class="text-end">
                      <span class="badge badge-soft-danger">{{ number_format((int)($row->qty ?? 0), 0, ',', '.') }}</span>
                    </td>
                    <td class="text-end">
                      @if((bool)($row->barrier_configured ?? false))
                        {{ number_format((int)$row->stock_barrier, 0, ',', '.') }}
                      @else
                        <span class="badge badge-soft-warning">Belum diatur</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">Tidak ada data stok rendah warehouse.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="stock-monitor-footer">
            <span>Menampilkan {{ min(20, $lowStockWarehouse->count()) }} dari {{ $lowStockWarehouse->count() }} produk</span>
            <span><i class="mdi mdi-swap-vertical me-1"></i>Scroll untuk melihat daftar</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-6">
      <div class="card stock-monitor-card h-100">
        <div class="card-header d-flex align-items-start justify-content-between gap-3">
          <div>
            <h5 class="mb-1 fw-bold">Stok Prioritas Store</h5>
            <small class="text-muted">Produk cabang yang perlu dipantau atau direplenish.</small>
          </div>
          <span class="badge badge-soft-danger flex-shrink-0">
            {{ number_format($lowStockStore->count(), 0, ',', '.') }} produk
          </span>
        </div>

        <div class="card-body">
          @unless($storeBarrierConfigured)
            <div class="stock-monitor-note">
              <i class="mdi mdi-alert-circle-outline fs-6"></i>
              <span>Barrier belum diatur. Stok kosong dan stok aktif paling sedikit diprioritaskan.</span>
            </div>
          @endunless

          <div class="table-responsive stock-monitor-table">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <th>Store</th>
                  <th>Produk</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Barrier</th>
                </tr>
              </thead>
              <tbody>
                @forelse($lowStockStore->take(20) as $row)
                  <tr>
                    <td><div class="stock-location-name" title="{{ $row->store_name ?? '-' }}">{{ $row->store_name ?? '-' }}</div></td>
                    <td><div class="stock-product-name" title="{{ $row->product_name ?? '-' }}">{{ $row->product_name ?? '-' }}</div></td>
                    <td class="text-end">
                      <span class="badge badge-soft-danger">{{ number_format((int)($row->qty ?? 0), 0, ',', '.') }}</span>
                    </td>
                    <td class="text-end">
                      @if((bool)($row->barrier_configured ?? false))
                        {{ number_format((int)$row->stock_barrier, 0, ',', '.') }}
                      @else
                        <span class="badge badge-soft-warning">Belum diatur</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">Tidak ada data stok rendah store.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="stock-monitor-footer">
            <span>Menampilkan {{ min(20, $lowStockStore->count()) }} dari {{ $lowStockStore->count() }} produk</span>
            @if($lowStockStore->count() > 6)
              <span><i class="mdi mdi-swap-vertical me-1"></i>Scroll untuk melihat daftar</span>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- MODAL MEMBER STORE HARI INI --}}
<div class="modal fade" id="modalStoreMemberToday" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">
            <i class="mdi mdi-store-outline me-1"></i>
            Member Baru Hari Ini - <span id="modalStoreName">Store</span>
          </h5>
          <small class="text-muted">Data realtime member yang daftar dari store hari ini.</small>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>WA / Email</th>
                <th>Gender</th>
                <th>Lokasi</th>
                <th>Tanggal Daftar</th>
              </tr>
            </thead>
            <tbody id="storeMemberTodayBody">
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Pilih store terlebih dahulu.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

{{-- MODAL DETAIL STORE DARI PETA --}}
<div class="modal fade" id="modalMapStoreDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content overflow-hidden">
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center gap-3">
          <div id="mapStoreAvatar"
               class="division-icon text-white"
               style="background:#4f46e5">
            <i class="mdi mdi-account-outline"></i>
          </div>
          <div>
            <h5 id="mapStoreName" class="modal-title mb-0">Detail Store</h5>
            <small id="mapStoreCode" class="text-muted">Kode Store</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-light border d-flex align-items-start gap-2 mb-3">
          <i class="mdi mdi-map-marker-radius-outline text-primary fs-5"></i>
          <div>
            <div class="fw-bold">Lokasi Store</div>
            <small id="mapStoreCoordinates" class="text-muted">-</small>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-6">
            <small class="text-muted d-block">Telepon</small>
            <span id="mapStorePhone" class="fw-medium">-</span>
          </div>
          <div class="col-6">
            <small class="text-muted d-block">Kode Pos</small>
            <span id="mapStorePostalCode" class="fw-medium">-</span>
          </div>
          <div class="col-6">
            <small class="text-muted d-block">Customer Terdaftar</small>
            <span id="mapStoreCustomers" class="fw-medium">-</span>
          </div>
          <div class="col-6">
            <small class="text-muted d-block">Stok Item</small>
            <span id="mapStoreItems" class="fw-medium">-</span>
          </div>
          <div class="col-6">
            <small class="text-muted d-block">Kunjungan Periode Ini</small>
            <span id="mapStoreVisits" class="fw-medium">-</span>
          </div>
          <div class="col-6">
            <small class="text-muted d-block">Status Peta</small>
            <span class="badge badge-soft-success">Koordinat tersedia</span>
          </div>
          <div class="col-12">
            <small class="text-muted d-block">Alamat Store</small>
            <span id="mapStoreAddress" class="fw-medium">-</span>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalMapWarehouseDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content overflow-hidden">
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center gap-3">
          <div class="division-icon text-white bg-info">
            <i class="mdi mdi-warehouse"></i>
          </div>
          <div>
            <h5 id="mapWarehouseName" class="modal-title mb-0">Detail Warehouse</h5>
            <small id="mapWarehouseCode" class="text-muted">Kode Warehouse</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info d-flex align-items-start gap-2 mb-3">
          <i class="mdi mdi-map-marker-outline fs-5"></i>
          <div>
            <div class="fw-bold">Koordinat Warehouse</div>
            <small id="mapWarehouseCoordinates">-</small>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-4">
            <small class="text-muted d-block">Stok Item</small>
            <span id="mapWarehouseItems" class="fw-bold">-</span>
          </div>
          <div class="col-4">
            <small class="text-muted d-block">Inbound</small>
            <span id="mapWarehouseInbound" class="fw-bold">-</span>
          </div>
          <div class="col-4">
            <small class="text-muted d-block">Outbound</small>
            <span id="mapWarehouseOutbound" class="fw-bold">-</span>
          </div>
          <div class="col-6">
            <small class="text-muted d-block">Kode Pos</small>
            <span id="mapWarehousePostalCode" class="fw-medium">-</span>
          </div>
          <div class="col-12">
            <small class="text-muted d-block">Alamat Warehouse</small>
            <span id="mapWarehouseAddress" class="fw-medium">-</span>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
(function ($) {
  'use strict';

  const autoRefresh = @json($autoRefresh);

  const ordersDaily = @json($ordersDaily);
  const channelSalesRows = @json($channelSalesRows);
  const orderStatusRows = @json($orderStatusRows);
  const gmvPerStore = @json($gmvPerStore);
  const branchRevenueAchievement = @json($branchRevenueAchievement);
  const memberDailyRows = @json($memberDailyRows);
  const customerGenderRows = @json($customerGenderRows);
  const customerAgeRows = @json($customerAgeRows);
  const customerChannelRows = @json($customerChannelRows);
  const wmsByWarehouse = @json($wms['by_wh']);
  const visitsByStore = @json($visitsByStore);
  const storeMemberTodayRows = @json($newMemberTodayStoreRows);
  const ecommerceMemberTodayRows = @json($newMemberTodayEcommerceRows);
  const storeMapPoints = @json($storeMap['points']);
  const warehouseMapPoints = @json($warehouseMap['points']);

  const colors = window.VeeStoreChartPalette || ['#ec4899', '#7c3aed', '#17111f', '#be185d', '#a855f7', '#f9a8d4'];

  function numberId(value) {
    return Number(value || 0).toLocaleString('id-ID');
  }

  function rupiah(value) {
    return 'Rp' + Number(value || 0).toLocaleString('id-ID');
  }

  function escapeHtml(text) {
    return String(text ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function emptyChart(el, message) {
    if (el) {
      el.innerHTML = `<div class="empty-state text-center text-muted">${message}</div>`;
    }
  }

  function mountOrdersDaily() {
    const el = document.querySelector('#chartOrdersDaily');
    if (!el) return;

    if (!Array.isArray(ordersDaily) || ordersDaily.length === 0) {
      emptyChart(el, 'Belum ada data order pada periode ini.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'line', height: 330, toolbar: { show: false } },
      stroke: { width: [0, 3], curve: 'smooth' },
      series: [
        { name: 'Orders', type: 'column', data: ordersDaily.map(r => Number(r.orders || 0)) },
        { name: 'GMV', type: 'line', data: ordersDaily.map(r => Number(r.gmv || 0)) }
      ],
      colors: ['#06b6d4', '#5b5cf6'],
      dataLabels: { enabled: false },
      xaxis: { categories: ordersDaily.map(r => r.day) },
      yaxis: [
        { title: { text: 'Orders' }, labels: { formatter: v => numberId(v) } },
        { opposite: true, title: { text: 'GMV' }, labels: { formatter: v => rupiah(v) } }
      ],
      tooltip: {
        shared: true,
        y: [
          { formatter: v => numberId(v) + ' order' },
          { formatter: v => rupiah(v) }
        ]
      }
    }).render();
  }

  function mountChannelSales() {
    const el = document.querySelector('#chartChannelSales');
    if (!el) return;

    if (!Array.isArray(channelSalesRows) || channelSalesRows.length === 0) {
      emptyChart(el, 'Belum ada data channel pada periode ini.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'donut', height: 320, toolbar: { show: false } },
      labels: channelSalesRows.map(r => r.label),
      series: channelSalesRows.map(r => Number(r.gmv || 0)),
      colors,
      legend: { position: 'bottom' },
      dataLabels: { enabled: true },
      tooltip: { y: { formatter: v => rupiah(v) } }
    }).render();
  }

  function mountOrderStatuses() {
    const el = document.querySelector('#chartOrderStatuses');
    if (!el) return;

    if (!Array.isArray(orderStatusRows) || orderStatusRows.length === 0) {
      emptyChart(el, 'Belum ada status order pada periode ini.');
      return;
    }

    const statuses = [...new Set(orderStatusRows.map(row => row.status || 'unknown'))];
    const channels = ['store', 'ecommerce'];
    const series = channels.map(channel => ({
      name: channel === 'store' ? 'Store' : 'E-commerce',
      data: statuses.map(status => {
        const row = orderStatusRows.find(item => item.channel === channel && item.status === status);
        return Number(row?.total || 0);
      })
    }));

    new ApexCharts(el, {
      chart: { type: 'bar', height: 330, stacked: true, toolbar: { show: false } },
      plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: '70%' } },
      series,
      colors: ['#06b6d4', '#5b5cf6'],
      xaxis: {
        categories: statuses.map(status => status.replaceAll('_', ' ')),
        labels: { formatter: value => numberId(value) }
      },
      dataLabels: { enabled: false },
      legend: { position: 'bottom' },
      tooltip: { y: { formatter: value => numberId(value) + ' order' } }
    }).render();
  }

  function showMapStoreDetail(row, markerColor) {
    const setText = (id, value) => {
      const element = document.getElementById(id);
      if (element) element.textContent = value || '-';
    };

    setText('mapStoreName', row.name || 'Store');
    setText('mapStoreCode', row.code || 'Kode belum tersedia');
    setText('mapStorePhone', row.phone);
    setText('mapStorePostalCode', row.postal_code);
    setText('mapStoreCustomers', `${numberId(row.customer_count)} customer`);
    setText('mapStoreItems', `${numberId(row.item_count)} item`);
    setText('mapStoreVisits', `${numberId(row.visit_count)} kunjungan`);
    setText('mapStoreAddress', row.address);
    setText(
      'mapStoreCoordinates',
      `${Number(row.latitude).toFixed(6)}, ${Number(row.longitude).toFixed(6)}`
    );

    const avatar = document.getElementById('mapStoreAvatar');
    if (avatar) avatar.style.background = markerColor;

    const modalElement = document.getElementById('modalMapStoreDetail');
    if (modalElement) {
      bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
  }

  function showMapWarehouseDetail(row) {
    const setText = (id, value) => {
      const element = document.getElementById(id);
      if (element) element.textContent = value || '-';
    };

    setText('mapWarehouseName', row.name || 'Warehouse');
    setText('mapWarehouseCode', row.code || 'Kode belum tersedia');
    setText('mapWarehouseItems', `${numberId(row.item_count)} item`);
    setText('mapWarehouseInbound', numberId(row.inbound_count));
    setText('mapWarehouseOutbound', numberId(row.outbound_count));
    setText('mapWarehousePostalCode', row.postal_code);
    setText('mapWarehouseAddress', row.address);
    setText(
      'mapWarehouseCoordinates',
      `${Number(row.latitude).toFixed(6)}, ${Number(row.longitude).toFixed(6)}`
    );

    const modalElement = document.getElementById('modalMapWarehouseDetail');
    if (modalElement) {
      bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
  }

  function mountStoreMap() {
    const el = document.getElementById('storeMap');
    if (!el) return;

    const rows = (storeMapPoints || []).filter(row =>
      row.latitude !== null && row.longitude !== null
    );
    const warehouseRows = (warehouseMapPoints || []).filter(row =>
      row.latitude !== null && row.longitude !== null
    );
    const storePalette = [
      '#ec4899', '#7c3aed', '#17111f', '#be185d', '#a855f7',
      '#f9a8d4', '#581c87', '#9d174d', '#2a2033', '#d946ef'
    ];
    const storeNames = rows.map(row => row.name || 'Store');
    const colorForStore = name => {
      const hash = String(name).split('').reduce(
        (value, character) => ((value << 5) - value) + character.charCodeAt(0),
        0
      );
      return storePalette[Math.abs(hash) % storePalette.length];
    };
    const storeColors = new Map(
      storeNames.map(name => [
        name,
        colorForStore(name)
      ])
    );

    if (typeof window.L === 'undefined') {
      emptyChart(el, 'Peta store tidak dapat dimuat.');
      return;
    }

    const map = L.map(el, {
      zoomControl: true,
      scrollWheelZoom: false,
      attributionControl: true
    }).setView([-2.4, 117.5], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const storeLayer = L.layerGroup().addTo(map);
    const warehouseLayer = L.layerGroup().addTo(map);
    const locationMarkers = new Map();

    L.control.layers(null, {
      'Store': storeLayer,
      'Warehouse': warehouseLayer
    }, {
      collapsed: false,
      position: 'topright'
    }).addTo(map);

    if (rows.length === 0 && warehouseRows.length === 0) {
      L.popup()
        .setLatLng([-2.4, 117.5])
        .setContent('Belum ada store atau warehouse yang memiliki koordinat.')
        .openOn(map);
      return;
    }

    const bounds = [];

    rows.forEach(row => {
      const latLng = [Number(row.latitude), Number(row.longitude)];
      const storeName = row.name || 'Store';
      const storeColor = storeColors.get(storeName) || '#7c3aed';
      bounds.push(latLng);

      const marker = L.circleMarker(latLng, {
        radius: 9,
        color: '#ffffff',
        weight: 3,
        fillColor: storeColor,
        fillOpacity: .95
      })
        .bindTooltip(
          `<strong>${escapeHtml(storeName)}</strong><br>` +
          `<small>${escapeHtml(row.code || '')}</small><br>` +
          `<small>${numberId(row.customer_count)} customer</small>`,
          { direction: 'top' }
        )
        .on('click', function () {
          showMapStoreDetail(row, storeColor);
        })
        .addTo(storeLayer);

      locationMarkers.set(`store:${row.id}`, marker);
    });

    warehouseRows.forEach(row => {
      const latLng = [Number(row.latitude), Number(row.longitude)];
      bounds.push(latLng);

      const warehouseIcon = L.divIcon({
        className: '',
        html:
          '<div style="' +
          'width:20px;height:20px;background:#ec4899;border:3px solid #fff;' +
          'border-radius:4px;transform:rotate(45deg);' +
          'box-shadow:0 3px 10px rgba(15,23,42,.35)"></div>',
        iconSize: [20, 20],
        iconAnchor: [10, 10]
      });

      const marker = L.marker(latLng, { icon: warehouseIcon })
        .bindTooltip(
          `<strong>${escapeHtml(row.name || 'Warehouse')}</strong><br>` +
          `<small>${escapeHtml(row.code || '')}</small><br>` +
          `<small>${numberId(row.item_count)} item</small>`,
          { direction: 'top' }
        )
        .on('click', function () {
          showMapWarehouseDetail(row);
        })
        .addTo(warehouseLayer);

      locationMarkers.set(`warehouse:${row.id}`, marker);
    });

    if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [35, 35], maxZoom: 9 });
    } else {
      map.setView(bounds[0], 13);
    }

    const legend = L.control({ position: 'bottomright' });
    legend.onAdd = function () {
      const div = L.DomUtil.create('div', 'customer-map-legend');
      div.innerHTML =
        '<strong>Jenis Lokasi</strong><br>' +
        '<span style="color:#7c3aed;font-size:1rem">●</span> Store (warna per cabang)<br>' +
        '<span style="color:#ec4899;font-size:1rem">◆</span> Warehouse';
      return div;
    };
    legend.addTo(map);

    document.querySelectorAll('.location-list-item').forEach(button => {
      button.addEventListener('click', function () {
        const marker = locationMarkers.get(
          `${this.dataset.locationType}:${this.dataset.locationId}`
        );

        if (!marker) return;

        if (this.dataset.locationType === 'store' && !map.hasLayer(storeLayer)) {
          storeLayer.addTo(map);
        }
        if (this.dataset.locationType === 'warehouse' && !map.hasLayer(warehouseLayer)) {
          warehouseLayer.addTo(map);
        }

        const position = marker.getLatLng();
        map.flyTo(position, 14, { duration: .65 });
        setTimeout(() => marker.fire('click'), 700);
      });
    });

    const locationSearch = document.getElementById('mapLocationSearch');
    locationSearch?.addEventListener('input', function () {
      const keyword = this.value.trim().toLocaleLowerCase('id-ID');
      const activePane = document.querySelector(
        '.location-directory .tab-pane.active'
      );
      let visible = 0;

      activePane?.querySelectorAll('.location-list-item').forEach(item => {
        const matches = !keyword ||
          (item.dataset.locationName || '').includes(keyword);
        item.classList.toggle('d-none', !matches);
        if (matches) visible++;
      });

      document.getElementById('mapLocationEmpty')
        ?.classList.toggle('d-none', visible > 0);
    });

    document.querySelectorAll('.location-directory [data-bs-toggle="tab"]')
      .forEach(tab => {
        tab.addEventListener('shown.bs.tab', function () {
          if (locationSearch) {
            locationSearch.value = '';
            locationSearch.dispatchEvent(new Event('input'));
          }
        });
      });

    setTimeout(() => map.invalidateSize(), 250);
  }

  function mountBranchRevenueAchievement() {
    const el = document.querySelector('#chartBranchRevenueAchievement');
    if (!el) return;

    if (!Array.isArray(branchRevenueAchievement) || branchRevenueAchievement.length === 0) {
      emptyChart(el, 'Belum ada cabang untuk dibandingkan.');
      return;
    }

    const chartHeight = Math.max(330, Math.min(620, branchRevenueAchievement.length * 48));

    new ApexCharts(el, {
      chart: { type: 'bar', height: chartHeight, toolbar: { show: false } },
      plotOptions: {
        bar: {
          horizontal: true,
          distributed: true,
          borderRadius: 7,
          barHeight: '62%'
        }
      },
      series: [{
        name: 'Omzet',
        data: branchRevenueAchievement.map(row => Number(row.revenue || 0))
      }],
      xaxis: {
        categories: branchRevenueAchievement.map(row => row.store_name || 'Store'),
        labels: { formatter: value => rupiah(value) }
      },
      colors: branchRevenueAchievement.map(row =>
        Number(row.revenue || 0) === 0 ? '#cbd5e1' :
          Number(row.achievement_percent || 0) >= 80 ? '#22c55e' :
            Number(row.achievement_percent || 0) >= 50 ? '#5b5cf6' : '#f59e0b'
      ),
      grid: { borderColor: '#eef2f7', strokeDashArray: 4 },
      legend: { show: false },
      dataLabels: {
        enabled: true,
        textAnchor: 'start',
        offsetX: 8,
        formatter: (value, options) => {
          const row = branchRevenueAchievement[options.dataPointIndex];
          return `${Number(row?.achievement_percent || 0).toLocaleString('id-ID')}%`;
        },
        style: { fontSize: '11px', fontWeight: 800, colors: ['#334155'] }
      },
      tooltip: {
        custom: ({ dataPointIndex }) => {
          const row = branchRevenueAchievement[dataPointIndex] || {};
          return `<div class="p-3">
            <div class="fw-bold mb-2">${escapeHtml(row.store_name || 'Store')}</div>
            <div>Omzet: <strong>${rupiah(row.revenue)}</strong></div>
            <div>Benchmark: <strong>${numberId(row.achievement_percent)}%</strong></div>
            <div>Kontribusi: <strong>${numberId(row.contribution_percent)}%</strong></div>
            <div>Order: <strong>${numberId(row.orders)}</strong></div>
            <div>AOV: <strong>${rupiah(row.average_order_value)}</strong></div>
          </div>`;
        }
      }
    }).render();
  }

  function mountMemberDaily() {
    const el = document.querySelector('#chartMemberDaily');
    if (!el) return;

    if (!Array.isArray(memberDailyRows) || memberDailyRows.length === 0) {
      emptyChart(el, 'Belum ada data member baru pada periode ini.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'line', height: 280, toolbar: { show: false } },
      stroke: { width: 3, curve: 'smooth' },
      series: [
        { name: 'Store', data: memberDailyRows.map(r => Number(r.store_total || 0)) },
        { name: 'Ecommerce', data: memberDailyRows.map(r => Number(r.ecommerce_total || 0)) }
      ],
      colors: ['#5b5cf6', '#22c55e'],
      xaxis: { categories: memberDailyRows.map(r => r.label) },
      dataLabels: { enabled: false },
      tooltip: { y: { formatter: v => numberId(v) + ' member' } },
      legend: { position: 'bottom' }
    }).render();
  }

  function mountCustomerGender() {
    const el = document.querySelector('#chartCustomerGender');
    if (!el) return;

    if (!Array.isArray(customerGenderRows) || customerGenderRows.length === 0) {
      emptyChart(el, 'Belum ada data gender pada periode ini.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'donut', height: 260, toolbar: { show: false } },
      labels: customerGenderRows.map(row => row.label),
      series: customerGenderRows.map(row => Number(row.total || 0)),
      colors: ['#5b5cf6', '#ec4899', '#cbd5e1'],
      stroke: { colors: ['#fff'], width: 4 },
      plotOptions: {
        pie: {
          donut: {
            size: '68%',
            labels: {
              show: true,
              name: { show: true },
              value: { show: true, formatter: value => numberId(value) },
              total: {
                show: true,
                label: 'Customer',
                formatter: chart => numberId(chart.globals.seriesTotals.reduce((a, b) => a + b, 0))
              }
            }
          }
        }
      },
      dataLabels: { enabled: false },
      legend: { position: 'bottom', fontSize: '12px' },
      tooltip: { y: { formatter: value => numberId(value) + ' customer' } }
    }).render();
  }

  function mountCustomerAge() {
    const el = document.querySelector('#chartCustomerAge');
    if (!el) return;

    if (!Array.isArray(customerAgeRows) || customerAgeRows.length === 0) {
      emptyChart(el, 'Belum ada data usia pada periode ini.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'bar', height: 260, toolbar: { show: false } },
      series: [{ name: 'Customer', data: customerAgeRows.map(row => Number(row.total || 0)) }],
      colors: customerAgeRows.map(row => row.label === 'Belum diketahui' ? '#cbd5e1' : '#5b5cf6'),
      plotOptions: {
        bar: { horizontal: true, distributed: true, borderRadius: 6, barHeight: '62%' }
      },
      xaxis: {
        categories: customerAgeRows.map(row => row.label),
        labels: { formatter: value => numberId(value) }
      },
      grid: { borderColor: '#eef2f7', strokeDashArray: 4 },
      dataLabels: {
        enabled: true,
        formatter: value => numberId(value),
        style: { fontSize: '11px', colors: ['#334155'] },
        offsetX: 8
      },
      legend: { show: false },
      tooltip: { y: { formatter: value => numberId(value) + ' customer' } }
    }).render();
  }

  function mountCustomerChannel() {
    const el = document.querySelector('#chartCustomerChannel');
    if (!el) return;

    if (!Array.isArray(customerChannelRows) || customerChannelRows.length === 0) {
      emptyChart(el, 'Belum ada data channel.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'bar', height: 160, toolbar: { show: false }, sparkline: { enabled: false } },
      series: [{ name: 'Customer', data: customerChannelRows.map(row => Number(row.total || 0)) }],
      colors: ['#06b6d4', '#22c55e', '#94a3b8'],
      plotOptions: { bar: { distributed: true, borderRadius: 6, columnWidth: '55%' } },
      xaxis: { categories: customerChannelRows.map(row => row.label), labels: { style: { fontSize: '10px' } } },
      yaxis: { labels: { formatter: value => numberId(value) } },
      grid: { show: false },
      dataLabels: { enabled: false },
      legend: { show: false },
      tooltip: { y: { formatter: value => numberId(value) + ' customer' } }
    }).render();
  }

  function mountWmsByWarehouse() {
    const el = document.querySelector('#chartWmsByWarehouse');
    if (!el) return;

    if (!Array.isArray(wmsByWarehouse) || wmsByWarehouse.length === 0) {
      emptyChart(el, 'Belum ada data stok warehouse.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'donut', height: 280, toolbar: { show: false } },
      labels: wmsByWarehouse.map(r => r.warehouse_name || 'Warehouse'),
      series: wmsByWarehouse.map(r => Number(r.qty || 0)),
      colors,
      legend: { position: 'bottom' },
      tooltip: { y: { formatter: v => numberId(v) + ' item' } }
    }).render();
  }

  function mountVisitsPerStore() {
    const el = document.querySelector('#chartVisitsPerStore');
    if (!el) return;

    if (!Array.isArray(visitsByStore) || visitsByStore.length === 0) {
      emptyChart(el, 'Belum ada data kunjungan store.');
      return;
    }

    new ApexCharts(el, {
      chart: { type: 'bar', height: 280, toolbar: { show: false } },
      plotOptions: { bar: { horizontal: true, borderRadius: 8, barHeight: '65%' } },
      series: [{ name: 'Kunjungan', data: visitsByStore.map(r => Number(r.visits || 0)) }],
      xaxis: { categories: visitsByStore.map(r => r.store_name || 'Store') },
      colors: ['#06b6d4'],
      dataLabels: { enabled: false },
      tooltip: { y: { formatter: v => numberId(v) + ' kunjungan' } }
    }).render();
  }

  function updateDateTime() {
    const now = new Date();
    const dt = document.getElementById('currentDate');
    const tm = document.getElementById('currentTime');

    if (dt) {
      dt.textContent = now.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    }

    if (tm) {
      tm.textContent = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
    }
  }

  function renderStoreMemberModal(storeId, storeName) {
    const modalEl = document.getElementById('modalStoreMemberToday');
    const modal = new bootstrap.Modal(modalEl);
    const titleEl = document.getElementById('modalStoreName');
    const bodyEl = document.getElementById('storeMemberTodayBody');

    const rows = (storeMemberTodayRows || []).filter(row => String(row.registration_store_id || '') === String(storeId || ''));

    titleEl.textContent = storeName || 'Store';

    if (rows.length === 0) {
      bodyEl.innerHTML = `
        <tr>
          <td colspan="6" class="text-center text-muted py-4">Belum ada member baru hari ini untuk store ini.</td>
        </tr>
      `;
    } else {
      bodyEl.innerHTML = rows.map((row, index) => `
        <tr>
          <td>${index + 1}</td>
          <td>
            <div class="fw-semibold">${escapeHtml(row.name || '-')}</div>
          </td>
          <td>
            <div>${escapeHtml(row.wa_number || '-')}</div>
            <small class="text-muted">${escapeHtml(row.email || '-')}</small>
          </td>
          <td>${escapeHtml(row.gender || '-')}</td>
          <td>${escapeHtml(row.province_name || '-')}${row.regency_name ? ' / ' + escapeHtml(row.regency_name) : ''}</td>
          <td>${row.created_at ? new Date(row.created_at).toLocaleString('id-ID') : '-'}</td>
        </tr>
      `).join('');
    }

    modal.show();
  }

  $(document).ready(function () {
    $('.js-select2').select2({
      theme: 'bootstrap-5',
      width: '100%',
      allowClear: true,
      placeholder: function () {
        return $(this).data('placeholder') || 'Pilih data';
      }
    });

    mountOrdersDaily();
    mountChannelSales();
    mountOrderStatuses();
    mountStoreMap();
    mountBranchRevenueAchievement();
    mountMemberDaily();
    mountCustomerGender();
    mountCustomerAge();
    mountCustomerChannel();
    mountWmsByWarehouse();
    mountVisitsPerStore();

    updateDateTime();
    setInterval(updateDateTime, 1000);

    document.addEventListener('click', function (event) {
      const item = event.target.closest('.store-today-item');
      if (!item) return;

      renderStoreMemberModal(
        item.getAttribute('data-store-id'),
        item.getAttribute('data-store-name')
      );
    });

    if (autoRefresh) {
      setTimeout(function () {
        window.location.reload();
      }, 60000);
    }
  });
})(window.jQuery);
</script>
@endsection
