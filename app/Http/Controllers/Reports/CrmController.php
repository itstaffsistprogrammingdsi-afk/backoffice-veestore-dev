<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Analytics\Queries\CustomerDemographicsQuery;
use App\Exports\CrmDashboardExport;
use App\Exports\CrmDemographicsExport;
use App\Exports\CustomerProfileExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BirthdayGreetingRequest;
use App\Models\Customer;
use App\Models\Store;
use App\Services\BirthdayGreetingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->buildDashboardData($request);

        return Inertia::render('Reports/Crm', $data + [
            'urls' => [
                'index' => route('reports.crm.index'),
                'export' => route('reports.crm.export'),
                'customerProfiles' => route('reports.crm.customer-profiles'),
                'demographics' => route('reports.crm.demographics'),
                'preference' => route('reports.crm.customer-preference.index'),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $data = $this->buildDashboardData($request);

        $payload = [
            'from' => $data['from'],
            'to' => $data['to'],
            'channel' => $data['channel'],
            'channel_label' => $data['channelLabel'],
            'store_id' => $data['storeId'],
            'store_name' => $data['storeName'] ?: 'Semua Store',

            'summary' => $data['summary'],

            'purchase_customers' => $data['purchaseCustomers'],
            'spending_customers' => $data['spendingCustomers'],
            'visit_customers' => $data['visitCustomers'],
            'branch_omset_rows' => $data['branchOmsetRows'],

            'customer_transaction_details' => $data['customerTransactionDetails'],
            'customer_visit_details' => $data['customerVisitDetails'],
        ];

        return Excel::download(
            new CrmDashboardExport($payload),
            'crm-dashboard-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    /** Halaman awal Customer Profile untuk memilih customer yang akan dilihat. */
    public function customerProfiles(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $pointBalance = Schema::hasTable('customer_loyalty_points')
            ? DB::table('customer_loyalty_points')
                ->selectRaw('customer_id, COALESCE(MAX(balance), 0) as current_balance')
                ->groupBy('customer_id')
            : null;

        $customersQuery = Customer::query()
            ->select('customers.*');

        if ($pointBalance) {
            $customersQuery
                ->leftJoinSub($pointBalance, 'point_balance', function ($join) {
                    $join->on('point_balance.customer_id', '=', 'customers.id');
                })
                ->selectRaw('COALESCE(point_balance.current_balance, 0) as current_balance');
        } else {
            $customersQuery->selectRaw('0 as current_balance');
        }

        $customers = $customersQuery
            ->when($q !== '', function ($query) use ($q) {
                $like = "%{$q}%";
                $query->where(function ($where) use ($like) {
                    $where->where('customers.name', 'ILIKE', $like)
                        ->orWhere('customers.email', 'ILIKE', $like)
                        ->orWhere('customers.wa_number', 'ILIKE', $like);
                });
            })
            ->orderBy('customers.name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Reports/CrmCustomers', [
            'customers' => $customers,
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('reports.crm.customer-profiles'),
                'profile' => route('reports.crm.customer-profile', ['customerId' => '__ID__']),
            ],
        ]);
    }

    /**
     * Ringkasan data demografi untuk segmentasi dan aktivitas CRM.
     * Semua angka serta tabel mengikuti filter yang dipilih pengguna.
     */
    public function demographics(
        Request $request,
        CustomerDemographicsQuery $query,
        BirthdayGreetingService $greeting
    ) {
        $data = $query->build($request->query());

        return Inertia::render('Reports/CrmDemographics', $data + [
            'birthdayGreeting' => [
                'template' => $greeting->template(),
                'vouchers' => $greeting->availableVouchers(),
            ],
            'urls' => [
                'index' => route('reports.crm.demographics'),
                'export' => route('reports.crm.demographics.export'),
                'crm' => route('reports.crm.index'),
                'customerProfiles' => route('reports.crm.customer-profiles'),
                'customerProfile' => route('reports.crm.customer-profile', ['customerId' => '__ID__']),
                'preference' => route('reports.crm.customer-preference.index'),
                'birthdayGreeting' => route('reports.crm.demographics.birthday-greeting'),
                'regions' => [
                    'regencies' => route('reports.crm.demographics.regencies'),
                    'districts' => route('reports.crm.demographics.districts'),
                    'villages' => route('reports.crm.demographics.villages'),
                ],
            ],
        ]);
    }

    public function sendBirthdayGreeting(BirthdayGreetingRequest $request, BirthdayGreetingService $greeting)
    {
        $result = $greeting->send(
            $request->validated('customer_ids'),
            $request->validated('subject'),
            $request->validated('message'),
            $request->validated('voucher_id'),
        );

        return back()->with(
            $result['sent'] > 0 ? 'success' : 'error',
            $greeting->summaryMessage($result)
        );
    }

    /** Download seluruh baris demografi sesuai filter halaman. */
    public function exportDemographics(Request $request, CustomerDemographicsQuery $query)
    {
        return Excel::download(
            new CrmDemographicsExport($query->exportRows($request->query())),
            'crm-demografi-customer-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    /** Data wilayah bertingkat untuk filter demografi. */
    public function demographicRegencies(Request $request)
    {
        return response()->json([
            'results' => DB::table('regencies')
                ->where('province_code', (string) $request->query('province_code', ''))
                ->orderBy('name')
                ->get(['code as id', 'name as text']),
        ]);
    }

    public function demographicDistricts(Request $request)
    {
        return response()->json([
            'results' => DB::table('districts')
                ->where('regency_code', (string) $request->query('regency_code', ''))
                ->orderBy('name')
                ->get(['code as id', 'name as text']),
        ]);
    }

    public function demographicVillages(Request $request)
    {
        return response()->json([
            'results' => DB::table('villages')
                ->where('district_code', (string) $request->query('district_code', ''))
                ->orderBy('name')
                ->get(['code as id', 'name as text']),
        ]);
    }

    /**
     * Menampilkan riwayat seumur hidup seorang customer. Data transaksi tetap
     * dipaginasi supaya halaman profil aman dibuka untuk customer yang aktif.
     */
    public function customerProfile(Request $request, string $customerId)
    {
        $customer = Customer::query()->findOrFail($customerId);
        $channel = in_array($request->query('channel'), ['pos', 'ecom'], true)
            ? $request->query('channel')
            : '';

        $fromTs = Carbon::create(2000, 1, 1)->startOfDay();
        $toTs = now()->endOfDay();

        $transactionBase = DB::query()
            ->fromSub($this->paidCustomerTransactions($fromTs, $toTs, $channel), 'x')
            ->where('x.customer_id', $customer->id);

        $transactionSummary = DB::query()
            ->fromSub(clone $transactionBase, 'x')
            ->selectRaw('
                COUNT(DISTINCT x.transaction_id) as total_transactions,
                COALESCE(SUM(x.amount), 0) as total_spending,
                MIN(x.transaction_at) as first_purchase,
                MAX(x.transaction_at) as last_purchase
            ')
            ->first();

        $transactions = DB::query()
            ->fromSub($transactionBase, 'x')
            ->selectRaw('
                x.channel, x.store_name, x.transaction_id, x.transaction_code,
                x.transaction_status, x.visit_id, x.visit_code, x.transaction_at, x.amount
            ')
            ->orderByDesc('x.transaction_at')
            ->paginate(20)
            ->withQueryString();

        $this->attachPurchaseDetails($transactions->getCollection());

        $totalVisits = Schema::hasTable('store_customer_visits')
            ? DB::table('store_customer_visits')->where('customer_id', $customer->id)->count()
            : 0;

        $pointSummary = (object) [
            'current_balance' => 0,
            'total_point_in' => 0,
            'total_point_out' => 0,
            'last_mutation_at' => null,
        ];
        $pointMutations = collect();

        if (Schema::hasTable('customer_loyalty_point_mutations')) {
            $pointSummary = DB::table('customer_loyalty_point_mutations')
                ->where('customer_id', $customer->id)
                ->selectRaw('
                    COALESCE(SUM(CASE WHEN points > 0 THEN points ELSE 0 END), 0) as total_point_in,
                    COALESCE(SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END), 0) as total_point_out,
                    MAX(created_at) as last_mutation_at
                ')
                ->first();

            if (Schema::hasTable('customer_loyalty_points')) {
                $pointSummary->current_balance = (int) (DB::table('customer_loyalty_points')
                    ->where('customer_id', $customer->id)
                    ->max('balance') ?? 0);
            } else {
                $pointSummary->current_balance = (int) (($pointSummary->total_point_in ?? 0) - ($pointSummary->total_point_out ?? 0));
            }

            $pointMutations = DB::table('customer_loyalty_point_mutations')
                ->where('customer_id', $customer->id)
                ->selectRaw('type::text as type, mutation_type::text as mutation_type, points, balance_after, description, ref, created_at')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        return Inertia::render('Reports/CrmCustomerProfile', compact(
            'customer',
            'channel',
            'transactions',
            'transactionSummary',
            'totalVisits',
            'pointSummary',
            'pointMutations'
        ) + [
            'urls' => [
                'index' => route('reports.crm.customer-profiles'),
                'export' => route('reports.crm.customer-profile.export', ['customerId' => $customer->id]),
            ],
        ]);
    }

    public function exportCustomerProfile(string $customerId)
    {
        $customer = Customer::query()
            ->leftJoin('provinces', 'provinces.code', '=', 'customers.province_code')
            ->leftJoin('regencies', 'regencies.code', '=', 'customers.regency_code')
            ->leftJoin('districts', 'districts.code', '=', 'customers.district_code')
            ->leftJoin('villages', 'villages.code', '=', 'customers.village_code')
            ->leftJoin('stores as registration_stores', DB::raw('registration_stores.id::text'), '=', 'customers.registration_source_id')
            ->where('customers.id', $customerId)
            ->select(
                'customers.*',
                'provinces.name as province_name',
                'regencies.name as regency_name',
                'districts.name as district_name',
                'villages.name as village_name',
                'registration_stores.name as registration_source_name'
            )
            ->firstOrFail();

        $fromTs = Carbon::create(2000, 1, 1)->startOfDay();
        $toTs = now()->endOfDay();
        $transactionBase = DB::query()
            ->fromSub($this->paidCustomerTransactions($fromTs, $toTs), 'x')
            ->where('x.customer_id', $customer->id);

        $transactionSummary = DB::query()
            ->fromSub(clone $transactionBase, 'x')
            ->selectRaw('
                COUNT(DISTINCT x.transaction_id) as total_transactions,
                COALESCE(SUM(x.amount), 0) as total_spending,
                MIN(x.transaction_at) as first_purchase,
                MAX(x.transaction_at) as last_purchase
            ')
            ->first();

        $transactions = DB::query()
            ->fromSub($transactionBase, 'x')
            ->selectRaw('
                x.channel, x.store_name, x.transaction_id, x.transaction_code,
                x.transaction_status, x.visit_id, x.visit_code, x.transaction_at, x.amount
            ')
            ->orderByDesc('x.transaction_at')
            ->get();
        $this->attachPurchaseDetails($transactions);

        $visits = collect();
        if (Schema::hasTable('store_customer_visits') && Schema::hasTable('stores')) {
            $visits = DB::table('store_customer_visits as v')
                ->leftJoin('stores as s', 's.id', '=', 'v.store_id')
                ->where('v.customer_id', $customer->id)
                ->selectRaw('
                    COALESCE(v.visit_code, v.id::text) as visit_code,
                    s.name as store_name,
                    v.check_in_at,
                    v.check_out_at,
                    COALESCE(v.status::text, \'-\') as visit_status,
                    COALESCE(v.payment_status::text, \'-\') as payment_status,
                    COALESCE(v.visit_type::text, \'-\') as visit_type
                ')
                ->orderByDesc('v.check_in_at')
                ->get();
        }

        $pointSummary = (object) [
            'current_balance' => 0,
            'total_point_in' => 0,
            'total_point_out' => 0,
            'last_mutation_at' => null,
        ];
        $pointMutations = collect();

        if (Schema::hasTable('customer_loyalty_point_mutations')) {
            $pointSummary = DB::table('customer_loyalty_point_mutations')
                ->where('customer_id', $customer->id)
                ->selectRaw('
                    COALESCE(SUM(CASE WHEN points > 0 THEN points ELSE 0 END), 0) as total_point_in,
                    COALESCE(SUM(CASE WHEN points < 0 THEN ABS(points) ELSE 0 END), 0) as total_point_out,
                    MAX(created_at) as last_mutation_at
                ')
                ->first();

            $pointSummary->current_balance = Schema::hasTable('customer_loyalty_points')
                ? (int) (DB::table('customer_loyalty_points')->where('customer_id', $customer->id)->max('balance') ?? 0)
                : (int) (($pointSummary->total_point_in ?? 0) - ($pointSummary->total_point_out ?? 0));

            $pointMutations = DB::table('customer_loyalty_point_mutations')
                ->where('customer_id', $customer->id)
                ->selectRaw('type::text as type, mutation_type::text as mutation_type, points, balance_after, description, ref, created_at')
                ->orderByDesc('created_at')
                ->get();
        }

        $payload = compact('customer', 'transactionSummary', 'transactions', 'visits', 'pointSummary', 'pointMutations');

        return Excel::download(
            new CustomerProfileExport($payload),
            'customer-profile-'.$customer->id.'-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    /** Attach product/quantity summary for only the currently displayed page. */
    private function attachPurchaseDetails($transactions): void
    {
        $ecommerceOrderIds = $transactions
            ->where('channel', 'ECOMMERCE')
            ->pluck('transaction_id')
            ->filter()
            ->values();
        $visitIds = $transactions
            ->where('channel', 'POS')
            ->pluck('visit_id')
            ->filter()
            ->values();

        $ecommerceDetails = collect();
        if ($ecommerceOrderIds->isNotEmpty() && Schema::hasTable('ecommerce_order_details')) {
            $ecommerceDetails = DB::table('ecommerce_order_details as eod')
                ->leftJoin('products as p', 'p.id', '=', 'eod.product_id')
                ->whereIn('eod.ecommerce_order_id', $ecommerceOrderIds)
                ->selectRaw("eod.ecommerce_order_id, STRING_AGG(CONCAT(COALESCE(p.name, 'Produk'), ' ×', COALESCE(eod.quantity, 0)::text), ', ' ORDER BY COALESCE(p.name, 'Produk')) as purchase_details")
                ->groupBy('eod.ecommerce_order_id')
                ->pluck('purchase_details', 'ecommerce_order_id');
        }

        $posDetails = collect();
        if (
            $visitIds->isNotEmpty()
            && Schema::hasTable('store_carts')
            && Schema::hasTable('store_cart_details')
        ) {
            $posDetails = DB::table('store_carts as sc')
                ->join('store_cart_details as scd', 'scd.cart_id', '=', 'sc.id')
                ->leftJoin('items as i', 'i.id', '=', 'scd.item_id')
                ->leftJoin('product_prices as pp', 'pp.id', '=', 'scd.product_price_id')
                ->leftJoin('products as p', function ($join) {
                    $join->on('p.id', '=', 'i.product_id')
                        ->orOn('p.id', '=', 'pp.product_id');
                })
                ->whereIn('sc.visit_id', $visitIds)
                // Satu baris store_cart_details merepresentasikan satu item;
                // tabel ini tidak mempunyai kolom quantity.
                ->selectRaw("sc.visit_id, STRING_AGG(CONCAT(COALESCE(p.name, 'Produk'), ' ×1'), ', ' ORDER BY COALESCE(p.name, 'Produk')) as purchase_details")
                ->groupBy('sc.visit_id')
                ->pluck('purchase_details', 'visit_id');
        }

        $transactions->transform(function ($transaction) use ($ecommerceDetails, $posDetails) {
            $transaction->purchase_details = $transaction->channel === 'ECOMMERCE'
                ? ($ecommerceDetails->get($transaction->transaction_id) ?? '-')
                : ($posDetails->get($transaction->visit_id) ?? '-');

            return $transaction;
        });
    }

    private function buildDashboardData(Request $request): array
    {
        $from = $request->input('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->input('to') ?: now()->toDateString();
        $channel = $request->input('channel') ?? '';
        $storeId = $request->input('store_id');

        $fromTs = Carbon::parse($from)->startOfDay();
        $toTs = Carbon::parse($to)->endOfDay();

        $stores = Store::orderBy('name')->get(['id', 'name']);

        $storeName = $storeId
            ? optional($stores->firstWhere('id', $storeId))->name
            : null;

        $channelLabel = $channel === 'pos'
            ? 'POS'
            : ($channel === 'ecom' ? 'E-commerce' : 'Semua');

        $purchaseCustomers = $this->topPurchaseCustomers(
            fromTs: $fromTs,
            toTs: $toTs,
            channel: $channel,
            storeId: $storeId
        );

        $spendingCustomers = $this->topSpendingCustomers(
            fromTs: $fromTs,
            toTs: $toTs,
            channel: $channel,
            storeId: $storeId
        );

        $visitCustomers = $this->topVisitCustomers(
            fromTs: $fromTs,
            toTs: $toTs,
            storeId: $storeId
        );

        $branchOmsetRows = $this->topBranchOmset(
            fromTs: $fromTs,
            toTs: $toTs,
            channel: $channel,
            storeId: $storeId
        );

        $detailCustomerIds = collect()
            ->merge($purchaseCustomers->pluck('customer_id'))
            ->merge($spendingCustomers->pluck('customer_id'))
            ->merge($visitCustomers->pluck('customer_id'))
            ->filter()
            ->unique()
            ->values();

        $customerTransactionDetails = $this->customerTransactionDetails(
            fromTs: $fromTs,
            toTs: $toTs,
            channel: $channel,
            storeId: $storeId,
            customerIds: $detailCustomerIds
        );

        $customerVisitDetails = $this->customerVisitDetails(
            fromTs: $fromTs,
            toTs: $toTs,
            storeId: $storeId,
            customerIds: $detailCustomerIds
        );

        $summary = $this->summary(
            fromTs: $fromTs,
            toTs: $toTs,
            channel: $channel,
            storeId: $storeId
        );

        $topPurchase = $purchaseCustomers->first();
        $topSpending = $spendingCustomers->first();
        $topVisit = $visitCustomers->first();
        $topBranchOmset = $branchOmsetRows->first();

        $purchaseLabels = $purchaseCustomers
            ->map(fn ($row) => $row->customer_name ?? '-')
            ->values();

        $purchaseSeries = $purchaseCustomers
            ->map(fn ($row) => (int) ($row->total_purchases ?? 0))
            ->values();

        $spendingLabels = $spendingCustomers
            ->map(fn ($row) => $row->customer_name ?? '-')
            ->values();

        $spendingSeries = $spendingCustomers
            ->map(fn ($row) => (float) ($row->total_spending ?? 0))
            ->values();

        $visitLabels = $visitCustomers
            ->map(fn ($row) => $row->customer_name ?? '-')
            ->values();

        $visitSeries = $visitCustomers
            ->map(fn ($row) => (int) ($row->total_visits ?? 0))
            ->values();

        $branchOmsetLabels = $branchOmsetRows
            ->map(fn ($row) => $row->store_name ?? '-')
            ->values();

        $branchOmsetStoreSeries = $branchOmsetRows
            ->map(fn ($row) => (float) ($row->store_omset ?? 0))
            ->values();

        $branchOmsetEcommerceSeries = $branchOmsetRows
            ->map(fn ($row) => (float) ($row->ecommerce_omset ?? 0))
            ->values();

        $branchOmsetTotalSeries = $branchOmsetRows
            ->map(fn ($row) => (float) ($row->total_omset ?? 0))
            ->values();

        $branchOmsetStoreTransactions = $branchOmsetRows
            ->map(fn ($row) => (int) ($row->store_transactions ?? 0))
            ->values();

        $branchOmsetEcommerceTransactions = $branchOmsetRows
            ->map(fn ($row) => (int) ($row->ecommerce_transactions ?? 0))
            ->values();

        return compact(
            'from',
            'to',
            'channel',
            'channelLabel',
            'storeId',
            'storeName',
            'stores',
            'summary',

            'topPurchase',
            'topSpending',
            'topVisit',
            'topBranchOmset',

            'purchaseCustomers',
            'spendingCustomers',
            'visitCustomers',
            'branchOmsetRows',

            'purchaseLabels',
            'purchaseSeries',
            'spendingLabels',
            'spendingSeries',
            'visitLabels',
            'visitSeries',

            'customerTransactionDetails',
            'customerVisitDetails',

            'branchOmsetLabels',
            'branchOmsetStoreSeries',
            'branchOmsetEcommerceSeries',
            'branchOmsetTotalSeries',
            'branchOmsetStoreTransactions',
            'branchOmsetEcommerceTransactions'
        );
    }

    private function successStatuses(): array
    {
        return [
            'paid',
            'success',
            'settlement',
            'completed',
            'processed',
            'shipped',
            'received',
        ];
    }

    private function paidCustomerTransactions(
        Carbon $fromTs,
        Carbon $toTs,
        string $channel = '',
        ?string $storeId = null
    ) {
        if ($channel === 'pos') {
            return $this->posPaidCustomerTransactions($fromTs, $toTs, $storeId);
        }

        if ($channel === 'ecom') {
            return $this->ecommercePaidCustomerTransactions($fromTs, $toTs);
        }

        if ($storeId) {
            return $this->posPaidCustomerTransactions($fromTs, $toTs, $storeId);
        }

        return $this->posPaidCustomerTransactions($fromTs, $toTs, null)
            ->unionAll($this->ecommercePaidCustomerTransactions($fromTs, $toTs));
    }

    private function posPaidCustomerTransactions(
        Carbon $fromTs,
        Carbon $toTs,
        ?string $storeId = null
    ) {
        if (
            ! Schema::hasTable('store_customer_visits') ||
            ! Schema::hasTable('store_visit_payments') ||
            ! Schema::hasTable('payments') ||
            ! Schema::hasTable('customers') ||
            ! Schema::hasTable('stores')
        ) {
            return $this->emptyTransactionQuery();
        }

        $success = $this->successStatuses();

        $query = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as v', 'v.id', '=', 'svp.visit_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at)'), [$fromTs, $toTs])
            ->where(function ($q) use ($success) {
                $q->whereNotNull('p.completed_at')
                    ->orWhereIn(DB::raw('lower(p.status::text)'), $success);
            })
            ->when($storeId, fn ($q) => $q->where('v.store_id', $storeId));

        if (Schema::hasColumn('payments', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }

        if (Schema::hasColumn('stores', 'deleted_at')) {
            $query->whereNull('s.deleted_at');
        }

        return $query->selectRaw("
            c.id as customer_id,
            COALESCE(c.name, '-') as customer_name,
            c.wa_number as wa_number,

            'POS'::text as channel,

            s.id as store_id,
            s.name as store_name,

            p.id as transaction_id,
            COALESCE(p.transaction_number, p.id::text) as transaction_code,
            COALESCE(p.status::text, '-') as transaction_status,

            v.id as visit_id,
            COALESCE(v.visit_code, v.id::text) as visit_code,

            COALESCE(p.completed_at, p.created_at) as transaction_at,
            COALESCE(p.total_amount, 0)::numeric as amount
        ");
    }

    private function ecommercePaidCustomerTransactions(Carbon $fromTs, Carbon $toTs)
    {
        if (
            ! Schema::hasTable('ecommerce_orders') ||
            ! Schema::hasTable('ecommerce_order_details') ||
            ! Schema::hasTable('customers')
        ) {
            return $this->emptyTransactionQuery();
        }

        $success = $this->successStatuses();

        $orderTotals = DB::table('ecommerce_order_details as eod')
            ->selectRaw('
                eod.ecommerce_order_id,
                SUM(
                    COALESCE(eod.product_price, 0) *
                    COALESCE(eod.quantity, 0)
                )::numeric as order_total
            ')
            ->groupBy('eod.ecommerce_order_id');

        $query = DB::table('ecommerce_orders as eo')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoinSub($orderTotals, 'ot', function ($join) {
                $join->on('ot.ecommerce_order_id', '=', 'eo.id');
            })
            ->whereBetween('eo.created_at', [$fromTs, $toTs]);

        if (Schema::hasColumn('ecommerce_orders', 'status')) {
            $query->whereIn(DB::raw('lower(eo.status::text)'), $success);
            $statusSql = "COALESCE(eo.status::text, '-')";
        } else {
            $statusSql = "'-'";
        }

        $orderCodeSql = Schema::hasColumn('ecommerce_orders', 'order_number')
            ? 'COALESCE(eo.order_number, eo.id::text)'
            : 'eo.id::text';

        return $query->selectRaw("
            c.id as customer_id,
            COALESCE(c.name, '-') as customer_name,
            c.wa_number as wa_number,

            'ECOMMERCE'::text as channel,

            NULL::uuid as store_id,
            '-'::text as store_name,

            eo.id as transaction_id,
            {$orderCodeSql} as transaction_code,
            {$statusSql} as transaction_status,

            NULL::uuid as visit_id,
            '-'::text as visit_code,

            eo.created_at as transaction_at,
            COALESCE(ot.order_total, 0)::numeric as amount
        ");
    }

    private function emptyTransactionQuery()
    {
        return DB::query()->from(DB::raw("
            (
                SELECT
                    NULL::uuid as customer_id,
                    '-'::text as customer_name,
                    NULL::text as wa_number,
                    '-'::text as channel,
                    NULL::uuid as store_id,
                    '-'::text as store_name,
                    NULL::uuid as transaction_id,
                    '-'::text as transaction_code,
                    '-'::text as transaction_status,
                    NULL::uuid as visit_id,
                    '-'::text as visit_code,
                    NULL::timestamptz as transaction_at,
                    0::numeric as amount
                WHERE false
            ) as empty_tx
        "));
    }

    private function customerPurchaseBase(
        Carbon $fromTs,
        Carbon $toTs,
        string $channel = '',
        ?string $storeId = null
    ) {
        return DB::query()
            ->fromSub($this->paidCustomerTransactions($fromTs, $toTs, $channel, $storeId), 'x')
            ->whereNotNull('x.customer_id')
            ->selectRaw("
                x.customer_id,
                MAX(x.customer_name) as customer_name,
                MAX(x.wa_number) as wa_number,
                COUNT(DISTINCT x.transaction_id) as total_purchases,
                COALESCE(SUM(x.amount), 0) as total_spending,
                MIN(x.transaction_at) as first_purchase,
                MAX(x.transaction_at) as last_purchase,
                STRING_AGG(DISTINCT x.channel, ', ') as channels,
                STRING_AGG(DISTINCT x.store_name, ', ') as stores
            ")
            ->groupBy('x.customer_id');
    }

    private function topPurchaseCustomers(
        Carbon $fromTs,
        Carbon $toTs,
        string $channel = '',
        ?string $storeId = null
    ) {
        return DB::query()
            ->fromSub($this->customerPurchaseBase($fromTs, $toTs, $channel, $storeId), 'c')
            ->selectRaw('c.*')
            ->orderByDesc('c.total_purchases')
            ->orderByDesc('c.total_spending')
            ->limit(10)
            ->get();
    }

    private function topSpendingCustomers(
        Carbon $fromTs,
        Carbon $toTs,
        string $channel = '',
        ?string $storeId = null
    ) {
        return DB::query()
            ->fromSub($this->customerPurchaseBase($fromTs, $toTs, $channel, $storeId), 'c')
            ->selectRaw('c.*')
            ->orderByDesc('c.total_spending')
            ->orderByDesc('c.total_purchases')
            ->limit(10)
            ->get();
    }

    private function topVisitCustomers(
        Carbon $fromTs,
        Carbon $toTs,
        ?string $storeId = null
    ) {
        if (
            ! Schema::hasTable('store_customer_visits') ||
            ! Schema::hasTable('customers') ||
            ! Schema::hasTable('stores')
        ) {
            return collect();
        }

        return DB::table('store_customer_visits as v')
            ->join('customers as c', 'c.id', '=', 'v.customer_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->whereBetween('v.check_in_at', [$fromTs, $toTs])
            ->when($storeId, fn ($q) => $q->where('v.store_id', $storeId))
            ->selectRaw("
                c.id as customer_id,
                c.name as customer_name,
                c.wa_number as wa_number,
                COUNT(*) as total_visits,
                MIN(v.check_in_at) as first_visit,
                MAX(v.check_in_at) as last_visit,
                STRING_AGG(DISTINCT s.name, ', ') as stores
            ")
            ->groupBy('c.id', 'c.name', 'c.wa_number')
            ->orderByDesc('total_visits')
            ->limit(10)
            ->get();
    }

    private function customerTransactionDetails(
        Carbon $fromTs,
        Carbon $toTs,
        string $channel = '',
        ?string $storeId = null,
        $customerIds = null
    ) {
        $customerIds = collect($customerIds ?? [])->filter()->unique()->values();

        if ($customerIds->isEmpty()) {
            return collect();
        }

        return DB::query()
            ->fromSub($this->paidCustomerTransactions($fromTs, $toTs, $channel, $storeId), 'x')
            ->whereIn('x.customer_id', $customerIds)
            ->selectRaw('
                x.customer_id,
                x.customer_name,
                x.wa_number,
                x.channel,
                x.store_name,
                x.transaction_id,
                x.transaction_code,
                x.transaction_status,
                x.visit_id,
                x.visit_code,
                x.transaction_at,
                x.amount
            ')
            ->orderBy('x.customer_name')
            ->orderByDesc('x.transaction_at')
            ->get()
            ->groupBy('customer_id');
    }

    private function customerVisitDetails(
        Carbon $fromTs,
        Carbon $toTs,
        ?string $storeId = null,
        $customerIds = null
    ) {
        $customerIds = collect($customerIds ?? [])->filter()->unique()->values();

        if ($customerIds->isEmpty()) {
            return collect();
        }

        if (
            ! Schema::hasTable('store_customer_visits') ||
            ! Schema::hasTable('customers') ||
            ! Schema::hasTable('stores')
        ) {
            return collect();
        }

        return DB::table('store_customer_visits as v')
            ->join('customers as c', 'c.id', '=', 'v.customer_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->whereIn('v.customer_id', $customerIds)
            ->whereBetween('v.check_in_at', [$fromTs, $toTs])
            ->when($storeId, fn ($q) => $q->where('v.store_id', $storeId))
            ->selectRaw("
                v.customer_id,
                c.name as customer_name,
                c.wa_number,
                v.id as visit_id,
                COALESCE(v.visit_code, v.id::text) as visit_code,
                s.name as store_name,
                v.check_in_at,
                v.check_out_at,
                COALESCE(v.status::text, '-') as visit_status,
                COALESCE(v.payment_status::text, '-') as visit_payment_status,
                COALESCE(v.visit_type::text, '-') as visit_type
            ")
            ->orderBy('c.name')
            ->orderByDesc('v.check_in_at')
            ->get()
            ->groupBy('customer_id');
    }

    private function topBranchOmset(
        Carbon $fromTs,
        Carbon $toTs,
        string $channel = '',
        ?string $storeId = null
    ) {
        if ($channel === 'pos') {
            $base = $this->storeBranchOmsetQuery($fromTs, $toTs, $storeId);
        } elseif ($channel === 'ecom') {
            $base = $this->ecommerceBranchOmsetQuery($fromTs, $toTs, $storeId);
        } else {
            $base = $this->storeBranchOmsetQuery($fromTs, $toTs, $storeId)
                ->unionAll($this->ecommerceBranchOmsetQuery($fromTs, $toTs, $storeId));
        }

        return DB::query()
            ->fromSub($base, 'x')
            ->selectRaw("
                x.store_key,
                MAX(x.store_name) as store_name,

                SUM(CASE WHEN x.source = 'STORE' THEN x.total_transactions ELSE 0 END) as store_transactions,
                SUM(CASE WHEN x.source = 'ECOMMERCE' THEN x.total_transactions ELSE 0 END) as ecommerce_transactions,

                COALESCE(SUM(CASE WHEN x.source = 'STORE' THEN x.total_omset ELSE 0 END), 0) as store_omset,
                COALESCE(SUM(CASE WHEN x.source = 'ECOMMERCE' THEN x.total_omset ELSE 0 END), 0) as ecommerce_omset,

                COALESCE(SUM(x.total_omset), 0) as total_omset,
                COALESCE(SUM(x.total_transactions), 0) as total_transactions
            ")
            ->groupBy('x.store_key')
            ->orderByDesc('total_omset')
            ->limit(10)
            ->get();
    }

    private function storeBranchOmsetQuery(
        Carbon $fromTs,
        Carbon $toTs,
        ?string $storeId = null
    ) {
        if (
            ! Schema::hasTable('store_customer_visits') ||
            ! Schema::hasTable('store_visit_payments') ||
            ! Schema::hasTable('payments') ||
            ! Schema::hasTable('stores')
        ) {
            return $this->emptyBranchOmsetQuery();
        }

        $success = $this->successStatuses();

        $query = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as v', 'v.id', '=', 'svp.visit_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at)'), [$fromTs, $toTs])
            ->where(function ($q) use ($success) {
                $q->whereNotNull('p.completed_at')
                    ->orWhereIn(DB::raw('lower(p.status::text)'), $success);
            })
            ->when($storeId, fn ($q) => $q->where('v.store_id', $storeId));

        if (Schema::hasColumn('payments', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }

        if (Schema::hasColumn('stores', 'deleted_at')) {
            $query->whereNull('s.deleted_at');
        }

        return $query
            ->selectRaw("
                v.store_id::text as store_key,
                s.name as store_name,
                'STORE'::text as source,
                COUNT(DISTINCT p.id)::int as total_transactions,
                COALESCE(SUM(p.total_amount), 0)::numeric as total_omset
            ")
            ->groupBy('v.store_id', 's.name');
    }

    private function ecommerceBranchOmsetQuery(
        Carbon $fromTs,
        Carbon $toTs,
        ?string $storeId = null
    ) {
        if (
            ! Schema::hasTable('ecommerce_orders') ||
            ! Schema::hasTable('ecommerce_order_details')
        ) {
            return $this->emptyBranchOmsetQuery();
        }

        $success = $this->successStatuses();

        $orderTotals = DB::table('ecommerce_order_details as eod')
            ->selectRaw('
                eod.ecommerce_order_id,
                SUM(COALESCE(eod.product_price, 0) * COALESCE(eod.quantity, 0))::numeric as order_total
            ')
            ->groupBy('eod.ecommerce_order_id');

        $hasStoreOutbound = Schema::hasTable('store_outbounds')
            && Schema::hasColumn('store_outbounds', 'ecommerce_order_id')
            && Schema::hasColumn('store_outbounds', 'store_id')
            && Schema::hasTable('stores');

        if ($hasStoreOutbound) {
            $storeOutbound = DB::table('store_outbounds as so')
                ->whereNotNull('so.ecommerce_order_id')
                ->whereNotNull('so.store_id')
                ->selectRaw('so.ecommerce_order_id, so.store_id')
                ->groupBy('so.ecommerce_order_id', 'so.store_id');

            $query = DB::table('ecommerce_orders as eo')
                ->leftJoinSub($orderTotals, 'ot', function ($join) {
                    $join->on('ot.ecommerce_order_id', '=', 'eo.id');
                })
                ->leftJoinSub($storeOutbound, 'out_store', function ($join) {
                    $join->on('out_store.ecommerce_order_id', '=', 'eo.id');
                })
                ->leftJoin('stores as s', 's.id', '=', 'out_store.store_id')
                ->whereBetween('eo.created_at', [$fromTs, $toTs])
                ->when($storeId, fn ($q) => $q->where('out_store.store_id', $storeId));

            if (Schema::hasColumn('ecommerce_orders', 'status')) {
                $query->whereIn(DB::raw('lower(eo.status::text)'), $success);
            }

            if (Schema::hasColumn('stores', 'deleted_at')) {
                $query->where(function ($q) {
                    $q->whereNull('s.deleted_at')->orWhereNull('s.id');
                });
            }

            return $query
                ->selectRaw("
                    COALESCE(out_store.store_id::text, 'ECOMMERCE') as store_key,
                    COALESCE(s.name, 'Ecommerce') as store_name,
                    'ECOMMERCE'::text as source,
                    COUNT(DISTINCT eo.id)::int as total_transactions,
                    COALESCE(SUM(ot.order_total), 0)::numeric as total_omset
                ")
                ->groupBy('out_store.store_id', 's.name');
        }

        if ($storeId) {
            return $this->emptyBranchOmsetQuery();
        }

        $query = DB::table('ecommerce_orders as eo')
            ->leftJoinSub($orderTotals, 'ot', function ($join) {
                $join->on('ot.ecommerce_order_id', '=', 'eo.id');
            })
            ->whereBetween('eo.created_at', [$fromTs, $toTs]);

        if (Schema::hasColumn('ecommerce_orders', 'status')) {
            $query->whereIn(DB::raw('lower(eo.status::text)'), $success);
        }

        return $query->selectRaw("
            'ECOMMERCE'::text as store_key,
            'Ecommerce'::text as store_name,
            'ECOMMERCE'::text as source,
            COUNT(DISTINCT eo.id)::int as total_transactions,
            COALESCE(SUM(ot.order_total), 0)::numeric as total_omset
        ");
    }

    private function emptyBranchOmsetQuery()
    {
        return DB::query()->from(DB::raw("
            (
                SELECT
                    '-'::text as store_key,
                    '-'::text as store_name,
                    '-'::text as source,
                    0::int as total_transactions,
                    0::numeric as total_omset
                WHERE false
            ) as empty_branch_omset
        "));
    }

    private function summary(
        Carbon $fromTs,
        Carbon $toTs,
        string $channel = '',
        ?string $storeId = null
    ) {
        $purchaseSummary = DB::query()
            ->fromSub($this->paidCustomerTransactions($fromTs, $toTs, $channel, $storeId), 'x')
            ->selectRaw('
                COUNT(DISTINCT x.customer_id) as total_buyers,
                COUNT(DISTINCT x.transaction_id) as total_transactions,
                COALESCE(SUM(x.amount), 0) as total_spending
            ')
            ->first();

        $visitSummary = (object) [
            'total_visitors' => 0,
            'total_visits' => 0,
        ];

        if (
            Schema::hasTable('store_customer_visits') &&
            Schema::hasTable('customers')
        ) {
            $visitSummary = DB::table('store_customer_visits as v')
                ->join('customers as c', 'c.id', '=', 'v.customer_id')
                ->whereBetween('v.check_in_at', [$fromTs, $toTs])
                ->when($storeId, fn ($q) => $q->where('v.store_id', $storeId))
                ->selectRaw('
                    COUNT(DISTINCT c.id) as total_visitors,
                    COUNT(*) as total_visits
                ')
                ->first();
        }

        return (object) [
            'total_buyers' => (int) ($purchaseSummary->total_buyers ?? 0),
            'total_transactions' => (int) ($purchaseSummary->total_transactions ?? 0),
            'total_spending' => (float) ($purchaseSummary->total_spending ?? 0),
            'total_visitors' => (int) ($visitSummary->total_visitors ?? 0),
            'total_visits' => (int) ($visitSummary->total_visits ?? 0),
        ];
    }
}
