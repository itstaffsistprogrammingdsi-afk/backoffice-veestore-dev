<script setup lang="ts">
import { useTheme } from '@/composables/useTheme';

const { isDark, toggleTheme } = useTheme();

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
                    <div class="auth-mark">VS</div>
                    <div>
                        <strong>VeeStore</strong>
                        <span>Backoffice</span>
                    </div>
                </div>

                <button
                    class="theme-toggle"
                    type="button"
                    :aria-label="isDark ? 'Aktifkan light mode' : 'Aktifkan dark mode'"
                    :title="isDark ? 'Light mode' : 'Dark mode'"
                    @click="toggleTheme"
                >
                    <i class="mdi" :class="isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"></i>
                </button>
            </div>

            <div class="auth-card">
                <h1>{{ title }}</h1>
                <p class="auth-description">{{ description }}</p>
                <slot />
            </div>

            <p class="auth-footer">VeeStore Backoffice</p>
        </div>
    </div>
</template>

<style scoped>
.auth-shell {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 2rem 1rem;
    background: var(--app-bg);
    color: var(--app-ink);
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
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

.auth-mark {
    display: grid;
    place-items: center;
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: var(--app-surface-3);
    color: var(--app-accent-ink);
    font-weight: 800;
}

.auth-brand strong {
    display: block;
    font-size: 1.1rem;
}

.auth-brand span {
    display: block;
    color: var(--app-muted);
    font-size: 0.8rem;
}

.auth-card {
    padding: 1.75rem;
    border: 1px solid var(--app-border);
    border-radius: 1rem;
    background: var(--app-surface);
    box-shadow: 0 1.5rem 3rem rgba(23, 17, 31, 0.08);
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

.auth-footer {
    margin: 1rem 0 0;
    color: var(--app-muted);
    font-size: 0.75rem;
    text-align: center;
}
</style>
