<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatCurrency, formatDate, formatNumber } from '@/composables/useFormatters';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';

interface Filters {
    period: string;
    date_from: string;
    date_to: string;
    channel: string;
    store_id: string | null;
    warehouse_id: string | null;
}

interface Props {
    filters: Filters;
    periodOptions: Array<{ value: string; label: string }>;
    summary: {
        gross_revenue: number;
        net_revenue: number;
        revenue: number;
        transactions: number;
        average_transaction: number;
        buyers: number;
    };
    orders: {
        ecommerce_total: number;
        ecommerce_open: number;
        store_order_open: number;
        return_open: number;
    };
    inventory: {
        warehouse_units: number;
        store_units: number;
        low_stock_alerts: number;
        expiring_90_units: number;
    };
    urls: {
        hub: string;
        overview: string;
        assistant: string;
        monitoring: string;
        crm: string;
        demographics: string;
        preference: string;
        exports: string;
        branch_expansion: string;
        loyalty_accounts: string;
        pending_transactions: string;
        anomalies: string;
        warehouse_audit: string;
        stock_opname: string;
        stock_movements: string;
        promo_acquisition: string;
        user_access: string;
    };
}

const props = defineProps<Props>();

const form = reactive({
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const showCustom = ref(props.filters.period === 'custom');

watch(() => props.filters, (filters) => {
    form.date_from = filters.date_from ?? '';
    form.date_to = filters.date_to ?? '';
    showCustom.value = filters.period === 'custom';
}, { deep: true });

const activePeriodLabel = computed(
    () => props.periodOptions.find((option) => option.value === props.filters.period)?.label ?? 'Rentang kustom',
);

const quickPeriodValues = ['today', 'week', 'month'];

const quickPeriods = computed(
    () => props.periodOptions.filter((option) => quickPeriodValues.includes(option.value)),
);

const otherPeriods = computed(
    () => props.periodOptions.filter((option) => !quickPeriodValues.includes(option.value)),
);

const otherPeriod = computed(
    () => (otherPeriods.value.some((option) => option.value === props.filters.period) ? props.filters.period : ''),
);

const applyOtherPeriod = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;

    if (value) {
        applyPeriod(value);
    }
};

const channelOptions = [
    { value: 'all', label: 'Semua kanal' },
    { value: 'store', label: 'Toko' },
    { value: 'ecommerce', label: 'E-commerce' },
];

const activeChannelLabel = computed(
    () => channelOptions.find((option) => option.value === props.filters.channel)?.label ?? 'Semua kanal',
);

const buildQuery = (period: string, channel: string, dateFrom = '', dateTo = '') => {
    const query: Record<string, string> = { period };

    if (channel !== 'all') {
        query.channel = channel;
    }

    if (period === 'custom') {
        query.date_from = dateFrom || props.filters.date_from;
        query.date_to = dateTo || props.filters.date_to;
    }

    return query;
};

const overviewUrl = computed(() => {
    const search = new URLSearchParams(buildQuery(
        props.filters.period,
        props.filters.channel,
        props.filters.date_from,
        props.filters.date_to,
    )).toString();

    return search ? `${props.urls.overview}?${search}` : props.urls.overview;
});

const navigate = (params: Record<string, string>) => {
    router.get(props.urls.hub, params, { preserveScroll: true, replace: true });
};

const applyPeriod = (period: string) => {
    showCustom.value = false;
    navigate(buildQuery(period, props.filters.channel));
};

const applyChannel = (channel: string) => {
    navigate(buildQuery(props.filters.period, channel, form.date_from, form.date_to));
};

const openCustom = () => {
    showCustom.value = true;
};

const applyCustom = () => {
    navigate(buildQuery('custom', props.filters.channel, form.date_from, form.date_to));
};

const resetPeriod = () => {
    navigate(buildQuery('month', props.filters.channel));
};

const modules = [
    { title: 'VEE Assistant', description: 'Tanya data operasional dengan bahasa natural dan jawaban dari data terkini.', icon: 'mdi-chat-processing-outline', url: 'assistant', action: 'Buka VEE' },
    { title: 'Monitoring', description: 'Pusat komando, stok, omzet, dan audit operasional.', icon: 'mdi-monitor-dashboard', url: 'monitoring', action: 'Buka Monitoring' },
    { title: 'CRM', description: 'Profil customer dan aktivitas pelanggan.', icon: 'mdi-account-heart-outline', url: 'crm', action: 'Buka CRM' },
    { title: 'Customer Demographics', description: 'Segmentasi gender, usia, wilayah, tier, dan kanal registrasi.', icon: 'mdi-account-details-outline', url: 'demographics', action: 'Buka Demografi' },
    { title: 'Customer Preference', description: 'Hubungkan usia, gender, wilayah, dan tier dengan produk yang dibeli.', icon: 'mdi-account-star-outline', url: 'preference', action: 'Buka Preferensi' },
    { title: 'Ekspor Saya', description: 'Unduh laporan yang sudah diproses.', icon: 'mdi-file-export-outline', url: 'exports', action: 'Lihat Ekspor' },
    { title: 'Rekomendasi Cabang Baru', description: 'Prioritaskan kecamatan dan kelurahan dari demografi, pelanggan aktif, dan nilai penjualan.', icon: 'mdi-store-plus-outline', url: 'branch_expansion', action: 'Buka Rekomendasi' },
    { title: 'Loyalty Poin & Voucher per Akun', description: 'Saldo poin, poin masuk/keluar, voucher tersedia, dan voucher terpakai per akun.', icon: 'mdi-account-cash-outline', url: 'loyalty_accounts', action: 'Buka Loyalty' },
    { title: 'Transaksi Gantung', description: 'Keranjang, kunjungan, pesanan, permintaan barang, retur, dan pembayaran yang belum selesai.', icon: 'mdi-timer-alert-outline', url: 'pending_transactions', action: 'Buka Transaksi Gantung' },
    { title: 'Anomali Transaksi', description: 'Bandingkan permintaan barang, penjualan, penerimaan, dan pencatatan pembayaran per metode.', icon: 'mdi-alert-decagram-outline', url: 'anomalies', action: 'Buka Anomali' },
    { title: 'Audit Pengguna Gudang', description: 'Aktivitas WMS pada inbound, outbound, QC, dan stok opname per pengguna.', icon: 'mdi-warehouse-cog', url: 'warehouse_audit', action: 'Buka Audit Gudang' },
    { title: 'Stok Opname, Selisih & Penyesuaian', description: 'Nilai selisih, PIC, approval, rekap harian, mingguan, dan bulanan.', icon: 'mdi-clipboard-text-clock-outline', url: 'stock_opname', action: 'Buka Opname' },
    { title: 'Stok Barang Keluar Masuk', description: 'Pergerakan real time, harian, bulanan, serta gudang kecil ke display.', icon: 'mdi-swap-vertical-bold', url: 'stock_movements', action: 'Buka Pergerakan Stok' },
    { title: 'Promo, Poin, Referral & Voucher', description: 'Pemakaian promo dan voucher, mutasi poin, referral, serta redeem poin dalam satu laporan.', icon: 'mdi-sale-outline', url: 'promo_acquisition', action: 'Buka Laporan' },
    { title: 'Akses Pengguna Harian', description: 'Login dan aktivitas per hari dan per aplikasi, termasuk WMS.', icon: 'mdi-account-clock-outline', url: 'user_access', action: 'Buka Akses' },
];
</script>

<template>
    <Head title="Laporan" />

    <div class="hub-page">
        <AppPageHeader
            eyebrow="Area Laporan"
            title="Pusat laporan yang mudah dibaca"
            description="Mulai dari ringkasan penjualan, pesanan, dan stok & logistik, lalu telusuri detail ke monitoring."
        >
            <template #actions>
                <Link :href="overviewUrl" class="btn btn-primary">
                    <i class="mdi mdi-view-dashboard-outline me-1" aria-hidden="true"></i>
                    Buka Ringkasan Usaha
                </Link>
            </template>
        </AppPageHeader>

        <section class="hub-filter" aria-label="Filter periode laporan">
            <div class="hub-filter-head">
                <div class="hub-filter-current">
                    <span class="hub-filter-label">Periode laporan</span>
                    <strong>{{ activePeriodLabel }}</strong>
                    <span class="hub-filter-range">{{ formatDate(props.filters.date_from) }} - {{ formatDate(props.filters.date_to) }}</span>
                </div>
                <button v-if="props.filters.period !== 'month'" type="button" class="hub-filter-reset" @click="resetPeriod">
                    <i class="mdi mdi-refresh me-1"></i>
                    Kembali ke bulan ini
                </button>
            </div>

            <form class="hub-filter-form" @submit.prevent="applyCustom">
                <div class="hub-presets" role="group" aria-label="Pilih periode cepat">
                    <button
                        v-for="option in quickPeriods"
                        :key="option.value"
                        type="button"
                        class="chip"
                        :class="{ 'is-active': props.filters.period === option.value }"
                        :aria-pressed="props.filters.period === option.value"
                        @click="applyPeriod(option.value)"
                    >{{ option.label }}</button>
                    <label class="visually-hidden" for="hub-period-other">Periode lain</label>
                    <select
                        id="hub-period-other"
                        class="form-select form-select-sm hub-period-select"
                        :value="otherPeriod"
                        @change="applyOtherPeriod"
                    >
                        <option value="">Periode lain</option>
                        <option v-for="option in otherPeriods" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <button
                        type="button"
                        class="chip"
                        :class="{ 'is-active': props.filters.period === 'custom' }"
                        :aria-pressed="props.filters.period === 'custom'"
                        @click="openCustom"
                    >Rentang kustom</button>
                </div>

                <div class="hub-filter-channels">
                    <span class="hub-filter-label">Kanal</span>
                    <div class="hub-presets" role="group" aria-label="Filter kanal penjualan">
                        <button
                            v-for="option in channelOptions"
                            :key="option.value"
                            type="button"
                            class="chip"
                            :class="{ 'is-active': props.filters.channel === option.value }"
                            :aria-pressed="props.filters.channel === option.value"
                            @click="applyChannel(option.value)"
                        >{{ option.label }}</button>
                    </div>
                </div>

                <div v-if="showCustom" class="hub-custom">
                    <div class="hub-custom-field">
                        <label class="form-label" for="hub-date-from">Dari tanggal</label>
                        <input
                            id="hub-date-from"
                            v-model="form.date_from"
                            class="form-control form-control-sm"
                            type="date"
                            required
                            :max="form.date_to || undefined"
                        />
                    </div>
                    <div class="hub-custom-field">
                        <label class="form-label" for="hub-date-to">Sampai tanggal</label>
                        <input
                            id="hub-date-to"
                            v-model="form.date_to"
                            class="form-control form-control-sm"
                            type="date"
                            required
                            :min="form.date_from || undefined"
                        />
                    </div>
                    <div class="hub-custom-actions">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="mdi mdi-filter-outline me-1"></i>
                            Terapkan
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="showCustom = false">
                            Batal
                        </button>
                    </div>
                </div>
            </form>

            <p class="hub-filter-note">
                Omset kotor dari harga jual sebelum diskon, omset bersih setelah diskon produk dan voucher (tanpa ongkir). Kanal memfilter omzet, transaksi, pembeli, dan order. Stok menipis selalu kondisi saat ini.
            </p>
        </section>

        <h2 class="section-title">Ringkasan</h2>
        <div class="row g-3 mb-3">
            <div v-for="stat in [
                { label: `Omset Kotor (${activePeriodLabel})`, value: formatCurrency(summary.gross_revenue) },
                { label: `Omset Bersih (${activePeriodLabel})`, value: formatCurrency(summary.net_revenue) },
                { label: 'Transaksi', value: formatNumber(summary.transactions) },
                { label: 'Pembeli teridentifikasi', value: formatNumber(summary.buyers) },
                { label: 'Stok menipis', value: formatNumber(inventory.low_stock_alerts) },
                { label: 'Order terbuka', value: formatNumber(orders.ecommerce_open + orders.store_order_open + orders.return_open) }
            ]" :key="stat.label" class="col-6 col-md-4 col-xl-2">
                <div class="hub-stat">
                    <span>{{ stat.label }}</span>
                    <strong>{{ stat.value }}</strong>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div v-for="domain in [
                { title: 'Omset', description: 'Omset kotor sebelum diskon dan omset bersih setelah diskon produk dan voucher.', anchor: 'penjualan', icon: 'mdi-cash-multiple' },
                { title: 'Pembayaran', description: 'Kecocokan nilai order dengan pembayaran e-commerce yang tercatat.', anchor: 'pembayaran', icon: 'mdi-credit-card-check-outline' },
                { title: 'Pesanan', description: 'Status pesanan, antrean SLA, permintaan store, dan retur.', anchor: 'pesanan', icon: 'mdi-clipboard-check-outline' },
                { title: 'Stok & Logistik', description: 'Stok saat ini, stok menipis, inbound, outbound, dan restock.', anchor: 'stok-logistik', icon: 'mdi-warehouse' }
            ]" :key="domain.title" class="col-md-6 col-xl-3">
                <a :href="`${overviewUrl}#${domain.anchor}`" class="hub-domain">
                    <span class="hub-domain-icon"><i class="mdi" :class="domain.icon" aria-hidden="true"></i></span>
                    <div>
                        <h3>{{ domain.title }}</h3>
                        <p>{{ domain.description }}</p>
                    </div>
                    <i class="mdi mdi-arrow-right hub-domain-arrow" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        <h2 class="section-title">Jelajahi laporan</h2>
        <div class="row g-3">
            <div v-for="module in modules" :key="module.title" class="col-md-6 col-xl-4">
                <Link :href="props.urls[module.url as keyof Props['urls']]" class="hub-card">
                    <div class="hub-card-icon"><i class="mdi" :class="module.icon" aria-hidden="true"></i></div>
                    <h3>{{ module.title }}</h3>
                    <p>{{ module.description }}</p>
                    <span class="hub-card-link">
                        {{ module.action }}
                        <i class="mdi mdi-arrow-right" aria-hidden="true"></i>
                    </span>
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hub-page {
    color: var(--app-ink);
}

.hub-filter {
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
}

.hub-filter-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem 1rem;
}

.hub-filter-current {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.35rem 0.6rem;
}

.hub-filter-label {
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.hub-filter-current strong {
    font-size: var(--app-text-md);
    font-weight: var(--app-weight-bold);
}

.hub-filter-range {
    color: var(--app-muted);
    font-size: var(--app-text-sm);
}

.hub-filter-reset {
    min-height: 32px;
    padding: 0.25rem 0.4rem;
    border: 0;
    border-radius: var(--app-radius-sm);
    background: none;
    color: var(--app-accent-ink);
    font-size: var(--app-text-sm);
    font-weight: var(--app-weight-semibold);
    transition: background-color var(--app-dur-fast) var(--app-ease-out);
}

.hub-filter-reset:hover {
    background: var(--app-surface-2);
    text-decoration: underline;
}

.hub-filter-form {
    margin-top: 0.75rem;
}

.hub-filter-channels {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.6rem;
    margin-top: 0.6rem;
}

.hub-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.hub-period-select {
    width: auto;
    min-width: 9.5rem;
    max-width: 12rem;
}

.hub-custom {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.6rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--app-border);
}

.hub-custom-field {
    flex: 1 1 11rem;
    min-width: 0;
}

.hub-custom-field .form-label {
    margin-bottom: 0.25rem;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.hub-custom-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.hub-filter-note {
    margin: 0.6rem 0 0;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    line-height: var(--app-leading-normal);
}

.hub-stat {
    height: 100%;
    padding: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
}

.hub-stat span {
    display: block;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.hub-stat strong {
    display: block;
    margin-top: 0.25rem;
    font-size: var(--app-text-xl);
    font-weight: var(--app-weight-black);
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.hub-domain {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    height: 100%;
    padding: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
    color: var(--app-ink);
    text-decoration: none;
    transition: border-color var(--app-dur-fast) var(--app-ease-out), background-color var(--app-dur-fast) var(--app-ease-out);
}

.hub-domain:hover {
    border-color: var(--app-accent);
    background: var(--app-surface-2);
}

.hub-domain-icon {
    display: grid;
    flex: 0 0 auto;
    width: 2.3rem;
    height: 2.3rem;
    place-items: center;
    border-radius: var(--app-radius-md);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: 1.2rem;
}

.hub-domain h3 {
    margin: 0 0 0.2rem;
    font-size: var(--app-text-base);
    font-weight: var(--app-weight-bold);
}

.hub-domain p {
    margin: 0;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    line-height: var(--app-leading-normal);
}

.hub-domain-arrow {
    margin-left: auto;
    color: var(--app-accent-ink);
}

.hub-card {
    display: block;
    height: 100%;
    padding: 1.35rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
    color: var(--app-ink);
    text-decoration: none;
    transition: border-color var(--app-dur-fast) var(--app-ease-out), background-color var(--app-dur-fast) var(--app-ease-out);
}

.hub-card:hover {
    border-color: color-mix(in srgb, var(--app-accent) 40%, var(--app-border));
    background: var(--app-surface-2);
}

.hub-card-icon {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    margin-bottom: 1rem;
    border-radius: var(--app-radius-lg);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: 1.5rem;
}

.hub-card h3 {
    margin: 0 0 0.4rem;
    font-size: var(--app-text-xl);
    font-weight: var(--app-weight-bold);
}

.hub-card p {
    margin: 0 0 1rem;
    color: var(--app-muted);
    font-size: var(--app-text-base);
    line-height: var(--app-leading-normal);
}

.hub-card-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 32px;
    color: var(--app-accent-ink);
    font-weight: var(--app-weight-bold);
    text-decoration: none;
}

.hub-card-link:hover,
.hub-card:hover .hub-card-link {
    text-decoration: underline;
}

@media (pointer: coarse) {
    .hub-card-link {
        min-height: 44px;
    }
}

@media (max-width: 767.98px) {
    .hub-filter-head {
        flex-direction: column;
        align-items: flex-start;
    }

    .hub-filter-channels {
        flex-direction: column;
        align-items: flex-start;
    }

    .hub-filter-reset {
        min-height: 44px;
    }

    .hub-custom-field {
        flex-basis: 100%;
    }

    .hub-custom-actions {
        width: 100%;
    }

    .hub-custom-actions .btn {
        flex: 1 1 auto;
        min-height: 44px;
    }
}
</style>
