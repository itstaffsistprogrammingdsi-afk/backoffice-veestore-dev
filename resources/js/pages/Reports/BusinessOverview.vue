<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SearchableSelect from '@/components/SearchableSelect.vue';
import CustomerGrowthChart from '@/components/CustomerGrowthChart.vue';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
import { formatCurrency, formatDate, formatNumber } from '@/composables/useFormatters';
import { downloadExcelTable } from '@/composables/useExcelExport';

interface Filters {
    period: string;
    date_from: string;
    date_to: string;
    channel: string;
    store_id: string | null;
    warehouse_id: string | null;
}

interface SalesSummary {
    gross_revenue: number;
    net_revenue: number;
    revenue: number;
    transactions: number;
    average_transaction: number;
    average_net_transaction: number;
    buyers: number;
}

interface ChannelRow {
    key: string;
    label: string;
    gross_revenue: number;
    net_revenue: number;
    revenue: number;
    transactions: number;
    share: number;
    gross_share: number;
    net_share: number;
}

interface TrendRow {
    date: string;
    label: string;
    revenue: number;
    gross_revenue: number;
    net_revenue: number;
    transactions: number;
}

interface StoreRow {
    store_id: string | null;
    store_name: string;
    gross_revenue: number;
    net_revenue: number;
    revenue: number;
    transactions: number;
    buyers: number;
}


interface ProductRow {
    product_id: string;
    product_code: string;
    product_name: string;
    category_name: string | null;
    qty: number;
    transactions: number;
    stores: Array<{
        store_id: string | null;
        store_name: string;
        qty: number;
        transactions: number;
    }>;
}

interface StatusRow {
    status: string;
    label: string;
    count: number;
    share: number;
}

interface OrdersSummary {
    ecommerce_total: number;
    ecommerce_open: number;
    ecommerce_completed: number;
    ecommerce_cancelled: number;
    ecommerce_pending_payment: number;
    ecommerce_need_attention: number;
    store_order_total: number;
    store_order_open: number;
    return_total: number;
    return_open: number;
}

interface AttentionRow {
    id: string;
    type: string;
    reference: string;
    status: string;
    status_label: string;
    age_hours: number;
    amount: number | null;
    partner: string;
    action_url: string | null;
    action_label: string;
}

interface InventorySummary {
    warehouse_units: number;
    warehouse_skus: number;
    warehouse_locations: number;
    store_units: number;
    store_skus: number;
    store_locations: number;
    low_stock_alerts: number;
    expired_units: number;
    expiring_90_units: number;
    with_expiry_units: number;
}

interface StockLocationRow {
    location_id: string;
    location_name: string;
    units: number;
    skus: number;
}

interface LowStockRow {
    location_type: string;
    location_name: string;
    product_code: string;
    product_name: string;
    on_hand: number;
    reserved: number;
    available: number;
    stock_barrier: number;
    threshold: number;
    shortfall: number;
}

interface DocumentSummary {
    documents: number;
    units: number;
}

interface PaymentHealthRow {
    id: string;
    order_number: string;
    order_status: string;
    payment_status: string | null;
    reconciliation_status: string;
    grand_total: number;
    total_amount: number;
    created_at: string;
}

interface PaymentHealth {
    applicable: boolean;
    orders: number;
    matched: number;
    findings: number;
    exceptions: number;
    cancelled_orders: number;
    pending_payment: number;
    failed_payment: number;
    needs_review: number;
    without_payment: number;
    detail_mismatch: number;
    total_value: number;
    total_paid_amount: number;
    rows: PaymentHealthRow[];
}

interface CustomerTrendRow {
    date: string;
    label: string;
    new_members: number;
}

interface StoreVisitRow {
    store_id: string;
    store_name: string;
    visits: number;
    unique_visitors: number;
    share: number;
}

interface Logistics {
    warehouse_inbound: DocumentSummary;
    warehouse_outbound: DocumentSummary;
    store_inbound: DocumentSummary;
    store_outbound: DocumentSummary;
    store_return: DocumentSummary;
}

interface PendingQueues {
    warehouse_outbound_unreleased: number;
    store_inbound_pending_qc: number;
    store_inbound_partial_qc: number;
    store_transfer_not_recorded: number;
}

interface Props {
    filters: Filters;
    periodOptions: Array<{ value: string; label: string }>;
    stores: Array<Record<string, any>>;
    warehouses: Array<Record<string, any>>;
    generated_at: string;
    sales: {
        summary: SalesSummary;
        channels: ChannelRow[];
        trend: TrendRow[];
        top_stores: StoreRow[];
        top_products: ProductRow[];
    };
    customers: {
        summary: {
            new_members: number;
            store_members: number;
            ecommerce_members: number;
            visits: number;
            unique_visitors: number;
        };
        trend: {
            grain: 'day' | 'month';
            rows: CustomerTrendRow[];
        };
        store_visits: StoreVisitRow[];
    };
    orders: {
        summary: OrdersSummary;
        ecommerce_statuses: StatusRow[];
        store_order_statuses: StatusRow[];
        return_statuses: StatusRow[];
        attention: AttentionRow[];
    };
    inventory: {
        summary: InventorySummary;
        low_stock: LowStockRow[];
        warehouse_stock: StockLocationRow[];
        store_stock: StockLocationRow[];
        logistics: Logistics;
        pending_queues: PendingQueues;
    };
    payments: PaymentHealth;
    urls: {
        index: string;
        sales: string;
        sales_omset: string;
        top_products: string;
        orders: string;
        payments: string;
        pending_transactions: string;
        anomalies: string;
        ecommerce_orders: string;
        store_orders: string;
        store_returns: string;
        stock_store: string;
        stock_warehouse: string;
        warehouse_inbound: string;
        warehouse_outbound: string;
        store_inbound: string;
        store_outbound: string;
        expiring: string;
        slow_moving: string;
        aging: string;
        monitoring: string;
        crm: string;
        demographics: string;
        preference: string;
    };
}

const props = defineProps<Props>();

const showGuide = ref(false);
const showSalesTrend = ref(true);
const showMemberChart = ref(false);
const openProductId = ref<string | null>(null);

const toggleProduct = (productId: string) => {
    openProductId.value = openProductId.value === productId ? null : productId;
};

const form = reactive({
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    channel: props.filters.channel ?? 'all',
    store_id: props.filters.store_id ?? '',
    warehouse_id: props.filters.warehouse_id ?? '',
});

watch(() => props.filters, (filters) => {
    form.date_from = filters.date_from ?? '';
    form.date_to = filters.date_to ?? '';
    form.channel = filters.channel ?? 'all';
    form.store_id = filters.store_id ?? '';
    form.warehouse_id = filters.warehouse_id ?? '';
}, { deep: true });

const storeOptions = computed(() => props.stores.map((store) => ({
    value: String(store.id),
    label: String(store.name ?? '-'),
})));

const warehouseOptions = computed(() => props.warehouses.map((warehouse) => ({
    value: String(warehouse.id),
    label: String(warehouse.name ?? '-'),
})));

const activePeriodLabel = computed(
    () => props.periodOptions.find((option) => option.value === props.filters.period)?.label ?? 'Rentang kustom',
);

const quickPeriodValues = ['today', 'week', 'month'];

const quickPeriods = computed(
    () => props.periodOptions.filter((option) => quickPeriodValues.includes(option.value)),
);

const otherPeriods = computed(
    () => props.periodOptions.filter((option) => !quickPeriodValues.includes(option.value)),
);

const otherPeriod = computed(
    () => (otherPeriods.value.some((option) => option.value === props.filters.period) ? props.filters.period : ''),
);

const applyOtherPeriod = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;

    if (value) {
        applyPeriod(value);
    }
};

const baseQuery = () => ({
    channel: form.channel || undefined,
    store_id: form.store_id || undefined,
    warehouse_id: form.warehouse_id || undefined,
});

const navigate = (params: Record<string, string | undefined>) => {
    router.get(props.urls.index, { ...baseQuery(), ...params }, {
        preserveScroll: true,
        replace: true,
    });
};

const applyPeriod = (period: string) => {
    navigate({ period });
};

const applyRange = () => {
    navigate({
        period: 'custom',
        date_from: form.date_from || undefined,
        date_to: form.date_to || undefined,
    });
};

const resetRange = () => {
    form.date_from = '';
    form.date_to = '';
    form.channel = 'all';
    form.store_id = '';
    form.warehouse_id = '';
    router.get(props.urls.index, {}, { preserveScroll: true, replace: true });
};

const customerCards = computed(() => [
    {
        label: 'Member Baru',
        value: formatNumber(props.customers.summary.new_members),
        hint: 'Registrasi pada periode ini',
        icon: 'mdi-account-plus-outline',
        tone: 'accent',
    },
    {
        label: 'Member Store',
        value: formatNumber(props.customers.summary.store_members),
        hint: 'Registrasi melalui toko',
        icon: 'mdi-storefront-outline',
        tone: 'info',
    },
    {
        label: 'Member Ecommerce',
        value: formatNumber(props.customers.summary.ecommerce_members),
        hint: 'Registrasi melalui ecommerce',
        icon: 'mdi-cart-outline',
        tone: 'success',
    },
]);

const averageVisitsPerVisitor = computed(() => {
    const visitors = props.customers.summary.unique_visitors;

    return visitors > 0 ? Number((props.customers.summary.visits / visitors).toFixed(1)) : 0;
});

const visitCards = computed(() => [
    {
        label: 'Kunjungan Toko',
        value: formatNumber(props.customers.summary.visits),
        hint: 'Total check-in pada periode ini',
        icon: 'mdi-account-clock-outline',
        tone: 'accent',
    },
    {
        label: 'Pengunjung Unik',
        value: formatNumber(props.customers.summary.unique_visitors),
        hint: 'Customer berbeda yang berkunjung',
        icon: 'mdi-account-group-outline',
        tone: 'info',
    },
    {
        label: 'Rata-rata per Pengunjung',
        value: formatNumber(averageVisitsPerVisitor.value),
        hint: 'Kunjungan dibagi pengunjung unik',
        icon: 'mdi-chart-line',
        tone: 'neutral',
    },
]);

const salesCards = computed(() => [
    {
        label: 'Omset Kotor',
        value: formatCurrency(props.sales.summary.gross_revenue),
        hint: 'Harga jual produk sebelum diskon dan voucher',
        icon: 'mdi-cash-multiple',
        tone: 'accent',
    },
    {
        label: 'Omset Bersih',
        value: formatCurrency(props.sales.summary.net_revenue),
        hint: 'Setelah diskon produk dan voucher, tanpa ongkir dan biaya operasional',
        icon: 'mdi-cash-check',
        tone: 'success',
    },
    {
        label: 'Transaksi Lunas',
        value: formatNumber(props.sales.summary.transactions),
        hint: `${formatNumber(props.sales.summary.buyers)} pembeli teridentifikasi`,
        icon: 'mdi-receipt-text-check-outline',
        tone: 'info',
    },
    {
        label: 'Rata-rata / Transaksi',
        value: formatCurrency(props.sales.summary.average_net_transaction),
        hint: 'Omset bersih dibagi transaksi lunas',
        icon: 'mdi-chart-line',
        tone: 'neutral',
    },
]);

const orderCards = computed(() => [
    {
        label: 'Pesanan Ecommerce',
        value: formatNumber(props.orders.summary.ecommerce_total),
        hint: 'Dibuat pada periode ini',
        icon: 'mdi-cart-outline',
        tone: 'accent',
    },
    {
        label: 'Belum Selesai',
        value: formatNumber(props.orders.summary.ecommerce_open),
        hint: `${formatNumber(props.orders.summary.ecommerce_pending_payment)} menunggu pembayaran`,
        icon: 'mdi-clock-outline',
        tone: 'warning',
    },
    {
        label: 'Perlu Tindak Lanjut',
        value: formatNumber(props.orders.summary.ecommerce_need_attention),
        hint: 'Sudah diproses tetapi > 24 jam',
        icon: 'mdi-alert-circle-outline',
        tone: 'danger',
    },
    {
        label: 'Selesai / Dibatalkan',
        value: `${formatNumber(props.orders.summary.ecommerce_completed)} / ${formatNumber(props.orders.summary.ecommerce_cancelled)}`,
        hint: 'Status diterima / dibatalkan',
        icon: 'mdi-check-decagram-outline',
        tone: 'success',
    },
]);

const paymentCards = computed(() => {
    const failed = props.payments.cancelled_orders + props.payments.failed_payment;

    return [
        {
            label: 'Order',
            value: formatNumber(props.payments.orders),
            hint: `${formatNumber(props.payments.cancelled_orders)} dibatalkan`,
            icon: 'mdi-receipt-text-outline',
            tone: 'accent',
        },
        {
            label: 'Berhasil',
            value: formatNumber(props.payments.matched),
            hint: 'Nominal cocok dan pembayaran lunas',
            icon: 'mdi-check-decagram-outline',
            tone: 'success',
        },
        {
            label: 'Gagal / Batal',
            value: formatNumber(failed),
            hint: `${formatNumber(props.payments.cancelled_orders)} dibatalkan · ${formatNumber(props.payments.failed_payment)} pembayaran gagal`,
            icon: 'mdi-close-octagon-outline',
            tone: failed > 0 ? 'danger' : 'success',
        },
        {
            label: 'Perlu Dicek',
            value: formatNumber(props.payments.needs_review),
            hint: `${formatNumber(props.payments.pending_payment)} belum selesai · ${formatNumber(props.payments.without_payment)} tanpa pembayaran`,
            icon: 'mdi-alert-circle-outline',
            tone: props.payments.needs_review > 0 ? 'warning' : 'success',
        },
    ];
});

const inventoryCards = computed(() => [
    {
        label: 'Stok Gudang',
        value: formatNumber(props.inventory.summary.warehouse_units),
        hint: `${formatNumber(props.inventory.summary.warehouse_skus)} SKU · ${formatNumber(props.inventory.summary.warehouse_locations)} gudang`,
        icon: 'mdi-warehouse',
        tone: 'accent',
    },
    {
        label: 'Stok Cabang',
        value: formatNumber(props.inventory.summary.store_units),
        hint: `${formatNumber(props.inventory.summary.store_skus)} SKU · ${formatNumber(props.inventory.summary.store_locations)} toko`,
        icon: 'mdi-storefront-outline',
        tone: 'info',
    },
    {
        label: 'Stok Menipis',
        value: formatNumber(props.inventory.summary.low_stock_alerts),
        hint: 'Produk-lokasi di bawah batas minimum',
        icon: 'mdi-alert-octagon-outline',
        tone: 'warning',
    },
    {
        label: 'Kedaluwarsa ≤ 90 Hari',
        value: formatNumber(props.inventory.summary.expiring_90_units),
        hint: `${formatNumber(props.inventory.summary.expired_units)} unit sudah kedaluwarsa`,
        icon: 'mdi-calendar-clock',
        tone: 'danger',
    },
]);

const orderBreakdowns = computed(() => [
    { title: 'Status Pesanan Ecommerce', icon: 'mdi-cart-outline', rows: props.orders.ecommerce_statuses },
    { title: 'Status Permintaan Store', icon: 'mdi-store-arrow-up', rows: props.orders.store_order_statuses },
    { title: 'Status Retur Store', icon: 'mdi-keyboard-return', rows: props.orders.return_statuses },
]);

const logisticsItems = computed(() => [
    { label: 'Inbound Gudang', data: props.inventory.logistics.warehouse_inbound, url: props.urls.warehouse_inbound, icon: 'mdi-tray-arrow-down' },
    { label: 'Outbound Gudang', data: props.inventory.logistics.warehouse_outbound, url: props.urls.warehouse_outbound, icon: 'mdi-tray-arrow-up' },
    { label: 'Inbound Cabang', data: props.inventory.logistics.store_inbound, url: props.urls.store_inbound, icon: 'mdi-store-arrow-down' },
    { label: 'Outbound Cabang', data: props.inventory.logistics.store_outbound, url: props.urls.store_outbound, icon: 'mdi-store-arrow-up' },
    { label: 'Retur Cabang', data: props.inventory.logistics.store_return, url: props.urls.store_returns, icon: 'mdi-backup-restore' },
]);

const pendingItems = computed(() => [
    { label: 'Outbound gudang belum dirilis', value: props.inventory.pending_queues.warehouse_outbound_unreleased, url: props.urls.warehouse_outbound },
    { label: 'Inbound cabang belum lolos QC', value: props.inventory.pending_queues.store_inbound_pending_qc, url: props.urls.store_inbound },
    { label: 'Inbound cabang lolos QC sebagian', value: props.inventory.pending_queues.store_inbound_partial_qc, url: props.urls.store_inbound },
    { label: 'Outbound gudang ke toko belum tercatat', value: props.inventory.pending_queues.store_transfer_not_recorded, url: `${props.urls.warehouse_outbound}?reconciliation_status=not_recorded` },
]);

const visibleTrend = computed(() => props.sales.trend.slice(-14));
const maxTrendRevenue = computed(() => Math.max(
    1,
    ...visibleTrend.value.flatMap((row) => [row.gross_revenue, row.net_revenue]),
));
const trendHeight = (value: number) => `${Math.max(4, Math.round((value / maxTrendRevenue.value) * 100))}%`;

const formatAge = (hours: number) => {
    if (hours >= 48) {
        return `${Math.floor(hours / 24)} hari`;
    }

    return `${formatNumber(hours)} jam`;
};

const statusTone = (status: string) => {
    const normalized = status.toLowerCase();

    if (['received', 'close', 'approved', 'partial_approved'].includes(normalized)) return 'success';
    if (['cancelled', 'rejected', 'on_hold'].includes(normalized)) return 'danger';
    if (['pending', 'pending_approval', 'requested', 'draft'].includes(normalized)) return 'warning';
    if (['paid', 'processed', 'packing', 'packing_completed', 'shipped'].includes(normalized)) return 'accent';

    return 'neutral';
};

const paymentFindingTone = (status: string) => {
    const normalized = status.toLowerCase();

    if (normalized.includes('dibatalkan') || normalized.includes('berbeda') || normalized.includes('hilang')) return 'danger';

    return 'warning';
};

const generatedLabel = computed(() => props.generated_at ? formatDate(props.generated_at, true) : '-');

const exportOverview = () => {
    const rows: Array<Record<string, unknown>> = [
        { section: 'Periode', metric: 'Dari', value: props.filters.date_from },
        { section: 'Periode', metric: 'Sampai', value: props.filters.date_to },
        ...Object.entries(props.sales.summary).map(([metric, value]) => ({ section: 'Penjualan', metric, value })),
        ...props.sales.channels.map((row) => ({ section: 'Channel', metric: row.label, value: row.net_revenue, gross_revenue: row.gross_revenue, net_revenue: row.net_revenue, transactions: row.transactions, share_percent: row.net_share })),
        ...props.sales.trend.map((row) => ({ section: 'Tren Penjualan', metric: row.date, value: row.net_revenue, gross_revenue: row.gross_revenue, net_revenue: row.net_revenue, transactions: row.transactions })),
        ...props.sales.top_stores.map((row) => ({ section: 'Store Teratas', metric: row.store_name, value: row.net_revenue, gross_revenue: row.gross_revenue, net_revenue: row.net_revenue, transactions: row.transactions, buyers: row.buyers })),
        ...props.sales.top_products.map((row) => ({ section: 'Produk Teratas', metric: row.product_name, product_code: row.product_code, category: row.category_name, quantity: row.qty, transactions: row.transactions })),
        ...props.sales.top_products.flatMap((product) => product.stores.map((store) => ({
            section: 'Produk per Cabang',
            metric: product.product_name,
            product_code: product.product_code,
            location: store.store_name,
            quantity: store.qty,
            transactions: store.transactions,
        }))),
        ...Object.entries(props.customers.summary).map(([metric, value]) => ({ section: 'Pelanggan', metric, value })),
        ...props.customers.trend.rows.map((row) => ({
            section: 'Tren Pelanggan',
            metric: row.label,
            new_members: row.new_members,
        })),
        ...props.customers.store_visits.map((row) => ({
            section: 'Kunjungan per Toko',
            metric: row.store_name,
            visits: row.visits,
            unique_visitors: row.unique_visitors,
        })),
        ...(props.payments.applicable ? [
            { section: 'Pembayaran', metric: 'Order', value: props.payments.orders },
            { section: 'Pembayaran', metric: 'Berhasil', value: props.payments.matched },
            { section: 'Pembayaran', metric: 'Gagal / Batal', value: props.payments.cancelled_orders + props.payments.failed_payment },
            { section: 'Pembayaran', metric: 'Perlu dicek', value: props.payments.needs_review },
            { section: 'Pembayaran', metric: 'Pembayaran gagal', value: props.payments.failed_payment },
            { section: 'Pembayaran', metric: 'Pembayaran belum selesai', value: props.payments.pending_payment },
            { section: 'Pembayaran', metric: 'Tanpa pembayaran', value: props.payments.without_payment },
            { section: 'Pembayaran', metric: 'Detail pembayaran berbeda', value: props.payments.detail_mismatch },
            ...props.payments.rows.map((row) => ({
                section: 'Temuan Pembayaran',
                metric: row.order_number,
                status: row.reconciliation_status,
                value: row.grand_total,
                amount: row.total_amount,
            })),
        ] : []),
        ...Object.entries(props.orders.summary).map(([metric, value]) => ({ section: 'Pesanan', metric, value })),
        ...props.orders.ecommerce_statuses.map((row) => ({ section: 'Status Ecommerce', metric: row.label, status: row.status, count: row.count, share_percent: row.share })),
        ...props.orders.store_order_statuses.map((row) => ({ section: 'Status Order Store', metric: row.label, status: row.status, count: row.count, share_percent: row.share })),
        ...props.orders.return_statuses.map((row) => ({ section: 'Status Retur', metric: row.label, status: row.status, count: row.count, share_percent: row.share })),
        ...props.orders.attention.map((row) => ({ section: 'Perlu Perhatian', metric: row.reference, type: row.type, status: row.status_label, age_hours: row.age_hours, amount: row.amount, partner: row.partner })),
        ...Object.entries(props.inventory.summary).map(([metric, value]) => ({ section: 'Stok', metric, value })),
        ...props.inventory.low_stock.map((row) => ({ section: 'Stok Menipis', metric: row.product_name, product_code: row.product_code, location: row.location_name, on_hand: row.on_hand, reserved: row.reserved, available: row.available, threshold: row.threshold, shortfall: row.shortfall })),
        ...props.inventory.warehouse_stock.map((row) => ({ section: 'Stok Gudang per Lokasi', metric: row.location_name, units: row.units, skus: row.skus })),
        ...props.inventory.store_stock.map((row) => ({ section: 'Stok Cabang per Toko', metric: row.location_name, units: row.units, skus: row.skus })),
        ...Object.entries(props.inventory.logistics).map(([metric, value]) => ({ section: 'Logistik', metric, documents: value.documents, units: value.units })),
        ...Object.entries(props.inventory.pending_queues).map(([metric, value]) => ({ section: 'Antrean', metric, value })),
    ];

    downloadExcelTable('Ringkasan-Usaha', 'Ringkasan Usaha', [
        { key: 'section', label: 'Bagian' },
        { key: 'metric', label: 'Rincian' },
        { key: 'value', label: 'Nilai' },
        { key: 'gross_revenue', label: 'Omset Kotor' },
        { key: 'net_revenue', label: 'Omset Bersih' },
        { key: 'product_code', label: 'Kode Produk' },
        { key: 'category', label: 'Kategori' },
        { key: 'location', label: 'Lokasi' },
        { key: 'status', label: 'Status' },
        { key: 'type', label: 'Jenis' },
        { key: 'transactions', label: 'Transaksi' },
        { key: 'buyers', label: 'Pembeli' },
        { key: 'quantity', label: 'Kuantitas' },
        { key: 'new_members', label: 'Member Baru' },
        { key: 'visits', label: 'Kunjungan' },
        { key: 'unique_visitors', label: 'Pengunjung Unik' },
        { key: 'skus', label: 'SKU' },
        { key: 'documents', label: 'Dokumen' },
        { key: 'units', label: 'Unit' },
        { key: 'share_percent', label: 'Persentase' },
        { key: 'age_hours', label: 'Umur (Jam)' },
        { key: 'amount', label: 'Nominal' },
        { key: 'partner', label: 'Mitra' },
        { key: 'on_hand', label: 'Stok' },
        { key: 'reserved', label: 'Reservasi' },
        { key: 'available', label: 'Tersedia' },
        { key: 'threshold', label: 'Batas' },
        { key: 'shortfall', label: 'Kekurangan' },
    ], rows, {
        title: 'Ringkasan Usaha',
        filters: [
            { label: 'Periode', value: activePeriodLabel.value },
            { label: 'Dari', value: props.filters.date_from },
            { label: 'Sampai', value: props.filters.date_to },
            { label: 'Kanal', value: props.filters.channel === 'all' ? 'Semua kanal' : props.filters.channel === 'store' ? 'Toko' : 'E-commerce' },
            { label: 'Toko', value: props.stores.find((store) => String(store.id) === String(props.filters.store_id))?.name ?? 'Semua toko' },
            { label: 'Gudang', value: props.warehouses.find((warehouse) => String(warehouse.id) === String(props.filters.warehouse_id))?.name ?? 'Semua gudang' },
        ],
        currencyColumns: ['amount', 'gross_revenue', 'net_revenue'],
    });
};
</script>

<template>
    <Head title="Ringkasan Usaha" />

    <div class="overview-page">
        <AppPageHeader
            eyebrow="Laporan / Ringkasan Usaha"
            title="Ringkasan Omset, Pesanan, dan Stok"
            description="Angka ringkas yang mudah dibaca, dengan definisi data yang jelas agar tidak tertukar atau terhitung ganda."
        >
            <template #actions>
                <button type="button" class="btn btn-outline-secondary" @click="exportOverview">
                    <i class="mdi mdi-file-excel-outline me-1" aria-hidden="true"></i>
                    Ekspor Excel
                </button>
                <Link :href="props.urls.monitoring" class="btn btn-outline-secondary">
                    <i class="mdi mdi-monitor-dashboard me-1" aria-hidden="true"></i>
                    Pusat Komando
                </Link>
                <Link :href="props.urls.orders" class="btn btn-primary">
                    <i class="mdi mdi-clipboard-check-outline me-1" aria-hidden="true"></i>
                    Tindak Lanjut Pesanan
                </Link>
            </template>
        </AppPageHeader>

        <section class="overview-filter" aria-label="Filter periode ringkasan">
            <form @submit.prevent="applyRange">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="overview-date-from">Dari tanggal</label>
                        <input id="overview-date-from" v-model="form.date_from" class="form-control form-control-sm" type="date" :max="form.date_to || undefined" />
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="overview-date-to">Sampai tanggal</label>
                        <input id="overview-date-to" v-model="form.date_to" class="form-control form-control-sm" type="date" :min="form.date_from || undefined" />
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="overview-channel">Kanal</label>
                        <select id="overview-channel" v-model="form.channel" class="form-select form-select-sm">
                            <option value="all">Semua kanal</option>
                            <option value="store">Toko</option>
                            <option value="ecommerce">E-commerce</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-3">
                        <label class="form-label" for="overview-store">Toko</label>
                        <SearchableSelect id="overview-store" aria-label="Toko" v-model="form.store_id" :options="storeOptions" placeholder="Semua toko" search-placeholder="Cari toko..." />
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="overview-warehouse">Gudang</label>
                        <SearchableSelect id="overview-warehouse" aria-label="Gudang" v-model="form.warehouse_id" :options="warehouseOptions" placeholder="Semua gudang" search-placeholder="Cari gudang..." />
                    </div>
                </div>
                <div class="row g-2 align-items-end mt-1">
                    <div class="col-12 col-xl-7">
                        <span class="overview-preset-label">Pilih cepat</span>
                        <div class="overview-presets">
                            <button
                                v-for="option in quickPeriods"
                                :key="option.value"
                                type="button"
                                class="chip"
                                :class="{ 'is-active': props.filters.period === option.value }"
                                :aria-pressed="props.filters.period === option.value"
                                @click="applyPeriod(option.value)"
                            >{{ option.label }}</button>
                            <label class="visually-hidden" for="overview-period-other">Periode lain</label>
                            <select
                                id="overview-period-other"
                                class="form-select form-select-sm overview-period-select"
                                :value="otherPeriod"
                                @change="applyOtherPeriod"
                            >
                                <option value="">Periode lain</option>
                                <option v-for="option in otherPeriods" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-xl-5 d-flex flex-wrap gap-2 justify-content-xl-end">
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="mdi mdi-filter-outline me-1"></i>
                            Terapkan
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" @click="resetRange">
                            <i class="mdi mdi-refresh me-1"></i>
                            Reset
                        </button>
                    </div>
                </div>
            </form>
            <div class="overview-period-note">
                <span>Periode data: <strong>{{ activePeriodLabel }} · {{ formatDate(props.filters.date_from) }} – {{ formatDate(props.filters.date_to) }}</strong></span>
                <span>Terakhir dihitung: {{ generatedLabel }}</span>
            </div>
        </section>

        <section class="overview-guide">
            <button
                type="button"
                class="overview-guide-toggle"
                :aria-expanded="showGuide"
                aria-controls="overview-guide-content"
                @click="showGuide = !showGuide"
            >
                <span class="overview-guide-head">
                    <i class="mdi mdi-information-outline" aria-hidden="true"></i>
                    <strong>Cara membaca angka</strong>
                </span>
                <i class="mdi" :class="showGuide ? 'mdi-chevron-up' : 'mdi-chevron-down'" aria-hidden="true"></i>
            </button>
            <div v-show="showGuide" id="overview-guide-content">
                <div class="row g-2">
                    <div class="col-md-6 col-xl-3">
                        <p><strong>Omset</strong> dihitung dari transaksi lunas. Omset kotor memakai harga jual sebelum diskon dan voucher; omset bersih setelah diskon produk dan voucher, tanpa ongkir, biaya transport, listrik, atau biaya operasional lain.</p>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <p><strong>Pesanan</strong> dihitung dari tanggal pesanan dibuat, termasuk yang belum dibayar. Karena itu jumlah pesanan tidak sama dengan transaksi lunas.</p>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <p><strong>Pelanggan</strong> menghitung member baru berdasarkan tanggal registrasi dan kunjungan toko berdasarkan waktu check-in pada periode terpilih.</p>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <p><strong>Stok</strong> adalah posisi saat ini. Inbound, outbound, dan retur adalah dokumen pada periode yang dipilih, bukan saldo stok.</p>
                    </div>
                </div>
                <p class="overview-guide-note"><strong>Pembayaran</strong> membandingkan nilai order e-commerce dengan total pembayaran yang tercatat. Angka ini bukan bukti dana masuk ke rekening bank.</p>
            </div>
        </section>

        <nav class="overview-anchors" aria-label="Bagian ringkasan">
            <a href="#penjualan" class="chip">Omset</a>
            <a href="#pembayaran" class="chip">Pembayaran</a>
            <a href="#pelanggan" class="chip">Pelanggan</a>
            <a href="#kunjungan" class="chip">Kunjungan</a>
            <a href="#pesanan" class="chip">Pesanan</a>
            <a href="#stok-logistik" class="chip">Stok &amp; Logistik</a>
        </nav>

        <section id="penjualan" class="overview-section" aria-labelledby="sales-title">
            <div class="overview-section-head">
                <div>
                    <span class="overview-section-kicker">Omset</span>
                    <h2 id="sales-title">Omset kotor dan bersih dari transaksi lunas</h2>
                    <p>Omset kotor sebelum diskon dan voucher; omset bersih setelah diskon produk dan voucher, tanpa ongkir dan biaya operasional.</p>
                </div>
                <div class="overview-section-links">
                    <Link :href="props.urls.sales">Pemasukan Realtime</Link>
                    <Link :href="props.urls.sales_omset">Omzet Cabang</Link>
                    <Link :href="props.urls.top_products">Produk Terlaris</Link>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div v-for="card in salesCards" :key="card.label" class="col-6 col-xl-3">
                    <article class="overview-kpi" :class="`is-${card.tone}`">
                        <span class="overview-kpi-icon"><i class="mdi" :class="card.icon"></i></span>
                        <div>
                            <span class="overview-kpi-label">{{ card.label }}</span>
                            <strong class="overview-kpi-value">{{ card.value }}</strong>
                            <small>{{ card.hint }}</small>
                        </div>
                    </article>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-xl-7">
                    <article class="overview-card h-100">
                        <header class="overview-card-head">
                            <div>
                                <h3>Tren Omset Harian</h3>
                                <p>14 hari terakhir dalam periode. Batang kiri omset kotor, batang kanan omset bersih.</p>
                            </div>
                            <div class="overview-card-actions">
                                <span class="overview-card-meta">Bersih {{ formatCurrency(props.sales.summary.net_revenue) }}</span>
                                <button
                                    type="button"
                                    class="overview-card-toggle"
                                    :aria-expanded="showSalesTrend"
                                    aria-controls="sales-trend-chart"
                                    @click="showSalesTrend = !showSalesTrend"
                                >
                                    <i class="mdi" :class="showSalesTrend ? 'mdi-chevron-up' : 'mdi-chevron-down'" aria-hidden="true"></i>
                                    <span class="visually-hidden">{{ showSalesTrend ? 'Sembunyikan' : 'Tampilkan' }} tren omset</span>
                                </button>
                            </div>
                        </header>
                        <div v-show="showSalesTrend" id="sales-trend-chart">
                            <div v-if="visibleTrend.length" class="overview-chart" role="img" aria-label="Grafik tren omset kotor dan bersih harian" aria-describedby="trend-data-table">
                                <div class="overview-chart-legend" aria-hidden="true">
                                    <span><i class="overview-chart-swatch is-gross"></i>Omset kotor</span>
                                    <span><i class="overview-chart-swatch is-net"></i>Omset bersih</span>
                                    <span class="overview-chart-scale">Skala 0 – {{ formatCurrency(maxTrendRevenue) }}</span>
                                </div>
                                <div class="overview-chart-grid">
                                    <div v-for="row in visibleTrend" :key="row.date" class="overview-chart-column">
                                        <div class="overview-chart-track">
                                            <div
                                                class="overview-chart-bar is-gross"
                                                :style="{ height: trendHeight(row.gross_revenue) }"
                                                :title="`${row.label} kotor: ${formatCurrency(row.gross_revenue)}`"
                                            ></div>
                                            <div
                                                class="overview-chart-bar is-net"
                                                :style="{ height: trendHeight(row.net_revenue) }"
                                                :title="`${row.label} bersih: ${formatCurrency(row.net_revenue)} dari ${formatNumber(row.transactions)} transaksi`"
                                            ></div>
                                        </div>
                                        <span class="overview-chart-label">{{ row.label }}</span>
                                    </div>
                                </div>
                            </div>
                            <table v-if="visibleTrend.length" id="trend-data-table" class="visually-hidden">
                                <caption>Data tren omset harian</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Tanggal</th>
                                        <th scope="col">Omset Kotor</th>
                                        <th scope="col">Omset Bersih</th>
                                        <th scope="col">Transaksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in visibleTrend" :key="`trend-data-${row.date}`">
                                        <th scope="row">{{ row.label }}</th>
                                        <td>{{ formatCurrency(row.gross_revenue) }}</td>
                                        <td>{{ formatCurrency(row.net_revenue) }}</td>
                                        <td>{{ formatNumber(row.transactions) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p v-else class="overview-empty">Belum ada transaksi lunas pada periode ini.</p>
                        </div>
                    </article>
                </div>
                <div class="col-xl-5">
                    <article class="overview-card h-100">
                        <header class="overview-card-head">
                            <div>
                                <h3>Kontribusi Omset per Channel</h3>
                                <p>Perbandingan omset bersih POS dan Ecommerce.</p>
                            </div>
                        </header>
                        <div class="overview-channel-list">
                            <div v-for="channel in props.sales.channels" :key="channel.key" class="overview-channel">
                                <div class="overview-channel-row">
                                    <span>{{ channel.label }}</span>
                                    <strong>{{ formatCurrency(channel.net_revenue) }}</strong>
                                </div>
                                <div class="overview-progress" aria-hidden="true">
                                    <span :style="{ width: `${Math.max(2, channel.net_share)}%` }"></span>
                                </div>
                                <small>Omset kotor {{ formatCurrency(channel.gross_revenue) }} · {{ formatNumber(channel.transactions) }} transaksi · {{ channel.net_share }}% dari omset bersih</small>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-xl-7">
                    <article class="overview-card h-100">
                        <header class="overview-card-head">
                            <div>
                                <h3>Cabang dengan Omset Bersih Terbesar</h3>
                                <p>Berdasarkan omset bersih produk pada periode ini.</p>
                            </div>
                        </header>
                        <div v-if="props.sales.top_stores.length" class="table-responsive">
                            <table class="table align-middle mb-0 overview-table">
                                <thead>
                                    <tr><th scope="col">Cabang</th><th scope="col" class="text-end">Transaksi</th><th scope="col" class="text-end">Pembeli</th><th scope="col" class="text-end">Omset Kotor</th><th scope="col" class="text-end">Omset Bersih</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in props.sales.top_stores" :key="row.store_name">
                                        <td>{{ row.store_name }}</td>
                                        <td class="text-end">{{ formatNumber(row.transactions) }}</td>
                                        <td class="text-end">{{ formatNumber(row.buyers) }}</td>
                                        <td class="text-end">{{ formatCurrency(row.gross_revenue) }}</td>
                                        <td class="text-end"><strong>{{ formatCurrency(row.net_revenue) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="overview-empty">Belum ada data cabang.</p>
                    </article>
                </div>
                <div class="col-xl-5">
                    <article class="overview-card h-100">
                        <header class="overview-card-head">
                            <div>
                                <h3>Produk Paling Banyak Terjual</h3>
                                <p>Berdasarkan quantity item pada transaksi lunas. Klik produk untuk melihat cabang penjualnya.</p>
                            </div>
                        </header>
                        <div v-if="props.sales.top_products.length" class="overview-product-list">
                            <div v-for="product in props.sales.top_products" :key="product.product_id" class="overview-product">
                                <button
                                    type="button"
                                    class="overview-product-toggle"
                                    :aria-expanded="openProductId === product.product_id"
                                    :aria-controls="`product-stores-${product.product_id}`"
                                    @click="toggleProduct(product.product_id)"
                                >
                                    <span class="overview-product-name">
                                        <strong>{{ product.product_name }}</strong>
                                        <small>{{ product.product_code }} · {{ product.category_name ?? 'Tanpa kategori' }}</small>
                                    </span>
                                    <span class="overview-product-qty">{{ formatNumber(product.qty) }} pcs</span>
                                    <i class="mdi" :class="openProductId === product.product_id ? 'mdi-chevron-up' : 'mdi-chevron-down'" aria-hidden="true"></i>
                                </button>
                                <div
                                    v-show="openProductId === product.product_id"
                                    :id="`product-stores-${product.product_id}`"
                                    class="overview-product-stores"
                                >
                                    <p v-if="!product.stores.length" class="overview-cell-note">Belum ada data cabang.</p>
                                    <ul v-else>
                                        <li v-for="store in product.stores" :key="`${product.product_id}-${store.store_id ?? store.store_name}`">
                                            <span>{{ store.store_name }}</span>
                                            <strong>{{ formatNumber(store.qty) }} pcs</strong>
                                            <small>{{ formatNumber(store.transactions) }} transaksi</small>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <p v-else class="overview-empty">Belum ada produk terjual.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="pembayaran" class="overview-section" aria-labelledby="payments-title">
            <div class="overview-section-head">
                <div>
                    <span class="overview-section-kicker">Pembayaran</span>
                    <h2 id="payments-title">Kecocokan order dan pembayaran e-commerce</h2>
                    <p>Nilai order dibandingkan dengan total pembayaran yang tercatat pada periode ini.</p>
                </div>
                <div class="overview-section-links">
                    <Link :href="props.urls.payments">Rekonsiliasi Pembayaran</Link>
                    <Link :href="props.urls.pending_transactions">Transaksi Gantung</Link>
                    <Link :href="props.urls.anomalies">Anomali Transaksi</Link>
                </div>
            </div>

            <template v-if="props.payments.applicable">
                <div class="row g-3 mb-3">
                    <div v-for="card in paymentCards" :key="card.label" class="col-6 col-xl-3">
                        <article class="overview-kpi" :class="`is-${card.tone}`">
                            <span class="overview-kpi-icon"><i class="mdi" :class="card.icon"></i></span>
                            <div>
                                <span class="overview-kpi-label">{{ card.label }}</span>
                                <strong class="overview-kpi-value">{{ card.value }}</strong>
                                <small>{{ card.hint }}</small>
                            </div>
                        </article>
                    </div>
                </div>

                <div class="overview-payment-breakdown" aria-label="Rincian status order">
                    <span>Cocok <strong>{{ formatNumber(props.payments.matched) }}</strong></span>
                    <span>Dibatalkan <strong>{{ formatNumber(props.payments.cancelled_orders) }}</strong></span>
                    <span>Pembayaran gagal <strong>{{ formatNumber(props.payments.failed_payment) }}</strong></span>
                    <span>Belum selesai <strong>{{ formatNumber(props.payments.pending_payment) }}</strong></span>
                    <span>Tanpa pembayaran <strong>{{ formatNumber(props.payments.without_payment) }}</strong></span>
                    <span>Detail berbeda <strong>{{ formatNumber(props.payments.detail_mismatch) }}</strong></span>
                </div>

                <article class="overview-card">
                    <header class="overview-card-head">
                        <div>
                            <h3>Temuan Pembayaran</h3>
                            <p>Order yang tidak lolos pemeriksaan otomatis, termasuk order dibatalkan atau pembayaran gagal. Contoh: total pembayaran tidak sama dengan nilai order, order belum punya pembayaran, pembayaran belum lunas, atau jumlah detail pembayaran sukses berbeda dari total pembayaran.</p>
                        </div>
                        <span class="overview-card-meta">{{ formatNumber(props.payments.findings) }} temuan</span>
                    </header>
                    <div v-if="props.payments.rows.length" class="table-responsive">
                        <table class="table align-middle mb-0 overview-table">
                            <thead>
                                <tr>
                                    <th scope="col">Order</th>
                                    <th scope="col">Temuan</th>
                                    <th scope="col" class="text-end">Nilai Order</th>
                                    <th scope="col" class="text-end">Total Pembayaran</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in props.payments.rows" :key="row.id">
                                    <td>
                                        <strong>{{ row.order_number }}</strong>
                                        <small class="overview-cell-note">{{ formatDate(row.created_at) }}</small>
                                    </td>
                                    <td>
                                        <span class="overview-badge" :class="`is-${paymentFindingTone(row.reconciliation_status)}`">{{ row.reconciliation_status }}</span>
                                        <small class="overview-cell-note">Pembayaran: {{ row.payment_status ?? 'belum ada' }}</small>
                                    </td>
                                    <td class="text-end">{{ formatCurrency(row.grand_total) }}</td>
                                    <td class="text-end">{{ row.total_amount > 0 ? formatCurrency(row.total_amount) : '-' }}</td>
                                    <td>
                                        <Link :href="`${props.urls.payments}?q=${encodeURIComponent(row.order_number)}`" class="overview-action-link">Buka</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="overview-empty">Tidak ada temuan pembayaran pada periode ini.</p>
                    <div class="overview-card-footer">
                        <span>Nilai order, termasuk dibatalkan: <strong>{{ formatCurrency(props.payments.total_value) }}</strong></span>
                        <span>Detail pembayaran sukses: <strong>{{ formatCurrency(props.payments.total_paid_amount) }}</strong></span>
                    </div>
                </article>
            </template>
            <p v-else class="overview-empty">Rekonsiliasi pembayaran e-commerce tidak dihitung saat filter kanal diatur ke Toko.</p>
        </section>

        <section id="pelanggan" class="overview-section" aria-labelledby="customers-title">
            <div class="overview-section-head">
                <div>
                    <span class="overview-section-kicker">Pelanggan</span>
                    <h2 id="customers-title">Pertumbuhan member baru</h2>
                    <p>Registrasi member baru berdasarkan kanal dan periode yang dipilih.</p>
                </div>
                <div class="overview-section-links">
                    <Link :href="props.urls.crm">CRM</Link>
                    <Link :href="props.urls.demographics">Demografi</Link>
                    <Link :href="props.urls.preference">Preferensi</Link>
                </div>
            </div>

            <div class="row g-3">
                <div v-for="card in customerCards" :key="card.label" class="col-6 col-xl-4">
                    <article class="overview-kpi" :class="`is-${card.tone}`">
                        <span class="overview-kpi-icon"><i class="mdi" :class="card.icon"></i></span>
                        <div>
                            <span class="overview-kpi-label">{{ card.label }}</span>
                            <strong class="overview-kpi-value">{{ card.value }}</strong>
                            <small>{{ card.hint }}</small>
                        </div>
                    </article>
                </div>
            </div>

            <article class="overview-card mt-3">
                <header class="overview-card-head">
                    <div>
                        <h3>Tren Member Baru</h3>
                        <p>Jumlah member baru per {{ props.customers.trend.grain === 'month' ? 'bulan' : 'hari' }} pada periode ini.</p>
                    </div>
                    <div class="overview-card-actions">
                        <span class="overview-card-meta">Total {{ formatNumber(props.customers.summary.new_members) }} member</span>
                        <button
                            type="button"
                            class="overview-card-toggle"
                            :aria-expanded="showMemberChart"
                            aria-controls="member-trend-chart"
                            @click="showMemberChart = !showMemberChart"
                        >
                            <i class="mdi" :class="showMemberChart ? 'mdi-chevron-up' : 'mdi-chevron-down'" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ showMemberChart ? 'Sembunyikan' : 'Tampilkan' }} tren member baru</span>
                        </button>
                    </div>
                </header>
                <div v-show="showMemberChart" id="member-trend-chart">
                    <CustomerGrowthChart :rows="props.customers.trend.rows" :grain="props.customers.trend.grain" />
                </div>
            </article>
        </section>

        <section id="kunjungan" class="overview-section" aria-labelledby="visits-title">
            <div class="overview-section-head">
                <div>
                    <span class="overview-section-kicker">Kunjungan</span>
                    <h2 id="visits-title">Kunjungan toko</h2>
                    <p>Jumlah kunjungan dan pengunjung unik per toko pada periode ini.</p>
                </div>
                <div class="overview-section-links">
                    <Link :href="props.urls.crm">CRM</Link>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div v-for="card in visitCards" :key="card.label" class="col-6 col-xl-4">
                    <article class="overview-kpi" :class="`is-${card.tone}`">
                        <span class="overview-kpi-icon"><i class="mdi" :class="card.icon"></i></span>
                        <div>
                            <span class="overview-kpi-label">{{ card.label }}</span>
                            <strong class="overview-kpi-value">{{ card.value }}</strong>
                            <small>{{ card.hint }}</small>
                        </div>
                    </article>
                </div>
            </div>

            <article class="overview-card">
                <header class="overview-card-head">
                    <div>
                        <h3>Detail Kunjungan per Toko</h3>
                        <p>Kunjungan dan pengunjung unik di setiap toko. Porsi dihitung dari total kunjungan.</p>
                    </div>
                    <span class="overview-card-meta">{{ formatNumber(props.customers.store_visits.length) }} toko</span>
                </header>
                <div v-if="props.customers.store_visits.length" class="table-responsive">
                    <table class="table align-middle mb-0 overview-table">
                        <thead>
                            <tr>
                                <th scope="col">Toko</th>
                                <th scope="col" class="text-end">Kunjungan</th>
                                <th scope="col" class="text-end">Pengunjung Unik</th>
                                <th scope="col">Porsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in props.customers.store_visits" :key="row.store_id">
                                <td><strong>{{ row.store_name }}</strong></td>
                                <td class="text-end">{{ formatNumber(row.visits) }}</td>
                                <td class="text-end">{{ formatNumber(row.unique_visitors) }}</td>
                                <td>
                                    <div class="overview-progress" aria-hidden="true">
                                        <span :style="{ width: `${row.share > 0 ? Math.max(2, row.share) : 0}%` }"></span>
                                    </div>
                                    <small class="overview-cell-note">{{ row.share }}%</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="overview-empty">Belum ada kunjungan toko pada periode ini.</p>
            </article>
        </section>

        <section id="pesanan" class="overview-section" aria-labelledby="orders-title">
            <div class="overview-section-head">
                <div>
                    <span class="overview-section-kicker">Pesanan</span>
                    <h2 id="orders-title">Antrean dan status pesanan</h2>
                    <p>Pesanan ecommerce, permintaan store, dan retur dipisahkan agar mudah ditindaklanjuti.</p>
                </div>
                <div class="overview-section-links">
                    <Link :href="props.urls.orders">Fulfillment & SLA</Link>
                    <Link :href="props.urls.ecommerce_orders">Pesanan Ecommerce</Link>
                    <Link :href="props.urls.store_orders">Permintaan Store</Link>
                    <Link :href="props.urls.store_returns">Retur Store</Link>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div v-for="card in orderCards" :key="card.label" class="col-6 col-xl-3">
                    <article class="overview-kpi" :class="`is-${card.tone}`">
                        <span class="overview-kpi-icon"><i class="mdi" :class="card.icon"></i></span>
                        <div>
                            <span class="overview-kpi-label">{{ card.label }}</span>
                            <strong class="overview-kpi-value">{{ card.value }}</strong>
                            <small>{{ card.hint }}</small>
                        </div>
                    </article>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div v-for="breakdown in orderBreakdowns" :key="breakdown.title" class="col-md-4">
                    <article class="overview-card h-100">
                        <header class="overview-card-head">
                            <div>
                                <h3>{{ breakdown.title }}</h3>
                                <p>{{ breakdown.rows.length }} status tercatat pada periode ini.</p>
                            </div>
                            <span class="overview-card-icon"><i class="mdi" :class="breakdown.icon"></i></span>
                        </header>
                        <div v-if="breakdown.rows.length" class="overview-status-list">
                            <div v-for="row in breakdown.rows" :key="row.status" class="overview-status-item">
                                <div class="overview-status-row">
                                    <span>{{ row.label }}</span>
                                    <strong>{{ formatNumber(row.count) }}</strong>
                                </div>
                                <div class="overview-progress" aria-hidden="true">
                                    <span :style="{ width: `${Math.max(2, row.share)}%` }"></span>
                                </div>
                                <small>{{ row.share }}%</small>
                            </div>
                        </div>
                        <p v-else class="overview-empty">Belum ada data status.</p>
                    </article>
                </div>
            </div>

            <article class="overview-card">
                <header class="overview-card-head">
                    <div>
                        <h3>Perlu Tindak Lanjut (&gt; 24 Jam)</h3>
                        <p>Pesanan yang sudah dibayar/diproses, permintaan store, dan retur yang belum selesai.</p>
                    </div>
                    <span class="overview-card-meta">{{ formatNumber(props.orders.attention.length) }} antrean teratas</span>
                </header>
                <div v-if="props.orders.attention.length" class="table-responsive">
                    <table class="table align-middle mb-0 overview-table overview-attention-table">
                        <thead>
                            <tr>
                                <th scope="col">Jenis</th>
                                <th scope="col">Referensi</th>
                                <th scope="col">Status</th>
                                <th scope="col">Mitra / Lokasi</th>
                                <th scope="col" class="text-end">Umur</th>
                                <th scope="col" class="text-end">Nilai</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in props.orders.attention" :key="row.id">
                                <td><span class="overview-chip">{{ row.type }}</span></td>
                                <td><strong>{{ row.reference }}</strong></td>
                                <td><span class="overview-badge" :class="`is-${statusTone(row.status)}`">{{ row.status_label }}</span></td>
                                <td>{{ row.partner }}</td>
                                <td class="text-end">{{ formatAge(row.age_hours) }}</td>
                                <td class="text-end">{{ row.amount === null ? '-' : formatCurrency(row.amount) }}</td>
                                <td>
                                    <Link v-if="row.action_url" :href="row.action_url" class="overview-action-link">{{ row.action_label }}</Link>
                                    <span v-else>-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="overview-empty">Tidak ada antrean yang melewati 24 jam. Bagus.</p>
                <div class="overview-card-footer">
                    <span>Total permintaan store belum selesai: <strong>{{ formatNumber(props.orders.summary.store_order_open) }}</strong></span>
                    <span>Total retur belum selesai: <strong>{{ formatNumber(props.orders.summary.return_open) }}</strong></span>
                </div>
            </article>
        </section>

        <section id="stok-logistik" class="overview-section" aria-labelledby="inventory-title">
            <div class="overview-section-head">
                <div>
                    <span class="overview-section-kicker">Stok & Logistik</span>
                    <h2 id="inventory-title">Posisi stok dan pergerakan barang</h2>
                    <p>Stok adalah snapshot saat ini; unit = jumlah item, SKU = produk unik. Inbound, outbound, dan retur adalah aktivitas pada periode.</p>
                </div>
                <div class="overview-section-links">
                    <Link :href="props.urls.stock_warehouse">Stok Warehouse</Link>
                    <Link :href="props.urls.stock_store">Stok Cabang</Link>
                    <Link :href="props.urls.expiring">Kedaluwarsa</Link>
                    <Link :href="props.urls.slow_moving">Slow Moving</Link>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div v-for="card in inventoryCards" :key="card.label" class="col-6 col-xl-3">
                    <article class="overview-kpi" :class="`is-${card.tone}`">
                        <span class="overview-kpi-icon"><i class="mdi" :class="card.icon"></i></span>
                        <div>
                            <span class="overview-kpi-label">{{ card.label }}</span>
                            <strong class="overview-kpi-value">{{ card.value }}</strong>
                            <small>{{ card.hint }}</small>
                        </div>
                    </article>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-xl-6">
                    <article class="overview-card h-100">
                        <header class="overview-card-head">
                            <div>
                                <h3>Stok Gudang per Lokasi</h3>
                                <p>Unit item berstatus stok gudang, per gudang.</p>
                            </div>
                            <span class="overview-card-meta">{{ formatNumber(props.inventory.summary.warehouse_units) }} unit</span>
                        </header>
                        <div v-if="props.inventory.warehouse_stock.length" class="table-responsive">
                            <table class="table align-middle mb-0 overview-table">
                                <thead>
                                    <tr><th scope="col">Gudang</th><th scope="col" class="text-end">Unit</th><th scope="col" class="text-end">SKU</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in props.inventory.warehouse_stock" :key="row.location_id">
                                        <td>{{ row.location_name }}</td>
                                        <td class="text-end">{{ formatNumber(row.units) }}</td>
                                        <td class="text-end">{{ formatNumber(row.skus) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="overview-empty">Belum ada stok gudang.</p>
                    </article>
                </div>
                <div class="col-xl-6">
                    <article class="overview-card h-100">
                        <header class="overview-card-head">
                            <div>
                                <h3>Stok Cabang per Toko</h3>
                                <p>Unit item berstatus stok atau display toko.</p>
                            </div>
                            <span class="overview-card-meta">{{ formatNumber(props.inventory.summary.store_units) }} unit</span>
                        </header>
                        <div v-if="props.inventory.store_stock.length" class="table-responsive">
                            <table class="table align-middle mb-0 overview-table">
                                <thead>
                                    <tr><th scope="col">Toko</th><th scope="col" class="text-end">Unit</th><th scope="col" class="text-end">SKU</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in props.inventory.store_stock" :key="row.location_id">
                                        <td>{{ row.location_name }}</td>
                                        <td class="text-end">{{ formatNumber(row.units) }}</td>
                                        <td class="text-end">{{ formatNumber(row.skus) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="overview-empty">Belum ada stok cabang.</p>
                    </article>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-xl-7">
                    <article class="overview-card h-100">
                        <header class="overview-card-head">
                            <div>
                                <h3>Stok di Bawah Batas Minimum</h3>
                                <p>Prioritas restock berdasarkan selisih terhadap batas stok.</p>
                            </div>
                            <span class="overview-card-meta">{{ formatNumber(props.inventory.summary.low_stock_alerts) }} lokasi</span>
                        </header>
                        <div v-if="props.inventory.low_stock.length" class="table-responsive">
                            <table class="table align-middle mb-0 overview-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Lokasi</th>
                                        <th scope="col">Produk</th>
                                        <th scope="col" class="text-end">Tersedia</th>
                                        <th scope="col" class="text-end">Minimum</th>
                                        <th scope="col" class="text-end">Kurang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in props.inventory.low_stock" :key="`${row.location_name}-${row.product_code}`">
                                        <td>
                                            <span class="overview-chip">{{ row.location_type }}</span>
                                            <small class="overview-cell-note">{{ row.location_name }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ row.product_name }}</strong>
                                            <small class="overview-cell-note">{{ row.product_code }}</small>
                                        </td>
                                        <td class="text-end">{{ formatNumber(row.available) }}</td>
                                        <td class="text-end">{{ formatNumber(row.threshold) }}</td>
                                        <td class="text-end"><span class="overview-badge is-warning">-{{ formatNumber(row.shortfall) }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="overview-empty">Tidak ada stok di bawah batas minimum.</p>
                    </article>
                </div>

                <div class="col-xl-5">
                    <article class="overview-card mb-3">
                        <header class="overview-card-head">
                            <div>
                                <h3>Aktivitas Logistik</h3>
                                <p>Jumlah dokumen dan unit pada periode ini.</p>
                            </div>
                        </header>
                        <div class="overview-logistics">
                            <Link v-for="item in logisticsItems" :key="item.label" :href="item.url" class="overview-logistics-item">
                                <span class="overview-logistics-icon"><i class="mdi" :class="item.icon"></i></span>
                                <span class="overview-logistics-label">{{ item.label }}</span>
                                <strong>{{ formatNumber(item.data.documents) }}</strong>
                                <small>{{ formatNumber(item.data.units) }} unit</small>
                            </Link>
                        </div>
                    </article>

                    <article class="overview-card">
                        <header class="overview-card-head">
                            <div>
                                <h3>Antrean Logistik</h3>
                                <p>Posisi saat ini, tidak tergantung periode.</p>
                            </div>
                        </header>
                        <div class="overview-queue-list">
                            <Link v-for="item in pendingItems" :key="item.label" :href="item.url" class="overview-queue-item">
                                <span>{{ item.label }}</span>
                                <strong>{{ formatNumber(item.value) }}</strong>
                            </Link>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.overview-page {
    color: var(--app-ink);
}

.overview-filter {
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
}

.overview-preset-label {
    display: block;
    margin-bottom: 0.3rem;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.overview-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
}

.overview-period-select {
    width: auto;
    min-width: 9.5rem;
    max-width: 12rem;
}

.overview-period-note {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--app-border);
    color: var(--app-muted);
    font-size: var(--app-text-sm);
}

.overview-guide {
    padding: 0.9rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface-2);
}

.overview-guide-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--app-ink);
    text-align: left;
}

.overview-guide-toggle:hover .overview-guide-head {
    color: var(--app-accent-strong);
}

.overview-guide-head {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--app-accent-ink);
    font-size: var(--app-text-base);
}

.overview-guide-toggle > .mdi {
    flex: 0 0 auto;
    color: var(--app-muted);
    font-size: 1.1rem;
}

.overview-guide-content,
#overview-guide-content {
    margin-top: 0.65rem;
}

.overview-guide p {
    margin: 0;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    line-height: var(--app-leading-normal);
}

.overview-guide strong {
    color: var(--app-ink);
}

.overview-guide-note {
    margin: 0.65rem 0 0;
    padding-top: 0.6rem;
    border-top: 1px solid var(--app-border);
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    line-height: var(--app-leading-normal);
}

.overview-anchors {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 1rem;
}

.overview-section {
    margin-bottom: 1.6rem;
    scroll-margin-top: 5rem;
}

.overview-section-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.8rem;
}

.overview-section-kicker {
    display: block;
    margin-bottom: 0.2rem;
    color: var(--app-accent-ink);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-black);
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.overview-section-head h2 {
    margin: 0 0 0.2rem;
    font-size: var(--app-text-xl);
    font-weight: var(--app-weight-bold);
}

.overview-section-head p {
    margin: 0;
    color: var(--app-muted);
    font-size: var(--app-text-base);
}

.overview-section-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.overview-section-links a {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    padding: 0.3rem 0.55rem;
    border-radius: var(--app-radius-sm);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-bold);
    text-decoration: none;
    transition: background-color var(--app-dur-fast) var(--app-ease-out);
}

.overview-section-links a:hover {
    background: var(--app-surface-3);
    text-decoration: underline;
}

.overview-payment-breakdown {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.9rem;
    margin-bottom: 0.75rem;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
}

.overview-payment-breakdown span {
    display: inline-flex;
    align-items: baseline;
    gap: 0.3rem;
}

.overview-payment-breakdown strong {
    color: var(--app-ink);
    font-variant-numeric: tabular-nums;
}

.overview-kpi {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    height: 100%;
    padding: 0.9rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
}

.overview-kpi-icon {
    display: grid;
    flex: 0 0 auto;
    width: 2.2rem;
    height: 2.2rem;
    place-items: center;
    border-radius: var(--app-radius-sm);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: 1.1rem;
}

.overview-kpi-label {
    display: block;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.overview-kpi-value {
    display: block;
    margin-top: 0.1rem;
    font-size: var(--app-text-xl);
    font-weight: var(--app-weight-black);
    font-variant-numeric: tabular-nums;
    line-height: 1.25;
    overflow-wrap: anywhere;
}

.overview-kpi small {
    display: block;
    margin-top: 0.15rem;
    color: var(--app-muted);
    font-size: var(--app-text-xs);
}

.overview-kpi.is-success .overview-kpi-icon {
    background: var(--app-success-bg);
    color: var(--app-success-ink);
}

.overview-kpi.is-warning .overview-kpi-icon {
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
}

.overview-kpi.is-danger .overview-kpi-icon {
    background: var(--app-danger-bg);
    color: var(--app-danger-ink);
}

.overview-kpi.is-info .overview-kpi-icon {
    background: var(--app-info-bg);
    color: var(--app-info-ink);
}

.overview-card {
    padding: 1rem 1.05rem;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-xl);
    background: var(--app-surface);
}

.overview-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.overview-card-head h3 {
    margin: 0 0 0.15rem;
    font-size: var(--app-text-lg);
    font-weight: var(--app-weight-bold);
}

.overview-card-head p {
    margin: 0;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
}

.overview-card-meta {
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    font-weight: var(--app-weight-bold);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.overview-card-actions {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.overview-card-toggle {
    display: grid;
    place-items: center;
    width: 2rem;
    height: 2rem;
    padding: 0;
    border: 1px solid var(--app-border);
    border-radius: var(--app-radius-sm);
    background: var(--app-surface);
    color: var(--app-accent-ink);
    font-size: 1.1rem;
}

.overview-card-toggle:hover {
    background: var(--app-accent-soft);
}

.overview-attention-table {
    min-width: 760px;
}

.overview-action-link {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    color: var(--app-accent-ink);
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-bold);
    text-decoration: none;
}

.overview-action-link:hover {
    text-decoration: underline;
}

.overview-card-icon {
    display: grid;
    flex: 0 0 auto;
    width: 2rem;
    height: 2rem;
    place-items: center;
    border-radius: var(--app-radius-sm);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.overview-card-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding-top: 0.7rem;
    border-top: 1px solid var(--app-border);
    color: var(--app-muted);
    font-size: var(--app-text-sm);
}

.overview-card-footer strong {
    color: var(--app-ink);
}

.overview-chart {
    overflow-x: auto;
    padding-bottom: 0.2rem;
}

.overview-chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.8rem;
    margin-bottom: 0.55rem;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
}

.overview-chart-legend span {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.overview-chart-scale {
    margin-left: auto;
    font-variant-numeric: tabular-nums;
}

.overview-chart-swatch {
    width: 0.6rem;
    height: 0.6rem;
    border-radius: 2px;
    background: var(--app-accent);
}

.overview-chart-swatch.is-gross {
    background: var(--app-muted);
    opacity: 0.45;
}

.overview-chart-grid {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: minmax(2rem, 1fr);
    gap: 0.35rem;
    min-width: 560px;
    align-items: end;
}

.overview-chart-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    min-width: 0;
}

.overview-chart-track {
    display: flex;
    align-items: flex-end;
    gap: 2px;
    width: 100%;
    height: 8rem;
    border-radius: var(--app-radius-xs) var(--app-radius-xs) 0 0;
    background: var(--app-surface-2);
}

.overview-chart-bar {
    flex: 1 1 0;
    min-width: 0;
    border-radius: var(--app-radius-xs) var(--app-radius-xs) 0 0;
    transition: height var(--app-dur-base) var(--app-ease-out);
}

.overview-chart-bar.is-gross {
    background: var(--app-muted);
    opacity: 0.45;
}

.overview-chart-bar.is-net {
    background: var(--app-accent);
}

.overview-chart-label {
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    white-space: nowrap;
}

.overview-channel-list {
    display: grid;
    gap: 0.9rem;
}

.overview-channel-row,
.overview-status-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
    font-size: var(--app-text-base);
}

.overview-progress {
    overflow: hidden;
    height: 0.35rem;
    border-radius: var(--app-radius-pill);
    background: var(--app-surface-3);
}

.overview-progress span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--app-accent);
}

.overview-channel small,
.overview-status-item small {
    display: block;
    margin-top: 0.2rem;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
}

.overview-status-list {
    display: grid;
    gap: 0.55rem;
}

.overview-product-list {
    display: grid;
    gap: 0.35rem;
}

.overview-product {
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--app-border);
}

.overview-product:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}

.overview-product-toggle {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    min-height: 44px;
    padding: 0.35rem 0.4rem;
    border: 0;
    border-radius: var(--app-radius-sm);
    background: transparent;
    color: var(--app-ink);
    text-align: left;
}

.overview-product-toggle:hover {
    background: var(--app-surface-2);
}

.overview-product-toggle > .mdi {
    color: var(--app-muted);
}

.overview-product-name strong,
.overview-product-name small {
    display: block;
}

.overview-product small,
.overview-cell-note {
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
}

.overview-product-qty {
    flex: 0 0 auto;
    padding: 0.2rem 0.45rem;
    border-radius: var(--app-radius-pill);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: var(--app-text-xs);
    font-weight: var(--app-weight-bold);
    white-space: nowrap;
}

.overview-product-stores {
    padding: 0.45rem 0.4rem 0.15rem;
    margin-top: 0.25rem;
    border-top: 1px solid var(--app-border);
}

.overview-product-stores ul {
    display: grid;
    gap: 0.35rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.overview-product-stores li {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    align-items: baseline;
    gap: 0.5rem;
    font-size: var(--app-text-sm);
}

.overview-product-stores li span {
    min-width: 0;
    overflow-wrap: anywhere;
}

.overview-product-stores li strong {
    font-variant-numeric: tabular-nums;
}

.overview-product-stores li small {
    font-size: var(--app-text-2xs);
    white-space: nowrap;
}

.overview-table {
    min-width: 620px;
}

.overview-table th {
    padding: 0.55rem 0.65rem;
}

.overview-table td {
    padding: 0.65rem;
    border-color: var(--app-border);
    font-size: var(--app-text-sm);
    font-variant-numeric: tabular-nums;
    vertical-align: middle;
}

.overview-chip {
    display: inline-block;
    padding: 0.15rem 0.45rem;
    border-radius: var(--app-radius-pill);
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
}

.overview-badge {
    display: inline-block;
    padding: 0.16rem 0.45rem;
    border-radius: var(--app-radius-pill);
    font-size: var(--app-text-2xs);
    font-weight: var(--app-weight-bold);
}

.overview-badge.is-success {
    background: var(--app-success-bg);
    color: var(--app-success-ink);
}

.overview-badge.is-warning {
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
}

.overview-badge.is-danger {
    background: var(--app-danger-bg);
    color: var(--app-danger-ink);
}

.overview-badge.is-accent {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.overview-badge.is-neutral {
    background: var(--app-surface-2);
    color: var(--app-muted);
}

.overview-logistics {
    display: grid;
    gap: 0.4rem;
}

.overview-logistics-item {
    display: grid;
    grid-template-columns: 1.8rem 1fr auto auto;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem;
    border-radius: var(--app-radius-sm);
    color: var(--app-ink);
    text-decoration: none;
}

.overview-logistics-item:hover {
    background: var(--app-surface-2);
}

.overview-logistics-icon {
    display: grid;
    width: 1.8rem;
    height: 1.8rem;
    place-items: center;
    border-radius: var(--app-radius-xs);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.overview-logistics-label {
    font-size: var(--app-text-sm);
}

.overview-logistics-item strong {
    font-size: var(--app-text-sm);
    font-variant-numeric: tabular-nums;
}

.overview-logistics-item small {
    grid-column: 2 / -1;
    color: var(--app-muted);
    font-size: var(--app-text-2xs);
}

.overview-queue-list {
    display: grid;
    gap: 0.35rem;
}

.overview-queue-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.55rem 0.6rem;
    border-radius: var(--app-radius-sm);
    background: var(--app-surface-2);
    color: var(--app-ink);
    font-size: var(--app-text-sm);
    text-decoration: none;
}

.overview-queue-item:hover {
    background: var(--app-accent-soft);
}

.overview-queue-item span {
    color: var(--app-muted);
}

.overview-empty {
    margin: 0;
    padding: 1.5rem 0.5rem;
    color: var(--app-muted);
    font-size: var(--app-text-sm);
    text-align: center;
}

@media (max-width: 991.98px) {
    .overview-section-head {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
