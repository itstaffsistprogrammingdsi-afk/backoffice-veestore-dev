<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EcommerceRejectionHandoffService
{
    public const STATUS_PENDING = 'pending_backoffice_review';

    public const STATUS_SETTLED = 'store_rejected';

    public const RESOLUTION_NOTE_PREFIX = '[Backoffice] ';

    public function __construct(
        private readonly StockReservationService $reservations
    ) {}

    private const REASON_LABELS = [
        'inventory_shortage' => 'Stok tidak mencukupi',
        'item_issue' => 'Kondisi item bermasalah',
        'operational_exception' => 'Kendala operasional',
    ];

    private const MODE_LABELS = [
        'strict' => 'Strict',
        'flexible' => 'Flexible',
    ];

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $reason = trim((string) ($filters['reason'] ?? ''));
        $orderId = trim((string) ($filters['order_id'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 15);

        if (! in_array($perPage, [15, 25, 50], true)) {
            $perPage = 15;
        }

        $matchingOrderIds = $this->matchingOrderIds($q, $orderId);

        if ($matchingOrderIds === []) {
            return new Paginator(
                collect(),
                0,
                $perPage,
                max(1, (int) ($filters['page'] ?? 1)),
                ['path' => $filters['path'] ?? request()->url()]
            );
        }

        $cases = DB::connection('pos')
            ->table('ecommerce_fulfillment_rejection_cases as case_row')
            ->where('case_row.status', self::STATUS_PENDING)
            ->when($reason !== '', fn ($query) => $query->where('case_row.reason', $reason))
            ->when($matchingOrderIds !== null, fn ($query) => $query->whereIn('case_row.ecommerce_order_id', $matchingOrderIds))
            ->orderByDesc('case_row.requested_at')
            ->paginate($perPage)
            ->withQueryString();

        $rows = collect($cases->items());
        $orderIds = $rows->pluck('ecommerce_order_id')->unique()->filter()->values()->all();
        $orders = $this->ordersById($orderIds);
        $evidence = $this->evidenceByCase($rows->pluck('id')->all());
        $actors = $this->actorNames(
            $rows->pluck('requested_by_user_id')
                ->merge($rows->pluck('decided_by_user_id'))
                ->filter()
                ->unique()
                ->values()
                ->all()
        );

        $cases->setCollection($rows->map(fn ($case) => $this->decorateCase(
            $case,
            $orders->get($case->ecommerce_order_id),
            $evidence->get($case->id, collect()),
            $actors
        )));

        return $cases;
    }

    public function summary(): array
    {
        $pending = DB::connection('pos')
            ->table('ecommerce_fulfillment_rejection_cases')
            ->where('status', self::STATUS_PENDING);

        $oldest = (clone $pending)->min('requested_at');
        $count = (clone $pending)->count();
        $orderCount = (clone $pending)
            ->distinct()
            ->count('ecommerce_order_id');

        return [
            'pending_cases' => $count,
            'pending_orders' => $orderCount,
            'oldest_requested_at' => $oldest,
            'oldest_age_hours' => $oldest
                ? (int) CarbonImmutable::parse($oldest)->diffInHours(now())
                : null,
        ];
    }

    public function summaryByOrder(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        return DB::connection('pos')
            ->table('ecommerce_fulfillment_rejection_cases')
            ->whereIn('ecommerce_order_id', $orderIds)
            ->where('status', self::STATUS_PENDING)
            ->get(['id', 'ecommerce_order_id', 'reason', 'mode', 'requested_at'])
            ->mapWithKeys(fn ($case) => [
                (string) $case->ecommerce_order_id => [
                    'case_id' => (string) $case->id,
                    'reason' => (string) $case->reason,
                    'reason_label' => $this->reasonLabel((string) $case->reason),
                    'mode' => (string) $case->mode,
                    'requested_at' => $case->requested_at,
                    'age_hours' => $case->requested_at
                        ? (int) CarbonImmutable::parse($case->requested_at)->diffInHours(now())
                        : null,
                ],
            ])
            ->all();
    }

    public function resolve(
        string $caseId,
        string $decision,
        ?string $targetId,
        ?string $note,
        string $actorId
    ): array {
        if (! in_array($decision, ['reassign_store', 'reassign_warehouse', 'cancel'], true)) {
            throw ValidationException::withMessages([
                'decision' => 'Keputusan kendala tidak valid.',
            ]);
        }

        return DB::connection('pos')->transaction(function () use (
            $caseId,
            $decision,
            $targetId,
            $note,
            $actorId
        ): array {
            $case = DB::connection('pos')
                ->table('ecommerce_fulfillment_rejection_cases')
                ->where('id', $caseId)
                ->lockForUpdate()
                ->first();

            if (! $case) {
                throw ValidationException::withMessages([
                    'case' => 'Kendala e-commerce tidak ditemukan.',
                ]);
            }

            if ((string) $case->status !== self::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'case' => 'Kendala ini sudah diproses atau bukan kewenangan Backoffice.',
                ]);
            }

            $targetName = null;

            DB::connection('pgsql')->transaction(function () use (
                $case,
                $decision,
                $targetId,
                &$targetName
            ): void {
                $order = DB::connection('pgsql')
                    ->table('ecommerce_orders')
                    ->where('id', $case->ecommerce_order_id)
                    ->lockForUpdate()
                    ->first();

                if (! $order) {
                    throw ValidationException::withMessages([
                        'order' => 'Order terkait kendala tidak ditemukan.',
                    ]);
                }

                $status = strtolower((string) $order->status);

                if ($status === 'received' || ($status === 'cancelled' && $decision !== 'cancel')) {
                    throw ValidationException::withMessages([
                        'order' => 'Order sudah selesai atau dibatalkan, kendala tidak dapat diproses dari halaman ini.',
                    ]);
                }

                if ($decision === 'cancel') {
                    if ($status !== 'cancelled') {
                        DB::connection('pgsql')
                            ->table('ecommerce_orders')
                            ->where('id', $order->id)
                            ->update([
                                'status' => 'cancelled',
                            ]);

                        $this->reservations->releaseEcommerceOrder(
                            (string) $order->id,
                            'rejection_resolved_cancel'
                        );
                    }

                    $targetName = 'Order dibatalkan';

                    return;
                }

                $column = $decision === 'reassign_store'
                    ? 'handled_by_store_id'
                    : 'handled_by_warehouse_id';
                $table = $decision === 'reassign_store' ? 'stores' : 'warehouses';

                $target = $targetId
                    ? DB::connection('pgsql')
                        ->table($table)
                        ->where('id', $targetId)
                        ->whereNull('deleted_at')
                        ->first(['id', 'name'])
                    : null;

                if (! $target) {
                    throw ValidationException::withMessages([
                        'target_id' => 'Lokasi tujuan tidak ditemukan atau sudah diarsipkan.',
                    ]);
                }

                DB::connection('pgsql')
                    ->table('ecommerce_orders')
                    ->where('id', $order->id)
                    ->update([
                        $column => $target->id,
                        $decision === 'reassign_store'
                            ? 'handled_by_warehouse_id'
                            : 'handled_by_store_id' => null,
                    ]);

                DB::connection('pgsql')
                    ->table('stock_reservations')
                    ->where('reference_type', 'ecommerce_order')
                    ->where('reference_id', $order->id)
                    ->where('status', 'active')
                    ->update([
                        'handler_type' => $decision === 'reassign_store' ? 'store' : 'warehouse',
                        'handler_id' => $target->id,
                        'updated_at' => now(),
                    ]);

                $targetName = (string) $target->name;
            });

            $decisionLabel = match ($decision) {
                'reassign_store' => 'Dipindahkan ke toko',
                'reassign_warehouse' => 'Dipindahkan ke gudang',
                default => 'Order dibatalkan',
            };

            $resolvedNote = self::RESOLUTION_NOTE_PREFIX.trim(implode(' ', array_filter([
                $decisionLabel.($targetName ? ': '.$targetName.'.' : '.'),
                $note ? trim($note) : null,
            ])));

            $now = now();

            $affected = DB::connection('pos')
                ->table('ecommerce_fulfillment_rejection_cases')
                ->where('id', $caseId)
                ->where('status', self::STATUS_PENDING)
                ->update([
                    'status' => self::STATUS_SETTLED,
                    'decided_by_user_id' => $actorId,
                    'decided_at' => $now,
                    'decision_note' => $resolvedNote,
                    'updated_at' => $now,
                ]);

            if ($affected !== 1) {
                throw ValidationException::withMessages([
                    'case' => 'Kendala sudah diproses oleh pengguna lain. Muat ulang halaman.',
                ]);
            }

            DB::connection('pos')
                ->table('ecommerce_rejection_outbox')
                ->where('aggregate_id', $caseId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'published',
                    'published_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($decision === 'cancel') {
                $this->releaseLocalReservations(
                    (string) $case->ecommerce_order_id,
                    'rejection_resolved_cancel',
                    $now
                );
            }

            return [
                'case_id' => $caseId,
                'order_id' => (string) $case->ecommerce_order_id,
                'decision' => $decision,
                'target_name' => $targetName,
            ];
        });
    }

    public function resolveForOrderUpdate(
        string $orderId,
        string $actorId,
        string $note,
        bool $cancelled = false
    ): bool {
        if ($cancelled) {
            $this->reservations->releaseEcommerceOrder(
                $orderId,
                'cancelled_from_backoffice'
            );
        }

        return DB::connection('pos')->transaction(function () use (
            $orderId,
            $actorId,
            $note,
            $cancelled
        ): bool {
            $now = now();

            if ($cancelled) {
                $this->releaseLocalReservations(
                    $orderId,
                    'cancelled_from_backoffice',
                    $now
                );
            }

            $case = DB::connection('pos')
                ->table('ecommerce_fulfillment_rejection_cases')
                ->where('ecommerce_order_id', $orderId)
                ->where('status', self::STATUS_PENDING)
                ->lockForUpdate()
                ->first(['id']);

            if (! $case) {
                return false;
            }

            $affected = DB::connection('pos')
                ->table('ecommerce_fulfillment_rejection_cases')
                ->where('id', $case->id)
                ->where('status', self::STATUS_PENDING)
                ->update([
                    'status' => self::STATUS_SETTLED,
                    'decided_by_user_id' => $actorId,
                    'decided_at' => $now,
                    'decision_note' => self::RESOLUTION_NOTE_PREFIX.$note,
                    'updated_at' => $now,
                ]);

            if ($affected !== 1) {
                return false;
            }

            DB::connection('pos')
                ->table('ecommerce_rejection_outbox')
                ->where('aggregate_id', $case->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'published',
                    'published_at' => $now,
                    'updated_at' => $now,
                ]);

            return true;
        });
    }

    private function releaseLocalReservations(
        string $orderId,
        string $reason,
        \DateTimeInterface $releasedAt
    ): void {
        DB::connection('pos')
            ->table('ecommerce_inventory_reservations')
            ->where('ecommerce_order_id', $orderId)
            ->where('status', 'active')
            ->update([
                'status' => 'released',
                'released_at' => $releasedAt,
                'release_reason' => $reason,
                'updated_at' => $releasedAt,
            ]);
    }

    private function matchingOrderIds(string $q, string $orderId): ?array
    {
        if ($q === '' && $orderId === '') {
            return null;
        }

        if ($orderId !== '' && ! Str::isUuid($orderId)) {
            return [];
        }

        $query = DB::connection('pgsql')
            ->table('ecommerce_orders')
            ->select('id');

        if ($orderId !== '') {
            $query->where('id', $orderId);
        }

        if ($q !== '') {
            $like = '%'.$q.'%';

            $query->where(function ($subQuery) use ($like): void {
                $subQuery->where('order_number', 'ilike', $like)
                    ->orWhereRaw('CAST(id AS TEXT) ILIKE ?', [$like]);
            });
        }

        return $query->limit(200)->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    private function ordersById(array $orderIds): Collection
    {
        if ($orderIds === []) {
            return collect();
        }

        return DB::connection('pgsql')
            ->table('ecommerce_orders as eo')
            ->leftJoin('customers as customer', 'customer.id', '=', 'eo.customer_id')
            ->leftJoin('stores as store', 'store.id', '=', 'eo.handled_by_store_id')
            ->leftJoin('warehouses as warehouse', 'warehouse.id', '=', 'eo.handled_by_warehouse_id')
            ->whereIn('eo.id', $orderIds)
            ->get([
                'eo.id',
                'eo.order_number',
                'eo.status',
                'eo.grand_total',
                'eo.created_at',
                'eo.payment_method_name',
                'customer.name as customer_name',
                'store.name as store_name',
                'warehouse.name as warehouse_name',
            ])
            ->keyBy('id');
    }

    private function evidenceByCase(array $caseIds): Collection
    {
        if ($caseIds === []) {
            return collect();
        }

        return DB::connection('pos')
            ->table('ecommerce_fulfillment_rejection_evidence')
            ->whereIn('rejection_case_id', $caseIds)
            ->orderBy('captured_at')
            ->get(['rejection_case_id', 'type', 'payload', 'captured_at'])
            ->map(function ($row) {
                $row->payload = $this->decodePayload($row->payload);

                return $row;
            })
            ->groupBy('rejection_case_id');
    }

    private function actorNames(array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return DB::connection('pgsql')
            ->table('users')
            ->whereIn('id', $userIds)
            ->pluck('name', 'id');
    }

    private function decorateCase(
        object $case,
        ?object $order,
        Collection $evidence,
        Collection $actors
    ): object {
        $case->reason_label = $this->reasonLabel((string) $case->reason);
        $case->mode_label = self::MODE_LABELS[(string) $case->mode] ?? ucfirst((string) $case->mode);
        $case->requested_by_name = $actors->get($case->requested_by_user_id);
        $case->decided_by_name = $actors->get($case->decided_by_user_id);
        $case->age_hours = $case->requested_at
            ? (int) CarbonImmutable::parse($case->requested_at)->diffInHours(now())
            : null;
        $case->order = $order ? [
            'id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => (string) $order->status,
            'grand_total' => (float) $order->grand_total,
            'created_at' => $order->created_at,
            'payment_method_name' => $order->payment_method_name,
            'customer_name' => $order->customer_name,
            'store_name' => $order->store_name,
            'warehouse_name' => $order->warehouse_name,
        ] : null;
        $case->evidence = $evidence->map(fn ($row) => [
            'type' => (string) $row->type,
            'type_label' => $this->reasonLabel((string) $row->type),
            'payload' => $row->payload,
            'captured_at' => $row->captured_at,
        ])->values()->all();
        $case->evidence_count = $evidence->count();

        return $case;
    }

    private function decodePayload(mixed $payload): mixed
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $payload;
        }

        return $payload;
    }

    private function reasonLabel(string $reason): string
    {
        return self::REASON_LABELS[$reason] ?? Str::headline(str_replace('_', ' ', $reason));
    }
}
