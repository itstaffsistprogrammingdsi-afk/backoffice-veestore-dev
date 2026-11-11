<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

defineOptions({ layout: AppLayout });

interface StoreOption {
    id: string;
    name: string;
    code: string | null;
}

interface TransferRow {
    id: string;
    order_number: string;
    status: string;
    status_label: string;
    date: string | null;
    requested_at: string | null;
    approved_at: string | null;
    shipping_number: string | null;
    created_at: string | null;
    updated_at: string | null;
    destination_store_id: string;
    destination_store_name: string;
    destination_store_code: string | null;
    source_store_id: string | null;
    source_store_name: string | null;
    source_store_code: string | null;
    created_by_name: string | null;
    approved_by_name: string | null;
    transfer_outbound_id: string | null;
    shipment_date: string | null;
    item_quantity: number | null;
    total_products: number;
    requested_qty: number;
    approved_qty: number;
    received_qty: number;
    scanned_qty: number;
    scan_remaining_qty: number;
    scan_progress_percent: number;
    outstanding_qty: number;
    progress_percent: number;
    can_approve: boolean;
    can_ship: boolean;
    can_receive: boolean;
    can_reject: boolean;
    can_cancel: boolean;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
    current_page?: number;
    last_page?: number;
}

interface Summary {
    total_transfers: number;
    waiting_approval: number;
    waiting_scan: number;
    ready_to_ship: number;
    in_transit: number;
    partial_received: number;
    completed: number;
    cancelled: number;
    requested_qty: number;
    approved_qty: number;
    received_qty: number;
    outstanding_qty: number;
}

interface Props {
    rows: Paginator<TransferRow>;
    summary: Summary;
    stores: StoreOption[];
    statusOptions: Array<{ value: string; label: string }>;
    filters: {
        q: string;
        source_store_id: string | null;
        destination_store_id: string | null;
        status: string | null;
        from: string | null;
        to: string | null;
        per_page: number;
    };
    urls: {
        index: string;
        store: string;
        show: string;
        stock: string;
        approve: string;
        ship: string;
        receive: string;
        reject: string;
        cancel: string;
        stock_page: string;
        orders_page: string;
    };
}

interface StockProduct {
    product_id: string;
    product_name: string;
    product_code: string;
    category_name: string;
    available_qty: number;
    warehouse_available_qty: number;
    can_transfer: boolean;
    product_price_id: string | null;
    product_price: number;
}

interface TransferDetail {
    id: string;
    product_id: string;
    product_name: string;
    product_code: string;
    unit_name: string | null;
    product_price: number;
    requested_qty: number;
    approved_qty: number;
    received_qty: number;
    scanned_qty: number;
    note: string | null;
}

interface TransferItem {
    id: string;
    product_name: string;
    product_code: string;
    item_status: string;
    rfid_serial: string | null;
}

interface DetailResponse {
    order: TransferRow;
    details: TransferDetail[];
    items: TransferItem[];
}

interface CreateForm {
    source_store_id: string;
    destination_store_id: string;
    note: string;
    items: Array<{ product_id: string; qty: number; note: string }>;
}

const props = defineProps<Props>();

const filters = reactive({
    q: props.filters.q ?? '',
    source_store_id: props.filters.source_store_id ?? '',
    destination_store_id: props.filters.destination_store_id ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
    hint: store.code ?? undefined,
})));

const statusClass = (status: string) => ({
    requested: 'bg-label-warning',
    approved: 'bg-label-info',
    shipped: 'bg-label-primary',
    partial_received: 'bg-label-warning',
    received: 'bg-label-success',
    rejected: 'bg-label-danger',
    cancelled: 'bg-label-secondary',
}[status] ?? 'bg-label-secondary');

const formatNumber = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const formatCurrency = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value ?? 0));

const formatDate = (value: string | null | undefined, withTime = false) => {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    }).format(date);
};

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        source_store_id: filters.source_store_id || undefined,
        destination_store_id: filters.destination_store_id || undefined,
        status: filters.status || undefined,
        from: filters.from || undefined,
        to: filters.to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    filters.q = '';
    filters.source_store_id = '';
    filters.destination_store_id = '';
    filters.status = '';
    filters.from = '';
    filters.to = '';
    applyFilters();
};

const createOpen = ref(false);
const stockProducts = ref<StockProduct[]>([]);
const stockCache = ref<Record<string, StockProduct>>({});
const stockLoading = ref(false);
const stockSearch = ref('');
const stockError = ref('');
const warehouseRule = ref('');
const actionError = ref('');
let stockTimer: ReturnType<typeof setTimeout> | null = null;

const form = useForm<CreateForm>({
    source_store_id: '',
    destination_store_id: '',
    note: '',
    items: [],
});

const productFor = (productId: string) => stockProducts.value.find((row) => row.product_id === productId) ?? stockCache.value[productId];

const totalQty = computed(() => form.items.reduce((total, item) => total + Number(item.qty || 0), 0));
const totalValue = computed(() => form.items.reduce((total, item) => {
    const product = productFor(item.product_id);

    return total + (Number(item.qty || 0) * Number(product?.product_price || 0));
}, 0));

const loadStock = async () => {
    stockError.value = '';

    if (!form.source_store_id) {
        stockProducts.value = [];
        return;
    }

    stockLoading.value = true;

    try {
        const { data } = await axios.get(props.urls.stock, {
            params: {
                source_store_id: form.source_store_id,
                q: stockSearch.value || undefined,
            },
        });

        stockProducts.value = Array.isArray(data.products) ? data.products : [];
        warehouseRule.value = String(data.warehouse_rule ?? '');
        stockProducts.value.forEach((product) => {
            stockCache.value[product.product_id] = product;
        });
    } catch {
        stockError.value = 'Gagal memuat stok toko asal. Coba lagi.';
        stockProducts.value = [];
    } finally {
        stockLoading.value = false;
    }
};

watch(() => form.source_store_id, () => {
    form.items = [];
    loadStock();
});

watch(stockSearch, () => {
    if (stockTimer) clearTimeout(stockTimer);
    stockTimer = setTimeout(loadStock, 350);
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    stockProducts.value = [];
    stockCache.value = {};
    stockSearch.value = '';
    stockError.value = '';
    warehouseRule.value = '';
    actionError.value = '';
    createOpen.value = true;
};

const closeCreate = () => {
    createOpen.value = false;
    form.clearErrors();
};

const addProduct = (product: StockProduct) => {
    if (product.available_qty <= 0 || !product.product_price_id || !product.can_transfer) return;

    const existing = form.items.find((item) => item.product_id === product.product_id);

    if (existing) {
        existing.qty = Math.min(Number(existing.qty || 0) + 1, product.available_qty);
        return;
    }

    form.items.push({
        product_id: product.product_id,
        qty: 1,
        note: '',
    });
};

const removeItem = (productId: string) => {
    form.items = form.items.filter((item) => item.product_id !== productId);
};

const submitCreate = () => {
    form.post(props.urls.store, {
        preserveScroll: true,
        onSuccess: () => closeCreate(),
    });
};

const detailOpen = ref(false);
const detailLoading = ref(false);
const detail = ref<DetailResponse | null>(null);
const detailError = ref('');
const receiveOpen = ref(false);
const receiveQty = ref<Record<string, number>>({});
const actionLoading = ref(false);
const confirmAction = ref<{ title: string; message: string; label: string; run: () => void } | null>(null);

const askReject = (row: TransferRow) => {
    confirmAction.value = {
        title: 'Tolak Transfer',
        message: `Transfer ${row.order_number} akan ditolak dan tidak dapat dikirim. Lanjutkan?`,
        label: 'Tolak Transfer',
        run: () => reject(row, closeDetail),
    };
};

const askCancel = (row: TransferRow) => {
    confirmAction.value = {
        title: 'Batalkan Transfer',
        message: `Transfer ${row.order_number} akan dibatalkan. Stok tidak berpindah. Lanjutkan?`,
        label: 'Batalkan Transfer',
        run: () => cancel(row, closeDetail),
    };
};

const openDetail = async (row: TransferRow) => {
    detailOpen.value = true;
    detailLoading.value = true;
    detailError.value = '';
    actionError.value = '';
    detail.value = null;

    try {
        const { data } = await axios.get(props.urls.show.replace('__ID__', row.id));
        detail.value = data;
        receiveQty.value = Object.fromEntries(
            (data.details as TransferDetail[]).map((item) => [
                item.id,
                Math.max(0, Number(item.approved_qty || item.requested_qty || 0) - Number(item.received_qty || 0)),
            ]),
        );
    } catch {
        detailError.value = 'Detail transfer tidak dapat dimuat.';
    } finally {
        detailLoading.value = false;
    }
};

const closeDetail = () => {
    detailOpen.value = false;
    detail.value = null;
    receiveOpen.value = false;
    detailError.value = '';
    actionError.value = '';
};

const receiveRemaining = computed(() => {
    if (!detail.value) return 0;

    return detail.value.details.reduce((total, item) => {
        const remaining = Math.max(0, Number(item.approved_qty || item.requested_qty || 0) - Number(item.received_qty || 0));

        return total + Math.min(Number(receiveQty.value[item.id] ?? remaining), remaining);
    }, 0);
});

const runAction = (url: string, payload: Record<string, any> = {}, after?: () => void) => {
    actionLoading.value = true;
    actionError.value = '';
    router.patch(url, payload, {
        preserveScroll: true,
        onSuccess: () => after?.(),
        onError: (errors) => {
            actionError.value = Object.values(errors)[0] ?? 'Aksi transfer gagal diproses.';
        },
        onFinish: () => {
            actionLoading.value = false;
        },
    });
};

const approve = (row: TransferRow, after?: () => void) => runAction(props.urls.approve.replace('__ID__', row.id), {}, after);
const ship = (row: TransferRow, after?: () => void) => runAction(props.urls.ship.replace('__ID__', row.id), {}, after);
const reject = (row: TransferRow, after?: () => void) => runAction(props.urls.reject.replace('__ID__', row.id), {}, after);
const cancel = (row: TransferRow, after?: () => void) => runAction(props.urls.cancel.replace('__ID__', row.id), {}, after);

const submitReceive = () => {
    if (!detail.value) return;

    actionLoading.value = true;
    actionError.value = '';
    router.patch(props.urls.receive.replace('__ID__', detail.value.order.id), {
        received_qty: receiveQty.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            receiveOpen.value = false;
            detailOpen.value = false;
        },
        onError: (errors) => {
            actionError.value = Object.values(errors)[0] ?? 'Penerimaan transfer gagal diproses.';
        },
        onFinish: () => {
            actionLoading.value = false;
        },
    });
};

const modalOpen = computed(() => createOpen.value || detailOpen.value || confirmAction.value !== null);

watch(modalOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        if (confirmAction.value) {
            confirmAction.value = null;
            return;
        }

        if (receiveOpen.value) {
            receiveOpen.value = false;
            return;
        }

        if (detailOpen.value) {
            closeDetail();
            return;
        }

        if (createOpen.value) {
            closeCreate();
        }
    }
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';

    if (stockTimer) clearTimeout(stockTimer);
});
</script>

<template>
    <Head title="Transfer Toko ke Toko" />

    <div class="transfer-page">
        <section class="transfer-header">
            <div>
                <span class="transfer-eyebrow">Operations / Store</span>
                <h1>Transfer Toko ke Toko</h1>
                <p>Pindahkan stok gudang toko antar cabang. Transfer hanya dapat dibuat setelah stok gudang habis, dan barang wajib discan toko asal sebelum dikirim.</p>
            </div>
            <div class="transfer-header-actions">
                <Link :href="props.urls.stock_page" class="transfer-button transfer-button-light">
                    <i class="mdi mdi-clipboard-list-outline me-1"></i>Stok Toko
                </Link>
                <Link :href="props.urls.orders_page" class="transfer-button transfer-button-light">
                    <i class="mdi mdi-clipboard-check-outline me-1"></i>Pesanan Toko
                </Link>
                <button class="transfer-button transfer-button-primary" type="button" @click="openCreate">
                    <i class="mdi mdi-plus me-1"></i>Buat Transfer
                </button>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="transfer-summary">
                    <span class="transfer-summary-icon"><i class="mdi mdi-swap-horizontal-bold"></i></span>
                    <div><span>Total Transfer</span><strong>{{ formatNumber(props.summary.total_transfers) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="transfer-summary transfer-summary-warning">
                    <span class="transfer-summary-icon"><i class="mdi mdi-clock-outline"></i></span>
                    <div><span>Menunggu Approval</span><strong>{{ formatNumber(props.summary.waiting_approval) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="transfer-summary">
                    <span class="transfer-summary-icon"><i class="mdi mdi-check-decagram-outline"></i></span>
                    <div><span>Siap Kirim</span><strong>{{ formatNumber(props.summary.ready_to_ship) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="transfer-summary transfer-summary-info">
                    <span class="transfer-summary-icon"><i class="mdi mdi-truck-fast-outline"></i></span>
                    <div><span>Dalam Perjalanan</span><strong>{{ formatNumber(props.summary.in_transit + props.summary.partial_received) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="transfer-summary transfer-summary-success">
                    <span class="transfer-summary-icon"><i class="mdi mdi-check-circle-outline"></i></span>
                    <div><span>Selesai</span><strong>{{ formatNumber(props.summary.completed) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="transfer-summary transfer-summary-warning">
                    <span class="transfer-summary-icon"><i class="mdi mdi-qrcode-scan"></i></span>
                    <div><span>Menunggu Scan</span><strong>{{ formatNumber(props.summary.waiting_scan) }}</strong></div>
                </div>
            </div>
        </div>

        <div class="card transfer-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Cari transfer</label>
                        <input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nomor transfer, toko, atau produk..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Toko Asal</label>
                        <SearchableSelect v-model="filters.source_store_id" :options="storeOptions" placeholder="Semua toko" search-placeholder="Cari toko..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Toko Tujuan</label>
                        <SearchableSelect v-model="filters.destination_store_id" :options="storeOptions" placeholder="Semua toko" search-placeholder="Cari toko..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label">Status</label>
                        <select v-model="filters.status" class="form-select form-select-sm">
                            <option value="">Semua status</option>
                            <option v-for="option in props.statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-1">
                        <label class="form-label">Dari</label>
                        <input v-model="filters.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-4 col-lg-1">
                        <label class="form-label">Sampai</label>
                        <input v-model="filters.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-12 col-lg-12 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card transfer-card">
            <div class="transfer-card-header">
                <div>
                    <h2>Daftar Transfer</h2>
                    <p>Semua perpindahan stok antar toko. Qty outstanding dihitung dari qty disetujui yang belum diterima.</p>
                </div>
                <span class="transfer-card-meta">{{ formatNumber(props.rows.total) }} transfer · {{ formatNumber(props.summary.outstanding_qty) }} pcs sisa</span>
            </div>

            <div v-if="props.rows.data.length === 0" class="transfer-empty">
                <i class="mdi mdi-swap-horizontal-bold"></i>
                <strong>Belum ada transfer toko</strong>
                <span>Transfer antar toko hanya dapat dibuat setelah stok gudang habis. Barang dari stok gudang toko wajib discan sebelum dikirim.</span>
                <button class="btn btn-sm btn-primary" type="button" @click="openCreate"><i class="mdi mdi-plus me-1"></i>Buat Transfer</button>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 transfer-table">
                    <thead>
                        <tr>
                            <th>Nomor Transfer</th>
                            <th>Rute</th>
                            <th class="text-end">Produk / Qty</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.rows.data" :key="row.id">
                            <td>
                                <div class="transfer-main">
                                    <strong>{{ row.order_number }}</strong>
                                    <small>Dibuat oleh {{ row.created_by_name || '-' }}</small>
                                    <small v-if="row.shipping_number" class="transfer-subvalue">Ref: {{ row.shipping_number }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="transfer-route">
                                    <span class="transfer-route-store">{{ row.source_store_name || 'Toko asal belum tercatat' }}</span>
                                    <i class="mdi mdi-arrow-right transfer-route-arrow"></i>
                                    <span class="transfer-route-store">{{ row.destination_store_name }}</span>
                                </div>
                                <small class="transfer-subvalue">{{ row.total_products }} produk</small>
                            </td>
                            <td class="text-end">
                                <strong>{{ formatNumber(row.approved_qty || row.requested_qty) }} pcs</strong>
                                <small class="transfer-subvalue">{{ formatNumber(row.received_qty) }} diterima · {{ formatNumber(row.outstanding_qty) }} sisa</small>
                            </td>
                            <td>
                                <span class="badge" :class="statusClass(row.status)">{{ row.status_label }}</span>
                                <div class="transfer-progress mt-1">
                                    <span :style="{ width: `${row.progress_percent}%` }"></span>
                                </div>
                                <small class="transfer-subvalue">{{ row.progress_percent }}% diterima</small>
                                <small v-if="row.status === 'approved' || row.status === 'shipped' || row.status === 'partial_received'" class="transfer-subvalue">
                                    Scan toko {{ formatNumber(row.scanned_qty) }}/{{ formatNumber(row.approved_qty || row.requested_qty) }} pcs
                                </small>
                            </td>
                            <td>
                                <strong>{{ formatDate(row.requested_at || row.created_at) }}</strong>
                                <small class="transfer-subvalue">{{ row.shipment_date ? `Kirim ${formatDate(row.shipment_date)}` : 'Belum dikirim' }}</small>
                            </td>
                            <td class="text-end">
                                <div class="transfer-actions">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="openDetail(row)">
                                        <i class="mdi mdi-eye-outline"></i>Detail
                                    </button>
                                    <button v-if="row.can_approve" class="btn btn-sm btn-outline-primary" type="button" :disabled="actionLoading" @click="approve(row)">Setujui</button>
                                    <button v-if="row.can_ship" class="btn btn-sm btn-primary" type="button" :disabled="actionLoading" @click="ship(row)">Kirim</button>
                                    <span v-else-if="row.status === 'approved'" class="transfer-scan-wait">Menunggu scan toko asal</span>
                                    <button v-if="row.can_receive" class="btn btn-sm btn-success" type="button" :disabled="actionLoading" @click="openDetail(row)">Terima</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="transfer-footer">
                <small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} transfer</small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="createOpen" class="transfer-backdrop" @click.self="closeCreate">
            <div class="transfer-modal transfer-modal-lg" role="dialog" aria-modal="true" aria-labelledby="transfer-create-title">
                <div class="transfer-modal-header">
                    <div>
                        <h5 id="transfer-create-title" class="mb-0">Buat Transfer Toko</h5>
                        <small>Pilih toko asal, toko tujuan, lalu produk yang akan dipindahkan.</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup form transfer" @click="closeCreate"><i class="mdi mdi-close"></i></button>
                </div>

                <form @submit.prevent="submitCreate">
                    <div class="transfer-modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Toko Asal <span class="text-danger">*</span></label>
                                <SearchableSelect v-model="form.source_store_id" :options="storeOptions" placeholder="Pilih toko asal" search-placeholder="Cari toko..." :disabled="form.processing" />
                                <div v-if="form.errors.source_store_id" class="invalid-feedback d-block">{{ form.errors.source_store_id }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Toko Tujuan <span class="text-danger">*</span></label>
                                <SearchableSelect v-model="form.destination_store_id" :options="storeOptions" placeholder="Pilih toko tujuan" search-placeholder="Cari toko..." :disabled="form.processing" />
                                <div v-if="form.errors.destination_store_id" class="invalid-feedback d-block">{{ form.errors.destination_store_id }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan Transfer</label>
                                <textarea v-model="form.note" class="form-control form-control-sm" rows="2" placeholder="Opsional: alasan atau instruksi pengiriman..." :disabled="form.processing"></textarea>
                                <div v-if="form.errors.note" class="invalid-feedback d-block">{{ form.errors.note }}</div>
                            </div>
                        </div>

                        <hr class="transfer-divider" />

                        <div class="transfer-section-heading">
                            <div>
                                <h6>Produk Transfer</h6>
                                <p>Hanya stok gudang toko (inventory), bukan display, yang dapat ditransfer. Barang wajib memiliki RFID dan harus discan di toko asal sebelum dikirim.</p>
                            </div>
                            <span class="transfer-pill">{{ form.items.length }} produk · {{ formatNumber(totalQty) }} pcs</span>
                        </div>

                        <div class="transfer-hint transfer-hint-info mb-3" role="status">
                            <i class="mdi mdi-information-outline me-1"></i>
                            {{ warehouseRule || 'Transfer antar toko hanya dapat dibuat jika stok gudang untuk produk sudah habis.' }}
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-lg-5">
                                <label class="form-label">Cari Produk</label>
                                <input v-model="stockSearch" class="form-control form-control-sm" type="search" placeholder="Nama atau kode produk..." :disabled="!form.source_store_id || stockLoading" />

                                <div v-if="!form.source_store_id" class="transfer-hint">Pilih toko asal untuk melihat stok.</div>
                                <div v-else-if="stockLoading" class="transfer-hint"><span class="spinner-border spinner-border-sm me-2"></span>Memuat stok...</div>
                                <div v-else-if="stockError" class="transfer-hint transfer-hint-danger" role="alert">{{ stockError }}</div>
                                <div v-else-if="stockProducts.length === 0" class="transfer-hint">Tidak ada produk dengan stok tersedia.</div>
                                <div v-else class="transfer-stock-list">
                                    <button
                                        v-for="product in stockProducts"
                                        :key="product.product_id"
                                        class="transfer-stock-item"
                                        type="button"
                                        :disabled="product.available_qty <= 0 || !product.product_price_id || !product.can_transfer"
                                        @click="addProduct(product)"
                                    >
                                        <div>
                                            <strong>{{ product.product_name }}</strong>
                                            <small>{{ product.product_code }} · {{ product.category_name }}</small>
                                            <small v-if="product.warehouse_available_qty > 0" class="transfer-stock-blocked">
                                                Stok gudang masih {{ formatNumber(product.warehouse_available_qty) }} pcs
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="transfer-stock-qty">{{ formatNumber(product.available_qty) }} pcs</span>
                                            <small>{{ product.product_price_id ? formatCurrency(product.product_price) : 'Harga belum diatur' }}</small>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 col-lg-7">
                                <div class="table-responsive transfer-selected-wrap">
                                    <table class="table table-sm mb-0 transfer-selected-table">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th class="text-end" style="width: 110px;">Qty</th>
                                                <th class="text-end" style="width: 130px;">Subtotal</th>
                                                <th class="text-end" style="width: 48px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in form.items" :key="item.product_id">
                                                <td>
                                                    <strong>{{ productFor(item.product_id)?.product_name ?? item.product_id }}</strong>
                                                    <small>{{ productFor(item.product_id)?.product_code }} · tersedia {{ formatNumber(productFor(item.product_id)?.available_qty ?? 0) }} pcs</small>
                                                </td>
                                                <td class="text-end">
                                                    <input v-model.number="item.qty" class="form-control form-control-sm text-end" type="number" min="1" :max="productFor(item.product_id)?.available_qty ?? 1" :disabled="form.processing" />
                                                </td>
                                                <td class="text-end">{{ formatCurrency(Number(item.qty || 0) * Number(productFor(item.product_id)?.product_price || 0)) }}</td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-danger" type="button" aria-label="Hapus produk" @click="removeItem(item.product_id)"><i class="mdi mdi-trash-can-outline"></i></button>
                                                </td>
                                            </tr>
                                            <tr v-if="form.items.length === 0">
                                                <td colspan="4" class="text-center text-muted py-4">Belum ada produk dipilih. Klik produk di sebelah kiri untuk menambahkan.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="transfer-total">
                                    <span>Total nilai transfer</span>
                                    <strong>{{ formatCurrency(totalValue) }}</strong>
                                </div>
                                <div v-if="form.errors.items" class="invalid-feedback d-block">{{ form.errors.items }}</div>
                                <div v-for="(error, key) in form.errors" :key="key">
                                    <div v-if="String(key).startsWith('items.')" class="invalid-feedback d-block">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="transfer-modal-footer">
                        <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="form.processing" @click="closeCreate">Batal</button>
                        <button class="btn btn-sm btn-primary" type="submit" :disabled="form.processing || form.items.length === 0">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            Simpan Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="detailOpen" class="transfer-backdrop" @click.self="closeDetail">
            <div class="transfer-modal transfer-modal-lg" role="dialog" aria-modal="true" aria-labelledby="transfer-detail-title">
                <div class="transfer-modal-header">
                    <div>
                        <h5 id="transfer-detail-title" class="mb-0">Detail Transfer</h5>
                        <small v-if="detail">{{ detail.order.order_number }} · {{ detail.order.source_store_name || 'Toko asal' }} → {{ detail.order.destination_store_name }}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup detail transfer" @click="closeDetail"><i class="mdi mdi-close"></i></button>
                </div>

                <div class="transfer-modal-body">
                    <div v-if="actionError" class="transfer-hint transfer-hint-danger mb-2" role="alert">{{ actionError }}</div>
                    <div v-if="detailLoading" class="transfer-hint"><span class="spinner-border spinner-border-sm me-2"></span>Memuat detail...</div>
                    <div v-else-if="detailError" class="transfer-hint transfer-hint-danger" role="alert">{{ detailError }}</div>
                    <template v-else-if="detail">
                        <div class="transfer-detail-grid">
                            <div><span>Status</span><strong><span class="badge" :class="statusClass(detail.order.status)">{{ detail.order.status_label }}</span></strong></div>
                            <div><span>Tanggal</span><strong>{{ formatDate(detail.order.requested_at || detail.order.created_at, true) }}</strong></div>
                            <div><span>Disetujui</span><strong>{{ formatDate(detail.order.approved_at, true) }}</strong></div>
                            <div><span>Dikirim</span><strong>{{ formatDate(detail.order.shipment_date, true) }}</strong></div>
                            <div><span>Dibuat oleh</span><strong>{{ detail.order.created_by_name || '-' }}</strong></div>
                            <div><span>Disetujui oleh</span><strong>{{ detail.order.approved_by_name || '-' }}</strong></div>
                        </div>

                        <div class="transfer-progress transfer-progress-lg mt-3">
                            <span :style="{ width: `${detail.order.progress_percent}%` }"></span>
                        </div>
                        <small class="text-muted">{{ formatNumber(detail.order.received_qty) }} dari {{ formatNumber(detail.order.approved_qty || detail.order.requested_qty) }} pcs diterima ({{ detail.order.progress_percent }}%)</small>
                        <div class="transfer-progress transfer-progress-lg transfer-progress-scan mt-2">
                            <span :style="{ width: `${detail.order.scan_progress_percent}%` }"></span>
                        </div>
                        <small class="text-muted">
                            Scan toko asal {{ formatNumber(detail.order.scanned_qty) }} dari {{ formatNumber(detail.order.approved_qty || detail.order.requested_qty) }} pcs
                            ({{ detail.order.scan_progress_percent }}%)
                        </small>

                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle mb-0 transfer-detail-table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-end">Harga</th>
                                        <th class="text-end">Diminta</th>
                                        <th class="text-end">Disetujui</th>
                                        <th class="text-end">Discan</th>
                                        <th class="text-end">Diterima</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in detail.details" :key="item.id">
                                        <td>
                                            <strong>{{ item.product_name }}</strong>
                                            <small>{{ item.product_code }}<span v-if="item.unit_name"> · {{ item.unit_name }}</span></small>
                                            <small v-if="item.note" class="transfer-note">{{ item.note }}</small>
                                        </td>
                                        <td class="text-end">{{ formatCurrency(item.product_price) }}</td>
                                        <td class="text-end">{{ formatNumber(item.requested_qty) }}</td>
                                        <td class="text-end">{{ formatNumber(item.approved_qty) }}</td>
                                        <td class="text-end">{{ formatNumber(item.scanned_qty) }}</td>
                                        <td class="text-end">{{ formatNumber(item.received_qty) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <details v-if="detail.items.length > 0" class="transfer-items">
                            <summary>Barang yang dialokasikan ({{ detail.items.length }} item)</summary>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Produk</th><th>Status</th><th>RFID</th></tr></thead>
                                    <tbody>
                                        <tr v-for="item in detail.items" :key="item.id">
                                            <td>{{ item.product_name }} <small class="text-muted">{{ item.product_code }}</small></td>
                                            <td><span class="badge bg-label-secondary">{{ item.item_status }}</span></td>
                                            <td>{{ item.rfid_serial || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </details>

                        <div v-if="receiveOpen" class="transfer-receive-box">
                            <h6>Konfirmasi Penerimaan</h6>
                            <p class="text-muted small">Isi qty yang diterima. Qty yang belum diterima tetap tercatat dalam perjalanan.</p>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Produk</th><th class="text-end">Sisa</th><th class="text-end" style="width: 120px;">Diterima</th></tr></thead>
                                    <tbody>
                                        <tr v-for="item in detail.details" :key="`receive-${item.id}`">
                                            <td>{{ item.product_name }}</td>
                                            <td class="text-end">{{ formatNumber(Math.max(0, Number(item.approved_qty || item.requested_qty || 0) - Number(item.received_qty || 0))) }}</td>
                                            <td class="text-end">
                                                <input v-model.number="receiveQty[item.id]" class="form-control form-control-sm text-end" type="number" min="0" :max="Math.max(0, Number(item.approved_qty || item.requested_qty || 0) - Number(item.received_qty || 0))" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="transfer-modal-footer">
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="closeDetail">Tutup</button>
                    <template v-if="detail">
                        <button v-if="detail.order.can_reject" class="btn btn-sm btn-outline-danger" type="button" :disabled="actionLoading" @click="askReject(detail.order)">Tolak</button>
                        <button v-if="detail.order.can_cancel" class="btn btn-sm btn-outline-danger" type="button" :disabled="actionLoading" @click="askCancel(detail.order)">Batalkan</button>
                        <button v-if="detail.order.can_approve" class="btn btn-sm btn-primary" type="button" :disabled="actionLoading" @click="approve(detail.order, closeDetail)">Setujui</button>
                        <button v-if="detail.order.can_ship" class="btn btn-sm btn-primary" type="button" :disabled="actionLoading" @click="ship(detail.order, closeDetail)">Kirim dari Toko Asal</button>
                        <span v-else-if="detail.order.status === 'approved'" class="transfer-scan-wait">Menunggu scan barang dari toko asal sebelum dikirim.</span>
                        <button v-if="detail.order.can_receive && !receiveOpen" class="btn btn-sm btn-success" type="button" :disabled="actionLoading" @click="receiveOpen = true">Terima Barang</button>
                        <button v-if="detail.order.can_receive && receiveOpen" class="btn btn-sm btn-success" type="button" :disabled="actionLoading || receiveRemaining <= 0" @click="submitReceive">
                            <span v-if="actionLoading" class="spinner-border spinner-border-sm me-2"></span>
                            Konfirmasi Terima {{ formatNumber(receiveRemaining) }} pcs
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div v-if="confirmAction" class="transfer-backdrop" @click.self="confirmAction = null">
            <div class="transfer-modal transfer-modal-sm" role="alertdialog" aria-modal="true" aria-labelledby="transfer-confirm-title">
                <div class="transfer-modal-header">
                    <div>
                        <h5 id="transfer-confirm-title" class="mb-0">{{ confirmAction.title }}</h5>
                        <small>Konfirmasi tindakan</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup konfirmasi" @click="confirmAction = null"><i class="mdi mdi-close"></i></button>
                </div>
                <div class="transfer-modal-body">
                    <p class="mb-0">{{ confirmAction.message }}</p>
                </div>
                <div class="transfer-modal-footer">
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="confirmAction = null">Batal</button>
                    <button class="btn btn-sm btn-danger" type="button" :disabled="actionLoading" @click="confirmAction.run(); confirmAction = null">{{ confirmAction.label }}</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.transfer-page { color: var(--app-ink); }
.transfer-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; }
.transfer-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.transfer-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.transfer-header p { max-width: 760px; margin: 0; color: var(--app-hero-muted) !important; }
.transfer-header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.transfer-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border: 0; border-radius: 0.55rem; font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.transfer-button-light { background: var(--app-surface); color: var(--app-ink); }
.transfer-button-primary { background: var(--app-accent-strong); color: var(--app-on-accent); }
.transfer-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.transfer-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.transfer-summary span:not(.transfer-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.transfer-summary strong { display: block; margin-top: 0.15rem; font-size: 1.35rem; }
.transfer-summary-warning .transfer-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.transfer-summary-info .transfer-summary-icon { background: var(--app-accent-soft); color: var(--app-accent-ink); }
.transfer-summary-success .transfer-summary-icon { background: var(--app-success-bg, var(--app-accent-soft)); color: var(--app-success-ink, var(--app-accent-ink)); }
.transfer-summary-danger .transfer-summary-icon { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.transfer-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.transfer-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.transfer-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.transfer-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.transfer-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.transfer-table th { padding: 0.6rem 0.9rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.transfer-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); color: var(--app-ink); }
.transfer-main strong, .transfer-main small, .transfer-subvalue { display: block; }
.transfer-main strong { font-size: 0.86rem; }
.transfer-main small, .transfer-subvalue { color: var(--app-muted); font-size: 0.73rem; }
.transfer-route { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; font-size: 0.82rem; }
.transfer-route-store { font-weight: 700; }
.transfer-route-arrow { color: var(--app-accent-ink); }
.transfer-progress { height: 5px; overflow: hidden; border-radius: 999px; background: var(--app-surface-2); }
.transfer-progress span { display: block; height: 100%; border-radius: inherit; background: var(--app-accent-strong); transition: width 180ms ease; }
.transfer-progress-lg { height: 8px; }
.transfer-progress-scan span { background: var(--app-warning-ink); }
.transfer-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.35rem; }
.transfer-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.transfer-empty { display: grid; place-items: center; gap: 0.45rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.transfer-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.transfer-empty strong { color: var(--app-ink); }
.transfer-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: flex-start; justify-content: center; padding: min(6vh, 3rem) 1rem 1rem; overflow-y: auto; background: rgba(15, 23, 42, 0.62); }
.transfer-modal { width: min(680px, 100%); overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.28); color: var(--app-ink); }
.transfer-modal-lg { width: min(980px, 100%); }
.transfer-modal-sm { width: min(520px, 100%); }
.transfer-modal-header, .transfer-modal-footer { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; }
.transfer-modal-header { border-bottom: 1px solid var(--app-border); }
.transfer-modal-footer { align-items: center; justify-content: flex-end; border-top: 1px solid var(--app-border); background: var(--app-surface-2); }
.transfer-modal-body { padding: 1rem 1.1rem; }
.transfer-divider { margin: 1.1rem 0; border-color: var(--app-border); }
.transfer-section-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 0.8rem; }
.transfer-section-heading h6 { margin: 0 0 0.15rem; font-size: 0.95rem; }
.transfer-section-heading p { margin: 0; color: var(--app-muted); font-size: 0.78rem; }
.transfer-pill { padding: 0.25rem 0.55rem; border-radius: 999px; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 0.72rem; font-weight: 700; white-space: nowrap; }
.transfer-hint { display: flex; align-items: center; gap: 0.35rem; margin-top: 0.65rem; padding: 0.7rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.8rem; }
.transfer-hint-danger { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.transfer-hint-info { background: var(--app-accent-soft); color: var(--app-accent-ink); }
.transfer-stock-list { max-height: 280px; margin-top: 0.65rem; overflow-y: auto; border: 1px solid var(--app-border); border-radius: 0.6rem; }
.transfer-stock-item { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.65rem 0.75rem; border: 0; border-bottom: 1px solid var(--app-border); background: transparent; color: var(--app-ink); text-align: left; }
.transfer-stock-item:last-child { border-bottom: 0; }
.transfer-stock-item:hover, .transfer-stock-item:focus-visible { background: var(--app-surface-2); outline: 0; }
.transfer-stock-item:disabled { opacity: 0.5; }
.transfer-stock-item strong, .transfer-stock-item small { display: block; }
.transfer-stock-item strong { font-size: 0.82rem; }
.transfer-stock-item small { color: var(--app-muted); font-size: 0.72rem; }
.transfer-stock-qty { display: block; color: var(--app-accent-ink); font-size: 0.8rem; font-weight: 700; }
.transfer-stock-blocked { margin-top: 0.15rem; color: var(--app-warning-ink) !important; font-weight: 700; }
.transfer-scan-wait { color: var(--app-warning-ink); font-size: 0.72rem; font-weight: 700; }
.transfer-selected-wrap { border: 1px solid var(--app-border); border-radius: 0.6rem; }
.transfer-selected-table th { padding: 0.45rem 0.55rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.64rem; text-transform: uppercase; }
.transfer-selected-table td { padding: 0.55rem; font-size: 0.78rem; border-color: var(--app-border); }
.transfer-selected-table td strong, .transfer-selected-table td small { display: block; }
.transfer-selected-table td small { color: var(--app-muted); font-size: 0.7rem; }
.transfer-total { display: flex; align-items: center; justify-content: space-between; margin-top: 0.65rem; padding: 0.7rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface-2); }
.transfer-total span { color: var(--app-muted); font-size: 0.8rem; }
.transfer-total strong { font-size: 1rem; }
.transfer-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; }
.transfer-detail-grid span { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.transfer-detail-grid strong { display: block; margin-top: 0.2rem; font-size: 0.82rem; }
.transfer-detail-table th { padding: 0.45rem 0.55rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.64rem; text-transform: uppercase; }
.transfer-detail-table td { padding: 0.55rem; font-size: 0.78rem; border-color: var(--app-border); }
.transfer-detail-table td strong, .transfer-detail-table td small { display: block; }
.transfer-detail-table td small { color: var(--app-muted); font-size: 0.7rem; }
.transfer-note { margin-top: 0.15rem; font-style: italic; }
.transfer-items { margin-top: 0.9rem; border: 1px solid var(--app-border); border-radius: 0.6rem; }
.transfer-items summary { padding: 0.65rem 0.8rem; cursor: pointer; font-size: 0.82rem; font-weight: 700; }
.transfer-items > .table-responsive { border-top: 1px solid var(--app-border); }
.transfer-receive-box { margin-top: 1rem; padding: 0.85rem; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface-2); }
.transfer-receive-box h6 { margin: 0 0 0.2rem; }
@media (max-width: 991.98px) {
    .transfer-header { align-items: flex-start; flex-direction: column; }
    .transfer-card-header { align-items: flex-start; flex-direction: column; }
    .transfer-section-heading { align-items: flex-start; flex-direction: column; }
}
</style>
