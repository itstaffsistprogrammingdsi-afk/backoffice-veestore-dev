<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';

interface RoleRow {
    id: string;
    name: string;
    guard_name: string;
    created_at: string | null;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    roles: Paginator<RoleRow>;
    filters: { q: string };
    urls: {
        index: string;
        store: string;
        update: string;
        destroy: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const search = reactive({ q: props.filters.q ?? '' });
const createForm = useForm({ name: '' });
const editingId = ref<string | null>(null);
const editingName = ref('');

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

const createRole = () => {
    createForm.post(props.urls.store, {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const startEdit = (role: RoleRow) => {
    editingId.value = role.id;
    editingName.value = role.name;
};

const cancelEdit = () => {
    editingId.value = null;
    editingName.value = '';
};

const updateRole = (id: string) => {
    router.put(props.urls.update.replace('__ID__', id), {
        name: editingName.value,
    }, {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
};

const deleteRole = (role: RoleRow) => {
    if (!window.confirm(`Hapus role ${role.name}?`)) {
        return;
    }

    router.delete(props.urls.destroy.replace('__ID__', role.id), {
        preserveScroll: true,
    });
};

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};
</script>

<template>
    <Head title="Backoffice Roles" />

    <div class="management-page">
        <div class="management-header">
            <div>
                <span class="management-eyebrow">Management</span>
                <h1>Backoffice Roles</h1>
                <p>Kelola role yang dapat diberikan ke user backoffice.</p>
            </div>
            <form class="d-flex gap-2" @submit.prevent="applySearch">
                <input v-model="search.q" class="form-control form-control-sm" type="search" placeholder="Cari role..." />
                <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="mdi mdi-magnify"></i></button>
                <button class="btn btn-outline-secondary btn-sm" type="button" @click="resetSearch"><i class="mdi mdi-refresh"></i></button>
            </form>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card management-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="createRole">
                    <div class="col-md-5">
                        <label class="form-label">Role baru</label>
                        <input
                            v-model="createForm.name"
                            class="form-control"
                            :class="{ 'is-invalid': createForm.errors.name }"
                            placeholder="Nama role"
                            required
                        />
                        <div v-if="createForm.errors.name" class="invalid-feedback d-block">
                            {{ createForm.errors.name }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" type="submit" :disabled="createForm.processing">
                            <i class="mdi mdi-plus me-1"></i>
                            Tambah Role
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card management-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama Role</th>
                            <th>Guard</th>
                            <th>Dibuat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="role in roles.data" :key="role.id">
                            <td>
                                <input
                                    v-if="editingId === role.id"
                                    v-model="editingName"
                                    class="form-control form-control-sm"
                                />
                                <span v-else class="fw-semibold">{{ role.name }}</span>
                            </td>
                            <td><span class="badge bg-secondary">{{ role.guard_name }}</span></td>
                            <td>{{ formatDate(role.created_at) }}</td>
                            <td class="text-end">
                                <template v-if="editingId === role.id">
                                    <button class="btn btn-sm btn-success me-1" type="button" @click="updateRole(role.id)">
                                        <i class="mdi mdi-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="cancelEdit">
                                        <i class="mdi mdi-close"></i>
                                    </button>
                                </template>
                                <template v-else>
                                    <button class="btn btn-sm btn-outline-primary me-1" type="button" @click="startEdit(role)">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" type="button" @click="deleteRole(role)">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </template>
                            </td>
                        </tr>
                        <tr v-if="roles.data.length === 0">
                            <td colspan="4" class="management-empty">Belum ada role.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">
                    Menampilkan {{ roles.from ?? 0 }} - {{ roles.to ?? 0 }} dari {{ roles.total }} role
                </small>
                <PaginationLinks :links="roles.links" />
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
    color: #f9a8d4;
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
