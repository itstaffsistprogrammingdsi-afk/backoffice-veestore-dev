import { currentLocale } from '@/i18n';

const localeTag = () => (currentLocale.value === 'en' ? 'en-US' : 'id-ID');

export const formatNumber = (value: number | string | null | undefined): string => {
    const parsed = Number(value ?? 0);

    return new Intl.NumberFormat(localeTag()).format(Number.isFinite(parsed) ? parsed : 0);
};

export const formatCurrency = (value: number | string | null | undefined): string => {
    const parsed = Number(value ?? 0);

    return new Intl.NumberFormat(localeTag(), {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number.isFinite(parsed) ? parsed : 0);
};

const parseDate = (value: string): Date => {
    const normalized = value.includes(' ') && ! value.includes('T')
        ? value.replace(' ', 'T')
        : value;

    return new Date(normalized);
};

export const formatDate = (value: string | null | undefined, withTime = false): string => {
    if (!value) return '-';

    const date = parseDate(value);

    if (Number.isNaN(date.getTime())) return '-';

    return new Intl.DateTimeFormat(localeTag(), {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    }).format(date);
};

export const formatDateLong = (value: string | null | undefined): string => {
    if (!value) return '-';

    const date = parseDate(value);

    if (Number.isNaN(date.getTime())) return '-';

    return new Intl.DateTimeFormat(localeTag(), {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(date);
};

export const genderLabel = (value: string | null | undefined): string => {
    const normalized = (value ?? '').toLowerCase();

    if (['male', 'm', 'laki-laki'].includes(normalized)) return 'Laki-laki';
    if (['female', 'f', 'perempuan'].includes(normalized)) return 'Perempuan';

    return 'Belum diisi';
};

export const channelLabel = (value: string | null | undefined): string => {
    const normalized = (value ?? '').toLowerCase();

    if (normalized === 'store') return 'Store';
    if (normalized === 'ecommerce') return 'E-commerce';
    if (normalized === 'backoffice') return 'Backoffice';
    if (normalized === 'warehouse') return 'Warehouse';

    return value || 'Belum diisi';
};

export const membershipStatusLabel = (value: string | null | undefined): string => {
    const normalized = (value ?? '').toLowerCase();

    if (normalized === 'active') return 'Aktif';
    if (normalized === 'non_active') return 'Tidak aktif';

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
