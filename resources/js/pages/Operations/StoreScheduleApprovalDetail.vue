<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
import AppTableShell from '@/components/ui/AppTableShell.vue';
import AppEmptyState from '@/components/ui/AppEmptyState.vue';
import AppDialog from '@/components/ui/AppDialog.vue';
import { useToast } from '@/composables/useToast';

interface DayCell {
    date: string;
    shift_code: string | null;
    shift_name: string | null;
    start_time: string | null;
    end_time: string | null;
    is_day_off: boolean;
    is_national_holiday: boolean;
    notes: string | null;
}

interface Employee {
    id: string;
    name: string;
    days: Array<DayCell | null>;
}

interface Shift {
    id: string;
    name: string;
    code: string;
    start_time: string | null;
    end_time: string | null;
    is_active: boolean;
}

interface AuditEntry {
    at: string | null;
    by_name: string | null;
    action: string;
    target: string | null;
    old: string | null;
    new: string | null;
    reason: string | null;
}

interface Props {
    schedule: {
        id: string;
        store: { id: string; name: string; code: string | null };
        schedule_month: string;
        month_label: string;
        days_in_month: number;
        status: string;
        status_label: string;
        status_tone: string;
        submitted_at: string | null;
        approved_at: string | null;
        created_by: string | null;
        approved_by: string | null;
    };
    employees: Employee[];
    shifts: Shift[];
    summary: {
        employees_count: number;
        entries_count: number;
        day_off_count: number;
        holiday_count: number;
        shift_slots: number;
    };
    audit: AuditEntry[];
    can_approve: boolean;
    urls: {
        index: string;
        approve: string;
        reject: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });
const { success: toastSuccess, error: toastError } = useToast();
let lastFlashKey = '';

watch(
    flash,
    (value) => {
        const key = `${value.success ?? ''}|${value.error ?? ''}`;

        if (key === lastFlashKey) return;

        lastFlashKey = key;

        if (value.success) toastSuccess(value.success);
        if (value.error) toastError(value.error);
    },
    { immediate: true },
);

const dayMeta = computed(() => Array.from({ length: props.schedule.days_in_month }, (_, index) => {
    const iso = `${props.schedule.schedule_month.slice(0, 7)}-${String(index + 1).padStart(2, '0')}`;
    const date = new Date(`${iso}T00:00:00`);

    return {
        day: index + 1,
        weekday: new Intl.DateTimeFormat('id-ID', { weekday: 'short' }).format(date),
    };
}));

const statusBadgeClass = computed(() => `bg-label-${props.schedule.status_tone}`);

const pageDescription = computed(() => {
    const code = props.schedule.store.code ? ` (${props.schedule.store.code})` : '';

    return `Jadwal shift ${props.schedule.month_label} untuk ${props.schedule.store.name}${code}.`;
});

const actionMode = ref<'approve' | 'reject' | null>(null);
const actionNotes = ref('');
const actionError = ref('');
const processing = ref(false);

const actionOpen = computed(() => actionMode.value !== null);

const actionTitle = computed(() => (
    actionMode.value === 'reject' ? 'Kembalikan jadwal ke draft?' : 'Setujui jadwal ini?'
));

const actionDescription = computed(() => (
    actionMode.value === 'reject'
        ? `Jadwal ${props.schedule.month_label} akan dikembalikan ke draft agar toko dapat memperbaiki dan mengajukan ulang.`
        : `Jadwal ${props.schedule.month_label} untuk ${props.schedule.store.name} akan disetujui.`
));

const actionLabel = computed(() => (actionMode.value === 'reject' ? 'Kembalikan ke draft' : 'Setujui jadwal'));

const actionVariant = computed<'primary' | 'danger'>(() => (actionMode.value === 'reject' ? 'danger' : 'primary'));

const actionNotesLabel = computed(() => (actionMode.value === 'reject' ? 'Alasan pengembalian' : 'Catatan (opsional)'));

const actionPlaceholder = computed(() => (
    actionMode.value === 'reject'
        ? 'Tuliskan bagian jadwal yang perlu diperbaiki.'
        : 'Tambahkan catatan untuk toko bila diperlukan.'
));

const openAction = (mode: 'approve' | 'reject') => {
    actionMode.value = mode;
    actionNotes.value = '';
    actionError.value = '';
};

const closeAction = () => {
    if (processing.value) return;

    actionMode.value = null;
    actionNotes.value = '';
    actionError.value = '';
};

const submitAction = () => {
    if (!actionMode.value) return;

    const note = actionNotes.value.trim();

    if (actionMode.value === 'reject' && note === '') {
        actionError.value = 'Alasan pengembalian wajib diisi.';

        return;
    }

    processing.value = true;
    actionError.value = '';

    const url = actionMode.value === 'approve' ? props.urls.approve : props.urls.reject;
    const payload = actionMode.value === 'approve'
        ? { notes: note !== '' ? note : null }
        : { reason: note };

    router.patch(url, payload, {
        preserveScroll: true,
        onSuccess: () => {
            actionMode.value = null;
            actionNotes.value = '';
            actionError.value = '';
        },
        onError: (errors) => {
            actionError.value = errors.reason ?? errors.notes ?? 'Aksi gagal diproses. Coba lagi.';
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const cellClass = (cell: DayCell) => {
    if (cell.is_day_off) return 'is-off';
    if (cell.is_national_holiday) return 'is-holiday';
    if (cell.shift_code) return 'is-shift';

    return 'is-empty';
};

const cellLabel = (cell: DayCell) => {
    if (cell.is_day_off) return 'OFF';
    if (cell.is_national_holiday) return 'LIB';

    return cell.shift_code ?? '-';
};

const cellTitle = (cell: DayCell) => {
    const parts: string[] = [];

    if (cell.is_day_off) {
        parts.push('Hari libur');
    } else if (cell.shift_name) {
        parts.push(cell.shift_name);
    }

    if (cell.start_time && cell.end_time) {
        parts.push(`${cell.start_time} - ${cell.end_time}`);
    }

    if (cell.is_national_holiday) {
        parts.push('Libur nasional');
    }

    if (cell.notes) {
        parts.push(cell.notes);
    }

    return parts.join('. ') || 'Tidak ada jadwal';
};

const auditActionLabel = (action: string) => {
    const labels: Record<string, string> = {
        approve: 'Disetujui',
        reject: 'Dikembalikan ke draft',
        submit: 'Diajukan',
        bulk: 'Pengaturan massal',
        note: 'Catatan',
    };

    return labels[action] ?? action;
};

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

const formatDateTime = (value: string | null) => {
    if (!value) return '-';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return value;

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
</script>

<template>
    <Head :title="`Jadwal ${props.schedule.store.name}`" />

    <div class="schedule-detail-page">
        <Link :href="props.urls.index" class="btn btn-ghost btn-sm schedule-back">
            <i class="mdi mdi-arrow-left" aria-hidden="true"></i>
            Kembali ke daftar
        </Link>

        <AppPageHeader
            eyebrow="Persetujuan Jadwal Toko"
            :title="`${props.schedule.store.name} · ${props.schedule.month_label}`"
            :description="pageDescription"
        >
            <template #meta>
                <span class="badge" :class="statusBadgeClass">{{ props.schedule.status_label }}</span>
            </template>
            <template v-if="props.can_approve" #actions>
                <button class="btn btn-primary" type="button" @click="openAction('approve')">
                    <i class="mdi mdi-check" aria-hidden="true"></i>
                    Setujui
                </button>
                <button class="btn btn-outline-danger" type="button" @click="openAction('reject')">
                    <i class="mdi mdi-backup-restore" aria-hidden="true"></i>
                    Kembalikan
                </button>
            </template>
        </AppPageHeader>

        <div v-if="props.schedule.status === 'submitted' && !props.can_approve" class="alert alert-info" role="status">
            <i class="mdi mdi-information-outline me-1" aria-hidden="true"></i>
            Anda dapat melihat jadwal ini, tetapi tidak memiliki izin untuk menyetujui.
        </div>

        <div class="schedule-meta">
            <span>Kode toko: <strong>{{ props.schedule.store.code ?? '-' }}</strong></span>
            <span>
                Diajukan: <strong>{{ formatDate(props.schedule.submitted_at) }}</strong>
                <template v-if="props.schedule.created_by"> oleh {{ props.schedule.created_by }}</template>
            </span>
            <span v-if="props.schedule.approved_at">
                Disetujui: <strong>{{ formatDate(props.schedule.approved_at) }}</strong>
                <template v-if="props.schedule.approved_by"> oleh {{ props.schedule.approved_by }}</template>
            </span>
            <span>Entri jadwal: <strong>{{ formatNumber(props.summary.entries_count) }}</strong></span>
        </div>

        <div class="schedule-summary-grid">
            <article class="schedule-summary">
                <span>Karyawan</span>
                <strong>{{ formatNumber(props.summary.employees_count) }}</strong>
            </article>
            <article class="schedule-summary">
                <span>Slot shift</span>
                <strong>{{ formatNumber(props.summary.shift_slots) }}</strong>
            </article>
            <article class="schedule-summary">
                <span>Hari libur</span>
                <strong>{{ formatNumber(props.summary.day_off_count) }}</strong>
            </article>
            <article class="schedule-summary">
                <span>Libur nasional</span>
                <strong>{{ formatNumber(props.summary.holiday_count) }}</strong>
            </article>
        </div>

        <AppTableShell :label="`Matriks jadwal ${props.schedule.store.name}`">
            <template #toolbar>
                <div>
                    <h2 class="h6 mb-0">Matriks jadwal</h2>
                    <small class="text-muted">Geser ke kanan untuk melihat seluruh tanggal.</small>
                </div>
                <div class="schedule-legend">
                    <span v-for="shift in props.shifts" :key="shift.id" class="schedule-legend-item">
                        <span class="schedule-swatch is-shift">{{ shift.code }}</span>
                        {{ shift.name }}
                        <template v-if="shift.start_time && shift.end_time">{{ shift.start_time }}-{{ shift.end_time }}</template>
                    </span>
                    <span class="schedule-legend-item">
                        <span class="schedule-swatch is-off">OFF</span>
                        Hari libur
                    </span>
                    <span class="schedule-legend-item">
                        <span class="schedule-swatch is-holiday">LIB</span>
                        Libur nasional
                    </span>
                </div>
            </template>

            <table class="table schedule-table mb-0">
                <caption class="visually-hidden">Matriks jadwal {{ props.schedule.store.name }} {{ props.schedule.month_label }}</caption>
                <thead>
                    <tr>
                        <th scope="col" class="schedule-sticky">Karyawan</th>
                        <th v-for="meta in dayMeta" :key="meta.day" scope="col" class="text-center">
                            <span class="schedule-day">{{ meta.day }}</span>
                            <span class="schedule-weekday">{{ meta.weekday }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="employee in props.employees" :key="employee.id">
                        <th scope="row" class="schedule-sticky">{{ employee.name }}</th>
                        <td v-for="(cell, index) in employee.days" :key="index" class="text-center">
                            <span
                                v-if="cell"
                                class="schedule-cell"
                                :class="cellClass(cell)"
                                :title="cellTitle(cell)"
                            >
                                {{ cellLabel(cell) }}
                            </span>
                            <span v-else class="schedule-cell is-empty">-</span>
                        </td>
                    </tr>
                    <tr v-if="props.employees.length === 0">
                        <td :colspan="props.schedule.days_in_month + 1" class="p-0">
                            <AppEmptyState
                                title="Belum ada detail jadwal"
                                description="Jadwal ini tidak memiliki entri karyawan untuk periode tersebut."
                                icon="mdi-calendar-blank-outline"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>

            <template #footer>
                <small class="text-muted">
                    {{ formatNumber(props.summary.employees_count) }} karyawan,
                    {{ formatNumber(props.summary.entries_count) }} entri
                </small>
                <small class="text-muted">Periode {{ props.schedule.month_label }}</small>
            </template>
        </AppTableShell>

        <section class="card schedule-audit-card">
            <div class="card-header">
                <h2 class="h6 mb-0">Riwayat approval</h2>
            </div>
            <div class="card-body">
                <ol v-if="props.audit.length > 0" class="schedule-audit">
                    <li v-for="(entry, index) in props.audit" :key="index">
                        <div class="schedule-audit-head">
                            <strong>{{ auditActionLabel(entry.action) }}</strong>
                            <small>{{ formatDateTime(entry.at) }}</small>
                        </div>
                        <p v-if="entry.target">{{ entry.target }}</p>
                        <p v-if="entry.reason" class="schedule-audit-reason">{{ entry.reason }}</p>
                        <small class="text-muted">
                            Oleh {{ entry.by_name ?? 'Sistem' }}
                            <template v-if="entry.old || entry.new">
                                · {{ entry.old ?? '-' }} ke {{ entry.new ?? '-' }}
                            </template>
                        </small>
                    </li>
                </ol>
                <AppEmptyState
                    v-else
                    title="Belum ada riwayat"
                    description="Aktivitas approval akan tercatat di sini setelah jadwal diproses."
                    icon="mdi-history"
                />
            </div>
        </section>

        <AppDialog
            :open="actionOpen"
            :title="actionTitle"
            :description="actionDescription"
            :confirm-label="actionLabel"
            :variant="actionVariant"
            :loading="processing"
            initial-focus-selector="#schedule-action-notes"
            @update:open="closeAction"
            @confirm="submitAction"
            @cancel="closeAction"
        >
            <label class="form-label" :class="{ 'is-required': actionMode === 'reject' }" for="schedule-action-notes">
                {{ actionNotesLabel }}
            </label>
            <textarea
                id="schedule-action-notes"
                v-model="actionNotes"
                class="form-control"
                :class="{ 'is-invalid': actionError }"
                rows="3"
                maxlength="1000"
                :placeholder="actionPlaceholder"
            ></textarea>
            <div v-if="actionError" class="invalid-feedback d-block" role="alert">{{ actionError }}</div>
            <p class="form-text mt-2">Catatan akan tersimpan di riwayat approval.</p>
        </AppDialog>
    </div>
</template>

<style scoped>
.schedule-detail-page {
    color: var(--app-ink);
}

.schedule-back {
    margin-bottom: 0.75rem;
}

.schedule-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 1.5rem;
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-lg);
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: var(--app-text-sm);
}

.schedule-meta strong {
    color: var(--app-ink);
    font-weight: var(--app-weight-semibold);
}

.schedule-summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.schedule-summary {
    padding: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
}

.schedule-summary span {
    display: block;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.schedule-summary strong {
    display: block;
    margin-top: 0.25rem;
    font-size: var(--app-text-2xl);
    font-weight: var(--app-weight-black);
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}

.schedule-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem 0.9rem;
}

.schedule-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
}

.schedule-swatch {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    min-height: 24px;
    padding: 0.1rem 0.3rem;
    border-radius: var(--app-radius-xs);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
    font-variant-numeric: tabular-nums;
}

.schedule-swatch.is-shift {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.schedule-swatch.is-off {
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
}

.schedule-swatch.is-holiday {
    background: var(--app-info-bg);
    color: var(--app-info-ink);
}

.schedule-table.table {
    min-width: 980px;
    overflow: visible;
}

.schedule-table th,
.schedule-table td {
    padding: 0.4rem 0.35rem;
    font-size: var(--app-text-xs);
}

.schedule-table thead th {
    font-size: var(--app-text-2xs);
    letter-spacing: 0;
    text-transform: none;
}

.schedule-sticky {
    position: sticky;
    left: 0;
    z-index: 2;
    min-width: 160px;
    background: var(--app-surface);
    box-shadow: 1px 0 0 var(--app-border);
    text-align: left;
}

.schedule-table thead .schedule-sticky {
    z-index: 3;
    background: var(--app-surface-3);
}

.schedule-day {
    display: block;
    color: var(--app-ink);
    font-size: var(--app-text-sm);
    font-weight: var(--app-weight-bold);
    font-variant-numeric: tabular-nums;
}

.schedule-weekday {
    display: block;
    color: var(--app-muted);
    font-size: 10px;
    font-weight: var(--app-weight-semibold);
    text-transform: capitalize;
}

.schedule-cell {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    min-height: 26px;
    padding: 0.1rem 0.25rem;
    border-radius: var(--app-radius-xs);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
    font-variant-numeric: tabular-nums;
}

.schedule-cell.is-shift {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.schedule-cell.is-off {
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
}

.schedule-cell.is-holiday {
    background: var(--app-info-bg);
    color: var(--app-info-ink);
}

.schedule-cell.is-empty {
    color: var(--app-muted);
    font-weight: var(--app-weight-regular);
}

.schedule-audit-card {
    margin-top: 1rem;
}

.schedule-audit {
    display: grid;
    gap: 0.9rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.schedule-audit li {
    padding-left: 1rem;
    border-left: 2px solid var(--app-border);
}

.schedule-audit-head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.35rem 1rem;
}

.schedule-audit-head strong {
    font-size: var(--app-text-base);
    font-weight: var(--app-weight-bold);
}

.schedule-audit-head small {
    color: var(--app-muted);
    font-size: var(--app-text-xs);
}

.schedule-audit p {
    margin: 0.25rem 0;
    color: var(--app-ink);
    font-size: var(--app-text-sm);
    line-height: var(--app-leading-normal);
}

.schedule-audit .schedule-audit-reason {
    color: var(--app-muted);
}

@media (min-width: 992px) {
    .schedule-summary-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .schedule-table th,
    .schedule-table td {
        padding: 0.35rem 0.25rem;
    }

    .schedule-sticky {
        min-width: 130px;
    }
}
</style>
