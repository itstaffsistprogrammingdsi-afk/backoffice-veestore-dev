<script setup lang="ts">
import { computed, reactive } from 'vue';
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
    insights: Array<Record<string, any>>;
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

const apply = () => {
    router.get(props.urls.index, {
        from: form.from || undefined,
        to: form.to || undefined,
        channel: form.channel || undefined,
        store_id: form.store_id || undefined,
        warehouse_id: form.warehouse_id || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const formatNumber = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const formatCurrency = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value ?? 0));

const cards = computed(() => [
    { label: 'GMV', value: formatCurrency(props.gmv) },
    { label: 'Order', value: formatNumber(props.orderCount) },
    { label: 'AOV', value: formatCurrency(props.aov) },
    { label: 'Pembeli', value: formatNumber(props.buyers) },
    { label: 'Member', value: formatNumber(props.memberSummary?.total_member ?? 0) },
    { label: 'Kunjungan', value: formatNumber(props.visitSummary?.total ?? 0) },
    { label: 'Stok On Hand', value: formatNumber(props.wms?.on_hand ?? 0) },
    { label: 'Low Stock', value: formatNumber((props.lowStockWarehouse?.length ?? 0) + (props.lowStockStore?.length ?? 0)) },
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
            <div class="dash-range">{{ props.from }} sampai {{ props.to }}</div>
        </div>

        <div class="card dash-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="apply">
                    <div class="col-md-2"><label class="form-label">Dari</label><input v-model="form.from" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label class="form-label">Sampai</label><input v-model="form.to" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label class="form-label">Channel</label><select v-model="form.channel" class="form-select form-select-sm"><option value="all">Semua</option><option value="store">Store</option><option value="ecommerce">Ecommerce</option></select></div>
                    <div class="col-md-2"><label class="form-label">Store</label><SearchableSelect v-model="form.store_id" :options="storeOptions" placeholder="Semua store" search-placeholder="Cari store..." /></div>
                    <div class="col-md-2"><label class="form-label">Warehouse</label><SearchableSelect v-model="form.warehouse_id" :options="warehouseOptions" placeholder="Semua warehouse" search-placeholder="Cari warehouse..." /></div>
                    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100" type="submit"><i class="mdi mdi-filter me-1"></i>Terapkan</button></div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div v-for="card in cards" :key="card.label" class="col-6 col-md-4 col-xl-3">
                <div class="card dash-card h-100"><div class="card-body"><div class="dash-label">{{ card.label }}</div><div class="dash-value">{{ card.value }}</div></div></div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-6">
                <div class="card dash-card h-100">
                    <div class="card-header"><h6 class="mb-0">Penjualan per Channel</h6></div>
                    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Channel</th><th class="text-end">Order</th><th class="text-end">GMV</th></tr></thead><tbody>
                        <tr v-for="row in channelSalesRows" :key="row.label"><td>{{ row.label }}</td><td class="text-end">{{ formatNumber(row.orders) }}</td><td class="text-end">{{ formatCurrency(row.gmv) }}</td></tr>
                        <tr v-if="channelSalesRows.length === 0"><td colspan="3" class="dash-empty">Belum ada data.</td></tr>
                    </tbody></table></div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card dash-card h-100">
                    <div class="card-header"><h6 class="mb-0">Status Order</h6></div>
                    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Channel</th><th>Status</th><th class="text-end">Total</th></tr></thead><tbody>
                        <tr v-for="(row, index) in orderStatusRows" :key="index"><td>{{ row.channel }}</td><td>{{ row.status }}</td><td class="text-end">{{ formatNumber(row.total) }}</td></tr>
                        <tr v-if="orderStatusRows.length === 0"><td colspan="3" class="dash-empty">Belum ada data.</td></tr>
                    </tbody></table></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <div class="card dash-card h-100">
                    <div class="card-header"><h6 class="mb-0">GMV per Store</h6></div>
                    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Store</th><th class="text-end">Order</th><th class="text-end">Buyer</th><th class="text-end">GMV</th></tr></thead><tbody>
                        <tr v-for="(row, index) in gmvPerStore" :key="index"><td>{{ row.store_name }}</td><td class="text-end">{{ formatNumber(row.orders) }}</td><td class="text-end">{{ formatNumber(row.buyers) }}</td><td class="text-end">{{ formatCurrency(row.gmv) }}</td></tr>
                        <tr v-if="gmvPerStore.length === 0"><td colspan="4" class="dash-empty">Belum ada data.</td></tr>
                    </tbody></table></div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card dash-card h-100">
                    <div class="card-header"><h6 class="mb-0">Action Items</h6></div>
                    <div class="card-body">
                        <div v-for="(item, index) in actionItems" :key="index" class="dash-action" :class="`dash-action-${item.type ?? 'info'}`">
                            <strong>{{ item.title ?? 'Perhatian' }}</strong>
                            <p>{{ item.message ?? '' }}</p>
                        </div>
                        <div v-if="actionItems.length === 0" class="dash-empty">Tidak ada action item.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card dash-card">
            <div class="card-header"><h6 class="mb-0">Insights</h6></div>
            <div class="card-body">
                <div v-for="(insight, index) in insights" :key="index" class="dash-insight">
                    <strong>{{ insight.title ?? 'Insight' }}</strong>
                    <p>{{ insight.message ?? '' }}</p>
                </div>
                <div v-if="insights.length === 0" class="dash-empty">Belum ada insight.</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dash-page { color: var(--app-ink); }
.dash-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.dash-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.dash-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; color: var(--app-hero-text); }
.dash-header p { margin: 0; color: var(--app-hero-muted); }
.dash-range { color: var(--app-hero-muted); font-size: 0.85rem; }
.dash-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.dash-card th { color: var(--app-muted); font-size: 0.72rem; letter-spacing: 0.04em; text-transform: uppercase; }
.dash-label { color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.dash-value { margin-top: 0.3rem; font-size: 1.2rem; font-weight: 800; }
.dash-action { padding: 0.75rem; margin-bottom: 0.6rem; border-radius: 0.6rem; background: var(--app-surface-2); border-left: 3px solid var(--app-accent); }
.dash-action p, .dash-insight p { margin: 0.25rem 0 0; color: var(--app-muted); font-size: 0.85rem; }
.dash-action-danger { border-left-color: var(--app-danger-ink); }
.dash-action-warning { border-left-color: var(--app-warning-ink); }
.dash-insight { padding: 0.75rem 0; border-bottom: 1px solid var(--app-border); }
.dash-insight:last-child { border-bottom: 0; }
.dash-empty { padding: 2rem; color: var(--app-muted); text-align: center; }
</style>
