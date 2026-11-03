<?php

namespace App\Services;

use App\Mail\StockBarrierAlertDigest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class StockBarrierAlertMonitorService
{
    private const LOCK_KEY_FIRST = 20261104;

    private const LOCK_KEY_SECOND = 1104;

    private const EMAIL_SAMPLE_LIMIT = 200;

    public function run(): array
    {
        $backoffice = DB::connection('immostore_backoffice');
        $lock = $backoffice->selectOne(
            'SELECT pg_try_advisory_lock(?, ?) AS acquired',
            [self::LOCK_KEY_FIRST, self::LOCK_KEY_SECOND]
        );

        if (! filter_var($lock->acquired ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return ['locked' => false, 'active_count' => 0, 'new_count' => 0, 'email_failures' => 0];
        }

        try {
            $result = $this->scanAndPersist();
            $result['email_failures'] = $this->sendPendingEmails();

            return $result;
        } finally {
            $backoffice->selectOne(
                'SELECT pg_advisory_unlock(?, ?)',
                [self::LOCK_KEY_FIRST, self::LOCK_KEY_SECOND]
            );
        }
    }

    private function scanAndPersist(): array
    {
        $backoffice = DB::connection('immostore_backoffice');
        $scanId = (string) Str::uuid();
        $now = now();
        $newCounts = [];

        $backoffice->beginTransaction();

        try {
            $backoffice->statement(<<<'SQL'
                CREATE TEMPORARY TABLE stock_barrier_alert_scan (
                    id uuid NOT NULL,
                    scope_type varchar(20) NOT NULL,
                    location_id uuid NOT NULL,
                    location_name text NOT NULL,
                    location_code text NULL,
                    product_id uuid NOT NULL,
                    product_code text NULL,
                    product_name text NOT NULL,
                    unit_name text NOT NULL,
                    on_hand integer NOT NULL,
                    reserved integer NOT NULL,
                    available integer NOT NULL,
                    stock_barrier integer NOT NULL,
                    threshold integer NOT NULL,
                    PRIMARY KEY (scope_type, location_id, product_id)
                ) ON COMMIT DROP
                SQL);

            foreach (['store', 'warehouse'] as $scope) {
                $batch = [];

                foreach ($this->lowStockRows($scope) as $row) {
                    $batch[] = [
                        'id' => (string) Str::uuid(),
                        'scope_type' => $scope,
                        'location_id' => (string) $row->location_id,
                        'location_name' => (string) $row->location_name,
                        'location_code' => $row->location_code !== null ? (string) $row->location_code : null,
                        'product_id' => (string) $row->product_id,
                        'product_code' => $row->product_code !== null ? (string) $row->product_code : null,
                        'product_name' => (string) $row->product_name,
                        'unit_name' => (string) ($row->unit_name ?? ''),
                        'on_hand' => (int) $row->on_hand,
                        'reserved' => (int) $row->reserved,
                        'available' => (int) $row->available,
                        'stock_barrier' => (int) $row->stock_barrier,
                        'threshold' => (int) $row->threshold,
                    ];

                    if (count($batch) === 500) {
                        $backoffice->table('stock_barrier_alert_scan')->insert($batch);
                        $batch = [];
                    }
                }

                if ($batch !== []) {
                    $backoffice->table('stock_barrier_alert_scan')->insert($batch);
                }
            }

            $newCounts = $backoffice->table('stock_barrier_alert_scan as scan')
                ->leftJoin('stock_barrier_alert_states as state', function ($join) {
                    $join->on('state.scope_type', '=', 'scan.scope_type')
                        ->on('state.location_id', '=', 'scan.location_id')
                        ->on('state.product_id', '=', 'scan.product_id')
                        ->where('state.is_active', true);
                })
                ->whereNull('state.id')
                ->select('scan.scope_type')
                ->selectRaw('COUNT(*)::int AS alert_count')
                ->groupBy('scan.scope_type')
                ->pluck('alert_count', 'scope_type')
                ->map(fn ($count) => (int) $count)
                ->all();

            $newItems = $this->newTransitionItems($backoffice);

            $backoffice->statement(<<<'SQL'
                INSERT INTO stock_barrier_alert_states (
                    id, scope_type, location_id, location_name, location_code,
                    product_id, product_code, product_name, unit_name,
                    on_hand, reserved, available, stock_barrier, threshold,
                    is_active, activated_at, last_seen_at, resolved_at, scan_id,
                    created_at, updated_at
                )
                SELECT
                    scan.id, scan.scope_type, scan.location_id, scan.location_name, scan.location_code,
                    scan.product_id, scan.product_code, scan.product_name, scan.unit_name,
                    scan.on_hand, scan.reserved, scan.available, scan.stock_barrier, scan.threshold,
                    TRUE, NOW(), NOW(), NULL, ?, NOW(), NOW()
                FROM stock_barrier_alert_scan AS scan
                ON CONFLICT (scope_type, location_id, product_id) DO UPDATE SET
                    location_name = EXCLUDED.location_name,
                    location_code = EXCLUDED.location_code,
                    product_code = EXCLUDED.product_code,
                    product_name = EXCLUDED.product_name,
                    unit_name = EXCLUDED.unit_name,
                    on_hand = EXCLUDED.on_hand,
                    reserved = EXCLUDED.reserved,
                    available = EXCLUDED.available,
                    stock_barrier = EXCLUDED.stock_barrier,
                    threshold = EXCLUDED.threshold,
                    is_active = TRUE,
                    activated_at = CASE
                        WHEN stock_barrier_alert_states.is_active THEN stock_barrier_alert_states.activated_at
                        ELSE EXCLUDED.activated_at
                    END,
                    last_seen_at = EXCLUDED.last_seen_at,
                    resolved_at = NULL,
                    scan_id = EXCLUDED.scan_id,
                    updated_at = EXCLUDED.updated_at
                SQL, [$scanId]);

            $backoffice->table('stock_barrier_alert_states')
                ->where('is_active', true)
                ->where('scan_id', '<>', $scanId)
                ->update([
                    'is_active' => false,
                    'resolved_at' => $now,
                    'updated_at' => $now,
                ]);

            $this->queueDigest($backoffice, $newCounts, $newItems, $now);

            $activeCount = (int) $backoffice->table('stock_barrier_alert_states')
                ->where('is_active', true)
                ->count();

            $backoffice->commit();

            return [
                'locked' => true,
                'active_count' => $activeCount,
                'new_count' => array_sum($newCounts),
                'email_failures' => 0,
            ];
        } catch (Throwable $exception) {
            $backoffice->rollBack();

            throw $exception;
        }
    }

    private function lowStockRows(string $scope): iterable
    {
        $locationTable = $scope === 'store' ? 'stores' : 'warehouses';
        $mappingTable = $scope === 'store' ? 'store_items' : 'warehouse_items';
        $locationKey = $scope === 'store' ? 'store_id' : 'warehouse_id';
        $barrierTable = $scope === 'store' ? 'store_stock_barriers' : 'warehouse_stock_barriers';
        $stockStatuses = $scope === 'store' ? ['store_stock', 'store_display'] : ['warehouse_stock'];

        if (! Schema::hasTable($barrierTable) || ! Schema::hasTable($mappingTable)) {
            return [];
        }

        $stock = DB::table('items as item')
            ->join($mappingTable.' as mapping', 'mapping.item_id', '=', 'item.id')
            ->join($barrierTable.' as configured_barrier', function ($join) use ($locationKey) {
                $join->on('configured_barrier.'.$locationKey, '=', 'mapping.'.$locationKey)
                    ->on('configured_barrier.product_id', '=', 'item.product_id');
            })
            ->whereIn(DB::raw('LOWER(item.status::text)'), $stockStatuses)
            ->select('mapping.'.$locationKey.' as location_id', 'item.product_id')
            ->selectRaw('COUNT(item.id)::int AS on_hand')
            ->groupBy('mapping.'.$locationKey, 'item.product_id');

        $reservations = app(StockReservationService::class)->remainingByLocation($scope);
        if (Schema::hasTable('stock_reservations')) {
            $reservations->whereExists(function ($query) use ($barrierTable, $locationKey) {
                $query->selectRaw('1')
                    ->from($barrierTable.' as configured_barrier')
                    ->whereColumn('configured_barrier.'.$locationKey, 'sr.handler_id')
                    ->whereColumn('configured_barrier.product_id', 'sr.product_id');
            });
        }

        $hasUnits = Schema::hasTable('units') && Schema::hasColumn('products', 'unit_id');

        return DB::table($barrierTable.' as barrier')
            ->join('products as product', 'product.id', '=', 'barrier.product_id')
            ->join($locationTable.' as location', 'location.id', '=', 'barrier.'.$locationKey)
            ->leftJoinSub($stock, 'stock', function ($join) use ($locationKey) {
                $join->on('stock.location_id', '=', 'barrier.'.$locationKey)
                    ->on('stock.product_id', '=', 'barrier.product_id');
            })
            ->leftJoinSub($reservations, 'reservation', function ($join) use ($locationKey) {
                $join->on('reservation.location_id', '=', 'barrier.'.$locationKey)
                    ->on('reservation.product_id', '=', 'barrier.product_id');
            })
            ->when($hasUnits, fn ($query) => $query->leftJoin('units as unit', 'unit.id', '=', 'product.unit_id'))
            ->whereNull('location.deleted_at')
            ->whereRaw('GREATEST(COALESCE(stock.on_hand, 0) - COALESCE(reservation.qty_reserved, 0), 0) <= CEIL(barrier.stock_barrier * (1 + COALESCE(barrier.tolerance_percentage, 0) / 100.0))')
            ->selectRaw('
                barrier.'.$locationKey.' AS location_id,
                location.name AS location_name,
                location.code AS location_code,
                product.id AS product_id,
                product.code AS product_code,
                product.name AS product_name,
                COALESCE(stock.on_hand, 0)::int AS on_hand,
                COALESCE(reservation.qty_reserved, 0)::int AS reserved,
                GREATEST(COALESCE(stock.on_hand, 0) - COALESCE(reservation.qty_reserved, 0), 0)::int AS available,
                barrier.stock_barrier::int AS stock_barrier,
                CEIL(barrier.stock_barrier * (1 + COALESCE(barrier.tolerance_percentage, 0) / 100.0))::int AS threshold
            ')
            ->when(
                $hasUnits,
                fn ($query) => $query->selectRaw("COALESCE(unit.name, '') AS unit_name"),
                fn ($query) => $query->selectRaw("'' AS unit_name")
            )
            ->orderBy('barrier.'.$locationKey)
            ->orderBy('barrier.product_id')
            ->cursor();
    }

    private function newTransitionItems($backoffice): array
    {
        $items = [];

        foreach (['store', 'warehouse'] as $scope) {
            $items[$scope] = $backoffice->table('stock_barrier_alert_scan as scan')
                ->leftJoin('stock_barrier_alert_states as state', function ($join) {
                    $join->on('state.scope_type', '=', 'scan.scope_type')
                        ->on('state.location_id', '=', 'scan.location_id')
                        ->on('state.product_id', '=', 'scan.product_id')
                        ->where('state.is_active', true);
                })
                ->whereNull('state.id')
                ->where('scan.scope_type', $scope)
                ->orderBy('scan.location_name')
                ->orderBy('scan.product_name')
                ->limit(self::EMAIL_SAMPLE_LIMIT)
                ->get([
                    'scan.scope_type',
                    'scan.location_name',
                    'scan.location_code',
                    'scan.product_code',
                    'scan.product_name',
                    'scan.unit_name',
                    'scan.available',
                    'scan.stock_barrier',
                    'scan.threshold',
                ])
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        return $items;
    }

    private function queueDigest($backoffice, array $newCounts, array $itemsByScope, $now): void
    {
        if (array_sum($newCounts) === 0) {
            return;
        }

        $additionalRecipients = collect(config('stock_barrier_alerts.recipients', []))
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        foreach ($newCounts as $scope => $count) {
            $recipients = collect($additionalRecipients)
                ->merge($this->authorizedUserEmails([$scope]))
                ->unique()
                ->values()
                ->all();

            if ($recipients === []) {
                Log::warning('Stok barrier baru terdeteksi, tetapi tidak ada user berizin dengan email yang valid.', [
                    'scope' => $scope,
                ]);

                continue;
            }

            $backoffice->table('stock_barrier_alert_emails')->insert([
                'id' => (string) Str::uuid(),
                'recipients' => json_encode($recipients, JSON_THROW_ON_ERROR),
                'payload' => json_encode([
                    'counts' => [$scope => (int) $count],
                    'items' => $itemsByScope[$scope] ?? [],
                    'sample_limit' => self::EMAIL_SAMPLE_LIMIT,
                ], JSON_THROW_ON_ERROR),
                'attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function authorizedUserEmails(array $scopes): array
    {
        $permissionNames = collect($scopes)
            ->map(fn (string $scope) => $scope === 'store'
                ? 'view monitoring-stok-barier-cabang'
                : 'view monitoring-stok-barier-gudang')
            ->unique()
            ->values()
            ->all();

        $backoffice = DB::connection('immostore_backoffice');
        $requiredTables = ['permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions'];

        foreach ($requiredTables as $table) {
            if (! Schema::connection('immostore_backoffice')->hasTable($table)) {
                return [];
            }
        }

        $permissionIds = $backoffice->table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return [];
        }

        $roleIds = $backoffice->table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->pluck('role_id');

        $userIds = $backoffice->table('model_has_roles')
            ->whereIn('role_id', $roleIds)
            ->pluck('model_id')
            ->merge(
                $backoffice->table('model_has_permissions')
                    ->whereIn('permission_id', $permissionIds)
                    ->pluck('model_id')
            )
            ->unique()
            ->values();

        if ($userIds->isEmpty() || ! Schema::connection('pgsql')->hasTable('users')) {
            return [];
        }

        $users = DB::connection('pgsql')->table('users')
            ->whereIn('id', $userIds)
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->when(
                Schema::connection('pgsql')->hasColumn('users', 'deleted_at'),
                fn ($query) => $query->whereNull('deleted_at')
            )
            ->when(
                Schema::connection('pgsql')->hasColumn('users', 'backoffice_access'),
                fn ($query) => $query->where('backoffice_access', true)
            )
            ->pluck('email')
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        return $users->all();
    }

    private function sendPendingEmails(): int
    {
        $backoffice = DB::connection('immostore_backoffice');
        $failures = 0;
        $pending = $backoffice->table('stock_barrier_alert_emails')
            ->whereNull('sent_at')
            ->where(function ($query) {
                $query->where('attempts', 0)
                    ->orWhere('updated_at', '<=', now()->subMinutes(5));
            })
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        foreach ($pending as $email) {
            try {
                $recipients = json_decode($email->recipients, true, flags: JSON_THROW_ON_ERROR);
                $payload = json_decode($email->payload, true, flags: JSON_THROW_ON_ERROR);

                Mail::to($recipients)->send(new StockBarrierAlertDigest($payload));

                $backoffice->table('stock_barrier_alert_emails')
                    ->where('id', $email->id)
                    ->update([
                        'sent_at' => now(),
                        'attempts' => (int) $email->attempts + 1,
                        'last_error' => null,
                        'updated_at' => now(),
                    ]);
            } catch (Throwable $exception) {
                $failures++;
                report($exception);

                $backoffice->table('stock_barrier_alert_emails')
                    ->where('id', $email->id)
                    ->update([
                        'attempts' => (int) $email->attempts + 1,
                        'last_error' => Str::limit($exception->getMessage(), 2000, ''),
                        'updated_at' => now(),
                    ]);

                Log::error('Email alert stok barrier gagal dikirim dan akan dicoba ulang.', [
                    'alert_email_id' => $email->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $failures;
    }
}
