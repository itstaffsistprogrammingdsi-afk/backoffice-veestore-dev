<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface StoreOption {
    id: string;
    name: string;
    code: string | null;
    address: string | null;
}

interface TypeOption {
    value: string;
    label: string;
}

interface LocationRow {
    id: string;
    name: string;
    code: string;
    type: string;
    type_label: string;
    parent_id: string | null;
    parent_name: string | null;
    depth: number;
    path: string;
    has_children: boolean;
    qr_url: string;
}

interface ParentOption {
    id: string;
    name: string;
    code: string;
    type: string;
    type_label: string;
}

interface StoreGroup {
    id: string;
    name: string;
    code: string | null;
    address: string | null;
    root_id: string | null;
    root_code: string | null;
    stats: {
        rooms: number;
        staging: number;
        racks: number;
        layers: number;
        total: number;
    };
    parentOptions: {
        staging: ParentOption[];
        rack: ParentOption[];
        layer: ParentOption[];
    };
    rows: LocationRow[];
}

interface Props {
    groups: StoreGroup[];
    stores: StoreOption[];
    filters: {
        q: string;
        store_id: string | null;
        type: string | null;
    };
    typeOptions: TypeOption[];
    urls: {
        index: string;
        generate: string;
        quickAdd: string;
        update: string;
        destroy: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const filters = reactive({
    q: props.filters.q ?? '',
    store_id: props.filters.store_id ?? '',
    type: props.filters.type ?? '',
});

const showCreate = ref(false);
const expandedRows = ref<Record<string, boolean>>({});
const quickAddOpen = ref<string | null>(null);
const quickAdd = ref<{ group: StoreGroup; type: string } | null>(null);
const quickAddName = ref<HTMLInputElement | null>(null);
const editing = ref<LocationRow | null>(null);
const editingGroup = ref<StoreGroup | null>(null);
const editNameInput = ref<HTMLInputElement | null>(null);

const qrModal = ref<{ row: LocationRow; group: StoreGroup } | null>(null);
const qrCloseButton = ref<HTMLButtonElement | null>(null);
const qrLoading = ref(false);
const qrError = ref('');
const qrSvgUrl = ref('');
const qrBlob = ref<Blob | null>(null);
let qrRequestId = 0;

const qrFileName = computed(() => {
    if (!qrModal.value) return 'qr-location';

    const code = qrModal.value.row.code
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    return `qr-${code || 'location'}`;
});

const modalOpen = computed(() => Boolean(editing.value || qrModal.value || quickAdd.value));
const filtersActive = computed(() => Boolean(filters.q || filters.type));

const createForm = useForm({
    name: '',
    store_id: '',
    rooms: 1,
    staging: 1,
    racks: 1,
    layers: 1,
});

const editForm = useForm({
    name: '',
    code: '',
    type: '',
    location_parent_id: '',
    store_id: '',
});

const quickAddForm = useForm({
    name: '',
    type: '',
    parent_id: '',
    store_id: '',
});

const quickAddTypes = [
    { value: 'room', label: 'Room' },
    { value: 'store_display', label: 'Display' },
    { value: 'staging', label: 'Staging' },
    { value: 'rack', label: 'Rack' },
    { value: 'layer', label: 'Layer' },
];

const selectedStore = computed(() => props.stores.find((store) => store.id === createForm.store_id));

const previewCode = computed(() => {
    const prefix = selectedStore.value?.code || 'STORE';

    return [
        `${prefix}/R01`,
        `${prefix}/R01/STG01`,
        `${prefix}/R01/RK01`,
        `${prefix}/R01/RK01/L01`,
    ];
});

const generatedTotal = computed(() => {
    const rooms = Number(createForm.rooms) || 0;
    const staging = Number(createForm.staging) || 0;
    const racks = Number(createForm.racks) || 0;
    const layers = Number(createForm.layers) || 0;

    return rooms * (1 + staging + racks + ((staging + racks) * layers));
});

const formatCount = (value: number) => new Intl.NumberFormat('id-ID').format(value);

const groupOpen = (_group: StoreGroup) => Boolean(filters.q || filters.type);

const typeClass = (type: string) => ({
    room: 'bg-label-primary',
    store_display: 'bg-label-info',
    staging: 'bg-label-warning',
    rack: 'bg-label-success',
    layer: 'bg-label-secondary',
}[type] ?? 'bg-label-secondary');

const quickAddTitle = computed(() => {
    const type = quickAddTypes.find((item) => item.value === quickAdd.value?.type);

    return type ? `Add ${type.label}` : 'Add Layout';
});

const quickAddNeedsParent = computed(() => Boolean(quickAdd.value && ['staging', 'rack', 'layer'].includes(quickAdd.value.type)));

const quickAddParentOptions = computed(() => {
    if (!quickAdd.value) return [];

    if (quickAdd.value.type === 'staging' || quickAdd.value.type === 'rack') {
        return quickAdd.value.group.parentOptions.staging;
    }

    if (quickAdd.value.type === 'layer') {
        return quickAdd.value.group.parentOptions.layer;
    }

    return [];
});

const visibleRows = (group: StoreGroup) => {
    if (filtersActive.value) return group.rows;

    const visible = new Set<string>();

    return group.rows.filter((row) => {
        if (row.depth === 0) {
            visible.add(row.id);
            return true;
        }

        if (!row.parent_id || !visible.has(row.parent_id) || !expandedRows.value[row.parent_id]) {
            return false;
        }

        visible.add(row.id);
        return true;
    });
};

const toggleRow = (rowId: string) => {
    expandedRows.value[rowId] = !expandedRows.value[rowId];
};

const rowExpanded = (rowId: string) => Boolean(expandedRows.value[rowId]);

const toggleQuickAddMenu = (groupId: string) => {
    quickAddOpen.value = quickAddOpen.value === groupId ? null : groupId;
};

const closeQuickAddMenu = () => {
    quickAddOpen.value = null;
};

const openQuickAdd = (group: StoreGroup, type: string) => {
    closeQuickAddMenu();
    closeQr();
    editing.value = null;
    showCreate.value = false;
    quickAdd.value = { group, type };
    quickAddForm.reset();
    quickAddForm.clearErrors();
    quickAddForm.type = type;
    quickAddForm.store_id = group.id;
    quickAddForm.parent_id = '';

    const options = type === 'staging' || type === 'rack'
        ? group.parentOptions.staging
        : type === 'layer'
            ? group.parentOptions.layer
            : [];

    if (options.length > 0) {
        quickAddForm.parent_id = options[0].id;
    }
};

const closeQuickAdd = () => {
    quickAdd.value = null;
    quickAddForm.reset();
    quickAddForm.clearErrors();
    closeQuickAddMenu();
};

const submitQuickAdd = () => {
    quickAddForm.post(props.urls.quickAdd, {
        preserveScroll: true,
        onSuccess: () => closeQuickAdd(),
    });
};

const applyFilters = () => {
    router.get(props.urls.index, {
        q: filters.q || undefined,
        store_id: filters.store_id || undefined,
        type: filters.type || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.q = '';
    filters.store_id = '';
    filters.type = '';
    applyFilters();
};

const openCreate = () => {
    closeQr();
    createForm.reset();
    createForm.clearErrors();
    showCreate.value = true;
    editing.value = null;
};

const closeCreate = () => {
    showCreate.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = () => {
    createForm.post(props.urls.generate, {
        preserveScroll: true,
        onSuccess: () => closeCreate(),
    });
};

const startEdit = (row: LocationRow, group: StoreGroup) => {
    closeQr();
    editing.value = row;
    editingGroup.value = group;
    showCreate.value = false;
    editForm.clearErrors();
    editForm.name = row.name;
    editForm.code = row.code;
    editForm.type = row.type;
    editForm.location_parent_id = row.parent_id ?? '';
    editForm.store_id = group.id;
};

const cancelEdit = () => {
    editing.value = null;
    editingGroup.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    if (!editing.value) return;

    editForm.put(props.urls.update.replace('__ID__', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

const releaseQrUrl = () => {
    if (qrSvgUrl.value) {
        URL.revokeObjectURL(qrSvgUrl.value);
    }

    qrSvgUrl.value = '';
    qrBlob.value = null;
};

const openQr = async (row: LocationRow, group: StoreGroup) => {
    showCreate.value = false;
    editing.value = null;
    qrModal.value = { row, group };
    qrLoading.value = true;
    qrError.value = '';
    releaseQrUrl();

    const requestId = ++qrRequestId;

    try {
        const response = await fetch(row.qr_url, {
            credentials: 'same-origin',
            headers: { Accept: 'image/svg+xml' },
        });

        if (!response.ok) {
            throw new Error('QR request failed');
        }

        const blob = await response.blob();

        if (requestId !== qrRequestId) return;

        qrBlob.value = blob;
        qrSvgUrl.value = URL.createObjectURL(blob);
    } catch {
        if (requestId === qrRequestId) {
            qrError.value = 'QR tidak dapat dimuat. Coba lagi.';
        }
    } finally {
        if (requestId === qrRequestId) {
            qrLoading.value = false;
        }
    }
};

const closeQr = () => {
    qrRequestId++;
    qrModal.value = null;
    qrLoading.value = false;
    qrError.value = '';
    releaseQrUrl();
};

const triggerDownload = (url: string, filename: string) => {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
};

const downloadSvg = () => {
    if (!qrSvgUrl.value) return;

    triggerDownload(qrSvgUrl.value, `${qrFileName.value}.svg`);
};

const downloadPng = async () => {
    if (!qrSvgUrl.value) return;

    const image = new Image();
    image.src = qrSvgUrl.value;

    try {
        await image.decode();
    } catch {
        return;
    }

    const size = 1024;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const context = canvas.getContext('2d');

    if (!context) return;

    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, size, size);
    context.drawImage(image, 0, 0, size, size);

    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/png'));

    if (!blob) return;

    const url = URL.createObjectURL(blob);
    triggerDownload(url, `${qrFileName.value}.png`);
    window.setTimeout(() => URL.revokeObjectURL(url), 1500);
};

const destroy = (row: LocationRow) => {
    const message = row.has_children
        ? `Hapus "${row.name}" beserta seluruh lokasi turunannya?`
        : `Hapus "${row.name}"?`;

    if (!window.confirm(message)) return;

    router.delete(props.urls.destroy.replace('__ID__', row.id), {
        preserveScroll: true,
    });
};

const trapFocus = (event: KeyboardEvent) => {
    if (event.key !== 'Tab') return;

    const modal = document.querySelector<HTMLElement>('.location-modal');
    if (!modal) return;

    const focusable = Array.from(
        modal.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]')
    ).filter((element) => element.offsetParent !== null);

    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

const onKeydown = (event: KeyboardEvent) => {
    trapFocus(event);

    if (event.key !== 'Escape') return;

    if (quickAdd.value) {
        closeQuickAdd();
        return;
    }

    if (qrModal.value) {
        closeQr();
        return;
    }

    if (editing.value) {
        cancelEdit();
    }
};

watch(editing, async (value) => {
    if (!value) return;

    await nextTick();
    editNameInput.value?.focus();
});

watch(qrModal, async (value) => {
    if (!value) return;

    await nextTick();
    qrCloseButton.value?.focus();
});

watch(quickAdd, async (value) => {
    if (!value) return;

    await nextTick();
    quickAddName.value?.focus();
});

watch(modalOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    document.addEventListener('click', closeQuickAddMenu);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.removeEventListener('click', closeQuickAddMenu);
    document.body.style.overflow = '';
    releaseQrUrl();
});
</script>

<template>
    <Head title="Store Layout" />
    <div class="location-page">
        <div class="location-header">
            <div>
                <span class="location-eyebrow">Master Data</span>
                <h1>Store Layout</h1>
                <p>Room, Staging, Rack, dan Layer per store. Kode dan QR dibuat otomatis untuk setiap bagian.</p>
            </div>
            <button class="btn btn-light btn-sm" type="button" @click="openCreate">
                <i class="mdi mdi-plus me-1"></i>Add Layout
            </button>
        </div>

        <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

        <div class="card location-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="applyFilters">
                    <div class="col-lg-5">
                        <label class="form-label">Cari lokasi</label>
                        <input v-model="filters.q" class="form-control form-control-sm" type="search" placeholder="Nama, kode, atau store..." />
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label">Store</label>
                        <select v-model="filters.store_id" class="form-select form-select-sm">
                            <option value="">Semua store</option>
                            <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label">Tipe</label>
                        <select v-model="filters.type" class="form-select form-select-sm">
                            <option value="">Semua tipe</option>
                            <option v-for="type in typeOptions" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3 d-flex gap-2">
                        <button class="btn btn-sm btn-primary flex-grow-1" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Reset filter" title="Reset filter" @click="resetFilters"><i class="mdi mdi-refresh"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showCreate" class="card location-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">Add Layout</h6>
                    <small class="text-muted">Buat Room, Display, Staging, Rack, dan Layer sekaligus. Kode dan QR dibuat otomatis.</small>
                </div>
                <button class="btn btn-sm btn-outline-secondary" type="button" @click="closeCreate">Tutup</button>
            </div>
            <div class="card-body">
                <form class="row g-3" @submit.prevent="submitCreate">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lokasi</label>
                        <input v-model="createForm.name" class="form-control form-control-sm" type="text" required placeholder="Contoh: Toko Utama" />
                        <div v-if="createForm.errors.name" class="text-danger small mt-1">{{ createForm.errors.name }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Store</label>
                        <select v-model="createForm.store_id" class="form-select form-select-sm" required>
                            <option value="">Pilih store</option>
                            <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }} ({{ store.code }})</option>
                        </select>
                        <div v-if="createForm.errors.store_id" class="text-danger small mt-1">{{ createForm.errors.store_id }}</div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <label class="form-label">Jumlah Room</label>
                        <input v-model.number="createForm.rooms" class="form-control form-control-sm" type="number" min="1" max="20" required />
                        <div v-if="createForm.errors.rooms" class="text-danger small mt-1">{{ createForm.errors.rooms }}</div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Staging per Room</label>
                        <input v-model.number="createForm.staging" class="form-control form-control-sm" type="number" min="0" max="20" required />
                        <div v-if="createForm.errors.staging" class="text-danger small mt-1">{{ createForm.errors.staging }}</div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Rack per Room</label>
                        <input v-model.number="createForm.racks" class="form-control form-control-sm" type="number" min="0" max="50" required />
                        <div v-if="createForm.errors.racks" class="text-danger small mt-1">{{ createForm.errors.racks }}</div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label">Layer per Rack/Staging</label>
                        <input v-model.number="createForm.layers" class="form-control form-control-sm" type="number" min="0" max="20" required />
                        <div v-if="createForm.errors.layers" class="text-danger small mt-1">{{ createForm.errors.layers }}</div>
                    </div>

                    <div class="col-12">
                        <div class="location-preview">
                            <div class="location-preview-head">
                                <strong>{{ formatCount(generatedTotal) }} lokasi akan dibuat</strong>
                                <span>QR otomatis untuk setiap lokasi</span>
                            </div>
                            <div class="location-preview-codes">
                                <span v-for="code in previewCode" :key="code"><code>{{ code }}</code></span>
                            </div>
                            <small class="text-muted">Contoh pola kode. Nomor urut menyesuaikan data yang sudah ada di store.</small>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-sm btn-primary" type="submit" :disabled="createForm.processing">
                            <i class="mdi mdi-auto-fix me-1"></i>
                            {{ createForm.processing ? 'Creating layout...' : 'Create Layout + QR' }}
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="closeCreate">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="groups.length === 0" class="card location-card">
            <div class="location-empty">
                <i class="mdi mdi-map-marker-off-outline"></i>
                <strong>Tidak ada lokasi yang cocok</strong>
                <span>Coba ubah kata kunci atau filter store.</span>
            </div>
        </div>

        <details v-for="group in groups" :key="group.id" class="location-group" :open="groupOpen(group)">
            <summary class="location-group-head">
                <div class="location-group-title">
                    <span class="location-group-icon"><i class="mdi mdi-store"></i></span>
                    <div>
                        <strong>{{ group.name }}</strong>
                        <small>{{ group.code || 'Tanpa kode' }}<template v-if="group.address"> · {{ group.address }}</template></small>
                    </div>
                </div>
                <div class="location-group-stats">
                    <span><b>{{ formatCount(group.stats.rooms) }}</b> Room</span>
                    <span><b>{{ formatCount(group.stats.staging) }}</b> Staging</span>
                    <span><b>{{ formatCount(group.stats.racks) }}</b> Rack</span>
                    <span><b>{{ formatCount(group.stats.layers) }}</b> Layer</span>
                    <span class="location-total">{{ formatCount(group.stats.total) }} lokasi</span>
                </div>
                <div class="location-add-wrap">
                    <button class="location-add-button" type="button" :aria-expanded="quickAddOpen === group.id" aria-haspopup="menu" @click.stop.prevent="toggleQuickAddMenu(group.id)">
                        <i class="mdi mdi-plus"></i>
                        Add
                    </button>
                    <div v-if="quickAddOpen === group.id" class="location-add-menu" role="menu" @click.stop.prevent>
                        <button v-for="type in quickAddTypes" :key="type.value" type="button" role="menuitem" @click="openQuickAdd(group, type.value)">
                            Add {{ type.label }}
                        </button>
                    </div>
                </div>
                <i class="mdi mdi-chevron-down location-chevron"></i>
            </summary>

            <div v-if="group.rows.length === 0" class="location-empty location-empty-compact">
                <i class="mdi mdi-map-marker-plus-outline"></i>
                <strong>Belum ada lokasi</strong>
                <span>Tambahkan lokasi pertama untuk store ini.</span>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 location-table">
                    <thead>
                        <tr>
                            <th>Lokasi</th>
                            <th>Kode</th>
                            <th>Tipe</th>
                            <th>Parent</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in visibleRows(group)" :key="row.id">
                            <td>
                                <div class="location-name" :style="{ paddingLeft: `${row.depth * 1.15}rem` }">
                                    <button v-if="row.has_children && !filtersActive" class="location-row-toggle" type="button" :aria-expanded="rowExpanded(row.id)" :aria-label="`${rowExpanded(row.id) ? 'Collapse' : 'Expand'} ${row.name}`" @click.stop.prevent="toggleRow(row.id)">
                                        <i class="mdi" :class="rowExpanded(row.id) ? 'mdi-chevron-down' : 'mdi-chevron-right'"></i>
                                    </button>
                                    <span v-else class="location-row-toggle-spacer"></span>
                                    <div>
                                        <strong>{{ row.name }}</strong>
                                        <small>{{ row.path }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><code class="location-code">{{ row.code }}</code></td>
                            <td><span class="badge" :class="typeClass(row.type)">{{ row.type_label }}</span></td>
                            <td class="text-muted">{{ row.parent_name || '—' }}</td>
                            <td class="text-end location-actions">
                                <button class="btn btn-sm btn-outline-secondary" type="button" :aria-label="`Buka QR ${row.name}`" title="Buka QR" @click="openQr(row, group)">
                                    <i class="mdi mdi-qrcode"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary" type="button" :aria-label="`Edit ${row.name}`" title="Edit" @click="startEdit(row, group)">
                                    <i class="mdi mdi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" type="button" :aria-label="`Hapus ${row.name}`" title="Hapus" @click="destroy(row)">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    </div>

    <Teleport to="body">
        <div v-if="quickAdd" class="location-modal-backdrop" @click.self="closeQuickAdd">
            <div class="location-modal location-modal-sm" role="dialog" aria-modal="true" aria-labelledby="quick-add-title">
                <form @submit.prevent="submitQuickAdd">
                    <div class="location-modal-header">
                        <div>
                            <h5 id="quick-add-title" class="mb-0">{{ quickAddTitle }}</h5>
                            <small class="text-muted">{{ quickAdd.group.name }}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup modal tambah layout" @click="closeQuickAdd">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                    <div class="location-modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input ref="quickAddName" v-model="quickAddForm.name" class="form-control" type="text" required placeholder="Contoh: Room A" />
                            <div v-if="quickAddForm.errors.name" class="text-danger small mt-1">{{ quickAddForm.errors.name }}</div>
                        </div>
                        <div v-if="quickAddNeedsParent" class="mb-3">
                            <label class="form-label">Parent</label>
                            <select v-model="quickAddForm.parent_id" class="form-select" :disabled="quickAddParentOptions.length === 0">
                                <option value="">Pilih parent</option>
                                <option v-for="option in quickAddParentOptions" :key="option.id" :value="option.id">{{ option.type_label }} · {{ option.name }} ({{ option.code }})</option>
                            </select>
                            <div v-if="quickAddForm.errors.parent_id" class="text-danger small mt-1">{{ quickAddForm.errors.parent_id }}</div>
                            <div v-if="quickAddParentOptions.length === 0" class="alert alert-warning small mt-2 mb-0">
                                Tambahkan Room atau Display terlebih dahulu.
                            </div>
                        </div>
                        <div v-else class="alert alert-info small mb-3">
                            Parent: {{ quickAdd.group.name }}
                        </div>
                        <p class="location-quick-hint mb-0">
                            Code and QR will be generated automatically.
                        </p>
                    </div>
                    <div class="location-modal-footer">
                        <button class="btn btn-sm btn-primary" type="submit" :disabled="quickAddForm.processing || !quickAddForm.name.trim() || (quickAddNeedsParent && !quickAddForm.parent_id)">
                            Create
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="closeQuickAdd">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div v-if="editing" class="location-modal-backdrop" @click.self="cancelEdit">
            <div class="location-modal" role="dialog" aria-modal="true" aria-labelledby="edit-location-title">
                <form @submit.prevent="submitEdit">
                    <div class="location-modal-header">
                        <div>
                            <h5 id="edit-location-title" class="mb-0">Edit {{ editing.type_label }}</h5>
                            <small class="text-muted">{{ editing.path }}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup modal edit" @click="cancelEdit">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                    <div class="location-modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Lokasi</label>
                                <input ref="editNameInput" v-model="editForm.name" class="form-control" type="text" required />
                                <div v-if="editForm.errors.name" class="text-danger small mt-1">{{ editForm.errors.name }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Kode</label>
                                <input v-model="editForm.code" class="form-control" type="text" required />
                                <div v-if="editForm.errors.code" class="text-danger small mt-1">{{ editForm.errors.code }}</div>
                            </div>
                            <div class="col-12">
                                <div class="location-edit-meta">
                                    <span class="badge" :class="typeClass(editing.type)">{{ editing.type_label }}</span>
                                    <span>Store: {{ editingGroup?.name }}</span>
                                    <span>Parent: {{ editing.parent_name || 'Root store' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="location-modal-footer">
                        <button class="btn btn-sm btn-primary" type="submit" :disabled="editForm.processing">Simpan</button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="cancelEdit">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div v-if="qrModal" class="location-modal-backdrop" @click.self="closeQr">
            <div class="location-modal location-modal-sm" role="dialog" aria-modal="true" aria-labelledby="qr-modal-title">
                <div class="location-modal-header">
                    <div>
                        <h5 id="qr-modal-title" class="mb-0">QR {{ qrModal.row.type_label }}</h5>
                        <small class="text-muted">{{ qrModal.group.name }} · {{ qrModal.row.code }}</small>
                    </div>
                    <button ref="qrCloseButton" class="btn btn-sm btn-outline-secondary" type="button" aria-label="Tutup modal QR" @click="closeQr">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
                <div class="location-modal-body">
                    <div class="qr-preview">
                        <div v-if="qrLoading" class="qr-placeholder">
                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <span>Memuat QR...</span>
                        </div>
                        <div v-else-if="qrError" class="qr-placeholder qr-error">
                            <i class="mdi mdi-alert-circle-outline"></i>
                            <span>{{ qrError }}</span>
                        </div>
                        <img v-else-if="qrSvgUrl" :src="qrSvgUrl" :alt="`QR ${qrModal.row.name}`" />
                    </div>
                    <div class="qr-meta">
                        <strong>{{ qrModal.row.name }}</strong>
                        <code>{{ qrModal.row.code }}</code>
                    </div>
                </div>
                <div class="location-modal-footer">
                    <button class="btn btn-sm btn-primary" type="button" :disabled="!qrSvgUrl" @click="downloadSvg">
                        <i class="mdi mdi-download me-1"></i>Download SVG
                    </button>
                    <button class="btn btn-sm btn-outline-primary" type="button" :disabled="!qrSvgUrl" @click="downloadPng">
                        <i class="mdi mdi-download me-1"></i>Download PNG
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="closeQr">Tutup</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.location-page { --location-border: var(--app-border); color: var(--app-ink); }
.location-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.location-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.location-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text); font-size: 1.5rem; }
.location-header p { max-width: 780px; margin: 0; color: var(--app-hero-muted); }
.location-card { border: 1px solid var(--location-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.location-preview { padding: 0.9rem; border: 1px dashed var(--app-border); border-radius: 0.75rem; background: var(--app-surface-2); }
.location-preview-head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 0.25rem 1rem; margin-bottom: 0.6rem; }
.location-preview-head span { color: var(--app-muted); font-size: 0.78rem; }
.location-preview-codes { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-bottom: 0.45rem; }
.location-preview-codes code,
.location-code { padding: 0.15rem 0.4rem; border-radius: 0.35rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 0.76rem; }
.location-group { margin-bottom: 1rem; overflow: visible; border: 1px solid var(--location-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.location-group-head { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.1rem; cursor: pointer; list-style: none; }
.location-group-head::-webkit-details-marker { display: none; }
.location-group-head:focus-visible { outline: 2px solid var(--app-accent); outline-offset: -2px; }
.location-group-title { display: flex; flex: 1 1 320px; align-items: center; gap: 0.75rem; min-width: 0; }
.location-group-icon { display: grid; flex: 0 0 auto; width: 2.15rem; height: 2.15rem; place-items: center; border-radius: 0.6rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.1rem; }
.location-group-title strong { display: block; color: var(--app-ink); }
.location-group-title small { display: block; overflow: hidden; max-width: 62ch; color: var(--app-muted); text-overflow: ellipsis; white-space: nowrap; }
.location-group-stats { display: flex; flex-wrap: wrap; gap: 0.35rem; justify-content: flex-end; }
.location-group-stats span { padding: 0.25rem 0.5rem; border-radius: 999px; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.72rem; }
.location-group-stats b { color: var(--app-ink); }
.location-group-stats .location-total { background: var(--app-accent-soft); color: var(--app-accent-ink); font-weight: 700; }
.location-add-wrap { position: relative; flex: 0 0 auto; }
.location-add-button { display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.32rem 0.55rem; border: 1px solid var(--app-accent); border-radius: 0.45rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 0.76rem; font-weight: 700; }
.location-add-menu { position: absolute; top: calc(100% + 0.35rem); right: 0; z-index: 20; display: grid; min-width: 180px; overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.55rem; background: var(--app-surface); box-shadow: 0 0.9rem 2rem rgba(15, 23, 42, 0.18); }
.location-add-menu button { padding: 0.55rem 0.75rem; border: 0; background: transparent; color: var(--app-ink); font-size: 0.8rem; text-align: left; }
.location-add-menu button:hover, .location-add-menu button:focus-visible { background: var(--app-accent-soft); color: var(--app-accent-ink); }
.location-chevron { color: var(--app-muted); transition: transform 160ms ease; }
.location-group[open] .location-chevron { transform: rotate(180deg); }
.location-table th { padding: 0.6rem 1rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.7rem; letter-spacing: 0.05em; text-transform: uppercase; }
.location-table td { padding: 0.7rem 1rem; border-color: var(--app-border); color: var(--app-ink); }
.location-name { display: flex; align-items: flex-start; gap: 0.4rem; }
.location-name strong { display: block; font-size: 0.88rem; }
.location-row-toggle { display: inline-grid; flex: 0 0 auto; width: 1.35rem; height: 1.35rem; margin-top: 0.05rem; place-items: center; border: 0; border-radius: 0.3rem; background: transparent; color: var(--app-muted); }
.location-row-toggle:hover { background: var(--app-surface-2); color: var(--app-ink); }
.location-row-toggle-spacer { display: inline-block; flex: 0 0 1.35rem; }
.location-name small { display: block; max-width: 58ch; overflow: hidden; color: var(--app-muted); font-size: 0.74rem; text-overflow: ellipsis; white-space: nowrap; }
.location-actions { white-space: nowrap; }
.location-actions .btn { padding: 0.25rem 0.4rem; }
.location-empty { display: grid; place-items: center; gap: 0.25rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.location-empty i { font-size: 1.8rem; }
.location-empty strong { color: var(--app-ink); }
.location-empty-compact { padding: 1.5rem 1rem; }
.location-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1080;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: min(8vh, 4rem) 1rem 1rem;
    overflow-y: auto;
    background: rgba(15, 23, 42, 0.62);
}

.location-modal {
    width: min(640px, 100%);
    overflow: hidden;
    border: 1px solid var(--app-border);
    border-radius: 0.9rem;
    background: var(--app-surface);
    box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.28);
    color: var(--app-ink);
}

.location-modal-sm {
    width: min(420px, 100%);
}

.location-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid var(--app-border);
}

.location-modal-body {
    padding: 1.1rem;
}

.location-modal-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.8rem 1.1rem;
    border-top: 1px solid var(--app-border);
    background: var(--app-surface-2);
}

.location-edit-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    color: var(--app-muted);
    font-size: 0.78rem;
}

.qr-preview {
    display: grid;
    min-height: 280px;
    place-items: center;
    padding: 1rem;
    border: 1px solid var(--app-border);
    border-radius: 0.75rem;
    background: #fff;
}

.qr-preview img {
    width: 280px;
    max-width: 100%;
    height: auto;
}

.qr-placeholder {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #475569;
    text-align: center;
}

.qr-placeholder i {
    font-size: 1.4rem;
}

.qr-error {
    color: #991b1b;
}

.qr-meta {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.2rem;
    margin-top: 0.9rem;
    text-align: center;
}

.qr-meta code {
    padding: 0.15rem 0.4rem;
    border-radius: 0.35rem;
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: 0.78rem;
}

.location-quick-hint {
    color: var(--app-muted);
    font-size: 0.82rem;
}

@media (max-width: 991.98px) {
    .location-header { align-items: flex-start; flex-direction: column; }
    .location-group-head { align-items: flex-start; }
    .location-group-stats { display: none; }
}

@media (max-width: 575.98px) {
    .location-modal-backdrop { padding: 0.5rem; }
    .location-modal-footer .btn { flex: 1 1 auto; }
}
</style>
