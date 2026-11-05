<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AssistantChat from './AssistantChat.vue';
import VeeMascot from './VeeMascot.vue';

interface ChatMessage {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    tools?: Array<{ name: string; summary?: string }>;
    created_at?: string;
}

interface AssistantMode {
    key: string;
    label: string;
    description?: string;
    models: string[];
}

interface AssistantState {
    messages: ChatMessage[];
    suggestions: string[];
    model: string;
    modes: AssistantMode[];
    default_mode: string;
    urls: {
        chat: string;
        reset: string;
        state: string;
    };
}

const page = usePage();
const open = ref(false);
const loaded = ref(false);
const loading = ref(false);
const loadError = ref('');
const state = ref<AssistantState | null>(null);
const launcher = ref<HTMLButtonElement | null>(null);
const chat = ref<InstanceType<typeof AssistantChat> | null>(null);

const hidden = computed(() => page.url.split('?')[0].startsWith('/ai-assistant'));

const defaults: AssistantState = {
    messages: [],
    suggestions: [
        'Ringkas performa penjualan 30 hari terakhir.',
        'Stok apa yang perlu segera diisi ulang?',
        'Pesanan ecommerce mana yang terlambat diproses?',
    ],
    model: 'cx/gpt-5.6-luna',
    modes: [],
    default_mode: 'auto',
    urls: {
        chat: '/ai-assistant/chat',
        reset: '/ai-assistant/reset',
        state: '/ai-assistant/state',
    },
};

const loadState = async () => {
    if (loading.value) return;

    loading.value = true;
    loadError.value = '';

    try {
        const response = await fetch(defaults.urls.state, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Gagal memuat percakapan.');
        }

        state.value = (await response.json()) as AssistantState;
        loaded.value = true;
    } catch (exception) {
        loadError.value = exception instanceof Error ? exception.message : 'Gagal memuat percakapan.';
    } finally {
        loading.value = false;
    }
};

const toggle = async () => {
    if (open.value) {
        close();
        return;
    }

    open.value = true;

    if (!loaded.value) {
        await loadState();
    }

    await nextTick();
    chat.value?.focusInput();
};

const close = () => {
    open.value = false;
    nextTick(() => launcher.value?.focus());
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && open.value) {
        close();
    }
};
</script>

<template>
    <div v-if="!hidden" class="vee-widget" @keydown="onKeydown">
        <Transition name="vee-panel">
            <section
                v-if="open"
                id="vee-assistant-panel"
                class="vee-widget__panel"
                aria-label="VEE Assistant"
                role="dialog"
                aria-modal="false"
            >
                <header class="vee-widget__header">
                    <span class="vee-widget__avatar">
                        <VeeMascot :scale="0.19" variant="bust" decorative />
                    </span>
                    <div class="vee-widget__identity">
                        <strong>VEE</strong>
                        <span>Asisten data VeeStore</span>
                    </div>
                    <button type="button" class="vee-widget__close" aria-label="Tutup VEE Assistant" @click="close">
                        <i class="mdi mdi-close" aria-hidden="true"></i>
                    </button>
                </header>

                <div class="vee-widget__body">
                    <div v-if="loading" class="vee-widget__state" role="status">
                        <i class="mdi mdi-loading mdi-spin" aria-hidden="true"></i>
                        Menyiapkan percakapan...
                    </div>

                    <div v-else-if="loadError" class="vee-widget__state vee-widget__state--error" role="alert">
                        <i class="mdi mdi-alert-circle-outline" aria-hidden="true"></i>
                        <span>{{ loadError }}</span>
                        <button type="button" class="vee-widget__retry" @click="loadState">Coba lagi</button>
                    </div>

                    <AssistantChat
                        v-else-if="state"
                        ref="chat"
                        mode="panel"
                        :initial-messages="state.messages"
                        :suggestions="state.suggestions"
                        :urls="state.urls"
                        :model="state.model"
                        :modes="state.modes"
                        :default-mode="state.default_mode"
                    />
                </div>
            </section>
        </Transition>

        <button
            ref="launcher"
            type="button"
            class="vee-launcher"
            :aria-expanded="open"
            aria-controls="vee-assistant-panel"
            :aria-label="open ? 'Tutup VEE Assistant' : 'Buka VEE Assistant'"
            @click="toggle"
        >
            <VeeMascot :scale="0.34" variant="bust" decorative />
            <span class="vee-launcher__status" aria-hidden="true"></span>
        </button>
    </div>
</template>

<style scoped>
.vee-widget {
    position: fixed;
    z-index: 1000;
    inset: auto 0 0 auto;
    pointer-events: none;
}

.vee-widget__panel {
    position: fixed;
    right: 24px;
    bottom: 104px;
    display: flex;
    flex-direction: column;
    width: min(410px, calc(100vw - 32px));
    height: min(640px, calc(100dvh - 132px));
    overflow: hidden;
    border: 1px solid var(--app-border);
    border-radius: 0.75rem;
    background: var(--app-surface);
    box-shadow: 0 0.9rem 2rem rgba(25, 44, 62, 0.18);
    pointer-events: auto;
}

.vee-widget__header {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 0.6rem;
    padding: 0.7rem 0.85rem;
    border-bottom: 1px solid var(--app-hero-bg);
    background: var(--app-hero-bg);
    color: var(--app-hero-text);
}

.vee-widget__avatar {
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.24);
    border-radius: 0.55rem;
    background: var(--app-accent-soft);
}

.vee-widget__avatar .vee-mascot {
    margin: 0;
}

.vee-widget__identity {
    display: grid;
    flex: 1;
    min-width: 0;
}

.vee-widget__identity strong {
    font-size: 0.9rem;
    font-weight: 800;
    letter-spacing: 0.01em;
}

.vee-widget__identity span {
    color: var(--app-hero-muted);
    font-size: 0.68rem;
}

.vee-widget__close {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    font-size: 1.15rem;
    cursor: pointer;
}

.vee-widget__close:hover,
.vee-widget__close:focus-visible {
    background: rgba(255, 255, 255, 0.2);
}

.vee-widget__body {
    flex: 1;
    min-height: 0;
}

.vee-widget__state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 100%;
    padding: 2rem 1.25rem;
    color: var(--app-muted);
    font-size: 0.82rem;
    text-align: center;
}

.vee-widget__state i {
    font-size: 1.5rem;
    color: var(--app-accent-ink);
}

.vee-widget__state--error {
    color: var(--app-danger-ink);
}

.vee-widget__state--error i {
    color: var(--app-danger-ink);
}

.vee-widget__retry {
    padding: 0.35rem 0.65rem;
    border: 1px solid var(--app-border);
    border-radius: 0.45rem;
    background: var(--app-surface);
    color: var(--app-ink);
    font: inherit;
    font-size: 0.75rem;
    cursor: pointer;
}

.vee-widget .vee-launcher {
    pointer-events: auto;
}

.vee-panel-enter-active,
.vee-panel-leave-active {
    transition: opacity 160ms ease, transform 180ms cubic-bezier(0.2, 0.8, 0.2, 1);
}

.vee-panel-enter-from,
.vee-panel-leave-to {
    opacity: 0;
    transform: translateY(8px) scale(0.985);
}

@media (max-width: 480px) {
    .vee-widget__panel {
        right: 10px;
        bottom: 86px;
        width: calc(100vw - 20px);
        height: min(680px, calc(100dvh - 104px));
    }
}

@media (prefers-reduced-motion: reduce) {
    .vee-panel-enter-active,
    .vee-panel-leave-active {
        transition: none;
    }
}
</style>
