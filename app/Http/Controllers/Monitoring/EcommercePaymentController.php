<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\EcommercePaymentExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class EcommercePaymentController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Default tanggal hari ini
        |--------------------------------------------------------------------------
        | Jika user belum pilih start_date dan end_date, halaman otomatis hanya
        | menampilkan data order hari ini.
        */
        $today = now()->toDateString();

        $customerName   = trim((string) $request->get('customer_name'));
        $email          = trim((string) $request->get('email'));
        $waNumber       = trim((string) $request->get('wa_number'));
        $orderNumber    = trim((string) $request->get('order_number'));
        $orderStatus    = $this->normalizeNullableString($request->get('order_status'));
        $paymentStatus  = $this->normalizeNullableString($request->get('payment_status'));
        $paymentMethod  = $this->normalizeNullableString($request->get('payment_method_id'));

        $startDate      = $this->normalizeDate($request->get('start_date')) ?: $today;
        $endDate        = $this->normalizeDate($request->get('end_date')) ?: $today;

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $paymentDetailAgg = $this->paymentDetailAggregate();
        $itemAgg = $this->itemAggregate();

        $filters = [
            'customer_name'     => $customerName,
            'email'             => $email,
            'wa_number'         => $waNumber,
            'order_number'      => $orderNumber,
            'order_status'      => $orderStatus,
            'payment_status'    => $paymentStatus,
            'payment_method_id' => $paymentMethod,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
        ];

        $query = $this->baseQuery($paymentDetailAgg, $itemAgg, $filters);

        $dashboardSummary = $this->dashboardSummary(clone $query);
        $chartOmzetDaily = $this->chartOmzetDaily(clone $query);
        $chartPaymentStatus = $this->chartPaymentStatus(clone $query);
        $chartPaymentMethod = $this->chartPaymentMethod(clone $query);

        $rows = $query
            ->orderByDesc('eo.created_at')
            ->paginate($perPage)
            ->withQueryString();

        $paymentMethods = DB::table('payment_methods')
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $orderStatuses = DB::table('ecommerce_orders')
            ->selectRaw('DISTINCT status::text as status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        $paymentStatuses = DB::table('payments')
            ->selectRaw('DISTINCT status::text as status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        return Inertia::render('Monitoring/Report', [
            'title' => 'Ecommerce Payment',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows'               => $rows,
            'customerName'       => $customerName,
            'email'              => $email,
            'waNumber'           => $waNumber,
            'orderNumber'        => $orderNumber,
            'orderStatus'        => $orderStatus,
            'paymentStatus'      => $paymentStatus,
            'paymentMethod'      => $paymentMethod,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'perPage'            => $perPage,
            'paymentMethods'     => $paymentMethods,
            'orderStatuses'      => $orderStatuses,
            'paymentStatuses'    => $paymentStatuses,
            'today'              => $today,
            'lastUpdate'         => now()->format('d-m-Y H:i:s'),

            'dashboardSummary'   => $dashboardSummary,
            'chartOmzetDaily'    => $chartOmzetDaily,
            'chartPaymentStatus' => $chartPaymentStatus,
            'chartPaymentMethod' => $chartPaymentMethod,
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function products(string $order)
    {
        $orderData = DB::table('ecommerce_orders as eo')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->where('eo.id', $order)
            ->select([
                'eo.id',
                'eo.order_number',
                'eo.status as order_status',
                'eo.created_at as order_created_at',
                'eo.sub_total',
                'eo.discount_total',
                'eo.grand_total',
                'c.name as customer_name',
                'c.email',
                'c.wa_number',
                'p.status as payment_status',
                'p.paid_amount',
            ])
            ->first();

        if (!$orderData) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        $hasPrice = Schema::hasColumn('ecommerce_order_details', 'price');
        $hasUnitPrice = Schema::hasColumn('ecommerce_order_details', 'unit_price');
        $hasDiscountPrice = Schema::hasColumn('ecommerce_order_details', 'discount_price');
        $hasFinalPrice = Schema::hasColumn('ecommerce_order_details', 'final_price');

        $details = DB::table('ecommerce_order_details as eod')
            ->leftJoin('products as p', 'p.id', '=', 'eod.product_id')
            ->where('eod.ecommerce_order_id', $order)
            ->select([
                'eod.id',
                'eod.product_id',
                DB::raw("COALESCE(p.code, '-') as product_code"),
                DB::raw("COALESCE(p.name, '-') as product_name"),
                DB::raw("COALESCE(eod.quantity, 0) as quantity"),
                DB::raw("COALESCE(eod.total_price, 0) as total_price"),
                DB::raw("COALESCE(eod.total_discount, 0) as total_discount"),
            ])
            ->when($hasPrice, function ($query) {
                $query->addSelect(DB::raw("COALESCE(eod.price, 0) as price"));
            }, function ($query) {
                $query->addSelect(DB::raw("0 as price"));
            })
            ->when($hasUnitPrice, function ($query) {
                $query->addSelect(DB::raw("COALESCE(eod.unit_price, 0) as unit_price"));
            }, function ($query) {
                $query->addSelect(DB::raw("0 as unit_price"));
            })
            ->when($hasDiscountPrice, function ($query) {
                $query->addSelect(DB::raw("COALESCE(eod.discount_price, 0) as discount_price"));
            }, function ($query) {
                $query->addSelect(DB::raw("0 as discount_price"));
            })
            ->when($hasFinalPrice, function ($query) {
                $query->addSelect(DB::raw("COALESCE(eod.final_price, 0) as final_price"));
            }, function ($query) {
                $query->addSelect(DB::raw("0 as final_price"));
            })
            ->orderBy('p.name')
            ->get()
            ->map(function ($row) {
                $quantity = (int) ($row->quantity ?? 0);
                $totalPrice = (float) ($row->total_price ?? 0);
                $totalDiscount = (float) ($row->total_discount ?? 0);

                $unitPrice = 0;

                if ((float) ($row->final_price ?? 0) > 0) {
                    $unitPrice = (float) $row->final_price;
                } elseif ((float) ($row->unit_price ?? 0) > 0) {
                    $unitPrice = (float) $row->unit_price;
                } elseif ((float) ($row->price ?? 0) > 0) {
                    $unitPrice = (float) $row->price;
                } elseif ($quantity > 0) {
                    $unitPrice = $totalPrice / $quantity;
                }

                return [
                    'id' => $row->id,
                    'product_id' => $row->product_id,
                    'product_code' => $row->product_code,
                    'product_name' => $row->product_name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_discount' => $totalDiscount,
                    'total_price' => $totalPrice,
                ];
            });

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $orderData->id,
                'order_number' => $orderData->order_number,
                'order_status' => $orderData->order_status,
                'order_created_at' => $orderData->order_created_at,
                'customer_name' => $orderData->customer_name,
                'email' => $orderData->email,
                'wa_number' => $orderData->wa_number,
                'payment_status' => $orderData->payment_status,
                'sub_total' => (float) ($orderData->sub_total ?? 0),
                'discount_total' => (float) ($orderData->discount_total ?? 0),
                'grand_total' => (float) ($orderData->grand_total ?? 0),
                'paid_amount' => (float) ($orderData->paid_amount ?? 0),
            ],
            'summary' => [
                'total_item' => $details->count(),
                'total_qty' => (int) $details->sum('quantity'),
                'total_discount' => (float) $details->sum('total_discount'),
                'total_price' => (float) $details->sum('total_price'),
            ],
            'products' => $details,
        ]);
    }

    public function export(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Export default hari ini
        |--------------------------------------------------------------------------
        | Supaya export juga mengikuti default halaman, jika tanggal kosong maka
        | otomatis export data hari ini.
        */
        $today = now()->toDateString();

        if (!$request->filled('start_date')) {
            $request->merge(['start_date' => $today]);
        }

        if (!$request->filled('end_date')) {
            $request->merge(['end_date' => $today]);
        }

        $filename = 'monitoring-ecommerce-payment-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new EcommercePaymentExport($request), $filename);
    }

    private function paymentDetailAggregate()
    {
        return DB::table('payment_details as pd')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->selectRaw("
                pd.payment_id,

                COALESCE(SUM(pd.amount), 0) as total_payment_detail_amount,

                STRING_AGG(
                    DISTINCT COALESCE(pm.name, '-'),
                    ', '
                ) as payment_methods,

                STRING_AGG(
                    DISTINCT COALESCE(pd.transaction_ref, '-'),
                    ', '
                ) as transaction_refs,

                STRING_AGG(
                    DISTINCT COALESCE(pd.status::text, '-'),
                    ', '
                ) as payment_detail_statuses,

                STRING_AGG(
                    DISTINCT COALESCE(
                        NULLIF((regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1], ''),
                        '-'
                    ),
                    ', '
                ) as midtrans_payment_types,

                STRING_AGG(
                    DISTINCT
                    CASE
                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'qris'
                            THEN 'Payment Gateway Midtrans - QRIS'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'bank_transfer'
                            THEN 'Payment Gateway Midtrans - Bank Transfer / Virtual Account'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'echannel'
                            THEN 'Payment Gateway Midtrans - Mandiri Bill Payment / E-Channel'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'gopay'
                            THEN 'Payment Gateway Midtrans - GoPay'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'shopeepay'
                            THEN 'Payment Gateway Midtrans - ShopeePay'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'cstore'
                            THEN 'Payment Gateway Midtrans - Convenience Store'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'credit_card'
                            THEN 'Payment Gateway Midtrans - Credit Card'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'akulaku'
                            THEN 'Payment Gateway Midtrans - Akulaku'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'kredivo'
                            THEN 'Payment Gateway Midtrans - Kredivo'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'bca_klikpay'
                            THEN 'Payment Gateway Midtrans - BCA KlikPay'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'bri_epay'
                            THEN 'Payment Gateway Midtrans - BRI ePay'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'danamon_online'
                            THEN 'Payment Gateway Midtrans - Danamon Online Banking'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] IS NOT NULL
                            THEN CONCAT(
                                'Payment Gateway Midtrans - ',
                                UPPER(REPLACE((regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1], '_', ' '))
                            )

                        WHEN LOWER(COALESCE(pm.name, '')) LIKE '%midtrans%'
                            THEN 'Payment Gateway Midtrans - Method tidak tersedia di detail'

                        ELSE COALESCE(pm.name, '-')
                    END,
                    ', '
                ) as payment_method_descriptions
            ")
            ->groupBy('pd.payment_id');
    }

    private function itemAggregate()
    {
        return DB::table('ecommerce_order_details as eod')
            ->leftJoin('products as pr', 'pr.id', '=', 'eod.product_id')
            ->selectRaw("
                eod.ecommerce_order_id,
                COALESCE(SUM(eod.quantity), 0) as total_qty,
                COALESCE(SUM(eod.total_price), 0) as total_item_price,
                COALESCE(SUM(eod.total_discount), 0) as total_item_discount,
                STRING_AGG(DISTINCT COALESCE(pr.name, '-'), ', ') as product_names
            ")
            ->groupBy('eod.ecommerce_order_id');
    }

    private function baseQuery($paymentDetailAgg, $itemAgg, array $filters)
    {
        $customerName  = $filters['customer_name'] ?? '';
        $email         = $filters['email'] ?? '';
        $waNumber      = $filters['wa_number'] ?? '';
        $orderNumber   = $filters['order_number'] ?? '';
        $orderStatus   = $filters['order_status'] ?? null;
        $paymentStatus = $filters['payment_status'] ?? null;
        $paymentMethod = $filters['payment_method_id'] ?? null;
        $startDate     = $filters['start_date'] ?? null;
        $endDate       = $filters['end_date'] ?? null;

        return DB::table('ecommerce_orders as eo')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->leftJoinSub($paymentDetailAgg, 'pda', function ($join) {
                $join->on('pda.payment_id', '=', 'p.id');
            })
            ->leftJoinSub($itemAgg, 'ia', function ($join) {
                $join->on('ia.ecommerce_order_id', '=', 'eo.id');
            })
            ->leftJoin('ecommerce_order_shipping_addresses as sa', 'sa.id', '=', 'eo.shipping_address_snapshot_id')
            ->leftJoin('ecommerce_order_shipping_methods as sm', 'sm.id', '=', 'eo.shipping_method_id')
            ->leftJoin('provinces as prov', 'prov.code', '=', 'sa.province_code')
            ->leftJoin('regencies as reg', 'reg.code', '=', 'sa.regency_code')
            ->leftJoin('districts as dist', 'dist.code', '=', 'sa.district_code')
            ->leftJoin('villages as vill', 'vill.code', '=', 'sa.village_code')
            ->select([
                'eo.id',
                'eo.order_number',
                'eo.status as order_status',
                'eo.created_at as order_created_at',
                'eo.sub_total',
                'eo.discount_total',
                'eo.grand_total',
                'eo.shipping_number',
                'eo.shipping_method_id',

                DB::raw("COALESCE(sm.type, '-') as shipping_method_type"),
                DB::raw("COALESCE(sm.courier_code, '-') as shipping_courier_code"),
                DB::raw("COALESCE(sm.courier_name, '-') as shipping_courier_name"),
                DB::raw("COALESCE(sm.service, '-') as shipping_service"),
                DB::raw("COALESCE(sm.duration, '-') as shipping_duration"),
                DB::raw("COALESCE(sm.price, 0) as shipping_price"),
                DB::raw("
                    CASE
                        WHEN sm.id IS NULL THEN '-'
                        WHEN sm.courier_name IS NULL OR sm.courier_name = '' THEN '-'
                        WHEN sm.service IS NULL OR sm.service = '' THEN sm.courier_name
                        ELSE CONCAT(sm.courier_name, ' - ', sm.service)
                    END as shipping_method_label
                "),

                DB::raw("COALESCE(c.name, '-') as customer_name"),
                DB::raw("COALESCE(c.email, '-') as email"),
                DB::raw("COALESCE(c.wa_number, '-') as wa_number"),

                'p.id as payment_id',
                'p.status as payment_status',
                'p.note as payment_note',
                'p.transaction_number',
                DB::raw("COALESCE(p.total_amount, 0) as total_amount"),
                DB::raw("COALESCE(p.paid_amount, 0) as paid_amount"),
                DB::raw("COALESCE(p.change_amount, 0) as change_amount"),
                DB::raw("COALESCE(p.tax_amount, 0) as tax_amount"),
                'p.completed_at as payment_completed_at',

                DB::raw('COALESCE(pda.total_payment_detail_amount, 0) as total_payment_detail_amount'),
                DB::raw('COALESCE(pda.payment_methods, \'-\') as payment_methods'),
                DB::raw('COALESCE(pda.payment_method_descriptions, COALESCE(pda.payment_methods, \'-\')) as payment_method_descriptions'),
                DB::raw('COALESCE(pda.midtrans_payment_types, \'-\') as midtrans_payment_types'),
                DB::raw('COALESCE(pda.transaction_refs, \'-\') as transaction_refs'),
                DB::raw('COALESCE(pda.payment_detail_statuses, \'-\') as payment_detail_statuses'),

                DB::raw('COALESCE(ia.total_qty, 0) as total_qty'),
                DB::raw('COALESCE(ia.total_item_price, 0) as total_item_price'),
                DB::raw('COALESCE(ia.total_item_discount, 0) as total_item_discount'),
                DB::raw('COALESCE(ia.product_names, \'-\') as product_names'),

                DB::raw("COALESCE(sa.name, '-') as receiver_name"),
                DB::raw("COALESCE(sa.phone, '-') as receiver_phone"),
                DB::raw("COALESCE(sa.detail, '-') as address_detail"),
                DB::raw("COALESCE(sa.other_detail, '-') as address_other_detail"),
                DB::raw("COALESCE(prov.name, '-') as province_name"),
                DB::raw("COALESCE(reg.name, '-') as regency_name"),
                DB::raw("COALESCE(dist.name, '-') as district_name"),
                DB::raw("COALESCE(vill.name, '-') as village_name"),
            ])
            ->when($customerName !== '', function ($q) use ($customerName) {
                $q->where('c.name', 'ILIKE', "%{$customerName}%");
            })
            ->when($email !== '', function ($q) use ($email) {
                $q->where('c.email', 'ILIKE', "%{$email}%");
            })
            ->when($waNumber !== '', function ($q) use ($waNumber) {
                $q->where('c.wa_number', 'ILIKE', "%{$waNumber}%");
            })
            ->when($orderNumber !== '', function ($q) use ($orderNumber) {
                $q->where('eo.order_number', 'ILIKE', "%{$orderNumber}%");
            })
            ->when($orderStatus, function ($q) use ($orderStatus) {
                $q->where('eo.status', $orderStatus);
            })
            ->when($paymentStatus, function ($q) use ($paymentStatus) {
                $q->where('p.status', $paymentStatus);
            })
            ->when($paymentMethod, function ($q) use ($paymentMethod) {
                $q->whereExists(function ($sub) use ($paymentMethod) {
                    $sub->select(DB::raw(1))
                        ->from('payment_details as pdx')
                        ->whereColumn('pdx.payment_id', 'eo.payment_id')
                        ->where('pdx.payment_method_id', $paymentMethod);
                });
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('eo.created_at', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('eo.created_at', '<=', $endDate);
            });
    }

    private function dashboardSummary($query)
    {
        return DB::query()
            ->fromSub($query, 'x')
            ->selectRaw("
                COUNT(DISTINCT x.id)::int as total_order,
                COALESCE(SUM(x.total_qty), 0)::int as total_qty,

                COALESCE(SUM(x.sub_total), 0) as total_sub_total,
                COALESCE(SUM(x.discount_total), 0) as total_discount,
                COALESCE(SUM(x.grand_total), 0) as total_grand_total,
                COALESCE(SUM(x.paid_amount), 0) as total_paid_amount,

                COALESCE(SUM(x.grand_total), 0) - COALESCE(SUM(x.paid_amount), 0) as total_outstanding,

                COALESCE(AVG(NULLIF(x.grand_total, 0)), 0) as average_order_value
            ")
            ->first();
    }

    private function chartOmzetDaily($query)
    {
        return DB::query()
            ->fromSub($query, 'x')
            ->selectRaw("
                DATE(x.order_created_at) as tanggal,
                COUNT(DISTINCT x.id)::int as total_order,
                COALESCE(SUM(x.grand_total), 0) as total_omset,
                COALESCE(SUM(x.paid_amount), 0) as total_paid
            ")
            ->groupByRaw('DATE(x.order_created_at)')
            ->orderByRaw('DATE(x.order_created_at)')
            ->get();
    }

    private function chartPaymentStatus($query)
    {
        return DB::query()
            ->fromSub($query, 'x')
            ->selectRaw("
                COALESCE(x.payment_status::text, '-') as label,
                COUNT(DISTINCT x.id)::int as total_order,
                COALESCE(SUM(x.grand_total), 0) as total_omset
            ")
            ->groupByRaw("COALESCE(x.payment_status::text, '-')")
            ->orderByDesc('total_order')
            ->get();
    }

    private function chartPaymentMethod($query)
    {
        return DB::query()
            ->fromSub($query, 'x')
            ->selectRaw("
                COALESCE(x.payment_method_descriptions, '-') as label,
                COUNT(DISTINCT x.id)::int as total_order,
                COALESCE(SUM(x.grand_total), 0) as total_omset
            ")
            ->groupByRaw("COALESCE(x.payment_method_descriptions, '-')")
            ->orderByDesc('total_omset')
            ->limit(10)
            ->get();
    }

    private function normalizeDate($date): ?string
    {
        if (!$date) {
            return null;
        }

        $date = trim((string) $date);

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            [$day, $month, $year] = explode('/', $date);
            return "{$year}-{$month}-{$day}";
        }

        return null;
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
