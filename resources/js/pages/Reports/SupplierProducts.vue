<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

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

const summaryCards = computed(() => [
    { label: 'Relasi Supplier Produk', value: props.summary.total_relations },
    { label: 'Supplier', value: props.summary.total_suppliers },
    { label: 'Produk', value: props.summary.total_products },
    { label: 'Total Qty Inbound', value: props.summary.total_qty_inbound },
    { label: 'Stok Saat Ini', value: props.summary.total_stock },
    { label: 'Produk Ada Stok', value: props.summary.products_with_stock },
    { label: 'Produk Tanpa Stok', value: props.summary.products_without_stock },
]);
</script>

<template>
    <Head title="Supplier Products" />

    <div class="report-page">
        <div class="report-header">
            <div>
                <span class="report-eyebrow">Reports</span>
                <h1>Supplier Products</h1>
                <p>Analisis hubungan supplier, produk, inbound, dan stok saat ini.</p>
            </div>
            <a :href="exportUrl" class="btn btn-light btn-sm">
                <i class="mdi mdi-download me-1"></i>
                Export Excel
            </a>
        </div>

        <div class="row g-3 mb-3">
            <div v-for="card in summaryCards" :key="card.label" class="col-6 col-md-4 col-xl-3">
                <div class="card report-card h-100">
                    <div class="card-body">
                        <div class="report-label">{{ card.label }}</div>
                        <div class="report-value">{{ formatNumber(card.value) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-3">
                        <label class="form-label">Supplier</label>
                        <SearchableSelect
                            v-model="form.supplier_id"
                            :options="supplierOptions"
                            placeholder="Semua Supplier"
                            search-placeholder="Cari supplier..."
                        />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Produk</label>
                        <SearchableSelect
                            v-model="form.product_id"
                            :options="productOptions"
                            placeholder="Semua Produk"
                            search-placeholder="Cari nama atau kode produk..."
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Dari</label>
                        <input v-model="form.date_from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai</label>
                        <input v-model="form.date_to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stok</label>
                        <select v-model="form.has_stock" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="1">Ada stok</option>
                            <option value="0">Tanpa stok</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cari</label>
                        <input v-model="form.keyword" class="form-control form-control-sm" type="search" placeholder="Supplier, produk, kode..." />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Per Halaman</label>
                        <select v-model.number="form.per_page" class="form-select form-select-sm">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-filter me-1"></i>Filter</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="card report-card h-100">
                    <div class="card-header"><h6 class="mb-0">Top Supplier</h6></div>
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
                    <div class="card-header"><h6 class="mb-0">Top Produk</h6></div>
                    <ul class="list-group list-group-flush">
                        <li v-for="row in productChartRows.slice(0, 5)" :key="row.label" class="list-group-item d-flex justify-content-between">
                            <span>{{ row.label }}</span>
                            <strong>{{ formatNumber(row.total_suppliers) }} supplier</strong>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card report-card h-100">
                    <div class="card-header"><h6 class="mb-0">Negara Supplier</h6></div>
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
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Negara</th>
                            <th>Produk</th>
                            <th>Kode</th>
                            <th class="text-end">Qty Inbound</th>
                            <th class="text-end">Stok</th>
                            <th>Terakhir Diterima</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in rows.data" :key="index">
                            <td class="fw-semibold">{{ row.supplier_name ?? '-' }}</td>
                            <td>{{ row.supplier_country ?? '-' }}</td>
                            <td>{{ row.product_name ?? '-' }}</td>
                            <td>{{ row.product_codes ?? '-' }}</td>
                            <td class="text-end">{{ formatNumber(row.qty_inbound) }}</td>
                            <td class="text-end">{{ formatNumber(row.stok_now) }}</td>
                            <td>{{ formatDate(row.last_inbound_date) }}</td>
                        </tr>
                        <tr v-if="rows.data.length === 0">
                            <td colspan="7" class="report-empty">Belum ada data.</td>
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
.report-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--report-ink); color: #fff; }
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.report-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.report-header p { margin: 0; color: var(--app-hero-muted); }
.report-card { border: 1px solid var(--report-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.report-card th { color: var(--report-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.report-label { color: var(--report-muted); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.report-value { margin-top: 0.3rem; font-size: 1.4rem; font-weight: 800; }
.report-empty { padding: 2.5rem 1rem; color: var(--report-muted); text-align: center; }
</style>
