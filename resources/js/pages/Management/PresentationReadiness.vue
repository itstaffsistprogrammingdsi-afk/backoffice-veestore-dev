<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface DummyProduct {
    id: string;
    name: string;
    code: string | null;
    brand_name: string | null;
    category_name: string | null;
    created_at: string | null;
}

interface StoreRow {
    id: string;
    name: string;
    code: string | null;
    address: string | null;
    contact_email: string | null;
    created_at: string | null;
}

interface StoreGroup {
    key: string;
    name: string;
    stores: StoreRow[];
}

interface Props {
    products: DummyProduct[];
    storeGroups: StoreGroup[];
    dummyStores: StoreRow[];
    dummyWarehouses: StoreRow[];
    summary: {
        dummy_products: number;
        duplicate_store_groups: number;
        dummy_stores: number;
        dummy_warehouses: number;
        no_brand_products: number;
    };
    canArchive: boolean;
    urls: {
        index: string;
        archive_products: string;
        archive_stores: string;
        archive_warehouses: string;
    };
}

interface PendingArchive {
    type: 'products' | 'stores' | 'warehouses';
    url: string;
    label: string;
    ids: string[];
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const productSelection = ref<string[]>([]);
const storeSelection = ref<string[]>([]);
const warehouseSelection = ref<string[]>([]);
const pending = ref<PendingArchive | null>(null);
const confirmationText = ref('');
const processing = ref(false);
const confirmationInput = ref<HTMLInputElement | null>(null);
const dialogRef = ref<HTMLElement | null>(null);
let previouslyFocused: HTMLElement | null = null;

const summaryCards = computed(() => [
    { label: 'Produk Dummy', value: props.summary.dummy_products, tone: 'is-warning' },
    { label: 'Duplikat Nama Toko', value: props.summary.duplicate_store_groups, tone: 'is-danger' },
    { label: 'Toko Dummy', value: props.summary.dummy_stores, tone: 'is-warning' },
    { label: 'Gudang Dummy', value: props.summary.dummy_warehouses, tone: 'is-warning' },
    { label: 'Produk Tanpa Brand', value: props.summary.no_brand_products, tone: 'is-info' },
]);

const allProductIds = computed(() => props.products.map((product) => product.id));

type SelectionType = 'products' | 'stores' | 'warehouses';

const selections: Record<SelectionType, typeof productSelection> = {
    products: productSelection,
    stores: storeSelection,
    warehouses: warehouseSelection,
};

const isSelected = (selection: string[], id: string) => selection.includes(id);

const toggle = (type: SelectionType, id: string) => {
    const selection = selections[type];

    selection.value = selection.value.includes(id)
        ? selection.value.filter((value) => value !== id)
        : [...selection.value, id];
};

const toggleAll = (type: SelectionType, ids: string[]) => {
    const selection = selections[type];

    selection.value = selection.value.length === ids.length ? [] : [...ids];
};

const selectedProductCount = computed(() => productSelection.value.length);
const selectedStoreCount = computed(() => storeSelection.value.length);
const selectedWarehouseCount = computed(() => warehouseSelection.value.length);

const requestArchive = (type: PendingArchive['type'], url: string, ids: string[], label: string) => {
    if (ids.length === 0) return;

    previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    pending.value = { type, url, ids, label };
    confirmationText.value = '';
};

const closeArchive = () => {
    if (processing.value) return;

    pending.value = null;
    confirmationText.value = '';

    nextTick(() => previouslyFocused?.focus());
};

const submitArchive = () => {
    if (!pending.value || confirmationText.value.trim() !== 'ARSIPKAN' || processing.value) return;

    processing.value = true;
    router.post(pending.value.url, {
        ids: pending.value.ids,
        confirmation: confirmationText.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            productSelection.value = [];
            storeSelection.value = [];
            warehouseSelection.value = [];
            pending.value = null;
            confirmationText.value = '';
        },
        onError: (errors) => {
            if (errors.confirmation) {
                confirmationText.value = '';
            }
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const archiveProducts = () => requestArchive(
    'products',
    props.urls.archive_products,
    productSelection.value,
    `${productSelection.value.length} produk`,
);

const archiveStores = () => requestArchive(
    'stores',
    props.urls.archive_stores,
    storeSelection.value,
    `${storeSelection.value.length} toko`,
);

const archiveWarehouses = () => requestArchive(
    'warehouses',
    props.urls.archive_warehouses,
    warehouseSelection.value,
    `${warehouseSelection.value.length} gudang`,
);

const onDialogKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        event.preventDefault();
        closeArchive();

        return;
    }

    if (event.key !== 'Tab') return;

    const focusable = Array.from(
        dialogRef.value?.querySelectorAll<HTMLElement>('input, button:not(:disabled)') ?? [],
    ).filter((element) => element.getClientRects().length > 0);

    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

const formatDate = (value: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

watch(pending, async (value) => {
    document.body.style.overflow = value ? 'hidden' : '';

    if (!value) return;

    await nextTick();
    confirmationInput.value?.focus();
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Head title="Kesiapan Presentasi" />

    <div class="presentation-page">
        <header class="presentation-header">
            <div>
                <span class="presentation-eyebrow">Operasional / Administrasi</span>
                <h1>Kesiapan Presentasi</h1>
                <p>Audit data dummy dan duplikat toko. Pengarsipan hanya dilakukan setelah konfirmasi eksplisit.</p>
            </div>
        </header>

        <div v-if="flash.success" class="alert alert-success py-2 small" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger py-2 small" role="alert">{{ flash.error }}</div>

        <section class="presentation-summary" aria-label="Ringkasan kesiapan presentasi">
            <div v-for="card in summaryCards" :key="card.label" class="presentation-summary-card">
                <span>{{ card.label }}</span>
                <strong :class="card.tone">{{ card.value }}</strong>
            </div>
        </section>

        <section class="presentation-card" aria-labelledby="presentation-products-title">
            <div class="presentation-card-head">
                <div>
                    <h2 id="presentation-products-title">Produk Dummy</h2>
                    <p>Produk dengan nama karakter, contoh, atau kata uji. Data diarsipkan, bukan dihapus permanen.</p>
                </div>
                <button
                    v-if="props.canArchive"
                    class="btn btn-sm btn-outline-danger"
                    type="button"
                    :disabled="selectedProductCount === 0"
                    @click="archiveProducts"
                >
                    <i class="mdi mdi-archive-arrow-down-outline me-1" aria-hidden="true"></i>
                    Arsipkan Terpilih ({{ selectedProductCount }})
                </button>
            </div>

            <div v-if="props.products.length > 0" class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="presentation-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    :checked="productSelection.length === allProductIds.length"
                                    aria-label="Pilih semua produk dummy"
                                    @change="toggleAll('products', allProductIds)"
                                />
                            </th>
                            <th>Produk</th>
                            <th>Kode</th>
                            <th>Brand</th>
                            <th>Kategori</th>
                            <th>Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="product in props.products" :key="product.id">
                            <td class="presentation-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    :checked="isSelected(productSelection, product.id)"
                                    :aria-label="`Pilih ${product.name}`"
                                    @change="toggle('products', product.id)"
                                />
                            </td>
                            <td class="fw-semibold">{{ product.name }}</td>
                            <td>{{ product.code ?? '-' }}</td>
                            <td>{{ product.brand_name ?? '-' }}</td>
                            <td>{{ product.category_name ?? '-' }}</td>
                            <td class="text-nowrap">{{ formatDate(product.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="presentation-empty is-success">
                <i class="mdi mdi-check-decagram-outline" aria-hidden="true"></i>
                <strong>Tidak ada produk dummy yang terdeteksi</strong>
                <span>Data produk siap untuk presentasi.</span>
            </div>
        </section>

        <section class="presentation-card" aria-labelledby="presentation-duplicates-title">
            <div class="presentation-card-head">
                <div>
                    <h2 id="presentation-duplicates-title">Duplikat Nama Toko</h2>
                    <p>Nama toko dibandingkan tanpa membedakan huruf besar-kecil dan spasi di awal/akhir.</p>
                </div>
                <button
                    v-if="props.canArchive && storeSelection.length > 0"
                    class="btn btn-sm btn-outline-danger"
                    type="button"
                    @click="archiveStores"
                >
                    <i class="mdi mdi-archive-arrow-down-outline me-1" aria-hidden="true"></i>
                    Arsipkan Terpilih ({{ selectedStoreCount }})
                </button>
            </div>

            <div v-if="props.storeGroups.length > 0" class="presentation-groups">
                <article v-for="group in props.storeGroups" :key="group.key" class="presentation-group">
                    <h3>{{ group.name }}</h3>
                    <div v-for="store in group.stores" :key="store.id" class="presentation-row">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            :checked="isSelected(storeSelection, store.id)"
                            :aria-label="`Pilih ${store.name}`"
                            @change="toggle('stores', store.id)"
                        />
                        <div>
                            <strong>{{ store.name }}</strong>
                            <small>{{ store.code ?? '-' }} · {{ store.address ?? 'Alamat belum diisi' }}</small>
                        </div>
                        <span class="presentation-muted">{{ formatDate(store.created_at) }}</span>
                    </div>
                </article>
            </div>

            <div v-else class="presentation-empty is-success">
                <i class="mdi mdi-check-decagram-outline" aria-hidden="true"></i>
                <strong>Tidak ada duplikat nama toko</strong>
                <span>Seluruh nama toko sudah unik.</span>
            </div>
        </section>

        <section v-if="props.dummyStores.length > 0" class="presentation-card" aria-labelledby="presentation-dummy-stores-title">
            <div class="presentation-card-head">
                <div>
                    <h2 id="presentation-dummy-stores-title">Toko Dummy</h2>
                    <p>Toko dengan nama uji atau nama contoh.</p>
                </div>
                <button
                    v-if="props.canArchive"
                    class="btn btn-sm btn-outline-danger"
                    type="button"
                    :disabled="selectedStoreCount === 0"
                    @click="archiveStores"
                >
                    Arsipkan Terpilih ({{ selectedStoreCount }})
                </button>
            </div>
            <div class="presentation-list">
                <div v-for="store in props.dummyStores" :key="store.id" class="presentation-row">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        :checked="isSelected(storeSelection, store.id)"
                        :aria-label="`Pilih ${store.name}`"
                        @change="toggle('stores', store.id)"
                    />
                    <div>
                        <strong>{{ store.name }}</strong>
                        <small>{{ store.code ?? '-' }} · {{ store.address ?? 'Alamat belum diisi' }}</small>
                    </div>
                    <span class="presentation-muted">{{ formatDate(store.created_at) }}</span>
                </div>
            </div>
        </section>

        <section v-if="props.dummyWarehouses.length > 0" class="presentation-card" aria-labelledby="presentation-dummy-warehouses-title">
            <div class="presentation-card-head">
                <div>
                    <h2 id="presentation-dummy-warehouses-title">Gudang Dummy</h2>
                    <p>Gudang dengan nama uji atau nama contoh.</p>
                </div>
                <button
                    v-if="props.canArchive"
                    class="btn btn-sm btn-outline-danger"
                    type="button"
                    :disabled="selectedWarehouseCount === 0"
                    @click="archiveWarehouses"
                >
                    Arsipkan Terpilih ({{ selectedWarehouseCount }})
                </button>
            </div>
            <div class="presentation-list">
                <div v-for="warehouse in props.dummyWarehouses" :key="warehouse.id" class="presentation-row">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        :checked="isSelected(warehouseSelection, warehouse.id)"
                        :aria-label="`Pilih ${warehouse.name}`"
                        @change="toggle('warehouses', warehouse.id)"
                    />
                    <div>
                        <strong>{{ warehouse.name }}</strong>
                        <small>{{ warehouse.code ?? '-' }}</small>
                    </div>
                    <span class="presentation-muted">{{ formatDate(warehouse.created_at) }}</span>
                </div>
            </div>
        </section>

        <Teleport to="body">
            <div v-if="pending" class="presentation-modal-backdrop" @click.self="closeArchive">
                <section
                    ref="dialogRef"
                    class="presentation-modal"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="presentation-modal-title"
                    @keydown="onDialogKeydown"
                >
                    <header class="presentation-modal-header">
                        <div>
                            <h2 id="presentation-modal-title">Konfirmasi Pengarsipan</h2>
                            <p>{{ pending.label }} akan diarsipkan dan tidak tampil di master.</p>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup konfirmasi" @click="closeArchive">
                            <i class="mdi mdi-close" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div class="presentation-modal-body">
                        <p class="mb-2">
                            Ketik <strong>ARSIPKAN</strong> untuk melanjutkan. Tindakan ini menggunakan soft delete,
                            sehingga data tidak hilang permanen.
                        </p>
                        <label for="presentation-confirmation" class="form-label">Konfirmasi</label>
                        <input
                            id="presentation-confirmation"
                            ref="confirmationInput"
                            v-model="confirmationText"
                            class="form-control"
                            type="text"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="ARSIPKAN"
                        />
                        <small v-if="page.props.errors?.confirmation" class="text-danger">{{ page.props.errors.confirmation }}</small>
                    </div>

                    <footer class="presentation-modal-footer">
                        <button class="btn btn-outline-secondary" type="button" :disabled="processing" @click="closeArchive">Batal</button>
                        <button
                            class="btn btn-danger"
                            type="button"
                            :disabled="confirmationText.trim() !== 'ARSIPKAN' || processing"
                            @click="submitArchive"
                        >
                            <i class="mdi mdi-archive-arrow-down-outline me-1" aria-hidden="true"></i>
                            {{ processing ? 'Mengarsipkan...' : 'Arsipkan' }}
                        </button>
                    </footer>
                </section>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.presentation-page { color: var(--app-ink); }
.presentation-header { padding: 1.4rem; margin-bottom: 1rem; border-radius: 0.9rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.presentation-eyebrow { display: inline-block; margin-bottom: 0.35rem; color: var(--app-hero-eyebrow); font-size: 0.7rem; font-weight: 700; }
.presentation-header h1 { margin: 0 0 0.35rem; font-size: clamp(1.3rem, 2vw, 1.6rem); }
.presentation-header p { margin: 0; color: var(--app-hero-muted); font-size: 0.86rem; }
.presentation-summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.presentation-summary-card { padding: 0.85rem 1rem; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface); }
.presentation-summary-card span { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 600; }
.presentation-summary-card strong { display: block; margin-top: 0.2rem; font-size: 1.25rem; font-weight: 800; }
.presentation-summary-card strong.is-warning { color: var(--app-warning-ink); }
.presentation-summary-card strong.is-danger { color: var(--app-danger-ink); }
.presentation-summary-card strong.is-info { color: var(--app-info-ink); }
.presentation-card { padding: 1rem; margin-bottom: 1rem; border: 1px solid var(--app-border); border-radius: 0.8rem; background: var(--app-surface); }
.presentation-card-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.85rem; }
.presentation-card-head h2 { margin: 0; font-size: 0.95rem; font-weight: 800; }
.presentation-card-head p { margin: 0.2rem 0 0; color: var(--app-muted); font-size: 0.78rem; }
.presentation-check { width: 2.4rem; text-align: center; }
.presentation-page .table th { color: var(--app-muted); font-size: 0.72rem; font-weight: 700; white-space: nowrap; }
.presentation-page .table td { font-size: 0.8rem; vertical-align: middle; }
.presentation-groups { display: grid; gap: 0.75rem; }
.presentation-group { padding: 0.8rem; border: 1px solid var(--app-border); border-radius: 0.65rem; background: var(--app-surface-2); }
.presentation-group h3 { margin: 0 0 0.5rem; font-size: 0.86rem; font-weight: 800; }
.presentation-list { display: grid; gap: 0.35rem; }
.presentation-row { display: grid; grid-template-columns: 1.25rem minmax(0, 1fr) auto; align-items: center; gap: 0.65rem; padding: 0.6rem; border-radius: 0.5rem; background: var(--app-surface-2); }
.presentation-row strong { display: block; font-size: 0.82rem; }
.presentation-row small { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: 0.72rem; overflow-wrap: anywhere; }
.presentation-muted { color: var(--app-muted); font-size: 0.72rem; white-space: nowrap; }
.presentation-empty { padding: 2rem 1rem; color: var(--app-muted); text-align: center; }
.presentation-empty i { display: block; margin-bottom: 0.35rem; font-size: 1.8rem; }
.presentation-empty strong { display: block; color: var(--app-ink); font-size: 0.9rem; }
.presentation-empty span { display: block; margin-top: 0.2rem; font-size: 0.78rem; }
.presentation-empty.is-success i { color: var(--app-accent-ink); }
.presentation-modal-backdrop { position: fixed; inset: 0; z-index: 1100; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(10, 20, 28, 0.55); }
.presentation-modal { width: min(440px, 100%); overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.8rem; background: var(--app-surface); box-shadow: 0 1.5rem 4rem rgba(10, 20, 28, 0.32); }
.presentation-modal-header, .presentation-modal-footer { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; padding: 0.9rem 1rem; }
.presentation-modal-header { border-bottom: 1px solid var(--app-border); }
.presentation-modal-header h2 { margin: 0; font-size: 0.98rem; font-weight: 800; }
.presentation-modal-header p { margin: 0.2rem 0 0; color: var(--app-muted); font-size: 0.78rem; }
.presentation-modal-body { padding: 1rem; }
.presentation-modal-footer { justify-content: flex-end; border-top: 1px solid var(--app-border); background: var(--app-surface-2); }
@media (max-width: 1199.98px) { .presentation-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 767.98px) { .presentation-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } .presentation-header { padding: 1.1rem; } }
@media (max-width: 575.98px) { .presentation-summary { grid-template-columns: 1fr; } .presentation-row { grid-template-columns: 1.25rem minmax(0, 1fr); } .presentation-muted { grid-column: 2; } }
</style>
