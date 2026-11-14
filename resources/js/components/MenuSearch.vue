<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { currentLocale, switchLocale, translateText } from '@/i18n';
import { useTheme } from '@/composables/useTheme';

interface NavItem {
    label: string;
    url: string | null;
    icon: string;
    route: string;
    inertia: boolean;
}

interface NavGroup {
    label: string;
    icon: string;
    items: NavItem[];
}

interface NavArea {
    key: string;
    label: string;
    description: string;
    url: string | null;
    icon: string;
    groups: NavGroup[];
}

type CommandAction = 'toggle-theme' | 'toggle-locale' | 'reload-page';

interface Command {
    id: string;
    kind: 'menu' | 'action';
    label: string;
    hint: string;
    icon: string;
    url: string | null;
    inertia: boolean;
    action?: CommandAction;
    areaKey: string;
    areaLabel: string;
    groupLabel: string;
    searchLabel: string;
    searchMeta: string;
}

interface SearchSection {
    key: string;
    label: string;
    ariaLabel?: string;
    clearable?: boolean;
    items: Command[];
}

const props = defineProps<{
    areas: NavArea[];
}>();

const emit = defineEmits<{
    open: [];
    close: [];
}>();

const { isDark, toggleTheme } = useTheme();

const RECENT_STORAGE_KEY = 'veestore.menu-search.recent';
const MAX_RECENT = 5;

const normalizeText = (value: string): string =>
    value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('id-ID')
        .replace(/\s+/g, ' ')
        .trim();

const escapeRegExp = (value: string): string => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const isSubsequence = (needle: string, haystack: string): boolean => {
    let cursor = 0;

    for (const character of haystack) {
        if (character === needle[cursor]) cursor += 1;
        if (cursor === needle.length) return true;
    }

    return false;
};

const SEARCH_SYNONYMS: Record<string, string> = {
    asisten: 'assistant',
    barang: 'item product goods',
    cabang: 'branch',
    cetak: 'print',
    diskon: 'discount',
    ekspor: 'export',
    gudang: 'warehouse',
    hadiah: 'reward',
    harian: 'daily',
    keanggotaan: 'membership',
    keluar: 'outbound',
    kunjungan: 'visit',
    kurir: 'courier',
    laporan: 'report',
    masuk: 'inbound',
    metode: 'method',
    mutasi: 'mutation transfer',
    omzet: 'revenue',
    pelanggan: 'customer',
    pembayaran: 'payment',
    pembatalan: 'void cancel',
    pemasok: 'supplier',
    pemasukan: 'income',
    pengaturan: 'setting settings',
    pengguna: 'user',
    pengiriman: 'shipping delivery',
    penjualan: 'sales',
    penyesuaian: 'adjustment',
    peran: 'role',
    pesanan: 'order',
    poin: 'point',
    promo: 'promotion',
    retur: 'return',
    riwayat: 'history',
    stok: 'stock inventory',
    toko: 'store',
    voucher: 'voucher coupon',
};

const expandSearchSynonyms = (value: string): string => {
    const extras = normalizeText(value)
        .split(' ')
        .flatMap((token) => (SEARCH_SYNONYMS[token] ?? '').split(' '))
        .filter(Boolean);

    return `${value} ${extras.join(' ')}`;
};

const readRecent = (): string[] => {
    if (typeof window === 'undefined') return [];

    try {
        const raw = window.localStorage.getItem(RECENT_STORAGE_KEY);

        if (!raw) return [];

        const parsed: unknown = JSON.parse(raw);

        if (!Array.isArray(parsed)) return [];

        return parsed.filter((value): value is string => typeof value === 'string').slice(0, MAX_RECENT);
    } catch {
        return [];
    }
};

const isEnglish = computed(() => currentLocale.value === 'en');

const strings = computed(() => {
    if (isEnglish.value) {
        return {
            triggerLabel: 'Search menus or actions',
            placeholder: 'Search menus or actions...',
            dialogTitle: 'Search menus and actions',
            quickActions: 'Quick actions',
            recent: 'Recently opened',
            clearRecent: 'Clear history',
            emptyTitle: 'No matching menu',
            emptyBody: 'Try another keyword such as stock, orders, or customers.',
            clearSearch: 'Clear search',
            closeSearch: 'Close search',
            results: (count: number) => `${count} results`,
            availableStatus: (count: number) => `${count} menus and actions available`,
            resultStatus: (count: number, value: string) => `${count} results for ${value}`,
            navigate: 'navigate',
            open: 'select',
            close: 'dismiss',
            resultsLabel: 'Search results',
            themeToLight: 'Switch to light mode',
            themeToDark: 'Switch to dark mode',
            localeToEnglish: 'Switch language to English',
            localeToIndonesian: 'Switch language to Indonesian',
            reload: 'Reload page',
            appearanceHint: 'Appearance',
            languageHint: 'Interface language',
            reloadHint: 'Reload page data',
        };
    }

    return {
        triggerLabel: 'Cari menu atau fungsi',
        placeholder: 'Cari menu atau fungsi...',
        dialogTitle: 'Cari menu dan fungsi',
        quickActions: 'Fungsi cepat',
        recent: 'Terakhir dibuka',
        clearRecent: 'Hapus riwayat',
        emptyTitle: 'Tidak ada menu yang cocok',
        emptyBody: 'Coba kata kunci lain, misalnya stok, pesanan, atau pelanggan.',
        clearSearch: 'Hapus pencarian',
        closeSearch: 'Tutup pencarian',
        results: (count: number) => `${count} hasil`,
        availableStatus: (count: number) => `${count} menu dan fungsi tersedia`,
        resultStatus: (count: number, value: string) => `${count} hasil untuk ${value}`,
        navigate: 'navigasi',
        open: 'pilih',
        close: 'tutup',
        resultsLabel: 'Hasil pencarian',
        themeToLight: 'Ganti ke mode terang',
        themeToDark: 'Ganti ke mode gelap',
        localeToEnglish: 'Ganti bahasa ke English',
        localeToIndonesian: 'Ganti bahasa ke Indonesia',
        reload: 'Muat ulang halaman',
        appearanceHint: 'Tampilan',
        languageHint: 'Bahasa antarmuka',
        reloadHint: 'Data halaman dimuat ulang',
    };
});

const shortcutHint = typeof navigator !== 'undefined' && /Mac|iPhone|iPad|iPod/.test(navigator.userAgent)
    ? '⌘K'
    : 'Ctrl K';

const open = ref(false);
const query = ref('');
const activeIndex = ref(0);
const recent = ref<string[]>(readRecent());
const triggerRef = ref<HTMLButtonElement | null>(null);
const dialogRef = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const resultsRef = ref<HTMLElement | null>(null);

let previouslyFocused: HTMLElement | null = null;
let previousBodyOverflow = '';

const queryText = computed(() => normalizeText(query.value));
const queryTokens = computed(() => queryText.value.split(' ').filter(Boolean));

const menuCommands = computed<Command[]>(() => {
    const commands: Command[] = [];

    for (const area of props.areas) {
        const areaEnglish = translateText(area.label, 'en');
        const areaIndonesian = translateText(area.label, 'id');

        for (const group of area.groups) {
            const groupEnglish = translateText(group.label, 'en');
            const groupIndonesian = translateText(group.label, 'id');

            for (const item of group.items) {
                const itemEnglish = translateText(item.label, 'en');
                const itemIndonesian = translateText(item.label, 'id');
                const routeWords = item.route.replace(/[.\-_]+/g, ' ');
                const urlWords = (item.url ?? '').replace(/[/?=&]+/g, ' ');

                commands.push({
                    id: `menu:${item.route}`,
                    kind: 'menu',
                    label: item.label,
                    hint: '',
                    icon: item.icon,
                    url: item.url,
                    inertia: item.inertia,
                    areaKey: area.key,
                    areaLabel: area.label,
                    groupLabel: group.label,
                    searchLabel: normalizeText(`${itemIndonesian} ${itemEnglish}`),
                    searchMeta: normalizeText(
                        expandSearchSynonyms(
                            `${areaIndonesian} ${areaEnglish} ${groupIndonesian} ${groupEnglish} `
                            + `${routeWords} ${urlWords} ${item.url ?? ''}`,
                        ),
                    ),
                });
            }
        }
    }

    return commands;
});

const actionCommands = computed<Command[]>(() => {
    const labels = strings.value;

    return [
        {
            id: 'action:theme',
            kind: 'action',
            label: isDark.value ? labels.themeToLight : labels.themeToDark,
            hint: labels.appearanceHint,
            icon: isDark.value ? 'mdi-weather-sunny' : 'mdi-weather-night',
            url: null,
            inertia: false,
            action: 'toggle-theme',
            areaKey: '',
            areaLabel: '',
            groupLabel: '',
            searchLabel: normalizeText(
                `${isDark.value ? labels.themeToLight : labels.themeToDark} `
                + 'mode tema theme gelap terang dark light appearance tampilan',
            ),
            searchMeta: normalizeText('mode tema theme gelap terang dark light appearance tampilan'),
        },
        {
            id: 'action:locale',
            kind: 'action',
            label: isEnglish.value ? labels.localeToIndonesian : labels.localeToEnglish,
            hint: labels.languageHint,
            icon: 'mdi-translate',
            url: null,
            inertia: false,
            action: 'toggle-locale',
            areaKey: '',
            areaLabel: '',
            groupLabel: '',
            searchLabel: normalizeText(
                `${isEnglish.value ? labels.localeToIndonesian : labels.localeToEnglish} `
                + 'bahasa language locale english indonesia id en',
            ),
            searchMeta: normalizeText('bahasa language locale english indonesia id en'),
        },
        {
            id: 'action:reload',
            kind: 'action',
            label: labels.reload,
            hint: labels.reloadHint,
            icon: 'mdi-refresh',
            url: null,
            inertia: false,
            action: 'reload-page',
            areaKey: '',
            areaLabel: '',
            groupLabel: '',
            searchLabel: normalizeText(`${labels.reload} muat ulang reload refresh halaman page data`),
            searchMeta: normalizeText('muat ulang reload refresh halaman page data'),
        },
    ];
});

const menuById = computed(() => new Map(menuCommands.value.map((command) => [command.id, command])));

const recentCommands = computed<Command[]>(() =>
    recent.value
        .map((id) => menuById.value.get(id))
        .filter((command): command is Command => Boolean(command)),
);

const commandLabel = (command: Command): string =>
    command.kind === 'menu' ? translateText(command.label, currentLocale.value) : command.label;

const commandHint = (command: Command): string => {
    if (command.kind === 'action') return command.hint;

    const groupLabel = translateText(command.groupLabel, currentLocale.value);

    if (!queryText.value) return groupLabel;

    return `${translateText(command.areaLabel, currentLocale.value)} · ${groupLabel}`;
};

const scoreCommand = (command: Command, tokens: string[]): number => {
    let total = 0;

    for (const token of tokens) {
        let score = 0;

        if (command.searchLabel === token) {
            score = 140;
        } else if (command.searchLabel.startsWith(token)) {
            score = 120;
        } else if (new RegExp(`(^|\\s)${escapeRegExp(token)}`).test(command.searchLabel)) {
            score = 100;
        } else if (command.searchLabel.includes(token)) {
            score = 80;
        } else if (
            token.length > 2
            && command.searchLabel
                .split(' ')
                .some((word) => word.length >= token.length && isSubsequence(token, word))
        ) {
            score = 45;
        } else if (command.searchMeta.includes(token)) {
            score = 40;
        } else {
            return 0;
        }

        total += score;
    }

    const fullQuery = tokens.join(' ');

    if (command.searchLabel.startsWith(fullQuery)) {
        total += 30;
    } else if (command.searchLabel.includes(fullQuery)) {
        total += 15;
    }

    if (recent.value.includes(command.id)) {
        total += 5;
    }

    return total;
};

const matchingCommands = computed<Command[]>(() => {
    if (queryTokens.value.length === 0) return [];

    const seen = new Set<string>();

    return [...menuCommands.value, ...actionCommands.value]
        .map((command) => ({ command, score: scoreCommand(command, queryTokens.value) }))
        .filter((entry) => entry.score > 0)
        .sort((left, right) =>
            right.score - left.score
            || commandLabel(left.command).localeCompare(commandLabel(right.command), currentLocale.value),
        )
        .filter((entry) => {
            if (seen.has(entry.command.id)) return false;

            seen.add(entry.command.id);

            return true;
        })
        .map((entry) => entry.command);
});

const sections = computed<SearchSection[]>(() => {
    if (queryText.value) {
        if (matchingCommands.value.length === 0) return [];

        return [
            {
                key: 'results',
                label: '',
                ariaLabel: strings.value.resultsLabel,
                items: matchingCommands.value,
            },
        ];
    }

    const result: SearchSection[] = [];

    if (recentCommands.value.length > 0) {
        result.push({
            key: 'recent',
            label: strings.value.recent,
            clearable: true,
            items: recentCommands.value,
        });
    }

    result.push({
        key: 'actions',
        label: strings.value.quickActions,
        items: actionCommands.value,
    });

    const recentIds = new Set(recent.value);

    for (const area of props.areas) {
        const items = menuCommands.value.filter(
            (command) => command.areaKey === area.key && !recentIds.has(command.id),
        );

        if (items.length === 0) continue;

        result.push({
            key: `area:${area.key}`,
            label: translateText(area.label, currentLocale.value),
            items,
        });
    }

    return result;
});

const flatCommands = computed<Command[]>(() => sections.value.flatMap((section) => section.items));

const commandIndexes = computed(() => {
    const indexes = new Map<string, number>();

    flatCommands.value.forEach((command, index) => {
        indexes.set(command.id, index);
    });

    return indexes;
});

const activeCommand = computed<Command | null>(() => flatCommands.value[activeIndex.value] ?? null);
const activeDescendant = computed(() => (
    activeIndex.value >= 0 ? `menu-search-option-${activeIndex.value}` : undefined
));

const resultCountLabel = computed(() => strings.value.results(flatCommands.value.length));

const resultStatus = computed(() => {
    if (!open.value) return '';

    if (!queryText.value) return strings.value.availableStatus(flatCommands.value.length);

    return strings.value.resultStatus(flatCommands.value.length, query.value.trim());
});

const persistRecent = () => {
    try {
        window.localStorage.setItem(RECENT_STORAGE_KEY, JSON.stringify(recent.value));
    } catch {
        return;
    }
};

const recordRecent = (command: Command) => {
    if (command.kind !== 'menu') return;

    recent.value = [command.id, ...recent.value.filter((id) => id !== command.id)].slice(0, MAX_RECENT);
    persistRecent();
};

const clearRecent = () => {
    recent.value = [];
    persistRecent();

    nextTick(() => inputRef.value?.focus());
};

const clearQuery = () => {
    query.value = '';

    nextTick(() => inputRef.value?.focus());
};

const scrollActiveIntoView = async () => {
    await nextTick();

    if (!resultsRef.value || activeIndex.value < 0) return;

    resultsRef.value
        .querySelector<HTMLElement>(`[data-command-index="${activeIndex.value}"]`)
        ?.scrollIntoView({ block: 'nearest' });
};

const moveActive = (direction: 1 | -1) => {
    const count = flatCommands.value.length;

    if (count === 0) {
        activeIndex.value = -1;

        return;
    }

    const current = activeIndex.value < 0 ? (direction === 1 ? -1 : 0) : activeIndex.value;
    activeIndex.value = (current + direction + count) % count;

    void scrollActiveIntoView();
};

const openDialog = async () => {
    if (open.value) {
        inputRef.value?.focus();

        return;
    }

    previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    previousBodyOverflow = document.body.style.overflow;
    query.value = '';
    activeIndex.value = 0;
    open.value = true;
    document.body.style.overflow = 'hidden';
    emit('open');

    await nextTick();
    inputRef.value?.focus();
    void scrollActiveIntoView();
};

const closeDialog = (restoreFocus = true) => {
    if (!open.value) return;

    open.value = false;
    document.body.style.overflow = previousBodyOverflow;
    emit('close');

    if (!restoreFocus) return;

    nextTick(() => {
        if (previouslyFocused?.isConnected) {
            previouslyFocused.focus();

            return;
        }

        triggerRef.value?.focus();
    });
};

const selectCommand = (command: Command) => {
    if (command.kind === 'menu') {
        recordRecent(command);
        closeDialog(false);

        if (!command.url) return;

        if (command.inertia) {
            router.visit(command.url, {
                onFinish: () => {
                    document.getElementById('main-content')?.focus();
                },
            });

            return;
        }

        window.location.assign(command.url);

        return;
    }

    closeDialog(true);

    if (command.action === 'toggle-theme') {
        toggleTheme();

        return;
    }

    if (command.action === 'toggle-locale') {
        switchLocale(currentLocale.value === 'id' ? 'en' : 'id');

        return;
    }

    if (command.action === 'reload-page') {
        router.reload();
    }
};

const trapFocus = (event: KeyboardEvent) => {
    const focusable = Array.from(
        dialogRef.value?.querySelectorAll<HTMLElement>('input:not(:disabled), button:not(:disabled), a[href]') ?? [],
    ).filter((element) => element.getClientRects().length > 0);

    if (focusable.length === 0) {
        event.preventDefault();

        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey) {
        if (document.activeElement === first || !dialogRef.value?.contains(document.activeElement)) {
            event.preventDefault();
            last?.focus();
        }

        return;
    }

    if (document.activeElement === last) {
        event.preventDefault();
        first?.focus();
    }
};

const onDialogKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        closeDialog(true);

        return;
    }

    if (event.key === 'Tab') {
        trapFocus(event);

        return;
    }

    if (event.target !== inputRef.value) return;

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        moveActive(event.key === 'ArrowDown' ? 1 : -1);

        return;
    }

    if (event.key === 'Enter' && activeCommand.value) {
        event.preventDefault();
        selectCommand(activeCommand.value);
    }
};

const onGlobalKeydown = (event: KeyboardEvent) => {
    if ((event.ctrlKey || event.metaKey) && !event.altKey && event.key.toLocaleLowerCase('en-US') === 'k') {
        event.preventDefault();

        if (open.value) {
            inputRef.value?.focus();
        } else {
            void openDialog();
        }

        return;
    }

    if (event.key === 'Escape' && open.value) {
        event.preventDefault();
        closeDialog(true);
    }
};

watch(queryText, () => {
    activeIndex.value = flatCommands.value.length > 0 ? 0 : -1;
});

watch(
    () => flatCommands.value.length,
    (length) => {
        if (length === 0) {
            activeIndex.value = -1;

            return;
        }

        if (activeIndex.value < 0 || activeIndex.value >= length) {
            activeIndex.value = 0;
        }
    },
);

watch(isDark, () => {
    if (!open.value) return;

    inputRef.value?.focus();
});

onMounted(() => window.addEventListener('keydown', onGlobalKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onGlobalKeydown);

    if (open.value) {
        document.body.style.overflow = previousBodyOverflow;
    }
});
</script>

<template>
    <button
        ref="triggerRef"
        class="menu-search-trigger"
        type="button"
        aria-haspopup="dialog"
        aria-controls="menu-search-dialog"
        aria-keyshortcuts="Control+K Meta+K"
        :aria-expanded="open"
        :aria-label="`${strings.triggerLabel} (${shortcutHint})`"
        :title="`${strings.triggerLabel} (${shortcutHint})`"
        @click="openDialog"
    >
        <i class="mdi mdi-magnify" aria-hidden="true"></i>
        <span class="menu-search-trigger-label">{{ strings.triggerLabel }}</span>
        <kbd class="menu-search-trigger-kbd" aria-hidden="true">{{ shortcutHint }}</kbd>
    </button>

    <Teleport to="body">
        <Transition name="menu-search">
            <div v-if="open" class="menu-search-overlay" @click.self="closeDialog(true)">
                <section
                    id="menu-search-dialog"
                    ref="dialogRef"
                    class="menu-search-dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="menu-search-title"
                    @keydown="onDialogKeydown"
                >
                    <h2 id="menu-search-title" class="visually-hidden">{{ strings.dialogTitle }}</h2>

                    <div class="menu-search-field">
                        <i class="mdi mdi-magnify menu-search-field-icon" aria-hidden="true"></i>
                        <input
                            id="menu-search-input"
                            ref="inputRef"
                            v-model="query"
                            class="menu-search-input"
                            type="text"
                            role="combobox"
                            autocomplete="off"
                            autocapitalize="off"
                            spellcheck="false"
                            aria-autocomplete="list"
                            aria-labelledby="menu-search-title"
                            :aria-expanded="sections.length > 0"
                            :aria-controls="sections.length > 0 ? 'menu-search-listbox' : undefined"
                            :aria-activedescendant="activeDescendant"
                            :placeholder="strings.placeholder"
                        />
                        <button
                            v-if="query"
                            class="menu-search-icon-button"
                            type="button"
                            :aria-label="strings.clearSearch"
                            @click="clearQuery"
                        >
                            <i class="mdi mdi-close-circle-outline" aria-hidden="true"></i>
                        </button>
                        <button
                            class="menu-search-icon-button menu-search-close"
                            type="button"
                            :aria-label="strings.closeSearch"
                            @click="closeDialog(true)"
                        >
                            <i class="mdi mdi-close" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div
                        v-if="sections.length > 0"
                        id="menu-search-listbox"
                        ref="resultsRef"
                        class="menu-search-results"
                        role="listbox"
                        aria-labelledby="menu-search-title"
                    >
                        <div
                            v-for="section in sections"
                            :key="section.key"
                            class="menu-search-section"
                            role="group"
                            :aria-label="section.ariaLabel ?? section.label"
                        >
                            <div v-if="section.label || section.clearable" class="menu-search-section-head">
                                <span class="menu-search-section-label">{{ section.label }}</span>
                                <button
                                    v-if="section.clearable"
                                    class="menu-search-clear-recent"
                                    type="button"
                                    @click.stop="clearRecent"
                                >
                                    {{ strings.clearRecent }}
                                </button>
                            </div>

                            <div
                                v-for="command in section.items"
                                :id="`menu-search-option-${commandIndexes.get(command.id)}`"
                                :key="command.id"
                                class="menu-search-option"
                                :class="{ 'is-active': commandIndexes.get(command.id) === activeIndex }"
                                :data-command-index="commandIndexes.get(command.id)"
                                role="option"
                                :aria-selected="commandIndexes.get(command.id) === activeIndex"
                                @mouseenter="activeIndex = commandIndexes.get(command.id) ?? activeIndex"
                                @click="selectCommand(command)"
                            >
                                <span class="menu-search-option-icon">
                                    <i class="mdi" :class="command.icon" aria-hidden="true"></i>
                                </span>
                                <span class="menu-search-option-copy">
                                    <strong class="menu-search-option-label">{{ commandLabel(command) }}</strong>
                                    <small class="menu-search-option-hint">{{ commandHint(command) }}</small>
                                </span>
                                <i class="mdi mdi-keyboard-return menu-search-option-enter" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>

                    <div v-else class="menu-search-empty">
                        <span class="menu-search-empty-icon">
                            <i class="mdi mdi-file-search-outline" aria-hidden="true"></i>
                        </span>
                        <strong>{{ strings.emptyTitle }}</strong>
                        <p>{{ strings.emptyBody }}</p>
                        <button v-if="query" class="btn btn-sm btn-outline-primary" type="button" @click="clearQuery">
                            {{ strings.clearSearch }}
                        </button>
                    </div>

                    <footer v-if="sections.length > 0" class="menu-search-footer">
                        <span class="menu-search-hints">
                            <span class="menu-search-hint">
                                <kbd class="menu-search-key">↑</kbd>
                                <kbd class="menu-search-key">↓</kbd>
                                {{ strings.navigate }}
                            </span>
                            <span class="menu-search-hint">
                                <kbd class="menu-search-key">Enter</kbd>
                                {{ strings.open }}
                            </span>
                            <span class="menu-search-hint">
                                <kbd class="menu-search-key">Esc</kbd>
                                {{ strings.close }}
                            </span>
                        </span>
                        <span class="menu-search-count">{{ resultCountLabel }}</span>
                    </footer>

                    <p class="visually-hidden" role="status" aria-live="polite">{{ resultStatus }}</p>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.menu-search-trigger {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 38px;
    height: 38px;
    padding: 0.35rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-md);
    background: var(--app-surface);
    color: var(--app-muted);
    font: inherit;
    font-size: 0.8rem;
    cursor: pointer;
    transition: background-color var(--app-dur-fast) var(--app-ease-out), border-color var(--app-dur-fast) var(--app-ease-out), color var(--app-dur-fast) var(--app-ease-out);
}

.menu-search-trigger:hover,
.menu-search-trigger:focus-visible {
    border-color: var(--app-focus);
    background: var(--app-surface-2);
    color: var(--app-ink);
}

.menu-search-trigger > i {
    font-size: 1.15rem;
}

.menu-search-trigger-label,
.menu-search-trigger-kbd {
    display: none;
}

.menu-search-trigger-kbd,
.menu-search-key {
    align-items: center;
    justify-content: center;
    min-width: 1.5rem;
    padding: 0.1rem 0.3rem;
    border: 1px solid var(--app-border);
    border-bottom-width: 2px;
    border-radius: var(--app-radius-xs);
    background: var(--app-surface);
    color: var(--app-muted);
    font-family: inherit;
    font-size: 0.66rem;
    font-weight: 700;
    line-height: 1.3;
}

.menu-search-key {
    display: inline-flex;
}

.menu-search-overlay {
    position: fixed;
    inset: 0;
    z-index: 1100;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: clamp(3rem, 12vh, 7rem) 1rem 1rem;
    background: var(--app-scrim);
}

.menu-search-dialog {
    display: flex;
    flex-direction: column;
    width: min(640px, 100%);
    max-height: min(620px, calc(100dvh - 8rem));
    overflow: hidden;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-2xl);
    background: var(--app-surface);
    color: var(--app-ink);
    box-shadow: var(--app-shadow-overlay);
}

.menu-search-field {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 0.6rem 0.55rem 0.85rem;
    border-bottom: 1px solid var(--app-border);
}

.menu-search-field-icon {
    flex: 0 0 auto;
    color: var(--app-accent-ink);
    font-size: 1.2rem;
}

.menu-search-input {
    flex: 1;
    min-width: 0;
    height: 2.4rem;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--app-ink);
    font: inherit;
    font-size: 0.98rem;
}

.menu-search-input::placeholder {
    color: var(--app-muted);
}

.menu-search-input:focus-visible {
    border-radius: var(--app-radius-xs);
    outline: 2px solid var(--app-focus);
    outline-offset: 2px;
}

.menu-search-icon-button {
    display: grid;
    flex: 0 0 auto;
    place-items: center;
    width: 34px;
    height: 34px;
    border: 1px solid transparent;
    border-radius: var(--app-radius-sm);
    background: transparent;
    color: var(--app-muted);
    font-size: 1.05rem;
    cursor: pointer;
}

.menu-search-icon-button:hover,
.menu-search-icon-button:focus-visible {
    border-color: var(--app-border);
    background: var(--app-surface-2);
    color: var(--app-ink);
}

.menu-search-results {
    flex: 1 1 auto;
    min-height: 0;
    padding: 0.35rem;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
}

.menu-search-section + .menu-search-section {
    margin-top: 0.2rem;
}

.menu-search-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.5rem 0.65rem 0.25rem;
}

.menu-search-section-label {
    color: var(--app-muted);
    font-size: 0.7rem;
    font-weight: 700;
}

.menu-search-clear-recent {
    padding: 0.2rem 0.35rem;
    border: 0;
    border-radius: var(--app-radius-xs);
    background: transparent;
    color: var(--app-accent-ink);
    font: inherit;
    font-size: 0.68rem;
    font-weight: 700;
    cursor: pointer;
}

.menu-search-clear-recent:hover,
.menu-search-clear-recent:focus-visible {
    background: var(--app-accent-soft);
}

.menu-search-option {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) 1.25rem;
    align-items: center;
    gap: 0.65rem;
    min-height: 52px;
    padding: 0.45rem 0.6rem;
    border-radius: var(--app-radius-md);
    cursor: pointer;
}

.menu-search-option + .menu-search-option {
    margin-top: 0.1rem;
}

.menu-search-option:hover {
    background: var(--app-surface-2);
}

.menu-search-option.is-active {
    background: var(--app-accent-soft);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--app-accent) 35%, var(--app-border));
}

.menu-search-option-icon {
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border-radius: var(--app-radius-sm);
    background: var(--app-surface-2);
    color: var(--app-accent-ink);
    font-size: 1.15rem;
}

.menu-search-option.is-active .menu-search-option-icon {
    background: var(--app-surface);
}

.menu-search-option-copy {
    min-width: 0;
}

.menu-search-option-label {
    display: block;
    color: var(--app-ink);
    font-size: 0.86rem;
    font-weight: 600;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.menu-search-option.is-active .menu-search-option-label {
    color: var(--app-accent-ink);
}

.menu-search-option-hint {
    display: block;
    margin-top: 0.1rem;
    color: var(--app-muted);
    font-size: 0.72rem;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.menu-search-option-enter {
    color: var(--app-accent-ink);
    font-size: 1rem;
    opacity: 0;
}

.menu-search-option.is-active .menu-search-option-enter {
    opacity: 1;
}

.menu-search-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding: 2.5rem 1.5rem;
    text-align: center;
}

.menu-search-empty-icon {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    margin-bottom: 0.25rem;
    border-radius: var(--app-radius-lg);
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 1.5rem;
}

.menu-search-empty strong {
    font-size: 0.95rem;
}

.menu-search-empty p {
    max-width: 32ch;
    margin: 0;
    color: var(--app-muted);
    font-size: 0.82rem;
}

.menu-search-empty .btn {
    margin-top: 0.5rem;
}

.menu-search-footer {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 1rem;
    padding: 0.55rem 0.75rem;
    border-top: 1px solid var(--app-border);
    color: var(--app-muted);
    font-size: 0.7rem;
}

.menu-search-hints {
    display: flex;
    flex-wrap: wrap;
    gap: 0.9rem;
}

.menu-search-hint {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.menu-search-count {
    margin-left: auto;
    font-weight: 600;
    white-space: nowrap;
}

.menu-search-enter-active,
.menu-search-leave-active {
    transition: opacity var(--app-dur-base) var(--app-ease-out);
}

.menu-search-enter-active .menu-search-dialog,
.menu-search-leave-active .menu-search-dialog {
    transition: opacity var(--app-dur-base) var(--app-ease-out), transform var(--app-dur-base) var(--app-ease-out);
}

.menu-search-enter-from,
.menu-search-leave-to {
    opacity: 0;
}

.menu-search-enter-from .menu-search-dialog,
.menu-search-leave-to .menu-search-dialog {
    opacity: 0;
    transform: translateY(-4px);
}

@media (min-width: 1200px) {
    .menu-search-trigger {
        justify-content: flex-start;
        width: clamp(190px, 18vw, 260px);
        padding: 0.35rem 0.45rem 0.35rem 0.7rem;
    }

    .menu-search-trigger-label {
        display: block;
        min-width: 0;
        overflow: hidden;
        color: inherit;
        font-weight: 500;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .menu-search-trigger-kbd {
        display: inline-flex;
        flex: 0 0 auto;
        margin-left: auto;
    }
}

@media (max-width: 767.98px) {
    .menu-search-hints {
        display: none;
    }

    .menu-search-input {
        font-size: 1rem;
    }
}

@media (max-width: 575.98px) {
    .menu-search-trigger {
        width: 44px;
        height: 44px;
    }

    .menu-search-overlay {
        padding: 0.5rem;
    }

    .menu-search-dialog {
        max-height: calc(100dvh - 1rem);
        border-radius: var(--app-radius-lg);
    }

    .menu-search-field {
        padding: 0.45rem 0.5rem 0.45rem 0.65rem;
    }

    .menu-search-icon-button {
        width: 44px;
        height: 44px;
    }

    .menu-search-option {
        min-height: 56px;
    }

    .menu-search-footer {
        padding-bottom: calc(0.55rem + env(safe-area-inset-bottom));
    }
}

@media (pointer: coarse) {
    .menu-search-trigger {
        min-width: 44px;
        min-height: 44px;
    }

    .menu-search-icon-button {
        width: 44px;
        height: 44px;
    }
}

@media (pointer: coarse) and (max-width: 1199.98px) {
    .menu-search-trigger {
        width: 44px;
        height: 44px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .menu-search-trigger,
    .menu-search-enter-active,
    .menu-search-leave-active,
    .menu-search-enter-active .menu-search-dialog,
    .menu-search-leave-active .menu-search-dialog {
        transition: none;
    }
}
</style>
