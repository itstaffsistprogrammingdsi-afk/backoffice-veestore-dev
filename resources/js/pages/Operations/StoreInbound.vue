<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';

defineOptions({ layout: AppLayout });

interface ProductDetail {
    product_code: string;
    product_name: string;
    qty: number;
    qc_checked: number;
    qc_pending: number;
    qty_good: number;
    qty_bad: number;
}

interface SkuDocument {
    id: string;
    reference: string;
    type_label: string;
    warehouse_name: string;
    date: string | null;
}

interface SkuRow {
    product_id: string;
    product_code: string;
    product_name: string;
    qty: number;
    document_count: number;
    last_received_at: string | null;
    documents: SkuDocument[];
}

interface InboundRow {
    id: string;
    store_id: string;
    store_name: string;
    store_code: string;
    warehouse_source_id: string | null;
    warehouse_source_name: string;
    warehouse_source_code: string;
    invoice_number: string | null;
    delivery_order_number: string | null;
    received_date: string | null;
    created_at: string;
    qc_status: string | null;
    status_label: string;
    total_sku: number;
    total_items: number;
    qc_checked_items: number;
    qc_pending_items: number;
    qty_good: number;
    qty_bad: number;
    product_details: ProductDetail[];
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<InboundRow>;
    summary: {
        total_inbounds: number;
        pending_qc: number;
        received_today: number;
        total_qty: number;
        total_sku: number;
    };
    skuRows: SkuRow[];
    stores: Array<{ id: string; name: string; code: string | null }>;
    warehouses: Array<{ id: string; name: string; code: string | null }>;
    statusOptions: Array<{ value: string; label: string }>;
    filters: {
        q: string;
        store_id: string | null;
        warehouse_id: string | null;
        status: string | null;
        from: string | null;
        to: string | null;
    };
    urls: {
        index: string;
        stock: string;
        outbound: string;
        monitoring: string;
    };
}

const props = defineProps<Props>();

const filters = reactive({
    q: props.filters.q ?? '',
    store_id: props.filters.store_id ?? '',
    warehouse_id: props.filters.warehouse_id ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const expanded = ref<Record<string, boolean>>({});
const skuDocsModal = ref<{ sku: SkuRow } | null>(null);

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);
const formatDate = (value: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const statusClass = (status: string | null) => ({
    all_passed: 'bg-label-success',
    partial_passed: 'bg-label-info',
    pending: 'bg-label-warning',
    all_failed: 'bg-label-danger',
}[status ?? 'pending'] ?? 'bg-label-warning');

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        store_id: filters.store_id || undefined,
        warehouse_id: filters.warehouse_id || undefined,
        status: filters.status || undefined,
        from: filters.from || undefined,
        to: filters.to || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.q = '';
    filters.store_id = '';
    filters.warehouse_id = '';
    filters.status = '';
    filters.from = '';
    filters.to = '';
    applyFilters();
};

const toggleDetail = (id: string) => {
    expanded.value[id] = !expanded.value[id];
};

const openSkuDocs = (sku: SkuRow) => {
    skuDocsModal.value = { sku };
};

const closeSkuDocs = () => {
    skuDocsModal.value = null;
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && skuDocsModal.value) {
        closeSkuDocs();
    }
};

watch(skuDocsModal, (value) => {
    document.body.style.overflow = value ? 'hidden' : '';
});

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Head title="Store Inbound Operations" />
    <div class="inbound-page">
        <section class="report-header inbound-header">
            <div>
                <span class="inbound-eyebrow">Operations / Store</span>
                <h1>Store Inbound Operations</h1>
                <p>Pantau dokumen masuk dari warehouse ke store, status QC, dan detail barang yang diterima.</p>
            </div>
            <div class="inbound-hero-actions">
                <Link :href="props.urls.stock" class="inbound-hero-button">
                    <i class="mdi mdi-clipboard-list-outline me-1"></i>
                    Store Stock Operations
                </Link>
                <Link :href="props.urls.outbound" class="inbound-hero-button">
                    <i class="mdi mdi-tray-arrow-up me-1"></i>
                    Store Outbound
                </Link>
                <Link :href="props.urls.monitoring" class="inbound-hero-button inbound-hero-button-outline">
                    <i class="mdi mdi-chart-box-outline me-1"></i>
                    Monitoring Store Inbound
                </Link>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl">
                <div class="inbound-summary"><span class="inbound-summary-icon"><i class="mdi mdi-tray-arrow-down"></i></span><div><span>Total Inbound</span><strong>{{ formatNumber(props.summary.total_inbounds) }}</strong></div></div>
            </div>
            <div class="col-6 col-xl">
                <div class="inbound-summary inbound-summary-warning"><span class="inbound-summary-icon"><i class="mdi mdi-shield-alert-outline"></i></span><div><span>Menunggu QC</span><strong>{{ formatNumber(props.summary.pending_qc) }}</strong></div></div>
            </div>
            <div class="col-6 col-xl">
                <div class="inbound-summary"><span class="inbound-summary-icon"><i class="mdi mdi-check-circle-outline"></i></span><div><span>Diterima Hari Ini</span><strong>{{ formatNumber(props.summary.received_today) }}</strong></div></div>
            </div>
            <div class="col-6 col-xl">
                <div class="inbound-summary"><span class="inbound-summary-icon"><i class="mdi mdi-package-variant-closed"></i></span><div><span>Total Qty Masuk</span><strong>{{ formatNumber(props.summary.total_qty) }}</strong></div></div>
            </div>
            <div class="col-6 col-xl">
                <div class="inbound-summary"><span class="inbound-summary-icon"><i class="mdi mdi-barcode"></i></span><div><span>Total SKU Masuk</span><strong>{{ formatNumber(props.summary.total_sku) }}</strong></div></div>
            </div>
        </div>

        <div class="card inbound-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Cari dokumen, store, atau SKU</label>
                        <input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Invoice, DO, store, warehouse, atau SKU..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Store</label>
                        <select v-model="filters.store_id" class="form-select form-select-sm">
                            <option value="">Semua store</option>
                            <option v-for="store in props.stores" :key="store.id" :value="store.id">{{ store.name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Warehouse Asal</label>
                        <select v-model="filters.warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua warehouse</option>
                            <option v-for="warehouse in props.warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-lg-2">
                        <label class="form-label">Status QC</label>
                        <select v-model="filters.status" class="form-select form-select-sm">
                            <option v-for="option in props.statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input v-model="filters.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input v-model="filters.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-12 col-md-4 col-lg-6 d-flex gap-2">
                        <button class="btn btn-sm btn-primary flex-grow-1" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card inbound-card">
            <div class="inbound-card-header">
                <div><h2>Dokumen Store Inbound</h2><p>Data operasional inbound toko. Detail laporan tetap ada di Monitoring.</p></div>
                <span class="inbound-card-meta">{{ formatNumber(props.rows.total) }} dokumen</span>
            </div>
            <div v-if="props.rows.data.length === 0" class="inbound-empty">
                <i class="mdi mdi-tray-arrow-down"></i><strong>Tidak ada dokumen store inbound</strong><span>Coba ubah kata kunci atau filter.</span>
            </div>
            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 inbound-table">
                    <thead><tr><th>Referensi Inbound</th><th>Store</th><th>Warehouse Asal</th><th class="text-end">Item</th><th class="text-end">Good</th><th class="text-end">Bad</th><th>Tanggal</th><th>Status QC</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <template v-for="row in props.rows.data" :key="row.id">
                            <tr>
                                <td><div class="inbound-main"><strong>{{ row.invoice_number || row.delivery_order_number || 'Tanpa nomor' }}</strong><small>{{ row.qc_checked_items }} checked &middot; {{ row.qc_pending_items }} pending</small><small class="inbound-mobile-meta d-md-none"><span class="badge" :class="statusClass(row.qc_status)">{{ row.status_label }}</span><span>Good {{ formatNumber(row.qty_good) }}</span><span>Bad {{ formatNumber(row.qty_bad) }}</span></small></div></td>
                                <td><strong>{{ row.store_name }}</strong><small class="inbound-subvalue">{{ row.store_code }}</small></td>
                                <td><strong>{{ row.warehouse_source_name }}</strong><small class="inbound-subvalue">{{ row.warehouse_source_code }}</small></td>
                                <td class="text-end"><strong>{{ formatNumber(row.total_items) }}</strong><small class="inbound-subvalue">{{ formatNumber(row.total_sku) }} SKU</small></td>
                                <td class="text-end">{{ formatNumber(row.qty_good) }}</td>
                                <td class="text-end">{{ formatNumber(row.qty_bad) }}</td>
                                <td><strong>{{ formatDate(row.received_date || row.created_at) }}</strong><small class="inbound-subvalue">{{ row.received_date ? 'Diterima' : 'Dibuat' }}</small></td>
                                <td><span class="badge" :class="statusClass(row.qc_status)">{{ row.status_label }}</span></td>
                                <td class="text-end"><button class="btn btn-sm btn-outline-secondary" type="button" @click="toggleDetail(row.id)"><i class="mdi" :class="expanded[row.id] ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>{{ expanded[row.id] ? 'Tutup' : 'Detail' }}</button></td>
                            </tr>
                            <tr v-if="expanded[row.id]" class="inbound-detail-row">
                                <td colspan="9"><div class="inbound-detail-wrap"><table class="table table-sm mb-0 inbound-detail-table"><thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">QC Checked</th><th class="text-end">QC Pending</th><th class="text-end">Good</th><th class="text-end">Bad</th></tr></thead><tbody><tr v-for="detail in row.product_details" :key="`${row.id}-${detail.product_code}`"><td><strong>{{ detail.product_name }}</strong><small>{{ detail.product_code }}</small></td><td class="text-end">{{ formatNumber(detail.qty) }}</td><td class="text-end">{{ formatNumber(detail.qc_checked) }}</td><td class="text-end">{{ formatNumber(detail.qc_pending) }}</td><td class="text-end">{{ formatNumber(detail.qty_good) }}</td><td class="text-end">{{ formatNumber(detail.qty_bad) }}</td></tr><tr v-if="row.product_details.length === 0"><td colspan="6" class="text-center text-muted py-3">Tidak ada detail produk.</td></tr></tbody></table></div></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="inbound-footer"><small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} dokumen</small><PaginationLinks :links="props.rows.links" /></div>
        </div>

        <details class="card inbound-card inbound-sku-card mt-3">
            <summary class="inbound-card-header inbound-sku-summary">
                <div><h2>SKU Masuk</h2><p>Rincian qty per SKU. Klik angka referensi untuk melihat invoice, DO, atau batch inbound yang memuat SKU tersebut.</p></div>
                <span class="inbound-card-meta">{{ formatNumber(props.skuRows.length) }} SKU <i class="mdi mdi-chevron-down inbound-sku-chevron"></i></span>
            </summary>
            <div v-if="props.skuRows.length === 0" class="inbound-empty"><i class="mdi mdi-barcode-off"></i><strong>Tidak ada SKU masuk</strong><span>Coba ubah kata kunci atau filter.</span></div>
            <div v-else class="table-responsive"><table class="table align-middle mb-0 inbound-table"><thead><tr><th>Produk</th><th class="text-end">Total Qty Masuk</th><th class="text-end">Referensi</th><th>Terakhir Diterima</th></tr></thead><tbody><tr v-for="sku in props.skuRows" :key="sku.product_id"><td><div class="inbound-main"><strong>{{ sku.product_name }}</strong><small>{{ sku.product_code }}</small></div></td><td class="text-end"><strong>{{ formatNumber(sku.qty) }}</strong></td><td class="text-end"><button class="stock-doc-link" type="button" @click="openSkuDocs(sku)">{{ formatNumber(sku.document_count) }}</button></td><td>{{ formatDate(sku.last_received_at) }}</td></tr></tbody></table></div>
        </details>
    </div>

    <Teleport to="body">
        <div v-if="skuDocsModal" class="sku-docs-backdrop" @click.self="closeSkuDocs">
            <div class="sku-docs-modal" role="dialog" aria-modal="true" aria-labelledby="sku-docs-title">
                <div class="sku-docs-header"><div><h5 id="sku-docs-title" class="mb-0">Referensi SKU Masuk</h5><small>{{ skuDocsModal.sku.product_name }} &middot; {{ skuDocsModal.sku.product_code }}</small></div><button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup dokumen SKU" @click="closeSkuDocs"><i class="mdi mdi-close"></i></button></div>
                <div class="sku-docs-body"><p class="text-muted small">Referensi menampilkan invoice, delivery order, atau batch store inbound yang memuat SKU ini.</p><div class="table-responsive"><table class="table table-sm mb-0 inbound-detail-table"><thead><tr><th>Referensi Inbound</th><th>Tipe</th><th>Warehouse Asal</th><th>Tanggal</th></tr></thead><tbody><tr v-for="document in skuDocsModal.sku.documents" :key="document.id"><td>{{ document.reference }}</td><td>{{ document.type_label }}</td><td>{{ document.warehouse_name }}</td><td>{{ formatDate(document.date) }}</td></tr></tbody></table></div></div>
                <div class="sku-docs-footer"><button class="btn btn-sm btn-outline-secondary" type="button" @click="closeSkuDocs">Tutup</button></div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.inbound-page { color: var(--app-ink); }
.inbound-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; }
.inbound-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.inbound-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.inbound-header p { max-width: 760px; margin: 0; color: var(--app-hero-muted) !important; }
.inbound-hero-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.inbound-hero-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface); color: var(--app-ink); font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.inbound-hero-button-outline { border: 1px solid var(--app-hero-muted); background: transparent; color: var(--app-hero-text); }
.inbound-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.inbound-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.inbound-summary span:not(.inbound-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.inbound-summary strong { display: block; margin-top: 0.15rem; font-size: 1.35rem; }
.inbound-summary-warning .inbound-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.inbound-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.inbound-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.inbound-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.inbound-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.inbound-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.inbound-table th { padding: 0.6rem 0.9rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.inbound-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); color: var(--app-ink); }
.inbound-main strong, .inbound-main small, .inbound-subvalue { display: block; }
.inbound-main strong { font-size: 0.86rem; }
.inbound-main small, .inbound-subvalue { color: var(--app-muted); font-size: 0.73rem; }
.inbound-main .inbound-mobile-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; margin-top: 0.3rem; }
.inbound-detail-row > td { padding: 0 !important; background: var(--app-surface-2); }
.inbound-detail-wrap { padding: 0.5rem 1rem 1rem 2.2rem; }
.inbound-detail-table th { padding: 0.45rem 0.55rem; background: transparent; font-size: 0.64rem; }
.inbound-detail-table td { padding: 0.55rem; font-size: 0.78rem; }
.inbound-detail-table td strong, .inbound-detail-table td small { display: block; }
.inbound-detail-table td small { color: var(--app-muted); font-size: 0.7rem; }
.inbound-sku-card { overflow: hidden; }
.inbound-sku-summary { cursor: pointer; list-style: none; }
.inbound-sku-summary::-webkit-details-marker { display: none; }
.inbound-sku-summary:focus-visible { outline: 2px solid var(--app-accent); outline-offset: -2px; }
.inbound-sku-chevron { margin-left: 0.35rem; transition: transform 160ms ease; }
.inbound-sku-card[open] .inbound-sku-chevron { transform: rotate(180deg); }
.inbound-sku-card:not([open]) .inbound-card-header { border-bottom: 0; }
.stock-doc-link { border: 0; background: transparent; color: var(--app-accent-ink); font-size: 0.8rem; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
.sku-docs-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: flex-start; justify-content: center; padding: min(8vh, 4rem) 1rem 1rem; overflow-y: auto; background: rgba(15, 23, 42, 0.62); }
.sku-docs-modal { width: min(680px, 100%); overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.28); color: var(--app-ink); }
.sku-docs-header, .sku-docs-footer { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; }
.sku-docs-header { border-bottom: 1px solid var(--app-border); }
.sku-docs-footer { justify-content: flex-end; border-top: 1px solid var(--app-border); background: var(--app-surface-2); }
.sku-docs-body { padding: 1rem 1.1rem; }
.inbound-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.inbound-empty { display: grid; place-items: center; gap: 0.3rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.inbound-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.inbound-empty strong { color: var(--app-ink); }
@media (max-width: 991.98px) {
    .inbound-header { align-items: flex-start; flex-direction: column; }
    .inbound-card-header { align-items: flex-start; flex-direction: column; }
    .inbound-detail-wrap { padding-left: 0.75rem; }
}
</style>
