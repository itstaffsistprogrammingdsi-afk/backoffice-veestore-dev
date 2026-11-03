<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import { downloadExcelTable } from '@/composables/useExcelExport';

interface CustomerRow {
    id: string;
    name: string | null;
    email: string | null;
    wa_number: string | null;
    current_balance: string | number;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    customers: Paginator<CustomerRow>;
    filters: { q: string };
    urls: { index: string; profile: string };
}

const props = defineProps<Props>();
const form = reactive({ q: props.filters.q ?? '' });

const apply = () => {
    router.get(props.urls.index, { q: form.q || undefined }, { preserveState: true, preserveScroll: true, replace: true });
};

const reset = () => {
    form.q = '';
    apply();
};

const formatNumber = (value: string | number) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const exportCustomers = () => downloadExcelTable('CRM-Customer-Profiles', 'Customer Profiles', [
    { key: 'name', label: 'Customer' },
    { key: 'email', label: 'Email' },
    { key: 'wa_number', label: 'Nomor WA' },
    { key: 'current_balance', label: 'Poin' },
], props.customers.data, {
    title: 'CRM Customer Profiles',
    filters: [{ label: 'Pencarian', value: props.filters.q }],
});
</script>

<template>
    <Head title="CRM Customer Profiles" />
    <div class="report-page">
        <div class="report-header d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <span class="report-eyebrow">Reports / CRM</span>
                <h1>Customer Profiles</h1>
                <p>Pilih customer untuk melihat profil dan riwayat transaksi.</p>
            </div>
            <button class="btn btn-light btn-sm" type="button" @click="exportCustomers">
                <i class="mdi mdi-file-excel-outline me-1"></i>Export Excel
            </button>
        </div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <form class="d-flex gap-2" @submit.prevent="apply">
                    <input v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Cari nama, email, atau WA..." />
                    <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-magnify"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button>
                </form>
            </div>
        </div>

        <div class="card report-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Customer</th><th>Email</th><th>WA</th><th class="text-end">Poin</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <tr v-for="row in customers.data" :key="row.id">
                            <td class="fw-semibold">{{ row.name ?? '-' }}</td>
                            <td>{{ row.email ?? '-' }}</td>
                            <td>{{ row.wa_number ?? '-' }}</td>
                            <td class="text-end">{{ formatNumber(row.current_balance) }}</td>
                            <td class="text-end"><Link :href="props.urls.profile.replace('__ID__', row.id)" class="btn btn-sm btn-outline-primary">Profil</Link></td>
                        </tr>
                        <tr v-if="customers.data.length === 0"><td colspan="5" class="report-empty">Belum ada customer.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">Menampilkan {{ customers.from ?? 0 }} - {{ customers.to ?? 0 }} dari {{ customers.total }} customer</small>
                <PaginationLinks :links="customers.links" />
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
.report-empty { padding: 2.5rem 1rem; color: var(--report-muted); text-align: center; }
</style>
