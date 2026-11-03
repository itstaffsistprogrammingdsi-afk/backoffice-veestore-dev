<?php

namespace App\Http\Controllers\Ecommerce;

use App\Domain\Ecommerce\Queries\EcommercePaymentOperationsQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EcommercePaymentOperationsController extends Controller
{
    public function __invoke(Request $request, EcommercePaymentOperationsQuery $query)
    {
        $q = trim((string) $request->query('q', ''));
        $paymentStatus = trim((string) $request->query('payment_status', ''));
        $paymentStatus = in_array($paymentStatus, ['pending', 'paid', 'failed', 'cancelled'], true)
            ? $paymentStatus
            : null;
        $paymentMethodCode = trim((string) $request->query('payment_method_code', ''));
        $paymentMethodCode = $paymentMethodCode !== '' ? $paymentMethodCode : null;
        $from = trim((string) $request->query('from', ''));
        $from = $from !== '' ? $from : null;
        $to = trim((string) $request->query('to', ''));
        $to = $to !== '' ? $to : null;

        return Inertia::render('Ecommerce/Payments', [
            ...$query->build($q, $paymentStatus, $paymentMethodCode, $from, $to),
            'filters' => [
                'q' => $q,
                'payment_status' => $paymentStatus,
                'payment_method_code' => $paymentMethodCode,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('ecommerce.payments.index'),
                'orders' => route('ecommerce.orders.index'),
                'report' => route('monitoring.ecommerce-payment.index'),
            ],
        ]);
    }
}
