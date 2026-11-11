<script setup lang="ts">
import { useToast, type ToastVariant } from '@/composables/useToast';

const { items, dismiss } = useToast();

const icons: Record<ToastVariant, string> = {
    success: 'mdi-check-circle-outline',
    error: 'mdi-alert-circle-outline',
    warning: 'mdi-alert-outline',
    info: 'mdi-information-outline',
};
</script>

<template>
    <Teleport to="body">
        <TransitionGroup
            name="toast"
            tag="div"
            class="toast-stack"
            role="region"
            aria-label="Notifikasi"
            aria-live="polite"
            aria-atomic="false"
        >
            <div
                v-for="item in items"
                :key="item.id"
                class="toast-card"
                :class="`is-${item.variant}`"
                :role="item.variant === 'error' ? 'alert' : 'status'"
            >
                <i class="mdi toast-icon" :class="icons[item.variant]" aria-hidden="true"></i>
                <p>{{ item.message }}</p>
                <button
                    type="button"
                    class="toast-close"
                    aria-label="Tutup notifikasi"
                    @click="dismiss(item.id)"
                >
                    <i class="mdi mdi-close" aria-hidden="true"></i>
                </button>
            </div>
        </TransitionGroup>
    </Teleport>
</template>
