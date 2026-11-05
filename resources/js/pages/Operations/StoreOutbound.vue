<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

defineOptions({ layout: AppLayout });

interface ProductDetail { product_code: string; product_name: string; qty: number; rfid_count: number; rfid_summary: string; released_by_summary: string; }
interface SkuDocument { id: string; reference: string; type_label: string; warehouse_name: string; shipping_number: string | null; date: string | null; }
interface SkuRow { product_id: string; product_code: string; product_name: string; qty: number; document_count: number; store_qty: number; ecommerce_qty: number; other_qty: number; documents: SkuDocument[]; }
interface OutboundRow {
    id: string;
    store_id: string;
    store_name: string;
    store_code: string;
    outbound_type: string | null;
    outbound_type_label: string;
    courier_name: string;
    ecommerce_order_number: string;
    payment_number: string;
    store_return_number: string;
    source_label: string;
    shipping_number: string;
    shipment_date: string | null;
    created_at: string;
    event_date: string | null;
    item_quantity: number;
    total_items: number;
    total_sku: number;
    total_rfid: number;
    has_shipping: boolean;
    status: 'completed' | 'missing_shipping';
    status_label: string;
    nota_print_count: number;
    nota_printed: boolean;
    nota_print_method: 'direct' | 'email';
    nota_print_method_label: string;
    product_details: ProductDetail[];
}
interface Paginator<T> { data: T[]; links: Array<{ url: string | null; label: string; active: boolean }>; from: number | null; to: number | null; total: number; }
interface Props {
    rows: Paginator<OutboundRow>;
    summary: { total_outbounds: number; total_qty: number; total_sku: number; store_qty: number; ecommerce_qty: number; missing_shipping: number; total_rfid: number; total_ecommerce: number; total_returns: number };
    movementSummary: { total_qty: number; total_sku: number; store_qty: number; ecommerce_qty: number; other_qty: number };
    skuRows: SkuRow[];
    stores: Array<{ id: string; name: string; code: string | null }>;
    statusOptions: Array<{ value: string; label: string }>;
    filters: { q: string; store_id: string | null; channel: string | null; status: string | null; from: string | null; to: string | null };
    urls: { index: string; stock: string; inbound: string; monitoring: string };
}

const props = defineProps<Props>();
const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
    hint: store.code ?? undefined,
})));

const filters = reactive({ q: props.filters.q ?? '', store_id: props.filters.store_id ?? '', channel: props.filters.channel ?? '', status: props.filters.status ?? '', from: props.filters.from ?? '', to: props.filters.to ?? '' });
const expanded = ref<Record<string, boolean>>({});
const skuDocsModal = ref<{ sku: SkuRow } | null>(null);
const channelOptions = [{ value: '', label: 'Semua channel' }, { value: 'store', label: 'Store' }, { value: 'ecommerce', label: 'Ecommerce' }];

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);
const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : '-';
const statusClass = (status: OutboundRow['status']) => status === 'completed' ? 'bg-label-success' : 'bg-label-danger';

const applyFilters = () => {
    router.get(props.urls.index, { q: filters.q || undefined, store_id: filters.store_id || undefined, channel: filters.channel || undefined, status: filters.status || undefined, from: filters.from || undefined, to: filters.to || undefined }, { preserveState: true, preserveScroll: true, replace: true });
};
const resetFilters = () => { filters.q = ''; filters.store_id = ''; filters.channel = ''; filters.status = ''; filters.from = ''; filters.to = ''; applyFilters(); };
const toggleDetail = (id: string) => { expanded.value[id] = !expanded.value[id]; };
const openSkuDocs = (sku: SkuRow) => { skuDocsModal.value = { sku }; };
const closeSkuDocs = () => { skuDocsModal.value = null; };
const onKeydown = (event: KeyboardEvent) => { if (event.key === 'Escape' && skuDocsModal.value) closeSkuDocs(); };
const documentReference = (row: OutboundRow) => row.ecommerce_order_number !== '-' ? row.ecommerce_order_number : row.payment_number !== '-' ? row.payment_number : row.store_return_number !== '-' ? row.store_return_number : 'Tanpa referensi';

watch(skuDocsModal, (value) => { document.body.style.overflow = value ? 'hidden' : ''; });
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => { window.removeEventListener('keydown', onKeydown); document.body.style.overflow = ''; });
</script>

<template>
    <Head title="Operasional Barang Keluar Toko" />
    <div class="outbound-page">
        <section class="report-header outbound-header">
            <div>
                <span class="outbound-eyebrow">Operations / Store</span>
                <h1>Operasional Barang Keluar Toko</h1>
                <p>Pantau barang keluar dari store, pembagian store/ecommerce, resi ecommerce, dan referensi transaksi atau surat jalan untuk tipe non-ecommerce.</p>
            </div>
            <div class="outbound-hero-actions">
                <Link :href="props.urls.stock" class="outbound-hero-button"><i class="mdi mdi-clipboard-list-outline me-1"></i>Operasional Stok Toko</Link>
                <Link :href="props.urls.inbound" class="outbound-hero-button"><i class="mdi mdi-tray-arrow-down me-1"></i>Barang Masuk Toko</Link>
                <Link :href="props.urls.monitoring" class="outbound-hero-button outbound-hero-button-outline"><i class="mdi mdi-chart-box-outline me-1"></i>Pemantauan Barang Keluar Toko</Link>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-2"><div class="outbound-summary"><span class="outbound-summary-icon"><i class="mdi mdi-tray-arrow-up"></i></span><div><span>Total Outbound</span><strong>{{ formatNumber(props.summary.total_outbounds) }}</strong></div></div></div>
            <div class="col-6 col-xl-2"><div class="outbound-summary"><span class="outbound-summary-icon"><i class="mdi mdi-package-variant-closed"></i></span><div><span>Total Qty Keluar</span><strong>{{ formatNumber(props.movementSummary.total_qty) }}</strong><small class="outbound-summary-note">{{ formatNumber(props.summary.total_rfid) }} RFID terikat</small></div></div></div>
            <div class="col-6 col-xl-2"><div class="outbound-summary"><span class="outbound-summary-icon"><i class="mdi mdi-barcode"></i></span><div><span>Total SKU Keluar</span><strong>{{ formatNumber(props.movementSummary.total_sku) }}</strong></div></div></div>
            <div class="col-6 col-xl-2"><div class="outbound-summary"><span class="outbound-summary-icon"><i class="mdi mdi-store"></i></span><div><span>Qty Store</span><strong>{{ formatNumber(props.movementSummary.store_qty) }}</strong></div></div></div>
            <div class="col-6 col-xl-2"><div class="outbound-summary"><span class="outbound-summary-icon"><i class="mdi mdi-cart-outline"></i></span><div><span>Qty Ecommerce</span><strong>{{ formatNumber(props.movementSummary.ecommerce_qty) }}</strong></div></div></div>
            <div class="col-6 col-xl-2"><div class="outbound-summary outbound-summary-danger"><span class="outbound-summary-icon"><i class="mdi mdi-barcode-off"></i></span><div><span>Belum Ada Resi (Ecommerce)</span><strong>{{ formatNumber(props.summary.missing_shipping) }}</strong></div></div></div>
        </div>

        <div class="card outbound-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-12 col-lg-6"><label class="form-label">Cari order atau SKU</label><input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Cari order, resi, store, atau SKU..." /></div>
                    <div class="col-6 col-md-4 col-lg-2"><label class="form-label">Store</label><SearchableSelect v-model="filters.store_id" :options="storeOptions" placeholder="Semua store" search-placeholder="Cari store..." /></div>
                    <div class="col-6 col-md-4 col-lg-2"><label class="form-label">Channel</label><select v-model="filters.channel" class="form-select form-select-sm"><option v-for="option in channelOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select></div>
                    <div class="col-12 col-md-4 col-lg-2">
                        <label class="form-label">Status</label><select v-model="filters.status" class="form-select form-select-sm"><option v-for="option in props.statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select></div>
                    <div class="col-6 col-md-4 col-lg-3"><label class="form-label">Dari Tanggal</label><input v-model="filters.from" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-6 col-md-4 col-lg-3"><label class="form-label">Sampai Tanggal</label><input v-model="filters.to" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-12 col-md-4 col-lg-6 d-flex gap-2"><button class="btn btn-sm btn-primary flex-grow-1" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari</button><button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button></div>
                </form>
            </div>
        </div>

        <div class="card outbound-card">
            <div class="outbound-card-header"><div><h2>Antrean Outbound Store</h2><p>Data operasional outbound toko. Laporan lengkap tetap ada di Monitoring.</p></div><span class="outbound-card-meta">{{ formatNumber(props.rows.total) }} dokumen</span></div>
            <div v-if="props.rows.data.length === 0" class="outbound-empty"><i class="mdi mdi-tray-arrow-up"></i><strong>Tidak ada dokumen store outbound</strong><span>Coba ubah kata kunci atau filter.</span></div>
            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 outbound-table">
                    <thead><tr><th>Referensi Outbound</th><th>Store</th><th>Tipe / Kurir</th><th class="text-end">Item</th><th>Tanggal</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <template v-for="row in props.rows.data" :key="row.id">
                            <tr>
                                <td><div class="outbound-main"><strong>{{ documentReference(row) }}</strong><small>{{ row.ecommerce_order_number !== '-' ? row.ecommerce_order_number : row.payment_number !== '-' ? row.payment_number : row.store_return_number !== '-' ? row.store_return_number : 'Tanpa referensi' }}</small><small class="outbound-subvalue">{{ row.source_label }}</small><small v-if="row.shipping_number !== '-'" class="outbound-awb">Resi: {{ row.shipping_number }}</small></div></td>
                                <td><strong>{{ row.store_name }}</strong><small class="outbound-subvalue">{{ row.store_code }}</small></td>
                                <td><strong>{{ row.outbound_type_label }}</strong><small class="outbound-subvalue">{{ row.courier_name || 'Tanpa kurir' }}</small></td>
                                <td class="text-end"><strong>{{ formatNumber(row.total_items) }}</strong><small class="outbound-subvalue">{{ formatNumber(row.total_sku) }} SKU &middot; {{ formatNumber(row.total_rfid) }} RFID</small></td>
                                <td><strong>{{ formatDate(row.event_date || row.created_at) }}</strong><small class="outbound-subvalue">{{ row.shipment_date ? 'Shipment' : 'Dibuat' }}</small></td>
                                <td><span class="badge" :class="statusClass(row.status)">{{ row.status_label }}</span></td>
                                <td class="text-end"><button class="btn btn-sm btn-outline-secondary" type="button" @click="toggleDetail(row.id)"><i class="mdi" :class="expanded[row.id] ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>{{ expanded[row.id] ? 'Tutup' : 'Detail' }}</button></td>
                            </tr>
                            <tr v-if="expanded[row.id]" class="outbound-detail-row"><td colspan="7"><div class="outbound-detail-wrap"><div class="outbound-detail-meta"><span><b>Referensi:</b> {{ documentReference(row) }}</span><span><b>Source:</b> {{ row.source_label }}</span><span v-if="row.outbound_type === 'ecommerce'"><b>Resi:</b> {{ row.shipping_number }}</span><span v-else><b>Dokumen:</b> {{ documentReference(row) }}</span><span><b>Tipe:</b> {{ row.outbound_type_label }}</span><span><b>Kurir:</b> {{ row.courier_name || '-' }}</span><span><b>Qty header:</b> {{ formatNumber(row.item_quantity) }}</span><span><b>RFID:</b> {{ formatNumber(row.total_rfid) }}</span><span><b>Shipment:</b> {{ formatDate(row.shipment_date) }}</span><span><b>Dibuat:</b> {{ formatDate(row.created_at) }}</span><span><b>Nota:</b> {{ row.nota_printed ? 'Sudah dicetak' : 'Belum dicetak' }}</span><span><b>Jumlah cetak:</b> {{ row.nota_print_count > 0 ? `${formatNumber(row.nota_print_count)}x` : '-' }}</span><span><b>Metode nota:</b> {{ row.nota_print_method_label }}</span></div><div class="table-responsive"><table class="table table-sm mb-0 outbound-detail-table"><thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">RFID</th><th>Serial RFID</th><th>Releaser</th></tr></thead><tbody><tr v-for="detail in row.product_details" :key="`${row.id}-${detail.product_code}`"><td><strong>{{ detail.product_name }}</strong><small>{{ detail.product_code }}</small></td><td class="text-end">{{ formatNumber(detail.qty) }}</td><td class="text-end">{{ formatNumber(detail.rfid_count) }}</td><td>{{ detail.rfid_summary }}</td><td>{{ detail.released_by_summary }}</td></tr><tr v-if="row.product_details.length === 0"><td colspan="5" class="text-center text-muted py-3">Tidak ada detail produk.</td></tr></tbody></table></div></div></td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="outbound-footer"><small class="text-muted">Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ props.rows.total }} dokumen</small><PaginationLinks :links="props.rows.links" /></div>
        </div>

        <details class="card outbound-card outbound-sku-card mt-3">
            <summary class="outbound-card-header outbound-sku-summary"><div><h2>SKU Keluar</h2><p>Rincian qty per SKU. Klik angka referensi untuk melihat order, transaksi, surat jalan, atau resi yang memuat SKU tersebut.</p></div><span class="outbound-card-meta">{{ formatNumber(props.skuRows.length) }} SKU <i class="mdi mdi-chevron-down outbound-sku-chevron"></i></span></summary>
            <div v-if="props.skuRows.length === 0" class="outbound-empty"><i class="mdi mdi-barcode-off"></i><strong>Tidak ada SKU keluar</strong><span>Coba ubah kata kunci atau filter.</span></div>
            <div v-else class="table-responsive"><table class="table align-middle mb-0 outbound-table"><thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Store</th><th class="text-end">Ecommerce</th><th class="text-end">Lainnya</th><th class="text-end">Referensi</th></tr></thead><tbody><tr v-for="sku in props.skuRows" :key="sku.product_id"><td><div class="outbound-main"><strong>{{ sku.product_name }}</strong><small>{{ sku.product_code }}</small></div></td><td class="text-end"><strong>{{ formatNumber(sku.qty) }}</strong></td><td class="text-end">{{ formatNumber(sku.store_qty) }}</td><td class="text-end">{{ formatNumber(sku.ecommerce_qty) }}</td><td class="text-end">{{ formatNumber(sku.other_qty) }}</td><td class="text-end"><button class="stock-doc-link" type="button" @click="openSkuDocs(sku)">{{ formatNumber(sku.document_count) }}</button></td></tr></tbody></table></div>
        </details>
    </div>

    <Teleport to="body">
        <div v-if="skuDocsModal" class="sku-docs-backdrop" @click.self="closeSkuDocs">
            <div class="sku-docs-modal" role="dialog" aria-modal="true" aria-labelledby="sku-docs-title">
                <div class="sku-docs-header"><div><h5 id="sku-docs-title" class="mb-0">Referensi SKU Keluar</h5><small>{{ skuDocsModal.sku.product_name }} &middot; {{ skuDocsModal.sku.product_code }}</small></div><button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup dokumen SKU" @click="closeSkuDocs"><i class="mdi mdi-close"></i></button></div>
                <div class="sku-docs-body"><p class="text-muted small">Referensi menampilkan order, transaksi, surat jalan, atau resi yang memuat SKU ini.</p><div class="table-responsive"><table class="table table-sm mb-0 outbound-detail-table"><thead><tr><th>Referensi</th><th>Tipe</th><th>Store</th><th>Resi (Ecommerce)</th><th>Tanggal</th></tr></thead><tbody><tr v-for="document in skuDocsModal.sku.documents" :key="document.id"><td>{{ document.reference }}</td><td>{{ document.type_label }}</td><td>{{ document.warehouse_name }}</td><td>{{ document.shipping_number || '-' }}</td><td>{{ formatDate(document.date) }}</td></tr></tbody></table></div></div>
                <div class="sku-docs-footer"><button class="btn btn-sm btn-outline-secondary" type="button" @click="closeSkuDocs">Tutup</button></div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.outbound-page { color: var(--app-ink); }
.outbound-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; }
.outbound-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.outbound-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.outbound-header p { max-width: 760px; margin: 0; color: var(--app-hero-muted) !important; }
.outbound-hero-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.outbound-hero-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface); color: var(--app-ink); font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.outbound-hero-button-outline { border: 1px solid var(--app-hero-muted); background: transparent; color: var(--app-hero-text); }
.outbound-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.outbound-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.outbound-summary span:not(.outbound-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.outbound-summary strong { display: block; margin-top: 0.15rem; font-size: 1.35rem; }
.outbound-summary-note { display: block; margin-top: 0.1rem; color: var(--app-muted); font-size: 0.68rem; }
.outbound-summary-warning .outbound-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.outbound-summary-danger .outbound-summary-icon { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.outbound-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.outbound-card-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.outbound-card-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.outbound-card-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.outbound-card-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.outbound-table th { padding: 0.6rem 0.9rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.outbound-table td { padding: 0.8rem 0.9rem; border-color: var(--app-border); color: var(--app-ink); }
.outbound-main strong, .outbound-main small, .outbound-subvalue { display: block; }
.outbound-main strong { font-size: 0.86rem; }
.outbound-main small, .outbound-subvalue { color: var(--app-muted); font-size: 0.73rem; }
.outbound-main .outbound-awb { color: var(--app-accent-ink); font-weight: 600; }
.outbound-detail-row > td { padding: 0 !important; background: var(--app-surface-2); }
.outbound-detail-wrap { padding: 0.5rem 1rem 1rem 2.2rem; }
.outbound-detail-meta { display: flex; flex-wrap: wrap; gap: 0.4rem 1.2rem; margin-bottom: 0.5rem; color: var(--app-muted); font-size: 0.74rem; }
.outbound-detail-table th { padding: 0.45rem 0.55rem; background: transparent; font-size: 0.64rem; }
.outbound-detail-table td { padding: 0.55rem; font-size: 0.78rem; }
.outbound-detail-table td strong, .outbound-detail-table td small { display: block; }
.outbound-detail-table td small { color: var(--app-muted); font-size: 0.7rem; }
.outbound-sku-card { overflow: hidden; }
.outbound-sku-summary { cursor: pointer; list-style: none; }
.outbound-sku-summary::-webkit-details-marker { display: none; }
.outbound-sku-summary:focus-visible { outline: 2px solid var(--app-accent); outline-offset: -2px; }
.outbound-sku-chevron { margin-left: 0.35rem; transition: transform 160ms ease; }
.outbound-sku-card[open] .outbound-sku-chevron { transform: rotate(180deg); }
.outbound-sku-card:not([open]) .outbound-card-header { border-bottom: 0; }
.stock-doc-link { border: 0; background: transparent; color: var(--app-accent-ink); font-size: 0.8rem; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
.sku-docs-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: flex-start; justify-content: center; padding: min(8vh, 4rem) 1rem 1rem; overflow-y: auto; background: rgba(15, 23, 42, 0.62); }
.sku-docs-modal { width: min(680px, 100%); overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.28); color: var(--app-ink); }
.sku-docs-header, .sku-docs-footer { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; }
.sku-docs-header { border-bottom: 1px solid var(--app-border); }
.sku-docs-footer { justify-content: flex-end; border-top: 1px solid var(--app-border); background: var(--app-surface-2); }
.sku-docs-body { padding: 1rem 1.1rem; }
.outbound-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.outbound-empty { display: grid; place-items: center; gap: 0.3rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.outbound-empty i { font-size: 1.8rem; color: var(--app-accent-ink); }
.outbound-empty strong { color: var(--app-ink); }
@media (max-width: 991.98px) {
    .outbound-header { align-items: flex-start; flex-direction: column; }
    .outbound-card-header { align-items: flex-start; flex-direction: column; }
    .outbound-detail-wrap { padding-left: 0.75rem; }
}
</style>
