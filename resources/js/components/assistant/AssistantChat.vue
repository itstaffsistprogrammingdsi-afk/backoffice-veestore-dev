<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import VeeMascot from './VeeMascot.vue';
import { renderAssistantMarkdown } from '@/composables/useAssistantMarkdown';

interface ToolTrace {
    name: string;
    summary?: string;
    duration_ms?: number;
}

interface ChatMessage {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    tools?: ToolTrace[];
    created_at?: string;
}

interface Urls {
    chat: string;
    reset: string;
    state?: string;
}

interface AssistantMode {
    key: string;
    label: string;
    description?: string;
    models: string[];
}

const props = withDefaults(
    defineProps<{
        initialMessages?: ChatMessage[];
        suggestions?: string[];
        urls: Urls;
        mode?: 'panel' | 'page';
        model?: string;
        modes?: AssistantMode[];
        defaultMode?: string;
    }>(),
    {
        initialMessages: () => [],
        suggestions: () => [],
        mode: 'panel',
        model: '',
        modes: () => [],
        defaultMode: '',
    },
);

const emit = defineEmits<{
    (event: 'activity-change', busy: boolean): void;
}>();

const messages = ref<ChatMessage[]>([...props.initialMessages]);
const draft = ref('');
const loading = ref(false);
const resetting = ref(false);
const error = ref('');
const scrollArea = ref<HTMLElement | null>(null);
const input = ref<HTMLTextAreaElement | null>(null);
const selectedMode = ref(props.defaultMode || props.modes[0]?.key || '');
const servedModel = ref('');

const hasMessages = computed(() => messages.value.length > 0);
const activeMode = computed(() => props.modes.find((mode) => mode.key === selectedMode.value));
const activeModels = computed(() =>
    activeMode.value?.models.length ? activeMode.value.models : props.model ? [props.model] : [],
);
const modelLabel = computed(() => servedModel.value || activeModels.value.join(' → '));

const uniqueTools = (tools?: ToolTrace[] | null) => {
    const seen = new Set<string>();
    const candidates = Array.isArray(tools) ? tools : [];

    return candidates.filter((tool) => {
        const name = typeof tool?.name === 'string' ? tool.name.trim() : '';

        if (!name || seen.has(name)) return false;

        seen.add(name);
        return true;
    });
};

const toolLabels: Record<string, string> = {
    system_map: 'Peta aplikasi',
    data_schema: 'Skema data',
    master_data: 'Data master',
    query_data: 'Query data',
    business_overview: 'Ringkasan bisnis',
    stock_health: 'Kesehatan stok',
    operations_pipeline: 'Alur operasional',
    promo_loyalty: 'Promo dan loyalty',
    customer_insight: 'Insight customer',
};

const scrollToBottom = async () => {
    await nextTick();
    const element = scrollArea.value;

    if (element) {
        element.scrollTop = element.scrollHeight;
    }
};

const selectMode = (mode: string) => {
    if (mode === selectedMode.value) return;

    selectedMode.value = mode;
    servedModel.value = '';
};

const csrfToken = () =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

const formatTime = (value?: string) => {
    if (!value) return '';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '';

    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const send = async (value?: string) => {
    const text = (value ?? draft.value).trim();

    if (!text || loading.value || resetting.value) {
        return;
    }

    const previous = [...messages.value];
    const optimistic: ChatMessage = {
        id: `local-${Date.now()}`,
        role: 'user',
        content: text,
        created_at: new Date().toISOString(),
    };

    messages.value = [...messages.value, optimistic];
    draft.value = '';
    error.value = '';
    loading.value = true;
    emit('activity-change', true);
    await scrollToBottom();

    try {
        const response = await fetch(props.urls.chat, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ message: text, mode: selectedMode.value || undefined }),
        });

        const payload = await response.json().catch(() => null);

        if (!response.ok) {
            throw new Error(payload?.message ?? 'VEE belum dapat menjawab.');
        }

        if (typeof payload?.mode === 'string' && payload.mode !== '' && payload.mode !== selectedMode.value) {
            selectedMode.value = payload.mode;
        }

        servedModel.value = typeof payload?.model === 'string' ? payload.model : '';

        messages.value = payload.messages ?? [
            ...messages.value,
            {
                id: `answer-${Date.now()}`,
                role: 'assistant',
                content: payload.answer ?? '',
                tools: payload.tool_calls ?? [],
            },
        ];
        await scrollToBottom();
    } catch (exception) {
        messages.value = previous;
        draft.value = text;
        error.value = exception instanceof Error ? exception.message : 'VEE belum dapat menjawab.';
    } finally {
        loading.value = false;
        emit('activity-change', false);
        await nextTick();
        input.value?.focus();
    }
};

const reset = async () => {
    if (resetting.value || loading.value) return;

    resetting.value = true;
    emit('activity-change', true);
    error.value = '';

    try {
        const response = await fetch(props.urls.reset, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Percakapan belum dapat direset.');
        }

        messages.value = [];
        draft.value = '';
        servedModel.value = '';
        await nextTick();
        input.value?.focus();
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Percakapan belum dapat direset.';
    } finally {
        resetting.value = false;
        emit('activity-change', false);
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key !== 'Enter' || event.shiftKey) return;

    event.preventDefault();
    void send();
};

const onInput = () => {
    if (!input.value) return;

    input.value.style.height = 'auto';
    input.value.style.height = Math.min(input.value.scrollHeight, 132) + 'px';
};

watch(
    () => props.initialMessages,
    (value) => {
        messages.value = [...value];
        void scrollToBottom();
    },
);

defineExpose({ focusInput: () => input.value?.focus() });
</script>

<template>
    <div class="assistant-chat" :class="`assistant-chat--${props.mode}`">
        <div ref="scrollArea" class="assistant-chat__scroll" role="log" aria-live="polite" aria-relevant="additions text">
            <div v-if="!hasMessages" class="assistant-chat__empty">
                <VeeMascot :scale="props.mode === 'panel' ? 0.58 : 0.72" variant="bust" animated decorative />
                <div>
                    <h3>Halo, saya VEE.</h3>
                    <p>
                        Tanya data penjualan, stok, pesanan, customer, promo, atau letak menu. Saya membaca data
                        langsung dari sistem.
                    </p>
                </div>
                <div v-if="props.suggestions.length" class="assistant-chat__suggestions">
                    <button
                        v-for="suggestion in props.suggestions"
                        :key="suggestion"
                        type="button"
                        class="assistant-suggestion"
                        :disabled="loading || resetting"
                        @click="send(suggestion)"
                    >
                        {{ suggestion }}
                    </button>
                </div>
            </div>

            <div v-else class="assistant-chat__thread">
                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="assistant-message"
                    :class="`assistant-message--${message.role}`"
                >
                    <div v-if="message.role === 'assistant'" class="assistant-message__avatar">
                        <VeeMascot :scale="0.17" variant="bust" decorative />
                    </div>
                    <div class="assistant-message__body">
                        <div
                            v-if="message.role === 'assistant'"
                            class="assistant-message__content"
                            v-html="renderAssistantMarkdown(message.content)"
                        ></div>
                        <p v-else class="assistant-message__content">{{ message.content }}</p>

                        <div v-if="uniqueTools(message.tools).length" class="assistant-tools" aria-label="Jenis sumber yang digunakan">
                            <span
                                v-for="tool in uniqueTools(message.tools)"
                                :key="`${message.id}-${tool.name}`"
                                class="assistant-tool"
                                :title="tool.summary"
                            >
                                <i class="mdi mdi-database-check-outline" aria-hidden="true"></i>
                                {{ toolLabels[tool.name] ?? tool.name }}
                            </span>
                        </div>

                        <span class="assistant-message__time">{{ formatTime(message.created_at) }}</span>
                    </div>
                </div>

                <div v-if="loading" class="assistant-message assistant-message--assistant">
                    <div class="assistant-message__avatar">
                        <VeeMascot :scale="0.17" variant="bust" decorative />
                    </div>
                    <div class="assistant-message__body">
                        <div class="assistant-thinking" role="status">
                            <span class="assistant-thinking__dots" aria-hidden="true">
                                <span></span><span></span><span></span>
                            </span>
                            VEE sedang membaca data
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="assistant-chat__footer">
            <div v-if="error" class="assistant-error" role="alert">
                <i class="mdi mdi-alert-circle-outline" aria-hidden="true"></i>
                <span>{{ error }}</span>
            </div>

            <div v-if="hasMessages && props.suggestions.length" class="assistant-chat__quick">
                <button
                    v-for="suggestion in props.suggestions.slice(0, 3)"
                    :key="suggestion"
                    type="button"
                    class="assistant-suggestion assistant-suggestion--compact"
                    :disabled="loading || resetting"
                    @click="send(suggestion)"
                >
                    {{ suggestion }}
                </button>
            </div>

            <form class="assistant-composer" @submit.prevent="send()">
                <label class="visually-hidden" for="vee-chat-input">Pertanyaan untuk VEE</label>
                <textarea
                    id="vee-chat-input"
                    ref="input"
                    v-model="draft"
                    class="assistant-composer__input"
                    rows="1"
                    maxlength="2000"
                    placeholder="Tanya sesuatu tentang data VeeStore..."
                    :disabled="loading || resetting"
                    @keydown="onKeydown"
                    @input="onInput"
                ></textarea>
                <button
                    type="submit"
                    class="assistant-composer__send"
                    :disabled="loading || resetting || draft.trim() === ''"
                    aria-label="Kirim pertanyaan"
                >
                    <i class="mdi" :class="loading ? 'mdi-loading mdi-spin' : 'mdi-send'" aria-hidden="true"></i>
                </button>
            </form>

            <div class="assistant-chat__meta">
                <div class="assistant-chat__controls">
                    <div v-if="props.modes.length > 1" class="assistant-modes" role="group" aria-label="Mode jawaban">
                        <button
                            v-for="mode in props.modes"
                            :key="mode.key"
                            type="button"
                            class="assistant-modes__button"
                            :class="{ 'is-active': selectedMode === mode.key }"
                            :aria-pressed="selectedMode === mode.key"
                            :title="mode.description || mode.label"
                            :disabled="loading || resetting"
                            @click="selectMode(mode.key)"
                        >
                            {{ mode.label }}
                        </button>
                    </div>
                    <span v-if="modelLabel" class="assistant-chat__model" :title="modelLabel">
                        <i class="mdi mdi-chip" aria-hidden="true"></i>
                        {{ modelLabel }}
                    </span>
                </div>
                <button
                    type="button"
                    class="assistant-reset"
                    :disabled="loading || resetting || !hasMessages"
                    @click="reset"
                >
                    <i class="mdi mdi-refresh" aria-hidden="true"></i>
                    {{ resetting ? 'Mereset...' : 'Percakapan baru' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.assistant-chat {
    display: flex;
    flex-direction: column;
    min-height: 0;
    height: 100%;
    color: var(--app-ink);
}

.assistant-chat__scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
}

.assistant-chat--panel .assistant-chat__scroll {
    padding: 1rem;
}

.assistant-chat--page .assistant-chat__scroll {
    padding: 1.5rem;
}

.assistant-chat__empty {
    display: grid;
    justify-items: center;
    gap: 0.65rem;
    padding: 1.25rem 0.5rem 1rem;
    text-align: center;
}

.assistant-chat__empty > div {
    max-width: 34rem;
}

.assistant-chat__empty h3 {
    margin: 0 0 0.35rem;
    font-size: 1.1rem;
    font-weight: 800;
}

.assistant-chat__empty p {
    margin: 0;
    color: var(--app-muted);
    font-size: 0.86rem;
    line-height: 1.6;
}

.assistant-chat__suggestions {
    display: grid;
    gap: 0.45rem;
    width: min(100%, 34rem);
    margin-top: 0.35rem;
    text-align: left;
}

.assistant-suggestion {
    min-height: 44px;
    padding: 0.55rem 0.75rem;
    border: 1px solid var(--app-border);
    border-radius: 0.6rem;
    background: var(--app-surface);
    color: var(--app-ink);
    font: inherit;
    font-size: 0.82rem;
    line-height: 1.4;
    text-align: left;
    cursor: pointer;
    transition: border-color 140ms ease, background 140ms ease;
}

.assistant-suggestion:hover,
.assistant-suggestion:focus-visible {
    border-color: var(--app-accent);
    background: var(--app-surface-2);
}

.assistant-suggestion:focus-visible,
.assistant-composer__send:focus-visible,
.assistant-reset:focus-visible {
    outline: 2px solid color-mix(in srgb, var(--app-accent) 35%, transparent);
    outline-offset: 2px;
}

.assistant-suggestion:disabled {
    opacity: 0.55;
    cursor: wait;
}

.assistant-suggestion--compact {
    min-height: 44px;
    padding: 0.35rem 0.55rem;
    font-size: 0.72rem;
    border-radius: 0.5rem;
    white-space: nowrap;
}

.assistant-chat__thread {
    display: grid;
    gap: 0.85rem;
}

.assistant-message {
    display: flex;
    gap: 0.55rem;
    max-width: 100%;
}

.assistant-message--user {
    justify-content: flex-end;
}

.assistant-message__avatar {
    flex: 0 0 auto;
    width: 34px;
    height: 34px;
    overflow: hidden;
    border: 1px solid var(--app-border);
    border-radius: 0.55rem;
    background: var(--app-accent-soft);
}

.assistant-message__avatar .vee-mascot {
    margin: 0;
}

.assistant-message__body {
    min-width: 0;
    max-width: min(96%, 76rem);
}

.assistant-message--user .assistant-message__body {
    order: -1;
}

.assistant-message__content {
    margin: 0;
    padding: 0.65rem 0.8rem;
    border: 1px solid var(--app-border);
    border-radius: 3px 0.8rem 0.8rem 0.8rem;
    background: var(--app-surface);
    font-size: 0.86rem;
    line-height: 1.62;
    overflow-wrap: anywhere;
}

.assistant-message--user .assistant-message__content {
    border-color: var(--app-accent);
    border-radius: 0.8rem 3px 0.8rem 0.8rem;
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    white-space: pre-wrap;
}

.assistant-message__content :deep(p) {
    margin: 0 0 0.5rem;
}

.assistant-message__content :deep(p:last-child) {
    margin-bottom: 0;
}

.assistant-message__content :deep(ul),
.assistant-message__content :deep(ol) {
    margin: 0 0 0.5rem;
    padding-left: 1.15rem;
}

.assistant-message__content :deep(li) {
    margin-bottom: 0.2rem;
}

.assistant-message__content :deep(h3),
.assistant-message__content :deep(h4),
.assistant-message__content :deep(h5) {
    margin: 0.6rem 0 0.35rem;
    font-size: 0.92rem;
    font-weight: 800;
}

.assistant-message__content :deep(code) {
    padding: 0.1rem 0.3rem;
    border-radius: 0.3rem;
    background: var(--app-surface-3);
    color: var(--app-accent-ink);
    font-size: 0.8em;
}

.assistant-message__content :deep(pre) {
    margin: 0 0 0.5rem;
    padding: 0.65rem;
    overflow-x: auto;
    border-radius: 0.5rem;
    background: var(--app-hero-bg);
    color: var(--app-hero-text);
}

.assistant-message__content :deep(pre code) {
    padding: 0;
    background: transparent;
    color: inherit;
}

.assistant-message__time {
    display: block;
    margin-top: 0.25rem;
    color: var(--app-muted);
    font-size: 0.66rem;
}

.assistant-message--user .assistant-message__time {
    text-align: right;
}

.assistant-tools {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-top: 0.35rem;
}

.assistant-tool {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.15rem 0.42rem;
    border: 1px solid var(--app-border);
    border-radius: 0.4rem;
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 0.64rem;
    font-weight: 700;
}

.assistant-tool i {
    font-size: 0.75rem;
}

.assistant-thinking {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.65rem 0.8rem;
    border: 1px solid var(--app-border);
    border-radius: 3px 0.8rem 0.8rem 0.8rem;
    background: var(--app-surface);
    color: var(--app-muted);
    font-size: 0.82rem;
}

.assistant-thinking__dots {
    display: inline-flex;
    gap: 3px;
}

.assistant-thinking__dots span {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--app-accent);
    animation: vee-thinking 1.1s ease-in-out infinite;
}

.assistant-thinking__dots span:nth-child(2) {
    animation-delay: 120ms;
}

.assistant-thinking__dots span:nth-child(3) {
    animation-delay: 240ms;
}

@keyframes vee-thinking {
    0%, 60%, 100% { opacity: 0.25; transform: translateY(0); }
    30% { opacity: 1; transform: translateY(-2px); }
}

.assistant-chat__footer {
    flex: 0 0 auto;
    padding: 0.75rem 0.85rem calc(0.75rem + env(safe-area-inset-bottom));
    border-top: 1px solid var(--app-border);
    background: var(--app-surface);
}

.assistant-chat__quick {
    display: flex;
    gap: 0.35rem;
    margin-bottom: 0.55rem;
    overflow-x: auto;
    scrollbar-width: none;
    padding-bottom: 2px;
}

.assistant-chat__quick::-webkit-scrollbar {
    display: none;
}

.assistant-error {
    display: flex;
    align-items: flex-start;
    gap: 0.35rem;
    margin-bottom: 0.55rem;
    padding: 0.5rem 0.65rem;
    border: 1px solid var(--app-danger-bg);
    border-radius: 0.5rem;
    background: var(--app-danger-bg);
    color: var(--app-danger-ink);
    font-size: 0.76rem;
    line-height: 1.45;
}

.assistant-composer {
    display: flex;
    align-items: flex-end;
    gap: 0.45rem;
    padding: 0.35rem 0.35rem 0.35rem 0.65rem;
    border: 1px solid var(--app-border);
    border-radius: 0.75rem;
    background: var(--app-surface-2);
}

.assistant-composer:focus-within {
    border-color: var(--app-accent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--app-accent) 18%, transparent);
}

.assistant-composer__input {
    flex: 1;
    min-width: 0;
    max-height: 132px;
    padding: 0.45rem 0;
    border: 0;
    background: transparent;
    color: var(--app-ink);
    font: inherit;
    font-size: 0.86rem;
    line-height: 1.5;
    resize: none;
    outline: none;
}

.assistant-composer__input::placeholder {
    color: var(--app-muted);
}

.assistant-composer__send {
    display: grid;
    flex: 0 0 auto;
    place-items: center;
    width: 44px;
    height: 44px;
    border: 0;
    border-radius: 0.6rem;
    background: var(--app-accent);
    color: var(--app-on-accent);
    font-size: 1.15rem;
    cursor: pointer;
}

.assistant-composer__send:hover:not(:disabled) {
    background: var(--app-accent-strong);
}

.assistant-composer__send:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.assistant-chat__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 0.45rem;
}

.assistant-chat__controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
    min-width: 0;
}

.assistant-modes {
    display: inline-flex;
    gap: 2px;
    padding: 2px;
    border: 1px solid var(--app-border);
    border-radius: 0.5rem;
    background: var(--app-surface-2);
}

.assistant-modes__button {
    min-height: 44px;
    padding: 0.3rem 0.65rem;
    border: 0;
    border-radius: 0.4rem;
    background: transparent;
    color: var(--app-muted);
    font: inherit;
    font-size: 0.7rem;
    font-weight: 800;
    cursor: pointer;
    transition: background 140ms ease, color 140ms ease;
}

.assistant-modes__button:hover:not(:disabled) {
    color: var(--app-ink);
}

.assistant-modes__button.is-active {
    background: var(--app-surface);
    color: var(--app-accent-ink);
    box-shadow: 0 1px 2px rgba(25, 44, 62, 0.12);
}

.assistant-modes__button:focus-visible {
    outline: 2px solid color-mix(in srgb, var(--app-accent) 35%, transparent);
    outline-offset: 2px;
}

.assistant-modes__button:disabled {
    cursor: wait;
    opacity: 0.55;
}

.assistant-chat__model {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    overflow: hidden;
    color: var(--app-muted);
    font-size: 0.64rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.assistant-reset {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    min-height: 44px;
    padding: 0.35rem 0.55rem;
    border: 0;
    border-radius: 0.4rem;
    background: transparent;
    color: var(--app-muted);
    font: inherit;
    font-size: 0.7rem;
    cursor: pointer;
}

.assistant-reset:hover:not(:disabled),
.assistant-reset:focus-visible {
    background: var(--app-surface-3);
    color: var(--app-ink);
}

.assistant-reset:disabled {
    cursor: default;
    opacity: 0.45;
}

@media (max-width: 575.98px) {
    .assistant-message__body {
        max-width: calc(100% - 40px);
    }

    .assistant-chat--page .assistant-chat__scroll {
        padding: 0.85rem;
    }

    .assistant-composer__input {
        font-size: 16px;
    }

    .assistant-chat__footer {
        padding-inline: 0.65rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .assistant-thinking__dots span {
        animation: none;
        opacity: 0.65;
    }
}
</style>
