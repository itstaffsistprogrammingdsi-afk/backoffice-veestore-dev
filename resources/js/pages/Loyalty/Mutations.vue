<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import ModuleNav from '../../components/ModuleNav.vue';

interface Customer {
    id: string;
    name: string | null;
    email: string | null;
    wa_number: string | null;
}

interface LoyaltyPoint {
    id: string;
    balance: string | number;
    customer: Customer | null;
}

interface MutationRow {
    id: string;
    type: string;
    type_label: string;
    mutation_type: string | null;
    points: number;
    balance_after: number;
    used_points: number;
    current_balance: number;
    description: string | null;
    ref: string | null;
    created_at: string | null;
    expired_at: string | null;
    has_expiration: boolean;
    customer_name: string | null;
    customer_email: string | null;
    customer_wa_number: string | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<MutationRow>;
    clps: LoyaltyPoint[];
    typeOptions: Array<{ value: string; label: string }>;
    filters: {
        q: string;
        type: string;
        clp: string;
        from: string;
        to: string;
    };
    urls: { index: string };
}

const props = defineProps<Props>();

const customerPointOptions = computed(() => props.clps.map((clp) => ({
    value: clp.id,
    label: `${clp.customer?.name ?? '-'} · saldo ${new Intl.NumberFormat('id-ID').format(Number(clp.balance ?? 0))}`,
    hint: clp.customer?.wa_number ?? undefined,
})));

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = reactive({
    q: props.filters.q ?? '',
    type: props.filters.type ?? '',
    clp: props.filters.clp ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const applyFilters = () => {
    router.get(
        props.urls.index,
        {
            q: form.q || undefined,
            type: form.type || undefined,
            clp: form.clp || undefined,
            from: form.from || undefined,
            to: form.to || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const reset = () => {
    form.q = '';
    form.type = '';
    form.clp = '';
    form.from = '';
    form.to = '';
    applyFilters();
};

const formatNumber = (value: number | string) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const typeClass = (type: string) => {
    if (['earn', 'referral_register', 'referral_first_tx'].includes(type)) return 'bg-label-success';
    if (type === 'redeem') return 'bg-label-danger';
    if (type === 'expire') return 'bg-label-warning';
    if (type === 'adjust') return 'bg-label-info';
    return 'bg-label-secondary';
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
    <Head title="Loyalty Point Mutations" />

    <div class="loyalty-page">
        <div class="loyalty-header">
            <div>
                <span class="loyalty-eyebrow">Loyalty / Mutations</span>
                <h1>Loyalty Point Mutations</h1>
                <p>Riwayat penambahan, redeem, expire, dan penyesuaian poin pelanggan.</p>
            </div>
        </div>

        <ModuleNav module-key="loyaltyModule" aria-label="Modul Loyalty" />

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card loyalty-card">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-3">
                        <label class="form-label">Cari</label>
                        <input v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Nama, email, atau WA" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipe</label>
                        <select v-model="form.type" class="form-select form-select-sm">
                            <option value="">Semua tipe</option>
                            <option v-for="option in props.typeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Customer Point</label>
                        <SearchableSelect
                            v-model="form.clp"
                            :options="customerPointOptions"
                            placeholder="Semua customer"
                            search-placeholder="Cari nama atau nomor customer..."
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Dari</label>
                        <input v-model="form.from" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai</label>
                        <input v-model="form.to" class="form-control form-control-sm" type="date" />
                    </div>
                    <div class="col-12 mt-2 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="mdi mdi-magnify me-1"></i>
                            Filter
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">
                            Reset
                        </button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Customer</th>
                            <th>Tipe</th>
                            <th class="text-end">Poin</th>
                            <th class="text-end">Saldo akhir</th>
                            <th>Referensi</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows.data" :key="row.id">
                            <td class="text-nowrap">{{ formatDate(row.created_at) }}</td>
                            <td>
                                <strong>{{ row.customer_name ?? '-' }}</strong>
                                <small class="loyalty-subvalue">{{ row.customer_email ?? '-' }}</small>
                                <small v-if="row.customer_wa_number" class="loyalty-subvalue">{{ row.customer_wa_number }}</small>
                            </td>
                            <td>
                                <span class="badge" :class="typeClass(row.type)">{{ row.type_label }}</span>
                            </td>
                            <td class="text-end">
                                <strong :class="row.points >= 0 ? 'text-success' : 'text-danger'">
                                    {{ row.points >= 0 ? '+' : '' }}{{ formatNumber(row.points) }}
                                </strong>
                            </td>
                            <td class="text-end">{{ formatNumber(row.balance_after) }}</td>
                            <td>
                                <span v-if="row.ref" class="badge bg-label-secondary">{{ row.ref }}</span>
                                <small v-if="row.has_expiration && row.expired_at" class="loyalty-subvalue">Exp: {{ formatDate(row.expired_at) }}</small>
                            </td>
                            <td>{{ row.description || '-' }}</td>
                        </tr>
                        <tr v-if="rows.data.length === 0">
                            <td colspan="7" class="loyalty-empty">Tidak ada data.</td>
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

.loyalty-subvalue {
    display: block;
    color: var(--loyalty-muted);
    font-size: 0.72rem;
}

.loyalty-empty {
    padding: 2.5rem 1rem;
    color: var(--loyalty-muted);
    text-align: center;
}
</style>
