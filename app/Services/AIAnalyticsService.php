<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AIAnalyticsService
{
    public function health(): array
    {
        try {
            $response = Http::acceptJson()->timeout((int) config('services.ai_analytics.timeout', 5))
                ->get(rtrim(config('services.ai_analytics.url'), '/') . '/health');
            return $response->successful() ? array_merge(['reachable' => true], $response->json()) : ['reachable'=>false,'status'=>'unavailable'];
        } catch (ConnectionException) {
            return ['reachable'=>false,'status'=>'unavailable'];
        }
    }

    public function runForecast(): array
    {
        return Http::acceptJson()->withHeaders(['X-API-Token'=>config('services.ai_analytics.token')])
            ->timeout((int) config('services.ai_analytics.job_timeout', 120))
            ->post(rtrim(config('services.ai_analytics.url'), '/') . '/api/v1/jobs/forecast')->throw()->json();
    }
}

