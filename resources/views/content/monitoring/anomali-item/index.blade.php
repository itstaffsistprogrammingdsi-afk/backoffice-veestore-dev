@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Anomali Item')

@push('page-style')
<style>
  :root {
    --ai-primary: #5b5cf6;
    --ai-primary-dark: #4338ca;
    --ai-success: #22c55e;
    --ai-info: #06b6d4;
    --ai-warning: #f59e0b;
    --ai-danger: #ef4444;
    --ai-muted: #64748b;
    --ai-body: #f6f7fb;
  }

  body {
    background: radial-gradient(circle at top left, rgba(91, 92, 246, .10), transparent 30%), var(--ai-body);
  }

  .ai-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.35rem;
    color: #fff;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 48%, #ef4444 130%);
    box-shadow: 0 1.25rem 2.5rem rgba(79, 70, 229, .22);
  }

  .ai-hero h4,
  .ai-hero .text-muted {
    color: #fff !important;
  }

  .hero-subtitle {
    color: rgba(255,255,255,.84);
  }

  .hero-badge {
    border-radius: 999px;
    padding: .55rem .85rem;
    background: rgba(255,255,255,.18);
    color: #fff;
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

  .card-body { padding: 1.35rem; }

  .form-control,
  .form-select {
    border-radius: .75rem;
    border-color: #dbe1ea;
  }

  .btn {
    border-radius: .75rem;
    font-weight: 700;
  }

  .btn-primary {
    border-color: var(--ai-primary);
    background: linear-gradient(135deg, var(--ai-primary), #8b5cf6);
  }

  .summary-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: #fff;
  }

  .summary-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--ai-primary), var(--ai-danger));
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
    background: linear-gradient(135deg, var(--ai-primary), var(--ai-primary-dark));
  }

  .summary-icon.warning { background: linear-gradient(135deg, #f59e0b, #f97316); }
  .summary-icon.danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
  .summary-icon.info { background: linear-gradient(135deg, #06b6d4, #0284c7); }

  .filter-box {
    background: linear-gradient(180deg, rgba(248,250,252,.98), rgba(255,255,255,.98));
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1.15rem;
  }

  .table-responsive {
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
  }

  .table th {
    background: linear-gradient(180deg, #f8fafc, #eef2ff);
    color: #334155;
    font-size: .76rem;
    text-transform: uppercase;
    letter-spacing: .035em;
    font-weight: 800;
    border-bottom: 1px solid #dbe1ea;
    white-space: nowrap;
  }

  .table td {
    vertical-align: middle;
    white-space: nowrap;
  }

  .product-col { min-width: 260px; white-space: normal !important; }
  .area-col { min-width: 260px; white-space: normal !important; }
  .note-col { min-width: 260px; white-space: normal !important; }
  .action-col { min-width: 160px; }

  .badge-soft-success,
  .badge-soft-warning,
  .badge-soft-danger,
  .badge-soft-info,
  .badge-soft-secondary,
  .badge-soft-primary,
  .badge-soft-wms,
  .badge-soft-store {
    border-radius: 999px;
    padding: .42rem .65rem;
    font-weight: 800;
  }

  .badge-soft-success { background: rgba(34,197,94,.14); color: #15803d; }
  .badge-soft-warning { background: rgba(245,158,11,.16); color: #b45309; }
  .badge-soft-danger { background: rgba(239,68,68,.14); color: #b91c1c; }
  .badge-soft-info { background: rgba(6,182,212,.14); color: #0369a1; }
  .badge-soft-primary { background: rgba(91,92,246,.14); color: #4338ca; }
  .badge-soft-secondary { background: #f1f5f9; color: #475569; }
  .badge-soft-wms { background: rgba(79,70,229,.13); color: #3730a3; }
  .badge-soft-store { background: rgba(14,165,233,.13); color: #0369a1; }

  .area-box {
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: .95rem;
    padding: .75rem;
  }

  .risk-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
  }
  .risk-1 { background: #22c55e; }
  .risk-3 { background: #f59e0b; }
  .risk-4 { background: #fb7185; }
  .risk-5 { background: #ef4444; }

  .pagination-info { color: var(--ai-muted); font-size: .875rem; }

  .live-indicator {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    border-radius: 999px;
    padding: .55rem .85rem;
    background: rgba(34,197,94,.18);
    color: #fff;
    border: 1px solid rgba(255,255,255,.22);
    font-weight: 800;
    font-size: .78rem;
  }

  .live-dot {
    width: .55rem;
    height: .55rem;
    border-radius: 999px;
    background: #22c55e;
    box-shadow: 0 0 0 rgba(34,197,94,.6);
    animation: livePulse 1.5s infinite;
  }

  @keyframes livePulse {
    0% { box-shadow: 0 0 0 0 rgba(34,197,94,.7); }
    70% { box-shadow: 0 0 0 .55rem rgba(34,197,94,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
  }

  .conclusion-title { font-weight: 800; }

  .history-modal .modal-content {
    border: 0;
    border-radius: 1.25rem;
    overflow: hidden;
  }

  .history-modal .modal-header {
    color: #fff;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
  }

  .history-card-small {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }

  .timeline {
    position: relative;
    padding-left: 2rem;
  }

  .timeline::before {
    content: "";
    position: absolute;
    left: .55rem;
    top: .25rem;
    bottom: .25rem;
    width: 2px;
    background: #e2e8f0;
  }

  .timeline-item {
    position: relative;
    padding-bottom: 1.15rem;
  }

  .timeline-dot {
    position: absolute;
    left: -1.95rem;
    top: .35rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: var(--ai-primary);
    border: 3px solid #fff;
    box-shadow: 0 0 0 3px rgba(91, 92, 246, .14);
  }

  .timeline-box {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
  }

  .movement-label {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    margin: .5rem 0 .75rem 0;
    color: #64748b;
    font-weight: 800;
    font-size: .8rem;
  }



  .demography-card {
    position: relative;
    overflow: hidden;
    height: 100%;
    border: 1px solid rgba(226, 232, 240, .85);
    border-radius: 1.15rem;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    transition: .18s ease;
  }

  .demography-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 .85rem 1.8rem rgba(15, 23, 42, .09);
  }

  .demography-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, var(--ai-primary), var(--ai-info), var(--ai-danger));
  }

  .demography-icon {
    width: 46px;
    height: 46px;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 23px;
    background: linear-gradient(135deg, var(--ai-primary), #8b5cf6);
    box-shadow: 0 .65rem 1.2rem rgba(91, 92, 246, .20);
  }

  .demography-section-title {
    font-weight: 900;
    color: #1e293b;
    letter-spacing: -.02em;
  }

  .demography-list {
    display: flex;
    flex-direction: column;
    gap: .85rem;
  }

  .demography-row {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: .9rem;
    background: #fff;
  }

  .demography-row:hover {
    border-color: rgba(91, 92, 246, .35);
    background: #f8fafc;
  }

  .demography-progress {
    height: .55rem;
    overflow: hidden;
    border-radius: 999px;
    background: #eef2ff;
  }

  .demography-progress-bar {
    height: 100%;
    min-width: .45rem;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--ai-primary), var(--ai-danger));
  }

  .management-insight {
    border: 1px solid rgba(91, 92, 246, .16);
    border-radius: 1.15rem;
    background:
      radial-gradient(circle at top right, rgba(91,92,246,.10), transparent 30%),
      linear-gradient(180deg, #ffffff, #f8fafc);
  }

  .insight-item {
    display: flex;
    gap: .8rem;
    padding: .85rem 0;
    border-bottom: 1px dashed #e2e8f0;
  }

  .insight-item:last-child { border-bottom: 0; }

  .insight-bullet {
    width: 34px;
    height: 34px;
    border-radius: .8rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #fff;
    background: linear-gradient(135deg, var(--ai-primary), var(--ai-info));
  }


  @media (max-width: 767.98px) {
    .card-body { padding: 1rem; }
  }
</style>
@endpush

@section('content')
@php
  $summary = $summary ?? (object) [
    'total_item' => 0,
    'total_anomaly' => 0,
    'total_frequent_move' => 0,
    'total_location_mismatch' => 0,
    'total_frequent_damage' => 0,
    'total_repaired_then_damaged' => 0,
  ];

  $demographicSummary = $demographicSummary ?? (object) [
    'total_anomaly_item' => 0,
    'total_product_affected' => 0,
    'total_location_affected' => 0,
    'total_location_move' => 0,
    'total_damage_event' => 0,
    'average_risk_score' => 0,
  ];

  $productDemographicRows = collect($productDemographicRows ?? []);
  $areaDemographicRows = collect($areaDemographicRows ?? []);
  $conditionDemographicRows = collect($conditionDemographicRows ?? []);
  $anomalyDemographicRows = collect($anomalyDemographicRows ?? []);
  $riskDemographicRows = collect($riskDemographicRows ?? []);

  $maxProductAnomaly = max(1, (int) $productDemographicRows->max('total_item'));
  $maxAreaAnomaly = max(1, (int) $areaDemographicRows->max('total_item'));
  $maxConditionAnomaly = max(1, (int) $conditionDemographicRows->max('total_item'));
  $maxAnomalyType = max(1, (int) $anomalyDemographicRows->max('total_item'));
  $maxRisk = max(1, (int) $riskDemographicRows->max('total_item'));

  $dominantProduct = $productDemographicRows->first();
  $dominantArea = $areaDemographicRows->first();
  $dominantCondition = $conditionDemographicRows->first();
  $dominantRisk = $riskDemographicRows->first();


  $hasFilter = request()->hasAny(['q', 'location_id', 'condition_id', 'anomaly_type', 'start_date', 'end_date', 'min_move_count', 'min_damage_count', 'per_page']);

  $riskBadge = function ($score) {
    $score = (int) $score;
    if ($score >= 5) return 'badge-soft-danger';
    if ($score === 4) return 'badge-soft-warning';
    if ($score >= 3) return 'badge-soft-info';
    return 'badge-soft-success';
  };

  $riskLabel = function ($score) {
    $score = (int) $score;
    if ($score >= 5) return 'Tinggi';
    if ($score === 4) return 'Perlu Dicek';
    if ($score >= 3) return 'Sedang';
    return 'Normal';
  };

  $areaBadge = function ($label) {
    if ($label === 'Gudang WMS') return 'badge-soft-wms';
    if ($label === 'Store') return 'badge-soft-store';
    return 'badge-soft-secondary';
  };

  $formatDateTime = function ($value) {
    return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y H:i') : '-';
  };

  $getConclusion = function ($row) use ($minMoveCount, $minDamageCount) {
    $currentLocationId = $row->current_location_id ?? null;
    $latestHistoryLocationId = $row->latest_history_location_id ?? null;

    $isLocationMismatch = !empty($currentLocationId)
      && !empty($latestHistoryLocationId)
      && (string) $currentLocationId !== (string) $latestHistoryLocationId;

    $label = $row->anomaly_label ?? 'Normal';

    if ($isLocationMismatch) {
      return [
        'title' => 'Cek fisik lokasi item.',
        'description' => 'Lokasi saat ini tidak sama dengan history lokasi terakhir. Kemungkinan barang salah taruh atau data lokasi belum sinkron.',
        'class' => 'text-danger',
      ];
    }

    if ($label === 'Lokasi sering pindah & kondisi sering rusak') {
      return [
        'title' => 'Prioritas audit item.',
        'description' => 'Item sering berpindah lokasi dan sering rusak. Cek handling, area penyimpanan, dan kondisi fisik barang.',
        'class' => 'text-danger',
      ];
    }

    if ((int) ($row->repaired_then_damaged_count ?? 0) > 0 || $label === 'Sudah diperbaiki lalu rusak lagi') {
      return [
        'title' => 'Cek kualitas perbaikan.',
        'description' => 'Item pernah diperbaiki atau kembali baik, tetapi rusak lagi. Perlu audit proses repair dan kelayakan item.',
        'class' => 'text-danger',
      ];
    }

    if ((int) ($row->location_move_count ?? 0) >= (int) $minMoveCount || $label === 'Sering pindah lokasi') {
      return [
        'title' => 'Perlu review perpindahan lokasi.',
        'description' => 'Lokasi saat ini sudah sinkron dengan history terakhir, tetapi item terlalu sering berpindah lokasi. Cek apakah perpindahan sesuai SOP WMS/Store.',
        'class' => 'text-warning',
      ];
    }

    if ((int) ($row->damage_condition_count ?? 0) >= (int) $minDamageCount || $label === 'Kondisi sering rusak') {
      return [
        'title' => 'Cek handling dan penyimpanan.',
        'description' => 'Item sering masuk kondisi rusak. Periksa proses pemindahan, lokasi penyimpanan, dan kualitas barang.',
        'class' => 'text-danger',
      ];
    }

    return [
      'title' => 'Data normal.',
      'description' => 'Lokasi saat ini sesuai dengan history terakhir dan belum ada pola anomali besar.',
      'class' => 'text-success',
    ];
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  <div class="ai-hero p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h4 class="mb-1"><span class="fw-light">Monitoring /</span> Anomali Item</h4>
        <div class="hero-subtitle">
          Monitoring item berdasarkan relasi lokasi Gudang WMS dan Store, histori lokasi, histori kondisi, dan status item.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="hero-badge"><i class="mdi mdi-package-variant-closed me-1"></i>{{ number_format((int) $summary->total_item, 0, ',', '.') }} Item</span>
        <span class="hero-badge"><i class="mdi mdi-alert-circle-outline me-1"></i>{{ number_format((int) $summary->total_anomaly, 0, ',', '.') }} Anomali</span>
        <span class="hero-badge"><i class="mdi mdi-clock-outline me-1"></i>Update: <span id="lastUpdateText">{{ $lastUpdate ?? now()->format('d-m-Y H:i:s') }}</span></span>
        <span class="live-indicator"><span class="live-dot"></span>Live 15 detik</span>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4" id="summaryCards">
    <div class="col-sm-6 col-xl-2">
      <div class="summary-card p-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div><small class="text-muted fw-semibold">Total Item</small><h4 class="mb-0 fw-bold">{{ number_format((int) $summary->total_item, 0, ',', '.') }}</h4></div>
          <span class="summary-icon"><i class="mdi mdi-package-variant"></i></span>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-2">
      <div class="summary-card p-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div><small class="text-muted fw-semibold">Anomali</small><h4 class="mb-0 fw-bold">{{ number_format((int) $summary->total_anomaly, 0, ',', '.') }}</h4></div>
          <span class="summary-icon danger"><i class="mdi mdi-alert-decagram-outline"></i></span>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-2">
      <div class="summary-card p-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div><small class="text-muted fw-semibold">Sering Pindah</small><h4 class="mb-0 fw-bold">{{ number_format((int) $summary->total_frequent_move, 0, ',', '.') }}</h4></div>
          <span class="summary-icon warning"><i class="mdi mdi-swap-horizontal"></i></span>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-2">
      <div class="summary-card p-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div><small class="text-muted fw-semibold">Salah Lokasi</small><h4 class="mb-0 fw-bold">{{ number_format((int) $summary->total_location_mismatch, 0, ',', '.') }}</h4></div>
          <span class="summary-icon info"><i class="mdi mdi-map-marker-alert-outline"></i></span>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-2">
      <div class="summary-card p-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div><small class="text-muted fw-semibold">Sering Rusak</small><h4 class="mb-0 fw-bold">{{ number_format((int) $summary->total_frequent_damage, 0, ',', '.') }}</h4></div>
          <span class="summary-icon danger"><i class="mdi mdi-tools"></i></span>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-2">
      <div class="summary-card p-3">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div><small class="text-muted fw-semibold">Rusak Lagi</small><h4 class="mb-0 fw-bold">{{ number_format((int) $summary->total_repaired_then_damaged, 0, ',', '.') }}</h4></div>
          <span class="summary-icon danger"><i class="mdi mdi-restore-alert"></i></span>
        </div>
      </div>
    </div>
  </div>

  {{-- DEMOGRAFI ANOMALI ITEM --}}
  <div class="card mb-4" id="anomalyDemographicBlock">
    <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <h5 class="mb-1 demography-section-title">Data Demografi Anomali Item</h5>
        <small class="text-muted">
          Ringkasan produk tertentu, area, kondisi, jenis anomali, dan level risiko untuk kebutuhan monitoring management.
        </small>
      </div>
      <span class="badge-soft-primary">
        <i class="mdi mdi-chart-box-outline me-1"></i> Demografi Produk Anomali
      </span>
    </div>

    <div class="card-body">
      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
          <div class="demography-card p-3">
            <div class="d-flex justify-content-between align-items-center gap-3">
              <div>
                <small class="text-muted fw-semibold">Item Anomali</small>
                <h4 class="mb-0 fw-bold">{{ number_format((int) $demographicSummary->total_anomaly_item, 0, ',', '.') }}</h4>
              </div>
              <span class="demography-icon"><i class="mdi mdi-alert-box-outline"></i></span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-2">
          <div class="demography-card p-3">
            <div class="d-flex justify-content-between align-items-center gap-3">
              <div>
                <small class="text-muted fw-semibold">Produk Terdampak</small>
                <h4 class="mb-0 fw-bold">{{ number_format((int) $demographicSummary->total_product_affected, 0, ',', '.') }}</h4>
              </div>
              <span class="demography-icon"><i class="mdi mdi-package-variant-closed-alert"></i></span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-2">
          <div class="demography-card p-3">
            <div class="d-flex justify-content-between align-items-center gap-3">
              <div>
                <small class="text-muted fw-semibold">Lokasi Terdampak</small>
                <h4 class="mb-0 fw-bold">{{ number_format((int) $demographicSummary->total_location_affected, 0, ',', '.') }}</h4>
              </div>
              <span class="demography-icon"><i class="mdi mdi-map-marker-multiple-outline"></i></span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-2">
          <div class="demography-card p-3">
            <div class="d-flex justify-content-between align-items-center gap-3">
              <div>
                <small class="text-muted fw-semibold">Total Pindah</small>
                <h4 class="mb-0 fw-bold">{{ number_format((int) $demographicSummary->total_location_move, 0, ',', '.') }}x</h4>
              </div>
              <span class="demography-icon"><i class="mdi mdi-swap-horizontal-bold"></i></span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-2">
          <div class="demography-card p-3">
            <div class="d-flex justify-content-between align-items-center gap-3">
              <div>
                <small class="text-muted fw-semibold">Total Rusak</small>
                <h4 class="mb-0 fw-bold">{{ number_format((int) $demographicSummary->total_damage_event, 0, ',', '.') }}x</h4>
              </div>
              <span class="demography-icon"><i class="mdi mdi-tools"></i></span>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-2">
          <div class="demography-card p-3">
            <div class="d-flex justify-content-between align-items-center gap-3">
              <div>
                <small class="text-muted fw-semibold">Rata-rata Risiko</small>
                <h4 class="mb-0 fw-bold">{{ number_format((float) $demographicSummary->average_risk_score, 2, ',', '.') }}</h4>
              </div>
              <span class="demography-icon"><i class="mdi mdi-gauge"></i></span>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-xl-7">
          <div class="management-insight p-3 h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
              <div>
                <h6 class="mb-1 fw-bold">Top Produk Paling Banyak Anomali</h6>
                <small class="text-muted">Gunakan filter produk/RFID untuk melihat demografi produk tertentu.</small>
              </div>
              <span class="badge-soft-danger">Prioritas Audit</span>
            </div>

            <div class="demography-list">
              @forelse($productDemographicRows as $productRow)
                @php
                  $productPercent = min(100, round(((int) $productRow->total_item / $maxProductAnomaly) * 100));
                @endphp
                <div class="demography-row">
                  <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                    <div>
                      <div class="fw-bold text-dark">{{ $productRow->label }}</div>
                      <small class="text-muted">Kode: {{ $productRow->code }}</small>
                    </div>
                    <div class="text-md-end">
                      <span class="badge-soft-danger">{{ number_format((int) $productRow->total_item, 0, ',', '.') }} item</span>
                      <small class="text-muted d-block mt-1">High risk: {{ number_format((int) $productRow->high_risk_item, 0, ',', '.') }}</small>
                    </div>
                  </div>
                  <div class="demography-progress mb-2">
                    <div class="demography-progress-bar" style="width: {{ $productPercent }}%"></div>
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <span class="badge-soft-warning">Pindah: {{ number_format((int) $productRow->total_move, 0, ',', '.') }}x</span>
                    <span class="badge-soft-danger">Rusak: {{ number_format((int) $productRow->total_damage, 0, ',', '.') }}x</span>
                    <span class="badge-soft-info">Risiko Max: {{ number_format((int) $productRow->max_risk, 0, ',', '.') }}</span>
                  </div>
                </div>
              @empty
                <div class="text-center text-muted py-4">Belum ada demografi produk anomali.</div>
              @endforelse
            </div>
          </div>
        </div>

        <div class="col-xl-5">
          <div class="management-insight p-3 h-100">
            <h6 class="mb-3 fw-bold">Insight Untuk Management</h6>

            <div class="insight-item">
              <span class="insight-bullet"><i class="mdi mdi-package-variant"></i></span>
              <div>
                <div class="fw-bold">Produk dominan</div>
                <small class="text-muted">
                  {{ $dominantProduct->label ?? '-' }}
                  @if($dominantProduct)
                    — {{ number_format((int) $dominantProduct->total_item, 0, ',', '.') }} item anomali.
                  @endif
                </small>
              </div>
            </div>

            <div class="insight-item">
              <span class="insight-bullet"><i class="mdi mdi-map-marker-alert-outline"></i></span>
              <div>
                <div class="fw-bold">Area paling terdampak</div>
                <small class="text-muted">
                  {{ $dominantArea->label ?? '-' }}
                  @if($dominantArea)
                    — {{ number_format((int) $dominantArea->total_item, 0, ',', '.') }} item.
                  @endif
                </small>
              </div>
            </div>

            <div class="insight-item">
              <span class="insight-bullet"><i class="mdi mdi-clipboard-pulse-outline"></i></span>
              <div>
                <div class="fw-bold">Kondisi paling sering muncul</div>
                <small class="text-muted">
                  {{ $dominantCondition->label ?? '-' }}
                  @if($dominantCondition)
                    — {{ number_format((int) $dominantCondition->total_item, 0, ',', '.') }} item.
                  @endif
                </small>
              </div>
            </div>

            <div class="insight-item">
              <span class="insight-bullet"><i class="mdi mdi-alert-decagram-outline"></i></span>
              <div>
                <div class="fw-bold">Level risiko dominan</div>
                <small class="text-muted">
                  {{ $dominantRisk->label ?? '-' }}
                  @if($dominantRisk)
                    — {{ number_format((int) $dominantRisk->total_item, 0, ',', '.') }} item.
                  @endif
                </small>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-4">
          <div class="management-insight p-3 h-100">
            <h6 class="mb-3 fw-bold">Demografi Area</h6>
            <div class="demography-list">
              @forelse($areaDemographicRows as $areaRow)
                @php $areaPercent = min(100, round(((int) $areaRow->total_item / $maxAreaAnomaly) * 100)); @endphp
                <div>
                  <div class="d-flex justify-content-between gap-2 mb-1">
                    <span class="{{ $areaBadge($areaRow->label) }}">{{ $areaRow->label }}</span>
                    <strong>{{ number_format((int) $areaRow->total_item, 0, ',', '.') }}</strong>
                  </div>
                  <div class="demography-progress"><div class="demography-progress-bar" style="width: {{ $areaPercent }}%"></div></div>
                  <small class="text-muted">Produk terdampak: {{ number_format((int) $areaRow->total_product, 0, ',', '.') }} | High risk: {{ number_format((int) $areaRow->high_risk_item, 0, ',', '.') }}</small>
                </div>
              @empty
                <div class="text-center text-muted py-4">Belum ada data area.</div>
              @endforelse
            </div>
          </div>
        </div>

        <div class="col-xl-4">
          <div class="management-insight p-3 h-100">
            <h6 class="mb-3 fw-bold">Demografi Kondisi</h6>
            <div class="demography-list">
              @forelse($conditionDemographicRows as $conditionRow)
                @php $conditionPercent = min(100, round(((int) $conditionRow->total_item / $maxConditionAnomaly) * 100)); @endphp
                <div>
                  <div class="d-flex justify-content-between gap-2 mb-1">
                    <span class="badge-soft-primary">{{ $conditionRow->label }}</span>
                    <strong>{{ number_format((int) $conditionRow->total_item, 0, ',', '.') }}</strong>
                  </div>
                  <div class="demography-progress"><div class="demography-progress-bar" style="width: {{ $conditionPercent }}%"></div></div>
                  <small class="text-muted">High risk: {{ number_format((int) $conditionRow->high_risk_item, 0, ',', '.') }}</small>
                </div>
              @empty
                <div class="text-center text-muted py-4">Belum ada data kondisi.</div>
              @endforelse
            </div>
          </div>
        </div>

        <div class="col-xl-4">
          <div class="management-insight p-3 h-100">
            <h6 class="mb-3 fw-bold">Jenis Anomali & Risiko</h6>
            <div class="demography-list">
              @forelse($anomalyDemographicRows as $anomalyRow)
                @php $anomalyPercent = min(100, round(((int) $anomalyRow->total_item / $maxAnomalyType) * 100)); @endphp
                <div>
                  <div class="d-flex justify-content-between gap-2 mb-1">
                    <span class="badge-soft-danger">{{ $anomalyRow->label }}</span>
                    <strong>{{ number_format((int) $anomalyRow->total_item, 0, ',', '.') }}</strong>
                  </div>
                  <div class="demography-progress"><div class="demography-progress-bar" style="width: {{ $anomalyPercent }}%"></div></div>
                  <small class="text-muted">Produk terdampak: {{ number_format((int) $anomalyRow->total_product, 0, ',', '.') }}</small>
                </div>
              @empty
                <div class="text-center text-muted py-4">Belum ada data jenis anomali.</div>
              @endforelse

              <hr class="my-2">

              @foreach($riskDemographicRows as $riskRow)
                @php $riskPercent = min(100, round(((int) $riskRow->total_item / $maxRisk) * 100)); @endphp
                <div>
                  <div class="d-flex justify-content-between gap-2 mb-1">
                    <span class="badge-soft-warning">{{ $riskRow->label }}</span>
                    <strong>{{ number_format((int) $riskRow->total_item, 0, ',', '.') }}</strong>
                  </div>
                  <div class="demography-progress"><div class="demography-progress-bar" style="width: {{ $riskPercent }}%"></div></div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h5 class="mb-1 fw-bold">Filter Anomali Item</h5>
        <small class="text-muted">Cari berdasarkan produk, RFID, area Gudang WMS/Store, lokasi, kondisi, dan periode histori.</small>
      </div>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#advancedFilterAnomaliItem">
        <i class="mdi mdi-filter-variant me-1"></i> Filter
      </button>
    </div>

    <div class="card-body pt-0">
      <div class="collapse {{ $hasFilter ? 'show' : '' }}" id="advancedFilterAnomaliItem">
        <div class="filter-box">
          <form method="GET" action="{{ route('monitoring.anomali-item.index') }}" class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Cari Produk / Kode / RFID / Area</label>
              <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Contoh: nama produk / RFID / gudang / store">
            </div>
            <div class="col-md-2">
              <label class="form-label">Dari</label>
              <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label">Sampai</label>
              <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label">Min. Pindah</label>
              <input type="number" min="1" name="min_move_count" value="{{ $minMoveCount }}" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label">Min. Rusak</label>
              <input type="number" min="1" name="min_damage_count" value="{{ $minDamageCount }}" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Lokasi Saat Ini</label>
              <select name="location_id" class="form-select">
                <option value="">Semua Lokasi</option>
                @foreach($locations as $location)
                  <option value="{{ $location->id }}" @selected((string) $locationId === (string) $location->id)>
                    {{ $location->name }}{{ $location->code ? ' - '.$location->code : '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Kondisi Saat Ini</label>
              <select name="condition_id" class="form-select">
                <option value="">Semua Kondisi</option>
                @foreach($conditions as $condition)
                  <option value="{{ $condition->id }}" @selected((string) $conditionId === (string) $condition->id)>{{ $condition->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Jenis Anomali</label>
              <select name="anomaly_type" class="form-select">
                <option value="">Semua</option>
                <option value="high_risk" @selected($anomalyType === 'high_risk')>Semua Anomali / Risiko</option>
                <option value="frequent_move" @selected($anomalyType === 'frequent_move')>Sering Pindah Lokasi</option>
                <option value="location_mismatch" @selected($anomalyType === 'location_mismatch')>Salah Lokasi / Tidak Sinkron</option>
                <option value="frequent_damage" @selected($anomalyType === 'frequent_damage')>Sering Rusak</option>
                <option value="repaired_then_damaged" @selected($anomalyType === 'repaired_then_damaged')>Diperbaiki Lalu Rusak Lagi</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Per Page</label>
              <select name="per_page" class="form-select">
                @foreach([10, 25, 50, 100, 200] as $size)
                  <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-primary"><i class="mdi mdi-magnify me-1"></i> Tampilkan</button>
              <a href="{{ route('monitoring.anomali-item.index') }}" class="btn btn-outline-secondary"><i class="mdi mdi-refresh me-1"></i> Reset</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div>
        <h5 class="mb-1 fw-bold">Daftar Anomali Item</h5>
        <small class="text-muted">Area lokasi dibaca dari relasi <b>warehouse_locations → warehouses</b> dan <b>store_locations → stores</b>.</small>
      </div>
      <div class="pagination-info" id="anomalyTotalText">Total: {{ number_format($rows->total(), 0, ',', '.') }} data</div>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Produk</th>
              <th>Status</th>
              <th>Area Saat Ini</th>
              <th>Area History Terakhir</th>
              <th>Kondisi</th>
              <th>Pindah</th>
              <th>Rusak</th>
              <th>Anomali</th>
              <th>Risiko</th>
              <th>Kesimpulan</th>
              <th>Detail</th>
            </tr>
          </thead>
          <tbody id="anomalyTableBody">
            @forelse($rows as $row)
              @php
                $riskScore = (int) ($row->risk_score ?? 1);
                $riskClass = $riskScore >= 5 ? 'risk-5' : ($riskScore === 4 ? 'risk-4' : ($riskScore >= 3 ? 'risk-3' : 'risk-1'));

                $currentAreaLabel = $row->current_area_label ?? 'Lokasi Lain';
                $latestAreaLabel = $row->latest_area_label ?? '-';
                $currentAreaName = $row->current_area_name ?? '-';
                $latestAreaName = $row->latest_area_name ?? '-';

                $conclusion = $getConclusion($row);
              @endphp
              <tr>
                <td class="product-col">
                  <div class="fw-bold text-dark">{{ $row->product_name ?? '-' }}</div>
                  <small class="text-muted">Kode: {{ $row->product_code ?? '-' }}</small><br>
                  <small class="text-muted">RFID: {{ $row->rfid_tag_id ?? '-' }}</small>
                </td>
                <td><span class="badge-soft-secondary">{{ $row->status ?? '-' }}</span></td>
                <td class="area-col">
                  <div class="area-box">
                    <span class="{{ $areaBadge($currentAreaLabel) }}">{{ $currentAreaLabel }}</span>
                    <div class="fw-bold mt-2">{{ $currentAreaName }}</div>
                    <small class="text-muted">Lokasi: {{ $row->current_location_name ?? '-' }} {{ $row->current_location_code ? '('.$row->current_location_code.')' : '' }}</small>
                  </div>
                </td>
                <td class="area-col">
                  <div class="area-box">
                    <span class="{{ $areaBadge($latestAreaLabel) }}">{{ $latestAreaLabel }}</span>
                    <div class="fw-bold mt-2">{{ $latestAreaName }}</div>
                    <small class="text-muted">Lokasi: {{ $row->latest_history_location_name ?? '-' }} {{ $row->latest_history_location_code ? '('.$row->latest_history_location_code.')' : '' }}</small>
                  </div>
                </td>
                <td><span class="badge-soft-primary">{{ $row->current_condition_name ?? '-' }}</span></td>
                <td>
                  <div class="fw-bold">{{ number_format((int) $row->location_move_count, 0, ',', '.') }}x</div>
                  <small class="text-muted">{{ number_format((int) $row->distinct_location_count, 0, ',', '.') }} lokasi</small>
                </td>
                <td>
                  <div class="fw-bold text-danger">{{ number_format((int) $row->damage_condition_count, 0, ',', '.') }}x</div>
                  <small class="text-muted">Rusak lagi: {{ number_format((int) $row->repaired_then_damaged_count, 0, ',', '.') }}x</small>
                </td>
                <td>
                  @if(($row->anomaly_label ?? 'Normal') === 'Normal')
                    <span class="badge-soft-success">Normal</span>
                  @else
                    <span class="badge-soft-danger">{{ $row->anomaly_label }}</span>
                  @endif
                </td>
                <td>
                  <span class="{{ $riskBadge($riskScore) }}"><span class="risk-dot {{ $riskClass }} me-1"></span>{{ $riskLabel($riskScore) }}</span>
                </td>
                <td class="note-col">
                  <div class="conclusion-title {{ $conclusion['class'] }}">{{ $conclusion['title'] }}</div>
                  <small class="text-muted d-block">{{ $conclusion['description'] }}</small>
                  <small class="text-muted d-block mt-1">History lokasi: {{ $formatDateTime($row->last_location_move_at) }}</small>
                  <small class="text-muted d-block">History kondisi: {{ $formatDateTime($row->last_condition_changed_at) }}</small>
                </td>
                <td class="action-col">
                  <button type="button"
                          class="btn btn-sm btn-outline-primary btn-history js-open-history"
                          data-url="{{ route('monitoring.anomali-item.location-history-json', $row->id) }}">
                    <i class="mdi mdi-map-marker-path me-1"></i> History
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center py-5">
                  <div class="text-muted"><i class="mdi mdi-database-search-outline mdi-36px d-block mb-2"></i>Belum ada data anomali item.</div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3" id="paginationBlock">
        <div class="pagination-info">Menampilkan {{ $rows->firstItem() ?? 0 }} - {{ $rows->lastItem() ?? 0 }} dari {{ number_format($rows->total(), 0, ',', '.') }} data</div>
        <div>{{ $rows->links() }}</div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade history-modal" id="historyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title text-white mb-1">History Item</h5>
          <small class="text-white-50">Alur lokasi Gudang WMS / Store dan perubahan kondisi.</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light">
        <div id="historyLoading" class="text-center py-5 d-none">
          <div class="spinner-border text-primary mb-3" role="status"></div>
          <div class="text-muted">Memuat history item...</div>
        </div>

        <div id="historyContent" class="d-none">
          <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="history-card-small"><small class="text-muted">Produk</small><div class="fw-bold" id="modalProductName">-</div><small id="modalProductCode">-</small></div></div>
            <div class="col-md-3"><div class="history-card-small"><small class="text-muted">RFID</small><div class="fw-bold" id="modalRfid">-</div></div></div>
            <div class="col-md-3"><div class="history-card-small"><small class="text-muted">Area Saat Ini</small><div class="fw-bold" id="modalCurrentArea">-</div><small id="modalCurrentLocation">-</small></div></div>
            <div class="col-md-3"><div class="history-card-small"><small class="text-muted">Kondisi Saat Ini</small><div class="fw-bold" id="modalCurrentCondition">-</div></div></div>
          </div>

          <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabLocationHistory" type="button">History Lokasi</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabConditionHistory" type="button">History Kondisi</button></li>
          </ul>

          <div class="tab-content bg-white rounded-4 p-3 border">
            <div class="tab-pane fade show active" id="tabLocationHistory">
              <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge-soft-wms" id="modalTotalWms">Gudang WMS: 0</span>
                <span class="badge-soft-store" id="modalTotalStore">Store: 0</span>
                <span class="badge-soft-secondary" id="modalTotalLocation">Total lokasi: 0</span>
              </div>
              <div id="locationTimeline" class="timeline"></div>
            </div>
            <div class="tab-pane fade" id="tabConditionHistory">
              <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge-soft-secondary" id="modalTotalCondition">Total kondisi: 0</span>
              </div>
              <div id="conditionTimeline" class="timeline"></div>
            </div>
          </div>
        </div>

        <div id="historyError" class="alert alert-danger d-none mb-0"></div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('historyModal');
    const modal = new bootstrap.Modal(modalEl);
    const loading = document.getElementById('historyLoading');
    const content = document.getElementById('historyContent');
    const error = document.getElementById('historyError');

    function escapeHtml(value) {
      if (value === null || value === undefined) return '-';
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function areaBadgeClass(label) {
      if (label === 'Gudang WMS') return 'badge-soft-wms';
      if (label === 'Store') return 'badge-soft-store';
      return 'badge-soft-secondary';
    }

    function movementLabel(previous, current) {
      if (!previous) return 'Lokasi awal';
      const prev = previous.area_label || 'Lokasi Lain';
      const curr = current.area_label || 'Lokasi Lain';
      if (prev === 'Gudang WMS' && curr === 'Store') return 'Gudang WMS ke Store';
      if (prev === 'Store' && curr === 'Gudang WMS') return 'Store ke Gudang WMS';
      if (prev === 'Store' && curr === 'Store') return 'Perubahan lokasi di area Store';
      if (prev === 'Gudang WMS' && curr === 'Gudang WMS') return 'Perubahan lokasi di area Gudang WMS';
      if (prev === curr) return 'Perubahan lokasi sejenis';
      return 'Perubahan lokasi';
    }

    function renderLocationTimeline(histories) {
      const target = document.getElementById('locationTimeline');
      if (!histories || !histories.length) {
        target.innerHTML = '<div class="text-center text-muted py-4">Belum ada history lokasi.</div>';
        return;
      }

      target.innerHTML = histories.map((row, index) => {
        const previous = index > 0 ? histories[index - 1] : null;
        const movement = movementLabel(previous, row);
        return `
          <div class="timeline-item">
            <span class="timeline-dot"></span>
            ${index > 0 ? `<div class="movement-label"><i class="mdi mdi-arrow-down"></i>${escapeHtml(movement)}</div>` : ''}
            <div class="timeline-box">
              <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                <div>
                  <span class="${areaBadgeClass(row.area_label)}">${escapeHtml(row.area_label)}</span>
                  <div class="fw-bold mt-2">${escapeHtml(row.area_name)}</div>
                  <small class="text-muted">Lokasi: ${escapeHtml(row.location_name)} ${row.location_code ? '(' + escapeHtml(row.location_code) + ')' : ''}</small>
                </div>
                <div class="text-md-end"><small class="text-muted">${escapeHtml(row.created_at_label)}</small></div>
              </div>
              ${row.note ? `<div class="mt-2 text-muted"><i class="mdi mdi-note-text-outline me-1"></i>${escapeHtml(row.note)}</div>` : ''}
            </div>
          </div>
        `;
      }).join('');
    }

    function renderConditionTimeline(histories) {
      const target = document.getElementById('conditionTimeline');
      if (!histories || !histories.length) {
        target.innerHTML = '<div class="text-center text-muted py-4">Belum ada history kondisi.</div>';
        return;
      }

      target.innerHTML = histories.map((row) => `
        <div class="timeline-item">
          <span class="timeline-dot"></span>
          <div class="timeline-box">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
              <div>
                <span class="badge-soft-primary">${escapeHtml(row.condition_name)}</span>
                <div class="mt-2 text-muted">Diubah oleh: ${escapeHtml(row.changed_by_name || row.changed_by_email || '-')}</div>
              </div>
              <div class="text-md-end"><small class="text-muted">${escapeHtml(row.date_label)}</small></div>
            </div>
          </div>
        </div>
      `).join('');
    }

    async function openHistory(button) {
      const url = button.dataset.url;
      loading.classList.remove('d-none');
      content.classList.add('d-none');
      error.classList.add('d-none');
      error.textContent = '';
      modal.show();

      try {
        const response = await fetch(url, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!response.ok) throw new Error('Gagal memuat history item.');

        const data = await response.json();
        const item = data.item || {};
        const summary = data.summary || {};

        document.getElementById('modalProductName').textContent = item.product_name || '-';
        document.getElementById('modalProductCode').textContent = item.product_code ? 'Kode: ' + item.product_code : 'Kode: -';
        document.getElementById('modalRfid').textContent = item.rfid_tag_id || '-';
        document.getElementById('modalCurrentArea').textContent = (item.current_area_label || '-') + ' - ' + (item.current_area_name || '-');
        document.getElementById('modalCurrentLocation').textContent = 'Lokasi: ' + (item.current_location_name || '-') + (item.current_location_code ? ' (' + item.current_location_code + ')' : '');
        document.getElementById('modalCurrentCondition').textContent = item.current_condition_name || '-';

        document.getElementById('modalTotalWms').textContent = 'Gudang WMS: ' + (summary.total_wms_history || 0);
        document.getElementById('modalTotalStore').textContent = 'Store: ' + (summary.total_store_history || 0);
        document.getElementById('modalTotalLocation').textContent = 'Total lokasi: ' + (summary.total_location_history || 0);
        document.getElementById('modalTotalCondition').textContent = 'Total kondisi: ' + (summary.total_condition_history || 0);

        renderLocationTimeline(data.location_histories || []);
        renderConditionTimeline(data.condition_histories || []);

        loading.classList.add('d-none');
        content.classList.remove('d-none');
      } catch (e) {
        loading.classList.add('d-none');
        error.classList.remove('d-none');
        error.textContent = e.message || 'Terjadi kesalahan saat memuat data.';
      }
    }

    document.addEventListener('click', function (event) {
      const button = event.target.closest('.js-open-history');
      if (!button) return;
      event.preventDefault();
      openHistory(button);
    });

    async function refreshAnomalyTable() {
      if (document.hidden) return;
      if (modalEl.classList.contains('show')) return;

      try {
        const response = await fetch(window.location.href, {
          headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
          },
          cache: 'no-store'
        });

        if (!response.ok) return;

        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        const freshBody = doc.getElementById('anomalyTableBody');
        const currentBody = document.getElementById('anomalyTableBody');
        if (freshBody && currentBody) currentBody.innerHTML = freshBody.innerHTML;

        const freshTotal = doc.getElementById('anomalyTotalText');
        const currentTotal = document.getElementById('anomalyTotalText');
        if (freshTotal && currentTotal) currentTotal.innerHTML = freshTotal.innerHTML;

        const freshSummary = doc.getElementById('summaryCards');
        const currentSummary = document.getElementById('summaryCards');
        if (freshSummary && currentSummary) currentSummary.innerHTML = freshSummary.innerHTML;

        const freshPagination = doc.getElementById('paginationBlock');
        const currentPagination = document.getElementById('paginationBlock');
        if (freshPagination && currentPagination) currentPagination.innerHTML = freshPagination.innerHTML;

        const freshDemographic = doc.getElementById('anomalyDemographicBlock');
        const currentDemographic = document.getElementById('anomalyDemographicBlock');
        if (freshDemographic && currentDemographic) currentDemographic.innerHTML = freshDemographic.innerHTML;

        const lastUpdateText = document.getElementById('lastUpdateText');
        if (lastUpdateText) {
          lastUpdateText.textContent = new Date().toLocaleString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
          }).replace(/\./g, ':');
        }
      } catch (e) {
        // Realtime polling gagal tidak perlu mengganggu user.
      }
    }

    setInterval(refreshAnomalyTable, 15000);
  });
</script>
@endpush
