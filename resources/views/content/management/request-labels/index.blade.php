@extends('layouts/contentNavbarLayout')

@section('title', 'Management - Request Label')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Management /</span> Request Label
  </h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-1">Request label menunggu keputusan</h5>
      <small class="text-muted">Manajemen hanya dapat menyetujui atau menolak request dengan status pending.</small>
    </div>
    <div class="card-body">
      <form method="GET" action="{{ route('management.request-labels.index') }}" class="row g-3 align-items-end">
        <div class="col-sm-6 col-lg-2">
          <label class="form-label" for="from">Dari tanggal</label>
          <input id="from" type="date" name="from" class="form-control" value="{{ $from }}">
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label" for="to">Sampai tanggal</label>
          <input id="to" type="date" name="to" class="form-control" value="{{ $to }}">
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label" for="store_id">Store</label>
          <select id="store_id" name="store_id" class="form-select">
            <option value="">Semua store</option>
            @foreach($stores as $store)
              <option value="{{ $store->id }}" @selected((string) $storeId === (string) $store->id)>{{ $store->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label" for="status">Status</label>
          <select id="status" name="status" class="form-select">
            <option value="">Semua status</option>
            @foreach(['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'printed' => 'Tercetak'] as $value => $label)
              <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-8 col-lg-3">
          <label class="form-label" for="q">Cari request</label>
          <input id="q" type="search" name="q" class="form-control" value="{{ $q }}" placeholder="ID, store, pemohon, alasan">
        </div>
        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="mdi mdi-magnify me-1"></i>Tampilkan</button>
          <a href="{{ route('management.request-labels.index') }}" class="btn btn-outline-secondary"><i class="mdi mdi-refresh me-1"></i>Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center gap-3">
      <div>
        <h5 class="mb-1">Daftar Request Label</h5>
        <small class="text-muted">Request pending tampil terlebih dahulu agar keputusan dapat diprioritaskan.</small>
      </div>
      <span class="text-muted small">{{ number_format($rows->total(), 0, ',', '.') }} request</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Waktu request</th>
            <th>Store</th>
            <th>Pemohon</th>
            <th>Alasan</th>
            <th>Label / produk</th>
            <th>Status</th>
            <th>Diproses oleh</th>
            <th>Waktu keputusan</th>
            <th class="text-center">Keputusan</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $row)
            @php
              $statusMap = [
                'pending' => ['label' => 'Pending', 'class' => 'bg-label-warning'],
                'approved' => ['label' => 'Disetujui', 'class' => 'bg-label-success'],
                'rejected' => ['label' => 'Ditolak', 'class' => 'bg-label-danger'],
                'printed' => ['label' => 'Tercetak', 'class' => 'bg-label-info'],
              ];
              $statusMeta = $statusMap[(string) $row->status] ?? ['label' => ucfirst((string) $row->status), 'class' => 'bg-label-secondary'];
            @endphp
            <tr>
              <td class="text-nowrap">{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') : '-' }}</td>
              <td>{{ $row->store_name ?? '-' }}</td>
              <td>{{ $row->requested_by_name ?? '-' }}</td>
              <td class="text-wrap" style="min-width: 180px">{{ $row->reason ?: '-' }}</td>
              <td class="text-wrap" style="min-width: 180px">
                {{ $row->item_summary ?: '-' }}
                <div class="small text-muted">{{ number_format($row->total_items ?? 0, 0, ',', '.') }} label</div>
              </td>
              <td><span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span></td>
              <td>{{ $row->approved_by_name ?? '-' }}</td>
              <td class="text-nowrap">{{ $row->approved_at ? \Carbon\Carbon::parse($row->approved_at)->format('d M Y H:i') : '-' }}</td>
              <td class="text-center">
                @if((string) $row->status === 'pending')
                  <div class="d-inline-flex gap-1">
                    <form method="POST" action="{{ route('management.request-labels.approve', $row->id) }}">
                      @csrf
                      @method('PUT')
                      <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Setujui request label ini?')">Setujui</button>
                    </form>
                    <form method="POST" action="{{ route('management.request-labels.reject', $row->id) }}">
                      @csrf
                      @method('PUT')
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tolak request label ini?')">Tolak</button>
                    </form>
                  </div>
                @else
                  <span class="text-muted small">Selesai diproses</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-4">Tidak ada request label pada filter ini. Ubah periode atau filter untuk melihat request lain.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($rows->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">Menampilkan {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} dari {{ $rows->total() }} request</small>
        {{ $rows->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
