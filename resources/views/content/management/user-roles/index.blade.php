{{-- resources/views/content/management/user-roles/index.blade.php --}}
@extends('layouts/contentNavbarLayout')

@section('title','Management - User ⇄ Roles')

@push('vendor-style')
<style>
  .table-sticky thead th{position:sticky;top:0;z-index:10;background:#fff;box-shadow:0 2px 4px rgba(0,0,0,.05)}
  .role-chip{display:inline-flex;align-items:center;gap:.25rem;border:1px solid #e0e5eb;padding:.25rem .75rem;border-radius:1rem;background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);font-size:.75rem;font-weight:500;color:#495057;transition:.2s}
  .role-chip:hover{transform:translateY(-1px);box-shadow:0 2px 4px rgba(0,0,0,.1)}
  .roles-scroll{max-height:240px;overflow-y:auto;overflow-x:hidden;border:2px solid #e9ecef;border-radius:.75rem;padding:1rem;background:#f8f9fa;transition:border-color .2s}
  .roles-scroll:hover{border-color:#696cff}
  .roles-scroll::-webkit-scrollbar{width:8px}
  .roles-scroll::-webkit-scrollbar-track{background:#f1f1f1;border-radius:10px}
  .roles-scroll::-webkit-scrollbar-thumb{background:#696cff;border-radius:10px}
  .roles-scroll::-webkit-scrollbar-thumb:hover{background:#5a5dd8}
  .role-checkbox-wrapper{display:flex;flex-wrap:wrap;gap:.75rem}
  .role-checkbox-item{flex:0 0 auto;min-width:200px}
  .form-check-input:checked{background-color:#696cff;border-color:#696cff}
  .form-check-label{cursor:pointer;user-select:none;transition:color .2s}
  .form-check-input:checked~.form-check-label{color:#696cff;font-weight:600}
  .user-card{transition:.2s}
  .user-card:hover{background:#f8f9fa}
  .action-buttons{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem}
  .search-wrapper .input-group-text{background:#fff;border-right:none}
  .search-wrapper input{border-left:none;padding-left:0}
  .search-wrapper input:focus{box-shadow:none;border-color:#696cff}
  .btn-filter{background:#696cff;border-color:#696cff;color:#fff;font-weight:500;transition:.2s}
  .btn-filter:hover{background:#5a5dd8;border-color:#5a5dd8;transform:translateY(-1px);box-shadow:0 4px 8px rgba(105,108,255,.3)}
  .card{border:none;box-shadow:0 2px 8px rgba(0,0,0,.08);border-radius:1rem;overflow:hidden}
  .card-header{background:linear-gradient(135deg,#696cff 0%,#5a5dd8 100%);color:#fff;border:none;padding:1.25rem 1.5rem}
  .card-header code{background:rgba(255,255,255,.2);color:#fff;padding:.25rem .5rem;border-radius:.375rem;font-weight:500}
  .user-info{display:flex;flex-direction:column;gap:.5rem}
  .user-name{font-size:1rem;font-weight:600;color:#2c2e33;margin:0}
  .user-details{display:flex;gap:.75rem;flex-wrap:wrap;font-size:.8rem;color:#697a8d}
  .empty-state{padding:3rem 1rem;text-align:center}
  .empty-state-icon{font-size:3rem;color:#d4d8dd;margin-bottom:1rem}
  .uuid-badge{display:inline-block;padding:.25rem .5rem;background:#f8f9fa;border:1px solid #e9ecef;border-radius:.375rem;font-family:'Courier New',monospace;font-size:.7rem;color:#697a8d}
  @media (max-width:768px){.role-checkbox-item{min-width:160px}.action-buttons{flex-direction:column}.action-buttons button{width:100%}}
</style>
@endpush

@section('content')
<div class="container-xxl py-3">
  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <h4 class="mb-1">
        <span class="text-muted fw-light">Management /</span>
        <span style="color:#696cff">User ⇄ Roles</span>
      </h4>
      <p class="text-muted small mb-0">Setiap user hanya boleh memiliki <strong>1 role</strong>.</p>
    </div>

    {{-- Search --}}
    <form class="d-flex gap-2" method="GET" action="{{ route('management.user-roles.index') }}">
      <div class="search-wrapper">
        <div class="input-group input-group-sm" style="min-width:320px">
          <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="🔍 Cari nama, email, atau username..." style="border-right:1px solid #d9dee3">
          <span class="input-group-text bg-white"><i class="mdi mdi-magnify"></i></span>
        </div>
      </div>
      <button class="btn btn-filter btn-sm px-3"><i class="mdi mdi-filter-variant me-1"></i>Filter</button>
      @if(($q ?? '')!=='')
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('management.user-roles.index') }}" title="Reset"><i class="mdi mdi-refresh"></i></a>
      @endif
    </form>
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="mdi mdi-check-circle-outline me-2" style="font-size:1.2rem"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="mdi mdi-alert-circle-outline me-2" style="font-size:1.2rem"></i> {{ $errors->first() }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Main --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div><i class="mdi mdi-account-multiple me-2"></i><strong>Users Management</strong>
        </div>
      
    </div>

    <div class="table-responsive table-sticky">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:380px;"><i class="mdi mdi-account me-1"></i>User</th>
            <th><i class="mdi mdi-shield-account me-1"></i>Pilih Role</th>
            <th class="text-end" style="width:140px;"><i class="mdi mdi-information-outline me-1"></i>Info</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $u)
            @php
              $uid = (string) $u->id;
              // kompatibel: $userRoleId[uid] (single) ATAU $userRoleIds[uid] (array lama)
              $selectedRoleId = $userRoleId[$uid] ?? (($userRoleIds[$uid][0] ?? null) ?? null);
            @endphp
            <tr class="user-card">
              <td>
                <div class="user-info">
                  <div class="user-name">{{ $u->name }}</div>
                  <div class="user-details">
                    <span><i class="mdi mdi-email-outline"></i>{{ $u->email ?? '-' }}</span>
                    <span><i class="mdi mdi-account-circle-outline"></i>{{ $u->username ?? '-' }}</span>
                  </div>

                  {{-- Ringkasan role saat ini --}}
                  <div class="mt-2">
                    @if($selectedRoleId)
                      @php $roleNow = $roles->firstWhere('id', $selectedRoleId); @endphp
                      @if($roleNow)
                        <span class="role-chip"><i class="mdi mdi-shield-check"></i>{{ $roleNow->name }}</span>
                      @else
                        <span class="badge bg-label-secondary">Role tidak ditemukan</span>
                      @endif
                    @else
                      <span class="badge bg-label-secondary"><i class="mdi mdi-information-outline me-1"></i>Tanpa role</span>
                    @endif
                  </div>
                </div>
              </td>

              <td>
                <form method="POST" action="{{ route('management.user-roles.sync', $uid) }}" id="form-{{ $uid }}">
                  @csrf

                  <div class="roles-scroll">
                    <div class="role-checkbox-wrapper">
                      {{-- Opsi kosong --}}
                      <div class="role-checkbox-item">
                        <div class="form-check m-0">
                          <input class="form-check-input" type="radio" id="r{{ $uid }}_none" name="role_id" value="" @checked(!$selectedRoleId)>
                          <label class="form-check-label" for="r{{ $uid }}_none">— Tanpa role —</label>
                        </div>
                      </div>

                      {{-- Daftar role (radio = hanya satu) --}}
                      @foreach($roles as $r)
                        <div class="role-checkbox-item">
                          <div class="form-check m-0">
                            <input class="form-check-input" type="radio" id="r{{ $uid }}_{{ $r->id }}" name="role_id" value="{{ $r->id }}" @checked($selectedRoleId === $r->id)>
                            <label class="form-check-label" for="r{{ $uid }}_{{ $r->id }}">{{ $r->name }}</label>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>

                  <div class="action-buttons">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="mdi mdi-content-save-outline me-1"></i> Simpan Perubahan</button>
                    <span class="text-muted small">Tip: tekan <kbd>Alt</kbd> + <kbd>Enter</kbd> untuk Simpan</span>
                  </div>
                </form>
              </td>

              <td class="text-end">
                <div class="uuid-badge" title="User UUID: {{ $uid }}">{{ \Illuminate\Support\Str::limit($uid, 8, '') }}...</div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="3">
                <div class="empty-state">
                  <div class="empty-state-icon"><i class="mdi mdi-account-off-outline"></i></div>
                  <h5 class="text-muted mb-2">Tidak Ada User</h5>
                  <p class="text-muted small mb-0">
                    @if(($q ?? '')!=='') Tidak ditemukan user dengan kata kunci "{{ $q }}"
                    @else Belum ada user yang terdaftar @endif
                  </p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="text-muted small">
        @if($users->total() > 0)
          <i class="mdi mdi-information-outline me-1"></i>
          Menampilkan <strong>{{ $users->firstItem() }}–{{ $users->lastItem() }}</strong> dari <strong>{{ $users->total() }}</strong> user
        @else
          <i class="mdi mdi-information-outline me-1"></i> Menampilkan <strong>0</strong> user
        @endif
      </div>
      {{ $users->appends(['q'=>$q])->links() }}
    </div>
  </div>
</div>

@push('vendor-script')
<script>
  // Shortcut: Alt + Enter => submit form yang sedang fokus
  document.addEventListener('keydown', function(e){
    if(e.altKey && e.key === 'Enter'){
      const active = document.activeElement;
      const form = active?.closest('form[id^="form-"]');
      if(form){ e.preventDefault(); form.requestSubmit(); }
    }
  });
</script>
@endpush
@endsection
