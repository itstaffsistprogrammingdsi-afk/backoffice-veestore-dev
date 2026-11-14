<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import ReportTableControls from '@/components/ui/ReportTableControls.vue';
import ReportPeriodPresets from '@/components/ui/ReportPeriodPresets.vue';
import { useReportTableColumns, useReportTableDensity } from '@/composables/useReportTable';

interface Option {
    id: string;
    name: string;
    code?: string | null;
}

interface Row {
    supplier_name: string | null;
    supplier_country: string | null;
    supplier_phone: string | null;
    product_name: string | null;
    product_codes: string | null;
    qty_inbound: string | number;
    stok_now: string | number;
    stok_good: string | number;
    stok_bad: string | number;
    stok_value: string | number;
    last_inbound_date: string | null;
}

interface ChartRow {
    label: string;
    total_products?: number;
    total_suppliers?: number;
    total_qty?: number;
    total?: number;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Summary {
    total_relations: number;
    total_suppliers: number;
    total_products: number;
    total_qty_inbound: number;
    total_stock: number;
    total_good: number;
    total_bad: number;
    total_stock_value: number;
    products_with_stock: number;
    products_without_stock: number;
}

interface Props {
    rows: Paginator<Row>;
    suppliers: Option[];
    products: Option[];
    summary: Summary;
    supplierChartRows: ChartRow[];
    productChartRows: ChartRow[];
    countryChartRows: ChartRow[];
    stockChartRows: ChartRow[];
    trendRows: Array<{ period: string; total_qty: number; total_inbounds: number }>;
    topSupplier: ChartRow | null;
    topProduct: ChartRow | null;
    topCountry: ChartRow | null;
    suppliersForProduct: Array<Record<string, unknown>>;
    filters: {
        supplier_id: string | null;
        product_id: string | null;
        keyword: string;
        date_from: string | null;
        date_to: string | null;
        has_stock: string | null;
        per_page: number;
    };
    urls: {
        index: string;
        export: string;
    };
}

const props = defineProps<Props>();

const supplierProductColumns = [
    { key: 'supplier_name', label: 'Pemasok' },
    { key: 'supplier_country', label: 'Negara' },
    { key: 'product_name', label: 'Produk' },
    { key: 'product_codes', label: 'Kode produk' },
    { key: 'qty_inbound', label: 'Qty masuk' },
    { key: 'stok_now', label: 'Stok saat ini' },
    { key: 'stok_good', label: 'Stok baik' },
    { key: 'stok_bad', label: 'Stok rusak' },
    { key: 'stok_value', label: 'Nilai stok' },
    { key: 'last_inbound_date', label: 'Terakhir diterima' },
];
const supplierProductSimpleColumns = ['supplier_name', 'product_name', 'qty_inbound', 'stok_now', 'stok_value'];
const { density, isCompact, toggleDensity } = useReportTableDensity();
const { visibleColumns, isVisible, toggleColumn, showAll, applyPreset, columnCount } = useReportTableColumns(
    'veestore.supplier-products.columns',
    supplierProductColumns.map((column) => column.key),
);

const form = reactive({
    supplier_id: props.filters.supplier_id ?? '',
    product_id: props.filters.product_id ?? '',
    keyword: props.filters.keyword ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    has_stock: props.filters.has_stock ?? '',
    per_page: props.filters.per_page ?? 25,
});

const supplierOptions = computed(() => props.suppliers.map((supplier) => ({
    value: supplier.id,
    label: supplier.name,
})));

const productOptions = computed(() => props.products.map((product) => ({
    value: product.id,
    label: `${product.code ? `${product.code} - ` : ''}${product.name}`,
})));

const query = computed(() => ({
    supplier_id: form.supplier_id || undefined,
    product_id: form.product_id || undefined,
    keyword: form.keyword || undefined,
    date_from: form.date_from || undefined,
    date_to: form.date_to || undefined,
    has_stock: form.has_stock || undefined,
    per_page: form.per_page || undefined,
}));

const applyFilters = () => {
    router.get(props.urls.index, query.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const reset = () => {
    form.supplier_id = '';
    form.product_id = '';
    form.keyword = '';
    form.date_from = '';
    form.date_to = '';
    form.has_stock = '';
    form.per_page = 25;
    applyFilters();
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(query.value)) {
        if (value) {
            params.set(key, String(value));
        }
    }

    const suffix = params.toString();

    return suffix ? `${props.urls.export}?${suffix}` : props.urls.export;
});

const formatNumber = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const formatCurrency = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const primaryCards = computed(() => [
    { label: 'Pemasok', value: formatNumber(props.summary.total_suppliers), hint: `${formatNumber(props.summary.total_relations)} relasi produk` },
    { label: 'Produk', value: formatNumber(props.summary.total_products), hint: `${formatNumber(props.summary.products_without_stock)} produk tanpa stok` },
    { label: 'Nilai stok', value: formatCurrency(props.summary.total_stock_value), hint: `${formatNumber(props.summary.total_stock)} unit stok saat ini` },
    { label: 'Qty masuk', value: formatNumber(props.summary.total_qty_inbound), hint: 'Akumulasi inbound pada periode filter' },
]);

const secondaryStats = computed(() => [
    { label: 'Stok baik', value: formatNumber(props.summary.total_good) },
    { label: 'Stok rusak', value: formatNumber(props.summary.total_bad) },
    { label: 'Produk ada stok', value: formatNumber(props.summary.products_with_stock) },
    { label: 'Relasi pemasok-produk', value: formatNumber(props.summary.total_relations) },
]);
</script>

<template>
    <Head title="Produk Pemasok" />

    <div class="report-page">
        <div class="report-header">
            <div>
                <span class="report-eyebrow">Laporan / Pemasok</span>
                <h1>Produk Pemasok</h1>
                <p>Hubungan pemasok, produk, inbound, dan stok saat ini.</p>
            </div>
            <a :href="exportUrl" class="btn btn-light btn-sm">
                <i class="mdi mdi-download me-1" aria-hidden="true"></i>
                Ekspor Excel
            </a>
        </div>

        <section class="supplier-summary" aria-labelledby="supplier-summary-title">
            <h2 id="supplier-summary-title" class="visually-hidden">Ringkasan produk pemasok</h2>
            <div class="supplier-summary-primary">
                <div v-for="card in primaryCards" :key="card.label" class="supplier-stat">
                    <span>{{ card.label }}</span>
                    <strong>{{ card.value }}</strong>
                    <small>{{ card.hint }}</small>
                </div>
            </div>
            <dl class="supplier-summary-secondary">
                <div v-for="stat in secondaryStats" :key="stat.label">
                    <dt>{{ stat.label }}</dt>
                    <dd>{{ stat.value }}</dd>
                </div>
            </dl>
        </section>

        <div class="card report-card mb-3">
            <div class="card-body">
                <ReportPeriodPresets
                    class="mb-2"
                    :route="props.urls.index"
                    from-key="date_from"
                    to-key="date_to"
                    :from="props.filters.date_from"
                    :to="props.filters.date_to"
                    :query="form"
                />
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-3">
                        <label for="supplier-filter" class="form-label">Pemasok</label>
                        <SearchableSelect
                            id="supplier-filter"
                            v-model="form.supplier_id"
                            :options="supplierOptions"
                            placeholder="Semua pemasok"
                            search-placeholder="Cari pemasok..."
                        />
                    </div>
                    <div class="col-md-3">
                        <label for="supplier-product-filter" class="form-label">Produk</label>
                        <SearchableSelect
                            id="supplier-product-filter"
                            v-model="form.product_id"
                            :options="productOptions"
                            placeholder="Semua produk"
                            search-placeholder="Cari nama atau kode produk..."
                        />
                    </div>
                    <div class="col-md-2">
                        <label for="supplier-date-from" class="form-label">Tanggal dari</label>
                        <input id="supplier-date-from" v-model="form.date_from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-md-2">
                        <label for="supplier-date-to" class="form-label">Tanggal sampai</label>
                        <input id="supplier-date-to" v-model="form.date_to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-md-2">
                        <label for="supplier-stock-filter" class="form-label">Status stok</label>
                        <select id="supplier-stock-filter" v-model="form.has_stock" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="1">Ada stok</option>
                            <option value="0">Tanpa stok</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="supplier-keyword" class="form-label">Cari</label>
                        <input id="supplier-keyword" v-model="form.keyword" class="form-control form-control-sm" type="search" placeholder="Pemasok, produk, atau kode..." />
                    </div>
                    <div class="col-md-2">
                        <label for="supplier-per-page" class="form-label">Baris per halaman</label>
                        <select id="supplier-per-page" v-model.number="form.per_page" class="form-select form-select-sm">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-filter me-1" aria-hidden="true"></i>Filter</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="card report-card h-100">
                    <div class="card-header"><h6 class="mb-0">Pemasok teratas</h6></div>
                    <ul class="list-group list-group-flush">
                        <li v-for="row in supplierChartRows.slice(0, 5)" :key="row.label" class="list-group-item d-flex justify-content-between">
                            <span>{{ row.label }}</span>
                            <strong>{{ formatNumber(row.total_products) }} produk</strong>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card report-card h-100">
                    <div class="card-header"><h6 class="mb-0">Produk teratas</h6></div>
                    <ul class="list-group list-group-flush">
                        <li v-for="row in productChartRows.slice(0, 5)" :key="row.label" class="list-group-item d-flex justify-content-between">
                            <span>{{ row.label }}</span>
                            <strong>{{ formatNumber(row.total_suppliers) }} pemasok</strong>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card report-card h-100">
                    <div class="card-header"><h6 class="mb-0">Negara pemasok</h6></div>
                    <ul class="list-group list-group-flush">
                        <li v-for="row in countryChartRows.slice(0, 5)" :key="row.label" class="list-group-item d-flex justify-content-between">
                            <span>{{ row.label }}</span>
                            <strong>{{ formatNumber(row.total) }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card report-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="h6 mb-0">Daftar produk pemasok</h2>
                    <small class="text-muted">{{ rows.total }} baris sesuai filter</small>
                </div>
                <ReportTableControls
                    :columns="supplierProductColumns"
                    :visible="visibleColumns"
                    :density="density"
                    :simple-preset="supplierProductSimpleColumns"
                    @toggle-density="toggleDensity"
                    @toggle-column="toggleColumn"
                    @show-all="showAll"
                    @apply-simple="applyPreset(supplierProductSimpleColumns)"
                />
            </div>
            <div class="table-responsive report-table-wrap" :class="{ 'is-compact': isCompact }" tabindex="0" role="region" aria-label="Daftar produk pemasok, inbound, dan stok">
                <table class="table align-middle mb-0">
                    <caption class="visually-hidden">Produk per pemasok dengan jumlah inbound dan posisi stok saat ini</caption>
                    <thead>
                        <tr>
                            <th v-if="isVisible('supplier_name')">Pemasok</th>
                            <th v-if="isVisible('supplier_country')">Negara</th>
                            <th v-if="isVisible('product_name')">Produk</th>
                            <th v-if="isVisible('product_codes')">Kode produk</th>
                            <th v-if="isVisible('qty_inbound')" class="text-end">Qty masuk</th>
                            <th v-if="isVisible('stok_now')" class="text-end">Stok saat ini</th>
                            <th v-if="isVisible('stok_good')" class="text-end">Stok baik</th>
                            <th v-if="isVisible('stok_bad')" class="text-end">Stok rusak</th>
                            <th v-if="isVisible('stok_value')" class="text-end">Nilai stok</th>
                            <th v-if="isVisible('last_inbound_date')">Terakhir diterima</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in rows.data" :key="index">
                            <td v-if="isVisible('supplier_name')" class="fw-semibold">{{ row.supplier_name ?? '-' }}</td>
                            <td v-if="isVisible('supplier_country')">{{ row.supplier_country ?? '-' }}</td>
                            <td v-if="isVisible('product_name')">{{ row.product_name ?? '-' }}</td>
                            <td v-if="isVisible('product_codes')">{{ row.product_codes ?? '-' }}</td>
                            <td v-if="isVisible('qty_inbound')" class="text-end">{{ formatNumber(row.qty_inbound) }}</td>
                            <td v-if="isVisible('stok_now')" class="text-end">{{ formatNumber(row.stok_now) }}</td>
                            <td v-if="isVisible('stok_good')" class="text-end">{{ formatNumber(row.stok_good) }}</td>
                            <td v-if="isVisible('stok_bad')" class="text-end">{{ formatNumber(row.stok_bad) }}</td>
                            <td v-if="isVisible('stok_value')" class="text-end">{{ formatCurrency(row.stok_value) }}</td>
                            <td v-if="isVisible('last_inbound_date')">{{ formatDate(row.last_inbound_date) }}</td>
                        </tr>
                        <tr v-if="rows.data.length === 0">
                            <td :colspan="columnCount" class="report-empty">Tidak ada produk pemasok pada filter ini. Ubah tanggal atau reset filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">Menampilkan {{ rows.from ?? 0 }} - {{ rows.to ?? 0 }} dari {{ rows.total }} baris</small>
                <PaginationLinks :links="rows.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.report-page { --report-ink: var(--app-ink); --report-border: var(--app-border); --report-muted: var(--app-muted); color: var(--report-ink); }
.report-header { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: var(--app-radius-xl); background: var(--app-hero-bg); color: var(--app-hero-text); }
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: var(--app-text-xs); font-weight: var(--app-weight-semibold); }
.report-header h1 { margin: 0 0 0.35rem; font-size: var(--app-text-3xl); font-weight: var(--app-weight-semibold); }
.report-header p { margin: 0; color: var(--app-hero-muted); font-size: var(--app-text-sm); }
.report-card { border: 1px solid var(--report-border); border-radius: var(--app-radius-lg); box-shadow: none; }
.report-card th { color: var(--report-muted); font-size: var(--app-text-xs); font-weight: var(--app-weight-semibold); }
.report-empty { padding: 2.5rem 1rem; color: var(--report-muted); text-align: center; }

.supplier-summary {
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-lg);
    background: var(--app-surface);
}

.supplier-summary-primary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
}

.supplier-stat {
    min-width: 0;
    padding-right: 0.75rem;
    border-right: 1px solid var(--app-border);
}

.supplier-stat:last-child {
    padding-right: 0;
    border-right: 0;
}

.supplier-stat span {
    display: block;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-medium);
}

.supplier-stat strong {
    display: block;
    margin-top: 0.15rem;
    overflow-wrap: anywhere;
    font-size: var(--app-text-2xl);
    font-weight: var(--app-weight-semibold);
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}

.supplier-stat small {
    display: block;
    margin-top: 0.1rem;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
    line-height: var(--app-leading-snug);
}

.supplier-summary-secondary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.65rem 1rem;
    margin: 0.9rem 0 0;
    padding-top: 0.85rem;
    border-top: 1px solid var(--app-border);
}

.supplier-summary-secondary div {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
    min-width: 0;
}

.supplier-summary-secondary dt {
    color: var(--app-muted);
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-medium);
}

.supplier-summary-secondary dd {
    margin: 0;
    font-size: var(--app-text-sm);
    font-weight: var(--app-weight-semibold);
    font-variant-numeric: tabular-nums;
}

@media (max-width: 991.98px) {
    .supplier-summary-primary,
    .supplier-summary-secondary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .supplier-stat {
        padding-right: 0;
        border-right: 0;
    }
}

@media (max-width: 575.98px) {
    .supplier-summary-primary,
    .supplier-summary-secondary {
        grid-template-columns: 1fr;
    }

    .report-header {
        padding: 1.1rem;
    }
}
</style>
