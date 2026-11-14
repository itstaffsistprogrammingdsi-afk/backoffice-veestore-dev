<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PaginationLinks from '@/components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import ReportPeriodPresets from '@/components/ui/ReportPeriodPresets.vue';
import { formatCurrency, formatDate, formatNumber } from '@/composables/useFormatters';

interface Option {
    id: string;
    name: string;
    code?: string | null;
}

interface MetricOption {
    value: string;
    label: string;
    unit: string;
}

interface LabelOption {
    value: string;
    label: string;
}

interface ProductOption {
    id: string;
    code: string;
    name: string;
}

interface Filters {
    date_from: string;
    date_to: string;
    channel: string;
    store_id: string;
    category_id: string;
    brand_id: string;
    product_id: string;
    q: string;
    dimension: string;
    metric: string;
}

interface SegmentRow {
    segment_label: string;
    qty: number;
    revenue: number;
    buyers: number;
    transactions: number;
    metric_value: number;
    share?: number;
}

interface ProductRow {
    product_id: string;
    product_code: string | null;
    product_name: string | null;
    category_name: string | null;
    brand_name: string | null;
    qty: number;
    revenue: number;
    buyers: number;
    transactions: number;
    metric_value: number;
}

interface MatrixRow extends ProductRow {
    cells: SegmentRow[];
}

interface DetailRow extends ProductRow {
    segment_label: string;
}

interface Insight {
    product_id: string;
    product_name: string;
    product_code: string | null;
    category_name: string | null;
    dimension_label: string;
    metric: string;
    metric_label: string;
    metric_unit: string;
    segment_label: string;
    metric_value: number;
    segment_buyers: number;
    segment_transactions: number;
    share: number;
    total_value: number;
    total_buyers: number;
    total_transactions: number;
    second_segment_label: string | null;
    second_value: number | null;
    second_share: number | null;
}

interface Summary {
    total_products: number;
    total_buyers: number;
    total_transactions: number;
    total_qty: number;
    total_revenue: number;
    metric_value: number;
    segment_count: number;
    top_segment: {
        segment_label: string;
        metric_value: number;
        share: number;
    } | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    filters: Filters;
    dimensionOptions: LabelOption[];
    metricOptions: MetricOption[];
    channelOptions: LabelOption[];
    stores: Option[];
    categories: Option[];
    brands: Option[];
    productOptions: ProductOption[];
    dimensionLabel: string;
    metricLabel: string;
    metricUnit: string;
    summary: Summary;
    segmentTotals: SegmentRow[];
    matrixSegments: SegmentRow[];
    matrixRows: MatrixRow[];
    selectedProduct: ProductRow | null;
    breakdown: SegmentRow[];
    insight: Insight | null;
    rows: Paginator<DetailRow>;
    urls: {
        index: string;
        export: string;
        demographics: string;
        crm: string;
        customer_profiles: string;
    };
}

const props = defineProps<Props>();

const form = reactive<Filters>({
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    channel: props.filters.channel ?? '',
    store_id: props.filters.store_id ?? '',
    category_id: props.filters.category_id ?? '',
    brand_id: props.filters.brand_id ?? '',
    product_id: props.filters.product_id ?? '',
    q: props.filters.q ?? '',
    dimension: props.filters.dimension ?? 'age_group',
    metric: props.filters.metric ?? 'qty',
});

const activeFilterCount = computed(() => {
    const keys: Array<keyof Filters> = ['channel', 'store_id', 'category_id', 'brand_id', 'product_id', 'q'];
    const base = keys.filter((key) => form[key] !== '').length;

    return base
        + (form.dimension !== 'age_group' ? 1 : 0)
        + (form.metric !== 'qty' ? 1 : 0);
});

const matrixMax = computed(() => {
    const values = props.matrixRows.flatMap((row) => row.cells.map((cell) => Number(cell.metric_value ?? 0)));

    return Math.max(1, ...values);
});

const breakdownMax = computed(() => {
    const values = props.breakdown.map((row) => Number(row.metric_value ?? 0));

    return Math.max(1, ...values);
});

const insightIncomplete = computed(() => (
    (props.insight?.segment_label ?? '').toLowerCase().includes('belum')
));

const storeSelectOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
})));

const categorySelectOptions = computed(() => props.categories.map((category) => ({
    value: category.id,
    label: category.name,
})));

const brandSelectOptions = computed(() => props.brands.map((brand) => ({
    value: brand.id,
    label: brand.name,
})));

const productSelectOptions = computed(() => props.productOptions.map((product) => ({
    value: product.id,
    label: `${product.code} · ${product.name}`,
})));

const formatMetric = (value: number | string | null | undefined) => (
    props.filters.metric === 'revenue' ? formatCurrency(value) : formatNumber(value)
);

const metricSuffix = computed(() => (
    props.filters.metric === 'revenue' ? '' : ` ${props.metricUnit}`
));

const heatStyle = (value: number): Record<string, string> => {
    const ratio = Math.min(1, Math.max(0, Number(value ?? 0) / matrixMax.value));

    return { '--heat': ratio.toFixed(3) };
};

const barWidth = (value: number, max: number) => {
    const ratio = Math.min(1, Math.max(0, Number(value ?? 0) / Math.max(1, max)));

    return `${Math.max(3, Math.round(ratio * 100))}%`;
};

const queryFor = (overrides: Partial<Filters> = {}) => {
    const params = new URLSearchParams();
    const merged: Filters = { ...props.filters, ...overrides };

    for (const [key, value] of Object.entries(merged)) {
        if (value) params.set(key, String(value));
    }

    const suffix = params.toString();

    return suffix ? `${props.urls.index}?${suffix}` : props.urls.index;
};

const productFilterUrl = (productId: string) => queryFor({ product_id: productId });

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(props.filters)) {
        if (value) params.set(key, String(value));
    }

    const suffix = params.toString();

    return suffix ? `${props.urls.export}?${suffix}` : props.urls.export;
});

const applyFilters = () => {
    router.get(props.urls.index, Object.fromEntries(
        Object.entries(form).map(([key, value]) => [key, value === '' ? undefined : value]),
    ), { preserveScroll: true, replace: true });
};

const resetFilters = () => {
    form.date_from = '';
    form.date_to = '';
    form.channel = '';
    form.store_id = '';
    form.category_id = '';
    form.brand_id = '';
    form.product_id = '';
    form.q = '';
    form.dimension = 'age_group';
    form.metric = 'qty';
    router.get(props.urls.index, {}, { preserveScroll: true, replace: true });
};
</script>

<template>
    <Head title="Preferensi Produk Pelanggan" />

    <div class="pref-page">
        <section class="pref-header">
            <div>
                <span class="pref-eyebrow">Laporan / Pelanggan</span>
                <h1>Preferensi Produk Pelanggan</h1>
                <p>Hubungkan profil demografi pelanggan dengan produk yang dibeli untuk menjawab pertanyaan seperti “pelanggan usia berapa yang paling banyak membeli item A?”.</p>
            </div>
            <div class="pref-header-actions">
                <Link :href="props.urls.demographics" class="pref-hero-button">
                    <i class="mdi mdi-account-details-outline me-1"></i>
                    Demografi
                </Link>
                <Link :href="props.urls.crm" class="pref-hero-button">
                    <i class="mdi mdi-account-heart-outline me-1"></i>
                    CRM Dashboard
                </Link>
                <a :href="exportUrl" class="pref-hero-button pref-hero-button-accent">
                    <i class="mdi mdi-download me-1"></i>
                    Ekspor Excel
                </a>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="pref-summary">
                    <span class="pref-summary-icon"><i class="mdi mdi-package-variant-closed"></i></span>
                    <div><span class="pref-summary-label">Produk Terjual</span><strong class="pref-summary-value">{{ formatNumber(props.summary.total_products) }}</strong><small>{{ props.summary.segment_count }} segmen {{ props.dimensionLabel.toLowerCase() }}</small></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="pref-summary">
                    <span class="pref-summary-icon"><i class="mdi mdi-account-group-outline"></i></span>
                    <div><span class="pref-summary-label">Customer Unik</span><strong class="pref-summary-value">{{ formatNumber(props.summary.total_buyers) }}</strong><small>Pembeli pada periode ini</small></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="pref-summary">
                    <span class="pref-summary-icon"><i class="mdi mdi-receipt-text-outline"></i></span>
                    <div><span class="pref-summary-label">Transaksi</span><strong class="pref-summary-value">{{ formatNumber(props.summary.total_transactions) }}</strong><small>POS dan ecommerce</small></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="pref-summary">
                    <span class="pref-summary-icon"><i class="mdi mdi-cube-outline"></i></span>
                    <div><span class="pref-summary-label">Quantity</span><strong class="pref-summary-value">{{ formatNumber(props.summary.total_qty) }}</strong><small>Total item terjual</small></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="pref-summary pref-summary-success">
                    <span class="pref-summary-icon"><i class="mdi mdi-cash-multiple"></i></span>
                    <div><span class="pref-summary-label">Omzet</span><strong class="pref-summary-value">{{ formatCurrency(props.summary.total_revenue) }}</strong><small>Nilai penjualan bersih</small></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="pref-summary pref-summary-warning">
                    <span class="pref-summary-icon"><i class="mdi mdi-account-star-outline"></i></span>
                    <div><span class="pref-summary-label">Segmen Dominan</span><strong class="pref-summary-value pref-summary-value-text">{{ props.summary.top_segment?.segment_label ?? '-' }}</strong><small v-if="props.summary.top_segment">{{ formatMetric(props.summary.top_segment.metric_value) }}{{ metricSuffix }} · {{ props.summary.top_segment.share }}%</small><small v-else>Belum ada data</small></div>
                </div>
            </div>
        </div>

        <section class="pref-filter" aria-label="Filter preferensi produk customer">
            <ReportPeriodPresets
                class="mb-2"
                :route="props.urls.index"
                from-key="date_from"
                to-key="date_to"
                :from="props.filters.date_from"
                :to="props.filters.date_to"
                :query="form"
            />
            <form @submit.prevent="applyFilters">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="pref-date-from">Dari</label>
                        <input id="pref-date-from" v-model="form.date_from" class="form-control form-control-sm" type="date" :max="form.date_to || undefined" />
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="pref-date-to">Sampai</label>
                        <input id="pref-date-to" v-model="form.date_to" class="form-control form-control-sm" type="date" :min="form.date_from || undefined" />
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="pref-channel">Channel</label>
                        <select id="pref-channel" v-model="form.channel" class="form-select form-select-sm">
                            <option v-for="option in props.channelOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label" for="pref-store">Store</label>
                        <SearchableSelect
                            id="pref-store"
                            v-model="form.store_id"
                            :options="storeSelectOptions"
                            placeholder="Semua store"
                            search-placeholder="Cari store..."
                        />
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label" for="pref-dimension">Dimensi demografi</label>
                        <select id="pref-dimension" v-model="form.dimension" class="form-select form-select-sm">
                            <option v-for="option in props.dimensionOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>

                    <div class="col-12 col-lg-4">
                        <label class="form-label" for="pref-product">Produk</label>
                        <SearchableSelect
                            id="pref-product"
                            v-model="form.product_id"
                            :options="productSelectOptions"
                            placeholder="Semua produk"
                            search-placeholder="Cari nama atau kode produk..."
                        />
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label" for="pref-category">Kategori</label>
                        <SearchableSelect
                            id="pref-category"
                            v-model="form.category_id"
                            :options="categorySelectOptions"
                            placeholder="Semua kategori"
                            search-placeholder="Cari kategori..."
                        />
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label" for="pref-brand">Brand</label>
                        <SearchableSelect
                            id="pref-brand"
                            v-model="form.brand_id"
                            :options="brandSelectOptions"
                            placeholder="Semua brand"
                            search-placeholder="Cari brand..."
                        />
                    </div>
                    <div class="col-12 col-lg-2">
                        <label class="form-label" for="pref-search">Cari produk</label>
                        <input id="pref-search" v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Nama / kode / kategori..." />
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label" for="pref-metric">Metrik</label>
                        <select id="pref-metric" v-model="form.metric" class="form-select form-select-sm">
                            <option v-for="option in props.metricOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="pref-filter-actions">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="mdi mdi-chart-box-plus-outline me-1"></i>
                            Analisa
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters">
                            <i class="mdi mdi-refresh me-1"></i>
                            Reset
                        </button>
                    </div>
                    <span class="pref-filter-meta">
                        <span v-if="activeFilterCount > 0" class="badge bg-label-primary me-2">{{ activeFilterCount }} filter aktif</span>
                        Periode {{ formatDate(props.filters.date_from) }} – {{ formatDate(props.filters.date_to) }}
                    </span>
                </div>
            </form>
        </section>

        <section v-if="props.insight" class="pref-insight" aria-label="Jawaban cepat preferensi customer">
            <span class="pref-insight-icon"><i class="mdi mdi-lightbulb-on-outline"></i></span>
            <div class="pref-insight-content">
                <span class="pref-insight-eyebrow">Jawaban cepat</span>
                <h2>
                    Pembeli <strong>{{ props.insight.product_name }}</strong> paling banyak berasal dari
                    {{ props.insight.dimension_label }} <strong>{{ props.insight.segment_label }}</strong>.
                </h2>
                <p>
                    Segmen ini menyumbang <strong>{{ props.insight.share }}%</strong> dari total {{ props.metricLabel.toLowerCase() }} produk,
                    dengan {{ formatNumber(props.insight.segment_buyers) }} customer unik dan {{ formatNumber(props.insight.segment_transactions) }} transaksi.
                </p>
                <div class="pref-insight-meter" aria-hidden="true">
                    <span :style="{ width: `${props.insight.share}%` }"></span>
                </div>
                <div class="pref-insight-meta">
                    <span><strong>{{ formatMetric(props.insight.metric_value) }}</strong>{{ metricSuffix }}</span>
                    <span>{{ formatNumber(props.insight.total_buyers) }} customer unik produk</span>
                    <span>{{ formatNumber(props.insight.total_transactions) }} transaksi produk</span>
                    <span v-if="props.insight.second_segment_label">Berikutnya: {{ props.insight.second_segment_label }} ({{ props.insight.second_share }}%)</span>
                </div>
                <p v-if="insightIncomplete" class="pref-insight-warning">
                    <i class="mdi mdi-alert-circle-outline me-1"></i>
                    Segmen teratas masih “{{ props.insight.segment_label }}”. Lengkapi data {{ props.insight.dimension_label.toLowerCase() }} customer agar analisis lebih akurat.
                </p>
            </div>
        </section>
        <section v-else class="pref-insight pref-insight-empty">
            <span class="pref-insight-icon"><i class="mdi mdi-chart-box-outline"></i></span>
            <div class="pref-insight-content">
                <span class="pref-insight-eyebrow">Jawaban cepat</span>
                <h2>Belum ada penjualan pada filter ini.</h2>
                <p>Ubah periode, channel, store, atau produk untuk melihat segmentasi pembeli.</p>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <section class="pref-card pref-matrix-card">
                    <header class="pref-card-header">
                        <div>
                            <h2>Matriks Produk × {{ props.dimensionLabel }}</h2>
                            <p>Klik nama produk untuk memfokuskan analisis. Warna sel menunjukkan intensitas {{ props.metricLabel.toLowerCase() }}.</p>
                        </div>
                        <span class="pref-card-meta">{{ props.metricLabel }}</span>
                    </header>

                    <div v-if="props.matrixRows.length > 0" class="table-responsive">
                        <table class="table align-middle mb-0 pref-matrix">
                            <caption class="visually-hidden">Matriks produk dan segmen {{ props.dimensionLabel }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Produk</th>
                                    <th v-for="segment in props.matrixSegments" :key="segment.segment_label" scope="col" class="text-end">{{ segment.segment_label }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in props.matrixRows" :key="row.product_id" :class="{ 'is-selected': props.selectedProduct?.product_id === row.product_id }">
                                    <th scope="row">
                                        <Link :href="productFilterUrl(row.product_id)" class="pref-product-link">{{ row.product_name }}</Link>
                                        <small>{{ row.product_code }} · {{ row.category_name ?? '-' }}</small>
                                        <span class="pref-product-total">Total {{ formatMetric(row.metric_value) }}</span>
                                    </th>
                                    <td
                                        v-for="cell in row.cells"
                                        :key="`${row.product_id}-${cell.segment_label}`"
                                        :style="heatStyle(cell.metric_value)"
                                    >
                                        <div class="pref-cell-content">
                                            <strong>{{ formatMetric(cell.metric_value) }}</strong>
                                            <small>{{ formatNumber(cell.buyers) }} customer</small>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="pref-empty">Belum ada data penjualan untuk matriks pada filter ini.</p>
                </section>
            </div>

            <div class="col-xl-5">
                <section class="pref-card pref-breakdown-card">
                    <header class="pref-card-header">
                        <div>
                            <h2>Segmentasi {{ props.selectedProduct?.product_name ?? 'Produk' }}</h2>
                            <p>{{ props.selectedProduct ? `${props.selectedProduct.product_code} · ${props.selectedProduct.category_name ?? 'Tanpa kategori'}` : 'Pilih produk untuk melihat segmentasi.' }}</p>
                        </div>
                        <div class="pref-card-meta-group">
                            <span class="pref-card-meta">{{ props.metricLabel }}</span>
                            <Link v-if="props.filters.product_id" :href="queryFor({ product_id: '' })" class="pref-clear-link">Semua produk</Link>
                        </div>
                    </header>

                    <div v-if="props.breakdown.length > 0" class="pref-breakdown-list">
                        <div v-for="row in props.breakdown" :key="row.segment_label" class="pref-breakdown-item">
                            <div class="pref-breakdown-row">
                                <span>{{ row.segment_label }}</span>
                                <strong>{{ formatMetric(row.metric_value) }}</strong>
                            </div>
                            <div class="pref-breakdown-bar" aria-hidden="true">
                                <span :style="{ width: barWidth(row.metric_value, breakdownMax) }"></span>
                            </div>
                            <small>{{ formatNumber(row.buyers) }} customer · {{ formatNumber(row.transactions) }} transaksi · {{ row.share ?? 0 }}%</small>
                        </div>
                    </div>
                    <p v-else class="pref-empty">Belum ada segmentasi untuk produk ini.</p>
                </section>
            </div>
        </div>

        <section class="pref-card">
            <header class="pref-card-header">
                <div>
                    <h2>Detail Preferensi Produk</h2>
                    <p>Rincian produk dan segmen {{ props.dimensionLabel.toLowerCase() }} sesuai filter yang aktif.</p>
                </div>
                <span class="pref-card-meta">{{ formatNumber(props.rows.total) }} baris</span>
            </header>

            <div v-if="props.rows.data.length === 0" class="pref-empty-state">
                <i class="mdi mdi-chart-box-outline"></i>
                <strong>Tidak ada data preferensi</strong>
                <span>Ubah filter atau periode untuk melihat hubungan customer dan produk.</span>
                <a :href="props.urls.index" class="btn btn-sm btn-outline-secondary mt-2">Reset filter</a>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 pref-detail-table">
                    <caption class="visually-hidden">Detail preferensi produk per segmen customer</caption>
                    <thead>
                        <tr>
                            <th scope="col">Produk</th>
                            <th scope="col">Segmen {{ props.dimensionLabel }}</th>
                            <th scope="col" class="text-end">Qty</th>
                            <th scope="col" class="text-end">Omzet</th>
                            <th scope="col" class="text-end">Customer Unik</th>
                            <th scope="col" class="text-end">Transaksi</th>
                            <th scope="col" class="text-end">{{ props.metricLabel }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.rows.data" :key="`${row.product_id}-${row.segment_label}`">
                            <td>
                                <strong class="d-block">{{ row.product_name }}</strong>
                                <small class="pref-cell-note">{{ row.product_code }} · {{ row.category_name ?? '-' }}<span v-if="row.brand_name"> · {{ row.brand_name }}</span></small>
                            </td>
                            <td><span class="badge bg-label-primary">{{ row.segment_label }}</span></td>
                            <td class="text-end">{{ formatNumber(row.qty) }}</td>
                            <td class="text-end">{{ formatCurrency(row.revenue) }}</td>
                            <td class="text-end">{{ formatNumber(row.buyers) }}</td>
                            <td class="text-end">{{ formatNumber(row.transactions) }}</td>
                            <td class="text-end"><strong>{{ formatMetric(row.metric_value) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pref-table-footer">
                <small>Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ formatNumber(props.rows.total) }} baris</small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </section>
    </div>
</template>

<style scoped>
.pref-page { color: var(--app-ink); }
.pref-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: #fff; }
.pref-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: none; }
.pref-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.pref-header p { max-width: 820px; margin: 0; color: var(--app-hero-muted) !important; }
.pref-header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.pref-hero-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface); color: var(--app-ink); font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.pref-hero-button-accent { background: var(--app-accent-strong); color: var(--app-on-accent); }
.pref-summary { display: flex; align-items: flex-start; gap: 0.7rem; height: 100%; padding: 0.9rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.pref-summary-icon { display: grid; flex: 0 0 auto; width: 2.2rem; height: 2.2rem; place-items: center; border-radius: 0.6rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.1rem; }
.pref-summary-label { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: none; }
.pref-summary-value { display: block; margin-top: 0.1rem; font-size: 1.15rem; line-height: 1.25; }
.pref-summary-value-text { font-size: 0.95rem; }
.pref-summary small { display: block; margin-top: 0.15rem; color: var(--app-muted); font-size: 0.7rem; }
.pref-summary-success .pref-summary-icon { background: var(--app-success-bg); color: var(--app-success-ink); }
.pref-summary-warning .pref-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.pref-filter { padding: 1rem 1.1rem; margin-bottom: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.pref-filter .form-label { margin-bottom: 0.3rem; color: var(--app-muted); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.03em; text-transform: none; }
.pref-filter-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; margin-top: 0.9rem; padding-top: 0.9rem; border-top: 1px solid var(--app-border); }
.pref-filter-meta { color: var(--app-muted); font-size: 0.78rem; }
.pref-insight { display: flex; align-items: flex-start; gap: 1rem; padding: 1.1rem 1.2rem; margin-bottom: 1rem; border: 1px solid var(--app-border); border-radius: 0.65rem; background: var(--app-surface-2); box-shadow: none; }
.pref-insight-empty { background: var(--app-surface); }
.pref-insight-icon { display: grid; flex: 0 0 auto; width: 2.6rem; height: 2.6rem; place-items: center; border-radius: 0.7rem; background: var(--app-accent-strong); color: var(--app-on-accent); font-size: 1.4rem; }
.pref-insight-content { flex: 1 1 auto; min-width: 0; }
.pref-insight-eyebrow { color: var(--app-accent-ink); font-size: 0.7rem; font-weight: 800; letter-spacing: 0.06em; text-transform: none; }
.pref-insight h2 { margin: 0.2rem 0 0.35rem; font-size: 1.15rem; line-height: 1.4; }
.pref-insight p { margin: 0; color: var(--app-muted); font-size: 0.85rem; }
.pref-insight-meter { overflow: hidden; height: 0.4rem; margin: 0.75rem 0 0.55rem; border-radius: 999px; background: var(--app-surface-3); }
.pref-insight-meter span { display: block; height: 100%; border-radius: inherit; background: var(--app-accent); }
.pref-insight-meta { display: flex; flex-wrap: wrap; gap: 0.4rem 1rem; color: var(--app-muted); font-size: 0.75rem; }
.pref-insight-meta strong { color: var(--app-ink); }
.pref-insight-warning { display: flex; align-items: flex-start; gap: 0.25rem; margin-top: 0.65rem !important; padding: 0.5rem 0.65rem; border-radius: 0.55rem; background: var(--app-warning-bg); color: var(--app-warning-ink) !important; font-size: 0.76rem !important; }
.pref-card { display: flex; flex-direction: column; height: 100%; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.pref-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.pref-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.pref-card-header p { margin: 0; color: var(--app-muted); font-size: 0.78rem; }
.pref-card-meta { color: var(--app-muted); font-size: 0.75rem; font-weight: 700; white-space: nowrap; }
.pref-card-meta-group { display: flex; flex-direction: column; align-items: flex-end; gap: 0.3rem; }
.pref-clear-link { color: var(--app-accent-ink); font-size: 0.72rem; font-weight: 700; text-decoration: none; }
.pref-clear-link:hover { text-decoration: underline; }
.pref-matrix { min-width: 780px; }
.pref-matrix th { padding: 0.6rem 0.7rem; color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.03em; text-transform: none; }
.pref-matrix thead th { background: var(--app-surface-2); }
.pref-matrix th:first-child { position: sticky; left: 0; z-index: 2; min-width: 220px; background: var(--app-surface); }
.pref-matrix thead th:first-child { z-index: 3; background: var(--app-surface-2); }
.pref-matrix td { padding: 0.65rem 0.7rem; border-color: var(--app-border); text-align: end; vertical-align: top; }
.pref-matrix td::before { position: absolute; inset: 0; background: var(--app-accent); content: ''; opacity: calc(var(--heat, 0) * 0.16); }
.pref-matrix td { position: relative; }
.pref-matrix tr.is-selected th:first-child { box-shadow: inset 3px 0 0 var(--app-accent-strong); }
.pref-cell-content { position: relative; z-index: 1; }
.pref-cell-content strong { display: block; font-size: 0.82rem; }
.pref-cell-content small { display: block; color: var(--app-muted); font-size: 0.68rem; white-space: nowrap; }
.pref-product-link { display: block; color: var(--app-accent-ink); font-size: 0.82rem; font-weight: 700; text-decoration: none; }
.pref-product-link:hover { text-decoration: underline; }
.pref-matrix th small { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 400; }
.pref-product-total { display: inline-block; margin-top: 0.2rem; color: var(--app-muted); font-size: 0.68rem; font-weight: 600; }
.pref-breakdown-list { display: grid; gap: 0.7rem; padding: 1rem 1.1rem 1.1rem; }
.pref-breakdown-row { display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.25rem; }
.pref-breakdown-row span { overflow: hidden; font-size: 0.8rem; text-overflow: ellipsis; white-space: nowrap; }
.pref-breakdown-row strong { font-size: 0.82rem; white-space: nowrap; }
.pref-breakdown-bar { overflow: hidden; height: 0.35rem; border-radius: 999px; background: var(--app-surface-3); }
.pref-breakdown-bar span { display: block; height: 100%; border-radius: inherit; background: var(--app-accent); }
.pref-breakdown-item small { display: block; margin-top: 0.2rem; color: var(--app-muted); font-size: 0.68rem; }
.pref-detail-table { min-width: 1000px; }
.pref-detail-table th { padding: 0.6rem 0.85rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: none; }
.pref-detail-table td { padding: 0.75rem 0.85rem; border-color: var(--app-border); font-size: 0.82rem; }
.pref-detail-table td:first-child { min-width: 260px; }
.pref-cell-note { display: block; color: var(--app-muted); font-size: 0.7rem; }
.pref-table-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.pref-table-footer small { color: var(--app-muted); }
.pref-empty { margin: 0; padding: 2rem 1rem; color: var(--app-muted); font-size: 0.82rem; text-align: center; }
.pref-empty-state { display: grid; place-items: center; gap: 0.3rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.pref-empty-state i { color: var(--app-accent-ink); font-size: 1.8rem; }
.pref-empty-state strong { color: var(--app-ink); }
@media (max-width: 991.98px) {
    .pref-header { align-items: flex-start; flex-direction: column; }
    .pref-insight { flex-direction: column; }
    .pref-card-header { align-items: flex-start; flex-direction: column; }
}
@media (max-width: 575.98px) {
    .pref-filter-actions > div { width: 100%; }
    .pref-filter-actions .btn { flex: 1 1 auto; }
}
</style>
