<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StoreTransferReconciliationPanel from '@/components/StoreTransferReconciliationPanel.vue';
import type { StoreTransferReconciliation } from '@/types/storeTransfer';

defineOptions({ layout: AppLayout });

interface WarehouseOption {
    id: string;
    name: string;
    code: string | null;
}

interface StockRow {
    warehouse_id: string;
    warehouse_name: string;
    warehouse_code: string | null;
    product_id: string;
    product_code: string;
    product_name: string;
    unit_name: string;
    on_hand: number;
    reserved: number;
    available: number;
    stock_barrier: number | null;
    tolerance_percentage: number | null;
    threshold: number | null;
    status: 'below' | 'near' | 'safe' | 'no_barrier';
    status_label: string;
    status_priority: number;
    suggested_import_qty: number;
    oldest_stock_at: string | null;
    oldest_age_days: number | null;
    earliest_expired_date: string | null;
    days_to_expiry: number | null;
    fefo_status: 'expired' | 'critical' | 'warning' | 'safe' | 'no_expiry';
    fefo_label: string;
    outbound_30d: number;
    movement_category: 'fast' | 'normal' | 'slow' | 'no_stock';
    movement_label: string;
    cover_days: number | null;
}

interface ProductGroup {
    product_id: string;
    product_code: string;
    product_name: string;
    unit_name: string;
    total_on_hand: number;
    total_reserved: number;
    total_available: number;
    warehouse_count: number;
    below_count: number;
    near_count: number;
    worst_status: StockRow['status'];
    worst_status_label: string;
    status_priority: number;
    total_outbound_30d: number;
    movement_category: 'fast' | 'normal' | 'slow' | 'no_stock';
    movement_label: string;
    oldest_age_days: number | null;
    earliest_expired_date: string | null;
    fefo_status: 'expired' | 'critical' | 'warning' | 'safe' | 'no_expiry';
    fefo_label: string;
    warehouses: StockRow[];
}

interface MissingStockRow {
    warehouse_id: string | null;
    warehouse_name: string;
    warehouse_code: string | null;
    product_id: string;
    product_code: string;
    product_name: string;
    qty: number;
    last_seen_at: string;
}

interface Summary {
    sku_total: number;
    warehouse_stock_count: number;
    below_barrier_count: number;
    near_barrier_count: number;
    import_product_count: number;
    slow_product_count: number;
    fast_product_count: number;
    normal_product_count: number;
    expiring_90_count: number;
    expired_count: number;
    missing_stock_qty: number;
    aging_buckets: {
        bucket_0_30: number;
        bucket_31_90: number;
        bucket_91_180: number;
        bucket_181_365: number;
        bucket_gt_365: number;
    };
    reconciliation: {
        stock_sku_total: number;
        inbound_sku_total: number;
        inbound_only_count: number;
        stock_only_count: number;
        inbound_only: Array<{
            product_id: string;
            product_code: string;
            product_name: string;
            inbound_qty: number;
            document_count: number;
            last_inbound_at: string | null;
        }>;
        stock_only: Array<{
            product_id: string;
            product_code: string;
            product_name: string;
            stock_qty: number;
        }>;
    };
}

interface Props {
    warehouses: WarehouseOption[];
    products: ProductGroup[];
    importNeeds: StockRow[];
    missingStock: MissingStockRow[];
    showThreshold: boolean;
    summary: Summary;
    transferReconciliation: StoreTransferReconciliation;
    filters: {
        q: string;
        warehouse_id: string | null;
    };
    urls: {
        index: string;
        store_requests: string;
        ecommerce_orders: string;
        stock_wms: string;
        stock_barriers: string;
        outbound_report: string;
    };
}

const props = defineProps<Props>();
const warehouseOptions = computed(() => props.warehouses.map((warehouse) => ({
    value: warehouse.id,
    label: warehouse.name,
    hint: warehouse.code ?? undefined,
})));


const filters = reactive({
    q: props.filters.q ?? '',
    warehouse_id: props.filters.warehouse_id ?? '',
});

const activeTab = ref<'availability' | 'import'>('availability');
const expanded = ref<Record<string, boolean>>({});

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);
const formatDate = (value: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value));
};

const movementClass = (category: string) => ({
    fast: 'bg-label-success',
    normal: 'bg-label-info',
    slow: 'bg-label-warning',
    no_stock: 'bg-label-secondary',
}[category] ?? 'bg-label-secondary');

const agingLabel = (days: number | null) => {
    if (days === null) return '-';
    if (days <= 30) return '0-30 hari';
    if (days <= 90) return '31-90 hari';
    if (days <= 180) return '91-180 hari';
    if (days <= 365) return '181-365 hari';
    return '>365 hari';
};

const agingClass = (days: number | null) => {
    if (days === null) return 'bg-label-secondary';
    if (days <= 90) return 'bg-label-success';
    if (days <= 180) return 'bg-label-info';
    if (days <= 365) return 'bg-label-warning';
    return 'bg-label-danger';
};

const fefoClass = (status: string) => ({
    expired: 'bg-label-danger',
    critical: 'bg-label-warning',
    warning: 'bg-label-warning',
    safe: 'bg-label-success',
    no_expiry: 'bg-label-secondary',
}[status] ?? 'bg-label-secondary');


const statusClass = (status: StockRow['status']) => ({
    below: 'bg-label-danger',
    near: 'bg-label-warning',
    safe: 'bg-label-success',
    no_barrier: 'bg-label-secondary',
}[status]);

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        warehouse_id: filters.warehouse_id || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.q = '';
    filters.warehouse_id = '';
    applyFilters();
};

const toggleProduct = (productId: string) => {
    expanded.value[productId] = !expanded.value[productId];
};

const stockWarehouses = (product: ProductGroup) => product.warehouses.filter((warehouse) => warehouse.on_hand > 0);

const moreWarehouseCount = (product: ProductGroup) => Math.max(stockWarehouses(product).length - 3, 0);

const stockWmsUrl = (row: StockRow) => {
    const base = props.urls.stock_wms.split('?')[0];
    const params = new URLSearchParams({
        warehouse_id: row.warehouse_id,
        q: row.product_code,
    });

    return `${base}?${params.toString()}`;
};

const importCountLabel = computed(() => formatNumber(props.importNeeds.length));
</script>

<template>
    <Head title="Stock Operations" />
    <div class="stock-page">
        <section class="stock-hero">
            <div>
                <span class="stock-eyebrow">Operations / Warehouse</span>
                <h1>Stock Operations</h1>
                <p>Cek ketersediaan stok per gudang untuk request store dan ecommerce, serta pantau barrier untuk kebutuhan import purchasing.</p>
            </div>
            <div class="stock-hero-actions">
                <Link :href="props.urls.store_requests" class="stock-hero-button">
                    <i class="mdi mdi-store-arrow-right me-1"></i>
                    Request Store
                </Link>
                <Link :href="props.urls.ecommerce_orders" class="stock-hero-button">
                    <i class="mdi mdi-cart-arrow-right me-1"></i>
                    Order Ecommerce
                </Link>
                <Link :href="props.urls.stock_barriers" class="stock-hero-button stock-hero-button-outline">
                    <i class="mdi mdi-tune-variant me-1"></i>
                    Atur Barrier
                </Link>
            </div>
        </section>

        <div class="card stock-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-lg-6">
                        <label class="form-label">Cari produk atau gudang</label>
                        <input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Kode produk, nama produk, atau warehouse..." />
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">Warehouse</label>
                        <SearchableSelect
                            v-model="filters.warehouse_id"
                            :options="warehouseOptions"
                            placeholder="Semua warehouse"
                            search-placeholder="Cari warehouse..."
                        />
                    </div>
                    <div class="col-md-6 col-lg-3 d-flex gap-2">
                        <button class="btn btn-sm btn-primary flex-grow-1" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="stock-summary">
                    <span class="stock-summary-icon"><i class="mdi mdi-package-variant"></i></span>
                    <div>
                        <span>SKU Dipantau</span>
                        <strong>{{ formatNumber(props.summary.sku_total) }}</strong>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stock-summary">
                    <span class="stock-summary-icon"><i class="mdi mdi-warehouse"></i></span>
                    <div>
                        <span>Gudang dengan Stok</span>
                        <strong>{{ formatNumber(props.summary.warehouse_stock_count) }}</strong>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stock-summary stock-summary-alert">
                    <span class="stock-summary-icon"><i class="mdi mdi-alert-outline"></i></span>
                    <div>
                        <span>Item di Bawah Barrier</span>
                        <strong>{{ formatNumber(props.summary.below_barrier_count) }}</strong>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stock-summary">
                    <span class="stock-summary-icon"><i class="mdi mdi-truck-delivery-outline"></i></span>
                    <div>
                        <span>SKU Perlu Import</span>
                        <strong>{{ formatNumber(props.summary.import_product_count) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="stock-summary stock-summary-danger">
                    <span class="stock-summary-icon"><i class="mdi mdi-help-circle-outline"></i></span>
                    <div>
                        <span>Stok Hilang</span>
                        <strong>{{ formatNumber(props.summary.missing_stock_qty) }}</strong>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stock-summary">
                    <span class="stock-summary-icon"><i class="mdi mdi-timer-sand"></i></span>
                    <div>
                        <span>Slow Moving</span>
                        <strong>{{ formatNumber(props.summary.slow_product_count) }}</strong>
                        <small class="stock-summary-note">SKU</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stock-summary">
                    <span class="stock-summary-icon"><i class="mdi mdi-rocket-launch-outline"></i></span>
                    <div>
                        <span>Fast Moving</span>
                        <strong>{{ formatNumber(props.summary.fast_product_count) }}</strong>
                        <small class="stock-summary-note">SKU</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stock-summary stock-summary-warning">
                    <span class="stock-summary-icon"><i class="mdi mdi-calendar-alert"></i></span>
                    <div>
                        <span>FEFO ≤90 Hari</span>
                        <strong>{{ formatNumber(props.summary.expiring_90_count) }}</strong>
                        <small class="stock-summary-note">{{ formatNumber(props.summary.expired_count) }} expired</small>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="props.summary.below_barrier_count > 0" class="stock-alert">
            <i class="mdi mdi-alert-outline"></i>
            <div>
                <strong>{{ formatNumber(props.summary.below_barrier_count) }} item sudah menyentuh stock barrier.</strong>
                <span v-if="props.summary.near_barrier_count > 0">Purchasing perlu mulai menyiapkan agenda import. Ada {{ formatNumber(props.summary.near_barrier_count) }} item lain yang mendekati barrier.</span>
                <span v-else>Purchasing perlu mulai menyiapkan agenda import. Tidak ada item lain yang mendekati barrier.</span>
            </div>
            <button class="btn btn-sm btn-outline-danger" type="button" @click="activeTab = 'import'">
                Lihat Perlu Import
            </button>
        </div>



        <StoreTransferReconciliationPanel
            :reconciliation="props.transferReconciliation"
            :index-url="props.urls.outbound_report"
            :warehouse-id="filters.warehouse_id || null"
            scope-label="stok gudang"
        />

        <div class="stock-tabs" role="tablist" aria-label="Tampilan stok operasional">
            <button
                id="stock-availability-tab"
                class="stock-tab"
                :class="{ 'is-active': activeTab === 'availability' }"
                type="button"
                role="tab"
                :aria-selected="activeTab === 'availability'"
                aria-controls="stock-availability-panel"
                @click="activeTab = 'availability'"
            >
                <i class="mdi mdi-view-list-outline me-1"></i>
                Ketersediaan per Produk
            </button>
            <button
                id="stock-import-tab"
                class="stock-tab"
                :class="{ 'is-active': activeTab === 'import' }"
                type="button"
                role="tab"
                :aria-selected="activeTab === 'import'"
                aria-controls="stock-import-panel"
                @click="activeTab = 'import'"
            >
                <i class="mdi mdi-truck-delivery-outline me-1"></i>
                Perlu Import
                <span class="stock-tab-count">{{ importCountLabel }}</span>
            </button>
        </div>

        <div v-show="activeTab === 'availability'" id="stock-availability-panel" class="card stock-card" role="tabpanel" aria-labelledby="stock-availability-tab">
            <div class="stock-card-header">
                <div>
                    <h2>Ketersediaan Stok</h2>
                    <p>Stok available sudah dikurangi reservasi aktif. Klik detail untuk melihat posisi per gudang.</p>
                </div>
                <span class="stock-card-meta">{{ formatNumber(props.products.length) }} produk</span>
            </div>

            <div v-if="props.products.length === 0" class="stock-empty">
                <i class="mdi mdi-package-variant-closed"></i>
                <strong>Tidak ada stok yang cocok</strong>
                <span>Coba ubah kata kunci atau filter warehouse.</span>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 stock-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-end">Available</th>
                            <th>Gudang dengan Stok</th>
                            <th>Movement</th>
                            <th>Aging</th>
                            <th>FEFO / FIFO</th>
                            <th>Status Barrier</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="product in props.products" :key="product.product_id">
                            <tr class="stock-product-row">
                                <td>
                                    <div class="stock-product">
                                        <strong>{{ product.product_name }}</strong>
                                        <small>{{ product.product_code }}<template v-if="product.unit_name"> · {{ product.unit_name }}</template></small>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <strong class="stock-available">{{ formatNumber(product.total_available) }}</strong>
                                    <small class="stock-subvalue">{{ formatNumber(product.total_on_hand) }} on hand · {{ formatNumber(product.total_reserved) }} reserved</small>
                                </td>
                                <td>
                                    <div class="stock-warehouse-list">
                                        <span v-for="warehouse in stockWarehouses(product).slice(0, 3)" :key="warehouse.warehouse_id" class="stock-warehouse-badge">
                                            {{ warehouse.warehouse_code || warehouse.warehouse_name }}
                                            <b>{{ formatNumber(warehouse.available) }}</b>
                                        </span>
                                        <span v-if="stockWarehouses(product).length === 0" class="stock-no-stock">Tidak ada stok</span>
                                        <span v-if="moreWarehouseCount(product) > 0" class="stock-warehouse-more">+{{ moreWarehouseCount(product) }} gudang</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" :class="movementClass(product.movement_category)">{{ product.movement_label }}</span>
                                    <small class="stock-status-note">Outbound 30d {{ formatNumber(product.total_outbound_30d) }}</small>
                                </td>
                                <td>
                                    <span class="badge" :class="agingClass(product.oldest_age_days)">{{ agingLabel(product.oldest_age_days) }}</span>
                                    <small class="stock-status-note">Umur tertua {{ product.oldest_age_days === null ? '-' : `${formatNumber(product.oldest_age_days)} hari` }}</small>
                                </td>
                                <td>
                                    <span class="badge" :class="fefoClass(product.fefo_status)">{{ product.fefo_label }}</span>
                                    <small class="stock-status-note">FIFO {{ product.oldest_age_days === null ? '-' : `${formatNumber(product.oldest_age_days)} hari` }}</small>
                                </td>
                                <td>
                                    <span class="badge" :class="statusClass(product.worst_status)">{{ product.worst_status_label }}</span>
                                    <small v-if="product.below_count > 0" class="stock-status-note">{{ product.below_count }} gudang di bawah barrier</small>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="toggleProduct(product.product_id)">
                                        <i class="mdi" :class="expanded[product.product_id] ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>
                                        {{ expanded[product.product_id] ? 'Tutup' : 'Detail' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expanded[product.product_id]" class="stock-detail-row">
                                <td colspan="7">
                                    <div class="stock-detail-wrap">
                                        <table class="table table-sm mb-0 stock-detail-table">
                                            <thead>
                                                <tr>
                                                    <th>Warehouse</th>
                                                    <th class="text-end">On Hand</th>
                                                    <th class="text-end">Reserved</th>
                                                    <th class="text-end">Available</th>
                                                    <th class="text-end">Barrier</th>
                                                    <th v-if="props.showThreshold" class="text-end">Threshold</th>
                                                    <th class="text-end">Outbound 30d</th>
                                                    <th>Movement</th>
                                                    <th>FIFO</th>
                                                    <th>FEFO</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Saran Import</th>
                                                    <th class="text-end">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="warehouse in product.warehouses" :key="warehouse.warehouse_id">
                                                    <td>
                                                        <strong>{{ warehouse.warehouse_name }}</strong>
                                                        <small>{{ warehouse.warehouse_code || '—' }}</small>
                                                    </td>
                                                    <td class="text-end">{{ formatNumber(warehouse.on_hand) }}</td>
                                                    <td class="text-end">{{ formatNumber(warehouse.reserved) }}</td>
                                                    <td class="text-end"><strong>{{ formatNumber(warehouse.available) }}</strong></td>
                                                    <td class="text-end">{{ warehouse.stock_barrier === null ? '—' : formatNumber(warehouse.stock_barrier) }}</td>
                                                    <td v-if="props.showThreshold" class="text-end">{{ warehouse.threshold === null ? '—' : formatNumber(warehouse.threshold) }}</td>
                                                    <td class="text-end">{{ formatNumber(warehouse.outbound_30d) }}</td>
                                                    <td><span class="badge" :class="movementClass(warehouse.movement_category)">{{ warehouse.movement_label }}</span></td>
                                                    <td>
                                                        <strong>{{ warehouse.oldest_age_days === null ? '-' : `${formatNumber(warehouse.oldest_age_days)} hari` }}</strong>
                                                        <small>{{ warehouse.oldest_stock_at ? formatDate(warehouse.oldest_stock_at) : '-' }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge" :class="fefoClass(warehouse.fefo_status)">{{ warehouse.fefo_label }}</span>
                                                        <small>{{ warehouse.earliest_expired_date ? formatDate(warehouse.earliest_expired_date) : '-' }}</small>
                                                    </td>
                                                    <td><span class="badge" :class="statusClass(warehouse.status)">{{ warehouse.status_label }}</span></td>
                                                    <td class="text-end">{{ warehouse.suggested_import_qty > 0 ? `+${formatNumber(warehouse.suggested_import_qty)}` : '—' }}</td>
                                                    <td class="text-end">
                                                        <Link :href="stockWmsUrl(warehouse)" class="btn btn-sm btn-outline-primary">Stok WMS</Link>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-show="activeTab === 'import'" id="stock-import-panel" class="card stock-card" role="tabpanel" aria-labelledby="stock-import-tab">
            <div class="stock-card-header">
                <div>
                    <h2>Perlu Import</h2>
                    <p>Item yang sudah menyentuh barrier atau mendekati ambang toleransi. Saran import memakai threshold barrier.</p>
                </div>
                <span class="stock-card-meta">{{ importCountLabel }} item</span>
            </div>

            <div v-if="props.importNeeds.length === 0" class="stock-empty">
                <i class="mdi mdi-check-circle-outline"></i>
                <strong>Tidak ada item yang menyentuh barrier</strong>
                <span>Stok gudang masih di atas ambang barrier untuk filter ini.</span>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 stock-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Warehouse</th>
                            <th class="text-end">Available</th>
                            <th class="text-end">Barrier</th>
                            <th v-if="props.showThreshold" class="text-end">Threshold</th>
                            <th class="text-end">Saran Import</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.importNeeds" :key="`${row.warehouse_id}-${row.product_id}`">
                            <td>
                                <div class="stock-product">
                                    <strong>{{ row.product_name }}</strong>
                                    <small>{{ row.product_code }}<template v-if="row.unit_name"> · {{ row.unit_name }}</template></small>
                                </div>
                            </td>
                            <td>
                                <strong>{{ row.warehouse_name }}</strong>
                                <small class="stock-subvalue">{{ row.warehouse_code || '—' }}</small>
                            </td>
                            <td class="text-end">{{ formatNumber(row.available) }}</td>
                            <td class="text-end">{{ row.stock_barrier === null ? '—' : formatNumber(row.stock_barrier) }}</td>
                            <td v-if="props.showThreshold" class="text-end">{{ row.threshold === null ? '—' : formatNumber(row.threshold) }}</td>
                            <td class="text-end">
                                <strong v-if="row.suggested_import_qty > 0" class="stock-import-qty">+{{ formatNumber(row.suggested_import_qty) }}</strong>
                                <span v-else class="text-muted">Pantau</span>
                            </td>
                            <td><span class="badge" :class="statusClass(row.status)">{{ row.status_label }}</span></td>
                            <td class="text-end">
                                <Link :href="stockWmsUrl(row)" class="btn btn-sm btn-outline-primary">Stok WMS</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card stock-card mb-3">
            <div class="stock-card-header">
                <div>
                    <h2>Umur Stok (Aging)</h2>
                    <p>Distribusi item warehouse_stock berdasarkan tanggal masuk atau tanggal item dibuat.</p>
                </div>
                <span class="stock-card-meta">{{ formatNumber(props.summary.aging_buckets.bucket_0_30 + props.summary.aging_buckets.bucket_31_90 + props.summary.aging_buckets.bucket_91_180 + props.summary.aging_buckets.bucket_181_365 + props.summary.aging_buckets.bucket_gt_365) }} item</span>
            </div>
            <div class="stock-aging-grid">
                <div><span>0-30 hari</span><strong>{{ formatNumber(props.summary.aging_buckets.bucket_0_30) }}</strong></div>
                <div><span>31-90 hari</span><strong>{{ formatNumber(props.summary.aging_buckets.bucket_31_90) }}</strong></div>
                <div><span>91-180 hari</span><strong>{{ formatNumber(props.summary.aging_buckets.bucket_91_180) }}</strong></div>
                <div><span>181-365 hari</span><strong>{{ formatNumber(props.summary.aging_buckets.bucket_181_365) }}</strong></div>
                <div><span>&gt;365 hari</span><strong>{{ formatNumber(props.summary.aging_buckets.bucket_gt_365) }}</strong></div>
            </div>
        </div>

        <details class="card stock-card stock-recon mb-3" :open="props.summary.reconciliation.inbound_only_count > 0">
            <summary class="stock-card-header stock-recon-summary">
                <div>
                    <h2>Rekonsiliasi SKU Stok vs Inbound</h2>
                    <p>Selisih berasal dari SKU yang punya riwayat inbound tetapi tidak punya item warehouse_stock saat ini.</p>
                </div>
                <span class="stock-card-meta">
                    {{ formatNumber(props.summary.reconciliation.inbound_only_count) }} SKU selisih
                    <i class="mdi mdi-chevron-down stock-recon-chevron"></i>
                </span>
            </summary>
            <div class="stock-recon-body">
                <div class="stock-recon-grid">
                    <div><span>SKU Stok Aktif</span><strong>{{ formatNumber(props.summary.reconciliation.stock_sku_total) }}</strong></div>
                    <div><span>SKU Inbound</span><strong>{{ formatNumber(props.summary.reconciliation.inbound_sku_total) }}</strong></div>
                    <div><span>Ada Inbound, Stok Kosong</span><strong>{{ formatNumber(props.summary.reconciliation.inbound_only_count) }}</strong></div>
                    <div><span>Ada Stok, Tanpa Inbound</span><strong>{{ formatNumber(props.summary.reconciliation.stock_only_count) }}</strong></div>
                </div>
                <p class="stock-recon-note">
                    SKU berikut tercatat di inbound tetapi tidak memiliki item berstatus warehouse_stock. Kemungkinan sudah keluar, masih pending, atau belum diproses menjadi item stok.
                </p>
                <div v-if="props.summary.reconciliation.inbound_only.length > 0" class="table-responsive">
                    <table class="table table-sm mb-0 stock-detail-table">
                        <thead><tr><th>Produk</th><th class="text-end">Qty Inbound</th><th class="text-end">Referensi</th><th>Inbound Terakhir</th></tr></thead>
                        <tbody>
                            <tr v-for="row in props.summary.reconciliation.inbound_only" :key="row.product_id">
                                <td><strong>{{ row.product_name }}</strong><small>{{ row.product_code }}</small></td>
                                <td class="text-end">{{ formatNumber(row.inbound_qty) }}</td>
                                <td class="text-end">{{ formatNumber(row.document_count) }}</td>
                                <td>{{ formatDate(row.last_inbound_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-muted small mb-0">Tidak ada selisih SKU inbound-only.</p>
            </div>
        </details>

        <details v-if="props.missingStock.length > 0" class="card stock-card mb-3">
            <summary class="stock-card-header stock-recon-summary">
                <div>
                    <h2>Stok Hilang</h2>
                    <p>Item dengan status missing yang masih tercatat di warehouse.</p>
                </div>
                <span class="stock-card-meta">{{ formatNumber(props.summary.missing_stock_qty) }} item <i class="mdi mdi-chevron-down stock-recon-chevron"></i></span>
            </summary>
            <div class="table-responsive">
                <table class="table table-sm mb-0 stock-detail-table">
                    <thead><tr><th>Warehouse</th><th>Produk</th><th class="text-end">Qty Hilang</th><th>Terakhir Update</th></tr></thead>
                    <tbody>
                        <tr v-for="row in props.missingStock" :key="`${row.warehouse_id}-${row.product_id}`">
                            <td><strong>{{ row.warehouse_name }}</strong><small>{{ row.warehouse_code || '-' }}</small></td>
                            <td><strong>{{ row.product_name }}</strong><small>{{ row.product_code }}</small></td>
                            <td class="text-end">{{ formatNumber(row.qty) }}</td>
                            <td>{{ formatDate(row.last_seen_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>

    </div>
</template>

<style scoped>
.stock-page { color: var(--app-ink); }
.stock-hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.stock-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.stock-hero h1 { margin: 0 0 0.35rem; color: var(--app-hero-text); font-size: 1.5rem; }
.stock-hero p { max-width: 760px; margin: 0; color: var(--app-hero-muted); }
.stock-hero-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.stock-hero-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface); color: var(--app-ink); font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.stock-hero-button-outline { border: 1px solid var(--app-hero-muted); background: transparent; color: var(--app-hero-text); }
.stock-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.stock-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.stock-summary span:not(.stock-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.stock-summary strong { display: block; margin-top: 0.15rem; font-size: 1.35rem; }
.stock-summary-alert .stock-summary-icon,
.stock-summary-danger .stock-summary-icon { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.stock-summary-warning .stock-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.stock-summary-note { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: 0.68rem; }
.stock-alert { display: flex; align-items: center; gap: 0.8rem; padding: 0.9rem 1rem; margin-bottom: 1rem; border: 1px solid color-mix(in srgb, var(--app-danger-ink) 25%, var(--app-border)); border-radius: 0.8rem; background: var(--app-danger-bg); color: var(--app-danger-ink); }
.stock-alert > i { font-size: 1.3rem; }
.stock-alert > div { flex: 1 1 320px; }
.stock-alert strong { display: block; }
.stock-alert span { font-size: 0.82rem; }
.stock-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.stock-aging-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.5rem; padding: 1rem 1.1rem; }
.stock-aging-grid > div { padding: 0.7rem; border: 1px solid var(--app-border); border-radius: 0.6rem; background: var(--app-surface-2); }
.stock-aging-grid span { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
.stock-aging-grid strong { display: block; margin-top: 0.2rem; font-size: 1.1rem; }
.stock-recon { overflow: hidden; }
.stock-recon-summary { cursor: pointer; list-style: none; }
.stock-recon-summary::-webkit-details-marker { display: none; }
.stock-recon-summary:focus-visible { outline: 2px solid var(--app-accent); outline-offset: -2px; }
.stock-recon-chevron { margin-left: 0.35rem; transition: transform 160ms ease; }
.stock-recon[open] .stock-recon-chevron { transform: rotate(180deg); }
.stock-recon:not([open]) .stock-card-header { border-bottom: 0; }
.stock-recon-body { padding: 1rem 1.1rem; }
.stock-recon-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.6rem; margin-bottom: 0.8rem; }
.stock-recon-grid > div { padding: 0.6rem 0.7rem; border-radius: 0.55rem; background: var(--app-surface-2); }
.stock-recon-grid span { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
.stock-recon-grid strong { display: block; margin-top: 0.15rem; font-size: 1rem; }
.stock-recon-note { color: var(--app-muted); font-size: 0.8rem; }
.stock-tabs { display: flex; gap: 0.4rem; margin-bottom: 0.6rem; overflow-x: auto; }
.stock-tab { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.55rem 0.8rem; border: 1px solid transparent; border-radius: 0.55rem; background: transparent; color: var(--app-muted); font-size: 0.82rem; font-weight: 700; white-space: nowrap; }
.stock-tab:hover { background: var(--app-surface-2); color: var(--app-ink); }
.stock-tab.is-active { border-color: var(--app-accent); background: var(--app-accent-soft); color: var(--app-accent-ink); }
.stock-tab-count { padding: 0.05rem 0.35rem; border-radius: 999px; background: var(--app-surface); color: var(--app-accent-ink); font-size: 0.7rem; }
.stock-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.stock-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.stock-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.stock-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.stock-table th { padding: 0.6rem 0.9rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.stock-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); color: var(--app-ink); }
.stock-product strong { display: block; font-size: 0.86rem; }
.stock-product small, .stock-subvalue { display: block; color: var(--app-muted); font-size: 0.73rem; }
.stock-available { font-size: 1rem; }
.stock-warehouse-list { display: flex; flex-wrap: wrap; gap: 0.3rem; }
.stock-warehouse-badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.18rem 0.4rem; border-radius: 0.35rem; background: var(--app-surface-2); border: 1px solid var(--app-border); color: var(--app-muted); font-size: 0.72rem; }
.stock-warehouse-badge b { color: var(--app-ink); }
.stock-warehouse-more { color: var(--app-muted); font-size: 0.72rem; align-self: center; }
.stock-no-stock { color: var(--app-muted); font-size: 0.75rem; }
.stock-status-note { display: block; margin-top: 0.3rem; color: var(--app-muted); font-size: 0.72rem; }
.stock-detail-row > td { padding: 0 !important; background: var(--app-surface-2); }
.stock-detail-wrap { padding: 0.5rem 1rem 1rem 2.2rem; }
.stock-detail-table th { padding: 0.45rem 0.55rem; background: transparent; font-size: 0.64rem; }
.stock-detail-table td { padding: 0.55rem; font-size: 0.78rem; }
.stock-detail-table td strong, .stock-detail-table td small { display: block; }
.stock-detail-table td small { color: var(--app-muted); font-size: 0.7rem; }
.stock-import-qty { color: var(--app-danger-ink); }
.stock-empty { display: grid; place-items: center; gap: 0.3rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.stock-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.stock-empty strong { color: var(--app-ink); }
@media (max-width: 991.98px) {
    .stock-hero { align-items: flex-start; flex-direction: column; }
    .stock-card-header { align-items: flex-start; flex-direction: column; }
    .stock-detail-wrap { padding-left: 0.75rem; }
    .stock-aging-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .stock-recon-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575.98px) {
    .stock-alert { align-items: flex-start; flex-direction: column; }
}
</style>
