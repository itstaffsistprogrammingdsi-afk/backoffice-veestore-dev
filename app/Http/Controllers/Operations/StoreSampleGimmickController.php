<?php

namespace App\Http\Controllers\Operations;

use App\Domain\StoreOperations\Actions\ApproveStoreSampleGimmick;
use App\Domain\StoreOperations\Actions\RejectStoreSampleGimmick;
use App\Domain\StoreOperations\Queries\StoreSampleGimmickRequestQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StoreSampleGimmickController extends Controller
{
    private const CATEGORIES = ['inventory', 'display', 'sample', 'gimmick'];

    private const CATEGORY_LABELS = [
        'inventory' => 'Inventory',
        'display' => 'Display',
        'sample' => 'Sample',
        'gimmick' => 'Gimmick',
    ];

    public function index(Request $request, StoreSampleGimmickRequestQuery $requestQuery): Response
    {
        $filters = $this->filters($request);
        $requestFilters = $this->requestFilters($request);
        $requestData = $requestQuery->build(
            $requestFilters['q'],
            $requestFilters['store_id'],
            $requestFilters['type'],
            $requestFilters['status'],
            $requestFilters['per_page']
        );
        $base = $this->baseQuery($filters);

        $summary = DB::query()
            ->fromSub(clone $base, 'store_item_rows')
            ->selectRaw("
                COUNT(*)::int as total_items,
                COUNT(*) FILTER (WHERE category = 'sample')::int as total_sample,
                COUNT(*) FILTER (WHERE category = 'gimmick')::int as total_gimmick,
                COUNT(*) FILTER (WHERE category = 'inventory')::int as total_inventory,
                COUNT(*) FILTER (WHERE category = 'display')::int as total_display
            ")
            ->first();

        $rows = DB::query()
            ->fromSub(clone $base, 'store_item_rows')
            ->when($filters['category'] !== '', fn ($query) => $query->where('category', $filters['category']))
            ->orderBy('product_name')
            ->orderBy('store_name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Operations/SampleGimmick', [
            'rows' => $rows,
            'summary' => (array) $summary,
            'requests' => $requestData['requests'],
            'requestSummary' => $requestData['requestSummary'],
            'stores' => $stores,
            'categories' => collect(self::CATEGORIES)
                ->map(fn (string $value): array => [
                    'value' => $value,
                    'label' => self::CATEGORY_LABELS[$value],
                ])
                ->values()
                ->all(),
            'requestStatusOptions' => [
                ['value' => '', 'label' => 'Semua status'],
                ['value' => 'pending', 'label' => 'Menunggu approval'],
                ['value' => 'approved', 'label' => 'Disetujui'],
                ['value' => 'rejected', 'label' => 'Ditolak'],
            ],
            'requestTypeOptions' => [
                ['value' => '', 'label' => 'Semua jenis'],
                ['value' => 'gimmick', 'label' => 'Gimmick / Gratis'],
                ['value' => 'sample', 'label' => 'Sample / Tester'],
            ],
            'filters' => $filters,
            'requestFilters' => $requestFilters,
            'urls' => [
                'index' => route('operations.sample-gimmick.index'),
                'mark' => route('operations.sample-gimmick.mark'),
                'request_approve' => route('operations.sample-gimmick.requests.approve', ['requestId' => '__ID__']),
                'request_reject' => route('operations.sample-gimmick.requests.reject', ['requestId' => '__ID__']),
            ],
        ]);
    }

    public function mark(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid'],
            'category' => ['required', 'string', 'in:inventory,display,sample,gimmick'],
        ], [
            'ids.required' => 'Pilih minimal satu item.',
            'category.in' => 'Kategori yang dipilih tidak valid.',
        ]);

        $validIds = DB::table('store_items')->whereIn('id', $data['ids'])->pluck('id')->all();

        if ($validIds === []) {
            return back()->with('error', 'Tidak ada item yang berhasil diperbarui.');
        }

        $now = now();
        $actor = auth()->user()?->name;

        DB::transaction(function () use ($validIds, $data, $now, $actor): void {
            if (in_array($data['category'], ['sample', 'gimmick'], true)) {
                foreach ($validIds as $id) {
                    DB::table('app_settings')->updateOrInsert(
                        ['key' => 'store_sample_gimmick:'.$id],
                        [
                            'value' => json_encode([
                                'type' => $data['category'],
                                'marked_at' => $now->toIso8601String(),
                                'marked_by' => $actor,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }

                return;
            }

            DB::table('app_settings')
                ->whereIn('key', array_map(fn (string $id): string => 'store_sample_gimmick:'.$id, $validIds))
                ->delete();

            DB::table('store_items')
                ->whereIn('id', $validIds)
                ->update(['category' => $data['category']]);
        });

        return back()->with(
            'success',
            count($validIds).' item ditandai sebagai '.self::CATEGORY_LABELS[$data['category']].'.'
        );
    }

    public function approve(
        Request $request,
        string $requestId,
        ApproveStoreSampleGimmick $approve
    ): RedirectResponse {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $approve->handle(
            $requestId,
            $data['note'] ?? null,
            Auth::id(),
            Auth::user()?->name
        );

        return back()->with(
            'success',
            "Pengajuan {$result['request_number']} disetujui. {$result['message']}"
        );
    }

    public function reject(
        Request $request,
        string $requestId,
        RejectStoreSampleGimmick $reject
    ): RedirectResponse {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $result = $reject->handle(
            $requestId,
            (string) $data['reason'],
            Auth::id(),
            Auth::user()?->name
        );

        return back()->with(
            'success',
            "Pengajuan {$result['request_number']} ditolak."
        );
    }

    private function requestFilters(Request $request): array
    {
        $status = trim((string) $request->query('rstatus', 'pending'));

        if ($status === 'all') {
            $status = '';
        }

        if (! in_array($status, ['', 'pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $type = trim((string) $request->query('rtype', ''));

        if (! in_array($type, ['', 'sample', 'gimmick'], true)) {
            $type = '';
        }

        $perPage = (int) ($request->query('rper_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        return [
            'q' => trim((string) $request->query('rq', '')),
            'store_id' => trim((string) $request->query('rstore_id', '')) ?: null,
            'type' => $type,
            'status' => $status,
            'per_page' => $perPage,
        ];
    }

    private function baseQuery(array $filters)
    {
        return DB::table('store_items as store_item')
            ->join('stores as store', 'store.id', '=', 'store_item.store_id')
            ->join('items as item', 'item.id', '=', 'store_item.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->leftJoin('product_categories as category', 'category.id', '=', 'product.category_id')
            ->leftJoin('item_conditions as condition', 'condition.id', '=', 'item.current_condition_id')
            ->leftJoin('app_settings as marker', 'marker.key', '=', DB::raw("'store_sample_gimmick:' || store_item.id::text"))
            ->whereNull('store.deleted_at')
            ->whereNull('product.deleted_at')
            ->whereIn(DB::raw('item.status::text'), ['store_stock', 'store_display'])
            ->select([
                'store_item.id',
                'store_item.created_at',
                'store.name as store_name',
                'store.code as store_code',
                'product.name as product_name',
                'product.code as product_code',
                'category.name as product_category',
                'condition.name as condition_name',
            ])
            ->selectRaw("COALESCE(marker.value->>'type', store_item.category::text) as category")
            ->selectRaw("marker.value->>'marked_at' as marked_at")
            ->selectRaw("marker.value->>'marked_by' as marked_by")
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';

                $query->where(function ($search) use ($like): void {
                    $search->where('product.name', 'ILIKE', $like)
                        ->orWhere('product.code', 'ILIKE', $like)
                        ->orWhere('store.name', 'ILIKE', $like)
                        ->orWhere('store.code', 'ILIKE', $like);
                });
            })
            ->when($filters['store_id'] !== '', fn ($query) => $query->where('store_item.store_id', $filters['store_id']));
    }

    private function filters(Request $request): array
    {
        $category = trim((string) $request->query('category', ''));

        if (! in_array($category, self::CATEGORIES, true)) {
            $category = '';
        }

        $perPage = (int) ($request->query('per_page', 25) ?: 25);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'store_id' => trim((string) $request->query('store_id', '')),
            'category' => $category,
            'per_page' => $perPage,
        ];
    }
}
