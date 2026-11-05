<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

defineOptions({ layout: AppLayout });

interface ProductDetail {
    product_code: string;
    product_name: string;
    quantity: number;
    expired_date: string | null;
    price_idr: number;
    exchange_currency: string | null;
    exchange_rate: number | null;
    exchange_amount: number | null;
    item_count: number;
    qty_good: number;
    qty_bad: number;
    qty_other: number;
    last_qc_at: string | null;
    condition_summary: string;
    note: string | null;
}

interface InboundRow {
    id: string;
    warehouse_id: string;
    warehouse_name: string;
    warehouse_code: string;
    source_warehouse_id: string | null;
    source_warehouse_name: string;
    source_warehouse_code: string;
    supplier_id: string | null;
    supplier_name: string;
    store_return_id: string | null;
    store_return_number: string;
    return_store_name: string;
    inbound_type: string | null;
    inbound_type_label: string;
    invoice_number: string | null;
    delivery_order_number: string | null;
    batch_number: string | null;
    outbound_reference_number: string | null;
    quantity_item: number;
    grand_total: number;
    received_date: string | null;
    created_at: string;
    received_by_name: string;
    created_by_name: string;
    origin_name: string;
    origin_reference: string;
    total_sku: number;
    total_qty: number;
    item_count: number;
    qty_good: number;
    qty_bad: number;
    qty_other: number;
    last_qc_at: string | null;
    status: 'pending' | 'received';
    status_label: string;
    product_details: ProductDetail[];
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
        pending_inbounds: number;
        received_today: number;
        total_qty: number;
        total_sku: number;
        total_items: number;
        total_good: number;
        total_bad: number;
        total_other: number;
        total_value: number;
        total_warehouses: number;
        total_origins: number;
    };
    warehouses: Array<{ id: string; name: string; code: string | null }>;
    suppliers: Array<{ id: string; name: string }>;
    inboundTypes: Array<{ value: string; label: string }>;
    skuRows: SkuRow[];
    statusOptions: Array<{ value: string; label: string }>;
    filters: {
        q: string;
        warehouse_id: string | null;
        source_warehouse_id: string | null;
        status: string | null;
        supplier_id: string | null;
        inbound_type: string | null;
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

const warehouseOptions = computed(() => props.warehouses.map((warehouse) => ({
    value: warehouse.id,
    label: warehouse.name,
    hint: warehouse.code ?? undefined,
})));

const supplierOptions = computed(() => props.suppliers.map((supplier) => ({
    value: supplier.id,
    label: supplier.name,
})));

const filters = reactive({
    q: props.filters.q ?? '',
    warehouse_id: props.filters.warehouse_id ?? '',
    source_warehouse_id: props.filters.source_warehouse_id ?? '',
    status: props.filters.status ?? '',
    supplier_id: props.filters.supplier_id ?? '',
    inbound_type: props.filters.inbound_type ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const expanded = ref<Record<string, boolean>>({});
const skuDocsModal = ref<{ sku: SkuRow } | null>(null);

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);
const formatCurrency = (value: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value ?? 0);
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

const statusClass = (status: InboundRow['status']) => status === 'received' ? 'bg-label-success' : 'bg-label-warning';

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        warehouse_id: filters.warehouse_id || undefined,
        source_warehouse_id: filters.source_warehouse_id || undefined,
        status: filters.status || undefined,
        supplier_id: filters.supplier_id || undefined,
        inbound_type: filters.inbound_type || undefined,
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
    filters.warehouse_id = '';
    filters.source_warehouse_id = '';
    filters.status = '';
    filters.supplier_id = '';
    filters.inbound_type = '';
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
    <Head title="Operasional Barang Masuk" />
    <div class="inbound-page">
        <section class="report-header inbound-header">
            <div>
                <span class="inbound-eyebrow">Operations / Warehouse</span>
                <h1>Operasional Barang Masuk</h1>
                <p>Pantau dokumen masuk, status penerimaan, dan detail barang yang diterima gudang.</p>
            </div>
            <div class="inbound-hero-actions">
                <Link :href="props.urls.stock" class="inbound-hero-button">
                    <i class="mdi mdi-clipboard-list-outline me-1"></i>
                    Operasional Stok
                </Link>
                <Link :href="props.urls.outbound" class="inbound-hero-button">
                    <i class="mdi mdi-tray-arrow-up me-1"></i>
                    Outbound
                </Link>
                <Link :href="props.urls.monitoring" class="inbound-hero-button inbound-hero-button-outline">
                    <i class="mdi mdi-chart-box-outline me-1"></i>
                    Pemantauan Barang Masuk
                </Link>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl">
                <div class="inbound-summary">
                    <span class="inbound-summary-icon"><i class="mdi mdi-tray-arrow-down"></i></span>
                    <div><span>Total Inbound</span><strong>{{ formatNumber(props.summary.total_inbounds) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-xl">
                <div class="inbound-summary inbound-summary-warning">
                    <span class="inbound-summary-icon"><i class="mdi mdi-clock-outline"></i></span>
                    <div><span>Menunggu Penerimaan</span><strong>{{ formatNumber(props.summary.pending_inbounds) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-xl">
                <div class="inbound-summary">
                    <span class="inbound-summary-icon"><i class="mdi mdi-check-circle-outline"></i></span>
                    <div><span>Diterima Hari Ini</span><strong>{{ formatNumber(props.summary.received_today) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-xl">
                <div class="inbound-summary">
                    <span class="inbound-summary-icon"><i class="mdi mdi-package-variant-closed"></i></span>
                    <div><span>Total Qty Masuk</span><strong>{{ formatNumber(props.summary.total_qty) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-xl">
                <div class="inbound-summary">
                    <span class="inbound-summary-icon"><i class="mdi mdi-barcode"></i></span>
                    <div><span>Total SKU Masuk</span><strong>{{ formatNumber(props.summary.total_sku) }}</strong></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="inbound-summary">
                    <span class="inbound-summary-icon"><i class="mdi mdi-cash-multiple"></i></span>
                    <div><span>Total Nilai Inbound</span><strong class="inbound-summary-currency">{{ formatCurrency(props.summary.total_value) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="inbound-summary inbound-summary-success">
                    <span class="inbound-summary-icon"><i class="mdi mdi-check-decagram-outline"></i></span>
                    <div><span>Barang Baik</span><strong>{{ formatNumber(props.summary.total_good) }}</strong><small class="inbound-summary-note">{{ formatNumber(props.summary.total_items) }} unit tercatat</small></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="inbound-summary inbound-summary-danger">
                    <span class="inbound-summary-icon"><i class="mdi mdi-alert-octagon-outline"></i></span>
                    <div><span>Barang Rusak</span><strong>{{ formatNumber(props.summary.total_bad) }}</strong><small class="inbound-summary-note">{{ formatNumber(props.summary.total_other) }} kondisi lain</small></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="inbound-summary">
                    <span class="inbound-summary-icon"><i class="mdi mdi-source-branch"></i></span>
                    <div><span>Sumber & Tujuan</span><strong>{{ formatNumber(props.summary.total_origins) }} / {{ formatNumber(props.summary.total_warehouses) }}</strong><small class="inbound-summary-note">sumber / warehouse</small></div>
                </div>
            </div>
        </div>

        <div class="card inbound-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Cari dokumen atau SKU</label>
                        <input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Invoice, DO, batch, supplier, atau SKU..." />
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Warehouse Tujuan</label>
                        <SearchableSelect v-model="filters.warehouse_id" :options="warehouseOptions" placeholder="Semua warehouse" search-placeholder="Cari warehouse..." />
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Warehouse Asal</label>
                        <SearchableSelect v-model="filters.source_warehouse_id" :options="warehouseOptions" placeholder="Semua asal" search-placeholder="Cari warehouse asal..." />
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Supplier</label>
                        <SearchableSelect v-model="filters.supplier_id" :options="supplierOptions" placeholder="Semua supplier" search-placeholder="Cari supplier..." />
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Tipe Inbound</label>
                        <select v-model="filters.inbound_type" class="form-select form-select-sm">
                            <option value="">Semua tipe</option>
                            <option v-for="option in props.inboundTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Status</label>
                        <select v-model="filters.status" class="form-select form-select-sm">
                            <option v-for="option in props.statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Dari Tanggal</label>
                        <input v-model="filters.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Sampai Tanggal</label>
                        <input v-model="filters.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-12 col-lg-4 d-flex gap-2">
                        <button class="btn btn-sm btn-primary flex-grow-1" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card inbound-card">
            <div class="inbound-card-header">
                <div>
                    <h2>Dokumen Inbound</h2>
                    <p>Data operasional inbound, bukan laporan export. Detail laporan tetap ada di Monitoring.</p>
                </div>
                <span class="inbound-card-meta">{{ formatNumber(props.rows.total) }} dokumen</span>
            </div>

            <div v-if="props.rows.data.length === 0" class="inbound-empty">
                <i class="mdi mdi-tray-arrow-down"></i>
                <strong>Tidak ada dokumen inbound</strong>
                <span>Coba ubah kata kunci atau filter warehouse/status.</span>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 inbound-table">
                    <thead>
                        <tr>
                            <th>Referensi Inbound</th>
                            <th>Asal Inbound</th>
                            <th>Warehouse Tujuan</th>
                            <th class="text-end">Qty / Nilai</th>
                            <th>PIC Penerima</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="row in props.rows.data" :key="row.id">
                            <tr>
                                <td>
                                    <div class="inbound-main">
                                        <strong>{{ row.invoice_number || row.delivery_order_number || row.origin_reference }}</strong>
                                        <small>{{ row.inbound_type_label }}<template v-if="row.batch_number"> &middot; Batch {{ row.batch_number }}</template></small>
                                        <small v-if="row.delivery_order_number" class="inbound-subvalue">DO: {{ row.delivery_order_number }}</small>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ row.origin_name }}</strong>
                                    <small class="inbound-subvalue">{{ row.origin_reference }}</small>
                                    <small v-if="row.store_return_number !== '-'" class="inbound-subvalue">Retur: {{ row.store_return_number }}</small>
                                </td>
                                <td>
                                    <strong>{{ row.warehouse_name }}</strong>
                                    <small class="inbound-subvalue">{{ row.warehouse_code }}</small>
                                </td>
                                <td class="text-end">
                                    <strong>{{ formatNumber(row.total_qty) }}</strong>
                                    <small class="inbound-subvalue">{{ formatNumber(row.total_sku) }} SKU &middot; {{ formatCurrency(row.grand_total) }}</small>
                                </td>
                                <td>
                                    <strong>{{ row.received_by_name || '-' }}</strong>
                                    <small class="inbound-subvalue">Dibuat: {{ row.created_by_name || '-' }}</small>
                                </td>
                                <td>
                                    <strong>{{ formatDate(row.received_date || row.created_at) }}</strong>
                                    <small class="inbound-subvalue">{{ row.received_date ? 'Diterima' : 'Dibuat' }}</small>
                                </td>
                                <td>
                                    <span class="badge" :class="statusClass(row.status)">{{ row.status_label }}</span>
                                    <small class="inbound-subvalue">{{ formatNumber(row.qty_good) }} baik / {{ formatNumber(row.qty_bad) }} rusak</small>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="toggleDetail(row.id)">
                                        <i class="mdi" :class="expanded[row.id] ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>
                                        {{ expanded[row.id] ? 'Tutup' : 'Detail' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expanded[row.id]" class="inbound-detail-row">
                                <td colspan="8">
                                    <div class="inbound-detail-wrap">
                                        <div class="inbound-detail-meta">
                                            <span><b>Supplier:</b> {{ row.supplier_name }}</span>
                                            <span><b>Warehouse asal:</b> {{ row.source_warehouse_name }}</span>
                                            <span><b>Retur store:</b> {{ row.store_return_number }}</span>
                                            <span><b>Invoice:</b> {{ row.invoice_number || '-' }}</span>
                                            <span><b>DO:</b> {{ row.delivery_order_number || '-' }}</span>
                                            <span><b>Batch:</b> {{ row.batch_number || '-' }}</span>
                                            <span><b>Referensi keluar:</b> {{ row.outbound_reference_number || '-' }}</span>
                                            <span><b>Qty header:</b> {{ formatNumber(row.quantity_item) }}</span>
                                            <span><b>Nilai:</b> {{ formatCurrency(row.grand_total) }}</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0 inbound-detail-table">
                                                <thead>
                                                    <tr>
                                                        <th>Produk</th>
                                                        <th class="text-end">Qty</th>
                                                        <th class="text-end">Item</th>
                                                        <th class="text-end">Good</th>
                                                        <th class="text-end">Bad</th>
                                                        <th class="text-end">Lain</th>
                                                        <th>Expired</th>
                                                        <th class="text-end">Harga IDR</th>
                                                        <th>Kondisi</th>
                                                        <th>Catatan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="detail in row.product_details" :key="`${row.id}-${detail.product_code}`">
                                                        <td><strong>{{ detail.product_name }}</strong><small>{{ detail.product_code }}</small></td>
                                                        <td class="text-end">{{ formatNumber(detail.quantity) }}</td>
                                                        <td class="text-end">{{ formatNumber(detail.item_count) }}</td>
                                                        <td class="text-end">{{ formatNumber(detail.qty_good) }}</td>
                                                        <td class="text-end">{{ formatNumber(detail.qty_bad) }}</td>
                                                        <td class="text-end">{{ formatNumber(detail.qty_other) }}</td>
                                                        <td>{{ detail.expired_date ? formatDate(detail.expired_date) : '-' }}</td>
                                                        <td class="text-end">{{ formatCurrency(detail.price_idr) }}</td>
                                                        <td>
                                                            <span>{{ detail.condition_summary }}</span>
                                                            <small v-if="detail.last_qc_at">QC {{ formatDate(detail.last_qc_at) }}</small>
                                                        </td>
                                                        <td>{{ detail.note || '-' }}</td>
                                                    </tr>
                                                    <tr v-if="row.product_details.length === 0"><td colspan="10" class="text-center text-muted py-3">Tidak ada detail produk.</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="inbound-footer">
                <small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} dokumen</small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </div>

        <details class="card inbound-card inbound-sku-card mt-3">
            <summary class="inbound-card-header inbound-sku-summary">
                <div>
                    <h2>SKU Masuk</h2>
                    <p>Rincian qty per SKU. Klik angka referensi untuk melihat invoice, DO, atau batch inbound yang memuat SKU tersebut.</p>
                </div>
                <span class="inbound-card-meta">
                    {{ formatNumber(props.skuRows.length) }} SKU
                    <i class="mdi mdi-chevron-down inbound-sku-chevron"></i>
                </span>
            </summary>

            <div v-if="props.skuRows.length === 0" class="inbound-empty">
                <i class="mdi mdi-barcode-off"></i>
                <strong>Tidak ada SKU masuk</strong>
                <span>Coba ubah kata kunci atau filter.</span>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 inbound-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-end">Total Qty Masuk</th>
                            <th class="text-end">Referensi</th>
                            <th>Terakhir Diterima</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sku in props.skuRows" :key="sku.product_id">
                            <td>
                                <div class="inbound-main">
                                    <strong>{{ sku.product_name }}</strong>
                                    <small>{{ sku.product_code }}</small>
                                </div>
                            </td>
                            <td class="text-end"><strong>{{ formatNumber(sku.qty) }}</strong></td>
                            <td class="text-end"><button class="stock-doc-link" type="button" @click="openSkuDocs(sku)">{{ formatNumber(sku.document_count) }}</button></td>
                            <td>{{ formatDate(sku.last_received_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    </div>

    <Teleport to="body">
        <div v-if="skuDocsModal" class="sku-docs-backdrop" @click.self="closeSkuDocs">
            <div class="sku-docs-modal" role="dialog" aria-modal="true" aria-labelledby="sku-docs-title">
                <div class="sku-docs-header">
                    <div>
                        <h5 id="sku-docs-title" class="mb-0">Referensi SKU Masuk</h5>
                        <small>{{ skuDocsModal.sku.product_name }} &middot; {{ skuDocsModal.sku.product_code }}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup dokumen SKU" @click="closeSkuDocs"><i class="mdi mdi-close"></i></button>
                </div>
                <div class="sku-docs-body">
                    <p class="text-muted small">Referensi menampilkan invoice, delivery order, transfer, atau batch inbound yang memuat SKU ini.</p>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 inbound-detail-table">
                            <thead><tr><th>Referensi Inbound</th><th>Tipe</th><th>Warehouse</th><th>Tanggal</th></tr></thead>
                            <tbody>
                                <tr v-for="document in skuDocsModal.sku.documents" :key="document.id">
                                    <td>{{ document.reference }}</td>
                                    <td>{{ document.type_label }}</td>
                                    <td>{{ document.warehouse_name }}</td>
                                    <td>{{ formatDate(document.date) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="sku-docs-footer">
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="closeSkuDocs">Tutup</button>
                </div>
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
.inbound-summary-success .inbound-summary-icon { background: var(--app-success-bg); color: var(--app-success-ink); }
.inbound-summary-danger .inbound-summary-icon { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.inbound-summary-currency { font-size: 1rem !important; line-height: 1.35; }
.inbound-summary-note { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: 0.68rem; }
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
.inbound-detail-row > td { padding: 0 !important; background: var(--app-surface-2); }
.inbound-detail-wrap { padding: 0.75rem 1rem 1rem 2.2rem; }
.inbound-detail-meta { display: flex; flex-wrap: wrap; gap: 0.35rem 1.1rem; margin-bottom: 0.75rem; color: var(--app-muted); font-size: 0.74rem; }
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
