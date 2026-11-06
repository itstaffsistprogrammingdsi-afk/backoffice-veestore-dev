<?php

namespace App\Http\Controllers\BranchOperations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StoreLabelPrintRequestController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $storeId = $request->query('store_id');
        $requestedBy = $request->query('requested_by');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();
        $perPage = 10;
        $accessibleStoreIds = $this->accessibleStoreIds();

        $rows = DB::table('store_label_print_requests as slpr')
            ->leftJoin('stores as s', 's.id', '=', 'slpr.store_id')
            ->leftJoin('users as req_user', 'req_user.id', '=', 'slpr.requested_by')
            ->leftJoin('users as app_user', 'app_user.id', '=', 'slpr.approved_by')
            ->leftJoin('store_label_print_request_items as slpri', 'slpri.label_print_request_id', '=', 'slpr.id')
            ->leftJoin('store_label_print_histories as slph', 'slph.label_print_request_id', '=', 'slpr.id')
            ->select([
                'slpr.id',
                'slpr.store_id',
                'slpr.requested_by',
                'slpr.status',
                'slpr.reason',
                'slpr.approved_by',
                'slpr.approved_at',
                'slpr.created_at',
                'slpr.updated_at',

                's.name as store_name',
                'req_user.name as requested_by_name',
                'app_user.name as approved_by_name',

                DB::raw('COUNT(DISTINCT slpri.id) as total_items'),
                DB::raw('COUNT(DISTINCT slph.id) as total_printed'),
            ])
            ->when($accessibleStoreIds !== null, fn ($builder) => $builder->whereIn('slpr.store_id', $accessibleStoreIds))
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";
                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('s.name', 'ILIKE', $like)
                        ->orWhere('req_user.name', 'ILIKE', $like)
                        ->orWhere('app_user.name', 'ILIKE', $like)
                        ->orWhere('slpr.reason', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(slpr.id AS TEXT)'), 'ILIKE', $like);
                });
            })
            ->when($status, function ($qBuilder) use ($status) {
                $qBuilder->where('slpr.status', $status);
            })
            ->when($storeId, function ($qBuilder) use ($storeId) {
                $qBuilder->where('slpr.store_id', $storeId);
            })
            ->when($requestedBy, function ($qBuilder) use ($requestedBy) {
                $qBuilder->where('slpr.requested_by', $requestedBy);
            })
            ->whereBetween(DB::raw('slpr.created_at::date'), [$from, $to])
            ->groupBy([
                'slpr.id',
                'slpr.store_id',
                'slpr.requested_by',
                'slpr.status',
                'slpr.reason',
                'slpr.approved_by',
                'slpr.approved_at',
                'slpr.created_at',
                'slpr.updated_at',
                's.name',
                'req_user.name',
                'app_user.name',
            ])
            ->orderByDesc('slpr.created_at')
            ->paginate($perPage)
            ->withQueryString();

        $requestIds = $rows->getCollection()->pluck('id')->all();

        $detailItems = collect();
        if (! empty($requestIds)) {
            $detailItems = DB::table('store_label_print_request_items as slpri')
                ->join('items as i', 'i.id', '=', 'slpri.item_id')
                ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
                ->leftJoin('store_label_print_histories as slph', function ($join) {
                    $join->on('slph.label_print_request_id', '=', 'slpri.label_print_request_id')
                        ->on('slph.item_id', '=', 'slpri.item_id');
                })
                ->leftJoin('users as print_user', 'print_user.id', '=', 'slph.printed_by')
                ->whereIn('slpri.label_print_request_id', $requestIds)
                ->select([
                    'slpri.id',
                    'slpri.label_print_request_id',
                    'slpri.item_id',
                    'p.code as product_code',
                    'p.name as product_name',
                    'i.status as item_status',
                    'slph.print_index',
                    'slph.printed_at',
                    'print_user.name as printed_by_name',
                ])
                ->orderBy('slpri.created_at')
                ->get()
                ->groupBy('label_print_request_id');
        }

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->when($accessibleStoreIds !== null, fn ($builder) => $builder->whereIn('id', $accessibleStoreIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = DB::table('store_label_print_requests')
            ->select(DB::raw('status::text as status'))
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $storeItems = DB::table('store_items as si')
            ->join('items as i', 'i.id', '=', 'si.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->whereNull('s.deleted_at')
            ->when($accessibleStoreIds !== null, fn ($builder) => $builder->whereIn('si.store_id', $accessibleStoreIds))
            ->orderBy('s.name')
            ->orderBy('p.name')
            ->get([
                'si.store_id',
                'si.item_id',
                'si.category',
                'p.code as product_code',
                'p.name as product_name',
                'i.status as item_status',
                DB::raw('COALESCE(rt.value, i.id::text) as barcode_value'),
            ]);

        $canDecide = $this->canDecideLabelRequest();

        return Inertia::render('BranchOperations/LabelPrintRequests', [
            'rows' => $rows,
            'detailItems' => $detailItems,
            'stores' => $stores,
            'users' => $users,
            'statuses' => $statuses,
            'storeItems' => $storeItems,
            'canDecide' => $canDecide,
            'canRequest' => $this->canRequestLabel(),
            'canPrint' => $this->canPrintLabel(),
            'filters' => [
                'q' => $q,
                'status' => $status,
                'store_id' => $storeId,
                'requested_by' => $requestedBy,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('branch-operations.request-label.index'),
                'store' => route('branch-operations.request-label.store'),
                'approve' => route('management.request-labels.approve', ['id' => '__ID__']),
                'reject' => route('management.request-labels.reject', ['id' => '__ID__']),
                'print' => route('branch-operations.request-label.print', ['id' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->canRequestLabel(), 403);

        $validated = $request->validate([
            'store_id' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'max:500'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'uuid'],
        ]);

        $accessibleStoreIds = $this->accessibleStoreIds();

        if ($accessibleStoreIds !== null && ! in_array($validated['store_id'], $accessibleStoreIds, true)) {
            abort(403, 'Toko tidak termasuk akses pengguna.');
        }

        $validItems = DB::table('store_items')
            ->where('store_id', $validated['store_id'])
            ->whereIn('item_id', $validated['item_ids'])
            ->pluck('item_id')
            ->all();

        if (count($validItems) !== count(array_unique($validated['item_ids']))) {
            return back()->with('error', 'Sebagian item tidak berada di toko yang dipilih.');
        }

        $requestId = (string) Str::uuid();

        DB::transaction(function () use ($validated, $validItems, $requestId) {
            DB::table('store_label_print_requests')->insert([
                'id' => $requestId,
                'store_id' => $validated['store_id'],
                'requested_by' => auth()->id(),
                'status' => 'pending',
                'reason' => $validated['reason'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = collect($validItems)->map(fn ($itemId) => [
                'id' => (string) Str::uuid(),
                'label_print_request_id' => $requestId,
                'item_id' => $itemId,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            DB::table('store_label_print_request_items')->insert($rows);
        });

        return redirect()
            ->route('branch-operations.request-label.index')
            ->with('success', 'Permintaan cetak label dikirim dan menunggu persetujuan HO/BO.');
    }

    public function print(Request $request, string $id)
    {
        abort_unless($this->canPrintLabel(), 403);

        $labelRequest = DB::table('store_label_print_requests as slpr')
            ->join('stores as s', 's.id', '=', 'slpr.store_id')
            ->leftJoin('users as approver', 'approver.id', '=', 'slpr.approved_by')
            ->leftJoin('users as requester', 'requester.id', '=', 'slpr.requested_by')
            ->where('slpr.id', $id)
            ->first([
                'slpr.id',
                'slpr.store_id',
                'slpr.status',
                'slpr.reason',
                'slpr.created_at',
                'slpr.approved_at',
                's.name as store_name',
                's.code as store_code',
                's.address as store_address',
                'approver.name as approved_by_name',
                'requester.name as requested_by_name',
            ]);

        abort_unless($labelRequest, 404);
        abort_unless(in_array((string) $labelRequest->status, ['approved', 'printed'], true), 403, 'Label hanya dapat dicetak setelah disetujui HO/BO.');

        $items = DB::table('store_label_print_request_items as slpri')
            ->join('items as i', 'i.id', '=', 'slpri.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->where('slpri.label_print_request_id', $id)
            ->orderBy('p.name')
            ->get([
                'slpri.item_id',
                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                'i.status as item_status',
                DB::raw('COALESCE(rt.value, i.id::text) as barcode_value'),
            ]);

        abort_if($items->isEmpty(), 422, 'Permintaan tidak memiliki item.');

        $maxPrints = DB::table('store_label_print_histories')
            ->where('label_print_request_id', $id)
            ->select('item_id')
            ->selectRaw('MAX(COALESCE(print_index, 0))::int as max_print_index')
            ->groupBy('item_id')
            ->pluck('max_print_index', 'item_id');

        $printedAt = now();

        DB::transaction(function () use ($id, $items, $maxPrints, $printedAt) {
            $rows = $items->map(function ($item) use ($id, $maxPrints, $printedAt) {
                return [
                    'id' => (string) Str::uuid(),
                    'item_id' => $item->item_id,
                    'label_print_request_id' => $id,
                    'printed_by' => auth()->id(),
                    'print_index' => ((int) ($maxPrints[$item->item_id] ?? 0)) + 1,
                    'printed_at' => $printedAt,
                    'created_at' => $printedAt,
                    'updated_at' => $printedAt,
                ];
            })->all();

            DB::table('store_label_print_histories')->insert($rows);

            DB::table('store_label_print_requests')
                ->where('id', $id)
                ->update([
                    'status' => 'printed',
                    'updated_at' => $printedAt,
                ]);
        });

        $printItems = $items->map(function ($item) use ($maxPrints) {
            $printIndex = ((int) ($maxPrints[$item->item_id] ?? 0)) + 1;
            $svg = (string) QrCode::format('svg')
                ->size(130)
                ->margin(1)
                ->generate($item->barcode_value);

            return [
                ...((array) $item),
                'print_index' => $printIndex,
                'qr_svg' => $svg,
            ];
        });

        $requestNumber = strtoupper(substr(str_replace('-', '', $id), 0, 8));

        return response()
            ->view('labels.print', [
                'labelRequest' => $labelRequest,
                'items' => $printItems,
                'requestNumber' => $requestNumber,
                'printedBy' => auth()->user()?->name ?? '-',
                'printedAt' => $printedAt,
                'printCount' => $printItems->max('print_index') ?? 0,
            ]);
    }

    private function accessibleStoreIds(): ?array
    {
        $user = auth()->user();

        if (! $user || $this->canDecideLabelRequest() || $user->backoffice_access) {
            return null;
        }

        return DB::table('store_users')
            ->where('user_id', $user->id)
            ->pluck('store_id')
            ->all();
    }

    private function canRequestLabel(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->can('create label-print-requests') || $user->can('manage label-print-requests')) {
            return true;
        }

        $roles = ['superadmin', 'administrator', 'admin', 'management', 'manager', 'store operations'];

        return $user->getRoleNames()
            ->map(fn ($role) => strtolower(trim($role)))
            ->contains(fn ($role) => in_array($role, $roles, true))
            || (bool) $user->store_access;
    }

    private function canPrintLabel(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->can('print label-print-requests') || $user->can('manage label-print-requests')) {
            return true;
        }

        return $this->canDecideLabelRequest() || $this->canRequestLabel();
    }

    private function canDecideLabelRequest(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->can('approve management-label-requests') || $user->can('manage label-print-requests')) {
            return true;
        }

        $managementRoles = ['superadmin', 'administrator', 'admin', 'management', 'manager'];

        return $user->getRoleNames()
            ->map(fn ($role) => strtolower(preg_replace('/[^a-z0-9]/', '', $role)))
            ->contains(fn ($role) => in_array($role, $managementRoles, true));
    }
}
