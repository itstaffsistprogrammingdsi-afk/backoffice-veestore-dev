import { intlLocale, translate } from '@/composables/i18n';

export const formatNumber = (value: number | string | null | undefined): string => {
    const parsed = Number(value ?? 0);

    return new Intl.NumberFormat(intlLocale()).format(Number.isFinite(parsed) ? parsed : 0);
};

export const formatCurrency = (value: number | string | null | undefined): string => {
    const parsed = Number(value ?? 0);

    return new Intl.NumberFormat(intlLocale(), {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number.isFinite(parsed) ? parsed : 0);
};

export const formatDate = (value: string | null | undefined, withTime = false): string => {
    if (!value) return '-';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '-';

    return new Intl.DateTimeFormat(intlLocale(), {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    }).format(date);
};

export const formatDateLong = (value: string | null | undefined): string => {
    if (!value) return '-';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '-';

    return new Intl.DateTimeFormat(intlLocale(), {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(date);
};

export const genderLabel = (value: string | null | undefined): string => {
    const normalized = (value ?? '').toLowerCase();

    if (['male', 'm', 'laki-laki'].includes(normalized)) return translate('Laki-laki');
    if (['female', 'f', 'perempuan'].includes(normalized)) return translate('Perempuan');

    return translate('Belum diisi');
};

export const channelLabel = (value: string | null | undefined): string => {
    const normalized = (value ?? '').toLowerCase();

    if (normalized === 'store') return translate('Store');
    if (normalized === 'ecommerce') return 'E-commerce';
    if (normalized === 'backoffice') return 'Backoffice';
    if (normalized === 'warehouse') return translate('Warehouse');

    return value ? translate(value) : translate('Belum diisi');
};

export const membershipStatusLabel = (value: string | null | undefined): string => {
    const normalized = (value ?? '').toLowerCase();

    if (normalized === 'active') return translate('Active');
    if (normalized === 'non_active') return translate('Inactive');

    return value || '-';
};

export const tierBadgeClass = (tier: string | null | undefined): string => {
    const normalized = (tier ?? '').toLowerCase();

    if (normalized.includes('platinum')) return 'bg-label-primary';
    if (normalized.includes('gold')) return 'bg-label-warning';
    if (normalized.includes('silver')) return 'bg-label-info';
    if (normalized.includes('bronze')) return 'bg-label-secondary';

    return 'bg-label-secondary';
};

export const percentage = (value: number, total: number): number => {
    if (total <= 0) return 0;

    return Math.round((value / total) * 100);
};
