<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SearchableSelect from '@/components/SearchableSelect.vue';
import ReportTableControls from '@/components/ui/ReportTableControls.vue';
import ReportPeriodPresets from '@/components/ui/ReportPeriodPresets.vue';
import { downloadExcelTable } from '@/composables/useExcelExport';
import { useReportTableColumns, useReportTableDensity } from '@/composables/useReportTable';

interface Option { id: string; name: string }
interface PosRow { visit_id: string; check_in_at: string | null; paid_at: string | null; store_name: string | null; customer_name: string | null; wa_number: string | null; product_name: string | null }
interface EcomRow { cart_id: string; cart_at: string | null; paid_at: string | null; order_number: string | null; customer_name: string | null; wa_number: string | null; product_name: string | null; qty: number }

interface Props {
    from: string;
    to: string;
    channel: string;
    storeId: string | null;
    stores: Option[];
    search: string | null;
    custId: string | null;
    rowsPos: PosRow[];
    rowsEcom: EcomRow[];
    urls: { index: string; ecom: string };
}

const props = defineProps<Props>();

const posColumns = [
    { key: 'check_in', label: 'Check-in' },
    { key: 'paid', label: 'Dibayar' },
    { key: 'store', label: 'Toko' },
    { key: 'customer', label: 'Pelanggan' },
    { key: 'wa', label: 'WA' },
    { key: 'product', label: 'Produk' },
];
const ecomColumns = [
    { key: 'cart', label: 'Cart' },
    { key: 'paid', label: 'Dibayar' },
    { key: 'order', label: 'Order' },
    { key: 'customer', label: 'Pelanggan' },
    { key: 'wa', label: 'WA' },
    { key: 'product', label: 'Produk' },
    { key: 'qty', label: 'Qty' },
];
const posSimpleColumns = ['check_in', 'paid', 'store', 'customer', 'product'];
const ecomSimpleColumns = ['cart', 'paid', 'order', 'customer', 'product', 'qty'];
const { density, isCompact, toggleDensity } = useReportTableDensity();
const posTable = useReportTableColumns('veestore.history-customer.pos.columns', posColumns.map((column) => column.key));
const ecomTable = useReportTableColumns('veestore.history-customer.ecom.columns', ecomColumns.map((column) => column.key));
const posColumnCount = posTable.columnCount;
const ecomColumnCount = ecomTable.columnCount;

const form = reactive({
    from: props.from,
    to: props.to,
    channel: props.channel,
    store_id: props.storeId ?? '',
    q: props.search ?? '',
});

const activeColumns = computed(() => (form.channel === 'ecom' ? ecomColumns : posColumns));
const activeVisibleColumns = computed(() => (
    form.channel === 'ecom' ? ecomTable.visibleColumns.value : posTable.visibleColumns.value
));
const activeSimpleColumns = computed(() => (form.channel === 'ecom' ? ecomSimpleColumns : posSimpleColumns));
const isPosVisible = (key: string) => posTable.isVisible(key);
const isEcomVisible = (key: string) => ecomTable.isVisible(key);
const toggleActiveColumn = (key: string) => {
    if (form.channel === 'ecom') {
        ecomTable.toggleColumn(key);
        return;
    }

    posTable.toggleColumn(key);
};
const showAllActiveColumns = () => {
    if (form.channel === 'ecom') {
        ecomTable.showAll();
        return;
    }

    posTable.showAll();
};
const applyActiveSimpleColumns = () => {
    if (form.channel === 'ecom') {
        ecomTable.applyPreset(ecomSimpleColumns);
        return;
    }

    posTable.applyPreset(posSimpleColumns);
};

const storeOptions = computed(() => props.stores.map((store) => ({
    value: store.id,
    label: store.name,
})));

const apply = () => {
    router.get(props.urls.index, {
        from: form.from || undefined,
        to: form.to || undefined,
        channel: form.channel || undefined,
        store_id: form.store_id || undefined,
        q: form.q || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const reset = () => {
    form.from = props.from;
    form.to = props.to;
    form.channel = 'pos';
    form.store_id = '';
    form.q = '';
    apply();
};

const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : '-';
const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);

const rows = computed(() => form.channel === 'ecom' ? props.rowsEcom : props.rowsPos);

const exportRows = () => {
    if (form.channel === 'ecom') {
        downloadExcelTable('History-Customer-Ecommerce', 'Transaksi Ecommerce', [
            { key: 'cart_at', label: 'Tanggal Cart' },
            { key: 'paid_at', label: 'Tanggal Pembayaran' },
            { key: 'order_number', label: 'Nomor Order' },
            { key: 'customer_name', label: 'Customer' },
            { key: 'wa_number', label: 'Nomor WA' },
            { key: 'product_name', label: 'Produk' },
            { key: 'qty', label: 'Qty' },
        ], props.rowsEcom, {
            title: 'History Customer Ecommerce',
            filters: [
                { label: 'Customer', value: props.custId },
                { label: 'Dari', value: props.from },
                { label: 'Sampai', value: props.to },
                { label: 'Channel', value: 'Ecommerce' },
                { label: 'Store', value: storeOptions.value.find((option) => option.value === props.storeId)?.label },
                { label: 'Pencarian', value: props.search },
            ],
        });

        return;
    }

    downloadExcelTable('History-Customer-POS', 'Transaksi POS', [
        { key: 'check_in_at', label: 'Check-in' },
        { key: 'paid_at', label: 'Tanggal Pembayaran' },
        { key: 'store_name', label: 'Store' },
        { key: 'customer_name', label: 'Customer' },
        { key: 'wa_number', label: 'Nomor WA' },
        { key: 'product_name', label: 'Produk' },
    ], props.rowsPos, {
        title: 'History Customer POS',
        filters: [
            { label: 'Customer', value: props.custId },
            { label: 'Dari', value: props.from },
            { label: 'Sampai', value: props.to },
            { label: 'Channel', value: 'POS' },
            { label: 'Store', value: storeOptions.value.find((option) => option.value === props.storeId)?.label },
            { label: 'Pencarian', value: props.search },
        ],
    });
};
</script>

<template>
    <Head title="Riwayat Pelanggan" />
    <div class="report-page">
        <div class="report-header d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <span class="report-eyebrow">Laporan / Pelanggan</span>
                <h1>Riwayat Pelanggan</h1>
                <p>Riwayat transaksi dan kunjungan pelanggan per kanal.</p>
            </div>
            <button class="btn btn-light btn-sm" type="button" @click="exportRows">
                <i class="mdi mdi-file-excel-outline me-1" aria-hidden="true"></i>Ekspor Excel
            </button>
        </div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <ReportPeriodPresets
                    class="mb-2"
                    :route="props.urls.index"
                    from-key="from"
                    to-key="to"
                    :from="props.from"
                    :to="props.to"
                    :query="form"
                />
                <form class="row g-2 align-items-end" @submit.prevent="apply">
                    <div class="col-md-2"><label for="history-from" class="form-label">Tanggal dari</label><input id="history-from" v-model="form.from" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label for="history-to" class="form-label">Tanggal sampai</label><input id="history-to" v-model="form.to" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label for="history-channel" class="form-label">Kanal</label><select id="history-channel" v-model="form.channel" class="form-select form-select-sm"><option value="pos">POS</option><option value="ecom">E-commerce</option></select></div>
                    <div class="col-md-3"><label for="history-store" class="form-label">Toko</label><SearchableSelect id="history-store" v-model="form.store_id" :options="storeOptions" placeholder="Semua toko" search-placeholder="Cari toko..." /></div>
                    <div class="col-md-3"><label for="history-q" class="form-label">Cari</label><input id="history-q" v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Nama, WA, atau produk..." /></div>
                    <div class="col-12 d-flex gap-2 mt-2"><button class="btn btn-sm btn-primary" type="submit">Terapkan filter</button><button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button></div>
                </form>
            </div>
        </div>

        <div class="card report-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="h6 mb-0">{{ form.channel === 'ecom' ? 'Transaksi e-commerce' : 'Transaksi POS' }}</h2>
                    <small class="text-muted">{{ rows.length }} baris pada filter ini</small>
                </div>
                <ReportTableControls
                    :columns="activeColumns"
                    :visible="activeVisibleColumns"
                    :density="density"
                    :simple-preset="activeSimpleColumns"
                    @toggle-density="toggleDensity"
                    @toggle-column="toggleActiveColumn"
                    @show-all="showAllActiveColumns"
                    @apply-simple="applyActiveSimpleColumns"
                />
            </div>
            <div class="table-responsive report-table-wrap" :class="{ 'is-compact': isCompact }" tabindex="0" role="region" aria-label="Riwayat transaksi pelanggan">
                <table v-if="form.channel === 'pos'" class="table table-sm mb-0">
                    <caption class="visually-hidden">Riwayat transaksi POS pelanggan</caption>
                    <thead>
                        <tr>
                            <th v-if="isPosVisible('check_in')">Check-in</th>
                            <th v-if="isPosVisible('paid')">Dibayar</th>
                            <th v-if="isPosVisible('store')">Toko</th>
                            <th v-if="isPosVisible('customer')">Pelanggan</th>
                            <th v-if="isPosVisible('wa')">WA</th>
                            <th v-if="isPosVisible('product')">Produk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rowsPos" :key="row.visit_id + (row.product_name ?? '')">
                            <td v-if="isPosVisible('check_in')">{{ formatDate(row.check_in_at) }}</td>
                            <td v-if="isPosVisible('paid')">{{ formatDate(row.paid_at) }}</td>
                            <td v-if="isPosVisible('store')">{{ row.store_name ?? '-' }}</td>
                            <td v-if="isPosVisible('customer')">{{ row.customer_name ?? '-' }}</td>
                            <td v-if="isPosVisible('wa')">{{ row.wa_number ?? '-' }}</td>
                            <td v-if="isPosVisible('product')">{{ row.product_name ?? '-' }}</td>
                        </tr>
                        <tr v-if="rowsPos.length === 0"><td :colspan="posColumnCount" class="report-empty">Belum ada transaksi POS pada filter ini.</td></tr>
                    </tbody>
                </table>
                <table v-else class="table table-sm mb-0">
                    <caption class="visually-hidden">Riwayat transaksi e-commerce pelanggan</caption>
                    <thead>
                        <tr>
                            <th v-if="isEcomVisible('cart')">Cart</th>
                            <th v-if="isEcomVisible('paid')">Dibayar</th>
                            <th v-if="isEcomVisible('order')">Order</th>
                            <th v-if="isEcomVisible('customer')">Pelanggan</th>
                            <th v-if="isEcomVisible('wa')">WA</th>
                            <th v-if="isEcomVisible('product')">Produk</th>
                            <th v-if="isEcomVisible('qty')" class="text-end">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rowsEcom" :key="row.cart_id">
                            <td v-if="isEcomVisible('cart')">{{ formatDate(row.cart_at) }}</td>
                            <td v-if="isEcomVisible('paid')">{{ formatDate(row.paid_at) }}</td>
                            <td v-if="isEcomVisible('order')">{{ row.order_number ?? '-' }}</td>
                            <td v-if="isEcomVisible('customer')">{{ row.customer_name ?? '-' }}</td>
                            <td v-if="isEcomVisible('wa')">{{ row.wa_number ?? '-' }}</td>
                            <td v-if="isEcomVisible('product')">{{ row.product_name ?? '-' }}</td>
                            <td v-if="isEcomVisible('qty')" class="text-end">{{ formatNumber(row.qty) }}</td>
                        </tr>
                        <tr v-if="rowsEcom.length === 0"><td :colspan="ecomColumnCount" class="report-empty">Belum ada transaksi e-commerce pada filter ini.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.report-page { --report-ink: var(--app-ink); --report-border: var(--app-border); --report-muted: var(--app-muted); color: var(--report-ink); }
.report-header { padding: 1.5rem; margin-bottom: 1rem; border-radius: var(--app-radius-xl); background: var(--app-hero-bg); color: var(--app-hero-text); }
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: var(--app-text-xs); font-weight: var(--app-weight-semibold); }
.report-header h1 { margin: 0 0 0.35rem; font-size: var(--app-text-3xl); font-weight: var(--app-weight-semibold); }
.report-header p { margin: 0; color: var(--app-hero-muted); font-size: var(--app-text-sm); }
.report-card { border: 1px solid var(--report-border); border-radius: var(--app-radius-lg); box-shadow: none; }
.report-card th { color: var(--report-muted); font-size: var(--app-text-xs); font-weight: var(--app-weight-semibold); }
.report-empty { padding: 2rem 1rem; color: var(--report-muted); text-align: center; }
</style>
