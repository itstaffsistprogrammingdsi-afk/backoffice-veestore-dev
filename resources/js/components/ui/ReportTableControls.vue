<script setup lang="ts">
import type { ReportTableDensity } from '@/composables/useReportTable';

interface ColumnOption {
    key: string;
    label: string;
}

const props = withDefaults(defineProps<{
    columns?: ColumnOption[];
    visible?: string[];
    density: ReportTableDensity;
    simplePreset?: string[];
}>(), {
    columns: () => [],
    visible: () => [],
    simplePreset: () => [],
});

const emit = defineEmits<{
    (event: 'toggle-density'): void;
    (event: 'toggle-column', key: string): void;
    (event: 'show-all'): void;
    (event: 'apply-simple'): void;
}>();

const isVisible = (key: string) => props.visible.includes(key);
const hasColumnControls = () => props.columns.length > 0;
</script>

<template>
    <div class="report-table-controls" role="group" aria-label="Pengaturan tampilan tabel">
        <button
            class="report-table-controls__density"
            type="button"
            :aria-pressed="density === 'compact'"
            :title="density === 'compact' ? 'Gunakan kepadatan nyaman' : 'Gunakan kepadatan ringkas'"
            @click="emit('toggle-density')"
        >
            <i class="mdi" :class="density === 'compact' ? 'mdi-table-large' : 'mdi-table'" aria-hidden="true"></i>
            {{ density === 'compact' ? 'Ringkas' : 'Nyaman' }}
        </button>

        <details v-if="hasColumnControls()" class="report-table-controls__menu">
            <summary class="report-table-controls__trigger">
                <i class="mdi mdi-view-column-outline" aria-hidden="true"></i>
                Kolom
            </summary>
            <div class="report-table-controls__panel">
                <div class="report-table-controls__presets">
                    <button type="button" @click="emit('apply-simple')">Tampilan sederhana</button>
                    <button type="button" @click="emit('show-all')">Tampilan lengkap</button>
                </div>
                <fieldset class="report-table-controls__list">
                    <legend class="visually-hidden">Pilih kolom yang ditampilkan</legend>
                    <label v-for="column in columns" :key="column.key">
                        <input
                            type="checkbox"
                            :checked="isVisible(column.key)"
                            :disabled="isVisible(column.key) && visible.length === 1"
                            @change="emit('toggle-column', column.key)"
                        />
                        <span>{{ column.label }}</span>
                    </label>
                </fieldset>
            </div>
        </details>
    </div>
</template>

<style scoped>
.report-table-controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
}

.report-table-controls__density,
.report-table-controls__trigger {
    display: inline-flex;
    min-height: 34px;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.6rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-sm);
    background: var(--app-surface);
    color: var(--app-ink);
    font: inherit;
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-semibold);
    cursor: pointer;
    list-style: none;
    transition: border-color var(--app-dur-fast) var(--app-ease-out), background-color var(--app-dur-fast) var(--app-ease-out);
}

.report-table-controls__trigger::-webkit-details-marker {
    display: none;
}

.report-table-controls__density:hover,
.report-table-controls__trigger:hover,
.report-table-controls__density:focus-visible,
.report-table-controls__trigger:focus-visible {
    border-color: var(--app-accent);
    background: var(--app-surface-2);
}

.report-table-controls__menu {
    position: relative;
}

.report-table-controls__menu:not([open]) .report-table-controls__panel {
    display: none;
}

.report-table-controls__panel {
    position: absolute;
    z-index: 20;
    top: calc(100% + 0.35rem);
    right: 0;
    width: min(18rem, calc(100vw - 2rem));
    padding: 0.65rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-md);
    background: var(--app-surface);
    box-shadow: var(--app-shadow-md);
}

.report-table-controls__presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    padding-bottom: 0.55rem;
    margin-bottom: 0.55rem;
    border-bottom: 1px solid var(--app-border);
}

.report-table-controls__presets button {
    flex: 1 1 auto;
    min-height: 34px;
    padding: 0.3rem 0.5rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xs);
    background: var(--app-surface-2);
    color: var(--app-ink);
    font: inherit;
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-semibold);
    cursor: pointer;
}

.report-table-controls__presets button:hover,
.report-table-controls__presets button:focus-visible {
    border-color: var(--app-accent);
}

.report-table-controls__list {
    display: grid;
    gap: 0.3rem;
    max-height: 16rem;
    padding: 0;
    margin: 0;
    overflow-y: auto;
    border: 0;
}

.report-table-controls__list label {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    min-height: 30px;
    color: var(--app-ink);
    font-size: var(--app-text-sm);
    cursor: pointer;
}

.report-table-controls__list input {
    width: 1rem;
    height: 1rem;
    accent-color: var(--app-accent-strong);
}

@media (max-width: 767.98px) {
    .report-table-controls__density,
    .report-table-controls__trigger,
    .report-table-controls__presets button {
        min-height: 44px;
    }

    .report-table-controls__panel {
        position: fixed;
        top: auto;
        right: 1rem;
        bottom: 1rem;
        left: 1rem;
        width: auto;
        max-height: 60dvh;
        overflow-y: auto;
    }
}
</style>
