export interface StatusMeta {
    label: string;
    tone: string;
}

const fallback = (status: string): StatusMeta => ({
    label: status || '-',
    tone: 'bg-label-secondary',
});

const orderStatuses: Record<string, StatusMeta> = {
    pending: { label: 'Menunggu bayar', tone: 'bg-label-warning' },
    paid: { label: 'Dibayar', tone: 'bg-label-success' },
    processed: { label: 'Diproses', tone: 'bg-label-info' },
    packing: { label: 'Dikemas', tone: 'bg-label-info' },
    shipped: { label: 'Dikirim', tone: 'bg-label-info' },
    received: { label: 'Diterima', tone: 'bg-label-success' },
    cancelled: { label: 'Dibatalkan', tone: 'bg-label-danger' },
};

const paymentStatuses: Record<string, StatusMeta> = {
    paid: { label: 'Lunas', tone: 'bg-label-success' },
    pending: { label: 'Menunggu', tone: 'bg-label-warning' },
    failed: { label: 'Gagal', tone: 'bg-label-danger' },
    cancelled: { label: 'Dibatalkan', tone: 'bg-label-secondary' },
    expired: { label: 'Kedaluwarsa', tone: 'bg-label-secondary' },
    refunded: { label: 'Dikembalikan', tone: 'bg-label-secondary' },
};

const exportStatuses: Record<string, StatusMeta> = {
    queued: { label: 'Dalam antrean', tone: 'bg-label-warning' },
    processing: { label: 'Diproses', tone: 'bg-label-info' },
    completed: { label: 'Selesai', tone: 'bg-label-success' },
    failed: { label: 'Gagal', tone: 'bg-label-danger' },
};

export const orderStatusMeta = (status: string | null | undefined): StatusMeta =>
    orderStatuses[status ?? ''] ?? fallback(status ?? '');

export const paymentStatusMeta = (status: string | null | undefined): StatusMeta =>
    paymentStatuses[status ?? ''] ?? fallback(status ?? '');

export const exportStatusMeta = (status: string | null | undefined): StatusMeta =>
    exportStatuses[status ?? ''] ?? fallback(status ?? '');
