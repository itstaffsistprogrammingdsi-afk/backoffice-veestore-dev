<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '../../components/PaginationLinks.vue';

defineOptions({ layout: AppLayout });

interface ProductOption { id: string; name: string; code: string | null }
interface PriceDetail { id: string; price_type: string; price: string | number; valid_from: string | null; valid_to: string | null }
interface PriceRow {
    product_id: string;
    product_name: string;
    product_code: string | null;
    base_price: string | number;
    active_count: number;
    prices_by_type: Record<string, PriceDetail | null>;
}
interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}
interface Props {
    prices: Paginator<PriceRow>;
    products: ProductOption[];
    priceTypes: string[];
    displayTypes: string[];
    allPriceDetails: Record<string, PriceDetail[]>;
    filters: { q: string; price_type: string | null; from: string | null; to: string | null };
    urls: { index: string; store: string; update: string; destroy: string };
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const filters = reactive({
    q: props.filters.q ?? '',
    price_type: props.filters.price_type ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const createForm = useForm({
    product_id: '',
    valid_from: new Date().toISOString().slice(0, 10),
    valid_to: '',
    prices: { base: '', store: '', ecommerce: '' },
});

const editingId = ref<string | null>(null);
const editForm = useForm({
    product_id: '',
    valid_from: '',
    valid_to: '',
    prices: { base: '', store: '', ecommerce: '' },
});

const apply = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        price_type: filters.price_type || undefined,
        from: filters.from || undefined,
        to: filters.to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    filters.q = '';
    filters.price_type = '';
    filters.from = '';
    filters.to = '';
    apply();
};

const submitCreate = () => {
    createForm.post(props.urls.store, {
        preserveScroll: true,
        onSuccess: () => createForm.reset('product_id', 'valid_to', 'prices'),
    });
};

const startEdit = (row: PriceRow) => {
    const details = props.allPriceDetails[row.product_id] ?? [];
    const first = details[0];

    if (!first) return;

    editingId.value = first.id;
    editForm.product_id = row.product_id;
    editForm.valid_from = first.valid_from?.slice(0, 10) ?? '';
    editForm.valid_to = first.valid_to?.slice(0, 10) ?? '';
    editForm.prices = {
        base: String(row.prices_by_type.base?.price ?? ''),
        store: String(row.prices_by_type.store?.price ?? ''),
        ecommerce: String(row.prices_by_type.ecommerce?.price ?? ''),
    };
};

const submitEdit = () => {
    if (!editingId.value) return;

    editForm.put(props.urls.update.replace('__ID__', editingId.value), {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
};

const remove = (row: PriceRow) => {
    const details = props.allPriceDetails[row.product_id] ?? [];
    const first = details[0];

    if (!first || !window.confirm(`Hapus semua harga untuk ${row.product_name}?`)) return;

    router.delete(props.urls.destroy.replace('__ID__', first.id), { preserveScroll: true });
};

const formatCurrency = (value: string | number | null | undefined) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value ?? 0));

const priceOf = (row: PriceRow, type: string) => row.prices_by_type[type]?.price ?? null;
</script>

<template>
    <Head title="Product Prices" />
    <div class="price-page">
        <div class="price-header">
            <div>
                <span class="price-eyebrow">Master Data</span>
                <h1>Product Prices</h1>
                <p>Kelola harga base, store, dan ecommerce per produk.</p>
            </div>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card price-card mb-3">
            <div class="card-header"><h6 class="mb-0">Tambah Harga Produk</h6></div>
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="submitCreate">
                    <div class="col-md-4">
                        <label class="form-label">Produk</label>
                        <select v-model="createForm.product_id" class="form-select form-select-sm" required>
                            <option value="">Pilih produk</option>
                            <option v-for="product in products" :key="product.id" :value="product.id">{{ product.code ? `${product.code} - ` : '' }}{{ product.name }}</option>
                        </select>
                        <div v-if="createForm.errors.product_id" class="text-danger small">{{ createForm.errors.product_id }}</div>
                    </div>
                    <div class="col-md-2"><label class="form-label">Berlaku Dari</label><input v-model="createForm.valid_from" class="form-control form-control-sm" type="date" required /></div>
                    <div class="col-md-2"><label class="form-label">Berlaku Sampai</label><input v-model="createForm.valid_to" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-1"><label class="form-label">Base</label><input v-model="createForm.prices.base" class="form-control form-control-sm" type="number" min="0" required /></div>
                    <div class="col-md-1"><label class="form-label">Store</label><input v-model="createForm.prices.store" class="form-control form-control-sm" type="number" min="0" required /></div>
                    <div class="col-md-2"><label class="form-label">Ecommerce</label><input v-model="createForm.prices.ecommerce" class="form-control form-control-sm" type="number" min="0" required /></div>
                    <div class="col-12 d-flex gap-2 mt-2"><button class="btn btn-sm btn-primary" type="submit" :disabled="createForm.processing">Simpan Harga</button></div>
                </form>
            </div>
        </div>

        <div class="card price-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="apply">
                    <div class="col-md-3"><label class="form-label">Cari</label><input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nama atau kode produk" /></div>
                    <div class="col-md-2"><label class="form-label">Tipe</label><select v-model="filters.price_type" class="form-select form-select-sm"><option value="">Semua</option><option v-for="type in priceTypes" :key="type" :value="type">{{ type }}</option></select></div>
                    <div class="col-md-2"><label class="form-label">Dari</label><input v-model="filters.from" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label class="form-label">Sampai</label><input v-model="filters.to" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary" type="submit">Filter</button><button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters">Reset</button></div>
                </form>
            </div>
        </div>

        <div class="card price-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Produk</th><th class="text-end">Base</th><th class="text-end">Store</th><th class="text-end">Ecommerce</th><th class="text-end">Harga Aktif</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <template v-for="row in prices.data" :key="row.product_id">
                            <tr>
                                <td><div class="fw-semibold">{{ row.product_name }}</div><small class="text-muted">{{ row.product_code ?? '-' }}</small></td>
                                <td class="text-end">{{ formatCurrency(priceOf(row, 'base')) }}</td>
                                <td class="text-end">{{ formatCurrency(priceOf(row, 'store')) }}</td>
                                <td class="text-end">{{ formatCurrency(priceOf(row, 'ecommerce')) }}</td>
                                <td class="text-end">{{ row.active_count }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary" type="button" @click="startEdit(row)"><i class="mdi mdi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" type="button" @click="remove(row)"><i class="mdi mdi-delete"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="editingId && editingId === (props.allPriceDetails[row.product_id]?.[0]?.id ?? '')">
                                <td colspan="6" class="price-edit">
                                    <form class="row g-2 align-items-end" @submit.prevent="submitEdit">
                                        <div class="col-md-2"><label class="form-label">Dari</label><input v-model="editForm.valid_from" class="form-control form-control-sm" type="date" required /></div>
                                        <div class="col-md-2"><label class="form-label">Sampai</label><input v-model="editForm.valid_to" class="form-control form-control-sm" type="date" /></div>
                                        <div class="col-md-2"><label class="form-label">Base</label><input v-model="editForm.prices.base" class="form-control form-control-sm" type="number" min="0" required /></div>
                                        <div class="col-md-2"><label class="form-label">Store</label><input v-model="editForm.prices.store" class="form-control form-control-sm" type="number" min="0" required /></div>
                                        <div class="col-md-2"><label class="form-label">Ecommerce</label><input v-model="editForm.prices.ecommerce" class="form-control form-control-sm" type="number" min="0" required /></div>
                                        <div class="col-md-2 d-flex gap-2"><button class="btn btn-sm btn-success" type="submit">Simpan</button><button class="btn btn-sm btn-outline-secondary" type="button" @click="editingId = null">Batal</button></div>
                                    </form>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="prices.data.length === 0"><td colspan="6" class="price-empty">Belum ada harga produk.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2"><small class="text-muted">Menampilkan {{ prices.from ?? 0 }} - {{ prices.to ?? 0 }} dari {{ prices.total }} produk</small><PaginationLinks :links="prices.links" /></div>
        </div>
    </div>
</template>

<style scoped>
.price-page { --price-ink: var(--app-ink); --price-border: var(--app-border); --price-muted: var(--app-muted); color: var(--price-ink); }
.price-header { padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.price-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: #f9a8d4; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.price-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; color: var(--app-hero-text); }
.price-header p { margin: 0; color: var(--app-hero-muted); }
.price-card { border: 1px solid var(--price-border); border-radius: 0.9rem; box-shadow: var(--app-shadow); background: var(--app-surface); }
.price-card th { color: var(--price-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.price-edit { background: var(--app-surface-2); }
.price-empty { padding: 2rem; color: var(--price-muted); text-align: center; }
</style>
