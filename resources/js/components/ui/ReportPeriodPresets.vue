<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    route: string;
    fromKey: string;
    toKey: string;
    from: string | null;
    to: string | null;
    query?: Record<string, any>;
}>();

const formatDate = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const today = () => formatDate(new Date());

const startOfWeek = () => {
    const date = new Date();
    const offset = (date.getDay() + 6) % 7;
    date.setDate(date.getDate() - offset);

    return formatDate(date);
};

const startOfMonth = () => {
    const date = new Date();

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-01`;
};

const presets = computed(() => [
    { key: 'today', label: 'Hari ini', from: today(), to: today() },
    { key: 'week', label: 'Minggu ini', from: startOfWeek(), to: today() },
    { key: 'month', label: 'Bulan ini', from: startOfMonth(), to: today() },
]);

const activePreset = computed(
    () => presets.value.find((preset) => preset.from === props.from && preset.to === props.to)?.key ?? '',
);

const buildQuery = (from: string, to: string) => {
    const query: Record<string, string> = {};

    for (const [key, value] of Object.entries(props.query ?? {})) {
        if (value === null || value === undefined || String(value) === '') continue;

        query[key] = String(value);
    }

    query[props.fromKey] = from;
    query[props.toKey] = to;

    return query;
};

const apply = (from: string, to: string) => {
    if (!from || !to) return;

    router.get(props.route, buildQuery(from, to), {
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <div class="period-presets" role="group" aria-label="Pintasan periode">
        <button
            v-for="preset in presets"
            :key="preset.key"
            type="button"
            class="chip"
            :class="{ 'is-active': activePreset === preset.key }"
            :aria-pressed="activePreset === preset.key"
            @click="apply(preset.from, preset.to)"
        >
            {{ preset.label }}
        </button>
    </div>
</template>

<style scoped>
.period-presets {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
}

@media (max-width: 767.98px) {
    .period-presets .chip {
        min-height: 44px;
    }
}
</style>
