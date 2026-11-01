@extends('layouts/contentNavbarLayout')

@section('title', 'Loyalty & Membership - Loyalty Point Mutations')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4"><span class="text-muted fw-light">Loyalty & Membership /</span> Loyalty Point Mutations</h4>

    <div class="card">
        <h5 class="card-header">
            <form action="{{ route('loyalty.customer-loyalty-point-mutations.index') }}" method="GET"
                class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Cari (nama/email/WA)</label>
                    <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm"
                        placeholder="Ketik kata kunci...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipe</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="in" @selected(($type ?? '' )==='in' )>In</option>
                        <option value="out" @selected(($type ?? '' )==='out' )>Out</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Customer Point</label>
                    <select name="clp" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach($clps as $clp)
                        <option value="{{ $clp->id }}" @selected(($clpId ?? '' )===$clp->id)>
                            {{ $clp->customer->name ?? '-' }} — saldo: {{ number_format($clp->balance ?? 0) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari</label>
                    <input type="date" name="from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="to" value="{{ $dateTo }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-12 mt-2">
                    <button class="btn btn-sm btn-primary"><i class="mdi mdi-magnify me-1"></i>Filter</button>
                    <a href="{{ route('loyalty.customer-loyalty-point-mutations.index') }}"
                        class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </h5>

        <div class="table-responsive text-nowrap">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>WA</th>
                        <th class="text-center">Type</th>
                        <th class="text-end">Value</th>
                        <th class="text-end">Balance (Saat Ini)</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($rows as $row)
                    @php
                    $customer = optional(optional($row->loyaltyPoint)->customer);
                    @endphp
                    <tr>
                        <td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                        <td class="fw-medium"><i
                                class="mdi mdi-account-outline text-primary me-1"></i>{{ $customer->name ?? '-' }}</td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td>{{ $customer->wa_number ?? '-' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $row->type === 'in' ? 'bg-label-success' : 'bg-label-danger' }}">
                                {{ strtoupper($row->type) }}
                            </span>
                        </td>
                        <td class="text-end">{{ number_format($row->value ?? 0) }}</td>
                        <td class="text-end">{{ number_format(optional($row->loyaltyPoint)->balance ?? 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-end">
            {{ $rows->links() }}
        </div>
    </div>

</div>
@endsection