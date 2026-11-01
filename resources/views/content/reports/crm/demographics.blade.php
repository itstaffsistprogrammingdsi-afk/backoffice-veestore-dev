@extends('layouts/contentNavbarLayout')

@section('title', 'CRM - Demografi Customer')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  .demography-filter .select2-container { width: 100% !important; }
  .demography-hero {
    padding: 1.5rem;
    border-radius: 1rem;
    color: #fff;
    background: linear-gradient(115deg, #34205e 0%, #7038d5 58%, #bd3ec2 100%);
    box-shadow: 0 .8rem 2rem rgba(83, 42, 157, .2);
  }
  .demography-hero .eyebrow { font-size: .75rem; font-weight: 700; letter-spacing: .08em; opacity: .72; }
  .demography-filter { border: 1px solid #ebe5f5; }
  .demography-kpi { border: 1px solid #eee9f6; border-radius: .85rem; box-shadow: 0 .15rem .55rem rgba(42, 27, 70, .035); }
  .demography-kpi .icon { width: 42px; height: 42px; display: grid; place-items: center; border-radius: .75rem; color: #6d35d3; background: #f1eaff; font-size: 1.3rem; }
  .demography-kpi .value { font-size: 1.35rem; font-weight: 700; color: #2c2340; }
  .segment-row + .segment-row { margin-top: .85rem; }
  .segment-label { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .segment-track { height: .5rem; overflow: hidden; border-radius: 999px; background: #eee9f6; }
  .segment-fill { height: 100%; border-radius: inherit; background: linear-gradient(90deg, #7740e5, #bd48c6); }
  .location-rank { width: 1.75rem; height: 1.75rem; display: grid; place-items: center; flex: 0 0 auto; border-radius: 50%; background: #f3ecff; color: #6c35ca; font-size: .75rem; font-weight: 700; }
  .birthday-date { width: 48px; text-align: center; padding: .3rem; border-radius: .65rem; background: #fff0fa; color: #b92b82; font-weight: 700; line-height: 1.1; }
  .birthday-date small { display: block; font-size: .65rem; font-weight: 600; }
  .demography-table th { white-space: nowrap; font-size: .73rem; text-transform: uppercase; letter-spacing: .025em; }
</style>
@endpush

@section('content')
@php
  $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
  $percentage = fn ($total) => $totalCustomers > 0 ? min(100, round(((int) $total / $totalCustomers) * 100)) : 0;
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="demography-hero mb-4">
    <div class="eyebrow">CUSTOMER INTELLIGENCE</div>
    <div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mt-1">
      <div>
        <h4 class="mb-1 text-white">Demografi Customer</h4>
        <p class="mb-0 opacity-75">Segmentasi pelanggan berdasarkan usia, ulang tahun, lokasi, gender, dan sumber pendaftaran untuk kebutuhan CRM.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('reports.crm.demographics.export', request()->query()) }}" class="btn btn-light text-primary">
          <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
        </a>
        <a href="{{ route('reports.crm.customer-profiles') }}" class="btn btn-outline-light">
          <i class="mdi mdi-account-details-outline me-1"></i> Lihat Profil Customer
        </a>
      </div>
    </div>
  </div>

  <div class="card demography-filter mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('reports.crm.demographics') }}" class="row g-3 align-items-end">
        <div class="col-md-4 col-xl-3">
          <label class="form-label">Cari Customer</label>
          <input class="form-control" name="q" value="{{ $q }}" placeholder="Nama, email, atau WhatsApp">
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
          <label class="form-label">Gender</label>
          <select class="form-select js-demography-select" name="gender" data-placeholder="Semua gender">
            <option value=""></option>
            <option value="male" @selected($gender === 'male')>Laki-laki</option>
            <option value="female" @selected($gender === 'female')>Perempuan</option>
          </select>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
          <label class="form-label">Usia</label>
          <select class="form-select js-demography-select" name="age_group" data-placeholder="Semua usia">
            <option value=""></option>
            @foreach($ageGroups as $key => $label)<option value="{{ $key }}" @selected($ageGroup === $key)>{{ $label }}</option>@endforeach
          </select>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
          <label class="form-label">Bulan Ulang Tahun</label>
          <select class="form-select js-demography-select" name="birth_month" data-placeholder="Semua bulan">
            <option value=""></option>
            @foreach($months as $number => $month)<option value="{{ $number }}" @selected($birthMonth === $number)>{{ $month }}</option>@endforeach
          </select>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
          <label class="form-label">Provinsi</label>
          <select class="form-select js-demography-select" name="province_code" id="demographyProvince" data-placeholder="Semua provinsi">
            <option value=""></option>
            @foreach($provinces as $province)<option value="{{ $province->code }}" @selected($provinceCode === $province->code)>{{ $province->name }}</option>@endforeach
          </select>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
          <label class="form-label">Kota / Kabupaten</label>
          <select class="form-select js-demography-select" name="regency_code" id="demographyRegency" data-placeholder="Semua kota/kabupaten" @disabled(!$provinceCode)>
            <option value=""></option>
            @if($selectedRegency)<option value="{{ $selectedRegency->code }}" selected>{{ $selectedRegency->name }}</option>@endif
          </select>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
          <label class="form-label">Kecamatan</label>
          <select class="form-select js-demography-select" name="district_code" id="demographyDistrict" data-placeholder="Semua kecamatan" @disabled(!$regencyCode)>
            <option value=""></option>
            @if($selectedDistrict)<option value="{{ $selectedDistrict->code }}" selected>{{ $selectedDistrict->name }}</option>@endif
          </select>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
          <label class="form-label">Kelurahan</label>
          <select class="form-select js-demography-select" name="village_code" id="demographyVillage" data-placeholder="Semua kelurahan" @disabled(!$districtCode)>
            <option value=""></option>
            @if($selectedVillage)<option value="{{ $selectedVillage->code }}" selected>{{ $selectedVillage->name }}</option>@endif
          </select>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3">
          <label class="form-label">Sumber Pendaftaran</label>
          <select class="form-select js-demography-select" name="registration_channel" data-placeholder="Semua sumber">
            <option value=""></option>
            @foreach($registrationChannels as $item)<option value="{{ $item }}" @selected($registrationChannel === $item)>{{ $item }}</option>@endforeach
          </select>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-3 d-flex gap-2">
          <button class="btn btn-primary" type="submit"><i class="mdi mdi-filter-outline me-1"></i>Tampilkan</button>
          <a class="btn btn-outline-secondary" href="{{ route('reports.crm.demographics') }}"><i class="mdi mdi-refresh me-1"></i>Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card demography-kpi h-100"><div class="card-body d-flex align-items-center gap-3"><div class="icon"><i class="mdi mdi-account-group-outline"></i></div><div><div class="text-muted small">Total Customer</div><div class="value">{{ number_format($totalCustomers, 0, ',', '.') }}</div></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card demography-kpi h-100"><div class="card-body d-flex align-items-center gap-3"><div class="icon"><i class="mdi mdi-cake-variant-outline"></i></div><div><div class="text-muted small">Ulang Tahun {{ $months[$birthdayMonth] }}</div><div class="value">{{ number_format($birthdayThisMonth, 0, ',', '.') }}</div></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card demography-kpi h-100"><div class="card-body d-flex align-items-center gap-3"><div class="icon"><i class="mdi mdi-map-marker-outline"></i></div><div><div class="text-muted small">Data Lokasi Terisi</div><div class="value">{{ number_format($withLocation, 0, ',', '.') }} <small class="text-muted fs-6">({{ $percentage($withLocation) }}%)</small></div></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card demography-kpi h-100"><div class="card-body d-flex align-items-center gap-3"><div class="icon"><i class="mdi mdi-account-check-outline"></i></div><div><div class="text-muted small">Profil Lengkap</div><div class="value">{{ $profileCompletion }}%</div><small class="text-muted">DOB, gender, dan provinsi</small></div></div></div></div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h5 class="mb-1">Komposisi Gender</h5><small class="text-muted">Berdasarkan data profil customer.</small></div><div class="card-body">@forelse($genderRows as $row)<div class="segment-row"><div class="d-flex justify-content-between gap-3 mb-1"><span class="segment-label">{{ $row->label }}</span><strong>{{ number_format($row->total) }} <small class="text-muted fw-normal">({{ $percentage($row->total) }}%)</small></strong></div><div class="segment-track"><div class="segment-fill" style="width: {{ $percentage($row->total) }}%"></div></div></div>@empty<div class="text-muted text-center py-4">Belum ada data customer.</div>@endforelse</div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h5 class="mb-1">Segmentasi Usia</h5><small class="text-muted">Dihitung dari tanggal lahir customer.</small></div><div class="card-body">@forelse($ageRows as $row)<div class="segment-row"><div class="d-flex justify-content-between gap-3 mb-1"><span class="segment-label">{{ $row->label }}</span><strong>{{ number_format($row->total) }} <small class="text-muted fw-normal">({{ $percentage($row->total) }}%)</small></strong></div><div class="segment-track"><div class="segment-fill" style="width: {{ $percentage($row->total) }}%"></div></div></div>@empty<div class="text-muted text-center py-4">Belum ada data usia.</div>@endforelse</div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h5 class="mb-1">Sumber Pendaftaran</h5><small class="text-muted">Channel saat customer terdaftar.</small></div><div class="card-body">@forelse($channelRows as $row)<div class="segment-row"><div class="d-flex justify-content-between gap-3 mb-1"><span class="segment-label text-capitalize">{{ $row->label }}</span><strong>{{ number_format($row->total) }} <small class="text-muted fw-normal">({{ $percentage($row->total) }}%)</small></strong></div><div class="segment-track"><div class="segment-fill" style="width: {{ $percentage($row->total) }}%"></div></div></div>@empty<div class="text-muted text-center py-4">Belum ada sumber pendaftaran.</div>@endforelse</div></div></div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-1">Sebaran Provinsi</h5><small class="text-muted">8 provinsi dengan customer terbanyak.</small></div><div class="card-body">@forelse($provinceRows as $index => $row)<div class="d-flex align-items-center gap-3 {{ $loop->last ? '' : 'mb-3' }}"><div class="location-rank">{{ $index + 1 }}</div><div class="flex-grow-1 min-w-0"><div class="d-flex justify-content-between gap-3"><span class="segment-label">{{ $row->label }}</span><strong>{{ number_format($row->total) }}</strong></div><div class="segment-track mt-1"><div class="segment-fill" style="width: {{ $percentage($row->total) }}%"></div></div></div></div>@empty<div class="text-muted text-center py-4">Data provinsi belum tersedia.</div>@endforelse</div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-1">Sebaran Kota / Kabupaten</h5><small class="text-muted">8 kota/kabupaten dengan customer terbanyak.</small></div><div class="card-body">@forelse($regencyRows as $index => $row)<div class="d-flex align-items-center gap-3 {{ $loop->last ? '' : 'mb-3' }}"><div class="location-rank">{{ $index + 1 }}</div><div class="flex-grow-1 min-w-0"><div class="d-flex justify-content-between gap-3"><span class="segment-label">{{ $row->label }}</span><strong>{{ number_format($row->total) }}</strong></div><div class="segment-track mt-1"><div class="segment-fill" style="width: {{ $percentage($row->total) }}%"></div></div></div></div>@empty<div class="text-muted text-center py-4">Data kota/kabupaten belum tersedia.</div>@endforelse</div></div></div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-lg-5"><div class="card h-100"><div class="card-header d-flex align-items-center justify-content-between"><div><h5 class="mb-1">Ulang Tahun Bulan {{ $months[$birthdayMonth] }}</h5><small class="text-muted">Peluang campaign dan loyalty greeting.</small></div><span class="badge bg-label-primary">{{ number_format($birthdayCustomers->count()) }} customer</span></div><div class="card-body">@forelse($birthdayCustomers as $birthday)<div class="d-flex align-items-center gap-3 {{ $loop->last ? '' : 'mb-3' }}"><div class="birthday-date">{{ $birthday->date_of_birth->format('d') }}<small>{{ strtoupper($birthday->date_of_birth->format('M')) }}</small></div><div class="flex-grow-1 min-w-0"><div class="fw-semibold text-truncate">{{ $birthday->name ?: '-' }}</div><small class="text-muted">{{ $birthday->province_name ?: 'Lokasi belum diisi' }} · {{ $birthday->wa_number ?: 'WhatsApp belum diisi' }}</small></div><a href="{{ route('reports.crm.customer-profile', $birthday->id) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-account-details-outline"></i></a></div>@empty<div class="text-muted text-center py-4">Tidak ada data ulang tahun pada bulan ini.</div>@endforelse</div></div></div>
    <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h5 class="mb-1">Catatan untuk Aktivasi CRM</h5><small class="text-muted">Gunakan segmentasi ini untuk membuat komunikasi yang lebih relevan.</small></div><div class="card-body"><div class="d-flex gap-3 mb-3"><i class="mdi mdi-cake-variant-outline text-primary fs-3"></i><div><div class="fw-semibold">Birthday campaign</div><small class="text-muted">Gunakan daftar ulang tahun untuk voucher, pengingat poin, atau greeting personal.</small></div></div><div class="d-flex gap-3 mb-3"><i class="mdi mdi-map-marker-radius-outline text-primary fs-3"></i><div><div class="fw-semibold">Campaign berbasis lokasi</div><small class="text-muted">Prioritaskan promo cabang, event, atau stok lokal berdasarkan sebaran kota dan provinsi.</small></div></div><div class="d-flex gap-3"><i class="mdi mdi-account-edit-outline text-primary fs-3"></i><div><div class="fw-semibold">Lengkapi data profil</div><small class="text-muted">Masih ada {{ number_format(max($totalCustomers - $completeProfiles, 0)) }} customer yang perlu dilengkapi untuk segmentasi lebih akurat.</small></div></div></div></div></div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap"><div><h5 class="mb-1">Data Demografi Customer</h5><small class="text-muted">Menampilkan {{ number_format($customers->firstItem() ?? 0) }}–{{ number_format($customers->lastItem() ?? 0) }} dari {{ number_format($customers->total()) }} customer.</small></div><span class="badge bg-label-primary">{{ number_format($withBirthDate) }} tanggal lahir terisi</span></div>
    <div class="table-responsive"><table class="table table-hover demography-table mb-0"><thead class="table-light"><tr><th>Customer</th><th>Gender</th><th>Ulang Tahun / Usia</th><th>Lokasi</th><th>Sumber</th><th>Kelengkapan</th><th class="text-center">Aksi</th></tr></thead><tbody>@forelse($customers as $customer)@php($completed = collect([$customer->date_of_birth, $customer->gender, $customer->province_code, $customer->regency_code, $customer->wa_number])->filter()->count())<tr><td><div class="fw-semibold">{{ $customer->name ?: '-' }}</div><small class="text-muted">{{ $customer->email ?: $customer->wa_number ?: '-' }}</small></td><td>{{ $customer->gender ? ucfirst($customer->gender) : '-' }}</td><td>@if($customer->date_of_birth)<div>{{ $customer->date_of_birth->format('d M Y') }}</div><small class="text-muted">{{ $customer->date_of_birth->age }} tahun</small>@else - @endif</td><td><div>{{ $customer->village_name ?: $customer->district_name ?: $customer->regency_name ?: '-' }}</div><small class="text-muted">{{ collect([$customer->district_name, $customer->regency_name, $customer->province_name])->filter()->join(' · ') ?: 'Belum diisi' }}</small></td><td class="text-capitalize">{{ $customer->registration_channel ?: '-' }}</td><td><span class="badge {{ $completed >= 4 ? 'bg-label-success' : 'bg-label-warning' }}">{{ $completed }}/5 data</span></td><td class="text-center"><a href="{{ route('reports.crm.customer-profile', $customer->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat profil"><i class="mdi mdi-account-details-outline"></i></a></td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-5">Tidak ada customer sesuai filter demografi.</td></tr>@endforelse</tbody></table></div>
    @if($customers->hasPages())<div class="card-body border-top">{{ $customers->links() }}</div>@endif
  </div>
</div>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@push('page-script')
<script>
(() => {
  const $province = $('#demographyProvince');
  const $regency = $('#demographyRegency');
  const $district = $('#demographyDistrict');
  const $village = $('#demographyVillage');

  const endpoints = {
    regencies: @json(route('reports.crm.demographics.regencies')),
    districts: @json(route('reports.crm.demographics.districts')),
    villages: @json(route('reports.crm.demographics.villages'))
  };

  function initSelect2($select) {
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    $select.select2({
      theme: 'bootstrap-5',
      width: '100%',
      allowClear: true,
      placeholder: $select.data('placeholder') || 'Pilih data'
    });
  }

  $('.js-demography-select').each(function () {
    initSelect2($(this));
  });

  function resetChild($select) {
    $select.empty()
      .append(new Option('', '', false, false))
      .prop('disabled', true)
      .trigger('change.select2');
  }

  function loadChild($select, url, parentKey, parentValue, selectedValue) {
    resetChild($select);
    if (!parentValue) return Promise.resolve();

    return $.getJSON(url, { [parentKey]: parentValue })
      .then((response) => {
        (response.results || []).forEach((item) => {
          $select.append(new Option(item.text, item.id, false, String(item.id) === String(selectedValue || '')));
        });
        $select.prop('disabled', false).trigger('change.select2');
      })
      .catch(() => {
        resetChild($select);
      });
  }

  $province.on('change', function () {
    resetChild($district);
    resetChild($village);
    loadChild($regency, endpoints.regencies, 'province_code', $(this).val(), '');
  });

  $regency.on('change', function () {
    resetChild($village);
    loadChild($district, endpoints.districts, 'regency_code', $(this).val(), '');
  });

  $district.on('change', function () {
    loadChild($village, endpoints.villages, 'district_code', $(this).val(), '');
  });

  // Saat halaman dibuka kembali dari hasil filter, isi ulang opsi wilayah
  // bertingkat sambil mempertahankan pilihan sebelumnya.
  const selectedRegency = $regency.val();
  const selectedDistrict = $district.val();
  const selectedVillage = $village.val();

  (async () => {
    if ($province.val()) {
      await loadChild($regency, endpoints.regencies, 'province_code', $province.val(), selectedRegency);
    }
    if ($regency.val()) {
      await loadChild($district, endpoints.districts, 'regency_code', $regency.val(), selectedDistrict);
    }
    if ($district.val()) {
      await loadChild($village, endpoints.villages, 'district_code', $district.val(), selectedVillage);
    }
  })();
})();
</script>
@endpush
