<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { channelLabel } from '@/composables/useFormatters';

interface Option {
    id: string;
    name: string;
    code?: string | null;
}

interface RegionOption {
    code: string;
    name: string;
}

interface SelectedRegions {
    regency: RegionOption | null;
    district: RegionOption | null;
    village: RegionOption | null;
}

interface Filters {
    q: string;
    gender: string;
    age_group: string;
    birth_month: string;
    registration_channel: string;
    membership: string;
    store_id: string;
    province_code: string;
    regency_code: string;
    district_code: string;
    village_code: string;
    is_guest: string;
    joined_from: string;
    joined_to: string;
    sort: string;
}

interface Props {
    filters: Filters;
    ageGroups: Record<string, string>;
    registrationChannels: string[];
    memberships: Option[];
    stores: Option[];
    provinces: RegionOption[];
    selectedRegions: SelectedRegions;
    urls: {
        regencies: string;
        districts: string;
        villages: string;
    };
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (event: 'apply', value: Record<string, string | undefined>): void;
}>();

const monthOptions = [
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const sortOptions = [
    { value: 'name', label: 'Nama A-Z' },
    { value: 'newest', label: 'Terbaru mendaftar' },
    { value: 'oldest', label: 'Terlama mendaftar' },
    { value: 'age_youngest', label: 'Usia termuda' },
    { value: 'age_oldest', label: 'Usia tertua' },
    { value: 'visits', label: 'Kunjungan terbanyak' },
    { value: 'last_visit', label: 'Kunjungan terakhir' },
    { value: 'spending', label: 'Belanja tertinggi' },
    { value: 'points', label: 'Poin terbanyak' },
];

const form = reactive<Filters>({
    q: props.filters.q ?? '',
    gender: props.filters.gender ?? '',
    age_group: props.filters.age_group ?? '',
    birth_month: props.filters.birth_month ?? '',
    registration_channel: props.filters.registration_channel ?? '',
    membership: props.filters.membership ?? '',
    store_id: props.filters.store_id ?? '',
    province_code: props.filters.province_code ?? '',
    regency_code: props.filters.regency_code ?? '',
    district_code: props.filters.district_code ?? '',
    village_code: props.filters.village_code ?? '',
    is_guest: props.filters.is_guest ?? '',
    joined_from: props.filters.joined_from ?? '',
    joined_to: props.filters.joined_to ?? '',
    sort: props.filters.sort ?? 'name',
});

const advancedOpen = ref(false);
const regencies = ref<RegionOption[]>(props.selectedRegions.regency ? [props.selectedRegions.regency] : []);
const districts = ref<RegionOption[]>(props.selectedRegions.district ? [props.selectedRegions.district] : []);
const villages = ref<RegionOption[]>(props.selectedRegions.village ? [props.selectedRegions.village] : []);
const loadingRegion = ref('');
const regionError = ref('');

let regencyRequest = 0;
let districtRequest = 0;
let villageRequest = 0;

const activeFilterCount = computed(() => {
    const keys: Array<keyof Filters> = [
        'gender',
        'age_group',
        'birth_month',
        'registration_channel',
        'membership',
        'store_id',
        'province_code',
        'regency_code',
        'district_code',
        'village_code',
        'is_guest',
        'joined_from',
        'joined_to',
    ];

    return keys.filter((key) => form[key] !== '').length + (form.q.trim() !== '' ? 1 : 0);
});

const buildUrl = (base: string, key: string, value: string) => {
    const separator = base.includes('?') ? '&' : '?';

    return `${base}${separator}${key}=${encodeURIComponent(value)}`;
};

const parseRegions = (payload: unknown): RegionOption[] => {
    const results = (payload as { results?: Array<{ id: string; text: string }> })?.results ?? [];

    return results.map((row) => ({ code: row.id, name: row.text }));
};

const loadRegencies = async (clear = true) => {
    const requestId = ++regencyRequest;

    if (clear) regencies.value = [];

    if (!form.province_code) return;

    loadingRegion.value = 'regency';
    regionError.value = '';

    try {
        const response = await fetch(buildUrl(props.urls.regencies, 'province_code', form.province_code), {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        if (requestId === regencyRequest) {
            regencies.value = parseRegions(payload);
        }
    } catch {
        if (requestId === regencyRequest) regionError.value = 'Gagal memuat daftar kota / kabupaten.';
    } finally {
        if (requestId === regencyRequest) loadingRegion.value = '';
    }
};

const loadDistricts = async (clear = true) => {
    const requestId = ++districtRequest;

    if (clear) districts.value = [];

    if (!form.regency_code) return;

    loadingRegion.value = 'district';
    regionError.value = '';

    try {
        const response = await fetch(buildUrl(props.urls.districts, 'regency_code', form.regency_code), {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        if (requestId === districtRequest) {
            districts.value = parseRegions(payload);
        }
    } catch {
        if (requestId === districtRequest) regionError.value = 'Gagal memuat daftar kecamatan.';
    } finally {
        if (requestId === districtRequest) loadingRegion.value = '';
    }
};

const loadVillages = async (clear = true) => {
    const requestId = ++villageRequest;

    if (clear) villages.value = [];

    if (!form.district_code) return;

    loadingRegion.value = 'village';
    regionError.value = '';

    try {
        const response = await fetch(buildUrl(props.urls.villages, 'district_code', form.district_code), {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        if (requestId === villageRequest) {
            villages.value = parseRegions(payload);
        }
    } catch {
        if (requestId === villageRequest) regionError.value = 'Gagal memuat daftar kelurahan / desa.';
    } finally {
        if (requestId === villageRequest) loadingRegion.value = '';
    }
};

watch(
    () => form.province_code,
    async (value, previous) => {
        if (value === previous) return;

        form.regency_code = '';
        form.district_code = '';
        form.village_code = '';
        districts.value = [];
        villages.value = [];
        await loadRegencies();
    },
);

watch(
    () => form.regency_code,
    async (value, previous) => {
        if (value === previous) return;

        form.district_code = '';
        form.village_code = '';
        villages.value = [];
        await loadDistricts();
    },
);

watch(
    () => form.district_code,
    async (value, previous) => {
        if (value === previous) return;

        form.village_code = '';
        await loadVillages();
    },
);

const payload = (): Record<string, string | undefined> => {
    const values: Record<string, string | undefined> = {};

    for (const [key, value] of Object.entries(form)) {
        values[key] = value === '' ? undefined : value;
    }

    return values;
};

const apply = () => {
    emit('apply', payload());
};

const reset = () => {
    form.q = '';
    form.gender = '';
    form.age_group = '';
    form.birth_month = '';
    form.registration_channel = '';
    form.membership = '';
    form.store_id = '';
    form.province_code = '';
    form.regency_code = '';
    form.district_code = '';
    form.village_code = '';
    form.is_guest = '';
    form.joined_from = '';
    form.joined_to = '';
    form.sort = 'name';
    regencies.value = [];
    districts.value = [];
    villages.value = [];
    emit('apply', {});
};

onMounted(() => {
    if (form.province_code) void loadRegencies(false);
    if (form.regency_code) void loadDistricts(false);
    if (form.district_code) void loadVillages(false);
});
</script>

<template>
    <section class="demo-filter" aria-label="Filter demografi customer">
        <form class="demo-filter-form" @submit.prevent="apply">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="demo-search">Cari customer</label>
                    <input
                        id="demo-search"
                        v-model="form.q"
                        class="form-control form-control-sm"
                        type="search"
                        placeholder="Nama, WhatsApp, email, referral, alamat, atau store..."
                    />
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="demo-gender">Gender</label>
                    <select id="demo-gender" v-model="form.gender" class="form-select form-select-sm">
                        <option value="">Semua gender</option>
                        <option value="male">Laki-laki</option>
                        <option value="female">Perempuan</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="demo-age">Kelompok usia</label>
                    <select id="demo-age" v-model="form.age_group" class="form-select form-select-sm">
                        <option value="">Semua usia</option>
                        <option v-for="(label, value) in props.ageGroups" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="demo-channel">Kanal registrasi</label>
                    <select id="demo-channel" v-model="form.registration_channel" class="form-select form-select-sm">
                        <option value="">Semua kanal</option>
                        <option v-for="channel in props.registrationChannels" :key="channel" :value="channel">{{ channelLabel(channel) }}</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label" for="demo-tier">Membership</label>
                    <select id="demo-tier" v-model="form.membership" class="form-select form-select-sm">
                        <option value="">Semua tier</option>
                        <option value="none">Belum memiliki tier</option>
                        <option v-for="membership in props.memberships" :key="membership.id" :value="membership.id">{{ membership.name }}</option>
                    </select>
                </div>

                <div id="demo-advanced-filters" class="demo-filter-advanced">
                    <template v-if="advancedOpen">
                    <div class="col-12"><div class="demo-filter-divider"></div></div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label" for="demo-store">Store registrasi</label>
                        <select id="demo-store" v-model="form.store_id" class="form-select form-select-sm">
                            <option value="">Semua store</option>
                            <option v-for="store in props.stores" :key="store.id" :value="store.id">{{ store.name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label" for="demo-province">Provinsi</label>
                        <select id="demo-province" v-model="form.province_code" class="form-select form-select-sm">
                            <option value="">Semua provinsi</option>
                            <option v-for="province in props.provinces" :key="province.code" :value="province.code">{{ province.name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label" for="demo-regency">Kota / Kabupaten</label>
                        <select
                            id="demo-regency"
                            v-model="form.regency_code"
                            class="form-select form-select-sm"
                            :disabled="!form.province_code || loadingRegion === 'regency'"
                        >
                            <option value="">{{ form.province_code ? 'Semua kota / kabupaten' : 'Pilih provinsi dahulu' }}</option>
                            <option v-for="regency in regencies" :key="regency.code" :value="regency.code">{{ regency.name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label" for="demo-district">Kecamatan</label>
                        <select
                            id="demo-district"
                            v-model="form.district_code"
                            class="form-select form-select-sm"
                            :disabled="!form.regency_code || loadingRegion === 'district'"
                        >
                            <option value="">{{ form.regency_code ? 'Semua kecamatan' : 'Pilih kota / kabupaten dahulu' }}</option>
                            <option v-for="district in districts" :key="district.code" :value="district.code">{{ district.name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="form-label" for="demo-village">Kelurahan / Desa</label>
                        <select
                            id="demo-village"
                            v-model="form.village_code"
                            class="form-select form-select-sm"
                            :disabled="!form.district_code || loadingRegion === 'village'"
                        >
                            <option value="">{{ form.district_code ? 'Semua kelurahan / desa' : 'Pilih kecamatan dahulu' }}</option>
                            <option v-for="village in villages" :key="village.code" :value="village.code">{{ village.name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="demo-birth-month">Bulan lahir</label>
                        <select id="demo-birth-month" v-model="form.birth_month" class="form-select form-select-sm">
                            <option value="">Semua bulan</option>
                            <option v-for="month in monthOptions" :key="month.value" :value="month.value">{{ month.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="demo-status">Status customer</label>
                        <select id="demo-status" v-model="form.is_guest" class="form-select form-select-sm">
                            <option value="">Semua status</option>
                            <option value="no">Terdaftar</option>
                            <option value="yes">Guest</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="demo-sort">Urutkan</label>
                        <select id="demo-sort" v-model="form.sort" class="form-select form-select-sm">
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="demo-joined-from">Daftar dari</label>
                        <input
                            id="demo-joined-from"
                            v-model="form.joined_from"
                            class="form-control form-control-sm"
                            type="date"
                            :max="form.joined_to || undefined"
                        />
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="demo-joined-to">Daftar sampai</label>
                        <input
                            id="demo-joined-to"
                            v-model="form.joined_to"
                            class="form-control form-control-sm"
                            type="date"
                            :min="form.joined_from || undefined"
                        />
                    </div>
                    <div class="col-12 col-lg-2 d-flex align-items-center">
                        <span v-if="loadingRegion" class="demo-filter-loading" role="status">
                            <i class="mdi mdi-loading mdi-spin"></i>
                            Memuat wilayah...
                        </span>
                        <span v-else-if="regionError" class="demo-filter-error" role="alert">{{ regionError }}</span>
                    </div>
                    </template>
                </div>
            </div>

            <div class="demo-filter-actions">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button class="btn btn-sm btn-primary" type="submit">
                        <i class="mdi mdi-filter-outline me-1"></i>
                        Terapkan filter
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">
                        <i class="mdi mdi-refresh me-1"></i>
                        Reset
                    </button>
                    <button
                        class="btn btn-sm btn-light"
                        type="button"
                        :aria-expanded="advancedOpen"
                        aria-controls="demo-advanced-filters"
                        @click="advancedOpen = !advancedOpen"
                    >
                        <i class="mdi me-1" :class="advancedOpen ? 'mdi-chevron-up' : 'mdi-tune-variant'"></i>
                        {{ advancedOpen ? 'Sembunyikan filter lanjutan' : 'Filter lanjutan' }}
                    </button>
                </div>
                <span class="demo-filter-meta">
                    <span v-if="activeFilterCount > 0" class="badge bg-label-primary">{{ activeFilterCount }} filter aktif</span>
                    <span v-else>Belum ada filter aktif</span>
                </span>
            </div>
        </form>
    </section>
</template>

<style scoped>
.demo-filter {
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--app-border);
    border-radius: 0.9rem;
    background: var(--app-surface);
    box-shadow: var(--app-shadow);
}
.demo-filter-form .form-label {
    margin-bottom: 0.3rem;
    color: var(--app-muted);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}
.demo-filter-advanced {
    display: contents;
}
.demo-filter-divider {
    height: 1px;
    margin: 0.55rem 0 0.15rem;
    background: var(--app-border);
}
.demo-filter-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 0.9rem;
    padding-top: 0.9rem;
    border-top: 1px solid var(--app-border);
}
.demo-filter-meta {
    color: var(--app-muted);
    font-size: 0.78rem;
}
.demo-filter-loading {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    color: var(--app-muted);
    font-size: 0.75rem;
}
.demo-filter-error {
    color: var(--app-danger-ink);
    font-size: 0.75rem;
}
@media (max-width: 575.98px) {
    .demo-filter-actions > div {
        width: 100%;
    }
    .demo-filter-actions .btn {
        flex: 1 1 auto;
    }
}
</style>
