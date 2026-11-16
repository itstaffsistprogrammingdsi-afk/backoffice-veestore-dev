<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SearchableSelect from '@/components/SearchableSelect.vue';
import ReportPeriodPresets from '@/components/ui/ReportPeriodPresets.vue';

interface Option { id: string; name: string }

interface Props {
    from: string;
    to: string;
    channel: string;
    channelLabel: string;
    storeId: string | null;
    storeName: string | null;
    stores: Option[];
    summary: {
        total_buyers: number;
        total_transactions: number;
        total_spending: number;
        total_visitors: number;
        total_visits: number;
    };
    purchaseCustomers: Array<Record<string, any>>;
    spendingCustomers: Array<Record<string, any>>;
    visitCustomers: Array<Record<string, any>>;
    branchOmsetRows: Array<Record<string, any>>;
    urls: {
        index: string;
        export: string;
        customerProfiles: string;
        demographics: string;
        preference: string;
    };
}

const props = defineProps<Props>();

const form = reactive({
    from: props.from,
    to: props.to,
    channel: props.channel,
    store_id: props.storeId ?? '',
});

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
})));

const query = computed(() => ({
    from: form.from || undefined,
    to: form.to || undefined,
    channel: form.channel || undefined,
    store_id: form.store_id || undefined,
}));

const applyFilters = () => {
    router.get(props.urls.index, query.value, { preserveState: true, preserveScroll: true, replace: true });
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(query.value)) if (value) params.set(key, String(value));
    const suffix = params.toString();
    return suffix ? `${props.urls.export}?${suffix}` : props.urls.export;
});

const formatNumber = (value: number | string) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));
const formatCurrency = (value: number | string) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value ?? 0));

const cards = computed(() => [
    { label: 'Pembeli', value: formatNumber(props.summary.total_buyers), hint: 'Pelanggan dengan transaksi' },
    { label: 'Transaksi', value: formatNumber(props.summary.total_transactions), hint: 'Transaksi lunas pada periode' },
    { label: 'Total belanja', value: formatCurrency(props.summary.total_spending), hint: 'Nilai belanja bersih' },
    { label: 'Pengunjung', value: formatNumber(props.summary.total_visitors), hint: 'Pengunjung teridentifikasi' },
    { label: 'Kunjungan', value: formatNumber(props.summary.total_visits), hint: 'Check-in toko' },
]);

function topValue(row: Record<string, any>, keys: string[]) {
    for (const key of keys) if (row[key] !== undefined && row[key] !== null) return row[key];
    return '-';
}
</script>

<template>
    <Head title="CRM Dashboard" />

    <div class="report-page">
        <div class="report-header">
            <div>
                <span class="report-eyebrow">Laporan / Pelanggan</span>
                <h1>Ringkasan Pelanggan</h1>
                <p>Pelanggan, transaksi, kunjungan, dan omzet cabang pada periode terpilih.</p>
            </div>
            <div class="report-header-actions">
                <a :href="props.urls.customerProfiles" class="btn btn-light btn-sm">Profil pelanggan</a>
                <a :href="props.urls.demographics" class="btn btn-light btn-sm">Demografi</a>
                <a :href="props.urls.preference" class="btn btn-light btn-sm">Preferensi</a>
                <a :href="exportUrl" class="btn btn-light btn-sm"><i class="mdi mdi-download me-1" aria-hidden="true"></i>Ekspor</a>
            </div>
        </div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <ReportPeriodPresets
                    class="mb-2"
                    :route="props.urls.index"
                    from-key="from"
                    to-key="to"
                    :from="props.from"
                    :to="props.to"
                    :query="form"
                />
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-2"><label for="crm-from" class="form-label">Tanggal dari</label><input id="crm-from" v-model="form.from" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label for="crm-to" class="form-label">Tanggal sampai</label><input id="crm-to" v-model="form.to" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label for="crm-channel" class="form-label">Kanal</label><select id="crm-channel" v-model="form.channel" class="form-select form-select-sm"><option value="">Semua</option><option value="pos">POS</option><option value="ecom">E-commerce</option></select></div>
                    <div class="col-md-3"><label for="crm-store" class="form-label">Toko</label><SearchableSelect id="crm-store" v-model="form.store_id" :options="storeOptions" placeholder="Semua toko" search-placeholder="Cari toko..." /></div>
                    <div class="col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary" type="submit">Terapkan filter</button></div>
                </form>
            </div>
        </div>

        <section class="crm-summary" aria-label="Ringkasan pelanggan">
            <div v-for="card in cards" :key="card.label" class="crm-stat">
                <span>{{ card.label }}</span>
                <strong>{{ card.value }}</strong>
                <small>{{ card.hint }}</small>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="card report-card h-100">
                    <div class="card-header"><h6 class="mb-0">Pembeli teratas</h6></div>
                    <div class="table-responsive report-table-wrap" tabindex="0" role="region" aria-label="Pembeli dengan transaksi dan belanja terbesar">
                        <table class="table table-sm mb-0">
                            <caption class="visually-hidden">Pembeli teratas berdasarkan transaksi dan belanja</caption>
                            <thead><tr><th>Pelanggan</th><th class="text-end">Transaksi</th><th class="text-end">Belanja</th></tr></thead>
                            <tbody>
                                <tr v-for="row in purchaseCustomers" :key="row.customer_id"><td>{{ row.customer_name ?? '-' }}</td><td class="text-end">{{ formatNumber(topValue(row, ['total_purchases', 'total_transactions'])) }}</td><td class="text-end">{{ formatCurrency(topValue(row, ['total_spending', 'total_amount'])) }}</td></tr>
                                <tr v-if="purchaseCustomers.length === 0"><td colspan="3" class="report-empty">Belum ada pembeli pada periode ini. Ubah tanggal atau kanal.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card report-card h-100">
                    <div class="card-header"><h6 class="mb-0">Kunjungan terbanyak</h6></div>
                    <div class="table-responsive report-table-wrap" tabindex="0" role="region" aria-label="Pelanggan dengan kunjungan terbanyak">
                        <table class="table table-sm mb-0">
                            <caption class="visually-hidden">Pelanggan dengan kunjungan toko terbanyak</caption>
                            <thead><tr><th>Pelanggan</th><th class="text-end">Kunjungan</th></tr></thead>
                            <tbody>
                                <tr v-for="row in visitCustomers" :key="row.customer_id"><td>{{ row.customer_name ?? '-' }}</td><td class="text-end">{{ formatNumber(topValue(row, ['total_visits'])) }}</td></tr>
                                <tr v-if="visitCustomers.length === 0"><td colspan="2" class="report-empty">Belum ada kunjungan pada periode ini. Ubah tanggal atau kanal.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card report-card">
                    <div class="card-header"><h6 class="mb-0">Omzet cabang</h6></div>
                    <div class="table-responsive report-table-wrap" tabindex="0" role="region" aria-label="Omzet toko dan e-commerce per cabang">
                        <table class="table table-sm mb-0">
                            <caption class="visually-hidden">Omzet toko dan e-commerce per cabang</caption>
                            <thead><tr><th>Toko</th><th class="text-end">Omzet toko</th><th class="text-end">Omzet e-commerce</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                <tr v-for="row in branchOmsetRows" :key="row.store_id ?? row.store_name"><td>{{ row.store_name ?? '-' }}</td><td class="text-end">{{ formatCurrency(row.store_omset ?? 0) }}</td><td class="text-end">{{ formatCurrency(row.ecommerce_omset ?? 0) }}</td><td class="text-end fw-bold">{{ formatCurrency(row.total_omset ?? 0) }}</td></tr>
                                <tr v-if="branchOmsetRows.length === 0"><td colspan="4" class="report-empty">Belum ada omzet cabang pada periode ini.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.report-page { --report-ink: var(--app-ink); --report-border: var(--app-border); --report-muted: var(--app-muted); color: var(--report-ink); }
.report-header { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: var(--app-radius-xl); background: var(--app-hero-bg); color: var(--app-hero-text); }
.report-header > div:first-child { min-width: 0; flex: 1 1 auto; }
.report-header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; min-width: 0; }
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: var(--app-text-xs); font-weight: var(--app-weight-semibold); }
.report-header h1 { margin: 0 0 0.35rem; font-size: var(--app-text-3xl); font-weight: var(--app-weight-semibold); }
.report-header p { margin: 0; color: var(--app-hero-muted); font-size: var(--app-text-sm); }
.report-card { border: 1px solid var(--report-border); border-radius: var(--app-radius-lg); box-shadow: none; }
.report-card th { color: var(--report-muted); font-size: var(--app-text-xs); font-weight: var(--app-weight-semibold); }
.report-empty { padding: 2rem 1rem; color: var(--report-muted); text-align: center; }

.crm-summary {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.crm-stat {
    min-width: 0;
    padding: 0.85rem 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-lg);
    background: var(--app-surface);
}

.crm-stat span {
    display: block;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-medium);
}

.crm-stat strong {
    display: block;
    margin-top: 0.15rem;
    overflow-wrap: anywhere;
    font-size: var(--app-text-2xl);
    font-weight: var(--app-weight-semibold);
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}

.crm-stat small {
    display: block;
    margin-top: 0.1rem;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
    line-height: var(--app-leading-snug);
}

@media (max-width: 1199.98px) {
    .crm-summary {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .report-header {
        padding: 1.1rem;
    }

    .report-header-actions {
        width: 100%;
    }

    .report-header-actions .btn {
        flex: 1 1 calc(50% - 0.25rem);
        min-height: 44px;
    }

    .crm-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 479.98px) {
    .crm-summary {
        grid-template-columns: 1fr;
    }
}
</style>
