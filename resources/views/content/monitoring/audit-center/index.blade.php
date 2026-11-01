@extends('layouts/contentNavbarLayout')
@section('title', 'Audit & Security Center')
@push('page-style') @include('content.monitoring._monitoring-styles') @endpush
@section('content')
<div class="monitor-shell">
  <div class="monitor-hero mb-4"><h4 class="mb-2">Audit & Security Center</h4><p class="mb-0">Jejak perubahan data Backoffice, hasil request, identitas user, aktivitas login, dan fraud alert untuk kebutuhan pemeriksaan internal.</p></div>
  @include('content.monitoring._summary', ['items'=>[
    ['label'=>'Perubahan hari ini','value'=>$summary['changes_today'],'icon'=>'mdi-database-edit-outline'],
    ['label'=>'Aksi gagal','value'=>$summary['failed_changes'],'icon'=>'mdi-alert-circle-outline','tone'=>'danger','hint'=>'Error'],
    ['label'=>'Fraud terbuka','value'=>$summary['open_fraud'],'icon'=>'mdi-shield-alert-outline','tone'=>'danger','hint'=>'Review'],
    ['label'=>'Login gagal 24 jam','value'=>$summary['failed_login'],'icon'=>'mdi-account-lock-outline','tone'=>'warning','hint'=>'Security'],
  ]])
  <div class="card monitor-card mb-4"><div class="card-header"><h5 class="mb-1">Jejak perubahan Backoffice</h5><small class="text-muted">Data sensitif seperti password, token, secret, dan PIN otomatis disensor.</small></div><div class="card-body">
    <form class="filter-panel row g-2 mb-4"><div class="col-md-7"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari user, modul, atau route"></div><div class="col-md-3"><select name="action" class="form-select"><option value="">Semua aksi</option>@foreach(['create','update','delete','approve','reject'] as $action)<option value="{{ $action }}" @selected(request('action')===$action)>{{ ucfirst($action) }}</option>@endforeach</select></div><div class="col-md-2 d-grid"><button class="btn btn-primary">Terapkan</button></div></form>
    @if(!$logs)<div class="alert alert-warning mb-0"><i class="mdi mdi-database-clock-outline me-1"></i>Jalankan migration agar pencatatan audit mulai aktif.</div>
    @else<div class="table-responsive"><table class="table monitor-table"><thead><tr><th>Waktu</th><th>User</th><th>Modul</th><th>Aksi</th><th>Endpoint</th><th>Hasil</th><th>IP</th></tr></thead><tbody>
      @forelse($logs as $row)<tr><td>{{ \Carbon\Carbon::parse($row->occurred_at)->format('d M Y H:i:s') }}</td><td><strong>{{ $row->user_name ?: 'System/Guest' }}</strong></td><td>{{ $row->module }}</td><td><span class="status-pill info">{{ ucfirst($row->action) }}</span></td><td><small>{{ $row->method }} {{ $row->url }}</small></td><td><span class="status-pill {{ $row->response_status < 400 ? 'success':'danger' }}">HTTP {{ $row->response_status }}</span></td><td>{{ $row->ip_address ?: '-' }}</td></tr>
      @empty<tr><td colspan="7" class="monitor-empty">Belum ada perubahan tercatat. Log akan muncul setelah user melakukan tambah, edit, hapus, atau approval.</td></tr>@endforelse
    </tbody></table></div>{{ $logs->links() }}@endif
  </div></div>
  <div class="card monitor-card"><div class="card-header"><h5 class="mb-1">Fraud alert terbaru</h5><small class="text-muted">Aktivitas berisiko yang memerlukan review.</small></div><div class="table-responsive"><table class="table monitor-table mb-0"><thead><tr><th>Waktu</th><th>Tipe</th><th>Severity</th><th>Store / Kasir</th><th>Deskripsi</th><th>Status</th></tr></thead><tbody>
    @forelse($fraudAlerts as $row)<tr><td>{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y H:i') }}</td><td>{{ \Illuminate\Support\Str::headline($row->alert_type) }}</td><td><span class="status-pill danger">{{ \Illuminate\Support\Str::headline((string)$row->severity) }}</span></td><td>{{ $row->store_name ?: '-' }}<div class="small text-muted">{{ $row->cashier_name ?: '-' }}</div></td><td>{{ $row->description }}</td><td>{{ \Illuminate\Support\Str::headline((string)$row->status) }}</td></tr>
    @empty<tr><td colspan="6" class="monitor-empty">Tidak ada fraud alert.</td></tr>@endforelse
  </tbody></table></div></div>
</div>
@endsection
