<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthShell from '../../components/AuthShell.vue';
import AppDialog from '@/components/ui/AppDialog.vue';

interface Props {
    urls: {
        login: string;
        forgot: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });
const loginInput = ref<HTMLInputElement | null>(null);
const passwordInput = ref<HTMLInputElement | null>(null);
const showPassword = ref(false);
const capsLockOn = ref(false);
const failureOpen = ref(false);
const failureReason = ref('');

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const showFailure = async () => {
    await nextTick();

    const reason = flash.value.error || form.errors.login || form.errors.password;

    if (!reason) return;

    failureReason.value = reason;
    failureOpen.value = true;
};

const submit = () => {
    form.post(props.urls.login, {
        preserveScroll: true,
        onError: () => {
            void showFailure();
        },
        onFinish: () => {
            void showFailure();
        },
    });
};

const dismissFailure = () => {
    failureOpen.value = false;
    failureReason.value = '';
};

const retryLogin = () => {
    dismissFailure();
    void nextTick(() => {
        passwordInput.value?.focus();
        passwordInput.value?.select();
    });
};

const syncCapsLock = (event: KeyboardEvent) => {
    capsLockOn.value = event.getModifierState('CapsLock');
};

const focusFirstError = async () => {
    await nextTick();

    if (form.errors.login) {
        loginInput.value?.focus();
        return;
    }

    if (form.errors.password) {
        passwordInput.value?.focus();
    }
};

watch(() => [form.errors.login, form.errors.password], () => {
    void focusFirstError();
});

watch(() => flash.value.error, (reason) => {
    if (!reason) return;

    failureReason.value = reason;
    failureOpen.value = true;
}, { immediate: true });
</script>

<template>
    <Head title="Masuk" />

    <AuthShell title="Masuk" description="Gunakan akun backoffice untuk melanjutkan.">
        <div v-if="flash.success" class="auth-alert auth-alert-success" role="status">{{ flash.success }}</div>

        <form @submit.prevent="submit">
            <div class="auth-field">
                <label for="login">Email atau Username</label>
                <input
                    id="login"
                    ref="loginInput"
                    v-model="form.login"
                    type="text"
                    autocomplete="username"
                    autocapitalize="none"
                    spellcheck="false"
                    enterkeyhint="next"
                    :class="{ 'is-invalid': form.errors.login }"
                    :aria-invalid="Boolean(form.errors.login)"
                    :aria-describedby="form.errors.login ? 'login-error' : undefined"
                    required
                />
                <small v-if="form.errors.login" id="login-error" class="auth-error" role="alert">{{ form.errors.login }}</small>
            </div>

            <div class="auth-field">
                <label for="password">Kata Sandi</label>
                <div class="auth-password">
                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        enterkeyhint="go"
                        :class="{ 'is-invalid': form.errors.password }"
                        :aria-invalid="Boolean(form.errors.password)"
                        :aria-describedby="[
                            form.errors.password ? 'password-error' : '',
                            capsLockOn ? 'password-caps' : '',
                        ].filter(Boolean).join(' ') || undefined"
                        required
                        @keydown="syncCapsLock"
                        @keyup="syncCapsLock"
                        @blur="capsLockOn = false"
                    />
                    <button
                        type="button"
                        class="auth-password-toggle"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        :aria-pressed="showPassword"
                        @click="showPassword = !showPassword"
                    >
                        <i class="mdi" :class="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'" aria-hidden="true"></i>
                    </button>
                </div>
                <small v-if="form.errors.password" id="password-error" class="auth-error" role="alert">{{ form.errors.password }}</small>
                <small v-if="capsLockOn" id="password-caps" class="auth-caps" aria-live="polite">Caps Lock aktif</small>
            </div>

            <div class="auth-options">
                <label class="auth-check">
                    <input v-model="form.remember" type="checkbox" />
                    Ingat saya
                </label>
                <Link :href="props.urls.forgot" class="auth-forgot">Lupa password?</Link>
            </div>

            <button class="auth-button" type="submit" :disabled="form.processing" :aria-busy="form.processing">
                <i v-if="form.processing" class="mdi mdi-loading mdi-spin" aria-hidden="true"></i>
                {{ form.processing ? 'Memproses…' : 'Masuk' }}
            </button>
        </form>

        <AppDialog
            :open="failureOpen"
            title="Login gagal"
            :description="failureReason"
            confirm-label="Coba lagi"
            cancel-label="Tutup"
            variant="danger"
            initial-focus="confirm"
            @update:open="dismissFailure"
            @confirm="retryLogin"
            @cancel="dismissFailure"
        />
    </AuthShell>
</template>

<style scoped>
.auth-alert {
    padding: 0.75rem 0.9rem;
    margin-bottom: 1rem;
    border-radius: 0.625rem;
    font-size: 0.875rem;
    line-height: 1.5;
}

.auth-alert-success {
    background: var(--app-success-bg);
    color: var(--app-success-ink);
}

.auth-alert-danger {
    background: var(--app-danger-bg);
    color: var(--app-danger-ink);
}

.auth-field {
    margin-bottom: 1rem;
}

.auth-field label {
    display: block;
    margin-bottom: 0.375rem;
    color: var(--app-ink);
    font-size: 0.875rem;
    font-weight: 500;
}

.auth-field input {
    width: 100%;
    min-height: 3rem;
    padding: 0 0.875rem;
    border: 1px solid var(--app-control-border);
    border-radius: 0.625rem;
    background: var(--app-surface-2);
    color: var(--app-ink);
    font: inherit;
    font-size: 1rem;
}

.auth-field input::placeholder {
    color: var(--app-muted);
}

.auth-field input:hover:not(:disabled):not(:focus) {
    border-color: var(--app-muted);
}

.auth-field input:-webkit-autofill,
.auth-field input:-webkit-autofill:hover,
.auth-field input:-webkit-autofill:focus {
    -webkit-text-fill-color: var(--app-ink);
    box-shadow: 0 0 0 1000px var(--app-surface-2) inset;
    caret-color: var(--app-ink);
}

.auth-field input:focus {
    border-color: var(--app-accent);
    outline: 2px solid var(--app-accent);
    outline-offset: 1px;
}

.auth-field input.is-invalid {
    border-color: var(--app-danger-ink);
}

.auth-field input.is-invalid:focus {
    outline-color: var(--app-danger-ink);
}

.auth-password {
    position: relative;
}

.auth-password input {
    padding-right: 3.5rem;
}

.auth-password-toggle {
    position: absolute;
    top: 0.125rem;
    right: 0.125rem;
    display: grid;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    padding: 0;
    border: 0;
    border-radius: 0.5rem;
    background: transparent;
    color: var(--app-muted);
    font-size: 1.15rem;
    cursor: pointer;
}

.auth-password-toggle:hover {
    background: var(--app-surface-3);
    color: var(--app-ink);
}

.auth-error,
.auth-caps {
    display: block;
    margin-top: 0.375rem;
    font-size: 0.8125rem;
    line-height: 1.4;
}

.auth-error {
    color: var(--app-danger-ink);
}

.auth-caps {
    color: var(--app-warning-ink);
}

.auth-options {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem 1rem;
    margin: 0.25rem 0 1.25rem;
}

.auth-check {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 2.75rem;
    color: var(--app-muted);
    font-size: 0.875rem;
    cursor: pointer;
}

.auth-check input {
    width: 1.125rem;
    height: 1.125rem;
    margin: 0;
    accent-color: var(--app-accent-strong);
    cursor: pointer;
}

.auth-forgot {
    display: inline-flex;
    align-items: center;
    min-height: 2.75rem;
    color: var(--app-accent-ink);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
}

.auth-forgot:hover {
    text-decoration: underline;
}

.auth-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    min-height: 3rem;
    padding: 0 1rem;
    border: 0;
    border-radius: 0.625rem;
    background: var(--app-accent-strong);
    color: var(--app-on-accent);
    font: inherit;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color var(--app-dur-fast) var(--app-ease-out);
}

.auth-button:hover:not(:disabled) {
    background: var(--app-accent-strong-hover);
}

.auth-button:disabled {
    opacity: 0.65;
    cursor: wait;
}

.auth-button .mdi {
    font-size: 1.1rem;
}

@media (max-width: 900px) {
    .auth-field input {
        min-height: 3.25rem;
    }

    .auth-password-toggle {
        top: 0.375rem;
        right: 0.375rem;
        width: 2.75rem;
        height: 2.75rem;
    }

    .auth-button {
        min-height: 3.25rem;
    }
}
</style>
