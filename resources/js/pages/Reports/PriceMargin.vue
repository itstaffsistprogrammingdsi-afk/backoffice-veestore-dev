<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ReportTableControls from '@/components/ui/ReportTableControls.vue';
import { downloadExcelTable } from '@/composables/useExcelExport';
import { useReportTableColumns, useReportTableDensity } from '@/composables/useReportTable';

defineOptions({ layout: AppLayout });

interface Option {
    id: string;
    name: string;
}

interface PriceMarginRow {
    id: string;
    product_name: string;
    product_code: string | null;
    category_name: string | null;
    brand_name: string | null;
    base_price: string | number | null;
    store_price: string | number | null;
    ecommerce_price: string | number | null;
    store_margin_percent: number | null;
    ecommerce_margin_percent: number | null;
    margin_status: string;
    margin_status_label: string;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<PriceMarginRow>;
    summary: {
        total_products: number;
        missing_price: number;
        below_base: number;
        below_min_margin: number;
    };
    categories: Option[];
    brands: Option[];
    statusOptions: Array<{ value: string; label: string }>;
    filters: {
        q: string;
        category_id: string;
        brand_id: string;
        status: string;
        per_page: number;
        min_margin: number;
    };
    urls: { index: string };
}

const props = defineProps<Props>();

const priceMarginColumns = [
    { key: 'product_name', label: 'Produk' },
    { key: 'category_name', label: 'Kategori' },
    { key: 'brand_name', label: 'Merek' },
    { key: 'base_price', label: 'Harga acuan' },
    { key: 'store_price', label: 'Harga toko' },
    { key: 'store_margin', label: 'Margin toko' },
    { key: 'ecommerce_price', label: 'Harga e-commerce' },
    { key: 'ecommerce_margin', label: 'Margin e-commerce' },
    { key: 'status', label: 'Status' },
];
const priceMarginSimpleColumns = ['product_name', 'base_price', 'store_margin', 'ecommerce_margin', 'status'];
const { density, isCompact, toggleDensity } = useReportTableDensity();
const { visibleColumns, isVisible, toggleColumn, showAll, applyPreset, columnCount } = useReportTableColumns(
    'veestore.price-margin.columns',
    priceMarginColumns.map((column) => column.key),
);

const form = reactive({
    q: props.filters.q ?? '',
    category_id: props.filters.category_id ?? '',
    brand_id: props.filters.brand_id ?? '',
    status: props.filters.status ?? '',
    per_page: props.filters.per_page ?? 25,
});

const categoryOptions = computed(() => props.categories.map((category) => ({
    value: category.id,
    label: category.name,
})));

const brandOptions = computed(() => props.brands.map((brand) => ({
    value: brand.id,
    label: brand.name,
})));

const summaryCards = computed(() => [
    { label: 'Produk dengan harga', value: formatNumber(props.summary.total_products) },
    { label: 'Harga belum lengkap', value: formatNumber(props.summary.missing_price), tone: 'is-info' },
    { label: 'Di bawah harga acuan', value: formatNumber(props.summary.below_base), tone: 'is-danger' },
    { label: `Margin di bawah ${formatPercent(props.filters.min_margin)}`, value: formatNumber(props.summary.below_min_margin), tone: 'is-warning' },
]);

const applyFilters = () => {
    router.get(props.urls.index, {
        q: form.q || undefined,
        category_id: form.category_id || undefined,
        brand_id: form.brand_id || undefined,
        status: form.status || undefined,
        per_page: form.per_page || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    form.q = '';
    form.category_id = '';
    form.brand_id = '';
    form.status = '';
    form.per_page = 25;
    applyFilters();
};

const exportRows = () => downloadExcelTable('Kontrol-Margin-Harga', 'Kontrol Margin Harga', [
    { key: 'product_name', label: 'Produk' },
    { key: 'product_code', label: 'Kode' },
    { key: 'category_name', label: 'Kategori' },
    { key: 'brand_name', label: 'Brand' },
    { key: 'base_price', label: 'Harga Base' },
    { key: 'store_price', label: 'Harga Store' },
    { key: 'store_margin_percent', label: 'Margin Store (%)' },
    { key: 'ecommerce_price', label: 'Harga Ecommerce' },
    { key: 'ecommerce_margin_percent', label: 'Margin Ecommerce (%)' },
    { key: 'margin_status_label', label: 'Status' },
], props.rows.data, {
    title: 'Kontrol Margin Harga',
    filters: [{ label: 'Pencarian', value: props.filters.q }],
});

const formatNumber = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const formatCurrency = (value: number | string | null | undefined) => {
    if (value === null || value === undefined || value === '') return '-';

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value));
};

const formatPercent = (value: number | string | null | undefined) => {
    if (value === null || value === undefined || value === '') return '-';

    return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value))}%`;
};

const statusClass = (status: string) => {
    if (status === 'below_base') return 'bg-label-danger';
    if (status === 'below_min_margin') return 'bg-label-warning';
    if (status === 'missing_price') return 'bg-label-info';

    return 'bg-label-success';
};

const marginClass = (value: number | null) => {
    if (value === null) return '';
    if (value < 0) return 'text-danger';
    if (value < props.filters.min_margin) return 'text-warning';

    return 'text-success';
};
</script>

<template>
    <Head title="Kontrol Margin Harga" />

    <div class="price-margin-page">
        <header class="price-margin-header">
            <div>
                <span class="price-margin-eyebrow">Laporan / Penjualan</span>
                <h1>Kontrol Margin Harga</h1>
                <p>Bandingkan harga toko dan e-commerce terhadap harga acuan. Batas margin aktif {{ formatPercent(props.filters.min_margin) }}.</p>
            </div>
            <button class="btn btn-light btn-sm" type="button" @click="exportRows">
                <i class="mdi mdi-file-excel-outline me-1" aria-hidden="true"></i>
                Ekspor Excel
            </button>
        </header>

        <section class="price-margin-summary" aria-label="Ringkasan margin harga">
            <div v-for="card in summaryCards" :key="card.label" class="price-margin-summary-card">
                <span>{{ card.label }}</span>
                <strong :class="card.tone">{{ card.value }}</strong>
            </div>
        </section>

        <section class="price-margin-filter" aria-labelledby="price-margin-filter-title">
            <h2 id="price-margin-filter-title">Filter produk</h2>
            <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                <div class="col-md-4">
                    <label for="price-margin-q" class="form-label">Pencarian</label>
                    <input id="price-margin-q" v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Nama produk, kode, kategori, atau merek..." />
                </div>
                <div class="col-md-3">
                    <label for="price-margin-category" class="form-label">Kategori</label>
                    <SearchableSelect id="price-margin-category" v-model="form.category_id" :options="categoryOptions" placeholder="Semua kategori" search-placeholder="Cari kategori..." />
                </div>
                <div class="col-md-3">
                    <label for="price-margin-brand" class="form-label">Merek</label>
                    <SearchableSelect id="price-margin-brand" v-model="form.brand_id" :options="brandOptions" placeholder="Semua merek" search-placeholder="Cari merek..." />
                </div>
                <div class="col-md-2">
                    <label for="price-margin-status" class="form-label">Status</label>
                    <select id="price-margin-status" v-model="form.status" class="form-select form-select-sm">
                        <option value="">Semua status</option>
                        <option v-for="option in props.statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="price-margin-per-page" class="form-label">Baris per halaman</label>
                    <select id="price-margin-per-page" v-model.number="form.per_page" class="form-select form-select-sm">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-filter me-1" aria-hidden="true"></i>Terapkan</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters">Reset</button>
                </div>
            </form>
        </section>

        <section class="price-margin-table-card" aria-labelledby="price-margin-table-title">
            <div class="price-margin-table-head">
                <div>
                    <h2 id="price-margin-table-title">Daftar harga</h2>
                    <p>{{ props.rows.total }} produk sesuai filter. Margin dihitung dari harga jual terhadap harga acuan.</p>
                </div>
                <ReportTableControls
                    :columns="priceMarginColumns"
                    :visible="visibleColumns"
                    :density="density"
                    :simple-preset="priceMarginSimpleColumns"
                    @toggle-density="toggleDensity"
                    @toggle-column="toggleColumn"
                    @show-all="showAll"
                    @apply-simple="applyPreset(priceMarginSimpleColumns)"
                />
            </div>

            <div class="table-responsive report-table-wrap" :class="{ 'is-compact': isCompact }" tabindex="0" role="region" aria-label="Daftar harga dan margin produk">
                <table class="table align-middle mb-0">
                    <caption class="visually-hidden">Harga toko dan e-commerce beserta margin terhadap harga acuan</caption>
                    <thead>
                        <tr>
                            <th v-if="isVisible('product_name')">Produk</th>
                            <th v-if="isVisible('category_name')">Kategori</th>
                            <th v-if="isVisible('brand_name')">Merek</th>
                            <th v-if="isVisible('base_price')" class="text-end">Harga acuan</th>
                            <th v-if="isVisible('store_price')" class="text-end">Harga toko</th>
                            <th v-if="isVisible('store_margin')" class="text-end">Margin toko</th>
                            <th v-if="isVisible('ecommerce_price')" class="text-end">Harga e-commerce</th>
                            <th v-if="isVisible('ecommerce_margin')" class="text-end">Margin e-commerce</th>
                            <th v-if="isVisible('status')">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.rows.data" :key="row.id">
                            <td v-if="isVisible('product_name')">
                                <strong>{{ row.product_name }}</strong>
                                <small class="price-margin-note">{{ row.product_code ?? '-' }}</small>
                            </td>
                            <td v-if="isVisible('category_name')">{{ row.category_name ?? '-' }}</td>
                            <td v-if="isVisible('brand_name')">{{ row.brand_name ?? '-' }}</td>
                            <td v-if="isVisible('base_price')" class="text-end">{{ formatCurrency(row.base_price) }}</td>
                            <td v-if="isVisible('store_price')" class="text-end">{{ formatCurrency(row.store_price) }}</td>
                            <td v-if="isVisible('store_margin')" class="text-end" :class="marginClass(row.store_margin_percent)" :title="`Margin toko ${formatPercent(row.store_margin_percent)} dari harga acuan`">{{ formatPercent(row.store_margin_percent) }}</td>
                            <td v-if="isVisible('ecommerce_price')" class="text-end">{{ formatCurrency(row.ecommerce_price) }}</td>
                            <td v-if="isVisible('ecommerce_margin')" class="text-end" :class="marginClass(row.ecommerce_margin_percent)" :title="`Margin e-commerce ${formatPercent(row.ecommerce_margin_percent)} dari harga acuan`">{{ formatPercent(row.ecommerce_margin_percent) }}</td>
                            <td v-if="isVisible('status')"><span class="badge" :class="statusClass(row.margin_status)">{{ row.margin_status_label }}</span></td>
                        </tr>
                        <tr v-if="props.rows.data.length === 0">
                            <td :colspan="columnCount" class="price-margin-empty">
                                <i class="mdi mdi-scale-balance" aria-hidden="true"></i>
                                <strong>Tidak ada produk yang cocok</strong>
                                <span>Coba ubah filter atau pastikan harga produk sudah diisi.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="price-margin-footer">
                <small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} produk</small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </section>
    </div>
</template>

<style scoped>
.price-margin-page { color: var(--app-ink); }
.price-margin-header { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.4rem; margin-bottom: 1rem; border-radius: 0.9rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.price-margin-header > div { max-width: 52rem; }
.price-margin-eyebrow { display: inline-block; margin-bottom: 0.35rem; color: var(--app-hero-eyebrow); font-size: var(--app-text-xs); font-weight: var(--app-weight-semibold); }
.price-margin-header h1 { margin: 0 0 0.35rem; font-size: clamp(1.3rem, 2vw, 1.6rem); font-weight: var(--app-weight-semibold); }
.price-margin-header p { margin: 0; color: var(--app-hero-muted); font-size: var(--app-text-sm); }
.price-margin-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.price-margin-summary-card { padding: 0.85rem 1rem; border: 1px solid var(--app-border); border-radius: var(--app-radius-lg); background: var(--app-surface); }
.price-margin-summary-card span { display: block; color: var(--app-muted); font-size: var(--app-text-xs); font-weight: var(--app-weight-medium); }
.price-margin-summary-card strong { display: block; margin-top: 0.2rem; font-size: var(--app-text-2xl); font-weight: var(--app-weight-semibold); font-variant-numeric: tabular-nums; }
.price-margin-summary-card strong.is-danger { color: var(--app-danger-ink); }
.price-margin-summary-card strong.is-warning { color: var(--app-warning-ink); }
.price-margin-summary-card strong.is-info { color: var(--app-info-ink); }
.price-margin-filter, .price-margin-table-card { padding: 1rem; margin-bottom: 1rem; border: 1px solid var(--app-border); border-radius: var(--app-radius-lg); background: var(--app-surface); }
.price-margin-filter h2, .price-margin-table-head h2 { margin: 0 0 0.65rem; font-size: var(--app-text-md); font-weight: var(--app-weight-semibold); }
.price-margin-table-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 0.65rem 1rem; margin-bottom: 0.85rem; }
.price-margin-table-head > div:first-child { min-width: 0; }
.price-margin-table-head p { margin: 0.2rem 0 0; color: var(--app-muted); font-size: var(--app-text-sm); }
.price-margin-note { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: var(--app-text-xs); }
.price-margin-page .table th { color: var(--app-muted); font-size: var(--app-text-xs); font-weight: var(--app-weight-semibold); white-space: nowrap; }
.price-margin-page .table td { font-size: var(--app-text-sm); vertical-align: middle; }
.price-margin-empty { padding: 2.5rem 1rem; color: var(--app-muted); text-align: center; }
.price-margin-empty i { display: block; margin-bottom: 0.35rem; font-size: 1.8rem; }
.price-margin-empty strong { display: block; color: var(--app-ink); font-size: 0.9rem; }
.price-margin-empty span { display: block; margin-top: 0.2rem; font-size: 0.78rem; }
.price-margin-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding-top: 0.85rem; }
@media (max-width: 1199.98px) { .price-margin-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575.98px) { .price-margin-summary { grid-template-columns: 1fr; } .price-margin-header { padding: 1.1rem; } }
</style>
