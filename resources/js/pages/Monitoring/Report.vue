<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '../../components/PaginationLinks.vue';

defineOptions({ layout: AppLayout });

interface Paginator {
    data: Record<string, any>[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from?: number | null;
    to?: number | null;
    total?: number;
}

interface Props {
    title: string;
    description?: string;
    data: Record<string, any>;
    filters: Record<string, any>;
    urls: { index: string };
}

const props = defineProps<Props>();

const filterValues = reactive<Record<string, string>>({});

for (const [key, value] of Object.entries(props.filters ?? {})) {
    if (key === 'page' || key === 'theme') continue;
    filterValues[key] = value == null ? '' : String(value);
}

const filterKeys = Object.keys(filterValues);

const isPaginator = (value: any): value is Paginator =>
    value && typeof value === 'object' && Array.isArray(value.data) && Array.isArray(value.links);

const preferredKeys = ['rows', 'items', 'logs', 'customers', 'orders', 'results', 'vouchers', 'referrals', 'returns', 'products'];

const dataset = computed<Paginator | null>(() => {
    for (const key of preferredKeys) {
        if (isPaginator(props.data[key])) return props.data[key];
    }

    for (const value of Object.values(props.data)) {
        if (isPaginator(value)) return value;
    }

    for (const key of preferredKeys) {
        const value = props.data[key];
        if (Array.isArray(value) && value.length > 0 && typeof value[0] === 'object') {
            return { data: value, links: [] };
        }
    }

    for (const value of Object.values(props.data)) {
        if (Array.isArray(value) && value.length > 0 && typeof value[0] === 'object') {
            return { data: value, links: [] };
        }
    }

    return null;
});

const rows = computed(() => dataset.value?.data ?? []);

const columns = computed(() => {
    const first = rows.value[0] ?? {};
    const keys = Object.keys(first).filter((key) => {
        if (['id', 'uuid', 'password', 'deleted_at'].includes(key)) return false;
        if (key.endsWith('_id') && key !== 'id') return false;
        const value = first[key];
        if (Array.isArray(value)) return false;
        if (value && typeof value === 'object' && !('name' in value) && !('label' in value) && !('code' in value)) return false;
        return true;
    });

    return keys.slice(0, 12).map((key) => ({
        key,
        label: key.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()),
    }));
});

const summaryCards = computed(() => {
    const summary = props.data.summary;
    if (!summary || typeof summary !== 'object' || Array.isArray(summary)) return [];

    return Object.entries(summary)
        .filter(([, value]) => typeof value === 'number' || (typeof value === 'string' && value !== '' && !Number.isNaN(Number(value))))
        .slice(0, 8)
        .map(([key, value]) => ({
            label: key.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()),
            value: Number(value),
        }));
});

const applyFilters = () => {
    const payload = Object.fromEntries(
        Object.entries(filterValues).filter(([, value]) => value !== ''),
    );

    router.get(props.urls.index, payload, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    for (const key of Object.keys(filterValues)) filterValues[key] = '';
    applyFilters();
};

const filterInputType = (key: string) =>
    /date|from|to|start|end/.test(key) ? 'date' : 'text';

const cellValue = (row: Record<string, any>, key: string) => {
    const value = row[key];

    if (value === null || value === undefined) return '-';
    if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';

    if (typeof value === 'object') {
        return value.name ?? value.label ?? value.code ?? '-';
    }

    if (typeof value === 'string' && /(date|_at|from|to)$/.test(key)) {
        const date = new Date(value);
        if (!Number.isNaN(date.getTime())) {
            return new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }).format(date);
        }
    }

    if (/(amount|total|price|omset|revenue|spending|discount)/.test(key) && !Number.isNaN(Number(value))) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(value));
    }

    if (typeof value === 'number') return new Intl.NumberFormat('id-ID').format(value);

    return String(value);
};
</script>

<template>
    <Head :title="props.title" />
    <div class="monitor-report">
        <div class="monitor-header">
            <div>
                <span class="monitor-eyebrow">Monitoring</span>
                <h1>{{ props.title }}</h1>
                <p>{{ props.description ?? 'Laporan monitoring operasional.' }}</p>
            </div>
        </div>

        <div v-if="filterKeys.length" class="card monitor-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div v-for="key in filterKeys" :key="key" class="col-md-3">
                        <label class="form-label text-capitalize">{{ key.replace(/_/g, ' ') }}</label>
                        <input v-model="filterValues[key]" class="form-control form-control-sm" :type="filterInputType(key)" />
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="mdi mdi-filter me-1"></i>Filter</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetFilters">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="summaryCards.length" class="row g-3 mb-3">
            <div v-for="card in summaryCards" :key="card.label" class="col-6 col-md-4 col-xl-3">
                <div class="card monitor-card h-100">
                    <div class="card-body">
                        <div class="monitor-label">{{ card.label }}</div>
                        <div class="monitor-value">{{ new Intl.NumberFormat('id-ID').format(card.value) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card monitor-card">
            <div class="table-responsive" v-if="rows.length">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th v-for="column in columns" :key="column.key">{{ column.label }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in rows" :key="`${index}-${row.id ?? ''}`">
                            <td v-for="column in columns" :key="column.key">{{ cellValue(row, column.key) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="monitor-empty">Belum ada data untuk filter ini.</div>

            <div v-if="dataset" class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">
                    Menampilkan {{ dataset.from ?? 0 }} - {{ dataset.to ?? rows.length }} dari {{ dataset.total ?? rows.length }} data
                </small>
                <PaginationLinks v-if="dataset.links.length" :links="dataset.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.monitor-report { color: var(--app-ink); }
.monitor-header { padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.monitor-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: #f9a8d4; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.monitor-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; color: var(--app-hero-text); }
.monitor-header p { margin: 0; color: var(--app-hero-muted); }
.monitor-card { border: 1px solid var(--app-border); border-radius: 0.9rem; box-shadow: var(--app-shadow); background: var(--app-surface); }
.monitor-card th { color: var(--app-muted); font-size: 0.72rem; letter-spacing: 0.04em; text-transform: uppercase; white-space: nowrap; }
.monitor-label { color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.monitor-value { margin-top: 0.25rem; font-size: 1.2rem; font-weight: 800; }
.monitor-empty { padding: 2.5rem 1rem; color: var(--app-muted); text-align: center; }
</style>
