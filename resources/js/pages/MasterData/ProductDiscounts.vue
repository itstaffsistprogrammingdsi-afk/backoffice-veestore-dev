<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '../../components/PaginationLinks.vue';
import ModuleNav from '../../components/ModuleNav.vue';

defineOptions({ layout: AppLayout });

interface Option { id: string; name: string; code?: string | null }
interface DiscountOption { id: string; name: string; type: string; value: string | number; is_active: boolean | number }
interface Row {
    discount_id: string;
    product_id: string;
    applied_to: string;
    store_id: string[] | null;
    store_id_single: string | null;
    product_name: string;
    product_code: string | null;
    discount_name: string;
    discount_type: string;
    discount_value: string | number;
    is_active: boolean | number;
    store_name: string;
    store_code: string | null;
}
interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}
interface Props {
    products: Option[];
    discounts: DiscountOption[];
    stores: Option[];
    rows: Paginator<Row>;
    filters: { q: string; discount_id: string | null; store_id: string | null };
    appliedToOptions: string[];
    categories: Option[];
    urls: { index: string; store: string; update: string; destroy: string };
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const filters = reactive({
    q: props.filters.q ?? '',
    discount_id: props.filters.discount_id ?? '',
    store_id: props.filters.store_id ?? '',
});

const createForm = useForm({
    discount_id: '',
    all_products: false,
    product_id: [] as string[],
    applied_to: 'global',
    store_id: '',
});

const editing = ref<Row | null>(null);
const editForm = useForm({
    original_discount_id: '',
    original_product_id: '',
    original_store_id: '',
    discount_id: '',
    product_id: '',
    applied_to: 'global',
    store_id: '',
});

const apply = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        discount_id: filters.discount_id || undefined,
        store_id: filters.store_id || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    filters.q = '';
    filters.discount_id = '';
    filters.store_id = '';
    apply();
};

const submitCreate = () => {
    createForm.post(props.urls.store, {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const startEdit = (row: Row) => {
    editing.value = row;
    editForm.original_discount_id = row.discount_id;
    editForm.original_product_id = row.product_id;
    editForm.original_store_id = row.store_id_single ?? '';
    editForm.discount_id = row.discount_id;
    editForm.product_id = row.product_id;
    editForm.applied_to = row.applied_to;
    editForm.store_id = row.store_id_single ?? '';
};

const submitEdit = () => {
    editForm.put(props.urls.update, {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
};

const remove = (row: Row) => {
    if (!window.confirm('Hapus produk dari diskon ini?')) return;

    router.delete(props.urls.destroy, {
        data: {
            discount_id: row.discount_id,
            product_id: row.product_id,
            store_id: row.store_id_single,
        },
        preserveScroll: true,
    });
};

const formatNumber = (value: string | number) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));
</script>

<template>
    <Head title="Product Discounts" />
    <div class="pd-page">
        <div class="pd-header">
            <div>
                <span class="pd-eyebrow">Promo / Produk Diskon</span>
                <h1>Product Discounts</h1>
                <p>Atur produk yang mengikuti diskon dan cakupannya.</p>
            </div>
        </div>

        <ModuleNav module-key="promoModule" aria-label="Modul Promo" />

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card pd-card mb-3">
            <div class="card-header"><h6 class="mb-0">Tambah Produk Diskon</h6></div>
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="submitCreate">
                    <div class="col-md-4">
                        <label class="form-label">Diskon</label>
                        <select v-model="createForm.discount_id" class="form-select form-select-sm" required>
                            <option value="">Pilih diskon</option>
                            <option v-for="discount in discounts" :key="discount.id" :value="discount.id">{{ discount.name }} ({{ discount.type }} {{ formatNumber(discount.value) }})</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Berlaku Untuk</label>
                        <select v-model="createForm.applied_to" class="form-select form-select-sm">
                            <option v-for="option in appliedToOptions" :key="option" :value="option">{{ option }}</option>
                        </select>
                    </div>
                    <div v-if="createForm.applied_to === 'store'" class="col-md-3">
                        <label class="form-label">Store</label>
                        <select v-model="createForm.store_id" class="form-select form-select-sm">
                            <option value="">Pilih store</option>
                            <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check"><input id="all-products" v-model="createForm.all_products" class="form-check-input" type="checkbox" /><label class="form-check-label" for="all-products">Semua Produk</label></div>
                    </div>
                    <div v-if="!createForm.all_products" class="col-md-12">
                        <label class="form-label">Produk</label>
                        <select v-model="createForm.product_id" class="form-select form-select-sm" multiple size="5">
                            <option v-for="product in products" :key="product.id" :value="product.id">{{ product.code ? `${product.code} - ` : '' }}{{ product.name }}</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2 mt-2"><button class="btn btn-sm btn-primary" type="submit" :disabled="createForm.processing">Simpan</button></div>
                </form>
            </div>
        </div>

        <div class="card pd-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="apply">
                    <div class="col-md-4"><label class="form-label">Cari Produk</label><input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nama atau kode produk" /></div>
                    <div class="col-md-3"><label class="form-label">Diskon</label><select v-model="filters.discount_id" class="form-select form-select-sm"><option value="">Semua</option><option v-for="discount in discounts" :key="discount.id" :value="discount.id">{{ discount.name }}</option></select></div>
                    <div class="col-md-3"><label class="form-label">Store</label><select v-model="filters.store_id" class="form-select form-select-sm"><option value="">Semua</option><option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option></select></div>
                    <div class="col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary" type="submit">Filter</button><button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters">Reset</button></div>
                </form>
            </div>
        </div>

        <div class="card pd-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Produk</th><th>Diskon</th><th>Berlaku</th><th>Store</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <template v-for="row in rows.data" :key="`${row.discount_id}-${row.product_id}-${row.store_id_single ?? 'all'}`">
                            <tr>
                                <td><div class="fw-semibold">{{ row.product_name }}</div><small class="text-muted">{{ row.product_code ?? '-' }}</small></td>
                                <td>{{ row.discount_name }} <small class="text-muted">({{ row.discount_type }} {{ formatNumber(row.discount_value) }})</small></td>
                                <td>{{ row.applied_to }}</td>
                                <td>{{ row.store_name }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary" type="button" @click="startEdit(row)"><i class="mdi mdi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" type="button" @click="remove(row)"><i class="mdi mdi-delete"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="editing && editing.discount_id === row.discount_id && editing.product_id === row.product_id">
                                <td colspan="5" class="pd-edit">
                                    <form class="row g-2 align-items-end" @submit.prevent="submitEdit">
                                        <div class="col-md-3"><label class="form-label">Diskon Baru</label><select v-model="editForm.discount_id" class="form-select form-select-sm"><option v-for="discount in discounts" :key="discount.id" :value="discount.id">{{ discount.name }}</option></select></div>
                                        <div class="col-md-2"><label class="form-label">Berlaku</label><select v-model="editForm.applied_to" class="form-select form-select-sm"><option v-for="option in appliedToOptions" :key="option" :value="option">{{ option }}</option></select></div>
                                        <div class="col-md-3"><label class="form-label">Store</label><select v-model="editForm.store_id" class="form-select form-select-sm"><option value="">Tanpa store</option><option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option></select></div>
                                        <div class="col-md-2 d-flex gap-2"><button class="btn btn-sm btn-success" type="submit">Simpan</button><button class="btn btn-sm btn-outline-secondary" type="button" @click="editing = null">Batal</button></div>
                                    </form>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="rows.data.length === 0"><td colspan="5" class="pd-empty">Belum ada produk diskon.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2"><small class="text-muted">Menampilkan {{ rows.from ?? 0 }} - {{ rows.to ?? 0 }} dari {{ rows.total }} data</small><PaginationLinks :links="rows.links" /></div>
        </div>
    </div>
</template>

<style scoped>
.pd-page { color: var(--app-ink); }
.pd-header { padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.pd-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.pd-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; color: var(--app-hero-text); }
.pd-header p { margin: 0; color: var(--app-hero-muted); }
.pd-card { border: 1px solid var(--app-border); border-radius: 0.9rem; box-shadow: var(--app-shadow); background: var(--app-surface); }
.pd-card th { color: var(--app-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.pd-edit { background: var(--app-surface-2); }
.pd-empty { padding: 2rem; color: var(--app-muted); text-align: center; }
</style>
