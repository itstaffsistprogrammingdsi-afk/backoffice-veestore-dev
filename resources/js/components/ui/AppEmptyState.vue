<script setup lang="ts">
withDefaults(defineProps<{
    title: string;
    description?: string;
    icon?: string;
}>(), {
    description: undefined,
    icon: 'mdi-database-off-outline',
});
</script>

<template>
    <div class="empty-state">
        <span class="empty-state-icon">
            <i class="mdi" :class="icon" aria-hidden="true"></i>
        </span>
        <strong class="empty-state-title">{{ title }}</strong>
        <p v-if="description" class="empty-state-text">{{ description }}</p>
        <div v-if="$slots.default" class="empty-state-actions">
            <slot />
        </div>
    </div>
</template>
