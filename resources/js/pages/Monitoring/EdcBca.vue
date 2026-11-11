<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { formatCurrency, formatDate, formatNumber } from '@/composables/useFormatters';

defineOptions({ layout: AppLayout });

interface Metadata {
    terminal_id: string | null;
    approval_code: string | null;
    batch_number: string | null;
    card_number: string | null;
    card_type: string | null;
    reference_number: string | null;
    acquirer: string | null;
    issuer: string | null;
    merchant_id: string | null;
}

interface EdcRow {
    id: string;
    payment_id: string;
    amount: number;
    detail_status: string;
    transaction_ref: string | null;
    ref: string | null;
    event_at: string | null;
    event_date: string | null;
    transaction_number: string | null;
    payment_status: string;
    total_amount: number;
    completed_at: string | null;
    payment_method_name: string;
    payment_method_type: string;
    store_name: string;
    channel: string;
    channel_label: string;
    cashier_name: string | null;
    ecommerce_order_number: string | null;
    mdr_rate: number;
    mdr_amount: number;
    net_amount: number;
    metadata: Metadata;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface MethodSummary {
    payment_method_name: string;
    total_transactions: number;
    gross_amount: number;
    mdr_amount: number;
    net_amount: number;
}

interface StoreSummary {
    store_name: string;
    total_transactions: number;
    gross_amount: number;
}

interface DaySummary {
    event_date: string;
    total_transactions: number;
    gross_amount: number;
}

interface Props {
    rows: Paginator<EdcRow>;
    summary: {
        total_transactions: number;
        gross_amount: number;
        mdr_amount: number;
        net_amount: number;
    };
    byMethod: MethodSummary[];
    byStore: StoreSummary[];
    byDay: DaySummary[];
    methods: Array<{ id: string; name: string; type: string }>;
    statuses: string[];
    stores: Array<{ id: string; name: string; code: string | null }>;
    mdr: { credit_percent: number; debit_percent: number; settlement_days: number };
    readiness: { has_edc_bca_method: boolean; method_names: string[] };
    filters: {
        q: string;
        store_id: string | null;
        method_id: string | null;
        status: string | null;
        from: string;
        to: string;
        per_page: number;
    };
    urls: {
        index: string;
        export: string;
        payment_methods: string;
        payments_page: string;
    };
}

const props = defineProps<Props>();

const filters = reactive({
    q: props.filters.q ?? '',
    store_id: props.filters.store_id ?? '',
    method_id: props.filters.method_id ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const expanded = ref<Record<string, boolean>>({});

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
    hint: store.code ?? undefined,
})));

const methodOptions = computed(() => props.methods.map((method) => ({
    value: method.id,
    label: method.name,
    hint: method.type,
})));

const averageTicket = computed(() => {
    const count = Number(props.summary.total_transactions ?? 0);

    return count > 0 ? Number(props.summary.gross_amount ?? 0) / count : 0;
});

const statusClass = (status: string) => ({
    success: 'bg-label-success',
    failed: 'bg-label-danger',
    refunded: 'bg-label-warning',
}[status] ?? 'bg-label-secondary');

const statusLabel = (status: string) => ({
    success: 'Berhasil',
    failed: 'Gagal',
    refunded: 'Refund',
}[status] ?? (status || '-'));

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        store_id: filters.store_id || undefined,
        method_id: filters.method_id || undefined,
        status: filters.status || undefined,
        from: filters.from || undefined,
        to: filters.to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    filters.q = '';
    filters.store_id = '';
    filters.method_id = '';
    filters.status = '';
    filters.from = '';
    filters.to = '';
    applyFilters();
};

const toggleDetail = (id: string) => {
    expanded.value[id] = !expanded.value[id];
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    if (filters.q) params.set('q', filters.q);
    if (filters.store_id) params.set('store_id', filters.store_id);
    if (filters.method_id) params.set('method_id', filters.method_id);
    if (filters.status) params.set('status', filters.status);
    if (filters.from) params.set('from', filters.from);
    if (filters.to) params.set('to', filters.to);

    const query = params.toString();

    return query ? `${props.urls.export}?${query}` : props.urls.export;
});
</script>

<template>
    <Head title="EDC BCA" />

    <div class="edc-page">
        <section class="edc-header">
            <div>
                <span class="edc-eyebrow">Monitoring / Pembayaran</span>
                <h1>EDC BCA</h1>
                <p>Pantau transaksi kartu BCA dari EDC/POS, lengkap dengan terminal, approval code, estimasi MDR, dan settlement.</p>
            </div>
            <div class="edc-header-actions">
                <Link :href="props.urls.payments_page" class="edc-button edc-button-light">
                    <i class="mdi mdi-credit-card-check-outline me-1"></i>Semua Pembayaran
                </Link>
                <a :href="exportUrl" class="edc-button edc-button-primary">
                    <i class="mdi mdi-file-excel-outline me-1"></i>Ekspor
                </a>
            </div>
        </section>

        <div v-if="!props.readiness.has_edc_bca_method" class="edc-readiness mb-3">
            <i class="mdi mdi-credit-card-off-outline"></i>
            <div>
                <strong>Metode EDC BCA belum terdaftar</strong>
                <p>Transaksi BCA CC/BCA DEBIT tetap dipantau. Tambahkan metode <b>EDC BCA</b> di master data agar terminal EDC dapat dikenali terpisah.</p>
            </div>
            <Link :href="props.urls.payment_methods" class="btn btn-sm btn-outline-primary">Kelola Metode</Link>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="edc-summary">
                    <span class="edc-summary-icon"><i class="mdi mdi-credit-card-chip-outline"></i></span>
                    <div><span>Transaksi</span><strong>{{ formatNumber(props.summary.total_transactions) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="edc-summary">
                    <span class="edc-summary-icon"><i class="mdi mdi-cash-multiple"></i></span>
                    <div><span>Gross Amount</span><strong>{{ formatCurrency(props.summary.gross_amount) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="edc-summary edc-summary-warning">
                    <span class="edc-summary-icon"><i class="mdi mdi-percent-outline"></i></span>
                    <div><span>Estimasi MDR</span><strong>{{ formatCurrency(props.summary.mdr_amount) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="edc-summary edc-summary-success">
                    <span class="edc-summary-icon"><i class="mdi mdi-bank-transfer-in"></i></span>
                    <div><span>Estimasi Settlement</span><strong>{{ formatCurrency(props.summary.net_amount) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="edc-summary">
                    <span class="edc-summary-icon"><i class="mdi mdi-calculator-variant-outline"></i></span>
                    <div><span>Rata-rata Tiket</span><strong>{{ formatCurrency(averageTicket) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="edc-summary">
                    <span class="edc-summary-icon"><i class="mdi mdi-clock-fast"></i></span>
                    <div><span>Settlement</span><strong>H+{{ props.mdr.settlement_days }}</strong><small class="edc-summary-note">MDR {{ props.mdr.credit_percent }}% kredit · {{ props.mdr.debit_percent }}% debit</small></div>
                </div>
            </div>
        </div>

        <div class="card edc-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-4">
                        <label class="form-label" for="edc-search">Cari transaksi</label>
                        <input id="edc-search" v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nomor transaksi, referensi, toko, atau terminal..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="edc-store">Toko</label>
                        <SearchableSelect id="edc-store" v-model="filters.store_id" :options="storeOptions" placeholder="Semua toko" search-placeholder="Cari toko..." aria-label="Filter toko" />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="edc-method">Metode</label>
                        <SearchableSelect id="edc-method" v-model="filters.method_id" :options="methodOptions" placeholder="Semua metode BCA" search-placeholder="Cari metode..." aria-label="Filter metode pembayaran" />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="edc-status">Status</label>
                        <select id="edc-status" v-model="filters.status" class="form-select form-select-sm">
                            <option value="">Semua status</option>
                            <option v-for="status in props.statuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-1">
                        <label class="form-label" for="edc-from">Dari</label>
                        <input id="edc-from" v-model="filters.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-4 col-lg-1">
                        <label class="form-label" for="edc-to">Sampai</label>
                        <input id="edc-to" v-model="filters.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-8">
                <div class="card edc-card h-100">
                    <div class="edc-card-header">
                        <div><h2>Transaksi EDC BCA</h2><p>Klik detail untuk melihat metadata terminal dan approval.</p></div>
                        <span class="edc-card-meta">{{ formatNumber(props.rows.total) }} transaksi</span>
                    </div>

                    <div v-if="props.rows.data.length === 0" class="edc-empty">
                        <i class="mdi mdi-credit-card-off-outline"></i>
                        <strong>Belum ada transaksi EDC BCA</strong>
                        <span>Transaksi akan muncul setelah POS mengirim pembayaran BCA CC/DEBIT/EDC.</span>
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table align-middle mb-0 edc-table">
                            <thead>
                                <tr><th>Transaksi</th><th>Toko / Kanal</th><th>Metode</th><th class="text-end">Amount</th><th class="text-end">Settlement</th><th>Status</th><th class="text-end">Aksi</th></tr>
                            </thead>
                            <tbody>
                                <template v-for="row in props.rows.data" :key="row.id">
                                    <tr>
                                        <td>
                                            <div class="edc-main">
                                                <strong>{{ row.transaction_number || row.transaction_ref || '-' }}</strong>
                                                <small>{{ formatDate(row.event_at, true) }}</small>
                                                <small v-if="row.ecommerce_order_number" class="edc-subvalue">{{ row.ecommerce_order_number }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ row.store_name }}</strong>
                                            <small class="edc-subvalue">{{ row.channel_label }}<span v-if="row.cashier_name"> · {{ row.cashier_name }}</span></small>
                                        </td>
                                        <td>
                                            <strong>{{ row.payment_method_name }}</strong>
                                            <small class="edc-subvalue">MDR {{ row.mdr_rate }}%</small>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ formatCurrency(row.amount) }}</strong>
                                            <small class="edc-subvalue">MDR {{ formatCurrency(row.mdr_amount) }}</small>
                                        </td>
                                        <td class="text-end"><strong>{{ formatCurrency(row.net_amount) }}</strong></td>
                                        <td><span class="badge" :class="statusClass(row.detail_status)">{{ statusLabel(row.detail_status) }}</span></td>
                                        <td class="text-end">
                                            <button
                                                class="btn btn-sm btn-outline-secondary"
                                                type="button"
                                                :aria-expanded="Boolean(expanded[row.id])"
                                                :aria-controls="`edc-detail-${row.id}`"
                                                @click="toggleDetail(row.id)"
                                            >
                                                <i class="mdi" :class="expanded[row.id] ? 'mdi-chevron-up' : 'mdi-chevron-down'" aria-hidden="true"></i>Detail
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="expanded[row.id]" :id="`edc-detail-${row.id}`" class="edc-detail-row">
                                        <td colspan="7">
                                            <div class="edc-detail-grid">
                                                <div><span>Terminal</span><strong>{{ row.metadata.terminal_id || '-' }}</strong></div>
                                                <div><span>Approval Code</span><strong>{{ row.metadata.approval_code || '-' }}</strong></div>
                                                <div><span>Batch</span><strong>{{ row.metadata.batch_number || '-' }}</strong></div>
                                                <div><span>Kartu</span><strong>{{ row.metadata.card_number || '-' }}</strong></div>
                                                <div><span>Tipe Kartu</span><strong>{{ row.metadata.card_type || '-' }}</strong></div>
                                                <div><span>Acquirer</span><strong>{{ row.metadata.acquirer || '-' }}</strong></div>
                                                <div><span>Issuer</span><strong>{{ row.metadata.issuer || '-' }}</strong></div>
                                                <div><span>Merchant ID</span><strong>{{ row.metadata.merchant_id || '-' }}</strong></div>
                                                <div><span>Referensi</span><strong>{{ row.metadata.reference_number || row.ref || '-' }}</strong></div>
                                                <div><span>Payment ID</span><strong class="edc-mono">{{ row.payment_id }}</strong></div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="edc-footer">
                        <small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} transaksi</small>
                        <PaginationLinks :links="props.rows.links" />
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card edc-card mb-3">
                    <div class="edc-card-header"><div><h2>Per Metode</h2><p>Estimasi MDR dan settlement per metode BCA.</p></div></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 edc-mini-table">
                            <thead><tr><th>Metode</th><th class="text-end">Trx</th><th class="text-end">Settlement</th></tr></thead>
                            <tbody>
                                <tr v-for="row in props.byMethod" :key="row.payment_method_name">
                                    <td>{{ row.payment_method_name }}</td>
                                    <td class="text-end">{{ formatNumber(row.total_transactions) }}</td>
                                    <td class="text-end">{{ formatCurrency(row.net_amount) }}</td>
                                </tr>
                                <tr v-if="props.byMethod.length === 0"><td colspan="3" class="text-center text-muted py-3">Belum ada data.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card edc-card">
                    <div class="edc-card-header"><div><h2>Per Toko</h2><p>Nominal transaksi BCA per toko.</p></div></div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 edc-mini-table">
                            <thead><tr><th>Toko</th><th class="text-end">Trx</th><th class="text-end">Gross</th></tr></thead>
                            <tbody>
                                <tr v-for="row in props.byStore" :key="row.store_name">
                                    <td>{{ row.store_name }}</td>
                                    <td class="text-end">{{ formatNumber(row.total_transactions) }}</td>
                                    <td class="text-end">{{ formatCurrency(row.gross_amount) }}</td>
                                </tr>
                                <tr v-if="props.byStore.length === 0"><td colspan="3" class="text-center text-muted py-3">Belum ada data.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="edc-note">
            <i class="mdi mdi-information-outline"></i>
            <span>MDR adalah estimasi dari konfigurasi backoffice, bukan nilai settlement bank final. Ubah persentase melalui environment <code>BACKOFFICE_EDC_BCA_CREDIT_MDR_PERCENT</code> dan <code>BACKOFFICE_EDC_BCA_DEBIT_MDR_PERCENT</code>.</span>
        </div>
    </div>
</template>

<style scoped>
.edc-page { color: var(--app-ink); }
.edc-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; }
.edc-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.edc-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.edc-header p { max-width: 760px; margin: 0; color: var(--app-hero-muted) !important; }
.edc-header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.edc-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border-radius: 0.55rem; font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.edc-button-light { background: var(--app-surface); color: var(--app-ink); }
.edc-button-primary { background: var(--app-accent-strong); color: var(--app-on-accent); }
.edc-readiness { display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1rem; border: 1px solid var(--app-warning-bg); border-radius: 0.8rem; background: var(--app-warning-bg); color: var(--app-warning-ink); }
.edc-readiness i { font-size: 1.4rem; }
.edc-readiness strong { display: block; font-size: 0.88rem; }
.edc-readiness p { margin: 0.15rem 0 0; font-size: 0.78rem; }
.edc-readiness .btn { margin-left: auto; white-space: nowrap; }
.edc-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.edc-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.edc-summary span:not(.edc-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.edc-summary strong { display: block; margin-top: 0.15rem; font-size: 1.3rem; overflow-wrap: anywhere; }
.edc-summary-note { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: 0.68rem; }
.edc-summary-warning .edc-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.edc-summary-success .edc-summary-icon { background: var(--app-success-bg, var(--app-accent-soft)); color: var(--app-success-ink, var(--app-accent-ink)); }
.edc-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.edc-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.edc-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.edc-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.edc-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.edc-table th { padding: 0.6rem 0.9rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.edc-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); color: var(--app-ink); }
.edc-main strong, .edc-main small, .edc-subvalue { display: block; }
.edc-main strong { font-size: 0.84rem; }
.edc-main small, .edc-subvalue { color: var(--app-muted); font-size: 0.72rem; }
.edc-detail-row > td { padding: 0 !important; background: var(--app-surface-2); }
.edc-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.6rem 1rem; padding: 0.85rem 1rem; }
.edc-detail-grid span { display: block; color: var(--app-muted); font-size: 0.66rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.edc-detail-grid strong { display: block; margin-top: 0.15rem; font-size: 0.8rem; }
.edc-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.7rem !important; word-break: break-all; }
.edc-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.edc-empty { display: grid; place-items: center; gap: 0.3rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.edc-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.edc-empty strong { color: var(--app-ink); }
.edc-mini-table th { padding: 0.5rem 0.75rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.64rem; text-transform: uppercase; }
.edc-mini-table td { padding: 0.55rem 0.75rem; font-size: 0.78rem; border-color: var(--app-border); }
.edc-note { display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.75rem 0.9rem; border-radius: 0.6rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.78rem; }
.edc-note code { color: var(--app-ink); font-size: 0.72rem; }
@media (max-width: 991.98px) {
    .edc-header { align-items: flex-start; flex-direction: column; }
    .edc-card-header { align-items: flex-start; flex-direction: column; }
    .edc-readiness { align-items: flex-start; flex-direction: column; }
    .edc-readiness .btn { margin-left: 0; }
}
</style>
