<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PartnerController extends Controller
{
    private const BILLING_CYCLES = [
        'monthly' => 'Bulanan',
        'weekly' => 'Mingguan',
        'biweekly' => 'Dua Mingguan',
    ];

    private const LATE_FEE_TYPES = [
        'percentage_per_day' => 'Persentase per Hari',
        'fixed_per_day' => 'Nominal per Hari',
        'percentage_per_month' => 'Persentase per Bulan',
        'fixed' => 'Nominal Tetap',
    ];

    public function __construct()
    {
        $this->middleware('permission:view master-data-partners')
            ->only(['index', 'store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $status = trim((string) $request->get('status', ''));
        $status = in_array($status, array_keys(Partner::STATUS_LABELS), true) ? $status : null;

        $items = Partner::query()
            ->when($q !== '', function ($w) use ($q) {
                $needle = mb_strtolower($q);
                $w->where(function ($sub) use ($needle) {
                    $sub->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(COALESCE(billing_cycle, \'\')) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(COALESCE(status, \'\')) LIKE ?', ["%{$needle}%"]);
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $statuses = collect(Partner::STATUS_LABELS)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
        $billingCycles = collect(self::BILLING_CYCLES)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
        $lateFeeTypes = collect(self::LATE_FEE_TYPES)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Mitra',
            'eyebrow' => 'Mitra / Partner',
            'description' => 'Kelola partner, limit kredit, siklus tagihan, dan status kerja sama.',
            'searchPlaceholder' => 'Cari partner...',
            'items' => $items,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'status_label', 'label' => 'Status'],
                ['key' => 'default_credit_limit', 'label' => 'Limit Kredit', 'align' => 'end'],
                ['key' => 'billing_cycle', 'label' => 'Siklus Tagihan'],
                ['key' => 'late_fee_type', 'label' => 'Denda'],
                ['key' => 'late_fee_rate', 'label' => 'Nilai Denda', 'align' => 'end'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Partner', 'required' => true],
                ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => $statuses],
                ['key' => 'default_credit_limit', 'label' => 'Limit Kredit', 'type' => 'number'],
                ['key' => 'billing_cycle', 'label' => 'Siklus Tagihan', 'type' => 'select', 'required' => true, 'options' => $billingCycles],
                ['key' => 'late_fee_type', 'label' => 'Tipe Denda', 'type' => 'select', 'required' => true, 'options' => $lateFeeTypes],
                ['key' => 'late_fee_rate', 'label' => 'Nilai Denda', 'type' => 'number'],
            ],
            'filters' => ['q' => $q, 'status' => $status],
            'extraFilters' => [
                ['key' => 'status', 'label' => 'Status', 'value' => $status, 'options' => $statuses],
            ],
            'urls' => [
                'index' => route('master-data.partners.index'),
                'store' => route('master-data.partners.store'),
                'update' => route('master-data.partners.update', ['partner' => '__ID__']),
                'destroy' => route('master-data.partners.destroy', ['partner' => '__ID__']),
            ],
        ]);

    }

    public function store(Request $request)
    {
        $validated = $this->validatePartner($request);

        Partner::create($validated);

        return redirect()
            ->route('master-data.partners.index')
            ->with('success', 'Mitra berhasil ditambahkan.');
    }

    public function update(Request $request, Partner $partner)
    {
        $validated = $this->validatePartner($request, $partner);

        $partner->update($validated);

        return redirect()
            ->route('master-data.partners.index')
            ->with('success', 'Mitra berhasil diperbarui.');
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();

        return redirect()
            ->route('master-data.partners.index')
            ->with('success', 'Mitra berhasil dihapus.');
    }

    private function validatePartner(Request $request, ?Partner $partner = null): array
    {
        $uniqueName = Rule::unique('partners', 'name');

        if ($partner) {
            $uniqueName->ignore($partner->getKey(), 'id');
        }

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueName,
            ],
            'status' => ['required', Rule::in(array_keys(Partner::STATUS_LABELS))],
            'default_credit_limit' => ['nullable', 'numeric', 'min:0'],
            'billing_cycle' => ['required', Rule::in(array_keys(self::BILLING_CYCLES))],
            'late_fee_type' => ['required', Rule::in(array_keys(self::LATE_FEE_TYPES))],
            'late_fee_rate' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
