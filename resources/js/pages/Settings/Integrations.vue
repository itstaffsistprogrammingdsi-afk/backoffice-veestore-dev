<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatNumber } from '@/composables/useFormatters';

defineOptions({ layout: AppLayout });

interface ProviderActivity {
    label: string;
    last_activity_at: string | null;
    total_last_30_days: number;
    success_last_30_days: number;
    failed_last_30_days: number;
    pending_last_30_days?: number;
}

interface Provider {
    key: string;
    label: string;
    enabled: boolean;
    configured: boolean;
    status: 'ready' | 'missing_credentials' | 'disabled';
    status_label: string;
    environment: string;
    base_url: string;
    webhook_url: string | null;
    docs_url: string;
    checks: Record<string, boolean>;
    missing: string[];
    activity: ProviderActivity;
}

interface Props {
    providers: Record<string, Provider>;
    summary: {
        ready: number;
        missing_credentials: number;
        disabled: number;
        last_checked_at: string;
    };
    urls: {
        index: string;
        verify: string;
        payment_methods: string;
        couriers: string;
        ecommerce_payments: string;
        shipments: string;
    };
}

const props = defineProps<Props>();

const providers = ref<Record<string, Provider>>({ ...props.providers });
const lastCheckedAt = ref(props.summary.last_checked_at);
const verifying = ref(false);
const verifyMessage = ref('');
const verifyTone = ref<'success' | 'warning'>('success');

const summary = computed(() => ({
    ready: Object.values(providers.value).filter((provider) => provider.status === 'ready').length,
    missing_credentials: Object.values(providers.value).filter((provider) => provider.status === 'missing_credentials').length,
    disabled: Object.values(providers.value).filter((provider) => provider.status === 'disabled').length,
}));

const providerList = computed(() => Object.values(providers.value));

const statusClass = (status: Provider['status']) => ({
    ready: 'bg-label-success',
    missing_credentials: 'bg-label-warning',
    disabled: 'bg-label-secondary',
}[status] ?? 'bg-label-secondary');

const envLabel = (key: string) => key.toUpperCase();

const verify = async () => {
    verifying.value = true;
    verifyMessage.value = '';

    try {
        const { data } = await axios.post(props.urls.verify);
        providers.value = data.providers;
        lastCheckedAt.value = data.checked_at;
        verifyMessage.value = data.ok
            ? 'Semua integrasi siap digunakan.'
            : 'Konfigurasi diperiksa. Lengkapi kredensial yang masih kosong.';
        verifyTone.value = data.ok ? 'success' : 'warning';
    } catch {
        verifyMessage.value = 'Gagal memeriksa konfigurasi integrasi.';
        verifyTone.value = 'warning';
    } finally {
        verifying.value = false;
    }
};
</script>

<template>
    <Head title="Integrasi Doku & Biteship" />

    <div class="integration-page">
        <section class="integration-header">
            <div>
                <span class="integration-eyebrow">Pengaturan / Integrasi</span>
                <h1>Integrasi Doku & Biteship</h1>
                <p>Periksa kesiapan kredensial, endpoint webhook, dan aktivitas terakhir integrasi pembayaran serta pengiriman.</p>
            </div>
            <div class="integration-header-actions">
                <button class="integration-button integration-button-primary" type="button" :disabled="verifying" @click="verify">
                    <span v-if="verifying" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="mdi mdi-shield-check-outline me-1"></i>
                    Periksa Konfigurasi
                </button>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="integration-summary integration-summary-success">
                    <span class="integration-summary-icon"><i class="mdi mdi-check-decagram-outline"></i></span>
                    <div><span>Siap</span><strong>{{ formatNumber(summary.ready) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="integration-summary integration-summary-warning">
                    <span class="integration-summary-icon"><i class="mdi mdi-key-alert-outline"></i></span>
                    <div><span>Kredensial Kurang</span><strong>{{ formatNumber(summary.missing_credentials) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="integration-summary">
                    <span class="integration-summary-icon"><i class="mdi mdi-power-plug-off-outline"></i></span>
                    <div><span>Belum Aktif</span><strong>{{ formatNumber(summary.disabled) }}</strong></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="integration-summary">
                    <span class="integration-summary-icon"><i class="mdi mdi-clock-check-outline"></i></span>
                    <div><span>Terakhir Diperiksa</span><strong class="integration-summary-date">{{ formatDate(lastCheckedAt, true) }}</strong></div>
                </div>
            </div>
        </div>

        <div v-if="verifyMessage" class="integration-alert mb-3" role="status" :class="verifyTone === 'success' ? 'integration-alert-success' : 'integration-alert-warning'">
            <i class="mdi" :class="verifyTone === 'success' ? 'mdi-check-circle-outline' : 'mdi-alert-outline'"></i>
            <span>{{ verifyMessage }}</span>
        </div>

        <div class="row g-3">
            <div v-for="provider in providerList" :key="provider.key" class="col-12 col-xl-6">
                <article class="integration-card">
                    <div class="integration-card-header">
                        <div class="integration-card-title">
                            <span class="integration-card-icon"><i class="mdi" :class="provider.key === 'doku' ? 'mdi-credit-card-check-outline' : 'mdi-truck-fast-outline'"></i></span>
                            <div>
                                <h2>{{ provider.label }}</h2>
                                <p>{{ provider.key === 'doku' ? 'Payment gateway dan kanal pembayaran online.' : 'Agregator pengiriman dan pelacakan resi.' }}</p>
                            </div>
                        </div>
                        <span class="badge" :class="statusClass(provider.status)">{{ provider.status_label }}</span>
                    </div>

                    <div class="integration-card-body">
                        <dl class="integration-meta">
                            <div><dt>Environment</dt><dd>{{ provider.environment }}</dd></div>
                            <div><dt>Base URL</dt><dd class="integration-mono">{{ provider.base_url }}</dd></div>
                            <div class="integration-meta-wide"><dt>Webhook URL</dt><dd class="integration-mono">{{ provider.webhook_url || 'Belum dikonfigurasi' }}</dd></div>
                        </dl>

                        <div class="integration-checks">
                            <h3>Kredensial Wajib</h3>
                            <ul>
                                <li v-for="(configured, key) in provider.checks" :key="key">
                                    <i class="mdi" :class="configured ? 'mdi-check-circle text-success' : 'mdi-close-circle text-danger'"></i>
                                    <span>{{ envLabel(String(key)) }}</span>
                                    <small>{{ configured ? 'Terisi' : 'Belum diisi' }}</small>
                                </li>
                            </ul>
                        </div>

                        <div class="integration-activity">
                            <h3>{{ provider.activity.label }} · 30 Hari Terakhir</h3>
                            <div class="integration-activity-grid">
                                <div><span>Total</span><strong>{{ formatNumber(provider.activity.total_last_30_days) }}</strong></div>
                                <div><span>Sukses</span><strong>{{ formatNumber(provider.activity.success_last_30_days) }}</strong></div>
                                <div><span>Gagal</span><strong>{{ formatNumber(provider.activity.failed_last_30_days) }}</strong></div>
                                <div><span>Aktivitas Terakhir</span><strong>{{ formatDate(provider.activity.last_activity_at, true) }}</strong></div>
                            </div>
                        </div>

                        <div class="integration-card-actions">
                            <a v-if="provider.docs_url" :href="provider.docs_url" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                                <i class="mdi mdi-book-open-variant me-1"></i>Dokumentasi
                            </a>
                            <Link v-if="provider.key === 'doku'" :href="props.urls.payment_methods" class="btn btn-sm btn-outline-primary">
                                <i class="mdi mdi-credit-card-outline me-1"></i>Metode Pembayaran
                            </Link>
                            <Link v-if="provider.key === 'biteship'" :href="props.urls.couriers" class="btn btn-sm btn-outline-primary">
                                <i class="mdi mdi-truck-outline me-1"></i>Kurir
                            </Link>
                        </div>
                    </div>
                </article>
            </div>
        </div>

        <div class="card integration-guide mt-3">
            <div class="integration-guide-header">
                <div><h2>Panduan Konfigurasi</h2><p>Isi variabel berikut pada environment aplikasi, lalu jalankan ulang pemeriksaan.</p></div>
            </div>
            <div class="integration-guide-body">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <h3>Doku</h3>
                        <ul class="integration-env-list">
                            <li><code>DOKU_ENABLED</code><span>true untuk mengaktifkan integrasi.</span></li>
                            <li><code>DOKU_ENVIRONMENT</code><span>sandbox atau production.</span></li>
                            <li><code>DOKU_CLIENT_ID</code><span>Client ID dari dashboard Doku.</span></li>
                            <li><code>DOKU_SECRET_KEY</code><span>Secret key dari dashboard Doku.</span></li>
                            <li><code>DOKU_BASE_URL</code><span>URL API Doku sesuai environment.</span></li>
                            <li><code>DOKU_WEBHOOK_URL</code><span>URL notifikasi pembayaran.</span></li>
                        </ul>
                    </div>
                    <div class="col-12 col-lg-6">
                        <h3>Biteship</h3>
                        <ul class="integration-env-list">
                            <li><code>BITESHIP_ENABLED</code><span>true untuk mengaktifkan integrasi.</span></li>
                            <li><code>BITESHIP_ENVIRONMENT</code><span>sandbox atau production.</span></li>
                            <li><code>BITESHIP_API_KEY</code><span>API key dari dashboard Biteship.</span></li>
                            <li><code>BITESHIP_BASE_URL</code><span>URL API Biteship.</span></li>
                            <li><code>BITESHIP_WEBHOOK_URL</code><span>URL callback status pengiriman.</span></li>
                        </ul>
                    </div>
                </div>
                <div class="integration-guide-note">
                    <i class="mdi mdi-information-outline"></i>
                    <span>Backoffice memantau aktivitas integrasi, tetapi endpoint webhook tetap dimiliki aplikasi POS/e-commerce. Rahasia kredensial tidak pernah ditampilkan di halaman ini.</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.integration-page { color: var(--app-ink); }
.integration-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; }
.integration-eyebrow { display: inline-block; margin-bottom: 0.5rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.integration-header h1 { margin: 0 0 0.35rem; color: var(--app-hero-text) !important; font-size: 1.5rem; }
.integration-header p { max-width: 760px; margin: 0; color: var(--app-hero-muted) !important; }
.integration-header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.integration-button { display: inline-flex; align-items: center; padding: 0.55rem 0.8rem; border: 0; border-radius: 0.55rem; font-size: 0.8rem; font-weight: 700; }
.integration-button-primary { background: var(--app-accent-strong); color: var(--app-on-accent); }
.integration-summary { display: flex; align-items: center; gap: 0.85rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.integration-summary-icon { display: grid; flex: 0 0 auto; width: 2.4rem; height: 2.4rem; place-items: center; border-radius: 0.65rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.25rem; }
.integration-summary span:not(.integration-summary-icon) { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.integration-summary strong { display: block; margin-top: 0.15rem; font-size: 1.35rem; }
.integration-summary-date { font-size: 0.82rem !important; }
.integration-summary-success .integration-summary-icon { background: var(--app-success-bg, var(--app-accent-soft)); color: var(--app-success-ink, var(--app-accent-ink)); }
.integration-summary-warning .integration-summary-icon { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.integration-alert { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 0.9rem; border-radius: 0.65rem; font-size: 0.82rem; }
.integration-alert i { font-size: 1.15rem; }
.integration-alert-success { background: var(--app-success-bg, var(--app-accent-soft)); color: var(--app-success-ink, var(--app-accent-ink)); }
.integration-alert-warning { background: var(--app-warning-bg); color: var(--app-warning-ink); }
.integration-card { height: 100%; overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.integration-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.integration-card-title { display: flex; gap: 0.75rem; }
.integration-card-icon { display: grid; flex: 0 0 auto; width: 2.6rem; height: 2.6rem; place-items: center; border-radius: 0.7rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.35rem; }
.integration-card-title h2 { margin: 0 0 0.15rem; font-size: 1rem; }
.integration-card-title p { margin: 0; color: var(--app-muted); font-size: 0.78rem; }
.integration-card-body { padding: 1rem 1.1rem; }
.integration-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.7rem 1rem; margin: 0 0 1rem; }
.integration-meta-wide { grid-column: 1 / -1; }
.integration-meta dt { color: var(--app-muted); font-size: 0.66rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.integration-meta dd { margin: 0.15rem 0 0; font-size: 0.8rem; }
.integration-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.72rem !important; word-break: break-all; }
.integration-checks h3, .integration-activity h3 { margin: 0 0 0.45rem; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--app-muted); }
.integration-checks ul { display: flex; flex-wrap: wrap; gap: 0.4rem 0.75rem; margin: 0 0 1rem; padding: 0; list-style: none; }
.integration-checks li { display: flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.55rem; border: 1px solid var(--app-border); border-radius: 999px; font-size: 0.74rem; }
.integration-checks li small { color: var(--app-muted); }
.integration-activity { padding: 0.75rem 0.85rem; border-radius: 0.65rem; background: var(--app-surface-2); }
.integration-activity-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.5rem; }
.integration-activity-grid span { display: block; color: var(--app-muted); font-size: 0.64rem; font-weight: 700; text-transform: uppercase; }
.integration-activity-grid strong { display: block; margin-top: 0.1rem; font-size: 0.82rem; }
.integration-card-actions { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 1rem; }
.integration-guide { border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
.integration-guide-header { padding: 1rem 1.1rem; border-bottom: 1px solid var(--app-border); }
.integration-guide-header h2 { margin: 0 0 0.2rem; font-size: 1rem; }
.integration-guide-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.integration-guide-body { padding: 1rem 1.1rem; }
.integration-guide-body h3 { margin: 0 0 0.5rem; font-size: 0.85rem; }
.integration-env-list { margin: 0; padding: 0; list-style: none; }
.integration-env-list li { display: flex; flex-wrap: wrap; align-items: baseline; gap: 0.4rem; padding: 0.35rem 0; border-bottom: 1px solid var(--app-border); font-size: 0.78rem; }
.integration-env-list li:last-child { border-bottom: 0; }
.integration-env-list code { color: var(--app-accent-ink); font-size: 0.74rem; }
.integration-env-list span { color: var(--app-muted); }
.integration-guide-note { display: flex; align-items: flex-start; gap: 0.5rem; margin-top: 1rem; padding: 0.7rem 0.85rem; border-radius: 0.6rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 0.78rem; }
@media (max-width: 991.98px) {
    .integration-header { align-items: flex-start; flex-direction: column; }
    .integration-activity-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
