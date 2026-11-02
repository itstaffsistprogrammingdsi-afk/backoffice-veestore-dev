<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

interface MonitoringItem { name: string; url: string; icon?: string | null }
interface MonitoringGroup { name: string; description?: string; icon?: string; items: MonitoringItem[] }

const props = defineProps<{ monitoringGroups: MonitoringGroup[] }>();
</script>

<template>
    <Head title="Monitoring" />
    <div class="monitoring-page">
        <div class="monitoring-header">
            <div>
                <span class="monitoring-eyebrow">Laporan</span>
                <h1>Monitoring Command Center</h1>
                <p>Akses seluruh laporan operasional dari satu tempat.</p>
            </div>
        </div>

        <div class="row g-3">
            <div v-for="group in props.monitoringGroups" :key="group.name" class="col-md-6 col-xl-4">
                <div class="card monitoring-card h-100">
                    <div class="card-body">
                        <div class="monitoring-icon"><i class="mdi" :class="group.icon ?? 'mdi-chart-box-outline'"></i></div>
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
    </div>
</template>

<style scoped>
.monitoring-page { color: var(--app-ink); }
.monitoring-header { padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.monitoring-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.monitoring-header h1 { margin: 0 0 0.35rem; font-size: 1.5rem; color: var(--app-hero-text); }
.monitoring-header p { margin: 0; color: var(--app-hero-muted); }
.monitoring-card { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.monitoring-icon { display: grid; place-items: center; width: 44px; height: 44px; margin-bottom: 0.8rem; border-radius: 0.7rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.4rem; }
.monitoring-card h3 { margin: 0 0 0.35rem; font-size: 1rem; }
.monitoring-card p { color: var(--app-muted); font-size: 0.85rem; min-height: 2.4rem; }
.monitoring-links { margin: 0; padding-left: 1rem; }
.monitoring-links li { margin-bottom: 0.3rem; font-size: 0.85rem; }
.monitoring-links a { color: var(--app-accent-ink); text-decoration: none; }
.monitoring-links a:hover { text-decoration: underline; }
</style>
