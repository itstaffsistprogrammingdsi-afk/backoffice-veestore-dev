<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { Head, Link } from '@inertiajs/vue3';

interface ExportRow {
    id: string;
    filename: string;
    status: string;
    error: string | null;
    created_at: string | null;
}

interface Props {
    export: ExportRow;
    urls: {
        index: string;
        download: string;
        refresh: string;
    };
    limits: {
        max_days: number;
        max_rows: number;
    };
}

const props = defineProps<Props>();

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        queued: 'Permintaan export masuk antrean.',
        processing: 'Laporan sedang disiapkan.',
        completed: 'Laporan siap diunduh.',
        failed: 'Export gagal.',
    };

    return labels[status] ?? status;
};
</script>

<template>
    <Head title="Status Export" />

    <div class="report-page">
        <div class="report-header">
            <div>
                <span class="report-eyebrow">Reports</span>
                <h1>Export Laporan</h1>
                <p>{{ props.export.filename }}</p>
            </div>
        </div>

        <div class="card report-card">
            <div class="card-body">
                <div v-if="props.export.status === 'completed'" class="alert alert-success">
                    {{ statusLabel(props.export.status) }}
                </div>
                <div v-else-if="props.export.status === 'failed'" class="alert alert-danger">
                    {{ props.export.error ?? statusLabel(props.export.status) }}
                </div>
                <div v-else class="alert alert-info">
                    {{ statusLabel(props.export.status) }}
                    Anda dapat melanjutkan pekerjaan dan kembali ke halaman ini.
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a
                        v-if="props.export.status === 'completed'"
                        :href="props.urls.download"
                        class="btn btn-primary"
                    >
                        <i class="mdi mdi-download me-1"></i>
                        Unduh Excel
                    </a>
                    <Link :href="props.urls.refresh" class="btn btn-outline-secondary">
                        <i class="mdi mdi-refresh me-1"></i>
                        Periksa Status
                    </Link>
                    <Link :href="props.urls.index" class="btn btn-outline-secondary">
                        Export Saya
                    </Link>
                </div>

                <p class="text-muted mt-3 mb-0">
                    Rentang maksimal {{ props.limits.max_days }} hari, hingga
                    {{ new Intl.NumberFormat('id-ID').format(props.limits.max_rows) }} baris per laporan.
                </p>
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
    color: #f9a8d4;
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
</style>
