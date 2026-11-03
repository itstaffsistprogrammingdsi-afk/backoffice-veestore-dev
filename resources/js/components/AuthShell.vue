<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';

interface Branding {
    name: string;
    tagline: string;
    logo_url: string;
}

const { isDark, toggleTheme } = useTheme();
const page = usePage();
const branding = computed<Branding>(() => (
    page.props.branding ?? {
        name: 'VeeStore',
        tagline: 'Backoffice',
        logo_url: '/assets/img/logo.png',
    }
) as Branding);

defineProps<{
    title: string;
    description: string;
}>();
</script>

<template>
    <div class="auth-shell">
        <div class="auth-panel">
            <div class="auth-topline">
                <div class="auth-brand">
                    <img :src="branding.logo_url" :alt="`${branding.name} ${branding.tagline}`" class="auth-logo" />
                    <strong class="auth-tagline">{{ branding.tagline }}</strong>
                </div>

                <button
                    class="theme-toggle"
                    type="button"
                    :aria-label="isDark ? 'Aktifkan light mode' : 'Aktifkan dark mode'"
                    :title="isDark ? 'Light mode' : 'Dark mode'"
                    @click="toggleTheme"
                >
                    <i class="mdi" :class="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'" aria-hidden="true"></i>
                </button>
            </div>

            <div class="auth-card">
                <h1>{{ title }}</h1>
                <p class="auth-description">{{ description }}</p>
                <slot />
            </div>

        </div>
    </div>
</template>

<style scoped>
.auth-shell {
    min-height: 100vh;
    min-height: 100dvh;
    display: grid;
    place-items: center;
    padding: 2rem 1rem;
    background: var(--app-bg);
    color: var(--app-ink);
    font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
}

.auth-panel {
    width: min(100%, 440px);
}

.auth-topline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.auth-brand {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.auth-logo {
    display: block;
    width: 128px;
    height: auto;
}

.auth-tagline {
    display: block;
    padding-left: 0.75rem;
    border-left: 1px solid var(--app-border);
    color: var(--app-muted);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.auth-card {
    padding: 1.75rem;
    border: 1px solid var(--app-border);
    border-radius: 1rem;
    background: var(--app-surface);
    box-shadow: 0 1rem 2rem rgba(26, 43, 35, 0.1);
}

.auth-card h1 {
    margin: 0 0 0.4rem;
    font-size: 1.5rem;
}

.auth-description {
    margin: 0 0 1.25rem;
    color: var(--app-muted);
    font-size: 0.9rem;
}

</style>
