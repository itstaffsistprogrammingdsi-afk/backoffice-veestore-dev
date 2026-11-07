<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

type ScheduleMode = 'instant' | 'daily' | 'weekly' | 'monthly';

interface ModeOption {
    value: ScheduleMode;
    label: string;
    description: string;
}

interface DayOption {
    value: number;
    label: string;
}

interface Props {
    data: {
        mode: ScheduleMode;
        time: string;
        day_of_week: number;
        day_of_month: number;
        updated_by: string | null;
        updated_at: string | null;
    };
    modes: ModeOption[];
    daysOfWeek: DayOption[];
    updateUrl: string;
    resetUrl: string;
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = useForm({
    mode: props.data.mode,
    time: props.data.time,
    day_of_week: props.data.day_of_week,
    day_of_month: props.data.day_of_month,
});

const selectedDay = computed(
    () => props.daysOfWeek.find((day) => day.value === Number(form.day_of_week))?.label ?? 'Senin',
);

const scheduleSummary = computed(() => {
    if (form.mode === 'instant') {
        return 'Email dikirim segera setelah ada SKU baru yang menyentuh barrier.';
    }

    if (form.mode === 'daily') {
        return `Email ringkasan dikirim setiap hari pukul ${form.time}.`;
    }

    if (form.mode === 'weekly') {
        return `Email ringkasan dikirim setiap hari ${selectedDay.value} pukul ${form.time}.`;
    }

    return `Email ringkasan dikirim setiap tanggal ${form.day_of_month} pukul ${form.time}.`;
});

const submit = () => {
    form.post(props.updateUrl, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Email Notifikasi Barrier" />

    <div class="settings-page">
        <div class="settings-header">
            <span class="settings-eyebrow">Monitoring / Stok Barrier</span>
            <h1>Email Notifikasi Barrier</h1>
            <p>Atur kapan email peringatan stok barrier dikirim ke tim terkait.</p>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <form @submit.prevent="submit">
            <div class="card settings-card">
                <div class="card-header">
                    <h5 class="mb-1">Jadwal pengiriman</h5>
                    <small class="text-muted">
                        Notifikasi internal di backoffice tetap diperbarui setiap menit, terlepas dari jadwal email ini.
                    </small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div v-for="mode in props.modes" :key="mode.value" class="col-md-6">
                            <label
                                class="schedule-option"
                                :class="{ 'schedule-option--active': form.mode === mode.value }"
                            >
                                <input
                                    v-model="form.mode"
                                    class="form-check-input mt-1"
                                    type="radio"
                                    name="schedule-mode"
                                    :value="mode.value"
                                />
                                <span>
                                    <strong>{{ mode.label }}</strong>
                                    <small>{{ mode.description }}</small>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div v-if="form.mode !== 'instant'" class="row g-3 mt-1">
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-label" for="schedule-time">Waktu pengiriman</label>
                            <input
                                id="schedule-time"
                                v-model="form.time"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.time }"
                                type="time"
                                required
                            />
                            <div v-if="form.errors.time" class="invalid-feedback d-block">
                                {{ form.errors.time }}
                            </div>
                        </div>

                        <div v-if="form.mode === 'weekly'" class="col-sm-6 col-lg-4">
                            <label class="form-label" for="schedule-day-of-week">Hari pengiriman</label>
                            <select
                                id="schedule-day-of-week"
                                v-model.number="form.day_of_week"
                                class="form-select"
                                :class="{ 'is-invalid': form.errors.day_of_week }"
                                required
                            >
                                <option v-for="day in props.daysOfWeek" :key="day.value" :value="day.value">
                                    {{ day.label }}
                                </option>
                            </select>
                            <div v-if="form.errors.day_of_week" class="invalid-feedback d-block">
                                {{ form.errors.day_of_week }}
                            </div>
                        </div>

                        <div v-if="form.mode === 'monthly'" class="col-sm-6 col-lg-4">
                            <label class="form-label" for="schedule-day-of-month">Tanggal pengiriman</label>
                            <select
                                id="schedule-day-of-month"
                                v-model.number="form.day_of_month"
                                class="form-select"
                                :class="{ 'is-invalid': form.errors.day_of_month }"
                                required
                            >
                                <option v-for="day in 28" :key="day" :value="day">{{ day }}</option>
                            </select>
                            <div class="form-text">Pilih tanggal 1–28 agar jadwal tetap berjalan di bulan Februari.</div>
                            <div v-if="form.errors.day_of_month" class="invalid-feedback d-block">
                                {{ form.errors.day_of_month }}
                            </div>
                        </div>
                    </div>

                    <div class="settings-summary mt-3">
                        <div class="fw-semibold mb-1">Ringkasan jadwal</div>
                        <div>{{ scheduleSummary }}</div>
                        <small class="text-muted">
                            Penerima email adalah user backoffice dengan akses halaman stok barrier, ditambah alamat pada
                            <code>STOCK_BARRIER_ALERT_EMAILS</code>.
                        </small>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
                <small v-if="props.data.updated_at" class="text-muted">
                    Terakhir diubah oleh {{ props.data.updated_by ?? 'system' }} pada {{ props.data.updated_at }}
                </small>
                <div class="d-flex gap-2 ms-auto">
                    <Link :href="props.resetUrl" class="btn btn-outline-secondary">Reset</Link>
                    <button class="btn btn-primary" type="submit" :disabled="form.processing">
                        <i class="mdi mdi-content-save-outline me-1"></i>
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<style scoped>
.settings-page {
    --settings-ink: var(--app-ink);
    --settings-muted: var(--app-muted);
    --settings-border: var(--app-border);
    color: var(--settings-ink);
}

.settings-header {
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: var(--settings-ink);
    color: #fff;
}

.settings-eyebrow {
    display: inline-block;
    margin-bottom: 0.5rem;
    color: var(--app-hero-eyebrow);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.settings-header h1 {
    margin: 0 0 0.35rem;
    font-size: 1.5rem;
}

.settings-header p {
    margin: 0;
    color: var(--app-hero-muted);
}

.settings-card {
    border: 1px solid var(--settings-border);
    border-radius: 0.9rem;
    box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05);
}

.settings-summary {
    padding: 1rem;
    border: 1px solid var(--settings-border);
    border-radius: 0.75rem;
    background: var(--app-surface-2);
}

.schedule-option {
    display: flex;
    gap: 0.75rem;
    height: 100%;
    padding: 0.9rem 1rem;
    border: 1px solid var(--settings-border);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.schedule-option strong {
    display: block;
    font-size: 0.95rem;
}

.schedule-option small {
    display: block;
    margin-top: 0.2rem;
    color: var(--settings-muted);
    line-height: 1.4;
}

.schedule-option--active {
    border-color: var(--app-ink);
    background: var(--app-surface-2);
}

@media (max-width: 575.98px) {
    .schedule-option {
        padding: 0.75rem;
    }
}
</style>
