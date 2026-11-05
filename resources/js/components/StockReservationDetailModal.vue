<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface ReservationDetail {
    id: string;
    reference_type: string | null;
    order_number: string | null;
    reserved_by_name: string | null;
    reserved_by_role: string | null;
    reserved_for_name: string | null;
    reserved_quantity: number | null;
    remaining_quantity: number | null;
    reserved_at: string | null;
    expires_at: string | null;
    is_expired: boolean;
}

const props = withDefaults(defineProps<{
    open: boolean;
    url: string;
    handlerType: 'warehouse' | 'store';
    handlerId: string | null;
    productId: string | null;
    productName?: string;
    productCode?: string;
    locationName?: string;
    locationCode?: string;
    unitName?: string;
    reservedQty?: number;
}>(), {
    productName: '',
    productCode: '',
    locationName: '',
    locationCode: '',
    unitName: '',
    reservedQty: 0,
});

const emit = defineEmits<{
    (event: 'close'): void;
}>();

const rows = ref<ReservationDetail[]>([]);
const loading = ref(false);
const errorMessage = ref('');
const modal = ref<HTMLElement | null>(null);
const closeButton = ref<HTMLButtonElement | null>(null);

let controller: AbortController | null = null;
let requestId = 0;
let previousFocus: HTMLElement | null = null;

const handlerLabel = computed(() => (props.handlerType === 'store' ? 'Cabang' : 'Gudang'));
const totalRemaining = computed(() => rows.value.reduce((total, row) => total + Number(row.remaining_quantity ?? 0), 0));
const totalOrders = computed(() => rows.value.length);

const formatNumber = (value: number | null | undefined) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const formatDateTime = (value: string | null): string => {
    if (!value) return '-';

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(parsed);
};

const channelLabel = (type: string | null): string => {
    if (type === 'store_order') return 'Store Order';
    if (type === 'ecommerce_order') return 'Ecommerce';

    return 'Reservasi';
};

const channelClass = (type: string | null): string => {
    if (type === 'store_order') return 'is-store';
    if (type === 'ecommerce_order') return 'is-ecommerce';

    return 'is-neutral';
};

const load = async () => {
    if (!props.open) return;

    if (!props.url || !props.handlerId || !props.productId) {
        rows.value = [];
        errorMessage.value = 'Data reservasi belum lengkap sehingga rincian tidak bisa dimuat.';

        return;
    }

    controller?.abort();
    controller = new AbortController();

    const currentRequest = ++requestId;

    loading.value = true;
    errorMessage.value = '';
    rows.value = [];

    const params = new URLSearchParams({
        [props.handlerType === 'store' ? 'store_id' : 'warehouse_id']: props.handlerId,
        product_id: props.productId,
    });

    try {
        const response = await fetch(`${props.url}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });

        if (!response.ok) {
            throw new Error(`Reservation request failed with status ${response.status}`);
        }

        const payload = await response.json();

        if (currentRequest !== requestId) return;

        rows.value = Array.isArray(payload?.rows) ? payload.rows : [];
    } catch (error) {
        if ((error as Error).name === 'AbortError') return;
        if (currentRequest !== requestId) return;

        rows.value = [];
        errorMessage.value = 'Detail reservasi gagal dimuat. Periksa koneksi lalu coba lagi.';
    } finally {
        if (currentRequest === requestId) loading.value = false;
    }
};

const close = () => {
    emit('close');
};

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const trapFocus = (event: KeyboardEvent) => {
    const root = modal.value;

    if (!root) return;

    const items = Array.from(root.querySelectorAll<HTMLElement>(focusableSelector))
        .filter((item) => item.offsetParent !== null);

    if (items.length === 0) return;

    const first = items[0];
    const last = items[items.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();

        return;
    }

    if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (!props.open) return;

    if (event.key === 'Escape') {
        event.stopPropagation();
        close();

        return;
    }

    if (event.key === 'Tab') {
        trapFocus(event);
    }
};

watch(
    () => [props.open, props.handlerId, props.productId, props.url],
    async ([open]) => {
        if (open) {
            previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            document.body.style.overflow = 'hidden';

            await nextTick();
            closeButton.value?.focus();
            load();

            return;
        }

        controller?.abort();
        controller = null;
        requestId += 1;
        loading.value = false;
        errorMessage.value = '';
        rows.value = [];
        document.body.style.overflow = '';
        previousFocus?.focus();
        previousFocus = null;
    },
    { immediate: true },
);

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    controller?.abort();
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div v-if="props.open" class="reservation-backdrop" @click.self="close">
            <section
                ref="modal"
                class="reservation-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="stock-reservation-title"
                :aria-busy="loading"
            >
                <header class="reservation-header">
                    <div class="reservation-heading">
                        <span class="reservation-heading-icon" aria-hidden="true">
                            <i class="mdi mdi-account-search-outline"></i>
                        </span>
                        <div>
                            <h2 id="stock-reservation-title">Reservasi Stok</h2>
                            <p class="reservation-subtitle">
                                <strong>{{ props.productName || 'Produk' }}</strong>
                                <template v-if="props.productCode"> · {{ props.productCode }}</template>
                                <span class="reservation-location">
                                    {{ handlerLabel }} {{ props.locationName || '-' }}<template v-if="props.locationCode"> ({{ props.locationCode }})</template>
                                </span>
                            </p>
                        </div>
                    </div>
                    <button
                        ref="closeButton"
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        aria-label="Tutup detail reservasi"
                        @click="close"
                    >
                        <i class="mdi mdi-close" aria-hidden="true"></i>
                    </button>
                </header>

                <div class="reservation-body">
                    <div class="reservation-summary">
                        <div>
                            <span>Qty reserved</span>
                            <strong>
                                {{ formatNumber(props.reservedQty) }}
                                <small v-if="props.unitName">{{ props.unitName }}</small>
                            </strong>
                        </div>
                        <div>
                            <span>Order aktif</span>
                            <strong>{{ loading || errorMessage ? '—' : formatNumber(totalOrders) }}</strong>
                        </div>
                        <div>
                            <span>Sisa ditahan</span>
                            <strong>
                                {{ loading || errorMessage ? '—' : formatNumber(totalRemaining) }}
                                <small v-if="props.unitName">{{ props.unitName }}</small>
                            </strong>
                        </div>
                    </div>

                    <p class="reservation-hint">
                        <i class="mdi mdi-information-outline" aria-hidden="true"></i>
                        Reservasi aktif mengurangi stok tersedia. Setiap baris menunjukkan order dan pihak yang menahan stok.
                    </p>

                    <div v-if="loading" class="reservation-state" role="status" aria-live="polite">
                        <i class="mdi mdi-loading mdi-spin reservation-state-icon" aria-hidden="true"></i>
                        <strong>Memuat detail reservasi</strong>
                        <span>Mengambil order yang masih menahan stok produk ini.</span>
                    </div>

                    <div v-else-if="errorMessage" class="reservation-state is-error" role="alert">
                        <i class="mdi mdi-alert-circle-outline reservation-state-icon" aria-hidden="true"></i>
                        <strong>Detail reservasi belum bisa ditampilkan</strong>
                        <span>{{ errorMessage }}</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="load">
                            <i class="mdi mdi-refresh" aria-hidden="true"></i>
                            Coba lagi
                        </button>
                    </div>

                    <div v-else-if="rows.length === 0" class="reservation-state">
                        <i class="mdi mdi-lock-open-variant-outline reservation-state-icon" aria-hidden="true"></i>
                        <strong>Tidak ada rincian reservasi aktif</strong>
                        <span v-if="props.reservedQty > 0">
                            Stok masih tercatat reserved, tetapi order penahannya sudah tidak aktif atau datanya tidak ditemukan.
                            Muat ulang halaman untuk memperbarui angka.
                        </span>
                        <span v-else>
                            Seluruh stok produk ini di lokasi tersebut sedang tidak ditahan order mana pun.
                        </span>
                    </div>

                    <div v-else class="reservation-table-wrap">
                        <table class="reservation-table">
                            <caption class="visually-hidden">
                                Order yang mereservasi stok {{ props.productName }} di {{ handlerLabel.toLowerCase() }} {{ props.locationName }}
                            </caption>
                            <thead>
                                <tr>
                                    <th scope="col">Sumber</th>
                                    <th scope="col">Order</th>
                                    <th scope="col">Direservasi oleh</th>
                                    <th scope="col">Untuk</th>
                                    <th scope="col" class="text-end">Qty</th>
                                    <th scope="col">Waktu reservasi</th>
                                    <th scope="col">Berlaku sampai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in rows" :key="row.id">
                                    <td data-label="Sumber">
                                        <span class="reservation-badge" :class="channelClass(row.reference_type)">
                                            {{ channelLabel(row.reference_type) }}
                                        </span>
                                    </td>
                                    <td data-label="Order">
                                        <strong class="reservation-order">{{ row.order_number || '-' }}</strong>
                                    </td>
                                    <td data-label="Direservasi oleh">
                                        <div class="reservation-stack">
                                            <strong>{{ row.reserved_by_name || '-' }}</strong>
                                            <small v-if="row.reserved_by_role">{{ row.reserved_by_role }}</small>
                                        </div>
                                    </td>
                                    <td data-label="Untuk">
                                        <span>{{ row.reserved_for_name || '-' }}</span>
                                    </td>
                                    <td data-label="Qty" class="text-end">
                                        <div class="reservation-stack is-end">
                                            <strong>{{ formatNumber(row.remaining_quantity) }}</strong>
                                            <small v-if="Number(row.remaining_quantity) !== Number(row.reserved_quantity)">
                                                dari {{ formatNumber(row.reserved_quantity) }}
                                            </small>
                                        </div>
                                    </td>
                                    <td data-label="Waktu reservasi">{{ formatDateTime(row.reserved_at) }}</td>
                                    <td data-label="Berlaku sampai">
                                        <div class="reservation-stack">
                                            <span>{{ formatDateTime(row.expires_at) }}</span>
                                            <span v-if="row.is_expired" class="reservation-badge is-expired">Kedaluwarsa</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="rows.some((row) => row.is_expired)" class="reservation-expiry-note">
                            Reservasi kedaluwarsa tetap menahan stok sampai dilepas oleh sistem. Hubungi tim terkait bila stok perlu segera tersedia.
                        </p>
                    </div>
                </div>

                <footer class="reservation-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="close">
                        Tutup
                    </button>
                </footer>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.reservation-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1080;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: min(6vh, 3rem) 1rem 1rem;
    overflow-y: auto;
    background: rgba(15, 23, 42, 0.64);
}

.reservation-modal {
    width: min(1040px, 100%);
    overflow: hidden;
    border: 1px solid var(--app-border);
    border-radius: 0.9rem;
    background: var(--app-surface);
    box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.3);
    color: var(--app-ink);
}

.reservation-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid var(--app-border);
}

.reservation-heading {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.reservation-heading-icon {
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

.reservation-header h2 {
    margin: 0 0 0.25rem;
    font-size: 1.05rem;
}

.reservation-subtitle {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.15rem 0.35rem;
    margin: 0;
    color: var(--app-muted);
    font-size: 0.8rem;
}

.reservation-subtitle strong {
    color: var(--app-ink);
}

.reservation-location::before {
    content: '·';
    margin-right: 0.35rem;
}

.reservation-body {
    padding: 1rem 1.1rem;
}

.reservation-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.6rem;
    margin-bottom: 0.8rem;
}

.reservation-summary > div {
    padding: 0.7rem 0.8rem;
    border: 1px solid var(--app-border);
    border-radius: 0.6rem;
    background: var(--app-surface-2);
}

.reservation-summary span {
    display: block;
    color: var(--app-muted);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.reservation-summary strong {
    display: block;
    margin-top: 0.15rem;
    font-size: 1.15rem;
}

.reservation-summary small {
    color: var(--app-muted);
    font-size: 0.72rem;
    font-weight: 600;
}

.reservation-hint {
    display: flex;
    align-items: flex-start;
    gap: 0.45rem;
    margin: 0 0 0.9rem;
    color: var(--app-muted);
    font-size: 0.8rem;
}

.reservation-hint i {
    margin-top: 0.1rem;
    color: var(--app-accent-ink);
}

.reservation-state {
    display: grid;
    place-items: center;
    gap: 0.3rem;
    padding: 2.5rem 1rem;
    border: 1px dashed var(--app-border);
    border-radius: 0.7rem;
    color: var(--app-muted);
    text-align: center;
}

.reservation-state-icon {
    font-size: 1.8rem;
    color: var(--app-accent-ink);
}

.reservation-state strong {
    color: var(--app-ink);
}

.reservation-state span {
    max-width: 34rem;
    font-size: 0.82rem;
}

.reservation-state.is-error .reservation-state-icon {
    color: var(--app-danger-ink);
}

.reservation-state.is-error .btn {
    margin-top: 0.5rem;
}

.reservation-table-wrap {
    overflow-x: auto;
}

.reservation-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
}

.reservation-table th {
    padding: 0.55rem 0.65rem;
    border-bottom: 1px solid var(--app-border);
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.reservation-table td {
    padding: 0.65rem;
    border-bottom: 1px solid var(--app-border);
    vertical-align: top;
}

.reservation-table tbody tr:last-child td {
    border-bottom: 0;
}

.reservation-table td strong,
.reservation-table td small {
    display: block;
}

.reservation-table td small {
    color: var(--app-muted);
    font-size: 0.72rem;
}

.reservation-stack {
    display: grid;
    gap: 0.15rem;
    min-width: 0;
}

.reservation-stack.is-end {
    justify-items: end;
}

.reservation-order {
    font-size: 0.8rem;
    overflow-wrap: anywhere;
}

.reservation-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.45rem;
    border-radius: 0.35rem;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}

.reservation-badge.is-store {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.reservation-badge.is-ecommerce {
    background: var(--app-info-bg);
    color: var(--app-info-ink);
}

.reservation-badge.is-neutral {
    background: var(--app-surface-2);
    color: var(--app-muted);
}

.reservation-badge.is-expired {
    margin-top: 0.25rem;
    background: var(--app-danger-bg);
    color: var(--app-danger-ink);
}

.reservation-expiry-note {
    margin: 0.7rem 0 0;
    padding: 0.6rem 0.7rem;
    border-radius: 0.5rem;
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
    font-size: 0.78rem;
}

.reservation-footer {
    display: flex;
    justify-content: flex-end;
    padding: 0.9rem 1.1rem;
    border-top: 1px solid var(--app-border);
    background: var(--app-surface-2);
}

.reservation-table .text-end,
.reservation-table td.text-end {
    text-align: right;
}

@media (max-width: 767.98px) {
    .reservation-backdrop {
        padding: 0.5rem;
    }

    .reservation-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reservation-table thead {
        display: none;
    }

    .reservation-table,
    .reservation-table tbody,
    .reservation-table tr,
    .reservation-table td {
        display: block;
        width: 100%;
    }

    .reservation-table tr {
        padding: 0.35rem 0;
        border-bottom: 1px solid var(--app-border);
    }

    .reservation-table tbody tr:last-child {
        border-bottom: 0;
    }

    .reservation-table td {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.35rem 0.2rem;
        border-bottom: 0;
        text-align: right;
    }

    .reservation-table td::before {
        content: attr(data-label);
        flex: 0 0 42%;
        color: var(--app-muted);
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-align: left;
        text-transform: uppercase;
    }

    .reservation-table td.text-end {
        text-align: right;
    }

    .reservation-table td > * {
        min-width: 0;
    }
}

@media (max-width: 575.98px) {
    .reservation-heading-icon {
        display: none;
    }

    .reservation-summary {
        grid-template-columns: 1fr;
    }
}
</style>
