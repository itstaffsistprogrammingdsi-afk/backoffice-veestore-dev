<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
import AppFilterBar from '@/components/ui/AppFilterBar.vue';
import AppTableShell from '@/components/ui/AppTableShell.vue';
import AppEmptyState from '@/components/ui/AppEmptyState.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';

interface ScheduleRow {
    id: string;
    store: { id: string; name: string; code: string | null };
    schedule_month: string;
    month_label: string;
    status: string;
    status_label: string;
    status_tone: string;
    submitted_at: string | null;
    approved_at: string | null;
    created_by: string | null;
    approved_by: string | null;
    employees_count: number;
    entries_count: number;
    day_off_count: number;
    holiday_count: number;
}

interface Props {
    schedules: {
        data: ScheduleRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    stats: {
        pending: number;
        approved_this_month: number;
        draft: number;
        stores_pending: number;
    };
    statusOptions: Array<{ value: string; label: string }>;
    filters: {
        q: string;
        status: string;
        month: string | null;
        per_page: number;
    };
    urls: {
        index: string;
        show: string;
    };
}

const props = defineProps<Props>();

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status || 'submitted',
    month: props.filters.month ?? '',
    per_page: props.filters.per_page ?? 10,
});

watch(
    () => props.filters,
    (value) => {
        filters.q = value.q ?? '';
        filters.status = value.status || 'submitted';
        filters.month = value.month ?? '';
        filters.per_page = value.per_page ?? 10;
    },
    { deep: true },
);

const stats = computed(() => [
    { label: 'Menunggu approval', value: props.stats.pending, icon: 'mdi-clock-alert-outline' },
    { label: 'Disetujui bulan ini', value: props.stats.approved_this_month, icon: 'mdi-check-decagram-outline' },
    { label: 'Draft', value: props.stats.draft, icon: 'mdi-file-edit-outline' },
    { label: 'Toko menunggu', value: props.stats.stores_pending, icon: 'mdi-store-alert-outline' },
]);

const emptyDescription = computed(() => (
    filters.status === 'submitted'
        ? 'Semua jadwal yang diajukan toko akan muncul di sini.'
        : 'Ubah filter status atau bulan untuk melihat jadwal lain.'
));

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        status: filters.status || undefined,
        month: filters.month || undefined,
        per_page: filters.per_page,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.q = '';
    filters.status = 'submitted';
    filters.month = '';
    filters.per_page = 10;
    applyFilters();
};

const showUrl = (id: string) => props.urls.show.replace('__ID__', id);

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);

const formatDate = (value: string | null) => {
    if (!value) return '-';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return value;

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
};
</script>

<template>
    <Head title="Persetujuan Jadwal Toko" />

    <div class="schedule-approval-page">
        <AppPageHeader
            eyebrow="Operasional / Toko"
            title="Persetujuan Jadwal Toko"
            description="Tinjau jadwal shift bulanan yang diajukan toko, lalu setujui atau kembalikan ke draft dengan alasan yang jelas."
        />

        <div class="schedule-stats">
            <article v-for="stat in stats" :key="stat.label" class="schedule-stat">
                <span class="schedule-stat-label">
                    <i class="mdi" :class="stat.icon" aria-hidden="true"></i>
                    {{ stat.label }}
                </span>
                <strong class="schedule-stat-value">{{ formatNumber(stat.value) }}</strong>
            </article>
        </div>

        <AppFilterBar>
            <form class="d-flex w-100 flex-wrap align-items-end gap-2" @submit.prevent="applyFilters">
                <div class="schedule-filter-field">
                    <label class="form-label" for="schedule-search">Cari toko</label>
                    <input
                        id="schedule-search"
                        v-model="filters.q"
                        class="form-control form-control-sm"
                        type="search"
                        placeholder="Nama atau kode toko"
                    />
                </div>

                <div class="schedule-filter-field">
                    <label class="form-label" for="schedule-status">Status</label>
                    <select id="schedule-status" v-model="filters.status" class="form-select form-select-sm">
                        <option v-for="option in props.statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div class="schedule-filter-field">
                    <label class="form-label" for="schedule-month">Bulan jadwal</label>
                    <input id="schedule-month" v-model="filters.month" class="form-control form-control-sm" type="month" />
                </div>

                <div class="schedule-filter-field is-narrow">
                    <label class="form-label" for="schedule-per-page">Baris</label>
                    <select id="schedule-per-page" v-model.number="filters.per_page" class="form-select form-select-sm">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>

                <div class="d-flex gap-2 ms-auto">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">
                        <i class="mdi mdi-magnify" aria-hidden="true"></i>
                        Terapkan
                    </button>
                    <button class="btn btn-ghost btn-sm" type="button" @click="resetFilters">
                        <i class="mdi mdi-refresh" aria-hidden="true"></i>
                        Reset
                    </button>
                </div>
            </form>
        </AppFilterBar>

        <AppTableShell label="Daftar jadwal toko">
            <table class="table table-hover align-middle mb-0">
                <caption class="visually-hidden">Daftar jadwal shift toko</caption>
                <thead>
                    <tr>
                        <th scope="col">Toko</th>
                        <th scope="col">Periode</th>
                        <th scope="col" class="text-end">Karyawan</th>
                        <th scope="col" class="text-end">Entri</th>
                        <th scope="col" class="text-end">Libur</th>
                        <th scope="col">Status</th>
                        <th scope="col">Diajukan</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in props.schedules.data" :key="row.id">
                        <td>
                            <Link :href="showUrl(row.id)" class="schedule-store-link">
                                {{ row.store.name }}
                            </Link>
                            <span v-if="row.store.code" class="schedule-store-code">{{ row.store.code }}</span>
                        </td>
                        <td class="text-nowrap">{{ row.month_label }}</td>
                        <td class="text-end num">{{ formatNumber(row.employees_count) }}</td>
                        <td class="text-end num">{{ formatNumber(row.entries_count) }}</td>
                        <td class="text-end num">
                            {{ formatNumber(row.day_off_count) }}
                            <small v-if="row.holiday_count > 0" class="schedule-holiday-note">+{{ formatNumber(row.holiday_count) }} libur nasional</small>
                        </td>
                        <td>
                            <span class="badge" :class="`bg-label-${row.status_tone}`">{{ row.status_label }}</span>
                        </td>
                        <td class="text-nowrap">
                            <span>{{ formatDate(row.submitted_at) }}</span>
                            <small v-if="row.created_by" class="schedule-submitted-by">{{ row.created_by }}</small>
                        </td>
                        <td class="text-end">
                            <Link
                                :href="showUrl(row.id)"
                                class="btn btn-sm"
                                :class="row.status === 'submitted' ? 'btn-outline-primary' : 'btn-outline-secondary'"
                            >
                                Tinjau
                                <i class="mdi mdi-arrow-right" aria-hidden="true"></i>
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="props.schedules.data.length === 0">
                        <td colspan="8" class="p-0">
                            <AppEmptyState
                                title="Tidak ada jadwal"
                                :description="emptyDescription"
                                icon="mdi-calendar-blank-outline"
                            >
                                <button class="btn btn-outline-secondary" type="button" @click="resetFilters">
                                    <i class="mdi mdi-refresh" aria-hidden="true"></i>
                                    Reset filter
                                </button>
                            </AppEmptyState>
                        </td>
                    </tr>
                </tbody>
            </table>

            <template #footer>
                <small class="text-muted">
                    Menampilkan {{ props.schedules.from ?? 0 }} - {{ props.schedules.to ?? 0 }} dari {{ props.schedules.total }} jadwal
                </small>
                <PaginationLinks :links="props.schedules.links" />
            </template>
        </AppTableShell>
    </div>
</template>

<style scoped>
.schedule-approval-page {
    color: var(--app-ink);
}

.schedule-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.schedule-stat {
    padding: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
}

.schedule-stat-label {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.schedule-stat-label .mdi {
    color: var(--app-accent-ink);
    font-size: 1rem;
}

.schedule-stat-value {
    display: block;
    margin-top: 0.35rem;
    font-size: var(--app-text-2xl);
    font-weight: var(--app-weight-black);
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}

.schedule-filter-field {
    flex: 1 1 200px;
    min-width: 0;
}

.schedule-filter-field.is-narrow {
    flex: 0 0 90px;
}

.schedule-store-link {
    color: var(--app-ink);
    font-weight: var(--app-weight-semibold);
    text-decoration: none;
}

.schedule-store-link:hover {
    color: var(--app-accent-ink);
    text-decoration: underline;
}

.schedule-store-code {
    margin-left: 0.35rem;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-semibold);
}

.schedule-holiday-note,
.schedule-submitted-by {
    display: block;
    margin-top: 0.1rem;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
}

@media (min-width: 992px) {
    .schedule-stats {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .schedule-filter-field,
    .schedule-filter-field.is-narrow {
        flex: 1 1 100%;
        width: 100%;
    }
}
</style>
