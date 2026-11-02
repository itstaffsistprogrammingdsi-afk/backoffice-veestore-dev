<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';

interface StoreOption {
    id: string;
    name: string;
}

interface DetailItem {
    id: string;
    item_id: string | null;
    product_code: string | null;
    product_name: string | null;
    item_status: string | null;
}

interface VoucherRequestRow {
    id: string;
    request_number: string | null;
    request_date: string | null;
    status: string;
    status_label: string | null;
    store_name: string | null;
    requested_by_name: string | null;
    notes: string | null;
    total_items: number;
    voucher_code: string | null;
    voucher_name: string | null;
    valid_from: string | null;
    valid_until: string | null;
    approved_by_name: string | null;
    approved_at: string | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<VoucherRequestRow>;
    detailItems: Record<string, DetailItem[]>;
    stores: StoreOption[];
    statuses: string[];
    filters: {
        q: string;
        status: string | null;
        store_id: string | null;
        from: string;
        to: string;
    };
    urls: {
        index: string;
        approve: string;
        reject: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    store_id: props.filters.store_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const expandedId = ref<string | null>(null);

const applyFilters = () => {
    router.get(props.urls.index, {
        q: form.q || undefined,
        status: form.status || undefined,
        store_id: form.store_id || undefined,
        from: form.from || undefined,
        to: form.to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const reset = () => {
    form.q = '';
    form.status = '';
    form.store_id = '';
    form.from = '';
    form.to = '';
    applyFilters();
};

const toggleDetail = (id: string) => {
    expandedId.value = expandedId.value === id ? null : id;
};

const decide = (row: VoucherRequestRow, action: 'approve' | 'reject') => {
    const label = action === 'approve' ? 'menyetujui' : 'menolak';

    if (!window.confirm(`Yakin ingin ${label} request ini?`)) {
        return;
    }

    router.put(props.urls[action].replace('__ID__', row.id), {}, { preserveScroll: true });
};

const statusClass = (status: string) => {
    if (status === 'approved') {
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
    <Head title="Request Voucher" />

    <div class="branch-page">
        <div class="branch-header">
            <div>
                <span class="branch-eyebrow">Branch Operations</span>
                <h1>Request Voucher</h1>
                <p>Kelola permintaan voucher dari toko.</p>
            </div>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

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
                    <div class="col-md-3">
                        <label class="form-label">Store</label>
                        <select v-model="form.store_id" class="form-select form-select-sm">
                            <option value="">Semua Store</option>
                            <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select v-model="form.status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cari</label>
                        <input v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Request, store, voucher..." />
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
                            <th>Request</th>
                            <th>Store</th>
                            <th>Peminta</th>
                            <th>Voucher</th>
                            <th>Status</th>
                            <th class="text-end">Item</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="row in rows.data" :key="row.id">
                            <tr>
                                <td>{{ formatDate(row.request_date) }}</td>
                                <td class="fw-semibold">{{ row.request_number ?? row.id }}</td>
                                <td>{{ row.store_name ?? '-' }}</td>
                                <td>{{ row.requested_by_name ?? '-' }}</td>
                                <td>
                                    <div>{{ row.voucher_code ?? '-' }}</div>
                                    <small class="text-muted">{{ row.voucher_name }}</small>
                                </td>
                                <td><span class="badge" :class="statusClass(row.status)">{{ row.status_label ?? row.status }}</span></td>
                                <td class="text-end">{{ row.total_items }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary me-1" type="button" @click="toggleDetail(row.id)">
                                        <i class="mdi mdi-eye-outline"></i>
                                    </button>
                                    <template v-if="row.status === 'pending'">
                                        <button class="btn btn-sm btn-success me-1" type="button" @click="decide(row, 'approve')">
                                            <i class="mdi mdi-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" type="button" @click="decide(row, 'reject')">
                                            <i class="mdi mdi-close"></i>
                                        </button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="expandedId === row.id">
                                <td colspan="8" class="branch-detail">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4"><small class="text-muted">Notes</small><div>{{ row.notes ?? '-' }}</div></div>
                                        <div class="col-md-4"><small class="text-muted">Approved By</small><div>{{ row.approved_by_name ?? '-' }}</div></div>
                                        <div class="col-md-4"><small class="text-muted">Approved At</small><div>{{ formatDate(row.approved_at) }}</div></div>
                                        <div class="col-md-6"><small class="text-muted">Valid From</small><div>{{ formatDate(row.valid_from) }}</div></div>
                                        <div class="col-md-6"><small class="text-muted">Valid Until</small><div>{{ formatDate(row.valid_until) }}</div></div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr><th>Item ID</th><th>Product Code</th><th>Product Name</th><th>Status</th></tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="item in detailItems[row.id] ?? []" :key="item.id">
                                                    <td>{{ item.item_id ?? '-' }}</td>
                                                    <td>{{ item.product_code ?? '-' }}</td>
                                                    <td>{{ item.product_name ?? '-' }}</td>
                                                    <td>{{ item.item_status ?? '-' }}</td>
                                                </tr>
                                                <tr v-if="(detailItems[row.id] ?? []).length === 0">
                                                    <td colspan="4" class="text-muted text-center">Belum ada item.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="rows.data.length === 0">
                            <td colspan="8" class="branch-empty">Belum ada data.</td>
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
.branch-header { padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--branch-ink); color: #fff; }
.branch-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.branch-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.branch-header p { margin: 0; color: var(--app-hero-muted); }
.branch-card { border: 1px solid var(--branch-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.branch-card th { color: var(--branch-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.branch-detail { background: var(--app-surface-2); }
.branch-empty { padding: 2.5rem 1rem; color: var(--branch-muted); text-align: center; }
</style>
