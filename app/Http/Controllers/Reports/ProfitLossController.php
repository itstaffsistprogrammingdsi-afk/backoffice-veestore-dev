<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Monitoring\OmsetKotorSalesController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfitLossController extends Controller
{
    private const STORAGE_KEY = 'pnl_expenses';

    public function index(Request $request, OmsetKotorSalesController $sales): Response
    {
        $period = $this->period($request);
        [$start, $end] = $this->periodRange($period);

        $finance = $sales->financialSummary($start, $end);
        $expenses = $this->expenses($period);
        $totalExpenses = array_sum(array_column($expenses, 'amount'));
        $netProfit = (float) $finance['gross_profit'] - $totalExpenses;
        $netSales = (float) $finance['net_sales'];

        return Inertia::render('Reports/ProfitLoss', [
            'period' => $period,
            'periodLabel' => Carbon::createFromFormat('Y-m', $period)->locale('id')->translatedFormat('F Y'),
            'finance' => $finance,
            'expenses' => $expenses,
            'summary' => [
                'net_sales' => $netSales,
                'base_price' => (float) $finance['base_price'],
                'gross_profit' => (float) $finance['gross_profit'],
                'gross_margin_percent' => (float) $finance['gross_margin_percent'],
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'net_margin_percent' => $netSales > 0 ? round(($netProfit / $netSales) * 100, 2) : 0,
            ],
            'urls' => [
                'index' => route('reports.profit-loss.index'),
                'add_expense' => route('reports.profit-loss.add-expense'),
                'delete_expense' => route('reports.profit-loss.delete-expense'),
            ],
        ]);
    }

    public function addExpense(Request $request): RedirectResponse
    {
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
            'created_at' => $now->toIso8601String(),
        ];

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
        $data = $request->validate([
            'id' => ['required', 'uuid'],
            'period' => ['required', 'date_format:Y-m'],
        ]);

        $now = now();

        DB::transaction(function () use ($data, $now): void {
            $entries = array_values(array_filter(
                $this->allExpenses(),
                fn (array $entry) => ($entry['id'] ?? null) !== $data['id']
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
}
