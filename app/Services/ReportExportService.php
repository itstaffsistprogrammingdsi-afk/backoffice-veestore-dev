<?php

namespace App\Services;

use App\Exports\EcommercePaymentExport;
use App\Exports\MonitoringDataCustomerExport;
use App\Exports\PromoUsageExport;
use App\Jobs\GenerateReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReportExportService
{
    public const TYPES = [
        'ecommerce-payment' => [EcommercePaymentExport::class, 'monitoring.ecommerce-payment.export', 'start_date', 'end_date'],
        'promo-usage' => [PromoUsageExport::class, 'monitoring.promo-usage.export', 'from', 'to'],
        'customers' => [MonitoringDataCustomerExport::class, 'monitoring.data-customer.export', 'start_date', 'end_date'],
    ];

    public function enqueue(Request $request, string $type, string $filename)
    {
        [, , $from, $to] = self::TYPES[$type];
        $request->merge([
            $from => $request->input($from) ?: now()->startOfMonth()->toDateString(),
            $to => $request->input($to) ?: now()->toDateString(),
        ]);
        $request->validate([
            $from => ['required', 'date_format:Y-m-d'],
            $to => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$from],
        ]);
        if (Carbon::parse($request->input($from))->diffInDays(Carbon::parse($request->input($to))) + 1 > config('report_exports.max_days')) {
            throw ValidationException::withMessages([$to => 'Rentang export maksimal '.config('report_exports.max_days').' hari.']);
        }

        $reportDb = DB::connection('immostore_backoffice');

        $id = DB::transaction(function () use ($request, $type, $filename, $reportDb) {
            // Serialize enqueue requests per owner to prevent unlimited concurrent exports.
            DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first(['id']);

            if ($reportDb->table('report_exports')->where('user_id', $request->user()->id)
                ->whereIn('status', ['queued', 'processing'])->exists()) {
                throw ValidationException::withMessages(['export' => 'Masih ada export yang berjalan. Tunggu sampai selesai.']);
            }

            $id = (string) Str::uuid();

            $reportDb->table('report_exports')->insert([
                'id' => $id, 'user_id' => $request->user()->id, 'type' => $type,
                'status' => 'queued', 'filters' => json_encode($request->except(['_token', '_method'])),
                'filename' => $filename, 'created_at' => now(), 'updated_at' => now(),
            ]);

            GenerateReportExport::dispatch($id)->onConnection(config('report_exports.connection'))->onQueue('exports');

            return $id;
        });

        return redirect()->route('report-exports.show', $id);
    }
}
