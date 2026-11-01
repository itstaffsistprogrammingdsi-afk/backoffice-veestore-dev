<?php

namespace App\Domain\Analytics\Actions;

use App\Services\AIAnalyticsService;

class RunForecast
{
    public function __construct(private readonly AIAnalyticsService $service) {}

    public function handle(): array
    {
        return $this->service->runForecast();
    }
}
