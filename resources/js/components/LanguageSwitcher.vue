<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { AppLocale } from '@/composables/i18n';

withDefaults(defineProps<{
    variant?: 'app' | 'auth';
}>(), {
    variant: 'app',
});

const page = usePage();
const formRef = ref<HTMLFormElement | null>(null);
const csrfToken = typeof document === 'undefined'
    ? ''
    : document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
const locale = computed<AppLocale>(() => page.props.locale === 'en' ? 'en' : 'id');

const submitLanguage = () => formRef.value?.requestSubmit();
</script>

<template>
    <form ref="formRef" action="/language" method="post" class="language-switcher" :class="`is-${variant}`">
        <input type="hidden" name="_token" :value="csrfToken" />
        <label class="visually-hidden" for="app-language">{{ $t('Pilih bahasa') }}</label>
        <select id="app-language" name="locale" :value="locale" @change="submitLanguage">
            <option value="id">Bahasa Indonesia</option>
            <option value="en">English</option>
        </select>
    </form>
</template>

<style scoped>
.language-switcher {
    margin: 0;
}

.language-switcher select {
    min-height: 38px;
    padding: 0.4rem 2rem 0.4rem 0.65rem;
    border: 1px solid var(--app-border);
    border-radius: 0.5rem;
    background: var(--app-surface);
    color: var(--app-ink);
    font: inherit;
    font-size: 0.8rem;
    cursor: pointer;
}

.language-switcher select:focus-visible {
    outline: 2px solid var(--app-accent);
    outline-offset: 2px;
}

.is-app select {
    width: 100%;
}

.is-auth select {
    min-height: 36px;
}
</style>
