@extends('layouts/contentNavbarLayout')

@section('title','History Customer - E-commerce Detail')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 mb-4"><span class="text-muted fw-light">Reports / History Customer /</span> E-commerce Detail</h4>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-3">
            <div><strong>Customer ID:</strong> {{ $customerId }}</div>
            <div><strong>Periode:</strong> {{ \Carbon\Carbon::parse($from)->format('d M Y') }} –
                {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
            <a href="{{ route('reports.history-customer.index', ['channel'=>'ecom','from'=>$from,'to'=>$to,'customer_id'=>$customerId]) }}"
                class="btn btn-sm btn-outline-secondary">Kembali</a>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Daftar Transaksi (sementara)</h5>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Order</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                    <tr>
                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($o->order_date)->format('d M Y H:i') }}</td>
                        <td>#{{ $o->order_id }}</td>
                        <td class="text-end">Rp {{ number_format((float)$o->amount,0,',','.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Belum ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body text-muted small">
            Relasi produk, detail item, dan store fulfillment akan ditambahkan kemudian.
        </div>
    </div>
</div>
@endsection
