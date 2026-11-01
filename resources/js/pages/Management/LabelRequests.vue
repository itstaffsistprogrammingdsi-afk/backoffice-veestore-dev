<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';

interface StoreOption {
    id: string;
    name: string;
}

interface LabelRequestRow {
    id: string;
    status: string;
    reason: string | null;
    created_at: string | null;
    approved_at: string | null;
    store_name: string | null;
    requested_by_name: string | null;
    approved_by_name: string | null;
    total_items: number;
    item_summary: string | null;
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
    stores: StoreOption[];
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

const applyFilters = () => {
    router.get(props.urls.index, {
        q: form.q || undefined,
        status: form.status || undefined,
        store_id: form.store_id || undefined,
        from: form.from || undefined,
        to: form.to || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const reset = () => {
    form.q = '';
    form.status = '';
    form.store_id = '';
    form.from = '';
    form.to = '';
    applyFilters();
};

const decide = (row: LabelRequestRow, action: 'approve' | 'reject') => {
    const label = action === 'approve' ? 'menyetujui' : 'menolak';

    if (!window.confirm(`Yakin ingin ${label} request ini?`)) {
        return;
    }

    const url = props.urls[action].replace('__ID__', row.id);
    router.put(url, {}, { preserveScroll: true });
};

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
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
</script>

<template>
    <Head title="Request Labels" />

    <div class="management-page">
        <div class="management-header">
            <div>
                <span class="management-eyebrow">Management</span>
                <h1>Request Labels</h1>
                <p>Tinjau dan proses permintaan cetak label dari toko.</p>
            </div>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card management-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-3">
                        <label class="form-label">Cari</label>
                        <input v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Toko, peminta, alasan..." />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select v-model="form.status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Toko</label>
                        <select v-model="form.store_id" class="form-select form-select-sm">
                            <option value="">Semua Toko</option>
                            <option v-for="store in stores" :key="store.id" :value="store.id">
                                {{ store.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Dari</label>
                        <input v-model="form.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai</label>
                        <input v-model="form.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-12 d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="mdi mdi-filter me-1"></i>
                            Filter
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card management-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Toko</th>
                            <th>Peminta</th>
                            <th>Alasan</th>
                            <th class="text-end">Item</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows.data" :key="row.id">
                            <td>{{ formatDate(row.created_at) }}</td>
                            <td class="fw-semibold">{{ row.store_name ?? '-' }}</td>
                            <td>{{ row.requested_by_name ?? '-' }}</td>
                            <td>{{ row.reason ?? '-' }}</td>
                            <td class="text-end">{{ row.total_items }}</td>
                            <td>
                                <span class="badge" :class="statusClass(row.status)">
                                    {{ row.status }}
                                </span>
                            </td>
                            <td>
                                <template v-if="row.status === 'pending'">
                                    <button class="btn btn-sm btn-success me-1" type="button" @click="decide(row, 'approve')">
                                        <i class="mdi mdi-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" type="button" @click="decide(row, 'reject')">
                                        <i class="mdi mdi-close"></i>
                                    </button>
                                </template>
                                <span v-else class="text-muted small">-</span>
                            </td>
                        </tr>
                        <tr v-if="rows.data.length === 0">
                            <td colspan="7" class="management-empty">Belum ada request.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">
                    Menampilkan {{ rows.from ?? 0 }} - {{ rows.to ?? 0 }} dari {{ rows.total }} request
                </small>
                <PaginationLinks :links="rows.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.management-page {
    --management-ink: var(--app-ink);
    --management-border: var(--app-border);
    --management-muted: var(--app-muted);
    color: var(--management-ink);
}

.management-header {
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: var(--management-ink);
    color: #fff;
}

.management-eyebrow {
    display: inline-block;
    margin-bottom: 0.5rem;
    color: #f9a8d4;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.management-header h1 {
    margin: 0 0 0.35rem;
    font-size: 1.5rem;
}

.management-header p {
    margin: 0;
    color: var(--app-hero-muted);
}

.management-card {
    border: 1px solid var(--management-border);
    border-radius: 0.9rem;
    box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05);
}

.management-card th {
    color: var(--management-muted);
    font-size: 0.75rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.management-empty {
    padding: 2.5rem 1rem;
    color: var(--management-muted);
    text-align: center;
}
</style>
