<script setup lang="ts">
import { computed } from 'vue';
import { formatNumber } from '@/composables/useFormatters';

interface TrendRow {
    date: string;
    label: string;
    new_members: number;
    visits: number;
}

const props = defineProps<{
    rows: TrendRow[];
    grain: 'day' | 'month';
}>();

const totals = computed(() => ({
    members: props.rows.reduce((total, row) => total + row.new_members, 0),
    visits: props.rows.reduce((total, row) => total + row.visits, 0),
}));

const maxValue = computed(() => Math.max(
    1,
    ...props.rows.flatMap((row) => [row.new_members, row.visits]),
));

const barHeight = (value: number) => `${Math.max(4, Math.round((value / maxValue.value) * 100))}%`;

const labelStep = computed(() => Math.max(1, Math.ceil(props.rows.length / 7)));

const showLabel = (index: number) => index % labelStep.value === 0 || index === props.rows.length - 1;

const hasData = computed(() => props.rows.some((row) => row.new_members > 0 || row.visits > 0));
</script>

<template>
    <div class="customer-growth">
        <div class="customer-growth-legend">
            <span><i class="customer-growth-swatch is-members" aria-hidden="true"></i>Member baru <strong>{{ formatNumber(totals.members) }}</strong></span>
            <span><i class="customer-growth-swatch is-visits" aria-hidden="true"></i>Kunjungan toko <strong>{{ formatNumber(totals.visits) }}</strong></span>
            <span class="customer-growth-scale">Skala 0 – {{ formatNumber(maxValue) }}</span>
        </div>

        <div v-if="hasData" class="customer-growth-bars" role="img" :aria-label="`Grafik batang tren member baru dan kunjungan toko per ${grain === 'month' ? 'bulan' : 'hari'}`">
            <div class="customer-growth-grid">
                <div v-for="(row, index) in rows" :key="row.date" class="customer-growth-column">
                    <div class="customer-growth-track">
                        <div
                            class="customer-growth-bar is-members"
                            :style="{ height: barHeight(row.new_members) }"
                            :title="`${row.label} member baru: ${formatNumber(row.new_members)}`"
                        ></div>
                        <div
                            class="customer-growth-bar is-visits"
                            :style="{ height: barHeight(row.visits) }"
                            :title="`${row.label} kunjungan: ${formatNumber(row.visits)}`"
                        ></div>
                    </div>
                    <span class="customer-growth-label">{{ showLabel(index) ? row.label : '' }}</span>
                </div>
            </div>
        </div>

        <p v-else class="customer-growth-empty">Belum ada member baru atau kunjungan pada periode ini.</p>

        <table class="visually-hidden">
            <caption>Data tren member baru dan kunjungan toko</caption>
            <thead>
                <tr>
                    <th scope="col">Periode</th>
                    <th scope="col">Member baru</th>
                    <th scope="col">Kunjungan</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="`table-${row.date}`">
                    <th scope="row">{{ row.label }}</th>
                    <td>{{ formatNumber(row.new_members) }}</td>
                    <td>{{ formatNumber(row.visits) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
.customer-growth {
    display: grid;
    gap: 0.5rem;
}

.customer-growth-legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem 1rem;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
}

.customer-growth-legend span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.customer-growth-legend strong {
    color: var(--app-ink);
    font-variant-numeric: tabular-nums;
}

.customer-growth-swatch {
    width: 0.7rem;
    height: 0.7rem;
    border-radius: 2px;
    background: var(--app-accent);
}

.customer-growth-swatch.is-visits {
    background: var(--app-warning-ink);
}

.customer-growth-scale {
    margin-left: auto;
    font-variant-numeric: tabular-nums;
}

.customer-growth-bars {
    overflow-x: auto;
    padding-bottom: 0.2rem;
}

.customer-growth-grid {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: minmax(1.4rem, 1fr);
    gap: 0.3rem;
    min-width: 560px;
    align-items: end;
}

.customer-growth-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    min-width: 0;
}

.customer-growth-track {
    display: flex;
    align-items: flex-end;
    gap: 2px;
    width: 100%;
    height: 8rem;
    border-radius: var(--app-radius-xs) var(--app-radius-xs) 0 0;
    background: var(--app-surface-2);
}

.customer-growth-bar {
    flex: 1 1 0;
    min-width: 0;
    border-radius: var(--app-radius-xs) var(--app-radius-xs) 0 0;
    transition: height var(--app-dur-base) var(--app-ease-out);
}

.customer-growth-bar.is-members {
    background: var(--app-accent);
}

.customer-growth-bar.is-visits {
    background: var(--app-warning-ink);
}

.customer-growth-label {
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    line-height: 1;
    white-space: nowrap;
}

.customer-growth-empty {
    margin: 0;
    padding: 1.5rem 0.5rem;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    text-align: center;
}
</style>
