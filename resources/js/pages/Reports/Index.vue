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
    { title: 'Demografi Pelanggan', description: 'Segmentasi gender, usia, wilayah, tier, dan kanal registrasi.', icon: 'mdi-account-details-outline', url: 'demographics', action: 'Buka Demografi' },
    { title: 'Preferensi Pelanggan', description: 'Hubungkan usia, gender, wilayah, dan tier dengan produk yang dibeli pelanggan.', icon: 'mdi-account-star-outline', url: 'preference', action: 'Buka Preferensi' },
    { title: 'Ekspor Saya', description: 'Unduh laporan yang sudah diproses.', icon: 'mdi-file-export-outline', url: 'exports', action: 'Lihat Ekspor' },
    { title: 'Rekomendasi Cabang Baru', description: 'Prioritaskan kecamatan dan kelurahan dari demografi, pelanggan aktif, dan nilai penjualan.', icon: 'mdi-store-plus-outline', url: 'branch_expansion', action: 'Buka Rekomendasi' },
    { title: 'Loyalty Poin & Voucher per Akun', description: 'Saldo poin, poin masuk/keluar, voucher tersedia, dan voucher terpakai per akun.', icon: 'mdi-account-cash-outline', url: 'loyalty_accounts', action: 'Buka Loyalty' },
    { title: 'Transaksi Gantung', description: 'Keranjang, kunjungan, pesanan, permintaan barang, retur, dan pembayaran yang belum selesai.', icon: 'mdi-timer-alert-outline', url: 'pending_transactions', action: 'Buka Transaksi Gantung' },
    { title: 'Anomali Transaksi', description: 'Bandingkan permintaan barang, penjualan, penerimaan, dan pencatatan pembayaran per metode.', icon: 'mdi-alert-decagram-outline', url: 'anomalies', action: 'Buka Anomali' },
    { title: 'Audit Pengguna Gudang', description: 'Aktivitas WMS pada inbound, outbound, QC, dan stok opname per pengguna.', icon: 'mdi-clipboard-account-outline', url: 'warehouse_audit', action: 'Buka Audit Gudang' },
    { title: 'Stok Opname, Selisih & Penyesuaian', description: 'Nilai selisih, PIC, approval, rekap harian, mingguan, dan bulanan.', icon: 'mdi-clipboard-text-clock-outline', url: 'stock_opname', action: 'Buka Opname' },
    { title: 'Stok Barang Keluar Masuk', description: 'Pergerakan real time, harian, bulanan, serta gudang kecil ke display.', icon: 'mdi-swap-vertical-bold', url: 'stock_movements', action: 'Buka Pergerakan Stok' },
    { title: 'Promo, Poin, Referral & Voucher', description: 'Pemakaian promo dan voucher, mutasi poin, referral, serta redeem poin dalam satu laporan.', icon: 'mdi-sale-outline', url: 'promo_acquisition', action: 'Buka Laporan' },
    { title: 'Akses Pengguna Harian', description: 'Login dan aktivitas per hari dan per aplikasi, termasuk WMS.', icon: 'mdi-account-clock-outline', url: 'user_access', action: 'Buka Akses' },
];

const domains = [
    { title: 'Omset', description: 'Omset kotor dan bersih, transaksi, serta rata-rata belanja.', anchor: 'penjualan', icon: 'mdi-cash-multiple' },
    { title: 'Pembayaran', description: 'Kecocokan nilai order dengan pembayaran e-commerce.', anchor: 'pembayaran', icon: 'mdi-credit-card-check-outline' },
    { title: 'Pesanan', description: 'Status pesanan, antrean SLA, permintaan store, dan retur.', anchor: 'pesanan', icon: 'mdi-clipboard-check-outline' },
    { title: 'Stok & Logistik', description: 'Stok saat ini, stok menipis, inbound, outbound, dan restock.', anchor: 'stok-logistik', icon: 'mdi-warehouse' },
];
</script>

<template>
    <Head title="Laporan" />

    <div class="hub-page">
        <AppPageHeader
            title="Pusat laporan"
            description="Ringkasan penjualan, pesanan, dan stok untuk periode terpilih. Setiap laporan menyertakan konteks data dan tautan ke detail."
        >
            <template #actions>
                <Link :href="overviewUrl" class="btn btn-primary">
                    <i class="mdi mdi-view-dashboard-outline me-1" aria-hidden="true"></i>
                    Buka Ringkasan Usaha
                </Link>
            </template>
        </AppPageHeader>

        <section class="hub-filter" aria-labelledby="hub-filter-title">
            <div class="hub-filter-head">
                <div class="hub-filter-current">
                    <span id="hub-filter-title" class="hub-filter-label">Periode laporan</span>
                    <strong>{{ activePeriodLabel }}</strong>
                    <span class="hub-filter-range">{{ formatDate(props.filters.date_from) }} - {{ formatDate(props.filters.date_to) }}</span>
                </div>
                <button v-if="props.filters.period !== 'month'" type="button" class="hub-filter-reset" @click="resetPeriod">
                    <i class="mdi mdi-refresh me-1" aria-hidden="true"></i>
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
                            <i class="mdi mdi-filter-outline me-1" aria-hidden="true"></i>
                            Terapkan
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="showCustom = false">
                            Batal
                        </button>
                    </div>
                </div>
            </form>

            <p class="hub-filter-note">
                Omset kotor sebelum diskon. Omset bersih setelah diskon produk dan voucher, tanpa ongkir. Stok menipis adalah kondisi saat ini.
            </p>
        </section>

        <section class="hub-summary" aria-labelledby="hub-summary-title">
            <div class="hub-summary-head">
                <h2 id="hub-summary-title">Ringkasan {{ activePeriodLabel.toLowerCase() }}</h2>
                <span>{{ formatDate(props.filters.date_from) }} - {{ formatDate(props.filters.date_to) }}</span>
            </div>
            <div class="hub-summary-body">
                <div class="hub-summary-primary">
                    <span class="hub-summary-label">Omset bersih</span>
                    <strong class="hub-summary-amount">{{ formatCurrency(summary.net_revenue) }}</strong>
                    <p>
                        Omset kotor {{ formatCurrency(summary.gross_revenue) }}.
                        Rata-rata {{ formatCurrency(summary.average_transaction) }} per transaksi.
                    </p>
                </div>
                <dl class="hub-summary-metrics">
                    <div>
                        <dt>Transaksi</dt>
                        <dd>{{ formatNumber(summary.transactions) }}</dd>
                    </div>
                    <div>
                        <dt>Pembeli teridentifikasi</dt>
                        <dd>{{ formatNumber(summary.buyers) }}</dd>
                    </div>
                    <div>
                        <dt>Stok menipis</dt>
                        <dd>{{ formatNumber(inventory.low_stock_alerts) }}</dd>
                    </div>
                    <div>
                        <dt>Order terbuka</dt>
                        <dd>{{ formatNumber(orders.ecommerce_open + orders.store_order_open + orders.return_open) }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="hub-domains" aria-labelledby="hub-domains-title">
            <div class="hub-section-head">
                <div>
                    <h2 id="hub-domains-title">Ringkasan per topik</h2>
                    <p>Buka Ringkasan Usaha langsung pada bagian yang dipilih.</p>
                </div>
            </div>
            <div class="hub-domain-grid">
                <a v-for="domain in domains" :key="domain.title" :href="`${overviewUrl}#${domain.anchor}`" class="hub-domain">
                    <span class="hub-domain-icon"><i class="mdi" :class="domain.icon" aria-hidden="true"></i></span>
                    <span class="hub-domain-copy">
                        <strong>{{ domain.title }}</strong>
                        <small>{{ domain.description }}</small>
                    </span>
                </a>
            </div>
        </section>

        <section class="hub-directory" aria-labelledby="hub-directory-title">
            <div class="hub-section-head">
                <div>
                    <h2 id="hub-directory-title">Semua laporan</h2>
                    <p>{{ modules.length }} laporan tersedia untuk peran Anda.</p>
                </div>
            </div>
            <div class="hub-directory-grid">
                <Link
                    v-for="module in modules"
                    :key="module.title"
                    :href="props.urls[module.url as keyof Props['urls']]"
                    class="hub-row"
                >
                    <span class="hub-row-icon"><i class="mdi" :class="module.icon" aria-hidden="true"></i></span>
                    <span class="hub-row-copy">
                        <strong>{{ module.title }}</strong>
                        <small>{{ module.description }}</small>
                    </span>
                    <span class="hub-row-action">{{ module.action }}</span>
                </Link>
            </div>
        </section>
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
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-semibold);
}

.hub-filter-current strong {
    font-size: var(--app-text-md);
    font-weight: var(--app-weight-semibold);
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
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-semibold);
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

.hub-summary {
    padding: 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
}

.hub-summary-head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.35rem 1rem;
    margin-bottom: 1rem;
}

.hub-summary-head h2 {
    margin: 0;
    font-size: var(--app-text-md);
    font-weight: var(--app-weight-semibold);
}

.hub-summary-head span {
    color: var(--app-muted);
    font-size: var(--app-text-sm);
}

.hub-summary-body {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
    gap: 1.5rem;
    align-items: end;
}

.hub-summary-primary {
    min-width: 0;
}

.hub-summary-label {
    display: block;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
}

.hub-summary-amount {
    display: block;
    margin-top: 0.15rem;
    font-size: clamp(1.75rem, 3vw, 2.5rem);
    font-weight: var(--app-weight-semibold);
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.02em;
    line-height: 1.1;
    overflow-wrap: anywhere;
}

.hub-summary-primary p {
    margin: 0.4rem 0 0;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    line-height: var(--app-leading-normal);
}

.hub-summary-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem 1rem;
    margin: 0;
}

.hub-summary-metrics div {
    min-width: 0;
}

.hub-summary-metrics dt {
    color: var(--app-muted);
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-medium);
}

.hub-summary-metrics dd {
    margin: 0.1rem 0 0;
    font-size: var(--app-text-lg);
    font-weight: var(--app-weight-semibold);
    font-variant-numeric: tabular-nums;
}

.hub-domains {
    margin-bottom: 1.25rem;
}

.hub-section-head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.35rem 1rem;
    margin-bottom: 0.6rem;
}

.hub-section-head h2 {
    margin: 0;
    font-size: var(--app-text-lg);
    font-weight: var(--app-weight-semibold);
}

.hub-section-head p {
    margin: 0;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
}

.hub-domain-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.5rem;
}

.hub-domain {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    min-width: 0;
    padding: 0.85rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-lg);
    background: var(--app-surface);
    color: var(--app-ink);
    text-decoration: none;
    transition: border-color var(--app-dur-fast) var(--app-ease-out), background-color var(--app-dur-fast) var(--app-ease-out);
}

.hub-domain:hover,
.hub-domain:focus-visible {
    border-color: var(--app-accent);
    background: var(--app-surface-2);
}

.hub-domain-icon {
    display: grid;
    flex: 0 0 auto;
    width: 2rem;
    height: 2rem;
    place-items: center;
    border-radius: var(--app-radius-sm);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: 1.05rem;
}

.hub-domain-copy {
    min-width: 0;
}

.hub-domain-copy strong {
    display: block;
    font-size: var(--app-text-base);
    font-weight: var(--app-weight-semibold);
}

.hub-domain-copy small {
    display: block;
    margin-top: 0.1rem;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
    line-height: var(--app-leading-snug);
}

.hub-directory {
    padding-top: 1rem;
    border-top: 1px solid var(--app-border);
}

.hub-directory-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: 1.5rem;
}

.hub-row {
    display: grid;
    grid-template-columns: 2rem minmax(0, 1fr) auto;
    align-items: center;
    gap: 0.65rem;
    padding: 0.7rem 0.25rem;
    border-bottom: 1px solid var(--app-border);
    color: var(--app-ink);
    text-decoration: none;
    transition: background-color var(--app-dur-fast) var(--app-ease-out);
}

.hub-row:hover,
.hub-row:focus-visible {
    background: var(--app-surface-2);
}

.hub-row-icon {
    display: grid;
    width: 2rem;
    height: 2rem;
    place-items: center;
    border-radius: var(--app-radius-sm);
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 1rem;
}

.hub-row-copy {
    min-width: 0;
}

.hub-row-copy strong {
    display: block;
    font-size: var(--app-text-base);
    font-weight: var(--app-weight-medium);
}

.hub-row-copy small {
    display: block;
    overflow: hidden;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
    line-height: var(--app-leading-snug);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hub-row-action {
    color: var(--app-accent-ink);
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-semibold);
    white-space: nowrap;
}

@media (max-width: 1199.98px) {
    .hub-domain-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 991.98px) {
    .hub-summary-body {
        grid-template-columns: 1fr;
        align-items: start;
    }

    .hub-directory-grid {
        grid-template-columns: 1fr;
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

    .hub-domain-grid {
        grid-template-columns: 1fr;
    }

    .hub-row {
        grid-template-columns: 2rem minmax(0, 1fr);
        min-height: 56px;
    }

    .hub-row-action {
        display: none;
    }

    .hub-row-copy small {
        white-space: normal;
    }
}
</style>
