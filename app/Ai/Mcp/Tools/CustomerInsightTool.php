<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Mcp\AssistantTool;
use App\Ai\Security\AssistantAccessPolicy;
use App\Ai\Support\AnswerStatus;
use App\Domain\Analytics\Queries\CustomerDemographicsQuery;
use App\Domain\Analytics\Queries\CustomerProductPreferenceQuery;

class CustomerInsightTool extends AssistantTool
{
    public function name(): string
    {
        return 'customer_insight';
    }

    public function description(): string
    {
        return 'Analisis customer VeeStore: demografi gender, usia, wilayah, kanal registrasi, tier membership, tren registrasi, customer ulang tahun, peringkat pelanggan berdasarkan nilai belanja atau jumlah transaksi pada periode tertentu, serta preferensi produk berdasarkan usia, gender, wilayah, tier, atau kanal.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'focus' => [
                    'type' => 'string',
                    'enum' => ['demographics', 'top_customers', 'product_preference'],
                    'default' => 'demographics',
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'YYYY-MM-DD. Untuk top_customers dan product_preference: awal periode transaksi. Untuk demographics: awal tanggal registrasi.',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'YYYY-MM-DD. Untuk top_customers dan product_preference: akhir periode transaksi. Untuk demographics: akhir tanggal registrasi.',
                ],
                'rank_by' => [
                    'type' => 'string',
                    'enum' => ['spending', 'transactions', 'visits', 'points'],
                    'default' => 'spending',
                    'description' => 'Dasar peringkat untuk focus top_customers.',
                ],
                'dimension' => [
                    'type' => 'string',
                    'enum' => ['age_group', 'gender', 'province', 'regency', 'membership', 'registration_channel'],
                    'default' => 'age_group',
                    'description' => 'Dimensi segmentasi untuk product_preference.',
                ],
                'metric' => [
                    'type' => 'string',
                    'enum' => ['qty', 'revenue', 'buyers', 'transactions'],
                    'default' => 'revenue',
                ],
                'channel' => [
                    'type' => 'string',
                    'enum' => ['', 'pos', 'ecommerce'],
                    'default' => '',
                ],
                'store_id' => ['type' => 'string', 'description' => 'UUID toko, opsional.'],
                'category_id' => ['type' => 'string', 'description' => 'UUID kategori, opsional.'],
                'brand_id' => ['type' => 'string', 'description' => 'UUID brand, opsional.'],
                'limit' => [
                    'type' => 'integer',
                    'default' => 20,
                    'min' => 5,
                    'max' => 100,
                ],
            ],
        ];
    }

    protected function execute(array $arguments): array
    {
        $focus = $arguments['focus'] ?? 'demographics';

        return match ($focus) {
            'product_preference' => $this->preference($arguments),
            'top_customers' => $this->topCustomers($arguments),
            default => $this->demographics($arguments),
        };
    }

    private function topCustomers(array $arguments): array
    {
        $rankBy = (string) ($arguments['rank_by'] ?? 'spending');
        $rankBy = in_array($rankBy, ['spending', 'transactions', 'visits', 'points'], true) ? $rankBy : 'spending';

        $data = app(CustomerDemographicsQuery::class)->topCustomers([
            'spent_from' => $arguments['date_from'] ?? null,
            'spent_to' => $arguments['date_to'] ?? null,
            'store_id' => $arguments['store_id'] ?? null,
            'sort' => $rankBy,
        ], (int) ($arguments['limit'] ?? 20));

        $rows = collect($data['rows']->items());
        $canViewDetail = app(AssistantAccessPolicy::class)->canViewCustomerPii();

        $customers = $rows->map(fn ($row) => [
            'name' => $row->name ?? null,
            'email' => $row->email ?? null,
            'wa_number' => $row->wa_number ?? null,
            'city' => $row->regency_name ?? null,
            'province' => $row->province_name ?? null,
            'membership' => $row->membership_name ?? null,
            'transactions' => (int) ($row->pos_transactions ?? 0) + (int) ($row->ecom_orders ?? 0),
            'pos_transactions' => (int) ($row->pos_transactions ?? 0),
            'ecommerce_transactions' => (int) ($row->ecom_orders ?? 0),
            'spending' => (float) ($row->total_spending ?? 0),
            'pos_spending' => (float) ($row->pos_spending ?? 0),
            'ecommerce_spending' => (float) ($row->ecom_spending ?? 0),
            'visits' => (int) ($row->visit_count ?? 0),
            'loyalty_points' => (int) ($row->loyalty_points ?? 0),
            'last_visit_at' => $row->last_visit_at ?? null,
        ])->values()->all();

        return [
            'focus' => 'top_customers',
            'rank_by' => $rankBy,
            'period' => $data['period'],
            'answer_status' => $canViewDetail ? AnswerStatus::OK : AnswerStatus::AGGREGATE_ONLY,
            'row_count' => $rows->count(),
            'total_matched' => $data['rows']->total(),
            'customers' => $canViewDetail ? $customers : [],
            'totals' => [
                'spending' => (float) $rows->sum(fn ($row) => (float) ($row->total_spending ?? 0)),
                'transactions' => (int) $rows->sum(fn ($row) => (int) ($row->pos_transactions ?? 0) + (int) ($row->ecom_orders ?? 0)),
            ],
            'note' => $canViewDetail
                ? 'Peringkat memakai pembayaran POS lunas ditambah grand total ecommerce lunas pada periode tersebut.'
                : 'Detail nama pelanggan memerlukan kewenangan tambahan; hanya total agregat yang ditampilkan.',
        ];
    }

    private function demographics(array $arguments): array
    {
        $filters = array_filter([
            'joined_from' => $arguments['date_from'] ?? null,
            'joined_to' => $arguments['date_to'] ?? null,
            'store_id' => $arguments['store_id'] ?? null,
        ], fn ($value) => is_string($value) && trim($value) !== '');

        $data = app(CustomerDemographicsQuery::class)->build($filters, (int) ($arguments['limit'] ?? 20));
        $canViewDetail = app(AssistantAccessPolicy::class)->canViewCustomerPii();
        $limit = max(1, (int) config('assistant.limits.max_customer_rows', 5));

        return [
            'filters' => $data['filters'],
            'answer_status' => $canViewDetail ? AnswerStatus::OK : AnswerStatus::AGGREGATE_ONLY,
            'summary' => $data['summary'],
            'gender' => $data['genderRows'],
            'age_groups' => $data['ageRows'],
            'channels' => $data['channelRows'],
            'membership_tiers' => $data['tierRows'],
            'provinces' => $data['provinceRows'],
            'registration_trend' => $data['registrationTrend'],
            'birthday_month' => $data['birthdayMonthLabel'],
            'birthday_customers' => $canViewDetail
                ? collect($data['birthdayCustomers'])
                    ->take($limit)
                    ->map(fn ($row) => is_object($row) && method_exists($row, 'toArray')
                        ? $row->toArray()
                        : (array) $row)
                    ->values()
                    ->all()
                : [],
            'customers' => $canViewDetail
                ? collect($data['rows']->items())->take($limit)->map(fn ($row) => [
                    'name' => $row->name ?? null,
                    'gender' => $row->gender ?? null,
                    'age_group' => $row->age_group ?? null,
                    'membership' => $row->membership_name ?? $row->membership ?? null,
                    'province' => $row->province_name ?? null,
                    'regency' => $row->regency_name ?? null,
                    'joined_at' => $row->created_at ?? null,
                    'visit_count' => $row->visit_count ?? null,
                    'total_spending' => $row->total_spending ?? null,
                    'loyalty_points' => $row->loyalty_points ?? null,
                ])->values()->all()
                : [],
            'note' => $canViewDetail
                ? 'Detail pelanggan ditampilkan untuk peran berwenang dan dibatasi per halaman.'
                : 'Hanya ringkasan agregat yang ditampilkan. Detail pelanggan memerlukan kewenangan tambahan.',
        ];
    }

    private function preference(array $arguments): array
    {
        $filters = array_filter([
            'date_from' => $arguments['date_from'] ?? null,
            'date_to' => $arguments['date_to'] ?? null,
            'dimension' => $arguments['dimension'] ?? 'age_group',
            'metric' => $arguments['metric'] ?? 'revenue',
            'channel' => $arguments['channel'] ?? '',
            'store_id' => $arguments['store_id'] ?? null,
            'category_id' => $arguments['category_id'] ?? null,
            'brand_id' => $arguments['brand_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $data = app(CustomerProductPreferenceQuery::class)->build($filters, (int) ($arguments['limit'] ?? 20));

        return [
            'filters' => $data['filters'],
            'dimension' => $data['dimensionLabel'],
            'metric' => $data['metricLabel'],
            'summary' => $data['summary'],
            'segments' => $data['segmentTotals'],
            'top_products' => array_slice($data['matrixRows'], 0, 20),
            'selected_product' => $data['selectedProduct'],
            'breakdown' => $data['breakdown'],
            'insight' => $data['insight'],
        ];
    }
}
