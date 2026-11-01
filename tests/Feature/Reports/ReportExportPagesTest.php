<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class ReportExportPagesTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_report_export_pages_use_inertia(): void
    {
        $db = DB::connection('immostore_backoffice');
        $db->beginTransaction();

        try {
            $id = (string) Str::uuid();

            $db->table('report_exports')->insert([
                'id' => $id,
                'user_id' => $this->admin()->getKey(),
                'type' => 'ecommerce-payment',
                'status' => 'completed',
                'filters' => json_encode(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']),
                'filename' => 'test-export.xlsx',
                'path' => 'private/report-exports/test.xlsx',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->actingAs($this->admin())
                ->get(route('report-exports.index'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Reports/ExportList')
                    ->has('exports')
                    ->has('urls'));

            $this->actingAs($this->admin())
                ->get(route('report-exports.show', $id))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Reports/ExportStatus')
                    ->has('export')
                    ->has('urls')
                    ->has('limits'));
        } finally {
            $db->rollBack();
        }
    }
}
