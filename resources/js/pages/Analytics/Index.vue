<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useTheme } from '@/composables/useTheme';

defineOptions({ layout: AppLayout });

import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Chart from 'chart.js/auto';

interface StoreOption {
    id: string;
    name: string;
}

interface ModelRun {
    id: string;
    model_name: string;
    model_version: string;
    status: string;
    training_from: string | null;
    training_to: string | null;
    completed_at: string | null;
    metrics: Record<string, number | string> | null;
}

interface Summary {
    total: number;
    critical: number;
    high: number;
    recommended_units: number;
}

interface ForecastRow {
    forecast_date: string;
    predicted_qty: string | number;
    predicted_revenue: string | number;
}

interface Recommendation {
    id: string;
    store: StoreOption | null;
    product: { id: string; code: string; name: string } | null;
    current_stock: number;
    reserved_stock: number;
    forecast_qty_7d: string | number;
    safety_stock: number;
    recommended_qty: number;
    risk_level: string;
    status: string;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    latestRun: ModelRun | null;
    recommendations: Paginator<Recommendation>;
    summary: Summary;
    forecastDaily: ForecastRow[];
    filters: {
        store_id?: string;
        risk_level?: string;
        status?: string;
    };
    stores: StoreOption[];
    urls: {
        index: string;
        run: string;
        review: string;
    };
    flash: {
        success?: string;
        error?: string;
    };
}

const props = defineProps<Props>();
const { isDark } = useTheme();

const form = reactive({
    store_id: props.filters.store_id ?? '',
    risk_level: props.filters.risk_level ?? '',
    status: props.filters.status ?? '',
});

const running = ref(false);
const chartCanvas = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;

const metrics = computed(() => [
    { label: 'Rekomendasi', value: props.summary.total, className: 'text-primary' },
    { label: 'Risiko Kritis', value: props.summary.critical, className: 'text-danger' },
    { label: 'Risiko Tinggi', value: props.summary.high, className: 'text-warning' },
    { label: 'Saran Restock', value: props.summary.recommended_units, className: 'text-success' },
]);

const eligibleSeries = computed(() => Number(props.latestRun?.metrics?.eligible_series ?? 0));
const totalSeries = computed(() => Number(props.latestRun?.metrics?.total_series ?? 0));
const minimumSalesDays = computed(() => Number(props.latestRun?.metrics?.minimum_sales_days ?? 7));

const applyFilters = () => {
    router.get(
        props.urls.index,
        {
            store_id: form.store_id || undefined,
            risk_level: form.risk_level || undefined,
            status: form.status || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    form.store_id = '';
    form.risk_level = '';
    form.status = '';
    applyFilters();
};

const runForecast = () => {
    if (running.value) {
        return;
    }

    running.value = true;
    router.post(props.urls.run, {}, {
        preserveScroll: true,
        onFinish: () => {
            running.value = false;
        },
    });
};

const review = (recommendation: Recommendation, status: 'approved' | 'rejected') => {
    const label = status === 'approved' ? 'menyetujui' : 'menolak';

    if (!window.confirm(`Yakin ingin ${label} rekomendasi ini?`)) {
        return;
    }

    router.patch(
        props.urls.review.replace('__ID__', recommendation.id),
        {
            status,
            review_notes: 'Ditinjau dari dashboard AI',
        },
        {
            preserveScroll: true,
        },
    );
};

const formatNumber = (value: number | string, digits = 0) =>
    new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    }).format(Number(value ?? 0));

const formatCurrency = (value: number | string) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};

const renderChart = () => {
    if (!chartCanvas.value || props.forecastDaily.length === 0) {
        chart?.destroy();
        chart = null;
        return;
    }

    chart?.destroy();

    chart = new Chart(chartCanvas.value, {
        type: 'line',
        data: {
            labels: props.forecastDaily.map((row) =>
                new Intl.DateTimeFormat('id-ID', {
                    day: '2-digit',
                    month: 'short',
                }).format(new Date(row.forecast_date)),
            ),
            datasets: [
                {
                    label: 'Prediksi Unit',
                    data: props.forecastDaily.map((row) => Number(row.predicted_qty ?? 0)),
                    borderColor: '#3b4cf0',
                    backgroundColor: 'rgba(59, 76, 240, 0.12)',
                    fill: true,
                    tension: 0.35,
                    yAxisID: 'y',
                },
                {
                    label: 'Prediksi Omzet',
                    data: props.forecastDaily.map((row) => Number(row.predicted_revenue ?? 0)),
                    borderColor: '#6f7dff',
                    tension: 0.35,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: isDark.value ? '#dbe5ed' : '#42515e',
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        color: isDark.value ? '#a9b8c8' : '#65727e',
                    },
                    grid: {
                        color: isDark.value ? 'rgba(255,255,255,0.08)' : 'rgba(28,42,107,0.08)',
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: isDark.value ? '#a9b8c8' : '#65727e',
                    },
                    grid: {
                        color: isDark.value ? 'rgba(255,255,255,0.08)' : 'rgba(28,42,107,0.08)',
                    },
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        color: isDark.value ? '#a9b8c8' : '#65727e',
                        callback: (value) => formatCurrency(Number(value)),
                    },
                },
            },
        },
    });
};

onMounted(renderChart);

watch(() => props.forecastDaily, () => nextTick(renderChart), { deep: true });
watch(isDark, () => nextTick(renderChart));

onBeforeUnmount(() => {
    chart?.destroy();
    chart = null;
});
</script>

<template>
    <Head title="AI Analytics" />

    <div class="ai-page">
        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <section class="ai-hero">
            <div>
                <span class="ai-eyebrow">Forecast dan restock</span>
                <h1>AI Analytics</h1>
                <p>
                    Pantau prediksi penjualan dan rekomendasi restock dari model yang sedang aktif.
                </p>
            </div>
            <button class="btn btn-light btn-run" type="button" :disabled="running" @click="runForecast">
                <i class="mdi mdi-play me-1"></i>
                {{ running ? 'Menjalankan...' : 'Jalankan Prediksi' }}
            </button>
        </section>

        <div v-if="!latestRun" class="alert alert-info">
            Belum ada hasil analisis yang tersedia.
        </div>
        <div v-else-if="eligibleSeries === 0" class="alert alert-warning">
            <strong>Data belum cukup untuk prediksi.</strong>
            Ditemukan {{ totalSeries }} seri produk, tetapi belum ada yang memiliki minimal
            {{ minimumSalesDays }} hari penjualan berbeda.
        </div>

        <div class="row g-3 mb-3">
            <div v-for="metric in metrics" :key="metric.label" class="col-12 col-sm-6 col-xl-3">
                <div class="card ai-card h-100">
                    <div class="card-body">
                        <div class="ai-label">{{ metric.label }}</div>
                        <div class="ai-value" :class="metric.className">
                            {{ formatNumber(metric.value) }}
                        </div>
                        <small class="text-muted">
                            {{ metric.label === 'Saran Restock' ? 'Total unit' : 'Model aktif' }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="card ai-card h-100">
                    <div class="card-header">
                        <h5 class="mb-1">Forecast 7 Hari</h5>
                        <small class="text-muted">Prediksi unit dan omzet seluruh channel.</small>
                    </div>
                    <div class="card-body">
                        <div v-if="forecastDaily.length" class="ai-chart">
                            <canvas ref="chartCanvas"></canvas>
                        </div>
                        <div v-else class="ai-empty">
                            Forecast belum diterbitkan karena histori penjualan belum memenuhi syarat minimum.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card ai-card h-100">
                    <div class="card-header">
                        <h5 class="mb-1">Model Aktif</h5>
                    </div>
                    <div class="card-body">
                        <dl v-if="latestRun" class="row mb-0 ai-model">
                            <dt class="col-5">Model</dt>
                            <dd class="col-7">{{ latestRun.model_name }}</dd>
                            <dt class="col-5">Versi</dt>
                            <dd class="col-7">{{ latestRun.model_version }}</dd>
                            <dt class="col-5">Training</dt>
                            <dd class="col-7">
                                {{ formatDate(latestRun.training_from) }} - {{ formatDate(latestRun.training_to) }}
                            </dd>
                            <dt class="col-5">Seri layak</dt>
                            <dd class="col-7">{{ eligibleSeries }} / {{ totalSeries }}</dd>
                            <dt class="col-5">Selesai</dt>
                            <dd class="col-7">{{ formatDate(latestRun.completed_at) }}</dd>
                            <dt class="col-5">Status</dt>
                            <dd class="col-7">
                                <span class="badge bg-success">{{ latestRun.status }}</span>
                            </dd>
                        </dl>
                        <div v-else class="ai-empty">Belum ada model aktif.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card ai-card">
            <div class="card-header">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-md-4">
                        <label class="form-label" for="filter-store">Store</label>
                        <select id="filter-store" v-model="form.store_id" class="form-select">
                            <option value="">Semua Store</option>
                            <option v-for="store in stores" :key="store.id" :value="store.id">
                                {{ store.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-risk">Risiko</label>
                        <select id="filter-risk" v-model="form.risk_level" class="form-select">
                            <option value="">Semua</option>
                            <option value="critical">Kritis</option>
                            <option value="high">Tinggi</option>
                            <option value="medium">Sedang</option>
                            <option value="low">Rendah</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-status">Status</label>
                        <select id="filter-status" v-model="form.status" class="form-select">
                            <option value="">Semua</option>
                            <option value="recommended">Recommended</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary" type="submit">
                            <i class="mdi mdi-filter me-1"></i>
                            Filter
                        </button>
                        <button class="btn btn-outline-secondary ms-2" type="button" @click="resetFilters">
                            Reset
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle ai-table">
                        <thead>
                            <tr>
                                <th>Store / Produk</th>
                                <th class="text-end">Stok</th>
                                <th class="text-end">Prediksi 7 Hari</th>
                                <th class="text-end">Safety</th>
                                <th class="text-end">Rekomendasi</th>
                                <th>Risiko</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in recommendations.data" :key="row.id">
                                <td>
                                    <strong>{{ row.store?.name ?? '-' }}</strong>
                                    <br />
                                    <small class="text-muted">
                                        {{ row.product?.code ?? '-' }} - {{ row.product?.name ?? '-' }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    {{ formatNumber(row.current_stock) }}
                                    <small class="text-muted">(-{{ formatNumber(row.reserved_stock) }})</small>
                                </td>
                                <td class="text-end">{{ formatNumber(row.forecast_qty_7d, 1) }}</td>
                                <td class="text-end">{{ formatNumber(row.safety_stock) }}</td>
                                <td class="text-end fw-bold">{{ formatNumber(row.recommended_qty) }}</td>
                                <td>
                                    <span class="risk-badge" :class="`risk-${row.risk_level}`">
                                        {{ row.risk_level.toUpperCase() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge">{{ row.status }}</span>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-success me-1"
                                        type="button"
                                        title="Setujui"
                                        @click="review(row, 'approved')"
                                    >
                                        <i class="mdi mdi-check"></i>
                                    </button>
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        type="button"
                                        title="Tolak"
                                        @click="review(row, 'rejected')"
                                    >
                                        <i class="mdi mdi-close"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="recommendations.data.length === 0">
                                <td colspan="8" class="ai-empty">
                                    Belum ada rekomendasi untuk filter ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                    <small class="text-muted">
                        Menampilkan {{ recommendations.from ?? 0 }} - {{ recommendations.to ?? 0 }}
                        dari {{ recommendations.total }} rekomendasi
                    </small>
                    <div class="d-flex flex-wrap gap-1">
                        <template v-for="link in recommendations.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="btn btn-sm"
                                :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                                preserve-scroll
                                v-html="link.label"
                            />
                            <span v-else class="btn btn-sm btn-outline-secondary disabled" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ai-page {
    --ai-ink: var(--app-ink);
    --ai-muted: var(--app-muted);
    --ai-border: var(--app-border);
    --ai-accent: #3b4cf0;
    --ai-accent-soft: #eef1ff;
    color: var(--ai-ink);
}

.ai-hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 1.75rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: var(--ai-ink);
    color: #fff;
}

.ai-eyebrow {
    display: inline-block;
    margin-bottom: 0.5rem;
    color: var(--app-hero-eyebrow);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.ai-hero h1 {
    margin: 0 0 0.35rem;
    font-size: 1.75rem;
}

.ai-hero p {
    margin: 0;
    color: var(--app-hero-muted);
    max-width: 42rem;
}

.btn-run {
    flex-shrink: 0;
    font-weight: 700;
}

.ai-card {
    border: 1px solid var(--ai-border);
    border-radius: 0.9rem;
    box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05);
}

.ai-label {
    color: var(--ai-muted);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.ai-value {
    margin: 0.35rem 0 0.15rem;
    font-size: 1.7rem;
    font-weight: 800;
}

.ai-chart {
    height: 310px;
}

.ai-empty {
    padding: 2.5rem 1rem;
    color: var(--ai-muted);
    text-align: center;
}

.ai-model dt {
    color: var(--ai-muted);
    font-weight: 500;
}

.ai-model dd {
    font-weight: 600;
}

.ai-table th {
    color: var(--ai-muted);
    font-size: 0.75rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.risk-badge,
.status-badge {
    display: inline-flex;
    padding: 0.3rem 0.55rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 800;
}

.status-badge {
    background: #eef1ff;
    color: #2f3dd1;
    text-transform: capitalize;
}

.risk-critical {
    background: #fee2e2;
    color: #b91c1c;
}

.risk-high {
    background: #ffedd5;
    color: #c2410c;
}

.risk-medium {
    background: #fef3c7;
    color: #a16207;
}

.risk-low {
    background: #dcfce7;
    color: #15803d;
}

@media (max-width: 767.98px) {
    .ai-hero {
        align-items: flex-start;
        flex-direction: column;
    }

    .btn-run {
        width: 100%;
    }
}
</style>
