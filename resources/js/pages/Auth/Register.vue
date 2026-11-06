<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthShell from '../../components/AuthShell.vue';

defineProps<{
    urls: {
        login: string;
    };
}>();
</script>

<template>
    <Head title="Akses Master Data & Report" />

    <AuthShell title="Akses Master Data & Report" description="Akun Master Data & Report dikelola oleh administrator.">
        <div class="auth-info" role="note">
            Hubungi administrator untuk meminta akun atau bantuan akses.
        </div>

        <p class="auth-login-link">
            <Link :href="urls.login">Kembali ke halaman masuk</Link>
        </p>
    </AuthShell>
</template>

<style scoped>
.auth-info {
    padding: 0.9rem 1rem;
    border: 1px solid var(--app-border);
    border-radius: 0.55rem;
    background: var(--app-surface-2);
    color: var(--app-ink);
    font-size: 0.9rem;
    line-height: 1.5;
}

.auth-login-link {
    margin: 1rem 0 0;
    text-align: center;
    font-size: 0.9rem;
}

.auth-login-link a {
    color: var(--app-accent-ink);
    font-weight: 600;
    text-decoration: none;
}
</style>
