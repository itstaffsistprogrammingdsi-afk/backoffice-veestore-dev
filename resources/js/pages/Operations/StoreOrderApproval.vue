<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';

defineOptions({ layout: AppLayout });

interface Option {
    id: string;
    name: string;
    code?: string | null;
}

interface OrderRow {
    id: string;
    order_number: string | null;
    status: string;
    status_label: string;
    date: string | null;
    requested_at: string | null;
    approved_at: string | null;
    shipping_number: string | null;
    store: Option | null;
    warehouse: Option | null;
    approver: string | null;
    details_count: number;
    requested_qty: number;
    approved_qty: number;
    received_qty: number;
}

interface StockCell {
    qty_good: number;
    qty_good_after_repair: number;
    qty_bad: number;
    qty_on_hand: number;
    qty_reserved: number;
    qty_available: number;
}

interface AllocationSeed {
    warehouse_id: string;
    qty: number;
}

interface DraftAllocation {
    warehouse_id: string;
    qty: number;
}

interface OrderDetail {
    id: string;
    product_id: string;
    product_code: string | null;
    product_name: string;
    unit_name: string | null;
    product_price: number;
    requested_qty: number;
    approved_qty: number;
    received_qty: number;
    note: string | null;
    allocations: AllocationSeed[];
}

interface OrderDetailResponse {
    order: {
        id: string;
        order_number: string | null;
        status: string;
        status_label: string;
        date: string | null;
        requested_at: string | null;
        approved_at: string | null;
        shipping_number: string | null;
        created_by: string | null;
        approved_by: string | null;
        store: Option | null;
        warehouse: Option | null;
    };
    details: OrderDetail[];
    warehouses: Option[];
    stock: Record<string, Record<string, StockCell>>;
    is_editable: boolean;
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
    stats: {
        pending: number;
        pending_qty: number;
        approved_today: number;
        partial_today: number;
        rejected_today: number;
    };
    stores: Option[];
    statusOptions: Array<{ value: string; label: string }>;
    filters: {
        q: string;
        status: string;
        store_id: string | null;
        from: string | null;
        to: string | null;
        per_page: number;
    };
    urls: {
        index: string;
        show: string;
        approve: string;
        reject: string;
        stock: string;
        inbound: string;
    };
}

const props = defineProps<Props>();
const warehouseSelectOptions = (warehouses: Array<{ id: string; name: string }>) => warehouses.map((warehouse) => ({
    value: warehouse.id,
    label: warehouse.name,
}));

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
    hint: store.code ?? undefined,
})));


const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status || 'all',
    store_id: props.filters.store_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    per_page: String(props.filters.per_page ?? 10),
});

const selectedOrder = ref<OrderRow | null>(null);
const orderDetail = ref<OrderDetailResponse | null>(null);
const detailDraft = reactive<Record<string, DraftAllocation[]>>({});
const loadingDetail = ref(false);
const detailError = ref('');
const actionError = ref('');
const working = ref(false);
const confirmAction = ref<'approve' | 'reject' | null>(null);
const drawerCloseRef = ref<HTMLButtonElement | null>(null);
const confirmCancelRef = ref<HTMLButtonElement | null>(null);

const notify = (message: string) => {
    actionError.value = message;
};

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        status: filters.status,
        store_id: filters.store_id || undefined,
        from: filters.from || undefined,
        to: filters.to || undefined,
        per_page: filters.per_page || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.q = '';
    filters.status = 'requested';
    filters.store_id = '';
    filters.from = '';
    filters.to = '';
    filters.per_page = '10';
    applyFilters();
};

const zeroStock = (): StockCell => ({
    qty_good: 0,
    qty_good_after_repair: 0,
    qty_bad: 0,
    qty_on_hand: 0,
    qty_reserved: 0,
    qty_available: 0,
});

const stockFor = (productId: string, warehouseId: string): StockCell => {
    return orderDetail.value?.stock?.[warehouseId]?.[productId] ?? zeroStock();
};

const formatNumber = (value: number | null | undefined) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));
const formatCurrency = (value: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(value || 0);
const formatDate = (value: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
    : '-';
const formatDateTime = (value: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value))
    : '-';

const statusClass = (status: string) => {
    if (['approved', 'received'].includes(status)) return 'bg-label-success';
    if (['partial_approved', 'partial_received'].includes(status)) return 'bg-label-info';
    if (['rejected', 'cancelled'].includes(status)) return 'bg-label-danger';
    if (['requested', 'processing', 'packing', 'shipped'].includes(status)) return 'bg-label-warning';
    return 'bg-label-secondary';
};

const suggestedAllocations = (detail: OrderDetail, data: OrderDetailResponse): DraftAllocation[] => {
    let remaining = detail.requested_qty;
    const rows: DraftAllocation[] = [];

    const candidates = data.warehouses
        .map((warehouse) => ({
            warehouse_id: warehouse.id,
            available: data.stock?.[warehouse.id]?.[detail.product_id]?.qty_available ?? 0,
        }))
        .filter((candidate) => candidate.available > 0)
        .sort((a, b) => b.available - a.available || a.warehouse_id.localeCompare(b.warehouse_id));

    for (const candidate of candidates) {
        if (remaining <= 0) break;

        const qty = Math.min(remaining, candidate.available);
        rows.push({ warehouse_id: candidate.warehouse_id, qty });
        remaining -= qty;
    }

    if (rows.length === 0) {
        rows.push({ warehouse_id: '', qty: detail.requested_qty });
    }

    return rows;
};

const buildDraft = (data: OrderDetailResponse) => {
    for (const key of Object.keys(detailDraft)) {
        delete detailDraft[key];
    }

    for (const detail of data.details) {
        detailDraft[detail.id] = data.is_editable
            ? suggestedAllocations(detail, data)
            : detail.allocations.map((allocation) => ({
                warehouse_id: allocation.warehouse_id,
                qty: allocation.qty,
            }));

        if (detailDraft[detail.id].length === 0) {
            detailDraft[detail.id] = [{ warehouse_id: '', qty: 0 }];
        }
    }
};

const loadDetail = async (order: OrderRow) => {
    loadingDetail.value = true;
    detailError.value = '';
    actionError.value = '';
    orderDetail.value = null;

    try {
        const response = await fetch(props.urls.show.replace('__ID__', order.id), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = (await response.json()) as OrderDetailResponse;
        orderDetail.value = data;
        buildDraft(data);
    } catch {
        detailError.value = 'Detail order gagal dimuat. Periksa koneksi lalu coba lagi.';
    } finally {
        loadingDetail.value = false;
    }
};

const openOrder = (order: OrderRow) => {
    selectedOrder.value = order;
    document.body.style.overflow = 'hidden';
    document.getElementById('app')?.setAttribute('inert', '');
    void loadDetail(order);
    nextTick(() => drawerCloseRef.value?.focus());
};

const retryDetail = () => {
    if (selectedOrder.value) {
        void loadDetail(selectedOrder.value);
    }
};

const closeOrder = () => {
    if (working.value) return;

    selectedOrder.value = null;
    orderDetail.value = null;
    detailError.value = '';
    actionError.value = '';
    confirmAction.value = null;
    document.body.style.overflow = '';
    document.getElementById('app')?.removeAttribute('inert');
};

const rowsFor = (detail: OrderDetail) => detailDraft[detail.id] ?? [];

const availableFor = (detail: OrderDetail, row: DraftAllocation): number => {
    if (!row.warehouse_id) return 0;

    return stockFor(detail.product_id, row.warehouse_id).qty_available;
};

const detailTotal = (detail: OrderDetail): number => {
    return rowsFor(detail)
        .filter((row) => row.warehouse_id && Number(row.qty) > 0)
        .reduce((total, row) => total + Number(row.qty), 0);
};

const detailRemaining = (detail: OrderDetail): number => Math.max(0, detail.requested_qty - detailTotal(detail));

const rowError = (detail: OrderDetail, row: DraftAllocation): string => {
    const qty = Number(row.qty) || 0;

    if (qty <= 0) return '';

    if (!row.warehouse_id) return 'Pilih gudang untuk qty ini.';

    const available = availableFor(detail, row);

    if (qty > available) {
        return `Stok tersedia hanya ${formatNumber(available)} pcs.`;
    }

    return '';
};

const hasDuplicateWarehouse = (detail: OrderDetail): boolean => {
    const warehouseIds = rowsFor(detail)
        .filter((row) => row.warehouse_id && Number(row.qty) > 0)
        .map((row) => row.warehouse_id);

    return new Set(warehouseIds).size !== warehouseIds.length;
};

const detailErrors = (detail: OrderDetail): string[] => {
    const errors: string[] = [];

    if (detailTotal(detail) > detail.requested_qty) {
        errors.push(`Qty melebihi permintaan ${formatNumber(detail.requested_qty)} pcs.`);
    }

    if (hasDuplicateWarehouse(detail)) {
        errors.push('Gudang yang sama dipilih lebih dari sekali.');
    }

    for (const row of rowsFor(detail)) {
        const message = rowError(detail, row);

        if (message !== '' && !errors.includes(message)) {
            errors.push(message);
        }
    }

    return errors;
};

const canAddWarehouse = (detail: OrderDetail): boolean => {
    if (!orderDetail.value?.is_editable) return false;
    if (detailRemaining(detail) <= 0) return false;

    const used = new Set(rowsFor(detail).map((row) => row.warehouse_id).filter(Boolean));

    return orderDetail.value.warehouses.some((warehouse) => !used.has(warehouse.id));
};

const addAllocation = (detail: OrderDetail) => {
    if (!canAddWarehouse(detail)) return;

    const used = new Set(rowsFor(detail).map((row) => row.warehouse_id).filter(Boolean));
    const candidates = orderDetail.value?.warehouses ?? [];
    const warehouse = candidates.find((candidate) => !used.has(candidate.id) && stockFor(detail.product_id, candidate.id).qty_available > 0)
        ?? candidates.find((candidate) => !used.has(candidate.id));

    if (!warehouse) return;

    const remaining = detailRemaining(detail);
    const available = stockFor(detail.product_id, warehouse.id).qty_available;

    detailDraft[detail.id].push({
        warehouse_id: warehouse.id,
        qty: Math.max(1, Math.min(remaining, available || remaining)),
    });
};

const removeAllocation = (detail: OrderDetail, index: number) => {
    detailDraft[detail.id].splice(index, 1);

    if (detailDraft[detail.id].length === 0) {
        detailDraft[detail.id].push({ warehouse_id: '', qty: 0 });
    }
};

const autoSplit = (detail: OrderDetail) => {
    if (!orderDetail.value?.is_editable) return;

    detailDraft[detail.id] = suggestedAllocations(detail, orderDetail.value);
};

const onWarehouseChange = (detail: OrderDetail, row: DraftAllocation) => {
    if (Number(row.qty) > 0 || !row.warehouse_id) return;

    const available = stockFor(detail.product_id, row.warehouse_id).qty_available;
    const remaining = Math.max(0, detail.requested_qty - detailTotal(detail));

    if (remaining <= 0) return;

    row.qty = Math.max(1, Math.min(remaining, available || remaining));
};

const stockTone = (detail: OrderDetail, row: DraftAllocation) => {
    if (!row.warehouse_id) return '';

    const qty = Number(row.qty) || 0;
    const available = availableFor(detail, row);

    if (qty > available) return 'is-danger';
    if (available === 0) return 'is-danger';
    if (qty === available) return 'is-warning';

    return 'is-ok';
};

const validationMessages = computed<string[]>(() => {
    if (!orderDetail.value) return [];

    const messages: string[] = [];

    for (const detail of orderDetail.value.details) {
        for (const message of detailErrors(detail)) {
            const formatted = `${detail.product_name}: ${message}`;

            if (!messages.includes(formatted)) {
                messages.push(formatted);
            }
        }
    }

    if (messages.length === 0 && totalAllocated.value <= 0) {
        messages.push('Isi qty minimal satu produk untuk disetujui.');
    }

    return messages;
});

const totalRequested = computed(() => {
    return orderDetail.value?.details.reduce((total, detail) => total + detail.requested_qty, 0) ?? 0;
});

const totalAllocated = computed(() => {
    return orderDetail.value?.details.reduce((total, detail) => total + detailTotal(detail), 0) ?? 0;
});

const progressWidth = computed(() => {
    if (totalRequested.value <= 0) return '0%';

    return `${Math.min(100, Math.round((totalAllocated.value / totalRequested.value) * 100))}%`;
});

const isPartial = computed(() => totalAllocated.value < totalRequested.value);

const allocationPayload = computed(() => {
    if (!orderDetail.value) return [];

    return orderDetail.value.details.flatMap((detail) => rowsFor(detail)
        .filter((row) => row.warehouse_id && Number(row.qty) > 0)
        .map((row) => ({
            detail_id: detail.id,
            warehouse_id: row.warehouse_id,
            qty: Number(row.qty),
        })));
});

const warehouseCount = computed(() => new Set(allocationPayload.value.map((allocation) => allocation.warehouse_id)).size);

const overallValid = computed(() => {
    if (!orderDetail.value?.is_editable) return false;
    if (orderDetail.value.details.length === 0) return false;
    if (totalAllocated.value <= 0) return false;

    return orderDetail.value.details.every((detail) => detailErrors(detail).length === 0);
});

const openConfirm = (type: 'approve' | 'reject') => {
    if (type === 'approve' && !overallValid.value) return;

    actionError.value = '';
    confirmAction.value = type;
};

const closeConfirm = () => {
    if (working.value) return;

    confirmAction.value = null;
};

const submitApprove = () => {
    if (!selectedOrder.value || !overallValid.value) return;

    working.value = true;
    actionError.value = '';

    router.patch(props.urls.approve.replace('__ID__', selectedOrder.value.id), {
        allocations: allocationPayload.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            confirmAction.value = null;
            working.value = false;
            closeOrder();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string | undefined;
            notify(firstError ?? 'Order gagal disetujui. Periksa alokasi gudang.');
            confirmAction.value = null;
        },
        onFinish: () => {
            working.value = false;
        },
    });
};

const submitReject = () => {
    if (!selectedOrder.value) return;

    working.value = true;
    actionError.value = '';

    router.patch(props.urls.reject.replace('__ID__', selectedOrder.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            confirmAction.value = null;
            working.value = false;
            closeOrder();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string | undefined;
            notify(firstError ?? 'Order gagal ditolak.');
            confirmAction.value = null;
        },
        onFinish: () => {
            working.value = false;
        },
    });
};

const executeConfirm = () => {
    if (confirmAction.value === 'approve') {
        submitApprove();
        return;
    }

    if (confirmAction.value === 'reject') {
        submitReject();
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key !== 'Escape') return;

    if (confirmAction.value) {
        closeConfirm();
        return;
    }

    if (selectedOrder.value) {
        closeOrder();
    }
};

watch(confirmAction, async (value) => {
    await nextTick();

    if (value) {
        confirmCancelRef.value?.focus();
        return;
    }

    if (selectedOrder.value) {
        drawerCloseRef.value?.focus();
    }
});

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
    document.getElementById('app')?.removeAttribute('inert');
});
</script>

<template>
    <Head title="Persetujuan Order Store" />

    <div class="approval-page">
        <section class="report-header approval-header">
            <div>
                <span class="approval-eyebrow">Operations / Store</span>
                <h1>Persetujuan Order Store</h1>
                <p>Tinjau permintaan store, pilih gudang sumber, dan pecah pemenuhan ke beberapa gudang saat stok tidak cukup.</p>
            </div>
            <div class="approval-hero-actions">
                <Link :href="props.urls.stock" class="approval-hero-button">
                    <i class="mdi mdi-clipboard-list-outline me-1"></i>
                    Store Stock
                </Link>
                <Link :href="props.urls.inbound" class="approval-hero-button approval-hero-button-outline">
                    <i class="mdi mdi-tray-arrow-down me-1"></i>
                    Store Inbound
                </Link>
            </div>
        </section>

        <div v-if="flash.success" class="alert alert-success" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger" role="alert">{{ flash.error }}</div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="approval-summary approval-summary-warning">
                    <span class="approval-summary-icon"><i class="mdi mdi-clipboard-clock-outline"></i></span>
                    <div>
                        <span>Menunggu approval</span>
                        <strong>{{ formatNumber(props.stats.pending) }}</strong>
                        <small>{{ formatNumber(props.stats.pending_qty) }} pcs diminta</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="approval-summary">
                    <span class="approval-summary-icon"><i class="mdi mdi-check-circle-outline"></i></span>
                    <div>
                        <span>Disetujui hari ini</span>
                        <strong>{{ formatNumber(props.stats.approved_today) }}</strong>
                        <small>Order penuh</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="approval-summary">
                    <span class="approval-summary-icon"><i class="mdi mdi-call-split"></i></span>
                    <div>
                        <span>Disetujui sebagian</span>
                        <strong>{{ formatNumber(props.stats.partial_today) }}</strong>
                        <small>Hari ini</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="approval-summary approval-summary-danger">
                    <span class="approval-summary-icon"><i class="mdi mdi-close-circle-outline"></i></span>
                    <div>
                        <span>Ditolak</span>
                        <strong>{{ formatNumber(props.stats.rejected_today) }}</strong>
                        <small>Hari ini</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card approval-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-4">
                        <label class="form-label" for="approval-search">Cari order, store, atau produk</label>
                        <input id="approval-search" v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nomor order, store, atau SKU..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="approval-status">Status</label>
                        <select id="approval-status" v-model="filters.status" class="form-select form-select-sm">
                            <option value="all">Semua status</option>
                            <option v-for="option in props.statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="approval-store">Store</label>
                        <SearchableSelect
                            id="approval-store"
                            v-model="filters.store_id"
                            :options="storeOptions"
                            placeholder="Semua store"
                            search-placeholder="Cari store..."
                        />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="approval-from">Dari tanggal</label>
                        <input id="approval-from" v-model="filters.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="approval-to">Sampai tanggal</label>
                        <input id="approval-to" v-model="filters.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="approval-per-page">Baris</label>
                        <select id="approval-per-page" v-model="filters.per_page" class="form-select form-select-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-4 d-flex gap-2">
                        <button class="btn btn-sm btn-primary flex-grow-1" type="submit">
                            <i class="mdi mdi-magnify me-1"></i>
                            Terapkan filter
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters">
                            <i class="mdi mdi-refresh"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card approval-card">
            <div class="approval-card-header">
                <div>
                    <h2>Antrean approval order store</h2>
                    <p>Default menampilkan order yang menunggu keputusan backoffice.</p>
                </div>
                <span class="approval-card-meta">{{ formatNumber(props.orders.total) }} order</span>
            </div>

            <div v-if="props.orders.data.length === 0" class="approval-empty">
                <i class="mdi mdi-clipboard-check-outline"></i>
                <strong>Tidak ada order pada filter ini</strong>
                <span>Ubah filter atau reset untuk melihat seluruh antrean approval.</span>
                <button class="btn btn-sm btn-outline-primary" type="button" @click="resetFilters">Reset filter</button>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 approval-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Store</th>
                            <th class="d-none d-md-table-cell">Kebutuhan</th>
                            <th class="d-none d-lg-table-cell">Gudang</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in props.orders.data" :key="order.id">
                            <td>
                                <button class="approval-order-link" type="button" @click="openOrder(order)">
                                    {{ order.order_number ?? order.id }}
                                </button>
                                <small class="approval-subvalue">Diajukan {{ formatDateTime(order.requested_at) }}</small>
                            </td>
                            <td>
                                <strong>{{ order.store?.name ?? '-' }}</strong>
                                <small class="approval-subvalue">{{ order.store?.code ?? 'Tanpa kode' }}</small>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <strong>{{ formatNumber(order.requested_qty) }} pcs</strong>
                                <small class="approval-subvalue">{{ formatNumber(order.details_count) }} produk</small>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <strong :class="{ 'approval-muted-strong': !order.warehouse }">{{ order.warehouse?.name ?? 'Belum dipilih' }}</strong>
                                <small v-if="order.warehouse" class="approval-subvalue">{{ formatNumber(order.approved_qty) }} pcs disetujui</small>
                                <small v-else class="approval-subvalue">Ditentukan saat approval</small>
                            </td>
                            <td>
                                <span class="badge" :class="statusClass(order.status)">{{ order.status_label }}</span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" @click="openOrder(order)">
                                    <i class="mdi mdi-clipboard-search-outline me-1"></i>
                                    Tinjau
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="approval-footer">
                <small class="text-muted">Menampilkan {{ props.orders.from ?? 0 }} - {{ props.orders.to ?? 0 }} dari {{ props.orders.total }} order</small>
                <PaginationLinks :links="props.orders.links" />
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="selectedOrder" class="approval-backdrop" @click.self="closeOrder">
            <section class="approval-drawer" role="dialog" aria-modal="true" aria-labelledby="approval-drawer-title" :inert="confirmAction ? true : undefined">
                <header class="approval-drawer-header">
                    <div>
                        <span class="approval-drawer-eyebrow">Order store</span>
                        <h2 id="approval-drawer-title">{{ selectedOrder.order_number ?? selectedOrder.id }}</h2>
                        <p>
                            {{ selectedOrder.store?.name ?? '-' }}
                            <span aria-hidden="true"> &middot; </span>
                            Diajukan {{ formatDateTime(selectedOrder.requested_at) }}
                        </p>
                    </div>
                    <div class="approval-drawer-header-actions">
                        <span class="badge" :class="statusClass(selectedOrder.status)">{{ selectedOrder.status_label }}</span>
                        <button ref="drawerCloseRef" class="approval-icon-button" type="button" aria-label="Tutup detail order" @click="closeOrder">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                </header>

                <div class="approval-drawer-body">
                    <div v-if="loadingDetail" class="approval-state" aria-live="polite">
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        <strong>Memuat detail order</strong>
                        <span>Menyiapkan produk, stok gudang, dan alokasi.</span>
                    </div>

                    <div v-else-if="detailError" class="approval-state approval-state-danger" role="alert">
                        <i class="mdi mdi-alert-circle-outline"></i>
                        <strong>Detail order tidak dapat dimuat</strong>
                        <span>{{ detailError }}</span>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="retryDetail">Coba lagi</button>
                    </div>

                    <template v-else-if="orderDetail">
                        <div v-if="actionError" class="alert alert-danger" role="alert">{{ actionError }}</div>

                        <div v-if="!orderDetail.is_editable" class="alert alert-info" role="status">
                            Order ini sudah diproses. Alokasi gudang hanya dapat dilihat.
                        </div>

                        <div class="approval-meta-grid">
                            <div>
                                <span>Tanggal order</span>
                                <strong>{{ formatDate(orderDetail.order.date) }}</strong>
                            </div>
                            <div>
                                <span>Diminta oleh</span>
                                <strong>{{ orderDetail.order.created_by ?? '-' }}</strong>
                            </div>
                            <div>
                                <span>Gudang saat ini</span>
                                <strong>{{ orderDetail.order.warehouse?.name ?? 'Belum dipilih' }}</strong>
                            </div>
                            <div>
                                <span>Nomor pengiriman</span>
                                <strong>{{ orderDetail.order.shipping_number || '-' }}</strong>
                            </div>
                        </div>

                        <div v-if="validationMessages.length > 0" class="alert alert-warning" role="alert">
                            <strong>Periksa alokasi:</strong>
                            {{ validationMessages[0] }}
                            <span v-if="validationMessages.length > 1"> (+{{ validationMessages.length - 1 }} masalah lain)</span>
                        </div>

                        <div class="approval-items">
                            <article v-for="detail in orderDetail.details" :key="detail.id" class="approval-item">
                                <header class="approval-item-header">
                                    <div>
                                        <strong>{{ detail.product_name }}</strong>
                                        <small>{{ detail.product_code ?? 'Tanpa kode' }}<span v-if="detail.unit_name"> &middot; {{ detail.unit_name }}</span></small>
                                    </div>
                                    <div class="approval-item-request">
                                        <span>Diminta</span>
                                        <strong>{{ formatNumber(detail.requested_qty) }} {{ detail.unit_name ?? 'pcs' }}</strong>
                                        <small>{{ formatCurrency(detail.product_price) }} / unit</small>
                                    </div>
                                </header>

                                <p v-if="detail.note" class="approval-note">
                                    <i class="mdi mdi-note-text-outline me-1"></i>
                                    {{ detail.note }}
                                </p>

                                <div class="approval-allocations">
                                    <div v-for="(row, index) in rowsFor(detail)" :key="`${detail.id}-${index}`" class="approval-allocation-row">
                                        <div class="approval-field">
                                            <label :for="`warehouse-${detail.id}-${index}`">Gudang</label>
                                            <SearchableSelect
                                                :id="`warehouse-${detail.id}-${index}`"
                                                v-model="row.warehouse_id"
                                                :options="warehouseSelectOptions(orderDetail.warehouses)"
                                                placeholder="Pilih gudang"
                                                search-placeholder="Cari gudang..."
                                                :disabled="!orderDetail.is_editable || working"
                                                @change="onWarehouseChange(detail, row)"
                                            />
                                        </div>

                                        <div class="approval-field approval-field-qty">
                                            <label :for="`qty-${detail.id}-${index}`">Qty</label>
                                            <input
                                                :id="`qty-${detail.id}-${index}`"
                                                v-model.number="row.qty"
                                                class="form-control form-control-sm text-end"
                                                type="number"
                                                min="0"
                                                :max="detail.requested_qty"
                                                :disabled="!orderDetail.is_editable || working"
                                            />
                                        </div>

                                        <div class="approval-stock" :class="stockTone(detail, row)">
                                            <span v-if="row.warehouse_id">Tersedia {{ formatNumber(availableFor(detail, row)) }} {{ detail.unit_name ?? 'pcs' }}</span>
                                            <span v-else>Pilih gudang untuk melihat stok</span>
                                            <small v-if="row.warehouse_id && stockFor(detail.product_id, row.warehouse_id).qty_reserved > 0">
                                                {{ formatNumber(stockFor(detail.product_id, row.warehouse_id).qty_reserved) }} sedang dipesan order lain
                                            </small>
                                            <small v-else-if="rowError(detail, row)">{{ rowError(detail, row) }}</small>
                                        </div>

                                        <button
                                            v-if="orderDetail.is_editable"
                                            class="approval-remove-button"
                                            type="button"
                                            :aria-label="`Hapus alokasi untuk ${detail.product_name}`"
                                            :disabled="working"
                                            @click="removeAllocation(detail, index)"
                                        >
                                            <i class="mdi mdi-delete-outline"></i>
                                        </button>
                                    </div>
                                </div>

                                <footer class="approval-item-footer">
                                    <div v-if="orderDetail.is_editable" class="approval-item-actions">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="working" @click="autoSplit(detail)">
                                            <i class="mdi mdi-call-split me-1"></i>
                                            Bagi otomatis
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="working || !canAddWarehouse(detail)" @click="addAllocation(detail)">
                                            <i class="mdi mdi-plus me-1"></i>
                                            Tambah gudang
                                        </button>
                                    </div>

                                    <div class="approval-item-total" :class="{ 'is-invalid': detailErrors(detail).length > 0 }">
                                        <span>Dialokasikan</span>
                                        <strong>{{ formatNumber(detailTotal(detail)) }} / {{ formatNumber(detail.requested_qty) }} {{ detail.unit_name ?? 'pcs' }}</strong>
                                        <small v-if="detailErrors(detail).length > 0">{{ detailErrors(detail)[0] }}</small>
                                    </div>
                                </footer>
                            </article>
                        </div>

                        <div v-if="orderDetail.details.length === 0" class="approval-state">
                            <i class="mdi mdi-package-variant-remove"></i>
                            <strong>Order tidak memiliki detail produk</strong>
                            <span>Hubungi store untuk melengkapi order sebelum approval.</span>
                        </div>
                    </template>
                </div>

                <footer v-if="orderDetail" class="approval-drawer-footer">
                    <div class="approval-total">
                        <span>Total alokasi</span>
                        <strong>{{ formatNumber(totalAllocated) }} / {{ formatNumber(totalRequested) }} pcs</strong>
                        <div class="approval-progress" role="progressbar" :aria-valuenow="totalAllocated" aria-valuemin="0" :aria-valuemax="totalRequested">
                            <span :style="{ width: progressWidth }"></span>
                        </div>
                        <small v-if="isPartial">Sebagian qty belum dialokasikan, order akan disetujui sebagian.</small>
                        <small v-else>Seluruh qty yang diminta sudah dialokasikan.</small>
                    </div>

                    <div class="approval-footer-actions">
                        <button
                            v-if="orderDetail.is_editable"
                            class="btn btn-outline-danger"
                            type="button"
                            :disabled="working"
                            @click="openConfirm('reject')"
                        >
                            Tolak
                        </button>
                        <button
                            v-if="orderDetail.is_editable"
                            class="btn btn-primary"
                            type="button"
                            :disabled="!overallValid || working"
                            @click="openConfirm('approve')"
                        >
                            <span v-if="working" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                            {{ isPartial ? 'Setujui sebagian' : 'Setujui order' }}
                        </button>
                        <button v-else class="btn btn-outline-secondary" type="button" @click="closeOrder">Tutup</button>
                    </div>
                </footer>
            </section>
        </div>

        <div v-if="confirmAction" class="approval-confirm-backdrop" @click.self="closeConfirm">
            <section class="approval-confirm" role="alertdialog" aria-modal="true" aria-labelledby="approval-confirm-title" aria-describedby="approval-confirm-description">
                <h3 id="approval-confirm-title">{{ confirmAction === 'approve' ? 'Setujui order ini?' : 'Tolak order ini?' }}</h3>
                <p id="approval-confirm-description">
                    <template v-if="confirmAction === 'approve'">
                        {{ formatNumber(totalAllocated) }} pcs akan dialokasikan dari {{ formatNumber(warehouseCount) }} gudang.
                        <span v-if="warehouseCount > 1">Order akan dipecah menjadi {{ formatNumber(warehouseCount) }} order gudang.</span>
                        <span v-else>Order tetap menjadi satu order gudang.</span>
                    </template>
                    <template v-else>
                        Order akan ditolak dan tidak ada stok yang dipesan untuk order ini.
                    </template>
                </p>
                <div class="approval-confirm-summary">
                    <span>{{ selectedOrder?.order_number ?? selectedOrder?.id }}</span>
                    <strong>{{ selectedOrder?.store?.name ?? '-' }}</strong>
                </div>
                <div class="approval-confirm-actions">
                    <button ref="confirmCancelRef" class="btn btn-outline-secondary" type="button" :disabled="working" @click="closeConfirm">Batal</button>
                    <button class="btn" :class="confirmAction === 'reject' ? 'btn-danger' : 'btn-primary'" type="button" :disabled="working" @click="executeConfirm">
                        <span v-if="working" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                        {{ confirmAction === 'approve' ? 'Ya, setujui' : 'Ya, tolak' }}
                    </button>
                </div>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.approval-page { color: var(--app-ink); }
.approval-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; }
.approval-eyebrow { display: inline-block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.approval-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.approval-header p { max-width: 760px; margin: 0; }
.approval-hero-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.approval-hero-button { display: inline-flex; align-items: center; min-height: 44px; padding: 0.55rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface); color: var(--app-ink); font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.approval-hero-button-outline { border: 1px solid var(--app-hero-muted); background: transparent; color: var(--app-hero-text); }
.approval-hero-button:focus-visible,
.approval-order-link:focus-visible,
.approval-icon-button:focus-visible,
.approval-remove-button:focus-visible { outline: 2px solid var(--app-accent); outline-offset: 2px; }
.approval-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.approval-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.approval-summary span:not(.approval-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.approval-summary strong { display: block; margin-top: 0.15rem; font-size: 1.35rem; }
.approval-summary small { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: 0.68rem; }
.approval-summary-warning .approval-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.approval-summary-danger .approval-summary-icon { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.approval-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.approval-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.approval-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.approval-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.approval-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.approval-table th { padding: 0.6rem 0.9rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.approval-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); color: var(--app-ink); }
.approval-order-link { padding: 0; border: 0; background: transparent; color: var(--app-accent-ink); font-weight: 700; text-align: left; text-decoration: underline; text-underline-offset: 3px; }
.approval-subvalue { display: block; color: var(--app-muted); font-size: 0.73rem; }
.approval-muted-strong { color: var(--app-muted); font-weight: 600; }
.approval-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.approval-empty { display: grid; place-items: center; gap: 0.35rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.approval-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.approval-empty strong { color: var(--app-ink); }
.approval-empty .btn { margin-top: 0.4rem; }
.approval-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; justify-content: flex-end; background: rgba(15, 23, 42, 0.58); }
.approval-drawer { display: flex; flex-direction: column; width: min(820px, 100%); height: 100%; background: var(--app-surface); color: var(--app-ink); box-shadow: -1rem 0 2.5rem rgba(15, 23, 42, 0.24); }
.approval-drawer-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--app-border); background: var(--app-surface); }
.approval-drawer-eyebrow { display: block; margin-bottom: 0.2rem; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; }
.approval-drawer-header h2 { margin: 0 0 0.2rem; font-size: 1.15rem; }
.approval-drawer-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.approval-drawer-header-actions { display: flex; align-items: center; gap: 0.6rem; }
.approval-icon-button { display: grid; width: 44px; height: 44px; place-items: center; border: 1px solid var(--app-border); border-radius: 0.6rem; background: var(--app-surface-2); color: var(--app-ink); font-size: 1.2rem; }
.approval-drawer-body { flex: 1; padding: 1.1rem 1.25rem; overflow-y: auto; }
.approval-state { display: grid; place-items: center; gap: 0.35rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.approval-state i { font-size: 1.8rem; color: var(--app-accent-ink); }
.approval-state strong { color: var(--app-ink); }
.approval-state-danger i { color: var(--app-danger-ink); }
.approval-state-danger .btn { margin-top: 0.4rem; }
.approval-meta-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.7rem; margin-bottom: 1rem; }
.approval-meta-grid > div { padding: 0.7rem 0.8rem; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface-2); }
.approval-meta-grid span { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.approval-meta-grid strong { display: block; margin-top: 0.15rem; font-size: 0.85rem; overflow-wrap: anywhere; }
.approval-items { display: grid; gap: 0.9rem; }
.approval-item { padding: 0.95rem; border: 1px solid var(--app-border); border-radius: 0.85rem; background: var(--app-surface); }
.approval-item-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.approval-item-header > div:first-child { min-width: 0; }
.approval-item-header strong { display: block; overflow-wrap: anywhere; }
.approval-item-header small { display: block; color: var(--app-muted); font-size: 0.74rem; }
.approval-item-request { text-align: right; white-space: nowrap; }
.approval-item-request span { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.approval-item-request strong { font-size: 0.95rem; }
.approval-note { margin: 0.6rem 0 0; padding: 0.5rem 0.65rem; border-radius: 0.55rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.78rem; }
.approval-allocations { display: grid; gap: 0.55rem; margin-top: 0.75rem; }
.approval-allocation-row { display: grid; grid-template-columns: minmax(150px, 1.2fr) minmax(90px, 0.45fr) minmax(150px, 1fr) 44px; gap: 0.55rem; align-items: end; }
.approval-field label { display: block; margin-bottom: 0.2rem; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; }
.approval-field .form-control,
.approval-field .form-select { min-height: 38px; }
.approval-stock { display: grid; gap: 0.1rem; min-height: 38px; padding: 0.4rem 0.55rem; border: 1px solid var(--app-border); border-radius: 0.45rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.74rem; }
.approval-stock span { overflow-wrap: anywhere; }
.approval-stock small { font-size: 0.68rem; }
.approval-stock.is-ok { border-color: color-mix(in srgb, var(--app-success-ink) 32%, var(--app-border)); background: var(--app-success-bg); color: var(--app-success-ink); }
.approval-stock.is-warning { border-color: color-mix(in srgb, var(--app-warning-ink) 32%, var(--app-border)); background: var(--app-warning-bg); color: var(--app-warning-ink); }
.approval-stock.is-danger { border-color: color-mix(in srgb, var(--app-danger-ink) 32%, var(--app-border)); background: var(--app-danger-bg); color: var(--app-danger-ink); }
.approval-remove-button { display: grid; width: 44px; height: 38px; place-items: center; border: 1px solid var(--app-border); border-radius: 0.45rem; background: var(--app-surface); color: var(--app-danger-ink); }
.approval-item-footer { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 0.7rem; margin-top: 0.75rem; }
.approval-item-actions { display: flex; flex-wrap: wrap; gap: 0.45rem; }
.approval-item-total { text-align: right; }
.approval-item-total span { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.approval-item-total strong { display: block; font-size: 0.88rem; }
.approval-item-total small { display: block; color: var(--app-danger-ink); font-size: 0.7rem; }
.approval-item-total.is-invalid strong { color: var(--app-danger-ink); }
.approval-drawer-footer { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-top: 1px solid var(--app-border); background: var(--app-surface-2); }
.approval-total { min-width: 240px; max-width: 420px; }
.approval-total span { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.approval-total strong { display: block; margin-top: 0.1rem; font-size: 1rem; }
.approval-total small { display: block; margin-top: 0.3rem; color: var(--app-muted); font-size: 0.72rem; }
.approval-progress { height: 0.4rem; margin-top: 0.35rem; overflow: hidden; border-radius: 999px; background: var(--app-surface-3); }
.approval-progress span { display: block; height: 100%; border-radius: inherit; background: var(--app-accent); transition: width 180ms ease; }
.approval-footer-actions { display: flex; flex-wrap: wrap; gap: 0.6rem; }
.approval-footer-actions .btn,
.approval-item-actions .btn,
.approval-confirm-actions .btn { min-height: 44px; }
.approval-confirm-backdrop { position: fixed; inset: 0; z-index: 1090; display: grid; place-items: center; padding: 1rem; background: rgba(15, 23, 42, 0.68); }
.approval-confirm { width: min(460px, 100%); padding: 1.25rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); color: var(--app-ink); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.32); }
.approval-confirm h3 { margin: 0 0 0.4rem; font-size: 1.1rem; }
.approval-confirm p { margin: 0 0 0.85rem; color: var(--app-muted); font-size: 0.86rem; line-height: 1.55; }
.approval-confirm-summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.65rem 0.75rem; border: 1px solid var(--app-border); border-radius: 0.6rem; background: var(--app-surface-2); font-size: 0.8rem; }
.approval-confirm-summary span { color: var(--app-muted); overflow-wrap: anywhere; }
.approval-confirm-actions { display: flex; justify-content: flex-end; gap: 0.55rem; margin-top: 1rem; }
@media (max-width: 991.98px) {
    .approval-header { align-items: flex-start; flex-direction: column; }
    .approval-card-header { align-items: flex-start; flex-direction: column; }
    .approval-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .approval-allocation-row { grid-template-columns: minmax(0, 1fr) minmax(80px, 0.4fr) 44px; }
    .approval-allocation-row > .approval-field:first-child { grid-column: 1; grid-row: 1; }
    .approval-field-qty { grid-column: 2; grid-row: 1; }
    .approval-remove-button { align-self: end; grid-column: 3; grid-row: 1; }
    .approval-stock { grid-column: 1 / -1; grid-row: 2; }
}
@media (max-width: 575.98px) {
    .approval-drawer-header { flex-direction: column; }
    .approval-drawer-header-actions { width: 100%; justify-content: space-between; }
    .approval-meta-grid { grid-template-columns: 1fr; }
    .approval-item-header { flex-direction: column; }
    .approval-item-request { text-align: left; }
    .approval-allocation-row { grid-template-columns: minmax(0, 1fr) minmax(76px, 0.45fr) 44px; gap: 0.4rem; }
    .approval-item-footer { align-items: stretch; }
    .approval-item-total { text-align: left; }
    .approval-drawer-footer { align-items: stretch; flex-direction: column; }
    .approval-footer-actions .btn { flex: 1; }
}
@media (prefers-reduced-motion: reduce) {
    .approval-progress span { transition: none; }
}
</style>
