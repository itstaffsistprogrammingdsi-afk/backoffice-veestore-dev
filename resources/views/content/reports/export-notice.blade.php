<div class="alert alert-info d-flex flex-wrap justify-content-between gap-2">
  <span>Export diproses di background. Maksimal {{ config('report_exports.max_days') }} hari dan {{ number_format(config('report_exports.max_rows')) }} baris. Jika tanggal kosong, digunakan periode bawaan laporan.</span>
  <a href="{{ route('report-exports.index') }}" class="fw-semibold">Lihat Export Saya</a>
</div>
@error('export')<div class="alert alert-danger">{{ $message }}</div>@enderror
@error('start_date')<div class="alert alert-danger">{{ $message }}</div>@enderror
@error('end_date')<div class="alert alert-danger">{{ $message }}</div>@enderror
@error('from')<div class="alert alert-danger">{{ $message }}</div>@enderror
@error('to')<div class="alert alert-danger">{{ $message }}</div>@enderror
