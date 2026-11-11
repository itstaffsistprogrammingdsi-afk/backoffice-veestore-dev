<script setup lang="ts">
withDefaults(defineProps<{
    title: string;
    description?: string;
    eyebrow?: string;
}>(), {
    description: undefined,
    eyebrow: undefined,
});
</script>

<template>
    <header class="page-header">
        <div class="page-header-copy">
            <span v-if="eyebrow" class="page-header-eyebrow">{{ eyebrow }}</span>
            <h1 class="page-header-title">{{ title }}</h1>
            <p v-if="description" class="page-header-description">{{ description }}</p>
        </div>

        <div v-if="$slots.actions || $slots.meta" class="page-header-side">
            <div v-if="$slots.meta" class="page-header-meta">
                <slot name="meta" />
            </div>
            <div v-if="$slots.actions" class="page-header-actions">
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
