<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import ModuleNav from '../../components/ModuleNav.vue';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
import AppDialog from '@/components/ui/AppDialog.vue';
import { useToast } from '@/composables/useToast';
import { formatDate } from '@/composables/useFormatters';

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
const { success: toastSuccess, error: toastError } = useToast();
const resetDialogOpen = ref(false);
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

const confirmReset = () => {
    resetDialogOpen.value = false;
    router.visit(props.resetUrl);
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
        <AppPageHeader
            eyebrow="Loyalty / Point Rate"
            title="Kurs Point"
            description="Atur nilai poin masuk dari transaksi dan nilai tukar poin saat redeem."
        />

        <ModuleNav module-key="loyaltyModule" aria-label="Modul Loyalty" />

        <form @submit.prevent="submit">
            <div class="row g-3">
                <div class="col-xl-6">
                    <section class="card h-100">
                        <div class="card-header">
                            <h2 class="h6 mb-1">Kurs: Rupiah ke Point In</h2>
                            <small class="text-muted">Dipakai untuk menghitung poin masuk dari transaksi.</small>
                        </div>
                        <div class="card-body">
                            <label class="form-label is-required" for="points-in-idr-base">Kurs dasar point masuk</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input
                                    id="points-in-idr-base"
                                    v-model.number="form.in_idr_base"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.in_idr_base }"
                                    :aria-describedby="form.errors.in_idr_base ? 'points-in-idr-base-error' : undefined"
                                    aria-label="Rupiah dasar point masuk"
                                    required
                                />
                                <span class="input-group-text">=</span>
                                <input
                                    id="points-in-points-base"
                                    v-model.number="form.in_points_per_base"
                                    type="number"
                                    min="0.000001"
                                    step="0.000001"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.in_points_per_base }"
                                    :aria-describedby="form.errors.in_points_per_base ? 'points-in-points-base-error' : undefined"
                                    aria-label="Point per rupiah dasar"
                                    required
                                />
                                <span class="input-group-text">point</span>
                            </div>
                            <div v-if="form.errors.in_idr_base" id="points-in-idr-base-error" class="invalid-feedback d-block" role="alert">
                                {{ form.errors.in_idr_base }}
                            </div>
                            <div v-if="form.errors.in_points_per_base" id="points-in-points-base-error" class="invalid-feedback d-block" role="alert">
                                {{ form.errors.in_points_per_base }}
                            </div>

                            <div class="settings-summary mt-3">
                                <div class="fw-semibold mb-2">Ringkasan Point In</div>
                                <div>
                                    <strong>1 Rp</strong> =
                                    <strong class="num">{{ formatNumber(inPointsPerIdr, 6) }}</strong> point
                                </div>
                                <div class="text-muted small">
                                    Rp{{ formatNumber(100000) }} sekitar
                                    <strong class="num">{{ formatNumber(samplePointIn, 2) }} point</strong>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="col-xl-6">
                    <section class="card h-100">
                        <div class="card-header">
                            <h2 class="h6 mb-1">Kurs: Point Out ke Rupiah</h2>
                            <small class="text-muted">Dipakai saat customer menukar poin.</small>
                        </div>
                        <div class="card-body">
                            <label class="form-label is-required" for="points-out-points-base">Kurs dasar point keluar</label>
                            <div class="input-group">
                                <input
                                    id="points-out-points-base"
                                    v-model.number="form.out_points_base"
                                    type="number"
                                    min="0.000001"
                                    step="0.000001"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.out_points_base }"
                                    :aria-describedby="form.errors.out_points_base ? 'points-out-points-base-error' : undefined"
                                    aria-label="Point dasar untuk redeem"
                                    required
                                />
                                <span class="input-group-text">point</span>
                                <span class="input-group-text">=</span>
                                <span class="input-group-text">Rp</span>
                                <input
                                    id="points-out-idr-base"
                                    v-model.number="form.out_idr_per_base"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.out_idr_per_base }"
                                    :aria-describedby="form.errors.out_idr_per_base ? 'points-out-idr-base-error' : undefined"
                                    aria-label="Rupiah per point dasar"
                                    required
                                />
                            </div>
                            <div v-if="form.errors.out_points_base" id="points-out-points-base-error" class="invalid-feedback d-block" role="alert">
                                {{ form.errors.out_points_base }}
                            </div>
                            <div v-if="form.errors.out_idr_per_base" id="points-out-idr-base-error" class="invalid-feedback d-block" role="alert">
                                {{ form.errors.out_idr_per_base }}
                            </div>

                            <div class="settings-summary mt-3">
                                <div class="fw-semibold mb-2">Ringkasan Point Out</div>
                                <div>
                                    <strong>1 point</strong> =
                                    <strong class="num">Rp{{ formatNumber(outIdrPerPoint) }}</strong>
                                </div>
                                <div class="text-muted small">
                                    100 point sekitar
                                    <strong class="num">Rp{{ formatNumber(sampleRedeemValue) }}</strong>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="settings-actions">
                <small v-if="props.data.updated_at" class="text-muted">
                    Terakhir diubah oleh {{ props.data.updated_by ?? 'system' }} pada
                    {{ formatDate(props.data.updated_at, true) }}
                </small>
                <div class="d-flex gap-2 ms-auto">
                    <button class="btn btn-outline-secondary" type="button" @click="resetDialogOpen = true">
                        <i class="mdi mdi-restore me-1" aria-hidden="true"></i>
                        Reset
                    </button>
                    <button class="btn btn-primary" type="submit" :disabled="form.processing" :aria-busy="form.processing">
                        <i v-if="form.processing" class="mdi mdi-loading mdi-spin me-1" aria-hidden="true"></i>
                        <i v-else class="mdi mdi-content-save-outline me-1" aria-hidden="true"></i>
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </form>

        <AppDialog
            :open="resetDialogOpen"
            title="Reset kurs point?"
            description="Nilai kurs akan dikembalikan ke pengaturan awal. Perubahan yang belum disimpan akan hilang."
            confirm-label="Reset kurs"
            cancel-label="Batal"
            variant="danger"
            @update:open="resetDialogOpen = false"
            @confirm="confirmReset"
            @cancel="resetDialogOpen = false"
        />
    </div>
</template>

<style scoped>
.settings-page {
    color: var(--app-ink);
}

.settings-summary {
    padding: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-lg);
    background: var(--app-surface-2);
}

.settings-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--app-border);
}

@media (max-width: 575.98px) {
    .settings-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .settings-actions > .d-flex {
        width: 100%;
    }

    .settings-actions .btn {
        flex: 1 1 auto;
    }
}
</style>
