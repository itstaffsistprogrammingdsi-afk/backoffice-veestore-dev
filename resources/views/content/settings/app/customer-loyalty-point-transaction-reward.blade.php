@extends('layouts/contentNavbarLayout')

@section('title','Settings - Customer Loyalty Point Reward')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4"><span class="text-muted fw-light">Settings /</span> Customer Loyalty Point Reward</h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger alert-dismissible">
      <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali input Anda.
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card">
    <h5 class="card-header">Customer Loyalty Point Transaction Reward</h5>
    <form method="POST" action="{{ route('settings.app.customer-loyalty-point-transaction-reward.update') }}">
      @csrf
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-sm-6 col-md-4">
            <label class="form-label">Reward per Transaction (LP)</label>
            <div class="input-group">
              <input type="number"
                     name="reward_per_transaction"
                     class="form-control @error('reward_per_transaction') is-invalid @enderror"
                     value="{{ old('reward_per_transaction', (int)($value['reward_per_transaction'] ?? 10)) }}"
                     min="0"
                     required>
              <span class="input-group-text">LP</span>
              @error('reward_per_transaction')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="form-text">Jumlah poin otomatis yang diberikan setiap transaksi (default 10 LP).</div>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary"><i class="mdi mdi-content-save-outline me-1"></i> Simpan</button>
        <a href="{{ route('settings.app.customer-loyalty-point-transaction-reward.edit') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>
@endsection
