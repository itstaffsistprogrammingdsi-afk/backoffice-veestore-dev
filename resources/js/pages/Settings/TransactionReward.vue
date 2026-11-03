<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import ModuleNav from '../../components/ModuleNav.vue';

interface Props {
    reward: number;
    updateUrl: string;
    resetUrl: string;
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const form = useForm({
    reward_per_transaction: props.reward,
});

const submit = () => {
    form.post(props.updateUrl, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Customer Loyalty Point Reward" />

    <div class="settings-page">
        <div class="settings-header">
            <span class="settings-eyebrow">Loyalty / Loyalty Reward</span>
            <h1>Customer Loyalty Point Reward</h1>
            <p>Jumlah poin otomatis yang diberikan setiap transaksi pelanggan.</p>
        </div>

        <ModuleNav module-key="loyaltyModule" aria-label="Modul Loyalty" />

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card settings-card">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-label" for="transaction-reward">Reward per Transaction (LP)</label>
                            <div class="input-group">
                                <input
                                    id="transaction-reward"
                                    v-model.number="form.reward_per_transaction"
                                    type="number"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.reward_per_transaction }"
                                    min="0"
                                    max="1000000"
                                    required
                                />
                                <span class="input-group-text">LP</span>
                                <div v-if="form.errors.reward_per_transaction" class="invalid-feedback d-block">
                                    {{ form.errors.reward_per_transaction }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary" type="submit" :disabled="form.processing">
                            <i class="mdi mdi-content-save-outline me-1"></i>
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                        <Link :href="props.resetUrl" class="btn btn-outline-secondary">Reset</Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.settings-page {
    --settings-ink: var(--app-ink);
    --settings-muted: var(--app-muted);
    --settings-border: var(--app-border);
    color: var(--settings-ink);
}

.settings-header {
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: var(--settings-ink);
    color: #fff;
}

.settings-eyebrow {
    display: inline-block;
    margin-bottom: 0.5rem;
    color: var(--app-hero-eyebrow);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.settings-header h1 {
    margin: 0 0 0.35rem;
    font-size: 1.5rem;
}

.settings-header p {
    margin: 0;
    color: var(--app-hero-muted);
}

.settings-card {
    border: 1px solid var(--settings-border);
    border-radius: 0.9rem;
    box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05);
}
</style>
