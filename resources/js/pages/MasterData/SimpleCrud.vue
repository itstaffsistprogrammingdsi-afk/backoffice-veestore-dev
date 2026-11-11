<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, defineAsyncComponent, nextTick, reactive, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PaginationLinks from '../../components/PaginationLinks.vue';
import ModuleNav from '../../components/ModuleNav.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
import AppFilterBar from '@/components/ui/AppFilterBar.vue';
import AppTableShell from '@/components/ui/AppTableShell.vue';
import AppRowActions from '@/components/ui/AppRowActions.vue';
import AppEmptyState from '@/components/ui/AppEmptyState.vue';
import AppDialog from '@/components/ui/AppDialog.vue';
import { useToast } from '@/composables/useToast';

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

interface RowMenuItem {
    label: string;
    icon?: string;
    danger?: boolean;
    disabled?: boolean;
    onSelect: () => void;
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
const { success: toastSuccess, error: toastError } = useToast();
let lastFlashKey = '';

watch(
    flash,
    (value) => {
        const key = `${value.success ?? ''}|${value.error ?? ''}`;

        if (key === lastFlashKey) return;

        lastFlashKey = key;

        if (value.success) toastSuccess(value.success);
        if (value.error) toastError(value.error);
    },
    { immediate: true },
);

const search = reactive({ q: props.filters.q ?? '' });
const extraSearch = reactive<Record<string, string>>(
    Object.fromEntries((props.extraFilters ?? []).map((filter) => [filter.key, filter.value ?? ''])),
);
const showCreate = ref(false);
const createCardRef = ref<HTMLElement | null>(null);
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
const actionPromptOpen = computed(() => Boolean(actionPrompt.value?.action.prompt));
const actionPromptValue = computed({
    get: () => actionPrompt.value?.value ?? '',
    set: (value: string) => {
        if (actionPrompt.value) {
            actionPrompt.value.value = value;
        }
    },
});

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

        return;
    }

    executeRowAction(action, row, {});
};

const menuActionsFor = (row: Record<string, any>): RowMenuItem[] => {
    const items: RowMenuItem[] = (props.rowActions ?? [])
        .filter((action) => rowActionVisible(action, row))
        .map((action) => ({
            label: action.label,
            icon: action.icon ?? 'mdi-dots-horizontal',
            onSelect: () => runRowAction(action, row),
        }));

    items.push({ label: 'Edit data', icon: 'mdi-pencil', onSelect: () => startEdit(row) });
    items.push({ label: 'Hapus data', icon: 'mdi-delete', danger: true, onSelect: () => requestRemove(row) });

    return items;
};

const rowLabel = (row: Record<string, any>) => {
    const key = props.columns[0]?.key;
    const value = key ? display(row, key) : row.id;

    return String(value ?? row.id);
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

    void nextTick(() => {
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        createCardRef.value?.scrollIntoView({
            block: 'start',
            behavior: reducedMotion ? 'auto' : 'smooth',
        });
        createCardRef.value?.querySelector<HTMLElement>('input, select, textarea')?.focus();
    });
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

const deleteTarget = ref<Record<string, any> | null>(null);
const deleting = ref(false);

const requestRemove = (row: Record<string, any>) => {
    deleteTarget.value = row;
};

const cancelRemove = () => {
    if (deleting.value) return;

    deleteTarget.value = null;
};

const confirmRemove = () => {
    if (!deleteTarget.value) return;

    deleting.value = true;

    router.delete(props.urls.destroy.replace('__ID__', deleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
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
        <AppPageHeader
            :eyebrow="props.eyebrow ?? 'Master Data'"
            :title="props.title"
            :description="props.description"
        >
            <template #actions>
                <button class="btn btn-primary" type="button" @click="openCreate">
                    <i class="mdi mdi-plus" aria-hidden="true"></i>
                    Tambah
                </button>
            </template>
        </AppPageHeader>

        <ModuleNav module-key="promoModule" aria-label="Modul Promo" />
        <ModuleNav module-key="masterDataModule" aria-label="Modul Master Produk" />
        <ModuleNav module-key="loyaltyModule" aria-label="Modul Loyalty" />

        <AppFilterBar>
            <form id="crud-filter-form" class="d-flex w-100 flex-wrap align-items-end gap-2" @submit.prevent="applySearch">
                <div class="crud-filter-field">
                    <label class="visually-hidden" for="crud-search">Cari data</label>
                    <input id="crud-search" v-model="search.q" class="form-control form-control-sm" type="search" :placeholder="props.searchPlaceholder ?? 'Cari...'" />
                </div>
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
                <div class="d-flex gap-2 ms-auto">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">
                        <i class="mdi mdi-magnify" aria-hidden="true"></i>
                        Cari
                    </button>
                    <button class="btn btn-ghost btn-sm" type="button" @click="resetSearch">
                        <i class="mdi mdi-refresh" aria-hidden="true"></i>
                        Reset
                    </button>
                </div>
            </form>
        </AppFilterBar>

        <div v-if="showCreate" ref="createCardRef" class="card crud-create mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Tambah {{ props.title }}</h2>
                <button class="btn btn-sm btn-ghost" type="button" aria-label="Tutup formulir" @click="cancelCreate">
                    <i class="mdi mdi-close" aria-hidden="true"></i>
                </button>
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
                <form class="row g-3 align-items-end" @submit.prevent="submitCreate">
                    <div v-for="field in fields" :key="field.key" :class="field.type === 'map' ? 'col-12' : 'col-md-4'">
                        <label class="form-label" :class="{ 'is-required': field.required }" :for="`create-${field.key}`">{{ field.label }}</label>
                        <LocationPicker
                            v-if="field.type === 'map'"
                            :latitude="createForm[field.latitudeKey ?? 'lat']"
                            :longitude="createForm[field.longitudeKey ?? 'lng']"
                            @update:coordinates="setCoordinates(false, field, $event)"
                        />
                        <SearchableSelect
                            v-else-if="field.type === 'multiselect' && (field.options?.length ?? 0) > 8"
                            :id="`create-${field.key}`"
                            v-model="createForm[field.key]"
                            :options="field.options ?? []"
                            :placeholder="`Pilih ${field.label}`"
                            :search-placeholder="`Cari ${field.label.toLowerCase()}...`"
                            size="md"
                            multiple
                        />
                        <select v-else-if="field.type === 'multiselect'" :id="`create-${field.key}`" v-model="createForm[field.key]" class="form-select" :class="{ 'is-invalid': createForm.errors[field.key] }" multiple>
                            <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <SearchableSelect
                            v-else-if="field.type === 'select' && (field.options?.length ?? 0) > 8"
                            :id="`create-${field.key}`"
                            v-model="createForm[field.key]"
                            :options="field.options ?? []"
                            :placeholder="`Pilih ${field.label}`"
                            :search-placeholder="`Cari ${field.label.toLowerCase()}...`"
                            size="md"
                            :required="field.required"
                            :clearable="!field.required"
                        />
                        <select v-else-if="field.type === 'select'" :id="`create-${field.key}`" v-model="createForm[field.key]" class="form-select" :class="{ 'is-invalid': createForm.errors[field.key] }" :required="field.required">
                            <option value="">Pilih {{ field.label }}</option>
                            <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <textarea v-else-if="field.type === 'textarea'" :id="`create-${field.key}`" v-model="createForm[field.key]" class="form-control" :class="{ 'is-invalid': createForm.errors[field.key] }" :required="field.required" rows="2"></textarea>
                        <input v-else-if="field.type === 'file'" :id="`create-${field.key}`" class="form-control" type="file" :required="field.required" @change="onFileChange(field.key, $event)" />
                        <input v-else :id="`create-${field.key}`" v-model="createForm[field.key]" class="form-control" :class="{ 'is-invalid': createForm.errors[field.key] }" :type="field.type ?? 'text'" :step="field.type === 'number' ? 'any' : undefined" :required="field.required" :placeholder="field.placeholder" />
                        <div v-if="createForm.errors[field.key]" class="invalid-feedback d-block" role="alert">{{ createForm.errors[field.key] }}</div>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit" :disabled="createForm.processing" :aria-busy="createForm.processing">
                            <i v-if="createForm.processing" class="mdi mdi-loading mdi-spin" aria-hidden="true"></i>
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                        <button class="btn btn-outline-secondary" type="button" :disabled="createForm.processing" @click="cancelCreate">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <AppTableShell :label="props.title">
                <table class="table table-hover align-middle mb-0">
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
                                    <span v-if="editingId === String(row.id)" class="badge bg-label-primary">Sedang diedit</span>
                                    <AppRowActions v-else :actions="menuActionsFor(row)" :label="`Aksi untuk ${rowLabel(row)}`" />
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
                                    <form class="row g-3 align-items-end" @submit.prevent="submitEdit">
                                        <div v-for="field in fields" :key="field.key" :class="field.type === 'map' ? 'col-12' : 'col-md-4'">
                                            <label class="form-label" :class="{ 'is-required': field.required }" :for="`edit-${field.key}`">{{ field.label }}</label>
                                            <LocationPicker
                                                v-if="field.type === 'map'"
                                                :latitude="editForm[field.latitudeKey ?? 'lat']"
                                                :longitude="editForm[field.longitudeKey ?? 'lng']"
                                                @update:coordinates="setCoordinates(true, field, $event)"
                                            />
                                            <SearchableSelect
                                                v-else-if="field.type === 'multiselect' && (field.options?.length ?? 0) > 8"
                                                :id="`edit-${field.key}`"
                                                v-model="editForm[field.key]"
                                                :options="field.options ?? []"
                                                :placeholder="`Pilih ${field.label}`"
                                                :search-placeholder="`Cari ${field.label.toLowerCase()}...`"
                                                size="md"
                                                multiple
                                            />
                                            <select v-else-if="field.type === 'multiselect'" :id="`edit-${field.key}`" v-model="editForm[field.key]" class="form-select" :class="{ 'is-invalid': editForm.errors[field.key] }" multiple>
                                                <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">{{ option.label }}</option>
                                            </select>
                                            <SearchableSelect
                                                v-else-if="field.type === 'select' && (field.options?.length ?? 0) > 8"
                                                :id="`edit-${field.key}`"
                                                v-model="editForm[field.key]"
                                                :options="field.options ?? []"
                                                :placeholder="`Pilih ${field.label}`"
                                                :search-placeholder="`Cari ${field.label.toLowerCase()}...`"
                                                size="md"
                                                :required="field.required"
                                                :clearable="!field.required"
                                            />
                                            <select v-else-if="field.type === 'select'" :id="`edit-${field.key}`" v-model="editForm[field.key]" class="form-select" :class="{ 'is-invalid': editForm.errors[field.key] }" :required="field.required">
                                                <option value="">Pilih {{ field.label }}</option>
                                                <option v-for="option in field.options ?? []" :key="option.value" :value="option.value">{{ option.label }}</option>
                                            </select>
                                            <textarea v-else-if="field.type === 'textarea'" :id="`edit-${field.key}`" v-model="editForm[field.key]" class="form-control" :class="{ 'is-invalid': editForm.errors[field.key] }" :required="field.required" rows="2"></textarea>
                                            <input v-else-if="field.type === 'file'" :id="`edit-${field.key}`" class="form-control" type="file" @change="onFileChange(field.key, $event)" />
                                            <input v-else :id="`edit-${field.key}`" v-model="editForm[field.key]" class="form-control" :class="{ 'is-invalid': editForm.errors[field.key] }" :type="field.type ?? 'text'" :step="field.type === 'number' ? 'any' : undefined" :required="field.required" />
                                            <div v-if="editForm.errors[field.key]" class="invalid-feedback d-block" role="alert">{{ editForm.errors[field.key] }}</div>
                                        </div>
                                        <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                                            <button class="btn btn-primary" type="submit" :disabled="editForm.processing" :aria-busy="editForm.processing">
                                                <i v-if="editForm.processing" class="mdi mdi-loading mdi-spin" aria-hidden="true"></i>
                                                {{ editForm.processing ? 'Menyimpan...' : 'Simpan perubahan' }}
                                            </button>
                                            <button class="btn btn-outline-secondary" type="button" :disabled="editForm.processing" @click="cancelEdit">Batal</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="items.data.length === 0">
                            <td :colspan="columns.length + 1" class="p-0">
                                <AppEmptyState
                                    title="Belum ada data"
                                    description="Tambahkan data baru atau ubah kata kunci pencarian."
                                >
                                    <button class="btn btn-primary" type="button" @click="openCreate">
                                        <i class="mdi mdi-plus" aria-hidden="true"></i>
                                        Tambah {{ props.title }}
                                    </button>
                                </AppEmptyState>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <template #footer>
                    <small class="text-muted">Menampilkan {{ items.from ?? 0 }} - {{ items.to ?? 0 }} dari {{ items.total }} data</small>
                    <PaginationLinks :links="items.links" />
                </template>
        </AppTableShell>

        <AppDialog
            :open="actionPromptOpen"
            :title="actionPrompt?.action.prompt?.label ?? 'Masukkan nilai'"
            description="Nilai ini dipakai untuk aksi yang dipilih."
            confirm-label="Lanjutkan"
            cancel-label="Batal"
            initial-focus-selector="#crud-action-value"
            @update:open="closeActionPrompt"
            @confirm="submitActionPrompt"
            @cancel="closeActionPrompt"
        >
            <label class="form-label" for="crud-action-value">Nilai</label>
            <input
                id="crud-action-value"
                v-model="actionPromptValue"
                class="form-control"
                :class="{ 'is-invalid': Boolean(actionPrompt?.error) }"
                type="number"
                :min="actionPrompt?.action.prompt?.min ?? 1"
                :max="actionPrompt?.action.prompt?.max ?? 100"
                step="1"
                @keydown.enter.prevent="submitActionPrompt"
            />
            <div v-if="actionPrompt?.error" class="invalid-feedback d-block" role="alert">{{ actionPrompt.error }}</div>
            <p class="form-text mt-2">
                Rentang nilai {{ actionPrompt?.action.prompt?.min ?? 1 }} sampai {{ actionPrompt?.action.prompt?.max ?? 100 }}.
            </p>
        </AppDialog>

        <AppDialog
            :open="Boolean(deleteTarget)"
            title="Hapus data?"
            :description="`Data ${props.title} yang dihapus tidak bisa dikembalikan.`"
            confirm-label="Hapus data"
            cancel-label="Batal"
            variant="danger"
            :loading="deleting"
            @update:open="cancelRemove"
            @confirm="confirmRemove"
            @cancel="cancelRemove"
        />
    </div>
</template>

<style scoped>
.crud-page {
    color: var(--app-ink);
}

.crud-create {
    scroll-margin-top: 5rem;
}

.crud-actions {
    white-space: nowrap;
}

.crud-thumb {
    width: 52px;
    height: 52px;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-sm);
    background: var(--app-surface-2);
    object-fit: cover;
}

.crud-scan {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 0.85rem 0.95rem;
    border: 1px dashed var(--app-control-border);
    border-radius: var(--app-radius-lg);
    background: var(--app-surface-2);
}

.crud-scan-copy {
    flex: 1 1 auto;
    min-width: 0;
}

.crud-scan-copy strong {
    display: block;
    margin-bottom: 0.2rem;
    font-size: var(--app-text-base);
}

.crud-scan-copy p {
    margin: 0 0 0.6rem;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    line-height: var(--app-leading-normal);
}

.crud-scan-status {
    margin-top: 0.5rem;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
}

.crud-scan-warnings {
    margin: 0.5rem 0 0;
    padding-left: 1.1rem;
    color: var(--app-warning-ink);
    font-size: var(--app-text-sm);
}

.crud-scan-preview {
    flex: 0 0 auto;
}

.crud-scan-preview img {
    display: block;
    width: 132px;
    max-height: 132px;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-md);
    background: var(--app-surface);
    object-fit: contain;
}

.crud-edit {
    padding: 1.1rem 1.25rem;
    background: var(--app-surface-2);
}

.crud-filter-field {
    flex: 1 1 220px;
    min-width: 0;
}

.crud-filter-select {
    width: auto;
    min-width: 150px;
}

@media (max-width: 767.98px) {
    .crud-scan {
        flex-direction: column;
    }

    .crud-filter-field,
    .crud-filter-select {
        flex: 1 1 100%;
        width: 100%;
    }
}

@media (max-width: 575.98px) {
    .crud-scan-preview img {
        width: 100%;
        max-height: 180px;
    }
}
</style>
