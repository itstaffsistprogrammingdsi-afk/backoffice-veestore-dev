<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BirthdayGreetingCard from '@/components/BirthdayGreetingCard.vue';
import CustomerDemographicsFilters from '@/components/CustomerDemographicsFilters.vue';
import DemographicsDistributionCard from '@/components/DemographicsDistributionCard.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import {
    channelLabel,
    formatCurrency,
    formatDate,
    formatNumber,
    genderLabel,
    membershipStatusLabel,
    percentage,
    tierBadgeClass,
} from '@/composables/useFormatters';

interface DistributionRow {
    label: string;
    total: number;
}

interface MembershipOption {
    id: string;
    name: string;
}

interface StoreOption {
    id: string;
    name: string;
    code?: string | null;
}

interface RegionOption {
    code: string;
    name: string;
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

interface CustomerRow {
    id: string;
    name: string | null;
    email: string | null;
    wa_number: string | null;
    gender: string | null;
    date_of_birth: string | null;
    age: number | string | null;
    age_group: string | null;
    province_name: string | null;
    regency_name: string | null;
    district_name: string | null;
    village_name: string | null;
    registration_channel: string | null;
    registration_source_id: string | null;
    registration_source_name: string | null;
    membership_name: string | null;
    membership_status: string | null;
    loyalty_points: number | string;
    visit_count: number;
    visited_store_count: number;
    last_visit_at: string | null;
    pos_transactions: number;
    pos_spending: number | string;
    ecom_orders: number;
    ecom_spending: number | string;
    total_spending: number | string;
    is_guest: boolean;
    created_at: string | null;
}

interface BirthdayRow {
    id: string;
    name: string | null;
    wa_number: string | null;
    email: string | null;
    date_of_birth: string | null;
    province_name: string | null;
    regency_name: string | null;
    membership_name: string | null;
}

interface GreetingTemplate {
    subject: string;
    message: string;
    placeholders: string[];
}

interface BirthdayVoucher {
    id: string;
    name: string;
    code: string;
    type: string;
    value: number;
    distribution_type: string;
    valid_until: string | null;
    available_codes_count: number | null;
}

interface Summary {
    totalCustomers: number;
    registeredCustomers: number;
    guestCustomers: number;
    withBirthDate: number;
    withLocation: number;
    birthdayThisMonth: number;
    completeProfiles: number;
    profileCompletion: number;
    memberCustomers: number;
    averageAge: number;
    totalVisits: number;
    posTransactions: number;
    posSpending: number;
    ecomOrders: number;
    ecomSpending: number;
    totalSpending: number;
}

interface Paginator<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    filters: Filters;
    ageGroups: Record<string, string>;
    registrationChannels: string[];
    memberships: MembershipOption[];
    stores: StoreOption[];
    provinces: RegionOption[];
    selectedRegions: {
        regency: RegionOption | null;
        district: RegionOption | null;
        village: RegionOption | null;
    };
    birthdayMonth: number;
    birthdayMonthLabel: string;
    summary: Summary;
    genderRows: DistributionRow[];
    ageRows: DistributionRow[];
    channelRows: DistributionRow[];
    tierRows: DistributionRow[];
    provinceRows: DistributionRow[];
    regencyRows: DistributionRow[];
    districtRows: DistributionRow[];
    villageRows: DistributionRow[];
    sourceRows: DistributionRow[];
    registrationTrend: Array<{ period: string; label: string; total: number }>;
    birthdayCustomers: BirthdayRow[];
    birthdayGreeting: {
        template: GreetingTemplate;
        vouchers: BirthdayVoucher[];
    };
    rows: Paginator<CustomerRow>;
    urls: {
        index: string;
        export: string;
        crm: string;
        customerProfiles: string;
        customerProfile: string;
        preference: string;
        birthdayGreeting: string;
        regions: {
            regencies: string;
            districts: string;
            villages: string;
        };
    };
}

const props = defineProps<Props>();

const applyFilters = (query: Record<string, string | undefined>) => {
    router.get(props.urls.index, query, { preserveScroll: true, replace: true });
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(props.filters)) {
        if (value) params.set(key, String(value));
    }

    const suffix = params.toString();

    return suffix ? `${props.urls.export}?${suffix}` : props.urls.export;
});

const summaryCards = computed(() => [
    {
        label: 'Total Customer',
        value: formatNumber(props.summary.totalCustomers),
        hint: `${formatNumber(props.summary.registeredCustomers)} terdaftar · ${formatNumber(props.summary.guestCustomers)} guest`,
        icon: 'mdi-account-group-outline',
        tone: 'accent',
    },
    {
        label: 'Profil Lengkap',
        value: `${props.summary.profileCompletion}%`,
        hint: `${formatNumber(props.summary.completeProfiles)} dari ${formatNumber(props.summary.totalCustomers)}`,
        icon: 'mdi-account-check-outline',
        tone: 'success',
    },
    {
        label: 'Punya Lokasi',
        value: formatNumber(props.summary.withLocation),
        hint: `${formatNumber(props.summary.totalCustomers - props.summary.withLocation)} belum diisi`,
        icon: 'mdi-map-marker-check-outline',
        tone: 'info',
    },
    {
        label: 'Ulang Tahun',
        value: formatNumber(props.summary.birthdayThisMonth),
        hint: props.birthdayMonthLabel,
        icon: 'mdi-cake-variant-outline',
        tone: 'warning',
    },
    {
        label: 'Member Tier',
        value: formatNumber(props.summary.memberCustomers),
        hint: `${percentage(props.summary.memberCustomers, props.summary.totalCustomers)}% dari total`,
        icon: 'mdi-crown-outline',
        tone: 'accent',
    },
    {
        label: 'Total Kunjungan',
        value: formatNumber(props.summary.totalVisits),
        hint: `${formatNumber(props.summary.posTransactions)} transaksi POS`,
        icon: 'mdi-account-clock-outline',
        tone: 'info',
    },
    {
        label: 'Order Ecommerce',
        value: formatNumber(props.summary.ecomOrders),
        hint: 'Order dengan pembayaran lunas',
        icon: 'mdi-cart-check',
        tone: 'success',
    },
    {
        label: 'Total Belanja',
        value: formatCurrency(props.summary.totalSpending),
        hint: `POS ${formatCurrency(props.summary.posSpending)} · Ecom ${formatCurrency(props.summary.ecomSpending)}`,
        icon: 'mdi-cash-multiple',
        tone: 'warning',
    },
]);

const regionLabel = (row: CustomerRow) => {
    const parts = [row.village_name, row.district_name, row.regency_name, row.province_name].filter(Boolean);

    return parts.length > 0 ? parts.join(', ') : 'Belum diisi';
};

const profileUrl = (row: CustomerRow) => props.urls.customerProfile.replace('__ID__', row.id);
</script>

<template>
    <Head title="CRM Demographics" />

    <div class="demo-page">
        <section class="demo-header">
            <div>
                <span class="demo-eyebrow">Reports / CRM</span>
                <h1>Customer Demographics</h1>
                <p>Analisis lengkap segmentasi customer berdasarkan gender, usia, wilayah, kanal registrasi, tier, dan nilai belanja.</p>
            </div>
            <div class="demo-header-actions">
                <Link :href="props.urls.crm" class="demo-hero-button">
                    <i class="mdi mdi-account-heart-outline me-1"></i>
                    CRM Dashboard
                </Link>
                <Link :href="props.urls.customerProfiles" class="demo-hero-button">
                    <i class="mdi mdi-account-search-outline me-1"></i>
                    Customer Profiles
                </Link>
                <Link :href="props.urls.preference" class="demo-hero-button">
                    <i class="mdi mdi-account-star-outline me-1"></i>
                    Preferensi Produk
                </Link>
                <a :href="exportUrl" class="demo-hero-button demo-hero-button-accent">
                    <i class="mdi mdi-download me-1"></i>
                    Ekspor Excel
                </a>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div v-for="card in summaryCards" :key="card.label" class="col-6 col-md-3 col-xl-3">
                <div class="demo-summary" :class="`demo-summary-${card.tone}`">
                    <span class="demo-summary-icon"><i class="mdi" :class="card.icon"></i></span>
                    <div>
                        <span class="demo-summary-label">{{ card.label }}</span>
                        <strong class="demo-summary-value">{{ card.value }}</strong>
                        <small>{{ card.hint }}</small>
                    </div>
                </div>
            </div>
        </div>

        <CustomerDemographicsFilters
            :filters="props.filters"
            :age-groups="props.ageGroups"
            :registration-channels="props.registrationChannels"
            :memberships="props.memberships"
            :stores="props.stores"
            :provinces="props.provinces"
            :selected-regions="props.selectedRegions"
            :urls="props.urls.regions"
            @apply="applyFilters"
        />

        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3">
                <DemographicsDistributionCard title="Gender" icon="mdi-gender-male-female" :rows="props.genderRows" :total="props.summary.totalCustomers" />
            </div>
            <div class="col-md-6 col-xl-3">
                <DemographicsDistributionCard title="Kelompok Usia" icon="mdi-account-clock-outline" :rows="props.ageRows" :total="props.summary.totalCustomers" :subtitle="`Rata-rata usia ${props.summary.averageAge} tahun`" />
            </div>
            <div class="col-md-6 col-xl-3">
                <DemographicsDistributionCard title="Membership Tier" icon="mdi-crown-outline" :rows="props.tierRows" :total="props.summary.totalCustomers" />
            </div>
            <div class="col-md-6 col-xl-3">
                <DemographicsDistributionCard title="Kanal Registrasi" icon="mdi-source-branch" :rows="props.channelRows" :total="props.summary.totalCustomers" />
            </div>

            <div class="col-md-6 col-xl-3">
                <DemographicsDistributionCard title="Provinsi" icon="mdi-map-marker-outline" :rows="props.provinceRows" :total="props.summary.totalCustomers" />
            </div>
            <div class="col-md-6 col-xl-3">
                <DemographicsDistributionCard title="Kota / Kabupaten" icon="mdi-city-variant-outline" :rows="props.regencyRows" :total="props.summary.totalCustomers" />
            </div>
            <div class="col-md-6 col-xl-3">
                <DemographicsDistributionCard title="Kecamatan" icon="mdi-map-marker-radius-outline" :rows="props.districtRows" :total="props.summary.totalCustomers" />
            </div>
            <div class="col-md-6 col-xl-3">
                <DemographicsDistributionCard title="Kelurahan / Desa" icon="mdi-home-map-marker" :rows="props.villageRows" :total="props.summary.totalCustomers" />
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-5">
                <DemographicsDistributionCard title="Sumber Registrasi" icon="mdi-store-outline" :rows="props.sourceRows" :total="props.summary.totalCustomers" />
            </div>
            <div class="col-lg-7">
                <DemographicsDistributionCard
                    title="Registrasi 12 Bulan Terakhir"
                    icon="mdi-chart-timeline-variant"
                    subtitle="Jumlah customer baru per bulan mengikuti filter yang aktif."
                    :rows="props.registrationTrend.map((row) => ({ label: row.label, total: row.total }))"
                />
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12">
                <BirthdayGreetingCard
                    :customers="props.birthdayCustomers"
                    :total="props.summary.birthdayThisMonth"
                    :month-label="props.birthdayMonthLabel"
                    :template="props.birthdayGreeting.template"
                    :vouchers="props.birthdayGreeting.vouchers"
                    :send-url="props.urls.birthdayGreeting"
                    title="Customer Ulang Tahun"
                    subtitle="Daftar customer yang dapat menjadi target komunikasi dan program loyalitas."
                />
            </div>
        </div>

        <section class="demo-table-card">
            <div class="demo-table-header">
                <div>
                    <h2>Data Customer</h2>
                    <p>Seluruh kolom demografi, membership, kunjungan, dan nilai belanja sesuai filter.</p>
                </div>
                <span class="demo-table-meta">{{ formatNumber(props.rows.total) }} customer</span>
            </div>

            <div v-if="props.rows.data.length === 0" class="demo-empty">
                <i class="mdi mdi-account-off-outline"></i>
                <strong>Tidak ada customer</strong>
                <span>Coba ubah kata kunci atau filter yang dipakai.</span>
                <a :href="props.urls.index" class="btn btn-sm btn-outline-secondary mt-2">Reset filter</a>
            </div>

            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 demo-table">
                    <caption class="visually-hidden">Data customer sesuai filter demografi</caption>
                    <thead>
                        <tr>
                            <th scope="col">Customer</th>
                            <th scope="col">Gender / Usia</th>
                            <th scope="col">Tanggal Lahir</th>
                            <th scope="col">Wilayah</th>
                            <th scope="col">Kanal / Store</th>
                            <th scope="col">Tier / Poin</th>
                            <th scope="col" class="text-end">Kunjungan</th>
                            <th scope="col" class="text-end">Transaksi</th>
                            <th scope="col" class="text-end">Belanja</th>
                            <th scope="col">Terdaftar</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.rows.data" :key="row.id">
                            <td>
                                <div class="demo-customer">
                                    <strong>{{ row.name ?? '-' }}</strong>
                                    <small>{{ row.wa_number ?? row.email ?? '-' }}</small>
                                    <div class="demo-customer-meta">
                                        <span v-if="row.is_guest" class="badge bg-label-warning">Guest</span>
                                        <span v-else class="badge bg-label-success">Terdaftar</span>
                                        <span v-if="row.last_visit_at" class="badge bg-label-secondary">Kunjungan {{ formatDate(row.last_visit_at) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="demo-strong">{{ genderLabel(row.gender) }}</span>
                                <small class="demo-cell-note">{{ row.age ?? '-' }} tahun · {{ row.age_group ?? '-' }}</small>
                            </td>
                            <td>{{ formatDate(row.date_of_birth) }}</td>
                            <td><span class="demo-region">{{ regionLabel(row) }}</span></td>
                            <td>
                                <span class="badge bg-label-primary">{{ channelLabel(row.registration_channel) }}</span>
                                <small class="demo-cell-note">{{ row.registration_source_name ?? row.registration_source_id ?? 'Tanpa sumber' }}</small>
                            </td>
                            <td>
                                <span class="badge" :class="tierBadgeClass(row.membership_name)">{{ row.membership_name ?? 'Non-member' }}</span>
                                <small class="demo-cell-note">{{ row.membership_status ? membershipStatusLabel(row.membership_status) : 'Tanpa membership' }}</small>
                                <small class="demo-cell-note">{{ formatNumber(row.loyalty_points) }} poin</small>
                            </td>
                            <td class="text-end">
                                <strong>{{ formatNumber(row.visit_count) }}</strong>
                                <small class="demo-cell-note">{{ formatNumber(row.visited_store_count) }} store</small>
                            </td>
                            <td class="text-end">
                                <span class="demo-strong">{{ formatNumber(row.pos_transactions) }} POS</span>
                                <small class="demo-cell-note">{{ formatNumber(row.ecom_orders) }} Ecom</small>
                            </td>
                            <td class="text-end">
                                <strong>{{ formatCurrency(row.total_spending) }}</strong>
                                <small class="demo-cell-note">POS {{ formatCurrency(row.pos_spending) }}</small>
                                <small class="demo-cell-note">Ecom {{ formatCurrency(row.ecom_spending) }}</small>
                            </td>
                            <td>{{ formatDate(row.created_at) }}</td>
                            <td class="text-end">
                                <Link :href="profileUrl(row)" class="btn btn-sm btn-outline-primary">Profil</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="demo-table-footer">
                <small>Menampilkan {{ props.rows.from ?? 0 }} - {{ props.rows.to ?? 0 }} dari {{ formatNumber(props.rows.total) }} customer</small>
                <PaginationLinks :links="props.rows.links" />
            </div>
        </section>
    </div>
</template>

<style scoped>
.demo-page { color: var(--app-ink); }
.demo-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: #fff; }
.demo-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.demo-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.demo-header p { max-width: 760px; margin: 0; color: var(--app-hero-muted) !important; }
.demo-header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.demo-hero-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface); color: var(--app-ink); font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.demo-hero-button-accent { background: var(--app-accent-strong); color: var(--app-on-accent); }
.demo-summary { display: flex; align-items: flex-start; gap: 0.75rem; height: 100%; padding: 0.9rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.demo-summary-icon { display: grid; flex: 0 0 auto; width: 2.2rem; height: 2.2rem; place-items: center; border-radius: 0.6rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.15rem; }
.demo-summary-label { display: block; color: var(--app-muted); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.demo-summary-value { display: block; margin-top: 0.1rem; font-size: 1.15rem; line-height: 1.2; }
.demo-summary small { display: block; margin-top: 0.15rem; color: var(--app-muted); font-size: 0.7rem; }
.demo-summary-success .demo-summary-icon { background: var(--app-success-bg); color: var(--app-success-ink); }
.demo-summary-warning .demo-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.demo-summary-danger .demo-summary-icon { background: var(--app-danger-bg); color: var(--app-danger-ink); }
.demo-summary-info .demo-summary-icon { background: var(--app-info-bg); color: var(--app-info-ink); }
.demo-table-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.demo-table-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.demo-table-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.demo-table-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.demo-table-meta { color: var(--app-muted); font-size: 0.78rem; white-space: nowrap; }
.demo-table { min-width: 1280px; }
.demo-table th { padding: 0.6rem 0.85rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.68rem; letter-spacing: 0.04em; text-transform: uppercase; }
.demo-table td { padding: 0.75rem 0.85rem; border-color: var(--app-border); font-size: 0.82rem; }
.demo-table th:first-child, .demo-table td:first-child { position: sticky; left: 0; z-index: 2; background: var(--app-surface); }
.demo-table thead th:first-child { z-index: 3; background: var(--app-surface-2); }
.demo-customer strong, .demo-customer small { display: block; }
.demo-customer small { color: var(--app-muted); font-size: 0.74rem; }
.demo-customer-meta { display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.35rem; }
.demo-customer-meta .badge { font-size: 0.64rem; }
.demo-region { display: block; max-width: 220px; color: var(--app-muted); line-height: 1.45; }
.demo-cell-note { display: block; margin-top: 0.15rem; color: var(--app-muted); font-size: 0.7rem; }
.demo-strong { font-weight: 700; }
.demo-table-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.8rem; padding: 0.85rem 1.1rem; border-top: 1px solid var(--app-border); }
.demo-table-footer small { color: var(--app-muted); }
.demo-empty { display: grid; place-items: center; gap: 0.3rem; padding: 3rem 1rem; color: var(--app-muted); text-align: center; }
.demo-empty i { color: var(--app-accent-ink); font-size: 1.8rem; }
.demo-empty strong { color: var(--app-ink); }
@media (max-width: 991.98px) {
    .demo-header { align-items: flex-start; flex-direction: column; }
    .demo-table-header { align-items: flex-start; flex-direction: column; }
}
</style>
