@extends('layouts/contentNavbarLayout')
@section('title', 'Monitoring Command Center')
@push('page-style')
  @include('content.monitoring._monitoring-styles')
  <style>
    .monitoring-directory-card {
      border: 0;
      border-radius: 1.25rem;
      overflow: hidden;
      background: rgba(255, 255, 255, .94);
      box-shadow: 0 .9rem 2.25rem rgba(43, 30, 83, .10);
    }

    .monitoring-directory-card .card-header {
      padding: 1.25rem 1.5rem;
      background: linear-gradient(110deg, #f8f6ff 0%, #fff 62%, #f2efff 100%);
      border-bottom: 1px solid #ece8fa;
    }

    .monitoring-directory-title-icon,
    .monitoring-directory-icon {
      width: 2.75rem;
      height: 2.75rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: .9rem;
      color: #fff;
      background: linear-gradient(135deg, #6338d6, #a342d5);
      box-shadow: 0 .45rem .9rem rgba(100, 56, 214, .24);
      font-size: 1.35rem;
    }

    .monitoring-directory-link {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .75rem 0;
      color: #30303d;
      text-decoration: none;
      border-bottom: 1px solid #f0f0f5;
      transition: color .15s ease, padding-left .15s ease;
    }

    .monitoring-directory-link:last-child { border-bottom: 0; }
    .monitoring-directory-link:hover { color: #5d3fd3; padding-left: .35rem; }

    .monitoring-category-link,
    .monitoring-feature-link {
      position: relative;
      display: block;
      height: 100%;
      overflow: hidden;
      color: #242133;
      text-decoration: none;
      border: 1px solid var(--category-border, #e9e7f5);
      border-radius: 1.1rem;
      background: linear-gradient(145deg, #fff 15%, var(--category-soft, #fbfaff));
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .monitoring-category-link { min-height: 11.25rem; padding: 1.25rem; }
    .monitoring-feature-link { min-height: 5.75rem; padding: 1rem; }

    .monitoring-category-link:hover,
    .monitoring-feature-link:hover {
      color: var(--category-color, #6338d6);
      border-color: var(--category-color, #bcb0f9);
      box-shadow: 0 .8rem 1.65rem color-mix(in srgb, var(--category-color, #6338d6) 18%, transparent);
      transform: translateY(-2px);
    }

    .monitoring-category-link::after {
      position: absolute;
      right: -2rem;
      bottom: -2.75rem;
      width: 7.5rem;
      height: 7.5rem;
      content: '';
      pointer-events: none;
      border-radius: 50%;
      background: color-mix(in srgb, var(--category-color, #6338d6) 10%, transparent);
    }

    .monitoring-category-icon {
      width: 3rem;
      height: 3rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: .9rem;
      color: var(--category-color, #6338d6);
      background: var(--category-icon-bg, #eeeaff);
      font-size: 1.35rem;
    }

    .monitoring-category-count {
      color: var(--category-color, #6338d6);
      background: var(--category-icon-bg, #eeeaff);
    }

    .monitoring-category-cta {
      color: var(--category-color, #6338d6);
      font-size: .78rem;
      font-weight: 700;
      letter-spacing: .02em;
      text-transform: uppercase;
    }

    .monitoring-category--monitoring-sales-payment { --category-color: #15803d; --category-soft: #f1fcf4; --category-border: #d5f2dc; --category-icon-bg: #ddf7e5; }
    .monitoring-category--monitoring-orders { --category-color: #2563eb; --category-soft: #f2f7ff; --category-border: #d8e6ff; --category-icon-bg: #e3eeff; }
    .monitoring-category--monitoring-inventory { --category-color: #7c3aed; --category-soft: #f7f3ff; --category-border: #e7ddff; --category-icon-bg: #eee7ff; }
    .monitoring-category--monitoring-customer-marketing { --category-color: #db2777; --category-soft: #fff4fa; --category-border: #ffdcea; --category-icon-bg: #ffe5f1; }
    .monitoring-category--monitoring-audit-data { --category-color: #b45309; --category-soft: #fff9ef; --category-border: #f8e3bd; --category-icon-bg: #ffefcf; }
    .monitoring-category--monitoring-supplier { --category-color: #0f766e; --category-soft: #effcfb; --category-border: #d2f2ef; --category-icon-bg: #daf5f1; }

    @media (max-width: 575.98px) {
      .monitoring-directory-card .card-header { padding: 1rem; }
      .monitoring-category-link { min-height: auto; }
    }
  </style>
@endpush
@section('content')
<div class="monitor-shell">
  <div class="monitor-hero mb-4">
    <div><div class="small text-uppercase fw-bold opacity-75 mb-1">Pusat Monitoring</div><h4 class="mb-2">Monitoring</h4><p class="mb-0">Pilih kategori untuk membuka laporan dan kontrol operasional yang dibutuhkan.</p></div>
  </div>
  @php
    $selectedMonitoringGroup = collect($monitoringGroups)->firstWhere('slug', request('category'));
    $monitoringMenuCount = collect($monitoringGroups)->sum(fn ($group) => count($group['items']));
  @endphp
  <div class="card monitoring-directory-card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
      <div class="d-flex align-items-center gap-3">
        <span class="monitoring-directory-title-icon"><i class="mdi {{ $selectedMonitoringGroup ? $selectedMonitoringGroup['icon'] : 'mdi-view-grid-outline' }}"></i></span>
        <div>
          <div class="small fw-bold text-uppercase text-primary mb-1">Pusat Monitoring</div>
          <h5 class="mb-1">{{ $selectedMonitoringGroup ? $selectedMonitoringGroup['name'] : 'Direktori Menu Monitoring' }}</h5>
          <small class="text-muted">
            {{ $selectedMonitoringGroup ? 'Pilih menu monitoring yang ingin dibuka.' : 'Pilih kategori terlebih dahulu untuk melihat menu di dalamnya.' }}
          </small>
        </div>
      </div>
      @if($selectedMonitoringGroup)
        <a href="{{ route('monitoring.command-center.index') }}" class="btn btn-sm btn-outline-primary">
          <i class="mdi mdi-arrow-left me-1"></i> Semua kategori
        </a>
      @else
        <span class="badge bg-label-primary rounded-pill px-3 py-2">{{ $monitoringMenuCount }} menu</span>
      @endif
    </div>
    <div class="card-body">
      @if($selectedMonitoringGroup)
        <div class="row g-3">
          @foreach($selectedMonitoringGroup['items'] as $menuItem)
            <div class="col-12 col-md-6 col-xl-4">
              <a href="{{ url($menuItem['url']) }}" class="monitoring-feature-link monitoring-category--{{ $selectedMonitoringGroup['slug'] }} d-flex align-items-center gap-3">
                <span class="monitoring-category-icon flex-shrink-0"><i class="mdi {{ $selectedMonitoringGroup['icon'] }}"></i></span>
                <span class="flex-grow-1 fw-semibold">{{ $menuItem['name'] }}</span>
                <i class="mdi mdi-arrow-right text-muted"></i>
              </a>
            </div>
          @endforeach
        </div>
      @else
        <div class="row g-3">
          @forelse($monitoringGroups as $group)
            <div class="col-12 col-md-6 col-xl-4">
              <a href="{{ route('monitoring.command-center.index', ['category' => $group['slug']]) }}" class="monitoring-category-link">
                <div class="d-flex align-items-start gap-3">
                  <span class="monitoring-category-icon flex-shrink-0"><i class="mdi {{ $group['icon'] }}"></i></span>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between gap-2">
                      <h6 class="mb-1">{{ $group['name'] }}</h6>
                      <i class="mdi mdi-arrow-top-right fs-5"></i>
                    </div>
                    <p class="small text-muted mb-3">{{ $group['description'] }}</p>
                    <div class="d-flex align-items-center justify-content-between gap-2">
                      <span class="badge monitoring-category-count rounded-pill px-2 py-1">{{ count($group['items']) }} menu</span>
                      <span class="monitoring-category-cta">Buka kategori <i class="mdi mdi-arrow-right"></i></span>
                    </div>
                  </div>
                </div>
              </a>
            </div>
          @empty
            <div class="col-12 text-center text-muted py-4">Menu Monitoring belum tersedia.</div>
          @endforelse
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
