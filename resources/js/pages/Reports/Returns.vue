<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';

interface Option { id: string; name: string }

interface ReturnRow {
    id: string;
    return_number: string | null;
    status: string;
    date: string | null;
    created_at: string | null;
    store_name: string | null;
    warehouse_name: string | null;
    approved_by_name: string | null;
    approved_at: string | null;
}

interface DetailRow {
    product_id: string;
    product_name: string;
    qty_requested: number;
    qty_approved: number;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    returns: Paginator<ReturnRow>;
    stores: Option[];
    warehouses: Option[];
    statuses: string[];
    filters: {
        q: string;
        status: string;
        from: string;
        to: string;
    };
    urls: {
        index: string;
        show: string;
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
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const selected = ref<ReturnRow | null>(null);
const header = ref<Record<string, any> | null>(null);
const details = ref<DetailRow[]>([]);
const loading = ref(false);
const warehouseId = ref('');

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

const openReturn = async (row: ReturnRow) => {
    selected.value = row;
    header.value = null;
    details.value = [];
    loading.value = true;

    try {
        const response = await fetch(props.urls.show.replace('__ID__', row.id), { headers: { Accept: 'application/json' } });
        const data = await response.json();
        header.value = data.header ?? null;
        details.value = data.details ?? [];
        warehouseId.value = data.header?.warehouse_id ?? '';
    } finally {
        loading.value = false;
    }
};

const closeReturn = () => {
    selected.value = null;
    header.value = null;
    details.value = [];
};

const decide = (action: 'approve' | 'reject') => {
    if (!selected.value) return;

    if (action === 'reject' && !window.confirm('Reject retur ini?')) return;

    const payload = action === 'approve'
        ? { warehouse_id: warehouseId.value, approved_qty: Object.fromEntries(details.value.map((detail) => [detail.product_id, detail.qty_requested])) }
        : {};

    router.patch(props.urls[action].replace('__ID__', selected.value.id), payload, {
        preserveScroll: true,
        onSuccess: closeReturn,
    });
};

const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)) : '-';
const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);

const statusClass = (status: string) => {
    if (['approved', 'received', 'close', 'partial_approved'].includes(status)) return 'bg-success';
    if (['rejected', 'cancelled'].includes(status)) return 'bg-danger';
    if (['requested', 'pending_approval', 'shipped'].includes(status)) return 'bg-info';
    return 'bg-warning';
};
</script>

<template>
    <Head title="Retur Cabang" />
    <div class="report-page">
        <div class="report-header">
            <div>
                <span class="report-eyebrow">Reports</span>
                <h1>Retur Cabang</h1>
                <p>Permintaan retur dari toko ke gudang.</p>
            </div>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="apply">
                    <div class="col-md-3"><label class="form-label">Cari</label><input v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Return number, store, warehouse..." /></div>
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
                    <thead><tr><th>Return</th><th>Tanggal</th><th>Store</th><th>Warehouse</th><th>Status</th><th>Approved By</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <tr v-for="row in returns.data" :key="row.id">
                            <td class="fw-semibold">{{ row.return_number ?? row.id }}</td>
                            <td>{{ formatDate(row.date ?? row.created_at) }}</td>
                            <td>{{ row.store_name ?? '-' }}</td>
                            <td>{{ row.warehouse_name ?? '-' }}</td>
                            <td><span class="badge" :class="statusClass(row.status)">{{ row.status }}</span></td>
                            <td>{{ row.approved_by_name ?? '-' }}</td>
                            <td><button class="btn btn-sm btn-outline-primary" type="button" @click="openReturn(row)">Kelola</button></td>
                        </tr>
                        <tr v-if="returns.data.length === 0"><td colspan="7" class="report-empty">Belum ada retur.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2"><small class="text-muted">Menampilkan {{ returns.from ?? 0 }} - {{ returns.to ?? 0 }} dari {{ returns.total }} retur</small><PaginationLinks :links="returns.links" /></div>
        </div>

        <div v-if="selected" class="card report-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Retur {{ selected.return_number ?? selected.id }}</h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" @click="closeReturn">Tutup</button>
            </div>
            <div class="card-body">
                <div v-if="loading" class="text-muted">Memuat detail...</div>
                <template v-else>
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Warehouse untuk approve</label>
                            <select v-model="warehouseId" class="form-select form-select-sm">
                                <option value="">Pilih warehouse</option>
                                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex gap-2">
                            <button class="btn btn-sm btn-success" type="button" @click="decide('approve')">Approve</button>
                            <button class="btn btn-sm btn-outline-danger" type="button" @click="decide('reject')">Reject</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Produk</th><th class="text-end">Requested</th><th class="text-end">Approved</th></tr></thead>
                            <tbody>
                                <tr v-for="detail in details" :key="detail.product_id"><td>{{ detail.product_name }}</td><td class="text-end">{{ formatNumber(detail.qty_requested) }}</td><td class="text-end">{{ formatNumber(detail.qty_approved) }}</td></tr>
                                <tr v-if="details.length === 0"><td colspan="3" class="report-empty">Belum ada detail.</td></tr>
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
