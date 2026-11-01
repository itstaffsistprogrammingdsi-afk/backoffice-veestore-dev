<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthShell from '../../components/AuthShell.vue';

interface Props {
    urls: {
        login: string;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <Head title="Forgot Password" />

    <AuthShell title="Lupa password?" description="Reset password belum tersedia otomatis.">
        <div class="auth-info">
            Hubungi administrator backoffice untuk mereset password akun Anda.
        </div>

        <Link :href="props.urls.login" class="auth-back">Kembali ke halaman login</Link>
    </AuthShell>
</template>

<style scoped>
.auth-info {
    padding: 0.9rem 1rem;
    border: 1px dashed #ddd6e2;
    border-radius: 0.65rem;
    background: var(--app-surface-2);
    color: #4b4453;
    font-size: 0.9rem;
    line-height: 1.5;
}

.auth-back {
    display: inline-block;
    margin-top: 1rem;
    color: var(--app-accent-ink);
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
}
</style>
