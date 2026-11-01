<?php

namespace App\Jobs;

use App\Services\ReportExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(public string $exportId) {}

    public function handle(): void
    {
        $db = DB::connection('immostore_backoffice');
        $row = $db->table('report_exports')->where('id', $this->exportId)->first();

        if (! $row || $row->status === 'completed') {
            return;
        }

        $db->table('report_exports')->where('id', $this->exportId)->update([
            'status' => 'processing',
            'updated_at' => now(),
        ]);

        $path = 'private/report-exports/'.$this->exportId.'.xlsx';

        try {
            $class = ReportExportService::TYPES[$row->type][0];
            $request = Request::create('/', 'GET', json_decode($row->filters, true));

            if (! Excel::store(new $class($request), $path, 'local')) {
                throw new \RuntimeException('File export gagal disimpan.');
            }

            $db->table('report_exports')->where('id', $this->exportId)->update([
                'status' => 'completed',
                'path' => $path,
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);
            $this->failed($e);
            report($e);
        }
    }

    public function failed(?Throwable $e): void
    {
        DB::connection('immostore_backoffice')->table('report_exports')->where('id', $this->exportId)->update([
            'status' => 'failed',
            'error' => 'Export gagal. Persempit rentang/filter laporan lalu coba lagi.',
            'updated_at' => now(),
        ]);
    }
}
