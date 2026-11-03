<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ModuleNav from '@/components/ModuleNav.vue';

defineOptions({ layout: AppLayout });

interface VoucherCode {
    id: string;
    code: string;
    expires_at: string | null;
    is_active: boolean | number;
    usages_count: number;
}

interface Props {
    voucher: Record<string, any>;
    codes: {
        data: VoucherCode[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    usedCodesCount: number;
    urls: { index: string; status: string };
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });
const processingId = ref<string | null>(null);

const formatDate = (value: string | null) => {
    if (!value) return '-';
    return new Date(value).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const toggleStatus = (code: VoucherCode) => {
    processingId.value = code.id;
    const next = !isActive(code);
    code.is_active = next;

    const url = props.urls.status.replace('__ID__', String(props.voucher.id)).replace('__CODE_ID__', String(code.id));
    router.patch(url, { is_active: next }, {
        preserveScroll: true,
        onError: () => { code.is_active = !next; },
        onFinish: () => { processingId.value = null; },
    });
};

const isActive = (code: VoucherCode) => Boolean(code.is_active);
</script>

<template>
    <Head :title="`Kode Voucher - ${voucher.name}`" />
    <div class="voucher-page">
        <div class="voucher-header">
            <div>
                <span class="voucher-eyebrow">Promo / Voucher</span>
                <h1>Kode Voucher</h1>
                <p>{{ voucher.name }} <span class="badge bg-label-primary ms-1">{{ voucher.code }}</span></p>
            </div>
            <Link :href="urls.index" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-arrow-left me-1"></i>Kembali</Link>
        </div>

        <ModuleNav module-key="promoModule" aria-label="Modul Promo" />

        <div v-if="flash.success" class="alert alert-success"><i class="mdi mdi-check-circle-outline me-1"></i>{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger"><i class="mdi mdi-alert-circle-outline me-1"></i>{{ flash.error }}</div>

        <div class="card voucher-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Daftar Unique Code</span>
                <div class="d-flex gap-2">
                    <span class="badge bg-label-success">{{ new Intl.NumberFormat('id-ID').format(usedCodesCount) }} digunakan</span>
                    <span class="badge bg-label-secondary">{{ new Intl.NumberFormat('id-ID').format(codes.total) }} kode</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>#</th><th>Kode</th><th>Berakhir</th><th>Penggunaan</th><th>Status Kode</th><th class="text-center">Aksi</th></tr></thead>
                    <tbody>
                        <tr v-for="(code, index) in codes.data" :key="code.id">
                            <td>{{ (codes.from ?? 1) + index }}</td>
                            <td><span class="badge bg-label-primary fs-6">{{ code.code }}</span></td>
                            <td>{{ formatDate(code.expires_at) }}</td>
                            <td>
                                <template v-if="code.usages_count > 0">
                                    <span class="badge bg-label-success"><i class="mdi mdi-check-circle-outline me-1"></i>Digunakan</span>
                                    <small class="text-muted d-block mt-1">{{ new Intl.NumberFormat('id-ID').format(code.usages_count) }} kali</small>
                                </template>
                                <span v-else class="badge bg-label-secondary">Belum digunakan</span>
                            </td>
                            <td><span class="badge" :class="isActive(code) ? 'bg-label-success' : 'bg-label-danger'">{{ isActive(code) ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm" :class="isActive(code) ? 'btn-outline-danger' : 'btn-outline-success'" type="button" :disabled="processingId === code.id" @click="toggleStatus(code)">
                                    <i class="mdi me-1" :class="isActive(code) ? 'mdi-close-circle-outline' : 'mdi-check-circle-outline'"></i>
                                    {{ isActive(code) ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="codes.data.length === 0"><td colspan="6" class="text-center text-muted py-5">Belum ada kode voucher.</td></tr>
                    </tbody>
                </table>
            </div>
            <div v-if="codes.links.length > 3" class="card-footer d-flex justify-content-end"><PaginationLinks :links="codes.links" /></div>
        </div>
    </div>
</template>

<style scoped>
.voucher-page { color: var(--app-ink); }
.voucher-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.5rem; margin-bottom: 1rem; border-radius: 1rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.voucher-eyebrow { display: inline-block; margin-bottom: .5rem; color: var(--app-hero-eyebrow); font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
.voucher-header h1 { margin: 0 0 .35rem; font-size: 1.5rem; }
.voucher-header p { margin: 0; color: var(--app-hero-muted); }
.voucher-card { border: 1px solid var(--app-border); border-radius: .9rem; background: var(--app-surface); box-shadow: var(--app-shadow); }
</style>
