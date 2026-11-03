<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

defineOptions({ layout: AppLayout });

interface Props {
    roleName: string | null;
    filters: Record<string, any>;
    from: string;
    to: string;
    storeId: string | null;
    warehouseId: string | null;
    channel: string;
    autoRefresh: string | number | null;
    stores: Array<Record<string, any>>;
    warehouses: Array<Record<string, any>>;
    salesSummary: Record<string, any> | null;
    orderCount: number;
    gmv: number;
    aov: number;
    buyers: number;
    channelSalesRows: Array<Record<string, any>>;
    gmvPerStore: Array<Record<string, any>>;
    orderStatusRows: Array<Record<string, any>>;
    memberSummary: Record<string, any> | null;
    visitSummary: Record<string, any> | null;
    wms: Record<string, any> | null;
    lowStockWarehouse: Array<Record<string, any>>;
    lowStockStore: Array<Record<string, any>>;
    actionItems: Array<Record<string, any>>;
    urls: { index: string };
}

const props = defineProps<Props>();
const storeOptions = computed(() => props.stores.map((store) => ({
    value: String(store.id),
    label: String(store.name ?? '-'),
})));

const warehouseOptions = computed(() => props.warehouses.map((warehouse) => ({
    value: String(warehouse.id),
    label: String(warehouse.name ?? '-'),
})));


const form = reactive({
    from: props.from,
    to: props.to,
    channel: props.channel ?? 'all',
    store_id: props.storeId ?? '',
    warehouse_id: props.warehouseId ?? '',
});

const isApplying = ref(false);

const apply = () => {
    router.get(props.urls.index, {
        from: form.from || undefined,
        to: form.to || undefined,
        channel: form.channel || undefined,
        store_id: form.store_id || undefined,
        warehouse_id: form.warehouse_id || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => { isApplying.value = true; },
        onFinish: () => { isApplying.value = false; },
    });
};

const formatNumber = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const formatCurrency = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value ?? 0));

const formatDate = (value: string) => new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
}).format(new Date(`${value}T00:00:00`));

const salesMetrics = computed(() => [
    { label: 'Pesanan', value: formatNumber(props.orderCount) },
    { label: 'Nilai rata-rata pesanan', value: formatCurrency(props.aov) },
    { label: 'Pembeli', value: formatNumber(props.buyers) },
]);

const customerMetrics = computed(() => [
    { label: 'Member', value: formatNumber(props.memberSummary?.total_member ?? 0) },
    { label: 'Kunjungan toko', value: formatNumber(props.visitSummary?.total ?? 0) },
]);

const inventoryMetrics = computed(() => [
    { label: 'Stok tersedia', value: formatNumber(props.wms?.on_hand ?? 0) },
    { label: 'Item stok rendah', value: formatNumber((props.lowStockWarehouse?.length ?? 0) + (props.lowStockStore?.length ?? 0)) },
]);
</script>

<template>
    <Head title="Dashboard Analitik" />
    <div class="dash-page">
        <div class="dash-header">
            <div>
                <span class="dash-eyebrow">Laporan</span>
                <h1>Dashboard Analitik</h1>
                <p>Ringkasan performa penjualan, pelanggan, dan operasional.</p>
            </div>
            <div class="dash-range">{{ formatDate(props.from) }} sampai {{ formatDate(props.to) }}</div>
        </div>

        <div class="card dash-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="apply">
                    <div class="col-md-2"><label for="dashboard-from" class="form-label">Dari</label><input id="dashboard-from" v-model="form.from" class="form-control form-control-sm" type="date" :max="form.to || undefined" /></div>
                    <div class="col-md-2"><label for="dashboard-to" class="form-label">Sampai</label><input id="dashboard-to" v-model="form.to" class="form-control form-control-sm" type="date" :min="form.from || undefined" /></div>
                    <div class="col-md-2"><label for="dashboard-channel" class="form-label">Kanal</label><select id="dashboard-channel" v-model="form.channel" class="form-select form-select-sm"><option value="all">Semua kanal</option><option value="store">Toko</option><option value="ecommerce">E-commerce</option></select></div>
                    <div class="col-md-2"><label for="dashboard-store" class="form-label">Toko</label><SearchableSelect id="dashboard-store" aria-label="Toko" v-model="form.store_id" :options="storeOptions" placeholder="Semua toko" search-placeholder="Cari toko..." /></div>
                    <div class="col-md-2"><label for="dashboard-warehouse" class="form-label">Gudang</label><SearchableSelect id="dashboard-warehouse" aria-label="Gudang" v-model="form.warehouse_id" :options="warehouseOptions" placeholder="Semua gudang" search-placeholder="Cari gudang..." /></div>
                    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100" type="submit" :disabled="isApplying"><i class="mdi" :class="isApplying ? 'mdi-loading mdi-spin me-1' : 'mdi-filter me-1'" aria-hidden="true"></i>{{ isApplying ? 'Memuat...' : 'Terapkan' }}</button></div>
                </form>
            </div>
        </div>

        <section class="dash-overview" aria-label="Ringkasan">
            <article class="dash-gmv">
                <span class="dash-metric-label">GMV</span>
                <strong>{{ formatCurrency(props.gmv) }}</strong>
                <p>{{ formatNumber(props.orderCount) }} pesanan pada periode ini</p>
            </article>
            <div class="dash-sales-metrics" aria-label="Penjualan">
                <div v-for="metric in salesMetrics" :key="metric.label" class="dash-sales-metric">
                    <span>{{ metric.label }}</span>
                    <strong>{{ metric.value }}</strong>
                </div>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <section class="dash-support-card" aria-labelledby="dashboard-customer-title">
                    <h2 id="dashboard-customer-title">Pelanggan</h2>
                    <dl class="dash-support-metrics">
                        <div v-for="metric in customerMetrics" :key="metric.label">
                            <dt>{{ metric.label }}</dt>
                            <dd>{{ metric.value }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
            <div class="col-md-6">
                <section class="dash-support-card" aria-labelledby="dashboard-stock-title">
                    <h2 id="dashboard-stock-title">Stok</h2>
                    <dl class="dash-support-metrics">
                        <div v-for="metric in inventoryMetrics" :key="metric.label">
                            <dt>{{ metric.label }}</dt>
                            <dd>{{ metric.value }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-6">
                <div class="card dash-card h-100">
                    <div class="card-header"><h2 class="mb-0">Penjualan per kanal</h2></div>
                    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th scope="col">Kanal</th><th scope="col" class="text-end">Pesanan</th><th scope="col" class="text-end">GMV</th></tr></thead><tbody>
                        <tr v-for="row in channelSalesRows" :key="row.label"><td>{{ row.label }}</td><td class="text-end">{{ formatNumber(row.orders) }}</td><td class="text-end">{{ formatCurrency(row.gmv) }}</td></tr>
                        <tr v-if="channelSalesRows.length === 0"><td colspan="3" class="dash-empty">Tidak ada data untuk filter ini.</td></tr>
                    </tbody></table></div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card dash-card h-100">
                    <div class="card-header"><h2 class="mb-0">Status pesanan</h2></div>
                    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th scope="col">Kanal</th><th scope="col">Status</th><th scope="col" class="text-end">Jumlah</th></tr></thead><tbody>
                        <tr v-for="(row, index) in orderStatusRows" :key="index"><td>{{ row.channel }}</td><td>{{ row.status }}</td><td class="text-end">{{ formatNumber(row.total) }}</td></tr>
                        <tr v-if="orderStatusRows.length === 0"><td colspan="3" class="dash-empty">Tidak ada data untuk filter ini.</td></tr>
                    </tbody></table></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <div class="card dash-card h-100">
                    <div class="card-header"><h2 class="mb-0">GMV per toko</h2></div>
                    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th scope="col">Toko</th><th scope="col" class="text-end">Pesanan</th><th scope="col" class="text-end">Pembeli</th><th scope="col" class="text-end">GMV</th></tr></thead><tbody>
                        <tr v-for="(row, index) in gmvPerStore" :key="index"><td>{{ row.store_name }}</td><td class="text-end">{{ formatNumber(row.orders) }}</td><td class="text-end">{{ formatNumber(row.buyers) }}</td><td class="text-end">{{ formatCurrency(row.gmv) }}</td></tr>
                        <tr v-if="gmvPerStore.length === 0"><td colspan="4" class="dash-empty">Tidak ada data untuk filter ini.</td></tr>
                    </tbody></table></div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card dash-card h-100">
                    <div class="card-header"><h2 class="mb-0">Perlu ditindaklanjuti</h2></div>
                    <div class="card-body">
                        <div v-for="(item, index) in actionItems" :key="index" class="dash-action" :class="`dash-action-${item.type ?? 'info'}`">
                            <strong>{{ item.title ?? 'Perhatian' }}</strong>
                            <p>{{ item.message ?? '' }}</p>
                            <Link v-if="item.url" :href="item.url" class="dash-action-link">{{ item.action }}<i class="mdi mdi-arrow-right" aria-hidden="true"></i></Link>
                        </div>
                        <div v-if="actionItems.length === 0" class="dash-empty">Tidak ada item stok rendah pada filter ini.</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.dash-page { color: var(--app-ink); }
.dash-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.35rem 1.5rem; margin-bottom: 1rem; border-radius: 0.75rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.dash-eyebrow { display: inline-block; margin-bottom: 0.35rem; color: var(--app-hero-eyebrow); font-size: 0.82rem; font-weight: 700; }
.dash-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; color: var(--app-hero-text); }
.dash-header p { margin: 0; color: var(--app-hero-muted); }
.dash-range { color: var(--app-hero-muted); font-size: 0.85rem; white-space: nowrap; }
.dash-card { min-width: 0; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface); }
.dash-card h2 { font-size: 1rem; font-weight: 700; }
.dash-card th { color: var(--app-muted); font-size: 0.75rem; font-weight: 700; }
.dash-overview { display: grid; grid-template-columns: minmax(220px, 0.85fr) minmax(0, 2.15fr); gap: 1rem; margin-bottom: 1rem; }
.dash-gmv { display: flex; min-width: 0; flex-direction: column; justify-content: center; padding: 1.1rem 1.25rem; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface); }
.dash-metric-label { color: var(--app-muted); font-size: 0.8rem; font-weight: 700; }
.dash-gmv strong { margin-top: 0.2rem; color: var(--app-accent-ink); font-size: clamp(1.45rem, 2.5vw, 2rem); line-height: 1.2; overflow-wrap: anywhere; }
.dash-gmv p { margin: 0.4rem 0 0; color: var(--app-muted); font-size: 0.82rem; }
.dash-sales-metrics { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items: center; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface); }
.dash-sales-metric { min-width: 0; padding: 1rem; }
.dash-sales-metric + .dash-sales-metric { border-left: 1px solid var(--app-border); }
.dash-sales-metric span, .dash-support-metrics dt { display: block; color: var(--app-muted); font-size: 0.78rem; }
.dash-sales-metric strong, .dash-support-metrics dd { display: block; margin: 0.25rem 0 0; color: var(--app-ink); font-size: 1.05rem; font-weight: 700; overflow-wrap: anywhere; }
.dash-support-card { height: 100%; padding: 1rem 1.1rem; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface); }
.dash-support-card h2 { margin: 0 0 0.85rem; font-size: 0.95rem; font-weight: 700; }
.dash-support-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; margin: 0; }
.dash-action { padding: 0.8rem; margin-bottom: 0.65rem; border: 1px solid var(--app-border); border-radius: 0.55rem; background: var(--app-surface-2); }
.dash-action p { margin: 0.25rem 0 0; color: var(--app-muted); font-size: 0.85rem; }
.dash-action-warning { border-color: color-mix(in srgb, var(--app-warning-ink) 42%, var(--app-border)); }
.dash-action-link { display: inline-flex; min-height: 40px; align-items: center; gap: 0.35rem; margin-top: 0.25rem; color: var(--app-accent-ink); font-size: 0.85rem; font-weight: 700; text-decoration: none; }
.dash-empty { padding: 2rem; color: var(--app-muted); text-align: center; }

@media (max-width: 767.98px) {
    .dash-overview { grid-template-columns: 1fr; }
}

@media (max-width: 479.98px) {
    .dash-range { white-space: normal; }
    .dash-sales-metrics { grid-template-columns: 1fr; }
    .dash-sales-metric + .dash-sales-metric { border-top: 1px solid var(--app-border); border-left: 0; }
    .dash-support-metrics { grid-template-columns: 1fr; }
}
</style>
