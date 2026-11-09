<?php

namespace App\Http\Controllers\Monitoring;

use App\Domain\Promotions\Actions\SyncDiscountCaps;
use App\Domain\Promotions\Queries\DiscountSyncQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscountSyncController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-diskon')->only(['index']);
        $this->middleware('permission:update master-data-discounts')->only(['sync']);
    }

    public function index(Request $request, DiscountSyncQuery $query): Response
    {
        $q = trim((string) $request->query('q', ''));
        $channel = trim((string) $request->query('channel', ''));
        $channel = in_array($channel, ['mismatch', 'pos', 'ecommerce', 'global', 'unmapped'], true) ? $channel : null;
        $active = trim((string) $request->query('active', ''));
        $active = in_array($active, ['1', '0'], true) ? $active : null;
        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        return Inertia::render('Monitoring/DiscountSync', [
            ...$query->build($q, $channel, $active, $perPage),
            'urls' => [
                'index' => route('monitoring.discount-sync.index'),
                'sync' => route('monitoring.discount-sync.sync'),
                'discounts' => route('master-data.diskons.index'),
            ],
        ]);
    }

    public function sync(Request $request, SyncDiscountCaps $sync): RedirectResponse
    {
        $data = $request->validate([
            'discount_ids' => ['required', 'array', 'min:1'],
            'discount_ids.*' => ['required', 'uuid', 'exists:discounts,id'],
            'target_max_discount' => ['nullable', 'numeric', 'min:0'],
            'channel_scope' => ['required', 'in:keep,global,store,ecommerce'],
        ], [
            'channel_scope.in' => 'Cakupan kanal tidak valid.',
            'discount_ids.required' => 'Pilih diskon yang akan disinkronkan.',
            'target_max_discount.min' => 'Max diskon tidak boleh negatif.',
        ]);

        $result = $sync->handle(
            $data['discount_ids'],
            isset($data['target_max_discount']) ? (float) $data['target_max_discount'] : null,
            (string) $data['channel_scope']
        );

        $message = $result['discounts'].' diskon disinkronkan';
        $message .= $result['mappings'] > 0
            ? " dan {$result['mappings']} pemetaan kanal diperbarui."
            : '.';

        return back()->with('success', $message);
    }
}
