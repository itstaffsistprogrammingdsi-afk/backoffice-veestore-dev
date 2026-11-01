<?php

namespace Tests\Feature\AIAnalytics;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_analytics_dashboard_can_be_rendered(): void
    {
        $this->actingAs($this->admin())
            ->get(route('ai-analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Analytics/Index')
                ->has('recommendations')
                ->has('summary')
                ->has('stores')
                ->has('urls'));
    }

    public function test_recommendation_can_be_reviewed(): void
    {
        $db = DB::connection('immostore_backoffice');
        $db->beginTransaction();

        try {
            $runId = (string) Str::uuid();
            $recommendationId = (string) Str::uuid();

            $db->table('analytics_model_runs')->insert([
                'id' => $runId,
                'model_name' => 'test-model',
                'model_type' => 'forecast',
                'model_version' => '1.0.0',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $db->table('analytics_stock_recommendations')->insert([
                'id' => $recommendationId,
                'model_run_id' => $runId,
                'store_id' => (string) Str::uuid(),
                'product_id' => (string) Str::uuid(),
                'risk_level' => 'high',
                'status' => 'recommended',
                'generated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->actingAs($this->admin())
                ->patch(route('ai-analytics.recommendations.review', $recommendationId), [
                    'status' => 'approved',
                    'review_notes' => 'Disetujui untuk restock.',
                ])
                ->assertRedirect();

            $row = $db->table('analytics_stock_recommendations')->where('id', $recommendationId)->first();

            $this->assertSame('approved', $row->status);
            $this->assertSame('Disetujui untuk restock.', $row->review_notes);
            $this->assertNotNull($row->reviewed_by);
            $this->assertNotNull($row->reviewed_at);
        } finally {
            $db->rollBack();
        }
    }
}
