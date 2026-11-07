<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\VoucherReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PromoLoyaltyReportController extends Controller
{
    private const DATASETS = [
        'promoRows',
        'pointRows',
        'pointMutationRows',
        'referralRows',
        'promoUsageRows',
        'voucherUsageRows',
        'redeemPointRows',
    ];

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $acquisition = app(PromoAcquisitionReportController::class)->reportData(
            $this->providerRequest($filters)
        );

        $referral = app(ReferralScoreReportController::class)->reportData(
            $this->providerRequest($filters, [
                'from' => $filters['start_date'],
                'to' => $filters['end_date'],
                'q' => $filters['q'],
                'status' => $filters['status'],
            ])
        );

        $promoUsage = app(PromoUsageController::class)->reportData(
            $this->providerRequest($filters, [
                'from' => $filters['start_date'],
                'to' => $filters['end_date'],
                'q' => $filters['q'],
                'promo_id' => $filters['promo_id'],
                'store_id' => $filters['store_id'],
                'voucher_id' => $filters['voucher_id'],
                'order_status' => $filters['order_status'],
            ])
        );

        $voucher = app(VoucherReportController::class)->reportData(
            $this->providerRequest($filters, [
                'start' => $filters['start_date'],
                'end' => $filters['end_date'],
                'voucher' => $this->voucherCode($filters['voucher_id']),
            ])
        );

        $redeem = app(RedeemPointReportController::class)->reportData(
            $this->providerRequest($filters, [
                'from' => $filters['start_date'],
                'to' => $filters['end_date'],
                'q' => $filters['q'],
                'customer_id' => $filters['customer_id'],
            ])
        );

        $points = app(CustomerLoyaltyPointsReportController::class)->reportData(
            $this->providerRequest($filters, [
                'q' => $filters['q'],
                'mutation_type' => $filters['mutation_type'],
            ])
        );

        $referral['rows']->appends(['dataset' => 'referralRows']);
        $promoUsage['rows']->appends(['dataset' => 'promoUsageRows']);
        $voucher['vouchers']->appends(['dataset' => 'voucherUsageRows']);
        $redeem['rows']->appends(['dataset' => 'redeemPointRows']);
        $points['rows']->appends(['dataset' => 'pointMutationRows']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Promo, Poin, Referral & Voucher',
            'description' => 'Ringkasan promo, pemakaian voucher, mutasi poin, referral, dan redeem poin dalam satu laporan.',
            'data' => [
                'filters' => $filters,
                'activeDataset' => $this->activeDataset($request),
                'promoRows' => $acquisition['rows'],
                'pointRows' => $acquisition['pointRows'],
                'pointMutationRows' => $points['rows'],
                'referralRows' => $referral['rows'],
                'promoUsageRows' => $promoUsage['rows'],
                'voucherUsageRows' => $voucher['vouchers'],
                'redeemPointRows' => $redeem['rows'],
                'summary' => $acquisition['summary'],
                'stores' => $promoUsage['stores'],
                'promos' => $promoUsage['promos'],
                'vouchers' => $promoUsage['vouchers'],
                'customers' => $redeem['customers'],
                'statuses' => $referral['statuses'],
                'orderStatuses' => $promoUsage['orderStatuses'],
                'mutationTypes' => $points['mutationTypes'],
                'lastUpdate' => now()->format('d-m-Y H:i:s'),
            ],
            'filters' => $request->query(),
            'urls' => [
                'index' => route('monitoring.promo-loyalty.index'),
                'exports' => [
                    'pointMutationRows' => route('monitoring.promo-loyalty.export', ['dataset' => 'pointMutationRows']),
                    'referralRows' => route('monitoring.promo-loyalty.export', ['dataset' => 'referralRows']),
                    'promoUsageRows' => route('monitoring.promo-loyalty.export', ['dataset' => 'promoUsageRows']),
                    'voucherUsageRows' => route('monitoring.promo-loyalty.export', ['dataset' => 'voucherUsageRows']),
                    'redeemPointRows' => route('monitoring.promo-loyalty.export', ['dataset' => 'redeemPointRows']),
                ],
            ],
        ]);
    }

    public function export(Request $request)
    {
        $dataset = (string) $request->query('dataset', '');
        $filters = $this->filters($request);

        return match ($dataset) {
            'pointMutationRows' => app(CustomerLoyaltyPointsReportController::class)->export(
                $this->exportRequest($filters, [
                    'q' => $filters['q'],
                    'mutation_type' => $filters['mutation_type'],
                ])
            ),
            'referralRows' => app(ReferralScoreReportController::class)->export(
                $this->exportRequest($filters, [
                    'from' => $filters['start_date'],
                    'to' => $filters['end_date'],
                    'q' => $filters['q'],
                    'status' => $filters['status'],
                ])
            ),
            'promoUsageRows' => app(PromoUsageController::class)->export(
                $this->exportRequest($filters, [
                    'from' => $filters['start_date'],
                    'to' => $filters['end_date'],
                    'q' => $filters['q'],
                    'promo_id' => $filters['promo_id'],
                    'store_id' => $filters['store_id'],
                    'voucher_id' => $filters['voucher_id'],
                    'order_status' => $filters['order_status'],
                ])
            ),
            'voucherUsageRows' => app(VoucherReportController::class)->export(
                $this->exportRequest($filters, [
                    'start' => $filters['start_date'],
                    'end' => $filters['end_date'],
                    'voucher' => $this->voucherCode($filters['voucher_id']),
                ])
            ),
            'redeemPointRows' => app(RedeemPointReportController::class)->export(
                $this->exportRequest($filters, [
                    'from' => $filters['start_date'],
                    'to' => $filters['end_date'],
                    'q' => $filters['q'],
                    'customer_id' => $filters['customer_id'],
                ])
            ),
            default => back()->with('error', 'Dataset export tidak dikenali.'),
        };
    }

    private function filters(Request $request): array
    {
        $startDate = $this->normalizeDate($request->query('start_date'))
            ?: now()->startOfMonth()->toDateString();

        $endDate = $this->normalizeDate($request->query('end_date'))
            ?: now()->toDateString();

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'store_id' => $this->nullable($request->query('store_id')),
            'promo_id' => $this->nullable($request->query('promo_id')),
            'voucher_id' => $this->nullable($request->query('voucher_id')),
            'order_status' => $this->nullable($request->query('order_status')),
            'status' => $this->nullable($request->query('status')),
            'mutation_type' => $this->nullable($request->query('mutation_type')),
            'customer_id' => $this->nullable($request->query('customer_id')),
        ];
    }

    private function activeDataset(Request $request): ?string
    {
        $dataset = (string) $request->query('dataset', '');

        return in_array($dataset, self::DATASETS, true) ? $dataset : null;
    }

    private function providerRequest(array $filters, array $mapped = []): Request
    {
        $request = Request::create(route('monitoring.promo-loyalty.index'), 'GET', array_merge($filters, $mapped));
        $request->setUserResolver(fn ($guard = null) => auth()->guard($guard)->user());

        return $request;
    }

    private function exportRequest(array $filters, array $mapped): Request
    {
        $request = Request::create(
            route('monitoring.promo-loyalty.export'),
            'GET',
            array_merge($filters, $mapped, ['dataset' => request()->query('dataset')])
        );
        $request->setUserResolver(fn ($guard = null) => request()->user($guard));

        return $request;
    }

    private function voucherCode(?string $voucherId): ?string
    {
        if (! $voucherId) {
            return null;
        }

        return DB::table('vouchers')->where('id', $voucherId)->value('code');
    }

    private function normalizeDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        $date = trim($date);

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

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
