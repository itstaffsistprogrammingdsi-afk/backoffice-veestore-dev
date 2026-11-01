@extends('layouts/contentNavbarLayout')

@section('title', 'Monitoring - Customer Membership')

@section('content')
@php
  $summary = $summary ?? (object) [
    'total_customer' => 0,
    'total_active' => 0,
    'total_unassigned' => 0,
    'expiring_soon' => 0,
  ];

  $statusLabel = fn ($status) => match ($status) {
    'active' => 'Aktif',
    'inactive' => 'Tidak Aktif',
    'expired' => 'Berakhir',
    'unassigned' => 'Belum Memiliki Tier',
    default => ucfirst(str_replace('_', ' ', (string) $status)),
  };

  $statusClass = fn ($status) => match ($status) {
    'active' => 'bg-label-success',
    'inactive', 'expired' => 'bg-label-danger',
    'unassigned' => 'bg-label-secondary',
    default => 'bg-label-warning',
  };
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
      <h4 class="mb-1"><span class="text-muted fw-light">Monitoring / Customer & Promo /</span> Customer Membership</h4>
      <p class="text-muted mb-0">Pantau tier aktif, total belanja 12 bulan, masa berlaku, dan perubahan tier setiap customer.</p>
    </div>
    <span class="badge bg-label-primary px-3 py-2"><i class="mdi mdi-account-star-outline me-1"></i>{{ number_format((int) $summary->total_customer, 0, ',', '.') }} customer</span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card membership-stat"><div class="card-body"><small class="text-muted">Customer Terdaftar</small><h4 class="mb-0">{{ number_format((int) $summary->total_customer, 0, ',', '.') }}</h4></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card membership-stat stat-success"><div class="card-body"><small class="text-muted">Tier Aktif</small><h4 class="mb-0">{{ number_format((int) $summary->total_active, 0, ',', '.') }}</h4></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card membership-stat stat-muted"><div class="card-body"><small class="text-muted">Belum Memiliki Tier</small><h4 class="mb-0">{{ number_format((int) $summary->total_unassigned, 0, ',', '.') }}</h4></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card membership-stat stat-warning"><div class="card-body"><small class="text-muted">Berakhir ≤ 30 Hari</small><h4 class="mb-0">{{ number_format((int) $summary->expiring_soon, 0, ',', '.') }}</h4></div></div></div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-1">Filter Customer Membership</h5><small class="text-muted">Filter data berdasarkan tier, status tier, masa berlaku, atau data customer.</small></div>
    <div class="card-body">
      <form method="GET" action="{{ route('monitoring.customer-membership.index') }}" class="row g-3 align-items-end">
        <div class="col-sm-6 col-lg-3"><label class="form-label">Tier Membership</label><select class="form-select" name="membership_id"><option value="">Semua Tier</option>@foreach($memberships as $membership)<option value="{{ $membership->id }}" @selected(($filters['membership_id'] ?? null) === $membership->id)>{{ $membership->name }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">Semua Status</option><option value="unassigned" @selected(($filters['status'] ?? null) === 'unassigned')>Belum Memiliki Tier</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ $statusLabel($status) }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Berlaku dari</label><input class="form-control" type="date" name="valid_from" value="{{ $filters['valid_from'] ?? '' }}"></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Berlaku sampai</label><input class="form-control" type="date" name="valid_to" value="{{ $filters['valid_to'] ?? '' }}"></div>
        <div class="col-sm-8 col-lg-3"><label class="form-label">Cari Customer</label><input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama, WhatsApp, email, atau tier"></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="mdi mdi-magnify me-1"></i>Tampilkan</button><a class="btn btn-outline-secondary" href="{{ route('monitoring.customer-membership.index') }}"><i class="mdi mdi-refresh me-1"></i>Reset</a></div>
      </form>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Komposisi Tier</h5></div>
    <div class="card-body d-flex flex-wrap gap-2">
      @forelse($tierDistribution as $tier)
        <span class="tier-pill"><span>{{ $tier->membership_name }}</span><strong>{{ number_format((int) $tier->customer_count, 0, ',', '.') }}</strong></span>
      @empty
        <span class="text-muted">Belum ada data tier pada filter ini.</span>
      @endforelse
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div><h5 class="mb-1">Daftar Tier Customer</h5><small class="text-muted">{{ number_format($rows->total(), 0, ',', '.') }} customer pada filter aktif.</small></div>
      @if($rows->count())<small class="text-muted">Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }}</small>@endif
    </div>
    <div class="table-responsive membership-table-scroll">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>No</th><th>Customer</th><th>Tier</th><th>Status</th><th class="text-end">Belanja 12 Bulan</th><th class="text-end">Multiplier</th><th>Tier Sebelumnya</th><th>Evaluasi Tier</th><th>Berlaku Sampai</th><th class="text-center">Riwayat Tier</th></tr></thead>
        <tbody>
          @forelse($rows as $row)
            @php $isExpiringSoon = $row->tier_valid_until && \Carbon\Carbon::parse($row->tier_valid_until)->between(now()->startOfDay(), now()->addDays(30)->endOfDay()); @endphp
            <tr>
              <td>{{ $rows->firstItem() + $loop->index }}</td>
              <td><div class="fw-semibold">{{ $row->customer_name }}</div><small class="text-muted">{{ $row->customer_wa_number ?: ($row->customer_email ?: '-') }}</small></td>
              <td><span class="badge bg-label-primary">{{ $row->membership_name ?: 'Belum memiliki tier' }}</span></td>
              <td><span class="badge {{ $statusClass($row->membership_status) }}">{{ $statusLabel($row->membership_status) }}</span></td>
              <td class="text-end">Rp {{ number_format((float) ($row->total_spend_12_month ?? 0), 0, ',', '.') }}</td>
              <td class="text-end">{{ $row->earning_multiplier ? number_format((float) $row->earning_multiplier, 2, ',', '.') . 'x' : '-' }}</td>
              <td>{{ $row->previous_membership_name ?: '-' }}</td>
              <td>{{ $row->tier_evaluated_at ? \Carbon\Carbon::parse($row->tier_evaluated_at)->format('d M Y H:i') : '-' }}</td>
              <td><span class="{{ $isExpiringSoon ? 'text-warning fw-semibold' : '' }}">{{ $row->tier_valid_until ? \Carbon\Carbon::parse($row->tier_valid_until)->format('d M Y') : '-' }}</span></td>
              <td class="text-center"><span class="badge bg-label-info">{{ number_format((int) ($row->tier_change_count ?? 0), 0, ',', '.') }}</span><small class="d-block text-muted">{{ $row->last_tier_change_at ? \Carbon\Carbon::parse($row->last_tier_change_at)->format('d M Y') : '-' }}</small></td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center text-muted py-5"><i class="mdi mdi-account-search-outline d-block fs-1 mb-2"></i>Tidak ada data customer membership pada filter ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($rows->hasPages())<div class="card-body border-top">{{ $rows->onEachSide(1)->links() }}</div>@endif
  </div>
</div>
@endsection

@push('page-style')
<style>
  .membership-stat { border: 0; border-left: 4px solid #696cff; }
  .membership-stat h4 { margin-top: .35rem; }
  .stat-success { border-left-color: #71dd37; }
  .stat-muted { border-left-color: #8592a3; }
  .stat-warning { border-left-color: #ffab00; }
  .tier-pill { display: inline-flex; align-items: center; gap: .75rem; padding: .55rem .8rem; border: 1px solid #e7e7ff; border-radius: .75rem; background: #f8f7ff; color: #566a7f; }
  .tier-pill strong { color: #696cff; }
  .membership-table-scroll { max-height: 65vh; overflow: auto; }
  .membership-table-scroll thead th { position: sticky; top: 0; z-index: 2; white-space: nowrap; background: #f7f7ff; }
  .membership-table-scroll td { white-space: nowrap; }
  .membership-table-scroll td:nth-child(2) { min-width: 220px; white-space: normal; }
</style>
@endpush
