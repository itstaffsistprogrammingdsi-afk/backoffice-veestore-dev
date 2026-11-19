<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppDialog from './AppDialog.vue';
import { confirmState, settleConfirm } from '@/composables/useConfirm';

let removeNavigationListener: (() => void) | undefined;

onMounted(() => {
    removeNavigationListener = router.on('start', () => settleConfirm(false));
});

onBeforeUnmount(() => {
    removeNavigationListener?.();
});
</script>

<template>
    <AppDialog
        :open="confirmState.open"
        :title="confirmState.options.title"
        :description="confirmState.options.description"
        :confirm-label="confirmState.options.confirmLabel"
        :cancel-label="confirmState.options.cancelLabel"
        :variant="confirmState.options.variant"
        :initial-focus="confirmState.options.variant === 'danger' ? 'cancel' : 'confirm'"
        @update:open="settleConfirm(false)"
        @confirm="settleConfirm(true)"
        @cancel="settleConfirm(false)"
    />
</template>
