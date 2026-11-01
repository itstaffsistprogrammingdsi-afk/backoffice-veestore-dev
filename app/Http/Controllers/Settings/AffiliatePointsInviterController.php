<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AffiliatePointsInviterController extends Controller
{
    private const KEY = 'affiliate_points_inviter';

    public function edit()
    {
        $default = ['points' => 25]; // default_value: "25 LP"
        $data = AppSetting::getValue(self::KEY, $default);

        return view('content.settings.app.affiliate-points-inviter', [
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
            ->route('settings.app.affiliate-points.inviter.edit')
            ->with('success', 'Pengaturan reward affiliate (inviter) berhasil diperbarui.');
    }
}
