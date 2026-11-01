<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';

interface LabelRow { label: string; total: number }
interface BirthdayRow { id: string; name: string | null; wa_number: string | null; date_of_birth: string | null; province_name: string | null }
interface RegionRow { code: string; name: string }

interface Props {
    q: string;
    gender: string;
    provinceCode: string;
    regencyCode: string;
    districtCode: string;
    villageCode: string;
    registrationChannel: string;
    birthMonth: number;
    birthdayMonth: number;
    ageGroup: string;
    ageGroups: Record<string, string>;
    totalCustomers: number;
    withBirthDate: number;
    withLocation: number;
    birthdayThisMonth: number;
    completeProfiles: number;
    profileCompletion: number;
    genderRows: LabelRow[];
    ageRows: LabelRow[];
    channelRows: LabelRow[];
    provinceRows: LabelRow[];
    regencyRows: LabelRow[];
    birthdayCustomers: BirthdayRow[];
    provinces: RegionRow[];
    registrationChannels: string[];
    urls: { index: string; export: string };
}

const props = defineProps<Props>();

const form = reactive({
    q: props.q ?? '',
    gender: props.gender ?? '',
    age_group: props.ageGroup ?? '',
    birth_month: props.birthMonth || '',
    registration_channel: props.registrationChannel ?? '',
});

const query = computed(() => ({
    q: form.q || undefined,
    gender: form.gender || undefined,
    age_group: form.age_group || undefined,
    birth_month: form.birth_month || undefined,
    registration_channel: form.registration_channel || undefined,
}));

const apply = () => {
    router.get(props.urls.index, query.value, { preserveState: true, preserveScroll: true, replace: true });
};

const reset = () => {
    form.q = '';
    form.gender = '';
    form.age_group = '';
    form.birth_month = '';
    form.registration_channel = '';
    apply();
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(query.value)) if (value) params.set(key, String(value));
    const suffix = params.toString();
    return suffix ? `${props.urls.export}?${suffix}` : props.urls.export;
});

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);
const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)) : '-';

const cards = computed(() => [
    { label: 'Total Customer', value: props.totalCustomers },
    { label: 'Punya Tanggal Lahir', value: props.withBirthDate },
    { label: 'Punya Lokasi', value: props.withLocation },
    { label: 'Ulang Tahun Bulan Ini', value: props.birthdayThisMonth },
    { label: 'Profil Lengkap', value: props.completeProfiles },
    { label: 'Kelengkapan Profil', value: `${props.profileCompletion}%` },
]);
</script>

<template>
    <Head title="CRM Demographics" />
    <div class="report-page">
        <div class="report-header">
            <div>
                <span class="report-eyebrow">Reports / CRM</span>
                <h1>Demografi Customer</h1>
                <p>Segmentasi customer berdasarkan gender, usia, kanal registrasi, dan wilayah.</p>
            </div>
            <a :href="exportUrl" class="btn btn-light btn-sm"><i class="mdi mdi-download me-1"></i>Export Excel</a>
        </div>

        <div class="row g-3 mb-3">
            <div v-for="card in cards" :key="card.label" class="col-6 col-md-4 col-xl-2">
                <div class="card report-card h-100"><div class="card-body"><div class="report-label">{{ card.label }}</div><div class="report-value">{{ typeof card.value === 'number' ? formatNumber(card.value) : card.value }}</div></div></div>
            </div>
        </div>

        <div class="card report-card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="apply">
                    <div class="col-md-3"><label class="form-label">Cari</label><input v-model="form.q" class="form-control form-control-sm" type="search" placeholder="Nama, email, WA..." /></div>
                    <div class="col-md-2"><label class="form-label">Gender</label><select v-model="form.gender" class="form-select form-select-sm"><option value="">Semua</option><option value="male">Laki-laki</option><option value="female">Perempuan</option></select></div>
                    <div class="col-md-2"><label class="form-label">Kelompok Usia</label><select v-model="form.age_group" class="form-select form-select-sm"><option value="">Semua</option><option v-for="(label, value) in ageGroups" :key="value" :value="value">{{ label }}</option></select></div>
                    <div class="col-md-2"><label class="form-label">Bulan Lahir</label><input v-model="form.birth_month" class="form-control form-control-sm" type="number" min="1" max="12" /></div>
                    <div class="col-md-3"><label class="form-label">Kanal Registrasi</label><select v-model="form.registration_channel" class="form-select form-select-sm"><option value="">Semua</option><option v-for="channel in registrationChannels" :key="channel" :value="channel">{{ channel }}</option></select></div>
                    <div class="col-12 d-flex gap-2 mt-2"><button class="btn btn-sm btn-primary" type="submit">Filter</button><button class="btn btn-sm btn-outline-secondary" type="button" @click="reset">Reset</button></div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div v-for="section in [{ title: 'Gender', rows: genderRows }, { title: 'Kelompok Usia', rows: ageRows }, { title: 'Kanal Registrasi', rows: channelRows }, { title: 'Provinsi', rows: provinceRows }]" :key="section.title" class="col-md-6">
                <div class="card report-card h-100">
                    <div class="card-header"><h6 class="mb-0">{{ section.title }}</h6></div>
                    <ul class="list-group list-group-flush">
                        <li v-for="row in section.rows.slice(0, 8)" :key="row.label" class="list-group-item d-flex justify-content-between"><span>{{ row.label }}</span><strong>{{ formatNumber(row.total) }}</strong></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card report-card">
            <div class="card-header"><h6 class="mb-0">Customer Ulang Tahun Bulan Ini</h6></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Nama</th><th>WA</th><th>Tanggal Lahir</th><th>Provinsi</th></tr></thead><tbody>
                <tr v-for="row in birthdayCustomers" :key="row.id"><td>{{ row.name ?? '-' }}</td><td>{{ row.wa_number ?? '-' }}</td><td>{{ formatDate(row.date_of_birth) }}</td><td>{{ row.province_name ?? '-' }}</td></tr>
                <tr v-if="birthdayCustomers.length === 0"><td colspan="4" class="report-empty">Belum ada data.</td></tr>
            </tbody></table></div>
        </div>
    </div>
</template>

<style scoped>
.report-page { --report-ink: var(--app-ink); --report-border: var(--app-border); --report-muted: var(--app-muted); color: var(--report-ink); }
.report-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--report-ink); color: #fff; }
.report-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: #f9a8d4; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.report-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; }
.report-header p { margin: 0; color: var(--app-hero-muted); }
.report-card { border: 1px solid var(--report-border); border-radius: 0.9rem; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.report-card th { color: var(--report-muted); font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; }
.report-label { color: var(--report-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.report-value { margin-top: 0.3rem; font-size: 1.2rem; font-weight: 800; }
.report-empty { padding: 2rem 1rem; color: var(--report-muted); text-align: center; }
</style>
