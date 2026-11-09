@php
    $statusLabels = [
        'submitted' => 'Pengajuan baru',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];
    $statusLabel = $statusLabels[$status] ?? ucfirst($status);
@endphp
Pembaruan Listing Brand VeeStore

Status: {{ $statusLabel }}
Brand: {{ $payload['brand_name'] ?? '-' }}
Store: {{ $payload['store_name'] ?? '-' }}
Contact Person: {{ $payload['contact_name'] ?? '-' }}
Telepon: {{ $payload['contact_phone'] ?? '-' }}
Email: {{ $payload['contact_email'] ?? '-' }}
Diajukan oleh: {{ $payload['requested_by_name'] ?? '-' }}
Tanggal: {{ $payload['created_at'] ?? '-' }}

@if (! empty($payload['notes']))
Catatan pengajuan:
{{ $payload['notes'] }}
@endif

@if (! empty($payload['review_notes']))
Catatan review:
{{ $payload['review_notes'] }}
@endif

@if ($status === 'submitted')
Pengajuan menunggu peninjauan backoffice.
Buka halaman listing brand:
{{ route('management.brand-listings.index') }}
@elseif ($status === 'approved')
Pengajuan disetujui. Brand sudah ditambahkan ke master brand VeeStore.
@elseif ($status === 'rejected')
Pengajuan ditolak. Silakan cek catatan review untuk tindak lanjut.
@endif

Email ini dikirim otomatis oleh VeeStore Master Data & Report.
