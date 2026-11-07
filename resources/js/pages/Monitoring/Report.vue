<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import StockReservationDetailModal from '@/components/StockReservationDetailModal.vue';
import { downloadExcelTable } from '@/composables/useExcelExport';

defineOptions({ layout: AppLayout });

type Row = Record<string, any>;

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginator {
    data: Row[];
    links: PaginatorLink[];
    from?: number | null;
    to?: number | null;
    total?: number;
    per_page?: number;
    current_page?: number;
    last_page?: number;
}

interface Props {
    title: string;
    description?: string;
    data: Record<string, any>;
    filters: Record<string, any> | any[];
    urls: {
        index: string;
        export?: string;
        exports?: Record<string, string>;
        operations?: string;
        reservations?: string;
        approve?: string;
        reject?: string;
        email_alert_settings?: string;
    };
}

interface FinanceReconciliation {
    expected_net: number;
    actual_net: number;
    difference: number;
    clamped_discount: number;
    unexplained_difference: number;
    is_balanced: boolean;
}

interface FinanceBreakdownValues {
    gross_sales: number;
    product_discount: number;
    net_after_product_discount: number;
    voucher_discount: number;
    net_sales: number;
    base_price: number;
    gross_profit: number;
    gross_margin_percent: number;
    product_discount_percent: number;
    voucher_discount_percent: number;
    effective_discount_percent: number;
    transactions: number;
    qty: number;
    average_net_per_transaction: number;
    reconciliation: FinanceReconciliation;
}

interface FinanceChannel extends FinanceBreakdownValues {
    key: string;
    label: string;
}

interface FinanceSummary extends FinanceBreakdownValues {
    channels?: FinanceChannel[];
}

interface FilterOption {
    value: string;
    label: string;
}

type FilterType = 'text' | 'date' | 'number' | 'select';

interface FilterField {
    key: string;
    label: string;
    type: FilterType;
    options: FilterOption[];
    placeholder: string;
    wide: boolean;
}

interface PeriodFilter {
    value: string;
    label: string;
    year: string;
    currentYear: string;
    options: FilterOption[];
    years: string[];
    rangeLabel: string;
    autoRefresh: boolean;
}

interface Dataset {
    key: string;
    label: string;
    description: string;
    rows: Row[];
    paginator: Paginator | null;
}

interface TableColumn {
    key: string;
    label: string;
    align: 'text-start' | 'text-end';
    wrap: boolean;
}

type CellKind = 'text' | 'number' | 'currency' | 'percent' | 'date' | 'badge';

interface TableCell {
    text: string;
    kind: CellKind;
    tone: string;
    title: string;
    wrap: boolean;
}

const props = defineProps<Props>();

const reportDescription = computed(() => {
    const description = String(props.description ?? '').trim();

    if (description !== '' && description.toLowerCase() !== 'laporan monitoring.') {
        return description;
    }

    return 'Ringkasan dan tabel mengikuti filter yang sama. Gunakan tab data untuk melihat rincian; angka antar tab tidak dijumlahkan.';
});
const page = usePage();

const isObject = (value: unknown): value is Record<string, any> =>
    Boolean(value) && typeof value === 'object' && !Array.isArray(value);

const isPaginator = (value: unknown): value is Paginator =>
    isObject(value) && Array.isArray(value.data) && Array.isArray(value.links);

const periodFilter = computed<PeriodFilter | null>(() => {
    const value = props.data?.periodFilter;

    if (!isObject(value) || !Array.isArray(value.options) || !Array.isArray(value.years)) return null;

    return value as PeriodFilter;
});

const periodOptions = computed<FilterOption[]>(() => {
    const filter = periodFilter.value;

    if (!filter) return [];

    return filter.options.map((option) => {
        if (option.value !== 'year') return option;

        return {
            ...option,
            label: filter.year && filter.year !== filter.currentYear ? `Tahun ${filter.year}` : 'Tahun ini',
        };
    });
});

const FILTER_ALIASES: Record<string, string> = {
    storeId: 'store_id',
    warehouseId: 'warehouse_id',
    warehouseSourceId: 'warehouse_source_id',
    sourceWarehouseId: 'source_warehouse_id',
    productId: 'product_id',
    brandId: 'brand_id',
    categoryId: 'category_id',
    customerId: 'customer_id',
    referrerId: 'referrer_id',
    paymentMethodId: 'payment_method_id',
    paymentMethod: 'payment_method_id',
    supplierId: 'supplier_id',
    courierId: 'courier_id',
    cashierId: 'cashier_id',
    userId: 'user_id',
    shiftId: 'shift_id',
    membershipId: 'membership_id',
    locationId: 'location_id',
    locationKey: 'location_key',
    conditionId: 'condition_id',
    promoId: 'promo_id',
    discountId: 'discount_id',
    voucherId: 'voucher_id',
    orderStatus: 'order_status',
    paymentStatus: 'payment_status',
    qcStatus: 'qc_status',
    inboundType: 'inbound_type',
    outboundType: 'outbound_type',
    periodStatus: 'period_status',
    isActive: 'is_active',
    registrationChannel: 'registration_channel',
    registrationStoreId: 'registration_store_id',
    attendanceType: 'attendance_type',
    trackingStatus: 'tracking_status',
    anomalyType: 'anomaly_type',
    handledType: 'handled_type',
    startDate: 'start_date',
    endDate: 'end_date',
    dateFrom: 'date_from',
    dateTo: 'date_to',
    fromDate: 'from_date',
    toDate: 'to_date',
    startBirthDate: 'start_birth_date',
    endBirthDate: 'end_birth_date',
    birthdayMonth: 'birthday_month',
    minMoveCount: 'min_move_count',
    minDamageCount: 'min_damage_count',
    perPage: 'per_page',
    customerName: 'customer_name',
    waNumber: 'wa_number',
    orderNumber: 'order_number',
    postalCode: 'postal_code',
    provinceCode: 'province_code',
    regencyCode: 'regency_code',
    districtCode: 'district_code',
    villageCode: 'village_code',
    itemId: 'item_id',
    requestedBy: 'requested_by',
    printedBy: 'printed_by',
    authorizedBy: 'authorized_by',
    groupBy: 'group_by',
    mutationType: 'mutation_type',
    appType: 'app_type',
    exceptionOnly: 'exception_only',
};

const ALLOWED_FILTERS = new Set([
    'q',
    'keyword',
    'search',
    'name',
    'email',
    'wa_number',
    'customer_name',
    'order_number',
    'postal_code',
    'item_id',
    'store_id',
    'warehouse_id',
    'warehouse_source_id',
    'source_warehouse_id',
    'product_id',
    'brand_id',
    'category_id',
    'customer_id',
    'referrer_id',
    'payment_method_id',
    'supplier_id',
    'courier_id',
    'cashier_id',
    'user_id',
    'shift_id',
    'membership_id',
    'location_id',
    'location_key',
    'condition_id',
    'promo_id',
    'discount_id',
    'voucher_id',
    'requested_by',
    'printed_by',
    'authorized_by',
    'status',
    'order_status',
    'payment_status',
    'qc_status',
    'reconciliation_status',
    'inbound_type',
    'outbound_type',
    'period_status',
    'is_active',
    'is_paid',
    'registration_channel',
    'registration_store_id',
    'attendance_type',
    'tracking_status',
    'anomaly_type',
    'handled_type',
    'item_qc_status',
    'release_status',
    'shipping_status',
    'document_status',
    'source',
    'action',
    'type',
    'mode',
    'scope',
    'channel',
    'group_by',
    'promo',
    'aging_bucket',
    'mutation_type',
    'app_type',
    'device',
    'platform',
    'gender',
    'level',
    'priority',
    'source_type',
    'min_age_days',
    'has_activity',
    'opname_status',
    'movement_type',
    'direction',
    'min',
    'min_move_count',
    'min_damage_count',
    'per_page',
    'exception_only',
    'slow_max_qty',
    'expiry_days',
    'province_code',
    'regency_code',
    'district_code',
    'village_code',
    'birthday_month',
    'start_date',
    'end_date',
    'date_from',
    'date_to',
    'from_date',
    'to_date',
    'valid_from',
    'valid_to',
    'start_birth_date',
    'end_birth_date',
    'from',
    'to',
]);

const FILTER_LABELS: Record<string, string> = {
    q: 'Pencarian',
    keyword: 'Pencarian',
    search: 'Pencarian',
    name: 'Nama',
    email: 'Email',
    wa_number: 'Nomor WA',
    customer_name: 'Nama Customer',
    midtrans_payment_types: 'Payment Type',
    raw_payment_status: 'Status Pembayaran (Raw)',
    visit_payment_status: 'Status Pembayaran Kunjungan',
    payment_method_type: 'Tipe Metode',
    transaction_count: 'Jumlah Transaksi',
    total_cash_amount: 'Total Tunai',
    total_non_cash_amount: 'Total Non-Tunai',
    total_cash_transactions: 'Transaksi Tunai',
    total_non_cash_transactions: 'Transaksi Non-Tunai',
    total_receivable_amount: 'Piutang Rekanan',
    order_number: 'No. Pesanan',
    postal_code: 'Kode Pos',
    item_id: 'ID Item',
    store_id: 'Cabang',
    warehouse_id: 'Gudang',
    warehouse_source_id: 'Gudang Asal',
    source_warehouse_id: 'Gudang Asal',
    product_id: 'Produk',
    brand_id: 'Brand',
    category_id: 'Kategori',
    customer_id: 'Customer',
    referrer_id: 'Referrer',
    payment_method_id: 'Metode Pembayaran',
    supplier_id: 'Supplier',
    courier_id: 'Kurir',
    cashier_id: 'Kasir',
    user_id: 'Pengguna',
    shift_id: 'Shift',
    membership_id: 'Membership',
    location_id: 'Lokasi',
    location_key: 'Lokasi',
    condition_id: 'Kondisi',
    promo_id: 'Promo',
    discount_id: 'Diskon',
    voucher_id: 'Voucher',
    requested_by: 'Diminta Oleh',
    printed_by: 'Dicetak Oleh',
    authorized_by: 'Diotorisasi Oleh',
    status: 'Status',
    order_status: 'Status Pesanan',
    payment_status: 'Status Pembayaran',
    qc_status: 'Status QC',
    reconciliation_status: 'Status Rekonsiliasi',
    inbound_type: 'Tipe Inbound',
    outbound_type: 'Tipe Outbound',
    period_status: 'Status Periode',
    is_active: 'Status Aktif',
    registration_channel: 'Channel Registrasi',
    registration_store_id: 'Cabang Registrasi',
    attendance_type: 'Tipe Kehadiran',
    tracking_status: 'Status Tracking',
    anomaly_type: 'Tipe Anomali',
    handled_type: 'Penanggung Jawab',
    item_qc_status: 'Status QC Item',
    release_status: 'Status Rilis',
    shipping_status: 'Status Pengiriman',
    document_status: 'Status Dokumen',
    source: 'Sumber',
    action: 'Aksi',
    type: 'Tipe',
    mode: 'Mode',
    scope: 'Cakupan',
    channel: 'Channel',
    group_by: 'Kelompokkan',
    promo: 'Promo',
    aging_bucket: 'Rentang Aging',
    slow_max_qty: 'Batas Qty Slow Moving',
    expiry_days: 'Batas Hari Kedaluwarsa',
    mutation_type: 'Tipe Mutasi',
    app_type: 'Tipe Aplikasi',
    device: 'Device',
    platform: 'Platform',
    gender: 'Gender',
    level: 'Level Wilayah',
    priority: 'Prioritas',
    source_type: 'Jenis Transaksi',
    min_age_days: 'Umur Minimal (hari)',
    has_activity: 'Cakupan Akun',
    opname_status: 'Status Opname',
    movement_type: 'Jenis Pergerakan',
    direction: 'Arah Pergerakan',
    min: 'Nilai Minimal',
    min_move_count: 'Min. Perpindahan',
    min_damage_count: 'Min. Kerusakan',
    per_page: 'Baris',
    exception_only: 'Tampilkan',
    province_code: 'Provinsi',
    regency_code: 'Kabupaten/Kota',
    district_code: 'Kecamatan',
    village_code: 'Desa/Kelurahan',
    birthday_month: 'Bulan Lahir',
    start_date: 'Dari Tanggal',
    end_date: 'Sampai Tanggal',
    date_from: 'Dari Tanggal',
    date_to: 'Sampai Tanggal',
    from_date: 'Dari Tanggal',
    to_date: 'Sampai Tanggal',
    valid_from: 'Berlaku Dari',
    valid_to: 'Berlaku Sampai',
    start_birth_date: 'Lahir Dari',
    end_birth_date: 'Lahir Sampai',
    from: 'Dari Tanggal',
    to: 'Sampai Tanggal',
};

const SEARCH_KEYS = new Set([
    'q',
    'keyword',
    'search',
    'name',
    'email',
    'wa_number',
    'customer_name',
    'order_number',
    'postal_code',
    'item_id',
    'province_code',
    'regency_code',
    'district_code',
    'village_code',
]);

const DATE_KEYS = new Set([
    'start_date',
    'end_date',
    'date_from',
    'date_to',
    'from_date',
    'to_date',
    'valid_from',
    'valid_to',
    'start_birth_date',
    'end_birth_date',
    'from',
    'to',
]);

const NUMBER_KEYS = new Set(['min', 'min_move_count', 'min_damage_count', 'min_age_days', 'slow_max_qty']);

const OPTION_SOURCES: Record<string, { keys: string[]; labels?: string[] }> = {
    store_id: { keys: ['stores'] },
    warehouse_id: { keys: ['warehouses'] },
    warehouse_source_id: { keys: ['warehouses'] },
    source_warehouse_id: { keys: ['warehouses'] },
    product_id: { keys: ['products'] },
    brand_id: { keys: ['brands'] },
    category_id: { keys: ['categories'] },
    customer_id: { keys: ['customers'] },
    referrer_id: { keys: ['referrers'] },
    payment_method_id: { keys: ['paymentMethods'] },
    supplier_id: { keys: ['suppliers'] },
    courier_id: { keys: ['couriers'] },
    cashier_id: { keys: ['cashiers', 'users'] },
    user_id: { keys: ['users'] },
    requested_by: { keys: ['users'] },
    printed_by: { keys: ['users'] },
    authorized_by: { keys: ['users'] },
    shift_id: { keys: ['shifts'] },
    membership_id: { keys: ['memberships'] },
    location_id: { keys: ['locations'] },
    location_key: { keys: ['locations'] },
    condition_id: { keys: ['conditions'] },
    promo_id: { keys: ['promos'] },
    discount_id: { keys: ['discounts'] },
    voucher_id: { keys: ['vouchers'] },
    status: { keys: ['statuses', 'statusList', 'orderStatuses'] },
    order_status: { keys: ['orderStatuses', 'statuses'] },
    payment_status: { keys: ['paymentStatuses'] },
    qc_status: { keys: ['qcStatuses'], labels: ['qcStatusLabels'] },
    reconciliation_status: { keys: ['reconciliationStatuses'] },
    inbound_type: { keys: ['inboundTypes'], labels: ['inboundTypeLabels'] },
    outbound_type: { keys: ['outboundTypes'], labels: ['outboundTypeLabels'] },
    type: { keys: ['types'] },
    mutation_type: { keys: ['mutationTypes'] },
    app_type: { keys: ['appTypes'] },
    device: { keys: ['devices'] },
    platform: { keys: ['platforms'] },
    registration_channel: { keys: ['registrationChannels'] },
    registration_store_id: { keys: ['stores'] },
    province_code: { keys: ['provinces'] },
    gender: { keys: ['genders'] },
    promo: { keys: ['promoOptions'] },
    handled_type: { keys: ['handledTypes'] },
    action: { keys: ['actions'] },
    mode: { keys: ['modeOptions'] },
    item_qc_status: { keys: [] },
    release_status: { keys: [] },
    shipping_status: { keys: [] },
    document_status: { keys: ['documentStatuses'] },
    source: { keys: ['sources'] },
    attendance_type: { keys: [] },
    tracking_status: { keys: [] },
    anomaly_type: { keys: [] },
    period_status: { keys: [] },
    is_active: { keys: [] },
    exception_only: { keys: [] },
    scope: { keys: [] },
    channel: { keys: [] },
    group_by: { keys: [] },
    birthday_month: { keys: [] },
    level: { keys: ['levelOptions'] },
    priority: { keys: ['priorityOptions'] },
    source_type: { keys: ['sourceTypes'] },
    movement_type: { keys: ['movementTypes'] },
    direction: { keys: ['directionOptions'] },
    opname_status: { keys: ['statuses'] },
};

const PRESET_OPTIONS: Record<string, FilterOption[]> = {
    item_qc_status: [
        { value: 'all_checked', label: 'Semua sudah dicek' },
        { value: 'pending', label: 'Masih ada yang pending' },
    ],
    release_status: [
        { value: 'released', label: 'Sudah dirilis' },
        { value: 'unreleased', label: 'Belum dirilis' },
    ],
    qc_status: [
        { value: 'passed', label: 'Lolos QC' },
        { value: 'pending', label: 'Menunggu QC' },
    ],
    shipping_status: [
        { value: 'with_shipping', label: 'Punya nomor resi' },
        { value: 'without_shipping', label: 'Tanpa nomor resi' },
    ],
    reconciliation_status: [
        { value: 'not_recorded', label: 'Belum tercatat di stok toko' },
        { value: 'partial', label: 'Tercatat sebagian' },
        { value: 'recorded', label: 'Sudah tercatat' },
    ],
    source: [
        { value: 'ecommerce', label: 'Ecommerce' },
        { value: 'store_cart', label: 'Store cart' },
        { value: 'store_return', label: 'Store return' },
        { value: 'other', label: 'Lainnya' },
    ],
    attendance_type: [
        { value: 'late', label: 'Terlambat' },
        { value: 'overtime', label: 'Lembur' },
        { value: 'late_overtime', label: 'Terlambat dan lembur' },
        { value: 'not_clock_in', label: 'Belum clock in' },
        { value: 'not_clock_out', label: 'Belum clock out' },
    ],
    tracking_status: [
        { value: 'inbound_warehouse', label: 'Inbound Warehouse' },
        { value: 'in_warehouse', label: 'In Warehouse' },
        { value: 'stock_warehouse', label: 'Stock Warehouse' },
        { value: 'outbound_warehouse', label: 'Outbound Warehouse' },
        { value: 'inbound_store', label: 'Inbound Store' },
        { value: 'stock_store', label: 'Stock Store' },
        { value: 'display_store', label: 'Display Store' },
        { value: 'sold', label: 'Sold' },
    ],
    anomaly_type: [
        { value: 'frequent_move', label: 'Sering pindah lokasi' },
        { value: 'location_mismatch', label: 'Lokasi tidak sinkron' },
        { value: 'frequent_damage', label: 'Kondisi sering rusak' },
        { value: 'repaired_then_damaged', label: 'Diperbaiki lalu rusak lagi' },
        { value: 'high_risk', label: 'Risiko tinggi' },
    ],
    period_status: [
        { value: 'active', label: 'Aktif' },
        { value: 'not_started', label: 'Belum mulai' },
        { value: 'expired', label: 'Kedaluwarsa' },
        { value: 'no_period', label: 'Tanpa periode' },
    ],
    is_active: [
        { value: '1', label: 'Aktif' },
        { value: '0', label: 'Tidak aktif' },
    ],
    expiry_days: [
        { value: '30', label: '30 hari' },
        { value: '90', label: '90 hari' },
        { value: '180', label: '180 hari' },
        { value: '365', label: '365 hari' },
        { value: '730', label: '730 hari' },
    ],
    exception_only: [{ value: '1', label: 'Hanya data anomali' }],
    scope: [
        { value: 'warehouse', label: 'Warehouse' },
        { value: 'store', label: 'Store' },
    ],
    channel: [
        { value: 'store', label: 'Store' },
        { value: 'ecommerce', label: 'Ecommerce' },
    ],
    group_by: [
        { value: 'product', label: 'Produk' },
        { value: 'daily', label: 'Harian' },
        { value: 'weekly', label: 'Mingguan' },
        { value: 'monthly', label: 'Bulanan' },
    ],
    has_activity: [
        { value: '1', label: 'Hanya akun aktif' },
        { value: '0', label: 'Semua akun' },
    ],
    aging_bucket: [
        { value: '0_30', label: '0 - 30 hari' },
        { value: '31_90', label: '31 - 90 hari' },
        { value: '91_180', label: '91 - 180 hari' },
        { value: '181_365', label: '181 - 365 hari' },
        { value: 'gt_365', label: 'Lebih dari 365 hari' },
    ],
    registration_channel: [
        { value: 'store', label: 'Store' },
        { value: 'ecommerce', label: 'Ecommerce' },
    ],
    payment_status: [
        { value: 'paid', label: 'Paid' },
        { value: 'pending', label: 'Pending' },
        { value: 'failed', label: 'Failed' },
        { value: 'cancelled', label: 'Cancelled' },
    ],
    order_status: [
        { value: 'pending', label: 'Pending' },
        { value: 'paid', label: 'Paid' },
        { value: 'processing', label: 'Processing' },
        { value: 'processed', label: 'Processed' },
        { value: 'shipped', label: 'Shipped' },
        { value: 'completed', label: 'Completed' },
        { value: 'cancelled', label: 'Cancelled' },
        { value: 'refunded', label: 'Refunded' },
    ],
    handled_type: [
        { value: 'store', label: 'Store' },
        { value: 'warehouse', label: 'Warehouse' },
    ],
    birthday_month: Array.from({ length: 12 }, (_, index) => ({
        value: String(index + 1),
        label: new Intl.DateTimeFormat('id-ID', { month: 'long' }).format(new Date(2024, index, 1)),
    })),
};

const DATASET_LABELS: Record<string, string> = {
    rows: 'Data Utama',
    items: 'Data Utama',
    logs: 'Riwayat Aktivitas',
    fraudAlerts: 'Peringatan Fraud',
    checks: 'Hasil Pemeriksaan Data',
    slow: 'Produk Lambat Terjual',
    fast: 'Produk Cepat Terjual',
    dead: 'Produk Tidak Terjual',
    expiring: 'Mendekati Kedaluwarsa',
    promoRows: 'Ringkasan Promo & Voucher',
    pointMutationRows: 'Poin per Customer',
    promoUsageRows: 'Promo Usage',
    redeemPointRows: 'Redeem Point',
    supplierRows: 'Ringkasan Kinerja Supplier',
    productRisks: 'Produk Berisiko',
    monthlyTrend: 'Tren Bulanan',
    detailItems: 'Rincian Item',
    detailRows: 'Rincian Data',
    latestRows: 'Data Terbaru',
    topReferrers: 'Referrer Teratas',
    topMembers: 'Member Teratas',
    topBranches: 'Cabang Teratas',
    tierDistribution: 'Sebaran Tier Membership',
    barriers: 'Batas Stok (Barrier)',
    ecommerce: 'Pesanan Ecommerce',
    ecommerceHandled: 'Ecommerce Handled',
    storeOrders: 'Store Order',
    storeReturns: 'Store Return',
    payment_breakdown: 'Rincian Metode Pembayaran',
    frequentRequesters: 'Paling Sering Mengajukan',
    frequentReprinters: 'Paling Sering Cetak Ulang',
    topCustomerRows: 'Customer Teratas',
    latestReferralRows: 'Referral Terbaru',
    scheduleRows: 'Jadwal Shift',
    topAgingStore: 'Aging di Cabang',
    topAgingWarehouse: 'Aging di Gudang',
    topAgingOverall: 'Aging Keseluruhan',
    topRegions: 'Wilayah Prioritas',
    affiliateRows: 'Ringkasan Afiliasi',
    pendingByType: 'Transaksi Gantung per Jenis',
    flowRows: 'Rantai Barang ke Pembayaran per Cabang',
    paymentMethodRows: 'Pencatatan per Metode Pembayaran',
    userSummary: 'Aktivitas per Pengguna Gudang',
    storeSummary: 'Rekap Selisih per Toko',
    periodRows: 'Rekap Harian / Mingguan / Bulanan',
    reasonRows: 'Alasan Selisih / Penyesuaian',
    dailyRows: 'Rekap Harian',
    monthlyRows: 'Rekap Bulanan',
    pointRows: 'Poin per Jenis Mutasi',
    referralRows: 'Referral & Afiliasi',
    appSummary: 'Akses per Aplikasi',
    voucherUsageRows: 'Voucher Terpakai',
    opnameItems: 'Rincian Item Opname',
};

const DATASET_DESCRIPTIONS: Record<string, string> = {
    rows: 'Daftar utama sesuai filter yang dipilih.',
    items: 'Daftar item utama sesuai filter yang dipilih.',
    logs: 'Riwayat aktivitas sistem. Bukan data transaksi penjualan.',
    fraudAlerts: 'Peringatan yang perlu diverifikasi oleh tim terkait.',
    checks: 'Hasil pemeriksaan kualitas dan kelengkapan data.',
    slow: 'Produk dengan penjualan rendah pada periode analisis.',
    fast: 'Produk dengan penjualan tinggi pada periode analisis.',
    dead: 'Produk tanpa penjualan pada periode analisis, tetapi masih memiliki stok.',
    expiring: 'Produk dengan tanggal kedaluwarsa dalam batas hari yang dipilih, termasuk yang sudah kedaluwarsa.',
    promoRows: 'Ringkasan pemakaian voucher dan promo pada periode terpilih.',
    pointMutationRows: 'Saldo dan mutasi poin per customer.',
    promoUsageRows: 'Rincian pemakaian promo dan diskon pada pesanan ecommerce.',
    redeemPointRows: 'Rekap redeem poin per customer pada periode terpilih.',
    supplierRows: 'Agregasi kinerja supplier, bukan daftar transaksi.',
    productRisks: 'Produk dengan indikasi risiko operasional.',
    monthlyTrend: 'Agregasi per bulan. Jangan dijumlahkan dengan daftar transaksi.',
    detailItems: 'Rincian item pendukung dari data utama.',
    detailRows: 'Rincian pendukung dari data utama.',
    latestRows: 'Data terbaru dan dapat beririsan dengan data utama.',
    topReferrers: 'Peringkat referrer berdasarkan metrik yang dipilih.',
    topMembers: 'Peringkat member berdasarkan metrik yang dipilih.',
    topBranches: 'Peringkat cabang berdasarkan metrik yang dipilih.',
    tierDistribution: 'Sebaran customer per tier membership.',
    barriers: 'Batas stok minimum per lokasi dan produk.',
    ecommerce: 'Pesanan ecommerce pada periode yang dipilih.',
    ecommerceHandled: 'Pesanan ecommerce beserta status pemenuhan, penanggung jawab, dan SLA-nya.',
    storeOrders: 'Permintaan pesanan dari cabang ke gudang beserta status persetujuannya.',
    storeReturns: 'Permintaan retur barang dari cabang beserta status penanganannya.',
    payment_breakdown: 'Rincian nominal dan jumlah transaksi per metode pembayaran pada sesi kasir.',
    frequentRequesters: 'Pengguna yang paling sering mengajukan permintaan.',
    frequentReprinters: 'Pengguna yang paling sering melakukan cetak ulang.',
    topCustomerRows: 'Peringkat customer berdasarkan metrik yang dipilih.',
    latestReferralRows: 'Referral terbaru pada periode ini.',
    scheduleRows: 'Jadwal shift karyawan pada periode ini.',
    topAgingStore: 'Produk dengan aging tertinggi di cabang.',
    topAgingWarehouse: 'Produk dengan aging tertinggi di gudang.',
    topAgingOverall: 'Produk dengan aging tertinggi secara keseluruhan.',
    topRegions: 'Wilayah dengan potensi tertinggi berdasarkan demografi dan penjualan.',
    affiliateRows: 'Poin afiliasi dan jumlah referral per referrer.',
    pendingByType: 'Jumlah dan nilai transaksi gantung per jenis.',
    flowRows: 'Perbandingan permintaan barang, penjualan, pembayaran diterima, dan pembayaran tercatat per cabang.',
    paymentMethodRows: 'Total pencatatan pembayaran per metode, misalnya BCA, BRI, atau tunai.',
    userSummary: 'Rekap aktivitas pengguna WMS pada periode dan filter yang dipilih.',
    storeSummary: 'Rekap nilai dan kuantitas selisih stok opname per toko.',
    periodRows: 'Rekap selisih stok opname harian, mingguan, atau bulanan.',
    reasonRows: 'Pengelompokan selisih berdasarkan alasan penyesuaian, cacat, hilang, atau koreksi.',
    dailyRows: 'Pergerakan stok yang diagregasi per hari.',
    monthlyRows: 'Pergerakan stok yang diagregasi per bulan.',
    pointRows: 'Penerimaan dan pemakaian poin per jenis mutasi.',
    referralRows: 'Daftar referral beserta referrer, referee, dan statusnya.',
    appSummary: 'Jumlah akses dan pengguna unik per aplikasi, termasuk WMS.',
    voucherUsageRows: 'Voucher yang sudah digunakan pada periode ini.',
    opnameItems: 'Rincian item per sesi stok opname beserta nilai selisih.',
};

const DATASET_KEYS = new Set(Object.keys(DATASET_LABELS));

const DATASET_ORDER = [
    'rows',
    'ecommerce',
    'ecommerceHandled',
    'storeOrders',
    'storeReturns',
    'payment_breakdown',
    'barriers',
    'logs',
    'slow',
    'fast',
    'dead',
    'expiring',
    'promoRows',
    'pointRows',
    'pointMutationRows',
    'referralRows',
    'promoUsageRows',
    'voucherUsageRows',
    'redeemPointRows',
    'supplierRows',
    'checks',
    'fraudAlerts',
    'productRisks',
    'monthlyTrend',
    'tierDistribution',
    'detailItems',
    'detailRows',
    'latestRows',
    'topReferrers',
    'topMembers',
    'topBranches',
    'topCustomerRows',
    'latestReferralRows',
    'frequentRequesters',
    'frequentReprinters',
    'scheduleRows',
    'topAgingStore',
    'topAgingWarehouse',
    'topAgingOverall',
    'topRegions',
    'affiliateRows',
    'pendingByType',
    'flowRows',
    'paymentMethodRows',
    'userSummary',
    'storeSummary',
    'periodRows',
    'reasonRows',
    'dailyRows',
    'monthlyRows',
    'appSummary',
    'opnameItems',
];

const COLUMN_LABELS: Record<string, string> = {
    midtrans_payment_types: 'Payment Type',
    raw_payment_status: 'Status Pembayaran (Raw)',
    visit_payment_status: 'Status Pembayaran Kunjungan',
    order_number: 'No. Pesanan',
    invoice_number: 'No. Invoice',
    delivery_order_number: 'No. Surat Jalan',
    opname_code: 'Kode Opname',
    transaction_number: 'No. Transaksi',
    shipping_number: 'No. Resi',
    product_code: 'Kode Produk',
    product_name: 'Nama Produk',
    category_name: 'Kategori',
    qty_sold: 'Qty Terjual / Keluar',
    qty_stock: 'Qty Stok',
    product_price: 'Harga Produk',
    stock_value: 'Nilai Stok',
    last_sold_at: 'Terakhir Terjual / Keluar',
    movement_status: 'Status Pergerakan',
    expiry_status: 'Status Kedaluwarsa',
    customer_name: 'Customer',
    store_name: 'Cabang',
    warehouse_name: 'Gudang',
    supplier_name: 'Supplier',
    location_name: 'Lokasi',
    employee_name: 'Karyawan',
    cashier_name: 'Kasir',
    performer_name: 'Petugas',
    username: 'Pengguna',
    membership_name: 'Membership',
    status: 'Status',
    sla_status: 'SLA',
    order_status: 'Status Pesanan',
    payment_status: 'Status Pembayaran',
    reconciliation_status: 'Status Rekonsiliasi',
    qc_status: 'Status QC',
    release_status: 'Status Rilis',
    condition_name: 'Kondisi',
    current_condition_name: 'Kondisi Saat Ini',
    anomaly_label: 'Anomali',
    risk_score: 'Skor Risiko',
    created_at: 'Dibuat',
    updated_at: 'Diperbarui',
    occurred_at: 'Waktu Kejadian',
    logged_at: 'Waktu Login',
    performed_at: 'Waktu QC',
    report_at: 'Waktu Laporan',
    received_date: 'Tanggal Terima',
    shipment_date: 'Tanggal Kirim',
    released_at: 'Waktu Rilis',
    completed_at: 'Waktu Selesai',
    last_activity_at: 'Aktivitas Terakhir',
    last_inbound_at: 'Inbound Terakhir',
    last_mutation_at: 'Mutasi Terakhir',
    grand_total: 'Grand Total',
    detail_value: 'Nilai Detail',
    total_amount: 'Total Pembayaran',
    gross_sales: 'Omset Kotor',
    net_after_product_discount: 'Setelah Diskon Produk',
    final_paid_amount: 'Omset Bersih',
    product_discount_amount: 'Diskon Produk',
    allocated_order_discount: 'Voucher / Poin',
    total_discount_amount: 'Total Potongan',
    base_price_total: 'Nilai Harga Dasar',
    total_transaksi: 'Total Transaksi',
    total_omset_kotor: 'Omset Kotor',
    total_omset_bersih: 'Omset Bersih',
    total_omset: 'Total Omset Bersih',
    total_diskon_produk: 'Diskon Produk',
    total_voucher_promo: 'Voucher / Poin',
    total_harga_awal: 'Omset Kotor',
    total_harga_akhir: 'Omset Bersih',
    total_harga_base: 'Nilai Harga Dasar',
    total_omset_kotor_store: 'Omset Store POS (Bersih)',
    total_omset_kotor_ecommerce: 'Omset Ecommerce (Bersih)',
    laba_kotor: 'Laba Kotor',
    margin_kotor_percent: 'Margin Kotor',
    paid_amount: 'Nominal Dibayar',
    total_nominal: 'Nilai Stok',
    total_qty: 'Total Qty',
    total_items: 'Total Item',
    total_sku: 'Total SKU',
    quantity: 'Qty',
    stock_barrier: 'Batas Stok',
    current_balance: 'Saldo Poin',
    store_code: 'Kode Cabang',
    warehouse_code: 'Kode Gudang',
    product_summary: 'Ringkasan Produk',
    note: 'Catatan',
    description: 'Deskripsi',
    address: 'Alamat',
    user_agent: 'User Agent',
    ip_address: 'IP Address',
    device: 'Device',
    platform: 'Platform',
    app_type: 'Tipe Aplikasi',
    failure_reason: 'Alasan Gagal',
    is_late: 'Terlambat',
    is_overtime: 'Lembur',
    is_active: 'Aktif',
    is_guest: 'Guest',
    late_minutes: 'Menit Terlambat',
    overtime_minutes: 'Menit Lembur',
    work_minutes: 'Menit Kerja',
    damage_rate: 'Tingkat Kerusakan',
    return_rate: 'Tingkat Retur',
    usage_count: 'Terpakai',
    usage_limit: 'Batas Pakai',
    priority: 'Prioritas',
    max_aging_days: 'Aging Maksimal',
    quantity_total: 'Total Qty',
    warehouse_quantity: 'Qty Gudang',
    store_quantity: 'Qty Cabang',
    customer_email: 'Email Customer',
    customer_wa_number: 'WA Customer',
    min_spend_treshold: 'Min. Belanja',
    earning_multiplier: 'Pengali Poin',
    membership_sort_order: 'Urutan Tier',
    tier_evaluated_at: 'Evaluasi Tier',
    tier_valid_until: 'Berlaku Sampai',
    last_tier_change_at: 'Perubahan Tier Terakhir',
    previous_membership_name: 'Tier Sebelumnya',
    tier_change_count: 'Jumlah Perubahan Tier',
    total_spend_12_month: 'Belanja 12 Bulan',
    supplier_country: 'Negara Supplier',
    supplier_phone: 'Telepon Supplier',
    problem_products: 'Produk Bermasalah',
    damage_conditions: 'Kondisi Rusak',
    latest_note: 'Catatan Terakhir',
    latest_location_note: 'Catatan Lokasi Terakhir',
    total_inbounds: 'Total Inbound',
    total_mutations: 'Total Mutasi',
    total_point_in: 'Poin Masuk',
    total_point_out: 'Poin Keluar',
    registration_channel: 'Channel Registrasi',
    branch_name: 'Cabang',
    location_move_count: 'Pindah Lokasi',
    distinct_location_count: 'Lokasi Berbeda',
    condition_change_count: 'Perubahan Kondisi',
    damage_condition_count: 'Kondisi Rusak',
    repaired_then_damaged_count: 'Diperbaiki Lalu Rusak',
    first_location_move_at: 'Pindah Pertama',
    last_location_move_at: 'Pindah Terakhir',
    first_condition_changed_at: 'Perubahan Kondisi Pertama',
    last_condition_changed_at: 'Perubahan Kondisi Terakhir',
    last_repaired_then_damaged_at: 'Terakhir Diperbaiki Lalu Rusak',
    current_area_label: 'Area Saat Ini',
    latest_area_label: 'Area Terakhir',
    current_area_name: 'Nama Area Saat Ini',
    latest_area_name: 'Nama Area Terakhir',
    current_warehouse_name: 'Gudang Saat Ini',
    current_store_name: 'Cabang Saat Ini',
    latest_warehouse_name: 'Gudang Terakhir',
    latest_store_name: 'Cabang Terakhir',
    current_location_name: 'Lokasi Saat Ini',
    latest_history_location_name: 'Lokasi Riwayat Terakhir',
    status_label: 'Status',
    opname_status: 'Status Opname',
    qty_pending: 'Qty Tertunda',
    qty_system: 'Qty Sistem',
    qty_scanned: 'Qty Pindai',
    qty_match: 'Qty Sesuai',
    qty_missing: 'Qty Hilang',
    qty_extra: 'Qty Lebih',
    qty_mismatch: 'Qty Tidak Sesuai',
    qty_diff: 'Selisih Qty',
    status_summary: 'Ringkasan Status',
    condition_names: 'Kondisi',
    destroyed_qty: 'Qty Dimusnahkan',
    rfid_summary: 'Ringkasan RFID',
    difference_reason: 'Alasan Selisih',
    additional_note: 'Catatan Tambahan',
    tanggal_dibuat: 'Tanggal Dibuat',
    tanggal_selesai: 'Tanggal Selesai',
    scan_time: 'Waktu Scan',
    total_aging_days: 'Total Hari Aging',
    warehouse_aging_label: 'Aging Gudang',
    store_aging_label: 'Aging Cabang',
    total_aging_label: 'Total Aging',
    expired_date: 'Tanggal Kedaluwarsa',
    days_to_expiry: 'Hari Menuju Kedaluwarsa',
    warehouse_names: 'Gudang',
    store_names: 'Cabang',
    max_warehouse_aging_days: 'Aging Maks. Gudang',
    max_store_aging_days: 'Aging Maks. Cabang',
    price_type: 'Tipe Harga',
    location_type: 'Tipe Lokasi',
    location_code: 'Kode Lokasi',
    unit_name: 'Satuan',
    qty_store: 'Qty Cabang',
    qty_display: 'Qty Display',
    qty_real: 'Qty Nyata',
    qty_reserved: 'Qty Reservasi',
    qty_available: 'Qty Tersedia',
    qty_good: 'Qty Baik',
    qty_good_after_repair: 'Qty Baik Setelah Perbaikan',
    qty_bad: 'Qty Rusak',
    tolerance_percentage: 'Toleransi (%)',
    order_ref: 'Referensi Order',
    store_order_number: 'Order Store',
    item_quantity: 'Qty Header',
    quantity_item: 'Qty Header',
    total_header_qty: 'Qty Header',
    outbound_type_label: 'Tipe Outbound',
    destination_store_name: 'Cabang Tujuan',
    destination_store_code: 'Kode Cabang Tujuan',
    store_received_qty: 'Qty Diterima Cabang',
    store_variance_qty: 'Selisih Qty Cabang',
    store_received_at: 'Diterima Cabang Pada',
    store_recording_status: 'Status Stok Cabang',
    store_recording_status_label: 'Status Stok Cabang',
    document_status: 'Status Dokumen',
    document_status_label: 'Status Dokumen',
    inbound_type_label: 'Tipe Inbound',
    shipping_status: 'Status Pengiriman',
    source_label: 'Sumber',
    payment_method_name: 'Metode Pembayaran',
    payment_method_code: 'Kode Metode',
    payment_fee: 'Biaya Pembayaran',
    discount_total: 'Total Diskon',
    sub_total: 'Subtotal',
    shipping_cost: 'Biaya Kirim',
    employee_email: 'Email Karyawan',
    shift_name: 'Shift',
    shift_code: 'Kode Shift',
    shift_start_time: 'Jam Mulai Shift',
    shift_end_time: 'Jam Selesai Shift',
    scheduled_start: 'Jadwal Mulai',
    scheduled_end: 'Jadwal Selesai',
    clock_in_at: 'Clock In',
    clock_out_at: 'Clock Out',
    registration_source_name: 'Sumber Registrasi',
    province_name: 'Provinsi',
    regency_name: 'Kabupaten/Kota',
    district_name: 'Kecamatan',
    village_name: 'Desa/Kelurahan',
    date_of_birth: 'Tanggal Lahir',
    gender: 'Gender',
    referral_code: 'Kode Referral',
    region_name: 'Wilayah',
    region_code: 'Kode Wilayah',
    total_customers: 'Jumlah Pelanggan',
    buying_customers: 'Pelanggan Bertransaksi',
    total_transactions: 'Total Transaksi',
    pos_spending: 'Belanja POS',
    ecom_spending: 'Belanja Ecommerce',
    total_spending: 'Total Belanja',
    average_spending: 'Rata-rata Belanja',
    potential_score: 'Skor Potensi',
    priority_label: 'Prioritas',
    recommendation: 'Rekomendasi',
    point_balance: 'Saldo Poin',
    earned_points: 'Poin Masuk',
    redeemed_points: 'Poin Ditukar',
    expired_points: 'Poin Kedaluwarsa',
    adjusted_points: 'Penyesuaian Poin',
    mutation_count: 'Jumlah Mutasi',
    used_voucher_count: 'Voucher Terpakai',
    used_voucher_value: 'Nilai Voucher Terpakai',
    available_voucher_count: 'Voucher Tersedia',
    available_voucher_value: 'Estimasi Nilai Voucher',
    last_voucher_at: 'Voucher Terakhir',
    source_type: 'Jenis Transaksi',
    reference: 'Referensi',
    age_days: 'Umur (hari)',
    min_age_days: 'Umur Minimal',
    quantity_gap: 'Selisih Qty',
    requested_qty: 'Qty Diminta',
    sold_qty: 'Qty Terjual',
    solved_qty: 'Qty Terjual',
    request_count: 'Jumlah Permintaan',
    sale_count: 'Jumlah Penjualan',
    payment_count: 'Jumlah Pembayaran',
    payment_received: 'Pembayaran Diterima',
    payment_paid: 'Nominal Dibayar',
    payment_change: 'Uang Kembalian',
    payment_recorded: 'Pembayaran Tercatat',
    recording_gap: 'Selisih Pencatatan',
    change_gap: 'Selisih Kembalian',
    payment_gap: 'Selisih Penjualan vs Pembayaran',
    payment_methods: 'Metode Pembayaran',
    change_amount: 'Uang Kembalian',
    ecommerce_used: 'Dipakai Ecommerce',
    store_used: 'Dipakai Store',
    used_total: 'Total Dipakai',
    payment_source: 'Sumber Pembayaran',
    payment_detail_amount: 'Nominal Metode',
    payment_real_date: 'Waktu Pembayaran',
    tax_amount: 'Pajak',
    voucher_discount: 'Diskon Voucher',
    used_discounts: 'Diskon Terpakai',
    total_product_rows: 'Jumlah Baris Produk',
    method_count: 'Jumlah Metode',
    detail_total: 'Total Tercatat',
    expected_amount: 'Nominal Seharusnya',
    recorded_amount: 'Nominal Tercatat',
    transaction_count: 'Jumlah Transaksi',
    event_count: 'Jumlah Aktivitas',
    inbound_events: 'Aktivitas Inbound',
    outbound_events: 'Aktivitas Outbound',
    qc_events: 'Aktivitas QC',
    opname_events: 'Aktivitas Opname',
    user_name: 'Pengguna',
    action: 'Aksi',
    detail: 'Detail',
    session_count: 'Jumlah Sesi',
    item_count: 'Jumlah Item',
    discrepancy_qty: 'Selisih Qty',
    discrepancy_value: 'Nilai Selisih',
    difference_qty: 'Selisih Qty',
    difference_value: 'Nilai Selisih',
    approval_status_label: 'Status Persetujuan',
    approver_name: 'Approver',
    pic_name: 'PIC',
    unit_price: 'Harga Satuan',
    shortage_qty: 'Qty Kurang',
    surplus_qty: 'Qty Lebih',
    unapproved_sessions: 'Sesi Belum Disetujui',
    period_label: 'Periode',
    movement_type: 'Jenis Pergerakan',
    direction: 'Arah',
    document_count: 'Jumlah Dokumen',
    to_display_items: 'Item ke Display',
    to_store_stock_items: 'Item ke Gudang Kecil',
    inbound_documents: 'Dokumen Masuk',
    outbound_documents: 'Dokumen Keluar',
    voucher_context: 'Konteks Voucher',
    voucher_type: 'Tipe Voucher',
    usage_count_in_period: 'Pemakaian Periode',
    unique_customers: 'Akun Unik',
    remaining_quota: 'Sisa Kuota',
    mutation_type: 'Jenis Mutasi',
    mutation_label: 'Keterangan Mutasi',
    points_in: 'Poin Masuk',
    points_out: 'Poin Keluar',
    total_points: 'Total Poin',
    referral_status: 'Status Referral',
    total_referral: 'Total Referral',
    total_referrer: 'Total Referrer',
    total_referee: 'Total Referee',
    register_points: 'Poin Registrasi',
    first_tx_points: 'Poin Transaksi Pertama',
    total_affiliate_points: 'Total Poin Afiliasi',
    last_point_at: 'Poin Terakhir',
    registered_referral: 'Referral Terdaftar',
    first_transaction_referral: 'Transaksi Pertama',
    unique_users: 'Pengguna Unik',
    failed_count: 'Gagal',
    failed_logins: 'Login Gagal',
    wms_sessions: 'Akses WMS',
    store_sessions: 'Akses Toko',
    ecommerce_sessions: 'Akses Ecommerce',
    backoffice_sessions: 'Akses Master Data & Report',
    today_sessions: 'Akses Hari Ini',
    total_sessions: 'Total Akses',
    total_pending: 'Total Gantung',
    affected_stores: 'Cabang Terdampak',
    oldest_age_days: 'Umur Terlama (hari)',
    over_seven_days: 'Lebih dari 7 Hari',
    max_age_days: 'Umur Maksimal',
    total_anomalies: 'Total Anomali',
    total_payments: 'Total Pembayaran',
    total_sales: 'Total Penjualan',
    total_paid: 'Total Dibayar',
    total_change: 'Total Kembalian',
    total_recorded: 'Total Tercatat',
    total_recording_difference: 'Total Selisih Pencatatan',
    total_change_difference: 'Total Selisih Kembalian',
    total_flow_difference: 'Total Selisih Rantai',
    total_quantity_gap: 'Total Selisih Qty',
    method: 'Metode',
    amount: 'Nominal',
};

const HIDDEN_COLUMNS = new Set([
    'id',
    'uuid',
    'password',
    'deleted_at',
    'updated_at',
    'session_token',
    'product_details',
    'details',
    'rowItems',
    'rfid_list',
    'item_conditions',
    'show_detail_button',
    'can_decide',
    'url',
    'created_by',
    'updated_by',
    'registration_source_id',
    'province_code',
    'regency_code',
    'district_code',
    'village_code',
    'current_location_id',
    'latest_history_location_id',
    'rfid_tag_id',
    'payment_id',
    'order_id',
    'store_cart_id',
    'ecommerce_order_id',
    'store_return_id',
    'warehouse_source_id',
    'source_warehouse_id',
]);

const OBJECT_COLUMNS = new Set(['store', 'product', 'warehouse', 'membership', 'customer', 'location']);

const CURRENCY_KEYS = new Set([
    'grand_total',
    'sub_total',
    'total_amount',
    'paid_amount',
    'total_nominal',
    'product_price',
    'total_value',
    'stock_value',
    'payment_fee',
    'shipping_cost',
    'max_discount',
    'min_purchase',
    'total_spend_12_month',
    'min_spend_treshold',
    'discount_total',
    'cash_difference',
    'total_cash',
    'total_non_cash',
    'total_paid',
    'total_unpaid',
    'total_refund',
    'total_fee',
    'total_shipping',
    'gross_sales',
    'net_sales',
    'gross_profit',
    'laba_kotor',
    'base_price',
    'total_base_price',
    'total_gross_sales',
    'total_net_after_product_discount',
    'total_final_paid',
    'total_product_discount',
    'total_voucher_discount',
    'total_harga_awal',
    'total_harga_akhir',
    'total_harga_base',
    'total_harga_order_store',
    'total_omset',
    'total_omset_kotor',
    'total_omset_bersih',
    'total_omset_kotor_store',
    'total_omset_kotor_ecommerce',
    'total_diskon_produk',
    'total_voucher_promo',
    'available_voucher_value',
    'used_voucher_value',
    'difference_value',
    'discrepancy_value',
    'total_discrepancy_value',
    'net_discrepancy_value',
    'sold_value',
    'payment_received',
    'payment_paid',
    'payment_change',
    'payment_recorded',
    'recording_gap',
    'change_gap',
    'payment_gap',
    'payment_gap',
    'detail_total',
    'recorded_amount',
    'total_affiliate_points',
    'register_points',
    'first_tx_points',
    'voucher_discount_value',
    'discount_amount',
    'recorded_amount',
]);

const LONG_TEXT_KEYS = new Set([
    'note',
    'description',
    'address',
    'failure_reason',
    'product_summary',
    'damage_conditions',
    'latest_note',
    'latest_location_note',
    'additional_note',
    'difference_reason',
    'status_summary',
    'condition_names',
    'rfid_summary',
    'warehouse_names',
    'store_names',
    'user_agent',
]);

const isDateKey = (key: string): boolean =>
    DATE_KEYS.has(key) ||
    /(_at|_date|^date|date_of_birth|birth_date|_until|tanggal|period$|report_time|scan_time|last_item_update)/i.test(key);

const isCurrencyKey = (key: string): boolean =>
    CURRENCY_KEYS.has(key) ||
    (/amount|price|nominal|grand_total|sub_total|total_value|paid_amount|payment_fee|shipping_cost|max_discount|min_purchase|omset|revenue|spending|discount|spend/i.test(key) &&
        !/balance|point/i.test(key));

const isPercentKey = (key: string): boolean =>
    /(percentage|_rate$|rate$|margin|tolerance)/i.test(key) && !/rating/i.test(key);

const isNumberKey = (key: string): boolean =>
    /(qty|quantity|count|total|items|sku|stock|stok|points?|minutes|days|aging|score|priority|usage|visit|shift|inbound|outbound|scanned|missing|extra|mismatch|diff|system|physical|selisih|destroyed|balance)/i.test(key);

const isStatusKey = (key: string): boolean =>
    /(status|condition|anomaly|type|label|severity|tone|state)/i.test(key) &&
    !/status_summary|condition_names|_count$|_at$|_date$|_id$|_days$|_minutes$/i.test(key);

const isLongTextKey = (key: string): boolean => LONG_TEXT_KEYS.has(key);

const isHiddenColumn = (key: string, value: unknown): boolean => {
    if (HIDDEN_COLUMNS.has(key)) return true;
    if (key.endsWith('_id')) return true;
    if (Array.isArray(value)) return true;
    if (isObject(value) && !OBJECT_COLUMNS.has(key)) return true;
    return false;
};

const humanize = (key: string): string => {
    if (COLUMN_LABELS[key]) return COLUMN_LABELS[key];

    return key
        .replace(/_/g, ' ')
        .split(' ')
        .map((word) => {
            const lower = word.toLowerCase();

            if (['id', 'sku', 'rfid', 'awb', 'qc', 'wms', 'pos', 'sla', 'wa', 'gps', 'isp', 'ip', 'qty', 'no'].includes(lower)) {
                return lower.toUpperCase();
            }

            return word.charAt(0).toUpperCase() + word.slice(1);
        })
        .join(' ');
};

const filterLabel = (key: string): string => FILTER_LABELS[key] ?? humanize(key);

const emptyOptionLabel = (field: FilterField): string => {
    const labels: Record<string, string> = {
        exception_only: 'Semua data',
        is_active: 'Semua status',
        source: 'Semua sumber',
        scope: 'Semua cakupan',
        channel: 'Semua channel',
        group_by: 'Default',
        promo: 'Semua transaksi',
        mode: 'Default',
        handled_type: 'Semua penanggung jawab',
        birthday_month: 'Semua bulan',
        payment_method_id: 'Semua metode',
        store_id: 'Semua cabang',
        warehouse_id: 'Semua gudang',
        warehouse_source_id: 'Semua gudang asal',
        source_warehouse_id: 'Semua gudang asal',
        product_id: 'Semua produk',
        brand_id: 'Semua brand',
        category_id: 'Semua kategori',
        customer_id: 'Semua customer',
        referrer_id: 'Semua referrer',
        supplier_id: 'Semua supplier',
        courier_id: 'Semua kurir',
        cashier_id: 'Semua kasir',
        user_id: 'Semua pengguna',
        shift_id: 'Semua shift',
        membership_id: 'Semua membership',
        location_id: 'Semua lokasi',
        location_key: 'Semua lokasi',
        condition_id: 'Semua kondisi',
        promo_id: 'Semua promo',
        discount_id: 'Semua diskon',
        voucher_id: 'Semua voucher',
        requested_by: 'Semua pengguna',
        printed_by: 'Semua pengguna',
        authorized_by: 'Semua pengguna',
        action: 'Semua aksi',
    };

    if (labels[field.key]) return labels[field.key];
    if (field.key === 'status' || field.key.endsWith('_status')) return 'Semua status';
    if (field.key === 'type' || field.key.endsWith('_type')) return 'Semua tipe';

    return `Semua ${field.label.toLowerCase()}`;
};

const optionLabel = (key: string, value: string): string => {
    const field = filterFields.value.find((item) => item.key === key);

    return field?.options.find((option) => option.value === value)?.label ?? value;
};

const toOptions = (source: unknown, labels?: unknown): FilterOption[] => {
    const resolveLabel = (value: string): string =>
        isObject(labels) && labels[value] !== undefined ? String(labels[value]) : value;

    if (Array.isArray(source)) {
        return source
            .map((item) => {
                if (isObject(item)) {
                    const rawValue = item.value ?? item.id ?? item.key ?? item.code ?? item.status ?? item.slug ?? item.name ?? '';
                    const baseLabel =
                        (item.name && item.code ? `${item.name} (${item.code})` : item.name) ??
                        item.status ??
                        item.value ??
                        item.code ??
                        rawValue;
                    const rawLabel = item.label ?? (item.store_name && item.name ? `${item.store_name} - ${baseLabel}` : baseLabel);

                    return { value: String(rawValue), label: String(rawLabel) };
                }

                const value = String(item ?? '');

                return { value, label: resolveLabel(value) };
            })
            .filter((option) => option.value !== '');
    }

    if (isObject(source)) {
        return Object.entries(source).map(([value, label]) => ({ value, label: String(label) }));
    }

    return [];
};

const TITLE_CASE_OPTION_KEYS = new Set([
    'status',
    'order_status',
    'payment_status',
    'qc_status',
    'release_status',
    'shipping_status',
    'item_qc_status',
    'period_status',
    'attendance_type',
    'tracking_status',
    'anomaly_type',
    'handled_type',
    'source',
    'type',
    'registration_channel',
    'app_type',
    'action',
    'mutation_type',
]);

const MUTATION_TYPE_LABELS: Record<string, string> = {
    '+': 'Poin Masuk',
    '-': 'Poin Keluar',
};

const humanizeOption = (value: string): string => {
    if (/[A-Z]/.test(value)) return value;

    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
};

const withOptionLabels = (key: string, options: FilterOption[]): FilterOption[] =>
    options.map((option) => {
        if (option.label !== option.value) return option;
        if (key === 'mutation_type') {
            return { ...option, label: MUTATION_TYPE_LABELS[option.value] ?? humanizeOption(option.value) };
        }
        if (TITLE_CASE_OPTION_KEYS.has(key)) {
            return { ...option, label: humanizeOption(option.value) };
        }

        return option;
    });

const withoutAllOption = (key: string, options: FilterOption[]): FilterOption[] => {
    const filtered = ['promo', 'scope', 'channel'].includes(key)
        ? options.filter((option) => option.value !== 'all')
        : options;

    return withOptionLabels(key, filtered);
};

const resolveOptions = (key: string): FilterOption[] => {
    const mapping = OPTION_SOURCES[key];

    if (mapping) {
        for (const sourceKey of mapping.keys) {
            const source = props.data?.[sourceKey];

            if (Array.isArray(source) && source.length > 0) {
                const labels = mapping.labels?.[0] ? props.data?.[mapping.labels[0]] : undefined;
                const options = toOptions(source, labels);

                if (options.length > 0) return withoutAllOption(key, options);
            }

            if (isObject(source) && Object.keys(source).length > 0) {
                const options = toOptions(source);

                if (options.length > 0) return withoutAllOption(key, options);
            }
        }
    }

    if (key === 'status' && Array.isArray(props.data?.statuses)) {
        const options = toOptions(props.data.statuses);

        if (options.length > 0) return withoutAllOption(key, options);
    }

    return withoutAllOption(key, PRESET_OPTIONS[key] ?? []);
};

const normalizeFilterValue = (key: string, value: unknown): string => {
    if (typeof value === 'boolean') {
        if (key === 'is_active') return value ? '1' : '0';

        return value ? '1' : '';
    }

    if (value === null || value === undefined) return '';

    const normalized = String(value);

    if (['scope', 'channel', 'promo'].includes(key) && normalized.toLowerCase() === 'all') return '';

    return normalized;
};

const rawFilterEntries = computed<Map<string, any>>(() => {
    const entries = new Map<string, any>();

    const add = (key: string, value: unknown) => {
        if (!ALLOWED_FILTERS.has(key)) return;
        if (value !== null && typeof value === 'object') return;
        entries.set(key, value);
    };

    if (isObject(props.filters)) {
        for (const [key, value] of Object.entries(props.filters)) {
            if (key === 'page' || key === 'theme') continue;
            add(key, value);
        }
    }

    if (isObject(props.data?.filters)) {
        for (const [key, value] of Object.entries(props.data.filters)) {
            if (!entries.has(key)) add(key, value);
        }
    }

    for (const [key, value] of Object.entries(props.data ?? {})) {
        const canonical = FILTER_ALIASES[key] ?? (ALLOWED_FILTERS.has(key) ? key : null);

        if (canonical && !entries.has(canonical)) add(canonical, value);
    }

    return entries;
});

const filterSignature = computed(() => JSON.stringify(Array.from(rawFilterEntries.value.entries()).sort()));

const filterValues = reactive<Record<string, string>>({});

const syncFilterValues = () => {
    const entries = rawFilterEntries.value;

    for (const key of Object.keys(filterValues)) {
        if (!entries.has(key)) delete filterValues[key];
    }

    for (const [key, value] of entries.entries()) {
        filterValues[key] = normalizeFilterValue(key, value);
    }
};

syncFilterValues();
watch(filterSignature, syncFilterValues);

const filterFields = computed<FilterField[]>(() => {
    const fields: FilterField[] = [];

    for (const [key] of rawFilterEntries.value.entries()) {
        if (key === 'per_page') continue;

        const options = resolveOptions(key);
        let type: FilterType = 'text';

        if (DATE_KEYS.has(key)) {
            type = 'date';
        } else if (NUMBER_KEYS.has(key)) {
            type = 'number';
        } else if (options.length > 0) {
            type = 'select';
        }

        fields.push({
            key,
            label: filterLabel(key),
            type,
            options,
            placeholder: SEARCH_KEYS.has(key) ? 'Cari nomor, nama, atau kode...' : '',
            wide: SEARCH_KEYS.has(key) && key !== 'item_id',
        });
    }

    const rank = (field: FilterField): number => {
        if (SEARCH_KEYS.has(field.key)) return 0;
        if (field.type === 'date') return 1;
        if (field.type === 'select') return 2;
        if (field.type === 'number') return 3;
        return 4;
    };

    return fields.sort((left, right) => rank(left) - rank(right));
});

const visibleFilterFields = computed<FilterField[]>(() =>
    filterFields.value.filter((field) => {
        const mode = filterValues.mode ?? '';
        const scope = filterValues.scope ?? '';
        const source = filterValues.source ?? '';
        const handled = filterValues.handled_type ?? '';

        if (field.key === 'store_id') {
            return !(
                mode === 'all' ||
                mode === 'warehouse' ||
                scope === 'warehouse' ||
                source === 'warehouse' ||
                handled === 'warehouse' ||
                handled === 'unhandled'
            );
        }

        if (field.key === 'warehouse_id') {
            return !(
                mode === 'all' ||
                mode === 'store' ||
                scope === 'store' ||
                source === 'store' ||
                handled === 'store' ||
                handled === 'unhandled'
            );
        }

        return true;
    }),
);

const visibleFilterKeys = computed(() => new Set(visibleFilterFields.value.map((field) => field.key)));

watch(
    () => [filterValues.mode, filterValues.scope, filterValues.source, filterValues.handled_type],
    () => {
        if (filterValues.mode === 'all') {
            filterValues.store_id = '';
            filterValues.warehouse_id = '';
        }

        if (filterValues.mode === 'warehouse' || filterValues.scope === 'warehouse' || filterValues.source === 'warehouse' || filterValues.handled_type === 'warehouse') {
            filterValues.store_id = '';
        }

        if (filterValues.mode === 'store' || filterValues.scope === 'store' || filterValues.source === 'store' || filterValues.handled_type === 'store') {
            filterValues.warehouse_id = '';
        }

        if (filterValues.handled_type === 'unhandled') {
            filterValues.store_id = '';
            filterValues.warehouse_id = '';
        }
    },
    { immediate: true },
);

const activeQueryFilters = computed<Map<string, string>>(() => {
    const entries = new Map<string, string>();

    if (!isObject(props.filters)) return entries;

    for (const [key, value] of Object.entries(props.filters)) {
        if (key === 'page' || key === 'theme' || key === 'per_page') continue;
        if (!ALLOWED_FILTERS.has(key)) continue;
        if (value === null || value === undefined || value === '') continue;
        if (typeof value === 'object') continue;

        entries.set(key, String(value));
    }

    return entries;
});

const activeFilterCount = computed(() => activeQueryFilters.value.size);

const activeFilterChips = computed(() =>
    Array.from(activeQueryFilters.value.entries())
        .filter(([key]) => visibleFilterKeys.value.has(key))
        .map(([key, value]) => ({
            key,
            label: filterLabel(key),
            value: DATE_KEYS.has(key) ? formatDateValue(value, key) : optionLabel(key, value),
        })),
);

const datasetSignature = (rows: Row[]): string => {
    if (rows.length === 0) return 'empty';

    try {
        return JSON.stringify(rows);
    } catch {
        return `${rows.length}-${Object.keys(rows[0] ?? {}).join(',')}`;
    }
};

const datasets = computed<Dataset[]>(() => {
    const list: Dataset[] = [];

    for (const [key, value] of Object.entries(props.data ?? {})) {
        if (isPaginator(value)) {
            list.push({
                key,
                label: DATASET_LABELS[key] ?? humanize(key),
                description: DATASET_DESCRIPTIONS[key] ?? 'Kumpulan data sesuai filter yang dipilih.',
                rows: value.data,
                paginator: value,
            });
            continue;
        }

        if (Array.isArray(value) && DATASET_KEYS.has(key) && value.length > 0 && isObject(value[0])) {
            list.push({
                key,
                label: DATASET_LABELS[key] ?? humanize(key),
                description: DATASET_DESCRIPTIONS[key] ?? 'Kumpulan data sesuai filter yang dipilih.',
                rows: value as Row[],
                paginator: null,
            });
        }
    }

    const seen = new Set<string>();

    return list
        .filter((dataset) => {
            const signature = datasetSignature(dataset.rows);

            if (seen.has(signature)) return false;

            seen.add(signature);

            return true;
        })
        .sort((left, right) => {
            const leftIndex = DATASET_ORDER.indexOf(left.key);
            const rightIndex = DATASET_ORDER.indexOf(right.key);

            return (leftIndex === -1 ? 999 : leftIndex) - (rightIndex === -1 ? 999 : rightIndex);
        });
});

const currentDatasetKey = ref<string | null>(null);

const requestedDatasetKey = computed(() => {
    if (typeof props.data?.activeDataset === 'string' && props.data.activeDataset !== '') {
        return props.data.activeDataset;
    }

    if (isObject(props.filters)) {
        const dataset = (props.filters as Record<string, unknown>).dataset;

        if (typeof dataset === 'string') return dataset;
    }

    return '';
});

const activeDataset = computed<Dataset | null>(() => {
    const list = datasets.value;

    if (list.length === 0) return null;

    return list.find((dataset) => dataset.key === currentDatasetKey.value) ?? list[0];
});

let lastRequestedDataset = '';

watch(
    [datasets, requestedDatasetKey],
    ([list, requested]) => {
        if (list.length === 0) {
            currentDatasetKey.value = null;
            lastRequestedDataset = requested;
            return;
        }

        if (requested !== '' && requested !== lastRequestedDataset && list.some((dataset) => dataset.key === requested)) {
            lastRequestedDataset = requested;
            currentDatasetKey.value = requested;
            return;
        }

        lastRequestedDataset = requested;

        if (!currentDatasetKey.value || !list.some((dataset) => dataset.key === currentDatasetKey.value)) {
            currentDatasetKey.value = list[0].key;
        }
    },
    { immediate: true },
);

const activeDatasetDescription = computed(() => activeDataset.value?.description ?? '');

const rows = computed<Row[]>(() => activeDataset.value?.rows ?? []);

const selectedRow = ref<Row | null>(null);
const operationsUrl = computed(() => typeof props.urls?.operations === 'string' ? props.urls.operations : '');
const rowHasDetails = (row: Row): boolean => {
    const details = row.product_details ?? row.details;

    return Array.isArray(details) && details.length > 0;
};
const hasRowDetails = computed(() => rows.value.some(rowHasDetails));

const reservationRow = ref<Row | null>(null);
const reservationUrl = computed(() => typeof props.urls?.reservations === 'string' ? props.urls.reservations : '');

const reservationHandler = (row: Row): { type: 'warehouse' | 'store'; id: string } | null => {
    if (row.warehouse_id) {
        return { type: 'warehouse', id: String(row.warehouse_id) };
    }

    if (row.store_id) {
        return { type: 'store', id: String(row.store_id) };
    }

    return null;
};

const canOpenReservations = (row: Row): boolean => {
    if (!reservationUrl.value) return false;
    if (!columnKeys.value.includes('qty_reserved')) return false;
    if (!row.product_id) return false;
    if (Number(row.qty_reserved ?? 0) <= 0) return false;

    return reservationHandler(row) !== null;
};

const reservationContext = computed(() => {
    const row = reservationRow.value;

    if (!row) return null;

    const handler = reservationHandler(row);

    return {
        handlerType: handler?.type ?? 'warehouse',
        handlerId: handler?.id ?? null,
        productId: row.product_id ? String(row.product_id) : null,
        productName: String(row.product_name ?? ''),
        productCode: String(row.product_code ?? ''),
        locationName: String(row.warehouse_name ?? row.store_name ?? ''),
        locationCode: String(row.warehouse_code ?? row.store_code ?? ''),
        unitName: String(row.unit_name ?? ''),
        reservedQty: Number(row.qty_reserved ?? 0),
    };
});

const openReservations = (row: Row) => {
    if (!canOpenReservations(row)) return;

    reservationRow.value = row;
};

const closeReservations = () => {
    reservationRow.value = null;
};

const detailRows = computed<Row[]>(() => {
    const details = selectedRow.value?.product_details ?? selectedRow.value?.details;

    return Array.isArray(details) ? details as Row[] : [];
});
const detailSectionTitle = computed<string>(() => {
    const detail = detailRows.value[0] ?? {};

    if ('voucher_code' in detail || 'voucher_name' in detail) return 'Daftar Voucher Akun';
    if ('method' in detail || 'payment_method' in detail) return 'Rincian Metode Pembayaran';

    return 'Detail Produk';
});

const detailSectionDescription = computed<string>(() =>
    detailSectionTitle.value === 'Detail Produk'
        ? 'Detail dokumen dan seluruh baris produk pada data ini.'
        : 'Detail dokumen dan rincian pendukung pada data ini.',
);

const detailColumns = computed<TableColumn[]>(() => {
    const keys = new Set<string>();

    for (const row of detailRows.value.slice(0, 5)) {
        for (const key of Object.keys(row)) {
            if (!isHiddenColumn(key, row[key])) keys.add(key);
        }
    }

    return Array.from(keys).map((key) => ({
        key,
        label: humanize(key),
        align: isNumberKey(key) || isCurrencyKey(key) ? 'text-end' : 'text-start',
        wrap: isLongTextKey(key),
    }));
});
const openRowDetail = (row: Row) => {
    if (!rowHasDetails(row)) return;

    selectedRow.value = row;
};
const closeRowDetail = () => {
    selectedRow.value = null;
};

const hasApprovalActions = computed(() => Boolean((props.urls?.approve || props.urls?.reject) && activeDataset.value?.key === 'rows'));

const decideOpname = (row: Row, action: 'approve' | 'reject') => {
    const url = action === 'approve' ? props.urls?.approve : props.urls?.reject;

    if (!url) return;

    const sessionId = String(row.session_id ?? '');

    if (!sessionId) return;

    let reason = '';

    if (action === 'reject') {
        reason = window.prompt('Alasan penolakan sesi stok opname:')?.trim() ?? '';

        if (!reason) return;
    } else if (!window.confirm('Setujui sesi stok opname ini dan catat keputusan penyesuaian?')) {
        return;
    }

    router.post(url.replace('__ID__', sessionId), { reason }, { preserveScroll: true });
};
const onDetailKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && selectedRow.value) closeRowDetail();
};

watch(selectedRow, (value) => {
    document.body.style.overflow = value ? 'hidden' : '';
});
onMounted(() => window.addEventListener('keydown', onDetailKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onDetailKeydown);
    document.body.style.overflow = '';
});

const paginator = computed<Paginator | null>(() => activeDataset.value?.paginator ?? null);
const supportsPerPage = computed(() => rawFilterEntries.value.has('per_page'));
const perPageValue = computed(() => filterValues.per_page ?? (paginator.value?.per_page ? String(paginator.value.per_page) : ''));
const perPageOptions = computed(() => {
    const values = new Set(['10', '25', '50', '100']);

    if (perPageValue.value) values.add(perPageValue.value);

    return Array.from(values).sort((left, right) => Number(left) - Number(right));
});

const summarySource = computed<Record<string, any>>(() => {
    if (isObject(props.data?.summary)) return props.data.summary;
    if (isObject(props.data?.dashboardSummary)) return props.data.dashboardSummary;

    return {};
});

const summaryLabel = (key: string): string => {
    const labels: Record<string, string> = {
        total: 'Total Data',
        orders: 'Total Pesanan',
        ecommerce: 'Pesanan Ecommerce',
        pending: 'Pending',
        pending_payment: 'Pembayaran Pending',
        without_payment: 'Tanpa Pembayaran',
        exceptions: 'Data Anomali',
        without_handler: 'Belum Ditangani',
        without_awb: 'Tanpa Resi',
        over_24h: 'Lewat 24 Jam',
        paid: 'Sudah Dibayar',
        unpaid: 'Belum Dibayar',
        failed: 'Gagal',
        rejected: 'Ditolak',
        cancelled: 'Dibatalkan',
        active: 'Aktif',
        inactive: 'Tidak Aktif',
        today: 'Hari Ini',
        warehouse: 'Warehouse',
        store: 'Store',
        changes_today: 'Perubahan Hari Ini',
        failed_changes: 'Perubahan Gagal',
        open_fraud: 'Fraud Terbuka',
        failed_login: 'Login Gagal',
        total_customer: 'Total Customer',
        total_items: 'Total Item',
        total_sku: 'Total SKU',
        total_qty: 'Total Qty',
        total_header_qty: 'Qty Header',
        received_today: 'Diterima Hari Ini',
        total_value: 'Total Nilai',
        total_header_value: 'Total Nilai Header',
        total_nominal: 'Total Nilai',
        total_amount: 'Total Nominal',
        total_transactions: 'Total Transaksi',
        total_attendance: 'Total Absensi',
        total_late: 'Terlambat',
        total_overtime: 'Lembur',
        total_not_clock_in: 'Belum Clock In',
        total_not_clock_out: 'Belum Clock Out',
        total_suppliers: 'Total Supplier',
        suppliers_monitored: 'Supplier Dimonitor',
        damaged_items: 'Item Rusak',
        returned_items: 'Item Retur',
        total_referral: 'Total Referral',
        success_referral: 'Referral Berhasil',
        referral_success_rate: 'Tingkat Keberhasilan Referral',
        voucher_users: 'Akun Pemakai Voucher',
        voucher_usages: 'Pemakaian Voucher',
        voucher_discount_value: 'Nilai Diskon Voucher',
        point_users: 'Akun Pemakai Poin',
        point_mutations: 'Mutasi Poin',
        total_success: 'Berhasil',
        total_pending: 'Pending',
        total_point_in: 'Poin Masuk',
        total_point_out: 'Poin Keluar',
        total_current_balance: 'Total Saldo Poin',
        expired_quantity: 'Qty Kedaluwarsa',
        urgent_quantity: 'Qty Segera Expired',
        upcoming_quantity: 'Qty Akan Expired',
        slow_count: 'Slow Moving',
        fast_count: 'Fast Moving',
        dead_count: 'Deadstock',
        expiring_count: 'Mendekati Kedaluwarsa',
        slow_qty: 'Qty Slow Moving',
        fast_qty: 'Qty Fast Moving',
        dead_qty: 'Qty Deadstock',
        expiring_qty: 'Qty Mendekati Kedaluwarsa',
        expired_qty: 'Qty Sudah Kedaluwarsa',
        total_store: 'Total Store',
        total_warehouse: 'Total Warehouse',
        total_good: 'Kondisi Baik',
        total_good_after_repair: 'Baik Setelah Perbaikan',
        total_bad: 'Kondisi Rusak',
        total_anomaly: 'Total Anomali',
        total_anomaly_item: 'Item Anomali',
        total_frequent_move: 'Sering Pindah',
        total_location_mismatch: 'Lokasi Tidak Sinkron',
        total_frequent_damage: 'Sering Rusak',
        total_repaired_then_damaged: 'Diperbaiki Lalu Rusak',
        average_risk_score: 'Rata-rata Risiko',
        total_product_affected: 'Produk Terdampak',
        total_location_affected: 'Lokasi Terdampak',
        total_location_move: 'Total Pindah Lokasi',
        total_damage_event: 'Kejadian Rusak',
        high_risk_item: 'Item Risiko Tinggi',
        max_risk: 'Risiko Maksimal',
        total_omzet: 'Total Omzet',
        total_sessions: 'Total Sesi',
        total_open_sessions: 'Sesi Terbuka',
        total_closed_sessions: 'Sesi Tertutup',
        cash_difference: 'Selisih Kas',
        total_cash: 'Total Kas',
        total_non_cash: 'Total Non-Tunai',
        total_products: 'Total Produk',
        total_customers: 'Total Customer',
        total_revenue: 'Total Pendapatan',
        total_spend: 'Total Belanja',
        total_point: 'Total Poin',
        total_members: 'Total Member',
        total_active: 'Aktif',
        total_unassigned: 'Belum Punya Tier',
        expiring_soon: 'Segera Berakhir',
        total_referrer: 'Total Referrer',
        total_referee: 'Total Referee',
        total_other_status: 'Status Lain',
        total_score_row: 'Total Skor',
        active_point_customer: 'Customer Punya Poin',
        total_grand_total: 'Nilai Pesanan',
        total_sub_total: 'Subtotal Pesanan',
        total_discount: 'Total Diskon',
        total_paid_amount: 'Pembayaran Diterima',
        total_outstanding: 'Sisa Tagihan',
        average_order_value: 'Rata-rata Pembayaran',
        total_cash_amount: 'Total Tunai',
        total_non_cash_amount: 'Total Non-Tunai',
        total_cash_transactions: 'Transaksi Tunai',
        total_non_cash_transactions: 'Transaksi Non-Tunai',
        total_receivable_amount: 'Piutang Rekanan',
        total_gross_sales: 'Omset Kotor',
        total_net_after_product_discount: 'Setelah Diskon Produk',
        total_final_paid: 'Omset Bersih',
        total_product_discount: 'Diskon Produk',
        total_voucher_discount: 'Voucher / Poin',
        total_discount_amount: 'Total Potongan',
        total_base_price: 'Nilai Harga Dasar',
        average_net_transaction: 'Rata-rata Omset Bersih',
        total_omset_kotor: 'Omset Kotor',
        total_omset_bersih: 'Omset Bersih',
        total_diskon_produk: 'Diskon Produk',
        total_voucher_promo: 'Voucher / Poin',
        laba_kotor: 'Laba Kotor',
        margin_kotor_percent: 'Margin Kotor',
        total_store_customer: 'Customer Store',
        total_ecommerce_customer: 'Customer Ecommerce',
        slow_total_nominal: 'Nilai Slow Moving',
        dead_total_nominal: 'Nilai Deadstock',
        total_product: 'Total Produk',
        total_location: 'Total Lokasi',
        total_quantity: 'Total Qty',
        total_store_stock: 'Qty Stok Toko',
        total_store_display: 'Qty Store Display',
        total_warehouse_stock: 'Qty Warehouse',
        total_qc_checked_items: 'Item Sudah QC',
        total_qc_pending_items: 'Item Pending QC',
        total_all_passed: 'Semua Lolos QC',
        total_partial_passed: 'QC Sebagian',
        total_late_minutes: 'Menit Terlambat',
        total_overtime_minutes: 'Menit Lembur',
        total_work_minutes: 'Menit Kerja',
        total_returns: 'Total Retur',
        total_damaged: 'Total Rusak',
        total_orders: 'Total Pesanan',
        total_with_shipping: 'Resi Ecommerce Tersedia',
        total_missing_awb: 'Belum Ada Resi Ecommerce',
        total_without_shipping: 'Belum Ada Resi Ecommerce',
        total_without_delivery_order: 'Belum Ada Surat Jalan Store',
        total_store_transfers: 'Transfer ke Toko',
        store_not_recorded: 'Belum Tercatat di Toko',
        store_partial: 'Tercatat Sebagian',
        store_recorded: 'Sudah Tercatat',
    };

    return labels[key] ?? humanize(key);
};

const summaryTone = (key: string): string => {
    if (/reject|fail|bad|danger|expired|over_24h|damage|missing|void|cancel|open_fraud|dead|high_risk|not_recorded/i.test(key)) return 'danger';
    if (/pending|unpaid|without|warning|slow|urgent|expiring|upcoming|late|not_clock|partial|anomaly|mismatch|unassigned|risk/i.test(key)) return 'warning';
    if (/paid|success|complete|active|good|matched|approved|released/i.test(key)) return 'success';
    if (/amount|nominal|value|revenue|omset|total_transactions|balance/i.test(key)) return 'accent';

    return 'neutral';
};

const summaryIcon = (key: string): string => {
    if (/order|pesanan|ecommerce/i.test(key)) return 'mdi-receipt-text-outline';
    if (/pending|unpaid|waiting/i.test(key)) return 'mdi-clock-outline';
    if (/reject|fail|bad|danger|fraud|damage|expired|anomaly|risk|mismatch|dead/i.test(key)) return 'mdi-alert-circle-outline';
    if (/paid|success|complete|active|good|approved/i.test(key)) return 'mdi-check-circle-outline';
    if (/amount|nominal|value|revenue|omset|balance|total_amount|cash|spend|sales/i.test(key)) return 'mdi-cash-multiple';
    if (/customer|member|referr|attend/i.test(key)) return 'mdi-account-group-outline';
    if (/item|sku|qty|stock|product/i.test(key)) return 'mdi-package-variant-closed';
    if (/today|date/i.test(key)) return 'mdi-calendar-today';
    if (/warehouse/i.test(key)) return 'mdi-warehouse';
    if (/store|cabang/i.test(key)) return 'mdi-storefront-outline';

    return 'mdi-chart-box-outline';
};

const formatSummaryValue = (key: string, value: number): string => {
    if (isCurrencyKey(key)) return formatCurrency(value);
    if (isPercentKey(key)) return formatPercent(value);

    return formatNumber(value);
};

const summaryCards = computed(() =>
    Object.entries(summarySource.value)
        .filter(([, value]) => typeof value === 'number' || (typeof value === 'string' && value !== '' && !Number.isNaN(Number(value))))
        .slice(0, 8)
        .map(([key, value]) => {
            const numeric = Number(value);

            return {
                key,
                label: summaryLabel(key),
                text: formatSummaryValue(key, numeric),
                tone: summaryTone(key),
                icon: summaryIcon(key),
            };
        }),
);

const formatNumber = (value: number): string =>
    new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(value);

const formatCurrency = (value: number): string =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatPercent = (value: number): string => `${formatNumber(value)}%`;

const financeSummary = computed<FinanceSummary | null>(() => {
    const finance = props.data?.finance;

    return isObject(finance) ? (finance as FinanceSummary) : null;
});

const financeFlow = computed(() => {
    const finance = financeSummary.value;

    if (!finance) return [];

    return [
        {
            key: 'gross',
            label: 'Omset Kotor',
            value: formatCurrency(finance.gross_sales),
            note: 'Harga jual sebelum potongan',
            tone: 'accent',
        },
        {
            key: 'product-discount',
            label: 'Diskon Produk',
            value: `- ${formatCurrency(finance.product_discount)}`,
            note: `${formatPercent(finance.product_discount_percent)} dari omset kotor`,
            tone: 'deduction',
        },
        {
            key: 'after-product-discount',
            label: 'Setelah Diskon Produk',
            value: formatCurrency(finance.net_after_product_discount),
            note: 'Omset kotor dikurangi diskon produk, minimum 0',
            tone: 'neutral',
        },
        {
            key: 'voucher-discount',
            label: 'Voucher / Poin',
            value: `- ${formatCurrency(finance.voucher_discount)}`,
            note: `${formatPercent(finance.voucher_discount_percent)} dari omset kotor`,
            tone: 'deduction',
        },
        {
            key: 'net',
            label: 'Omset Bersih',
            value: formatCurrency(finance.net_sales),
            note: 'Pendapatan setelah seluruh potongan',
            tone: 'success',
        },
    ];
});

const financeKpis = computed(() => {
    const finance = financeSummary.value;

    if (!finance) return [];

    return [
        {
            key: 'base-price',
            label: 'Nilai Harga Dasar',
            value: formatCurrency(finance.base_price),
            note: 'Harga base produk, acuan laba kotor',
        },
        {
            key: 'gross-profit',
            label: 'Laba Kotor',
            value: formatCurrency(finance.gross_profit),
            note: 'Omset bersih dikurangi nilai harga dasar',
        },
        {
            key: 'margin',
            label: 'Margin Kotor',
            value: formatPercent(finance.gross_margin_percent),
            note: 'Laba kotor dibagi omset bersih',
        },
        {
            key: 'effective-discount',
            label: 'Diskon Efektif',
            value: formatPercent(finance.effective_discount_percent),
            note: 'Total potongan dibagi omset kotor',
        },
        {
            key: 'average-net',
            label: 'Rata-rata Omset Bersih',
            value: formatCurrency(finance.average_net_per_transaction),
            note: 'Omset bersih dibagi transaksi unik',
        },
        {
            key: 'transactions',
            label: 'Transaksi',
            value: formatNumber(finance.transactions),
            note: `${formatNumber(finance.qty)} barang terjual`,
        },
    ];
});

const formatDateValue = (value: unknown, key: string): string => {
    if (value === null || value === undefined || value === '') return '-';

    const raw = String(value);

    if (/^\d{4}-\d{2}$/.test(raw)) return raw;

    const parsed = /^\d{4}-\d{2}-\d{2}$/.test(raw) ? new Date(`${raw}T00:00:00`) : new Date(raw);

    if (Number.isNaN(parsed.getTime())) return raw;

    const withTime = /(_at$|time|report_time|scan_time|last_item_update)/i.test(key) && !/date$/i.test(key);
    const options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    };

    return new Intl.DateTimeFormat('id-ID', options).format(parsed);
};

const statusTone = (value: unknown, key: string): string => {
    const text = String(value ?? '').toLowerCase();

    if (/sesuai|matched|match|valid|normal/.test(text)) return 'success';
    if (/paid|success|completed|complete|active|good|approved|released|passed|passed_all|lunas|selesai|verified|available|converted|done/.test(text)) return 'success';
    if (/pending|waiting|process|partial|slow|low|expiring|soon|warning|review|hold|unpaid|unassigned|belum|menunggu|perlu|not_started/.test(text)) return 'warning';
    if (/failed|failure|rejected|reject|cancelled|canceled|void|bad|expired|error|lost|missing|damaged|danger|ditolak|gagal|batal|rusak|unreleased|lewat 24 jam|overdue|over_24h/.test(text)) return 'danger';
    if (/store|warehouse|shipped|processed|inbound|outbound|display|sold|ecommerce|fixed|percentage|info/.test(text)) return 'accent';

    if (key === 'risk_score') {
        const score = Number(value);

        if (score >= 5) return 'danger';
        if (score >= 3) return 'warning';
    }

    return 'neutral';
};

const booleanTone = (key: string, value: boolean): string => {
    if (!value) return 'neutral';
    if (key === 'is_late') return 'danger';
    if (key === 'is_overtime') return 'accent';

    return 'success';
};

const cellCache = new WeakMap<Row, Map<string, TableCell>>();

const cellFor = (row: Row, key: string): TableCell => {
    let rowCache = cellCache.get(row);

    if (!rowCache) {
        rowCache = new Map<string, TableCell>();
        cellCache.set(row, rowCache);
    }

    const cached = rowCache.get(key);

    if (cached) return cached;

    const cell = buildCell(row, key);
    rowCache.set(key, cell);

    return cell;
};

const buildCell = (row: Row, key: string): TableCell => {
    const value = row[key];

    if (value === null || value === undefined || value === '') {
        return { text: '-', kind: 'text', tone: 'neutral', title: '', wrap: false };
    }

    if (typeof value === 'boolean') {
        return {
            text: value ? 'Ya' : 'Tidak',
            kind: 'badge',
            tone: booleanTone(key, value),
            title: value ? 'Ya' : 'Tidak',
            wrap: false,
        };
    }

    if (Array.isArray(value)) {
        const text = value.length === 0 ? '-' : `${value.length} item`;

        return { text, kind: 'text', tone: 'neutral', title: text, wrap: false };
    }

    if (isObject(value)) {
        const text =
            value.name && value.code
                ? `${value.name} (${value.code})`
                : value.name ?? value.label ?? value.code ?? value.value ?? '-';

        return { text: String(text), kind: 'text', tone: 'neutral', title: String(text), wrap: false };
    }

    if (key === 'value' && (row.type === 'percentage' || row.type === 'fixed')) {
        const numeric = Number(value);

        if (!Number.isNaN(numeric)) {
            return {
                text: row.type === 'percentage' ? formatPercent(numeric) : formatCurrency(numeric),
                kind: row.type === 'percentage' ? 'percent' : 'currency',
                tone: 'neutral',
                title: String(value),
                wrap: false,
            };
        }
    }

    if (isDateKey(key)) {
        const text = formatDateValue(value, key);

        return { text, kind: 'date', tone: 'neutral', title: text, wrap: false };
    }

    if (isCurrencyKey(key)) {
        const numeric = Number(value);

        if (!Number.isNaN(numeric)) {
            return { text: formatCurrency(numeric), kind: 'currency', tone: 'neutral', title: String(value), wrap: false };
        }
    }

    if (isPercentKey(key)) {
        const numeric = Number(value);

        if (!Number.isNaN(numeric)) {
            return { text: formatPercent(numeric), kind: 'percent', tone: 'neutral', title: String(value), wrap: false };
        }
    }

    if (isNumberKey(key)) {
        const numeric = Number(value);

        if (!Number.isNaN(numeric)) {
            return { text: formatNumber(numeric), kind: 'number', tone: 'neutral', title: String(value), wrap: false };
        }
    }

    if (isStatusKey(key)) {
        return {
            text: String(value),
            kind: 'badge',
            tone: statusTone(value, key),
            title: String(value),
            wrap: false,
        };
    }

    const text = String(value);
    const wrap = isLongTextKey(key);

    return { text, kind: 'text', tone: 'neutral', title: text, wrap };
};

const SUPERSEDED_COLUMNS: Record<string, string[]> = {
    status: ['status_label', 'status_text'],
    raw_payment_status: ['payment_status'],
    visit_payment_status: ['payment_status'],
    order_status: ['order_status_label', 'status_label'],
    payment_status: ['payment_status_label'],
    qc_status: ['qc_status_label'],
    release_status: ['release_status_label'],
    tracking_status: ['tracking_status_label'],
    type: ['type_label'],
    source: ['source_label'],
    inbound_type: ['inbound_type_label'],
    outbound_type: ['outbound_type_label'],
    store_recording_status: ['store_recording_status_label'],
    document_status: ['document_status_label'],
};

const columnKeys = computed(() => {
    const keys = new Set<string>();

    for (const row of rows.value.slice(0, 3)) {
        for (const key of Object.keys(row)) {
            if (!isHiddenColumn(key, row[key])) keys.add(key);
        }
    }

    return Array.from(keys).filter((key) => {
        const labels = SUPERSEDED_COLUMNS[key];

        return !labels || !labels.some((label) => (
            keys.has(label) && rows.value.some((row) => {
                const value = row[label];

                return value !== null && value !== undefined && String(value).trim() !== '';
            })
        ));
    });
});

const COLUMN_PRIORITY: Record<string, number> = {
    order_number: 0,
    invoice_number: 0,
    delivery_order_number: 0,
    opname_code: 0,
    transaction_number: 0,
    shipping_number: 0,
    payment_methods: 0,
    payment_method_code: 0,
    payment_method_name: 0,
    product_code: 0,
    product_name: 0,
    customer_name: 0,
    store_name: 0,
    destination_store_name: 0,
    destination_store_code: 0,
    warehouse_name: 0,
    supplier_name: 0,
    location_name: 0,
    employee_name: 0,
    cashier_name: 0,
    username: 0,
    membership_name: 0,
    branch_name: 0,
    current_location_name: 0,
    latest_history_location_name: 0,
    current_area_name: 0,
    latest_area_name: 0,
    name: 0,
    code: 0,
    store: 0,
    product: 0,
    warehouse: 0,
    status: 1,
    order_status: 1,
    payment_status: 1,
    reconciliation_status: 1,
    qc_status: 1,
    release_status: 1,
    condition_name: 1,
    current_condition_name: 1,
    anomaly_label: 1,
    status_label: 1,
    opname_status: 1,
    membership_status: 1,
    tracking_status_label: 1,
    is_late: 1,
    is_overtime: 1,
    is_active: 1,
    is_guest: 1,
    change_amount: 1,
    payment_change: 1,
    occurred_at: 2,
    logged_at: 2,
    performed_at: 2,
    created_at: 2,
    report_at: 2,
    received_date: 2,
    shipment_date: 2,
    released_at: 2,
    completed_at: 2,
    last_activity_at: 2,
    last_inbound_at: 2,
    last_mutation_at: 2,
    date: 2,
    grand_total: 3,
    detail_value: 3,
    total_header_value: 3,
    total_amount: 3,
    paid_amount: 3,
    total_nominal: 3,
    product_price: 3,
    total_value: 3,
    amount: 3,
    total_items: 3,
    total_sku: 3,
    quantity: 3,
    total_qty: 3,
    qty_real: 3,
    qty_available: 3,
    stock_barrier: 3,
    current_balance: 3,
    risk_score: 3,
};

const columnRank = (key: string): number => {
    if (COLUMN_PRIORITY[key] !== undefined) return COLUMN_PRIORITY[key];
    if (isStatusKey(key)) return 1;
    if (isDateKey(key)) return 2;
    if (isCurrencyKey(key) || isPercentKey(key) || isNumberKey(key)) return 3;
    if (/_name$|_code$/.test(key)) return 4;

    return 5;
};

const allColumns = computed<TableColumn[]>(() =>
    columnKeys.value
        .map((key, index) => ({ key, index }))
        .sort((left, right) => {
            const rank = columnRank(left.key) - columnRank(right.key);

            return rank !== 0 ? rank : left.index - right.index;
        })
        .map(({ key }) => {
            const sample = rows.value.find((row) => row[key] !== null && row[key] !== undefined)?.[key];
            const align: 'text-start' | 'text-end' =
                isCurrencyKey(key) || isPercentKey(key) || isNumberKey(key) ? 'text-end' : 'text-start';

            return {
                key,
                label: humanize(key),
                align,
                wrap: isLongTextKey(key) || typeof sample === 'string' && sample.length > 80,
            };
        }),
);

const showAllColumns = ref(false);
const visibleColumns = computed<TableColumn[]>(() =>
    showAllColumns.value ? allColumns.value : allColumns.value.slice(0, 14),
);
const hasMoreColumns = computed(() => allColumns.value.length > 12);

const loading = ref(false);
const errorMessage = computed(() => String(props.data?.errorMessage ?? '').trim());
const validationErrors = computed<string[]>(() => {
    const errors = page.props.errors;

    if (!isObject(errors)) return [];

    const errorBag = errors as Record<string, unknown>;

    return Object.values(errorBag).filter((value): value is string => typeof value === 'string' && value !== '');
});

const hasError = computed(() => errorMessage.value !== '' || validationErrors.value.length > 0);

const recordCount = computed(() => paginator.value?.total ?? rows.value.length);
const rowStart = computed(() => paginator.value?.from ?? (rows.value.length > 0 ? 1 : 0));
const lastUpdate = computed(() => String(props.data?.lastUpdate ?? '').trim());

const AUTO_REFRESH_INTERVAL = 30_000;
const autoRefreshEnabled = ref(false);
const isRefreshing = ref(false);
let autoRefreshTimer: ReturnType<typeof setInterval> | null = null;

const appliedFilterPayload = (): Record<string, string> => {
    const payload: Record<string, string> = {};

    if (isObject(props.filters)) {
        for (const [key, value] of Object.entries(props.filters)) {
            if (key === 'page' || key === 'theme') continue;
            if (value === null || value === undefined || value === '') continue;
            if (typeof value === 'object') continue;

            payload[key] = String(value);
        }
    }

    const filter = periodFilter.value;

    if (filter) {
        payload.period = filter.value;
        payload.year = filter.year;

        if (autoRefreshEnabled.value) {
            payload.auto_refresh = '1';
        }
    }

    return payload;
};

const stopAutoRefresh = () => {
    if (autoRefreshTimer !== null) {
        window.clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }
};

const refreshData = () => {
    if (!autoRefreshEnabled.value || document.hidden || isRefreshing.value || loading.value) return;

    isRefreshing.value = true;

    router.get(props.urls.index, appliedFilterPayload(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

const startAutoRefresh = () => {
    stopAutoRefresh();

    if (!autoRefreshEnabled.value) return;

    autoRefreshTimer = window.setInterval(refreshData, AUTO_REFRESH_INTERVAL);
};

const setAutoRefresh = (enabled: boolean) => {
    autoRefreshEnabled.value = enabled;
    startAutoRefresh();

    if (!periodFilter.value) return;

    isRefreshing.value = true;

    router.get(props.urls.index, {
        ...appliedFilterPayload(),
        auto_refresh: enabled ? '1' : '0',
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

const toggleAutoRefresh = (event: Event) => {
    setAutoRefresh((event.target as HTMLInputElement).checked);
};

const handleVisibilityChange = () => {
    if (!document.hidden && autoRefreshEnabled.value) refreshData();
};

watch(
    () => periodFilter.value?.autoRefresh,
    (value) => {
        const enabled = Boolean(value);

        if (enabled === autoRefreshEnabled.value) return;

        autoRefreshEnabled.value = enabled;
        startAutoRefresh();
    },
    { immediate: true },
);

onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange);
    startAutoRefresh();
});

onBeforeUnmount(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    stopAutoRefresh();
});

const buildPayload = (): Record<string, string> => {
    const payload: Record<string, string> = {};

    for (const field of filterFields.value) {
        payload[field.key] = filterValues[field.key] ?? '';
    }

    if (supportsPerPage.value && filterValues.per_page) {
        payload.per_page = filterValues.per_page;
    }

    return payload;
};

const exportUrl = computed(() => {
    const url = activeDataset.value
        ? props.urls.exports?.[activeDataset.value.key] ?? props.urls.export
        : props.urls.export;

    if (!url) return null;

    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(props.filters as Record<string, unknown>)) {
        if (key !== 'per_page' && key !== 'page' && key !== 'dataset' && value !== '' && value !== null && value !== undefined) {
            params.set(key, String(value));
        }
    }

    const query = params.toString();

    return query ? `${url}${url.includes('?') ? '&' : '?'}${query}` : url;
});

const exportCurrentTable = () => {
    const filters = filterFields.value
        .map((field) => ({
            label: field.label,
            value: field.options.find((option) => option.value === filterValues[field.key])?.label ?? filterValues[field.key],
        }))
        .filter(({ value }) => value !== undefined && value !== null && String(value).trim() !== '');

    downloadExcelTable(
        props.title,
        props.title,
        allColumns.value.map(({ key, label }) => ({ key, label })),
        rows.value,
        {
            title: props.title,
            filters,
            currencyColumns: allColumns.value.filter(({ key }) => isCurrencyKey(key)).map(({ key }) => key),
        },
    );
};

const visitWithPayload = (payload: Record<string, string>) => {
    loading.value = true;

    router.get(props.urls.index, payload, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
            isRefreshing.value = false;
        },
    });
};

const applyFilters = () => {
    const payload = buildPayload();
    const filter = periodFilter.value;

    if (filter) {
        const currentStart = String(props.data?.filters?.start_date ?? '');
        const currentEnd = String(props.data?.filters?.end_date ?? '');
        const start = payload.start_date ?? '';
        const end = payload.end_date ?? '';

        if (!start && !end) {
            payload.period = 'today';
        } else if (start !== currentStart || end !== currentEnd) {
            payload.period = 'custom';
        } else {
            payload.period = filter.value;
        }

        payload.year = filter.year;

        if (autoRefreshEnabled.value) {
            payload.auto_refresh = '1';
        }
    }

    visitWithPayload(payload);
};

const applyPeriod = (period: string) => {
    const filter = periodFilter.value;

    if (!filter) return;

    const payload = buildPayload();
    delete payload.start_date;
    delete payload.end_date;
    payload.period = period;
    payload.year = filter.year;

    if (autoRefreshEnabled.value) {
        payload.auto_refresh = '1';
    }

    visitWithPayload(payload);
};

const changePeriodYear = (event: Event) => {
    const filter = periodFilter.value;

    if (!filter) return;

    const payload = buildPayload();
    delete payload.start_date;
    delete payload.end_date;
    payload.period = 'year';
    payload.year = (event.target as HTMLSelectElement).value;

    if (autoRefreshEnabled.value) {
        payload.auto_refresh = '1';
    }

    visitWithPayload(payload);
};

const resetFilters = () => {
    loading.value = true;

    router.get(props.urls.index, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const removeFilter = (key: string) => {
    if (filterValues[key] !== undefined) filterValues[key] = '';

    applyFilters();
};

const changePerPage = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;

    filterValues.per_page = value;
    applyFilters();
};

const selectDataset = (key: string) => {
    currentDatasetKey.value = key;
};

</script>

<template>
    <Head :title="props.title" />

    <div class="monitor-page">
        <header class="monitor-header">
            <div class="monitor-header-copy">
                <nav class="monitor-breadcrumb" aria-label="Breadcrumb">
                    <Link href="/monitoring">Monitoring</Link>
                    <span aria-hidden="true">/</span>
                    <span>{{ props.title }}</span>
                </nav>
                <h1>{{ props.title }}</h1>
                <p>{{ reportDescription }}</p>
            </div>

            <div class="monitor-header-meta">
                <span v-if="lastUpdate" class="monitor-meta-item">
                    <i class="mdi mdi-clock-outline" aria-hidden="true"></i>
                    Diperbarui {{ lastUpdate }}
                </span>
                <span class="monitor-meta-item">
                    <i class="mdi mdi-database-outline" aria-hidden="true"></i>
                    {{ new Intl.NumberFormat('id-ID').format(recordCount) }} data
                </span>
                <Link v-if="props.urls.email_alert_settings" :href="props.urls.email_alert_settings" class="monitor-back-link">
                    <i class="mdi mdi-email-alert-outline" aria-hidden="true"></i>
                    Jadwal Email Alert
                </Link>
                <Link href="/monitoring" class="monitor-back-link">
                    <i class="mdi mdi-arrow-left" aria-hidden="true"></i>
                    Command Center
                </Link>
            </div>
        </header>

        <div v-if="hasError" class="alert alert-danger monitor-alert" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="mdi mdi-alert-circle-outline monitor-alert-icon" aria-hidden="true"></i>
                <div>
                    <strong>Data belum bisa ditampilkan sepenuhnya.</strong>
                    <p v-if="errorMessage" class="mb-0">{{ errorMessage }}</p>
                    <ul v-if="validationErrors.length" class="mb-0 ps-3">
                        <li v-for="message in validationErrors" :key="message">{{ message }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <section
            v-if="periodFilter"
            class="card monitor-card monitor-period-card"
            aria-labelledby="monitor-period-title"
        >
            <div class="card-body">
                <div class="monitor-period-head">
                    <div>
                        <h2 id="monitor-period-title" class="monitor-section-title">
                            <i class="mdi mdi-calendar-range" aria-hidden="true"></i>
                            Periode pemasukan
                        </h2>
                        <p>
                            <strong>{{ periodFilter.rangeLabel }}</strong>
                            <span class="monitor-period-separator" aria-hidden="true">·</span>
                            {{ periodFilter.label }}
                        </p>
                    </div>

                    <div class="monitor-period-controls">
                        <div class="monitor-period-options" role="group" aria-label="Pilih periode pemasukan">
                            <button
                                v-for="option in periodOptions"
                                :key="option.value"
                                type="button"
                                class="monitor-period-option"
                                :class="{ 'is-active': periodFilter.value === option.value }"
                                :aria-pressed="periodFilter.value === option.value"
                                :disabled="loading"
                                @click="applyPeriod(option.value)"
                            >
                                {{ option.label }}
                            </button>
                        </div>

                        <div v-if="periodFilter.value === 'year'" class="monitor-period-year">
                            <label for="monitor-period-year" class="form-label">Pilih tahun</label>
                            <select
                                id="monitor-period-year"
                                class="form-select form-select-sm"
                                :value="periodFilter.year"
                                :disabled="loading"
                                @change="changePeriodYear"
                            >
                                <option v-for="year in periodFilter.years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="monitor-period-foot">
                    <p class="monitor-period-hint">
                        <i class="mdi mdi-information-outline" aria-hidden="true"></i>
                        Pilih periode cepat, atau ubah tanggal pada filter di bawah untuk rentang kustom.
                    </p>

                    <div class="monitor-refresh">
                        <label class="monitor-refresh-toggle">
                            <input
                                type="checkbox"
                                role="switch"
                                :checked="autoRefreshEnabled"
                                :disabled="loading"
                                @change="toggleAutoRefresh"
                            />
                            <span>Perbarui otomatis setiap 30 detik</span>
                        </label>
                        <span v-if="isRefreshing" class="monitor-refresh-state" role="status">
                            <i class="mdi mdi-loading mdi-spin" aria-hidden="true"></i>
                            Memperbarui data
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <nav
            v-if="props.data?.omsetTab && props.data?.omsetUrls"
            class="monitor-omset-tabs"
            aria-label="Jenis laporan omset"
        >
            <Link
                :href="props.data.omsetUrls.cabang"
                class="monitor-omset-tab"
                :class="{ 'is-active': props.data.omsetTab === 'cabang' }"
                :aria-current="props.data.omsetTab === 'cabang' ? 'page' : undefined"
            >
                <i class="mdi mdi-chart-bar" aria-hidden="true"></i>
                Omset per Cabang
            </Link>
            <Link
                :href="props.data.omsetUrls.kotor"
                class="monitor-omset-tab"
                :class="{ 'is-active': props.data.omsetTab === 'kotor' }"
                :aria-current="props.data.omsetTab === 'kotor' ? 'page' : undefined"
            >
                <i class="mdi mdi-chart-areaspline" aria-hidden="true"></i>
                Omset Kotor &amp; Bersih
            </Link>
        </nav>

        <nav
            v-if="props.data?.orderTabs"
            class="monitor-omset-tabs"
            aria-label="Jenis order handled"
        >
            <Link
                v-for="tab in props.data.orderTabs"
                :key="tab.key"
                :href="tab.url"
                class="monitor-omset-tab"
                :class="{ 'is-active': tab.active }"
                :aria-current="tab.active ? 'page' : undefined"
            >
                <i class="mdi" :class="tab.icon" aria-hidden="true"></i>
                {{ tab.label }}
            </Link>
        </nav>

        <section v-if="visibleFilterFields.length" class="card monitor-card monitor-filter-card" aria-labelledby="monitor-filter-title">
            <div class="card-body">
                <div class="monitor-filter-head">
                    <div>
                        <h2 id="monitor-filter-title" class="monitor-section-title">
                            <i class="mdi mdi-filter-variant" aria-hidden="true"></i>
                            {{ periodFilter ? 'Filter data' : 'Filter audit' }}
                        </h2>
                        <p>Persempit data berdasarkan periode, status, lokasi, atau kata kunci.</p>
                    </div>
                    <span class="monitor-filter-count">
                        {{ activeFilterCount > 0 ? `${activeFilterCount} filter diterapkan` : 'Filter default' }}
                    </span>
                </div>

                <form class="monitor-filter-grid" @submit.prevent="applyFilters">
                    <div
                        v-for="field in visibleFilterFields"
                        :key="field.key"
                        class="monitor-field"
                        :class="{ 'monitor-field-wide': field.wide }"
                    >
                        <label :for="`monitor-filter-${field.key}`" class="form-label">{{ field.label }}</label>

                        <SearchableSelect
                            v-if="field.type === 'select' && field.options.length > 8"
                            :id="`monitor-filter-${field.key}`"
                            v-model="filterValues[field.key]"
                            :options="field.options"
                            :placeholder="emptyOptionLabel(field)"
                            :search-placeholder="`Cari ${field.label.toLowerCase()}...`"
                            :aria-label="field.label"
                        />

                        <select
                            v-else-if="field.type === 'select'"
                            :id="`monitor-filter-${field.key}`"
                            v-model="filterValues[field.key]"
                            class="form-select form-select-sm"
                        >
                            <option value="">{{ emptyOptionLabel(field) }}</option>
                            <option v-for="option in field.options" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>

                        <input
                            v-else
                            :id="`monitor-filter-${field.key}`"
                            v-model="filterValues[field.key]"
                            class="form-control form-control-sm"
                            :type="field.type"
                            :placeholder="field.placeholder"
                            :inputmode="field.type === 'number' ? 'numeric' : undefined"
                            :min="field.type === 'number' ? 0 : undefined"
                        />
                    </div>

                    <div class="monitor-filter-actions">
                        <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="loading" @click="resetFilters">
                            <i class="mdi mdi-restore" aria-hidden="true"></i>
                            Reset
                        </button>
                        <button class="btn btn-sm btn-primary" type="submit" :disabled="loading">
                            <i class="mdi" :class="loading ? 'mdi-loading mdi-spin' : 'mdi-magnify'" aria-hidden="true"></i>
                            {{ loading ? 'Memuat...' : 'Terapkan filter' }}
                        </button>
                    </div>
                </form>

                <div v-if="activeFilterChips.length" class="monitor-chips" aria-label="Filter aktif">
                    <button
                        v-for="chip in activeFilterChips"
                        :key="chip.key"
                        type="button"
                        class="monitor-chip"
                        :aria-label="`Hapus filter ${chip.label}`"
                        @click="removeFilter(chip.key)"
                    >
                        <span>{{ chip.label }}: {{ chip.value }}</span>
                        <i class="mdi mdi-close" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </section>

        <section v-if="financeSummary" class="card monitor-card monitor-finance" aria-labelledby="monitor-finance-title">
            <div class="card-body">
                <div class="monitor-finance-head">
                    <div>
                        <h2 id="monitor-finance-title" class="monitor-section-title">
                            <i class="mdi mdi-finance" aria-hidden="true"></i>
                            Ringkasan Keuangan
                        </h2>
                        <p>Angka mengikuti filter aktif. Alur di bawah menunjukkan asal setiap angka, dari harga jual sampai omset bersih.</p>
                    </div>
                    <span
                        class="monitor-finance-badge"
                        :class="financeSummary.reconciliation.is_balanced ? 'is-balanced' : 'is-warning'"
                    >
                        <i
                            class="mdi"
                            :class="financeSummary.reconciliation.is_balanced ? 'mdi-check-circle-outline' : 'mdi-alert-circle-outline'"
                            aria-hidden="true"
                        ></i>
                        {{ financeSummary.reconciliation.is_balanced ? 'Rekonsiliasi cocok' : 'Perlu dicek' }}
                    </span>
                </div>

                <p v-if="financeSummary.base_price <= 0" class="monitor-finance-alert" role="status">
                    <i class="mdi mdi-alert-outline" aria-hidden="true"></i>
                    Nilai harga dasar belum tersedia untuk periode ini, sehingga laba kotor dan margin belum dapat dibaca sebagai laba sebenarnya.
                </p>

                <ol class="monitor-finance-flow" aria-label="Alur perhitungan omset">
                    <li
                        v-for="step in financeFlow"
                        :key="step.key"
                        class="monitor-finance-step"
                        :class="`is-${step.tone}`"
                    >
                        <span>{{ step.label }}</span>
                        <strong>{{ step.value }}</strong>
                        <small>{{ step.note }}</small>
                    </li>
                </ol>

                <div class="monitor-finance-kpis">
                    <article v-for="item in financeKpis" :key="item.key" class="monitor-finance-kpi">
                        <span>{{ item.label }}</span>
                        <strong>{{ item.value }}</strong>
                        <small>{{ item.note }}</small>
                    </article>
                </div>

                <div
                    class="monitor-finance-check"
                    :class="financeSummary.reconciliation.is_balanced ? 'is-balanced' : 'is-warning'"
                >
                    <div>
                        <strong>Rekonsiliasi angka</strong>
                        <p>
                            Setelah Diskon Produk - Voucher/Poin =
                            {{ formatCurrency(financeSummary.reconciliation.expected_net) }}.
                            Omset Bersih tercatat {{ formatCurrency(financeSummary.reconciliation.actual_net) }}.
                        </p>
                    </div>
                    <div class="monitor-finance-check-notes">
                        <span v-if="financeSummary.reconciliation.clamped_discount > 0">
                            Potongan dibatasi 0: {{ formatCurrency(financeSummary.reconciliation.clamped_discount) }}
                        </span>
                        <span v-if="financeSummary.reconciliation.unexplained_difference < 0">
                            Selisih tak terjelaskan: {{ formatCurrency(financeSummary.reconciliation.unexplained_difference) }}
                        </span>
                        <span
                            v-if="financeSummary.reconciliation.is_balanced && financeSummary.reconciliation.clamped_discount <= 0"
                        >
                            Tidak ada selisih
                        </span>
                    </div>
                </div>

                <div v-if="financeSummary.channels?.length" class="monitor-finance-channels">
                    <h3>Rincian per kanal</h3>
                    <div class="table-responsive">
                        <table class="table monitor-finance-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Kanal</th>
                                    <th scope="col" class="text-end">Omset Kotor</th>
                                    <th scope="col" class="text-end">Diskon Produk</th>
                                    <th scope="col" class="text-end">Voucher / Poin</th>
                                    <th scope="col" class="text-end">Omset Bersih</th>
                                    <th scope="col" class="text-end">Nilai Harga Dasar</th>
                                    <th scope="col" class="text-end">Laba Kotor</th>
                                    <th scope="col" class="text-end">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="channel in financeSummary.channels" :key="channel.key">
                                    <td>
                                        <strong>{{ channel.label }}</strong>
                                        <small>{{ formatNumber(channel.transactions) }} transaksi</small>
                                    </td>
                                    <td class="text-end">{{ formatCurrency(channel.gross_sales) }}</td>
                                    <td class="text-end">{{ formatCurrency(channel.product_discount) }}</td>
                                    <td class="text-end">{{ formatCurrency(channel.voucher_discount) }}</td>
                                    <td class="text-end"><strong>{{ formatCurrency(channel.net_sales) }}</strong></td>
                                    <td class="text-end">{{ formatCurrency(channel.base_price) }}</td>
                                    <td class="text-end">{{ formatCurrency(channel.gross_profit) }}</td>
                                    <td class="text-end">{{ formatPercent(channel.gross_margin_percent) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <details class="monitor-finance-method">
                    <summary>Cara membaca dan sumber data</summary>
                    <dl class="monitor-finance-definitions">
                        <div>
                            <dt>Omset Kotor</dt>
                            <dd>Harga jual barang sebelum diskon produk, voucher, poin, atau diskon pesanan. Untuk ecommerce, qty dikalikan harga jual.</dd>
                        </div>
                        <div>
                            <dt>Diskon Produk</dt>
                            <dd>Potongan per barang yang tercatat pada detail transaksi.</dd>
                        </div>
                        <div>
                            <dt>Setelah Diskon Produk</dt>
                            <dd>Omset kotor dikurangi diskon produk (minimum 0), sebelum voucher, poin, atau diskon pesanan.</dd>
                        </div>
                        <div>
                            <dt>Voucher / Poin</dt>
                            <dd>Porsi voucher, poin, atau diskon pesanan yang dialokasikan proporsional ke setiap barang.</dd>
                        </div>
                        <div>
                            <dt>Omset Bersih</dt>
                            <dd>Pendapatan penjualan setelah seluruh potongan. Bukan kas final karena biaya pembayaran, ongkir, dan pajak tidak dikurangkan di sini.</dd>
                        </div>
                        <div>
                            <dt>Nilai Harga Dasar</dt>
                            <dd>Harga base produk pada periode transaksi. Jika harga base tidak tersedia, nilainya 0 sehingga laba kotor bisa tampak lebih tinggi.</dd>
                        </div>
                        <div>
                            <dt>Laba Kotor</dt>
                            <dd>Omset bersih dikurangi nilai harga dasar. Belum termasuk biaya operasional, payment fee, ongkir, atau pajak.</dd>
                        </div>
                        <div>
                            <dt>Margin Kotor dan Diskon Efektif</dt>
                            <dd>Margin kotor = laba kotor dibagi omset bersih. Diskon efektif = total potongan dibagi omset kotor.</dd>
                        </div>
                        <div>
                            <dt>Cakupan data</dt>
                            <dd>POS hanya pembayaran lunas dan detail non-void. Ecommerce hanya order non-cancelled dengan pembayaran lunas. Retur, void, dan transaksi belum dibayar tidak masuk.</dd>
                        </div>
                        <div>
                            <dt>Rekonsiliasi</dt>
                            <dd>Potongan yang melebihi harga jual dibatasi 0 dan dilaporkan terpisah sebagai potongan dibatasi. Selisih tak terjelaskan berarti angka omset bersih tidak sesuai rumus dan perlu diperiksa.</dd>
                        </div>
                    </dl>
                </details>
            </div>
        </section>

        <section v-if="summaryCards.length && !financeSummary" class="monitor-summary" aria-label="Ringkasan data">
            <article
                v-for="card in summaryCards"
                :key="card.key"
                class="monitor-summary-card"
                :class="`is-${card.tone}`"
            >
                <div class="monitor-summary-icon" aria-hidden="true">
                    <i class="mdi" :class="card.icon"></i>
                </div>
                <div>
                    <div class="monitor-summary-label">{{ card.label }}</div>
                    <div class="monitor-summary-value">{{ card.text }}</div>
                </div>
            </article>
        </section>
        <p v-if="summaryCards.length && !financeSummary" class="monitor-summary-note">
            <i class="mdi mdi-information-outline" aria-hidden="true"></i>
            Angka ringkasan mengikuti filter aktif. Tabel di bawah menampilkan rincian, bukan penjumlahan tambahan.
        </p>

        <section class="card monitor-card monitor-data-card" aria-labelledby="monitor-data-title">
            <div class="card-header monitor-data-head">
                <div>
                    <h2 id="monitor-data-title" class="monitor-section-title">
                        <i class="mdi mdi-table-large" aria-hidden="true"></i>
                        {{ activeDataset?.label ?? 'Data' }}
                    </h2>
                    <p v-if="activeDatasetDescription" class="monitor-dataset-description">{{ activeDatasetDescription }}</p>
                    <p v-if="paginator" class="mb-0">
                        Menampilkan {{ paginator.from ?? 0 }} - {{ paginator.to ?? rows.length }} dari
                        {{ new Intl.NumberFormat('id-ID').format(paginator.total ?? rows.length) }} data
                    </p>
                    <p v-else class="mb-0">
                        {{ new Intl.NumberFormat('id-ID').format(rows.length) }} data
                    </p>
                </div>

                <div class="monitor-data-tools">
                    <Link v-if="operationsUrl" :href="operationsUrl" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-open-in-app" aria-hidden="true"></i>
                        Buka Operasional
                    </Link>
                    <button
                        v-if="hasMoreColumns"
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        @click="showAllColumns = !showAllColumns"
                    >
                        <i class="mdi" :class="showAllColumns ? 'mdi-table-column-width' : 'mdi-table-column-plus-after'" aria-hidden="true"></i>
                        {{ showAllColumns ? 'Ringkas kolom' : `Semua kolom (${allColumns.length})` }}
                    </button>
                    <a v-if="exportUrl" :href="exportUrl" class="btn btn-sm btn-outline-primary">
                        <i class="mdi mdi-download" aria-hidden="true"></i>
                        Ekspor Excel
                    </a>
                    <button v-else type="button" class="btn btn-sm btn-outline-primary" @click="exportCurrentTable">
                        <i class="mdi mdi-file-excel-outline" aria-hidden="true"></i>
                        Ekspor Excel
                    </button>
                </div>
            </div>

            <p v-if="datasets.length > 1" class="monitor-data-note">
                <i class="mdi mdi-information-outline" aria-hidden="true"></i>
                Setiap tab adalah kumpulan data berbeda. Gunakan tab untuk melihat rincian; angka ringkasan mengikuti filter yang sama.
            </p>

            <div v-if="datasets.length > 1" class="monitor-tabs" role="tablist" aria-label="Pilih data">
                <button
                    v-for="dataset in datasets"
                    :key="dataset.key"
                    type="button"
                    role="tab"
                    class="monitor-tab"
                    :class="{ 'is-active': dataset.key === activeDataset?.key }"
                    :aria-selected="dataset.key === activeDataset?.key"
                    @click="selectDataset(dataset.key)"
                >
                    {{ dataset.label }}
                    <span>{{ dataset.paginator?.total ?? dataset.rows.length }}</span>
                </button>
            </div>

            <div v-if="loading" class="monitor-state" role="status" aria-live="polite">
                <i class="mdi mdi-loading mdi-spin monitor-state-icon" aria-hidden="true"></i>
                <strong>Memuat data</strong>
                <p>Filter sedang diterapkan ke laporan.</p>
            </div>

            <div v-else-if="rows.length === 0" class="monitor-state">
                <i class="mdi mdi-file-search-outline monitor-state-icon" aria-hidden="true"></i>
                <strong>{{ activeFilterCount > 0 ? 'Belum ada data yang cocok' : 'Belum ada data pada periode ini' }}</strong>
                <p>
                    {{ activeFilterCount > 0
                        ? 'Coba ubah periode, status, lokasi, atau kata kunci filter.'
                        : 'Ubah periode atau pilih filter lain untuk melihat data.' }}
                </p>
                <button v-if="activeFilterCount > 0" type="button" class="btn btn-sm btn-primary" @click="resetFilters">
                    <i class="mdi mdi-restore" aria-hidden="true"></i>
                    Reset filter
                </button>
            </div>

            <div v-else class="monitor-table-wrap">
                <table class="table table-hover align-middle monitor-table mb-0">
                    <caption class="visually-hidden">Data {{ props.title }} - {{ activeDataset?.label ?? 'Data' }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="monitor-row-number">#</th>
                            <th
                                v-for="column in visibleColumns"
                                :key="column.key"
                                scope="col"
                                :class="column.align"
                            >
                                {{ column.label }}
                            </th>
                            <th v-if="hasRowDetails" scope="col" class="text-end">Detail</th>
                            <th v-if="hasApprovalActions" scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in rows" :key="`${activeDataset?.key ?? 'data'}-${row.id ?? row.uuid ?? index}`">
                            <td class="monitor-row-number">{{ rowStart + index }}</td>
                            <td
                                v-for="column in visibleColumns"
                                :key="column.key"
                                :class="[column.align, { 'monitor-cell-wrap': column.wrap }]"
                            >
                                <template v-if="column.key === 'qty_reserved' && canOpenReservations(row)">
                                    <button
                                        type="button"
                                        class="monitor-reservation-link"
                                        :aria-label="`Lihat ${cellFor(row, column.key).text} reservasi untuk ${row.product_name ?? 'produk'} di ${row.warehouse_name ?? row.store_name ?? 'lokasi ini'}`"
                                        title="Lihat order dan pihak yang mereservasi stok"
                                        @click="openReservations(row)"
                                    >
                                        {{ cellFor(row, column.key).text }}
                                        <i class="mdi mdi-account-search-outline" aria-hidden="true"></i>
                                    </button>
                                </template>
                                <template v-else-if="cellFor(row, column.key).kind === 'badge'">
                                    <span
                                        class="monitor-badge"
                                        :class="`is-${cellFor(row, column.key).tone}`"
                                        :title="cellFor(row, column.key).title"
                                    >
                                        {{ cellFor(row, column.key).text }}
                                    </span>
                                </template>
                                <template v-else>
                                    <span :title="cellFor(row, column.key).title">{{ cellFor(row, column.key).text }}</span>
                                </template>
                            </td>
                            <td v-if="hasRowDetails" class="text-end">
                                <button
                                    v-if="rowHasDetails(row)"
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    :aria-label="`Buka detail dokumen baris ${rowStart + index}`"
                                    @click="openRowDetail(row)"
                                >
                                    <i class="mdi mdi-eye-outline" aria-hidden="true"></i>
                                    Detail
                                </button>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td v-if="hasApprovalActions" class="text-end">
                                <template v-if="row.can_decide">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success me-1"
                                        title="Setujui sesi opname"
                                        @click="decideOpname(row, 'approve')"
                                    >
                                        <i class="mdi mdi-check" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Tolak sesi opname"
                                        @click="decideOpname(row, 'reject')"
                                    >
                                        <i class="mdi mdi-close" aria-hidden="true"></i>
                                    </button>
                                </template>
                                <span v-else class="text-muted">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!loading && rows.length > 0" class="card-footer monitor-footer">
                <div class="monitor-footer-meta">
                    <span v-if="paginator" class="text-muted">
                        Halaman {{ paginator.current_page ?? 1 }} dari {{ paginator.last_page ?? 1 }}
                    </span>
                    <label v-if="supportsPerPage" class="monitor-per-page">
                        <span>Baris</span>
                        <select class="form-select form-select-sm" :value="perPageValue" aria-label="Baris per halaman" @change="changePerPage">
                            <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                        </select>
                    </label>
                </div>

                <PaginationLinks v-if="paginator && paginator.links.length > 3" :links="paginator.links" />
            </div>
        </section>

        <Teleport to="body">
            <div v-if="selectedRow" class="monitor-modal-backdrop" @click.self="closeRowDetail">
                <div class="monitor-modal" role="dialog" aria-modal="true" aria-labelledby="monitor-detail-title">
                    <header class="monitor-modal-header">
                        <div>
                            <h2 id="monitor-detail-title">{{ props.title }}</h2>
                            <p>{{ detailSectionDescription }}</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" aria-label="Tutup detail dokumen" @click="closeRowDetail">
                            <i class="mdi mdi-close" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div class="monitor-modal-body">
                        <dl class="monitor-detail-grid">
                            <div v-for="column in allColumns" :key="`detail-${column.key}`">
                                <dt>{{ column.label }}</dt>
                                <dd>
                                    <span v-if="cellFor(selectedRow, column.key).kind === 'badge'" class="monitor-badge" :class="`is-${cellFor(selectedRow, column.key).tone}`">
                                        {{ cellFor(selectedRow, column.key).text }}
                                    </span>
                                    <template v-else>{{ cellFor(selectedRow, column.key).text }}</template>
                                </dd>
                            </div>
                        </dl>

                        <section v-if="detailRows.length" class="monitor-detail-products" aria-labelledby="monitor-detail-products-title">
                            <h3 id="monitor-detail-products-title">{{ detailSectionTitle }}</h3>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle monitor-detail-table mb-0">
                                    <thead>
                                        <tr>
                                            <th v-for="column in detailColumns" :key="`product-${column.key}`" :class="column.align">{{ column.label }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(detail, index) in detailRows" :key="`detail-row-${index}`">
                                            <td v-for="column in detailColumns" :key="`${index}-${column.key}`" :class="[column.align, { 'monitor-cell-wrap': column.wrap }]">
                                                <span v-if="cellFor(detail, column.key).kind === 'badge'" class="monitor-badge" :class="`is-${cellFor(detail, column.key).tone}`">
                                                    {{ cellFor(detail, column.key).text }}
                                                </span>
                                                <template v-else>{{ cellFor(detail, column.key).text }}</template>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                        <p v-else class="text-muted small mb-0">Tidak ada rincian pendukung pada data ini.</p>
                    </div>

                    <footer class="monitor-modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeRowDetail">Tutup</button>
                    </footer>
                </div>
            </div>
        </Teleport>

        <StockReservationDetailModal
            :open="reservationRow !== null"
            :url="reservationUrl"
            :handler-type="reservationContext?.handlerType ?? 'warehouse'"
            :handler-id="reservationContext?.handlerId ?? null"
            :product-id="reservationContext?.productId ?? null"
            :product-name="reservationContext?.productName"
            :product-code="reservationContext?.productCode"
            :location-name="reservationContext?.locationName"
            :location-code="reservationContext?.locationCode"
            :unit-name="reservationContext?.unitName"
            :reserved-qty="reservationContext?.reservedQty ?? 0"
            @close="closeReservations"
        />
    </div>
</template>

<style scoped>
.monitor-page {
    --monitor-radius: 0.9rem;
    --monitor-radius-sm: 0.6rem;
    color: var(--app-ink);
}

.monitor-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: var(--app-hero-bg);
    color: var(--app-hero-text);
}

.monitor-header-copy {
    max-width: 46rem;
}

.monitor-breadcrumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.55rem;
    color: var(--app-hero-eyebrow);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.monitor-breadcrumb a {
    color: var(--app-hero-eyebrow);
    text-decoration: none;
}

.monitor-breadcrumb a:hover {
    color: var(--app-hero-text);
    text-decoration: underline;
}

.monitor-header h1 {
    margin: 0 0 0.35rem;
    font-size: clamp(1.3rem, 2vw, 1.6rem);
    color: var(--app-hero-text);
}

.monitor-header p {
    max-width: 42rem;
    margin: 0;
    color: var(--app-hero-muted);
}

.monitor-header-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.55rem;
}

.monitor-meta-item,
.monitor-back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 2.4rem;
    padding: 0.4rem 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 999px;
    color: var(--app-hero-text);
    font-size: 0.78rem;
    text-decoration: none;
}

.monitor-back-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--app-hero-text);
}

.monitor-alert {
    border-radius: var(--monitor-radius);
}

.monitor-alert-icon {
    font-size: 1.2rem;
    line-height: 1.2;
}

.monitor-card {
    border: 1px solid var(--app-border);
    border-radius: var(--monitor-radius);
    background: var(--app-surface);
    box-shadow: var(--app-shadow);
}

.monitor-omset-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 1rem;
    padding: 0.45rem;
    border: 1px solid var(--app-border);
    border-radius: 0.8rem;
    background: var(--app-surface);
    box-shadow: var(--app-shadow);
}

.monitor-omset-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 0.8rem;
    border-radius: 0.55rem;
    color: var(--app-muted);
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: none;
}

.monitor-omset-tab:hover {
    background: var(--app-surface-2);
    color: var(--app-ink);
}

.monitor-omset-tab.is-active {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.monitor-period-card {
    margin-bottom: 1rem;
}

.monitor-period-card .card-body {
    padding: 1rem;
}

.monitor-period-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.9rem;
}

.monitor-period-head > div:first-child {
    min-width: 0;
}

.monitor-period-head p {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    margin: 0;
    color: var(--app-muted);
    font-size: 0.82rem;
}

.monitor-period-head strong {
    color: var(--app-ink);
    font-size: 0.95rem;
}

.monitor-period-separator {
    color: var(--app-border);
}

.monitor-period-controls {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.6rem;
}

.monitor-period-options {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    padding: 0.25rem;
    border: 1px solid var(--app-border);
    border-radius: 0.7rem;
    background: var(--app-surface-2);
}

.monitor-period-option {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.5rem;
    padding: 0.4rem 0.8rem;
    border: 0;
    border-radius: 0.5rem;
    background: transparent;
    color: var(--app-muted);
    font-size: 0.8rem;
    font-weight: 700;
    white-space: nowrap;
}

.monitor-period-option:hover {
    background: var(--app-surface);
    color: var(--app-ink);
}

.monitor-period-option.is-active {
    background: var(--app-surface);
    color: var(--app-accent-ink);
    box-shadow: 0 1px 2px rgba(15, 36, 54, 0.12);
}

.monitor-period-option:disabled {
    opacity: 0.65;
}

.monitor-period-year {
    min-width: 8rem;
}

.monitor-period-year .form-label {
    margin-bottom: 0.3rem;
    color: var(--app-muted);
    font-size: 0.75rem;
    font-weight: 700;
}

.monitor-period-year .form-select {
    min-height: 2.5rem;
    border-radius: var(--monitor-radius-sm);
    font-size: 0.82rem;
}

.monitor-period-foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem 1rem;
    margin-top: 0.9rem;
    padding-top: 0.8rem;
    border-top: 1px solid var(--app-border);
}

.monitor-period-hint {
    display: flex;
    align-items: flex-start;
    gap: 0.35rem;
    margin: 0;
    color: var(--app-muted);
    font-size: 0.75rem;
}

.monitor-refresh {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.9rem;
}

.monitor-refresh-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    min-height: 2.75rem;
    margin: 0;
    color: var(--app-ink);
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
}

.monitor-refresh-toggle input {
    appearance: none;
    position: relative;
    flex: 0 0 auto;
    width: 2.3rem;
    height: 1.25rem;
    margin: 0;
    border: 1px solid var(--app-muted);
    border-radius: 999px;
    background: var(--app-surface-3);
    cursor: pointer;
    transition: background-color 160ms ease, border-color 160ms ease;
}

.monitor-refresh-toggle input::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 0.15rem;
    width: 0.9rem;
    height: 0.9rem;
    border-radius: 50%;
    background: var(--app-ink);
    box-shadow: 0 1px 2px rgba(15, 36, 54, 0.25);
    transform: translateY(-50%);
    transition: left 160ms ease;
}

.monitor-refresh-toggle input:checked {
    border-color: var(--app-accent);
    background: var(--app-accent);
}

.monitor-refresh-toggle input:checked::after {
    left: calc(100% - 1.05rem);
    background: var(--app-on-accent);
}

.monitor-refresh-toggle input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.monitor-refresh-state {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    color: var(--app-accent-ink);
    font-size: 0.75rem;
    font-weight: 700;
}

.monitor-filter-card {
    margin-bottom: 1rem;
}

.monitor-filter-head,
.monitor-data-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.9rem;
}

.monitor-data-head {
    margin-bottom: 0;
}

.monitor-section-title {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin: 0 0 0.25rem;
    font-size: 1rem;
}

.monitor-section-title .mdi {
    color: var(--app-accent-ink);
    font-size: 1.1rem;
}

.monitor-filter-head p,
.monitor-data-head p {
    margin: 0;
    color: var(--app-muted);
    font-size: 0.8rem;
}

.monitor-filter-count {
    display: inline-flex;
    align-items: center;
    min-height: 1.8rem;
    padding: 0.2rem 0.65rem;
    border-radius: 999px;
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
    font-size: 0.72rem;
    font-weight: 700;
}

.monitor-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
    gap: 0.8rem;
    align-items: end;
}

.monitor-field {
    min-width: 0;
}

.monitor-field-wide {
    grid-column: span 2;
}

.monitor-field .form-label {
    margin-bottom: 0.3rem;
    color: var(--app-muted);
    font-size: 0.75rem;
    font-weight: 700;
}

.monitor-field .form-control,
.monitor-field .form-select,
.monitor-per-page .form-select {
    min-height: 2.4rem;
    border-radius: var(--monitor-radius-sm);
    font-size: 0.82rem;
}

.monitor-filter-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.5rem;
    grid-column: 1 / -1;
    padding-top: 0.2rem;
}

.monitor-filter-actions .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    min-height: 2.4rem;
    border-radius: var(--monitor-radius-sm);
    font-weight: 700;
}

.monitor-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin-top: 0.9rem;
    padding-top: 0.8rem;
    border-top: 1px solid var(--app-border);
}

.monitor-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 2rem;
    padding: 0.25rem 0.65rem;
    border: 1px solid var(--app-border);
    border-radius: 999px;
    background: var(--app-surface-2);
    color: var(--app-ink);
    font-size: 0.72rem;
}

.monitor-chip:hover {
    border-color: var(--app-accent);
    color: var(--app-accent-ink);
}

.monitor-dataset-description {
    margin: 0.15rem 0 0;
    color: var(--app-muted);
    font-size: 0.78rem;
    line-height: 1.45;
}

.monitor-data-note {
    display: flex;
    align-items: flex-start;
    gap: 0.35rem;
    margin: 0;
    padding: 0.55rem 1rem;
    border-top: 1px solid var(--app-border);
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 0.75rem;
}

.monitor-summary-note {
    display: flex;
    align-items: flex-start;
    gap: 0.35rem;
    margin: -0.5rem 0 1rem;
    color: var(--app-muted);
    font-size: 0.75rem;
}

.monitor-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(10.5rem, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.monitor-summary-card {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    min-height: 5.2rem;
    padding: 0.9rem;
    border: 1px solid var(--app-border);
    border-radius: var(--monitor-radius);
    background: var(--app-surface);
    box-shadow: var(--app-shadow);
}

.monitor-summary-icon {
    display: grid;
    flex: 0 0 auto;
    place-items: center;
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 0.6rem;
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 1.05rem;
}

.monitor-summary-card.is-success .monitor-summary-icon {
    background: var(--app-success-bg);
    color: var(--app-success-ink);
}

.monitor-summary-card.is-warning .monitor-summary-icon {
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
}

.monitor-summary-card.is-danger .monitor-summary-icon {
    background: var(--app-danger-bg);
    color: var(--app-danger-ink);
}

.monitor-summary-card.is-accent .monitor-summary-icon {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.monitor-summary-label {
    color: var(--app-muted);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.monitor-summary-value {
    margin-top: 0.2rem;
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.monitor-data-card {
    overflow: hidden;
}

.monitor-data-head {
    padding: 1rem 1rem 0.9rem;
}

.monitor-data-tools {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.monitor-data-tools .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    min-height: 2.3rem;
    border-radius: var(--monitor-radius-sm);
    font-weight: 700;
}

.monitor-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    padding: 0 1rem 0.9rem;
    border-bottom: 1px solid var(--app-border);
}

.monitor-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 2.3rem;
    padding: 0.35rem 0.7rem;
    border: 1px solid var(--app-border);
    border-radius: 999px;
    background: var(--app-surface);
    color: var(--app-muted);
    font-size: 0.76rem;
    font-weight: 700;
}

.monitor-tab span {
    display: inline-flex;
    align-items: center;
    min-height: 1.25rem;
    padding: 0 0.4rem;
    border-radius: 999px;
    background: var(--app-surface-2);
    font-size: 0.68rem;
}

.monitor-tab.is-active {
    border-color: var(--app-accent);
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.monitor-table-wrap {
    max-height: 68vh;
    overflow: auto;
    overscroll-behavior: contain;
}

.monitor-table {
    min-width: 54rem;
    font-size: 0.8rem;
}

.monitor-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 0.65rem 0.7rem;
    border-bottom: 1px solid var(--app-border);
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
}

.monitor-table tbody td {
    padding: 0.6rem 0.7rem;
    border-color: var(--app-border);
    color: var(--app-ink);
    vertical-align: middle;
    white-space: nowrap;
}

.monitor-table tbody tr:last-child td {
    border-bottom: 0;
}

.monitor-table tbody tr:nth-child(even) {
    background: color-mix(in srgb, var(--app-accent) 2.5%, transparent);
}

.monitor-table tbody tr:hover {
    background: var(--app-surface-2);
}

.monitor-table tbody td > span {
    display: inline-block;
    max-width: 22rem;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}

.monitor-row-number {
    width: 3rem;
    min-width: 3rem;
    color: var(--app-muted);
    font-variant-numeric: tabular-nums;
    text-align: right;
}

.monitor-reservation-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.15rem 0.35rem;
    border: 1px solid transparent;
    border-radius: 0.35rem;
    background: transparent;
    color: var(--app-accent-ink);
    font-size: 0.8rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}

.monitor-reservation-link:hover {
    border-color: var(--app-border);
    background: var(--app-accent-soft);
}

.monitor-reservation-link:focus-visible {
    outline: 2px solid var(--app-accent);
    outline-offset: 1px;
}

.monitor-cell-wrap {
    min-width: 15rem;
    max-width: 24rem;
    white-space: normal !important;
}

.monitor-cell-wrap span {
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.monitor-badge {
    display: inline-flex;
    align-items: center;
    max-width: 18rem;
    padding: 0.18rem 0.5rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.monitor-badge.is-success {
    background: var(--app-success-bg);
    color: var(--app-success-ink);
}

.monitor-badge.is-warning {
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
}

.monitor-badge.is-danger {
    background: var(--app-danger-bg);
    color: var(--app-danger-ink);
}

.monitor-badge.is-accent {
    background: var(--app-accent-soft);
    color: var(--app-accent-ink);
}

.monitor-badge.is-neutral {
    background: var(--app-surface-3);
    color: var(--app-ink);
}

.monitor-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-height: 15rem;
    padding: 2rem 1rem;
    color: var(--app-muted);
    text-align: center;
}

.monitor-state-icon {
    font-size: 2rem;
    color: var(--app-accent-ink);
}

.monitor-state strong {
    color: var(--app-ink);
    font-size: 0.95rem;
}

.monitor-state p {
    max-width: 28rem;
    margin: 0 0 0.5rem;
    font-size: 0.82rem;
}

.monitor-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
}

.monitor-footer-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.78rem;
}

.monitor-per-page {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--app-muted);
    font-size: 0.78rem;
}

.monitor-per-page .form-select {
    width: auto;
    min-width: 4.4rem;
}

.monitor-page :focus-visible {
    outline: 2px solid var(--app-accent);
    outline-offset: 2px;
}

@media (max-width: 767.98px) {
    .monitor-header {
        align-items: flex-start;
        padding: 1.1rem;
    }

    .monitor-header-meta {
        width: 100%;
    }

    .monitor-meta-item,
    .monitor-back-link {
        flex: 1 1 auto;
        justify-content: center;
    }

    .monitor-filter-grid {
        grid-template-columns: 1fr;
    }

    .monitor-field-wide {
        grid-column: auto;
    }

    .monitor-filter-actions {
        justify-content: stretch;
    }

    .monitor-filter-actions .btn {
        flex: 1 1 100%;
        justify-content: center;
        min-height: 2.75rem;
    }

    .monitor-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .monitor-summary-card {
        min-height: 4.6rem;
        padding: 0.75rem;
    }

    .monitor-data-head {
        padding: 0.9rem 0.8rem 0.8rem;
    }

    .monitor-tabs {
        padding-inline: 0.8rem;
    }

    .monitor-tab {
        flex: 1 1 auto;
        justify-content: center;
        min-height: 2.75rem;
    }

    .monitor-table-wrap {
        max-height: none;
    }

    .monitor-footer {
        align-items: flex-start;
    }

    .monitor-footer-meta {
        width: 100%;
        justify-content: space-between;
    }

    .monitor-field .form-control,
    .monitor-field .form-select,
    .monitor-data-tools .btn,
    .monitor-chip,
    .monitor-per-page .form-select {
        min-height: 2.75rem;
    }

    .monitor-period-head {
        flex-direction: column;
        align-items: stretch;
    }

    .monitor-period-controls {
        flex-direction: column;
        align-items: stretch;
    }

    .monitor-period-options {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        width: 100%;
    }

    .monitor-period-option {
        min-height: 2.75rem;
    }

    .monitor-period-year {
        width: 100%;
    }

    .monitor-period-year .form-select {
        min-height: 2.75rem;
    }

    .monitor-period-foot {
        flex-direction: column;
        align-items: flex-start;
    }

    .monitor-refresh {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 359.98px) {
    .monitor-summary {
        grid-template-columns: 1fr;
    }
}

.monitor-modal-backdrop { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: flex-start; justify-content: center; padding: min(6vh, 3rem) 1rem 1rem; overflow-y: auto; background: rgba(15, 23, 42, 0.64); }
.monitor-modal { width: min(1080px, 100%); overflow: hidden; border: 1px solid var(--app-border); border-radius: 0.9rem; background: var(--app-surface); box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.3); color: var(--app-ink); }
.monitor-modal-header, .monitor-modal-footer { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; }
.monitor-modal-header { border-bottom: 1px solid var(--app-border); }
.monitor-modal-header h2 { margin: 0 0 0.2rem; font-size: 1.05rem; }
.monitor-modal-header p { margin: 0; color: var(--app-muted); font-size: 0.8rem; }
.monitor-modal-body { padding: 1rem 1.1rem; }
.monitor-modal-footer { justify-content: flex-end; border-top: 1px solid var(--app-border); background: var(--app-surface-2); }
.monitor-detail-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.55rem; margin: 0 0 1rem; }
.monitor-detail-grid > div { padding: 0.55rem 0.65rem; border: 1px solid var(--app-border); border-radius: 0.55rem; background: var(--app-surface-2); }
.monitor-detail-grid dt { color: var(--app-muted); font-size: 0.66rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.monitor-detail-grid dd { margin: 0.15rem 0 0; font-size: 0.8rem; overflow-wrap: anywhere; }
.monitor-detail-products h3 { margin: 0 0 0.5rem; font-size: 0.9rem; }
.monitor-detail-table th { padding: 0.5rem 0.55rem; background: var(--app-surface-2); color: var(--app-muted); font-size: 0.66rem; letter-spacing: 0.04em; text-transform: uppercase; white-space: nowrap; }
.monitor-detail-table td { padding: 0.5rem 0.55rem; font-size: 0.78rem; vertical-align: middle; }
@media (max-width: 767.98px) {
    .monitor-detail-grid { grid-template-columns: 1fr; }
    .monitor-modal-backdrop { padding: 0.5rem; }
}

.monitor-finance {
    margin-bottom: 1rem;
}

.monitor-finance .card-body {
    padding: 1rem 1.1rem;
}

.monitor-finance-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.monitor-finance-head p {
    max-width: 72ch;
    margin: 0.2rem 0 0;
    color: var(--app-muted);
    font-size: 0.8rem;
}

.monitor-finance-badge {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.6rem;
    border-radius: 0.5rem;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}

.monitor-finance-badge.is-balanced,
.monitor-finance-check.is-balanced {
    background: var(--app-success-bg);
    color: var(--app-success-ink);
}

.monitor-finance-badge.is-warning,
.monitor-finance-check.is-warning {
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
}

.monitor-finance-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.4rem;
    margin: 0.8rem 0 0;
    padding: 0.6rem 0.7rem;
    border: 1px solid color-mix(in srgb, var(--app-warning-ink) 28%, var(--app-border));
    border-radius: 0.55rem;
    background: var(--app-warning-bg);
    color: var(--app-warning-ink);
    font-size: 0.78rem;
}

.monitor-finance-flow {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.5rem;
    margin: 1rem 0;
    padding: 0;
    list-style: none;
}

.monitor-finance-step,
.monitor-finance-kpi {
    min-width: 0;
    padding: 0.75rem;
    border: 1px solid var(--app-border);
    border-radius: 0.65rem;
    background: var(--app-surface-2);
}

.monitor-finance-step span,
.monitor-finance-kpi span {
    display: block;
    color: var(--app-muted);
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.monitor-finance-step strong,
.monitor-finance-kpi strong {
    display: block;
    margin-top: 0.2rem;
    font-size: 1rem;
    overflow-wrap: anywhere;
}

.monitor-finance-step small,
.monitor-finance-kpi small {
    display: block;
    margin-top: 0.2rem;
    color: var(--app-muted);
    font-size: 0.68rem;
    line-height: 1.35;
}

.monitor-finance-step.is-accent {
    border-color: color-mix(in srgb, var(--app-accent) 32%, var(--app-border));
    background: var(--app-accent-soft);
}

.monitor-finance-step.is-accent strong {
    color: var(--app-accent-ink);
}

.monitor-finance-step.is-deduction {
    border-color: color-mix(in srgb, var(--app-warning-ink) 28%, var(--app-border));
    background: var(--app-warning-bg);
}

.monitor-finance-step.is-deduction strong {
    color: var(--app-warning-ink);
}

.monitor-finance-step.is-success {
    border-color: color-mix(in srgb, var(--app-success-ink) 28%, var(--app-border));
    background: var(--app-success-bg);
}

.monitor-finance-step.is-success strong {
    color: var(--app-success-ink);
}

.monitor-finance-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.monitor-finance-check {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 0.7rem 0.8rem;
    border: 1px solid var(--app-border);
    border-radius: 0.6rem;
    font-size: 0.78rem;
}

.monitor-finance-check > div {
    min-width: 0;
}

.monitor-finance-check strong {
    display: block;
    font-size: 0.76rem;
}

.monitor-finance-check p {
    margin: 0.15rem 0 0;
    color: inherit;
}

.monitor-finance-check-notes {
    display: grid;
    flex: 0 0 auto;
    gap: 0.15rem;
    font-weight: 700;
    text-align: right;
    white-space: nowrap;
}

.monitor-finance-channels {
    margin-bottom: 0.75rem;
}

.monitor-finance-channels h3 {
    margin: 0 0 0.4rem;
    font-size: 0.86rem;
}

.monitor-finance-table th {
    padding: 0.5rem 0.6rem;
    background: var(--app-surface-2);
    color: var(--app-muted);
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
}

.monitor-finance-table td {
    padding: 0.55rem 0.6rem;
    border-color: var(--app-border);
    color: var(--app-ink);
    font-size: 0.78rem;
    vertical-align: middle;
}

.monitor-finance-table small {
    display: block;
    color: var(--app-muted);
    font-size: 0.68rem;
}

.monitor-finance-method {
    padding-top: 0.75rem;
    border-top: 1px solid var(--app-border);
}

.monitor-finance-method summary {
    color: var(--app-accent-ink);
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 700;
}

.monitor-finance-method summary:focus-visible {
    border-radius: 0.25rem;
    outline: 2px solid var(--app-accent);
    outline-offset: 2px;
}

.monitor-finance-definitions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.6rem 0.9rem;
    margin: 0.75rem 0 0;
}

.monitor-finance-definitions > div {
    padding: 0.6rem 0.7rem;
    border: 1px solid var(--app-border);
    border-radius: 0.55rem;
    background: var(--app-surface-2);
}

.monitor-finance-definitions dt {
    font-size: 0.76rem;
    font-weight: 700;
}

.monitor-finance-definitions dd {
    margin: 0.15rem 0 0;
    color: var(--app-muted);
    font-size: 0.75rem;
    line-height: 1.45;
}

@media (max-width: 1199.98px) {
    .monitor-finance-flow,
    .monitor-finance-kpis {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .monitor-finance-head,
    .monitor-finance-check {
        flex-direction: column;
    }

    .monitor-finance-flow,
    .monitor-finance-definitions {
        grid-template-columns: 1fr;
    }

    .monitor-finance-kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .monitor-finance-check-notes {
        text-align: left;
        white-space: normal;
    }
}

@media (max-width: 359.98px) {
    .monitor-finance-kpis {
        grid-template-columns: 1fr;
    }
}
</style>
