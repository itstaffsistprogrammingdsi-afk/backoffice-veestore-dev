<?php

namespace App\Http\Controllers\Reports;

use App\Exports\ProfitLossExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Monitoring\OmsetKotorSalesController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfitLossController extends Controller
{
    private const STORAGE_KEY = 'pnl_expenses';

    public function index(Request $request, OmsetKotorSalesController $sales): Response
    {
        $period = $this->period($request);
        [$start, $end] = $this->periodRange($period);

        $finance = $sales->financialSummary($start, $end);
        $expenses = $this->expenses($period);

        return Inertia::render('Reports/ProfitLoss', [
            'period' => $period,
            'periodLabel' => Carbon::createFromFormat('Y-m', $period)->locale('id')->translatedFormat('F Y'),
            'finance' => $finance,
            'expenses' => $expenses,
            'summary' => $this->summary($finance, $expenses),
            'canManage' => $this->canManage(),
            'urls' => [
                'index' => route('reports.profit-loss.index'),
                'export' => route('reports.profit-loss.export'),
                'add_expense' => route('reports.profit-loss.add-expense'),
                'delete_expense' => route('reports.profit-loss.delete-expense'),
            ],
        ]);
    }

    public function export(Request $request, OmsetKotorSalesController $sales): BinaryFileResponse
    {
        $period = $this->period($request);
        [$start, $end] = $this->periodRange($period);
        $finance = $sales->financialSummary($start, $end);
        $expenses = $this->expenses($period);
        $periodLabel = Carbon::createFromFormat('Y-m', $period)->locale('id')->translatedFormat('F Y');

        $this->recordExportAudit($request, $period);

        return Excel::download(
            new ProfitLossExport($periodLabel, $finance, $expenses, $this->summary($finance, $expenses)),
            'estimasi-laba-operasional-'.$period.'.xlsx'
        );
    }

    public function addExpense(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'period.required' => 'Periode wajib diisi.',
            'period.date_format' => 'Format periode harus YYYY-MM.',
            'category.required' => 'Kategori beban wajib diisi.',
            'amount.required' => 'Nominal beban wajib diisi.',
            'amount.min' => 'Nominal beban harus lebih dari nol.',
        ]);

        $now = now();
        $entry = [
            'id' => (string) Str::uuid(),
            'period' => $data['period'],
            'category' => trim($data['category']),
            'amount' => round((float) $data['amount'], 2),
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
            'created_by' => auth()->user()?->name,
            'created_by_id' => auth()->id(),
            'created_at' => $now->toIso8601String(),
        ];

        $request->merge(['entry_id' => $entry['id']]);

        DB::transaction(function () use ($entry, $now): void {
            $entries = $this->allExpenses();
            $entries[] = $entry;

            DB::table('app_settings')->updateOrInsert(
                ['key' => self::STORAGE_KEY],
                [
                    'value' => json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        });

        return back()->with('success', 'Beban operasional berhasil ditambahkan.');
    }

    public function deleteExpense(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'id' => ['required', 'uuid'],
            'period' => ['required', 'date_format:Y-m'],
        ]);

        $entry = collect($this->allExpenses())->first(
            fn (array $candidate): bool => ($candidate['id'] ?? null) === $data['id']
                && ($candidate['period'] ?? null) === $data['period']
        );

        if ($entry === null) {
            return back()->with('error', 'Beban tidak ditemukan pada periode ini.');
        }

        $request->merge(['deleted_entry' => $entry]);

        $now = now();

        DB::transaction(function () use ($data, $now): void {
            $entries = array_values(array_filter(
                $this->allExpenses(),
                fn (array $candidate): bool => ($candidate['id'] ?? null) !== $data['id']
                    || ($candidate['period'] ?? null) !== $data['period']
            ));

            DB::table('app_settings')->updateOrInsert(
                ['key' => self::STORAGE_KEY],
                [
                    'value' => json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        });

        return back()->with('success', 'Beban operasional dihapus.');
    }

    /** @return array<string, float|int> */
    private function summary(array $finance, array $expenses): array
    {
        $totalExpenses = array_sum(array_column($expenses, 'amount'));
        $netProfit = (float) $finance['gross_profit'] - $totalExpenses;
        $netSales = (float) $finance['net_sales'];

        return [
            'net_sales' => $netSales,
            'base_price' => (float) $finance['base_price'],
            'gross_profit' => (float) $finance['gross_profit'],
            'gross_margin_percent' => (float) $finance['gross_margin_percent'],
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'net_margin_percent' => $netSales > 0 ? round(($netProfit / $netSales) * 100, 2) : 0,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function expenses(string $period): array
    {
        $entries = array_values(array_filter(
            $this->allExpenses(),
            fn (array $entry) => ($entry['period'] ?? null) === $period
        ));

        usort($entries, fn (array $left, array $right) => strcmp(
            (string) ($right['created_at'] ?? ''),
            (string) ($left['created_at'] ?? '')
        ));

        return $entries;
    }

    /** @return array<int, array<string, mixed>> */
    private function allExpenses(): array
    {
        $value = DB::table('app_settings')->where('key', self::STORAGE_KEY)->value('value');

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
        }

        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }

    private function period(Request $request): string
    {
        $period = trim((string) $request->query('period', ''));

        if ($period === '' || ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            return now()->format('Y-m');
        }

        return $period;
    }

    /** @return array{0: string, 1: string} */
    private function periodRange(string $period): array
    {
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start->toDateString(), $end->toDateString()];
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->getRoleNames()
            ->map(fn ($role) => preg_replace('/[^a-z0-9]/', '', strtolower($role)))
            ->contains(fn ($role) => in_array($role, ['superadmin', 'administrator', 'admin', 'management', 'finance'], true));
    }

    private function ensureCanManage(): void
    {
        abort_unless($this->canManage(), 403);
    }

    private function recordExportAudit(Request $request, string $period): void
    {
        try {
            $connection = 'immostore_backoffice';

            if (! Schema::connection($connection)->hasTable('business_audit_logs')) {
                return;
            }

            $user = auth()->user();

            DB::connection($connection)->table('business_audit_logs')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user?->getAuthIdentifier(),
                'user_name' => $user?->name ?? $user?->username ?? $user?->email,
                'app_type' => 'backoffice',
                'module' => 'Reports Profit Loss',
                'action' => 'export',
                'route_name' => 'reports.profit-loss.export',
                'url' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'response_status' => 200,
                'request_data' => json_encode(['period' => $period], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'context' => json_encode(['successful' => true, 'export' => 'xlsx'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Audit ekspor laba rugi gagal disimpan.', [
                'message' => $exception->getMessage(),
                'period' => $period,
            ]);
        }
    }
}
