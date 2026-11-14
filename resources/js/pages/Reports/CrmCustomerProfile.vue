<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { Head, Link } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import { orderStatusMeta } from '@/composables/useStatusLabels';

interface Customer {
    id: string;
    name: string | null;
    email: string | null;
    wa_number: string | null;
    address: string | null;
    date_of_birth: string | null;
}

interface TransactionRow {
    channel: string;
    store_name: string | null;
    transaction_id: string;
    transaction_code: string | null;
    transaction_status: string | null;
    visit_code: string | null;
    transaction_at: string | null;
    amount: string | number;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    customer: Customer;
    channel: string;
    transactions: Paginator<TransactionRow>;
    transactionSummary: {
        total_transactions: number;
        total_spending: string | number;
        first_purchase: string | null;
        last_purchase: string | null;
    };
    totalVisits: number;
    pointSummary: {
        current_balance: number;
        total_point_in: number;
        total_point_out: number;
        last_mutation_at: string | null;
    };
    pointMutations: Array<{
        type: string;
        mutation_type: string | null;
        points: number;
        balance_after: number;
        description: string | null;
        created_at: string | null;
    }>;
    urls: { index: string; export: string };
}

const props = defineProps<Props>();

const formatNumber = (value: number | string) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));
const formatCurrency = (value: number | string) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value ?? 0));
const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)) : '-';
</script>

<template>
    <Head :title="`Profil ${props.customer.name ?? 'Customer'}`" />
    <div class="report-page">
        <div class="report-header">
            <div>
                <span class="report-eyebrow">Laporan / Pelanggan</span>
                <h1>{{ props.customer.name ?? 'Pelanggan' }}</h1>
                <p>{{ props.customer.email ?? '-' }} | {{ props.customer.wa_number ?? '-' }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <Link :href="props.urls.index" class="btn btn-light btn-sm">Kembali</Link>
                <a :href="props.urls.export" class="btn btn-light btn-sm"><i class="mdi mdi-download me-1" aria-hidden="true"></i>Ekspor</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3"><div class="card report-card"><div class="card-body"><div class="report-label">Transaksi</div><div class="report-value">{{ formatNumber(transactionSummary.total_transactions) }}</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card report-card"><div class="card-body"><div class="report-label">Total Belanja</div><div class="report-value">{{ formatCurrency(transactionSummary.total_spending) }}</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card report-card"><div class="card-body"><div class="report-label">Kunjungan</div><div class="report-value">{{ formatNumber(totalVisits) }}</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card report-card"><div class="card-body"><div class="report-label">Saldo Poin</div><div class="report-value">{{ formatNumber(pointSummary.current_balance) }}</div></div></div></div>
        </div>

        <div class="card report-card mb-3">
            <div class="card-header"><h6 class="mb-0">Riwayat transaksi</h6></div>
            <div class="table-responsive report-table-wrap" tabindex="0" role="region" aria-label="Riwayat transaksi pelanggan">
                <table class="table table-sm mb-0">
                    <caption class="visually-hidden">Riwayat transaksi pelanggan per kanal</caption>
                    <thead><tr><th>Tanggal</th><th>Kanal</th><th>Transaksi</th><th>Toko</th><th>Status</th><th class="text-end">Jumlah</th></tr></thead>
                    <tbody>
                        <tr v-for="row in transactions.data" :key="row.transaction_id">
                            <td>{{ formatDate(row.transaction_at) }}</td>
                            <td>{{ row.channel === 'ecom' || row.channel === 'ecommerce' ? 'E-commerce' : 'POS' }}</td>
                            <td>{{ row.transaction_code ?? row.transaction_id }}</td>
                            <td>{{ row.store_name ?? '-' }}</td>
                            <td><span class="badge" :class="orderStatusMeta(row.transaction_status).tone">{{ orderStatusMeta(row.transaction_status).label }}</span></td>
                            <td class="text-end">{{ formatCurrency(row.amount) }}</td>
                        </tr>
                        <tr v-if="transactions.data.length === 0"><td colspan="6" class="report-empty">Belum ada transaksi untuk pelanggan ini.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2"><small class="text-muted">Menampilkan {{ transactions.from ?? 0 }} - {{ transactions.to ?? 0 }} dari {{ transactions.total }} transaksi</small><PaginationLinks :links="transactions.links" /></div>
        </div>

        <div class="card report-card">
            <div class="card-header"><h6 class="mb-0">Mutasi poin terakhir</h6></div>
            <div class="table-responsive report-table-wrap" tabindex="0" role="region" aria-label="Mutasi poin pelanggan">
                <table class="table table-sm mb-0">
                    <caption class="visually-hidden">Mutasi poin pelanggan beserta saldo setelah mutasi</caption>
                    <thead><tr><th>Tanggal</th><th>Tipe</th><th>Mutasi</th><th class="text-end">Poin</th><th class="text-end">Saldo</th><th>Deskripsi</th></tr></thead>
                    <tbody>
                        <tr v-for="(row, index) in pointMutations" :key="index"><td>{{ formatDate(row.created_at) }}</td><td>{{ row.type }}</td><td>{{ row.mutation_type ?? '-' }}</td><td class="text-end">{{ formatNumber(row.points) }}</td><td class="text-end">{{ formatNumber(row.balance_after) }}</td><td>{{ row.description ?? '-' }}</td></tr>
                        <tr v-if="pointMutations.length === 0"><td colspan="6" class="report-empty">Belum ada mutasi poin.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.report-page { --report-ink: var(--app-ink); --report-border: var(--app-border); --report-muted: var(--app-muted); color: var(--report-ink); }
.report-header { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: var(--app-radius-xl); background: var(--app-hero-bg); color: var(--app-hero-text); }
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: var(--app-text-xs); font-weight: var(--app-weight-semibold); }
.report-header h1 { margin: 0 0 0.35rem; font-size: var(--app-text-3xl); font-weight: var(--app-weight-semibold); }
.report-header p { margin: 0; color: var(--app-hero-muted); font-size: var(--app-text-sm); }
.report-card { border: 1px solid var(--report-border); border-radius: var(--app-radius-lg); box-shadow: none; }
.report-card th { color: var(--report-muted); font-size: var(--app-text-xs); font-weight: var(--app-weight-semibold); }
.report-label { color: var(--report-muted); font-size: var(--app-text-xs); font-weight: var(--app-weight-medium); }
.report-value { margin-top: 0.3rem; font-size: var(--app-text-xl); font-weight: var(--app-weight-semibold); font-variant-numeric: tabular-nums; }
.report-empty { padding: 2rem 1rem; color: var(--report-muted); text-align: center; }
</style>
