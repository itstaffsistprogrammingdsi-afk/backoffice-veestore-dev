<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthShell from '../../components/AuthShell.vue';

interface Props {
    urls: {
        login: string;
        register: string;
        forgot: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(props.urls.login, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Login" />

    <AuthShell title="Masuk ke Backoffice" description="Gunakan email atau username dan password akun Anda.">
        <div v-if="flash.success" class="auth-alert auth-alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="auth-alert auth-alert-danger">{{ flash.error }}</div>

        <form @submit.prevent="submit">
            <div class="auth-field">
                <label for="login">Email atau Username</label>
                <input
                    id="login"
                    v-model="form.login"
                    type="text"
                    autocomplete="username"
                    :class="{ 'is-invalid': form.errors.login }"
                    required
                    autofocus
                />
                <small v-if="form.errors.login" class="auth-error">{{ form.errors.login }}</small>
            </div>

            <div class="auth-field">
                <label for="password">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    :class="{ 'is-invalid': form.errors.password }"
                    required
                />
                <small v-if="form.errors.password" class="auth-error">{{ form.errors.password }}</small>
            </div>

            <label class="auth-check">
                <input v-model="form.remember" type="checkbox" />
                Ingat saya
            </label>

            <button class="auth-button" type="submit" :disabled="form.processing">
                {{ form.processing ? 'Memproses...' : 'Masuk' }}
            </button>
        </form>

        <div class="auth-links">
            <Link :href="props.urls.forgot">Lupa password?</Link>
            <Link :href="props.urls.register">Buat akun</Link>
        </div>
    </AuthShell>
</template>

<style scoped>
.auth-alert {
    padding: 0.75rem 0.9rem;
    margin-bottom: 1rem;
    border-radius: 0.6rem;
    font-size: 0.85rem;
}

.auth-alert-success {
    background: #dcfce7;
    color: #166534;
}

.auth-alert-danger {
    background: #fee2e2;
    color: #991b1b;
}

.auth-field {
    margin-bottom: 0.9rem;
}

.auth-field label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.85rem;
    font-weight: 600;
}

.auth-field input {
    width: 100%;
    padding: 0.65rem 0.75rem;
    border: 1px solid var(--app-border);
    border-radius: 0.55rem;
    background: var(--app-surface);
    font: inherit;
}

.auth-field input:focus {
    outline: 2px solid var(--app-accent);
    outline-offset: 1px;
    border-color: transparent;
}

.auth-field input.is-invalid {
    border-color: #dc2626;
}

.auth-error {
    display: block;
    margin-top: 0.3rem;
    color: #b91c1c;
    font-size: 0.8rem;
}

.auth-check {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 1rem;
    color: var(--app-muted);
    font-size: 0.85rem;
}

.auth-button {
    width: 100%;
    padding: 0.7rem 1rem;
    border: 0;
    border-radius: 0.55rem;
    background: var(--app-accent-strong);
    color: var(--app-on-accent);
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

.auth-button:disabled {
    opacity: 0.6;
    cursor: wait;
}

.auth-links {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    font-size: 0.85rem;
}

.auth-links a {
    color: var(--app-accent-ink);
    text-decoration: none;
    font-weight: 600;
}
</style>
