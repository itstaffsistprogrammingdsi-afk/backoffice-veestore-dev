@extends('layouts/contentNavbarLayout')

@section('title', 'Setting App - Kurs Point')

@section('content')
@php
  $inIdrBase = (int) old(
      'in_idr_base',
      data_get($data, 'in.idr_base', data_get($data, 'idr_base', 1000))
  );

  $inPointsPerBase = (float) old(
      'in_points_per_base',
      data_get(
          $data,
          'in.points_per_base',
          data_get($data, 'points_per_base', 1)
      )
  );

  $inPointsPerIdr = $inIdrBase > 0
      ? $inPointsPerBase / $inIdrBase
      : 0;

  $outPointsBase = (float) old(
      'out_points_base',
      data_get($data, 'out.points_base', 1)
  );

  $outIdrPerBase = (float) old(
      'out_idr_per_base',
      data_get($data, 'out.idr_per_base', 1000)
  );

  $outIdrPerPoint = $outPointsBase > 0
      ? $outIdrPerBase / $outPointsBase
      : 0;

  $sampleIdr = 100000;
  $samplePointIn = $sampleIdr * $inPointsPerIdr;

  $samplePointOut = 100;
  $sampleRedeemValue = $samplePointOut * $outIdrPerPoint;

  $updatedBy = data_get($data, 'updated_by');
  $updatedAt = data_get($data, 'updated_at');
@endphp

<div class="container-xxl py-3">
  <h4 class="mb-3">
    <span class="text-muted fw-light">Setting App /</span> Kurs Point
  </h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <i class="mdi mdi-check-circle-outline me-1"></i>
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i>
      {{ $errors->first() }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form method="POST" action="{{ route('settings.points.update') }}">
    @csrf

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-1">Kurs: Rupiah ⇄ Point In</h5>
            <small class="text-muted">
              Digunakan untuk menghitung point masuk / earning point dari transaksi.
            </small>
          </div>

          <div class="card-body">
            <label class="form-label">Kurs dasar point masuk</label>

            <div class="input-group">
              <span class="input-group-text">Rp</span>

              <input
                type="number"
                min="1"
                step="1"
                name="in_idr_base"
                value="{{ old('in_idr_base', $inIdrBase) }}"
                class="form-control"
                required
              >

              <span class="input-group-text">=</span>

              <input
                type="number"
                step="0.000001"
                min="0.000001"
                name="in_points_per_base"
                value="{{ old('in_points_per_base', $inPointsPerBase) }}"
                class="form-control"
                required
              >

              <span class="input-group-text">point</span>
            </div>

            <div class="form-text mt-2">
              Contoh: <code>Rp1.000 = 1 point</code>, artinya <code>1 Rp = 0.001 point</code>.
            </div>

            <div class="mt-3 p-3 border rounded bg-light">
              <div class="fw-semibold mb-2">Ringkasan Point In</div>

              <div>
                <strong>1 Rp</strong>
                =
                <strong>{{ number_format($inPointsPerIdr, 6, ',', '.') }}</strong>
                point
              </div>

              <div class="text-muted small">
                Rp{{ number_format($sampleIdr, 0, ',', '.') }}
                ≈
                <strong>{{ number_format($samplePointIn, 2, ',', '.') }} point</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-1">Kurs: Point Out ⇄ Rupiah</h5>
            <small class="text-muted">
              Digunakan untuk menghitung nilai rupiah saat customer menukar / redeem point.
            </small>
          </div>

          <div class="card-body">
            <label class="form-label">Kurs dasar point keluar</label>

            <div class="input-group">
              <input
                type="number"
                min="0.000001"
                step="0.000001"
                name="out_points_base"
                value="{{ old('out_points_base', $outPointsBase) }}"
                class="form-control"
                required
              >

              <span class="input-group-text">point</span>
              <span class="input-group-text">=</span>
              <span class="input-group-text">Rp</span>

              <input
                type="number"
                min="1"
                step="1"
                name="out_idr_per_base"
                value="{{ old('out_idr_per_base', $outIdrPerBase) }}"
                class="form-control"
                required
              >
            </div>

            <div class="form-text mt-2">
              Contoh: <code>1 point = Rp1.000</code>, artinya 100 point bisa dipakai senilai Rp100.000.
            </div>

            <div class="mt-3 p-3 border rounded bg-light">
              <div class="fw-semibold mb-2">Ringkasan Point Out</div>

              <div>
                <strong>1 point</strong>
                =
                <strong>Rp{{ number_format($outIdrPerPoint, 0, ',', '.') }}</strong>
              </div>

              <div class="text-muted small">
                {{ number_format($samplePointOut, 0, ',', '.') }} point
                ≈
                <strong>Rp{{ number_format($sampleRedeemValue, 0, ',', '.') }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

     

    <div class="d-flex justify-content-end gap-2 mt-4">
      <button class="btn btn-primary">
        <i class="mdi mdi-content-save-outline me-1"></i>
        Simpan
      </button>
    </div>
  </form>
</div>
@endsection