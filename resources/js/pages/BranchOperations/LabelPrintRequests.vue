<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';

interface StoreOption {
    id: string;
    name: string;
}

interface UserOption {
    id: string;
    name: string | null;
}

interface StoreItemOption {
    store_id: string;
    item_id: string;
    category: string | null;
    product_code: string | null;
    product_name: string | null;
    item_status: string | null;
    barcode_value: string;
}

interface DetailItem {
    id: string;
    item_id: string | null;
    product_code: string | null;
    product_name: string | null;
    item_status: string | null;
    print_index: number | null;
    printed_by_name: string | null;
    printed_at: string | null;
}

interface LabelRequestRow {
    id: string;
    status: string;
    reason: string | null;
    created_at: string | null;
    store_name: string | null;
    requested_by_name: string | null;
    approved_by_name: string | null;
    approved_at: string | null;
    total_items: number;
    total_printed: number;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<LabelRequestRow>;
    detailItems: Record<string, DetailItem[]>;
    stores: StoreOption[];
    users: UserOption[];
    statuses: string[];
    storeItems: StoreItemOption[];
    canDecide: boolean;
    canRequest: boolean;
    canPrint: boolean;
    filters: {
        q: string;
        status: string | null;
        store_id: string | null;
        requested_by: string | null;
        from: string;
        to: string;
    };
    urls: {
        index: string;
        store: string;
        approve: string;
        reject: string;
        print: string;
    };
}

const props = defineProps<Props>();
const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
})));

const userOptions = computed(() => props.users.map((user) => ({
    value: user.id,
    label: user.name ?? '-',
})));


const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    store_id: props.filters.store_id ?? '',
    requested_by: props.filters.requested_by ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const expandedId = ref<string | null>(null);
const showCreate = ref(false);
const itemSearch = ref('');
const printFormRef = ref<HTMLFormElement | null>(null);
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const createForm = reactive({
    store_id: '',
    reason: '',
    item_ids: [] as string[],
});

watch(() => createForm.store_id, () => {
    createForm.item_ids = [];
    itemSearch.value = '';
});

const selectedStoreItems = computed(() => {
    if (!createForm.store_id) return [];

    const needle = itemSearch.value.trim().toLowerCase();

    return props.storeItems.filter((item) => {
        if (item.store_id !== createForm.store_id) return false;
        if (!needle) return true;

        return `${item.product_code ?? ''} ${item.product_name ?? ''} ${item.barcode_value}`.toLowerCase().includes(needle);
    });
});

const toggleItem = (itemId: string) => {
    const index = createForm.item_ids.indexOf(itemId);

    if (index >= 0) {
        createForm.item_ids.splice(index, 1);
        return;
    }

    createForm.item_ids.push(itemId);
};

const submitCreate = () => {
    if (!createForm.store_id) {
        window.alert('Pilih toko terlebih dahulu.');
        return;
    }

    if (createForm.item_ids.length === 0) {
        window.alert('Pilih minimal satu item yang akan dicetak.');
        return;
    }

    router.post(props.urls.store, {
        store_id: createForm.store_id,
        reason: createForm.reason,
        item_ids: createForm.item_ids,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            createForm.store_id = '';
            createForm.reason = '';
            createForm.item_ids = [];
        },
    });
};

const printRequest = (row: LabelRequestRow) => {
    const formElement = printFormRef.value;

    if (!formElement) return;

    formElement.action = props.urls.print.replace('__ID__', row.id);
    formElement.submit();
};

const applyFilters = () => {
    router.get(props.urls.index, {
        q: form.q || undefined,
        status: form.status || undefined,
        store_id: form.store_id || undefined,
        requested_by: form.requested_by || undefined,
        from: form.from || undefined,
        to: form.to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const reset = () => {
    form.q = '';
    form.status = '';
    form.store_id = '';
    form.requested_by = '';
    form.from = '';
    form.to = '';
    applyFilters();
};

const toggleDetail = (id: string) => {
    expandedId.value = expandedId.value === id ? null : id;
};

const decide = (row: LabelRequestRow, action: 'approve' | 'reject') => {
    const label = action === 'approve' ? 'menyetujui' : 'menolak';

    if (!window.confirm(`Yakin ingin ${label} request ini?`)) {
        return;
    }

    router.put(props.urls[action].replace('__ID__', row.id), { from_branch: 1 }, { preserveScroll: true });
};

const statusClass = (status: string) => {
    if (status === 'approved' || status === 'printed') {
        return 'bg-success';
    }

    if (status === 'rejected') {
        return 'bg-danger';
    }

    return 'bg-warning';
};

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
</script>

<template>
    <Head title="Permintaan Cetak Label" />

    <div class="branch-page">
        <div class="branch-header">
            <div>
                <span class="branch-eyebrow">Branch Operations</span>
                <h1>Permintaan Label</h1>
                <p>Riwayat permintaan cetak label dari toko, termasuk persetujuan HO/BO dan jumlah cetak ulang.</p>
            </div>
            <button v-if="props.canRequest" type="button" class="btn btn-primary" @click="showCreate = !showCreate">
                <i class="mdi mdi-tag-plus-outline me-1"></i>
                Buat Permintaan
            </button>
        </div>

        <form ref="printFormRef" method="post" target="_blank" class="d-none">
            <input type="hidden" name="_token" :value="csrfToken" />
        </form>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div v-if="showCreate && props.canRequest" class="card branch-card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h6 mb-1">Permintaan Cetak Label Baru</h2>
                        <p class="text-muted small mb-0">Permintaan akan menunggu persetujuan HO/BO sebelum dapat dicetak.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="showCreate = false">Tutup</button>
                </div>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Toko</label>
                        <SearchableSelect v-model="createForm.store_id" :options="storeOptions" placeholder="Pilih toko" search-placeholder="Cari toko..." />
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Alasan cetak / cetak ulang</label>
                        <input v-model="createForm.reason" class="form-control form-control-sm" type="text" maxlength="500" placeholder="Contoh: label rusak, hilang, atau permintaan ulang cabang" />
                    </div>
                </div>
                <div v-if="createForm.store_id">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <label class="form-label mb-0">Item yang akan dicetak</label>
                        <input v-model="itemSearch" class="form-control form-control-sm branch-item-search" type="search" placeholder="Cari produk atau kode..." />
                    </div>
                    <div class="branch-item-picker">
                        <label v-for="item in selectedStoreItems" :key="item.item_id" class="branch-item-row">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                :checked="createForm.item_ids.includes(item.item_id)"
                                @change="toggleItem(item.item_id)"
                            />
                            <span>
                                <strong>{{ item.product_name ?? '-' }}</strong>
                                <small>{{ item.product_code ?? '-' }} · {{ item.barcode_value }} · {{ item.category ?? '-' }} · {{ item.item_status ?? '-' }}</small>
                            </span>
                        </label>
                        <p v-if="selectedStoreItems.length === 0" class="text-muted small mb-0">Tidak ada item yang cocok.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mt-3">
                    <button type="button" class="btn btn-primary" :disabled="!createForm.store_id || createForm.item_ids.length === 0 || !createForm.reason" @click="submitCreate">
                        <i class="mdi mdi-send-outline me-1"></i>
                        Kirim ke HO/BO
                    </button>
                    <span class="text-muted small">{{ createForm.item_ids.length }} item dipilih</span>
                </div>
            </div>
        </div>

        <div class="card branch-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-2">
                        <label class="form-label">Dari</label>
                        <input v-model="form.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai</label>
                        <input v-model="form.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Toko</label>
                        <SearchableSelect v-model="form.store_id" :options="storeOptions" placeholder="Semua Toko" search-placeholder="Cari toko..." />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select v-model="form.status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Peminta</label>
                        <SearchableSelect v-model="form.requested_by" :options="userOptions" placeholder="Semua Pengguna" search-placeholder="Cari pengguna..." />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cari</label>
                        <input v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Permintaan, toko..." />
                    </div>
                    <div class="col-12 d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-magnify me-1"></i>Filter</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card branch-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Permintaan</th>
                            <th>Toko</th>
                            <th>Peminta</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th class="text-end">Barang</th>
                            <th class="text-end">Dicetak</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="row in rows.data" :key="row.id">
                            <tr>
                                <td>{{ formatDate(row.created_at) }}</td>
                                <td class="fw-semibold">{{ row.id }}</td>
                                <td>{{ row.store_name ?? '-' }}</td>
                                <td>{{ row.requested_by_name ?? '-' }}</td>
                                <td>{{ row.reason ?? '-' }}</td>
                                <td><span class="badge" :class="statusClass(row.status)">{{ row.status }}</span></td>
                                <td class="text-end">{{ row.total_items }}</td>
                                <td class="text-end">{{ row.total_printed }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary me-1" type="button" @click="toggleDetail(row.id)">
                                        <i class="mdi mdi-eye-outline"></i>
                                    </button>
                                    <template v-if="props.canDecide && row.status === 'pending'">
                                        <button class="btn btn-sm btn-success me-1" type="button" title="Setujui" @click="decide(row, 'approve')">
                                            <i class="mdi mdi-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger me-1" type="button" title="Tolak" @click="decide(row, 'reject')">
                                            <i class="mdi mdi-close"></i>
                                        </button>
                                    </template>
                                    <button
                                        v-if="props.canPrint && (row.status === 'approved' || row.status === 'printed')"
                                        class="btn btn-sm btn-outline-primary"
                                        type="button"
                                        title="Cetak label"
                                        @click="printRequest(row)"
                                    >
                                        <i class="mdi mdi-printer-outline"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expandedId === row.id">
                                <td colspan="9" class="branch-detail">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr><th>Item ID</th><th>Product Code</th><th>Product Name</th><th>Status</th><th>Cetakan ke-</th><th>Dicetak Oleh</th><th>Waktu Cetak</th></tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="item in detailItems[row.id] ?? []" :key="item.id">
                                                    <td>{{ item.item_id ?? '-' }}</td>
                                                    <td>{{ item.product_code ?? '-' }}</td>
                                                    <td>{{ item.product_name ?? '-' }}</td>
                                                    <td>{{ item.item_status ?? '-' }}</td>
                                                    <td>{{ item.print_index ? `Cetakan ke-${item.print_index}` : '-' }}</td>
                                                    <td>{{ item.printed_by_name ?? '-' }}</td>
                                                    <td>{{ formatDate(item.printed_at) }}</td>
                                                </tr>
                                                <tr v-if="(detailItems[row.id] ?? []).length === 0">
                                                    <td colspan="7" class="text-muted text-center">Belum ada item.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="rows.data.length === 0">
                            <td colspan="9" class="branch-empty">Belum ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">Menampilkan {{ rows.from ?? 0 }} - {{ rows.to ?? 0 }} dari {{ rows.total }} data</small>
                <PaginationLinks :links="rows.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.branch-page { --branch-ink: var(--app-ink); --branch-border: var(--app-border); --branch-muted: var(--app-muted); color: var(--branch-ink); }
.branch-header { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--branch-ink); color: #fff; }
.branch-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.branch-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.branch-header p { margin: 0; color: var(--app-hero-muted); }
.branch-card { border: 1px solid var(--branch-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.branch-card th { color: var(--branch-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.branch-detail { background: var(--app-surface-2); }
.branch-empty { padding: 2.5rem 1rem; color: var(--branch-muted); text-align: center; }
.branch-item-search { max-width: 320px; }
.branch-item-picker { max-height: 280px; overflow-y: auto; border: 1px solid var(--app-border); border-radius: 0.6rem; }
.branch-item-row { display: flex; align-items: flex-start; gap: 0.65rem; padding: 0.6rem 0.75rem; border-bottom: 1px solid var(--app-border); cursor: pointer; }
.branch-item-row:last-child { border-bottom: 0; }
.branch-item-row:hover { background: var(--app-surface-2); }
.branch-item-row span { display: grid; gap: 0.15rem; min-width: 0; }
.branch-item-row strong { font-size: 0.84rem; }
.branch-item-row small { color: var(--app-muted); font-size: 0.72rem; }
</style>
