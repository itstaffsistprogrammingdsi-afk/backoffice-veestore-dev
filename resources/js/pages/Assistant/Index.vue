<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import AssistantChat from '@/components/assistant/AssistantChat.vue';
import VeeMascot from '@/components/assistant/VeeMascot.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

interface ChatMessage {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    tools?: Array<{ name: string; summary?: string }>;
    created_at?: string;
}

interface Feature {
    key: string;
    label: string;
    menu: string;
    description: string;
    tables: number;
    questions: string[];
}

interface Tool {
    name: string;
    description: string;
}

interface Props {
    messages: ChatMessage[];
    suggestions: string[];
    tools: Tool[];
    features: Feature[];
    coverage: Array<{ label: string; value: number }>;
    model: string;
    urls: {
        index: string;
        state: string;
        chat: string;
        reset: string;
        mcp: string;
    };
}

const props = defineProps<Props>();
const assistantBusy = ref(false);

const capabilityLabels: Record<string, string> = {
    system_map: 'Peta menu dan fitur',
    data_schema: 'Struktur tabel',
    master_data: 'Pencarian data master',
    query_data: 'Query data read-only',
    business_overview: 'Penjualan, pesanan, stok',
    stock_health: 'Restock dan kedaluwarsa',
    operations_pipeline: 'Order, retur, logistik',
    promo_loyalty: 'Promo, voucher, loyalty',
    customer_insight: 'Demografi dan preferensi',
};

const capabilities = props.tools
    .filter((tool) => capabilityLabels[tool.name])
    .map((tool) => ({ name: tool.name, label: capabilityLabels[tool.name] }));

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);
</script>

<template>
    <Head title="VEE Assistant" />

    <div class="vee-page">
        <section class="vee-hero">
            <div class="vee-hero__mascot">
                <VeeMascot :scale="0.72" animated decorative :priority="true" />
            </div>
            <div class="vee-hero__copy">
                <span class="vee-hero__eyebrow">Asisten data internal</span>
                <h1>Halo, saya VEE.</h1>
                <p>
                    Saya membaca data VeeStore langsung dari database operasional dan backoffice. Tanya kondisi
                    penjualan, stok, pesanan, customer, promo, atau letak menu.
                </p>
                <div class="vee-hero__meta">
                    <span class="vee-chip">
                        <i class="mdi mdi-database-check-outline" aria-hidden="true"></i>
                        {{ features.length }} domain data
                    </span>
                    <span class="vee-chip">
                        <i class="mdi mdi-shield-lock-outline" aria-hidden="true"></i>
                        Akses baca-only
                    </span>
                    <span class="vee-chip vee-chip--model">
                        <i class="mdi mdi-chip" aria-hidden="true"></i>
                        {{ props.model }}
                    </span>
                </div>
            </div>
        </section>

        <div class="vee-workspace">
            <section class="vee-chat-card" aria-label="Percakapan dengan VEE">
                <header class="vee-chat-card__header">
                    <div>
                        <h2>Percakapan</h2>
                        <p>Jawaban memakai data terkini dan menyebutkan sumber datanya.</p>
                    </div>
                    <span class="vee-status" role="status" aria-live="polite">
                        <span class="vee-status__dot" :class="{ 'vee-status__dot--busy': assistantBusy }" aria-hidden="true"></span>
                        {{ assistantBusy ? 'Sedang memproses' : 'Siap menerima pertanyaan' }}
                    </span>
                </header>

                <AssistantChat
                    mode="page"
                    :initial-messages="props.messages"
                    :suggestions="props.suggestions"
                    :urls="props.urls"
                    :model="props.model"
                    @activity-change="assistantBusy = $event"
                />
            </section>

            <aside class="vee-rail" aria-label="Informasi tentang VEE">
                <section class="vee-card">
                    <h2>Cakupan data</h2>
                    <p class="vee-card__lead">Angka ini dihitung dari database saat halaman dibuka.</p>
                    <div class="vee-coverage">
                        <div v-for="item in coverage" :key="item.label" class="vee-coverage__item">
                            <span>{{ item.label }}</span>
                            <strong>{{ formatNumber(item.value) }}</strong>
                        </div>
                    </div>
                </section>

                <section class="vee-card">
                    <h2>Yang bisa VEE lakukan</h2>
                    <ul class="vee-capabilities">
                        <li v-for="capability in capabilities" :key="capability.name">
                            <i class="mdi mdi-check-circle-outline" aria-hidden="true"></i>
                            {{ capability.label }}
                        </li>
                    </ul>
                </section>

                <section class="vee-card vee-card--muted">
                    <h2>Contoh pertanyaan</h2>
                    <ul class="vee-questions">
                        <li v-for="feature in features.slice(0, 4)" :key="feature.key">
                            <span>{{ feature.label }}</span>
                            <p>{{ feature.questions[0] }}</p>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.vee-page {
    min-width: 0;
    color: var(--app-ink);
}

.vee-hero {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    overflow: hidden;
    padding: 1.3rem 1.75rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: var(--app-hero-bg);
    color: var(--app-hero-text);
}

.vee-hero__mascot {
    flex: 0 0 auto;
    margin: 0 0 0 -0.5rem;
}

.vee-hero__copy {
    position: relative;
    z-index: 1;
    max-width: 46rem;
}

.vee-hero__eyebrow {
    display: inline-block;
    margin-bottom: 0.35rem;
    color: var(--app-hero-eyebrow);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.02em;
}

.vee-hero h1 {
    margin: 0 0 0.35rem;
    color: var(--app-hero-text) !important;
    font-size: clamp(1.55rem, 3vw, 2.1rem);
    font-weight: 800;
    line-height: 1.15;
}

.vee-hero p {
    max-width: 38rem;
    margin: 0;
    color: var(--app-hero-muted);
    font-size: 0.88rem;
    line-height: 1.62;
}

.vee-hero__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.85rem;
}

.vee-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.22rem 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 0.45rem;
    color: var(--app-hero-text);
    font-size: 0.68rem;
    font-weight: 700;
}

.vee-chip i {
    font-size: 0.82rem;
}

.vee-chip--model {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vee-workspace {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.9rem;
}

.vee-rail {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    min-width: 0;
}

.vee-card {
    min-width: 0;
    padding: 0.9rem;
    border: 1px solid var(--app-border);
    border-radius: 0.8rem;
    background: var(--app-surface);
}

.vee-card h2 {
    margin: 0 0 0.3rem;
    font-size: 0.82rem;
    font-weight: 800;
}

.vee-card__lead {
    margin: 0 0 0.65rem;
    color: var(--app-muted);
    font-size: 0.7rem;
    line-height: 1.45;
}

.vee-coverage {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.35rem;
}

.vee-coverage__item {
    padding: 0.4rem 0.5rem;
    border: 1px solid var(--app-border);
    border-radius: 0.5rem;
    background: var(--app-surface-2);
}

.vee-coverage__item span {
    display: block;
    overflow: hidden;
    color: var(--app-muted);
    font-size: 0.64rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.vee-coverage__item strong {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.95rem;
    font-weight: 800;
}

.vee-capabilities,
.vee-questions {
    display: grid;
    gap: 0.28rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.vee-capabilities li {
    display: flex;
    align-items: flex-start;
    gap: 0.35rem;
    color: var(--app-muted);
    font-size: 0.76rem;
    line-height: 1.4;
}

.vee-capabilities i {
    margin-top: 0.1rem;
    color: var(--app-accent-ink);
    font-size: 0.82rem;
}

.vee-card--muted {
    background: var(--app-surface-2);
}

.vee-questions li {
    padding: 0.4rem 0;
    border-bottom: 1px solid var(--app-border);
}

.vee-questions li:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.vee-questions span {
    display: block;
    color: var(--app-muted);
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.vee-questions p {
    margin: 0.1rem 0 0;
    font-size: 0.76rem;
    line-height: 1.4;
}

.vee-chat-card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    min-height: 680px;
    height: min(960px, max(680px, 78dvh));
    overflow: hidden;
    border: 1px solid var(--app-border);
    border-radius: 0.9rem;
    background: var(--app-surface);
    box-shadow: none;
}

.vee-chat-card__header {
    display: flex;
    flex: 0 0 auto;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    min-width: 0;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--app-border);
    background: var(--app-surface);
}

.vee-chat-card__header h2 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 800;
}

.vee-chat-card__header > div {
    flex: 1 1 auto;
    min-width: 0;
}

.vee-chat-card__header p {
    margin: 0.15rem 0 0;
    color: var(--app-muted);
    font-size: 0.72rem;
    line-height: 1.45;
}

.vee-status {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 0.3rem;
    padding: 0.22rem 0.45rem;
    border: 1px solid var(--app-border);
    border-radius: 0.45rem;
    color: var(--app-muted);
    font-size: 0.64rem;
    font-weight: 700;
    white-space: nowrap;
}

.vee-status__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--app-success-ink);
}

.vee-status__dot--busy {
    background: var(--app-warning-ink);
}

@media (max-width: 1099.98px) {
    .vee-rail {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vee-chat-card {
        min-height: 620px;
        height: min(860px, max(620px, 78dvh));
    }
}

@media (max-width: 767.98px) {
    .vee-hero {
        align-items: flex-start;
        padding: 1.1rem;
    }

    .vee-hero__mascot {
        width: 92px;
        height: 118px;
        margin: 0 0 0 -0.45rem;
        overflow: hidden;
    }

    .vee-hero__mascot :deep(.vee-mascot) {
        transform: scale(0.52);
        transform-origin: top left;
    }

    .vee-hero p {
        font-size: 0.82rem;
    }

    .vee-rail {
        grid-template-columns: minmax(0, 1fr);
    }

    .vee-chat-card {
        min-height: 520px;
        height: min(820px, max(520px, 78dvh));
        border-radius: 0.75rem;
    }

    .vee-chat-card__header {
        flex-wrap: wrap;
        align-items: center;
        padding: 0.75rem 0.85rem;
    }

    .vee-chat-card__header p {
        max-width: 32ch;
    }

    .vee-status {
        padding: 0.25rem 0.4rem;
        font-size: 0.6rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .vee-hero__mascot :deep(.vee-mascot__art) {
        animation: none;
    }
}
</style>
