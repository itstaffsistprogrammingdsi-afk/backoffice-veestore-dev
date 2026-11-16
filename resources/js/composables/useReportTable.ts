import { computed, ref, watch } from 'vue';

export type ReportTableDensity = 'comfortable' | 'compact';

const readDensity = (storageKey: string): ReportTableDensity => {
    if (typeof window === 'undefined') return 'comfortable';

    try {
        return window.localStorage.getItem(storageKey) === 'compact' ? 'compact' : 'comfortable';
    } catch {
        return 'comfortable';
    }
};

export const useReportTableDensity = (storageKey = 'veestore.report-table-density') => {
    const density = ref<ReportTableDensity>(readDensity(storageKey));

    watch(density, (value) => {
        try {
            window.localStorage.setItem(storageKey, value);
        } catch {
            return;
        }
    });

    const isCompact = computed(() => density.value === 'compact');

    const toggleDensity = () => {
        density.value = density.value === 'compact' ? 'comfortable' : 'compact';
    };

    return { density, isCompact, toggleDensity };
};

const readVisibleColumns = (storageKey: string, allColumns: string[]): string[] => {
    if (typeof window === 'undefined') return allColumns;

    try {
        const stored = window.localStorage.getItem(storageKey);
        if (!stored) return allColumns;

        const parsed = JSON.parse(stored);
        if (!Array.isArray(parsed)) return allColumns;

        const valid = parsed.filter((column): column is string => typeof column === 'string' && allColumns.includes(column));

        return valid.length > 0 ? valid : allColumns;
    } catch {
        return allColumns;
    }
};

export const useReportTableColumns = (storageKey: string, allColumns: string[]) => {
    const visibleColumns = ref<string[]>(readVisibleColumns(storageKey, allColumns));

    watch(visibleColumns, (value) => {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(value));
        } catch {
            return;
        }
    }, { deep: true });

    const isVisible = (column: string) => visibleColumns.value.includes(column);

    const toggleColumn = (column: string) => {
        if (isVisible(column)) {
            if (visibleColumns.value.length === 1) return;
            visibleColumns.value = visibleColumns.value.filter((item) => item !== column);
            return;
        }

        visibleColumns.value = allColumns.filter((item) => item === column || isVisible(item));
    };

    const showAll = () => {
        visibleColumns.value = [...allColumns];
    };

    const applyPreset = (columns: string[]) => {
        visibleColumns.value = allColumns.filter((column) => columns.includes(column));
    };

    const columnCount = computed(() => visibleColumns.value.length);

    return {
        visibleColumns,
        isVisible,
        toggleColumn,
        showAll,
        applyPreset,
        columnCount,
    };
};
