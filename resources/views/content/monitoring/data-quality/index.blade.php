@extends('layouts/contentNavbarLayout')
@section('title', 'Monitoring Kualitas Data')
@push('page-style') @include('content.monitoring._monitoring-styles') @endpush
@section('content')
<div class="monitor-shell">
  <div class="monitor-hero mb-4"><h4 class="mb-2">Data Quality Monitor</h4><p class="mb-0">Temukan data master dan transaksi yang belum lengkap agar peta, laporan, analisis, dan proses operasional tetap akurat.</p></div>
  <div class="row g-3">
    @foreach($checks as $check)
      @php($percent = $check['total'] > 0 ? round(($check['count'] / $check['total']) * 100, 1) : 0)
      <div class="col-md-6 col-xl-4"><div class="card monitor-card h-100"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3"><div class="monitor-icon"><i class="mdi mdi-database-alert-outline"></i></div><span class="status-pill {{ $check['severity'] }}">{{ $percent }}%</span></div>
        <h6 class="mb-1">{{ $check['title'] }}</h6><div class="display-6 fw-bold text-dark">{{ number_format($check['count'],0,',','.') }}</div><small class="text-muted">dari {{ number_format($check['total'],0,',','.') }} data diperiksa</small>
        <div class="progress mt-3" style="height:7px"><div class="progress-bar bg-{{ $check['severity']==='danger'?'danger':($check['severity']==='warning'?'warning':'info') }}" style="width:{{ min(100,$percent) }}%"></div></div>
        <a href="{{ $check['url'] }}" class="btn btn-sm btn-outline-primary w-100 mt-3">Buka data terkait</a>
      </div></div></div>
    @endforeach
  </div>
  <div class="alert alert-info mt-4 mb-0"><i class="mdi mdi-information-outline me-1"></i><strong>Cara membaca:</strong> angka menunjukkan data yang belum lengkap. Prioritaskan indikator merah, kemudian kuning. Setelah data diperbaiki, nilai akan berubah otomatis.</div>
</div>
@endsection
