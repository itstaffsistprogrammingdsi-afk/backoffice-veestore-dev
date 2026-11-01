<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MembershipBenefit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MembershipBenefitController extends Controller
{
     public function __construct()
    {
        $this->middleware('permission:view master-data-membership-benefits')->only(['index']);
        $this->middleware('permission:create master-data-membership-benefits')->only(['store']);
        $this->middleware('permission:update master-data-membership-benefits')->only(['update']);
        $this->middleware('permission:delete master-data-membership-benefits')->only(['destroy']);
    }
    public function index(Request $request)
    {
        $q            = trim((string) $request->get('q', ''));
        $membershipId = $request->get('membership_id');

        $benefits = MembershipBenefit::with('membership')
            ->when($membershipId, fn($qr) => $qr->where('membership_id', $membershipId))
            ->when($q, function ($qr) use ($q) {
                $qr->where(function ($w) use ($q) {
                    $w->where('name',  'ILIKE', "%{$q}%")
                        ->orWhere('type', 'ILIKE', "%{$q}%")
                        ->orWhere('value', 'ILIKE', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $memberships = Membership::orderBy('name')->get(['id', 'name']);

        return view('content.master-data.membership-benefits.index', compact(
            'benefits',
            'q',
            'memberships',
            'membershipId'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'membership_id' => ['required', 'string', 'exists:memberships,id'],
            'name'          => ['required', 'string', 'max:150'],
            'value'         => ['nullable', 'string', 'max:191'],
            'type'          => ['nullable', 'string', 'max:50'],
        ]);

        MembershipBenefit::create($data);

        return redirect()->route('master-data.membership-benefits.index')
            ->with('success', 'Benefit berhasil ditambahkan.');
    }

    public function update(Request $request, MembershipBenefit $membership_benefit)
    {
        $data = $request->validate([
            'membership_id' => ['required', 'string', 'exists:memberships,id'],
            'name'          => ['required', 'string', 'max:150'],
            'value'         => ['nullable', 'string', 'max:191'],
            'type'          => ['nullable', 'string', 'max:50'],
        ]);

        $membership_benefit->update($data);

        return redirect()->route('master-data.membership-benefits.index')
            ->with('success', 'Benefit berhasil diperbarui.');
    }

    public function destroy(MembershipBenefit $membership_benefit)
    {
        $membership_benefit->delete();
        return back()->with('success', 'Benefit berhasil dihapus.');
    }
}