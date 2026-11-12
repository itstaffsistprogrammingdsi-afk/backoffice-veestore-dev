<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class PaymentReconciliationReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_payment_reconciliation_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.payment-reconciliation.index', [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('title', 'Rekonsiliasi Pembayaran')
                ->has('data.summary.orders')
                ->has('data.summary.exceptions')
                ->has('data.summary.cancelled_orders')
                ->has('data.summary.detail_mismatch')
                ->has('data.reconciliationStatuses')
                ->has('data.lastUpdate')
                ->has('urls.export'));
    }

    public function test_payment_reconciliation_export_returns_excel_file(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.payment-reconciliation.export', [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
