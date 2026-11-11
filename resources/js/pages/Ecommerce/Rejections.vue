<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { orderStatusMeta } from '@/composables/useStatusLabels';

defineOptions({ layout: AppLayout });

interface Option {
    id: string;
    name: string;
    code?: string | null;
}

interface EvidenceItem {
    type: string;
    type_label: string;
    payload: unknown;
    captured_at: string | null;
}

interface OrderContext {
    id: string;
    order_number: string | null;
    status: string;
    grand_total: number;
    created_at: string | null;
    payment_method_name: string | null;
    customer_name: string | null;
    store_name: string | null;
    warehouse_name: string | null;
}

interface RejectionRow {
    id: string;
    ecommerce_order_id: string;
    store_id: string;
    mode: string;
    mode_label: string;
    reason: string;
    reason_label: string;
    note: string;
    status: string;
    requested_at: string | null;
    requested_by_name: string | null;
    age_hours: number | null;
    evidence_count: number;
    evidence: EvidenceItem[];
    order: OrderContext | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    cases: Paginator<RejectionRow>;
    summary: {
        pending_cases: number;
        pending_orders: number;
        oldest_requested_at: string | null;
        oldest_age_hours: number | null;
    };
    stores: Option[];
    warehouses: Option[];
    filters: {
        q: string;
        reason: string;
        order_id: string;
        per_page: number;
    };
    handoffError: string | null;
    urls: {
        index: string;
        resolve: string;
        orders: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
    hint: store.code ?? undefined,
})));
const warehouseOptions = computed(() => props.warehouses.map((warehouse) => ({
    value: warehouse.id,
    label: warehouse.name,
    hint: warehouse.code ?? undefined,
})));

const search = ref(props.filters.q ?? '');
const reason = ref(props.filters.reason ?? '');
const perPage = ref(String(props.filters.per_page ?? 15));

const reasonOptions = [
    { value: '', label: 'Semua alasan' },
    { value: 'inventory_shortage', label: 'Stok tidak mencukupi' },
    { value: 'item_issue', label: 'Kondisi item bermasalah' },
    { value: 'operational_exception', label: 'Kendala operasional' },
];

const selectedCase = ref<RejectionRow | null>(null);
const decision = ref<'reassign_store' | 'reassign_warehouse' | 'cancel'>('reassign_store');
const targetStoreId = ref('');
const targetWarehouseId = ref('');
const decisionNote = ref('');
const working = ref(false);
const actionError = ref('');
const drawerCloseRef = ref<HTMLButtonElement | null>(null);
const drawerRef = ref<HTMLElement | null>(null);
let previouslyFocused: HTMLElement | null = null;

const setBackgroundInert = (inert: boolean) => {
    const app = document.getElementById('app');

    if (app) {
        (app as HTMLElement & { inert: boolean }).inert = inert;
    }
};

const restoreFocus = () => {
    previouslyFocused?.focus();
    previouslyFocused = null;
};

const applyFilters = () => {
    router.get(props.urls.index, {
        q: search.value || undefined,
        reason: reason.value || undefined,
        order_id: props.filters.order_id || undefined,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    search.value = '';
    reason.value = '';
    perPage.value = '15';
    router.get(props.urls.index, {}, { preserveState: true, preserveScroll: true, replace: true });
};

const clearOrderContext = () => {
    search.value = '';
    reason.value = '';
    router.get(props.urls.index, {}, { preserveState: true, preserveScroll: true, replace: true });
};

const openCase = (row: RejectionRow) => {
    previouslyFocused = document.activeElement instanceof HTMLElement
        ? document.activeElement
        : null;
    selectedCase.value = row;
    decision.value = row.order?.store_name ? 'reassign_warehouse' : 'reassign_store';
    targetStoreId.value = '';
    targetWarehouseId.value = '';
    decisionNote.value = '';
    actionError.value = '';
    document.body.style.overflow = 'hidden';
    setBackgroundInert(true);
    void nextTick(() => drawerCloseRef.value?.focus());
};

const closeCase = () => {
    if (working.value) return;

    selectedCase.value = null;
    actionError.value = '';
    document.body.style.overflow = '';
    setBackgroundInert(false);
    restoreFocus();
};

const targetRequired = computed(() => decision.value !== 'cancel');
const selectedTarget = computed(() => {
    if (decision.value === 'reassign_store') return targetStoreId.value;
    if (decision.value === 'reassign_warehouse') return targetWarehouseId.value;

    return 'cancel';
});
const canSubmit = computed(() => !working.value && (!targetRequired.value || Boolean(selectedTarget.value)));

const submitDecision = () => {
    if (!selectedCase.value || !canSubmit.value) return;

    working.value = true;
    actionError.value = '';

    router.post(props.urls.resolve.replace('__ID__', selectedCase.value.id), {
        decision: decision.value,
        target_id: targetRequired.value ? selectedTarget.value : null,
        note: decisionNote.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            working.value = false;
            closeCase();
        },
        onError: (errors) => {
            actionError.value = (Object.values(errors)[0] as string | undefined)
                ?? 'Kendala belum dapat diselesaikan. Periksa pilihan lokasi lalu coba lagi.';
        },
        onFinish: () => {
            working.value = false;
        },
    });
};

const onKeydown = (event: KeyboardEvent) => {
    if (! selectedCase.value) return;

    if (event.key === 'Escape') {
        closeCase();

        return;
    }

    if (event.key !== 'Tab' || ! drawerRef.value) return;

    const focusable = Array.from(drawerRef.value.querySelectorAll<HTMLElement>(
        'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',
    )).filter((element) => element.offsetParent !== null);

    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (! event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

watch(selectedCase, (value) => {
    if (!value) {
        document.body.style.overflow = '';
    }
});

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
    setBackgroundInert(false);
});

const formatNumber = (value: number | null | undefined) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));
const formatCurrency = (value: number | null | undefined) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));
const formatDate = (value: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
    : '-';
const formatDateTime = (value: string | null) => value
    ? new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value))
    : '-';
const formatAge = (hours: number | null) => {
    if (hours === null) return '-';
    if (hours < 1) return 'Baru saja';
    if (hours < 24) return `${formatNumber(hours)} jam`;

    return `${formatNumber(Math.floor(hours / 24))} hari`;
};
const statusMeta = (status: string) => orderStatusMeta(status);
const orderLabel = (row: RejectionRow) => row.order?.order_number ?? row.ecommerce_order_id;
</script>

<template>
    <Head title="Kendala E-commerce" />

    <div class="rejection-page">
        <section class="rejection-header">
            <div>
                <span class="rejection-kicker">Operasional / E-commerce</span>
                <h1>Kendala E-commerce</h1>
                <p>Tinjau pengajuan kendala fulfillment dari toko, periksa bukti, lalu pindahkan order atau batalkan pesanan.</p>
            </div>
            <div class="rejection-header-actions">
                <Link :href="props.urls.orders" class="rejection-header-button">
                    <i class="mdi mdi-cart-arrow-right" aria-hidden="true"></i>
                    Daftar Pesanan
                </Link>
            </div>
        </section>

        <div v-if="flash.success" class="alert alert-success" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger" role="alert">{{ flash.error }}</div>
        <div v-if="props.handoffError" class="alert alert-danger" role="alert">{{ props.handoffError }}</div>

        <div v-if="props.filters.order_id" class="rejection-context" role="status">
            <div>
                <strong>Menampilkan kendala untuk satu pesanan.</strong>
                <span>Hapus fokus pesanan untuk melihat seluruh antrean.</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearOrderContext">
                Tampilkan semua
            </button>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <div class="rejection-summary rejection-summary-warning">
                    <span class="rejection-summary-icon"><i class="mdi mdi-alert-decagram-outline" aria-hidden="true"></i></span>
                    <div>
                        <span>Menunggu keputusan</span>
                        <strong>{{ formatNumber(props.summary.pending_cases) }}</strong>
                        <small>{{ formatNumber(props.summary.pending_orders) }} pesanan terdampak</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="rejection-summary">
                    <span class="rejection-summary-icon"><i class="mdi mdi-clock-outline" aria-hidden="true"></i></span>
                    <div>
                        <span>Kendala tertua</span>
                        <strong>{{ formatAge(props.summary.oldest_age_hours) }}</strong>
                        <small v-if="props.summary.oldest_requested_at">Diajukan {{ formatDateTime(props.summary.oldest_requested_at) }}</small>
                        <small v-else>Belum ada antrean</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card rejection-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-5">
                        <label class="form-label" for="rejection-search">Cari nomor pesanan</label>
                        <input
                            id="rejection-search"
                            v-model="search"
                            class="form-control form-control-sm"
                            type="search"
                            placeholder="Nomor pesanan atau ID order"
                        />
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <label class="form-label" for="rejection-reason">Alasan</label>
                        <select id="rejection-reason" v-model="reason" class="form-select form-select-sm">
                            <option v-for="option in reasonOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="rejection-per-page">Baris</label>
                        <select id="rejection-per-page" v-model="perPage" class="form-select form-select-sm">
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Terapkan</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="resetFilters">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card rejection-card">
            <div class="rejection-card-header">
                <div>
                    <h2>Antrean keputusan</h2>
                    <p>Order yang dibekukan sampai Backoffice memilih tindakan.</p>
                </div>
                <span class="rejection-card-meta">
                    {{ formatNumber(props.cases.total) }} kendala
                </span>
            </div>

            <div v-if="props.cases.data.length === 0" class="rejection-empty">
                <i class="mdi mdi-check-circle-outline" aria-hidden="true"></i>
                <strong>Tidak ada kendala yang menunggu keputusan.</strong>
                <span v-if="props.filters.q || props.filters.reason || props.filters.order_id">
                    Filter saat ini tidak menemukan kendala. Coba hapus filter.
                </span>
                <span v-else>Semua pengajuan kendala dari toko sudah diproses.</span>
                <Link :href="props.urls.orders" class="btn btn-sm btn-outline-primary">Kembali ke daftar pesanan</Link>
            </div>

            <div v-else class="table-responsive">
                <table class="table rejection-table align-middle mb-0">
                    <caption class="visually-hidden">
                        Antrean kendala fulfillment e-commerce yang menunggu keputusan Backoffice.
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Pesanan</th>
                            <th scope="col" class="d-none d-lg-table-cell">Toko pelapor</th>
                            <th scope="col">Alasan</th>
                            <th scope="col" class="d-none d-md-table-cell">Diajukan</th>
                            <th scope="col" class="d-none d-md-table-cell">Bukti</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.cases.data" :key="row.id">
                            <td>
                                <button type="button" class="rejection-order-link" @click="openCase(row)">
                                    {{ orderLabel(row) }}
                                </button>
                                <span class="rejection-subvalue">
                                    {{ row.order?.customer_name ?? 'Pelanggan' }}
                                    <template v-if="row.order">, {{ formatCurrency(row.order.grand_total) }}</template>
                                </span>
                                <span v-if="row.order" class="badge" :class="statusMeta(row.order.status).tone">
                                    {{ statusMeta(row.order.status).label }}
                                </span>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <span class="rejection-muted-strong">{{ row.order?.store_name ?? '-' }}</span>
                                <span class="rejection-subvalue">{{ row.requested_by_name ?? 'Petugas toko' }}</span>
                            </td>
                            <td>
                                <span class="rejection-muted-strong">{{ row.reason_label }}</span>
                                <span class="rejection-subvalue">{{ row.mode_label }}</span>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <span class="rejection-muted-strong">{{ formatAge(row.age_hours) }}</span>
                                <span class="rejection-subvalue">{{ formatDate(row.requested_at) }}</span>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <span class="rejection-muted-strong">{{ formatNumber(row.evidence_count) }} bukti</span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-primary rejection-action" @click="openCase(row)">
                                    Tinjau
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="props.cases.data.length > 0" class="rejection-footer">
                <div class="rejection-footer-meta">
                    <span v-if="props.cases.total > 0">
                        Menampilkan {{ props.cases.from ?? 0 }} sampai {{ props.cases.to ?? 0 }} dari {{ formatNumber(props.cases.total) }}
                    </span>
                </div>
                <PaginationLinks v-if="props.cases.links.length > 3" :links="props.cases.links" />
            </div>
        </div>

        <Teleport to="body">
            <div v-if="selectedCase" class="rejection-backdrop" @click.self="closeCase">
                <div ref="drawerRef" class="rejection-drawer" role="dialog" aria-modal="true" aria-labelledby="rejection-drawer-title">
                    <div class="rejection-drawer-header">
                        <div>
                            <span class="rejection-drawer-kicker">Kendala fulfillment</span>
                            <h2 id="rejection-drawer-title">{{ orderLabel(selectedCase) }}</h2>
                            <p>
                                {{ selectedCase.reason_label }} oleh {{ selectedCase.requested_by_name ?? 'petugas toko' }}
                                pada {{ formatDateTime(selectedCase.requested_at) }}.
                            </p>
                        </div>
                        <button ref="drawerCloseRef" type="button" class="rejection-icon-button" aria-label="Tutup detail kendala" :disabled="working" @click="closeCase">
                            <i class="mdi mdi-close" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="rejection-drawer-body">
                        <div class="rejection-meta-grid">
                            <div>
                                <span>Pelanggan</span>
                                <strong>{{ selectedCase.order?.customer_name ?? '-' }}</strong>
                                <small>{{ selectedCase.order?.payment_method_name ?? 'Metode belum tercatat' }}</small>
                            </div>
                            <div>
                                <span>Nilai pesanan</span>
                                <strong>{{ formatCurrency(selectedCase.order?.grand_total) }}</strong>
                                <small>{{ statusMeta(selectedCase.order?.status ?? '').label }}</small>
                            </div>
                            <div>
                                <span>Penanganan saat ini</span>
                                <strong>{{ selectedCase.order?.store_name ?? selectedCase.order?.warehouse_name ?? 'Belum ditentukan' }}</strong>
                                <small>{{ selectedCase.order?.store_name ? 'Toko' : selectedCase.order?.warehouse_name ? 'Gudang' : '-' }}</small>
                            </div>
                            <div>
                                <span>Umur kendala</span>
                                <strong>{{ formatAge(selectedCase.age_hours) }}</strong>
                                <small>{{ selectedCase.mode_label }}</small>
                            </div>
                        </div>

                        <section class="rejection-note" aria-labelledby="rejection-note-title">
                            <span class="rejection-note-icon"><i class="mdi mdi-message-text-outline" aria-hidden="true"></i></span>
                            <div>
                                <span id="rejection-note-title">Catatan petugas toko</span>
                                <p>{{ selectedCase.note || 'Tidak ada catatan tambahan.' }}</p>
                            </div>
                        </section>

                        <section class="rejection-evidence" aria-labelledby="rejection-evidence-title">
                            <h3 id="rejection-evidence-title">Bukti pengajuan</h3>
                            <p v-if="selectedCase.evidence.length === 0" class="rejection-muted-strong">
                                Tidak ada bukti terperinci. Keputusan tetap dapat diambil dari data pesanan.
                            </p>
                            <div v-else class="rejection-evidence-list">
                                <article v-for="(item, index) in selectedCase.evidence" :key="`${item.type}-${index}`" class="rejection-evidence-item">
                                    <header>
                                        <strong>{{ item.type_label }}</strong>
                                        <span>{{ formatDateTime(item.captured_at) }}</span>
                                    </header>
                                    <pre class="rejection-evidence-payload">{{ JSON.stringify(item.payload, null, 2) }}</pre>
                                </article>
                            </div>
                        </section>

                        <section class="rejection-decision" aria-labelledby="rejection-decision-title">
                            <h3 id="rejection-decision-title">Keputusan Backoffice</h3>
                            <p class="rejection-decision-help">
                                Menyimpan keputusan akan menandai kendala selesai dan membuka kembali fulfillment toko untuk pesanan ini.
                            </p>

                            <div class="rejection-choice-grid">
                                <label class="rejection-choice" :class="{ 'is-selected': decision === 'reassign_store' }">
                                    <input v-model="decision" type="radio" value="reassign_store" />
                                    <span>
                                        <strong>Pindahkan ke toko lain</strong>
                                        <small>Gunakan toko yang masih memiliki stok tersedia.</small>
                                    </span>
                                </label>
                                <label class="rejection-choice" :class="{ 'is-selected': decision === 'reassign_warehouse' }">
                                    <input v-model="decision" type="radio" value="reassign_warehouse" />
                                    <span>
                                        <strong>Pindahkan ke gudang</strong>
                                        <small>Serahkan pemenuhan ke gudang pusat.</small>
                                    </span>
                                </label>
                                <label class="rejection-choice rejection-choice-danger" :class="{ 'is-selected': decision === 'cancel' }">
                                    <input v-model="decision" type="radio" value="cancel" />
                                    <span>
                                        <strong>Batalkan pesanan</strong>
                                        <small>Hanya dipakai bila pesanan tidak mungkin dipenuhi.</small>
                                    </span>
                                </label>
                            </div>

                            <div v-if="decision === 'reassign_store'" class="rejection-field">
                                <label for="rejection-target-store">Toko tujuan</label>
                                <SearchableSelect
                                    id="rejection-target-store"
                                    v-model="targetStoreId"
                                    :options="storeOptions"
                                    placeholder="Pilih toko tujuan"
                                    search-placeholder="Cari toko..."
                                />
                            </div>

                            <div v-if="decision === 'reassign_warehouse'" class="rejection-field">
                                <label for="rejection-target-warehouse">Gudang tujuan</label>
                                <SearchableSelect
                                    id="rejection-target-warehouse"
                                    v-model="targetWarehouseId"
                                    :options="warehouseOptions"
                                    placeholder="Pilih gudang tujuan"
                                    search-placeholder="Cari gudang..."
                                />
                            </div>

                            <div class="rejection-field">
                                <label for="rejection-decision-note">Catatan keputusan (opsional)</label>
                                <textarea
                                    id="rejection-decision-note"
                                    v-model="decisionNote"
                                    class="form-control"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="Alasan pemindahan atau pembatalan untuk jejak audit."
                                ></textarea>
                            </div>

                            <div v-if="actionError" class="alert alert-danger" role="alert">{{ actionError }}</div>

                            <div class="rejection-drawer-actions">
                                <button type="button" class="btn btn-outline-secondary" :disabled="working" @click="closeCase">
                                    Batal
                                </button>
                                <button type="button" class="btn btn-primary" :disabled="!canSubmit" @click="submitDecision">
                                    {{ working ? 'Menyimpan...' : decision === 'cancel' ? 'Batalkan pesanan' : 'Simpan dan selesaikan' }}
                                </button>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.rejection-page { color: var(--app-ink); }
.rejection-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 1px solid var(--app-border);
    border-radius: 1rem;
    background: var(--app-surface);
}
.rejection-kicker {
    display: inline-block;
    margin-bottom: 0.4rem;
    color: var(--app-muted);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.rejection-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.rejection-header p { max-width: 760px; margin: 0; color: var(--app-muted); }
.rejection-header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.rejection-header-button {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 44px;
    padding: 0.55rem 0.85rem;
    border: 1px solid var(--app-border);
    border-radius: 0.55rem;
    background: var(--app-surface-2);
    color: var(--app-ink);
    font-size: 0.82rem;
    font-weight: 700;
    text-decoration: none;
}
.rejection-header-button:hover { border-color: var(--app-accent); color: var(--app-accent-ink); }
.rejection-context {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--app-border);
    border-radius: 0.8rem;
    background: var(--app-surface-2);
}
.rejection-context strong { display: block; }
.rejection-context span { display: block; margin-top: 0.15rem; color: var(--app-muted); font-size: 0.8rem; }
.rejection-summary {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    height: 100%;
    padding: 1rem;
    border: 1px solid var(--app-border);
    border-radius: 0.9rem;
    background: var(--app-surface);
}
.rejection-summary-icon {
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
.rejection-summary span:not(.rejection-summary-icon) {
    display: block;
    color: var(--app-muted);
    font-size: 0.7rem;
    font-weight: 700;
}
.rejection-summary strong { display: block; margin-top: 0.15rem; font-size: 1.35rem; }
.rejection-summary small { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: 0.7rem; }
.rejection-summary-warning .rejection-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.rejection-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); }
.rejection-card-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid var(--app-border);
}
.rejection-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.rejection-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.rejection-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.rejection-table th {
    padding: 0.6rem 0.9rem;
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 0.7rem;
    font-weight: 700;
}
.rejection-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); color: var(--app-ink); }
.rejection-order-link {
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--app-accent-ink);
    font-weight: 700;
    text-align: left;
    text-decoration: underline;
    text-underline-offset: 3px;
}
.rejection-subvalue { display: block; margin-top: 0.15rem; color: var(--app-muted); font-size: 0.73rem; }
.rejection-muted-strong { color: var(--app-muted); font-weight: 600; }
.rejection-action { min-height: 44px; }
.rejection-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.8rem;
    padding: 0.85rem 1.1rem;
    border-top: 1px solid var(--app-border);
}
.rejection-footer-meta { color: var(--app-muted); font-size: 0.78rem; }
.rejection-empty {
    display: grid;
    place-items: center;
    gap: 0.35rem;
    padding: 3rem 1rem;
    color: var(--app-muted);
    text-align: center;
}
.rejection-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.rejection-empty strong { color: var(--app-ink); }
.rejection-empty .btn { margin-top: 0.4rem; }
.rejection-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1080;
    display: flex;
    justify-content: flex-end;
    background: rgba(15, 23, 42, 0.58);
}
.rejection-drawer {
    display: flex;
    flex-direction: column;
    width: min(920px, 100%);
    height: 100%;
    background: var(--app-surface);
    color: var(--app-ink);
    box-shadow: -1rem 0 2.5rem rgba(15, 23, 42, 0.24);
}
.rejection-drawer-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid var(--app-border);
}
.rejection-drawer-kicker {
    display: block;
    margin-bottom: 0.2rem;
    color: var(--app-muted);
    font-size: 0.7rem;
    font-weight: 700;
}
.rejection-drawer-header h2 { margin: 0 0 0.2rem; font-size: 1.15rem; }
.rejection-drawer-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.rejection-icon-button {
    display: grid;
    width: 44px;
    height: 44px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid var(--app-border);
    border-radius: 0.6rem;
    background: var(--app-surface-2);
    color: var(--app-ink);
    font-size: 1.2rem;
}
.rejection-drawer-body { flex: 1; padding: 1.1rem 1.25rem; overflow-y: auto; }
.rejection-meta-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.7rem;
    margin-bottom: 1rem;
}
.rejection-meta-grid > div {
    padding: 0.7rem 0.8rem;
    border: 1px solid var(--app-border);
    border-radius: 0.7rem;
    background: var(--app-surface-2);
}
.rejection-meta-grid span {
    display: block;
    color: var(--app-muted);
    font-size: 0.68rem;
    font-weight: 700;
}
.rejection-meta-grid strong { display: block; margin-top: 0.15rem; font-size: 0.85rem; overflow-wrap: anywhere; }
.rejection-meta-grid small { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: 0.7rem; }
.rejection-note {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding: 0.85rem;
    border: 1px solid color-mix(in srgb, var(--app-warning-ink) 28%, var(--app-border));
    border-radius: 0.75rem;
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
}
.rejection-note-icon {
    display: grid;
    flex: 0 0 auto;
    width: 2rem;
    height: 2rem;
    place-items: center;
    border-radius: 0.5rem;
    background: color-mix(in srgb, var(--app-warning-ink) 14%, transparent);
    font-size: 1.1rem;
}
.rejection-note span:not(.rejection-note-icon) {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
}
.rejection-note p { margin: 0.2rem 0 0; color: inherit; font-size: 0.82rem; line-height: 1.5; white-space: pre-line; }
.rejection-evidence { margin-bottom: 1rem; }
.rejection-evidence h3,
.rejection-decision h3 { margin: 0 0 0.35rem; font-size: 0.95rem; }
.rejection-evidence-list { display: grid; gap: 0.65rem; }
.rejection-evidence-item {
    padding: 0.75rem;
    border: 1px solid var(--app-border);
    border-radius: 0.7rem;
    background: var(--app-surface-2);
}
.rejection-evidence-item header {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.4rem;
}
.rejection-evidence-item header span { color: var(--app-muted); font-size: 0.72rem; }
.rejection-evidence-payload {
    margin: 0;
    color: var(--app-ink);
    font-size: 0.74rem;
    line-height: 1.45;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}
.rejection-decision {
    padding: 0.9rem;
    border: 1px solid var(--app-border);
    border-radius: 0.8rem;
    background: var(--app-surface-2);
}
.rejection-decision-help { margin: 0 0 0.75rem; color: var(--app-muted); font-size: 0.8rem; }
.rejection-choice-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.65rem;
    margin-bottom: 0.85rem;
}
.rejection-choice {
    display: flex;
    gap: 0.55rem;
    padding: 0.75rem;
    border: 1px solid var(--app-border);
    border-radius: 0.7rem;
    background: var(--app-surface);
    cursor: pointer;
}
.rejection-choice input { margin-top: 0.2rem; }
.rejection-choice strong { display: block; font-size: 0.82rem; }
.rejection-choice small { display: block; margin-top: 0.15rem; color: var(--app-muted); font-size: 0.72rem; line-height: 1.35; }
.rejection-choice.is-selected { border-color: var(--app-accent); box-shadow: 0 0 0 1px var(--app-accent); }
.rejection-choice-danger.is-selected { border-color: var(--app-danger-ink); box-shadow: 0 0 0 1px var(--app-danger-ink); }
.rejection-field { margin-bottom: 0.75rem; }
.rejection-field label {
    display: block;
    margin-bottom: 0.25rem;
    color: var(--app-muted);
    font-size: 0.7rem;
    font-weight: 700;
}
.rejection-field .form-control,
.rejection-field .form-select { min-height: 42px; }
.rejection-drawer-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.6rem;
    padding-top: 0.85rem;
    border-top: 1px solid var(--app-border);
}
.rejection-drawer-actions .btn { min-height: 44px; }
.rejection-page :deep(.btn:focus-visible),
.rejection-page :deep(a:focus-visible),
.rejection-order-link:focus-visible,
.rejection-icon-button:focus-visible,
.rejection-choice:focus-within {
    outline: 2px solid var(--app-accent);
    outline-offset: 2px;
}
@media (max-width: 991.98px) {
    .rejection-header { align-items: flex-start; flex-direction: column; }
    .rejection-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rejection-choice-grid { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 575.98px) {
    .rejection-header { padding: 1.1rem; }
    .rejection-drawer-header { padding: 0.9rem 1rem; }
    .rejection-drawer-body { padding: 0.9rem 1rem; }
    .rejection-meta-grid { grid-template-columns: minmax(0, 1fr); }
    .rejection-summary { padding: 0.85rem; }
}
</style>
