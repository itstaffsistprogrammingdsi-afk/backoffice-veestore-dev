{{ $mailBody }}
@if (! empty($voucher))

========================================
VOUCHER ULANG TAHUN
{{ $voucher['name'] }}
Kode voucher: {{ $voucher['code'] }}
{{ $voucher['value_label'] }}
@if (! empty($voucher['min_purchase_label']))
{{ $voucher['min_purchase_label'] }}
@endif
@if (! empty($voucher['valid_from_label']) || ! empty($voucher['valid_until_label']))
Masa berlaku: {{ $voucher['valid_from_label'] ?? 'sekarang' }} sampai {{ $voucher['valid_until_label'] ?? 'tanpa batas' }}
@endif
@if (! empty($voucher['description']))
{{ $voucher['description'] }}
@endif
Tunjukkan atau gunakan kode ini saat bertransaksi.
========================================
@endif
