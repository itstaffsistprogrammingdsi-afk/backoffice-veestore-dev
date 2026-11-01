<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthShell from '../../components/AuthShell.vue';

interface Props {
    urls: {
        register: string;
        login: string;
    };
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(props.urls.register, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Register" />

    <AuthShell title="Buat akun" description="Daftarkan akun backoffice baru.">
        <form @submit.prevent="submit">
            <div class="auth-field">
                <label for="name">Nama Lengkap</label>
                <input id="name" v-model="form.name" type="text" :class="{ 'is-invalid': form.errors.name }" required autofocus />
                <small v-if="form.errors.name" class="auth-error">{{ form.errors.name }}</small>
            </div>

            <div class="auth-field">
                <label for="username">Username</label>
                <input id="username" v-model="form.username" type="text" :class="{ 'is-invalid': form.errors.username }" required />
                <small v-if="form.errors.username" class="auth-error">{{ form.errors.username }}</small>
            </div>

            <div class="auth-field">
                <label for="email">Email</label>
                <input id="email" v-model="form.email" type="email" :class="{ 'is-invalid': form.errors.email }" required />
                <small v-if="form.errors.email" class="auth-error">{{ form.errors.email }}</small>
            </div>

            <div class="auth-field">
                <label for="password">Password</label>
                <input id="password" v-model="form.password" type="password" autocomplete="new-password" :class="{ 'is-invalid': form.errors.password }" required />
                <small v-if="form.errors.password" class="auth-error">{{ form.errors.password }}</small>
            </div>

            <div class="auth-field">
                <label for="password_confirmation">Konfirmasi Password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
            </div>

            <button class="auth-button" type="submit" :disabled="form.processing">
                {{ form.processing ? 'Memproses...' : 'Buat Akun' }}
            </button>
        </form>

        <p class="auth-login-link">
            Sudah punya akun?
            <Link :href="props.urls.login">Masuk</Link>
        </p>
    </AuthShell>
</template>

<style scoped>
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
    border: 1px solid #ddd6e2;
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

.auth-login-link {
    margin: 1rem 0 0;
    text-align: center;
    font-size: 0.85rem;
    color: var(--app-muted);
}

.auth-login-link a {
    color: var(--app-accent-ink);
    font-weight: 600;
    text-decoration: none;
}
</style>
