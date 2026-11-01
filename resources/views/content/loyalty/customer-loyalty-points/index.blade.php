@extends('layouts/contentNavbarLayout')

@section('title', 'Loyalty & Membership - Customer Loyalty Points')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4"><span class="text-muted fw-light">Loyalty & Membership /</span> Customer Loyalty Points</h4>

    <div class="card">
        <h5 class="card-header d-flex align-items-center justify-content-between">
            <span>Daftar Loyalty Points</span>
            <form action="{{ route('loyalty.customer-loyalty-points.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm"
                    placeholder="Cari nama/email/WA ...">
                <button class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-magnify"></i></button>
            </form>
        </h5>

        <div class="table-responsive text-nowrap">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>WA</th>
                        <th class="text-end">Balance</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($rows as $row)
                    <tr>
                        <td class="fw-medium">
                            <i class="mdi mdi-account-circle-outline mdi-20px text-primary me-2"></i>
                            {{ $row->customer->name ?? '-' }}
                        </td>
                        <td>{{ $row->customer->email ?? '-' }}</td>
                        <td>{{ $row->customer->wa_number ?? '-' }}</td>
                        <td class="text-end">
                            <span class="badge bg-label-info">{{ number_format($row->balance ?? 0) }}</span>
                        </td>
                        <td>{{ optional($row->updated_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-end">
            {{ $rows->links() }}
        </div>
    </div>

    <p class="text-muted mt-2 mb-0">
        <small>Halaman ini bersifat <strong>read-only</strong>. Tidak ada Tambah/Ubah/Hapus.</small>
    </p>
</div>
@endsection