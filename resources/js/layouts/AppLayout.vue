<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';
import VeeAssistantWidget from '@/components/assistant/VeeAssistantWidget.vue';
import AppToastHost from '@/components/ui/AppToastHost.vue';
import MenuSearch from '@/components/MenuSearch.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { currentLocale, switchLocale as changeLocale } from '@/i18n';

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

interface Navigation {
    areas: NavArea[];
}

interface BreadcrumbItem {
    key: string;
    label: string;
    url: string | null;
    current: boolean;
}

interface Branding {
    name: string;
    tagline: string;
    logo_url: string;
}

interface Props {
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: undefined,
});

const page = usePage();
const { isDark, toggleTheme } = useTheme();
const locale = currentLocale;
const localeOptions = [
    { value: 'id', label: 'ID' },
    { value: 'en', label: 'EN' },
];

const switchLocale = (value: string) => {
    if (value === locale.value) return;

    changeLocale(value);
};

const navigation = computed<Navigation>(() => (page.props.navigation ?? { areas: [] }) as Navigation);
const notificationSummaryUrl = computed(
    () => (page.props.notificationSummaryUrl as string | undefined) ?? '/notifications/summary',
);
const branding = computed<Branding>(() => (
    page.props.branding ?? {
        name: 'VeeStore',
        tagline: 'Master Data & Report',
        logo_url: '/assets/img/logo.png',
    }
) as Branding);
const user = computed(() => (page.props.auth as { user?: { name?: string; email?: string } } | undefined)?.user);

const currentPath = computed(() => page.url.split('?')[0]);
const isReportsArea = computed(() => {
    const path = currentPath.value;

    return (
        path === '/reports' ||
        path.startsWith('/reports/') ||
        path === '/dashboard' ||
        path.startsWith('/monitoring') ||
        path.startsWith('/report-exports') ||
        path.startsWith('/ai-assistant')
    );
});

const activeAreaKey = computed(() => (isReportsArea.value ? 'reports' : 'operations'));
const activeArea = computed(() => navigation.value.areas.find((area) => area.key === activeAreaKey.value));

const goBack = () => {
    if (typeof window === 'undefined') return;

    if (hasInternalHistory.value && window.history.length > 1) {
        window.history.back();
        return;
    }

    router.visit(activeArea.value?.url ?? '/');
};

const SIDEBAR_STORAGE_KEY = 'veestore.sidebar.collapsed';

const readSidebarCollapsed = () => {
    if (typeof window === 'undefined') return false;

    try {
        return window.localStorage.getItem(SIDEBAR_STORAGE_KEY) === '1';
    } catch {
        return false;
    }
};

const openGroups = ref<Record<string, boolean>>({});
const mobileOpen = ref(false);
const userMenuOpen = ref(false);
const hasInternalHistory = ref(false);

watch(() => page.url, () => {
    hasInternalHistory.value = true;
});
const userMenuRef = ref<HTMLElement | null>(null);
const userMenuButtonRef = ref<HTMLButtonElement | null>(null);
const mobileMenuButtonRef = ref<HTMLButtonElement | null>(null);
const mobileCloseButtonRef = ref<HTMLButtonElement | null>(null);
const mobileSidebarRef = ref<HTMLElement | null>(null);
const pageHasHeading = ref(false);
const focusMainAfterNavigation = ref(false);
const isLoggingOut = ref(false);
const isNavigating = ref(false);
const sidebarCollapsed = ref(readSidebarCollapsed());
const isDesktopViewport = ref(
    typeof window !== 'undefined'
        ? window.matchMedia('(min-width: 992px)').matches
        : false,
);
const breadcrumbMenuOpen = ref(false);

const isSidebarCollapsed = computed(() => isDesktopViewport.value && sidebarCollapsed.value);

const pathOf = (url: string | null) => {
    if (!url) return '';
    try {
        return new URL(url, window.location.origin).pathname.replace(/\/$/, '') || '/';
    } catch {
        return url;
    }
};

const pathMatchScore = (item: NavItem) => {
    const path = pathOf(item.url);
    if (!path) return -1;
    if (path === '/') return currentPath.value === '/' ? 100000 : -1;
    if (currentPath.value === path) return 100000 + path.length;
    if (currentPath.value.startsWith(`${path}/`)) return path.length;

    return -1;
};

const activeItem = computed(() => {
    let best: NavItem | null = null;
    let bestScore = -1;

    for (const area of navigation.value.areas) {
        for (const group of area.groups) {
            for (const item of group.items) {
                const score = pathMatchScore(item);

                if (score > bestScore) {
                    best = item;
                    bestScore = score;
                }
            }
        }
    }

    return best;
});

const isActive = (item: NavItem) => activeItem.value?.route === item.route;

const activeGroup = computed(() => {
    for (const area of navigation.value.areas) {
        for (const group of area.groups) {
            if (group.items.some((item) => isActive(item))) {
                return group;
            }
        }
    }

    return null;
});

const headTitle = ref('');

const syncHeadTitle = () => {
    headTitle.value = document.title.replace(/\s*-\s*VeeStore$/, '').trim();
};

const syncPageHeading = () => {
    pageHasHeading.value = Boolean(document.querySelector('#main-content h1'));
};

const pageTitle = computed(
    () =>
        props.title ??
        activeItem.value?.label ??
        (headTitle.value && headTitle.value !== 'VeeStore Master Data & Report' ? headTitle.value : null) ??
        activeArea.value?.label ??
        'VeeStore',
);

const normalizedHeadTitle = computed(() => {
    const title = headTitle.value.trim();

    if (!title) return '';

    return title.split(' - ')[0].trim();
});

const isExactNavPage = computed(() => {
    return Boolean(
        activeItem.value
        && pathOf(activeItem.value.url) === currentPath.value,
    );
});

const currentPageLabel = computed(() => {
    if (normalizedHeadTitle.value) {
        return normalizedHeadTitle.value;
    }

    return activeItem.value?.label ?? activeArea.value?.label ?? 'VeeStore';
});

const breadcrumbItems = computed<BreadcrumbItem[]>(() => {
    const items: BreadcrumbItem[] = [];

    items.push({
        key: 'area',
        label: activeArea.value?.label ?? 'VeeStore',
        url: activeArea.value?.url ?? null,
        current: false,
    });

    if (activeGroup.value) {
        items.push({
            key: 'group',
            label: activeGroup.value.label,
            url: null,
            current: false,
        });
    }

    if (activeItem.value && !isExactNavPage.value) {
        items.push({
            key: 'item',
            label: activeItem.value.label,
            url: activeItem.value.url,
            current: false,
        });
    }

    const currentLabel = currentPageLabel.value;
    const last = items[items.length - 1];

    if (last && last.label.toLowerCase() === currentLabel.toLowerCase()) {
        last.current = true;
        last.url = null;
    } else {
        items.push({
            key: 'current',
            label: currentLabel,
            url: null,
            current: true,
        });
    }

    return items;
});

const groupsForArea = computed(() => {
    const area = activeArea.value;
    return area?.groups ?? [];
});

const groupContentId = (label: string) => `app-nav-group-${label.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;

const toggleGroup = (label: string, group: NavGroup) => {
    if (!(label in openGroups.value)) {
        openGroups.value[label] = group.items.some((item) => isActive(item)) || group.items.length <= 4;
    }
    openGroups.value[label] = !openGroups.value[label];
};

const isGroupOpen = (group: NavGroup) => {
    if (group.label in openGroups.value) return openGroups.value[group.label];
    return group.items.some((item) => isActive(item)) || group.items.length <= 4;
};

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

const toggleBreadcrumbMenu = async () => {
    breadcrumbMenuOpen.value = !breadcrumbMenuOpen.value;

    if (!breadcrumbMenuOpen.value) return;

    await nextTick();
    document.querySelector<HTMLElement>('.app-breadcrumb-menu-item')?.focus();
};

const closeBreadcrumbMenu = (restoreFocus = false) => {
    if (!breadcrumbMenuOpen.value) return;

    if (restoreFocus) {
        document.querySelector<HTMLButtonElement>('.app-page-breadcrumb-menu-button')?.focus();
    }

    breadcrumbMenuOpen.value = false;
};

const onBreadcrumbMenuFocusOut = (event: FocusEvent) => {
    const nextTarget = event.relatedTarget as Element | null;

    if (!nextTarget?.closest('.app-breadcrumb-menu-wrap')) {
        breadcrumbMenuOpen.value = false;
    }
};

const onBreadcrumbMenuKeydown = (event: KeyboardEvent) => {
    const keys = ['ArrowDown', 'ArrowUp', 'Home', 'End'];
    if (!keys.includes(event.key)) return;

    const items = Array.from(document.querySelectorAll<HTMLElement>('.app-breadcrumb-menu-item'));
    if (items.length === 0) return;

    event.preventDefault();

    const currentIndex = items.indexOf(document.activeElement as HTMLElement);

    if (event.key === 'Home') {
        items[0]?.focus();
        return;
    }

    if (event.key === 'End') {
        items[items.length - 1]?.focus();
        return;
    }

    if (event.key === 'ArrowDown') {
        items[(currentIndex + 1) % items.length]?.focus();
        return;
    }

    items[(currentIndex - 1 + items.length) % items.length]?.focus();
};

const openMobile = async () => {
    mobileOpen.value = true;
    await nextTick();
    mobileCloseButtonRef.value?.focus();
};

const closeMobile = (restoreFocus = true) => {
    const wasOpen = mobileOpen.value;
    mobileOpen.value = false;
    userMenuOpen.value = false;

    if (wasOpen && restoreFocus) {
        nextTick(() => mobileMenuButtonRef.value?.focus());
    }
};

const closeMobileForNavigation = () => {
    focusMainAfterNavigation.value = mobileOpen.value && !isDesktopViewport.value;
    closeMobile(false);
};

const onMenuSearchOpen = () => {
    closeUserMenu();
    breadcrumbMenuOpen.value = false;

    if (mobileOpen.value) {
        closeMobile(false);
    }
};

const closeUserMenu = (restoreFocus = false) => {
    if (!userMenuOpen.value) return;
    userMenuOpen.value = false;

    if (restoreFocus) {
        nextTick(() => userMenuButtonRef.value?.focus());
    }
};

const logout = () => {
    if (isLoggingOut.value) return;

    isLoggingOut.value = true;
    closeUserMenu();
    router.post('/logout', {}, {
        onFinish: () => {
            isLoggingOut.value = false;
        },
    });
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Tab' && mobileOpen.value && !isDesktopViewport.value) {
        const focusable = Array.from(
            mobileSidebarRef.value?.querySelectorAll<HTMLElement>('a[href], button:not(:disabled)') ?? [],
        ).filter((element) => element.getClientRects().length > 0);
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
        return;
    }

    if (event.key !== 'Escape') return;

    if (breadcrumbMenuOpen.value) {
        closeBreadcrumbMenu(true);
        return;
    }

    if (userMenuOpen.value) {
        closeUserMenu(true);
        return;
    }

    if (mobileOpen.value) {
        closeMobile(true);
    }
};

const onDocumentClick = (event: MouseEvent) => {
    const target = event.target as Element | null;

    if (breadcrumbMenuOpen.value && !target?.closest('.app-breadcrumb-menu-wrap')) {
        breadcrumbMenuOpen.value = false;
    }

    if (!userMenuOpen.value) return;

    if (userMenuRef.value && !userMenuRef.value.contains(event.target as Node)) {
        userMenuOpen.value = false;
    }
};

const onStorage = (event: StorageEvent) => {
    if (event.key === SIDEBAR_STORAGE_KEY) {
        sidebarCollapsed.value = event.newValue === '1';
    }
};

const onDesktopMediaChange = (event: MediaQueryListEvent) => {
    const wasMobileOpen = mobileOpen.value;
    isDesktopViewport.value = event.matches;

    if (event.matches) {
        mobileOpen.value = false;

        if (wasMobileOpen) {
            nextTick(() => document.querySelector<HTMLElement>('#main-content')?.focus());
        }
    }
};

const onNavigationStart = () => {
    isNavigating.value = true;
    breadcrumbMenuOpen.value = false;
};

const onNavigationFinish = async () => {
    isNavigating.value = false;
    await nextTick();
    syncPageHeading();

    if (focusMainAfterNavigation.value) {
        document.querySelector<HTMLElement>('#main-content')?.focus();
        focusMainAfterNavigation.value = false;
    }
};

watch(mobileOpen, (open) => {
    document.documentElement.classList.toggle('inertia-sidebar-open', open);
});

watch(sidebarCollapsed, (collapsed) => {
    try {
        window.localStorage.setItem(SIDEBAR_STORAGE_KEY, collapsed ? '1' : '0');
    } catch {
        return;
    }
});

let titleObserver: MutationObserver | null = null;
let desktopMediaQuery: MediaQueryList | null = null;

onMounted(() => {
    syncHeadTitle();
    syncPageHeading();
    titleObserver = new MutationObserver(syncHeadTitle);
    titleObserver.observe(document.head, { childList: true, subtree: true, characterData: true });
    desktopMediaQuery = window.matchMedia('(min-width: 992px)');
    isDesktopViewport.value = desktopMediaQuery.matches;
    desktopMediaQuery.addEventListener('change', onDesktopMediaChange);
    window.addEventListener('storage', onStorage);
    window.addEventListener('keydown', onKeydown);
    document.addEventListener('click', onDocumentClick);
    removeNavigationStartListener = router.on('start', onNavigationStart);
    removeNavigationFinishListener = router.on('finish', onNavigationFinish);
});

let removeNavigationStartListener: (() => void) | undefined;
let removeNavigationFinishListener: (() => void) | undefined;

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('storage', onStorage);
    desktopMediaQuery?.removeEventListener('change', onDesktopMediaChange);
    document.removeEventListener('click', onDocumentClick);
    removeNavigationStartListener?.();
    removeNavigationFinishListener?.();
    titleObserver?.disconnect();
    document.documentElement.classList.remove('inertia-sidebar-open');
});
</script>

<template>
    <div class="app-shell" :class="{ 'is-sidebar-collapsed': isSidebarCollapsed }">
        <div v-if="isNavigating" class="app-progress" role="progressbar" aria-label="Memuat halaman"></div>

        <a href="#main-content" class="skip-link">Lewati ke konten</a>

        <div v-if="mobileOpen" class="app-overlay" aria-hidden="true" @click="closeMobile()"></div>

        <aside
            ref="mobileSidebarRef"
            id="app-sidebar"
            class="app-sidebar"
            :class="{ 'is-open': mobileOpen, 'is-collapsed': isSidebarCollapsed }"
            :role="mobileOpen && !isDesktopViewport ? 'dialog' : undefined"
            :aria-modal="mobileOpen && !isDesktopViewport ? 'true' : undefined"
            :aria-hidden="!isDesktopViewport && !mobileOpen ? 'true' : undefined"
            :inert="!isDesktopViewport && !mobileOpen"
            aria-label="Navigasi utama"
        >
            <div class="app-brand">
                <img
                    v-if="!isSidebarCollapsed"
                    :src="branding.logo_url"
                    :alt="branding.name"
                    class="app-brand-logo"
                />
                <div v-else class="app-mark" :title="`${branding.name} ${branding.tagline}`">
                    <img :src="branding.logo_url" :alt="branding.name" class="app-mark-logo" />
                </div>
                <div class="app-brand-copy">
                    <strong>{{ branding.tagline }}</strong>
                </div>
                <button ref="mobileCloseButtonRef" class="app-close d-lg-none" type="button" aria-label="Tutup menu" @click="closeMobile()">
                    <i class="mdi mdi-close" aria-hidden="true"></i>
                </button>
            </div>

            <nav class="app-areas" aria-label="Area aplikasi">
                <template v-for="area in navigation.areas" :key="area.key">
                    <Link
                        v-if="area.url"
                        :href="area.url"
                        class="app-area"
                        :class="{ 'is-active': activeAreaKey === area.key }"
                        :aria-current="activeAreaKey === area.key ? 'location' : undefined"
                        :aria-label="isSidebarCollapsed ? area.label : undefined"
                        :title="isSidebarCollapsed ? area.label : undefined"
                        @click="closeMobileForNavigation"
                    >
                        <i class="mdi" :class="area.icon" aria-hidden="true"></i>
                        <span>
                            <strong>{{ area.label }}</strong>
                            <small>{{ area.description }}</small>
                        </span>
                    </Link>
                </template>
            </nav>

            <div class="app-nav-scroll">
                <div
                    v-for="group in groupsForArea"
                    :key="group.label"
                    class="app-group"
                    :class="{ 'is-active': group.items.some((item) => isActive(item)) }"
                    :role="isSidebarCollapsed ? 'group' : undefined"
                    :aria-label="isSidebarCollapsed ? group.label : undefined"
                >
                    <div v-if="isSidebarCollapsed" class="app-group-rail" :title="group.label" aria-hidden="true">
                        <i class="mdi" :class="group.icon"></i>
                    </div>
                    <button
                        v-else
                        class="app-group-toggle"
                        type="button"
                        :aria-controls="groupContentId(group.label)"
                        :aria-expanded="isGroupOpen(group)"
                        @click="toggleGroup(group.label, group)"
                    >
                        <span><i class="mdi" :class="group.icon" aria-hidden="true"></i> {{ group.label }}</span>
                        <i class="mdi" :class="isGroupOpen(group) ? 'mdi-chevron-up' : 'mdi-chevron-down'" aria-hidden="true"></i>
                    </button>

                    <div :id="groupContentId(group.label)" v-show="isSidebarCollapsed || isGroupOpen(group)" class="app-group-items">
                        <template v-for="item in group.items" :key="item.route">
                            <Link
                                v-if="item.inertia && item.url"
                                :href="item.url"
                                class="app-item"
                                :class="{ 'is-active': isActive(item) }"
                                :aria-current="isActive(item) ? 'page' : undefined"
                                :aria-label="isSidebarCollapsed ? item.label : undefined"
                                :title="isSidebarCollapsed ? item.label : undefined"
                                @click="closeMobileForNavigation"
                            >
                                <i class="mdi" :class="item.icon" aria-hidden="true"></i>
                                <span class="app-item-label">{{ item.label }}</span>
                            </Link>
                            <a
                                v-else-if="item.url"
                                :href="item.url"
                                class="app-item"
                                :class="{ 'is-active': isActive(item) }"
                                :aria-current="isActive(item) ? 'page' : undefined"
                                :aria-label="isSidebarCollapsed ? item.label : undefined"
                                :title="isSidebarCollapsed ? item.label : undefined"
                                @click="closeMobileForNavigation"
                            >
                                <i class="mdi" :class="item.icon" aria-hidden="true"></i>
                                <span class="app-item-label">{{ item.label }}</span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </aside>

        <div class="app-main" :inert="mobileOpen && !isDesktopViewport">
            <header class="app-topbar">
                <div class="d-flex align-items-center gap-2">
                    <button
                        ref="mobileMenuButtonRef"
                        class="app-menu-button d-lg-none"
                        type="button"
                        aria-label="Buka menu navigasi"
                        aria-controls="app-sidebar"
                        :aria-expanded="mobileOpen"
                        @click="openMobile"
                    >
                        <i class="mdi mdi-menu" aria-hidden="true"></i>
                    </button>
                    <button
                        class="app-back-button"
                        type="button"
                        aria-label="Kembali ke halaman sebelumnya"
                        title="Kembali"
                        @click="goBack"
                    >
                        <i class="mdi mdi-arrow-left" aria-hidden="true"></i>
                        <span class="d-none d-md-inline">Kembali</span>
                    </button>
                    <h1 v-if="!pageHasHeading" class="app-title">{{ pageTitle }}</h1>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button
                        class="app-sidebar-toggle d-none d-lg-inline-flex"
                        type="button"
                        aria-controls="app-sidebar"
                        :aria-expanded="!sidebarCollapsed"
                        :aria-label="sidebarCollapsed ? 'Perbesar sidebar' : 'Perkecil sidebar'"
                        :title="sidebarCollapsed ? 'Perbesar sidebar' : 'Perkecil sidebar'"
                        @click="toggleSidebar"
                    >
                        <i class="mdi mdi-page-layout-sidebar-left" aria-hidden="true"></i>
                    </button>

                    <MenuSearch :areas="navigation.areas" @open="onMenuSearchOpen" />

                    <div class="locale-toggle" role="group" aria-label="Bahasa antarmuka">
                        <button
                            v-for="option in localeOptions"
                            :key="option.value"
                            type="button"
                            :class="{ 'is-active': locale === option.value }"
                            :aria-pressed="locale === option.value"
                            :title="option.value === 'id' ? 'Bahasa Indonesia' : 'English'"
                            @click="switchLocale(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <NotificationBell :summary-url="notificationSummaryUrl" />

                    <button
                        class="theme-toggle"
                        type="button"
                        :aria-label="isDark ? 'Aktifkan light mode' : 'Aktifkan dark mode'"
                        :title="isDark ? 'Light mode' : 'Dark mode'"
                        @click="toggleTheme"
                    >
                        <i class="mdi" :class="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'" aria-hidden="true"></i>
                    </button>

                    <div ref="userMenuRef" class="app-user">
                    <button ref="userMenuButtonRef" class="app-user-button" type="button" aria-controls="app-user-menu" aria-haspopup="menu" :aria-expanded="userMenuOpen" @click.stop="userMenuOpen = !userMenuOpen">
                        <span class="app-avatar" aria-hidden="true">{{ (user?.name ?? 'U').slice(0, 1).toUpperCase() }}</span>
                        <span class="d-none d-md-block text-start">
                            <strong>{{ user?.name ?? 'User' }}</strong>
                            <small>{{ user?.email ?? '' }}</small>
                        </span>
                        <span class="visually-hidden">Menu akun</span>
                        <i class="mdi mdi-chevron-down d-none d-md-block" aria-hidden="true"></i>
                    </button>

                    <div v-show="userMenuOpen" id="app-user-menu" class="app-user-menu" role="group" aria-label="Menu akun">
                        <div class="app-user-locale d-sm-none" role="group" aria-label="Bahasa antarmuka">
                            <button
                                v-for="option in localeOptions"
                                :key="`user-locale-${option.value}`"
                                type="button"
                                :class="{ 'is-active': locale === option.value }"
                                :aria-pressed="locale === option.value"
                                :title="option.value === 'id' ? 'Bahasa Indonesia' : 'English'"
                                @click="switchLocale(option.value)"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                        <button type="button" :disabled="isLoggingOut" @click="logout">
                            <i class="mdi mdi-logout me-1" aria-hidden="true"></i>
                            {{ isLoggingOut ? 'Keluar...' : 'Keluar' }}
                        </button>
                    </div>
                    </div>
                </div>
            </header>

            <main id="main-content" class="app-content" tabindex="-1">
                <nav
                    v-if="breadcrumbItems.length > 0"
                    class="app-page-breadcrumb"
                    :class="{ 'is-primary-nav': isSidebarCollapsed }"
                    aria-label="Breadcrumb"
                >
                    <ol class="app-page-breadcrumb-list">
                        <li v-for="(item, index) in breadcrumbItems" :key="item.key" class="app-page-breadcrumb-item">
                            <div
                                v-if="item.key === 'group' && activeGroup && activeGroup.items.length > 1"
                                class="app-breadcrumb-menu-wrap"
                            >
                                <button
                                    class="app-page-breadcrumb-link app-page-breadcrumb-menu-button"
                                    type="button"
                                    aria-haspopup="menu"
                                    :aria-expanded="breadcrumbMenuOpen"
                                    :aria-label="`Buka menu ${item.label}`"
                                    @click.stop="toggleBreadcrumbMenu"
                                >
                                    {{ item.label }}
                                    <i class="mdi mdi-chevron-down" aria-hidden="true"></i>
                                </button>
                                <div
                                    v-if="breadcrumbMenuOpen"
                                    class="app-breadcrumb-menu"
                                    role="menu"
                                    :aria-label="`Menu ${item.label}`"
                                    @keydown="onBreadcrumbMenuKeydown"
                                    @focusout="onBreadcrumbMenuFocusOut"
                                >
                                    <Link
                                        v-for="groupItem in activeGroup.items"
                                        :key="groupItem.route"
                                        :href="groupItem.url ?? undefined"
                                        class="app-breadcrumb-menu-item"
                                        :class="{ 'is-active': isActive(groupItem) }"
                                        role="menuitem"
                                        @click="closeBreadcrumbMenu()"
                                    >
                                        <i class="mdi" :class="groupItem.icon" aria-hidden="true"></i>
                                        <span>{{ groupItem.label }}</span>
                                        <i v-if="isActive(groupItem)" class="mdi mdi-check app-breadcrumb-menu-check" aria-hidden="true"></i>
                                    </Link>
                                </div>
                            </div>
                            <Link
                                v-else-if="item.url && !item.current"
                                :href="item.url"
                                class="app-page-breadcrumb-link"
                            >
                                {{ item.label }}
                            </Link>
                            <span
                                v-else
                                class="app-page-breadcrumb-text"
                                :aria-current="item.current ? 'page' : undefined"
                            >
                                {{ item.label }}
                            </span>
                            <i
                                v-if="index < breadcrumbItems.length - 1"
                                class="mdi mdi-chevron-right app-page-breadcrumb-separator"
                                aria-hidden="true"
                            ></i>
                        </li>
                    </ol>
                </nav>

                <slot />
            </main>
        </div>

        <VeeAssistantWidget :inert="mobileOpen && !isDesktopViewport" :aria-hidden="mobileOpen && !isDesktopViewport ? 'true' : undefined" />
        <AppToastHost />
    </div>
</template>

<style scoped>
.app-shell {
    min-height: 100vh;
    min-height: 100dvh;
    background: var(--app-bg);
    color: var(--app-ink);
    font-family: var(--app-font-sans);
}

.app-progress {
    position: fixed;
    inset: 0 0 auto 0;
    z-index: 90;
    height: 3px;
    background: var(--app-accent);
    background-size: 40% 100%;
    background-repeat: no-repeat;
    animation: app-progress-slide 900ms ease-in-out infinite;
}

@keyframes app-progress-slide {
    from {
        background-position: -40% 0;
    }
    to {
        background-position: 140% 0;
    }
}

.skip-link {
    position: fixed;
    z-index: 100;
    top: -100px;
    left: 1rem;
    padding: 0.6rem 1rem;
    border-radius: 0.5rem;
    background: var(--app-accent);
    color: #fff;
    font-weight: 700;
    text-decoration: none;
}

.skip-link:focus {
    top: 1rem;
}

.app-sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 40;
    display: flex;
    flex-direction: column;
    width: min(var(--app-sidebar-w), 86vw);
    padding-bottom: env(safe-area-inset-bottom);
    background: var(--app-sidebar-bg);
    border-right: 1px solid var(--app-sidebar-border);
    color: var(--app-sidebar-ink);
    overscroll-behavior: contain;
    transform: translateX(-100%);
    transition: transform var(--app-dur-base) var(--app-ease-out), width var(--app-dur-slow) var(--app-ease-in-out);
}

.app-sidebar.is-open {
    transform: translateX(0);
}

.app-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.95rem 1rem;
    border-bottom: 1px solid var(--app-sidebar-border);
}

.app-brand-logo {
    display: block;
    width: 92px;
    height: auto;
    flex: 0 0 auto;
}

.app-mark {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    overflow: hidden;
    border: 1px solid var(--app-sidebar-border);
    border-radius: 14px;
    background: var(--app-sidebar-soft);
}

.app-mark-logo {
    display: block;
    width: 46px;
    height: 24px;
    object-fit: cover;
    object-position: top center;
}

.app-brand-copy {
    min-width: 0;
    padding-left: 0.75rem;
    border-left: 1px solid var(--app-sidebar-border);
}

.app-brand-copy strong {
    display: block;
    color: var(--app-sidebar-muted);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.app-close {
    margin-left: auto;
    padding: 0.35rem;
    border: 0;
    background: transparent;
    color: var(--app-sidebar-ink);
    font-size: 1.2rem;
}

.app-areas {
    display: grid;
    gap: 0.5rem;
    padding: 0.85rem;
}

.app-area {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.65rem 0.75rem;
    border: 1px solid var(--app-sidebar-border);
    border-radius: 0.5rem;
    color: var(--app-sidebar-ink);
    text-decoration: none;
    transition: background-color var(--app-dur-fast) var(--app-ease-out), border-color var(--app-dur-fast) var(--app-ease-out), color var(--app-dur-fast) var(--app-ease-out);
}

.app-area:hover,
.app-area:focus-visible {
    border-color: var(--app-accent);
    background: var(--app-sidebar-soft);
}

.app-area.is-active {
    border-color: var(--app-accent);
    background: var(--app-sidebar-active-bg);
    color: var(--app-sidebar-active-ink);
}

.app-area i {
    font-size: 1.35rem;
    color: var(--app-sidebar-muted);
}

.app-area.is-active i {
    color: var(--app-accent-ink);
}

.app-area strong {
    display: block;
    font-size: 0.88rem;
}

.app-area small {
    display: block;
    color: var(--app-sidebar-muted);
    font-size: 0.74rem;
}

.app-nav-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 0 0.85rem 1rem;
}

.app-group {
    margin-top: 0.6rem;
}

.app-group-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.5rem 0.5rem;
    border: 0;
    border-radius: var(--app-radius-sm);
    background: transparent;
    color: var(--app-sidebar-muted);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    transition: background-color var(--app-dur-fast) var(--app-ease-out), color var(--app-dur-fast) var(--app-ease-out);
}

.app-group-toggle:hover,
.app-group-toggle:focus-visible {
    background: var(--app-sidebar-soft);
    color: var(--app-sidebar-ink);
}

.app-group-rail {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    margin: 0.35rem 0 0.2rem;
    font-size: 0.85rem;
}

.app-group-rail::before,
.app-group-rail::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--app-sidebar-border);
}

.app-group-items {
    display: grid;
    gap: 0.15rem;
    padding-left: 0.25rem;
}

.app-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.48rem 0.6rem;
    border-radius: 0.55rem;
    color: var(--app-sidebar-muted);
    font-size: 0.85rem;
    text-decoration: none;
    transition: background-color var(--app-dur-fast) var(--app-ease-out), color var(--app-dur-fast) var(--app-ease-out), box-shadow var(--app-dur-fast) var(--app-ease-out);
}

.app-item:hover,
.app-item:focus-visible {
    background: var(--app-sidebar-soft);
    color: var(--app-sidebar-ink);
}

.app-item.is-active {
    background: var(--app-sidebar-active-bg);
    color: var(--app-sidebar-active-ink);
    font-weight: 600;
    box-shadow: inset 3px 0 0 var(--app-accent);
}

.app-item i {
    font-size: 1.05rem;
    color: var(--app-sidebar-muted);
}

.app-item.is-active i {
    color: var(--app-accent-ink);
}

.app-main {
    min-height: 100vh;
    min-height: 100dvh;
    min-width: 0;
}

.app-topbar {
    position: sticky;
    top: 0;
    z-index: 30;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--app-border);
    background: var(--app-surface);
    box-shadow: var(--app-shadow-xs);
}

.app-topbar > div {
    min-width: 0;
}

.app-menu-button,
.app-sidebar-toggle {
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    padding: 0.45rem;
    border: 1px solid var(--app-border);
    border-radius: 0.55rem;
    background: var(--app-surface);
    color: var(--app-ink);
    font-size: 1.3rem;
    transition: background-color var(--app-dur-fast) var(--app-ease-out), border-color var(--app-dur-fast) var(--app-ease-out), color var(--app-dur-fast) var(--app-ease-out), transform var(--app-dur-fast) var(--app-ease-out);
}

.app-menu-button:active,
.app-sidebar-toggle:active {
    transform: translateY(1px);
}

.app-menu-button:hover,
.app-sidebar-toggle:hover,
.app-menu-button:focus-visible,
.app-sidebar-toggle:focus-visible {
    border-color: var(--app-accent);
    background: var(--app-surface-2);
    color: var(--app-accent-ink);
}
.app-back-button {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 38px;
    padding: 0.35rem 0.6rem;
    border: 1px solid var(--app-border);
    border-radius: 0.55rem;
    background: var(--app-surface);
    color: var(--app-ink);
    font-size: 0.8rem;
    font-weight: 700;
    white-space: nowrap;
    transition: background-color var(--app-dur-fast) var(--app-ease-out), border-color var(--app-dur-fast) var(--app-ease-out), color var(--app-dur-fast) var(--app-ease-out);
}

.app-back-button:hover,
.app-back-button:focus-visible {
    border-color: var(--app-accent);
    background: var(--app-surface-2);
    color: var(--app-accent-ink);
}

.app-back-button .mdi {
    font-size: 1.1rem;
}

.app-title {

    overflow: hidden;
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.app-page-breadcrumb {
    margin-bottom: 0.75rem;
    padding: 0.15rem 0 0.45rem;
    border-bottom: 1px solid var(--app-border);
    background: transparent;
}

.app-page-breadcrumb-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.1rem 0.15rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.app-page-breadcrumb-item {
    display: inline-flex;
    align-items: center;
    min-width: 0;
}

.app-page-breadcrumb-link,
.app-page-breadcrumb-text {
    display: inline-flex;
    align-items: center;
    min-width: 0;
    min-height: 36px;
    padding: 0 0.4rem;
    border-radius: 0.45rem;
    font-size: 0.78rem;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.app-page-breadcrumb-link {
    color: var(--app-muted);
    text-decoration: none;
}

.app-page-breadcrumb-link:hover,
.app-page-breadcrumb-link:focus-visible {
    background: var(--app-surface-2);
    color: var(--app-accent-ink);
}

.app-page-breadcrumb-link:focus-visible {
    outline: 2px solid var(--app-accent);
    outline-offset: 2px;
}

.app-page-breadcrumb-text {
    color: var(--app-muted);
}

.app-page-breadcrumb-text[aria-current="page"] {
    color: var(--app-ink);
    font-weight: 700;
}

.app-page-breadcrumb-separator {
    flex: 0 0 auto;
    color: var(--app-muted);
    font-size: 0.9rem;
    opacity: 0.65;
}

.app-breadcrumb-menu-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.app-page-breadcrumb-menu-button {
    gap: 0.15rem;
    border: 0;
    background: transparent;
    cursor: pointer;
    font-family: inherit;
}

.app-breadcrumb-menu {
    position: absolute;
    top: calc(100% + 0.35rem);
    left: 0;
    z-index: 45;
    display: grid;
    gap: 0.1rem;
    width: max-content;
    min-width: 220px;
    max-width: min(320px, calc(100vw - 2rem));
    max-height: min(360px, 60vh);
    padding: 0.3rem;
    overflow-y: auto;
    border: 1px solid var(--app-border);
    border-radius: 0.65rem;
    background: var(--app-surface);
    box-shadow: 0 1rem 2.5rem rgba(15, 23, 42, 0.18);
}

.app-breadcrumb-menu-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-height: 40px;
    padding: 0.4rem 0.55rem;
    border-radius: 0.45rem;
    color: var(--app-muted);
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
}

.app-breadcrumb-menu-item:hover,
.app-breadcrumb-menu-item:focus-visible {
    background: var(--app-surface-2);
    color: var(--app-ink);
}

.app-breadcrumb-menu-item.is-active {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.app-breadcrumb-menu-item i {
    font-size: 1rem;
}

.app-breadcrumb-menu-check {
    margin-left: auto;
}

.app-page-breadcrumb.is-primary-nav {
    border-color: color-mix(in srgb, var(--app-accent) 22%, var(--app-border));
}

.app-user {
    position: relative;
}

.app-user-button {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.35rem 0.5rem;
    border: 1px solid transparent;
    border-radius: 0.65rem;
    background: transparent;
    color: var(--app-ink);
    cursor: pointer;
    transition: background-color var(--app-dur-fast) var(--app-ease-out), border-color var(--app-dur-fast) var(--app-ease-out);
}

.app-user-button:hover,
.app-user-button:focus-visible {
    border-color: var(--app-border);
    background: var(--app-surface-2);
}

.app-avatar {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--app-surface-3);
    color: var(--app-accent-ink);
    font-weight: 800;
}

.app-user-button strong {
    display: block;
    font-size: 0.82rem;
}

.app-user-button small {
    display: block;
    color: var(--app-muted);
    font-size: 0.7rem;
}

.app-user-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 0.4rem);
    width: max-content;
    min-width: 180px;
    max-width: min(240px, calc(100vw - 2rem));
    padding: 0.35rem;
    border: 1px solid var(--app-border);
    border-radius: 0.6rem;
    background: var(--app-surface);
    box-shadow: var(--app-shadow);
}

.app-user-menu button {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.55rem 0.65rem;
    border: 0;
    border-radius: 0.45rem;
    background: transparent;
    color: var(--app-ink);
    font: inherit;
    text-align: left;
    cursor: pointer;
}

.app-user-menu button:hover,
.app-user-menu button:focus-visible {
    background: var(--app-surface-2);
}

.app-user-locale {
    display: flex;
    gap: 0.25rem;
    padding: 0.15rem 0.1rem 0.45rem;
    margin-bottom: 0.25rem;
    border-bottom: 1px solid var(--app-border);
}

.app-user-menu .app-user-locale button {
    flex: 1;
    justify-content: center;
    width: auto;
    padding: 0.4rem 0.5rem;
    border: 1px solid var(--app-border);
    color: var(--app-muted);
    font-size: 0.7rem;
    font-weight: 800;
}

.app-user-menu .app-user-locale button.is-active {
    border-color: var(--app-accent);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.app-content {
    width: 100%;
    min-width: 0;
    max-width: var(--app-content-max);
    margin: 0 auto;
    padding: 1rem;
}

.app-content > * {
    animation: app-page-in var(--app-dur-base) var(--app-ease-out);
}

@keyframes app-page-in {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.app-overlay {
    position: fixed;
    inset: 0;
    z-index: 35;
    background: var(--app-scrim);
    animation: app-fade-in var(--app-dur-base) var(--app-ease-out);
}

@keyframes app-fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@media (min-width: 576px) {
    .app-content {
        padding: 1.25rem;
    }
}

@media (min-width: 768px) {
    .app-topbar {
        padding: 0.85rem 1.25rem;
    }

    .app-content {
        padding: 1.25rem 1.5rem;
    }
}

@media (min-width: 992px) {
    .app-sidebar {
        transform: none;
    }

    .app-sidebar.is-collapsed {
        width: var(--app-sidebar-w-collapsed);
    }

    .app-main {
        margin-left: var(--app-sidebar-w);
        transition: margin-left var(--app-dur-slow) var(--app-ease-in-out);
    }

    .app-shell.is-sidebar-collapsed .app-main {
        margin-left: var(--app-sidebar-w-collapsed);
    }

    .app-sidebar.is-collapsed .app-brand {
        justify-content: center;
        padding: 1rem 0.5rem;
    }

    .app-sidebar.is-collapsed .app-brand-copy,
    .app-sidebar.is-collapsed .app-area > span,
    .app-sidebar.is-collapsed .app-item-label {
        display: none;
    }

    .app-sidebar.is-collapsed .app-areas {
        padding: 0.75rem 0.5rem;
    }

    .app-sidebar.is-collapsed .app-area {
        justify-content: center;
        padding: 0.65rem;
    }

    .app-sidebar.is-collapsed .app-nav-scroll {
        padding: 0 0.5rem 1rem;
    }

    .app-sidebar.is-collapsed .app-group {
        margin-top: 0.35rem;
    }

    .app-sidebar.is-collapsed .app-group-items {
        gap: 0.2rem;
        padding-left: 0;
    }

    .app-sidebar.is-collapsed .app-item {
        justify-content: center;
        padding: 0.6rem 0.5rem;
    }

    .app-sidebar.is-collapsed .app-item i {
        font-size: 1.15rem;
    }

    .app-sidebar.is-collapsed .app-group-rail {
        color: var(--app-sidebar-muted);
        opacity: 0.75;
    }

    .app-sidebar.is-collapsed .app-group.is-active .app-group-rail {
        color: var(--app-accent-ink);
        opacity: 1;
    }

    .app-content {
        padding: 1.5rem 2rem;
    }
}

@media (min-width: 1400px) {
    .app-content {
        padding: 2rem 2.5rem;
    }
}

@media (max-width: 479.98px) {
    .app-title {
        display: none;
    }
}

@media (max-width: 575.98px) {
    .app-topbar .locale-toggle {
        display: none;
    }

    .app-menu-button,
    .theme-toggle {
        width: 44px;
        height: 44px;
    }

    .app-close {
        width: 44px;
        height: 44px;
    }

    .app-item {
        min-height: 44px;
    }

    .app-title {
        font-size: 1rem;
    }

    .app-page-breadcrumb {
        padding: 0.2rem;
    }

    .app-page-breadcrumb-link,
    .app-page-breadcrumb-text,
    .app-breadcrumb-menu-item {
        min-height: 44px;
        font-size: 0.76rem;
    }
}
@media (max-width: 991.98px) {
    .app-sidebar {
        border-radius: 0 var(--app-radius-xl) var(--app-radius-xl) 0;
        box-shadow: var(--app-shadow-lg);
    }
}
@media (prefers-reduced-motion: reduce) {
    .app-sidebar,
    .app-main,
    .app-progress,
    .app-overlay,
    .app-content > * {
        transition: none;
        animation: none;
    }
}

:focus-visible {
    outline: 2px solid var(--app-accent-strong);
    outline-offset: 2px;
}

</style>
