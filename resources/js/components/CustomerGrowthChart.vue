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

const chart = {
    width: 760,
    height: 240,
    top: 20,
    right: 18,
    bottom: 34,
    left: 46,
};

const innerWidth = chart.width - chart.left - chart.right;
const innerHeight = chart.height - chart.top - chart.bottom;

const totals = computed(() => ({
    members: props.rows.reduce((total, row) => total + row.new_members, 0),
    visits: props.rows.reduce((total, row) => total + row.visits, 0),
}));

const yAxis = computed(() => {
    const max = Math.max(1, ...props.rows.flatMap((row) => [row.new_members, row.visits]));
    const rawStep = Math.max(1, max / 3);
    const exponent = Math.floor(Math.log10(rawStep));
    const base = 10 ** exponent;
    const fraction = rawStep / base;
    const niceFraction = fraction <= 1 ? 1 : fraction <= 2 ? 2 : fraction <= 5 ? 5 : 10;
    const step = Math.max(1, niceFraction * base);

    return {
        max: step * 3,
        ticks: [0, step, step * 2, step * 3],
    };
});

const pointX = (index: number) => {
    if (props.rows.length <= 1) {
        return chart.left + innerWidth / 2;
    }

    return chart.left + (index / (props.rows.length - 1)) * innerWidth;
};

const pointY = (value: number) => chart.top + innerHeight - (value / yAxis.value.max) * innerHeight;

const linePath = (key: 'new_members' | 'visits') => props.rows
    .map((row, index) => `${index === 0 ? 'M' : 'L'}${pointX(index).toFixed(2)},${pointY(row[key]).toFixed(2)}`)
    .join(' ');

const xTicks = computed(() => {
    const count = props.rows.length;

    if (count <= 6) {
        return props.rows.map((row, index) => ({ index, label: row.label }));
    }

    const target = 6;
    const indexes = new Set<number>();

    for (let index = 0; index < target; index += 1) {
        indexes.add(Math.round((index / (target - 1)) * (count - 1)));
    }

    return [...indexes]
        .sort((a, b) => a - b)
        .map((index) => ({ index, label: props.rows[index].label }));
});

const hasData = computed(() => props.rows.some((row) => row.new_members > 0 || row.visits > 0));
</script>

<template>
    <div class="customer-growth">
        <div class="customer-growth-legend">
            <span><i class="customer-growth-key is-members" aria-hidden="true"></i>Member baru <strong>{{ formatNumber(totals.members) }}</strong></span>
            <span><i class="customer-growth-key is-visits" aria-hidden="true"></i>Kunjungan toko <strong>{{ formatNumber(totals.visits) }}</strong></span>
        </div>

        <div v-if="hasData" class="customer-growth-chart">
            <svg
                :viewBox="`0 0 ${chart.width} ${chart.height}`"
                role="img"
                :aria-label="`Tren member baru (garis biru solid) dan kunjungan toko (garis kuning putus-putus) per ${grain === 'month' ? 'bulan' : 'hari'}`"
            >
                <g class="customer-growth-grid">
                    <line
                        v-for="tick in yAxis.ticks"
                        :key="`grid-${tick}`"
                        :x1="chart.left"
                        :x2="chart.width - chart.right"
                        :y1="pointY(tick)"
                        :y2="pointY(tick)"
                    />
                    <text
                        v-for="tick in yAxis.ticks"
                        :key="`label-${tick}`"
                        :x="chart.left - 8"
                        :y="pointY(tick) + 4"
                        text-anchor="end"
                    >{{ formatNumber(tick) }}</text>
                </g>

                <path class="customer-growth-path is-members" :d="linePath('new_members')" />
                <path class="customer-growth-path is-visits" :d="linePath('visits')" />

                <g v-for="(row, index) in rows" :key="`points-${row.date}`">
                    <circle class="customer-growth-dot is-members" :cx="pointX(index)" :cy="pointY(row.new_members)" r="3.5">
                        <title>{{ row.label }} member baru: {{ formatNumber(row.new_members) }}</title>
                    </circle>
                    <rect
                        class="customer-growth-dot is-visits"
                        :x="pointX(index) - 3"
                        :y="pointY(row.visits) - 3"
                        width="6"
                        height="6"
                        rx="1"
                    >
                        <title>{{ row.label }} kunjungan: {{ formatNumber(row.visits) }}</title>
                    </rect>
                </g>

                <g v-if="rows.length" class="customer-growth-end-label" aria-hidden="true">
                    <text :x="chart.width - chart.right - 2" :y="pointY(rows[rows.length - 1].new_members) - 9" text-anchor="end" class="is-members">Member</text>
                    <text :x="chart.width - chart.right - 2" :y="pointY(rows[rows.length - 1].visits) + 16" text-anchor="end" class="is-visits">Kunjungan</text>
                </g>

                <g class="customer-growth-x">
                    <text
                        v-for="tick in xTicks"
                        :key="`x-${tick.index}`"
                        :x="pointX(tick.index)"
                        :y="chart.height - 10"
                        text-anchor="middle"
                    >{{ tick.label }}</text>
                </g>
            </svg>
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

.customer-growth-key {
    position: relative;
    display: inline-block;
    width: 1.6rem;
    height: 0.6rem;
    border-top: 2px solid var(--app-accent);
}

.customer-growth-key::after {
    position: absolute;
    top: -0.32rem;
    right: 0;
    width: 0.42rem;
    height: 0.42rem;
    border-radius: 50%;
    background: var(--app-accent);
    content: "";
}

.customer-growth-key.is-visits {
    border-top-style: dashed;
    border-color: var(--app-warning-ink);
}

.customer-growth-key.is-visits::after {
    border-radius: 1px;
    background: var(--app-warning-ink);
}

.customer-growth-chart {
    width: 100%;
    overflow-x: auto;
}

.customer-growth-chart svg {
    display: block;
    width: 100%;
    min-width: 520px;
    height: auto;
}

.customer-growth-grid line {
    stroke: var(--app-border);
    stroke-width: 1;
}

.customer-growth-grid text,
.customer-growth-x text {
    fill: var(--app-muted);
    font-size: 11px;
    font-variant-numeric: tabular-nums;
}

.customer-growth-path {
    fill: none;
    stroke-width: 2.5;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.customer-growth-path.is-members {
    stroke: var(--app-accent);
}

.customer-growth-path.is-visits {
    stroke: var(--app-warning-ink);
    stroke-dasharray: 7 5;
}

.customer-growth-dot.is-members {
    fill: var(--app-accent);
    stroke: var(--app-surface);
    stroke-width: 1.5;
}

.customer-growth-dot.is-visits {
    fill: var(--app-warning-ink);
    stroke: var(--app-surface);
    stroke-width: 1.5;
}

.customer-growth-end-label text {
    font-size: 10px;
    font-weight: 600;
    paint-order: stroke;
    stroke: var(--app-surface);
    stroke-width: 3px;
}

.customer-growth-end-label .is-members {
    fill: var(--app-accent);
}

.customer-growth-end-label .is-visits {
    fill: var(--app-warning-ink);
}

.customer-growth-empty {
    margin: 0;
    padding: 1.5rem 0.5rem;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    text-align: center;
}
</style>
