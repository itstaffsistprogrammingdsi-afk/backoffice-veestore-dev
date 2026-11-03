<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, defineAsyncComponent, reactive, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import ModuleNav from '../../components/ModuleNav.vue';

const LocationPicker = defineAsyncComponent(() => import('../../components/LocationPicker.vue'));

interface Option { value: string; label: string }

interface Field {
    key: string;
    label: string;
    type?: 'text' | 'number' | 'textarea' | 'select' | 'date' | 'file' | 'multiselect' | 'map';
    required?: boolean;
    options?: Option[];
    placeholder?: string;
    latitudeKey?: string;
    longitudeKey?: string;
}

interface Column {
    key: string;
    label: string;
    align?: 'start' | 'end' | 'center';
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface RowAction {
    label: string;
    icon?: string;
    url: string;
    method?: 'get' | 'post' | 'patch' | 'delete';
    prompt?: { label: string; key: string; default?: number; min?: number; max?: number };
    visibleWhen?: { key: string; equals: any };
}

interface Props {
    title: string;
    description: string;
    eyebrow?: string;
    searchPlaceholder?: string;
    items: Paginator<Record<string, any>>;
    columns: Column[];
    fields: Field[];
    rowActions?: RowAction[];
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
const showCreate = ref(false);
const editingId = ref<string | null>(null);

const emptyPayload = () => Object.fromEntries(
    props.fields
        .filter((field) => field.type !== 'map')
        .map((field) => [field.key, field.type === 'multiselect' ? [] : '']),
);

const createForm = useForm<Record<string, any>>(emptyPayload());
const editForm = useForm<Record<string, any>>(emptyPayload());

const payloadForSubmit = (source: Record<string, any>) => {
    const payload = { ...source };

    for (const field of props.fields) {
        if (field.type === 'file' && !(payload[field.key] instanceof File)) {
            delete payload[field.key];
        }
    }

    return payload;
};

const onFileChange = (key: string, event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? '';

    createForm[key] = file;
    editForm[key] = file;
};

const setCoordinates = (isEdit: boolean, field: Field, value: { latitude: number; longitude: number } | null) => {
    const form = formFor(isEdit);
    const latitudeKey = field.latitudeKey ?? 'lat';
    const longitudeKey = field.longitudeKey ?? 'lng';

    form[latitudeKey] = value?.latitude ?? '';
    form[longitudeKey] = value?.longitude ?? '';
};

const rowActionVisible = (action: RowAction, row: Record<string, any>) =>
    !action.visibleWhen || row[action.visibleWhen.key] === action.visibleWhen.equals;

const openPostInNewTab = (url: string, data: Record<string, string | number>) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.target = '_blank';

    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const fields: Record<string, string | number> = { _token: token, ...data };

    for (const [key, value] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = String(value);
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    form.remove();
};

const runRowAction = (action: RowAction, row: Record<string, any>) => {
    let url = action.url.replace('__ID__', String(row.id));
    let payload: Record<string, number> = {};

    if (action.prompt) {
        const raw = window.prompt(action.prompt.label, String(action.prompt.default ?? 1));
        if (raw === null) return;

        const value = Number(raw);
        const min = action.prompt.min ?? 1;
        const max = action.prompt.max ?? 100;

        if (!Number.isFinite(value) || value < min || value > max) {
            window.alert(`Nilai harus antara ${min} dan ${max}.`);
            return;
        }

        payload = { [action.prompt.key]: value };
    }

    if (action.method === 'post') {
        openPostInNewTab(url, payload);
        return;
    }

    if (action.method && action.method !== 'get') {
        router[action.method](url, payload);
        return;
    }

    if (Object.keys(payload).length > 0) {
        url += (url.includes('?') ? '&' : '?') + new URLSearchParams(Object.entries(payload).map(([k, v]) => [k, String(v)])).toString();
    }

    router.visit(url);
};

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

const openCreate = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreate.value = true;
    editingId.value = null;
};

const cancelCreate = () => {
    showCreate.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm
        .transform((data) => payloadForSubmit(data))
        .post(props.urls.store, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                showCreate.value = false;
                createForm.reset();
            },
        });
};

const startEdit = (row: Record<string, any>) => {
    editingId.value = String(row.id);
    editForm.clearErrors();

    for (const field of props.fields) {
        if (field.type === 'map') continue;

        editForm[field.key] = field.type === 'multiselect'
            ? (Array.isArray(row[field.key]) ? [...row[field.key]] : [])
            : (row[field.key] ?? '');
    }
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!editingId.value) return;

    editForm
        .transform((data) => payloadForSubmit(data))
        .put(props.urls.update.replace('__ID__', editingId.value), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                editingId.value = null;
                editForm.reset();
            },
        });
};

const remove = (row: Record<string, any>) => {
    if (!window.confirm(`Hapus data ini?`)) return;

    router.delete(props.urls.destroy.replace('__ID__', row.id), {
        preserveScroll: true,
    });
};

const valueOf = (row: Record<string, any>, key: string) => {
    return key.split('.').reduce<any>((value, part) => value?.[part], row) ?? '-';
};

const display = (row: Record<string, any>, key: string) => {
    const value = valueOf(row, key);

    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }

    if (typeof value === 'string' && (/^\d{4}-\d{2}-\d{2}T/.test(value) || key.endsWith('_at'))) {
        const date = new Date(value);

        if (!Number.isNaN(date.getTime())) {
            return new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }).format(date);
        }
    }

    return value;
};

const formFor = (isEdit: boolean) => (isEdit ? editForm : createForm);
</script>

<template>
    <Head :title="props.title" />

    <div class="crud-page">
        <div class="crud-header">
            <div>
                <span class="crud-eyebrow">{{ props.eyebrow ?? 'Master Data' }}</span>
                <h1>{{ props.title }}</h1>
                <p>{{ props.description }}</p>
            </div>
            <div class="d-flex gap-2">
                <form class="d-flex gap-2" @submit.prevent="applySearch">
                    <input v-model="search.q" class="form-control form-control-sm" type="search" :placeholder="props.searchPlaceholder ?? 'Cari...'" />
                    <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="mdi mdi-magnify"></i></button>
                    <button class="btn btn-outline-secondary btn-sm" type="button" @click="resetSearch"><i class="mdi mdi-refresh"></i></button>
                </form>
                <button class="btn btn-light btn-sm" type="button" @click="openCreate">
                    <i class="mdi mdi-plus me-1"></i>
                    Tambah
                </button>
            </div>
        </div>

        <ModuleNav module-key="promoModule" aria-label="Modul Promo" />
        <ModuleNav module-key="masterDataModule" aria-label="Modul Master Produk" />
        <ModuleNav module-key="loyaltyModule" aria-label="Modul Loyalty" />

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div v-if="showCreate" class="card crud-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Tambah {{ props.title }}</h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" @click="cancelCreate">Tutup</button>
            </div>
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="submitCreate">
                    <div v-for="field in fields" :key="field.key" :class="field.type === 'map' ? 'col-12' : 'col-md-4'">
                        <label class="form-label">{{ field.label }}</label>
                        <LocationPicker
                            v-if="field.type === 'map'"
                            :latitude="createForm[field.latitudeKey ?? 'lat']"
                            :longitude="createForm[field.longitudeKey ?? 'lng']"
                            @update:coordinates="setCoordinates(false, field, $event)"
                        />
                        <select v-else-if="field.type === 'multiselect'" v-model="createForm[field.key]" class="form-select form-select-sm" multiple>
                            <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <select v-else-if="field.type === 'select'" v-model="createForm[field.key]" class="form-select form-select-sm" :required="field.required">
                            <option value="">Pilih {{ field.label }}</option>
                            <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <textarea v-else-if="field.type === 'textarea'" v-model="createForm[field.key]" class="form-control form-control-sm" :required="field.required" rows="2"></textarea>
                        <input v-else-if="field.type === 'file'" class="form-control form-control-sm" type="file" :required="field.required" @change="onFileChange(field.key, $event)" />
                        <input v-else v-model="createForm[field.key]" class="form-control form-control-sm" :type="field.type ?? 'text'" :step="field.type === 'number' ? 'any' : undefined" :required="field.required" :placeholder="field.placeholder" />
                        <div v-if="createForm.errors[field.key]" class="text-danger small mt-1">{{ createForm.errors[field.key] }}</div>
                    </div>
                    <div class="col-12 d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-primary" type="submit" :disabled="createForm.processing">Simpan</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="cancelCreate">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card crud-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th v-for="column in columns" :key="column.key" :class="`text-${column.align ?? 'start'}`">{{ column.label }}</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="row in items.data" :key="row.id">
                            <tr>
                                <td v-for="column in columns" :key="column.key" :class="`text-${column.align ?? 'start'}`">{{ display(row, column.key) }}</td>
                                <td class="text-end crud-actions">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <template v-if="editingId === String(row.id)">
                                            <button class="btn btn-sm btn-success" type="button" :disabled="editForm.processing" @click="submitEdit">Simpan</button>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" @click="cancelEdit">Batal</button>
                                        </template>
                                        <template v-else>
                                            <button v-for="action in (rowActions ?? []).filter((item) => rowActionVisible(item, row))" :key="action.label" class="btn btn-sm btn-outline-secondary" type="button" :aria-label="action.label" :title="action.label" @click="runRowAction(action, row)">
                                                <i class="mdi" :class="action.icon ?? 'mdi-dots-horizontal'"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary" type="button" aria-label="Edit data" title="Edit" @click="startEdit(row)">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" aria-label="Hapus data" title="Hapus" @click="remove(row)">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="editingId === String(row.id)">
                                <td :colspan="columns.length + 1" class="crud-edit">
                                    <form class="row g-2 align-items-end" @submit.prevent="submitEdit">
                                        <div v-for="field in fields" :key="field.key" :class="field.type === 'map' ? 'col-12' : 'col-md-4'">
                                            <label class="form-label">{{ field.label }}</label>
                                            <LocationPicker
                                                v-if="field.type === 'map'"
                                                :latitude="editForm[field.latitudeKey ?? 'lat']"
                                                :longitude="editForm[field.longitudeKey ?? 'lng']"
                                                @update:coordinates="setCoordinates(true, field, $event)"
                                            />
                                            <select v-else-if="field.type === 'multiselect'" v-model="editForm[field.key]" class="form-select form-select-sm" multiple>
                                                <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">{{ option.label }}</option>
                                            </select>
                                            <select v-else-if="field.type === 'select'" v-model="editForm[field.key]" class="form-select form-select-sm" :required="field.required">
                                                <option value="">Pilih {{ field.label }}</option>
                                                <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">{{ option.label }}</option>
                                            </select>
                                            <textarea v-else-if="field.type === 'textarea'" v-model="editForm[field.key]" class="form-control form-control-sm" :required="field.required" rows="2"></textarea>
                                            <input v-else-if="field.type === 'file'" class="form-control form-control-sm" type="file" @change="onFileChange(field.key, $event)" />
                                            <input v-else v-model="editForm[field.key]" class="form-control form-control-sm" :type="field.type ?? 'text'" :step="field.type === 'number' ? 'any' : undefined" :required="field.required" />
                                            <div v-if="editForm.errors[field.key]" class="text-danger small mt-1">{{ editForm.errors[field.key] }}</div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="items.data.length === 0">
                            <td :colspan="columns.length + 1" class="crud-empty">Belum ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">Menampilkan {{ items.from ?? 0 }} - {{ items.to ?? 0 }} dari {{ items.total }} data</small>
                <PaginationLinks :links="items.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.crud-page { --crud-ink: var(--app-ink); --crud-border: var(--app-border); --crud-muted: var(--app-muted); color: var(--crud-ink); }
.crud-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--crud-ink); color: #fff; }
.crud-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.crud-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.crud-header p { margin: 0; color: var(--app-hero-muted); }
.crud-card { border: 1px solid var(--crud-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.crud-card th { color: var(--crud-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.crud-actions { white-space: nowrap; }
.crud-actions .btn { padding: .25rem .4rem; }
.crud-edit { background: var(--app-surface-2); }
.crud-empty { padding: 2.5rem 1rem; color: var(--crud-muted); text-align: center; }
@media (max-width: 767.98px) { .crud-header { align-items: flex-start; flex-direction: column; } }
</style>
