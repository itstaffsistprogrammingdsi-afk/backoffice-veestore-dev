<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Mcp\AssistantTool;
use App\Ai\Security\AssistantAccessPolicy;
use App\Ai\Support\AnswerStatus;
use Illuminate\Support\Facades\DB;

class MasterDataTool extends AssistantTool
{
    public function name(): string
    {
        return 'master_data';
    }

    public function description(): string
    {
        return 'Cari data master VeeStore: produk, harga, customer, store, gudang, supplier, courier, partner, kategori, brand, unit, metode pembayaran, voucher, membership, dan user. Gunakan untuk pencarian nama, kode, daftar master, atau detail record.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entity' => [
                    'type' => 'string',
                    'enum' => [
                        'products', 'product_prices', 'customers', 'stores', 'warehouses',
                        'suppliers', 'couriers', 'partners', 'categories', 'brands',
                        'units', 'payment_methods', 'vouchers', 'memberships', 'users',
                    ],
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Kata kunci nama atau kode.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'default' => 20,
                    'min' => 1,
                    'max' => 50,
                ],
            ],
            'required' => ['entity'],
        ];
    }

    protected function execute(array $arguments): array
    {
        $entity = (string) ($arguments['entity'] ?? '');
        $search = trim((string) ($arguments['search'] ?? ''));
        $limit = max(1, min((int) ($arguments['limit'] ?? 20), 50));

        $config = $this->entityConfig($entity);

        if ($config === null) {
            return ['error' => 'Entitas master data tidak dikenal.'];
        }

        if ($entity === 'customers') {
            return $this->customers($search, $limit);
        }

        if ($entity === 'users') {
            return $this->users($search, $limit);
        }

        $query = DB::table($config['table'])->limit($limit);

        if ($config['soft_delete']) {
            $query->whereNull('deleted_at');
        }

        if ($search !== '') {
            $query->where(function ($query) use ($config, $search) {
                foreach ($config['search'] as $column) {
                    $query->orWhere($column, 'ilike', '%'.$search.'%');
                }
            });
        }

        foreach ($config['order'] as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        $rows = $query->get($config['columns'])
            ->map(function ($row) use ($config) {
                $data = (array) $row;

                if (isset($config['map'])) {
                    $data = ($config['map'])($data);
                }

                return $data;
            })
            ->values()
            ->all();

        return [
            'entity' => $entity,
            'search' => $search,
            'count' => count($rows),
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customers(string $search, int $limit): array
    {
        $base = DB::table('customers')->whereNull('deleted_at');

        if ($search === '' && ! app(AssistantAccessPolicy::class)->canViewCustomerPii()) {
            $gender = (clone $base)
                ->selectRaw("COALESCE(NULLIF(LOWER(gender::text), ''), 'belum diisi') AS label, COUNT(*)::int AS total")
                ->groupByRaw("COALESCE(NULLIF(LOWER(gender::text), ''), 'belum diisi')")
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
                ->values()
                ->all();

            $provinces = (clone $base)
                ->leftJoin('provinces as p', 'p.code', '=', 'customers.province_code')
                ->selectRaw("COALESCE(p.name, 'Belum diisi') AS label, COUNT(*)::int AS total")
                ->groupBy('p.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
                ->values()
                ->all();

            return [
                'entity' => 'customers',
                'search' => '',
                'answer_status' => AnswerStatus::AGGREGATE_ONLY,
                'summary' => [
                    'total_customers' => (int) (clone $base)->count(),
                    'registered_customers' => (int) (clone $base)->whereRaw('is_guest IS NOT TRUE')->count(),
                    'guest_customers' => (int) (clone $base)->whereRaw('is_guest IS TRUE')->count(),
                    'registered_this_month' => (int) (clone $base)
                        ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                        ->count(),
                ],
                'gender' => $gender,
                'provinces' => $provinces,
                'rows' => [],
                'note' => 'Peran ini hanya menerima ringkasan agregat. Detail pelanggan memerlukan kewenangan tambahan.',
            ];
        }

        if ($search !== '' && mb_strlen($search) < 3) {
            return [
                'error' => 'Kata kunci pelanggan minimal 3 karakter.',
                'answer_status' => AnswerStatus::INVALID,
            ];
        }

        $query = clone $base;

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where('name', 'ilike', $like)
                    ->orWhere('email', 'ilike', $like)
                    ->orWhere('wa_number', 'ilike', $like);
            });
        }

        $limit = max(1, min($limit, (int) config('assistant.limits.max_customer_rows', 20)));
        $total = (int) (clone $query)->count();
        $rows = $query
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'wa_number', 'gender', 'province_code', 'regency_code', 'is_guest', 'created_at'])
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();

        return [
            'entity' => 'customers',
            'search' => $search,
            'answer_status' => $rows === [] ? AnswerStatus::NO_DATA : AnswerStatus::OK,
            'total_matches' => $total,
            'row_limit' => $limit,
            'rows' => $rows,
            'note' => $total > count($rows)
                ? 'Hasil dibatasi. Persempit pencarian jika perlu.'
                : 'Data pelanggan ditampilkan lengkap untuk peran berwenang.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function users(string $search, int $limit): array
    {
        if ($search !== '' && mb_strlen($search) < 3) {
            return [
                'error' => 'Kata kunci pengguna minimal 3 karakter.',
                'answer_status' => AnswerStatus::INVALID,
            ];
        }

        $query = DB::table('users')->whereNull('deleted_at');

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where('name', 'ilike', $like)
                    ->orWhere('username', 'ilike', $like)
                    ->orWhere('email', 'ilike', $like);
            });
        }

        $limit = max(1, min($limit, (int) config('assistant.limits.max_pii_rows', 20)));
        $total = (int) (clone $query)->count();
        $rows = $query
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'username', 'email', 'backoffice_access', 'store_access', 'wms_access', 'ecommerce_access', 'created_at'])
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();

        return [
            'entity' => 'users',
            'search' => $search,
            'answer_status' => $rows === [] ? AnswerStatus::NO_DATA : AnswerStatus::OK,
            'total_matches' => $total,
            'row_limit' => $limit,
            'rows' => $rows,
            'note' => $total > count($rows)
                ? 'Hasil dibatasi. Persempit pencarian jika perlu.'
                : 'Data pengguna ditampilkan lengkap untuk peran berwenang.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function entityConfig(string $entity): ?array
    {
        $configs = [
            'products' => [
                'table' => 'products',
                'soft_delete' => true,
                'columns' => ['id', 'code', 'name', 'category_id', 'brand_id', 'unit_id', 'created_at'],
                'search' => ['code', 'name'],
                'order' => ['name' => 'asc'],
            ],
            'product_prices' => [
                'table' => 'product_prices',
                'soft_delete' => false,
                'columns' => ['id', 'product_id', 'price_type', 'price', 'valid_from', 'valid_to'],
                'search' => [],
                'order' => ['valid_from' => 'desc'],
            ],
            'customers' => [
                'table' => 'customers',
                'soft_delete' => true,
                'columns' => ['id', 'name', 'email', 'wa_number', 'gender', 'province_code', 'regency_code', 'is_guest', 'created_at'],
                'search' => ['name', 'email', 'wa_number'],
                'order' => ['name' => 'asc'],
            ],
            'stores' => [
                'table' => 'stores',
                'soft_delete' => true,
                'columns' => ['id', 'code', 'name', 'address', 'phone', 'created_at'],
                'search' => ['code', 'name', 'address'],
                'order' => ['name' => 'asc'],
            ],
            'warehouses' => [
                'table' => 'warehouses',
                'soft_delete' => true,
                'columns' => ['id', 'code', 'name', 'address', 'contact_name', 'created_at'],
                'search' => ['code', 'name', 'address'],
                'order' => ['name' => 'asc'],
            ],
            'suppliers' => [
                'table' => 'suppliers',
                'soft_delete' => true,
                'columns' => ['id', 'name', 'country', 'phone', 'created_at'],
                'search' => ['name', 'country', 'phone'],
                'order' => ['name' => 'asc'],
            ],
            'couriers' => [
                'table' => 'couriers',
                'soft_delete' => true,
                'columns' => ['id', 'name', 'code', 'status', 'created_at'],
                'search' => ['name', 'code'],
                'order' => ['name' => 'asc'],
            ],
            'partners' => [
                'table' => 'partners',
                'soft_delete' => true,
                'columns' => ['id', 'name', 'status', 'default_credit_limit', 'billing_cycle', 'created_at'],
                'search' => ['name'],
                'order' => ['name' => 'asc'],
            ],
            'categories' => [
                'table' => 'product_categories',
                'soft_delete' => true,
                'columns' => ['id', 'name', 'group_id', 'created_at'],
                'search' => ['name'],
                'order' => ['name' => 'asc'],
            ],
            'brands' => [
                'table' => 'brands',
                'soft_delete' => true,
                'columns' => ['id', 'name', 'created_at'],
                'search' => ['name'],
                'order' => ['name' => 'asc'],
            ],
            'units' => [
                'table' => 'units',
                'soft_delete' => true,
                'columns' => ['id', 'name', 'created_at'],
                'search' => ['name'],
                'order' => ['name' => 'asc'],
            ],
            'payment_methods' => [
                'table' => 'payment_methods',
                'soft_delete' => true,
                'columns' => ['id', 'name', 'type', 'created_at'],
                'search' => ['name'],
                'order' => ['name' => 'asc'],
            ],
            'vouchers' => [
                'table' => 'vouchers',
                'soft_delete' => true,
                'columns' => ['id', 'code', 'name', 'type', 'value', 'min_purchase', 'valid_from', 'valid_until', 'is_active', 'context', 'usage_limit', 'usage_count'],
                'search' => ['code', 'name'],
                'order' => ['created_at' => 'desc'],
            ],
            'memberships' => [
                'table' => 'memberships',
                'soft_delete' => true,
                'columns' => ['id', 'name', 'earning_multiplier', 'min_spend_treshold', 'sort_order'],
                'search' => ['name'],
                'order' => ['sort_order' => 'asc'],
            ],
            'users' => [
                'table' => 'users',
                'soft_delete' => true,
                'columns' => ['id', 'name', 'username', 'email', 'backoffice_access', 'store_access', 'wms_access', 'ecommerce_access', 'created_at'],
                'search' => ['name', 'username', 'email'],
                'order' => ['name' => 'asc'],
            ],
        ];

        return $configs[$entity] ?? null;
    }
}
