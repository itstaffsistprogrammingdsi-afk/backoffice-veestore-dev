@extends('layouts/contentNavbarLayout')

@section('title', 'Approve Request Voucher')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Branch Operations / Request Voucher /</span> Approve
  </h4>

  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="fw-semibold mb-1">Terdapat kesalahan input:</div>
      <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('branch-operations.request-voucher.approve', $requestRow->id) }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Informasi Request</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Request Number</label>
                <input type="text" class="form-control" value="{{ $requestRow->request_number ?? '-' }}" readonly>
              </div>
              <div class="col-md-4">
                <label class="form-label">Store</label>
                <input type="text" class="form-control" value="{{ $requestRow->store_name ?? '-' }}" readonly>
              </div>
              <div class="col-md-4">
                <label class="form-label">Requested By</label>
                <input type="text" class="form-control" value="{{ $requestRow->requested_by_name ?? '-' }}" readonly>
              </div>

              <div class="col-md-4">
                <label class="form-label">Request Date</label>
                <input type="text" class="form-control" value="{{ $requestRow->request_date ? \Carbon\Carbon::parse($requestRow->request_date)->format('d M Y H:i') : '-' }}" readonly>
              </div>

              <div class="col-md-4">
                <label class="form-label">Status</label>
                <input type="text" class="form-control" value="{{ ucfirst((string) $requestRow->status) }}" readonly>
              </div>

              <div class="col-md-4">
                <label class="form-label">Usage Limit Otomatis</label>
                <input type="number" class="form-control" value="{{ $usageLimit }}" readonly>
              </div>

              <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea class="form-control" rows="3" readonly>{{ $requestRow->notes ?? '-' }}</textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Form Voucher</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Code <span class="text-danger">*</span></label>
                <input
                  type="text"
                  name="code"
                  class="form-control"
                  value="{{ old('code', $defaultCode) }}"
                  required
                >
              </div>

              <div class="col-md-6">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input
                  type="text"
                  name="name"
                  class="form-control"
                  value="{{ old('name', $defaultName) }}"
                  required
                >
              </div>

              <div class="col-md-6">
                <label class="form-label">Type <span class="text-danger">*</span></label>
                <select name="type" class="form-select" required>
                  <option value="">Pilih Type</option>
                  @foreach($voucherTypes as $type)
                    <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>
                      {{ $type }}
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Context <span class="text-danger">*</span></label>
                <select name="context" class="form-select" required>
                  <option value="">Pilih Context</option>
                  @foreach($voucherContexts as $context)
                    <option value="{{ $context }}" {{ old('context') === $context ? 'selected' : '' }}>
                      {{ $context }}
                    </option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Value <span class="text-danger">*</span></label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  name="value"
                  class="form-control"
                  value="{{ old('value') }}"
                  required
                >
              </div>

              <div class="col-md-4">
                <label class="form-label">Max Discount</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  name="max_discount"
                  class="form-control"
                  value="{{ old('max_discount', 0) }}"
                >
              </div>

              <div class="col-md-4">
                <label class="form-label">Min Purchase</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  name="min_purchase"
                  class="form-control"
                  value="{{ old('min_purchase', 0) }}"
                >
              </div>

              <div class="col-md-6">
                <label class="form-label">Valid From <span class="text-danger">*</span></label>
                <input
                  type="datetime-local"
                  name="valid_from"
                  class="form-control"
                  value="{{ old('valid_from', now()->format('Y-m-d\TH:i')) }}"
                  required
                >
              </div>

              <div class="col-md-6">
                <label class="form-label">Valid Until <span class="text-danger">*</span></label>
                <input
                  type="datetime-local"
                  name="valid_until"
                  class="form-control"
                  value="{{ old('valid_until', now()->addMonth()->format('Y-m-d\TH:i')) }}"
                  required
                >
              </div>

              <div class="col-md-6">
                <label class="form-label">Usage Limit</label>
                <input
                  type="number"
                  class="form-control"
                  value="{{ $usageLimit }}"
                  readonly
                >
                <div class="form-text">Otomatis sesuai jumlah item request.</div>
              </div>

              <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    name="is_active"
                    id="is_active"
                    value="1"
                    {{ old('is_active', '1') ? 'checked' : '' }}
                  >
                  <label class="form-check-label" for="is_active">Voucher aktif</label>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $requestRow->notes) }}</textarea>
              </div>
            </div>
          </div>

          <div class="card-footer d-flex gap-2">
            <a href="{{ route('branch-operations.request-voucher.index') }}" class="btn btn-outline-secondary">
              Batal
            </a>
            <button
              type="submit"
              class="btn btn-primary"
              onclick="return confirm('Yakin ingin membuat voucher dan approve request ini?')"
            >
              Simpan Approval
            </button>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Item Request</h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead class="table-light">
                  <tr>
                    <th>No</th>
                    <th>Item ID</th>
                    <th>Product</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($items as $index => $item)
                    <tr>
                      <td>{{ $index + 1 }}</td>
                      <td>{{ $item->item_id ?? '-' }}</td>
                      <td>
                        <div>{{ $item->product_name ?? '-' }}</div>
                        <small class="text-muted">{{ $item->product_code ?? '-' }}</small>
                      </td>
                      <td>{{ $item->item_status ?? '-' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" class="text-center text-muted">Belum ada item request</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer text-muted">
            Total item: {{ $usageLimit }}
          </div>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection
