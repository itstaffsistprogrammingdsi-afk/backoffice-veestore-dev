<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';

defineOptions({ layout: AppLayout });

interface StoreOption {
    id: string;
    name: string;
    code: string | null;
}

interface StatusOption {
    value: string;
    label: string;
}

interface BrandListingRow {
    id: string;
    brand_name: string;
    contact_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    price_list_path: string | null;
    price_list_url: string | null;
    notes: string | null;
    status: string;
    status_label: string;
    review_notes: string | null;
    store_name: string | null;
    requested_by_name: string | null;
    reviewed_by_name: string | null;
    created_at: string | null;
    reviewed_at: string | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<BrandListingRow>;
    summary: {
        total: number;
        pending: number;
        approved: number;
        rejected: number;
    };
    stores: StoreOption[];
    statusOptions: StatusOption[];
    filters: {
        q: string;
        status: string;
        store_id: string;
        from: string;
        to: string;
    };
    canDecide: boolean;
    urls: {
        index: string;
        store: string;
        approve: string;
        reject: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const showCreate = ref(false);
const fileInputKey = ref(0);

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    store_id: props.filters.store_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.code ? `${store.name} (${store.code})` : store.name,
})));

const createForm = useForm({
    store_id: '',
    brand_name: '',
    contact_name: '',
    contact_phone: '',
    contact_email: '',
    price_list: null as File | null,
    notes: '',
});

const summaryCards = computed(() => [
    { label: 'Total Pengajuan', value: props.summary.total, tone: '' },
    { label: 'Menunggu', value: props.summary.pending, tone: 'is-warning' },
    { label: 'Disetujui', value: props.summary.approved, tone: 'is-success' },
    { label: 'Ditolak', value: props.summary.rejected, tone: 'is-danger' },
]);

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        status: filters.status || undefined,
        store_id: filters.store_id || undefined,
        from: filters.from || undefined,
        to: filters.to || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.q = '';
    filters.status = '';
    filters.store_id = '';
    filters.from = '';
    filters.to = '';
    applyFilters();
};

const toggleCreate = () => {
    showCreate.value = !showCreate.value;

    if (!showCreate.value) {
        createForm.reset();
        createForm.clearErrors();
        fileInputKey.value += 1;
    }
};

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    createForm.price_list = input.files?.[0] ?? null;
};

const submitCreate = () => {
    createForm.post(props.urls.store, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createForm.clearErrors();
            fileInputKey.value += 1;
            showCreate.value = false;
        },
    });
};

const decide = (row: BrandListingRow, action: 'approve' | 'reject') => {
    if (action === 'approve') {
        if (!window.confirm(`Setujui listing brand ${row.brand_name}?`)) return;

        router.put(props.urls.approve.replace('__ID__', row.id), {}, { preserveScroll: true });

        return;
    }

    const reason = window.prompt(`Alasan menolak listing brand ${row.brand_name}:`)?.trim() ?? '';

    if (!reason) return;

    router.put(props.urls.reject.replace('__ID__', row.id), { review_notes: reason }, { preserveScroll: true });
};

const formatDate = (value: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const statusClass = (status: string) => {
    if (status === 'approved') return 'bg-label-success';
    if (status === 'rejected') return 'bg-label-danger';

    return 'bg-label-warning';
};

const fileName = (path: string | null) => {
    if (!path) return '';

    return path.split('/').pop() ?? path;
};
</script>

<template>
    <Head title="Listing Brand" />

    <div class="brand-listing-page">
        <header class="brand-listing-header">
            <div>
                <span class="brand-listing-eyebrow">Operasional / Mitra</span>
                <h1>Pengajuan Listing Brand</h1>
                <p>Store mengajukan brand, kontak, dan price list. Backoffice meninjau status pengajuan.</p>
            </div>
            <button class="btn btn-primary" type="button" :aria-expanded="showCreate" aria-controls="brand-listing-form" @click="toggleCreate">
                <i class="mdi" :class="showCreate ? 'mdi-close' : 'mdi-plus'" aria-hidden="true"></i>
                {{ showCreate ? 'Tutup Form' : 'Ajukan Brand' }}
            </button>
        </header>

        <div v-if="flash.success" class="alert alert-success py-2 small" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger py-2 small" role="alert">{{ flash.error }}</div>

        <section class="brand-listing-summary" aria-label="Ringkasan pengajuan listing brand">
            <div v-for="card in summaryCards" :key="card.label" class="brand-listing-summary-card">
                <span>{{ card.label }}</span>
                <strong :class="card.tone">{{ card.value }}</strong>
            </div>
        </section>

        <section v-if="showCreate" id="brand-listing-form" class="brand-listing-form-card" aria-labelledby="brand-listing-form-title">
            <div class="brand-listing-form-head">
                <div>
                    <h2 id="brand-listing-form-title">Form Pengajuan Brand</h2>
                    <p>Lengkapi data brand dan lampirkan price list bila tersedia.</p>
                </div>
            </div>

            <form class="row g-3" @submit.prevent="submitCreate">
                <div class="col-md-4">
                    <label for="brand-store" class="form-label">Store Pengaju</label>
                    <SearchableSelect
                        id="brand-store"
                        v-model="createForm.store_id"
                        :options="storeOptions"
                        placeholder="Pilih store"
                        search-placeholder="Cari store..."
                    />
                    <small v-if="createForm.errors.store_id" class="text-danger">{{ createForm.errors.store_id }}</small>
                </div>
                <div class="col-md-4">
                    <label for="brand-name" class="form-label">Nama Brand <span aria-hidden="true">*</span></label>
                    <input id="brand-name" v-model="createForm.brand_name" class="form-control" type="text" maxlength="150" required :aria-invalid="Boolean(createForm.errors.brand_name)" />
                    <small v-if="createForm.errors.brand_name" class="text-danger">{{ createForm.errors.brand_name }}</small>
                </div>
                <div class="col-md-4">
                    <label for="brand-contact" class="form-label">Contact Person</label>
                    <input id="brand-contact" v-model="createForm.contact_name" class="form-control" type="text" maxlength="150" />
                    <small v-if="createForm.errors.contact_name" class="text-danger">{{ createForm.errors.contact_name }}</small>
                </div>
                <div class="col-md-4">
                    <label for="brand-phone" class="form-label">Telepon</label>
                    <input id="brand-phone" v-model="createForm.contact_phone" class="form-control" type="tel" maxlength="50" />
                    <small v-if="createForm.errors.contact_phone" class="text-danger">{{ createForm.errors.contact_phone }}</small>
                </div>
                <div class="col-md-4">
                    <label for="brand-email" class="form-label">Email</label>
                    <input id="brand-email" v-model="createForm.contact_email" class="form-control" type="email" maxlength="150" />
                    <small v-if="createForm.errors.contact_email" class="text-danger">{{ createForm.errors.contact_email }}</small>
                </div>
                <div class="col-md-4">
                    <label for="brand-price-list" class="form-label">Price List / Lampiran</label>
                    <input :key="fileInputKey" id="brand-price-list" class="form-control" type="file" accept=".pdf,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.webp" @change="onFileChange" />
                    <small v-if="createForm.errors.price_list" class="text-danger">{{ createForm.errors.price_list }}</small>
                    <small class="text-muted">PDF, Excel, CSV, atau gambar. Maksimal 10 MB.</small>
                </div>
                <div class="col-12">
                    <label for="brand-notes" class="form-label">Catatan</label>
                    <textarea id="brand-notes" v-model="createForm.notes" class="form-control" rows="3" maxlength="2000" placeholder="Informasi tambahan mengenai brand, produk, atau kerja sama."></textarea>
                    <small v-if="createForm.errors.notes" class="text-danger">{{ createForm.errors.notes }}</small>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit" :disabled="createForm.processing">
                        <i class="mdi mdi-send-outline me-1" aria-hidden="true"></i>
                        {{ createForm.processing ? 'Mengirim...' : 'Kirim Pengajuan' }}
                    </button>
                    <button class="btn btn-outline-secondary" type="button" @click="toggleCreate">Batal</button>
                </div>
            </form>
        </section>

        <section class="brand-listing-filter-card" aria-labelledby="brand-listing-filter-title">
            <div class="brand-listing-filter-head">
                <h2 id="brand-listing-filter-title">Filter pengajuan</h2>
            </div>
            <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                <div class="col-md-4">
                    <label for="brand-filter-q" class="form-label">Pencarian</label>
                    <input id="brand-filter-q" v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Brand, kontak, store..." />
                </div>
                <div class="col-md-3">
                    <label for="brand-filter-status" class="form-label">Status</label>
                    <select id="brand-filter-status" v-model="filters.status" class="form-select form-select-sm">
                        <option value="">Semua status</option>
                        <option v-for="option in props.statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="brand-filter-store" class="form-label">Store</label>
                    <SearchableSelect
                        id="brand-filter-store"
                        v-model="filters.store_id"
                        :options="storeOptions"
                        placeholder="Semua store"
                        search-placeholder="Cari store..."
                    />
                </div>
                <div class="col-md-2">
                    <label for="brand-filter-from" class="form-label">Dari</label>
                    <input id="brand-filter-from" v-model="filters.from" class="form-control form-control-sm" type="date" />
                </div>
                <div class="col-md-2">
                    <label for="brand-filter-to" class="form-label">Sampai</label>
                    <input id="brand-filter-to" v-model="filters.to" class="form-control form-control-sm" type="date" />
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-filter me-1" aria-hidden="true"></i>Terapkan</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters">Reset</button>
                </div>
            </form>
        </section>

        <section class="brand-listing-table-card" aria-labelledby="brand-listing-table-title">
            <div class="brand-listing-table-head">
                <div>
                    <h2 id="brand-listing-table-title">Daftar Pengajuan</h2>
                    <p>{{ props.rows.total }} pengajuan sesuai filter.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Brand</th>
                            <th>Store</th>
                            <th>Kontak</th>
                            <th>Lampiran</th>
                            <th>Status</th>
                            <th>Diajukan</th>
                            <th>Reviewer</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.rows.data" :key="row.id">
                            <td>
                                <strong>{{ row.brand_name }}</strong>
                                <small v-if="row.notes" class="brand-listing-note">{{ row.notes }}</small>
                            </td>
                            <td>{{ row.store_name ?? '-' }}</td>
                            <td>
                                <span>{{ row.contact_name ?? '-' }}</span>
                                <small v-if="row.contact_phone" class="brand-listing-note">{{ row.contact_phone }}</small>
                                <small v-if="row.contact_email" class="brand-listing-note">{{ row.contact_email }}</small>
                            </td>
                            <td>
                                <a v-if="row.price_list_url" :href="row.price_list_url" class="btn btn-sm btn-outline-secondary">
                                    <i class="mdi mdi-download me-1" aria-hidden="true"></i>
                                    {{ fileName(row.price_list_path) }}
                                </a>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td>
                                <span class="badge" :class="statusClass(row.status)">{{ row.status_label }}</span>
                                <small v-if="row.review_notes" class="brand-listing-note">{{ row.review_notes }}</small>
                            </td>
                            <td class="text-nowrap">{{ formatDate(row.created_at) }}</td>
                            <td>
                                <span>{{ row.reviewed_by_name ?? '-' }}</span>
                                <small v-if="row.reviewed_at" class="brand-listing-note">{{ formatDate(row.reviewed_at) }}</small>
                            </td>
                            <td class="text-end">
                                <template v-if="props.canDecide && row.status === 'pending'">
                                    <button class="btn btn-sm btn-success me-1" type="button" title="Setujui pengajuan" @click="decide(row, 'approve')">
                                        <i class="mdi mdi-check" aria-hidden="true"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" type="button" title="Tolak pengajuan" @click="decide(row, 'reject')">
                                        <i class="mdi mdi-close" aria-hidden="true"></i>
                                    </button>
                                </template>
                                <span v-else class="text-muted">-</span>
                            </td>
                        </tr>
                        <tr v-if="props.rows.data.length === 0">
                            <td colspan="8" class="brand-listing-empty">
                                <i class="mdi mdi-tag-search-outline" aria-hidden="true"></i>
                                <strong>Belum ada pengajuan listing brand</strong>
                                <span>Pengajuan dari store akan tampil di sini.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="brand-listing-footer">
                <small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} pengajuan</small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </section>
    </div>
</template>

<style scoped>
.brand-listing-page {
    color: var(--app-ink);
}

.brand-listing-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.4rem;
    margin-bottom: 1rem;
    border-radius: 0.9rem;
    background: var(--app-hero-bg);
    color: var(--app-hero-text);
}

.brand-listing-header > div {
    max-width: 46rem;
}

.brand-listing-eyebrow {
    display: inline-block;
    margin-bottom: 0.35rem;
    color: var(--app-hero-eyebrow);
    font-size: 0.7rem;
    font-weight: 700;
}

.brand-listing-header h1 {
    margin: 0 0 0.35rem;
    font-size: clamp(1.3rem, 2vw, 1.6rem);
}

.brand-listing-header p {
    margin: 0;
    color: var(--app-hero-muted);
    font-size: 0.86rem;
}

.brand-listing-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.brand-listing-summary-card {
    padding: 0.85rem 1rem;
    border: 1px solid var(--app-border);
    border-radius: 0.7rem;
    background: var(--app-surface);
}

.brand-listing-summary-card span {
    display: block;
    color: var(--app-muted);
    font-size: 0.7rem;
    font-weight: 600;
}

.brand-listing-summary-card strong {
    display: block;
    margin-top: 0.2rem;
    font-size: 1.25rem;
    font-weight: 800;
}

.brand-listing-summary-card strong.is-warning {
    color: var(--app-warning-ink);
}

.brand-listing-summary-card strong.is-success {
    color: var(--app-accent-ink);
}

.brand-listing-summary-card strong.is-danger {
    color: var(--app-danger-ink);
}

.brand-listing-form-card,
.brand-listing-filter-card,
.brand-listing-table-card {
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--app-border);
    border-radius: 0.8rem;
    background: var(--app-surface);
}

.brand-listing-form-head,
.brand-listing-table-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
}

.brand-listing-form-head h2,
.brand-listing-filter-head h2,
.brand-listing-table-head h2 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 800;
}

.brand-listing-form-head p,
.brand-listing-table-head p {
    margin: 0.2rem 0 0;
    color: var(--app-muted);
    font-size: 0.78rem;
}

.brand-listing-filter-head {
    margin-bottom: 0.65rem;
}

.brand-listing-note {
    display: block;
    margin-top: 0.15rem;
    color: var(--app-muted);
    font-size: 0.7rem;
    line-height: 1.4;
}

.brand-listing-empty {
    padding: 2.5rem 1rem;
    color: var(--app-muted);
    text-align: center;
}

.brand-listing-empty i {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 1.8rem;
}

.brand-listing-empty strong {
    display: block;
    color: var(--app-ink);
    font-size: 0.9rem;
}

.brand-listing-empty span {
    display: block;
    margin-top: 0.2rem;
    font-size: 0.78rem;
}

.brand-listing-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding-top: 0.85rem;
}

.brand-listing-page .table th {
    color: var(--app-muted);
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}

.brand-listing-page .table td {
    font-size: 0.8rem;
    vertical-align: top;
}

@media (max-width: 767.98px) {
    .brand-listing-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .brand-listing-header {
        padding: 1.1rem;
    }
}

@media (max-width: 575.98px) {
    .brand-listing-summary {
        grid-template-columns: 1fr;
    }
}
</style>
