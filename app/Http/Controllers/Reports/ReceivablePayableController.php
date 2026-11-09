<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ReceivablePayableController extends Controller
{
    private const STORAGE_KEY = 'receivables_payables';

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $entries = $this->filteredEntries($filters);
        $today = now()->toDateString();

        $open = array_filter($entries, fn (array $entry): bool => ($entry['status'] ?? 'open') === 'open');
        $overdue = array_filter($open, fn (array $entry): bool => ($entry['due_date'] ?? '') !== ''
            && ($entry['due_date'] ?? '') < $today);

        $summary = [
            'total_receivable' => array_sum(array_map(
                fn (array $entry): float => ($entry['type'] ?? '') === 'receivable' ? (float) $entry['amount'] : 0,
                $open
            )),
            'total_payable' => array_sum(array_map(
                fn (array $entry): float => ($entry['type'] ?? '') === 'payable' ? (float) $entry['amount'] : 0,
                $open
            )),
            'overdue_amount' => array_sum(array_map(fn (array $entry): float => (float) $entry['amount'], $overdue)),
            'overdue_count' => count($overdue),
            'paid_count' => count(array_filter($entries, fn (array $entry): bool => ($entry['status'] ?? '') === 'paid')),
        ];

        return Inertia::render('Reports/ReceivablePayable', [
            'entries' => array_values($entries),
            'summary' => $summary,
            'filters' => $filters,
            'canManage' => $this->canManage(),
            'urls' => [
                'index' => route('reports.receivable-payable.index'),
                'store' => route('reports.receivable-payable.store'),
                'status' => route('reports.receivable-payable.status'),
                'destroy' => route('reports.receivable-payable.destroy'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'type' => ['required', 'in:receivable,payable'],
            'party' => ['required', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'type.required' => 'Jenis wajib dipilih.',
            'party.required' => 'Nama pihak wajib diisi.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.min' => 'Nominal harus lebih dari nol.',
        ]);

        $now = now();
        $entry = [
            'id' => (string) Str::uuid(),
            'type' => $data['type'],
            'party' => trim($data['party']),
            'reference' => trim((string) ($data['reference'] ?? '')) ?: null,
            'amount' => round((float) $data['amount'], 2),
            'due_date' => $data['due_date'] ?? null,
            'status' => 'open',
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'created_by' => auth()->user()?->name,
            'created_at' => $now->toIso8601String(),
            'paid_at' => null,
        ];

        $this->mutateEntries(function (array $entries) use ($entry): array {
            $entries[] = $entry;

            return $entries;
        });

        return back()->with('success', 'Catatan piutang/hutang berhasil ditambahkan.');
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'id' => ['required', 'uuid'],
            'status' => ['required', 'in:open,paid'],
        ]);

        $this->mutateEntries(function (array $entries) use ($data): array {
            return array_map(function (array $entry) use ($data): array {
                if (($entry['id'] ?? null) !== $data['id']) {
                    return $entry;
                }

                $entry['status'] = $data['status'];
                $entry['paid_at'] = $data['status'] === 'paid' ? now()->toIso8601String() : null;

                return $entry;
            }, $entries);
        });

        return back()->with('success', $data['status'] === 'paid'
            ? 'Catatan ditandai lunas.'
            : 'Catatan dikembalikan ke status terbuka.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->ensureCanManage();

        $data = $request->validate([
            'id' => ['required', 'uuid'],
        ]);

        $this->mutateEntries(function (array $entries) use ($data): array {
            return array_values(array_filter(
                $entries,
                fn (array $entry): bool => ($entry['id'] ?? null) !== $data['id']
            ));
        });

        return back()->with('success', 'Catatan piutang/hutang dihapus.');
    }

    private function filteredEntries(array $filters): array
    {
        $entries = $this->allEntries();

        $entries = array_filter($entries, function (array $entry) use ($filters): bool {
            if ($filters['type'] !== '' && ($entry['type'] ?? '') !== $filters['type']) {
                return false;
            }

            if ($filters['status'] !== '' && ($entry['status'] ?? 'open') !== $filters['status']) {
                return false;
            }

            if ($filters['q'] !== '') {
                $needle = mb_strtolower($filters['q']);
                $haystack = mb_strtolower(implode(' ', [
                    $entry['party'] ?? '',
                    $entry['reference'] ?? '',
                    $entry['notes'] ?? '',
                ]));

                if (! str_contains($haystack, $needle)) {
                    return false;
                }
            }

            return true;
        });

        usort($entries, function (array $left, array $right): int {
            $leftStatus = ($left['status'] ?? 'open') === 'open' ? 0 : 1;
            $rightStatus = ($right['status'] ?? 'open') === 'open' ? 0 : 1;

            if ($leftStatus !== $rightStatus) {
                return $leftStatus <=> $rightStatus;
            }

            return strcmp((string) ($left['due_date'] ?? '9999-12-31'), (string) ($right['due_date'] ?? '9999-12-31'));
        });

        return array_values($entries);
    }

    private function allEntries(): array
    {
        $value = DB::table('app_settings')->where('key', self::STORAGE_KEY)->value('value');

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
        }

        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }

    private function mutateEntries(callable $mutator): void
    {
        DB::transaction(function () use ($mutator): void {
            $entries = $mutator($this->allEntries());
            $now = now();

            DB::table('app_settings')->updateOrInsert(
                ['key' => self::STORAGE_KEY],
                [
                    'value' => json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        });
    }

    private function filters(Request $request): array
    {
        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('status', ''));

        if (! in_array($type, ['receivable', 'payable'], true)) {
            $type = '';
        }

        if (! in_array($status, ['open', 'paid'], true)) {
            $status = '';
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'type' => $type,
            'status' => $status,
        ];
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
}
