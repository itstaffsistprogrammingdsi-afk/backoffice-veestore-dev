<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, defineAsyncComponent, nextTick, reactive, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import ModuleNav from '../../components/ModuleNav.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

const LocationPicker = defineAsyncComponent(() => import('../../components/LocationPicker.vue'));

interface Option { value: string; label: string }

interface Field {
    key: string;
    label: string;
    type?: 'text' | 'email' | 'number' | 'textarea' | 'select' | 'date' | 'file' | 'multiselect' | 'map';
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
    type?: 'text' | 'image';
}

interface ScanConfig {
    url: string;
    field: string;
    imageUrlKey?: string;
    title?: string;
    hint?: string;
}

interface SelectFilter {
    key: string;
    label: string;
    options: Option[];
    value?: string | null;
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
    scan?: ScanConfig | null;
    rowActions?: RowAction[];
    filters: { q: string };
    extraFilters?: SelectFilter[];
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
const extraSearch = reactive<Record<string, string>>(
    Object.fromEntries((props.extraFilters ?? []).map((filter) => [filter.key, filter.value ?? ''])),
);
const showCreate = ref(false);
const editingId = ref<string | null>(null);
const scanLoading = ref(false);
const scanError = ref('');
const scanSuccess = ref('');
const scanWarnings = ref<string[]>([]);
const scannedImageUrl = ref<string | null>(null);
const existingImageUrl = ref<string | null>(null);

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

    if (props.scan && !(payload[props.scan.field] instanceof File)) {
        delete payload[props.scan.field];
    }

    return payload;
};

const onFileChange = (key: string, event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? '';

    createForm[key] = file;
    editForm[key] = file;
};

const clearScanFile = () => {
    if (!props.scan) return;

    delete createForm[props.scan.field];
    delete editForm[props.scan.field];
};

const releaseScanPreview = () => {
    if (scannedImageUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(scannedImageUrl.value);
    }

    scannedImageUrl.value = null;
};

const resetScanState = () => {
    releaseScanPreview();
    scanError.value = '';
    scanSuccess.value = '';
    scanWarnings.value = [];
    existingImageUrl.value = null;
};

const applyScannedData = (isEdit: boolean, data: Record<string, any>) => {
    const form = formFor(isEdit);

    for (const field of props.fields) {
        if (field.type === 'map') continue;

        const value = data[field.key];

        if (value === undefined || value === null || value === '') continue;

        if (field.type === 'multiselect') {
            if (Array.isArray(value)) {
                form[field.key] = value.map((item) => String(item));
            }

            continue;
        }

        if (field.type === 'select') {
            const stringValue = String(value);
            const options = field.options ?? [];

            if (options.length > 0 && !options.some((option) => option.value === stringValue)) {
                continue;
            }

            form[field.key] = stringValue;
            continue;
        }

        form[field.key] = String(value);
    }
};

const scanVoucherImage = async (file: File, isEdit: boolean) => {
    if (!props.scan) return;

    const targetIsEdit = isEdit;
    const form = formFor(targetIsEdit);
    form[props.scan.field] = file;

    releaseScanPreview();
    scannedImageUrl.value = URL.createObjectURL(file);
    scanLoading.value = true;
    scanError.value = '';
    scanSuccess.value = '';
    scanWarnings.value = [];

    const body = new FormData();
    body.append(props.scan.field, file);

    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    try {
        const response = await fetch(props.scan.url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body,
            credentials: 'same-origin',
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationMessage = payload?.errors?.[props.scan.field]?.[0];
            throw new Error(validationMessage ?? payload?.message ?? 'Gambar voucher gagal dibaca.');
        }

        if (targetIsEdit !== Boolean(editingId.value)) {
            return;
        }

        applyScannedData(targetIsEdit, payload.data ?? {});
        scanWarnings.value = Array.isArray(payload.warnings) ? payload.warnings : [];
        scanSuccess.value = 'Data voucher berhasil dibaca. Periksa kembali sebelum menyimpan.';
    } catch (error) {
        scanError.value = error instanceof Error ? error.message : 'Gambar voucher gagal dibaca.';
    } finally {
        scanLoading.value = false;
    }
};

const onScanFileChange = (event: Event, isEdit: boolean) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    void scanVoucherImage(file, isEdit);
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

const actionPrompt = ref<{
    action: RowAction;
    row: Record<string, any>;
    value: string;
    error: string;
} | null>(null);
const actionPromptInputRef = ref<HTMLInputElement | null>(null);

const executeRowAction = (
    action: RowAction,
    row: Record<string, any>,
    payload: Record<string, number>,
) => {
    let url = action.url.replace('__ID__', String(row.id));

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

const closeActionPrompt = () => {
    actionPrompt.value = null;
};

const submitActionPrompt = () => {
    const prompt = actionPrompt.value;

    if (!prompt?.action.prompt) return;

    const value = Number(prompt.value);
    const min = prompt.action.prompt.min ?? 1;
    const max = prompt.action.prompt.max ?? 100;

    if (!Number.isFinite(value) || value < min || value > max) {
        prompt.error = `Nilai harus antara ${min} dan ${max}.`;

        return;
    }

    const payload = { [prompt.action.prompt.key]: value };
    const action = prompt.action;
    const row = prompt.row;
    closeActionPrompt();
    executeRowAction(action, row, payload);
};

const runRowAction = (action: RowAction, row: Record<string, any>) => {
    if (action.prompt) {
        actionPrompt.value = {
            action,
            row,
            value: String(action.prompt.default ?? 1),
            error: '',
        };

        void nextTick(() => actionPromptInputRef.value?.focus());

        return;
    }

    executeRowAction(action, row, {});
};

const applySearch = () => {
    const params: Record<string, string | undefined> = { q: search.q || undefined };

    for (const [key, value] of Object.entries(extraSearch)) {
        params[key] = value || undefined;
    }

    router.get(props.urls.index, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetSearch = () => {
    search.q = '';

    for (const key of Object.keys(extraSearch)) {
        extraSearch[key] = '';
    }

    applySearch();
};

const openCreate = () => {
    createForm.reset();
    createForm.clearErrors();
    clearScanFile();
    resetScanState();
    showCreate.value = true;
    editingId.value = null;
};

const cancelCreate = () => {
    showCreate.value = false;
    createForm.reset();
    clearScanFile();
    resetScanState();
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
                clearScanFile();
                resetScanState();
            },
        });
};

const startEdit = (row: Record<string, any>) => {
    editingId.value = String(row.id);
    editForm.clearErrors();
    resetScanState();
    existingImageUrl.value = props.scan?.imageUrlKey
        ? (row[props.scan.imageUrlKey] ?? null)
        : null;

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
    clearScanFile();
    resetScanState();
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
                clearScanFile();
                resetScanState();
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
            <div class="d-flex flex-wrap gap-2">
                <form class="d-flex flex-wrap gap-2" @submit.prevent="applySearch">
                    <label class="visually-hidden" for="crud-search">Cari data</label>
                    <input id="crud-search" v-model="search.q" class="form-control form-control-sm" type="search" :placeholder="props.searchPlaceholder ?? 'Cari...'" />
                    <template v-for="filter in (props.extraFilters ?? [])" :key="filter.key">
                        <div v-if="filter.options.length > 8" class="crud-filter-select">
                            <SearchableSelect
                                v-model="extraSearch[filter.key]"
                                :options="filter.options"
                                :placeholder="`Semua ${filter.label.toLowerCase()}`"
                                :search-placeholder="`Cari ${filter.label.toLowerCase()}...`"
                                :aria-label="filter.label"
                            />
                        </div>
                        <select
                            v-else
                            v-model="extraSearch[filter.key]"
                            class="form-select form-select-sm crud-filter-select"
                            :aria-label="filter.label"
                        >
                            <option value="">Semua {{ filter.label.toLowerCase() }}</option>
                            <option v-for="option in filter.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </template>
                    <button class="btn btn-outline-secondary btn-sm" type="submit" aria-label="Cari" title="Cari"><i class="mdi mdi-magnify" aria-hidden="true"></i></button>
                    <button class="btn btn-outline-secondary btn-sm" type="button" aria-label="Reset pencarian" title="Reset pencarian" @click="resetSearch"><i class="mdi mdi-refresh" aria-hidden="true"></i></button>
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

        <div v-if="flash.success" class="alert alert-success" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger" role="alert">{{ flash.error }}</div>

        <div v-if="showCreate" class="card crud-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Tambah {{ props.title }}</h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" @click="cancelCreate">Tutup</button>
            </div>
            <div class="card-body">
                <div v-if="props.scan" class="crud-scan">
                    <div class="crud-scan-copy">
                        <strong>{{ props.scan.title ?? 'Upload gambar' }}</strong>
                        <p>{{ props.scan.hint ?? 'Sistem akan membaca data dari gambar secara otomatis.' }}</p>
                        <input
                            class="form-control form-control-sm"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            :disabled="scanLoading"
                            aria-label="Upload gambar voucher"
                            @change="onScanFileChange($event, false)"
                        />
                        <div v-if="scanLoading" class="crud-scan-status" role="status">
                            <i class="mdi mdi-loading mdi-spin me-1"></i>
                            Membaca gambar voucher...
                        </div>
                        <div v-else-if="scanError" class="text-danger small mt-2" role="alert">{{ scanError }}</div>
                        <div v-else-if="createForm.errors.voucher_image" class="text-danger small mt-2" role="alert">{{ createForm.errors.voucher_image }}</div>
                        <div v-else-if="scanSuccess" class="text-success small mt-2" role="status">{{ scanSuccess }}</div>
                        <ul v-if="scanWarnings.length > 0" class="crud-scan-warnings">
                            <li v-for="warning in scanWarnings" :key="warning">{{ warning }}</li>
                        </ul>
                    </div>
                    <div v-if="scannedImageUrl" class="crud-scan-preview">
                        <img :src="scannedImageUrl" alt="Pratinjau gambar voucher" />
                    </div>
                </div>
                <form class="row g-2 align-items-end" @submit.prevent="submitCreate">
                    <div v-for="field in fields" :key="field.key" :class="field.type === 'map' ? 'col-12' : 'col-md-4'">
                        <label class="form-label">{{ field.label }}</label>
                        <LocationPicker
                            v-if="field.type === 'map'"
                            :latitude="createForm[field.latitudeKey ?? 'lat']"
                            :longitude="createForm[field.longitudeKey ?? 'lng']"
                            @update:coordinates="setCoordinates(false, field, $event)"
                        />
                        <SearchableSelect
                            v-else-if="field.type === 'multiselect' && (field.options?.length ?? 0) > 8"
                            v-model="createForm[field.key]"
                            :options="field.options ?? []"
                            :placeholder="`Pilih ${field.label}`"
                            :search-placeholder="`Cari ${field.label.toLowerCase()}...`"
                            multiple
                        />
                        <select v-else-if="field.type === 'multiselect'" v-model="createForm[field.key]" class="form-select form-select-sm" multiple>
                            <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <SearchableSelect
                            v-else-if="field.type === 'select' && (field.options?.length ?? 0) > 8"
                            v-model="createForm[field.key]"
                            :options="field.options ?? []"
                            :placeholder="`Pilih ${field.label}`"
                            :search-placeholder="`Cari ${field.label.toLowerCase()}...`"
                            :required="field.required"
                            :clearable="!field.required"
                        />
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
                    <caption class="visually-hidden">{{ props.title }}</caption>
                    <thead>
                        <tr>
                            <th v-for="column in columns" :key="column.key" scope="col" :class="`text-${column.align ?? 'start'}`">{{ column.label }}</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="row in items.data" :key="row.id">
                            <tr>
                                <td v-for="column in columns" :key="column.key" :class="`text-${column.align ?? 'start'}`">
                                    <template v-if="column.type === 'image'">
                                        <a v-if="row[column.key]" :href="row[column.key]" target="_blank" rel="noopener">
                                            <img class="crud-thumb" :src="row[column.key]" :alt="column.label" loading="lazy" />
                                        </a>
                                        <span v-else class="text-muted">-</span>
                                    </template>
                                    <template v-else>{{ display(row, column.key) }}</template>
                                </td>
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
                                    <div v-if="props.scan" class="crud-scan mb-3">
                                        <div class="crud-scan-copy">
                                            <strong>{{ props.scan.title ?? 'Upload gambar' }}</strong>
                                            <p>{{ props.scan.hint ?? 'Sistem akan membaca data dari gambar secara otomatis.' }}</p>
                                            <input
                                                class="form-control form-control-sm"
                                                type="file"
                                                accept="image/jpeg,image/png,image/webp"
                                                :disabled="scanLoading"
                                                aria-label="Upload gambar voucher pengganti"
                                                @change="onScanFileChange($event, true)"
                                            />
                                            <div v-if="scanLoading" class="crud-scan-status" role="status">
                                                <i class="mdi mdi-loading mdi-spin me-1"></i>
                                                Membaca gambar voucher...
                                            </div>
                                            <div v-else-if="scanError" class="text-danger small mt-2" role="alert">{{ scanError }}</div>
                                            <div v-else-if="editForm.errors.voucher_image" class="text-danger small mt-2" role="alert">{{ editForm.errors.voucher_image }}</div>
                                            <div v-else-if="scanSuccess" class="text-success small mt-2" role="status">{{ scanSuccess }}</div>
                                            <ul v-if="scanWarnings.length > 0" class="crud-scan-warnings">
                                                <li v-for="warning in scanWarnings" :key="warning">{{ warning }}</li>
                                            </ul>
                                        </div>
                                        <div v-if="scannedImageUrl || existingImageUrl" class="crud-scan-preview">
                                            <img :src="scannedImageUrl || existingImageUrl || ''" alt="Pratinjau gambar voucher" />
                                        </div>
                                    </div>
                                    <form class="row g-2 align-items-end" @submit.prevent="submitEdit">
                                        <div v-for="field in fields" :key="field.key" :class="field.type === 'map' ? 'col-12' : 'col-md-4'">
                                            <label class="form-label">{{ field.label }}</label>
                                            <LocationPicker
                                                v-if="field.type === 'map'"
                                                :latitude="editForm[field.latitudeKey ?? 'lat']"
                                                :longitude="editForm[field.longitudeKey ?? 'lng']"
                                                @update:coordinates="setCoordinates(true, field, $event)"
                                            />
                                            <SearchableSelect
                                                v-else-if="field.type === 'multiselect' && (field.options?.length ?? 0) > 8"
                                                v-model="editForm[field.key]"
                                                :options="field.options ?? []"
                                                :placeholder="`Pilih ${field.label}`"
                                                :search-placeholder="`Cari ${field.label.toLowerCase()}...`"
                                                multiple
                                            />
                                            <select v-else-if="field.type === 'multiselect'" v-model="editForm[field.key]" class="form-select form-select-sm" multiple>
                                                <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">{{ option.label }}</option>
                                            </select>
                                            <SearchableSelect
                                                v-else-if="field.type === 'select' && (field.options?.length ?? 0) > 8"
                                                v-model="editForm[field.key]"
                                                :options="field.options ?? []"
                                                :placeholder="`Pilih ${field.label}`"
                                                :search-placeholder="`Cari ${field.label.toLowerCase()}...`"
                                                :required="field.required"
                                                :clearable="!field.required"
                                            />
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
                            <td :colspan="columns.length + 1" class="crud-empty">
                                <i class="mdi mdi-database-off-outline d-block mb-2" aria-hidden="true"></i>
                                <strong class="d-block">Belum ada data.</strong>
                                <span class="d-block">Tambahkan data baru atau ubah kata kunci pencarian.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <small class="text-muted">Menampilkan {{ items.from ?? 0 }} - {{ items.to ?? 0 }} dari {{ items.total }} data</small>
                <PaginationLinks :links="items.links" />
            </div>
        </div>

        <Teleport to="body">
            <div v-if="actionPrompt?.action.prompt" class="crud-modal-backdrop" @click.self="closeActionPrompt">
                <div class="crud-modal" role="dialog" aria-modal="true" aria-labelledby="crud-action-title" @keydown.esc="closeActionPrompt">
                    <header class="crud-modal-header">
                        <h2 id="crud-action-title">{{ actionPrompt.action.prompt.label }}</h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary" aria-label="Tutup dialog" @click="closeActionPrompt">
                            <i class="mdi mdi-close" aria-hidden="true"></i>
                        </button>
                    </header>
                    <div class="crud-modal-body">
                        <label class="form-label" for="crud-action-value">Nilai</label>
                        <input
                            id="crud-action-value"
                            ref="actionPromptInputRef"
                            v-model="actionPrompt.value"
                            class="form-control"
                            type="number"
                            :min="actionPrompt.action.prompt.min ?? 1"
                            :max="actionPrompt.action.prompt.max ?? 100"
                            step="1"
                            @keydown.enter.prevent="submitActionPrompt"
                        />
                        <div v-if="actionPrompt.error" class="text-danger small mt-2" role="alert">{{ actionPrompt.error }}</div>
                    </div>
                    <footer class="crud-modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeActionPrompt">Batal</button>
                        <button type="button" class="btn btn-sm btn-primary" @click="submitActionPrompt">Lanjutkan</button>
                    </footer>
                </div>
            </div>
        </Teleport>
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
.crud-thumb { width: 52px; height: 52px; border: 1px solid var(--crud-border); border-radius: .45rem; object-fit: cover; background: var(--app-surface-2); }
.crud-scan { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; padding: .85rem .95rem; border: 1px dashed var(--crud-border); border-radius: .7rem; background: var(--app-surface-2); }
.crud-scan-copy { flex: 1 1 auto; min-width: 0; }
.crud-scan-copy strong { display: block; margin-bottom: .2rem; font-size: .86rem; }
.crud-scan-copy p { margin: 0 0 .6rem; color: var(--crud-muted); font-size: .76rem; line-height: 1.5; }
.crud-scan-status { margin-top: .5rem; color: var(--crud-muted); font-size: .78rem; }
.crud-scan-warnings { margin: .5rem 0 0; padding-left: 1.1rem; color: var(--app-warning-ink); font-size: .76rem; }
.crud-scan-preview { flex: 0 0 auto; }
.crud-scan-preview img { display: block; width: 132px; max-height: 132px; border: 1px solid var(--crud-border); border-radius: .55rem; object-fit: contain; background: #fff; }
.crud-edit { background: var(--app-surface-2); }
.crud-empty { padding: 2.5rem 1rem; color: var(--crud-muted); text-align: center; }
.crud-filter-select { width: auto; min-width: 140px; }
.crud-modal-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: flex-start; justify-content: center; padding: min(10vh, 4rem) 1rem 1rem; overflow-y: auto; background: rgba(15, 23, 42, 0.64); }
.crud-modal { width: min(420px, 100%); overflow: hidden; border: 1px solid var(--crud-border); border-radius: 0.9rem; background: var(--app-surface); color: var(--app-ink); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.3); }
.crud-modal-header, .crud-modal-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.9rem 1rem; }
.crud-modal-header { border-bottom: 1px solid var(--crud-border); }
.crud-modal-header h2 { margin: 0; font-size: 1rem; }
.crud-modal-body { padding: 1rem; }
.crud-modal-footer { justify-content: flex-end; border-top: 1px solid var(--crud-border); background: var(--app-surface-2); }
.crud-modal .btn { min-height: 44px; }
@media (max-width: 767.98px) { .crud-header { align-items: flex-start; flex-direction: column; } .crud-scan { flex-direction: column; } }
@media (max-width: 575.98px) { .crud-filter-select { width: 100%; } .crud-scan-preview img { width: 100%; max-height: 180px; } }
</style>
