<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\IntegrationReadinessService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationController extends Controller
{
    public function edit(IntegrationReadinessService $readiness): Response
    {
        $providers = $readiness->all();

        return Inertia::render('Settings/Integrations', [
            'providers' => $providers,
            'summary' => [
                'ready' => collect($providers)->where('status', 'ready')->count(),
                'missing_credentials' => collect($providers)->where('status', 'missing_credentials')->count(),
                'disabled' => collect($providers)->where('status', 'disabled')->count(),
                'last_checked_at' => now()->toIso8601String(),
            ],
            'urls' => [
                'index' => route('settings.integrations.edit'),
                'verify' => route('settings.integrations.verify'),
                'payment_methods' => route('master-data.payment-methods.index'),
                'couriers' => route('master-data.couriers.index'),
                'ecommerce_payments' => route('ecommerce.payments.index'),
                'shipments' => route('monitoring.ecommerce-order-fulfillment.index'),
            ],
        ]);
    }

    public function verify(IntegrationReadinessService $readiness): JsonResponse
    {
        $providers = $readiness->all();

        return response()->json([
            'ok' => collect($providers)->every(fn (array $provider): bool => $provider['status'] === 'ready'),
            'checked_at' => now()->toIso8601String(),
            'providers' => $providers,
        ]);
    }
}
