<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class ProfitLossTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_profit_loss_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.profit-loss.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/ProfitLoss')
                ->has('finance')
                ->has('finance.channels')
                ->has('expenses')
                ->has('summary')
                ->has('period')
                ->has('urls'));
    }

    public function test_operating_expense_can_be_added_and_deleted(): void
    {
        $period = now()->format('Y-m');

        $this->actingAs($this->admin())
            ->post(route('reports.profit-loss.add-expense'), [
                'period' => $period,
                'category' => 'Listrik',
                'amount' => 1250000,
                'note' => 'Tagihan bulan ini',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $entries = json_decode((string) DB::table('app_settings')->where('key', 'pnl_expenses')->value('value'), true);

        $this->assertCount(1, $entries);
        $this->assertSame('Listrik', $entries[0]['category']);
        $this->assertSame(1250000.0, (float) $entries[0]['amount']);

        $this->actingAs($this->admin())
            ->delete(route('reports.profit-loss.delete-expense'), [
                'id' => $entries[0]['id'],
                'period' => $period,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $entries = json_decode((string) DB::table('app_settings')->where('key', 'pnl_expenses')->value('value'), true);

        $this->assertSame([], $entries);
    }
}
