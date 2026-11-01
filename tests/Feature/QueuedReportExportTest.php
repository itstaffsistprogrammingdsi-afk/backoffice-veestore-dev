<?php

namespace Tests\Feature;

use App\Exports\EcommercePaymentExport;
use App\Jobs\GenerateReportExport;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QueuedReportExportTest extends TestCase
{
    public function test_row_limit_fails_instead_of_returning_truncated_report(): void
    {
        config(['report_exports.max_rows' => 1]);
        $export = new EcommercePaymentExport(Request::create('/', 'GET'));
        $property = new \ReflectionProperty($export, 'rows');
        $property->setAccessible(true);
        $property->setValue($export, new class
        {
            public function lazy($size)
            {
                return LazyCollection::make(function () {
                    yield (object) ['order_created_at' => null];
                    yield (object) ['order_created_at' => null];
                });
            }
        });
        $this->expectException(\RuntimeException::class);
        iterator_to_array($export->generator());
    }

    public function test_rejects_excessive_date_range_before_queueing(): void
    {
        $this->expectException(ValidationException::class);
        app(ReportExportService::class)->enqueue(Request::create('/', 'GET', [
            'start_date' => '2026-01-01', 'end_date' => '2026-02-02',
        ]), 'ecommerce-payment', 'test.xlsx');
    }

    public function test_generators_write_workbooks_in_worker(): void
    {
        // Existing schema only; all test records are rolled back and files removed.
        $db = DB::connection('immostore_backoffice');
        $db->beginTransaction();
        $paths = [];

        try {
            foreach (ReportExportService::TYPES as $type => $definition) {
                $id = (string) Str::uuid();
                $paths[] = 'private/report-exports/'.$id.'.xlsx';
                $db->table('report_exports')->insert([
                    'id' => $id, 'user_id' => (string) Str::uuid(), 'type' => $type,
                    'status' => 'queued', 'filename' => 'test.xlsx',
                    'filters' => json_encode([$definition[2] => '2099-01-01', $definition[3] => '2099-01-01']),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                (new GenerateReportExport($id))->handle();
                $row = $db->table('report_exports')->where('id', $id)->first();
                $this->assertSame('completed', $row->status, $type);
                $this->assertTrue(Storage::disk('local')->exists($row->path));
                $this->assertSame('PK', substr(Storage::disk('local')->get($row->path), 0, 2));
            }
        } finally {
            $db->rollBack();
            foreach ($paths as $path) {
                Storage::disk('local')->delete($path);
            }
        }
    }
}
