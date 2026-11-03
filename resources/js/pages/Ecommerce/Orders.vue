<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';

interface Option {
    id: string;
    name: string;
}

interface OrderDetail {
    id: string;
    quantity: number;
    product: { id: string; name: string } | null;
}

interface PaymentInfo {
    id: string;
    status: string;
    total_amount: string | number;
    paid_amount: string | number;
    transaction_number: string | null;
    completed_at: string | null;
}

interface OrderRow {
    id: string;
    order_number: string | null;
    status: string;
    created_at: string | null;
    grand_total: string | number;
    payment_method_code: string | null;
    payment_method_name: string | null;
    payment_fee: string | number;
    payment: PaymentInfo | null;
    store: Option | null;
    warehouse: Option | null;
    details: OrderDetail[];
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
    statuses: string[];
    paymentMethods: Array<{ payment_method_code: string; payment_method_name: string }>;
    stockIssueOrderIds: string[];
    filters: {
        q: string;
        status: string | null;
        handler_type: string | null;
        store_id: string | null;
        warehouse_id: string | null;
        payment_method_code: string | null;
        stock_issue: boolean;
        from: string | null;
        to: string | null;
    };
    urls: {
        index: string;
        update: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    handler_type: props.filters.handler_type ?? '',
    store_id: props.filters.store_id ?? '',
    warehouse_id: props.filters.warehouse_id ?? '',
    payment_method_code: props.filters.payment_method_code ?? '',
    stock_issue: props.filters.stock_issue,
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const editingId = ref<string | null>(null);
const editForm = reactive({
    status: '',
    handler_type: 'store',
    store_id: '',
    warehouse_id: '',
});

const applyFilters = () => {
    router.get(props.urls.index, {
        q: form.q || undefined,
        status: form.status || undefined,
        handler_type: form.handler_type || undefined,
        store_id: form.store_id || undefined,
        warehouse_id: form.warehouse_id || undefined,
        payment_method_code: form.payment_method_code || undefined,
        stock_issue: form.stock_issue ? 1 : undefined,
        from: form.from || undefined,
        to: form.to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const reset = () => {
    form.q = '';
    form.status = '';
    form.handler_type = '';
    form.store_id = '';
    form.warehouse_id = '';
    form.payment_method_code = '';
    form.stock_issue = false;
    form.from = '';
    form.to = '';
    applyFilters();
};

const startEdit = (order: OrderRow) => {
    editingId.value = order.id;
    editForm.status = order.status;
    editForm.handler_type = order.store ? 'store' : order.warehouse ? 'warehouse' : 'store';
    editForm.store_id = order.store?.id ?? '';
    editForm.warehouse_id = order.warehouse?.id ?? '';
};

const cancelEdit = () => {
    editingId.value = null;
};

const save = (order: OrderRow) => {
    router.put(props.urls.update.replace('__ID__', order.id), {
        status: editForm.status,
        handler_type: editForm.handler_type,
        store_id: editForm.handler_type === 'store' ? editForm.store_id : null,
        warehouse_id: editForm.handler_type === 'warehouse' ? editForm.warehouse_id : null,
    }, {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
};

const totalItems = (order: OrderRow) =>
    order.details.reduce((total, detail) => total + Number(detail.quantity ?? 0), 0);

const hasStockIssue = (order: OrderRow) => props.stockIssueOrderIds.includes(order.id);

const formatCurrency = (value: number | string) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value ?? 0));

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const statusClass = (status: string) => {
    if (status === 'received' || status === 'paid') {
        return 'bg-success';
    }

    if (status === 'cancelled') {
        return 'bg-danger';
    }

    if (status === 'shipped' || status === 'packing' || status === 'processed') {
        return 'bg-info';
    }

    return 'bg-warning';
};

const paymentStatusClass = (status: string | null | undefined) => {
    if (status === 'paid' || status === 'success') {
        return 'bg-label-success';
    }

    if (status === 'failed' || status === 'cancelled' || status === 'refunded') {
        return 'bg-label-danger';
    }

    return 'bg-label-warning';
};
</script>

<template>
    <Head title="Ecommerce Orders" />

    <div class="ecommerce-page">
        <div class="ecommerce-header">
            <div>
                <span class="ecommerce-eyebrow">Ecommerce</span>
                <h1>Orders</h1>
                <p>Kelola order ecommerce dan lokasi pemenuhan stok.</p>
            </div>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card ecommerce-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Cari</label>
                        <input v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Order number, produk..." />
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select v-model="form.status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Handler</label>
                        <select v-model="form.handler_type" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="store">Store</option>
                            <option value="warehouse">Warehouse</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Store</label>
                        <select v-model="form.store_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Warehouse</label>
                        <select v-model="form.warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <select v-model="form.payment_method_code" class="form-select form-select-sm">
                            <option value="">Semua metode</option>
                            <option v-for="method in paymentMethods" :key="method.payment_method_code" :value="method.payment_method_code">{{ method.payment_method_name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Dari</label>
                        <input v-model="form.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Sampai</label>
                        <input v-model="form.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-12 col-md-4 col-lg-5 d-flex flex-wrap align-items-center gap-3">
                        <div class="form-check">
                            <input id="stock-issue" v-model="form.stock_issue" class="form-check-input" type="checkbox" />
                            <label class="form-check-label" for="stock-issue">Hanya kendala stok</label>
                        </div>
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-filter me-1"></i>Filter</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card ecommerce-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Pembayaran</th>
                            <th>Store</th>
                            <th>Warehouse</th>
                            <th class="text-end">Item</th>
                            <th class="text-end">Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="order in orders.data" :key="order.id">
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ order.order_number ?? order.id }}</div>
                                    <span v-if="hasStockIssue(order)" class="badge bg-danger mt-1">Kendala stok</span>
                                </td>
                                <td>{{ formatDate(order.created_at) }}</td>
                                <td><span class="badge" :class="statusClass(order.status)">{{ order.status }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ order.payment_method_name || order.payment_method_code || '-' }}</div>
                                    <span class="badge" :class="paymentStatusClass(order.payment?.status)">{{ order.payment?.status ?? 'tanpa payment' }}</span>
                                    <small v-if="Number(order.payment_fee) > 0" class="text-muted d-block">Fee {{ formatCurrency(order.payment_fee) }}</small>
                                </td>
                                <td>{{ order.store?.name ?? '-' }}</td>
                                <td>{{ order.warehouse?.name ?? '-' }}</td>
                                <td class="text-end">{{ totalItems(order) }}</td>
                                <td class="text-end">{{ formatCurrency(order.grand_total) }}</td>
                                <td>
                                    <button
                                        v-if="order.status === 'paid'"
                                        class="btn btn-sm btn-outline-primary"
                                        type="button"
                                        @click="startEdit(order)"
                                    >
                                        Edit
                                    </button>
                                    <span v-else class="text-muted small">-</span>
                                </td>
                            </tr>
                            <tr v-if="editingId === order.id">
                                <td colspan="9" class="ecommerce-detail">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label">Status</label>
                                            <select v-model="editForm.status" class="form-select form-select-sm">
                                                <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Handler</label>
                                            <select v-model="editForm.handler_type" class="form-select form-select-sm">
                                                <option value="store">Store</option>
                                                <option value="warehouse">Warehouse</option>
                                            </select>
                                        </div>
                                        <div v-if="editForm.handler_type === 'store'" class="col-md-3">
                                            <label class="form-label">Store</label>
                                            <select v-model="editForm.store_id" class="form-select form-select-sm">
                                                <option value="">Pilih store</option>
                                                <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option>
                                            </select>
                                        </div>
                                        <div v-else class="col-md-3">
                                            <label class="form-label">Warehouse</label>
                                            <select v-model="editForm.warehouse_id" class="form-select form-select-sm">
                                                <option value="">Pilih warehouse</option>
                                                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex gap-2">
                                            <button class="btn btn-sm btn-primary" type="button" @click="save(order)">Simpan</button>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" @click="cancelEdit">Batal</button>
                                        </div>
                                    </div>
                                    <div class="mt-3 small text-muted">
                                        Produk:
                                        <span v-for="detail in order.details" :key="detail.id" class="me-3">
                                            {{ detail.product?.name ?? '-' }} ({{ detail.quantity }})
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="orders.data.length === 0">
                            <td colspan="9" class="ecommerce-empty">Belum ada order.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">Menampilkan {{ orders.from ?? 0 }} - {{ orders.to ?? 0 }} dari {{ orders.total }} order</small>
                <PaginationLinks :links="orders.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.ecommerce-page { --ecommerce-ink: var(--app-ink); --ecommerce-border: var(--app-border); --ecommerce-muted: var(--app-muted); color: var(--ecommerce-ink); }
.ecommerce-header { padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--ecommerce-ink); color: #fff; }
.ecommerce-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.ecommerce-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.ecommerce-header p { margin: 0; color: var(--app-hero-muted); }
.ecommerce-card { border: 1px solid var(--ecommerce-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.ecommerce-card th { color: var(--ecommerce-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.ecommerce-detail { background: var(--app-surface-2); }
.ecommerce-empty { padding: 2.5rem 1rem; color: var(--ecommerce-muted); text-align: center; }
</style>
