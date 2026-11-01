<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface ModelRun {
    id: string;
    model_name: string;
    model_version: string;
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

interface Props {
    latestRun: ModelRun | null;
    summary: Summary;
    suggestions: string[];
    prompt: string | null;
    answer: string | null;
    error: string | null;
    urls: {
        index: string;
        ask: string;
    };
}

const props = defineProps<Props>();

const form = useForm({
    prompt: props.prompt ?? '',
});

const submit = () => {
    form.post(props.urls.ask, {
        preserveScroll: true,
        preserveState: true,
    });
};

const askSuggestion = (suggestion: string) => {
    form.prompt = suggestion;
    submit();
};

const metrics = computed(() => [
    { label: 'Rekomendasi', value: props.summary.total },
    { label: 'Risiko kritis', value: props.summary.critical },
    { label: 'Risiko tinggi', value: props.summary.high },
    { label: 'Saran restock', value: props.summary.recommended_units },
]);

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);

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

watch(
    () => props.prompt,
    (value) => {
        if (value) {
            form.prompt = value;
        }
    },
);
</script>

<template>
    <Head title="AI Assistant" />

    <div class="assistant-page">
        <section class="assistant-hero">
            <div>
                <span class="assistant-eyebrow">Analis internal</span>
                <h1>AI Assistant</h1>
                <p>
                    Tanya kondisi forecast, risiko stok, dan prioritas restock. Jawaban memakai data
                    dari model terakhir yang selesai.
                </p>
            </div>
            <Link :href="props.urls.index" class="btn btn-light btn-sm">
                <i class="mdi mdi-refresh me-1"></i>
                Reset percakapan
            </Link>
        </section>

        <div class="row g-3 mb-3">
            <div v-for="metric in metrics" :key="metric.label" class="col-6 col-xl-3">
                <div class="card assistant-card h-100">
                    <div class="card-body">
                        <div class="assistant-label">{{ metric.label }}</div>
                        <div class="assistant-value">{{ formatNumber(metric.value) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card assistant-card h-100">
                    <div class="card-body">
                        <h5 class="mb-3">Konteks model</h5>
                        <template v-if="latestRun">
                            <dl class="row mb-0 assistant-model">
                                <dt class="col-5">Model</dt>
                                <dd class="col-7">{{ latestRun.model_name }}</dd>
                                <dt class="col-5">Versi</dt>
                                <dd class="col-7">{{ latestRun.model_version }}</dd>
                                <dt class="col-5">Training</dt>
                                <dd class="col-7">
                                    {{ formatDate(latestRun.training_from) }} - {{ formatDate(latestRun.training_to) }}
                                </dd>
                                <dt class="col-5">Selesai</dt>
                                <dd class="col-7">{{ formatDate(latestRun.completed_at) }}</dd>
                            </dl>
                        </template>
                        <p v-else class="text-muted mb-0">
                            Belum ada model forecast yang selesai.
                        </p>

                        <hr />

                        <h6 class="mb-2">Contoh pertanyaan</h6>
                        <div class="d-grid gap-2">
                            <button
                                v-for="suggestion in suggestions"
                                :key="suggestion"
                                type="button"
                                class="btn btn-outline-secondary btn-sm text-start"
                                :disabled="form.processing"
                                @click="askSuggestion(suggestion)"
                            >
                                {{ suggestion }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card assistant-card">
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <label class="form-label fw-semibold" for="assistant-prompt">
                                Pertanyaan
                            </label>
                            <textarea
                                id="assistant-prompt"
                                v-model="form.prompt"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.prompt }"
                                rows="4"
                                maxlength="1000"
                                placeholder="Contoh: Produk apa yang perlu restock paling banyak minggu ini?"
                            ></textarea>
                            <div v-if="form.errors.prompt" class="invalid-feedback d-block">
                                {{ form.errors.prompt }}
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    AI dapat membuat kesalahan. Verifikasi angka dengan dashboard.
                                </small>
                                <button class="btn btn-primary" type="submit" :disabled="form.processing">
                                    <i class="mdi mdi-send me-1"></i>
                                    {{ form.processing ? 'Menganalisis...' : 'Tanya AI' }}
                                </button>
                            </div>
                        </form>

                        <div v-if="error" class="alert alert-danger mt-3 mb-0">
                            {{ error }}
                        </div>

                        <div v-if="answer" class="assistant-answer mt-3">
                            <div class="assistant-answer-header">
                                <i class="mdi mdi-robot-outline me-1"></i>
                                Jawaban
                            </div>
                            <div class="assistant-answer-body">{{ answer }}</div>
                        </div>

                        <div v-if="!answer && !error" class="assistant-empty mt-3">
                            Belum ada jawaban. Pilih contoh pertanyaan atau tulis pertanyaan sendiri.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.assistant-page {
    --assistant-ink: #17111f;
    --assistant-muted: #6b6474;
    --assistant-border: #ebe6ef;
    --assistant-pink: #ec4899;
    --assistant-purple: #7c3aed;
    color: var(--assistant-ink);
}

.assistant-hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 1.75rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: var(--assistant-ink);
    color: #fff;
}

.assistant-eyebrow {
    display: inline-block;
    margin-bottom: 0.5rem;
    color: #f9a8d4;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.assistant-hero h1 {
    margin: 0 0 0.35rem;
    font-size: 1.75rem;
}

.assistant-hero p {
    margin: 0;
    color: #d8d2de;
    max-width: 46rem;
}

.assistant-card {
    border: 1px solid var(--assistant-border);
    border-radius: 0.9rem;
    box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05);
}

.assistant-label {
    color: var(--assistant-muted);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.assistant-value {
    margin-top: 0.25rem;
    font-size: 1.5rem;
    font-weight: 800;
}

.assistant-model dt {
    color: var(--assistant-muted);
    font-weight: 500;
}

.assistant-model dd {
    font-weight: 600;
}

.assistant-answer {
    border: 1px solid #e9d5ff;
    border-radius: 0.75rem;
    background: #faf5ff;
    overflow: hidden;
}

.assistant-answer-header {
    padding: 0.7rem 1rem;
    background: #f3e8ff;
    color: #6b21a8;
    font-weight: 700;
}

.assistant-answer-body {
    padding: 1rem;
    white-space: pre-wrap;
    line-height: 1.65;
}

.assistant-empty {
    padding: 2.5rem 1rem;
    border: 1px dashed var(--assistant-border);
    border-radius: 0.75rem;
    color: var(--assistant-muted);
    text-align: center;
}

@media (max-width: 767.98px) {
    .assistant-hero {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
