@extends('layouts/contentNavbarLayout')
@section('title', 'Status Export')
@section('content')
<div class="card my-4"><div class="card-body">
  <h4>Export laporan</h4>
  <p>{{ $export->filename }}</p>
  @if($export->status === 'completed')
    <div class="alert alert-success">Laporan siap diunduh.</div>
    <a class="btn btn-primary" href="{{ route('report-exports.download', $export->id) }}">Unduh Excel</a>
  @elseif($export->status === 'failed')
    <div class="alert alert-danger">{{ $export->error }}</div>
  @else
    <div class="alert alert-info">{{ $export->status === 'processing' ? 'Laporan sedang disiapkan.' : 'Permintaan export masuk antrean.' }} Anda dapat melanjutkan pekerjaan dan kembali ke halaman ini.</div>
  @endif
  <a class="btn btn-outline-secondary" href="{{ url()->current() }}">Periksa status</a>
  <a class="btn btn-outline-secondary" href="{{ route('report-exports.index') }}">Export Saya</a>
  <p class="text-muted mt-3 mb-0">Rentang maksimal {{ config('report_exports.max_days') }} hari, hingga {{ number_format(config('report_exports.max_rows')) }} baris per laporan.</p>
</div></div>
@endsection
