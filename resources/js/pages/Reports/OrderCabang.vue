<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import ReportTableControls from '@/components/ui/ReportTableControls.vue';
import ReportPeriodPresets from '@/components/ui/ReportPeriodPresets.vue';
import { downloadExcelTable } from '@/composables/useExcelExport';
import { useReportTableColumns, useReportTableDensity } from '@/composables/useReportTable';

interface Option { id: string; name: string }

interface OrderRow {
    id: string;
    order_number: string | null;
    status: string;
    date: string | null;
    created_at: string | null;
    store: Option | null;
    warehouse: Option | null;
    details_sum_requested_qty: number | null;
}

interface DetailRow {
    id: string;
    product_id: string | null;
    product_price_id: string | null;
    product_price: string | number | null;
    requested_qty: number;
    approved_qty: number;
    received_qty: number;
    note: string | null;
    product: { id: string; name: string } | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    orders: Paginator<OrderRow>;
    stores: Option[];
    statuses: string[];
    filters: {
        q: string;
        status: string;
        from: string | null;
        to: string | null;
    };
    urls: {
        index: string;
        show: string;
        approval: string;
        receive: string;
        request: string;
    };
}

const props = defineProps<Props>();

const orderColumns = [
    { key: 'order_number', label: 'Order' },
    { key: 'date', label: 'Tanggal' },
    { key: 'store', label: 'Toko' },
    { key: 'warehouse', label: 'Gudang' },
    { key: 'status', label: 'Status' },
    { key: 'qty', label: 'Qty' },
];
const orderSimpleColumns = ['order_number', 'date', 'store', 'status'];
const { density, isCompact, toggleDensity } = useReportTableDensity();
const { visibleColumns, isVisible, toggleColumn, showAll, applyPreset, columnCount } = useReportTableColumns(
    'veestore.order-cabang.columns',
    orderColumns.map((column) => column.key),
);

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const selected = ref<OrderRow | null>(null);
const details = ref<DetailRow[]>([]);
const loading = ref(false);
const receivedQty = reactive<Record<string, number>>({});

const apply = () => {
    router.get(props.urls.index, {
        q: form.q || undefined,
        status: form.status || undefined,
        from: form.from || undefined,
        to: form.to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const reset = () => {
    form.q = '';
    form.status = 'requested';
    form.from = '';
    form.to = '';
    apply();
};

const openOrder = async (order: OrderRow) => {
    selected.value = order;
    details.value = [];
    loading.value = true;

    try {
        const response = await fetch(props.urls.show.replace('__ID__', order.id), {
            headers: { Accept: 'application/json' },
        });

        const data = await response.json();
        details.value = data.details ?? [];

        for (const detail of details.value) {
            receivedQty[detail.id] = detail.received_qty || detail.approved_qty || 0;
        }
    } finally {
        loading.value = false;
    }
};

const closeOrder = () => {
    selected.value = null;
    details.value = [];
};

const action = (type: 'request' | 'receive') => {
    if (!selected.value) {
        return;
    }

    let payload: Record<string, any> = {};

    if (type === 'receive') {
        payload = {
            received_qty: Object.fromEntries(details.value.map((detail) => [detail.id, receivedQty[detail.id] ?? detail.approved_qty])),
        };
    }

    router.patch(props.urls[type].replace('__ID__', selected.value.id), payload, {
        preserveScroll: true,
        onSuccess: closeOrder,
    });
};

const storeOrderStatusLabels: Record<string, string> = {
    draft: 'Draft',
    requested: 'Diminta',
    approved: 'Disetujui',
    partial_approved: 'Disetujui sebagian',
    rejected: 'Ditolak',
    processing: 'Diproses',
    packing: 'Dikemas',
    shipped: 'Dikirim',
    received: 'Diterima',
    partial_received: 'Diterima sebagian',
    cancelled: 'Dibatalkan',
};

const statusLabel = (status: string) => storeOrderStatusLabels[status] ?? status;

const statusClass = (status: string) => {
    if (['approved', 'received', 'partial_received'].includes(status)) return 'bg-label-success';
    if (['rejected', 'cancelled'].includes(status)) return 'bg-label-danger';
    if (['requested', 'processing', 'packing', 'shipped'].includes(status)) return 'bg-label-info';
    return 'bg-label-warning';
};

const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)) : '-';
const formatNumber = (value: number | null | undefined) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const exportOrders = () => downloadExcelTable('Order-Cabang', 'Order Cabang', [
    { key: 'order_number', label: 'Nomor Order' },
    { key: 'date', label: 'Tanggal' },
    { key: 'store', label: 'Store' },
    { key: 'warehouse', label: 'Warehouse' },
    { key: 'status', label: 'Status' },
    { key: 'requested_quantity', label: 'Qty Diminta' },
], props.orders.data.map((order) => ({
    order_number: order.order_number ?? order.id,
    date: order.date ?? order.created_at,
    store: order.store?.name ?? '',
    warehouse: order.warehouse?.name ?? '',
    status: order.status,
    requested_quantity: Number(order.details_sum_requested_qty ?? 0),
})), {
    title: 'Order Cabang',
    filters: [
        { label: 'Pencarian', value: props.filters.q },
        { label: 'Status', value: props.filters.status },
        { label: 'Dari', value: props.filters.from },
        { label: 'Sampai', value: props.filters.to },
    ],
});
</script>

<template>
    <Head title="Order Cabang" />
    <div class="report-page">
        <div class="report-header d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <span class="report-eyebrow">Laporan / Pesanan</span>
                <h1>Order Cabang</h1>
                <p>Permintaan order dari toko ke gudang pada periode terpilih.</p>
            </div>
            <button class="btn btn-light btn-sm" type="button" @click="exportOrders">
                <i class="mdi mdi-file-excel-outline me-1"></i>Ekspor Excel
            </button>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <ReportPeriodPresets
                    class="mb-2"
                    :route="props.urls.index"
                    from-key="from"
                    to-key="to"
                    :from="props.filters.from"
                    :to="props.filters.to"
                    :query="form"
                />
                <form class="row g-2 align-items-end" @submit.prevent="apply">
                    <div class="col-md-3"><label for="order-cabang-search" class="form-label">Cari</label><input id="order-cabang-search" v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Nomor order..." /></div>
                    <div class="col-md-2"><label for="order-cabang-status" class="form-label">Status</label><select id="order-cabang-status" v-model="form.status" class="form-select form-select-sm"><option value="">Semua</option><option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option></select></div>
                    <div class="col-md-2"><label for="order-cabang-from" class="form-label">Tanggal dari</label><input id="order-cabang-from" v-model="form.from" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label for="order-cabang-to" class="form-label">Tanggal sampai</label><input id="order-cabang-to" v-model="form.to" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary" type="submit">Terapkan filter</button><button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button></div>
                </form>
            </div>
        </div>

        <div class="card report-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="h6 mb-0">Daftar order cabang</h2>
                    <small class="text-muted">{{ orders.total }} order sesuai filter</small>
                </div>
                <ReportTableControls
                    :columns="orderColumns"
                    :visible="visibleColumns"
                    :density="density"
                    :simple-preset="orderSimpleColumns"
                    @toggle-density="toggleDensity"
                    @toggle-column="toggleColumn"
                    @show-all="showAll"
                    @apply-simple="applyPreset(orderSimpleColumns)"
                />
            </div>
            <div class="table-responsive report-table-wrap" :class="{ 'is-compact': isCompact }" tabindex="0" role="region" aria-label="Daftar order cabang">
                <table class="table align-middle mb-0">
                    <caption class="visually-hidden">Permintaan order dari toko ke gudang beserta status dan jumlah barang</caption>
                    <thead>
                        <tr>
                            <th v-if="isVisible('order_number')">Order</th>
                            <th v-if="isVisible('date')">Tanggal</th>
                            <th v-if="isVisible('store')">Toko</th>
                            <th v-if="isVisible('warehouse')">Gudang</th>
                            <th v-if="isVisible('status')">Status</th>
                            <th v-if="isVisible('qty')" class="text-end">Qty</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.id">
                            <td v-if="isVisible('order_number')" class="fw-semibold">{{ order.order_number ?? order.id }}</td>
                            <td v-if="isVisible('date')">{{ formatDate(order.date) }}</td>
                            <td v-if="isVisible('store')">{{ order.store?.name ?? '-' }}</td>
                            <td v-if="isVisible('warehouse')">{{ order.warehouse?.name ?? '-' }}</td>
                            <td v-if="isVisible('status')"><span class="badge" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                            <td v-if="isVisible('qty')" class="text-end">{{ formatNumber(order.details_sum_requested_qty) }}</td>
                            <td><button class="btn btn-sm btn-outline-primary" type="button" @click="openOrder(order)">Kelola</button></td>
                        </tr>
                        <tr v-if="orders.data.length === 0"><td :colspan="columnCount + 1" class="report-empty">Belum ada order pada filter ini. Ubah tanggal atau status.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2"><small class="text-muted">Menampilkan {{ orders.from ?? 0 }} - {{ orders.to ?? 0 }} dari {{ orders.total }} order</small><PaginationLinks :links="orders.links" /></div>
        </div>

        <div v-if="selected" class="card report-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Order {{ selected.order_number ?? selected.id }}</h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" @click="closeOrder">Tutup</button>
            </div>
            <div class="card-body">
                <div v-if="loading" class="text-muted">Memuat detail...</div>
                <template v-else>
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-8 d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-outline-secondary" type="button" @click="action('request')">Request</button>
                            <button class="btn btn-sm btn-primary" type="button" @click="action('receive')">Receive</button>
                            <a :href="props.urls.approval" class="btn btn-sm btn-success">Approve di antrean operasional</a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Produk</th><th class="text-end">Requested</th><th class="text-end">Approved</th><th class="text-end">Received</th><th class="text-end">Receive Qty</th></tr></thead>
                            <tbody>
                                <tr v-for="detail in details" :key="detail.id">
                                    <td>{{ detail.product?.name ?? detail.product_id ?? '-' }}</td>
                                    <td class="text-end">{{ detail.requested_qty }}</td>
                                    <td class="text-end">{{ detail.approved_qty }}</td>
                                    <td class="text-end">{{ detail.received_qty }}</td>
                                    <td class="text-end"><input v-model.number="receivedQty[detail.id]" class="form-control form-control-sm text-end" type="number" min="0" :max="detail.approved_qty" style="max-width: 100px; margin-left: auto;" /></td>
                                </tr>
                                <tr v-if="details.length === 0"><td colspan="5" class="report-empty">Belum ada detail.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.report-page { --report-ink: var(--app-ink); --report-border: var(--app-border); --report-muted: var(--app-muted); color: var(--report-ink); }
.report-header { padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--report-ink); color: #fff; }
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: none; }
.report-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.report-header p { margin: 0; color: var(--app-hero-muted); }
.report-card { border: 1px solid var(--report-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.report-card th { color: var(--report-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: none; }
.report-empty { padding: 2rem 1rem; color: var(--report-muted); text-align: center; }
</style>
