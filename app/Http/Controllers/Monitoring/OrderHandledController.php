<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderHandledController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-store-order-requests')
            ->only('storeOrders');
    }

    public function index(Request $request, EcommerceOrderFulfillmentController $ecommerce)
    {
        return $this->render(
            $request,
            'ecommerce',
            'ecommerceHandled',
            'Pesanan ecommerce beserta status pemenuhan, penanggung jawab, dan SLA-nya.',
            $ecommerce->report($request)
        );
    }

    public function storeOrders(Request $request, StoreOrderRequestController $storeOrders)
    {
        return $this->render(
            $request,
            'store-orders',
            'storeOrders',
            'Permintaan pesanan dari cabang ke gudang beserta status persetujuannya.',
            $storeOrders->report($request)
        );
    }

    public function storeReturns(Request $request, StoreReturnRequestController $storeReturns)
    {
        return $this->render(
            $request,
            'store-returns',
            'storeReturns',
            'Permintaan retur barang dari cabang beserta status penanganannya.',
            $storeReturns->report($request)
        );
    }

    private function render(Request $request, string $activeTab, string $datasetKey, string $description, array $report)
    {
        $rows = $report['rows'] ?? null;

        unset($report['rows']);

        $report[$datasetKey] = $rows;
        $report['orderTabs'] = $this->tabs($request, $activeTab);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Pesanan Ditangani',
            'description' => $description,
            'data' => $report,
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function tabs(Request $request, string $activeTab): array
    {
        $storeId = $request->query('store_id');
        $from = $request->query('start_date') ?: $request->query('from');
        $to = $request->query('end_date') ?: $request->query('to');

        $shared = array_filter([
            'store_id' => $storeId,
            'start_date' => $from,
            'end_date' => $to,
        ], fn ($value) => $value !== null && $value !== '');

        $returnShared = array_filter([
            'store_id' => $storeId,
            'from' => $from,
            'to' => $to,
        ], fn ($value) => $value !== null && $value !== '');

        return [
            [
                'key' => 'ecommerce',
                'label' => 'Ecommerce Handled',
                'url' => route('monitoring.order-handled.index', $shared),
                'icon' => 'mdi-cart-outline',
                'active' => $activeTab === 'ecommerce',
            ],
            [
                'key' => 'store-orders',
                'label' => 'Store Order',
                'url' => route('monitoring.order-handled.store-orders', $shared),
                'icon' => 'mdi-storefront-outline',
                'active' => $activeTab === 'store-orders',
            ],
            [
                'key' => 'store-returns',
                'label' => 'Store Return',
                'url' => route('monitoring.order-handled.store-returns', $returnShared),
                'icon' => 'mdi-backup-restore',
                'active' => $activeTab === 'store-returns',
            ],
        ];
    }
}
