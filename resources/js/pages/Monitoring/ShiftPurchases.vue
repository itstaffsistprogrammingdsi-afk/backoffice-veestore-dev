<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { formatCurrency, formatDate, formatNumber } from '@/composables/useFormatters';

defineOptions({ layout: AppLayout });

interface ShiftRow {
    report_date: string;
    period_label: string;
    store_id: string;
    store_name: string;
    shift_id: string | null;
    shift_name: string;
    shift_code: string | null;
    shift_start_time: string | null;
    total_cashiers: number;
    total_sessions: number;
    total_transactions: number;
    total_items: number;
    total_products: number;
    gross_amount: number;
    discount_amount: number;
    net_amount: number;
    payment_total: number;
    qty_good: number;
    qty_good_after_repair: number;
    qty_clearance: number;
    clearance_discount: number;
    clearance_percentage: number;
}

interface ProductRow {
    product_id: string;
    product_code: string;
    product_name: string;
    qty: number;
    gross_amount: number;
    discount_amount: number;
    net_amount: number;
    session_count: number;
    qty_good: number;
    qty_good_after_repair: number;
    qty_clearance: number;
    clearance_discount: number;
    clearance_percentage: number;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface DetailSession {
    cashier_session_id: string;
    session_number: string | null;
    store_name: string;
    cashier_name: string;
    shift_name: string;
    shift_code: string | null;
    shift_start_time: string | null;
    cashier_status: string;
    opened_at: string | null;
    closed_at: string | null;
    total_transactions: number;
    total_items: number;
    total_products: number;
    gross_amount: number;
    discount_amount: number;
    net_amount: number;
    payment_total: number;
    qty_good: number;
    qty_good_after_repair: number;
    qty_clearance: number;
    clearance_discount: number;
    clearance_percentage: number;
}

interface DetailTransaction {
    id: string;
    transaction_number: string | null;
    total_amount: number;
    paid_amount: number;
    change_amount: number;
    completed_at: string | null;
    status: string;
    customer_name: string;
    cashier_name: string | null;
    cashier_session_id: string;
}

interface DetailResponse {
    sessions: DetailSession[];
    products: ProductRow[];
    transactions: DetailTransaction[];
    summary: {
        total_sessions: number;
        total_transactions: number;
        total_items: number;
        gross_amount: number;
        discount_amount: number;
        net_amount: number;
        qty_good: number;
        qty_good_after_repair: number;
        qty_clearance: number;
        clearance_discount: number;
        clearance_percentage: number;
    };
}

interface Props {
    rows: Paginator<ShiftRow>;
    summary: {
        total_shifts: number;
        total_sessions: number;
        total_transactions: number;
        total_items: number;
        total_products: number;
        gross_amount: number;
        discount_amount: number;
        net_amount: number;
        payment_total: number;
        qty_good: number;
        qty_good_after_repair: number;
        qty_clearance: number;
        clearance_discount: number;
        clearance_percentage: number;
    };
    productRows: ProductRow[];
    stores: Array<{ id: string; name: string; code: string | null }>;
    shifts: Array<{ id: string; store_id: string; name: string; code: string | null; start_time: string | null; end_time: string | null; store_name: string }>;
    cashiers: Array<{ id: string; name: string }>;
    filters: {
        q: string;
        store_id: string | null;
        shift_id: string | null;
        cashier_id: string | null;
        from: string;
        to: string;
        per_page: number;
    };
    urls: {
        index: string;
        detail: string;
        export: string;
        shift_report: string;
    };
}

const props = defineProps<Props>();

const filters = reactive({
    q: props.filters.q ?? '',
    store_id: props.filters.store_id ?? '',
    shift_id: props.filters.shift_id ?? '',
    cashier_id: props.filters.cashier_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

watch(() => filters.store_id, () => {
    filters.shift_id = '';
});

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
    hint: store.code ?? undefined,
})));

const shiftOptions = computed(() => props.shifts
    .filter((shift) => !filters.store_id || shift.store_id === filters.store_id)
    .map((shift) => ({
        value: shift.id,
        label: `${shift.store_name} · ${shift.name}`,
        hint: shift.code ?? undefined,
    })));

const cashierOptions = computed(() => props.cashiers.map((cashier) => ({
    value: cashier.id,
    label: cashier.name,
})));

const detailOpen = ref(false);
const detailLoading = ref(false);
const detailError = ref('');
const detail = ref<DetailResponse | null>(null);
const detailLabel = ref('');

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    if (filters.q) params.set('q', filters.q);
    if (filters.store_id) params.set('store_id', filters.store_id);
    if (filters.shift_id) params.set('shift_id', filters.shift_id);
    if (filters.cashier_id) params.set('cashier_id', filters.cashier_id);
    if (filters.from) params.set('from', filters.from);
    if (filters.to) params.set('to', filters.to);

    const query = params.toString();

    return query ? `${props.urls.export}?${query}` : props.urls.export;
});

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        store_id: filters.store_id || undefined,
        shift_id: filters.shift_id || undefined,
        cashier_id: filters.cashier_id || undefined,
        from: filters.from || undefined,
        to: filters.to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    filters.q = '';
    filters.store_id = '';
    filters.shift_id = '';
    filters.cashier_id = '';
    filters.from = '';
    filters.to = '';
    applyFilters();
};

const openDetail = async (row: ShiftRow) => {
    detailOpen.value = true;
    detailLoading.value = true;
    detailError.value = '';
    detail.value = null;
    detailLabel.value = `${row.store_name} · ${row.shift_name} · ${row.period_label}`;

    try {
        const { data } = await axios.get(props.urls.detail, {
            params: {
                date: row.report_date,
                store_id: row.store_id,
                shift_id: row.shift_id || undefined,
            },
        });

        detail.value = data;
    } catch {
        detailError.value = 'Detail pembelian shift tidak dapat dimuat.';
    } finally {
        detailLoading.value = false;
    }
};

const closeDetail = () => {
    detailOpen.value = false;
    detail.value = null;
    detailError.value = '';
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && detailOpen.value) {
        closeDetail();
    }
};

watch(detailOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Head title="Laporan Pembelian per Shift" />

    <div class="purchase-page">
        <section class="purchase-header">
            <div>
                <span class="purchase-eyebrow">Monitoring / Penjualan</span>
                <h1>Laporan Pembelian per Shift</h1>
                <p>Rincian item yang dibeli pelanggan pada setiap shift, lengkap dengan gross, diskon, net, transaksi, kondisi barang, dan diskon clearance. Gimmick/gratis tidak dihitung sebagai omset.</p>
            </div>
            <div class="purchase-header-actions">
                <Link :href="props.urls.shift_report" class="purchase-button purchase-button-light">
                    <i class="mdi mdi-account-clock-outline me-1"></i>Shift Kasir
                </Link>
                <a :href="exportUrl" class="purchase-button purchase-button-primary">
                    <i class="mdi mdi-file-excel-outline me-1"></i>Ekspor
                </a>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="purchase-summary">
                    <span class="purchase-summary-icon"><i class="mdi mdi-calendar-clock"></i></span>
                    <div><span>Shift</span><strong>{{ formatNumber(props.summary.total_shifts) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="purchase-summary">
                    <span class="purchase-summary-icon"><i class="mdi mdi-receipt-text-outline"></i></span>
                    <div><span>Transaksi</span><strong>{{ formatNumber(props.summary.total_transactions) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="purchase-summary">
                    <span class="purchase-summary-icon"><i class="mdi mdi-basket-outline"></i></span>
                    <div><span>Item Terjual</span><strong>{{ formatNumber(props.summary.total_items) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="purchase-summary">
                    <span class="purchase-summary-icon"><i class="mdi mdi-cash-multiple"></i></span>
                    <div><span>Gross</span><strong>{{ formatCurrency(props.summary.gross_amount) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="purchase-summary purchase-summary-warning">
                    <span class="purchase-summary-icon"><i class="mdi mdi-sale-outline"></i></span>
                    <div><span>Diskon</span><strong>{{ formatCurrency(props.summary.discount_amount) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="purchase-summary purchase-summary-success">
                    <span class="purchase-summary-icon"><i class="mdi mdi-cash-check"></i></span>
                    <div><span>Net</span><strong>{{ formatCurrency(props.summary.net_amount) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="purchase-summary">
                    <span class="purchase-summary-icon"><i class="mdi mdi-check-decagram-outline"></i></span>
                    <div><span>Good / GAR</span><strong>{{ formatNumber(props.summary.qty_good) }} / {{ formatNumber(props.summary.qty_good_after_repair) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="purchase-summary purchase-summary-warning">
                    <span class="purchase-summary-icon"><i class="mdi mdi-tag-arrow-down-outline"></i></span>
                    <div><span>Diskon Clearance</span><strong>{{ formatCurrency(props.summary.clearance_discount) }}</strong><small class="purchase-subvalue">{{ formatNumber(props.summary.clearance_percentage) }}% dari nilai clearance</small></div>
                </div>
            </div>
        </div>

        <div class="card purchase-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Cari store, kasir, atau produk</label>
                        <input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nama store, kasir, sesi, atau produk..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Store</label>
                        <SearchableSelect v-model="filters.store_id" :options="storeOptions" placeholder="Semua store" search-placeholder="Cari store..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Shift</label>
                        <SearchableSelect v-model="filters.shift_id" :options="shiftOptions" placeholder="Semua shift" search-placeholder="Cari shift..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Kasir</label>
                        <SearchableSelect v-model="filters.cashier_id" :options="cashierOptions" placeholder="Semua kasir" search-placeholder="Cari kasir..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-1">
                        <label class="form-label">Dari</label>
                        <input v-model="filters.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-4 col-lg-1">
                        <label class="form-label">Sampai</label>
                        <input v-model="filters.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card purchase-card mb-3">
            <div class="purchase-card-header">
                <div><h2>Pembelian per Shift</h2><p>Agregasi pembelian berdasarkan tanggal, store, dan shift kasir.</p></div>
                <span class="purchase-card-meta">{{ formatNumber(props.rows.total) }} shift</span>
            </div>

            <div v-if="props.rows.data.length === 0" class="purchase-empty">
                <i class="mdi mdi-basket-off-outline"></i>
                <strong>Belum ada pembelian pada periode ini</strong>
                <span>Coba ubah filter tanggal, store, atau shift.</span>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 purchase-table">
                    <thead>
                        <tr><th>Tanggal</th><th>Store / Shift</th><th class="text-end">Kasir / Sesi</th><th class="text-end">Transaksi / Item</th><th class="text-end">Kondisi / Clearance</th><th class="text-end">Gross</th><th class="text-end">Diskon</th><th class="text-end">Net</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.rows.data" :key="`${row.report_date}-${row.store_id}-${row.shift_id ?? 'none'}`">
                            <td><strong>{{ row.period_label }}</strong><small class="purchase-subvalue">{{ row.shift_start_time ? `Mulai ${row.shift_start_time}` : 'Tanpa jam shift' }}</small></td>
                            <td><strong>{{ row.store_name }}</strong><small class="purchase-subvalue">{{ row.shift_name }}<span v-if="row.shift_code"> · {{ row.shift_code }}</span></small></td>
                            <td class="text-end"><strong>{{ formatNumber(row.total_cashiers) }}</strong><small class="purchase-subvalue">{{ formatNumber(row.total_sessions) }} sesi</small></td>
                            <td class="text-end"><strong>{{ formatNumber(row.total_transactions) }}</strong><small class="purchase-subvalue">{{ formatNumber(row.total_items) }} item · {{ formatNumber(row.total_products) }} SKU</small></td>
                            <td class="text-end">
                                <strong>{{ formatNumber(row.qty_good) }} good / {{ formatNumber(row.qty_good_after_repair) }} GAR</strong>
                                <small class="purchase-subvalue">Clearance {{ formatCurrency(row.clearance_discount) }} ({{ formatNumber(row.clearance_percentage) }}%)</small>
                            </td>
                            <td class="text-end">{{ formatCurrency(row.gross_amount) }}</td>
                            <td class="text-end">{{ formatCurrency(row.discount_amount) }}</td>
                            <td class="text-end"><strong>{{ formatCurrency(row.net_amount) }}</strong></td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-secondary" type="button" @click="openDetail(row)"><i class="mdi mdi-eye-outline"></i>Detail</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="purchase-footer">
                <small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} shift</small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </div>

        <div class="card purchase-card">
            <div class="purchase-card-header">
                <div><h2>Produk Terbanyak Dibeli</h2><p>Top 100 produk berdasarkan qty pada periode filter.</p></div>
                <span class="purchase-card-meta">{{ formatNumber(props.productRows.length) }} produk</span>
            </div>
            <div v-if="props.productRows.length === 0" class="purchase-empty">
                <i class="mdi mdi-package-variant"></i>
                <strong>Belum ada produk terjual</strong>
                <span>Produk akan muncul setelah ada transaksi paid pada shift.</span>
            </div>
            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 purchase-table">
                    <thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Good / GAR</th><th class="text-end">Clearance</th><th class="text-end">Gross</th><th class="text-end">Diskon</th><th class="text-end">Net</th><th class="text-end">Sesi</th></tr></thead>
                    <tbody>
                        <tr v-for="row in props.productRows" :key="row.product_id">
                            <td><div class="purchase-main"><strong>{{ row.product_name }}</strong><small>{{ row.product_code }}</small></div></td>
                            <td class="text-end"><strong>{{ formatNumber(row.qty) }}</strong></td>
                            <td class="text-end">{{ formatNumber(row.qty_good) }} / {{ formatNumber(row.qty_good_after_repair) }}</td>
                            <td class="text-end">{{ formatCurrency(row.clearance_discount) }} ({{ formatNumber(row.clearance_percentage) }}%)</td>
                            <td class="text-end">{{ formatCurrency(row.gross_amount) }}</td>
                            <td class="text-end">{{ formatCurrency(row.discount_amount) }}</td>
                            <td class="text-end">{{ formatCurrency(row.net_amount) }}</td>
                            <td class="text-end">{{ formatNumber(row.session_count) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="detailOpen" class="purchase-backdrop" @click.self="closeDetail">
            <div class="purchase-modal" role="dialog" aria-modal="true" aria-labelledby="purchase-detail-title">
                <div class="purchase-modal-header">
                    <div>
                        <h5 id="purchase-detail-title" class="mb-0">Detail Pembelian Shift</h5>
                        <small>{{ detailLabel }}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup detail" @click="closeDetail"><i class="mdi mdi-close"></i></button>
                </div>

                <div class="purchase-modal-body">
                    <div v-if="detailLoading" class="purchase-hint"><span class="spinner-border spinner-border-sm me-2"></span>Memuat detail...</div>
                    <div v-else-if="detailError" class="purchase-hint purchase-hint-danger" role="alert">{{ detailError }}</div>
                    <template v-else-if="detail">
                        <div class="purchase-detail-grid">
                            <div><span>Sesi</span><strong>{{ formatNumber(detail.summary.total_sessions) }}</strong></div>
                            <div><span>Transaksi</span><strong>{{ formatNumber(detail.summary.total_transactions) }}</strong></div>
                            <div><span>Item</span><strong>{{ formatNumber(detail.summary.total_items) }}</strong></div>
                            <div><span>Gross</span><strong>{{ formatCurrency(detail.summary.gross_amount) }}</strong></div>
                            <div><span>Diskon</span><strong>{{ formatCurrency(detail.summary.discount_amount) }}</strong></div>
                            <div><span>Net</span><strong>{{ formatCurrency(detail.summary.net_amount) }}</strong></div>
                            <div><span>Good / GAR</span><strong>{{ formatNumber(detail.summary.qty_good) }} / {{ formatNumber(detail.summary.qty_good_after_repair) }}</strong></div>
                            <div><span>Diskon Clearance</span><strong>{{ formatCurrency(detail.summary.clearance_discount) }}</strong><small>{{ formatNumber(detail.summary.clearance_percentage) }}%</small></div>
                        </div>

                        <h6 class="purchase-section-title">Sesi Kasir</h6>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 purchase-detail-table">
                                <thead><tr><th>Sesi</th><th>Kasir</th><th>Shift</th><th>Status</th><th class="text-end">Trx</th><th class="text-end">Item</th><th class="text-end">Net</th></tr></thead>
                                <tbody>
                                    <tr v-for="session in detail.sessions" :key="session.cashier_session_id">
                                        <td>{{ session.session_number || '-' }}<small>{{ formatDate(session.opened_at, true) }}</small></td>
                                        <td>{{ session.cashier_name }}</td>
                                        <td>{{ session.shift_name }}<small>{{ session.shift_code || '-' }}</small></td>
                                        <td><span class="badge" :class="session.cashier_status === 'open' ? 'bg-label-info' : 'bg-label-success'">{{ session.cashier_status }}</span></td>
                                        <td class="text-end">{{ formatNumber(session.total_transactions) }}</td>
                                        <td class="text-end">{{ formatNumber(session.total_items) }}</td>
                                        <td class="text-end">{{ formatCurrency(session.net_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="purchase-section-title">Produk Dibeli</h6>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 purchase-detail-table">
                                <thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Good / GAR</th><th class="text-end">Clearance</th><th class="text-end">Gross</th><th class="text-end">Diskon</th><th class="text-end">Net</th></tr></thead>
                                <tbody>
                                    <tr v-for="product in detail.products" :key="product.product_id">
                                        <td>{{ product.product_name }}<small>{{ product.product_code }}</small></td>
                                        <td class="text-end">{{ formatNumber(product.qty) }}</td>
                                        <td class="text-end">{{ formatNumber(product.qty_good) }} / {{ formatNumber(product.qty_good_after_repair) }}</td>
                                        <td class="text-end">{{ formatCurrency(product.clearance_discount) }} ({{ formatNumber(product.clearance_percentage) }}%)</td>
                                        <td class="text-end">{{ formatCurrency(product.gross_amount) }}</td>
                                        <td class="text-end">{{ formatCurrency(product.discount_amount) }}</td>
                                        <td class="text-end">{{ formatCurrency(product.net_amount) }}</td>
                                    </tr>
                                    <tr v-if="detail.products.length === 0"><td colspan="7" class="text-center text-muted py-3">Tidak ada produk.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="purchase-section-title">Transaksi</h6>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 purchase-detail-table">
                                <thead><tr><th>Transaksi</th><th>Waktu</th><th>Kasir</th><th>Customer</th><th class="text-end">Total</th></tr></thead>
                                <tbody>
                                    <tr v-for="transaction in detail.transactions" :key="transaction.id">
                                        <td>{{ transaction.transaction_number || '-' }}</td>
                                        <td>{{ formatDate(transaction.completed_at, true) }}</td>
                                        <td>{{ transaction.cashier_name || '-' }}</td>
                                        <td>{{ transaction.customer_name }}</td>
                                        <td class="text-end">{{ formatCurrency(transaction.total_amount) }}</td>
                                    </tr>
                                    <tr v-if="detail.transactions.length === 0"><td colspan="5" class="text-center text-muted py-3">Tidak ada transaksi.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>

                <div class="purchase-modal-footer">
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="closeDetail">Tutup</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.purchase-page { color: var(--app-ink); }
.purchase-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; }
.purchase-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.purchase-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.purchase-header p { max-width: 760px; margin: 0; color: var(--app-hero-muted) !important; }
.purchase-header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.purchase-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border-radius: 0.55rem; font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.purchase-button-light { background: var(--app-surface); color: var(--app-ink); }
.purchase-button-primary { background: var(--app-accent-strong); color: var(--app-on-accent); }
.purchase-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.purchase-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.purchase-summary span:not(.purchase-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.purchase-summary strong { display: block; margin-top: 0.15rem; font-size: 1.3rem; }
.purchase-summary-warning .purchase-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.purchase-summary-success .purchase-summary-icon { background: var(--app-success-bg, var(--app-accent-soft)); color: var(--app-success-ink, var(--app-accent-ink)); }
.purchase-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.purchase-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.purchase-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.purchase-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.purchase-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.purchase-table th { padding: 0.6rem 0.9rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.purchase-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); color: var(--app-ink); }
.purchase-main strong, .purchase-main small, .purchase-subvalue { display: block; }
.purchase-main strong { font-size: 0.84rem; }
.purchase-main small, .purchase-subvalue { color: var(--app-muted); font-size: 0.72rem; }
.purchase-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.purchase-empty { display: grid; place-items: center; gap: 0.3rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.purchase-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.purchase-empty strong { color: var(--app-ink); }
.purchase-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: flex-start; justify-content: center; padding: min(6vh, 3rem) 1rem 1rem; overflow-y: auto; background: rgba(15, 23, 42, 0.62); }
.purchase-modal { width: min(980px, 100%); overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.28); color: var(--app-ink); }
.purchase-modal-header, .purchase-modal-footer { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; }
.purchase-modal-header { border-bottom: 1px solid var(--app-border); }
.purchase-modal-footer { align-items: center; justify-content: flex-end; border-top: 1px solid var(--app-border); background: var(--app-surface-2); }
.purchase-modal-body { padding: 1rem 1.1rem; }
.purchase-hint { display: flex; align-items: center; gap: 0.35rem; padding: 0.7rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.8rem; }
.purchase-hint-danger { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.purchase-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.65rem 1rem; margin-bottom: 1rem; }
.purchase-detail-grid span { display: block; color: var(--app-muted); font-size: 0.66rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.purchase-detail-grid strong { display: block; margin-top: 0.15rem; font-size: 0.9rem; }
.purchase-section-title { margin: 1.1rem 0 0.5rem; font-size: 0.85rem; }
.purchase-detail-table th { padding: 0.45rem 0.55rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.64rem; text-transform: uppercase; }
.purchase-detail-table td { padding: 0.5rem 0.55rem; font-size: 0.78rem; border-color: var(--app-border); }
.purchase-detail-table td small { display: block; color: var(--app-muted); font-size: 0.7rem; }
@media (max-width: 991.98px) {
    .purchase-header { align-items: flex-start; flex-direction: column; }
    .purchase-card-header { align-items: flex-start; flex-direction: column; }
}
</style>
