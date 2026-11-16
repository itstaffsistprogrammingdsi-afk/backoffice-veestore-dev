<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class AnomalyItemReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_anomaly_item_page_uses_inertia_with_summary_and_demographics(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.anomali-item.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.rows')
                ->has('data.summary')
                ->has('data.summary.total_item')
                ->has('data.demographicSummary.total_anomaly_item')
                ->has('data.productDemographicRows')
                ->has('data.areaDemographicRows')
                ->has('data.conditionDemographicRows')
                ->has('data.anomalyDemographicRows')
                ->has('data.riskDemographicRows')
                ->has('urls.index'));
    }
}
