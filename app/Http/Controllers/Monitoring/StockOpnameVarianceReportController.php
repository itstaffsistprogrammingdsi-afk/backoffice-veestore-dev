<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StockOpnameVarianceReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();

        $base = $this->baseQuery($start, $end)
            ->when($filters['store_id'] !== '', fn ($q) => $q->where('store_id', $filters['store_id']))
            ->when($filters['location_id'] !== '', fn ($q) => $q->where('location_id', $filters['location_id']));

        $filtered = DB::query()
            ->fromSub(clone $base, 'x')
            ->when($filters['opname_status'] !== '', fn ($q) => $q->where('opname_status', $filters['opname_status']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $like = '%'.$filters['q'].'%';

                $q->where(function ($search) use ($like) {
                    $search->where('product_name', 'ILIKE', $like)
                        ->orWhere('product_code', 'ILIKE', $like)
                        ->orWhere('store_name', 'ILIKE', $like)
                        ->orWhere('location_name', 'ILIKE', $like)
                        ->orWhere('opname_code', 'ILIKE', $like)
                        ->orWhere('pic_name', 'ILIKE', $like)
                        ->orWhere('approver_name', 'ILIKE', $like)
                        ->orWhere('difference_reason', 'ILIKE', $like);
                });
            });

        $itemRows = (clone $filtered)
            ->orderByDesc('report_at')
            ->orderByDesc('product_name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $rows = DB::query()
            ->fromSub(clone $filtered, 'x')
            ->selectRaw('
                x.session_id,
                MAX(x.opname_code) AS opname_code,
                MAX(x.opname_type) AS opname_type,
                MAX(x.opname_status) AS opname_status,
                MAX(x.counting_round) AS counting_round,
                x.store_id,
                MAX(x.store_name) AS store_name,
                MAX(x.store_code) AS store_code,
                MAX(x.location_name) AS location_name,
                MAX(x.pic_name) AS pic_name,
                MAX(x.approver_name) AS approver_name,
                MAX(x.approval_status_label) AS approval_status_label,
                MAX(x.scheduled_at) AS scheduled_at,
                MAX(x.started_at) AS started_at,
                MAX(x.finished_at) AS finished_at,
                MAX(x.approved_at) AS approved_at,
                MAX(x.session_created_by) AS session_created_by,
                MAX(x.session_updated_at) AS session_updated_at,
                MAX(x.report_at) AS report_at,
                COUNT(*)::int AS item_count,
                COUNT(*) FILTER (WHERE x.difference_qty <> 0)::int AS discrepancy_items,
                COUNT(*) FILTER (WHERE x.difference_qty < 0)::int AS shortage_items,
                COUNT(*) FILTER (WHERE x.difference_qty > 0)::int AS surplus_items,
                COALESCE(SUM(ABS(x.difference_qty)), 0)::int AS total_discrepancy_qty,
                COALESCE(SUM(x.difference_value), 0)::numeric AS net_discrepancy_value,
                COALESCE(SUM(ABS(x.difference_value)), 0)::numeric AS total_discrepancy_value
            ')
            ->groupBy('x.session_id', 'x.store_id')
            ->orderByDesc('report_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $sessionIds = collect($rows->items())->pluck('session_id')->filter()->values()->all();
        $sessionDetails = collect();

        if ($sessionIds !== []) {
            $sessionDetails = DB::query()
                ->fromSub(clone $filtered, 'x')
                ->whereIn('x.session_id', $sessionIds)
                ->selectRaw('
                    x.session_id,
                    x.product_code,
                    x.product_name,
                    x.location_name,
                    x.system_stock,
                    x.physical_stock,
                    x.difference_qty,
                    x.unit_price,
                    x.difference_value,
                    x.difference_reason,
                    x.additional_note,
                    x.approval_status_label
                ')
                ->orderBy('x.product_name')
                ->get()
                ->groupBy('session_id');
        }

        $canDecide = $this->canDecide();
        $scannedSessionIds = $canDecide ? $this->scannedSessionIds($sessionIds) : [];
        $approvalContexts = $canDecide ? $this->approvalContexts($sessionIds) : [];

        $rows->through(function ($row) use ($sessionDetails, $canDecide, $scannedSessionIds, $approvalContexts) {
            $row->details = $sessionDetails->get($row->session_id, collect())->values()->all();

            $status = strtolower((string) $row->opname_status);
            $context = $approvalContexts[$row->session_id] ?? [
                'missing_items' => [],
                'condition_decisions' => [],
                'has_manual_exception' => false,
            ];
            $isIndependent = $canDecide
                && (string) $row->session_created_by !== (string) auth()->id()
                && ! in_array((string) $row->session_id, $scannedSessionIds, true);

            $row->approval_context = $context;
            $row->can_approve = $isIndependent
                && $row->approved_at === null
                && in_array($status, ['pending_approval', 'recount_required'], true)
                && ! $context['has_manual_exception'];
            $row->can_reject = $isIndependent
                && $row->approved_at === null
                && $status === 'pending_approval';
            $row->can_decide = $row->can_approve || $row->can_reject;

            return $row;
        });

        $summary = DB::query()
            ->fromSub(clone $filtered, 'x')
            ->selectRaw('
                COUNT(DISTINCT x.session_id)::int AS total_sessions,
                COUNT(*)::int AS total_items,
                COUNT(*) FILTER (WHERE x.difference_qty <> 0)::int AS discrepancy_items,
                COUNT(*) FILTER (WHERE x.difference_qty < 0)::int AS shortage_items,
                COUNT(*) FILTER (WHERE x.difference_qty > 0)::int AS surplus_items,
                COALESCE(SUM(ABS(x.difference_qty)), 0)::int AS total_discrepancy_qty,
                COALESCE(SUM(ABS(x.difference_value)), 0)::numeric AS total_discrepancy_value,
                COUNT(DISTINCT x.session_id) FILTER (WHERE x.approved_at IS NULL)::int AS unapproved_sessions,
                COALESCE(SUM(x.difference_value), 0)::numeric AS net_discrepancy_value
            ')
            ->first();

        $storeSummary = DB::query()
            ->fromSub(clone $filtered, 'x')
            ->selectRaw('
                x.store_id,
                x.store_name,
                COUNT(DISTINCT x.session_id)::int AS session_count,
                COUNT(*)::int AS item_count,
                COALESCE(SUM(ABS(x.difference_qty)), 0)::int AS discrepancy_qty,
                COALESCE(SUM(ABS(x.difference_value)), 0)::numeric AS discrepancy_value,
                COUNT(DISTINCT x.session_id) FILTER (WHERE x.approved_at IS NULL)::int AS unapproved_sessions,
                MAX(x.report_at) AS last_report_at,
                STRING_AGG(DISTINCT x.pic_name, \', \') AS pic_names
            ')
            ->groupBy('x.store_id', 'x.store_name')
            ->orderByDesc('discrepancy_value')
            ->get();

        $periodExpr = match ($filters['group_by']) {
            'monthly' => "DATE_TRUNC('month', x.report_at)",
            'weekly' => "DATE_TRUNC('week', x.report_at)",
            default => "DATE_TRUNC('day', x.report_at)",
        };

        $periodFormat = $filters['group_by'] === 'monthly' ? 'Mon YYYY' : 'DD Mon YYYY';

        $periodRows = DB::query()
            ->fromSub(clone $filtered, 'x')
            ->selectRaw("
                {$periodExpr} AS period_start,
                TO_CHAR({$periodExpr}, '{$periodFormat}') AS period_label,
                x.store_id,
                x.store_name,
                COUNT(DISTINCT x.session_id)::int AS session_count,
                COUNT(*)::int AS item_count,
                COALESCE(SUM(ABS(x.difference_qty)), 0)::int AS discrepancy_qty,
                COALESCE(SUM(CASE WHEN x.difference_qty < 0 THEN ABS(x.difference_qty) ELSE 0 END), 0)::int AS shortage_qty,
                COALESCE(SUM(CASE WHEN x.difference_qty > 0 THEN x.difference_qty ELSE 0 END), 0)::int AS surplus_qty,
                COALESCE(SUM(ABS(x.difference_value)), 0)::numeric AS discrepancy_value,
                COUNT(DISTINCT x.session_id) FILTER (WHERE x.approved_at IS NULL)::int AS unapproved_sessions,
                STRING_AGG(DISTINCT x.pic_name, ', ') AS pic_names,
                STRING_AGG(DISTINCT x.approver_name, ', ') AS approver_names,
                MAX(x.report_at) AS last_report_at
            ")
            ->groupByRaw("{$periodExpr}, x.store_id, x.store_name")
            ->orderByDesc('period_start')
            ->orderByDesc('discrepancy_value')
            ->limit(500)
            ->get();

        $reasonRows = DB::query()
            ->fromSub(clone $base, 'x')
            ->selectRaw('
                x.difference_reason,
                COUNT(*)::int AS item_count,
                COALESCE(SUM(x.difference_qty), 0)::int AS difference_qty,
                COALESCE(SUM(ABS(x.difference_qty)), 0)::int AS absolute_qty,
                COALESCE(SUM(x.difference_value), 0)::numeric AS difference_value
            ')
            ->where('x.difference_reason', '<>', '-')
            ->groupBy('x.difference_reason')
            ->orderByDesc('absolute_qty')
            ->get();

        $stores = DB::table('stores')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']);
        $statuses = DB::table('store_opname_sessions')
            ->selectRaw('DISTINCT status::text AS status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');
        $locations = DB::table('locations')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Stok Opname, Selisih & Penyesuaian',
            'description' => 'Rekap selisih stok opname gudang kecil/toko dan display: nilai selisih, PIC, status persetujuan, serta rekap harian, mingguan, dan bulanan. Keputusan approval dan penyesuaian stok dapat dilakukan dari Backoffice oleh pengguna berwenang atau dari aplikasi POS oleh Manager/SPV toko.',
            'data' => [
                'rows' => $rows,
                'opnameItems' => $itemRows,
                'storeSummary' => $storeSummary,
                'periodRows' => $periodRows,
                'reasonRows' => $reasonRows,
                'summary' => (array) $summary,
                'stores' => $stores,
                'locations' => $locations,
                'statuses' => $statuses,
                'filters' => $filters,
                'groupByOptions' => [
                    ['value' => 'daily', 'label' => 'Harian (tutup toko)'],
                    ['value' => 'weekly', 'label' => 'Mingguan (Manager Store)'],
                    ['value' => 'monthly', 'label' => 'Bulanan (HO)'],
                ],
            ],
            'filters' => $request->query(),
            'urls' => array_merge([
                'index' => url()->current(),
                'pos_stock_opname' => rtrim((string) config('backoffice.pos_app_url'), '/').'/storage/stock-opname',
            ], $canDecide ? [
                'approve' => route('monitoring.stock-opname-variance.approve', ['id' => '__ID__']),
                'reject' => route('monitoring.stock-opname-variance.reject', ['id' => '__ID__']),
            ] : []),
            'canDecide' => $canDecide,
        ]);
    }

    public function approve(Request $request, string $id)
    {
        $this->ensureCanDecide();

        $validated = $request->validate([
            'missing_item_ids' => ['present', 'array'],
            'missing_item_ids.*' => ['uuid', 'distinct'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'reviewed_at' => ['required', 'date'],
        ]);

        $userId = (string) auth()->id();
        $missingItemIds = collect($validated['missing_item_ids'])
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        DB::transaction(function () use ($id, $userId, $validated, $missingItemIds) {
            $session = DB::table('store_opname_sessions')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (! $session) {
                $this->decisionError('Sesi stok opname tidak ditemukan.');
            }

            $status = strtolower((string) $session->status);

            if (! in_array($status, ['pending_approval', 'recount_required'], true)) {
                $this->decisionError('Sesi tidak berada pada tahap keputusan approval.');
            }

            if ($session->updated_at
                && ! Carbon::parse((string) $session->updated_at)->equalTo(Carbon::parse((string) $validated['reviewed_at']))) {
                $this->decisionError('Review sudah kedaluwarsa. Muat ulang data sebelum approval.');
            }

            $this->ensureIndependentDecision($session, $userId);

            $results = DB::table('store_opname_reconciliation_results')
                ->where('opname_session_id', $id)
                ->get(['item_id', 'outcome']);

            if ($results->contains(fn ($result) => $result->outcome === 'manual_exception')) {
                $this->decisionError('Manual exception harus diselesaikan sebelum approval.');
            }

            $unresolvedIds = $results
                ->where('outcome', 'unresolved_discrepancy')
                ->pluck('item_id')
                ->map(fn ($value) => (string) $value)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($unresolvedIds !== $missingItemIds) {
                $this->decisionError('Setiap discrepancy harus diputuskan eksplisit sebagai missing.');
            }

            $now = now();

            if ($missingItemIds !== []) {
                $affected = DB::table('items')
                    ->whereIn('id', $missingItemIds)
                    ->where('status', '!=', 'missing')
                    ->whereExists(function ($query) use ($session) {
                        $query->selectRaw('1')
                            ->from('store_items')
                            ->whereColumn('store_items.item_id', 'items.id')
                            ->where('store_items.store_id', $session->store_id);
                    })
                    ->update([
                        'current_location_id' => null,
                        'status' => 'missing',
                        'updated_at' => $now,
                    ]);

                if ($affected !== count($missingItemIds)) {
                    $this->decisionError('Data item berubah saat approval; seluruh perubahan dibatalkan.');
                }
            }

            $this->applyScannedItemConditions((string) $session->id);

            DB::table('store_opname_approval_audits')->insert([
                'id' => (string) Str::uuid(),
                'opname_session_id' => $session->id,
                'counting_round' => (int) ($session->counting_round ?? 1),
                'actor_id' => $userId,
                'action' => 'approved',
                'reason' => trim((string) $validated['reason']),
                'reviewed_at' => Carbon::parse((string) $validated['reviewed_at'])->toDateTimeString(),
                'acted_at' => $now,
                'item_decisions' => json_encode(array_map(
                    fn (string $itemId): array => [
                        'item_id' => $itemId,
                        'decision' => 'missing',
                    ],
                    $missingItemIds
                ), JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('store_opname_sessions')->where('id', $session->id)->update([
                'status' => 'completed',
                'approved_by' => $userId,
                'approved_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return back()->with('success', 'Sesi stok opname disetujui dan penyesuaian stok telah diterapkan.');
    }

    public function reject(Request $request, string $id)
    {
        $this->ensureCanDecide();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $userId = (string) auth()->id();

        DB::transaction(function () use ($id, $userId, $validated) {
            $session = DB::table('store_opname_sessions')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (! $session) {
                $this->decisionError('Sesi stok opname tidak ditemukan.');
            }

            if (strtolower((string) $session->status) !== 'pending_approval') {
                $this->decisionError('Hanya sesi yang menunggu persetujuan yang dapat ditolak.');
            }

            $this->ensureIndependentDecision($session, $userId);

            $now = now();
            $reason = trim((string) $validated['reason']);

            DB::table('store_opname_sessions')->where('id', $session->id)->update([
                'status' => 'recount_required',
                'rejected_at' => $now,
                'rejected_by' => $userId,
                'rejection_reasons' => $reason,
                'recount_requested_at' => $now,
                'recount_requested_by' => $userId,
                'recount_reason' => $reason,
                'counting_round' => ((int) ($session->counting_round ?? 1)) + 1,
                'updated_at' => $now,
            ]);

            DB::table('store_opname_approval_audits')->insert([
                'id' => (string) Str::uuid(),
                'opname_session_id' => $session->id,
                'counting_round' => (int) ($session->counting_round ?? 1),
                'actor_id' => $userId,
                'action' => 'rejected',
                'reason' => $reason,
                'reviewed_at' => $now,
                'acted_at' => $now,
                'item_decisions' => json_encode([], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return back()->with('success', 'Sesi stok opname ditolak dan masuk antrean hitung ulang.');
    }

    private function ensureCanDecide(): void
    {
        abort_unless($this->canDecide(), 403);
    }

    private function decisionError(string $message): never
    {
        throw ValidationException::withMessages(['decision' => $message]);
    }

    private function ensureIndependentDecision(object $session, string $userId): void
    {
        if ((string) $session->created_by === $userId) {
            $this->decisionError('Pembuat sesi tidak boleh mengambil keputusan atas sesi sendiri.');
        }

        $hasScanned = DB::table('store_opname_session_scans')
            ->where('opname_session_id', $session->id)
            ->where('scanned_by', $userId)
            ->exists();

        if ($hasScanned) {
            $this->decisionError('Petugas yang pernah memindai tidak boleh menjadi approver.');
        }
    }

    private function scannedSessionIds(array $sessionIds): array
    {
        $userId = auth()->id();

        if (! $userId || $sessionIds === []) {
            return [];
        }

        return DB::table('store_opname_session_scans')
            ->whereIn('opname_session_id', $sessionIds)
            ->where('scanned_by', $userId)
            ->distinct()
            ->pluck('opname_session_id')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    private function approvalContexts(array $sessionIds): array
    {
        if ($sessionIds === []) {
            return [];
        }

        $missingBySession = DB::table('store_opname_reconciliation_results as result')
            ->leftJoin('store_opname_session_expected_items as expected', function ($join) {
                $join->on('expected.opname_session_id', '=', 'result.opname_session_id')
                    ->on('expected.item_id', '=', 'result.item_id');
            })
            ->leftJoin('items as item', 'item.id', '=', 'result.item_id')
            ->leftJoin('products as product', 'product.id', '=', 'item.product_id')
            ->whereIn('result.opname_session_id', $sessionIds)
            ->where('result.outcome', 'unresolved_discrepancy')
            ->orderBy('product.name')
            ->orderBy('expected.rfid_tag_value')
            ->get([
                'result.opname_session_id',
                'result.item_id',
                'expected.rfid_tag_value',
                'product.name as product_name',
            ])
            ->groupBy('opname_session_id');

        $manualExceptionSessions = DB::table('store_opname_reconciliation_results')
            ->whereIn('opname_session_id', $sessionIds)
            ->where('outcome', 'manual_exception')
            ->distinct()
            ->pluck('opname_session_id')
            ->map(fn ($value) => (string) $value)
            ->all();

        $conditionsBySession = DB::table('store_opname_session_scans as scan')
            ->join('item_conditions as condition', 'condition.id', '=', 'scan.item_condition_id')
            ->whereIn('scan.opname_session_id', $sessionIds)
            ->whereNotNull('scan.item_condition_id')
            ->groupBy('scan.opname_session_id', 'condition.id', 'condition.name')
            ->orderBy('condition.name')
            ->get([
                'scan.opname_session_id',
                'condition.name',
                DB::raw('COUNT(*)::int AS count'),
            ])
            ->groupBy('opname_session_id');

        $contexts = [];

        foreach ($sessionIds as $sessionId) {
            $missingItems = $missingBySession->get($sessionId, collect())
                ->map(fn ($row) => [
                    'id' => (string) $row->item_id,
                    'identifier' => (string) ($row->rfid_tag_value ?: $row->item_id),
                    'product_name' => $row->product_name ?: 'Produk tidak diketahui',
                ])
                ->values()
                ->all();

            $conditionDecisions = $conditionsBySession->get($sessionId, collect())
                ->map(fn ($row) => [
                    'name' => (string) $row->name,
                    'count' => (int) $row->count,
                ])
                ->values()
                ->all();

            $contexts[$sessionId] = [
                'missing_items' => $missingItems,
                'condition_decisions' => $conditionDecisions,
                'has_manual_exception' => in_array((string) $sessionId, $manualExceptionSessions, true),
            ];
        }

        return $contexts;
    }

    private function applyScannedItemConditions(string $sessionId): void
    {
        $latestScans = DB::table('store_opname_session_scans as scan')
            ->selectRaw(
                'DISTINCT ON (scan.item_id) '
                .'scan.item_id, scan.item_condition_id, '
                .'scan.id AS reference_id, scan.scanned_by'
            )
            ->where('scan.opname_session_id', $sessionId)
            ->whereNotNull('item_id')
            ->whereNotNull('item_condition_id')
            ->orderBy('scan.item_id')
            ->orderByDesc('scan.scanned_at')
            ->orderByDesc('scan.id');

        $changes = DB::query()
            ->fromSub($latestScans, 'latest')
            ->join('items as item', 'item.id', '=', 'latest.item_id')
            ->where(function ($query) {
                $query->whereNull('item.current_condition_id')
                    ->orWhereColumn('item.current_condition_id', '!=', 'latest.item_condition_id');
            })
            ->get([
                'latest.item_id',
                'latest.item_condition_id',
                'latest.reference_id',
                'latest.scanned_by',
            ]);

        if ($changes->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($changes->chunk(500) as $chunk) {
            $bindings = [];
            $values = $chunk->map(function ($change) use (&$bindings): string {
                $bindings[] = $change->item_id;
                $bindings[] = $change->item_condition_id;

                return '(?::uuid, ?::uuid)';
            })->implode(', ');

            DB::update(
                'UPDATE items AS item '
                .'SET current_condition_id = changed.item_condition_id, '
                .'updated_at = CURRENT_TIMESTAMP '
                ."FROM (VALUES {$values}) "
                .'AS changed(item_id, item_condition_id) '
                .'WHERE item.id = changed.item_id',
                $bindings
            );

            $historyPayload = $chunk->map(
                fn ($change): array => [
                    'id' => (string) Str::uuid(),
                    'item_id' => $change->item_id,
                    'item_condition_id' => $change->item_condition_id,
                    'reference_type' => 'store_stock_opname',
                    'reference_id' => $change->reference_id,
                    'changed_at' => $now,
                    'changed_by' => $change->scanned_by,
                    'created_at' => $now,
                ]
            )->all();

            if ($historyPayload !== []) {
                DB::table('item_condition_histories')->insert($historyPayload);
            }
        }
    }

    private function canDecide(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->can('approve store-opname-variance') || $user->can('manage store-opname')) {
            return true;
        }

        $managementRoles = ['superadmin', 'administrator', 'admin', 'management', 'manager'];

        return $user->getRoleNames()
            ->map(fn ($role) => strtolower(preg_replace('/[^a-z0-9]/', '', $role)))
            ->contains(fn ($role) => in_array($role, $managementRoles, true));
    }

    private function baseQuery(Carbon $start, Carbon $end)
    {
        $rankedPrices = DB::table('product_prices')
            ->whereRaw("LOWER(price_type::text) = 'base'")
            ->select(['product_id', 'price'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY valid_from DESC NULLS LAST, created_at DESC) AS row_rank');

        $latestPrices = DB::query()
            ->fromSub($rankedPrices, 'ranked_prices')
            ->where('row_rank', 1);

        return DB::table('store_opname_session_items as osi')
            ->join('store_opname_sessions as os', 'os.id', '=', 'osi.opname_session_id')
            ->leftJoin('stores as s', 's.id', '=', 'os.store_id')
            ->leftJoin('locations as l', 'l.id', '=', 'osi.location_id')
            ->leftJoin('products as p', 'p.id', '=', 'osi.product_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'os.created_by')
            ->leftJoin('users as approver', 'approver.id', '=', 'os.approved_by')
            ->leftJoinSub($latestPrices, 'price', fn ($join) => $join->on('price.product_id', '=', 'osi.product_id'))
            ->whereBetween(DB::raw('COALESCE(os.finished_at, os.started_at, os.scheduled_at, os.created_at)'), [$start, $end])
            ->selectRaw("
                os.id AS session_id,
                os.name AS opname_code,
                os.type AS opname_type,
                os.status::text AS opname_status,
                os.counting_round,
                os.scheduled_at,
                os.started_at,
                os.finished_at,
                os.approved_at,
                os.created_at AS session_created_at,
                os.created_by::text AS session_created_by,
                os.updated_at AS session_updated_at,
                os.store_id,
                COALESCE(s.name, '-') AS store_name,
                COALESCE(s.code, '-') AS store_code,
                osi.location_id,
                COALESCE(l.name, '-') AS location_name,
                COALESCE(l.type::text, '-') AS location_type,
                osi.product_id,
                COALESCE(p.code, '-') AS product_code,
                COALESCE(p.name, '-') AS product_name,
                COALESCE(osi.system_stock, 0)::int AS system_stock,
                COALESCE(osi.physical_stock, 0)::int AS physical_stock,
                (COALESCE(osi.physical_stock, 0) - COALESCE(osi.system_stock, 0))::int AS difference_qty,
                COALESCE(price.price, 0)::numeric AS unit_price,
                ((COALESCE(osi.physical_stock, 0) - COALESCE(osi.system_stock, 0)) * COALESCE(price.price, 0))::numeric AS difference_value,
                COALESCE(osi.discrepancy_reason, '-') AS difference_reason,
                COALESCE(osi.discrepancy_notes, '-') AS additional_note,
                COALESCE(creator.name, '-') AS pic_name,
                COALESCE(approver.name, '-') AS approver_name,
                CASE
                    WHEN os.approved_at IS NOT NULL THEN 'Disetujui'
                    WHEN LOWER(COALESCE(os.status::text, '')) IN ('pending_approval','pending') THEN 'Menunggu persetujuan'
                    WHEN LOWER(COALESCE(os.status::text, '')) IN ('rejected') THEN 'Ditolak'
                    WHEN LOWER(COALESCE(os.status::text, '')) IN ('in_progress','counting') THEN 'Berjalan'
                    ELSE COALESCE(os.status::text, '-')
                END AS approval_status_label,
                COALESCE(os.finished_at, os.started_at, os.scheduled_at, os.created_at) AS report_at
            ");
    }

    private function filters(Request $request): array
    {
        $from = $this->date($request->query('start_date')) ?: now()->startOfMonth()->toDateString();
        $to = $this->date($request->query('end_date')) ?: now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $groupBy = $request->query('group_by');
        $groupBy = in_array($groupBy, ['daily', 'weekly', 'monthly'], true) ? $groupBy : 'daily';

        return [
            'q' => trim((string) $request->query('q', '')),
            'store_id' => trim((string) $request->query('store_id', '')),
            'location_id' => trim((string) $request->query('location_id', '')),
            'opname_status' => trim((string) $request->query('opname_status', '')),
            'group_by' => $groupBy,
            'start_date' => $from,
            'end_date' => $to,
            'per_page' => $this->perPage($request->query('per_page')),
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function perPage(mixed $value): int
    {
        $perPage = (int) ($value ?: 25);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
    }
}
