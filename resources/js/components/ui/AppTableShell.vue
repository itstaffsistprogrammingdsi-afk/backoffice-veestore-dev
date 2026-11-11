<script setup lang="ts">
withDefaults(defineProps<{
    sticky?: boolean;
    label?: string;
}>(), {
    sticky: false,
    label: undefined,
});
</script>

<template>
    <section class="table-shell" :aria-label="label">
        <div v-if="$slots.toolbar" class="table-shell-toolbar">
            <slot name="toolbar" />
        </div>

        <div class="table-responsive" :class="{ 'is-sticky': sticky }">
            <slot />
        </div>

        <div v-if="$slots.footer" class="table-shell-footer">
            <slot name="footer" />
        </div>
    </section>
</template>
