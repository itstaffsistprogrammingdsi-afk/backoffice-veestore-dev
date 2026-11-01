<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AffiliatePointsInviteeController extends Controller
{
    private const KEY = 'affiliate_points_invitee';

    public function edit()
    {
        $default = ['points' => 10]; // default_value: "10 LP"
        $data = AppSetting::getValue(self::KEY, $default);

        return view('content.settings.app.affiliate-points-invitee', [
            'value' => $data,
        ]);
    }

    public function update(Request $request)
    {
        $payload = $request->validate([
            'points' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        AppSetting::putValue(self::KEY, [
            'points' => (int) $payload['points'],
        ]);

        return redirect()
            ->route('settings.app.affiliate-points.invitee.edit')
            ->with('success', 'Pengaturan reward affiliate (invitee) berhasil diperbarui.');
    }
}
