<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { downloadExcelTable } from '@/composables/useExcelExport';

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
    urls: {
        index: string;
        add_expense: string;
        delete_expense: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });

const periodInput = ref(props.period);

const expenseForm = useForm({
    period: props.period,
    category: '',
    amount: '',
    note: '',
});

const summaryCards = computed(() => [
    { label: 'Omset Bersih', value: formatCurrency(props.summary.net_sales), tone: '' },
    { label: 'HPP / Harga Base', value: formatCurrency(props.summary.base_price), tone: '' },
    { label: 'Laba Kotor', value: formatCurrency(props.summary.gross_profit), tone: props.summary.gross_profit >= 0 ? 'is-success' : 'is-danger' },
    { label: 'Beban Operasional', value: formatCurrency(props.summary.total_expenses), tone: 'is-warning' },
    { label: 'Laba Bersih', value: formatCurrency(props.summary.net_profit), tone: props.summary.net_profit >= 0 ? 'is-success' : 'is-danger' },
]);

const statementRows = computed(() => [
    { label: 'Omset Kotor', value: props.finance.gross_sales, kind: 'normal' },
    { label: 'Diskon Produk', value: -props.finance.product_discount, kind: 'deduction' },
    { label: 'Diskon Voucher / Poin', value: -props.finance.voucher_discount, kind: 'deduction' },
    { label: 'Omset Bersih', value: props.finance.net_sales, kind: 'subtotal' },
    { label: 'Harga Pokok Penjualan', value: -props.finance.base_price, kind: 'deduction' },
    { label: 'Laba Kotor', value: props.finance.gross_profit, kind: 'subtotal' },
    { label: 'Beban Operasional', value: -props.summary.total_expenses, kind: 'deduction' },
    { label: 'Laba Bersih', value: props.summary.net_profit, kind: 'total' },
]);

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

const exportReport = () => {
    const rows = [
        { label: 'Omset Kotor', amount: props.finance.gross_sales },
        { label: 'Diskon Produk', amount: -props.finance.product_discount },
        { label: 'Diskon Voucher / Poin', amount: -props.finance.voucher_discount },
        { label: 'Omset Bersih', amount: props.finance.net_sales },
        { label: 'Harga Pokok Penjualan', amount: -props.finance.base_price },
        { label: 'Laba Kotor', amount: props.finance.gross_profit },
        { label: 'Beban Operasional', amount: -props.summary.total_expenses },
        { label: 'Laba Bersih', amount: props.summary.net_profit },
        ...props.expenses.map((expense) => ({
            label: `Beban: ${expense.category}`,
            amount: -expense.amount,
        })),
    ];

    downloadExcelTable('Laba-Rugi', 'Laba Rugi', [
        { key: 'label', label: 'Komponen' },
        { key: 'amount', label: 'Nominal' },
    ], rows, {
        title: `Laba Rugi ${props.periodLabel}`,
        filters: [{ label: 'Periode', value: props.periodLabel }],
        currencyColumns: ['amount'],
    });
};

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
    <Head title="Laba Rugi" />

    <div class="pnl-page">
        <header class="pnl-header">
            <div>
                <span class="pnl-eyebrow">Reports / Penjualan</span>
                <h1>Laba Rugi</h1>
                <p>Laporan laba rugi periode {{ props.periodLabel }}. Beban operasional diinput manual.</p>
            </div>
            <div class="pnl-header-actions">
                <label for="pnl-period" class="visually-hidden">Periode laba rugi</label>
                <input id="pnl-period" v-model="periodInput" class="form-control form-control-sm" type="month" />
                <button class="btn btn-sm btn-primary" type="button" @click="applyPeriod">Terapkan</button>
                <button class="btn btn-sm btn-light" type="button" @click="exportReport">
                    <i class="mdi mdi-file-excel-outline me-1" aria-hidden="true"></i>
                    Ekspor
                </button>
            </div>
        </header>

        <div v-if="flash.success" class="alert alert-success py-2 small" role="status">{{ flash.success }}</div>
        <div v-if="flash.error" class="alert alert-danger py-2 small" role="alert">{{ flash.error }}</div>

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
                        <h2 id="pnl-statement-title">Laporan Laba Rugi</h2>
                        <span class="pnl-note">Margin kotor {{ formatPercent(props.summary.gross_margin_percent) }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <tbody>
                                <tr v-for="row in statementRows" :key="row.label" :class="`pnl-row-${row.kind}`">
                                    <td>{{ row.label }}</td>
                                    <td class="text-end">{{ formatCurrency(row.value) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
            <div class="col-lg-5">
                <section class="pnl-card h-100" aria-labelledby="pnl-channel-title">
                    <div class="pnl-card-head">
                        <h2 id="pnl-channel-title">Per Kanal</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kanal</th>
                                    <th class="text-end">Omset Bersih</th>
                                    <th class="text-end">Laba Kotor</th>
                                    <th class="text-end">Margin</th>
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

            <form class="row g-2 align-items-end pnl-expense-form" @submit.prevent="addExpense">
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

            <div class="table-responsive mt-3">
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
                                <button class="btn btn-sm btn-outline-danger" type="button" :aria-label="`Hapus beban ${expense.category}`" @click="deleteExpense(expense)">
                                    <i class="mdi mdi-delete-outline" aria-hidden="true"></i>
                                </button>
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
.pnl-row-subtotal td { background: var(--app-surface-2); font-weight: 700; }
.pnl-row-total td { border-top: 2px solid var(--app-border); background: var(--app-accent-soft); color: var(--app-accent-ink); font-weight: 800; }
.pnl-expense-form { padding: 0.75rem; border: 1px solid var(--app-border); border-radius: 0.6rem; background: var(--app-surface-2); }
.pnl-empty { padding: 1.5rem 1rem; color: var(--app-muted); text-align: center; }
@media (max-width: 1199.98px) { .pnl-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575.98px) { .pnl-summary { grid-template-columns: 1fr; } .pnl-header { padding: 1.1rem; } }
</style>
