<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportExportController extends Controller
{
    public function index(Request $request)
    {
        $exports = DB::connection('immostore_backoffice')->table('report_exports')->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')->paginate(20);

        return view('content.reports.export-list', compact('exports'));
    }

    public function show(Request $request, string $id)
    {
        $export = DB::connection('immostore_backoffice')->table('report_exports')->where('id', $id)->where('user_id', $request->user()->id)->first();
        abort_unless($export, 404);

        return view('content.reports.export-status', compact('export'));
    }

    public function download(Request $request, string $id)
    {
        $export = DB::connection('immostore_backoffice')->table('report_exports')->where('id', $id)->where('user_id', $request->user()->id)->first();
        abort_unless($export && $export->status === 'completed' && $export->path, 404);
        abort_unless(Storage::disk('local')->exists($export->path), 404);

        return Storage::disk('local')->download($export->path, $export->filename);
    }
}
