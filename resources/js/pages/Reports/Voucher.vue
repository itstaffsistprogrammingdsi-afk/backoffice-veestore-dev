<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';

interface VoucherRow {
    id: string;
    code: string | null;
    name: string | null;
    type: string | null;
    value: string | number | null;
    context: string | null;
    ecommerce_used: number;
    store_used: number;
    used_total: number;
}

interface DetailRow {
    order_number: string | null;
    customer_name: string | null;
    value: string | number | null;
    created_at: string | null;
    source: string;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    vouchers: Paginator<VoucherRow>;
    voucherList: string[];
    filters: {
        voucher: string | null;
        start: string | null;
        end: string | null;
    };
    urls: {
        index: string;
        export: string;
        detail: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = reactive({
    voucher: props.filters.voucher ?? '',
    start: props.filters.start ?? '',
    end: props.filters.end ?? '',
});

const detailId = ref<string | null>(null);
const detailRows = ref<DetailRow[]>([]);
const detailLoading = ref(false);

const query = computed(() => ({
    voucher: form.voucher || undefined,
    start: form.start || undefined,
    end: form.end || undefined,
}));

const applyFilters = () => {
    router.get(props.urls.index, query.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const reset = () => {
    form.voucher = '';
    form.start = '';
    form.end = '';
    applyFilters();
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(query.value)) {
        if (value) {
            params.set(key, String(value));
        }
    }

    const suffix = params.toString();

    return suffix ? `${props.urls.export}?${suffix}` : props.urls.export;
});

const loadDetail = async (row: VoucherRow) => {
    if (detailId.value === row.id) {
        detailId.value = null;
        detailRows.value = [];
        return;
    }

    detailId.value = row.id;
    detailLoading.value = true;
    detailRows.value = [];

    try {
        const response = await fetch(props.urls.detail.replace('__ID__', row.id), {
            headers: { Accept: 'application/json' },
        });

        detailRows.value = response.ok ? await response.json() : [];
    } finally {
        detailLoading.value = false;
    }
};

const formatNumber = (value: string | number | null) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

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
</script>

<template>
    <Head title="Report Voucher" />

    <div class="report-page">
        <div class="report-header">
            <div>
                <span class="report-eyebrow">Reports</span>
                <h1>Voucher Usage</h1>
                <p>Ringkasan pemakaian voucher pada transaksi toko dan ecommerce.</p>
            </div>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-4">
                        <label class="form-label">Voucher</label>
                        <select v-model="form.voucher" class="form-select form-select-sm">
                            <option value="">Semua Voucher</option>
                            <option v-for="code in voucherList" :key="code" :value="code">{{ code }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dari</label>
                        <input v-model="form.start" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sampai</label>
                        <input v-model="form.end" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card report-card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Voucher Usage Report</h5>
                <a :href="exportUrl" class="btn btn-sm btn-success">
                    <i class="mdi mdi-download me-1"></i>
                    Export Excel
                </a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th class="text-end">Value</th>
                            <th>Context</th>
                            <th class="text-end">Ecommerce</th>
                            <th class="text-end">Store</th>
                            <th class="text-end">Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="row in vouchers.data" :key="row.id">
                            <tr>
                                <td class="fw-semibold">{{ row.code ?? '-' }}</td>
                                <td>{{ row.name ?? '-' }}</td>
                                <td>{{ row.type ?? '-' }}</td>
                                <td class="text-end">{{ formatNumber(row.value) }}</td>
                                <td>{{ row.context ?? '-' }}</td>
                                <td class="text-end">{{ row.ecommerce_used }}</td>
                                <td class="text-end">{{ row.store_used }}</td>
                                <td class="text-end fw-bold">{{ row.used_total }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" type="button" @click="loadDetail(row)">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="detailId === row.id">
                                <td colspan="9" class="report-detail">
                                    <div v-if="detailLoading" class="text-muted">Memuat detail...</div>
                                    <div v-else class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr><th>Order</th><th>Customer</th><th class="text-end">Value</th><th>Tanggal</th><th>Source</th></tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(detail, index) in detailRows" :key="index">
                                                    <td>{{ detail.order_number ?? '-' }}</td>
                                                    <td>{{ detail.customer_name ?? '-' }}</td>
                                                    <td class="text-end">{{ formatNumber(detail.value) }}</td>
                                                    <td>{{ formatDate(detail.created_at) }}</td>
                                                    <td>{{ detail.source }}</td>
                                                </tr>
                                                <tr v-if="detailRows.length === 0">
                                                    <td colspan="5" class="text-muted text-center">Belum ada detail.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="vouchers.data.length === 0">
                            <td colspan="9" class="report-empty">Belum ada data voucher.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">Menampilkan {{ vouchers.from ?? 0 }} - {{ vouchers.to ?? 0 }} dari {{ vouchers.total }} voucher</small>
                <PaginationLinks :links="vouchers.links" />
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
.report-detail { background: var(--app-surface-2); }
.report-empty { padding: 2.5rem 1rem; color: var(--report-muted); text-align: center; }
</style>
