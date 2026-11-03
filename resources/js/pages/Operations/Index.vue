<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { Head, Link } from '@inertiajs/vue3';

interface Props {
    stats: {
        warehouses: number;
        stores: number;
        products: number;
        ecommerce_orders: number;
    };
    urls: {
        warehouses: string;
        stores: string;
        ecommerce: string;
        stock: string;
        inbound: string;
        outbound: string;
        customer_demographics: string;
        store_orders: string;
        store_returns: string;
    };
    can_review_store_orders: boolean;
    can_review_store_returns: boolean;
}

const props = defineProps<Props>();

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);

const modules = [
    {
        title: 'Warehouse',
        description: 'Warehouse master, layout, stock, inbound, outbound, dan QC operasional.',
        icon: 'mdi-warehouse',
        url: 'warehouses',
        action: 'Kelola Gudang',
    },
    {
        title: 'Store',
        description: 'Toko, stok, inbound/outbound, approval order, customer, dan kunjungan.',
        icon: 'mdi-store',
        url: 'stores',
        action: 'Kelola Toko',
    },
    {
        title: 'Ecommerce',
        description: 'Order ecommerce, pemenuhan stok, banner, promo, dan voucher.',
        icon: 'mdi-cart-outline',
        url: 'ecommerce',
        action: 'Kelola Ecommerce',
    },
];
</script>

<template>
    <Head title="Operasional" />

    <div class="hub-page">
        <section class="hub-hero">
            <div>
                <span class="hub-eyebrow">Area Operasional</span>
                <h2>Operasional harian dalam satu tempat</h2>
                <p>
                    Kelola warehouse, store, dan ecommerce tanpa bercampur dengan laporan analitik.
                </p>
            </div>
            <div class="hub-hero-actions">
                <Link :href="props.urls.stock" class="hub-button hub-button-accent">
                    <i class="mdi mdi-clipboard-list-outline me-1"></i>
                    Stock Operations
                </Link>
                <Link :href="props.urls.inbound" class="hub-button hub-button-light">
                    <i class="mdi mdi-tray-arrow-down me-1"></i>
                    Inbound
                </Link>
                <Link :href="props.urls.outbound" class="hub-button hub-button-light">
                    <i class="mdi mdi-tray-arrow-up me-1"></i>
                    Outbound
                </Link>
                <Link :href="props.urls.customer_demographics" class="hub-button hub-button-light">
                    <i class="mdi mdi-account-details-outline me-1"></i>
                    Customer Demographics
                </Link>
                <Link v-if="props.can_review_store_orders" :href="props.urls.store_orders" class="hub-button hub-button-light">
                    <i class="mdi mdi-clipboard-check-outline me-1"></i>
                    Approval Order Store
                </Link>
                <Link v-if="props.can_review_store_returns" :href="props.urls.store_returns" class="hub-button hub-button-light">
                    <i class="mdi mdi-backup-restore me-1"></i>
                    Approval Retur Store
                </Link>
                <Link :href="props.urls.ecommerce" class="hub-button hub-button-light">
                    <i class="mdi mdi-cart-arrow-right me-1"></i>
                    Order Ecommerce
                </Link>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div v-for="stat in [
                { label: 'Warehouse', value: stats.warehouses, icon: 'mdi-warehouse' },
                { label: 'Store', value: stats.stores, icon: 'mdi-store' },
                { label: 'Produk', value: stats.products, icon: 'mdi-package-variant' },
                { label: 'Order Ecommerce Aktif', value: stats.ecommerce_orders, icon: 'mdi-cart-outline' }
            ]" :key="stat.label" class="col-6 col-xl-3">
                <div class="hub-stat">
                    <i class="mdi" :class="stat.icon"></i>
                    <div>
                        <span>{{ stat.label }}</span>
                        <strong>{{ formatNumber(stat.value) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div v-for="module in modules" :key="module.title" class="col-lg-4">
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
.hub-hero-actions { display: flex; flex-wrap: wrap; gap: 0.6rem; }
.hub-button { display: inline-flex; align-items: center; justify-content: center; padding: 0.65rem 0.9rem; border-radius: 0.6rem; font-weight: 700; text-decoration: none; }
.hub-button-light { background: var(--app-surface); color: var(--app-ink); }
.hub-button-accent { background: var(--app-accent-strong); color: var(--app-on-accent); }
.hub-stat { display: flex; align-items: center; gap: 0.9rem; height: 100%; padding: 1rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.hub-stat i { font-size: 1.6rem; color: var(--app-accent-ink); }
.hub-stat span { display: block; color: var(--app-muted); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.hub-stat strong { display: block; margin-top: 0.15rem; font-size: 1.35rem; }
.hub-card { height: 100%; padding: 1.35rem; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 0.35rem 1rem rgba(23, 17, 31, 0.05); }
.hub-card-icon { display: grid; place-items: center; width: 48px; height: 48px; margin-bottom: 1rem; border-radius: 0.75rem; background: var(--app-accent-soft); color: var(--app-accent-ink); font-size: 1.5rem; }
.hub-card h3 { margin: 0 0 0.4rem; font-size: 1.1rem; }
.hub-card p { margin: 0 0 1rem; color: var(--app-muted); font-size: 0.9rem; line-height: 1.55; }
.hub-card-link { display: inline-flex; align-items: center; gap: 0.35rem; color: var(--app-accent-ink); font-weight: 700; text-decoration: none; }
.hub-card-link:hover { text-decoration: underline; }
@media (max-width: 767.98px) { .hub-hero { align-items: flex-start; flex-direction: column; } }
</style>
