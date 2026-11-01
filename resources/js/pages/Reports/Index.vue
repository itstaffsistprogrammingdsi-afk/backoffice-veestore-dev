<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { Head, Link } from '@inertiajs/vue3';

interface ModelRun {
    model_name: string;
    completed_at: string | null;
}

interface Props {
    latestRun: ModelRun | null;
    summary: {
        total: number;
        critical: number;
        high: number;
        recommended_units: number;
    };
    urls: {
        dashboard: string;
        ai: string;
        assistant: string;
        monitoring: string;
        crm: string;
        exports: string;
    };
}

const props = defineProps<Props>();

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);

const modules = [
    { title: 'Dashboard Analitik', description: 'Ringkasan performa penjualan dan operasional.', icon: 'mdi-view-dashboard-outline', url: 'dashboard', action: 'Buka Dashboard' },
    { title: 'AI Analytics', description: 'Forecast penjualan dan rekomendasi restock.', icon: 'mdi-robot-outline', url: 'ai', action: 'Buka AI Analytics' },
    { title: 'AI Assistant', description: 'Tanya data operasional dengan bahasa natural.', icon: 'mdi-chat-processing-outline', url: 'assistant', action: 'Buka Assistant' },
    { title: 'Monitoring', description: 'Command center, stok, omset, dan audit operasional.', icon: 'mdi-monitor-dashboard', url: 'monitoring', action: 'Buka Monitoring' },
    { title: 'CRM', description: 'Profil customer, demografi, dan aktivitas pelanggan.', icon: 'mdi-account-heart-outline', url: 'crm', action: 'Buka CRM' },
    { title: 'Export Saya', description: 'Unduh laporan yang sudah diproses.', icon: 'mdi-file-export-outline', url: 'exports', action: 'Lihat Export' },
];
</script>

<template>
    <Head title="Laporan" />

    <div class="hub-page">
        <section class="hub-hero">
            <div>
                <span class="hub-eyebrow">Area Laporan</span>
                <h2>Analitik dan laporan terpisah dari operasional</h2>
                <p>
                    Fokus pada insight, monitoring, dan export tanpa mengganggu alur kerja harian.
                </p>
            </div>
            <Link :href="props.urls.dashboard" class="hub-primary">
                <i class="mdi mdi-chart-line me-1"></i>
                Buka Dashboard
            </Link>
        </section>

        <div class="row g-3 mb-3">
            <div v-for="stat in [
                { label: 'Rekomendasi AI', value: summary.total },
                { label: 'Risiko Kritis', value: summary.critical },
                { label: 'Risiko Tinggi', value: summary.high },
                { label: 'Saran Restock', value: summary.recommended_units }
            ]" :key="stat.label" class="col-6 col-xl-3">
                <div class="hub-stat">
                    <span>{{ stat.label }}</span>
                    <strong>{{ formatNumber(stat.value) }}</strong>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div v-for="module in modules" :key="module.title" class="col-md-6 col-xl-4">
                <article class="hub-card">
                    <div class="hub-card-icon"><i class="mdi" :class="module.icon"></i></div>
                    <h3>{{ module.title }}</h3>
                    <p>{{ module.description }}</p>
                    <Link :href="props.urls[module.url as keyof Props['urls']]" class="hub-card-link">
                        {{ module.action }}
                        <i class="mdi mdi-arrow-right"></i>
                    </Link>
                </article>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hub-page { color: var(--app-ink); }
.hub-hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 2rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-ink); color: #fff; }
.hub-eyebrow { display: inline-block; margin-bottom: 0.6rem; color: #f9a8d4; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.hub-hero h2 { margin: 0 0 0.4rem; font-size: 1.7rem; max-width: 36rem; }
.hub-hero p { margin: 0; color: var(--app-hero-muted); max-width: 40rem; }
.hub-primary { display: inline-flex; align-items: center; padding: 0.7rem 1rem; border-radius: 0.6rem; background: var(--app-accent-strong); color: var(--app-on-accent); font-weight: 700; text-decoration: none; white-space: nowrap; }
.hub-stat { height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.hub-stat span { display: block; color: var(--app-muted); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.hub-stat strong { display: block; margin-top: 0.25rem; font-size: 1.45rem; }
.hub-card { height: 100%; padding: 1.35rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.hub-card-icon { display: grid; place-items: center; width: 48px; height: 48px; margin-bottom: 1rem; border-radius: 0.75rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.5rem; }
.hub-card h3 { margin: 0 0 0.4rem; font-size: 1.1rem; }
.hub-card p { margin: 0 0 1rem; color: var(--app-muted); font-size: 0.9rem; line-height: 1.55; }
.hub-card-link { display: inline-flex; align-items: center; gap: 0.35rem; color: var(--app-accent-ink); font-weight: 700; text-decoration: none; }
.hub-card-link:hover { text-decoration: underline; }
@media (max-width: 767.98px) { .hub-hero { align-items: flex-start; flex-direction: column; } }
</style>
