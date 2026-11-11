<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { Head, Link } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import { downloadExcelTable } from '@/composables/useExcelExport';
import { exportStatusMeta } from '@/composables/useStatusLabels';

interface ExportRow {
    id: string;
    filename: string;
    status: string;
    created_at: string | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    exports: Paginator<ExportRow>;
    urls: { show: string };
}

const props = defineProps<Props>();

const statusMeta = (status: string) => exportStatusMeta(status);

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const exportHistory = () => downloadExcelTable('Export-Saya', 'Ekspor Saya', [
    { key: 'filename', label: 'Nama File' },
    { key: 'status', label: 'Status' },
    { key: 'created_at', label: 'Dibuat' },
], props.exports.data);
</script>

<template>
    <Head title="Ekspor Saya" />

    <div class="report-page">
        <div class="report-header d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <span class="report-eyebrow">Reports</span>
                <h1>Ekspor Saya</h1>
                <p>Daftar permintaan export laporan yang Anda buat.</p>
            </div>
            <button class="btn btn-light btn-sm" type="button" @click="exportHistory">
                <i class="mdi mdi-file-excel-outline me-1"></i>Ekspor Excel
            </button>
        </div>

        <div class="card report-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <caption class="visually-hidden">
                        Riwayat permintaan export laporan milik pengguna.
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Laporan</th>
                            <th scope="col">Status</th>
                            <th scope="col">Dibuat</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in exports.data" :key="row.id">
                            <td class="fw-semibold">{{ row.filename }}</td>
                            <td>
                                <span class="badge" :class="statusMeta(row.status).tone">
                                    {{ statusMeta(row.status).label }}
                                </span>
                            </td>
                            <td>{{ formatDate(row.created_at) }}</td>
                            <td class="text-end">
                                <Link :href="props.urls.show.replace('__ID__', row.id)" class="btn btn-sm btn-outline-primary">
                                    Lihat
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="exports.data.length === 0">
                            <td colspan="4" class="report-empty">
                                <i class="mdi mdi-file-export-outline d-block mb-2" aria-hidden="true"></i>
                                <strong class="d-block">Belum ada permintaan export.</strong>
                                <span class="d-block">Jalankan export dari halaman laporan yang ingin diunduh.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">
                    Menampilkan {{ exports.from ?? 0 }} - {{ exports.to ?? 0 }} dari {{ exports.total }} export
                </small>
                <PaginationLinks :links="exports.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.report-page {
    --report-ink: var(--app-ink);
    --report-border: var(--app-border);
    --report-muted: var(--app-muted);
    color: var(--report-ink);
}

.report-header {
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: var(--report-ink);
    color: #fff;
}

.report-eyebrow {
    display: inline-block;
    margin-bottom: 0.5rem;
    color: var(--app-hero-eyebrow);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.report-header h1 {
    margin: 0 0 0.35rem;
    font-size: 1.5rem;
}

.report-header p {
    margin: 0;
    color: var(--app-hero-muted);
}

.report-card {
    border: 1px solid var(--report-border);
    border-radius: 0.9rem;
    box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05);
}

.report-card th {
    color: var(--report-muted);
    font-size: 0.75rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.report-empty {
    padding: 2.5rem 1rem;
    color: var(--report-muted);
    text-align: center;
}
</style>
