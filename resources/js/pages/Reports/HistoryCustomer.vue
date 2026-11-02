<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';

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
const form = reactive({
    from: props.from,
    to: props.to,
    channel: props.channel,
    store_id: props.storeId ?? '',
    q: props.search ?? '',
});

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
</script>

<template>
    <Head title="History Customer" />
    <div class="report-page">
        <div class="report-header">
            <div>
                <span class="report-eyebrow">Reports</span>
                <h1>History Customer</h1>
                <p>Riwayat transaksi dan kunjungan customer per channel.</p>
            </div>
        </div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="apply">
                    <div class="col-md-2"><label class="form-label">Dari</label><input v-model="form.from" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label class="form-label">Sampai</label><input v-model="form.to" class="form-control form-control-sm" type="date" /></div>
                    <div class="col-md-2"><label class="form-label">Channel</label><select v-model="form.channel" class="form-select form-select-sm"><option value="pos">POS</option><option value="ecom">Ecommerce</option></select></div>
                    <div class="col-md-3"><label class="form-label">Store</label><select v-model="form.store_id" class="form-select form-select-sm"><option value="">Semua Store</option><option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option></select></div>
                    <div class="col-md-3"><label class="form-label">Cari</label><input v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Nama, WA, produk..." /></div>
                    <div class="col-12 d-flex gap-2 mt-2"><button class="btn btn-sm btn-primary" type="submit">Filter</button><button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button></div>
                </form>
            </div>
        </div>

        <div class="card report-card">
            <div class="card-header"><h6 class="mb-0">{{ form.channel === 'ecom' ? 'Transaksi Ecommerce' : 'Transaksi POS' }}</h6></div>
            <div class="table-responsive">
                <table v-if="form.channel === 'pos'" class="table table-sm mb-0"><thead><tr><th>Check-in</th><th>Dibayar</th><th>Store</th><th>Customer</th><th>WA</th><th>Produk</th></tr></thead><tbody>
                    <tr v-for="row in rowsPos" :key="row.visit_id + (row.product_name ?? '')"><td>{{ formatDate(row.check_in_at) }}</td><td>{{ formatDate(row.paid_at) }}</td><td>{{ row.store_name ?? '-' }}</td><td>{{ row.customer_name ?? '-' }}</td><td>{{ row.wa_number ?? '-' }}</td><td>{{ row.product_name ?? '-' }}</td></tr>
                    <tr v-if="rowsPos.length === 0"><td colspan="6" class="report-empty">Belum ada data.</td></tr>
                </tbody></table>
                <table v-else class="table table-sm mb-0"><thead><tr><th>Cart</th><th>Dibayar</th><th>Order</th><th>Customer</th><th>WA</th><th>Produk</th><th class="text-end">Qty</th></tr></thead><tbody>
                    <tr v-for="row in rowsEcom" :key="row.cart_id"><td>{{ formatDate(row.cart_at) }}</td><td>{{ formatDate(row.paid_at) }}</td><td>{{ row.order_number ?? '-' }}</td><td>{{ row.customer_name ?? '-' }}</td><td>{{ row.wa_number ?? '-' }}</td><td>{{ row.product_name ?? '-' }}</td><td class="text-end">{{ formatNumber(row.qty) }}</td></tr>
                    <tr v-if="rowsEcom.length === 0"><td colspan="7" class="report-empty">Belum ada data.</td></tr>
                </tbody></table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.report-page { --report-ink: var(--app-ink); --report-border: var(--app-border); --report-muted: var(--app-muted); color: var(--report-ink); }
.report-header { padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--report-ink); color: #fff; }
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.report-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.report-header p { margin: 0; color: var(--app-hero-muted); }
.report-card { border: 1px solid var(--report-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.report-card th { color: var(--report-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.report-empty { padding: 2rem 1rem; color: var(--report-muted); text-align: center; }
</style>
