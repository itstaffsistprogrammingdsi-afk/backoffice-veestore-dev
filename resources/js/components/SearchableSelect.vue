<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';

type SelectValue = string | number;

interface SelectOption {
    value: SelectValue;
    label: string;
    hint?: string;
}

const props = withDefaults(defineProps<{
    modelValue: SelectValue | SelectValue[] | null | undefined;
    options: SelectOption[];
    id?: string;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    noResultsText?: string;
    ariaLabel?: string;
    disabled?: boolean;
    multiple?: boolean;
    clearable?: boolean;
    required?: boolean;
    size?: 'sm' | 'md';
    maxVisibleTags?: number;
}>(), {
    id: undefined,
    placeholder: 'Pilih opsi',
    searchPlaceholder: 'Cari...',
    emptyText: 'Belum ada opsi.',
    noResultsText: 'Tidak ada hasil yang cocok.',
    ariaLabel: undefined,
    disabled: false,
    multiple: false,
    clearable: true,
    required: false,
    size: 'sm',
    maxVisibleTags: 2,
});

const emit = defineEmits<{
    (event: 'update:modelValue', value: SelectValue | SelectValue[] | null): void;
    (event: 'change', value: SelectValue | SelectValue[] | null): void;
}>();

const uid = useId();
const listboxId = `searchable-listbox-${uid}`;
const searchInputId = `searchable-search-${uid}`;

const open = ref(false);
const search = ref('');
const activeIndex = ref(0);
const triggerRef = ref<HTMLButtonElement | null>(null);
const dropdownRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);
const listRef = ref<HTMLElement | null>(null);
const popoverStyle = ref<Record<string, string>>({});

const selectedValues = computed<SelectValue[]>(() => {
    const value = props.modelValue;

    if (props.multiple) {
        return Array.isArray(value) ? value : [];
    }

    if (Array.isArray(value)) {
        return value;
    }

    if (value === null || value === undefined || value === '') {
        return [];
    }

    return [value];
});

const isSelected = (option: SelectOption) => selectedValues.value.some(
    (value) => String(value) === String(option.value),
);

const selectedOptions = computed(() => props.options.filter(isSelected));

const singleLabel = computed(() => {
    if (selectedOptions.value.length > 0) {
        return selectedOptions.value[0].label;
    }

    if (!props.multiple && props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== '') {
        return String(props.modelValue);
    }

    return '';
});

const hasSelection = computed(() => selectedOptions.value.length > 0);

const maxOptions = 200;

const normalizedSearch = computed(() => search.value.trim().toLocaleLowerCase('id-ID'));

const filteredOptions = computed(() => {
    if (normalizedSearch.value === '') return props.options;

    return props.options.filter((option) => (
        option.label.toLocaleLowerCase('id-ID').includes(normalizedSearch.value)
        || (option.hint ?? '').toLocaleLowerCase('id-ID').includes(normalizedSearch.value)
    ));
});

const visibleOptions = computed(() => filteredOptions.value.slice(0, maxOptions));

const isTruncated = computed(() => filteredOptions.value.length > maxOptions);

const activeOption = computed(() => visibleOptions.value[activeIndex.value] ?? null);

const activeDescendant = computed(() => (
    open.value && activeOption.value
        ? optionId(activeIndex.value)
        : undefined
));

const optionId = (index: number) => `${listboxId}-option-${index}`;

const placeholderText = computed(() => props.placeholder);

const triggerLabel = computed(() => {
    if (props.multiple) {
        if (selectedOptions.value.length === 0) return '';
        if (selectedOptions.value.length <= props.maxVisibleTags) {
            return selectedOptions.value.map((option) => option.label).join(', ');
        }

        return `${selectedOptions.value.length} dipilih`;
    }

    return singleLabel.value;
});

const updatePosition = () => {
    if (!open.value || !triggerRef.value) return;

    const rect = triggerRef.value.getBoundingClientRect();
    const gap = 6;
    const width = Math.min(Math.max(rect.width, 220), Math.max(220, window.innerWidth - 16));
    const left = Math.min(Math.max(8, rect.left), Math.max(8, window.innerWidth - width - 8));
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    const openAbove = spaceBelow < 280 && spaceAbove > spaceBelow;
    const maxHeight = Math.max(180, Math.min(340, openAbove ? spaceAbove - gap - 8 : spaceBelow - gap - 8));

    popoverStyle.value = openAbove
        ? {
            left: `${left}px`,
            top: 'auto',
            bottom: `${window.innerHeight - rect.top + gap}px`,
            width: `${width}px`,
            maxHeight: `${maxHeight}px`,
        }
        : {
            left: `${left}px`,
            top: `${rect.bottom + gap}px`,
            bottom: 'auto',
            width: `${width}px`,
            maxHeight: `${maxHeight}px`,
        };
};

const scrollActiveIntoView = () => {
    nextTick(() => {
        const element = listRef.value?.querySelector<HTMLElement>(`[data-option-index="${activeIndex.value}"]`);

        element?.scrollIntoView({ block: 'nearest' });
    });
};

const addListeners = () => {
    document.addEventListener('pointerdown', onPointerDown, true);
    window.addEventListener('resize', updatePosition);
    window.addEventListener('scroll', updatePosition, true);
};

const removeListeners = () => {
    document.removeEventListener('pointerdown', onPointerDown, true);
    window.removeEventListener('resize', updatePosition);
    window.removeEventListener('scroll', updatePosition, true);
};

const openMenu = async () => {
    if (props.disabled || open.value) return;

    open.value = true;
    search.value = '';
    const selectedIndex = visibleOptions.value.findIndex(isSelected);
    activeIndex.value = selectedIndex >= 0 ? selectedIndex : 0;

    addListeners();
    await nextTick();
    updatePosition();
    searchInputRef.value?.focus();
    scrollActiveIntoView();
};

const closeMenu = (focusTrigger = false) => {
    if (!open.value) return;

    open.value = false;
    search.value = '';
    removeListeners();

    if (focusTrigger) {
        nextTick(() => triggerRef.value?.focus());
    }
};

const toggleMenu = () => {
    if (open.value) {
        closeMenu();
        return;
    }

    void openMenu();
};

const selectOption = (option: SelectOption) => {
    if (props.multiple) {
        const current = [...selectedValues.value];
        const index = current.findIndex((value) => String(value) === String(option.value));

        if (index >= 0) {
            current.splice(index, 1);
        } else {
            current.push(option.value);
        }

        emit('update:modelValue', current);
        emit('change', current);
        search.value = '';
        activeIndex.value = 0;
        nextTick(() => {
            searchInputRef.value?.focus();
            scrollActiveIntoView();
        });

        return;
    }

    emit('update:modelValue', option.value);
    emit('change', option.value);
    closeMenu(true);
};

const clearSelection = () => {
    const value = props.multiple ? [] : '';
    emit('update:modelValue', value);
    emit('change', value);
    closeMenu(true);
};

const moveActive = (direction: number) => {
    if (visibleOptions.value.length === 0) return;

    const next = activeIndex.value + direction;
    activeIndex.value = next < 0
        ? visibleOptions.value.length - 1
        : next >= visibleOptions.value.length
            ? 0
            : next;

    scrollActiveIntoView();
};

const onSearchKeydown = (event: KeyboardEvent) => {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        moveActive(1);
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        moveActive(-1);
        return;
    }

    if (event.key === 'Home') {
        event.preventDefault();
        activeIndex.value = 0;
        scrollActiveIntoView();
        return;
    }

    if (event.key === 'End') {
        event.preventDefault();
        activeIndex.value = Math.max(0, visibleOptions.value.length - 1);
        scrollActiveIntoView();
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        const option = activeOption.value;

        if (option) selectOption(option);
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        closeMenu(true);
        return;
    }

    if (event.key === 'Tab') {
        event.preventDefault();
        closeMenu(true);
    }
};

const onTriggerKeydown = (event: KeyboardEvent) => {
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        void openMenu();
    }
};

const onPointerDown = (event: PointerEvent) => {
    const target = event.target as Node | null;

    if (
        target
        && (triggerRef.value?.contains(target) || dropdownRef.value?.contains(target))
    ) {
        return;
    }

    closeMenu();
};

watch(search, () => {
    activeIndex.value = 0;
    scrollActiveIntoView();
});

watch(
    () => props.options,
    () => {
        if (!open.value) return;

        if (activeIndex.value >= visibleOptions.value.length) {
            activeIndex.value = 0;
        }

        nextTick(updatePosition);
    },
);

onBeforeUnmount(() => {
    removeListeners();
});
</script>

<template>
    <div
        class="searchable-select"
        :class="[
            `searchable-select-${props.size}`,
            { 'is-open': open, 'is-disabled': props.disabled, 'is-multiple': props.multiple },
        ]"
    >
        <div class="searchable-select-control" :class="{ 'has-clear': hasSelection && props.clearable && !props.disabled }">
            <button
                :id="props.id"
                ref="triggerRef"
                class="searchable-select-trigger"
                type="button"
                :aria-label="`${props.ariaLabel ?? props.placeholder}${props.required ? ' (wajib)' : ''}`"
                :aria-haspopup="'listbox'"
                :aria-expanded="open"
                :aria-controls="listboxId"
                :disabled="props.disabled"
                @click="toggleMenu"
                @keydown="onTriggerKeydown"
            >
                <span v-if="!triggerLabel" class="searchable-select-placeholder">{{ placeholderText }}</span>
                <span v-else class="searchable-select-value">{{ triggerLabel }}</span>
                <span class="searchable-select-indicators">
                    <i class="mdi searchable-select-chevron" :class="open ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>
                </span>
            </button>
            <button
                v-if="hasSelection && props.clearable && !props.disabled"
                class="searchable-select-clear"
                type="button"
                aria-label="Bersihkan pilihan"
                title="Bersihkan pilihan"
                @click.stop="clearSelection"
            >
                <i class="mdi mdi-close-circle"></i>
            </button>
        </div>

        <Teleport to="body">
            <div
                v-if="open"
                ref="dropdownRef"
                class="searchable-select-popover"
                :style="popoverStyle"
            >
                <div class="searchable-select-search">
                    <i class="mdi mdi-magnify" aria-hidden="true"></i>
                    <input
                        :id="searchInputId"
                        ref="searchInputRef"
                        v-model="search"
                        type="search"
                        role="combobox"
                        autocomplete="off"
                        spellcheck="false"
                        :aria-label="props.searchPlaceholder"
                        :aria-expanded="open"
                        :aria-controls="listboxId"
                        :aria-activedescendant="activeDescendant"
                        aria-autocomplete="list"
                        :placeholder="props.searchPlaceholder"
                        @keydown="onSearchKeydown"
                    />
                    <button
                        v-if="search"
                        class="searchable-select-search-clear"
                        type="button"
                        aria-label="Bersihkan pencarian"
                        @click="search = ''"
                    >
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>

                <ul
                    :id="listboxId"
                    ref="listRef"
                    class="searchable-select-list"
                    role="listbox"
                    :aria-multiselectable="props.multiple || undefined"
                >
                    <li
                        v-for="(option, index) in visibleOptions"
                        :id="optionId(index)"
                        :key="`${option.value}-${index}`"
                        class="searchable-select-option"
                        :class="{ 'is-active': index === activeIndex, 'is-selected': isSelected(option) }"
                        :data-option-index="index"
                        role="option"
                        :aria-selected="isSelected(option)"
                        @click="selectOption(option)"
                        @mouseenter="activeIndex = index"
                    >
                        <span class="searchable-select-option-label">{{ option.label }}</span>
                        <small v-if="option.hint">{{ option.hint }}</small>
                        <i v-if="isSelected(option)" class="mdi mdi-check searchable-select-check" aria-hidden="true"></i>
                    </li>
                </ul>

                <p v-if="props.options.length === 0" class="searchable-select-empty">{{ props.emptyText }}</p>
                <p v-else-if="visibleOptions.length === 0" class="searchable-select-empty">{{ props.noResultsText }}</p>
                <p v-else-if="isTruncated" class="searchable-select-more">
                    Menampilkan {{ maxOptions }} dari {{ filteredOptions.length }} opsi. Persempit pencarian untuk hasil lain.
                </p>

                <div v-if="props.multiple" class="searchable-select-footer">
                    <span>{{ selectedValues.length }} dipilih</span>
                    <button class="btn btn-sm btn-primary" type="button" @click="closeMenu(true)">Selesai</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.searchable-select {
    position: relative;
    width: 100%;
    color: var(--app-ink);
}

.searchable-select-control {
    position: relative;
    width: 100%;
}

.searchable-select-control.has-clear .searchable-select-trigger {
    padding-right: 2.9rem;
}

.searchable-select-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    width: 100%;
    padding: 0.32rem 0.55rem;
    border: 1px solid var(--app-border);
    border-radius: 0.4rem;
    background: var(--app-surface);
    color: var(--app-ink);
    font-size: 0.8125rem;
    line-height: 1.35;
    text-align: left;
    transition: border-color 120ms ease, box-shadow 120ms ease;
}

.searchable-select-md .searchable-select-trigger {
    padding: 0.48rem 0.7rem;
    font-size: 0.9rem;
}

.searchable-select-trigger:hover:not(:disabled) {
    border-color: var(--app-accent);
}

.searchable-select-trigger:focus-visible,
.searchable-select.is-open .searchable-select-trigger {
    border-color: var(--app-accent);
    outline: 0;
    box-shadow: 0 0 0 0.18rem var(--app-accent-soft);
}

.searchable-select-trigger:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}

.searchable-select-value {
    overflow: hidden;
    flex: 1 1 auto;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.searchable-select-placeholder {
    overflow: hidden;
    flex: 1 1 auto;
    color: var(--app-muted);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.searchable-select-indicators {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 0.1rem;
    color: var(--app-muted);
}

.searchable-select-clear {
    position: absolute;
    top: 50%;
    right: 1.6rem;
    display: inline-grid;
    width: 1.1rem;
    height: 1.1rem;
    padding: 0;
    place-items: center;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: var(--app-muted);
    font-size: 0.9rem;
    transform: translateY(-50%);
}

.searchable-select-clear::before {
    position: absolute;
    inset: -0.85rem;
    content: "";
}

.searchable-select-clear:hover {
    color: var(--app-danger-ink);
}

.searchable-select-chevron {
    font-size: 0.95rem;
}

.searchable-select-popover {
    position: fixed;
    z-index: 2050;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--app-border);
    border-radius: 0.55rem;
    background: var(--app-surface);
    box-shadow: 0 0.8rem 2rem rgba(15, 23, 42, 0.18);
}

.searchable-select-search {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 0.6rem;
    border-bottom: 1px solid var(--app-border);
    background: var(--app-surface-2);
}

.searchable-select-search i {
    color: var(--app-muted);
    font-size: 0.95rem;
}

.searchable-select-search input {
    flex: 1 1 auto;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--app-ink);
    font-size: 0.82rem;
}

.searchable-select-search input:focus-visible {
    outline: 2px solid var(--app-accent);
    outline-offset: 2px;
    border-radius: 0.2rem;
}

.searchable-select-search input::placeholder {
    color: var(--app-muted);
}

.searchable-select-search-clear {
    position: relative;
    display: inline-grid;
    width: 1.2rem;
    height: 1.2rem;
    padding: 0;
    place-items: center;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: var(--app-muted);
}

.searchable-select-search-clear::before {
    position: absolute;
    inset: -0.85rem;
    content: "";
}

.searchable-select-list {
    overflow-y: auto;
    flex: 1 1 auto;
    margin: 0;
    padding: 0.25rem;
    list-style: none;
}

.searchable-select-option {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 2rem;
    padding: 0.45rem 0.55rem;
    border-radius: 0.35rem;
    color: var(--app-ink);
    font-size: 0.8rem;
    cursor: pointer;
}

@media (pointer: coarse) {
    .searchable-select-option {
        min-height: 44px;
    }

    .searchable-select-clear,
    .searchable-select-search-clear {
        width: 44px;
        height: 44px;
    }
}

.searchable-select-option small {
    overflow: hidden;
    margin-left: auto;
    color: var(--app-muted);
    font-size: 0.68rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.searchable-select-option.is-active {
    background: var(--app-accent-soft);
}

.searchable-select-option.is-selected {
    color: var(--app-accent-ink);
    font-weight: 700;
}

.searchable-select-option-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.searchable-select-check {
    flex: 0 0 auto;
    margin-left: auto;
    color: var(--app-accent-ink);
    font-size: 0.9rem;
}

.searchable-select-empty,
.searchable-select-more {
    margin: 0;
    padding: 0.65rem 0.7rem;
    color: var(--app-muted);
    font-size: 0.75rem;
    text-align: center;
}

.searchable-select-more {
    border-top: 1px solid var(--app-border);
    text-align: left;
}

.searchable-select-footer {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.5rem 0.6rem;
    border-top: 1px solid var(--app-border);
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 0.75rem;
}

</style>
