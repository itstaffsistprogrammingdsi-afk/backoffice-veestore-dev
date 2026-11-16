<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import ReportTableControls from '@/components/ui/ReportTableControls.vue';
import { downloadExcelTable } from '@/composables/useExcelExport';
import { useReportTableColumns, useReportTableDensity } from '@/composables/useReportTable';

interface CustomerRow {
    id: string;
    name: string | null;
    email: string | null;
    wa_number: string | null;
    current_balance: string | number;
    created_at: string | null;
    registration_channel: string | null;
    registration_source_name: string | null;
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

const customerColumns = [
    { key: 'name', label: 'Pelanggan' },
    { key: 'created_at', label: 'Terdaftar' },
    { key: 'registration_channel', label: 'Kanal' },
    { key: 'registration_source_name', label: 'Sumber' },
    { key: 'email', label: 'Email' },
    { key: 'wa_number', label: 'WA' },
    { key: 'current_balance', label: 'Poin' },
];
const customerSimpleColumns = ['name', 'created_at', 'registration_channel', 'current_balance'];
const { density, isCompact, toggleDensity } = useReportTableDensity();
const { visibleColumns, isVisible, toggleColumn, showAll, applyPreset, columnCount } = useReportTableColumns(
    'veestore.crm-customers.columns',
    customerColumns.map((column) => column.key),
);

const form = reactive({ q: props.filters.q ?? '' });

const apply = () => {
    router.get(props.urls.index, { q: form.q || undefined }, { preserveState: true, preserveScroll: true, replace: true });
};

const reset = () => {
    form.q = '';
    apply();
};

const formatNumber = (value: string | number) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const formatDate = (value: string | null) => {
    if (!value) return '-';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
};

const formatRegistrationChannel = (value: string | null) => {
    if (value === 'store') return 'Toko';
    if (value === 'ecommerce') return 'E-commerce';

    return value || '-';
};

const exportCustomers = () => downloadExcelTable('CRM-Customer-Profiles', 'Customer Profiles', [
    { key: 'name', label: 'Customer' },
    { key: 'created_at', label: 'Tanggal Daftar' },
    { key: 'registration_channel', label: 'Channel Registrasi' },
    { key: 'registration_source_name', label: 'Sumber Registrasi' },
    { key: 'email', label: 'Email' },
    { key: 'wa_number', label: 'Nomor WA' },
    { key: 'current_balance', label: 'Poin' },
], props.customers.data, {
    title: 'CRM Customer Profiles',
    filters: [{ label: 'Pencarian', value: props.filters.q }],
});
</script>

<template>
    <Head title="Profil Pelanggan" />
    <div class="report-page">
        <div class="report-header d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <span class="report-eyebrow">Laporan / Pelanggan</span>
                <h1>Profil Pelanggan</h1>
                <p>Pilih pelanggan untuk melihat profil dan riwayat transaksi.</p>
            </div>
            <button class="btn btn-light btn-sm" type="button" @click="exportCustomers">
                <i class="mdi mdi-file-excel-outline me-1"></i>Ekspor Excel
            </button>
        </div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <form class="d-flex gap-2" @submit.prevent="apply">
                    <label class="visually-hidden" for="customer-search">Cari pelanggan</label>
                    <input id="customer-search" v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Cari nama, email, atau WA..." />
                    <button class="btn btn-sm btn-primary" type="submit" aria-label="Cari pelanggan"><i class="mdi mdi-magnify" aria-hidden="true"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button>
                </form>
            </div>
        </div>

        <div class="card report-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="h6 mb-0">Daftar pelanggan</h2>
                    <small class="text-muted">{{ customers.total }} pelanggan sesuai pencarian</small>
                </div>
                <ReportTableControls
                    :columns="customerColumns"
                    :visible="visibleColumns"
                    :density="density"
                    :simple-preset="customerSimpleColumns"
                    @toggle-density="toggleDensity"
                    @toggle-column="toggleColumn"
                    @show-all="showAll"
                    @apply-simple="applyPreset(customerSimpleColumns)"
                />
            </div>
            <div class="table-responsive report-table-wrap" :class="{ 'is-compact': isCompact }" tabindex="0" role="region" aria-label="Daftar profil pelanggan">
                <table class="table align-middle mb-0">
                    <caption class="visually-hidden">Daftar pelanggan beserta kanal registrasi, kontak, dan saldo poin</caption>
                    <thead>
                        <tr>
                            <th v-if="isVisible('name')">Pelanggan</th>
                            <th v-if="isVisible('created_at')">Terdaftar</th>
                            <th v-if="isVisible('registration_channel')">Kanal</th>
                            <th v-if="isVisible('registration_source_name')">Sumber</th>
                            <th v-if="isVisible('email')">Email</th>
                            <th v-if="isVisible('wa_number')">WA</th>
                            <th v-if="isVisible('current_balance')" class="text-end">Poin</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in customers.data" :key="row.id">
                            <td v-if="isVisible('name')" class="fw-semibold">{{ row.name ?? '-' }}</td>
                            <td v-if="isVisible('created_at')" class="text-nowrap">{{ formatDate(row.created_at) }}</td>
                            <td v-if="isVisible('registration_channel')">{{ formatRegistrationChannel(row.registration_channel) }}</td>
                            <td v-if="isVisible('registration_source_name')">{{ row.registration_source_name ?? '-' }}</td>
                            <td v-if="isVisible('email')">{{ row.email ?? '-' }}</td>
                            <td v-if="isVisible('wa_number')">{{ row.wa_number ?? '-' }}</td>
                            <td v-if="isVisible('current_balance')" class="text-end">{{ formatNumber(row.current_balance) }}</td>
                            <td class="text-end"><Link :href="props.urls.profile.replace('__ID__', row.id)" class="btn btn-sm btn-outline-primary">Profil</Link></td>
                        </tr>
                        <tr v-if="customers.data.length === 0"><td :colspan="columnCount + 1" class="report-empty">Belum ada pelanggan yang cocok. Ubah kata kunci lalu cari lagi.</td></tr>
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
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: none; }
.report-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.report-header p { margin: 0; color: var(--app-hero-muted); }
.report-card { border: 1px solid var(--report-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.report-card th { color: var(--report-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: none; }
.report-empty { padding: 2.5rem 1rem; color: var(--report-muted); text-align: center; }
</style>
