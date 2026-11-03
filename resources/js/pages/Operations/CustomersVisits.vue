<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

defineOptions({ layout: AppLayout });

interface ProductRow { product_id: string; product_code: string; product_name: string; qty: number; spending: number }
interface VisitDetail {
    id: string;
    visit_code: string | null;
    visit_at: string | null;
    check_in_at: string | null;
    check_out_at: string | null;
    status: string | null;
}
interface CustomerRow {
    customer_id: string;
    customer_name: string;
    wa_number: string | null;
    email: string | null;
    membership_tier: string | null;
    loyalty_points: number;
    store_id: string;
    store_name: string;
    visit_count: number;
    last_visit_at: string | null;
    visits: VisitDetail[];
    purchase_count: number;
    total_spending: number;
    item_qty: number;
    products: ProductRow[];
}
interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}
interface Props {
    rows: Paginator<CustomerRow>;
    summary: {
        total_customers: number;
        total_visits: number;
        paid_transactions: number;
        total_spending: number;
    };
    stores: Array<{ id: string; name: string; code: string | null }>;
    filters: { q: string; store_id: string | null };
    urls: { index: string; manage_customers: string; store_stock: string };
}

const props = defineProps<Props>();
const filters = reactive({ q: props.filters.q ?? '', store_id: props.filters.store_id ?? '' });
const expanded = ref<Record<string, boolean>>({});

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
})));

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);
const formatCurrency = (value: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : '-';
const tierClass = (tier: string | null) => ({
    Bronze: 'bg-label-secondary',
    Silver: 'bg-label-info',
    Platinum: 'bg-label-primary',
}[tier ?? ''] ?? 'bg-label-secondary');
const visitStatusClass = (status: string | null) => ({
    completed: 'bg-label-success',
    active: 'bg-label-info',
    cancelled: 'bg-label-danger',
}[status ?? ''] ?? 'bg-label-secondary');

const applyFilters = () => {
    router.get(props.urls.index, { q: filters.q || undefined, store_id: filters.store_id || undefined }, { preserveState: true, preserveScroll: true, replace: true });
};
const resetFilters = () => { filters.q = ''; filters.store_id = ''; applyFilters(); };
const toggle = (id: string) => { expanded.value[id] = !expanded.value[id]; };
</script>

<template>
    <Head title="Customers & Visits" />
    <div class="customer-page">
        <section class="report-header customer-header">
            <div>
                <span class="customer-eyebrow">Operations / Store</span>
                <h1>Customers &amp; Visits</h1>
                <p>Riwayat kunjungan dan pembelian customer per store, termasuk item yang dibeli dan total spending.</p>
            </div>
            <div class="customer-hero-actions">
                <Link :href="props.urls.store_stock" class="customer-hero-button"><i class="mdi mdi-clipboard-list-outline me-1"></i>Store Stock Operations</Link>
                <Link :href="props.urls.manage_customers" class="customer-hero-button"><i class="mdi mdi-account-cog-outline me-1"></i>Manage Customers</Link>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3"><div class="customer-summary"><span class="customer-summary-icon"><i class="mdi mdi-account-group-outline"></i></span><div><span>Total Customer</span><strong>{{ formatNumber(props.summary.total_customers) }}</strong></div></div></div>
            <div class="col-6 col-xl-3"><div class="customer-summary"><span class="customer-summary-icon"><i class="mdi mdi-account-clock-outline"></i></span><div><span>Total Kunjungan</span><strong>{{ formatNumber(props.summary.total_visits) }}</strong></div></div></div>
            <div class="col-6 col-xl-3"><div class="customer-summary"><span class="customer-summary-icon"><i class="mdi mdi-cart-check"></i></span><div><span>Transaksi Lunas</span><strong>{{ formatNumber(props.summary.paid_transactions) }}</strong></div></div></div>
            <div class="col-6 col-xl-3"><div class="customer-summary customer-summary-accent"><span class="customer-summary-icon"><i class="mdi mdi-cash-multiple"></i></span><div><span>Total Spending</span><strong>{{ formatCurrency(props.summary.total_spending) }}</strong></div></div></div>
        </div>

        <div class="card customer-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-lg-7"><label class="form-label">Cari customer atau store</label><input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nama customer, WhatsApp, email, atau store..." /></div>
                    <div class="col-md-4 col-lg-3"><label class="form-label">Store</label><SearchableSelect v-model="filters.store_id" :options="storeOptions" placeholder="Semua store" search-placeholder="Cari store..." /></div>
                    <div class="col-md-6 col-lg-2 d-flex gap-2"><button class="btn btn-sm btn-primary flex-grow-1" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari</button><button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button></div>
                </form>
            </div>
        </div>

        <div class="card customer-card">
            <div class="customer-card-header"><div><h2>Customer per Store</h2><p>Klik detail untuk melihat SKU yang sudah dibeli customer di store tersebut.</p></div><span class="customer-card-meta">{{ formatNumber(props.rows.total) }} customer</span></div>
            <div v-if="props.rows.data.length === 0" class="customer-empty"><i class="mdi mdi-account-off-outline"></i><strong>Tidak ada customer</strong><span>Coba ubah kata kunci atau filter store.</span></div>
            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 customer-table">
                    <thead><tr><th>Customer</th><th>Store</th><th>Tier</th><th class="text-end">Poin</th><th class="text-end">Kunjungan</th><th>Terakhir</th><th class="text-end">Transaksi Lunas</th><th class="text-end">Item</th><th class="text-end">Total Spending</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <template v-for="row in props.rows.data" :key="`${row.customer_id}-${row.store_id}`">
                            <tr>
                                <td><div class="customer-main"><strong>{{ row.customer_name }}</strong><small>{{ row.wa_number || row.email || '-' }}</small><small class="customer-mobile-meta d-md-none"><span class="badge" :class="tierClass(row.membership_tier)">{{ row.membership_tier || '-' }}</span><span>{{ formatNumber(row.loyalty_points) }} poin</span></small></div></td>
                                <td><strong>{{ row.store_name }}</strong></td>
                                <td><span class="badge" :class="tierClass(row.membership_tier)">{{ row.membership_tier || '-' }}</span></td>
                                <td class="text-end">{{ formatNumber(row.loyalty_points) }}</td>
                                <td class="text-end">{{ formatNumber(row.visit_count) }}</td>
                                <td>{{ formatDate(row.last_visit_at) }}</td>
                                <td class="text-end">{{ formatNumber(row.purchase_count) }}</td>
                                <td class="text-end">{{ formatNumber(row.item_qty) }}</td>
                                <td class="text-end"><strong>{{ formatCurrency(row.total_spending) }}</strong></td>
                                <td class="text-end"><button class="btn btn-sm btn-outline-secondary" type="button" @click="toggle(`${row.customer_id}-${row.store_id}`)"><i class="mdi" :class="expanded[`${row.customer_id}-${row.store_id}`] ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>{{ expanded[`${row.customer_id}-${row.store_id}`] ? 'Tutup' : 'Detail' }}</button></td>
                            </tr>
                            <tr v-if="expanded[`${row.customer_id}-${row.store_id}`]" class="customer-detail-row">
                                <td colspan="10">
                                    <div class="customer-detail-wrap">
                                        <p class="mb-2"><strong>Riwayat kunjungan:</strong> {{ row.visits.length }} kunjungan terakhir</p>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm mb-0 customer-detail-table">
                                                <thead><tr><th>Tanggal</th><th>Kode Visit</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr></thead>
                                                <tbody>
                                                    <tr v-for="visit in row.visits" :key="visit.id">
                                                        <td>{{ formatDate(visit.visit_at) }}</td>
                                                        <td>{{ visit.visit_code || '-' }}</td>
                                                        <td>{{ formatDate(visit.check_in_at) }}</td>
                                                        <td>{{ formatDate(visit.check_out_at) }}</td>
                                                        <td><span class="badge" :class="visitStatusClass(visit.status)">{{ visit.status || '-' }}</span></td>
                                                    </tr>
                                                    <tr v-if="row.visits.length === 0"><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat kunjungan.</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <p class="mb-2"><strong>Barang yang sudah dibeli:</strong> {{ row.item_qty }} item dari {{ row.products.length }} SKU</p>
                                        <table class="table table-sm mb-0 customer-detail-table">
                                            <thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Nilai</th></tr></thead>
                                            <tbody>
                                                <tr v-for="product in row.products" :key="product.product_id"><td><strong>{{ product.product_name }}</strong><small>{{ product.product_code }}</small></td><td class="text-end">{{ formatNumber(product.qty) }}</td><td class="text-end">{{ formatCurrency(product.spending) }}</td></tr>
                                                <tr v-if="row.products.length === 0"><td colspan="3" class="text-center text-muted py-3">Belum ada pembelian.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="customer-footer"><small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} customer</small><PaginationLinks :links="props.rows.links" /></div>
        </div>
    </div>
</template>

<style scoped>
.customer-page { color: var(--app-ink); }
.customer-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; }
.customer-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.customer-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.customer-header p { max-width: 760px; margin: 0; color: var(--app-hero-muted) !important; }
.customer-hero-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.customer-hero-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface); color: var(--app-ink); font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.customer-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.customer-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.customer-summary span:not(.customer-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.customer-summary strong { display: block; margin-top: 0.15rem; font-size: 1.2rem; }
.customer-summary-accent .customer-summary-icon { background: var(--app-success-bg); color: var(--app-success-ink); }
.customer-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.customer-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.customer-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.customer-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.customer-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.customer-table th { padding: 0.6rem 0.9rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.customer-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); }
.customer-main strong, .customer-main small { display: block; }
.customer-main small { color: var(--app-muted); font-size: 0.73rem; }
.customer-main .customer-mobile-meta { display: flex; align-items: center; gap: 0.35rem; margin-top: 0.3rem; }
.customer-detail-row > td { padding: 0 !important; background: var(--app-surface-2); }
.customer-detail-wrap { padding: 0.8rem 1rem 1rem 2.2rem; }
.customer-detail-table th { padding: 0.45rem 0.55rem; background: transparent; font-size: 0.64rem; }
.customer-detail-table td { padding: 0.55rem; font-size: 0.78rem; }
.customer-detail-table td strong, .customer-detail-table td small { display: block; }
.customer-detail-table td small { color: var(--app-muted); font-size: 0.7rem; }
.customer-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.customer-empty { display: grid; place-items: center; gap: 0.3rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.customer-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.customer-empty strong { color: var(--app-ink); }
@media (max-width: 991.98px) {
    .customer-header { align-items: flex-start; flex-direction: column; }
    .customer-card-header { align-items: flex-start; flex-direction: column; }
    .customer-detail-wrap { padding-left: 0.75rem; }
}
</style>
