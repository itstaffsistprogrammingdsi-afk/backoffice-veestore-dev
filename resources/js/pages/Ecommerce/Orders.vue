<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { orderStatusMeta, paymentStatusMeta } from '@/composables/useStatusLabels';

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

interface RejectionCaseSummary {
    case_id: string;
    reason: string;
    reason_label: string;
    mode: string;
    requested_at: string | null;
    age_hours: number | null;
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
    editableStatuses: string[];
    paymentMethods: Array<{ payment_method_code: string; payment_method_name: string }>;
    stockIssueOrderIds: string[];
    rejectionCases: Record<string, RejectionCaseSummary>;
    rejectionHandoffEnabled: boolean;
    rejectionHandoffError: string | null;
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
        rejections: string;
    };
}

const props = defineProps<Props>();
const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
})));

const warehouseOptions = computed(() => props.warehouses.map((warehouse) => ({
    value: warehouse.id,
    label: warehouse.name,
})));


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
const savingId = ref<string | null>(null);
const saveError = ref('');
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
    saveError.value = '';
    editForm.status = props.editableStatuses.includes(order.status)
        ? order.status
        : props.editableStatuses[0] ?? order.status;
    editForm.handler_type = order.store ? 'store' : order.warehouse ? 'warehouse' : 'store';
    editForm.store_id = order.store?.id ?? '';
    editForm.warehouse_id = order.warehouse?.id ?? '';
};

const cancelEdit = () => {
    editingId.value = null;
    saveError.value = '';
};

const save = (order: OrderRow) => {
    if (savingId.value) return;

    savingId.value = order.id;
    saveError.value = '';

    router.put(props.urls.update.replace('__ID__', order.id), {
        status: editForm.status,
        handler_type: editForm.status === 'cancelled' ? null : editForm.handler_type,
        store_id: editForm.status !== 'cancelled' && editForm.handler_type === 'store'
            ? editForm.store_id
            : null,
        warehouse_id: editForm.status !== 'cancelled' && editForm.handler_type === 'warehouse'
            ? editForm.warehouse_id
            : null,
    }, {
        preserveScroll: true,
        onSuccess: cancelEdit,
        onError: (errors) => {
            saveError.value = (Object.values(errors)[0] as string | undefined)
                ?? 'Pesanan belum dapat disimpan. Periksa data lalu coba lagi.';
        },
        onFinish: () => {
            savingId.value = null;
        },
    });
};

const totalItems = (order: OrderRow) =>
    order.details.reduce((total, detail) => total + Number(detail.quantity ?? 0), 0);

const hasStockIssue = (order: OrderRow) => props.stockIssueOrderIds.includes(order.id);
const rejectionFor = (order: OrderRow) => props.rejectionCases?.[order.id] ?? null;
const rejectionCount = computed(() => Object.keys(props.rejectionCases ?? {}).length);
const rejectionUrl = (order: OrderRow) => `${props.urls.rejections}?order_id=${encodeURIComponent(order.id)}`;
const rejectionAge = (hours: number | null) => {
    if (hours === null) return 'baru diajukan';
    if (hours < 1) return 'kurang dari 1 jam';
    if (hours < 24) return `${Math.floor(hours)} jam`;

    return `${Math.floor(hours / 24)} hari`;
};

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

const statusMeta = (status: string) => orderStatusMeta(status);
const paymentStatus = (status: string | null | undefined) => paymentStatusMeta(status);
</script>

<template>
    <Head title="Pesanan E-commerce" />

    <div class="ecommerce-page">
        <div class="ecommerce-header">
            <div>
                <span class="ecommerce-eyebrow">Ecommerce</span>
                <h1>Pesanan</h1>
                <p>Kelola pesanan e-commerce dan lokasi pemenuhan stok.</p>
            </div>
            <Link v-if="props.rejectionHandoffEnabled" :href="props.urls.rejections" class="ecommerce-header-link">
                <i class="mdi mdi-alert-decagram-outline" aria-hidden="true"></i>
                Kendala E-commerce
                <span v-if="rejectionCount > 0" class="badge bg-danger">{{ rejectionCount }}</span>
            </Link>
        </div>

        <div v-if="flash.success" class="alert alert-success" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger" role="alert">{{ flash.error }}</div>
        <div v-if="props.rejectionHandoffError" class="alert alert-warning" role="status">{{ props.rejectionHandoffError }}</div>

        <div class="card ecommerce-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-4">
                        <label class="form-label" for="orders-search">Cari</label>
                        <input id="orders-search" v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Nomor pesanan, produk..." />
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="orders-status">Status</label>
                        <select id="orders-status" v-model="form.status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option v-for="status in statuses" :key="status" :value="status">{{ statusMeta(status).label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="orders-handler">Penanganan</label>
                        <select id="orders-handler" v-model="form.handler_type" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="store">Toko</option>
                            <option value="warehouse">Gudang</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="orders-store">Toko</label>
                        <SearchableSelect id="orders-store" v-model="form.store_id" :options="storeOptions" placeholder="Semua toko" search-placeholder="Cari toko..." aria-label="Filter toko" />
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="orders-warehouse">Gudang</label>
                        <SearchableSelect id="orders-warehouse" v-model="form.warehouse_id" :options="warehouseOptions" placeholder="Semua gudang" search-placeholder="Cari gudang..." aria-label="Filter gudang" />
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <label class="form-label" for="orders-payment-method">Metode Pembayaran</label>
                        <select id="orders-payment-method" v-model="form.payment_method_code" class="form-select form-select-sm">
                            <option value="">Semua metode</option>
                            <option v-for="method in paymentMethods" :key="method.payment_method_code" :value="method.payment_method_code">{{ method.payment_method_name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="orders-from">Dari</label>
                        <input id="orders-from" v-model="form.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="orders-to">Sampai</label>
                        <input id="orders-to" v-model="form.to" class="form-control form-control-sm" type="date" />
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
                    <caption class="visually-hidden">
                        Daftar pesanan e-commerce beserta status, pembayaran, dan lokasi pemenuhan.
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Pesanan</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Status</th>
                            <th scope="col">Pembayaran</th>
                            <th scope="col">Toko</th>
                            <th scope="col">Gudang</th>
                            <th scope="col" class="text-end">Barang</th>
                            <th scope="col" class="text-end">Total</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="order in orders.data" :key="order.id">
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ order.order_number ?? order.id }}</div>
                                    <span v-if="hasStockIssue(order)" class="badge bg-danger mt-1">Kendala stok</span>
                                    <Link
                                        v-if="props.rejectionHandoffEnabled && rejectionFor(order)"
                                        :href="rejectionUrl(order)"
                                        class="badge bg-warning text-dark mt-1 d-inline-flex align-items-center gap-1 ecommerce-rejection-badge"
                                        :title="`Tinjau kendala toko: ${rejectionFor(order)?.reason_label}`"
                                    >
                                        <i class="mdi mdi-alert-outline" aria-hidden="true"></i>
                                        Kendala toko, {{ rejectionAge(rejectionFor(order)?.age_hours ?? null) }}
                                    </Link>
                                </td>
                                <td>{{ formatDate(order.created_at) }}</td>
                                <td><span class="badge" :class="statusMeta(order.status).tone">{{ statusMeta(order.status).label }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ order.payment_method_name || order.payment_method_code || '-' }}</div>
                                    <span class="badge" :class="paymentStatus(order.payment?.status).tone">{{ order.payment?.status ? paymentStatus(order.payment.status).label : 'Tanpa pembayaran' }}</span>
                                    <small v-if="Number(order.payment_fee) > 0" class="text-muted d-block">Biaya {{ formatCurrency(order.payment_fee) }}</small>
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
                                        Ubah
                                    </button>
                                    <span v-else class="text-muted small">-</span>
                                </td>
                            </tr>
                            <tr v-if="editingId === order.id">
                                <td colspan="9" class="ecommerce-detail">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label" :for="`edit-status-${order.id}`">Status</label>
                                            <select :id="`edit-status-${order.id}`" v-model="editForm.status" class="form-select form-select-sm">
                                                <option v-for="status in editableStatuses" :key="status" :value="status">{{ statusMeta(status).label }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label" :for="`edit-handler-${order.id}`">Penanganan</label>
                                            <select
                                                :id="`edit-handler-${order.id}`"
                                                v-model="editForm.handler_type"
                                                class="form-select form-select-sm"
                                                :disabled="editForm.status === 'cancelled'"
                                            >
                                                <option value="store">Toko</option>
                                                <option value="warehouse">Gudang</option>
                                            </select>
                                        </div>
                                        <div v-if="editForm.status !== 'cancelled' && editForm.handler_type === 'store'" class="col-md-3">
                                            <label class="form-label" :for="`edit-store-${order.id}`">Toko</label>
                                            <SearchableSelect
                                                :id="`edit-store-${order.id}`"
                                                v-model="editForm.store_id"
                                                :options="storeOptions"
                                                placeholder="Pilih toko"
                                                search-placeholder="Cari toko..."
                                                aria-label="Toko penanganan"
                                            />
                                        </div>
                                        <div v-else-if="editForm.status !== 'cancelled'" class="col-md-3">
                                            <label class="form-label" :for="`edit-warehouse-${order.id}`">Gudang</label>
                                            <SearchableSelect
                                                :id="`edit-warehouse-${order.id}`"
                                                v-model="editForm.warehouse_id"
                                                :options="warehouseOptions"
                                                placeholder="Pilih gudang"
                                                search-placeholder="Cari gudang..."
                                                aria-label="Gudang penanganan"
                                            />
                                        </div>
                                        <div class="col-md-3 d-flex gap-2">
                                            <button class="btn btn-sm btn-primary" type="button" :disabled="savingId === order.id" @click="save(order)">
                                                {{ savingId === order.id ? 'Menyimpan...' : 'Simpan' }}
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="savingId === order.id" @click="cancelEdit">Batal</button>
                                        </div>
                                    </div>
                                    <div v-if="saveError" class="alert alert-danger py-2 px-3 small mt-3 mb-0" role="alert">
                                        {{ saveError }}
                                    </div>
                                    <div v-if="props.rejectionHandoffEnabled && rejectionFor(order)" class="alert alert-warning py-2 px-3 small mt-3 mb-0" role="status">
                                        Pesanan ini sedang dibekukan karena kendala toko.
                                        Menyimpan perubahan akan menandai kendala selesai.
                                        <Link :href="rejectionUrl(order)" class="alert-link">Buka detail kendala</Link>
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
                            <td colspan="9" class="ecommerce-empty">
                                <i class="mdi mdi-cart-outline d-block mb-2" aria-hidden="true"></i>
                                <strong class="d-block">Tidak ada pesanan yang cocok.</strong>
                                <span class="d-block mb-2">Ubah filter atau reset untuk melihat semua pesanan.</span>
                                <button class="btn btn-sm btn-outline-primary" type="button" @click="reset">Reset filter</button>
                            </td>
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
.ecommerce-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border: 1px solid var(--ecommerce-border); border-radius: 1rem; background: var(--ecommerce-surface, var(--app-surface)); color: var(--ecommerce-ink); }
.ecommerce-eyebrow { color: var(--ecommerce-muted); letter-spacing: 0.04em; }
.ecommerce-header p { color: var(--ecommerce-muted); }
.ecommerce-header-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 44px;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--ecommerce-border);
    border-radius: 0.55rem;
    background: var(--app-surface-2);
    color: var(--ecommerce-ink);
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: none;
}
.ecommerce-header-link:hover { border-color: var(--app-accent); color: var(--app-accent-ink); }
.ecommerce-rejection-badge { min-height: 28px; text-decoration: none; }
.ecommerce-eyebrow { display: inline-block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
.ecommerce-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.ecommerce-header p { margin: 0; }
.ecommerce-card { border: 1px solid var(--ecommerce-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.ecommerce-card th { color: var(--ecommerce-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.ecommerce-detail { background: var(--app-surface-2); }
.ecommerce-empty { padding: 2.5rem 1rem; color: var(--ecommerce-muted); text-align: center; }
@media (max-width: 767.98px) {
    .ecommerce-header { align-items: flex-start; flex-direction: column; padding: 1.1rem; }
}
</style>
