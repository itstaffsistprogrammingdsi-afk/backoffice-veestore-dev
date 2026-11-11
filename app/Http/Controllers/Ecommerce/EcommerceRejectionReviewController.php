<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Services\EcommerceRejectionHandoffService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EcommerceRejectionReviewController extends Controller
{
    public function __construct(
        private readonly EcommerceRejectionHandoffService $handoff
    ) {}

    public function index(Request $request)
    {
        abort_unless(
            (bool) config('backoffice.ecommerce_rejection_handoff_enabled', false),
            404
        );

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'reason' => trim((string) $request->query('reason', '')),
            'order_id' => trim((string) $request->query('order_id', '')),
            'per_page' => (int) $request->query('per_page', 15),
            'path' => $request->url(),
        ];

        try {
            $cases = $this->handoff->paginate($filters);
            $summary = $this->handoff->summary();
            $handoffError = null;
        } catch (\Throwable $exception) {
            report($exception);

            $cases = new LengthAwarePaginator(
                collect(),
                0,
                $filters['per_page'],
                1,
                ['path' => $request->url()]
            );
            $summary = [
                'pending_cases' => 0,
                'pending_orders' => 0,
                'oldest_requested_at' => null,
                'oldest_age_hours' => null,
            ];
            $handoffError = 'Data kendala toko belum dapat dibaca. Periksa koneksi database POS.';
        }

        return Inertia::render('Ecommerce/Rejections', [
            'cases' => $cases,
            'summary' => $summary,
            'stores' => DB::table('stores')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'warehouses' => DB::table('warehouses')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'filters' => $filters,
            'handoffError' => $handoffError,
            'urls' => [
                'index' => route('ecommerce.rejections.index'),
                'resolve' => route('ecommerce.rejections.resolve', ['id' => '__ID__']),
                'orders' => route('ecommerce.orders.index'),
            ],
        ]);
    }

    public function resolve(Request $request, string $id)
    {
        abort_unless(
            (bool) config('backoffice.ecommerce_rejection_handoff_enabled', false),
            404
        );

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['reassign_store', 'reassign_warehouse', 'cancel'])],
            'target_id' => [
                Rule::requiredIf(in_array($request->input('decision'), ['reassign_store', 'reassign_warehouse'], true)),
                'nullable',
                'uuid',
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->handoff->resolve(
                $id,
                (string) $validated['decision'],
                isset($validated['target_id']) ? (string) $validated['target_id'] : null,
                isset($validated['note']) ? (string) $validated['note'] : null,
                (string) $request->user()->getAuthIdentifier()
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('ecommerce.rejections.index', $request->only(['q', 'reason', 'order_id']))
                ->with('error', 'Kendala belum dapat diselesaikan karena data POS tidak dapat diakses. Coba lagi beberapa saat.');
        }

        $message = match ($result['decision']) {
            'reassign_store' => 'Kendala diselesaikan. Order dipindahkan ke toko '.$result['target_name'].'.',
            'reassign_warehouse' => 'Kendala diselesaikan. Order dipindahkan ke gudang '.$result['target_name'].'.',
            default => 'Kendala diselesaikan. Order ditandai dibatalkan.',
        };

        return redirect()
            ->route('ecommerce.rejections.index', $request->only(['q', 'reason', 'order_id']))
            ->with('success', $message);
    }
}
