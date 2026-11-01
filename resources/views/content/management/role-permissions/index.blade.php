@extends('layouts/contentNavbarLayout')

@section('title', 'Hak Akses Role')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
      <span class="text-muted fw-light">Manajemen /</span> Hak Akses Role
    </h4>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <div class="d-flex align-items-center">
        <i class="mdi mdi-check-circle-outline me-2 fs-5"></i>
        <span>{{ session('success') }}</span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Atur Hak Akses Setiap Role</h5>
          <p class="text-muted small mb-0">
            Centang akses yang boleh digunakan oleh setiap role. Perubahan berlaku setelah disimpan.
          </p>
        </div>
        <span class="badge bg-label-primary">{{ $permissions->count() }} Hak Akses</span>
      </div>
    </div>

    <form method="POST" action="{{ route('management.role-permissions.update') }}">
      @csrf
      @method('PUT')

      <div class="card-body p-0">
        <div class="permission-toolbar p-3 border-bottom">
          <div class="row g-3 align-items-center">
            <div class="col-lg-6">
              <div class="input-group">
                <span class="input-group-text bg-white"><i class="mdi mdi-magnify"></i></span>
                <input type="search"
                       class="form-control"
                       id="permissionSearch"
                       placeholder="Cari modul atau hak akses...">
              </div>
            </div>
            <div class="col-lg-6">
              <div class="d-flex flex-wrap justify-content-lg-end gap-2 permission-legend">
                <span class="badge bg-label-info">Lihat</span>
                <span class="badge bg-label-success">Tambah</span>
                <span class="badge bg-label-warning">Ubah</span>
                <span class="badge bg-label-danger">Hapus</span>
                <span class="badge bg-label-primary">Setujui / Kelola</span>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold" for="permissionGroupFilter">Kelompok menu</label>
              <select class="form-select" id="permissionGroupFilter">
                <option value="">Semua kelompok</option>
                @foreach($permissionGroups as $groupName => $groupPermissions)
                  <option value="{{ Str::slug($groupName) }}">{{ $groupName }} ({{ $groupPermissions->count() }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold" for="permissionActionFilter">Fungsi</label>
              <select class="form-select" id="permissionActionFilter">
                <option value="">Semua fungsi</option>
                <option value="Lihat">Lihat</option>
                <option value="Tambah">Tambah</option>
                <option value="Ubah">Ubah</option>
                <option value="Hapus">Hapus</option>
                <option value="Setujui">Setujui</option>
                <option value="Kelola">Kelola</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold" for="permissionRoleFocus">Fokus role</label>
              <select class="form-select" id="permissionRoleFocus">
                <option value="">Tampilkan semua role</option>
                @foreach($roles as $role)
                  <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="alert alert-primary border-0 py-2 mt-3 mb-0">
            <i class="mdi mdi-information-outline me-1"></i>
            <strong>Petunjuk:</strong> pilih kelompok dan fokus role, lalu centang hanya fungsi yang dibutuhkan.
            Tombol tindakan cepat hanya berlaku untuk permission yang sedang ditampilkan.
          </div>
          <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnExpandGroups">
              <i class="mdi mdi-unfold-more-horizontal me-1"></i>Buka semua kelompok
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCollapseGroups">
              <i class="mdi mdi-unfold-less-horizontal me-1"></i>Tutup semua kelompok
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" id="btnEnableVisible">
              <i class="mdi mdi-check-all me-1"></i>Aktifkan yang ditampilkan
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnDisableVisible">
              <i class="mdi mdi-close-box-multiple-outline me-1"></i>Nonaktifkan yang ditampilkan
            </button>
          </div>
        </div>

        <div class="table-responsive permission-matrix-scroll"
             tabindex="0"
             aria-label="Matriks hak akses. Geser ke kanan atau kiri untuk melihat role lainnya.">
          <table class="table table-hover table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="fw-semibold js-permission-label-col" style="min-width: 320px; position: sticky; left: 0; background: #f8f9fa; z-index: 10;">
                  <div class="px-2">
                    <i class="mdi mdi-shield-key-outline me-2"></i>Modul dan Hak Akses
                  </div>
                </th>
                @foreach($roles as $role)
                  <th class="text-center permission-role-col" data-role-id="{{ $role->id }}" style="min-width: 120px;">
                    <div class="d-flex flex-column align-items-center gap-2 py-2">
                      <span class="badge bg-label-info rounded-pill px-3 py-2">
                        {{ $role->name }}
                      </span>
                      <small class="text-muted">
                        <span class="js-role-count" data-role-id="{{ $role->id }}">0</span>
                        akses aktif
                      </small>
                      <div class="form-check form-switch mb-0">
                        <input class="form-check-input js-col-toggle" 
                               type="checkbox" 
                               data-role-id="{{ $role->id }}"
                               id="toggle_{{ $role->id }}">
                        <label class="form-check-label text-muted small ms-1" for="toggle_{{ $role->id }}">
                          Semua role
                        </label>
                      </div>
                    </div>
                  </th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach($permissionGroups as $groupName => $groupPermissions)
              <tr class="permission-group-row" data-permission-group="{{ Str::slug($groupName) }}" data-collapsed="true">
                <td colspan="{{ $roles->count() + 1 }}">
                  <div class="d-flex align-items-center justify-content-between">
                    <button type="button" class="btn btn-link p-0 text-reset text-decoration-none fw-bold js-group-toggle" aria-expanded="false">
                      <i class="mdi mdi-chevron-down me-1 js-group-chevron"></i>
                      <i class="mdi mdi-folder-key-outline me-2"></i>{{ $groupName }}
                    </button>
                    <span class="badge bg-label-secondary"><span class="js-group-visible-count">{{ $groupPermissions->count() }}</span> akses</span>
                  </div>
                </td>
              </tr>
              @foreach($groupPermissions as $permission)
              @php
                $actionTone = match($permission->display_action) {
                  'Lihat' => 'info',
                  'Tambah' => 'success',
                  'Ubah' => 'warning',
                  'Hapus', 'Tolak' => 'danger',
                  default => 'primary',
                };
              @endphp
              <tr class="permission-row"
                  data-permission-group="{{ Str::slug($groupName) }}"
                  data-permission-action="{{ Str::lower($permission->display_action) }}"
                  data-permission-search="{{ Str::lower($groupName.' '.$permission->display_action.' '.$permission->display_resource.' '.$permission->name) }}">
                <td class="fw-medium bg-white" style="position: sticky; left: 0; z-index: 5;">
                  <div class="px-2 py-1 d-flex align-items-start gap-2">
                    <span class="badge bg-label-{{ $actionTone }} permission-action-badge">
                      {{ $permission->display_action }}
                    </span>
                    <div>
                      <div>{{ $permission->display_resource }}</div>
                      <small class="text-muted">{{ $permission->name }}</small>
                    </div>
                  </div>
                </td>
                @foreach($roles as $role)
                  @php
                    $checked = in_array($permission->name, $rolePermissions[$role->id] ?? [], true);
                  @endphp
                  <td class="text-center bg-white permission-role-col" data-role-id="{{ $role->id }}">
                    <div class="form-check d-flex justify-content-center mb-0">
                      <input type="checkbox" 
                             class="form-check-input js-matrix" 
                             name="matrix[{{ $role->id }}][]" 
                             value="{{ $permission->name }}" 
                             data-role-id="{{ $role->id }}"
                             id="perm_{{ $role->id }}_{{ $permission->id }}"
                             {{ $checked ? 'checked' : '' }}>
                      <label class="form-check-label" for="perm_{{ $role->id }}_{{ $permission->id }}"></label>
                    </div>
                  </td>
                @endforeach
              </tr>
              @endforeach
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer bg-white border-top py-3">
        <div class="d-flex justify-content-between align-items-center">
          <div class="text-muted small">
            <i class="mdi mdi-information-outline me-1"></i>
            Semua perubahan role akan disimpan sekaligus.
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="btnResetForm">
              <i class="mdi mdi-restore me-1"></i> Batalkan Perubahan
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="mdi mdi-content-save-outline me-1"></i> Simpan Hak Akses
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

@push('page-style')
<style>
.permission-row:hover {
  background-color: #f8f9fa;
}

.permission-toolbar {
  background: linear-gradient(180deg, #fff, #f8fafc);
}

.permission-group-row td {
  position: sticky;
  left: 0;
  z-index: 6;
  padding: .75rem 1rem;
  background: #eef2ff !important;
  color: #4338ca;
  font-size: .82rem;
  font-weight: 800;
  letter-spacing: .025em;
  text-transform: uppercase;
}

.permission-action-badge {
  min-width: 62px;
  margin-top: .1rem;
  text-align: center;
}

.permission-row.is-filtered-out,
.permission-group-row.is-filtered-out {
  display: none;
}

.permission-row.is-collapsed {
  display: none;
}

.permission-group-row .js-group-toggle {
  color: inherit;
}

.permission-group-row .js-group-toggle:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 3px;
  border-radius: .25rem;
}

.permission-group-row[data-collapsed="true"] .js-group-chevron {
  transform: rotate(-90deg);
}

.permission-role-col.d-none,
.js-permission-label-col.d-none {
  display: none !important;
}

.form-check-input:checked {
  background-color: #696cff;
  border-color: #696cff;
}

.table th {
  font-weight: 600;
  font-size: 0.9rem;
  vertical-align: middle;
}

.table td {
  vertical-align: middle;
  font-size: 0.875rem;
}

.badge {
  font-weight: 500;
  letter-spacing: 0.3px;
}

.card {
  border: none;
  border-radius: 0.5rem;
  overflow: hidden;
}

.card-header {
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}

/* Improve checkbox size and clickability */
.form-check-input {
  width: 1.25rem;
  height: 1.25rem;
  cursor: pointer;
}

.permission-toolbar .form-select,
.permission-toolbar .input-group-text,
.permission-toolbar .form-control {
  min-height: 2.6rem;
}

.permission-matrix-scroll {
  overflow-x: auto;
  overflow-y: visible;
  cursor: grab;
  scrollbar-gutter: stable;
  touch-action: pan-x pan-y;
}

.permission-matrix-scroll:focus-visible {
  outline: 2px solid #696cff;
  outline-offset: 3px;
}

.permission-matrix-scroll.is-dragging {
  cursor: grabbing;
  user-select: none;
}

.permission-matrix-scroll::-webkit-scrollbar {
  height: 12px;
}

.permission-matrix-scroll::-webkit-scrollbar-track {
  background: #eef2ff;
  border-radius: 999px;
}

.permission-matrix-scroll::-webkit-scrollbar-thumb {
  background: #a5b4fc;
  border: 3px solid #eef2ff;
  border-radius: 999px;
}

.permission-matrix-scroll::-webkit-scrollbar-thumb:hover {
  background: #696cff;
}

/* Sticky column shadow effect */
.table td[style*="position: sticky"] {
  box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
}

/* Responsive improvements */
@media (max-width: 768px) {
  .card-header .d-flex {
    flex-direction: column;
    gap: 1rem;
  }
  
  .card-footer .d-flex {
    flex-direction: column;
    gap: 1rem;
  }
  
  .card-footer .text-muted {
    text-align: center;
  }
}
</style>
@endpush

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const rows = Array.from(document.querySelectorAll('.permission-row'));
  const groupRows = Array.from(document.querySelectorAll('.permission-group-row'));
  const permissionSearch = document.getElementById('permissionSearch');
  const groupFilter = document.getElementById('permissionGroupFilter');
  const actionFilter = document.getElementById('permissionActionFilter');
  const roleFocus = document.getElementById('permissionRoleFocus');

  function updateRoleCounts() {
    document.querySelectorAll('.js-role-count').forEach(counter => {
      const roleId = counter.dataset.roleId;
      const checked = document.querySelectorAll(
        '.js-matrix[data-role-id="' + roleId + '"]:checked'
      ).length;
      counter.textContent = checked;
    });
  }

  function hasActiveFilter() {
    return Boolean(
      permissionSearch?.value.trim() ||
      groupFilter?.value ||
      actionFilter?.value
    );
  }

  function updateGroupVisibility() {
    groupRows.forEach(groupRow => {
      const group = groupRow.dataset.permissionGroup;
      const groupPermissionRows = rows.filter(row => row.dataset.permissionGroup === group);
      const visibleRows = groupPermissionRows.filter(row => !row.classList.contains('is-filtered-out'));

      groupRow.classList.toggle('is-filtered-out', visibleRows === 0);
      groupRow.querySelector('.js-group-visible-count').textContent = visibleRows.length;

      const collapsed = groupRow.dataset.collapsed === 'true';
      groupPermissionRows.forEach(row => {
        row.classList.toggle(
          'is-collapsed',
          !row.classList.contains('is-filtered-out') && collapsed && !hasActiveFilter()
        );
      });
    });
  }

  function applyFilters() {
    const keyword = permissionSearch?.value.trim().toLocaleLowerCase('id-ID') || '';
    const selectedGroup = groupFilter?.value || '';
    const selectedAction = actionFilter?.value.toLocaleLowerCase('id-ID') || '';

    rows.forEach(row => {
      const haystack = row.dataset.permissionSearch || '';
      const matchesSearch = keyword === '' || haystack.includes(keyword);
      const matchesGroup = selectedGroup === '' || row.dataset.permissionGroup === selectedGroup;
      const matchesAction = selectedAction === '' || row.dataset.permissionAction === selectedAction;

      row.classList.toggle('is-filtered-out', !(matchesSearch && matchesGroup && matchesAction));
    });

    updateGroupVisibility();
  }

  function setGroupCollapsed(groupRow, collapsed) {
    groupRow.dataset.collapsed = collapsed ? 'true' : 'false';
    groupRow.querySelector('.js-group-toggle')?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    updateGroupVisibility();
  }

  function updateRoleFocus() {
    const selectedRoleId = roleFocus?.value || '';

    document.querySelectorAll('.permission-role-col').forEach(column => {
      column.classList.toggle(
        'd-none',
        selectedRoleId !== '' && column.dataset.roleId !== selectedRoleId
      );
    });
  }

  permissionSearch?.addEventListener('input', applyFilters);
  groupFilter?.addEventListener('change', applyFilters);
  actionFilter?.addEventListener('change', applyFilters);
  roleFocus?.addEventListener('change', updateRoleFocus);

  document.querySelectorAll('.js-group-toggle').forEach(button => {
    button.addEventListener('click', function () {
      const groupRow = this.closest('.permission-group-row');
      setGroupCollapsed(groupRow, groupRow.dataset.collapsed !== 'true');
    });
  });

  document.getElementById('btnExpandGroups')?.addEventListener('click', function () {
    groupRows.forEach(groupRow => setGroupCollapsed(groupRow, false));
  });

  document.getElementById('btnCollapseGroups')?.addEventListener('click', function () {
    groupRows.forEach(groupRow => setGroupCollapsed(groupRow, true));
  });

  // Toggle kolom: nyalakan/matikan semua checkbox milik role tertentu
  document.querySelectorAll('.js-col-toggle').forEach(sw => {
    // Set initial state based on checkboxes
    const rid = sw.dataset.roleId;
    const boxes = document.querySelectorAll('.js-matrix[data-role-id="'+rid+'"]');
    const allChecked = Array.from(boxes).every(b => b.checked);
    sw.checked = allChecked && boxes.length > 0;

    sw.addEventListener('change', function() {
      const rid = this.dataset.roleId;
      const boxes = document.querySelectorAll('.js-matrix[data-role-id="'+rid+'"]');
      boxes.forEach(b => {
        b.checked = this.checked;
        // Add visual feedback
        if (this.checked) {
          b.closest('tr').classList.add('table-active');
          setTimeout(() => b.closest('tr').classList.remove('table-active'), 300);
        }
      });
      updateRoleCounts();
    });
  });

  // Update column toggle when individual checkboxes change
  document.querySelectorAll('.js-matrix').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      const rid = this.dataset.roleId;
      const boxes = document.querySelectorAll('.js-matrix[data-role-id="'+rid+'"]');
      const toggle = document.querySelector('.js-col-toggle[data-role-id="'+rid+'"]');
      const allChecked = Array.from(boxes).every(b => b.checked);
      if (toggle) {
        toggle.checked = allChecked;
      }
      updateRoleCounts();
    });
  });

  function toggleVisiblePermissions(checked) {
    const roleId = roleFocus?.value || '';

    if (roleId === '') {
      alert('Pilih satu role pada “Fokus role” sebelum memakai tindakan cepat.');
      roleFocus?.focus();
      return;
    }

    if (!hasActiveFilter()) {
      alert('Pilih kelompok menu, fungsi, atau gunakan pencarian sebelum memakai tindakan cepat.');
      groupFilter?.focus();
      return;
    }

    const boxes = rows
      .filter(row => !row.classList.contains('is-filtered-out'))
      .flatMap(row => Array.from(row.querySelectorAll('.js-matrix[data-role-id="' + roleId + '"]')));

    if (boxes.length === 0) {
      return;
    }

    boxes.forEach(box => {
      box.checked = checked;
    });

    const toggle = document.querySelector('.js-col-toggle[data-role-id="' + roleId + '"]');
    if (toggle) {
      const roleBoxes = document.querySelectorAll('.js-matrix[data-role-id="' + roleId + '"]');
      toggle.checked = Array.from(roleBoxes).every(box => box.checked);
    }

    updateRoleCounts();
  }

  document.getElementById('btnEnableVisible')?.addEventListener('click', function() {
    toggleVisiblePermissions(true);
  });

  document.getElementById('btnDisableVisible')?.addEventListener('click', function() {
    toggleVisiblePermissions(false);
  });

  document.getElementById('btnResetForm')?.addEventListener('click', function() {
    if (confirm('Batalkan semua perubahan yang belum disimpan?')) {
      window.location.reload();
    }
  });

  document.querySelectorAll('.permission-matrix-scroll').forEach(scrollArea => {
    let startX = 0;
    let startScrollLeft = 0;
    let dragging = false;

    scrollArea.addEventListener('pointerdown', function(event) {
      if (event.target.closest('input, label, button, a, select, option')) {
        return;
      }

      startX = event.clientX;
      startScrollLeft = scrollArea.scrollLeft;
      dragging = true;
      scrollArea.classList.add('is-dragging');
      scrollArea.setPointerCapture?.(event.pointerId);
    });

    scrollArea.addEventListener('pointermove', function(event) {
      if (!dragging) {
        return;
      }

      scrollArea.scrollLeft = startScrollLeft - (event.clientX - startX);
    });

    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(eventName => {
      scrollArea.addEventListener(eventName, function() {
        dragging = false;
        scrollArea.classList.remove('is-dragging');
      });
    });

    scrollArea.addEventListener('keydown', function(event) {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
        return;
      }

      event.preventDefault();
      scrollArea.scrollBy({
        left: event.key === 'ArrowRight' ? 180 : -180,
        behavior: 'smooth',
      });
    });
  });

  // Add confirmation on form submit
  document.querySelector('form').addEventListener('submit', function(e) {
    const checkedCount = document.querySelectorAll('.js-matrix:checked').length;
    if (checkedCount === 0) {
      if (!confirm('Tidak ada hak akses yang dipilih. Semua role akan kehilangan akses. Lanjutkan?')) {
        e.preventDefault();
      }
    }
  });

  updateRoleCounts();
  updateRoleFocus();
  applyFilters();
});
</script>
@endpush
@endsection
