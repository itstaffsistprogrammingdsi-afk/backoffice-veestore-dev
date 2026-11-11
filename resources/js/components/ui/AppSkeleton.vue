<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'text' | 'block' | 'circle';
    width?: string;
    height?: string;
    lines?: number;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'text',
    width: undefined,
    height: undefined,
    lines: 1,
});

const style = computed(() => ({
    width: props.width,
    height: props.height,
}));
</script>

<template>
    <div v-if="variant === 'text' && lines > 1" class="skeleton-lines" aria-hidden="true">
        <span
            v-for="line in lines"
            :key="line"
            class="skeleton skeleton-text"
            :style="{ width: line === lines ? '65%' : '100%' }"
        />
    </div>
    <span
        v-else
        class="skeleton"
        :class="`skeleton-${variant}`"
        :style="style"
        aria-hidden="true"
    />
</template>
