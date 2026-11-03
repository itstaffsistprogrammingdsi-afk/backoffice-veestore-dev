<script setup lang="ts">
import { computed } from 'vue';
import { formatNumber, percentage } from '@/composables/useFormatters';

interface Row {
    label: string;
    total: number;
}

const props = withDefaults(defineProps<{
    title: string;
    icon?: string;
    subtitle?: string;
    rows: Row[];
    total?: number;
    emptyLabel?: string;
}>(), {
    icon: 'mdi-chart-donut',
    subtitle: undefined,
    total: undefined,
    emptyLabel: 'Belum ada data.',
});

const total = computed(() => props.total ?? props.rows.reduce((sum, row) => sum + Number(row.total ?? 0), 0));
const max = computed(() => Math.max(1, ...props.rows.map((row) => Number(row.total ?? 0))));

const barWidth = (value: number) => {
    const parsed = Number(value ?? 0);
    if (parsed <= 0) return '0%';

    return `${Math.max(3, Math.round((parsed / max.value) * 100))}%`;
};
</script>

<template>
    <article class="dist-card">
        <header class="dist-card-header">
            <span class="dist-card-icon"><i class="mdi" :class="props.icon"></i></span>
            <div>
                <h2>{{ props.title }}</h2>
                <p v-if="props.subtitle">{{ props.subtitle }}</p>
            </div>
            <span class="dist-card-total">{{ formatNumber(total) }}</span>
        </header>

        <ul v-if="props.rows.length > 0" class="dist-list">
            <li v-for="row in props.rows" :key="row.label" class="dist-item">
                <div class="dist-row">
                    <span class="dist-label">{{ row.label }}</span>
                    <span class="dist-value">
                        <strong>{{ formatNumber(row.total) }}</strong>
                        <small>{{ percentage(row.total, total) }}%</small>
                    </span>
                </div>
                <div class="dist-bar" aria-hidden="true">
                    <span :style="{ width: barWidth(row.total) }"></span>
                </div>
            </li>
        </ul>
        <p v-else class="dist-empty">{{ props.emptyLabel }}</p>
    </article>
</template>

<style scoped>
.dist-card {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 1rem 1.05rem;
    border: 1px solid var(--app-border);
    border-radius: 0.9rem;
    background: var(--app-surface);
    box-shadow: var(--app-shadow);
}
.dist-card-header {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    margin-bottom: 0.85rem;
}
.dist-card-icon {
    display: grid;
    flex: 0 0 auto;
    width: 2rem;
    height: 2rem;
    place-items: center;
    border-radius: 0.55rem;
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: 1.05rem;
}
.dist-card-header h2 {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.25;
}
.dist-card-header p {
    margin: 0.15rem 0 0;
    color: var(--app-muted);
    font-size: 0.74rem;
}
.dist-card-total {
    margin-left: auto;
    color: var(--app-muted);
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
}
.dist-list {
    display: grid;
    gap: 0.55rem;
    margin: 0;
    padding: 0;
    list-style: none;
}
.dist-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.22rem;
}
.dist-label {
    overflow: hidden;
    color: var(--app-ink);
    font-size: 0.8rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.dist-value {
    display: inline-flex;
    align-items: baseline;
    gap: 0.35rem;
    white-space: nowrap;
}
.dist-value strong {
    font-size: 0.82rem;
}
.dist-value small {
    color: var(--app-muted);
    font-size: 0.7rem;
}
.dist-bar {
    overflow: hidden;
    height: 0.32rem;
    border-radius: 999px;
    background: var(--app-surface-3);
}
.dist-bar span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--app-accent);
    transition: width 180ms ease;
}
.dist-empty {
    margin: 0;
    padding: 1rem 0;
    color: var(--app-muted);
    font-size: 0.8rem;
    text-align: center;
}
</style>
