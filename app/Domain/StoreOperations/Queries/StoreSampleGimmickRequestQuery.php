<?php

namespace App\Domain\StoreOperations\Queries;

use App\Domain\StoreOperations\Support\SampleGimmickRequestStore;
use Illuminate\Support\Facades\DB;

class StoreSampleGimmickRequestQuery
{
    private const STATUS_LABELS = [
        'pending' => 'Menunggu approval',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    private const TYPE_LABELS = [
        'sample' => 'Sample / Tester',
        'gimmick' => 'Gimmick / Gratis',
    ];

    public function build(string $q, ?string $storeId, ?string $type, ?string $status, int $perPage): array
    {
        $query = $this->baseQuery($q, $storeId, $type, $status);

        $rows = $query
            ->orderByRaw("COALESCE(value->>'submitted_at', '') DESC")
            ->paginate($perPage)
            ->withQueryString();

        $rows->getCollection()->transform(fn (object $row): array => $this->present(
            SampleGimmickRequestStore::decode($row->value)
        ));

        return [
            'requests' => $rows,
            'requestSummary' => $this->summary($storeId, $type),
        ];
    }

    public function detail(string $requestId): ?array
    {
        $row = DB::table('app_settings')
            ->where('key', SampleGimmickRequestStore::requestKey($requestId))
            ->first(['key', 'value']);

        if (! $row) {
            return null;
        }

        $request = SampleGimmickRequestStore::decode($row->value);

        return $request === [] ? null : $this->present($request);
    }

    private function baseQuery(string $q, ?string $storeId, ?string $type, ?string $status)
    {
        $query = DB::table('app_settings')
            ->where('key', 'like', SampleGimmickRequestStore::REQUEST_KEY_PREFIX.'%');

        if ($storeId) {
            $query->whereRaw("value->>'store_id' = ?", [$storeId]);
        }

        if (in_array($type, ['sample', 'gimmick'], true)) {
            $query->whereRaw("value->>'type' = ?", [$type]);
        }

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->whereRaw("value->>'status' = ?", [$status]);
        }

        $q = trim($q);

        if ($q !== '') {
            $like = '%'.$q.'%';

            $query->where(function ($inner) use ($like): void {
                $inner->whereRaw("value->>'request_number' ILIKE ?", [$like])
                    ->orWhereRaw("value->'payment'->>'transaction_number' ILIKE ?", [$like])
                    ->orWhereRaw("value->>'store_name' ILIKE ?", [$like])
                    ->orWhereRaw("value->>'created_by_name' ILIKE ?", [$like])
                    ->orWhereRaw("value->>'reviewed_by_name' ILIKE ?", [$like]);
            });
        }

        return $query;
    }

    private function summary(?string $storeId, ?string $type): array
    {
        $query = $this->baseQuery('', $storeId, $type, null);

        $row = $query
            ->selectRaw("
                COUNT(*)::int AS total,
                COUNT(*) FILTER (WHERE value->>'status' = 'pending')::int AS pending,
                COUNT(*) FILTER (WHERE value->>'status' = 'approved')::int AS approved,
                COUNT(*) FILTER (WHERE value->>'status' = 'rejected')::int AS rejected
            ")
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'pending' => (int) ($row->pending ?? 0),
            'approved' => (int) ($row->approved ?? 0),
            'rejected' => (int) ($row->rejected ?? 0),
        ];
    }

    private function present(array $request): array
    {
        $status = (string) ($request['status'] ?? '');
        $type = (string) ($request['type'] ?? '');

        return [
            'id' => (string) ($request['id'] ?? ''),
            'request_number' => (string) ($request['request_number'] ?? ''),
            'store_id' => (string) ($request['store_id'] ?? ''),
            'store_name' => (string) ($request['store_name'] ?? '-'),
            'store_code' => $request['store_code'] ?? null,
            'type' => $type,
            'type_label' => self::TYPE_LABELS[$type] ?? ($type !== '' ? $type : '-'),
            'status' => $status,
            'status_label' => self::STATUS_LABELS[$status] ?? ($status !== '' ? $status : '-'),
            'submitted_at' => $request['submitted_at'] ?? null,
            'created_by_name' => $request['created_by_name'] ?? null,
            'reviewed_by_name' => $request['reviewed_by_name'] ?? null,
            'reviewed_at' => $request['reviewed_at'] ?? null,
            'notes' => $request['notes'] ?? null,
            'review_note' => $request['review_note'] ?? null,
            'rejection_reason' => $request['rejection_reason'] ?? null,
            'reference' => $request['reference'] ?? null,
            'items' => $request['items'] ?? [],
            'item_count' => (int) ($request['item_count'] ?? count($request['items'] ?? [])),
            'total_value' => (float) ($request['total_value'] ?? 0),
            'history' => $request['history'] ?? [],
        ];
    }
}
