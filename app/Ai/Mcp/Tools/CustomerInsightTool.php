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
        return 'Analisis customer VeeStore: demografi gender, usia, wilayah, kanal registrasi, tier membership, tren registrasi, customer ulang tahun, serta preferensi produk berdasarkan usia, gender, wilayah, tier, atau kanal.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'focus' => [
                    'type' => 'string',
                    'enum' => ['demographics', 'product_preference'],
                    'default' => 'demographics',
                ],
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD.'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD.'],
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

        return $focus === 'product_preference'
            ? $this->preference($arguments)
            : $this->demographics($arguments);
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
                ? 'Detail pelanggan dibatasi dan dimasking. Jangan menampilkan daftar massal.'
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
