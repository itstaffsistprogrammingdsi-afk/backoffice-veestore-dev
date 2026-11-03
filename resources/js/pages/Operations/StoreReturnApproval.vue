<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';

defineOptions({ layout: AppLayout });

interface Option {
    id: string;
    name: string;
    code?: string | null;
}

interface ReturnRow {
    id: string;
    return_number: string | null;
    status: string;
    status_label: string;
    store_id: string;
    warehouse_id: string | null;
    note_store: string | null;
    created_at: string | null;
    approved_at: string | null;
    rejected_at: string | null;
    store_name: string | null;
    store_code: string | null;
    warehouse_name: string | null;
    warehouse_code: string | null;
    approved_by_name: string | null;
    total_items: number;
    approved_items: number;
    remaining_items: number;
}

interface ConditionRow {
    name: string;
    qty: number;
}

interface ReturnDetail {
    product_id: string;
    product_name: string;
    product_code: string;
    unit_name: string;
    qty_requested: number;
    qty_approved: number;
    qty_rejected: number;
    qty_remaining: number;
    conditions: ConditionRow[];
}

interface ReturnDetailResponse {
    header: {
        id: string;
        return_number: string | null;
        status: string;
        status_label: string;
        created_at: string | null;
        approved_at: string | null;
        rejected_at: string | null;
        shipped_at: string | null;
        received_at: string | null;
        note_store: string | null;
        note_warehouse: string | null;
        created_by: string | null;
        approved_by: string | null;
        store: Option;
        warehouse: Option | null;
        total_requested: number;
        total_approved: number;
        total_remaining: number;
    };
    details: ReturnDetail[];
    warehouses: Option[];
    is_editable: boolean;
    can_reject: boolean;
    can_change_warehouse: boolean;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<ReturnRow>;
    stats: {
        pending: number;
        pending_items: number;
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
        store_outbound: string;
        store_inbound: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status || 'pending',
    store_id: props.filters.store_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    per_page: String(props.filters.per_page ?? 10),
});

const selectedReturn = ref<ReturnRow | null>(null);
const returnDetail = ref<ReturnDetailResponse | null>(null);
const draftQty = reactive<Record<string, number>>({});
const warehouseId = ref('');
const note = ref('');
const loadingDetail = ref(false);
const detailError = ref('');
const actionError = ref('');
const working = ref(false);
const confirmAction = ref<'approve' | 'reject' | null>(null);
const drawerCloseRef = ref<HTMLButtonElement | null>(null);
const confirmCancelRef = ref<HTMLButtonElement | null>(null);

const formatNumber = (value: number | null | undefined) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));
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
    if (['approved', 'received', 'close'].includes(status)) return 'bg-label-success';
    if (['partial_approved', 'packing', 'packing_completed'].includes(status)) return 'bg-label-info';
    if (['rejected'].includes(status)) return 'bg-label-danger';
    if (['requested', 'pending_approval', 'shipped'].includes(status)) return 'bg-label-warning';
    return 'bg-label-secondary';
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
    filters.status = 'pending';
    filters.store_id = '';
    filters.from = '';
    filters.to = '';
    filters.per_page = '10';
    applyFilters();
};

const buildDraft = (data: ReturnDetailResponse) => {
    for (const key of Object.keys(draftQty)) {
        delete draftQty[key];
    }

    warehouseId.value = data.header.warehouse?.id ?? '';
    note.value = data.header.note_warehouse ?? '';

    for (const detail of data.details) {
        draftQty[detail.product_id] = data.is_editable && detail.qty_remaining > 0
            ? detail.qty_remaining
            : 0;
    }
};

const loadDetail = async (row: ReturnRow) => {
    loadingDetail.value = true;
    detailError.value = '';
    actionError.value = '';
    returnDetail.value = null;

    try {
        const response = await fetch(props.urls.show.replace('__ID__', row.id), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = (await response.json()) as ReturnDetailResponse;
        returnDetail.value = data;
        buildDraft(data);
    } catch {
        detailError.value = 'Detail retur gagal dimuat. Periksa koneksi lalu coba lagi.';
    } finally {
        loadingDetail.value = false;
    }
};

const openReturn = (row: ReturnRow) => {
    selectedReturn.value = row;
    document.body.style.overflow = 'hidden';
    document.getElementById('app')?.setAttribute('inert', '');
    void loadDetail(row);
    nextTick(() => drawerCloseRef.value?.focus());
};

const retryDetail = () => {
    if (selectedReturn.value) {
        void loadDetail(selectedReturn.value);
    }
};

const closeReturn = () => {
    if (working.value) return;

    selectedReturn.value = null;
    returnDetail.value = null;
    detailError.value = '';
    actionError.value = '';
    confirmAction.value = null;
    document.body.style.overflow = '';
    document.getElementById('app')?.removeAttribute('inert');
};

const maxQty = (detail: ReturnDetail) => detail.qty_remaining;

const draftFor = (detail: ReturnDetail) => Math.max(0, Number(draftQty[detail.product_id] ?? 0));

const approveTotal = computed(() => {
    if (!returnDetail.value) return 0;

    return returnDetail.value.details.reduce((total, detail) => total + draftFor(detail), 0);
});

const totalRemaining = computed(() => {
    if (!returnDetail.value) return 0;

    return returnDetail.value.details.reduce((total, detail) => total + detail.qty_remaining, 0);
});

const projectedApproved = computed(() => {
    if (!returnDetail.value) return 0;

    return returnDetail.value.header.total_approved + approveTotal.value;
});

const totalRequested = computed(() => returnDetail.value?.header.total_requested ?? 0);

const progressWidth = computed(() => {
    if (totalRequested.value <= 0) return '0%';

    return `${Math.min(100, Math.round((projectedApproved.value / totalRequested.value) * 100))}%`;
});

const partialAfterApproval = computed(() => totalRemaining.value - approveTotal.value > 0);

const selectedWarehouse = computed(() => {
    return returnDetail.value?.warehouses.find((warehouse) => warehouse.id === warehouseId.value) ?? null;
});

const validationMessages = computed<string[]>(() => {
    if (!returnDetail.value?.is_editable) return [];

    const messages: string[] = [];

    if (!warehouseId.value) {
        messages.push('Pilih gudang tujuan retur.');
    }

    if (approveTotal.value <= 0) {
        messages.push('Isi qty minimal satu barang untuk disetujui.');
    }

    for (const detail of returnDetail.value.details) {
        if (draftFor(detail) > detail.qty_remaining) {
            messages.push(`${detail.product_name}: qty melebihi sisa ${formatNumber(detail.qty_remaining)} ${detail.unit_name}.`);
        }
    }

    return messages;
});

const overallValid = computed(() => {
    if (!returnDetail.value?.is_editable) return false;
    if (!warehouseId.value) return false;

    return validationMessages.value.length === 0;
});

const payloadItems = computed(() => {
    if (!returnDetail.value) return [];

    return returnDetail.value.details.map((detail) => ({
        product_id: detail.product_id,
        qty: draftFor(detail),
    }));
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
    if (!selectedReturn.value || !overallValid.value) return;

    working.value = true;
    actionError.value = '';

    router.patch(props.urls.approve.replace('__ID__', selectedReturn.value.id), {
        items: payloadItems.value,
        warehouse_id: warehouseId.value,
        note: note.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            confirmAction.value = null;
            working.value = false;
            closeReturn();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string | undefined;
            actionError.value = firstError ?? 'Retur gagal disetujui. Periksa gudang dan qty.';
            confirmAction.value = null;
        },
        onFinish: () => {
            working.value = false;
        },
    });
};

const submitReject = () => {
    if (!selectedReturn.value) return;

    working.value = true;
    actionError.value = '';

    router.patch(props.urls.reject.replace('__ID__', selectedReturn.value.id), {
        note: note.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            confirmAction.value = null;
            working.value = false;
            closeReturn();
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string | undefined;
            actionError.value = firstError ?? 'Retur gagal ditolak.';
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

    if (selectedReturn.value) {
        closeReturn();
    }
};

watch(confirmAction, async (value) => {
    await nextTick();

    if (value) {
        confirmCancelRef.value?.focus();
        return;
    }

    if (selectedReturn.value) {
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
    <Head title="Persetujuan Retur Store" />

    <div class="return-page">
        <section class="report-header return-header">
            <div>
                <span class="return-eyebrow">Operations / Store</span>
                <h1>Persetujuan Retur Store</h1>
                <p>Tinjau barang retur dari store, periksa ketidaksesuaian, pilih gudang tujuan, dan setujui qty yang diterima.</p>
            </div>
            <div class="return-hero-actions">
                <Link :href="props.urls.store_outbound" class="return-hero-button">
                    <i class="mdi mdi-tray-arrow-up me-1"></i>
                    Store Outbound
                </Link>
                <Link :href="props.urls.store_inbound" class="return-hero-button return-hero-button-outline">
                    <i class="mdi mdi-tray-arrow-down me-1"></i>
                    Store Inbound
                </Link>
            </div>
        </section>

        <div v-if="flash.success" class="alert alert-success" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger" role="alert">{{ flash.error }}</div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="return-summary return-summary-warning">
                    <span class="return-summary-icon"><i class="mdi mdi-backup-restore"></i></span>
                    <div>
                        <span>Menunggu approval</span>
                        <strong>{{ formatNumber(props.stats.pending) }}</strong>
                        <small>{{ formatNumber(props.stats.pending_items) }} barang menunggu</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="return-summary">
                    <span class="return-summary-icon"><i class="mdi mdi-check-circle-outline"></i></span>
                    <div>
                        <span>Disetujui hari ini</span>
                        <strong>{{ formatNumber(props.stats.approved_today) }}</strong>
                        <small>Retur penuh</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="return-summary">
                    <span class="return-summary-icon"><i class="mdi mdi-progress-check"></i></span>
                    <div>
                        <span>Disetujui sebagian</span>
                        <strong>{{ formatNumber(props.stats.partial_today) }}</strong>
                        <small>Hari ini</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="return-summary return-summary-danger">
                    <span class="return-summary-icon"><i class="mdi mdi-close-circle-outline"></i></span>
                    <div>
                        <span>Ditolak</span>
                        <strong>{{ formatNumber(props.stats.rejected_today) }}</strong>
                        <small>Hari ini</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card return-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-4">
                        <label class="form-label" for="return-search">Cari retur, store, atau produk</label>
                        <input id="return-search" v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nomor retur, store, catatan, atau SKU..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="return-status">Status</label>
                        <select id="return-status" v-model="filters.status" class="form-select form-select-sm">
                            <option v-for="option in props.statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="return-store">Store</label>
                        <select id="return-store" v-model="filters.store_id" class="form-select form-select-sm">
                            <option value="">Semua store</option>
                            <option v-for="store in props.stores" :key="store.id" :value="store.id">{{ store.name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="return-from">Dari tanggal</label>
                        <input id="return-from" v-model="filters.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="return-to">Sampai tanggal</label>
                        <input id="return-to" v-model="filters.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="return-per-page">Baris</label>
                        <select id="return-per-page" v-model="filters.per_page" class="form-select form-select-sm">
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

        <div class="card return-card">
            <div class="return-card-header">
                <div>
                    <h2>Antrean approval retur store</h2>
                    <p>Retur berisi barang yang tidak sesuai dengan pesanan atau pengiriman store.</p>
                </div>
                <span class="return-card-meta">{{ formatNumber(props.rows.total) }} retur</span>
            </div>

            <div v-if="props.rows.data.length === 0" class="return-empty">
                <i class="mdi mdi-backup-restore"></i>
                <strong>Tidak ada retur pada filter ini</strong>
                <span>Ubah filter atau reset untuk melihat seluruh antrean retur.</span>
                <button class="btn btn-sm btn-outline-primary" type="button" @click="resetFilters">Reset filter</button>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 return-table">
                    <thead>
                        <tr>
                            <th>Retur</th>
                            <th>Store</th>
                            <th class="d-none d-md-table-cell">Barang</th>
                            <th class="d-none d-lg-table-cell">Gudang tujuan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.rows.data" :key="row.id">
                            <td>
                                <button class="return-order-link" type="button" @click="openReturn(row)">
                                    {{ row.return_number ?? row.id }}
                                </button>
                                <small class="return-subvalue">Diajukan {{ formatDateTime(row.created_at) }}</small>
                            </td>
                            <td>
                                <strong>{{ row.store_name ?? '-' }}</strong>
                                <small class="return-subvalue">{{ row.store_code ?? 'Tanpa kode' }}</small>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <strong>{{ formatNumber(row.total_items) }} barang</strong>
                                <small class="return-subvalue">{{ formatNumber(row.remaining_items) }} menunggu approval</small>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <strong :class="{ 'return-muted-strong': !row.warehouse_name }">{{ row.warehouse_name ?? 'Belum dipilih' }}</strong>
                                <small v-if="row.warehouse_name" class="return-subvalue">{{ row.approved_by_name ?? 'Belum disetujui' }}</small>
                                <small v-else class="return-subvalue">Ditentukan saat approval</small>
                            </td>
                            <td>
                                <span class="badge" :class="statusClass(row.status)">{{ row.status_label }}</span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" @click="openReturn(row)">
                                    <i class="mdi mdi-clipboard-search-outline me-1"></i>
                                    Tinjau
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="return-footer">
                <small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} retur</small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="selectedReturn" class="return-backdrop" @click.self="closeReturn">
            <section class="return-drawer" role="dialog" aria-modal="true" aria-labelledby="return-drawer-title" :inert="confirmAction ? true : undefined">
                <header class="return-drawer-header">
                    <div>
                        <span class="return-drawer-eyebrow">Retur store</span>
                        <h2 id="return-drawer-title">{{ selectedReturn.return_number ?? selectedReturn.id }}</h2>
                        <p>
                            {{ selectedReturn.store_name ?? '-' }}
                            <span aria-hidden="true"> &middot; </span>
                            Diajukan {{ formatDateTime(selectedReturn.created_at) }}
                        </p>
                    </div>
                    <div class="return-drawer-header-actions">
                        <span class="badge" :class="statusClass(selectedReturn.status)">{{ selectedReturn.status_label }}</span>
                        <button ref="drawerCloseRef" class="return-icon-button" type="button" aria-label="Tutup detail retur" @click="closeReturn">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                </header>

                <div class="return-drawer-body">
                    <div v-if="loadingDetail" class="return-state" aria-live="polite">
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        <strong>Memuat detail retur</strong>
                        <span>Menyiapkan barang, kondisi, dan gudang tujuan.</span>
                    </div>

                    <div v-else-if="detailError" class="return-state return-state-danger" role="alert">
                        <i class="mdi mdi-alert-circle-outline"></i>
                        <strong>Detail retur tidak dapat dimuat</strong>
                        <span>{{ detailError }}</span>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="retryDetail">Coba lagi</button>
                    </div>

                    <template v-else-if="returnDetail">
                        <div v-if="actionError" class="alert alert-danger" role="alert">{{ actionError }}</div>

                        <div v-if="!returnDetail.is_editable" class="alert alert-info" role="status">
                            Retur ini sudah diproses. Detail dan keputusan approval hanya dapat dilihat.
                        </div>

                        <div v-else-if="returnDetail.header.total_approved > 0" class="alert alert-info" role="status">
                            Sebagian barang sudah disetujui. Sisa {{ formatNumber(returnDetail.header.total_remaining) }} barang masih dapat disetujui.
                        </div>

                        <div class="return-meta-grid">
                            <div>
                                <span>Tanggal retur</span>
                                <strong>{{ formatDate(returnDetail.header.created_at) }}</strong>
                            </div>
                            <div>
                                <span>Diminta oleh</span>
                                <strong>{{ returnDetail.header.created_by ?? '-' }}</strong>
                            </div>
                            <div>
                                <span>Gudang tujuan</span>
                                <strong>{{ returnDetail.header.warehouse?.name ?? 'Belum dipilih' }}</strong>
                            </div>
                            <div>
                                <span>Approval</span>
                                <strong>{{ returnDetail.header.approved_by ?? 'Belum ada' }}</strong>
                                <small v-if="returnDetail.header.approved_at">{{ formatDateTime(returnDetail.header.approved_at) }}</small>
                            </div>
                        </div>

                        <section v-if="returnDetail.header.note_store" class="return-note-store" aria-label="Catatan dari store">
                            <div class="return-note-icon"><i class="mdi mdi-message-alert-outline"></i></div>
                            <div>
                                <span>Catatan dari store</span>
                                <p>{{ returnDetail.header.note_store }}</p>
                            </div>
                        </section>

                        <div class="return-decision-card">
                            <div class="return-decision-grid">
                                <div class="return-field">
                                    <label for="return-warehouse">Gudang tujuan retur</label>
                                    <select
                                        id="return-warehouse"
                                        v-model="warehouseId"
                                        class="form-select"
                                        :disabled="!returnDetail.is_editable || !returnDetail.can_change_warehouse || working"
                                    >
                                        <option value="">Pilih gudang</option>
                                        <option v-for="warehouse in returnDetail.warehouses" :key="warehouse.id" :value="warehouse.id">
                                            {{ warehouse.name }}
                                        </option>
                                    </select>
                                    <small v-if="!returnDetail.can_change_warehouse">Gudang terkunci karena sudah ada barang yang disetujui.</small>
                                    <small v-else>Barang retur yang disetujui akan diproses ke gudang ini.</small>
                                </div>

                                <div class="return-field">
                                    <label for="return-note">Catatan backoffice</label>
                                    <textarea
                                        id="return-note"
                                        v-model="note"
                                        class="form-control"
                                        rows="2"
                                        maxlength="1000"
                                        :disabled="!returnDetail.is_editable || working"
                                        placeholder="Contoh: barang tidak sesuai pesanan, cek kondisi sebelum masuk gudang."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div v-if="validationMessages.length > 0" class="alert alert-warning" role="alert">
                            <strong>Periksa keputusan:</strong>
                            {{ validationMessages[0] }}
                            <span v-if="validationMessages.length > 1"> (+{{ validationMessages.length - 1 }} masalah lain)</span>
                        </div>

                        <div class="return-items">
                            <article v-for="detail in returnDetail.details" :key="detail.product_id" class="return-item">
                                <header class="return-item-header">
                                    <div>
                                        <strong>{{ detail.product_name }}</strong>
                                        <small>{{ detail.product_code }}<span v-if="detail.unit_name"> &middot; {{ detail.unit_name }}</span></small>
                                        <div class="return-condition-list">
                                            <span v-for="condition in detail.conditions" :key="`${detail.product_id}-${condition.name}`" class="return-condition">
                                                {{ condition.name }}: {{ formatNumber(condition.qty) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="return-item-stats">
                                        <div><span>Diminta</span><strong>{{ formatNumber(detail.qty_requested) }}</strong></div>
                                        <div><span>Disetujui</span><strong>{{ formatNumber(detail.qty_approved) }}</strong></div>
                                        <div><span>Sisa</span><strong>{{ formatNumber(detail.qty_remaining) }}</strong></div>
                                    </div>
                                </header>

                                <div class="return-item-decision">
                                    <div class="return-field return-field-qty">
                                        <label :for="`return-qty-${detail.product_id}`">Setujui sekarang</label>
                                        <input
                                            :id="`return-qty-${detail.product_id}`"
                                            v-model.number="draftQty[detail.product_id]"
                                            class="form-control text-end"
                                            type="number"
                                            min="0"
                                            :max="maxQty(detail)"
                                            :disabled="!returnDetail.is_editable || detail.qty_remaining <= 0 || working"
                                        />
                                        <small>Maksimal {{ formatNumber(detail.qty_remaining) }} {{ detail.unit_name }}</small>
                                    </div>

                                    <div class="return-item-status" :class="{ 'is-done': detail.qty_remaining === 0 }">
                                        <i class="mdi" :class="detail.qty_remaining === 0 ? 'mdi-check-circle-outline' : 'mdi-clock-outline'"></i>
                                        <span>
                                            {{ detail.qty_remaining === 0 ? 'Tidak ada sisa barang' : `${formatNumber(detail.qty_remaining)} ${detail.unit_name} masih menunggu approval` }}
                                        </span>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div v-if="returnDetail.details.length === 0" class="return-state">
                            <i class="mdi mdi-package-variant-remove"></i>
                            <strong>Retur tidak memiliki detail barang</strong>
                            <span>Hubungi store untuk melengkapi detail retur sebelum approval.</span>
                        </div>
                    </template>
                </div>

                <footer v-if="returnDetail" class="return-drawer-footer">
                    <div class="return-total">
                        <span>Setelah approval</span>
                        <strong>{{ formatNumber(projectedApproved) }} / {{ formatNumber(totalRequested) }} barang</strong>
                        <div class="return-progress" role="progressbar" :aria-valuenow="projectedApproved" aria-valuemin="0" :aria-valuemax="totalRequested">
                            <span :style="{ width: progressWidth }"></span>
                        </div>
                        <small v-if="partialAfterApproval">Sebagian barang akan tetap menunggu approval.</small>
                        <small v-else>Seluruh barang pada retur akan disetujui.</small>
                    </div>

                    <div class="return-footer-actions">
                        <button
                            v-if="returnDetail.can_reject"
                            class="btn btn-outline-danger"
                            type="button"
                            :disabled="working"
                            @click="openConfirm('reject')"
                        >
                            Tolak
                        </button>
                        <button
                            v-if="returnDetail.is_editable"
                            class="btn btn-primary"
                            type="button"
                            :disabled="!overallValid || working"
                            @click="openConfirm('approve')"
                        >
                            <span v-if="working" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                            {{ partialAfterApproval ? 'Setujui sebagian' : 'Setujui retur' }}
                        </button>
                        <button v-else class="btn btn-outline-secondary" type="button" @click="closeReturn">Tutup</button>
                    </div>
                </footer>
            </section>
        </div>

        <div v-if="confirmAction" class="return-confirm-backdrop" @click.self="closeConfirm">
            <section class="return-confirm" role="alertdialog" aria-modal="true" aria-labelledby="return-confirm-title" aria-describedby="return-confirm-description">
                <h3 id="return-confirm-title">{{ confirmAction === 'approve' ? 'Setujui retur ini?' : 'Tolak retur ini?' }}</h3>
                <p id="return-confirm-description">
                    <template v-if="confirmAction === 'approve'">
                        {{ formatNumber(approveTotal) }} barang akan disetujui dan diproses ke {{ selectedWarehouse?.name ?? 'gudang terpilih' }}.
                        <span v-if="partialAfterApproval"> Sisa {{ formatNumber(totalRemaining - approveTotal) }} barang tetap menunggu approval.</span>
                        <span v-else> Seluruh barang pada retur akan disetujui.</span>
                    </template>
                    <template v-else>
                        Retur akan ditolak dan seluruh barang pada retur ini ditandai rejected.
                    </template>
                </p>
                <div class="return-confirm-summary">
                    <span>{{ selectedReturn?.return_number ?? selectedReturn?.id }}</span>
                    <strong>{{ selectedReturn?.store_name ?? '-' }}</strong>
                </div>
                <div class="return-confirm-actions">
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
.return-page { color: var(--app-ink); }
.return-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; }
.return-eyebrow { display: inline-block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.return-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.return-header p { max-width: 760px; margin: 0; }
.return-hero-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.return-hero-button { display: inline-flex; align-items: center; min-height: 44px; padding: 0.55rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface); color: var(--app-ink); font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.return-hero-button-outline { border: 1px solid var(--app-hero-muted); background: transparent; color: var(--app-hero-text); }
.return-hero-button:focus-visible,
.return-order-link:focus-visible,
.return-icon-button:focus-visible { outline: 2px solid var(--app-accent); outline-offset: 2px; }
.return-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.return-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.return-summary span:not(.return-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.return-summary strong { display: block; margin-top: 0.15rem; font-size: 1.35rem; }
.return-summary small { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: 0.68rem; }
.return-summary-warning .return-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.return-summary-danger .return-summary-icon { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.return-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.return-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.return-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.return-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.return-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.return-table th { padding: 0.6rem 0.9rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.return-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); color: var(--app-ink); }
.return-order-link { padding: 0; border: 0; background: transparent; color: var(--app-accent-ink); font-weight: 700; text-align: left; text-decoration: underline; text-underline-offset: 3px; }
.return-subvalue { display: block; color: var(--app-muted); font-size: 0.73rem; }
.return-muted-strong { color: var(--app-muted); font-weight: 600; }
.return-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.return-empty { display: grid; place-items: center; gap: 0.35rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.return-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.return-empty strong { color: var(--app-ink); }
.return-empty .btn { margin-top: 0.4rem; }
.return-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; justify-content: flex-end; background: rgba(15, 23, 42, 0.58); }
.return-drawer { display: flex; flex-direction: column; width: min(840px, 100%); height: 100%; background: var(--app-surface); color: var(--app-ink); box-shadow: -1rem 0 2.5rem rgba(15, 23, 42, 0.24); }
.return-drawer-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--app-border); background: var(--app-surface); }
.return-drawer-eyebrow { display: block; margin-bottom: 0.2rem; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; }
.return-drawer-header h2 { margin: 0 0 0.2rem; font-size: 1.15rem; }
.return-drawer-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.return-drawer-header-actions { display: flex; align-items: center; gap: 0.6rem; }
.return-icon-button { display: grid; width: 44px; height: 44px; place-items: center; border: 1px solid var(--app-border); border-radius: 0.6rem; background: var(--app-surface-2); color: var(--app-ink); font-size: 1.2rem; }
.return-drawer-body { flex: 1; padding: 1.1rem 1.25rem; overflow-y: auto; }
.return-state { display: grid; place-items: center; gap: 0.35rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.return-state i { font-size: 1.8rem; color: var(--app-accent-ink); }
.return-state strong { color: var(--app-ink); }
.return-state-danger i { color: var(--app-danger-ink); }
.return-state-danger .btn { margin-top: 0.4rem; }
.return-meta-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.7rem; margin-bottom: 1rem; }
.return-meta-grid > div { padding: 0.7rem 0.8rem; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface-2); }
.return-meta-grid span { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.return-meta-grid strong { display: block; margin-top: 0.15rem; font-size: 0.85rem; overflow-wrap: anywhere; }
.return-meta-grid small { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: 0.68rem; }
.return-note-store { display: flex; gap: 0.75rem; margin-bottom: 1rem; padding: 0.85rem; border: 1px solid color-mix(in srgb, var(--app-warning-ink) 28%, var(--app-border)); border-radius: 0.75rem; background: var(--app-warning-bg); color: var(--app-warning-ink); }
.return-note-icon { display: grid; flex: 0 0 auto; width: 2rem; height: 2rem; place-items: center; border-radius: 0.5rem; background: color-mix(in srgb, var(--app-warning-ink) 14%, transparent); font-size: 1.1rem; }
.return-note-store span { display: block; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.return-note-store p { margin: 0.2rem 0 0; color: inherit; font-size: 0.82rem; line-height: 1.5; white-space: pre-line; }
.return-decision-card { margin-bottom: 1rem; padding: 0.9rem; border: 1px solid var(--app-border); border-radius: 0.8rem; background: var(--app-surface-2); }
.return-decision-grid { display: grid; grid-template-columns: minmax(180px, 0.45fr) minmax(0, 1fr); gap: 0.75rem; align-items: start; }
.return-field label { display: block; margin-bottom: 0.25rem; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; }
.return-field .form-control,
.return-field .form-select { min-height: 42px; }
.return-field small { display: block; margin-top: 0.25rem; color: var(--app-muted); font-size: 0.7rem; }
.return-items { display: grid; gap: 0.9rem; }
.return-item { padding: 0.95rem; border: 1px solid var(--app-border); border-radius: 0.85rem; background: var(--app-surface); }
.return-item-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.return-item-header > div:first-child { min-width: 0; }
.return-item-header strong { display: block; overflow-wrap: anywhere; }
.return-item-header small { display: block; color: var(--app-muted); font-size: 0.74rem; }
.return-condition-list { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.45rem; }
.return-condition { padding: 0.15rem 0.45rem; border: 1px solid var(--app-border); border-radius: 999px; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; }
.return-item-stats { display: flex; gap: 0.75rem; text-align: right; }
.return-item-stats span { display: block; color: var(--app-muted); font-size: 0.62rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.return-item-stats strong { display: block; font-size: 0.9rem; }
.return-item-decision { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 0.75rem; margin-top: 0.8rem; padding-top: 0.75rem; border-top: 1px solid var(--app-border); }
.return-field-qty { width: 170px; }
.return-item-status { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.55rem; border-radius: 0.5rem; background: var(--app-warning-bg); color: var(--app-warning-ink); font-size: 0.74rem; }
.return-item-status.is-done { background: var(--app-success-bg); color: var(--app-success-ink); }
.return-drawer-footer { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-top: 1px solid var(--app-border); background: var(--app-surface-2); }
.return-total { min-width: 240px; max-width: 440px; }
.return-total span { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.return-total strong { display: block; margin-top: 0.1rem; font-size: 1rem; }
.return-total small { display: block; margin-top: 0.3rem; color: var(--app-muted); font-size: 0.72rem; }
.return-progress { height: 0.4rem; margin-top: 0.35rem; overflow: hidden; border-radius: 999px; background: var(--app-surface-3); }
.return-progress span { display: block; height: 100%; border-radius: inherit; background: var(--app-accent); transition: width 180ms ease; }
.return-footer-actions { display: flex; flex-wrap: wrap; gap: 0.6rem; }
.return-footer-actions .btn,
.return-confirm-actions .btn { min-height: 44px; }
.return-confirm-backdrop { position: fixed; inset: 0; z-index: 1090; display: grid; place-items: center; padding: 1rem; background: rgba(15, 23, 42, 0.68); }
.return-confirm { width: min(480px, 100%); padding: 1.25rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); color: var(--app-ink); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.32); }
.return-confirm h3 { margin: 0 0 0.4rem; font-size: 1.1rem; }
.return-confirm p { margin: 0 0 0.85rem; color: var(--app-muted); font-size: 0.86rem; line-height: 1.55; }
.return-confirm-summary { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.65rem 0.75rem; border: 1px solid var(--app-border); border-radius: 0.6rem; background: var(--app-surface-2); font-size: 0.8rem; }
.return-confirm-summary span { color: var(--app-muted); overflow-wrap: anywhere; }
.return-confirm-actions { display: flex; justify-content: flex-end; gap: 0.55rem; margin-top: 1rem; }
@media (max-width: 991.98px) {
    .return-header { align-items: flex-start; flex-direction: column; }
    .return-card-header { align-items: flex-start; flex-direction: column; }
    .return-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .return-decision-grid { grid-template-columns: 1fr; }
    .return-item-header { flex-direction: column; }
    .return-item-stats { text-align: left; }
}
@media (max-width: 575.98px) {
    .return-drawer-header { flex-direction: column; }
    .return-drawer-header-actions { width: 100%; justify-content: space-between; }
    .return-meta-grid { grid-template-columns: 1fr; }
    .return-field-qty { width: 100%; }
    .return-item-decision { align-items: stretch; flex-direction: column; }
    .return-item-status { justify-content: flex-start; }
    .return-drawer-footer { align-items: stretch; flex-direction: column; }
    .return-footer-actions .btn { flex: 1; }
}
@media (prefers-reduced-motion: reduce) {
    .return-progress span { transition: none; }
}
</style>
