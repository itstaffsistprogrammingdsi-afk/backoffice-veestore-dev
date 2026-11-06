<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { Head, Link } from '@inertiajs/vue3';

interface Props {
    summary: {
        revenue: number;
        transactions: number;
        average_transaction: number;
        buyers: number;
    };
    orders: {
        ecommerce_total: number;
        ecommerce_open: number;
        store_order_open: number;
        return_open: number;
    };
    inventory: {
        warehouse_units: number;
        store_units: number;
        low_stock_alerts: number;
        expiring_90_units: number;
    };
    urls: {
        overview: string;
        dashboard: string;
        assistant: string;
        monitoring: string;
        crm: string;
        demographics: string;
        preference: string;
        exports: string;
        branch_expansion: string;
        loyalty_accounts: string;
        pending_transactions: string;
        anomalies: string;
        warehouse_audit: string;
        stock_opname: string;
        stock_movements: string;
        promo_acquisition: string;
        user_access: string;
    };
}

const props = defineProps<Props>();

const currency = (value: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(value);

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);

const modules = [
    { title: 'Dashboard Analitik', description: 'Ringkasan performa penjualan dan operasional.', icon: 'mdi-view-dashboard-outline', url: 'dashboard', action: 'Buka Dashboard' },
    { title: 'VEE Assistant', description: 'Tanya data operasional dengan bahasa natural dan jawaban dari data terkini.', icon: 'mdi-chat-processing-outline', url: 'assistant', action: 'Buka VEE' },
    { title: 'Monitoring', description: 'Pusat komando, stok, omzet, dan audit operasional.', icon: 'mdi-monitor-dashboard', url: 'monitoring', action: 'Buka Monitoring' },
    { title: 'CRM', description: 'Profil customer dan aktivitas pelanggan.', icon: 'mdi-account-heart-outline', url: 'crm', action: 'Buka CRM' },
    { title: 'Customer Demographics', description: 'Segmentasi gender, usia, wilayah, tier, dan kanal registrasi.', icon: 'mdi-account-details-outline', url: 'demographics', action: 'Buka Demografi' },
    { title: 'Customer Preference', description: 'Hubungkan usia, gender, wilayah, dan tier dengan produk yang dibeli.', icon: 'mdi-account-star-outline', url: 'preference', action: 'Buka Preferensi' },
    { title: 'Ekspor Saya', description: 'Unduh laporan yang sudah diproses.', icon: 'mdi-file-export-outline', url: 'exports', action: 'Lihat Ekspor' },
    { title: 'Rekomendasi Cabang Baru', description: 'Prioritaskan kecamatan dan kelurahan dari demografi, pelanggan aktif, dan nilai penjualan.', icon: 'mdi-store-plus-outline', url: 'branch_expansion', action: 'Buka Rekomendasi' },
    { title: 'Loyalty Poin & Voucher per Akun', description: 'Saldo poin, poin masuk/keluar, voucher tersedia, dan voucher terpakai per akun.', icon: 'mdi-account-cash-outline', url: 'loyalty_accounts', action: 'Buka Loyalty' },
    { title: 'Transaksi Gantung', description: 'Keranjang, kunjungan, pesanan, permintaan barang, retur, dan pembayaran yang belum selesai.', icon: 'mdi-timer-alert-outline', url: 'pending_transactions', action: 'Buka Transaksi Gantung' },
    { title: 'Anomali Transaksi', description: 'Bandingkan permintaan barang, penjualan, penerimaan, dan pencatatan pembayaran per metode.', icon: 'mdi-alert-decagram-outline', url: 'anomalies', action: 'Buka Anomali' },
    { title: 'Audit Pengguna Gudang', description: 'Aktivitas WMS pada inbound, outbound, QC, dan stok opname per pengguna.', icon: 'mdi-warehouse-cog', url: 'warehouse_audit', action: 'Buka Audit Gudang' },
    { title: 'Stok Opname, Selisih & Penyesuaian', description: 'Nilai selisih, PIC, approval, rekap harian, mingguan, dan bulanan.', icon: 'mdi-clipboard-text-clock-outline', url: 'stock_opname', action: 'Buka Opname' },
    { title: 'Stok Barang Keluar Masuk', description: 'Pergerakan real time, harian, bulanan, serta gudang kecil ke display.', icon: 'mdi-swap-vertical-bold', url: 'stock_movements', action: 'Buka Pergerakan Stok' },
    { title: 'Promo, Poin & Referral', description: 'Jumlah akun yang mengambil voucher, memakai poin, dan berhasil referral.', icon: 'mdi-sale-outline', url: 'promo_acquisition', action: 'Buka Promo' },
    { title: 'Akses Pengguna Harian', description: 'Login dan aktivitas per hari dan per aplikasi, termasuk WMS.', icon: 'mdi-account-clock-outline', url: 'user_access', action: 'Buka Akses' },
];
</script>

<template>
    <Head title="Laporan" />

    <div class="hub-page">
        <section class="hub-hero">
            <div>
                <span class="hub-eyebrow">Area Laporan</span>
                <h2>Pusat laporan yang mudah dibaca</h2>
                <p>
                    Mulai dari ringkasan penjualan, pesanan, dan stok &amp; logistik, lalu telusuri detail ke monitoring.
                </p>
            </div>
            <Link :href="props.urls.overview" class="hub-primary">
                <i class="mdi mdi-view-dashboard-outline me-1"></i>
                Buka Ringkasan Usaha
            </Link>
        </section>

        <div class="row g-3 mb-3">
            <div v-for="stat in [
                { label: 'Omzet 30 hari', value: currency(summary.revenue) },
                { label: 'Transaksi', value: formatNumber(summary.transactions) },
                { label: 'Stok menipis', value: formatNumber(inventory.low_stock_alerts) },
                { label: 'Order terbuka', value: formatNumber(orders.ecommerce_open + orders.store_order_open + orders.return_open) }
            ]" :key="stat.label" class="col-6 col-xl-3">
                <div class="hub-stat">
                    <span>{{ stat.label }}</span>
                    <strong>{{ stat.value }}</strong>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div v-for="domain in [
                { title: 'Penjualan', description: 'Nilai penjualan lunas, transaksi, channel, dan produk terlaris.', anchor: 'penjualan', icon: 'mdi-cash-multiple' },
                { title: 'Pesanan', description: 'Status pesanan, antrean SLA, permintaan store, dan retur.', anchor: 'pesanan', icon: 'mdi-clipboard-check-outline' },
                { title: 'Stok & Logistik', description: 'Stok saat ini, stok menipis, inbound, outbound, dan restock.', anchor: 'stok-logistik', icon: 'mdi-warehouse' }
            ]" :key="domain.title" class="col-md-4">
                <a :href="`${props.urls.overview}#${domain.anchor}`" class="hub-domain">
                    <span class="hub-domain-icon"><i class="mdi" :class="domain.icon"></i></span>
                    <div>
                        <h3>{{ domain.title }}</h3>
                        <p>{{ domain.description }}</p>
                    </div>
                    <i class="mdi mdi-arrow-right hub-domain-arrow"></i>
                </a>
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
.hub-eyebrow { display: inline-block; margin-bottom: 0.6rem; color: var(--app-hero-eyebrow); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.hub-hero h2 { margin: 0 0 0.4rem; font-size: 1.7rem; max-width: 36rem; }
.hub-hero p { margin: 0; color: var(--app-hero-muted); max-width: 40rem; }
.hub-primary { display: inline-flex; align-items: center; padding: 0.7rem 1rem; border-radius: 0.6rem; background: var(--app-accent-strong); color: var(--app-on-accent); font-weight: 700; text-decoration: none; white-space: nowrap; }
.hub-stat { height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.hub-stat span { display: block; color: var(--app-muted); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.hub-stat strong { display: block; margin-top: 0.25rem; font-size: 1.45rem; }
.hub-domain { display: flex; align-items: flex-start; gap: 0.75rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); color: var(--app-ink); text-decoration: none; box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.hub-domain:hover { border-color: var(--app-accent); }
.hub-domain-icon { display: grid; flex: 0 0 auto; width: 2.3rem; height: 2.3rem; place-items: center; border-radius: 0.6rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.2rem; }
.hub-domain h3 { margin: 0 0 0.2rem; font-size: 0.95rem; }
.hub-domain p { margin: 0; color: var(--app-muted); font-size: 0.78rem; line-height: 1.45; }
.hub-domain-arrow { margin-left: auto; color: var(--app-accent-ink); }
.hub-card { height: 100%; padding: 1.35rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.hub-card-icon { display: grid; place-items: center; width: 48px; height: 48px; margin-bottom: 1rem; border-radius: 0.75rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.5rem; }
.hub-card h3 { margin: 0 0 0.4rem; font-size: 1.1rem; }
.hub-card p { margin: 0 0 1rem; color: var(--app-muted); font-size: 0.9rem; line-height: 1.55; }
.hub-card-link { display: inline-flex; align-items: center; gap: 0.35rem; color: var(--app-accent-ink); font-weight: 700; text-decoration: none; }
.hub-card-link:hover { text-decoration: underline; }
@media (max-width: 767.98px) { .hub-hero { align-items: flex-start; flex-direction: column; } }
</style>
