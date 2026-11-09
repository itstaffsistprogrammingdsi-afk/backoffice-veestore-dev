<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface Entry {
    id: string;
    type: 'receivable' | 'payable';
    party: string;
    reference: string | null;
    amount: number;
    due_date: string | null;
    status: 'open' | 'paid';
    notes: string | null;
    created_by: string | null;
    created_at: string | null;
    paid_at: string | null;
}

interface Props {
    entries: Entry[];
    summary: {
        total_receivable: number;
        total_payable: number;
        overdue_amount: number;
        overdue_count: number;
        paid_count: number;
    };
    filters: {
        q: string;
        type: string;
        status: string;
    };
    canManage: boolean;
    urls: {
        index: string;
        store: string;
        status: string;
        destroy: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const filters = reactive({
    q: props.filters.q ?? '',
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
});

const form = useForm({
    type: 'receivable',
    party: '',
    reference: '',
    amount: '',
    due_date: '',
    notes: '',
});

const summaryCards = computed(() => [
    { label: 'Piutang Terbuka', value: formatCurrency(props.summary.total_receivable), tone: 'is-info' },
    { label: 'Hutang Terbuka', value: formatCurrency(props.summary.total_payable), tone: 'is-warning' },
    { label: 'Jatuh Tempo', value: formatCurrency(props.summary.overdue_amount), tone: 'is-danger', note: `${props.summary.overdue_count} catatan` },
    { label: 'Sudah Lunas', value: `${props.summary.paid_count} catatan`, tone: 'is-success' },
]);

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        type: filters.type || undefined,
        status: filters.status || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.q = '';
    filters.type = '';
    filters.status = '';
    applyFilters();
};

const submitEntry = () => {
    form.post(props.urls.store, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('party', 'reference', 'amount', 'due_date', 'notes');
        },
    });
};

const changeStatus = (entry: Entry) => {
    const next = entry.status === 'paid' ? 'open' : 'paid';
    const label = next === 'paid' ? 'lunas' : 'terbuka';

    if (!window.confirm(`Ubah status ${entry.party} menjadi ${label}?`)) return;

    router.patch(props.urls.status, { id: entry.id, status: next }, { preserveScroll: true });
};

const removeEntry = (entry: Entry) => {
    if (!window.confirm(`Hapus catatan ${entry.party} sebesar ${formatCurrency(entry.amount)}?`)) return;

    router.delete(props.urls.destroy, {
        data: { id: entry.id },
        preserveScroll: true,
    });
};

const isOverdue = (entry: Entry) => entry.status === 'open'
    && entry.due_date !== null
    && entry.due_date < new Date().toISOString().slice(0, 10);

const typeLabel = (type: string) => type === 'receivable' ? 'Piutang' : 'Hutang';

const typeClass = (type: string) => type === 'receivable' ? 'bg-label-info' : 'bg-label-warning';

const statusLabel = (status: string) => status === 'paid' ? 'Lunas' : 'Terbuka';

const statusClass = (status: string) => status === 'paid' ? 'bg-label-success' : 'bg-label-secondary';

const formatCurrency = (value: number | null | undefined) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));

const formatDate = (value: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};
</script>

<template>
    <Head title="Piutang & Hutang" />

    <div class="rp-page">
        <header class="rp-header">
            <div>
                <span class="rp-eyebrow">Reports / Penjualan</span>
                <h1>Piutang & Hutang</h1>
                <p>Pencatatan manual piutang dan hutang beserta jatuh tempo dan status pelunasan.</p>
            </div>
        </header>

        <div v-if="flash.success" class="alert alert-success py-2 small" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger py-2 small" role="alert">{{ flash.error }}</div>

        <section class="rp-summary" aria-label="Ringkasan piutang dan hutang">
            <div v-for="card in summaryCards" :key="card.label" class="rp-summary-card">
                <span>{{ card.label }}</span>
                <strong :class="card.tone">{{ card.value }}</strong>
                <small v-if="card.note">{{ card.note }}</small>
            </div>
        </section>

        <section v-if="props.canManage" class="rp-card" aria-labelledby="rp-add-title">
            <div class="rp-card-head">
                <div>
                    <h2 id="rp-add-title">Tambah Catatan</h2>
                    <p>Catatan manual, tidak terhubung otomatis ke transaksi.</p>
                </div>
            </div>
            <form class="row g-2 align-items-end" @submit.prevent="submitEntry">
                <div class="col-md-2">
                    <label for="rp-type" class="form-label">Jenis</label>
                    <select id="rp-type" v-model="form.type" class="form-select form-select-sm">
                        <option value="receivable">Piutang</option>
                        <option value="payable">Hutang</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="rp-party" class="form-label">Pihak</label>
                    <input id="rp-party" v-model="form.party" class="form-control form-control-sm" type="text" maxlength="150" required placeholder="Nama customer / partner" />
                    <small v-if="form.errors.party" class="text-danger">{{ form.errors.party }}</small>
                </div>
                <div class="col-md-2">
                    <label for="rp-reference" class="form-label">Referensi</label>
                    <input id="rp-reference" v-model="form.reference" class="form-control form-control-sm" type="text" maxlength="100" placeholder="Invoice / order" />
                </div>
                <div class="col-md-2">
                    <label for="rp-amount" class="form-label">Nominal</label>
                    <input id="rp-amount" v-model="form.amount" class="form-control form-control-sm" type="number" min="1" step="0.01" required />
                    <small v-if="form.errors.amount" class="text-danger">{{ form.errors.amount }}</small>
                </div>
                <div class="col-md-3">
                    <label for="rp-due" class="form-label">Jatuh Tempo</label>
                    <input id="rp-due" v-model="form.due_date" class="form-control form-control-sm" type="date" />
                </div>
                <div class="col-md-6">
                    <label for="rp-notes" class="form-label">Catatan</label>
                    <input id="rp-notes" v-model="form.notes" class="form-control form-control-sm" type="text" maxlength="500" placeholder="Opsional" />
                </div>
                <div class="col-md-3">
                    <button class="btn btn-sm btn-primary" type="submit" :disabled="form.processing">
                        <i class="mdi mdi-plus me-1" aria-hidden="true"></i>
                        {{ form.processing ? 'Menyimpan...' : 'Tambah Catatan' }}
                    </button>
                </div>
            </form>
        </section>

        <section class="rp-card" aria-labelledby="rp-list-title">
            <div class="rp-card-head">
                <div>
                    <h2 id="rp-list-title">Daftar Piutang & Hutang</h2>
                    <p>{{ props.entries.length }} catatan sesuai filter.</p>
                </div>
            </div>

            <form class="row g-2 align-items-end mb-3" @submit.prevent="applyFilters">
                <div class="col-md-4">
                    <label for="rp-search" class="form-label">Pencarian</label>
                    <input id="rp-search" v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nama pihak, referensi, catatan..." />
                </div>
                <div class="col-md-3">
                    <label for="rp-filter-type" class="form-label">Jenis</label>
                    <select id="rp-filter-type" v-model="filters.type" class="form-select form-select-sm">
                        <option value="">Semua jenis</option>
                        <option value="receivable">Piutang</option>
                        <option value="payable">Hutang</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="rp-filter-status" class="form-label">Status</label>
                    <select id="rp-filter-status" v-model="filters.status" class="form-select form-select-sm">
                        <option value="">Semua status</option>
                        <option value="open">Terbuka</option>
                        <option value="paid">Lunas</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary" type="submit">Terapkan</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters">Reset</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Pihak</th>
                            <th>Referensi</th>
                            <th class="text-end">Nominal</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Catatan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in props.entries" :key="entry.id" :class="{ 'rp-overdue': isOverdue(entry) }">
                            <td><span class="badge" :class="typeClass(entry.type)">{{ typeLabel(entry.type) }}</span></td>
                            <td class="fw-semibold">{{ entry.party }}</td>
                            <td>{{ entry.reference ?? '-' }}</td>
                            <td class="text-end">{{ formatCurrency(entry.amount) }}</td>
                            <td class="text-nowrap">
                                {{ formatDate(entry.due_date) }}
                                <small v-if="isOverdue(entry)" class="rp-overdue-label">Jatuh tempo</small>
                            </td>
                            <td><span class="badge" :class="statusClass(entry.status)">{{ statusLabel(entry.status) }}</span></td>
                            <td>{{ entry.notes ?? '-' }}</td>
                            <td class="text-end">
                                <template v-if="props.canManage">
                                    <button class="btn btn-sm btn-outline-secondary me-1" type="button" @click="changeStatus(entry)">
                                        {{ entry.status === 'paid' ? 'Buka' : 'Lunas' }}
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" type="button" :aria-label="`Hapus catatan ${entry.party}`" @click="removeEntry(entry)">
                                        <i class="mdi mdi-delete-outline" aria-hidden="true"></i>
                                    </button>
                                </template>
                                <span v-else class="text-muted">-</span>
                            </td>
                        </tr>
                        <tr v-if="props.entries.length === 0">
                            <td colspan="8" class="rp-empty">
                                <i class="mdi mdi-cash-clock" aria-hidden="true"></i>
                                <strong>Belum ada catatan</strong>
                                <span>Tambahkan piutang atau hutang manual untuk mulai memantau.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<style scoped>
.rp-page { color: var(--app-ink); }
.rp-header { padding: 1.4rem; margin-bottom: 1rem; border-radius: 0.9rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.rp-eyebrow { display: inline-block; margin-bottom: 0.35rem; color: var(--app-hero-eyebrow); font-size: 0.7rem; font-weight: 700; }
.rp-header h1 { margin: 0 0 0.35rem; font-size: clamp(1.3rem, 2vw, 1.6rem); }
.rp-header p { margin: 0; color: var(--app-hero-muted); font-size: 0.86rem; }
.rp-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.rp-summary-card { padding: 0.85rem 1rem; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface); }
.rp-summary-card span { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 600; }
.rp-summary-card strong { display: block; margin-top: 0.2rem; font-size: 1.1rem; font-weight: 800; }
.rp-summary-card small { display: block; margin-top: 0.15rem; color: var(--app-muted); font-size: 0.7rem; }
.rp-summary-card strong.is-info { color: var(--app-info-ink); }
.rp-summary-card strong.is-warning { color: var(--app-warning-ink); }
.rp-summary-card strong.is-danger { color: var(--app-danger-ink); }
.rp-summary-card strong.is-success { color: var(--app-accent-ink); }
.rp-card { padding: 1rem; margin-bottom: 1rem; border: 1px solid var(--app-border); border-radius: 0.8rem; background: var(--app-surface); }
.rp-card-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.8rem; }
.rp-card-head h2 { margin: 0; font-size: 0.95rem; font-weight: 800; }
.rp-card-head p { margin: 0.2rem 0 0; color: var(--app-muted); font-size: 0.78rem; }
.rp-page .table th { color: var(--app-muted); font-size: 0.72rem; font-weight: 700; white-space: nowrap; }
.rp-page .table td { font-size: 0.8rem; vertical-align: middle; }
.rp-overdue { background: color-mix(in srgb, var(--app-danger-bg) 45%, transparent); }
.rp-overdue-label { display: block; color: var(--app-danger-ink); font-size: 0.68rem; font-weight: 700; }
.rp-empty { padding: 2.5rem 1rem; color: var(--app-muted); text-align: center; }
.rp-empty i { display: block; margin-bottom: 0.35rem; font-size: 1.8rem; }
.rp-empty strong { display: block; color: var(--app-ink); font-size: 0.9rem; }
.rp-empty span { display: block; margin-top: 0.2rem; font-size: 0.78rem; }
@media (max-width: 991.98px) { .rp-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575.98px) { .rp-summary { grid-template-columns: 1fr; } .rp-header { padding: 1.1rem; } }
</style>
