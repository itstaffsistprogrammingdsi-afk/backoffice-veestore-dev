<script setup lang="ts">
defineOptions({ name: 'AppFilterBar' });
</script>

<template>
    <div class="filter-bar">
        <div class="filter-bar-fields">
            <slot />
        </div>
        <div v-if="$slots.actions" class="filter-bar-actions">
            <slot name="actions" />
        </div>
    </div>
</template>
