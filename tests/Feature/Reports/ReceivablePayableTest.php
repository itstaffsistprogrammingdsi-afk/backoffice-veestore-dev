<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class ReceivablePayableTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_receivable_payable_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.receivable-payable.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/ReceivablePayable')
                ->has('entries')
                ->has('summary')
                ->has('filters')
                ->where('canManage', true)
                ->has('today')
                ->has('urls.export'));
    }

    public function test_receivable_payable_export_returns_excel_file(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.receivable-payable.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_receivable_payable_due_status_filter_is_applied(): void
    {
        $this->actingAs($this->admin())
            ->post(route('reports.receivable-payable.store'), [
                'type' => 'receivable',
                'party' => 'Customer Overdue',
                'amount' => 100000,
                'due_date' => now()->subDay()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->get(route('reports.receivable-payable.index', ['due_status' => 'overdue']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/ReceivablePayable')
                ->where('filters.due_status', 'overdue')
                ->has('entries', 1)
                ->where('summary.overdue_receivable', 100000));
    }

    public function test_receivable_entry_can_be_added_marked_paid_and_deleted(): void
    {
        $this->actingAs($this->admin())
            ->post(route('reports.receivable-payable.store'), [
                'type' => 'receivable',
                'party' => 'Customer Piutang',
                'reference' => 'INV-001',
                'amount' => 750000,
                'due_date' => now()->addDays(7)->toDateString(),
                'notes' => 'Tempo 7 hari',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $entries = json_decode((string) DB::table('app_settings')->where('key', 'receivables_payables')->value('value'), true);

        $this->assertCount(1, $entries);
        $this->assertSame('receivable', $entries[0]['type']);
        $this->assertSame('open', $entries[0]['status']);

        $this->actingAs($this->admin())
            ->patch(route('reports.receivable-payable.status'), [
                'id' => $entries[0]['id'],
                'status' => 'paid',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $entries = json_decode((string) DB::table('app_settings')->where('key', 'receivables_payables')->value('value'), true);

        $this->assertSame('paid', $entries[0]['status']);
        $this->assertNotNull($entries[0]['paid_at']);

        $this->actingAs($this->admin())
            ->delete(route('reports.receivable-payable.destroy'), [
                'id' => $entries[0]['id'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $entries = json_decode((string) DB::table('app_settings')->where('key', 'receivables_payables')->value('value'), true);

        $this->assertSame([], $entries);
    }
}
