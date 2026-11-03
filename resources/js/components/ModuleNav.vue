<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

interface ModuleNavItem {
    key: string;
    label: string;
    icon: string;
    url: string;
}

interface ModuleNavigation {
    current: string;
    items: ModuleNavItem[];
}

const props = withDefaults(defineProps<{
    moduleKey: string;
    ariaLabel?: string;
}>(), {
    ariaLabel: 'Modul',
});

const page = usePage();
const moduleNavigation = computed(() => (page.props[props.moduleKey] ?? null) as ModuleNavigation | null);
</script>

<template>
    <nav v-if="moduleNavigation && moduleNavigation.items.length > 1" class="module-nav" :aria-label="props.ariaLabel">
        <Link
            v-for="item in moduleNavigation.items"
            :key="item.key"
            :href="item.url"
            class="module-nav-link"
            :class="{ 'is-active': moduleNavigation.current === item.key }"
            :aria-current="moduleNavigation.current === item.key ? 'page' : undefined"
        >
            <i class="mdi" :class="item.icon"></i>
            <span>{{ item.label }}</span>
        </Link>
    </nav>
</template>

<style scoped>
.module-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-bottom: 1rem;
    padding: 0.4rem;
    border: 1px solid var(--app-border);
    border-radius: 0.75rem;
    background: var(--app-surface);
    box-shadow: var(--app-shadow);
}
.module-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 44px;
    padding: 0.45rem 0.75rem;
    border-radius: 0.55rem;
    color: var(--app-muted);
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
}
.module-nav-link:hover,
.module-nav-link:focus-visible {
    background: var(--app-surface-2);
    color: var(--app-ink);
}
.module-nav-link:focus-visible {
    outline: 2px solid var(--app-accent);
    outline-offset: 2px;
}
.module-nav-link.is-active {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}
.module-nav-link i {
    font-size: 1rem;
}
@media (max-width: 575.98px) {
    .module-nav-link {
        flex: 0 1 calc(50% - 0.35rem);
    }
}
</style>
