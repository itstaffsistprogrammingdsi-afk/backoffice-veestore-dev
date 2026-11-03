<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SearchableSelect from '@/components/SearchableSelect.vue';

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
    { label: 'Pembeli', value: formatNumber(props.summary.total_buyers) },
    { label: 'Transaksi', value: formatNumber(props.summary.total_transactions) },
    { label: 'Total Belanja', value: formatCurrency(props.summary.total_spending) },
    { label: 'Pengunjung', value: formatNumber(props.summary.total_visitors) },
    { label: 'Kunjungan', value: formatNumber(props.summary.total_visits) },
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
                <span class="report-eyebrow">Reports</span>
                <h1>CRM Dashboard</h1>
                <p>Ringkasan pelanggan, transaksi, kunjungan, dan omset cabang.</p>
            </div>
            <div class="d-flex gap-2">
                <a :href="props.urls.customerProfiles" class="btn btn-light btn-sm">Customer Profile</a>
                <a :href="props.urls.demographics" class="btn btn-light btn-sm">Demografi</a>
                <a :href="props.urls.preference" class="btn btn-light btn-sm">Preferensi</a>
                <a :href="exportUrl" class="btn btn-light btn-sm"><i class="mdi mdi-download me-1"></i>Export</a>
            </div>
        </div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-2"><label class="form-label">Dari</label><input v-model="form.from" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label class="form-label">Sampai</label><input v-model="form.to" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label class="form-label">Channel</label><select v-model="form.channel" class="form-select form-select-sm"><option value="">Semua</option><option value="pos">POS</option><option value="ecom">Ecommerce</option></select></div>
                    <div class="col-md-3"><label class="form-label">Store</label><SearchableSelect v-model="form.store_id" :options="storeOptions" placeholder="Semua Store" search-placeholder="Cari store..." /></div>
                    <div class="col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary" type="submit">Filter</button></div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div v-for="card in cards" :key="card.label" class="col-6 col-md-4 col-xl-2">
                <div class="card report-card h-100"><div class="card-body"><div class="report-label">{{ card.label }}</div><div class="report-value">{{ card.value }}</div></div></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="card report-card h-100">
                    <div class="card-header"><h6 class="mb-0">Top Pembeli</h6></div>
                    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Customer</th><th class="text-end">Transaksi</th><th class="text-end">Belanja</th></tr></thead><tbody>
                        <tr v-for="row in purchaseCustomers" :key="row.customer_id"><td>{{ row.customer_name ?? '-' }}</td><td class="text-end">{{ formatNumber(topValue(row, ['total_purchases', 'total_transactions'])) }}</td><td class="text-end">{{ formatCurrency(topValue(row, ['total_spending', 'total_amount'])) }}</td></tr>
                        <tr v-if="purchaseCustomers.length === 0"><td colspan="3" class="report-empty">Belum ada data.</td></tr>
                    </tbody></table></div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card report-card h-100">
                    <div class="card-header"><h6 class="mb-0">Top Kunjungan</h6></div>
                    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Customer</th><th class="text-end">Kunjungan</th></tr></thead><tbody>
                        <tr v-for="row in visitCustomers" :key="row.customer_id"><td>{{ row.customer_name ?? '-' }}</td><td class="text-end">{{ formatNumber(topValue(row, ['total_visits'])) }}</td></tr>
                        <tr v-if="visitCustomers.length === 0"><td colspan="2" class="report-empty">Belum ada data.</td></tr>
                    </tbody></table></div>
                </div>
            </div>
            <div class="col-12">
                <div class="card report-card">
                    <div class="card-header"><h6 class="mb-0">Omset Cabang</h6></div>
                    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Store</th><th class="text-end">Store Omset</th><th class="text-end">Ecommerce Omset</th><th class="text-end">Total</th></tr></thead><tbody>
                        <tr v-for="row in branchOmsetRows" :key="row.store_id ?? row.store_name"><td>{{ row.store_name ?? '-' }}</td><td class="text-end">{{ formatCurrency(row.store_omset ?? 0) }}</td><td class="text-end">{{ formatCurrency(row.ecommerce_omset ?? 0) }}</td><td class="text-end fw-bold">{{ formatCurrency(row.total_omset ?? 0) }}</td></tr>
                        <tr v-if="branchOmsetRows.length === 0"><td colspan="4" class="report-empty">Belum ada data.</td></tr>
                    </tbody></table></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.report-page { --report-ink: var(--app-ink); --report-border: var(--app-border); --report-muted: var(--app-muted); color: var(--report-ink); }
.report-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--report-ink); color: #fff; }
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.report-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.report-header p { margin: 0; color: var(--app-hero-muted); }
.report-card { border: 1px solid var(--report-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.report-card th { color: var(--report-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.report-label { color: var(--report-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.report-value { margin-top: 0.3rem; font-size: 1.2rem; font-weight: 800; }
.report-empty { padding: 2rem 1rem; color: var(--report-muted); text-align: center; }
</style>
