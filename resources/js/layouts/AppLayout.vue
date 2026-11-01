<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
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

interface Navigation {
    areas: NavArea[];
}

interface Props {
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: undefined,
});

const page = usePage();
const { isDark, toggleTheme } = useTheme();

const navigation = computed<Navigation>(() => (page.props.navigation ?? { areas: [] }) as Navigation);
const user = computed(() => (page.props.auth as { user?: { name?: string; email?: string } } | undefined)?.user);
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const currentPath = computed(() => page.url.split('?')[0]);
const isReportsArea = computed(() => {
    const path = currentPath.value;

    return (
        path === '/reports' ||
        path.startsWith('/reports/') ||
        path === '/dashboard' ||
        path.startsWith('/monitoring') ||
        path.startsWith('/report-exports') ||
        path.startsWith('/ai-analytics') ||
        path.startsWith('/ai-assistant')
    );
});

const activeAreaKey = computed(() => (isReportsArea.value ? 'reports' : 'operations'));
const activeArea = computed(() => navigation.value.areas.find((area) => area.key === activeAreaKey.value));
const openGroups = ref<Record<string, boolean>>({});
const mobileOpen = ref(false);
const userMenuOpen = ref(false);

const pathOf = (url: string | null) => {
    if (!url) return '';
    try {
        return new URL(url, window.location.origin).pathname.replace(/\/$/, '') || '/';
    } catch {
        return url;
    }
};

const isActive = (item: NavItem) => {
    const path = pathOf(item.url);
    if (!path) return false;
    if (path === '/') return currentPath.value === '/';
    return currentPath.value === path || currentPath.value.startsWith(`${path}/`);
};

const activeItem = computed(() => {
    for (const area of navigation.value.areas) {
        for (const group of area.groups) {
            for (const item of group.items) {
                if (isActive(item)) return item;
            }
        }
    }

    return null;
});

const pageTitle = computed(() => props.title ?? activeItem.value?.label ?? activeArea.value?.label ?? 'VeeStore');

const groupsForArea = computed(() => {
    const area = activeArea.value;
    return area?.groups ?? [];
});

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

const closeMobile = () => {
    mobileOpen.value = false;
    userMenuOpen.value = false;
};

const logout = () => {
    router.post('/logout');
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        closeMobile();
    }
};

watch(mobileOpen, (open) => {
    document.documentElement.classList.toggle('inertia-sidebar-open', open);
});

window.addEventListener('keydown', onKeydown);
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div class="app-shell">
        <a href="#main-content" class="skip-link">Lewati ke konten</a>

        <div v-if="mobileOpen" class="app-overlay" aria-hidden="true" @click="closeMobile"></div>

        <aside class="app-sidebar" :class="{ 'is-open': mobileOpen }" aria-label="Navigasi utama">
            <div class="app-brand">
                <div class="app-mark">VS</div>
                <div>
                    <strong>VeeStore</strong>
                    <span>Backoffice</span>
                </div>
                <button class="app-close d-lg-none" type="button" aria-label="Tutup menu" @click="closeMobile">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>

            <nav class="app-areas" aria-label="Area aplikasi">
                <Link
                    v-for="area in navigation.areas"
                    :key="area.key"
                    :href="area.url ?? '#'"
                    class="app-area"
                    :class="{ 'is-active': activeAreaKey === area.key }"
                    :aria-current="activeAreaKey === area.key ? 'page' : undefined"
                    @click="closeMobile"
                >
                    <i class="mdi" :class="area.icon"></i>
                    <span>
                        <strong>{{ area.label }}</strong>
                        <small>{{ area.description }}</small>
                    </span>
                </Link>
            </nav>

            <div class="app-nav-scroll">
                <div v-for="group in groupsForArea" :key="group.label" class="app-group">
                    <button
                        class="app-group-toggle"
                        type="button"
                        :aria-expanded="isGroupOpen(group)"
                        @click="toggleGroup(group.label, group)"
                    >
                        <span><i class="mdi" :class="group.icon"></i> {{ group.label }}</span>
                        <i class="mdi" :class="isGroupOpen(group) ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>
                    </button>

                    <div v-show="isGroupOpen(group)" class="app-group-items">
                        <template v-for="item in group.items" :key="item.route">
                            <Link
                                v-if="item.inertia && item.url"
                                :href="item.url"
                                class="app-item"
                                :class="{ 'is-active': isActive(item) }"
                                :aria-current="isActive(item) ? 'page' : undefined"
                                @click="closeMobile"
                            >
                                <i class="mdi" :class="item.icon"></i>
                                <span>{{ item.label }}</span>
                            </Link>
                            <a
                                v-else-if="item.url"
                                :href="item.url"
                                class="app-item"
                                :class="{ 'is-active': isActive(item) }"
                                :aria-current="isActive(item) ? 'page' : undefined"
                            >
                                <i class="mdi" :class="item.icon"></i>
                                <span>{{ item.label }}</span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </aside>

        <div class="app-main">
            <header class="app-topbar">
                <div class="d-flex align-items-center gap-2">
                    <button class="app-menu-button d-lg-none" type="button" aria-label="Buka menu" @click="mobileOpen = true">
                        <i class="mdi mdi-menu"></i>
                    </button>
                    <div>
                        <div class="app-breadcrumb">
                            {{ activeArea?.label ?? 'VeeStore' }}
                            <span v-if="activeItem">/ {{ activeItem.label }}</span>
                        </div>
                        <h1 class="app-title">{{ pageTitle }}</h1>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button
                        class="theme-toggle"
                        type="button"
                        :aria-label="isDark ? 'Aktifkan light mode' : 'Aktifkan dark mode'"
                        :title="isDark ? 'Light mode' : 'Dark mode'"
                        @click="toggleTheme"
                    >
                        <i class="mdi" :class="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"></i>
                    </button>

                    <div class="app-user" @keydown.esc="userMenuOpen = false">
                    <button class="app-user-button" type="button" :aria-expanded="userMenuOpen" @click="userMenuOpen = !userMenuOpen">
                        <span class="app-avatar">{{ (user?.name ?? 'U').slice(0, 1).toUpperCase() }}</span>
                        <span class="d-none d-md-block text-start">
                            <strong>{{ user?.name ?? 'User' }}</strong>
                            <small>{{ user?.email ?? '' }}</small>
                        </span>
                        <i class="mdi mdi-chevron-down d-none d-md-block"></i>
                    </button>

                    <div v-if="userMenuOpen" class="app-user-menu" role="menu">
                        <button type="button" role="menuitem" @click="logout">
                            <i class="mdi mdi-logout me-1"></i>
                            Logout
                        </button>
                    </div>
                    </div>
                </div>
            </header>

            <main id="main-content" class="app-content">
                <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
                <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.app-shell {
    min-height: 100vh;
    background: var(--app-bg);
    color: var(--app-ink);
    font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
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
    width: 280px;
    background: var(--app-sidebar-bg);
    border-right: 1px solid var(--app-sidebar-border);
    color: var(--app-sidebar-ink);
    transform: translateX(-100%);
    transition: transform 180ms ease;
}

.app-sidebar.is-open {
    transform: translateX(0);
}

.app-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.1rem 1rem;
    border-bottom: 1px solid var(--app-sidebar-border);
}

.app-mark {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: var(--app-sidebar-active-bg);
    color: var(--app-sidebar-active-ink);
    font-weight: 800;
}

.app-brand strong {
    display: block;
    font-size: 1rem;
}

.app-brand span {
    display: block;
    color: var(--app-sidebar-muted);
    font-size: 0.75rem;
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
    border-radius: 0.7rem;
    color: var(--app-sidebar-ink);
    text-decoration: none;
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
    color: var(--app-accent);
}

.app-area strong {
    display: block;
    font-size: 0.88rem;
}

.app-area small {
    display: block;
    color: var(--app-sidebar-muted);
    font-size: 0.7rem;
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
    background: transparent;
    color: var(--app-sidebar-muted);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.app-group-toggle:hover,
.app-group-toggle:focus-visible {
    color: var(--app-sidebar-ink);
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
}

.app-item:hover,
.app-item:focus-visible {
    background: var(--app-sidebar-soft);
    color: var(--app-sidebar-ink);
}

.app-item.is-active {
    background: var(--app-sidebar-active-bg);
    color: var(--app-sidebar-active-ink);
    box-shadow: inset 3px 0 0 var(--app-accent);
}

.app-item i {
    font-size: 1.05rem;
    color: var(--app-accent);
}

.app-main {
    min-height: 100vh;
}

.app-topbar {
    position: sticky;
    top: 0;
    z-index: 30;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid var(--app-border);
    background: var(--app-surface);
    backdrop-filter: blur(10px);
}

.app-menu-button {
    padding: 0.45rem;
    border: 1px solid var(--app-border);
    border-radius: 0.55rem;
    background: var(--app-surface);
    color: var(--app-ink);
    font-size: 1.3rem;
}

.app-breadcrumb {
    color: var(--app-muted);
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.app-title {
    margin: 0.1rem 0 0;
    font-size: 1.15rem;
    font-weight: 800;
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
    min-width: 180px;
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
}

.app-user-menu button:hover,
.app-user-menu button:focus-visible {
    background: var(--app-surface-2);
}

.app-content {
    padding: 1.25rem;
    max-width: 1500px;
    margin: 0 auto;
}

.app-overlay {
    position: fixed;
    inset: 0;
    z-index: 35;
    background: rgba(23, 17, 31, 0.45);
}

@media (min-width: 992px) {
    .app-sidebar {
        transform: none;
    }

    .app-main {
        margin-left: 280px;
    }

    .app-content {
        padding: 1.5rem 2rem;
    }
}

:focus-visible {
    outline: 2px solid var(--app-accent);
    outline-offset: 2px;
}
</style>
