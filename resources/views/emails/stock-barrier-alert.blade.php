Peringatan stok mencapai barrier

Ada {{ array_sum($payload['counts'] ?? []) }} produk baru yang stok tersedia di store atau warehouse-nya sudah menyentuh ambang barrier.

@if (! empty($payload['counts']['store']))
Store: {{ number_format((int) $payload['counts']['store'], 0, ',', '.') }} produk
@endif
@if (! empty($payload['counts']['warehouse']))
Warehouse: {{ number_format((int) $payload['counts']['warehouse'], 0, ',', '.') }} produk
@endif

Rincian alert baru:
@foreach ($payload['items'] ?? [] as $item)
- {{ $item['product_code'] ?: 'Tanpa kode' }} — {{ $item['product_name'] }} ({{ $item['scope_type'] === 'store' ? 'Store' : 'Warehouse' }}: {{ $item['location_name'] }}{{ $item['location_code'] ? ' / '.$item['location_code'] : '' }}); tersedia {{ $item['available'] }}, barrier {{ $item['stock_barrier'] }}, ambang termasuk toleransi {{ $item['threshold'] }}{{ $item['unit_name'] ? ' '.$item['unit_name'] : '' }}
@endforeach

@if (array_sum($payload['counts'] ?? []) > count($payload['items'] ?? []))
Rincian email dibatasi hingga {{ $payload['sample_limit'] ?? 200 }} produk. Buka halaman stok store atau warehouse di backoffice untuk melihat alert aktif lainnya.
@endif

Alert yang sama tidak dikirim berulang selama stok masih di bawah ambang. Alert baru dikirim lagi setelah stok pulih melewati ambang lalu turun kembali.
