<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';

defineOptions({ layout: AppLayout });

interface StoreOption {
    id: string;
    name: string;
    code: string | null;
}

interface CategoryOption {
    value: string;
    label: string;
}

interface Row {
    id: string;
    category: string | null;
    created_at: string | null;
    store_name: string;
    store_code: string | null;
    product_name: string;
    product_code: string | null;
    product_category: string | null;
    condition_name: string | null;
}

interface RequestItem {
    store_item_id?: string;
    cart_detail_id?: string;
    item_id: string;
    product_id: string | null;
    product_name: string;
    product_code: string;
    rfid_value: string;
    price: number;
    discount: number;
    net_price: number;
    condition_name?: string | null;
}

interface RequestReference {
    payment_id: string;
    transaction_number: string;
    transaction_date: string | null;
    customer_name: string | null;
    cashier_name: string | null;
    total_amount: number;
}

interface RequestHistory {
    action: string;
    at: string;
    by: string | null;
    by_name: string | null;
    note: string | null;
}

interface RequestRow {
    id: string;
    request_number: string;
    store_id: string;
    store_name: string;
    store_code: string | null;
    type: 'sample' | 'gimmick' | string;
    type_label: string;
    status: 'pending' | 'approved' | 'rejected' | string;
    status_label: string;
    submitted_at: string | null;
    created_by_name: string | null;
    reviewed_by_name: string | null;
    reviewed_at: string | null;
    notes: string | null;
    review_note: string | null;
    rejection_reason: string | null;
    reference: RequestReference | null;
    items: RequestItem[];
    item_count: number;
    total_value: number;
    history: RequestHistory[];
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<Row>;
    summary: {
        total_items: number;
        total_sample: number;
        total_gimmick: number;
        total_inventory: number;
        total_display: number;
    };
    requests: Paginator<RequestRow>;
    requestSummary: {
        total: number;
        pending: number;
        approved: number;
        rejected: number;
    };
    stores: StoreOption[];
    categories: CategoryOption[];
    requestStatusOptions: Array<{ value: string; label: string }>;
    requestTypeOptions: Array<{ value: string; label: string }>;
    filters: {
        q: string;
        store_id: string;
        category: string;
        per_page: number;
    };
    requestFilters: {
        q: string;
        store_id: string | null;
        type: string;
        status: string;
        per_page: number;
    };
    urls: {
        index: string;
        report: string;
        mark: string;
        request_approve: string;
        request_reject: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const activeTab = ref<'requests' | 'items'>('requests');
const selection = ref<string[]>([]);
const processing = ref(false);

const itemFilters = reactive({
    q: props.filters.q ?? '',
    store_id: props.filters.store_id ?? '',
    category: props.filters.category ?? '',
    per_page: props.filters.per_page ?? 25,
});

const requestFilters = reactive({
    q: props.requestFilters.q ?? '',
    store_id: props.requestFilters.store_id ?? '',
    type: props.requestFilters.type ?? '',
    status: props.requestFilters.status ?? 'pending',
    per_page: props.requestFilters.per_page ?? 10,
});

const selectedRequest = ref<RequestRow | null>(null);
const requestModalOpen = ref(false);
const showReject = ref(false);
const reviewNote = ref('');
const rejectReason = ref('');
const actionProcessing = ref(false);
const actionError = ref('');
const requestTrigger = ref<HTMLElement | null>(null);
const closeRequestButton = ref<HTMLButtonElement | null>(null);
const rejectReasonInput = ref<HTMLTextAreaElement | null>(null);

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.code ? `${store.name} (${store.code})` : store.name,
})));

const summaryCards = computed(() => [
    { label: 'Sample', value: props.summary.total_sample, tone: 'is-info' },
    { label: 'Gimmick', value: props.summary.total_gimmick, tone: 'is-warning' },
    { label: 'Inventory', value: props.summary.total_inventory, tone: '' },
    { label: 'Display', value: props.summary.total_display, tone: '' },
]);

const requestSummaryCards = computed(() => [
    { label: 'Menunggu', value: props.requestSummary.pending, tone: 'is-warning', icon: 'mdi-clock-outline' },
    { label: 'Disetujui', value: props.requestSummary.approved, tone: 'is-success', icon: 'mdi-check-circle-outline' },
    { label: 'Ditolak', value: props.requestSummary.rejected, tone: 'is-danger', icon: 'mdi-close-circle-outline' },
    { label: 'Total', value: props.requestSummary.total, tone: 'is-info', icon: 'mdi-file-document-multiple-outline' },
]);

const allIds = computed(() => props.rows.data.map((row) => row.id));
const selectedCount = computed(() => selection.value.length);
const pendingCount = computed(() => props.requestSummary.pending);
const requestHasFilters = computed(() => (
    requestFilters.q !== ''
    || requestFilters.store_id !== ''
    || requestFilters.type !== ''
    || requestFilters.status !== 'pending'
));

const buildQuery = () => ({
    q: itemFilters.q || undefined,
    store_id: itemFilters.store_id || undefined,
    category: itemFilters.category || undefined,
    per_page: itemFilters.per_page || undefined,
    rq: requestFilters.q || undefined,
    rstore_id: requestFilters.store_id || undefined,
    rtype: requestFilters.type || undefined,
    rstatus: requestFilters.status || 'all',
    rper_page: requestFilters.per_page || undefined,
});

const navigateWithFilters = () => {
    selection.value = [];
    router.get(props.urls.index, buildQuery(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const toggle = (id: string) => {
    selection.value = selection.value.includes(id)
        ? selection.value.filter((value) => value !== id)
        : [...selection.value, id];
};

const toggleAll = () => {
    selection.value = selection.value.length === allIds.value.length ? [] : [...allIds.value];
};

const applyFilters = () => navigateWithFilters();

const resetFilters = () => {
    itemFilters.q = '';
    itemFilters.store_id = '';
    itemFilters.category = '';
    itemFilters.per_page = 25;
    navigateWithFilters();
};

const applyRequestFilters = () => navigateWithFilters();

const resetRequestFilters = () => {
    requestFilters.q = '';
    requestFilters.store_id = '';
    requestFilters.type = '';
    requestFilters.status = 'pending';
    requestFilters.per_page = 10;
    navigateWithFilters();
};

const mark = (category: 'sample' | 'gimmick' | 'inventory' | 'display') => {
    if (selection.value.length === 0 || processing.value) return;

    processing.value = true;
    router.post(props.urls.mark, {
        ids: selection.value,
        category,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selection.value = [];
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const switchTab = (tab: 'requests' | 'items') => {
    activeTab.value = tab;
};

const openRequest = (row: RequestRow, event?: Event) => {
    requestTrigger.value = (event?.currentTarget as HTMLElement) ?? null;
    selectedRequest.value = row;
    showReject.value = false;
    reviewNote.value = '';
    rejectReason.value = '';
    actionError.value = '';
    requestModalOpen.value = true;

    nextTick(() => closeRequestButton.value?.focus());
};

const closeRequest = () => {
    if (actionProcessing.value) return;

    requestModalOpen.value = false;
    selectedRequest.value = null;
    showReject.value = false;
    actionError.value = '';

    nextTick(() => requestTrigger.value?.focus());
};

const startReject = () => {
    showReject.value = true;
    actionError.value = '';
    nextTick(() => rejectReasonInput.value?.focus());
};

const cancelReject = () => {
    showReject.value = false;
    rejectReason.value = '';
    actionError.value = '';
};

const approveRequest = () => {
    if (!selectedRequest.value || actionProcessing.value) return;

    actionProcessing.value = true;
    actionError.value = '';

    router.post(props.urls.request_approve.replace('__ID__', selectedRequest.value.id), {
        note: reviewNote.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            actionProcessing.value = false;
            closeRequest();
        },
        onError: (errors) => {
            actionError.value = Object.values(errors)[0] ?? 'Pengajuan gagal disetujui.';
        },
        onFinish: () => {
            actionProcessing.value = false;
        },
    });
};

const rejectRequest = () => {
    if (!selectedRequest.value || actionProcessing.value) return;

    if (rejectReason.value.trim() === '') {
        actionError.value = 'Alasan penolakan wajib diisi.';
        rejectReasonInput.value?.focus();
        return;
    }

    actionProcessing.value = true;
    actionError.value = '';

    router.post(props.urls.request_reject.replace('__ID__', selectedRequest.value.id), {
        reason: rejectReason.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            actionProcessing.value = false;
            closeRequest();
        },
        onError: (errors) => {
            actionError.value = Object.values(errors)[0] ?? 'Pengajuan gagal ditolak.';
        },
        onFinish: () => {
            actionProcessing.value = false;
        },
    });
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && requestModalOpen.value) {
        closeRequest();
    }
};

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
});

const categoryClass = (category: string | null) => {
    if (category === 'sample') return 'bg-label-info';
    if (category === 'gimmick') return 'bg-label-warning';
    if (category === 'display') return 'bg-label-primary';

    return 'bg-label-secondary';
};

const categoryLabel = (category: string | null) => {
    if (category === 'sample') return 'Sample';
    if (category === 'gimmick') return 'Gimmick';
    if (category === 'display') return 'Display';
    if (category === 'inventory') return 'Inventory';

    return '-';
};

const requestStatusClass = (status: string) => {
    if (status === 'pending') return 'bg-label-warning';
    if (status === 'approved') return 'bg-label-success';
    if (status === 'rejected') return 'bg-label-danger';

    return 'bg-label-secondary';
};

const requestTypeClass = (type: string) => {
    if (type === 'gimmick') return 'bg-label-warning';
    if (type === 'sample') return 'bg-label-info';

    return 'bg-label-secondary';
};

const formatDate = (value: string | null, withTime = false) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    }).format(new Date(value));
};

const formatCurrency = (value: number) => `Rp ${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(Number(value || 0));

const requestReference = (request: RequestRow) => {
    if (request.type === 'gimmick' && request.reference?.transaction_number) {
        return request.reference.transaction_number;
    }

    return `${formatNumber(request.item_count)} item`;
};
</script>

<template>
    <Head title="Sample & Gimmick" />

    <div class="sample-page" :inert="requestModalOpen || undefined">
        <header class="sample-header">
            <div>
                <span class="sample-eyebrow">Operasional / Toko</span>
                <h1>Sample & Gimmick</h1>
                <p>Tinjau pengajuan toko untuk barang tester dan barang gratis pelanggan khusus. Gimmick dari nota dikecualikan dari omset, gimmick scan dan sample ditandai di stok toko.</p>
            </div>
            <Link :href="props.urls.report" class="btn btn-sm btn-outline-primary">
                <i class="mdi mdi-file-chart-outline me-1" aria-hidden="true"></i>
                Buka Laporan
            </Link>
        </header>

        <div v-if="flash.success" class="alert alert-success py-2 small" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger py-2 small" role="alert">{{ flash.error }}</div>

        <div class="sample-tabs" role="tablist" aria-label="Sample dan gimmick">
            <button
                id="sample-tab-requests"
                class="sample-tab"
                :class="{ 'is-active': activeTab === 'requests' }"
                type="button"
                role="tab"
                :aria-selected="activeTab === 'requests'"
                :tabindex="activeTab === 'requests' ? 0 : -1"
                aria-controls="sample-panel-requests"
                @click="switchTab('requests')"
                @keydown.right.prevent="switchTab('items')"
            >
                <i class="mdi mdi-clipboard-check-outline" aria-hidden="true"></i>
                Pengajuan
                <span v-if="pendingCount > 0" class="badge bg-warning rounded-pill">{{ formatNumber(pendingCount) }}</span>
            </button>
            <button
                id="sample-tab-items"
                class="sample-tab"
                :class="{ 'is-active': activeTab === 'items' }"
                type="button"
                role="tab"
                :aria-selected="activeTab === 'items'"
                :tabindex="activeTab === 'items' ? 0 : -1"
                aria-controls="sample-panel-items"
                @click="switchTab('items')"
                @keydown.left.prevent="switchTab('requests')"
            >
                <i class="mdi mdi-flask-outline" aria-hidden="true"></i>
                Item
                <span class="badge bg-label-secondary rounded-pill">{{ formatNumber(summary.total_items) }}</span>
            </button>
        </div>

        <section
            v-show="activeTab === 'requests'"
            id="sample-panel-requests"
            role="tabpanel"
            aria-labelledby="sample-tab-requests"
        >
            <div class="sample-summary" aria-label="Ringkasan pengajuan">
                <div v-for="card in requestSummaryCards" :key="card.label" class="sample-summary-card">
                    <div class="sample-summary-head">
                        <i :class="['mdi', card.icon]" aria-hidden="true"></i>
                        <span>{{ card.label }}</span>
                    </div>
                    <strong :class="card.tone">{{ formatNumber(card.value) }}</strong>
                </div>
            </div>

            <section class="sample-filter" aria-labelledby="sample-request-filter-title">
                <h2 id="sample-request-filter-title">Filter pengajuan</h2>
                <form class="row g-2 align-items-end" @submit.prevent="applyRequestFilters">
                    <div class="col-12 col-lg-4">
                        <label for="request-q" class="form-label">Pencarian</label>
                        <input id="request-q" v-model="requestFilters.q" class="form-control form-control-sm" type="search" placeholder="Nomor pengajuan, nota, toko, atau pengguna..." />
                    </div>
                    <div class="col-6 col-lg-3">
                        <label for="request-store" class="form-label">Toko</label>
                        <SearchableSelect id="request-store" v-model="requestFilters.store_id" :options="storeOptions" placeholder="Semua toko" search-placeholder="Cari toko..." />
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="request-type" class="form-label">Jenis</label>
                        <select id="request-type" v-model="requestFilters.type" class="form-select form-select-sm">
                            <option v-for="option in props.requestTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="request-status" class="form-label">Status</label>
                        <select id="request-status" v-model="requestFilters.status" class="form-select form-select-sm">
                            <option v-for="option in props.requestStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-1">
                        <label for="request-per-page" class="form-label">Baris</label>
                        <select id="request-per-page" v-model.number="requestFilters.per_page" class="form-select form-select-sm">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-filter me-1" aria-hidden="true"></i>Terapkan</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetRequestFilters">Reset</button>
                    </div>
                </form>
            </section>

            <section class="sample-table-card" aria-labelledby="sample-request-table-title">
                <div class="sample-table-head">
                    <div>
                        <h2 id="sample-request-table-title">Daftar Pengajuan</h2>
                        <p>{{ formatNumber(props.requests.total) }} pengajuan sesuai filter.</p>
                    </div>
                    <span class="sample-selected">Menunggu: {{ formatNumber(props.requestSummary.pending) }}</span>
                </div>

                <div v-if="props.requests.data.length === 0" class="sample-empty">
                    <i class="mdi mdi-clipboard-text-clock-outline" aria-hidden="true"></i>
                    <strong>{{ requestHasFilters ? 'Tidak ada pengajuan yang cocok' : 'Belum ada pengajuan' }}</strong>
                    <span v-if="requestHasFilters">Ubah toko, jenis, status, atau kata kunci pencarian.</span>
                    <span v-else>Pengajuan dari toko akan muncul di sini untuk ditinjau.</span>
                    <button v-if="requestHasFilters" class="btn btn-sm btn-outline-secondary mt-2" type="button" @click="resetRequestFilters">Reset Filter</button>
                </div>

                <template v-else>
                    <div class="table-responsive d-none d-md-block">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Toko</th>
                                    <th>Jenis</th>
                                    <th>Referensi</th>
                                    <th class="text-end">Item</th>
                                    <th>Diajukan</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="request in props.requests.data" :key="request.id">
                                    <td>
                                        <strong class="sample-request-number">{{ request.request_number }}</strong>
                                        <small class="sample-subvalue">{{ request.created_by_name ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ request.store_name }}</span>
                                        <small class="sample-subvalue">{{ request.store_code ?? '-' }}</small>
                                    </td>
                                    <td><span class="badge" :class="requestTypeClass(request.type)">{{ request.type_label }}</span></td>
                                    <td>
                                        <span class="fw-semibold">{{ requestReference(request) }}</span>
                                        <small v-if="request.type === 'gimmick' && request.reference?.customer_name" class="sample-subvalue">{{ request.reference.customer_name }}</small>
                                    </td>
                                    <td class="text-end">{{ formatNumber(request.item_count) }}</td>
                                    <td>
                                        <span>{{ formatDate(request.submitted_at, true) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge" :class="requestStatusClass(request.status)">{{ request.status_label }}</span>
                                        <small v-if="request.reviewed_by_name" class="sample-subvalue">{{ request.reviewed_by_name }}</small>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" type="button" @click="openRequest(request, $event)">
                                            <i class="mdi mdi-eye-outline me-1" aria-hidden="true"></i>Tinjau
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="sample-mobile-list d-md-none">
                        <article v-for="request in props.requests.data" :key="request.id" class="sample-mobile-card">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong class="sample-request-number">{{ request.request_number }}</strong>
                                    <small class="sample-subvalue">{{ request.store_name }}</small>
                                </div>
                                <span class="badge" :class="requestStatusClass(request.status)">{{ request.status_label }}</span>
                            </div>
                            <div class="sample-mobile-meta">
                                <span class="badge" :class="requestTypeClass(request.type)">{{ request.type_label }}</span>
                                <span>{{ requestReference(request) }}</span>
                                <span>{{ formatNumber(request.item_count) }} item</span>
                                <span>{{ formatDate(request.submitted_at, true) }}</span>
                            </div>
                            <button class="btn btn-sm btn-outline-primary w-100" type="button" @click="openRequest(request, $event)">
                                <i class="mdi mdi-eye-outline me-1" aria-hidden="true"></i>Tinjau Pengajuan
                            </button>
                        </article>
                    </div>
                </template>

                <div class="sample-footer">
                    <small class="text-muted">Menampilkan {{ props.requests.from ?? 0 }} - {{ props.requests.to ?? 0 }} dari {{ props.requests.total }} pengajuan</small>
                    <PaginationLinks :links="props.requests.links" />
                </div>
            </section>
        </section>

        <section
            v-show="activeTab === 'items'"
            id="sample-panel-items"
            role="tabpanel"
            aria-labelledby="sample-tab-items"
        >
            <div class="sample-summary" aria-label="Ringkasan sample dan gimmick">
                <div v-for="card in summaryCards" :key="card.label" class="sample-summary-card">
                    <span>{{ card.label }}</span>
                    <strong :class="card.tone">{{ formatNumber(card.value) }}</strong>
                </div>
            </div>

            <section class="sample-filter" aria-labelledby="sample-filter-title">
                <h2 id="sample-filter-title">Filter item</h2>
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-4">
                        <label for="sample-q" class="form-label">Pencarian</label>
                        <input id="sample-q" v-model="itemFilters.q" class="form-control form-control-sm" type="search" placeholder="Nama produk, kode, store..." />
                    </div>
                    <div class="col-md-3">
                        <label for="sample-store" class="form-label">Store</label>
                        <SearchableSelect id="sample-store" v-model="itemFilters.store_id" :options="storeOptions" placeholder="Semua store" search-placeholder="Cari store..." />
                    </div>
                    <div class="col-md-3">
                        <label for="sample-category" class="form-label">Kategori</label>
                        <select id="sample-category" v-model="itemFilters.category" class="form-select form-select-sm">
                            <option value="">Semua kategori</option>
                            <option v-for="option in props.categories" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sample-per-page" class="form-label">Per Halaman</label>
                        <select id="sample-per-page" v-model.number="itemFilters.per_page" class="form-select form-select-sm">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-filter me-1" aria-hidden="true"></i>Terapkan</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters">Reset</button>
                    </div>
                </form>
            </section>

            <section class="sample-table-card" aria-labelledby="sample-item-table-title">
                <div class="sample-table-head">
                    <div>
                        <h2 id="sample-item-table-title">Daftar Item</h2>
                        <p>{{ formatNumber(props.rows.total) }} item sesuai filter.</p>
                    </div>
                    <div class="sample-actions">
                        <span class="sample-selected">{{ selectedCount }} dipilih</span>
                        <button class="btn btn-sm btn-outline-info" type="button" :disabled="selectedCount === 0 || processing" @click="mark('sample')">Tandai Sample</button>
                        <button class="btn btn-sm btn-outline-warning" type="button" :disabled="selectedCount === 0 || processing" @click="mark('gimmick')">Tandai Gimmick</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="selectedCount === 0 || processing" @click="mark('inventory')">Kembalikan ke Inventory</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="sample-check">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        :checked="selection.length === allIds.length && allIds.length > 0"
                                        aria-label="Pilih semua item"
                                        @change="toggleAll"
                                    />
                                </th>
                                <th>Produk</th>
                                <th>Kode</th>
                                <th>Store</th>
                                <th>Kategori Produk</th>
                                <th>Kondisi</th>
                                <th>Status</th>
                                <th>Ditambahkan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in props.rows.data" :key="row.id">
                                <td class="sample-check">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        :checked="selection.includes(row.id)"
                                        :aria-label="`Pilih ${row.product_name}`"
                                        @change="toggle(row.id)"
                                    />
                                </td>
                                <td class="fw-semibold">{{ row.product_name }}</td>
                                <td>{{ row.product_code ?? '-' }}</td>
                                <td>{{ row.store_code ? `${row.store_name} (${row.store_code})` : row.store_name }}</td>
                                <td>{{ row.product_category ?? '-' }}</td>
                                <td>{{ row.condition_name ?? '-' }}</td>
                                <td><span class="badge" :class="categoryClass(row.category)">{{ categoryLabel(row.category) }}</span></td>
                                <td class="text-nowrap">{{ formatDate(row.created_at) }}</td>
                            </tr>
                            <tr v-if="props.rows.data.length === 0">
                                <td colspan="8" class="sample-empty">
                                    <i class="mdi mdi-flask-empty-outline" aria-hidden="true"></i>
                                    <strong>Tidak ada item yang cocok</strong>
                                    <span>Coba ubah filter atau pastikan stok toko sudah tersedia.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="sample-footer">
                    <small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} item</small>
                    <PaginationLinks :links="props.rows.links" />
                </div>
            </section>
        </section>
    </div>

    <Teleport to="body">
        <div v-if="requestModalOpen" class="sample-modal-backdrop" @click.self="closeRequest">
            <section
                class="sample-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="sample-modal-title"
                aria-describedby="sample-modal-description"
            >
                <header class="sample-modal-header">
                    <div>
                        <h2 id="sample-modal-title">{{ selectedRequest?.request_number ?? 'Detail Pengajuan' }}</h2>
                        <p id="sample-modal-description">
                            <span v-if="selectedRequest">{{ selectedRequest.store_name }} · {{ selectedRequest.type_label }}</span>
                            <span v-else>Memuat pengajuan.</span>
                        </p>
                    </div>
                    <button ref="closeRequestButton" class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup detail pengajuan" :disabled="actionProcessing" @click="closeRequest">
                        <i class="mdi mdi-close" aria-hidden="true"></i>
                    </button>
                </header>

                <div v-if="selectedRequest" class="sample-modal-body">
                    <div class="sample-modal-badges">
                        <span class="badge" :class="requestStatusClass(selectedRequest.status)">{{ selectedRequest.status_label }}</span>
                        <span class="badge" :class="requestTypeClass(selectedRequest.type)">{{ selectedRequest.type_label }}</span>
                    </div>

                    <dl class="sample-modal-meta">
                        <div><dt>Diajukan</dt><dd>{{ formatDate(selectedRequest.submitted_at, true) }}</dd></div>
                        <div><dt>Oleh</dt><dd>{{ selectedRequest.created_by_name ?? '-' }}</dd></div>
                        <template v-if="selectedRequest.type === 'gimmick' && selectedRequest.reference?.transaction_number">
                            <div><dt>Nomor nota</dt><dd>{{ selectedRequest.reference.transaction_number }}</dd></div>
                            <div><dt>Pelanggan</dt><dd>{{ selectedRequest.reference.customer_name ?? '-' }}</dd></div>
                            <div><dt>Total nota</dt><dd>{{ formatCurrency(selectedRequest.reference.total_amount ?? 0) }}</dd></div>
                        </template>
                        <div v-else-if="selectedRequest.type === 'gimmick'"><dt>Sumber</dt><dd>Scan langsung tanpa nota</dd></div>
                        <div><dt>Jumlah item</dt><dd>{{ formatNumber(selectedRequest.item_count) }} item</dd></div>
                        <div><dt>Reviewer</dt><dd>{{ selectedRequest.reviewed_by_name ?? '-' }}</dd></div>
                        <div><dt>Waktu review</dt><dd>{{ selectedRequest.reviewed_at ? formatDate(selectedRequest.reviewed_at, true) : '-' }}</dd></div>
                    </dl>

                    <div v-if="selectedRequest.notes" class="sample-modal-section">
                        <h3>Catatan pengajuan</h3>
                        <p>{{ selectedRequest.notes }}</p>
                    </div>

                    <div v-if="selectedRequest.review_note && selectedRequest.status === 'approved'" class="sample-modal-section">
                        <h3>Catatan reviewer</h3>
                        <p>{{ selectedRequest.review_note }}</p>
                    </div>

                    <div v-if="selectedRequest.rejection_reason" class="alert alert-danger py-2 small" role="alert">
                        <strong>Alasan penolakan:</strong> {{ selectedRequest.rejection_reason }}
                    </div>

                    <div class="sample-modal-section">
                        <h3>Item pengajuan</h3>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 sample-modal-table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Sumber</th>
                                        <th>RFID</th>
                                        <th>Kondisi</th>
                                        <th class="text-end">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in selectedRequest.items" :key="item.store_item_id ?? item.cart_detail_id">
                                        <td>
                                            <strong>{{ item.product_name }}</strong>
                                            <small class="sample-subvalue">{{ item.product_code }}</small>
                                        </td>
                                        <td>
                                            <span class="badge" :class="item.cart_detail_id ? 'bg-label-primary' : 'bg-label-info'">
                                                {{ item.cart_detail_id ? 'Nota' : 'Scan' }}
                                            </span>
                                        </td>
                                        <td class="font-monospace small">{{ item.rfid_value }}</td>
                                        <td>{{ item.condition_name ?? '-' }}</td>
                                        <td class="text-end fw-semibold">{{ item.cart_detail_id ? formatCurrency(item.net_price) : '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="selectedRequest.history.length > 0" class="sample-modal-section">
                        <h3>Riwayat</h3>
                        <ol class="sample-timeline">
                            <li v-for="(entry, index) in selectedRequest.history" :key="index">
                                <span class="sample-timeline-dot" aria-hidden="true"></span>
                                <div>
                                    <strong>{{ entry.action === 'submitted' ? 'Diajukan' : (entry.action === 'approved' ? 'Disetujui' : (entry.action === 'rejected' ? 'Ditolak' : entry.action)) }}</strong>
                                    <small>{{ formatDate(entry.at, true) }} · {{ entry.by_name ?? '-' }}</small>
                                    <p v-if="entry.note">{{ entry.note }}</p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <div v-if="selectedRequest.status === 'pending'" class="sample-review">
                        <h3>Keputusan review</h3>
                        <p class="sample-review-help">Setujui jika item valid. Penolakan wajib menyertakan alasan agar toko dapat memperbaiki pengajuan.</p>

                        <label for="review-note" class="form-label">Catatan review (opsional)</label>
                        <textarea id="review-note" v-model="reviewNote" class="form-control form-control-sm" rows="2" maxlength="1000" :disabled="actionProcessing"></textarea>

                        <div v-if="showReject" class="mt-3">
                            <label for="reject-reason" class="form-label">Alasan penolakan <span class="text-danger">*</span></label>
                            <textarea
                                id="reject-reason"
                                ref="rejectReasonInput"
                                v-model="rejectReason"
                                class="form-control form-control-sm"
                                rows="3"
                                maxlength="1000"
                                :disabled="actionProcessing"
                            ></textarea>
                        </div>

                        <div v-if="actionError" class="alert alert-danger py-2 small mt-3 mb-0" role="alert">{{ actionError }}</div>

                        <div class="sample-review-actions">
                            <template v-if="!showReject">
                                <button class="btn btn-sm btn-outline-danger" type="button" :disabled="actionProcessing" @click="startReject">
                                    <i class="mdi mdi-close me-1" aria-hidden="true"></i>Tolak
                                </button>
                                <button class="btn btn-sm btn-primary" type="button" :disabled="actionProcessing" @click="approveRequest">
                                    <span v-if="!actionProcessing"><i class="mdi mdi-check me-1" aria-hidden="true"></i>Setujui</span>
                                    <span v-else><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Memproses</span>
                                </button>
                            </template>
                            <template v-else>
                                <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="actionProcessing" @click="cancelReject">Batal</button>
                                <button class="btn btn-sm btn-danger" type="button" :disabled="actionProcessing" @click="rejectRequest">
                                    <span v-if="!actionProcessing"><i class="mdi mdi-send me-1" aria-hidden="true"></i>Kirim Penolakan</span>
                                    <span v-else><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Memproses</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.sample-page { color: var(--app-ink); }
.sample-header { padding: 1.4rem; margin-bottom: 1rem; border-radius: 0.9rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.sample-eyebrow { display: inline-block; margin-bottom: 0.35rem; color: var(--app-hero-eyebrow); font-size: 0.7rem; font-weight: 700; }
.sample-header h1 { margin: 0 0 0.35rem; font-size: clamp(1.3rem, 2vw, 1.6rem); }
.sample-header p { max-width: 58rem; margin: 0; color: var(--app-hero-muted); font-size: 0.86rem; }
.sample-tabs { display: flex; flex-wrap: wrap; gap: 0.35rem; padding: 0.3rem; margin-bottom: 1rem; border: 1px solid var(--app-border); border-radius: 0.75rem; background: var(--app-surface); }
.sample-tab { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.55rem 0.85rem; border: 1px solid transparent; border-radius: 0.55rem; background: transparent; color: var(--app-muted); font-size: 0.82rem; font-weight: 700; }
.sample-tab:hover { color: var(--app-ink); }
.sample-tab.is-active { border-color: rgba(var(--bs-primary-rgb), 0.25); background: rgba(var(--bs-primary-rgb), 0.08); color: var(--app-ink); }
.sample-tab:focus-visible { outline: 3px solid rgba(var(--bs-primary-rgb), 0.35); outline-offset: 2px; }
.sample-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.sample-summary-card { padding: 0.85rem 1rem; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface); }
.sample-summary-head { display: flex; align-items: center; gap: 0.35rem; color: var(--app-muted); font-size: 0.7rem; font-weight: 600; }
.sample-summary-head i { font-size: 1rem; }
.sample-summary-card span { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 600; }
.sample-summary-card strong { display: block; margin-top: 0.2rem; font-size: 1.25rem; font-weight: 800; }
.sample-summary-card strong.is-info { color: var(--app-info-ink); }
.sample-summary-card strong.is-warning { color: var(--app-warning-ink); }
.sample-summary-card strong.is-success { color: var(--app-success-ink, #157347); }
.sample-summary-card strong.is-danger { color: var(--app-danger-ink, #b02a37); }
.sample-filter, .sample-table-card { padding: 1rem; margin-bottom: 1rem; border: 1px solid var(--app-border); border-radius: 0.8rem; background: var(--app-surface); }
.sample-filter h2, .sample-table-head h2 { margin: 0 0 0.65rem; font-size: 0.95rem; font-weight: 800; }
.sample-table-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.85rem; }
.sample-table-head p { margin: 0.2rem 0 0; color: var(--app-muted); font-size: 0.78rem; }
.sample-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem; }
.sample-selected { color: var(--app-muted); font-size: 0.74rem; font-weight: 600; }
.sample-check { width: 2.4rem; text-align: center; }
.sample-page .table th { color: var(--app-muted); font-size: 0.72rem; font-weight: 700; white-space: nowrap; }
.sample-page .table td { font-size: 0.8rem; vertical-align: middle; }
.sample-request-number { display: block; color: var(--app-ink); font-weight: 700; font-variant-numeric: tabular-nums; }
.sample-subvalue { display: block; color: var(--app-muted); font-size: 0.72rem; }
.sample-empty { padding: 2.5rem 1rem; color: var(--app-muted); text-align: center; }
.sample-empty i { display: block; margin-bottom: 0.35rem; font-size: 1.8rem; }
.sample-empty strong { display: block; color: var(--app-ink); font-size: 0.9rem; }
.sample-empty span { display: block; margin-top: 0.2rem; font-size: 0.78rem; }
.sample-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding-top: 0.85rem; }
.sample-mobile-list { display: grid; gap: 0.7rem; }
.sample-mobile-card { padding: 0.85rem; border: 1px solid var(--app-border); border-radius: 0.7rem; }
.sample-mobile-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 0.45rem; margin: 0.7rem 0; color: var(--app-muted); font-size: 0.76rem; }
.sample-modal-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(15, 23, 42, 0.55); }
.sample-modal { display: flex; flex-direction: column; width: min(920px, 100%); max-height: min(88vh, 900px); overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.28); color: var(--app-ink); }
.sample-modal-header, .sample-modal-meta { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.75rem; padding: 1rem 1.1rem; }
.sample-modal-header { border-bottom: 1px solid var(--app-border); }
.sample-modal-header h2 { margin: 0; font-size: 1.05rem; font-weight: 800; }
.sample-modal-header p { margin: 0.15rem 0 0; color: var(--app-muted); font-size: 0.78rem; }
.sample-modal-body { padding: 1rem 1.1rem; overflow-y: auto; }
.sample-modal-badges { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.85rem; }
.sample-modal-meta { padding: 0; margin: 0 0 1rem; }
.sample-modal-meta > div { flex: 1 1 12rem; padding: 0.6rem 0.75rem; border: 1px solid var(--app-border); border-radius: 0.55rem; }
.sample-modal-meta dt { color: var(--app-muted); font-size: 0.7rem; font-weight: 600; }
.sample-modal-meta dd { margin: 0.15rem 0 0; font-size: 0.82rem; font-weight: 700; }
.sample-modal-section { padding-top: 0.9rem; margin-top: 0.9rem; border-top: 1px solid var(--app-border); }
.sample-modal-section h3 { margin: 0 0 0.55rem; font-size: 0.85rem; font-weight: 800; }
.sample-modal-section p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.sample-modal-table th { color: var(--app-muted); font-size: 0.7rem; font-weight: 700; }
.sample-timeline { display: grid; gap: 0.65rem; padding: 0; margin: 0; list-style: none; }
.sample-timeline li { display: flex; gap: 0.6rem; }
.sample-timeline-dot { flex: 0 0 auto; width: 0.5rem; height: 0.5rem; margin-top: 0.35rem; border-radius: 50%; background: var(--bs-primary); }
.sample-timeline strong { display: block; font-size: 0.8rem; }
.sample-timeline small { display: block; color: var(--app-muted); font-size: 0.72rem; }
.sample-timeline p { margin: 0.2rem 0 0; color: var(--app-muted); font-size: 0.76rem; }
.sample-review { padding-top: 0.9rem; margin-top: 0.9rem; border-top: 1px solid var(--app-border); }
.sample-review h3 { margin: 0 0 0.25rem; font-size: 0.9rem; font-weight: 800; }
.sample-review-help { margin: 0 0 0.75rem; color: var(--app-muted); font-size: 0.78rem; }
.sample-review-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.5rem; margin-top: 0.85rem; }
@media (max-width: 991.98px) { .sample-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575.98px) { .sample-summary { grid-template-columns: 1fr; } .sample-header { padding: 1.1rem; } .sample-modal-backdrop { padding: 0.5rem; } .sample-modal { max-height: 94vh; } }
</style>
