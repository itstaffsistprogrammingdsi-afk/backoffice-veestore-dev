<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ReportTableControls from '@/components/ui/ReportTableControls.vue';
import { useReportTableDensity } from '@/composables/useReportTable';

defineOptions({ layout: AppLayout });

interface FinanceChannel {
    key: string;
    label: string;
    net_sales: number;
    gross_profit: number;
    gross_margin_percent: number;
    transactions: number;
    qty: number;
}

interface FinanceReconciliation {
    expected_net: number;
    actual_net: number;
    difference: number;
    clamped_discount: number;
    unexplained_difference: number;
    is_balanced: boolean;
}

interface Finance {
    gross_sales: number;
    product_discount: number;
    net_after_product_discount: number;
    voucher_discount: number;
    net_sales: number;
    base_price: number;
    gross_profit: number;
    gross_margin_percent: number;
    transactions: number;
    qty: number;
    qty_good?: number;
    qty_good_after_repair?: number;
    clearance_discount?: number;
    clearance_percentage?: number;
    reconciliation: FinanceReconciliation;
    channels: FinanceChannel[];
}

interface Expense {
    id: string;
    period: string;
    category: string;
    amount: number;
    note: string | null;
    created_by: string | null;
    created_at: string | null;
}

interface Summary {
    net_sales: number;
    base_price: number;
    gross_profit: number;
    gross_margin_percent: number;
    total_expenses: number;
    net_profit: number;
    net_margin_percent: number;
}

interface Props {
    period: string;
    periodLabel: string;
    finance: Finance;
    expenses: Expense[];
    summary: Summary;
    canManage: boolean;
    urls: {
        index: string;
        export: string;
        add_expense: string;
        delete_expense: string;
    };
}

const props = defineProps<Props>();
const { density, isCompact, toggleDensity } = useReportTableDensity();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const periodInput = ref(props.period);

const expenseForm = useForm({
    period: props.period,
    category: '',
    amount: '',
    note: '',
});

const clampedDiscount = computed(() => Number(props.finance.reconciliation?.clamped_discount ?? 0));
const unexplainedDifference = computed(() => Number(props.finance.reconciliation?.unexplained_difference ?? 0));

const summaryCards = computed(() => [
    { label: 'Omset Bersih', value: formatCurrency(props.summary.net_sales), tone: '' },
    { label: 'Harga Dasar (bukan HPP)', value: formatCurrency(props.summary.base_price), tone: '' },
    { label: 'Selisih Kotor (estimasi)', value: formatCurrency(props.summary.gross_profit), tone: props.summary.gross_profit >= 0 ? 'is-success' : 'is-danger' },
    { label: 'Beban Operasional', value: formatCurrency(props.summary.total_expenses), tone: 'is-warning' },
    { label: 'Estimasi Laba Operasional', value: formatCurrency(props.summary.net_profit), tone: props.summary.net_profit >= 0 ? 'is-success' : 'is-danger' },
    ...(props.finance.qty_good !== undefined
        ? [{
            label: 'Good / GAR',
            value: `${formatNumber(props.finance.qty_good ?? 0)} / ${formatNumber(props.finance.qty_good_after_repair ?? 0)}`,
            tone: '',
        }]
        : []),
    ...(props.finance.clearance_discount !== undefined
        ? [{
            label: 'Diskon Clearance',
            value: formatCurrency(props.finance.clearance_discount ?? 0),
            tone: 'is-warning',
        }]
        : []),
]);

const statementRows = computed(() => {
    const rows = [
        { label: 'Omset Kotor', value: props.finance.gross_sales, kind: 'normal' },
        { label: 'Diskon Produk', value: -props.finance.product_discount, kind: 'deduction' },
        { label: 'Diskon Voucher / Poin', value: -props.finance.voucher_discount, kind: 'deduction' },
    ];

    if (clampedDiscount.value > 0.009) {
        rows.push({ label: 'Penyesuaian batas bawah 0', value: clampedDiscount.value, kind: 'adjustment' });
    }

    rows.push(
        { label: 'Omset Bersih', value: props.finance.net_sales, kind: 'subtotal' },
        { label: 'Nilai Harga Dasar (bukan HPP)', value: -props.finance.base_price, kind: 'deduction' },
        { label: 'Selisih Kotor (estimasi)', value: props.finance.gross_profit, kind: 'subtotal' },
        { label: 'Beban Operasional', value: -props.summary.total_expenses, kind: 'deduction' },
        { label: 'Estimasi Laba Operasional', value: props.summary.net_profit, kind: 'total' },
    );

    return rows;
});

const applyPeriod = () => {
    if (!periodInput.value) return;

    router.get(props.urls.index, { period: periodInput.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const addExpense = () => {
    expenseForm.period = props.period;
    expenseForm.post(props.urls.add_expense, {
        preserveScroll: true,
        onSuccess: () => {
            expenseForm.reset('category', 'amount', 'note');
            expenseForm.period = props.period;
        },
    });
};

const deleteExpense = (expense: Expense) => {
    if (!window.confirm(`Hapus beban ${expense.category} sebesar ${formatCurrency(expense.amount)}?`)) return;

    router.delete(props.urls.delete_expense, {
        data: { id: expense.id, period: props.period },
        preserveScroll: true,
    });
};

const exportUrl = computed(() => `${props.urls.export}?period=${encodeURIComponent(props.period)}`);

const formatNumber = (value: number | null | undefined) =>
    new Intl.NumberFormat('id-ID').format(Number(value ?? 0));

const formatCurrency = (value: number | null | undefined) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));

const formatPercent = (value: number | null | undefined) =>
    `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value ?? 0))}%`;

const formatDate = (value: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
};
</script>

<template>
    <Head title="Estimasi Laba Operasional" />

    <div class="pnl-page" :class="{ 'is-table-compact': isCompact }">
        <header class="pnl-header">
            <div>
                <span class="pnl-eyebrow">Laporan / Penjualan</span>
                <h1>Estimasi Laba Operasional</h1>
                <p>Estimasi manajemen periode {{ props.periodLabel }}. Basis tanggal: tanggal transaksi. Harga dasar adalah harga jual acuan, bukan HPP. Beban operasional diinput manual.</p>
            </div>
            <div class="pnl-header-actions">
                <label for="pnl-period" class="visually-hidden">Periode estimasi laba operasional</label>
                <input id="pnl-period" v-model="periodInput" class="form-control form-control-sm" type="month" />
                <button class="btn btn-sm btn-primary" type="button" @click="applyPeriod">Terapkan</button>
                <a class="btn btn-sm btn-light" :href="exportUrl">
                    <i class="mdi mdi-file-excel-outline me-1" aria-hidden="true"></i>
                    Ekspor
                </a>
                <ReportTableControls :density="density" @toggle-density="toggleDensity" />
            </div>
        </header>

        <div v-if="flash.success" class="alert alert-success py-2 small" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger py-2 small" role="alert">{{ flash.error }}</div>

        <div class="pnl-disclaimer" role="note">
            <i class="mdi mdi-information-outline" aria-hidden="true"></i>
            <div>
                <strong>Estimasi, bukan laba rugi akuntansi.</strong>
                Harga dasar berasal dari harga jual acuan pada master harga, bukan biaya perolehan. Biaya perolehan, pajak, dan jurnal belum termasuk.
            </div>
        </div>

        <section class="pnl-summary" aria-label="Ringkasan laba rugi">
            <div v-for="card in summaryCards" :key="card.label" class="pnl-summary-card">
                <span>{{ card.label }}</span>
                <strong :class="card.tone">{{ card.value }}</strong>
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <section class="pnl-card h-100" aria-labelledby="pnl-statement-title">
                    <div class="pnl-card-head">
                        <h2 id="pnl-statement-title">Laporan Estimasi Laba Operasional</h2>
                        <span class="pnl-note">Margin estimasi {{ formatPercent(props.summary.gross_margin_percent) }}</span>
                    </div>
                    <div class="table-responsive report-table-wrap" tabindex="0" role="region" aria-label="Rincian estimasi laba operasional">
                        <table class="table align-middle mb-0">
                            <tbody>
                                <tr v-for="row in statementRows" :key="row.label" :class="`pnl-row-${row.kind}`">
                                    <td>{{ row.label }}</td>
                                    <td class="text-end">{{ formatCurrency(row.value) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pnl-reconciliation" :class="{ 'is-warning': !props.finance.reconciliation?.is_balanced }">
                        <div>
                            <strong>Rekonsiliasi angka</strong>
                            <p>
                                Setelah diskon produk dan voucher/poin = {{ formatCurrency(props.finance.reconciliation?.expected_net) }}.
                                Omset bersih tercatat {{ formatCurrency(props.finance.reconciliation?.actual_net) }}.
                            </p>
                        </div>
                        <div class="pnl-reconciliation-notes">
                            <span v-if="clampedDiscount > 0.009">
                                Penyesuaian batas bawah 0: {{ formatCurrency(clampedDiscount) }}
                            </span>
                            <span v-if="unexplainedDifference < -0.009">
                                Selisih tak terjelaskan: {{ formatCurrency(unexplainedDifference) }}
                            </span>
                            <span v-if="props.finance.reconciliation?.is_balanced">Tidak ada selisih tak terjelaskan</span>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-lg-5">
                <section class="pnl-card h-100" aria-labelledby="pnl-channel-title">
                    <div class="pnl-card-head">
                        <h2 id="pnl-channel-title">Per Kanal</h2>
                    </div>
                    <div class="table-responsive report-table-wrap" tabindex="0" role="region" aria-label="Estimasi laba operasional per kanal">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kanal</th>
                                    <th class="text-end">Omset Bersih</th>
                                    <th class="text-end">Selisih Kotor</th>
                                    <th class="text-end">Margin estimasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="channel in props.finance.channels" :key="channel.key">
                                    <td class="fw-semibold">{{ channel.label }}</td>
                                    <td class="text-end">{{ formatCurrency(channel.net_sales) }}</td>
                                    <td class="text-end">{{ formatCurrency(channel.gross_profit) }}</td>
                                    <td class="text-end">{{ formatPercent(channel.gross_margin_percent) }}</td>
                                </tr>
                                <tr v-if="props.finance.channels.length === 0">
                                    <td colspan="4" class="pnl-empty">Belum ada transaksi pada periode ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <section class="pnl-card" aria-labelledby="pnl-expense-title">
            <div class="pnl-card-head">
                <div>
                    <h2 id="pnl-expense-title">Beban Operasional</h2>
                    <p class="pnl-note">Beban manual untuk periode {{ props.periodLabel }}.</p>
                </div>
            </div>

            <form v-if="props.canManage" class="row g-2 align-items-end pnl-expense-form" @submit.prevent="addExpense">
                <div class="col-md-3">
                    <label for="pnl-category" class="form-label">Kategori Beban</label>
                    <input id="pnl-category" v-model="expenseForm.category" class="form-control form-control-sm" type="text" maxlength="100" required placeholder="Contoh: listrik, gaji, sewa" />
                    <small v-if="expenseForm.errors.category" class="text-danger">{{ expenseForm.errors.category }}</small>
                </div>
                <div class="col-md-2">
                    <label for="pnl-amount" class="form-label">Nominal</label>
                    <input id="pnl-amount" v-model="expenseForm.amount" class="form-control form-control-sm" type="number" min="1" step="0.01" required />
                    <small v-if="expenseForm.errors.amount" class="text-danger">{{ expenseForm.errors.amount }}</small>
                </div>
                <div class="col-md-4">
                    <label for="pnl-note" class="form-label">Catatan</label>
                    <input id="pnl-note" v-model="expenseForm.note" class="form-control form-control-sm" type="text" maxlength="500" placeholder="Opsional" />
                </div>
                <div class="col-md-3">
                    <button class="btn btn-sm btn-primary" type="submit" :disabled="expenseForm.processing">
                        <i class="mdi mdi-plus me-1" aria-hidden="true"></i>
                        {{ expenseForm.processing ? 'Menyimpan...' : 'Tambah Beban' }}
                    </button>
                </div>
            </form>
            <p v-else class="pnl-note mb-0">
                Hanya peran finance atau management yang dapat mengubah beban operasional.
            </p>

            <div class="table-responsive report-table-wrap mt-3" tabindex="0" role="region" aria-label="Daftar beban operasional">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Catatan</th>
                            <th>Dicatat Oleh</th>
                            <th>Tanggal</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="expense in props.expenses" :key="expense.id">
                            <td class="fw-semibold">{{ expense.category }}</td>
                            <td>{{ expense.note ?? '-' }}</td>
                            <td>{{ expense.created_by ?? '-' }}</td>
                            <td class="text-nowrap">{{ formatDate(expense.created_at) }}</td>
                            <td class="text-end">{{ formatCurrency(expense.amount) }}</td>
                            <td class="text-end">
                                <button v-if="props.canManage" class="btn btn-sm btn-outline-danger" type="button" :aria-label="`Hapus beban ${expense.category}`" @click="deleteExpense(expense)">
                                    <i class="mdi mdi-delete-outline" aria-hidden="true"></i>
                                </button>
                                <span v-else class="text-muted">-</span>
                            </td>
                        </tr>
                        <tr v-if="props.expenses.length === 0">
                            <td colspan="6" class="pnl-empty">Belum ada beban operasional pada periode ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<style scoped>
.pnl-page { color: var(--app-ink); }
.pnl-header { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.4rem; margin-bottom: 1rem; border-radius: 0.9rem; background: var(--app-hero-bg); color: var(--app-hero-text); }
.pnl-header > div:first-child { max-width: 48rem; }
.pnl-eyebrow { display: inline-block; margin-bottom: 0.35rem; color: var(--app-hero-eyebrow); font-size: 0.7rem; font-weight: 700; }
.pnl-header h1 { margin: 0 0 0.35rem; font-size: clamp(1.3rem, 2vw, 1.6rem); }
.pnl-header p { margin: 0; color: var(--app-hero-muted); font-size: 0.86rem; }
.pnl-header-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem; }
.pnl-disclaimer { display: flex; gap: 0.65rem; align-items: flex-start; padding: 0.8rem 1rem; margin-bottom: 1rem; border: 1px solid var(--app-warning-border, var(--app-border)); border-radius: 0.7rem; background: var(--app-warning-bg, var(--app-surface-2)); color: var(--app-ink); font-size: 0.8rem; }
.pnl-disclaimer i { margin-top: 0.1rem; color: var(--app-warning-ink); font-size: 1.1rem; }
.pnl-disclaimer strong { display: block; margin-bottom: 0.15rem; }
.pnl-summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.pnl-summary-card { padding: 0.85rem 1rem; border: 1px solid var(--app-border); border-radius: 0.7rem; background: var(--app-surface); }
.pnl-summary-card span { display: block; color: var(--app-muted); font-size: 0.7rem; font-weight: 600; }
.pnl-summary-card strong { display: block; margin-top: 0.2rem; font-size: 1.05rem; font-weight: 800; }
.pnl-summary-card strong.is-success { color: var(--app-accent-ink); }
.pnl-summary-card strong.is-danger { color: var(--app-danger-ink); }
.pnl-summary-card strong.is-warning { color: var(--app-warning-ink); }
.pnl-card { padding: 1rem; margin-bottom: 1rem; border: 1px solid var(--app-border); border-radius: 0.8rem; background: var(--app-surface); }
.pnl-card-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.75rem; }
.pnl-card-head h2 { margin: 0; font-size: 0.95rem; font-weight: 800; }
.pnl-note { color: var(--app-muted); font-size: 0.74rem; }
.pnl-page .table th { color: var(--app-muted); font-size: 0.72rem; font-weight: 700; white-space: nowrap; }
.pnl-page .table td { font-size: 0.8rem; vertical-align: middle; }
.pnl-row-deduction td { color: var(--app-danger-ink); }
.pnl-row-adjustment td { color: var(--app-warning-ink); font-weight: 600; }
.pnl-row-subtotal td { background: var(--app-surface-2); font-weight: 700; }
.pnl-row-total td { border-top: 2px solid var(--app-border); background: var(--app-accent-soft); color: var(--app-accent-ink); font-weight: 800; }
.pnl-reconciliation { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 0.6rem; padding: 0.75rem 1rem 0; border-top: 1px solid var(--app-border); font-size: 0.76rem; }
.pnl-reconciliation p { margin: 0.15rem 0 0; color: var(--app-muted); }
.pnl-reconciliation-notes { display: flex; flex-direction: column; align-items: flex-end; color: var(--app-muted); text-align: right; }
.pnl-reconciliation.is-warning .pnl-reconciliation-notes { color: var(--app-warning-ink); }
.pnl-expense-form { padding: 0.75rem; border: 1px solid var(--app-border); border-radius: 0.6rem; background: var(--app-surface-2); }
.pnl-empty { padding: 1.5rem 1rem; color: var(--app-muted); text-align: center; }
@media (max-width: 1199.98px) { .pnl-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575.98px) { .pnl-summary { grid-template-columns: 1fr; } .pnl-header { padding: 1.1rem; } }
</style>
