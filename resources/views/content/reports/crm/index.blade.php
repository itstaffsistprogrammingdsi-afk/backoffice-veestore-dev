@extends('layouts/contentNavbarLayout')

@section('title', 'CRM - Customer Dashboard')

@push('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@push('page-style')
<style>
  .select2-container {
    width: 100% !important;
  }

  .crm-filter-box {
    background: #f8f9fa;
    border: 1px dashed #d9dee3;
    border-radius: .75rem;
    padding: 1rem;
  }

  .summary-card {
    border: 1px solid #d9dee3;
    border-radius: .75rem;
    height: 100%;
  }

  .summary-card .label {
    color: #8592a3;
    font-size: .8rem;
    margin-bottom: .35rem;
  }

  .summary-card .value {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: #566a7f;
  }

  .winner-card {
    border: 1px solid #d9dee3;
    border-radius: .75rem;
    height: 100%;
    overflow: hidden;
  }

  .winner-card .winner-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #eceef1;
    background: #f8f9fa;
  }

  .winner-card .winner-title {
    font-weight: 700;
    margin: 0;
  }

  .winner-card .winner-subtitle {
    color: #8592a3;
    font-size: .8rem;
    margin-top: .25rem;
  }

  .winner-card .winner-body {
    padding: 1.25rem;
  }

  .winner-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #566a7f;
    margin-bottom: .35rem;
  }

  .winner-meta {
    color: #8592a3;
    font-size: .85rem;
  }

  .winner-number {
    font-size: 1.5rem;
    font-weight: 800;
    color: #696cff;
    margin-top: .75rem;
  }

  .chart-wrapper {
    min-height: 340px;
  }

  .chart-wrapper-lg {
    min-height: 420px;
  }

  .table-nowrap th,
  .table-nowrap td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .table td,
  .table th {
    vertical-align: middle;
  }

  .text-wrap-customer {
    min-width: 220px;
    white-space: normal !important;
  }

  .badge-channel {
    display: inline-block;
    padding: .3rem .55rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
    background: #e7e7ff;
    color: #696cff;
  }

  .badge-store {
    display: inline-block;
    padding: .3rem .55rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
    background: #e7f3ff;
    color: #0d6efd;
  }

  .badge-ecommerce {
    display: inline-block;
    padding: .3rem .55rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
    background: #e8fadf;
    color: #71dd37;
  }

  .detail-modal-table th,
  .detail-modal-table td {
    white-space: nowrap;
    vertical-align: middle;
  }

  .detail-summary-box {
    border: 1px solid #d9dee3;
    border-radius: .75rem;
    padding: 1rem;
    background: #f8f9fa;
    height: 100%;
  }

  .detail-summary-label {
    color: #8592a3;
    font-size: .8rem;
    margin-bottom: .25rem;
  }

  .detail-summary-value {
    font-weight: 700;
    color: #566a7f;
    margin: 0;
  }

  @media (max-width: 767.98px) {
    .summary-card .value {
      font-size: 1.05rem;
    }

    .chart-wrapper,
    .chart-wrapper-lg {
      min-height: 280px;
    }
  }
</style>
@endpush

@php
  $channelLabel = $channel === 'pos' ? 'POS' : ($channel === 'ecom' ? 'E-commerce' : 'Semua');
  $storeName = ($storeId ?? null) ? optional($stores->firstWhere('id', $storeId))->name : null;

  $totalBuyers = (float) ($summary->total_buyers ?? 0);
  $totalTransactions = (float) ($summary->total_transactions ?? 0);
  $totalSpending = (float) ($summary->total_spending ?? 0);
  $totalVisitors = (float) ($summary->total_visitors ?? 0);
  $totalVisits = (float) ($summary->total_visits ?? 0);

  $purchaseLabels = $purchaseLabels ?? collect();
  $purchaseSeries = $purchaseSeries ?? collect();

  $spendingLabels = $spendingLabels ?? collect();
  $spendingSeries = $spendingSeries ?? collect();

  $visitLabels = $visitLabels ?? collect();
  $visitSeries = $visitSeries ?? collect();

  $customerTransactionDetails = $customerTransactionDetails ?? collect();
  $customerVisitDetails = $customerVisitDetails ?? collect();

  $branchOmsetRows = $branchOmsetRows ?? collect();
  $topBranchOmset = $topBranchOmset ?? null;

  $branchOmsetLabels = $branchOmsetLabels ?? collect();
  $branchOmsetStoreSeries = $branchOmsetStoreSeries ?? collect();
  $branchOmsetEcommerceSeries = $branchOmsetEcommerceSeries ?? collect();
  $branchOmsetTotalSeries = $branchOmsetTotalSeries ?? collect();
  $branchOmsetStoreTransactions = $branchOmsetStoreTransactions ?? collect();
  $branchOmsetEcommerceTransactions = $branchOmsetEcommerceTransactions ?? collect();

  $totalStoreOmset = (float) $branchOmsetRows->sum('store_omset');
  $totalEcommerceOmset = (float) $branchOmsetRows->sum('ecommerce_omset');
  $totalBranchOmset = (float) $branchOmsetRows->sum('total_omset');
  $totalStoreTransactions = (float) $branchOmsetRows->sum('store_transactions');
  $totalEcommerceTransactions = (float) $branchOmsetRows->sum('ecommerce_transactions');
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">CRM /</span> Customer Dashboard
  </h4>

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">Filter CRM Customer</h5>
    </div>

    <div class="card-body">
      <div class="crm-filter-box">
        <form method="GET" action="{{ url('reports/crm') }}" class="row g-3 align-items-end">
          <div class="col-sm-6 col-md-3">
            <label class="form-label">Dari</label>
            <input type="date" class="form-control" name="from" value="{{ $from }}">
          </div>

          <div class="col-sm-6 col-md-3">
            <label class="form-label">Sampai</label>
            <input type="date" class="form-control" name="to" value="{{ $to }}">
          </div>

          <div class="col-sm-6 col-md-3">
            <label class="form-label">Channel</label>
            <select name="channel" class="form-select js-select2">
              <option value="" {{ $channel === '' ? 'selected' : '' }}>Semua</option>
              <option value="pos" {{ $channel === 'pos' ? 'selected' : '' }}>POS</option>
              <option value="ecom" {{ $channel === 'ecom' ? 'selected' : '' }}>E-commerce</option>
            </select>
          </div>

          <div class="col-sm-6 col-md-3">
            <label class="form-label">Store POS</label>
            <select name="store_id" class="form-select js-select2" data-placeholder="Semua Store">
              <option value="">Semua Store</option>
              @foreach($stores as $store)
                <option value="{{ $store->id }}" {{ (string) ($storeId ?? '') === (string) $store->id ? 'selected' : '' }}>
                  {{ $store->name }}
                </option>
              @endforeach
            </select>
          </div>

        <div class="col-12 d-flex gap-2 flex-wrap">
  <button class="btn btn-primary">
    <i class="mdi mdi-magnify me-1"></i> Tampilkan
  </button>

  <a href="{{ url('reports/crm/export') . '?' . http_build_query(request()->query()) }}"
     class="btn btn-success">
    <i class="mdi mdi-file-excel-outline me-1"></i> Export Excel
  </a>

  <a href="{{ url('reports/crm') }}" class="btn btn-outline-secondary">
    <i class="mdi mdi-refresh me-1"></i> Reset
  </a>
</div>
        </form>
      </div>
    </div>
  </div>

  <div class="alert alert-info">
    <strong>Periode:</strong>
    {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
    s/d
    {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
    • <strong>Channel:</strong> {{ $channelLabel }}
    @if($storeName)
      • <strong>Store:</strong> {{ $storeName }}
    @endif
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="label">Total Buyer</div>
          <h5 class="value">{{ number_format($totalBuyers, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="label">Total Transaksi</div>
          <h5 class="value">{{ number_format($totalTransactions, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="label">Total Spending</div>
          <h5 class="value">Rp {{ number_format($totalSpending, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="label">Total Kunjungan</div>
          <h5 class="value">{{ number_format($totalVisits, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="label">Total Visitor</div>
          <h5 class="value">{{ number_format($totalVisitors, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="label">Omset Store/POS</div>
          <h5 class="value">Rp {{ number_format($totalStoreOmset, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="label">Omset Ecommerce</div>
          <h5 class="value">Rp {{ number_format($totalEcommerceOmset, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card summary-card">
        <div class="card-body">
          <div class="label">Total Omset Cabang</div>
          <h5 class="value">Rp {{ number_format($totalBranchOmset, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>
  </div>

 

  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1">Grafik Omset Terbanyak per Cabang</h5>
          <small class="text-muted">Perbandingan omset Store/POS dan Ecommerce per cabang.</small>
        </div>

        <div class="card-body">
          @if(count($branchOmsetLabels))
            <div id="chart-branch-omset" class="chart-wrapper-lg"></div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data omset cabang.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1">Komposisi Omset</h5>
          <small class="text-muted">Total omset Store/POS dibanding Ecommerce.</small>
        </div>

        <div class="card-body">
          @if(($totalStoreOmset + $totalEcommerceOmset) > 0)
            <div id="chart-omset-channel-share" class="chart-wrapper"></div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data komposisi omset.</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1">Grafik Pembelian Terbanyak</h5>
          <small class="text-muted">Top 10 customer berdasarkan transaksi.</small>
        </div>

        <div class="card-body">
          @if(count($purchaseLabels))
            <div id="chart-purchase-customers" class="chart-wrapper"></div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1">Grafik Spending Terbesar</h5>
          <small class="text-muted">Top 10 customer berdasarkan total belanja.</small>
        </div>

        <div class="card-body">
          @if(count($spendingLabels))
            <div id="chart-spending-customers" class="chart-wrapper"></div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data.</div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-1">Grafik Kunjungan Terbanyak</h5>
          <small class="text-muted">Top 10 customer berdasarkan kunjungan POS.</small>
        </div>

        <div class="card-body">
          @if(count($visitLabels))
            <div id="chart-visit-customers" class="chart-wrapper"></div>
          @else
            <div class="text-center text-muted py-5">Tidak ada data.</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1">Data Omset Terbanyak per Cabang</h5>
          <small class="text-muted">Omset Store/POS dan Ecommerce berdasarkan cabang.</small>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>No</th>
                <th>Cabang</th>
                <th class="text-end">Transaksi Store</th>
                <th class="text-end">Transaksi Ecommerce</th>
                <th class="text-end">Omset Store</th>
                <th class="text-end">Omset Ecommerce</th>
                <th class="text-end">Total Omset</th>
                <th class="text-end">Total Transaksi</th>
              </tr>
            </thead>

            <tbody>
              @forelse($branchOmsetRows as $index => $row)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td>{{ $row->store_name ?? '-' }}</td>
                  <td class="text-end">{{ number_format((float) ($row->store_transactions ?? 0), 0, ',', '.') }}</td>
                  <td class="text-end">{{ number_format((float) ($row->ecommerce_transactions ?? 0), 0, ',', '.') }}</td>
                  <td class="text-end">Rp {{ number_format((float) ($row->store_omset ?? 0), 0, ',', '.') }}</td>
                  <td class="text-end">Rp {{ number_format((float) ($row->ecommerce_omset ?? 0), 0, ',', '.') }}</td>
                  <td class="text-end fw-semibold">Rp {{ number_format((float) ($row->total_omset ?? 0), 0, ',', '.') }}</td>
                  <td class="text-end">{{ number_format((float) ($row->total_transactions ?? 0), 0, ',', '.') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted">Belum ada data omset cabang.</td>
                </tr>
              @endforelse
            </tbody>

            @if($branchOmsetRows->count())
              <tfoot class="table-light">
                <tr>
                  <th colspan="2" class="text-end">Total</th>
                  <th class="text-end">{{ number_format($totalStoreTransactions, 0, ',', '.') }}</th>
                  <th class="text-end">{{ number_format($totalEcommerceTransactions, 0, ',', '.') }}</th>
                  <th class="text-end">Rp {{ number_format($totalStoreOmset, 0, ',', '.') }}</th>
                  <th class="text-end">Rp {{ number_format($totalEcommerceOmset, 0, ',', '.') }}</th>
                  <th class="text-end">Rp {{ number_format($totalBranchOmset, 0, ',', '.') }}</th>
                  <th></th>
                </tr>
              </tfoot>
            @endif
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1">Data Customer Pembelian Terbanyak</h5>
          <small class="text-muted">Customer dengan jumlah transaksi terbanyak.</small>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>No</th>
                <th>Customer</th>
                <th>WA</th>
                <th>Channel</th>
                <th>Store</th>
                <th class="text-end">Pembelian</th>
                <th class="text-end">Spending</th>
                <th>First Purchase</th>
                <th>Last Purchase</th>
                <th class="text-center">Detail</th>
              </tr>
            </thead>

            <tbody>
              @forelse($purchaseCustomers as $index => $row)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td class="text-wrap-customer">{{ $row->customer_name ?? '-' }}</td>
                  <td>{{ $row->wa_number ?? '-' }}</td>
                  <td><span class="badge-channel">{{ $row->channels ?? '-' }}</span></td>
                  <td>{{ $row->stores ?? '-' }}</td>
                  <td class="text-end">{{ number_format((float) ($row->total_purchases ?? 0), 0, ',', '.') }}</td>
                  <td class="text-end">Rp {{ number_format((float) ($row->total_spending ?? 0), 0, ',', '.') }}</td>
                  <td>{{ $row->first_purchase ? \Carbon\Carbon::parse($row->first_purchase)->format('d M Y') : '-' }}</td>
                  <td>{{ $row->last_purchase ? \Carbon\Carbon::parse($row->last_purchase)->format('d M Y') : '-' }}</td>
                  <td class="text-center">
                    <a href="{{ route('reports.crm.customer-profile', $row->customer_id) }}" class="btn btn-sm btn-outline-primary">
                      <i class="mdi mdi-account-details-outline me-1"></i> Profil
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="10" class="text-center text-muted">Belum ada data.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1">Data Customer Spending Terbesar</h5>
          <small class="text-muted">Customer dengan total belanja terbesar.</small>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>No</th>
                <th>Customer</th>
                <th>WA</th>
                <th>Channel</th>
                <th>Store</th>
                <th class="text-end">Spending</th>
                <th class="text-end">Pembelian</th>
                <th>First Purchase</th>
                <th>Last Purchase</th>
                <th class="text-center">Detail</th>
              </tr>
            </thead>

            <tbody>
              @forelse($spendingCustomers as $index => $row)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td class="text-wrap-customer">{{ $row->customer_name ?? '-' }}</td>
                  <td>{{ $row->wa_number ?? '-' }}</td>
                  <td><span class="badge-channel">{{ $row->channels ?? '-' }}</span></td>
                  <td>{{ $row->stores ?? '-' }}</td>
                  <td class="text-end">Rp {{ number_format((float) ($row->total_spending ?? 0), 0, ',', '.') }}</td>
                  <td class="text-end">{{ number_format((float) ($row->total_purchases ?? 0), 0, ',', '.') }}</td>
                  <td>{{ $row->first_purchase ? \Carbon\Carbon::parse($row->first_purchase)->format('d M Y') : '-' }}</td>
                  <td>{{ $row->last_purchase ? \Carbon\Carbon::parse($row->last_purchase)->format('d M Y') : '-' }}</td>
                  <td class="text-center">
                    <a href="{{ route('reports.crm.customer-profile', $row->customer_id) }}" class="btn btn-sm btn-outline-primary">
                      <i class="mdi mdi-account-details-outline me-1"></i> Profil
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="10" class="text-center text-muted">Belum ada data.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-1">Data Customer Kunjungan Terbanyak</h5>
          <small class="text-muted">Customer dengan kunjungan POS terbanyak.</small>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>No</th>
                <th>Customer</th>
                <th>WA</th>
                <th>Store</th>
                <th class="text-end">Kunjungan</th>
                <th>First Visit</th>
                <th>Last Visit</th>
                <th class="text-center">Detail</th>
              </tr>
            </thead>

            <tbody>
              @forelse($visitCustomers as $index => $row)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td class="text-wrap-customer">{{ $row->customer_name ?? '-' }}</td>
                  <td>{{ $row->wa_number ?? '-' }}</td>
                  <td>{{ $row->stores ?? '-' }}</td>
                  <td class="text-end">{{ number_format((float) ($row->total_visits ?? 0), 0, ',', '.') }}</td>
                  <td>{{ $row->first_visit ? \Carbon\Carbon::parse($row->first_visit)->format('d M Y') : '-' }}</td>
                  <td>{{ $row->last_visit ? \Carbon\Carbon::parse($row->last_visit)->format('d M Y') : '-' }}</td>
                  <td class="text-center">
                    <a href="{{ route('reports.crm.customer-profile', $row->customer_id) }}" class="btn btn-sm btn-outline-primary">
                      <i class="mdi mdi-account-details-outline me-1"></i> Profil
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted">Belum ada data.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  @php
    $modalTransactionCustomers = collect()
        ->merge($purchaseCustomers ?? collect())
        ->merge($spendingCustomers ?? collect())
        ->filter(fn ($item) => !empty($item->customer_id))
        ->unique('customer_id')
        ->values();
  @endphp

  @foreach($modalTransactionCustomers as $customer)
    @php
      $customerId = (string) ($customer->customer_id ?? '');
      $modalId = md5($customerId);
      $transactionRows = $customerTransactionDetails->get($customerId, collect());
      $totalTransactionAmount = $transactionRows->sum('amount');
    @endphp

    <div class="modal fade" id="transactionDetailModal-{{ $modalId }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title mb-1">Detail Transaksi Customer</h5>
              <div class="small text-muted">
                {{ $customer->customer_name ?? '-' }} | WA: {{ $customer->wa_number ?? '-' }}
              </div>
            </div>

            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Total Transaksi</div>
                  <h6 class="detail-summary-value">
                    {{ number_format($transactionRows->count(), 0, ',', '.') }}
                  </h6>
                </div>
              </div>

              <div class="col-md-4">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Total Spending</div>
                  <h6 class="detail-summary-value">
                    Rp {{ number_format((float) $totalTransactionAmount, 0, ',', '.') }}
                  </h6>
                </div>
              </div>

              <div class="col-md-4">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Periode</div>
                  <h6 class="detail-summary-value">
                    {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                    -
                    {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
                  </h6>
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm detail-modal-table mb-0">
                <thead class="table-light">
                  <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Channel</th>
                    <th>Store</th>
                    <th>No Transaksi</th>
                    <th>Visit Code</th>
                    <th>Status</th>
                    <th class="text-end">Nominal</th>
                  </tr>
                </thead>

                <tbody>
                  @forelse($transactionRows as $index => $trx)
                    <tr>
                      <td>{{ $index + 1 }}</td>

                      <td>
                        {{ $trx->transaction_at ? \Carbon\Carbon::parse($trx->transaction_at)->format('d M Y H:i') : '-' }}
                      </td>

                      <td>
                        <span class="badge-channel">
                          {{ $trx->channel ?? '-' }}
                        </span>
                      </td>

                      <td>{{ $trx->store_name ?? '-' }}</td>

                      <td>{{ $trx->transaction_code ?? '-' }}</td>

                      <td>{{ $trx->visit_code ?? '-' }}</td>

                      <td>{{ $trx->transaction_status ?? '-' }}</td>

                      <td class="text-end">
                        Rp {{ number_format((float) ($trx->amount ?? 0), 0, ',', '.') }}
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="8" class="text-center text-muted">
                        Tidak ada detail transaksi.
                      </td>
                    </tr>
                  @endforelse
                </tbody>

                @if($transactionRows->count())
                  <tfoot class="table-light">
                    <tr>
                      <th colspan="7" class="text-end">Total</th>
                      <th class="text-end">
                        Rp {{ number_format((float) $totalTransactionAmount, 0, ',', '.') }}
                      </th>
                    </tr>
                  </tfoot>
                @endif
              </table>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  @endforeach

  @foreach($visitCustomers as $customer)
    @php
      $customerId = (string) ($customer->customer_id ?? '');
      $modalId = md5($customerId);
      $visitRows = $customerVisitDetails->get($customerId, collect());
    @endphp

    <div class="modal fade" id="visitDetailModal-{{ $modalId }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title mb-1">Detail Kunjungan Customer</h5>
              <div class="small text-muted">
                {{ $customer->customer_name ?? '-' }} | WA: {{ $customer->wa_number ?? '-' }}
              </div>
            </div>

            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Total Kunjungan</div>
                  <h6 class="detail-summary-value">
                    {{ number_format($visitRows->count(), 0, ',', '.') }}
                  </h6>
                </div>
              </div>

              <div class="col-md-4">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">First Visit</div>
                  <h6 class="detail-summary-value">
                    {{ $visitRows->min('check_in_at') ? \Carbon\Carbon::parse($visitRows->min('check_in_at'))->format('d M Y H:i') : '-' }}
                  </h6>
                </div>
              </div>

              <div class="col-md-4">
                <div class="detail-summary-box">
                  <div class="detail-summary-label">Last Visit</div>
                  <h6 class="detail-summary-value">
                    {{ $visitRows->max('check_in_at') ? \Carbon\Carbon::parse($visitRows->max('check_in_at'))->format('d M Y H:i') : '-' }}
                  </h6>
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm detail-modal-table mb-0">
                <thead class="table-light">
                  <tr>
                    <th>No</th>
                    <th>Visit Code</th>
                    <th>Store</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Visit Type</th>
                    <th>Status Visit</th>
                    <th>Status Payment</th>
                  </tr>
                </thead>

                <tbody>
                  @forelse($visitRows as $index => $visit)
                    <tr>
                      <td>{{ $index + 1 }}</td>

                      <td>{{ $visit->visit_code ?? '-' }}</td>

                      <td>{{ $visit->store_name ?? '-' }}</td>

                      <td>
                        {{ $visit->check_in_at ? \Carbon\Carbon::parse($visit->check_in_at)->format('d M Y H:i') : '-' }}
                      </td>

                      <td>
                        {{ $visit->check_out_at ? \Carbon\Carbon::parse($visit->check_out_at)->format('d M Y H:i') : '-' }}
                      </td>

                      <td>{{ $visit->visit_type ?? '-' }}</td>

                      <td>{{ $visit->visit_status ?? '-' }}</td>

                      <td>{{ $visit->visit_payment_status ?? '-' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="8" class="text-center text-muted">
                        Tidak ada detail kunjungan.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endsection

@push('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
@endpush

@push('page-script')
<script>
(function () {
  $('.js-select2').select2({
    theme: 'bootstrap-5',
    allowClear: true,
    width: '100%'
  });

  function formatNumber(value) {
    return new Intl.NumberFormat('id-ID', {
      maximumFractionDigits: 0
    }).format(Number(value || 0));
  }

  function formatRupiah(value) {
    return 'Rp ' + formatNumber(value);
  }

  function shortLabel(value) {
    value = String(value || '-');
    return value.length > 18 ? value.substring(0, 18) + '…' : value;
  }

  const branchOmsetLabels = @json($branchOmsetLabels);
  const branchOmsetStoreSeries = @json($branchOmsetStoreSeries);
  const branchOmsetEcommerceSeries = @json($branchOmsetEcommerceSeries);
  const totalStoreOmset = Number(@json($totalStoreOmset));
  const totalEcommerceOmset = Number(@json($totalEcommerceOmset));

  if (branchOmsetLabels.length) {
    new ApexCharts(document.querySelector('#chart-branch-omset'), {
      chart: {
        type: 'bar',
        height: 420,
        stacked: false,
        toolbar: { show: false }
      },
      series: [
        {
          name: 'Omset Store/POS',
          data: branchOmsetStoreSeries
        },
        {
          name: 'Omset Ecommerce',
          data: branchOmsetEcommerceSeries
        }
      ],
      xaxis: {
        categories: branchOmsetLabels.map(shortLabel)
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          columnWidth: '45%'
        }
      },
      dataLabels: {
        enabled: false
      },
      tooltip: {
        y: {
          formatter: function (value) {
            return formatRupiah(value);
          }
        }
      },
      yaxis: {
        labels: {
          formatter: function (value) {
            return formatRupiah(value);
          }
        }
      }
    }).render();
  }

  if ((totalStoreOmset + totalEcommerceOmset) > 0) {
    new ApexCharts(document.querySelector('#chart-omset-channel-share'), {
      chart: {
        type: 'donut',
        height: 340,
        toolbar: { show: false }
      },
      series: [
        totalStoreOmset,
        totalEcommerceOmset
      ],
      labels: [
        'Store/POS',
        'Ecommerce'
      ],
      legend: {
        position: 'bottom'
      },
      tooltip: {
        y: {
          formatter: function (value) {
            return formatRupiah(value);
          }
        }
      },
      dataLabels: {
        formatter: function (value) {
          return value.toFixed(1) + '%';
        }
      }
    }).render();
  }

  const purchaseLabels = @json($purchaseLabels);
  const purchaseSeries = @json($purchaseSeries);

  if (purchaseLabels.length) {
    new ApexCharts(document.querySelector('#chart-purchase-customers'), {
      chart: {
        type: 'bar',
        height: 340,
        toolbar: { show: false }
      },
      series: [{
        name: 'Pembelian',
        data: purchaseSeries
      }],
      xaxis: {
        categories: purchaseLabels.map(shortLabel)
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          columnWidth: '45%'
        }
      },
      dataLabels: { enabled: false },
      tooltip: {
        y: {
          formatter: function (value) {
            return formatNumber(value) + ' transaksi';
          }
        }
      }
    }).render();
  }

  const spendingLabels = @json($spendingLabels);
  const spendingSeries = @json($spendingSeries);

  if (spendingLabels.length) {
    new ApexCharts(document.querySelector('#chart-spending-customers'), {
      chart: {
        type: 'bar',
        height: 340,
        toolbar: { show: false }
      },
      series: [{
        name: 'Spending',
        data: spendingSeries
      }],
      xaxis: {
        categories: spendingLabels.map(shortLabel)
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          columnWidth: '45%'
        }
      },
      dataLabels: { enabled: false },
      tooltip: {
        y: {
          formatter: function (value) {
            return formatRupiah(value);
          }
        }
      }
    }).render();
  }

  const visitLabels = @json($visitLabels);
  const visitSeries = @json($visitSeries);

  if (visitLabels.length) {
    new ApexCharts(document.querySelector('#chart-visit-customers'), {
      chart: {
        type: 'bar',
        height: 340,
        toolbar: { show: false }
      },
      series: [{
        name: 'Kunjungan',
        data: visitSeries
      }],
      xaxis: {
        categories: visitLabels.map(shortLabel)
      },
      plotOptions: {
        bar: {
          borderRadius: 6,
          columnWidth: '45%'
        }
      },
      dataLabels: { enabled: false },
      tooltip: {
        y: {
          formatter: function (value) {
            return formatNumber(value) + ' kunjungan';
          }
        }
      }
    }).render();
  }
})();
</script>
@endpush
