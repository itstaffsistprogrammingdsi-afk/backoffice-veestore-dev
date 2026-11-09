<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';

defineOptions({ layout: AppLayout });

interface StoreOption {
    id: string;
    name: string;
    code: string | null;
}

interface CategoryOption {
    value: string;
    label: string;
}

interface Row {
    id: string;
    category: string | null;
    created_at: string | null;
    store_name: string;
    store_code: string | null;
    product_name: string;
    product_code: string | null;
    product_category: string | null;
    condition_name: string | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    rows: Paginator<Row>;
    summary: {
        total_items: number;
        total_sample: number;
        total_gimmick: number;
        total_inventory: number;
        total_display: number;
    };
    stores: StoreOption[];
    categories: CategoryOption[];
    filters: {
        q: string;
        store_id: string;
        category: string;
        per_page: number;
    };
    urls: {
        index: string;
        mark: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const selection = ref<string[]>([]);
const processing = ref(false);

const form = reactive({
    q: props.filters.q ?? '',
    store_id: props.filters.store_id ?? '',
    category: props.filters.category ?? '',
    per_page: props.filters.per_page ?? 25,
});

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.code ? `${store.name} (${store.code})` : store.name,
})));

const summaryCards = computed(() => [
    { label: 'Sample', value: props.summary.total_sample, tone: 'is-info' },
    { label: 'Gimmick', value: props.summary.total_gimmick, tone: 'is-warning' },
    { label: 'Inventory', value: props.summary.total_inventory, tone: '' },
    { label: 'Display', value: props.summary.total_display, tone: '' },
]);

const allIds = computed(() => props.rows.data.map((row) => row.id));
const selectedCount = computed(() => selection.value.length);

const toggle = (id: string) => {
    selection.value = selection.value.includes(id)
        ? selection.value.filter((value) => value !== id)
        : [...selection.value, id];
};

const toggleAll = () => {
    selection.value = selection.value.length === allIds.value.length ? [] : [...allIds.value];
};

const applyFilters = () => {
    selection.value = [];
    router.get(props.urls.index, {
        q: form.q || undefined,
        store_id: form.store_id || undefined,
        category: form.category || undefined,
        per_page: form.per_page || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    form.q = '';
    form.store_id = '';
    form.category = '';
    form.per_page = 25;
    applyFilters();
};

const mark = (category: 'sample' | 'gimmick' | 'inventory' | 'display') => {
    if (selection.value.length === 0 || processing.value) return;

    processing.value = true;
    router.post(props.urls.mark, {
        ids: selection.value,
        category,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selection.value = [];
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const categoryClass = (category: string | null) => {
    if (category === 'sample') return 'bg-label-info';
    if (category === 'gimmick') return 'bg-label-warning';
    if (category === 'display') return 'bg-label-primary';

    return 'bg-label-secondary';
};

const categoryLabel = (category: string | null) => {
    if (category === 'sample') return 'Sample';
    if (category === 'gimmick') return 'Gimmick';
    if (category === 'display') return 'Display';
    if (category === 'inventory') return 'Inventory';

    return '-';
};

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
    <Head title="Sample & Gimmick" />

    <div class="sample-page">
        <header class="sample-header">
            <div>
                <span class="sample-eyebrow">Operasional / Toko</span>
                <h1>Sample & Gimmick</h1>
                <p>Tandai item stok toko sebagai sample atau gimmick tanpa mengubah stok fisik.</p>
            </div>
        </header>

        <div v-if="flash.success" class="alert alert-success py-2 small" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger py-2 small" role="alert">{{ flash.error }}</div>

        <section class="sample-summary" aria-label="Ringkasan sample dan gimmick">
            <div v-for="card in summaryCards" :key="card.label" class="sample-summary-card">
                <span>{{ card.label }}</span>
                <strong :class="card.tone">{{ card.value }}</strong>
            </div>
        </section>

        <section class="sample-filter" aria-labelledby="sample-filter-title">
            <h2 id="sample-filter-title">Filter item</h2>
            <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                <div class="col-md-4">
                    <label for="sample-q" class="form-label">Pencarian</label>
                    <input id="sample-q" v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Nama produk, kode, store..." />
                </div>
                <div class="col-md-3">
                    <label for="sample-store" class="form-label">Store</label>
                    <SearchableSelect id="sample-store" v-model="form.store_id" :options="storeOptions" placeholder="Semua store" search-placeholder="Cari store..." />
                </div>
                <div class="col-md-3">
                    <label for="sample-category" class="form-label">Kategori</label>
                    <select id="sample-category" v-model="form.category" class="form-select form-select-sm">
                        <option value="">Semua kategori</option>
                        <option v-for="option in props.categories" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sample-per-page" class="form-label">Per Halaman</label>
                    <select id="sample-per-page" v-model.number="form.per_page" class="form-select form-select-sm">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-filter me-1" aria-hidden="true"></i>Terapkan</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters">Reset</button>
                </div>
            </form>
        </section>

        <section class="sample-table-card" aria-labelledby="sample-table-title">
            <div class="sample-table-head">
                <div>
                    <h2 id="sample-table-title">Daftar Item</h2>
                    <p>{{ props.rows.total }} item sesuai filter.</p>
                </div>
                <div class="sample-actions">
                    <span class="sample-selected">{{ selectedCount }} dipilih</span>
                    <button class="btn btn-sm btn-outline-info" type="button" :disabled="selectedCount === 0 || processing" @click="mark('sample')">Tandai Sample</button>
                    <button class="btn btn-sm btn-outline-warning" type="button" :disabled="selectedCount === 0 || processing" @click="mark('gimmick')">Tandai Gimmick</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="selectedCount === 0 || processing" @click="mark('inventory')">Kembalikan ke Inventory</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="sample-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    :checked="selection.length === allIds.length && allIds.length > 0"
                                    aria-label="Pilih semua item"
                                    @change="toggleAll"
                                />
                            </th>
                            <th>Produk</th>
                            <th>Kode</th>
                            <th>Store</th>
                            <th>Kategori Produk</th>
                            <th>Kondisi</th>
                            <th>Status</th>
                            <th>Ditambahkan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.rows.data" :key="row.id">
                            <td class="sample-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    :checked="selection.includes(row.id)"
                                    :aria-label="`Pilih ${row.product_name}`"
                                    @change="toggle(row.id)"
                                />
                            </td>
                            <td class="fw-semibold">{{ row.product_name }}</td>
                            <td>{{ row.product_code ?? '-' }}</td>
                            <td>{{ row.store_code ? `${row.store_name} (${row.store_code})` : row.store_name }}</td>
                            <td>{{ row.product_category ?? '-' }}</td>
                            <td>{{ row.condition_name ?? '-' }}</td>
                            <td><span class="badge" :class="categoryClass(row.category)">{{ categoryLabel(row.category) }}</span></td>
                            <td class="text-nowrap">{{ formatDate(row.created_at) }}</td>
                        </tr>
                        <tr v-if="props.rows.data.length === 0">
                            <td colspan="8" class="sample-empty">
                                <i class="mdi mdi-flask-empty-outline" aria-hidden="true"></i>
                                <strong>Tidak ada item yang cocok</strong>
                                <span>Coba ubah filter atau pastikan stok toko sudah tersedia.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="sample-footer">
                <small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} item</small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </section>
    </div>
</template>

<style scoped>
.sample-page { color: var(--app-ink); }
.sample-header { padding: 1.4rem; margin-bottom: 1rem; border-radius: 0.9rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.sample-eyebrow { display: inline-block; margin-bottom: 0.35rem; color: var(--app-hero-eyebrow); font-size: 0.7rem; font-weight: 700; }
.sample-header h1 { margin: 0 0 0.35rem; font-size: clamp(1.3rem, 2vw, 1.6rem); }
.sample-header p { margin: 0; color: var(--app-hero-muted); font-size: 0.86rem; }
.sample-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.sample-summary-card { padding: 0.85rem 1rem; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface); }
.sample-summary-card span { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 600; }
.sample-summary-card strong { display: block; margin-top: 0.2rem; font-size: 1.25rem; font-weight: 800; }
.sample-summary-card strong.is-info { color: var(--app-info-ink); }
.sample-summary-card strong.is-warning { color: var(--app-warning-ink); }
.sample-filter, .sample-table-card { padding: 1rem; margin-bottom: 1rem; border: 1px solid var(--app-border); border-radius: 0.8rem; background: var(--app-surface); }
.sample-filter h2, .sample-table-head h2 { margin: 0 0 0.65rem; font-size: 0.95rem; font-weight: 800; }
.sample-table-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.85rem; }
.sample-table-head p { margin: 0.2rem 0 0; color: var(--app-muted); font-size: 0.78rem; }
.sample-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem; }
.sample-selected { color: var(--app-muted); font-size: 0.74rem; font-weight: 600; }
.sample-check { width: 2.4rem; text-align: center; }
.sample-page .table th { color: var(--app-muted); font-size: 0.72rem; font-weight: 700; white-space: nowrap; }
.sample-page .table td { font-size: 0.8rem; vertical-align: middle; }
.sample-empty { padding: 2.5rem 1rem; color: var(--app-muted); text-align: center; }
.sample-empty i { display: block; margin-bottom: 0.35rem; font-size: 1.8rem; }
.sample-empty strong { display: block; color: var(--app-ink); font-size: 0.9rem; }
.sample-empty span { display: block; margin-top: 0.2rem; font-size: 0.78rem; }
.sample-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding-top: 0.85rem; }
@media (max-width: 991.98px) { .sample-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575.98px) { .sample-summary { grid-template-columns: 1fr; } .sample-header { padding: 1.1rem; } }
</style>
