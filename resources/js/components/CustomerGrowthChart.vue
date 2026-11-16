<script setup lang="ts">
import { computed } from 'vue';
import { formatNumber } from '@/composables/useFormatters';

interface TrendRow {
    date: string;
    label: string;
    new_members: number;
}

const props = defineProps<{
    rows: TrendRow[];
    grain: 'day' | 'month';
}>();

const totalMembers = computed(() => props.rows.reduce((total, row) => total + row.new_members, 0));

const yAxis = computed(() => {
    const max = Math.max(1, ...props.rows.map((row) => row.new_members));
    const rawStep = Math.max(1, max / 3);
    const exponent = Math.floor(Math.log10(rawStep));
    const base = 10 ** exponent;
    const fraction = rawStep / base;
    const niceFraction = fraction <= 1 ? 1 : fraction <= 2 ? 2 : fraction <= 5 ? 5 : 10;
    const step = Math.max(1, niceFraction * base);

    return {
        max: step * 3,
        ticks: [step * 3, step * 2, step, 0],
    };
});

const barHeight = (value: number) => `${Math.max(4, Math.round((value / yAxis.value.max) * 100))}%`;

const labelStep = computed(() => Math.max(1, Math.ceil(props.rows.length / 7)));

const showLabel = (index: number) => index % labelStep.value === 0 || index === props.rows.length - 1;

const hasData = computed(() => props.rows.some((row) => row.new_members > 0));
</script>

<template>
    <div class="member-growth">
        <div class="member-growth-legend">
            <span><i class="member-growth-swatch" aria-hidden="true"></i>Member baru <strong>{{ formatNumber(totalMembers) }}</strong></span>
        </div>

        <div v-if="hasData" class="member-growth-body">
            <div class="member-growth-axis" aria-hidden="true">
                <span v-for="tick in yAxis.ticks" :key="`y-${tick}`">{{ formatNumber(tick) }}</span>
            </div>
            <div class="member-growth-bars" role="img" :aria-label="`Grafik batang pertumbuhan member baru per ${grain === 'month' ? 'bulan' : 'hari'}`">
                <div class="member-growth-grid">
                    <div v-for="(row, index) in rows" :key="row.date" class="member-growth-column">
                        <div class="member-growth-track">
                            <div
                                class="member-growth-bar"
                                :style="{ height: barHeight(row.new_members) }"
                                :title="`${row.label}: ${formatNumber(row.new_members)} member baru`"
                            ></div>
                        </div>
                        <span class="member-growth-label">{{ showLabel(index) ? row.label : '' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <p v-else class="member-growth-empty">Belum ada member baru pada periode ini.</p>

        <table class="visually-hidden">
            <caption>Data pertumbuhan member baru</caption>
            <thead>
                <tr>
                    <th scope="col">Periode</th>
                    <th scope="col">Member baru</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="`table-${row.date}`">
                    <th scope="row">{{ row.label }}</th>
                    <td>{{ formatNumber(row.new_members) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
.member-growth {
    display: grid;
    gap: 0.5rem;
}

.member-growth-legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem 1rem;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
}

.member-growth-legend span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.member-growth-legend strong {
    color: var(--app-ink);
    font-variant-numeric: tabular-nums;
}

.member-growth-swatch {
    width: 0.7rem;
    height: 0.7rem;
    border-radius: 2px;
    background: var(--app-accent);
}

.member-growth-body {
    display: flex;
    align-items: flex-start;
    gap: 0.4rem;
}

.member-growth-axis {
    display: flex;
    flex: 0 0 auto;
    flex-direction: column;
    justify-content: space-between;
    width: 2.6rem;
    height: 8rem;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    font-variant-numeric: tabular-nums;
    line-height: 1;
    text-align: right;
}

.member-growth-bars {
    overflow-x: auto;
    padding-bottom: 0.2rem;
}

.member-growth-grid {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: minmax(1.4rem, 1fr);
    gap: 0.3rem;
    min-width: 560px;
    align-items: end;
}

.member-growth-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    min-width: 0;
}

.member-growth-track {
    display: flex;
    align-items: flex-end;
    width: 100%;
    height: 8rem;
    border-radius: var(--app-radius-xs) var(--app-radius-xs) 0 0;
    background: var(--app-surface-2);
}

.member-growth-bar {
    width: 100%;
    border-radius: var(--app-radius-xs) var(--app-radius-xs) 0 0;
    background: var(--app-accent);
    transition: height var(--app-dur-base) var(--app-ease-out);
}

.member-growth-label {
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    line-height: 1;
    white-space: nowrap;
}

.member-growth-empty {
    margin: 0;
    padding: 1.5rem 0.5rem;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    text-align: center;
}
</style>
