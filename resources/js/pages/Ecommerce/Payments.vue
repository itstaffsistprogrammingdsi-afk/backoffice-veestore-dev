<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';

defineOptions({ layout: AppLayout });

interface PaymentRow {
    id: string;
    order_number: string | null;
    order_status: string;
    order_created_at: string | null;
    grand_total: string | number;
    payment_method_code: string | null;
    payment_method_name: string | null;
    payment_fee: string | number;
    customer_name: string;
    customer_email: string;
    customer_wa: string;
    payment_status: string;
    paid_amount: string | number;
    payment_total_amount: string | number;
    transaction_number: string;
    payment_completed_at: string | null;
    payment_note: string | null;
    total_items: number;
    total_qty: number;
    product_names: string;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<PaymentRow>;
    summary: {
        total_orders: number;
        paid_orders: number;
        pending_orders: number;
        failed_orders: number;
        total_amount: string | number;
        paid_amount: string | number;
        outstanding_amount: string | number;
    };
    paymentMethods: Array<{ payment_method_code: string; payment_method_name: string }>;
    paymentStatuses: string[];
    filters: {
        q: string;
        payment_status: string | null;
        payment_method_code: string | null;
        from: string | null;
        to: string | null;
    };
    urls: {
        index: string;
        orders: string;
        report: string;
    };
}

const props = defineProps<Props>();

const filters = reactive({
    q: props.filters.q ?? '',
    payment_status: props.filters.payment_status ?? '',
    payment_method_code: props.filters.payment_method_code ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const expanded = ref<Record<string, boolean>>({});

const formatNumber = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const formatCurrency = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value ?? 0));

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

const paymentStatusLabel = (status: string) => ({
    paid: 'Lunas',
    pending: 'Menunggu',
    failed: 'Gagal',
    cancelled: 'Dibatalkan',
}[status] ?? (status || '-'));

const paymentStatusClass = (status: string) => ({
    paid: 'bg-label-success',
    pending: 'bg-label-warning',
    failed: 'bg-label-danger',
    cancelled: 'bg-label-secondary',
}[status] ?? 'bg-label-secondary');

const orderStatusClass = (status: string) => ({
    paid: 'bg-label-success',
    received: 'bg-label-success',
    processed: 'bg-label-info',
    packing: 'bg-label-info',
    shipped: 'bg-label-info',
    cancelled: 'bg-label-danger',
}[status] ?? 'bg-label-warning');

const remainingAmount = (row: PaymentRow) => Math.max(Number(row.grand_total ?? 0) - Number(row.paid_amount ?? 0), 0);

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        payment_status: filters.payment_status || undefined,
        payment_method_code: filters.payment_method_code || undefined,
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
    filters.payment_status = '';
    filters.payment_method_code = '';
    filters.from = '';
    filters.to = '';
    applyFilters();
};

const toggleDetail = (id: string) => {
    expanded.value[id] = !expanded.value[id];
};

const orderUrl = (row: PaymentRow) => {
    const params = new URLSearchParams();
    const date = row.order_created_at?.slice(0, 10);

    if (row.order_number) params.set('q', row.order_number);
    if (date) {
        params.set('from', date);
        params.set('to', date);
    }

    return `${props.urls.orders}?${params.toString()}`;
};
</script>

<template>
    <Head title="Ecommerce Payments" />

    <div class="payment-page">
        <section class="payment-header">
            <div>
                <span class="payment-eyebrow">Ecommerce / Operasional</span>
                <h1>Payments</h1>
                <p>Pantau status pembayaran order ecommerce, metode, nominal, dan sisa tagihan.</p>
            </div>
            <div class="payment-hero-actions">
                <Link :href="props.urls.report" class="payment-hero-button">
                    <i class="mdi mdi-chart-box-outline me-1"></i>
                    Payment Report
                </Link>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
                <div class="payment-summary">
                    <span class="payment-summary-icon"><i class="mdi mdi-receipt-text-outline"></i></span>
                    <div><span>Total Order</span><strong>{{ formatNumber(props.summary.total_orders) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
                <div class="payment-summary payment-summary-success">
                    <span class="payment-summary-icon"><i class="mdi mdi-check-circle-outline"></i></span>
                    <div><span>Lunas</span><strong>{{ formatNumber(props.summary.paid_orders) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
                <div class="payment-summary payment-summary-warning">
                    <span class="payment-summary-icon"><i class="mdi mdi-clock-outline"></i></span>
                    <div><span>Menunggu</span><strong>{{ formatNumber(props.summary.pending_orders) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
                <div class="payment-summary payment-summary-danger">
                    <span class="payment-summary-icon"><i class="mdi mdi-alert-circle-outline"></i></span>
                    <div><span>Gagal / Batal</span><strong>{{ formatNumber(props.summary.failed_orders) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
                <div class="payment-summary">
                    <span class="payment-summary-icon"><i class="mdi mdi-cash-check"></i></span>
                    <div><span>Total Dibayar</span><strong>{{ formatCurrency(props.summary.paid_amount) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
                <div class="payment-summary payment-summary-danger">
                    <span class="payment-summary-icon"><i class="mdi mdi-cash-clock"></i></span>
                    <div><span>Outstanding</span><strong>{{ formatCurrency(props.summary.outstanding_amount) }}</strong></div>
                </div>
            </div>
        </div>

        <div class="card payment-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Cari order, transaksi, atau customer</label>
                        <input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Order number, transaction number, customer..." />
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label">Status Pembayaran</label>
                        <select v-model="filters.payment_status" class="form-select form-select-sm">
                            <option value="">Semua status</option>
                            <option v-for="status in props.paymentStatuses" :key="status" :value="status">{{ paymentStatusLabel(status) }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <select v-model="filters.payment_method_code" class="form-select form-select-sm">
                            <option value="">Semua metode</option>
                            <option v-for="method in props.paymentMethods" :key="method.payment_method_code" :value="method.payment_method_code">{{ method.payment_method_name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input v-model="filters.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input v-model="filters.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-12 col-md-6 col-lg-6 d-flex gap-2">
                        <button class="btn btn-sm btn-primary flex-grow-1" type="submit"><i class="mdi mdi-filter me-1"></i>Terapkan</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card payment-card">
            <div class="payment-card-header">
                <div>
                    <h2>Transaksi Pembayaran</h2>
                    <p>Data pembayaran order ecommerce yang sudah tercatat di sistem.</p>
                </div>
                <span class="payment-card-meta">{{ formatNumber(props.rows.total) }} transaksi</span>
            </div>

            <div v-if="props.rows.data.length === 0" class="payment-empty">
                <i class="mdi mdi-credit-card-off-outline"></i>
                <strong>Tidak ada transaksi pembayaran</strong>
                <span>Coba ubah kata kunci, status, metode, atau rentang tanggal.</span>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 payment-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Dibayar</th>
                            <th class="text-end">Sisa</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="row in props.rows.data" :key="row.id">
                            <tr>
                                <td>
                                    <div class="payment-main">
                                        <strong>{{ row.order_number ?? row.id }}</strong>
                                        <span class="badge" :class="orderStatusClass(row.order_status)">{{ row.order_status }}</span>
                                        <small class="payment-mobile-meta d-md-none">
                                            <span class="badge" :class="paymentStatusClass(row.payment_status)">{{ paymentStatusLabel(row.payment_status) }}</span>
                                            <span>{{ formatCurrency(row.grand_total) }}</span>
                                        </small>
                                    </div>
                                </td>
                                <td>{{ formatDate(row.order_created_at) }}</td>
                                <td>
                                    <div class="payment-main">
                                        <strong>{{ row.customer_name }}</strong>
                                        <small>{{ row.customer_wa !== '-' ? row.customer_wa : row.customer_email }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="payment-main">
                                        <strong>{{ row.payment_method_name || row.payment_method_code || '-' }}</strong>
                                        <small v-if="row.payment_method_code">{{ row.payment_method_code }}</small>
                                    </div>
                                </td>
                                <td><span class="badge" :class="paymentStatusClass(row.payment_status)">{{ paymentStatusLabel(row.payment_status) }}</span></td>
                                <td class="text-end">{{ formatCurrency(row.grand_total) }}</td>
                                <td class="text-end">{{ formatCurrency(row.paid_amount) }}</td>
                                <td class="text-end">{{ formatCurrency(remainingAmount(row)) }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="toggleDetail(row.id)">
                                        <i class="mdi" :class="expanded[row.id] ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>
                                        {{ expanded[row.id] ? 'Tutup' : 'Detail' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expanded[row.id]" class="payment-detail-row">
                                <td colspan="9">
                                    <div class="payment-detail">
                                        <div class="payment-detail-grid">
                                            <div><span>Status Order</span><strong>{{ row.order_status }}</strong></div>
                                            <div><span>Payment Selesai</span><strong>{{ formatDate(row.payment_completed_at) }}</strong></div>
                                            <div><span>Transaksi</span><strong>{{ row.transaction_number }}</strong></div>
                                            <div><span>Payment Fee</span><strong>{{ formatCurrency(row.payment_fee) }}</strong></div>
                                            <div><span>Item</span><strong>{{ formatNumber(row.total_items) }} item / {{ formatNumber(row.total_qty) }} qty</strong></div>
                                            <div class="payment-detail-wide"><span>Produk</span><strong>{{ row.product_names }}</strong></div>
                                            <div><span>Email</span><strong>{{ row.customer_email }}</strong></div>
                                            <div><span>WhatsApp</span><strong>{{ row.customer_wa }}</strong></div>
                                            <div class="payment-detail-wide"><span>Catatan Pembayaran</span><strong>{{ row.payment_note || '-' }}</strong></div>
                                        </div>
                                        <div class="payment-detail-actions">
                                            <Link :href="orderUrl(row)" class="btn btn-sm btn-outline-primary">
                                                <i class="mdi mdi-cart-arrow-right me-1"></i>
                                                Lihat Order
                                            </Link>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="payment-footer">
                <small class="text-muted">
                    Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} transaksi
                </small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.payment-page { color: var(--app-ink); }
.payment-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.payment-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.payment-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.payment-header p { max-width: 760px; margin: 0; color: var(--app-hero-muted) !important; }
.payment-hero-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.payment-hero-button { display: inline-flex; align-items: center; min-height: 44px; padding: 0.55rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface); color: var(--app-ink); font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.payment-summary { display: flex; align-items: center; gap: 0.7rem; height: 100%; min-width: 0; padding: 0.9rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.payment-summary-icon { display: grid; flex: 0 0 auto; width: 2.25rem; height: 2.25rem; place-items: center; border-radius: 0.6rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.1rem; }
.payment-summary span:not(.payment-summary-icon) { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.payment-summary strong { display: block; margin-top: 0.15rem; font-size: 0.95rem; line-height: 1.2; overflow-wrap: anywhere; }
.payment-summary-success .payment-summary-icon { background: var(--app-success-bg); color: var(--app-success-ink); }
.payment-summary-warning .payment-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.payment-summary-danger .payment-summary-icon { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.payment-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.payment-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.payment-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.payment-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.payment-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.payment-table th { padding: 0.55rem 0.6rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.66rem; letter-spacing: 0.04em; text-transform: uppercase; white-space: nowrap; }
.payment-table td { padding: 0.7rem 0.6rem; border-color: var(--app-border); color: var(--app-ink); font-size: 0.82rem; }
.payment-main { display: flex; flex-direction: column; align-items: flex-start; gap: 0.25rem; min-width: 0; }
.payment-main strong { font-size: 0.86rem; }
.payment-main small { color: var(--app-muted); font-size: 0.73rem; }

.payment-mobile-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; margin-top: 0.2rem; }
.payment-detail-row > td { padding: 0 !important; background: var(--app-surface-2); }
.payment-detail { padding: 0.9rem 1.1rem 1rem 2.2rem; }
.payment-detail-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem 1rem; }
.payment-detail-grid span { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.payment-detail-grid strong { display: block; margin-top: 0.15rem; font-size: 0.82rem; overflow-wrap: anywhere; }
.payment-detail-wide { grid-column: span 2; }
.payment-detail-actions { display: flex; justify-content: flex-end; margin-top: 0.9rem; }
.payment-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.payment-empty { display: grid; place-items: center; gap: 0.3rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.payment-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.payment-empty strong { color: var(--app-ink); }
@media (max-width: 1199.98px) {
    .payment-detail-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 991.98px) {
    .payment-header { align-items: flex-start; flex-direction: column; }
    .payment-card-header { align-items: flex-start; flex-direction: column; }
    .payment-detail { padding-left: 0.75rem; }
    .payment-detail-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575.98px) {
    .payment-summary { gap: 0.5rem; padding: 0.75rem; }
    .payment-summary-icon { width: 1.9rem; height: 1.9rem; font-size: 0.95rem; }
    .payment-summary strong { font-size: 0.82rem; white-space: nowrap; }
    .payment-detail-grid { grid-template-columns: 1fr; }
    .payment-detail-wide { grid-column: span 1; }
}
</style>

