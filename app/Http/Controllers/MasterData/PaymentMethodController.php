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

        $methods = PaymentMethod::when(
            $q,
            fn ($qry) => $qry->where('name', 'ILIKE', "%{$q}%")
        )
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Payment Methods',
            'description' => 'Kelola metode pembayaran.',
            'searchPlaceholder' => 'Cari metode pembayaran...',
            'items' => $methods,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Payment Method', 'required' => true],
            ],
            'filters' => ['q' => $q],
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
        ]);

        PaymentMethod::create($data);

        return redirect()->route('master-data.payment-methods.index')
            ->with('success', 'Payment Method berhasil ditambahkan.');
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
        ]);

        $payment_method->update($data);

        return redirect()->route('master-data.payment-methods.index')
            ->with('success', 'Payment Method berhasil diperbarui.');
    }

    public function destroy(PaymentMethod $payment_method)
    {
        $payment_method->delete();

        return back()->with('success', 'Payment Method berhasil dihapus.');
    }
}
