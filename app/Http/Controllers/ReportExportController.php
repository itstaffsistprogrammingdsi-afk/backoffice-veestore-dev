<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReportExportController extends Controller
{
    public function index(Request $request)
    {
        $exports = DB::connection('immostore_backoffice')->table('report_exports')->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')->paginate(20);

        return Inertia::render('Reports/ExportList', [
            'exports' => $exports,
            'urls' => [
                'show' => route('report-exports.show', ['id' => '__ID__']),
            ],
        ]);
    }

    public function show(Request $request, string $id)
    {
        $export = DB::connection('immostore_backoffice')->table('report_exports')->where('id', $id)->where('user_id', $request->user()->id)->first();
        abort_unless($export, 404);

        return Inertia::render('Reports/ExportStatus', [
            'export' => $export,
            'urls' => [
                'index' => route('report-exports.index'),
                'download' => route('report-exports.download', ['id' => $export->id]),
                'refresh' => url()->current(),
            ],
            'limits' => [
                'max_days' => config('report_exports.max_days'),
                'max_rows' => config('report_exports.max_rows'),
            ],
        ]);
    }

    public function download(Request $request, string $id)
    {
        $export = DB::connection('immostore_backoffice')->table('report_exports')->where('id', $id)->where('user_id', $request->user()->id)->first();
        abort_unless($export && $export->status === 'completed' && $export->path, 404);
        abort_unless(Storage::disk('local')->exists($export->path), 404);

        return Storage::disk('local')->download($export->path, $export->filename);
    }
}
