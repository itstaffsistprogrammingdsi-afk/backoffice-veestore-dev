<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ModuleNav from '@/components/ModuleNav.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

defineOptions({ layout: AppLayout });

interface Option { id: string; name: string; code?: string | null }
interface PromoRow {
    id: string;
    name: string;
    code: string | null;
    start_date: string | null;
    end_date: string | null;
    all_store: boolean | number;
}
interface Props {
    stores: Option[];
    products: Option[];
    recentPromos: PromoRow[];
    voucherContexts: string[];
    canCreateVoucher: boolean;
    urls: { index: string; store: string };
}

const props = defineProps<Props>();
const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
    hint: store.code ?? undefined,
})));

const productOptions = computed(() => props.products.map((product) => ({
    value: product.id,
    label: `${product.code ? `${product.code} - ` : ''}${product.name}`,
})));

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    promo_name: '',
    promo_code: '',
    promo_description: '',
    start_date: today,
    end_date: today,
    all_stores: false,
    store_ids: [] as string[],
    discount_name: '',
    discount_type: 'percentage',
    discount_value: '',
    min_purchase: '',
    max_discount: '',
    all_products: false,
    product_ids: [] as string[],
    create_voucher: props.canCreateVoucher,
    voucher_name: '',
    voucher_type: '',
    voucher_value: '',
    voucher_min_purchase: '',
    voucher_max_discount: '',
    voucher_usage_limit: '',
    voucher_distribution_type: 'public',
    voucher_contexts: ['store_checkout'] as string[],
    voucher_is_active: true,
});

const voucherContextLabels: Record<string, string> = {
    ecommerce_checkout: 'Ecommerce Checkout',
    ecommerce_shipping: 'Ecommerce Shipping',
    store_checkout: 'Store Checkout',
    store_customer_return: 'Retur Customer Toko',
};

const submit = () => {
    form.post(props.urls.store, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)) : '-';
</script>

<template>
    <Head title="Pengaturan Promo" />
    <div class="promo-page">
        <div class="promo-header">
            <div>
                <span class="promo-eyebrow">Promo / Setup</span>
                <h1>Pengaturan Promo</h1>
                <p>Buat promo, diskon, target produk, dan voucher dalam satu alur.</p>
            </div>
        </div>

        <ModuleNav module-key="promoModule" aria-label="Modul Promo" />

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <form @submit.prevent="submit">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card promo-card h-100">
                        <div class="card-header"><h6 class="mb-0">Promo</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Nama Promo</label><input v-model="form.promo_name" class="form-control" required /><div v-if="form.errors.promo_name" class="text-danger small">{{ form.errors.promo_name }}</div></div>
                                <div class="col-md-6"><label class="form-label">Kode Promo</label><input v-model="form.promo_code" class="form-control" required /><div v-if="form.errors.promo_code" class="text-danger small">{{ form.errors.promo_code }}</div></div>
                                <div class="col-12"><label class="form-label">Deskripsi</label><textarea v-model="form.promo_description" class="form-control" rows="2"></textarea></div>
                                <div class="col-md-6"><label class="form-label">Mulai</label><input v-model="form.start_date" class="form-control" type="date" required /></div>
                                <div class="col-md-6"><label class="form-label">Selesai</label><input v-model="form.end_date" class="form-control" type="date" required /></div>
                                <div class="col-12">
                                    <div class="form-check"><input id="all-stores" v-model="form.all_stores" class="form-check-input" type="checkbox" /><label class="form-check-label" for="all-stores">Semua Store</label></div>
                                </div>
                                <div v-if="!form.all_stores" class="col-12">
                                    <label class="form-label">Stores</label>
                                    <SearchableSelect
                                        v-model="form.store_ids"
                                        :options="storeOptions"
                                        placeholder="Pilih satu atau beberapa store"
                                        search-placeholder="Cari store..."
                                        multiple
                                        size="md"
                                    />
                                    <div v-if="form.errors.store_ids" class="text-danger small">{{ form.errors.store_ids }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card promo-card h-100">
                        <div class="card-header"><h6 class="mb-0">Diskon</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Nama Diskon</label><input v-model="form.discount_name" class="form-control" required /></div>
                                <div class="col-md-6"><label class="form-label">Tipe</label><select v-model="form.discount_type" class="form-select"><option value="percentage">Percentage</option><option value="fixed">Fixed</option></select></div>
                                <div class="col-md-4"><label class="form-label">Nilai</label><input v-model="form.discount_value" class="form-control" type="number" min="0" required /></div>
                                <div class="col-md-4"><label class="form-label">Min Belanja</label><input v-model="form.min_purchase" class="form-control" type="number" min="0" /></div>
                                <div class="col-md-4"><label class="form-label">Max Diskon</label><input v-model="form.max_discount" class="form-control" type="number" min="0" /></div>
                                <div class="col-12">
                                    <div class="form-check"><input id="all-products" v-model="form.all_products" class="form-check-input" type="checkbox" /><label class="form-check-label" for="all-products">Semua Produk</label></div>
                                </div>
                                <div v-if="!form.all_products" class="col-12">
                                    <label class="form-label">Produk</label>
                                    <SearchableSelect
                                        v-model="form.product_ids"
                                        :options="productOptions"
                                        placeholder="Pilih satu atau beberapa produk"
                                        search-placeholder="Cari nama atau kode produk..."
                                        multiple
                                        size="md"
                                    />
                                    <div v-if="form.errors.product_ids" class="text-danger small">{{ form.errors.product_ids }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card promo-card mt-3">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h6 class="mb-0">Voucher</h6>
                    <div class="form-check mb-0">
                        <input id="create-voucher" v-model="form.create_voucher" class="form-check-input" type="checkbox" :disabled="!props.canCreateVoucher" />
                        <label class="form-check-label" for="create-voucher">Buat voucher sekaligus</label>
                    </div>
                </div>
                <div class="card-body">
                    <p v-if="!props.canCreateVoucher" class="text-muted small mb-0">Anda tidak memiliki izin membuat voucher.</p>
                    <template v-else-if="form.create_voucher">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nama Voucher</label>
                                <input v-model="form.voucher_name" class="form-control" placeholder="Mengikuti nama promo" />
                                <div v-if="form.errors.voucher_name" class="text-danger small">{{ form.errors.voucher_name }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipe Voucher</label>
                                <select v-model="form.voucher_type" class="form-select">
                                    <option value="">Ikuti tipe diskon</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nilai Voucher</label>
                                <input v-model="form.voucher_value" class="form-control" type="number" min="0" placeholder="Ikuti nilai diskon" />
                                <div v-if="form.errors.voucher_value" class="text-danger small">{{ form.errors.voucher_value }}</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Min Belanja</label>
                                <input v-model="form.voucher_min_purchase" class="form-control" type="number" min="0" placeholder="Ikuti min belanja" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Max Diskon</label>
                                <input v-model="form.voucher_max_discount" class="form-control" type="number" min="0" placeholder="Ikuti max diskon" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Batas Pemakaian</label>
                                <input v-model="form.voucher_usage_limit" class="form-control" type="number" min="1" :required="form.voucher_distribution_type === 'unique_code'" />
                                <div v-if="form.errors.voucher_usage_limit" class="text-danger small">{{ form.errors.voucher_usage_limit }}</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Distribusi</label>
                                <select v-model="form.voucher_distribution_type" class="form-select">
                                    <option value="public">Public</option>
                                    <option value="unique_code">Unique Code</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Konteks Voucher</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div v-for="context in props.voucherContexts" :key="context" class="form-check">
                                        <input :id="`voucher-context-${context}`" v-model="form.voucher_contexts" class="form-check-input" type="checkbox" :value="context" />
                                        <label class="form-check-label" :for="`voucher-context-${context}`">{{ voucherContextLabels[context] ?? context }}</label>
                                    </div>
                                </div>
                                <div v-if="form.errors.voucher_contexts" class="text-danger small">{{ form.errors.voucher_contexts }}</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input id="voucher-active" v-model="form.voucher_is_active" class="form-check-input" type="checkbox" />
                                    <label class="form-check-label" for="voucher-active">Voucher aktif</label>
                                </div>
                            </div>
                        </div>
                    </template>
                    <p v-else class="text-muted small mb-0">Aktifkan untuk membuat voucher dengan data promo dan diskon di atas.</p>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-primary" type="submit" :disabled="form.processing">
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Promo' }}
                </button>
            </div>
        </form>

        <div class="card promo-card mt-3">
            <div class="card-header"><h6 class="mb-0">Promo Terbaru</h6></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Nama</th><th>Kode</th><th>Mulai</th><th>Selesai</th><th>Semua Store</th></tr></thead>
                    <tbody>
                        <tr v-for="promo in recentPromos" :key="promo.id"><td>{{ promo.name }}</td><td>{{ promo.code }}</td><td>{{ formatDate(promo.start_date) }}</td><td>{{ formatDate(promo.end_date) }}</td><td>{{ promo.all_store ? 'Ya' : 'Tidak' }}</td></tr>
                        <tr v-if="recentPromos.length === 0"><td colspan="5" class="promo-empty">Belum ada promo.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.promo-page { color: var(--app-ink); }
.promo-header { padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.promo-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.promo-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; color: var(--app-hero-text); }
.promo-header p { margin: 0; color: var(--app-hero-muted); }
.promo-card { border: 1px solid var(--app-border); border-radius: 0.9rem; box-shadow: var(--app-shadow); background: var(--app-surface); }
.promo-card th { color: var(--app-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.promo-empty { padding: 2rem; color: var(--app-muted); text-align: center; }
</style>
