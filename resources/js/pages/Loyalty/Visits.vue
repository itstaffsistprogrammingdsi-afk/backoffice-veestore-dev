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

interface VisitRow {
    id: string;
    visit_code: string | null;
    visit_at: string | null;
    check_in_at: string | null;
    check_out_at: string | null;
    status: string | null;
    customer_name: string | null;
    wa_number: string | null;
    store_name: string | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    visits: Paginator<VisitRow>;
    stores: StoreOption[];
    filters: {
        wa: string;
        store_id: string;
        date_from: string;
        date_to: string;
    };
    urls: { index: string };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = reactive({
    wa: props.filters.wa ?? '',
    store_id: props.filters.store_id ?? 'all',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const applyFilters = () => {
    router.get(
        props.urls.index,
        {
            wa: form.wa || undefined,
            store_id: form.store_id || 'all',
            date_from: form.date_from || undefined,
            date_to: form.date_to || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const reset = () => {
    form.wa = '';
    form.store_id = 'all';
    form.date_from = '';
    form.date_to = '';
    applyFilters();
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

const statusClass = (status: string | null) => {
    const normalized = (status ?? '').toLowerCase();

    return normalized === 'active'
        ? 'bg-success'
        : normalized === 'inactive'
          ? 'bg-secondary'
          : 'bg-info';
};

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);
</script>

<template>
    <Head title="Visit Customer" />

    <div class="loyalty-page">
        <div class="loyalty-header">
            <div>
                <span class="loyalty-eyebrow">Loyalty & Membership</span>
                <h1>Visit Customer</h1>
                <p>Pantau riwayat kunjungan customer di setiap cabang.</p>
            </div>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card loyalty-card">
            <div class="card-body">
                <form class="row g-3 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-4">
                        <label class="form-label">Nomor WhatsApp</label>
                        <input v-model="form.wa" class="form-control" type="search" placeholder="Cari nomor WhatsApp" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cabang</label>
                        <select v-model="form.store_id" class="form-select">
                            <option value="all">Semua Cabang</option>
                            <option v-for="store in stores" :key="store.id" :value="store.id">
                                {{ store.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Dari Tanggal</label>
                        <input v-model="form.date_from" class="form-control" type="date" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai Tanggal</label>
                        <input v-model="form.date_to" class="form-control" type="date" />
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="mdi mdi-magnify me-1"></i>
                            Tampilkan
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" @click="reset">
                            Reset
                        </button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Cabang</th>
                            <th>Customer</th>
                            <th>WA Number</th>
                            <th>Kode Visit</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(visit, index) in visits.data" :key="visit.id">
                            <td>{{ (visits.from ?? 1) + index }}</td>
                            <td>{{ formatDate(visit.visit_at) }}</td>
                            <td>{{ visit.store_name ?? '-' }}</td>
                            <td>{{ visit.customer_name ?? '-' }}</td>
                            <td>
                                <span class="badge bg-primary">{{ visit.wa_number ?? '-' }}</span>
                            </td>
                            <td>{{ visit.visit_code ?? '-' }}</td>
                            <td>{{ formatDate(visit.check_in_at) }}</td>
                            <td>{{ formatDate(visit.check_out_at) }}</td>
                            <td>
                                <span class="badge" :class="statusClass(visit.status)">
                                    {{ visit.status ?? '-' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="visits.data.length === 0">
                            <td colspan="9" class="loyalty-empty">Belum ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">
                    Menampilkan {{ visits.from ?? 0 }} - {{ visits.to ?? 0 }} dari
                    {{ formatNumber(visits.total) }} data
                </small>
                <PaginationLinks :links="visits.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.loyalty-page {
    --loyalty-ink: var(--app-ink);
    --loyalty-muted: var(--app-muted);
    --loyalty-border: var(--app-border);
    color: var(--loyalty-ink);
}

.loyalty-header {
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: var(--loyalty-ink);
    color: #fff;
}

.loyalty-eyebrow {
    display: inline-block;
    margin-bottom: 0.5rem;
    color: var(--app-hero-eyebrow);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.loyalty-header h1 {
    margin: 0 0 0.35rem;
    font-size: 1.5rem;
}

.loyalty-header p {
    margin: 0;
    color: var(--app-hero-muted);
}

.loyalty-card {
    border: 1px solid var(--loyalty-border);
    border-radius: 0.9rem;
    box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05);
}

.loyalty-card th {
    color: var(--loyalty-muted);
    font-size: 0.75rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.loyalty-empty {
    padding: 2.5rem 1rem;
    color: var(--loyalty-muted);
    text-align: center;
}
</style>
