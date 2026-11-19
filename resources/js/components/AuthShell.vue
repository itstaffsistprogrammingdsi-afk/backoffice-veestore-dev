<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';
import { currentLocale, switchLocale as changeLocale } from '@/i18n';

interface Branding {
    name: string;
    tagline: string;
    logo_url: string;
}

const { isDark, toggleTheme } = useTheme();
const page = usePage();
const locale = currentLocale;
const localeOptions = [
    { value: 'id', label: 'ID' },
    { value: 'en', label: 'EN' },
];

const switchLocale = (value: string) => {
    if (value === locale.value) return;

    changeLocale(value);
};
const branding = computed<Branding>(() => (
    page.props.branding ?? {
        name: 'VeeStore',
        tagline: 'Master Data & Report',
        logo_url: '/assets/img/logo.png',
    }
) as Branding);

defineProps<{
    title: string;
    description: string;
}>();

const modules = [
    {
        title: 'Operasional stok',
        description: 'Barang masuk, keluar, dan transfer antar lokasi.',
    },
    {
        title: 'Persetujuan',
        description: 'Order store, retur, dan permintaan label.',
    },
    {
        title: 'Laporan & pelanggan',
        description: 'Penjualan, stok, CRM, dan performa toko.',
    },
];
</script>

<template>
    <div class="auth-shell">
        <section class="auth-story" aria-label="Tentang VeeStore">
            <header class="auth-story-head">
                <img
                    :src="branding.logo_url"
                    :alt="`${branding.name} ${branding.tagline}`"
                    class="auth-logo"
                    width="482"
                    height="292"
                    decoding="async"
                />
                <span class="auth-brand-line" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <p class="auth-tagline">{{ branding.tagline }}</p>
            </header>

            <div class="auth-story-body">
                <p class="auth-promise">Satu meja kerja untuk toko, gudang, dan e-commerce.</p>
                <p class="auth-context-copy">Masuk untuk mengelola stok, persetujuan, dan laporan VeeStore.</p>

                <ul class="auth-modules">
                    <li v-for="module in modules" :key="module.title">
                        <strong>{{ module.title }}</strong>
                        <span>{{ module.description }}</span>
                    </li>
                </ul>
            </div>
        </section>

        <section class="auth-form-shell">
            <div class="auth-utility">
                <p class="auth-shell-label">Akses backoffice</p>

                <div class="auth-utility-actions">
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
            </div>

            <div class="auth-form-wrap">
                <header class="auth-form-header">
                    <h1>{{ title }}</h1>
                    <p class="auth-description">{{ description }}</p>
                </header>
                <slot />
            </div>

            <p class="auth-footnote">Akses akun dikelola administrator.</p>
        </section>
    </div>
</template>

<style scoped>
.auth-shell {
    min-height: 100vh;
    min-height: 100dvh;
    display: grid;
    grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
    gap: clamp(2rem, 4vw, 4.5rem);
    width: min(100%, 1560px);
    margin: 0 auto;
    padding: clamp(1rem, 2.5vw, 1.75rem);
    background: var(--app-bg);
    color: var(--app-ink);
    font-family: var(--app-font-sans);
}

.auth-story {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 2.5rem;
    min-width: 0;
    padding: clamp(0.5rem, 2vw, 1.5rem) 0;
}

.auth-story-head {
    --auth-logo-width: clamp(8rem, 10vw, 10.5rem);

    display: grid;
    justify-items: start;
    gap: 0.875rem;
}

.auth-logo {
    display: block;
    width: var(--auth-logo-width);
    height: auto;
}

.auth-brand-line {
    display: flex;
    gap: 0.25rem;
    width: var(--auth-logo-width);
}

.auth-brand-line span {
    flex: 1 1 0;
    height: 0.25rem;
    border-radius: 999px;
}

.auth-brand-line span:nth-child(1) {
    background: var(--app-brand-coral);
}

.auth-brand-line span:nth-child(2) {
    background: var(--app-brand-yellow);
}

.auth-brand-line span:nth-child(3) {
    background: var(--app-brand-green);
}

.auth-brand-line span:nth-child(4) {
    background: var(--app-brand-purple);
}

.auth-tagline {
    margin: 0;
    color: var(--app-muted);
    font-size: 0.95rem;
    font-weight: 500;
    line-height: 1.4;
    overflow-wrap: anywhere;
}

.auth-story-body {
    display: grid;
    gap: 1.75rem;
}

.auth-promise {
    max-width: 14ch;
    margin: 0;
    font-size: clamp(1.875rem, 3.2vw, 3rem);
    font-weight: 600;
    letter-spacing: -0.03em;
    line-height: 1.05;
    text-wrap: balance;
    overflow-wrap: anywhere;
}

.auth-context-copy {
    max-width: 40ch;
    margin: -0.75rem 0 0;
    color: var(--app-muted);
    font-size: 0.95rem;
    line-height: 1.55;
}

.auth-modules {
    display: grid;
    margin: 0;
    padding: 0;
    border-top: 1px solid var(--app-border);
    list-style: none;
}

.auth-modules li {
    display: grid;
    gap: 0.2rem;
    padding: 0.8rem 0;
    border-bottom: 1px solid var(--app-border);
}

.auth-modules strong {
    color: var(--app-ink);
    font-size: 0.875rem;
    font-weight: 600;
}

.auth-modules span {
    color: var(--app-muted);
    font-size: 0.8rem;
    line-height: 1.45;
}

.auth-form-shell {
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding: clamp(1.5rem, 3vw, 2.5rem);
    border: 1px solid var(--app-border);
    border-radius: 1.25rem;
    background: var(--app-surface);
    box-shadow: var(--app-shadow-lg);
}

.auth-utility {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.auth-shell-label {
    margin: 0;
    color: var(--app-muted);
    font-size: 0.8rem;
    font-weight: 500;
}

.auth-utility-actions {
    display: flex;
    gap: 0.5rem;
}

.auth-utility .theme-toggle {
    width: 44px;
    height: 44px;
}

.auth-utility .locale-toggle button {
    min-width: 40px;
    height: 2.5rem;
}

.auth-form-wrap {
    width: 100%;
    max-width: 24rem;
    margin: auto;
    padding: 2.5rem 0;
}

.auth-form-header h1 {
    margin: 0 0 0.5rem;
    font-size: clamp(1.5rem, 2vw, 1.75rem);
    font-weight: 600;
    letter-spacing: -0.02em;
    line-height: 1.2;
    text-wrap: balance;
}

.auth-description {
    margin: 0 0 1.75rem;
    color: var(--app-muted);
    font-size: 0.95rem;
    line-height: 1.55;
    overflow-wrap: anywhere;
}

.auth-footnote {
    margin: 0;
    padding-top: 1rem;
    color: var(--app-muted);
    font-size: 0.75rem;
    line-height: 1.5;
    text-align: center;
}

@media (max-width: 960px) {
    .auth-shell {
        display: block;
        padding: 0;
    }

    .auth-story {
        justify-content: flex-start;
        gap: 1.25rem;
        padding:
            calc(1.5rem + env(safe-area-inset-top))
            calc(1.25rem + env(safe-area-inset-right))
            1.25rem
            calc(1.25rem + env(safe-area-inset-left));
    }

    .auth-story-head {
        --auth-logo-width: 7rem;
        gap: 0.75rem;
    }

    .auth-logo {
        width: var(--auth-logo-width);
    }

    .auth-tagline {
        font-size: 0.875rem;
    }

    .auth-story-body {
        gap: 0;
        margin-block: 0;
    }

    .auth-promise {
        max-width: 20ch;
        font-size: 1.5rem;
    }

    .auth-context-copy,
    .auth-modules {
        display: none;
    }

    .auth-form-shell {
        margin:
            0
            calc(0.75rem + env(safe-area-inset-right))
            calc(0.75rem + env(safe-area-inset-bottom))
            calc(0.75rem + env(safe-area-inset-left));
        padding: 1.25rem;
        border-radius: 1rem;
        box-shadow: none;
    }

    .auth-utility {
        width: 100%;
        max-width: 28rem;
        margin: 0 auto 0.5rem;
    }

    .auth-shell-label {
        display: none;
    }

    .auth-form-wrap {
        max-width: 28rem;
        padding: 1rem 0 2rem;
    }

    .auth-footnote {
        padding-top: 1.5rem;
    }
}

@media (pointer: coarse) {
    .auth-utility .theme-toggle {
        width: 44px;
        height: 44px;
    }

    .auth-utility .locale-toggle button {
        min-width: 44px;
        min-height: 44px;
    }
}
</style>
