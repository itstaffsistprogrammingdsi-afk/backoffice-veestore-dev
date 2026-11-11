<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { Head, Link } from '@inertiajs/vue3';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';

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
    };
}

const props = defineProps<Props>();

const formatNumber = (value: number) => new Intl.NumberFormat('id-ID').format(value);

const modules = [
    {
        title: 'Warehouse',
        description: 'Kelola data gudang, tata letak, stok, penerimaan, dan pengiriman barang.',
        icon: 'mdi-warehouse',
        url: 'warehouses',
        action: 'Kelola Gudang',
    },
    {
        title: 'Store',
        description: 'Kelola data toko, stok, pesanan cabang, pelanggan, dan kunjungan.',
        icon: 'mdi-store',
        url: 'stores',
        action: 'Kelola Toko',
    },
    {
        title: 'Ecommerce',
        description: 'Pantau pesanan, pembayaran, stok, banner, promo, dan voucher.',
        icon: 'mdi-cart-outline',
        url: 'ecommerce',
        action: 'Kelola Ecommerce',
    },
];
</script>

<template>
    <Head title="Operasional" />

    <div class="hub-page">
        <AppPageHeader
            eyebrow="Area Operasional"
            title="Kelola operasional toko dan gudang"
            description="Periksa stok, penerimaan barang, pengiriman, dan pesanan cabang dari halaman kerja terkait."
        >
            <template #actions>
                <Link :href="props.urls.stock" class="btn btn-primary">
                    <i class="mdi mdi-clipboard-list-outline me-1" aria-hidden="true"></i>
                    Buka stok gudang
                </Link>
                <Link :href="props.urls.inbound" class="btn btn-outline-secondary">
                    <i class="mdi mdi-tray-arrow-down me-1" aria-hidden="true"></i>
                    Penerimaan barang
                </Link>
                <Link :href="props.urls.outbound" class="btn btn-outline-secondary">
                    <i class="mdi mdi-tray-arrow-up me-1" aria-hidden="true"></i>
                    Pengiriman barang
                </Link>
            </template>
        </AppPageHeader>

        <h2 class="section-title">Ringkasan</h2>
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

        <h2 class="section-title">Modul operasional</h2>
        <div class="row g-3">
            <div v-for="module in modules" :key="module.title" class="col-lg-4">
                <Link :href="props.urls[module.url as keyof Props['urls']]" class="hub-card">
                    <div class="hub-card-icon"><i class="mdi" :class="module.icon" aria-hidden="true"></i></div>
                    <h3 class="hub-card-title">{{ module.title }}</h3>
                    <p>{{ module.description }}</p>
                    <span class="hub-card-link">
                        {{ module.action }}
                        <i class="mdi mdi-arrow-right" aria-hidden="true"></i>
                    </span>
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hub-page {
    color: var(--app-ink);
}

.hub-stat {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    height: 100%;
    padding: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
}

.hub-stat i {
    color: var(--app-accent-ink);
    font-size: 1.6rem;
}

.hub-stat span {
    display: block;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
    letter-spacing: var(--app-tracking-wide);
    text-transform: uppercase;
}

.hub-stat strong {
    display: block;
    margin-top: 0.15rem;
    font-size: var(--app-text-2xl);
    font-weight: var(--app-weight-black);
    font-variant-numeric: tabular-nums;
}

.hub-card {
    display: block;
    height: 100%;
    padding: 1.35rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
    color: var(--app-ink);
    text-decoration: none;
    transition: border-color var(--app-dur-fast) var(--app-ease-out), background-color var(--app-dur-fast) var(--app-ease-out);
}

.hub-card:hover {
    border-color: color-mix(in srgb, var(--app-accent) 40%, var(--app-border));
    background: var(--app-surface-2);
}

.hub-card-icon {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    margin-bottom: 1rem;
    border-radius: var(--app-radius-lg);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: 1.5rem;
}

.hub-card-title {
    margin: 0 0 0.4rem;
    font-size: var(--app-text-xl);
    font-weight: var(--app-weight-bold);
}

.hub-card p {
    margin: 0 0 1rem;
    color: var(--app-muted);
    font-size: var(--app-text-base);
    line-height: var(--app-leading-normal);
}

.hub-card-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 32px;
    color: var(--app-accent-ink);
    font-weight: var(--app-weight-bold);
    text-decoration: none;
}

.hub-card-link:hover,
.hub-card:hover .hub-card-link {
    text-decoration: underline;
}

@media (pointer: coarse) {
    .hub-card-link {
        min-height: 44px;
    }
}
</style>
