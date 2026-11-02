<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { Head, Link } from '@inertiajs/vue3';

interface OrderRow {
    order_id: string;
    order_date: string | null;
}

interface Props {
    customerId: string;
    from: string;
    to: string;
    orders: OrderRow[];
    urls: { index: string };
}

const props = defineProps<Props>();

const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : '-';
</script>

<template>
    <Head title="History Customer Ecommerce" />
    <div class="report-page">
        <div class="report-header">
            <div>
                <span class="report-eyebrow">Reports / History Customer</span>
                <h1>Detail Ecommerce</h1>
                <p>Customer {{ props.customerId }} | {{ props.from }} sampai {{ props.to }}</p>
            </div>
            <Link :href="props.urls.index" class="btn btn-light btn-sm">Kembali</Link>
        </div>

        <div class="card report-card">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Order ID</th><th>Tanggal</th></tr></thead>
                    <tbody>
                        <tr v-for="order in orders" :key="order.order_id"><td>{{ order.order_id }}</td><td>{{ formatDate(order.order_date) }}</td></tr>
                        <tr v-if="orders.length === 0"><td colspan="2" class="report-empty">Belum ada order.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.report-page { --report-ink: var(--app-ink); --report-border: var(--app-border); --report-muted: var(--app-muted); color: var(--report-ink); }
.report-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--report-ink); color: #fff; }
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.report-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.report-header p { margin: 0; color: var(--app-hero-muted); }
.report-card { border: 1px solid var(--report-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.report-empty { padding: 2rem 1rem; color: var(--report-muted); text-align: center; }
</style>
