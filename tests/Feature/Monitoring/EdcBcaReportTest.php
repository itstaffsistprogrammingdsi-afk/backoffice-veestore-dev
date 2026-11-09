<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class EdcBcaReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_edc_bca_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.edc-bca.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/EdcBca')
                ->has('rows')
                ->has('summary')
                ->has('byMethod')
                ->has('byStore')
                ->has('byDay')
                ->has('methods')
                ->has('statuses')
                ->has('stores')
                ->has('mdr')
                ->has('readiness')
                ->has('filters')
                ->has('urls'));
    }

    public function test_edc_bca_page_parses_transaction_metadata(): void
    {
        $suffix = Str::upper(Str::random(6));

        $methodId = (string) Str::uuid();

        DB::table('payment_methods')->insert([
            'id' => $methodId,
            'name' => 'BCA CC '.$suffix,
            'type' => 'non_cash',
            'created_at' => now(),
        ]);

        $paymentId = (string) Str::uuid();
        $transactionNumber = 'EDC-TRX-'.$suffix;

        DB::table('payments')->insert([
            'id' => $paymentId,
            'total_amount' => 150000,
            'change_amount' => 0,
            'paid_amount' => 150000,
            'status' => 'paid',
            'transaction_number' => $transactionNumber,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payment_details')->insert([
            'id' => (string) Str::uuid(),
            'payment_id' => $paymentId,
            'payment_method_id' => $methodId,
            'transaction_ref' => $transactionNumber,
            'amount' => 150000,
            'status' => 'success',
            'detail' => json_encode([
                'order' => ['terminal_id' => 'A01'],
                'transaction' => ['approval_code' => 'APP-1', 'batch_number' => 'B-1'],
                'card' => ['pan' => '4111111111111234', 'type' => 'CREDIT'],
                'acquirer' => ['name' => 'BCA'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('monitoring.edc-bca.index', ['q' => $transactionNumber]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/EdcBca')
                ->where('summary.total_transactions', 1)
                ->where('rows.data.0.metadata.terminal_id', 'A01')
                ->where('rows.data.0.metadata.approval_code', 'APP-1')
                ->where('rows.data.0.metadata.batch_number', 'B-1')
                ->where('rows.data.0.metadata.card_number', '************1234')
                ->where('rows.data.0.metadata.acquirer', 'BCA')
                ->where('rows.data.0.mdr_rate', 2)
                ->where('rows.data.0.mdr_amount', 3000)
                ->where('rows.data.0.net_amount', 147000));

        $this->actingAs($this->admin())
            ->get(route('monitoring.edc-bca.export', ['q' => $transactionNumber]))
            ->assertOk();
    }
}
