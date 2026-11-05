<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';

interface RoleRow {
    id: string;
    name: string;
}

interface PermissionRow {
    id: string;
    name: string;
    display_action: string;
    display_resource: string;
}

interface Props {
    roles: RoleRow[];
    permissionGroups: Record<string, PermissionRow[]>;
    rolePermissions: Record<string, string[]>;
    updateUrl: string;
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const matrix = reactive<Record<string, string[]>>({});
const processing = ref(false);

for (const role of props.roles) {
    matrix[role.id] = [...(props.rolePermissions[role.id] ?? [])];
}

const groups = computed(() => Object.entries(props.permissionGroups));

const submit = () => {
    processing.value = true;

    router.put(props.updateUrl, { matrix }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Hak Akses Peran" />

    <div class="management-page">
        <div class="management-header">
            <div>
                <span class="management-eyebrow">Management</span>
                <h1>Hak Akses Peran</h1>
                <p>Atur hak akses untuk setiap peran. Perubahan disimpan per peran.</p>
            </div>
            <button class="btn btn-light btn-sm" type="button" :disabled="processing" @click="submit">
                <i class="mdi mdi-content-save-outline me-1"></i>
                {{ processing ? 'Menyimpan...' : 'Simpan Hak Akses' }}
            </button>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card management-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0 permission-table">
                    <thead>
                        <tr>
                            <th style="min-width: 260px;">Hak Akses</th>
                            <th v-for="role in roles" :key="role.id" class="text-center">
                                {{ role.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="[group, permissions] in groups" :key="group">
                            <tr class="table-light">
                                <td :colspan="roles.length + 1" class="fw-bold">{{ group }}</td>
                            </tr>
                            <tr v-for="permission in permissions" :key="permission.id">
                                <td>
                                    <div class="fw-semibold">{{ permission.display_action }}</div>
                                    <small class="text-muted">{{ permission.display_resource }}</small>
                                </td>
                                <td v-for="role in roles" :key="role.id" class="text-center">
                                    <input
                                        v-model="matrix[role.id]"
                                        class="form-check-input"
                                        type="checkbox"
                                        :value="permission.name"
                                    />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.management-page {
    --management-ink: var(--app-ink);
    --management-border: var(--app-border);
    --management-muted: var(--app-muted);
    color: var(--management-ink);
}

.management-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: var(--management-ink);
    color: #fff;
}

.management-eyebrow {
    display: inline-block;
    margin-bottom: 0.5rem;
    color: var(--app-hero-eyebrow);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.management-header h1 {
    margin: 0 0 0.35rem;
    font-size: 1.5rem;
}

.management-header p {
    margin: 0;
    color: var(--app-hero-muted);
}

.management-card {
    border: 1px solid var(--management-border);
    border-radius: 0.9rem;
    box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05);
}

.permission-table th {
    color: var(--management-muted);
    font-size: 0.75rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

@media (max-width: 767.98px) {
    .management-header {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
