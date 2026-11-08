@php
    $statusLabels = [
        'requested' => 'Menunggu persetujuan',
        'approved' => 'Disetujui',
        'partial_approved' => 'Disetujui sebagian',
        'rejected' => 'Ditolak',
    ];
    $statusLabel = $statusLabels[$status] ?? ucfirst($status);
    $details = $order->details;
    $totalQty = (int) $details->sum(fn ($detail) => $status === 'requested'
        ? (int) $detail->requested_qty
        : (int) $detail->approved_qty);
@endphp
Pembaruan Purchase Order VeeStore

Nomor PO: {{ $order->order_number }}
Status: {{ $statusLabel }}
Toko: {{ $order->store?->name ?? '-' }}
Gudang: {{ $order->warehouse?->name ?? 'Belum dialokasikan' }}
Tanggal order: {{ optional($order->date)->format('d-m-Y') ?? optional($order->created_at)->format('d-m-Y H:i') ?? '-' }}
Jumlah SKU: {{ $details->count() }}
Total qty: {{ number_format($totalQty, 0, ',', '.') }}

@if (! empty($orderNumbers))
PO dipecah menjadi:
@foreach ($orderNumbers as $orderNumber)
- {{ $orderNumber }}
@endforeach

@endif
@if ($status === 'requested')
PO baru menunggu persetujuan backoffice. Buka halaman approval:
{{ $backofficeUrl }}
@elseif ($status === 'rejected')
PO ditolak. Silakan cek alasan dan detail pada sistem toko.
@else
PO sudah diproses backoffice. Silakan cek status dan rencana pengiriman pada sistem toko.
@endif

Email ini dikirim otomatis oleh VeeStore Master Data & Report.
