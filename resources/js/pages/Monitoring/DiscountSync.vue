<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { formatCurrency, formatNumber } from '@/composables/useFormatters';

defineOptions({ layout: AppLayout });

interface DiscountItem {
    id: string;
    name: string;
    type: string;
    value: number;
    max_discount: number | null;
    is_active: boolean;
    has_pos: boolean;
    has_ecommerce: boolean;
    product_count: number;
}

interface DiscountGroup {
    group_key: string;
    promo_id: string | null;
    promo_name: string;
    promo_code: string;
    discount_count: number;
    product_count: number;
    has_pos: boolean;
    has_ecommerce: boolean;
    has_global: boolean;
    has_unmapped: boolean;
    has_active: boolean;
    max_cap: number | null;
    pos_cap: number | null;
    ecommerce_cap: number | null;
    max_percent: number | null;
    max_fixed: number | null;
    discount_names: string;
    last_updated: string | null;
    mismatch: boolean;
    channel_label: string;
    discounts: DiscountItem[];
    ecommerce_drift: number;
    pos_cap_violations: number;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Summary {
    total_groups: number;
    mismatch_groups: number;
    pos_only_groups: number;
    ecommerce_only_groups: number;
    global_groups: number;
    unmapped_groups: number;
    active_groups: number;
    total_discounts: number;
    total_products: number;
    ecommerce_drift: number;
    pos_cap_violations: number;
}

interface Props {
    rows: Paginator<DiscountGroup>;
    summary: Summary;
    filters: {
        q: string;
        channel: string | null;
        active: string | null;
        per_page: number;
    };
    channelOptions: Array<{ value: string; label: string }>;
    activeOptions: Array<{ value: string; label: string }>;
    urls: {
        index: string;
        sync: string;
        discounts: string;
    };
}

const props = defineProps<Props>();

const filters = reactive({
    q: props.filters.q ?? '',
    channel: props.filters.channel ?? '',
    active: props.filters.active ?? '',
});

const expanded = ref<Record<string, boolean>>({});
const syncOpen = ref(false);
const syncGroup = ref<DiscountGroup | null>(null);

const form = useForm({
    discount_ids: [] as string[],
    target_max_discount: '',
    channel_scope: 'keep',
});

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        channel: filters.channel || undefined,
        active: filters.active || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    filters.q = '';
    filters.channel = '';
    filters.active = '';
    applyFilters();
};

const toggleDetail = (groupKey: string) => {
    expanded.value[groupKey] = !expanded.value[groupKey];
};

const channelClass = (row: DiscountGroup) => {
    if (row.has_pos && row.has_ecommerce) return 'bg-label-primary';
    if (row.has_pos) return 'bg-label-info';
    if (row.has_ecommerce) return 'bg-label-warning';

    return 'bg-label-secondary';
};

const capLabel = (value: number | null) => value === null ? 'Tanpa batas' : formatCurrency(value);

const openSync = (row: DiscountGroup) => {
    syncGroup.value = row;
    form.clearErrors();
    form.discount_ids = row.discounts.filter((discount) => discount.type === 'percentage').map((discount) => discount.id);
    form.target_max_discount = row.max_cap !== null ? String(row.max_cap) : '';
    form.channel_scope = 'keep';
    syncOpen.value = true;
};

const closeSync = () => {
    syncOpen.value = false;
    syncGroup.value = null;
    form.clearErrors();
};

const useCap = (value: number | null) => {
    form.target_max_discount = value !== null ? String(value) : '';
};

const submitSync = () => {
    form.transform((data) => ({
        ...data,
        target_max_discount: data.target_max_discount === '' ? null : Number(data.target_max_discount),
    })).post(props.urls.sync, {
        preserveScroll: true,
        onSuccess: () => closeSync(),
    });
};

const syncableDiscounts = computed(() => (syncGroup.value?.discounts ?? []).filter((discount) => discount.type === 'percentage'));

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && syncOpen.value) {
        closeSync();
    }
};

watch(syncOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Head title="Sinkronisasi Max Diskon" />

    <div class="sync-page">
        <section class="sync-header">
            <div>
                <span class="sync-eyebrow">Promo / Diskon</span>
                <h1>Sinkronisasi Max Diskon POS & E-commerce</h1>
                <p>Pastikan batas maksimal diskon konsisten di POS dan e-commerce, lalu sinkronkan dalam satu langkah.</p>
            </div>
            <div class="sync-header-actions">
                <Link :href="props.urls.discounts" class="sync-button sync-button-light">
                    <i class="mdi mdi-percent-outline me-1"></i>Master Diskon
                </Link>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sync-summary">
                    <span class="sync-summary-icon"><i class="mdi mdi-format-list-group"></i></span>
                    <div><span>Grup Diskon</span><strong>{{ formatNumber(props.summary.total_groups) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sync-summary sync-summary-danger">
                    <span class="sync-summary-icon"><i class="mdi mdi-alert-circle-outline"></i></span>
                    <div><span>Max Berbeda</span><strong>{{ formatNumber(props.summary.mismatch_groups) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sync-summary sync-summary-warning">
                    <span class="sync-summary-icon"><i class="mdi mdi-store-outline"></i></span>
                    <div><span>Hanya POS</span><strong>{{ formatNumber(props.summary.pos_only_groups) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sync-summary sync-summary-info">
                    <span class="sync-summary-icon"><i class="mdi mdi-cart-outline"></i></span>
                    <div><span>Hanya E-commerce</span><strong>{{ formatNumber(props.summary.ecommerce_only_groups) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sync-summary">
                    <span class="sync-summary-icon"><i class="mdi mdi-database-alert-outline"></i></span>
                    <div><span>Drift E-commerce</span><strong>{{ formatNumber(props.summary.ecommerce_drift) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="sync-summary sync-summary-danger">
                    <span class="sync-summary-icon"><i class="mdi mdi-shield-alert-outline"></i></span>
                    <div><span>Pelanggaran Cap POS</span><strong>{{ formatNumber(props.summary.pos_cap_violations) }}</strong></div>
                </div>
            </div>
        </div>

        <div class="card sync-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Cari promo atau diskon</label>
                        <input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nama promo, kode, atau nama diskon..." />
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <label class="form-label">Cakupan Kanal</label>
                        <select v-model="filters.channel" class="form-select form-select-sm">
                            <option v-for="option in props.channelOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <label class="form-label">Status Diskon</label>
                        <select v-model="filters.active" class="form-select form-select-sm">
                            <option v-for="option in props.activeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card sync-card">
            <div class="sync-card-header">
                <div>
                    <h2>Kepatuhan Max Diskon</h2>
                    <p>Drift e-commerce dihitung dari snapshot diskon 90 hari terakhir. Pelanggaran cap POS dihitung dari transaksi yang diskonnya melebihi cap master.</p>
                </div>
                <span class="sync-card-meta">{{ formatNumber(props.rows.total) }} grup</span>
            </div>

            <div v-if="props.rows.data.length === 0" class="sync-empty">
                <i class="mdi mdi-sync"></i>
                <strong>Tidak ada grup diskon</strong>
                <span>Coba ubah kata kunci atau filter.</span>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 sync-table">
                    <thead>
                        <tr>
                            <th>Promo / Diskon</th>
                            <th>Cakupan</th>
                            <th class="text-end">Max POS</th>
                            <th class="text-end">Max E-commerce</th>
                            <th class="text-end">Drift</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="row in props.rows.data" :key="row.group_key">
                            <tr>
                                <td>
                                    <div class="sync-main">
                                        <strong>{{ row.promo_name }}</strong>
                                        <small>{{ row.promo_code !== '-' ? row.promo_code : 'Tanpa kode promo' }} · {{ row.discount_count }} diskon · {{ row.product_count }} produk</small>
                                        <small v-if="row.mismatch" class="sync-warning-text"><i class="mdi mdi-alert-outline me-1"></i>Max diskon POS dan e-commerce berbeda.</small>
                                        <small v-else-if="row.has_unmapped" class="sync-muted-text">Sebagian diskon belum dipetakan ke produk.</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" :class="channelClass(row)">{{ row.channel_label }}</span>
                                    <small class="sync-subvalue">{{ row.has_active ? 'Aktif' : 'Tidak aktif' }}</small>
                                </td>
                                <td class="text-end">
                                    <strong>{{ capLabel(row.pos_cap) }}</strong>
                                    <small v-if="row.max_percent !== null" class="sync-subvalue">{{ formatNumber(row.max_percent) }}%</small>
                                </td>
                                <td class="text-end">
                                    <strong>{{ capLabel(row.ecommerce_cap) }}</strong>
                                    <small v-if="row.max_percent !== null" class="sync-subvalue">{{ formatNumber(row.max_percent) }}%</small>
                                </td>
                                <td class="text-end">
                                    <strong :class="{ 'text-danger': row.ecommerce_drift + row.pos_cap_violations > 0 }">{{ formatNumber(row.ecommerce_drift + row.pos_cap_violations) }}</strong>
                                    <small class="sync-subvalue">{{ formatNumber(row.ecommerce_drift) }} e-com · {{ formatNumber(row.pos_cap_violations) }} POS</small>
                                </td>
                                <td class="text-end">
                                    <div class="sync-actions">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="toggleDetail(row.group_key)">
                                            <i class="mdi" :class="expanded[row.group_key] ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>Detail
                                        </button>
                                        <button class="btn btn-sm btn-primary" type="button" :disabled="!row.discounts.some((discount) => discount.type === 'percentage')" @click="openSync(row)">
                                            <i class="mdi mdi-sync me-1"></i>Samakan
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="expanded[row.group_key]" class="sync-detail-row">
                                <td colspan="6">
                                    <div class="sync-detail-wrap">
                                        <table class="table table-sm mb-0 sync-detail-table">
                                            <thead>
                                                <tr><th>Diskon</th><th>Tipe</th><th class="text-end">Nilai</th><th class="text-end">Max Diskon</th><th>Cakupan</th><th>Status</th></tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="discount in row.discounts" :key="discount.id">
                                                    <td><strong>{{ discount.name }}</strong><small>{{ discount.product_count }} produk</small></td>
                                                    <td>{{ discount.type === 'percentage' ? 'Persentase' : 'Nominal' }}</td>
                                                    <td class="text-end">{{ discount.type === 'percentage' ? `${formatNumber(discount.value)}%` : formatCurrency(discount.value) }}</td>
                                                    <td class="text-end">{{ capLabel(discount.max_discount) }}</td>
                                                    <td>
                                                        <span v-if="discount.has_pos && discount.has_ecommerce" class="badge bg-label-primary">POS + E-commerce</span>
                                                        <span v-else-if="discount.has_pos" class="badge bg-label-info">POS</span>
                                                        <span v-else-if="discount.has_ecommerce" class="badge bg-label-warning">E-commerce</span>
                                                        <span v-else class="badge bg-label-secondary">Belum dipetakan</span>
                                                    </td>
                                                    <td><span class="badge" :class="discount.is_active ? 'bg-label-success' : 'bg-label-secondary'">{{ discount.is_active ? 'Aktif' : 'Tidak aktif' }}</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="sync-footer">
                <small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} grup</small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div v-if="syncOpen && syncGroup" class="sync-backdrop" @click.self="closeSync">
            <div class="sync-modal" role="dialog" aria-modal="true" aria-labelledby="sync-modal-title">
                <div class="sync-modal-header">
                    <div>
                        <h5 id="sync-modal-title" class="mb-0">Sinkronkan Max Diskon</h5>
                        <small>{{ syncGroup.promo_name }} · {{ syncGroup.promo_code }}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup sinkronisasi" @click="closeSync"><i class="mdi mdi-close"></i></button>
                </div>

                <form @submit.prevent="submitSync">
                    <div class="sync-modal-body">
                        <div class="sync-callout">
                            <i class="mdi mdi-information-outline"></i>
                            <p>Nilai max diskon akan diterapkan ke semua diskon persentase pada grup ini. Cakupan kanal dapat diubah agar POS dan e-commerce memakai aturan yang sama.</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Diskon yang Disinkronkan</label>
                            <ul class="sync-discount-list">
                                <li v-for="discount in syncableDiscounts" :key="discount.id">
                                    <span>{{ discount.name }}</span>
                                    <small>{{ discount.type === 'percentage' ? `${formatNumber(discount.value)}%` : formatCurrency(discount.value) }} · max saat ini {{ capLabel(discount.max_discount) }}</small>
                                </li>
                            </ul>
                            <div v-if="syncableDiscounts.length === 0" class="invalid-feedback d-block">Tidak ada diskon persentase pada grup ini.</div>
                            <div v-if="form.errors.discount_ids" class="invalid-feedback d-block">{{ form.errors.discount_ids }}</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="target-max-discount">Max Diskon Baru</label>
                                <input id="target-max-discount" v-model="form.target_max_discount" class="form-control form-control-sm" type="number" min="0" step="100" placeholder="Kosongkan untuk tanpa batas" autofocus />
                                <div class="sync-quick-actions">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="useCap(syncGroup.pos_cap)">Ikuti POS</button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="useCap(syncGroup.ecommerce_cap)">Ikuti E-commerce</button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="useCap(null)">Tanpa batas</button>
                                </div>
                                <div v-if="form.errors.target_max_discount" class="invalid-feedback d-block">{{ form.errors.target_max_discount }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="channel-scope">Cakupan Kanal</label>
                                <select id="channel-scope" v-model="form.channel_scope" class="form-select form-select-sm">
                                    <option value="keep">Biarkan seperti saat ini</option>
                                    <option value="global">Berlaku POS + E-commerce (global)</option>
                                    <option value="store">POS saja</option>
                                    <option value="ecommerce">E-commerce saja</option>
                                </select>
                                <div v-if="form.errors.channel_scope" class="invalid-feedback d-block">{{ form.errors.channel_scope }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="sync-modal-footer">
                        <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="form.processing" @click="closeSync">Batal</button>
                        <button class="btn btn-sm btn-primary" type="submit" :disabled="form.processing || syncableDiscounts.length === 0">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            Simpan Sinkronisasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.sync-page { color: var(--app-ink); }
.sync-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; }
.sync-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.sync-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.sync-header p { max-width: 760px; margin: 0; color: var(--app-hero-muted) !important; }
.sync-header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.sync-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border-radius: 0.55rem; font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.sync-button-light { background: var(--app-surface); color: var(--app-ink); }
.sync-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.sync-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.sync-summary span:not(.sync-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.sync-summary strong { display: block; margin-top: 0.15rem; font-size: 1.35rem; }
.sync-summary-warning .sync-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.sync-summary-info .sync-summary-icon { background: var(--app-accent-soft); color: var(--app-accent-ink); }
.sync-summary-danger .sync-summary-icon { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.sync-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.sync-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.sync-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.sync-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.sync-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.sync-table th { padding: 0.6rem 0.9rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.sync-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); color: var(--app-ink); }
.sync-main strong, .sync-main small, .sync-subvalue { display: block; }
.sync-main strong { font-size: 0.86rem; }
.sync-main small, .sync-subvalue { color: var(--app-muted); font-size: 0.73rem; }
.sync-warning-text { color: var(--app-warning-ink) !important; }
.sync-muted-text { color: var(--app-muted) !important; }
.sync-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.35rem; }
.sync-detail-row > td { padding: 0 !important; background: var(--app-surface-2); }
.sync-detail-wrap { padding: 0.5rem 1rem 1rem 2.2rem; }
.sync-detail-table th { padding: 0.45rem 0.55rem; background: transparent; font-size: 0.64rem; }
.sync-detail-table td { padding: 0.55rem; font-size: 0.78rem; }
.sync-detail-table td strong, .sync-detail-table td small { display: block; }
.sync-detail-table td small { color: var(--app-muted); font-size: 0.7rem; }
.sync-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.sync-empty { display: grid; place-items: center; gap: 0.3rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.sync-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.sync-empty strong { color: var(--app-ink); }
.sync-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: flex-start; justify-content: center; padding: min(8vh, 4rem) 1rem 1rem; overflow-y: auto; background: rgba(15, 23, 42, 0.62); }
.sync-modal { width: min(680px, 100%); overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.28); color: var(--app-ink); }
.sync-modal-header, .sync-modal-footer { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; }
.sync-modal-header { border-bottom: 1px solid var(--app-border); }
.sync-modal-footer { align-items: center; justify-content: flex-end; border-top: 1px solid var(--app-border); background: var(--app-surface-2); }
.sync-modal-body { padding: 1rem 1.1rem; }
.sync-callout { display: flex; gap: 0.6rem; margin-bottom: 1rem; padding: 0.75rem 0.85rem; border-radius: 0.6rem; background: var(--app-accent-soft); color: var(--app-accent-ink); }
.sync-callout i { font-size: 1.1rem; }
.sync-callout p { margin: 0; font-size: 0.8rem; }
.sync-discount-list { margin: 0; padding: 0; list-style: none; border: 1px solid var(--app-border); border-radius: 0.6rem; }
.sync-discount-list li { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.55rem 0.7rem; border-bottom: 1px solid var(--app-border); }
.sync-discount-list li:last-child { border-bottom: 0; }
.sync-discount-list span { font-size: 0.8rem; font-weight: 700; }
.sync-discount-list small { color: var(--app-muted); font-size: 0.72rem; }
.sync-quick-actions { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.45rem; }
@media (max-width: 991.98px) {
    .sync-header { align-items: flex-start; flex-direction: column; }
    .sync-card-header { align-items: flex-start; flex-direction: column; }
    .sync-detail-wrap { padding-left: 0.75rem; }
}
</style>
