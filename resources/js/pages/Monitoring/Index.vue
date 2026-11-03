<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface MonitoringItem { name: string; url: string; icon?: string | null }
interface MonitoringGroup { name: string; description?: string; icon?: string; items: MonitoringItem[] }

const props = defineProps<{ monitoringGroups: MonitoringGroup[]; overviewUrl: string }>();

const search = ref('');

const normalizedSearch = computed(() => search.value.trim().toLowerCase());

const filteredGroups = computed(() => {
    const needle = normalizedSearch.value;

    if (!needle) return props.monitoringGroups;

    return props.monitoringGroups
        .map((group) => {
            const groupMatches = group.name.toLowerCase().includes(needle);
            const items = groupMatches
                ? group.items
                : group.items.filter((item) => item.name.toLowerCase().includes(needle));

            return { ...group, items };
        })
        .filter((group) => group.items.length > 0);
});

const totalItems = computed(() => props.monitoringGroups.reduce((total, group) => total + group.items.length, 0));
const visibleItems = computed(() => filteredGroups.value.reduce((total, group) => total + group.items.length, 0));
</script>

<template>
    <Head title="Monitoring" />
    <div class="monitoring-page">
        <header class="monitoring-header">
            <div class="monitoring-header-copy">
                <span class="monitoring-eyebrow">Laporan</span>
                <h1>Monitoring Command Center</h1>
                <p>Akses seluruh laporan operasional dari satu tempat.</p>
            </div>

            <div class="monitoring-header-tools">
                <Link :href="props.overviewUrl" class="monitoring-overview-link">
                    <i class="mdi mdi-view-dashboard-outline me-1"></i>
                    Ringkasan Usaha
                </Link>
                <div class="monitoring-search">
                    <label for="monitoring-search-input" class="visually-hidden">Cari submenu monitoring</label>
                <i class="mdi mdi-magnify monitoring-search-icon" aria-hidden="true"></i>
                <input
                    id="monitoring-search-input"
                    v-model="search"
                    type="search"
                    class="form-control"
                    placeholder="Cari submenu monitoring..."
                    autocomplete="off"
                />
                    <button
                        v-if="search"
                        type="button"
                        class="monitoring-search-clear"
                        aria-label="Hapus pencarian"
                        @click="search = ''"
                    >
                        <i class="mdi mdi-close" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </header>

        <div class="monitoring-meta">
            <span>{{ totalItems }} submenu monitoring</span>
            <span v-if="normalizedSearch">Menampilkan {{ visibleItems }} hasil untuk "{{ search.trim() }}"</span>
        </div>

        <div v-if="filteredGroups.length" class="row g-3">
            <div v-for="group in filteredGroups" :key="group.name" class="col-md-6 col-xl-4">
                <div class="card monitoring-card h-100">
                    <div class="card-body">
                        <div class="monitoring-card-head">
                            <div class="monitoring-icon"><i class="mdi" :class="group.icon ?? 'mdi-chart-box-outline'"></i></div>
                            <span class="monitoring-count">{{ group.items.length }} menu</span>
                        </div>
                        <h3>{{ group.name }}</h3>
                        <p>{{ group.description ?? 'Kumpulan laporan monitoring.' }}</p>
                        <ul class="monitoring-links">
                            <li v-for="item in group.items" :key="item.url">
                                <Link :href="item.url">{{ item.name }}</Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="monitoring-empty">
            <i class="mdi mdi-file-search-outline" aria-hidden="true"></i>
            <strong>Tidak ada submenu yang cocok</strong>
            <p>Coba kata kunci lain, misalnya penjualan, pesanan, stok, atau logistik.</p>
            <button type="button" class="btn btn-sm btn-primary" @click="search = ''">Reset pencarian</button>
        </div>
    </div>
</template>

<style scoped>
.monitoring-page { color: var(--app-ink); }
.monitoring-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.5rem;
    margin-bottom: 0.75rem;
    border-radius: 1rem;
    background: var(--app-hero-bg);
    color: var(--app-hero-text);
}
.monitoring-header-copy { max-width: 38rem; }
.monitoring-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.monitoring-header h1 { margin: 0 0 0.35rem; font-size: clamp(1.3rem, 2vw, 1.6rem); color: var(--app-hero-text); }
.monitoring-header p { margin: 0; color: var(--app-hero-muted); }
.monitoring-header-tools { display: flex; flex: 1 1 24rem; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 0.5rem; }
.monitoring-overview-link { display: inline-flex; align-items: center; min-height: 2.75rem; padding: 0.55rem 0.8rem; border-radius: 0.55rem; background: var(--app-surface); color: var(--app-ink); font-size: 0.8rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
.monitoring-overview-link:hover { background: var(--app-accent-soft); color: var(--app-accent-ink); }
.monitoring-search { position: relative; flex: 1 1 20rem; max-width: 30rem; }
.monitoring-search .form-control { min-height: 2.75rem; padding-left: 2.6rem; padding-right: 2.6rem; border-color: rgba(255, 255, 255, 0.65); background: #eef1ff; color: #171b26; }
.monitoring-search .form-control::placeholder { color: #4b5563; }
.monitoring-search .form-control:focus { background: #ffffff; color: #171b26; border-color: var(--app-accent); }
.monitoring-search-icon { position: absolute; top: 50%; left: 0.9rem; transform: translateY(-50%); color: #2f3dd1; font-size: 1.1rem; }
.monitoring-search-clear { position: absolute; top: 50%; right: 0.45rem; display: grid; place-items: center; width: 2rem; height: 2rem; transform: translateY(-50%); border: 0; border-radius: 999px; background: transparent; color: #2f3dd1; }
.monitoring-search-clear:hover { background: rgba(47, 61, 209, 0.1); color: #2632b3; }
.monitoring-meta { display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; margin-bottom: 1rem; color: var(--app-muted); font-size: 0.8rem; }
.monitoring-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.monitoring-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
.monitoring-icon { display: grid; place-items: center; width: 44px; height: 44px; margin-bottom: 0.8rem; border-radius: 0.7rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.4rem; }
.monitoring-count { display: inline-flex; align-items: center; min-height: 1.7rem; padding: 0.15rem 0.55rem; border-radius: 999px; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.7rem; font-weight: 700; }
.monitoring-card h3 { margin: 0 0 0.35rem; font-size: 1rem; }
.monitoring-card p { color: var(--app-muted); font-size: 0.85rem; min-height: 2.4rem; }
.monitoring-links { margin: 0; padding-left: 1rem; }
.monitoring-links li { margin-bottom: 0.3rem; font-size: 0.85rem; }
.monitoring-links a { color: var(--app-accent-ink); text-decoration: none; }
.monitoring-links a:hover { text-decoration: underline; }
.monitoring-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.35rem; min-height: 16rem; padding: 2rem 1rem; border: 1px dashed var(--app-border); border-radius: 1rem; background: var(--app-surface); color: var(--app-muted); text-align: center; }
.monitoring-empty .mdi { font-size: 2rem; color: var(--app-accent-ink); }
.monitoring-empty strong { color: var(--app-ink); }
.monitoring-empty p { margin: 0 0 0.5rem; font-size: 0.85rem; }
.monitoring-page :focus-visible { outline: 2px solid var(--app-accent); outline-offset: 2px; }
@media (max-width: 767.98px) {
    .monitoring-header { align-items: flex-start; padding: 1.1rem; }
    .monitoring-header-tools { width: 100%; justify-content: stretch; }
    .monitoring-overview-link { justify-content: center; width: 100%; }
    .monitoring-search { max-width: none; width: 100%; }
}
</style>
