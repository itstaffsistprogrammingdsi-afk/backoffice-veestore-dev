<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import ModuleNav from '../../components/ModuleNav.vue';

interface Customer {
    id: string;
    name: string | null;
    email: string | null;
    wa_number: string | null;
}

interface PointRow {
    id: string;
    balance: string | number;
    updated_at: string | null;
    customer: Customer | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<PointRow>;
    summary: {
        members: number;
        total_balance: number;
        earned_this_month: number;
        redeemed_this_month: number;
    };
    filters: { q: string };
    urls: { index: string; mutations: string };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = reactive({
    q: props.filters.q ?? '',
});

const applyFilters = () => {
    router.get(
        props.urls.index,
        { q: form.q || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const reset = () => {
    form.q = '';
    applyFilters();
};

const formatNumber = (value: number | string) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

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
    <Head title="Customer Loyalty Points" />

    <div class="loyalty-page">
        <div class="loyalty-header">
            <div>
                <span class="loyalty-eyebrow">Loyalty / Points</span>
                <h1>Customer Loyalty Points</h1>
                <p>Pantau saldo poin pelanggan dan riwayat mutasinya.</p>
            </div>
            <form class="d-flex gap-2" @submit.prevent="applyFilters">
                <input
                    v-model="form.q"
                    class="form-control form-control-sm"
                    type="search"
                    placeholder="Cari nama, email, atau WA..."
                />
                <button class="btn btn-outline-secondary btn-sm" type="submit">
                    <i class="mdi mdi-magnify"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm" type="button" @click="reset">
                    <i class="mdi mdi-refresh"></i>
                </button>
            </form>
        </div>

        <ModuleNav module-key="loyaltyModule" aria-label="Modul Loyalty" />

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="loyalty-summary">
                    <span class="loyalty-summary-icon"><i class="mdi mdi-account-star-outline"></i></span>
                    <div><span>Member</span><strong>{{ formatNumber(props.summary.members) }}</strong><small>Punya saldo poin</small></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="loyalty-summary">
                    <span class="loyalty-summary-icon"><i class="mdi mdi-wallet-outline"></i></span>
                    <div><span>Total saldo</span><strong>{{ formatNumber(props.summary.total_balance) }}</strong><small>Poin aktif</small></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="loyalty-summary loyalty-summary-success">
                    <span class="loyalty-summary-icon"><i class="mdi mdi-plus-circle-outline"></i></span>
                    <div><span>Poin masuk</span><strong>{{ formatNumber(props.summary.earned_this_month) }}</strong><small>Bulan ini</small></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="loyalty-summary loyalty-summary-danger">
                    <span class="loyalty-summary-icon"><i class="mdi mdi-minus-circle-outline"></i></span>
                    <div><span>Poin keluar</span><strong>{{ formatNumber(props.summary.redeemed_this_month) }}</strong><small>Bulan ini</small></div>
                </div>
            </div>
        </div>

        <div class="card loyalty-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>WA</th>
                            <th class="text-end">Balance</th>
                            <th>Updated</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows.data" :key="row.id">
                            <td class="fw-semibold">{{ row.customer?.name ?? '-' }}</td>
                            <td>{{ row.customer?.email ?? '-' }}</td>
                            <td>{{ row.customer?.wa_number ?? '-' }}</td>
                            <td class="text-end">
                                <span class="badge loyalty-badge">{{ formatNumber(row.balance) }}</span>
                            </td>
                            <td>{{ formatDate(row.updated_at) }}</td>
                            <td class="text-end">
                                <Link
                                    :href="`${props.urls.mutations}?clp=${row.id}`"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="mdi mdi-swap-horizontal me-1"></i>
                                    Riwayat
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="rows.data.length === 0">
                            <td colspan="6" class="loyalty-empty">Tidak ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">
                    Menampilkan {{ rows.from ?? 0 }} - {{ rows.to ?? 0 }} dari {{ rows.total }} data
                </small>
                <PaginationLinks :links="rows.links" />
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
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
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

.loyalty-summary {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    height: 100%;
    padding: 1rem;
    border: 1px solid var(--loyalty-border);
    border-radius: 0.9rem;
    background: var(--app-surface);
    box-shadow: var(--app-shadow);
}

.loyalty-summary-icon {
    display: grid;
    flex: 0 0 auto;
    width: 2.4rem;
    height: 2.4rem;
    place-items: center;
    border-radius: 0.65rem;
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: 1.25rem;
}

.loyalty-summary span:not(.loyalty-summary-icon) {
    display: block;
    color: var(--loyalty-muted);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.loyalty-summary strong {
    display: block;
    margin-top: 0.15rem;
    font-size: 1.35rem;
}

.loyalty-summary small {
    display: block;
    margin-top: 0.1rem;
    color: var(--loyalty-muted);
    font-size: 0.68rem;
}

.loyalty-summary-success .loyalty-summary-icon {
    background: var(--app-success-bg);
    color: var(--app-success-ink);
}

.loyalty-summary-danger .loyalty-summary-icon {
    background: var(--app-danger-bg);
    color: var(--app-danger-ink);
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

.loyalty-badge {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.loyalty-empty {
    padding: 2.5rem 1rem;
    color: var(--loyalty-muted);
    text-align: center;
}

@media (max-width: 767.98px) {
    .loyalty-header {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
