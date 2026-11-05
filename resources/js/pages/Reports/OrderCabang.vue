<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import { downloadExcelTable } from '@/composables/useExcelExport';

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
    warehouses: Option[];
    products: Array<Option & { code?: string | null }>;
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
        approve: string;
        reject: string;
        receive: string;
        request: string;
    };
}

const props = defineProps<Props>();
const warehouseOptions = computed(() => props.warehouses.map((warehouse) => ({
    value: warehouse.id,
    label: warehouse.name,
})));


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
const warehouseId = ref('');
const approvedQty = reactive<Record<string, number>>({});
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
    warehouseId.value = order.warehouse?.id ?? '';

    try {
        const response = await fetch(props.urls.show.replace('__ID__', order.id), {
            headers: { Accept: 'application/json' },
        });

        const data = await response.json();
        details.value = data.details ?? [];

        for (const detail of details.value) {
            approvedQty[detail.id] = detail.approved_qty || detail.requested_qty;
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

const action = (type: 'request' | 'reject' | 'approve' | 'receive') => {
    if (!selected.value) {
        return;
    }

    if (type === 'reject' && !window.confirm('Reject order ini?')) {
        return;
    }

    let payload: Record<string, any> = {};

    if (type === 'approve') {
        payload = {
            warehouse_id: warehouseId.value,
            approved_qty: Object.fromEntries(details.value.map((detail) => [detail.id, [approvedQty[detail.id] ?? detail.requested_qty]])),
        };
    }

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

const statusClass = (status: string) => {
    if (['approved', 'received', 'partial_received'].includes(status)) return 'bg-success';
    if (['rejected', 'cancelled'].includes(status)) return 'bg-danger';
    if (['requested', 'processing', 'packing', 'shipped'].includes(status)) return 'bg-info';
    return 'bg-warning';
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
                <span class="report-eyebrow">Reports</span>
                <h1>Order Cabang</h1>
                <p>Permintaan order dari toko ke gudang.</p>
            </div>
            <button class="btn btn-light btn-sm" type="button" @click="exportOrders">
                <i class="mdi mdi-file-excel-outline me-1"></i>Ekspor Excel
            </button>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="apply">
                    <div class="col-md-3"><label class="form-label">Cari</label><input v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Order number..." /></div>
                    <div class="col-md-2"><label class="form-label">Status</label><select v-model="form.status" class="form-select form-select-sm"><option value="">Semua</option><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></select></div>
                    <div class="col-md-2"><label class="form-label">Dari</label><input v-model="form.from" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label class="form-label">Sampai</label><input v-model="form.to" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary" type="submit">Filter</button><button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button></div>
                </form>
            </div>
        </div>

        <div class="card report-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Order</th><th>Tanggal</th><th>Store</th><th>Warehouse</th><th>Status</th><th class="text-end">Qty</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.id">
                            <td class="fw-semibold">{{ order.order_number ?? order.id }}</td>
                            <td>{{ formatDate(order.date) }}</td>
                            <td>{{ order.store?.name ?? '-' }}</td>
                            <td>{{ order.warehouse?.name ?? '-' }}</td>
                            <td><span class="badge" :class="statusClass(order.status)">{{ order.status }}</span></td>
                            <td class="text-end">{{ formatNumber(order.details_sum_requested_qty) }}</td>
                            <td><button class="btn btn-sm btn-outline-primary" type="button" @click="openOrder(order)">Kelola</button></td>
                        </tr>
                        <tr v-if="orders.data.length === 0"><td colspan="7" class="report-empty">Belum ada order.</td></tr>
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
                        <div class="col-md-4">
                            <label class="form-label">Warehouse untuk approve</label>
                            <SearchableSelect v-model="warehouseId" :options="warehouseOptions" placeholder="Pilih warehouse" search-placeholder="Cari warehouse..." />
                        </div>
                        <div class="col-md-8 d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-outline-secondary" type="button" @click="action('request')">Request</button>
                            <button class="btn btn-sm btn-success" type="button" @click="action('approve')">Approve</button>
                            <button class="btn btn-sm btn-outline-danger" type="button" @click="action('reject')">Reject</button>
                            <button class="btn btn-sm btn-primary" type="button" @click="action('receive')">Receive</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Produk</th><th class="text-end">Requested</th><th class="text-end">Approved</th><th class="text-end">Received</th><th class="text-end">Approve Qty</th><th class="text-end">Receive Qty</th></tr></thead>
                            <tbody>
                                <tr v-for="detail in details" :key="detail.id">
                                    <td>{{ detail.product?.name ?? detail.product_id ?? '-' }}</td>
                                    <td class="text-end">{{ detail.requested_qty }}</td>
                                    <td class="text-end">{{ detail.approved_qty }}</td>
                                    <td class="text-end">{{ detail.received_qty }}</td>
                                    <td class="text-end"><input v-model.number="approvedQty[detail.id]" class="form-control form-control-sm text-end" type="number" min="0" :max="detail.requested_qty" style="max-width: 100px; margin-left: auto;" /></td>
                                    <td class="text-end"><input v-model.number="receivedQty[detail.id]" class="form-control form-control-sm text-end" type="number" min="0" :max="detail.approved_qty" style="max-width: 100px; margin-left: auto;" /></td>
                                </tr>
                                <tr v-if="details.length === 0"><td colspan="6" class="report-empty">Belum ada detail.</td></tr>
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
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.report-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.report-header p { margin: 0; color: var(--app-hero-muted); }
.report-card { border: 1px solid var(--report-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.report-card th { color: var(--report-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.report-empty { padding: 2rem 1rem; color: var(--report-muted); text-align: center; }
</style>
