<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            fn($qry) =>
            $qry->where('name', 'ILIKE', "%{$q}%")
        )
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('content.master-data.payment-methods.index', compact('methods', 'q'));
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