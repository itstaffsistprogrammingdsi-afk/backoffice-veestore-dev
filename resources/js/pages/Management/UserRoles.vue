<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';

interface UserRow {
    id: string;
    name: string | null;
    username: string | null;
    email: string | null;
}

interface RoleRow {
    id: string;
    name: string;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    users: Paginator<UserRow>;
    roles: RoleRow[];
    userRoleId: Record<string, string>;
    filters: { q: string };
    urls: {
        index: string;
        sync: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const search = reactive({ q: props.filters.q ?? '' });
const selectedRoles = reactive<Record<string, string>>({});

for (const user of props.users.data) {
    selectedRoles[user.id] = props.userRoleId[user.id] ?? '';
}

const applySearch = () => {
    router.get(props.urls.index, { q: search.q || undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetSearch = () => {
    search.q = '';
    applySearch();
};

const saveRole = (user: UserRow) => {
    router.post(
        props.urls.sync.replace('__ID__', user.id),
        { role_id: selectedRoles[user.id] ?? '' },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="User Roles" />

    <div class="management-page">
        <div class="management-header">
            <div>
                <span class="management-eyebrow">Management</span>
                <h1>User Roles</h1>
                <p>Atur role backoffice untuk setiap user.</p>
            </div>
            <form class="d-flex gap-2" @submit.prevent="applySearch">
                <input v-model="search.q" class="form-control form-control-sm" type="search" placeholder="Cari user..." />
                <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="mdi mdi-magnify"></i></button>
                <button class="btn btn-outline-secondary btn-sm" type="button" @click="resetSearch"><i class="mdi mdi-refresh"></i></button>
            </form>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card management-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th style="min-width: 220px;">Role</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="fw-semibold">{{ user.name ?? '-' }}</td>
                            <td>{{ user.username ?? '-' }}</td>
                            <td>{{ user.email ?? '-' }}</td>
                            <td>
                                <select v-model="selectedRoles[user.id]" class="form-select form-select-sm">
                                    <option value="">Tanpa role</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.id">
                                        {{ role.name }}
                                    </option>
                                </select>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-primary" type="button" @click="saveRole(user)">
                                    <i class="mdi mdi-content-save-outline me-1"></i>
                                    Simpan
                                </button>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="management-empty">Belum ada user.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">
                    Menampilkan {{ users.from ?? 0 }} - {{ users.to ?? 0 }} dari {{ users.total }} user
                </small>
                <PaginationLinks :links="users.links" />
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

.management-card th {
    color: var(--management-muted);
    font-size: 0.75rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.management-empty {
    padding: 2.5rem 1rem;
    color: var(--management-muted);
    text-align: center;
}

@media (max-width: 767.98px) {
    .management-header {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
