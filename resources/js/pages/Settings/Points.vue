<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import ModuleNav from '../../components/ModuleNav.vue';

interface PointRate {
    idr_base: number;
    points_per_base: number;
    points_per_idr: number;
}

interface RedeemRate {
    points_base: number;
    idr_per_base: number;
    idr_per_point: number;
}

interface Props {
    data: {
        in: PointRate;
        out: RedeemRate;
        updated_by: string | null;
        updated_at: string | null;
    };
    updateUrl: string;
    resetUrl: string;
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = useForm({
    in_idr_base: props.data.in.idr_base,
    in_points_per_base: props.data.in.points_per_base,
    out_points_base: props.data.out.points_base,
    out_idr_per_base: props.data.out.idr_per_base,
});

const inPointsPerIdr = computed(() => {
    const base = Number(form.in_idr_base);

    return base > 0 ? Number(form.in_points_per_base) / base : 0;
});

const outIdrPerPoint = computed(() => {
    const points = Number(form.out_points_base);

    return points > 0 ? Number(form.out_idr_per_base) / points : 0;
});

const samplePointIn = computed(() => 100000 * inPointsPerIdr.value);
const sampleRedeemValue = computed(() => 100 * outIdrPerPoint.value);

const submit = () => {
    form.post(props.updateUrl, {
        preserveScroll: true,
    });
};

const formatNumber = (value: number, digits = 0) =>
    new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    }).format(Number(value ?? 0));
</script>

<template>
    <Head title="Kurs Point" />

    <div class="settings-page">
        <div class="settings-header">
            <span class="settings-eyebrow">Loyalty / Point Rate</span>
            <h1>Kurs Point</h1>
            <p>Atur nilai poin masuk dari transaksi dan nilai tukar poin saat redeem.</p>
        </div>

        <ModuleNav module-key="loyaltyModule" aria-label="Modul Loyalty" />

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <form @submit.prevent="submit">
            <div class="row g-3">
                <div class="col-xl-6">
                    <div class="card settings-card h-100">
                        <div class="card-header">
                            <h5 class="mb-1">Kurs: Rupiah ke Point In</h5>
                            <small class="text-muted">Dipakai untuk menghitung poin masuk dari transaksi.</small>
                        </div>
                        <div class="card-body">
                            <label class="form-label">Kurs dasar point masuk</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input
                                    v-model.number="form.in_idr_base"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.in_idr_base }"
                                    required
                                />
                                <span class="input-group-text">=</span>
                                <input
                                    v-model.number="form.in_points_per_base"
                                    type="number"
                                    min="0.000001"
                                    step="0.000001"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.in_points_per_base }"
                                    required
                                />
                                <span class="input-group-text">point</span>
                            </div>
                            <div v-if="form.errors.in_idr_base" class="text-danger small mt-1">
                                {{ form.errors.in_idr_base }}
                            </div>
                            <div v-if="form.errors.in_points_per_base" class="text-danger small mt-1">
                                {{ form.errors.in_points_per_base }}
                            </div>

                            <div class="settings-summary mt-3">
                                <div class="fw-semibold mb-2">Ringkasan Point In</div>
                                <div>
                                    <strong>1 Rp</strong> =
                                    <strong>{{ formatNumber(inPointsPerIdr, 6) }}</strong> point
                                </div>
                                <div class="text-muted small">
                                    Rp{{ formatNumber(100000) }} sekitar
                                    <strong>{{ formatNumber(samplePointIn, 2) }} point</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card settings-card h-100">
                        <div class="card-header">
                            <h5 class="mb-1">Kurs: Point Out ke Rupiah</h5>
                            <small class="text-muted">Dipakai saat customer menukar poin.</small>
                        </div>
                        <div class="card-body">
                            <label class="form-label">Kurs dasar point keluar</label>
                            <div class="input-group">
                                <input
                                    v-model.number="form.out_points_base"
                                    type="number"
                                    min="0.000001"
                                    step="0.000001"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.out_points_base }"
                                    required
                                />
                                <span class="input-group-text">point</span>
                                <span class="input-group-text">=</span>
                                <span class="input-group-text">Rp</span>
                                <input
                                    v-model.number="form.out_idr_per_base"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.out_idr_per_base }"
                                    required
                                />
                            </div>
                            <div v-if="form.errors.out_points_base" class="text-danger small mt-1">
                                {{ form.errors.out_points_base }}
                            </div>
                            <div v-if="form.errors.out_idr_per_base" class="text-danger small mt-1">
                                {{ form.errors.out_idr_per_base }}
                            </div>

                            <div class="settings-summary mt-3">
                                <div class="fw-semibold mb-2">Ringkasan Point Out</div>
                                <div>
                                    <strong>1 point</strong> =
                                    <strong>Rp{{ formatNumber(outIdrPerPoint) }}</strong>
                                </div>
                                <div class="text-muted small">
                                    100 point sekitar
                                    <strong>Rp{{ formatNumber(sampleRedeemValue) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
                <small v-if="props.data.updated_at" class="text-muted">
                    Terakhir diubah oleh {{ props.data.updated_by ?? 'system' }} pada
                    {{ props.data.updated_at }}
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
</style>
