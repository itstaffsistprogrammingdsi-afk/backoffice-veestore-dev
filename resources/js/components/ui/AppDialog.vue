<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';

interface Props {
    open: boolean;
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'primary' | 'danger';
    loading?: boolean;
    closeOnBackdrop?: boolean;
    initialFocus?: 'cancel' | 'confirm';
    initialFocusSelector?: string;
}

const props = withDefaults(defineProps<Props>(), {
    description: undefined,
    confirmLabel: 'Lanjutkan',
    cancelLabel: 'Batal',
    variant: 'primary',
    loading: false,
    closeOnBackdrop: true,
    initialFocus: 'cancel',
    initialFocusSelector: undefined,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
    cancel: [];
}>();

const dialogRef = ref<HTMLElement | null>(null);
const cancelButtonRef = ref<HTMLButtonElement | null>(null);
const confirmButtonRef = ref<HTMLButtonElement | null>(null);
const titleId = useId();
const descriptionId = useId();

let previouslyFocused: HTMLElement | null = null;
let previousOverflow = '';
let scrollLocked = false;

const lockScroll = () => {
    if (scrollLocked) return;

    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    scrollLocked = true;
};

const unlockScroll = () => {
    if (!scrollLocked) return;

    document.body.style.overflow = previousOverflow;
    scrollLocked = false;
};

const getFocusable = () => Array.from(
    dialogRef.value?.querySelectorAll<HTMLElement>(
        'button:not(:disabled), [href], input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])',
    ) ?? [],
).filter((element) => element.getClientRects().length > 0);

const requestClose = () => {
    if (props.loading) return;

    emit('update:open', false);
    emit('cancel');
};

const onBackdrop = () => {
    if (props.closeOnBackdrop) {
        requestClose();
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        event.preventDefault();
        requestClose();
        return;
    }

    if (event.key !== 'Tab') return;

    const focusable = getFocusable();

    if (focusable.length === 0) {
        event.preventDefault();
        dialogRef.value?.focus();
        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
        return;
    }

    if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

watch(
    () => props.open,
    async (open) => {
        if (open) {
            previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            lockScroll();
            await nextTick();
            const customTarget = props.initialFocusSelector
                ? dialogRef.value?.querySelector<HTMLElement>(props.initialFocusSelector)
                : null;
            const target = customTarget
                ?? (props.initialFocus === 'confirm' ? confirmButtonRef.value : cancelButtonRef.value);
            target?.focus();
            return;
        }

        unlockScroll();
        previouslyFocused?.focus();
        previouslyFocused = null;
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    unlockScroll();
    previouslyFocused?.focus();
});
</script>

<template>
    <Teleport to="body">
        <Transition name="app-dialog">
            <div
                v-if="open"
                class="app-dialog-backdrop"
                @click.self="onBackdrop"
                @keydown="onKeydown"
            >
                <div
                    ref="dialogRef"
                    class="app-dialog"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="titleId"
                    :aria-describedby="description ? descriptionId : undefined"
                    tabindex="-1"
                >
                    <header class="app-dialog-header">
                        <div>
                            <h2 :id="titleId" class="app-dialog-title">{{ title }}</h2>
                            <p v-if="description" :id="descriptionId" class="app-dialog-description">{{ description }}</p>
                        </div>
                        <button
                            type="button"
                            class="btn btn-sm btn-ghost"
                            aria-label="Tutup dialog"
                            :disabled="loading"
                            @click="requestClose"
                        >
                            <i class="mdi mdi-close" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div v-if="$slots.default" class="app-dialog-body">
                        <slot />
                    </div>

                    <footer class="app-dialog-footer">
                        <button
                            ref="cancelButtonRef"
                            type="button"
                            class="btn btn-outline-secondary"
                            :disabled="loading"
                            @click="requestClose"
                        >
                            {{ cancelLabel }}
                        </button>
                        <button
                            ref="confirmButtonRef"
                            type="button"
                            class="btn"
                            :class="variant === 'danger' ? 'btn-danger' : 'btn-primary'"
                            :disabled="loading"
                            :aria-busy="loading"
                            @click="emit('confirm')"
                        >
                            <i v-if="loading" class="mdi mdi-loading mdi-spin" aria-hidden="true"></i>
                            {{ confirmLabel }}
                        </button>
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
