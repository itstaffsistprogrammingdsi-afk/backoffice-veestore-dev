<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class PaymentSlaReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_payment_sla_report_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.payment-sla.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.rows')
                ->has('data.summary')
                ->has('data.slaStatuses')
                ->has('data.filters')
                ->has('data.slaThresholdHours'));
    }

    public function test_payment_sla_report_accepts_filters(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.payment-sla.index', [
                'sla_status' => 'overdue',
                'q' => 'order',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.filters.sla_status', 'overdue')
                ->where('data.filters.q', 'order'));
    }
}
