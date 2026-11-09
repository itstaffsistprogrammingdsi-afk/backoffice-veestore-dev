<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class MasterChangeHistoryReportController extends Controller
{
    private const CONNECTION = 'immostore_backoffice';

    private const TYPE_PREFIXES = [
        'Diskon' => ['master-data.diskons.', 'master-data.products-diskons.'],
        'Voucher' => ['master-data.vouchers.'],
        'Harga' => ['master-data.product-prices.'],
        'Promo' => ['master-data.promos.', 'master-data.promo-setup.'],
    ];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $isOvernight = $filters['overnight'] === '1';
        $title = $isOvernight
            ? 'Penyesuaian Harga Overnight'
            : 'Riwayat Master Diskon, Voucher & Harga';
        $description = $isOvernight
            ? 'Perubahan harga yang dilakukan di luar jam operasional, antara pukul 22.00 dan 06.00.'
            : 'Jejak pembuatan dan perubahan master diskon, voucher, harga, dan promo beserta pengguna yang melakukan perubahan.';
        $hasTable = Schema::connection(self::CONNECTION)->hasTable('business_audit_logs');

        $empty = [
            'rows' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $filters['per_page'], 1, ['path' => url()->current()]),
            'summary' => ['total' => 0, 'diskon' => 0, 'voucher' => 0, 'harga' => 0, 'promo' => 0],
            'users' => [],
            'types' => [],
            'actions' => [],
            'filters' => $filters,
            'errorMessage' => $hasTable ? null : 'Tabel audit backoffice belum tersedia.',
        ];

        if (! $hasTable) {
            return Inertia::render('Monitoring/Report', [
                'title' => $title,
                'description' => $description,
                'data' => $empty,
                'filters' => $request->query(),
                'urls' => ['index' => url()->current()],
            ]);
        }

        $query = $this->baseQuery($filters);
        $rows = (clone $query)
            ->orderByDesc('occurred_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $productNames = $this->productNamesFor($rows->getCollection());
        $rows->through(fn ($row) => $this->transformRow($row, $productNames));

        $summary = (clone $query)
            ->selectRaw("
                COUNT(*)::int as total,
                COUNT(*) FILTER (WHERE " . $this->typeCondition('Diskon') . ")::int as diskon,
                COUNT(*) FILTER (WHERE " . $this->typeCondition('Voucher') . ")::int as voucher,
                COUNT(*) FILTER (WHERE " . $this->typeCondition('Harga') . ")::int as harga,
                COUNT(*) FILTER (WHERE " . $this->typeCondition('Promo') . ")::int as promo
            ")
            ->first();

        $users = (clone $query)
            ->whereNotNull('user_name')
            ->select('user_name')
            ->distinct()
            ->orderBy('user_name')
            ->pluck('user_name')
            ->filter()
            ->values();

        $actions = (clone $query)
            ->whereNotNull('action')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->filter()
            ->values();

        return Inertia::render('Monitoring/Report', [
            'title' => $title,
            'description' => $description,
            'data' => [
                'rows' => $rows,
                'summary' => (array) $summary,
                'users' => $users,
                'types' => collect(array_keys(self::TYPE_PREFIXES))
                    ->map(fn (string $type): array => ['value' => $type, 'label' => $type])
                    ->values()
                    ->all(),
                'actions' => $actions
                    ->map(fn (string $action): array => ['value' => $action, 'label' => ucfirst($action)])
                    ->values()
                    ->all(),
                'filters' => $filters,
                'errorMessage' => null,
            ],
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function baseQuery(array $filters)
    {
        $query = DB::connection(self::CONNECTION)->table('business_audit_logs')
            ->where(function ($scope): void {
                foreach (self::TYPE_PREFIXES as $prefixes) {
                    foreach ($prefixes as $prefix) {
                        $scope->orWhere('route_name', 'LIKE', $prefix.'%');
                    }
                }
            })
            ->when($filters['jenis'] !== '', function ($query) use ($filters): void {
                $prefixes = self::TYPE_PREFIXES[$filters['jenis']] ?? [];
                $query->where(function ($scope) use ($prefixes): void {
                    foreach ($prefixes as $prefix) {
                        $scope->orWhere('route_name', 'LIKE', $prefix.'%');
                    }
                });
            })
            ->when($filters['aksi'] !== '', fn ($query) => $query->where('action', $filters['aksi']))
            ->when($filters['user_name'] !== '', fn ($query) => $query->where('user_name', $filters['user_name']))
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';

                $query->where(function ($search) use ($like): void {
                    $search->where('user_name', 'ILIKE', $like)
                        ->orWhere('route_name', 'ILIKE', $like)
                        ->orWhere('action', 'ILIKE', $like)
                        ->orWhere('request_data', 'ILIKE', $like);
                });
            })
            ->when($filters['from'] !== '', fn ($query) => $query->whereDate('occurred_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->whereDate('occurred_at', '<=', $filters['to']))
            ->when($filters['overnight'] === '1', function ($query): void {
                $query->where(function ($time): void {
                    $time->whereRaw('EXTRACT(HOUR FROM occurred_at) >= 22')
                        ->orWhereRaw('EXTRACT(HOUR FROM occurred_at) < 6');
                });
            });

        return $query;
    }

    private function transformRow(object $row, array $productNames): array
    {
        $payload = json_decode((string) $row->request_data, true);
        $payload = is_array($payload) ? $payload : [];
        $type = $this->typeFor((string) $row->route_name);
        $productName = $productNames[(string) ($payload['product_id'] ?? '')] ?? null;

        return [
            'id' => $row->id,
            'occurred_at' => $row->occurred_at,
            'jenis' => $type,
            'aksi' => $row->action,
            'user_name' => $row->user_name,
            'ringkasan' => $this->summaryFor($type, $payload, $productName),
            'route_name' => $row->route_name,
            'response_status' => $row->response_status,
            'ip_address' => $row->ip_address,
        ];
    }

    private function summaryFor(string $type, array $payload, ?string $productName): string
    {
        return match ($type) {
            'Diskon' => trim(implode(' · ', array_filter([
                $payload['name'] ?? null,
                $payload['type'] ?? null,
                isset($payload['value']) ? $payload['value'] : null,
                isset($payload['max_discount']) ? 'maks '.$payload['max_discount'] : null,
            ]))) ?: '-',
            'Voucher' => trim(implode(' · ', array_filter([
                $payload['code'] ?? null,
                $payload['name'] ?? null,
                $payload['type'] ?? null,
                isset($payload['value']) ? $payload['value'] : null,
            ]))) ?: '-',
            'Promo' => trim(implode(' · ', array_filter([
                $payload['name'] ?? null,
                $payload['code'] ?? null,
            ]))) ?: '-',
            'Harga' => $this->priceSummary($payload, $productName),
            default => '-',
        };
    }

    private function priceSummary(array $payload, ?string $productName): string
    {
        $parts = [];

        if ($productName) {
            $parts[] = 'Produk '.$productName;
        }

        if (isset($payload['prices']) && is_array($payload['prices'])) {
            foreach (['base', 'store', 'ecommerce'] as $type) {
                if (isset($payload['prices'][$type])) {
                    $parts[] = $type.' '.$payload['prices'][$type];
                }
            }
        } elseif (isset($payload['price'])) {
            $parts[] = ($payload['price_type'] ?? 'harga').' '.$payload['price'];
        }

        if (! empty($payload['valid_from'])) {
            $parts[] = 'mulai '.$payload['valid_from'];
        }

        return $parts === [] ? '-' : implode(' · ', $parts);
    }

    private function productNamesFor($rows): array
    {
        $productIds = $rows
            ->map(function ($row): ?string {
                $payload = json_decode((string) $row->request_data, true);

                return is_array($payload) ? ($payload['product_id'] ?? null) : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($productIds === []) {
            return [];
        }

        return DB::table('products')
            ->whereIn('id', $productIds)
            ->pluck('name', 'id')
            ->all();
    }

    private function typeFor(string $routeName): string
    {
        foreach (self::TYPE_PREFIXES as $type => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($routeName, $prefix)) {
                    return $type;
                }
            }
        }

        return 'Lainnya';
    }

    private function typeCondition(string $type): string
    {
        $conditions = collect(self::TYPE_PREFIXES[$type] ?? [])
            ->map(fn (string $prefix): string => "route_name LIKE '".str_replace("'", "''", $prefix)."%'")
            ->implode(' OR ');

        return '('.$conditions.')';
    }

    public function overnight(Request $request)
    {
        $request->query->set('jenis', 'Harga');
        $request->query->set('overnight', '1');

        return $this->index($request);
    }

    private function filters(Request $request): array
    {
        $from = $this->normalizeDate($request->query('from')) ?: now()->startOfMonth()->toDateString();
        $to = $this->normalizeDate($request->query('to')) ?: now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $jenis = trim((string) $request->query('jenis', ''));
        $aksi = trim((string) $request->query('aksi', ''));
        $perPage = (int) ($request->query('per_page', 25) ?: 25);

        if (! array_key_exists($jenis, self::TYPE_PREFIXES)) {
            $jenis = '';
        }

        if (! in_array($aksi, ['create', 'update', 'delete', 'approve', 'reject'], true)) {
            $aksi = '';
        }

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'jenis' => $jenis,
            'aksi' => $aksi,
            'user_name' => trim((string) $request->query('user_name', '')),
            'overnight' => $request->boolean('overnight') ? '1' : '',
            'from' => $from,
            'to' => $to,
            'per_page' => $perPage,
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
