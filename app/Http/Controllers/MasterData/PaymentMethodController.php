<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        // Sesuaikan dengan seeder permission di bawah
        $this->middleware('permission:view master-data-payment-methods')->only(['index']);
        $this->middleware('permission:create master-data-payment-methods')->only(['store']);
        $this->middleware('permission:update master-data-payment-methods')->only(['update']);
        $this->middleware('permission:delete master-data-payment-methods')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $type = trim((string) $request->get('type', ''));
        $type = in_array($type, array_keys(PaymentMethod::TYPE_LABELS), true) ? $type : null;

        $methods = PaymentMethod::when(
            $q,
            fn ($qry) => $qry->where('name', 'ILIKE', "%{$q}%")
        )
            ->when($type, fn ($qry) => $qry->where('type', $type))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $typeOptions = collect(PaymentMethod::TYPE_LABELS)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Metode Pembayaran',
            'description' => 'Kelola metode pembayaran yang dipakai transaksi store dan ecommerce.',
            'searchPlaceholder' => 'Cari metode pembayaran...',
            'items' => $methods,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'type_label', 'label' => 'Tipe'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Payment Method', 'required' => true],
                ['key' => 'type', 'label' => 'Tipe', 'type' => 'select', 'options' => $typeOptions, 'required' => true],
            ],
            'filters' => ['q' => $q, 'type' => $type],
            'extraFilters' => [
                ['key' => 'type', 'label' => 'Tipe', 'value' => $type, 'options' => $typeOptions],
            ],
            'urls' => [
                'index' => route('master-data.payment-methods.index'),
                'store' => route('master-data.payment-methods.store'),
                'update' => route('master-data.payment-methods.update', ['payment_method' => '__ID__']),
                'destroy' => route('master-data.payment-methods.destroy', ['payment_method' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:payment_methods,name'],
            'type' => ['nullable', 'string', Rule::in(array_keys(PaymentMethod::TYPE_LABELS))],
        ]);

        PaymentMethod::create($data);

        return redirect()->route('master-data.payment-methods.index')
            ->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, PaymentMethod $payment_method)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('payment_methods', 'name')->ignore($payment_method->id, 'id'),
            ],
            'type' => ['nullable', 'string', Rule::in(array_keys(PaymentMethod::TYPE_LABELS))],
        ]);

        $payment_method->update($data);

        return redirect()->route('master-data.payment-methods.index')
            ->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentMethod $payment_method)
    {
        $payment_method->delete();

        return back()->with('success', 'Metode pembayaran berhasil dihapus.');
    }
}
