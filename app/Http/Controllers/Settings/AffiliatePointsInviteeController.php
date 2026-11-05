<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAffiliatePointsRequest;
use App\Models\AppSetting;
use Inertia\Inertia;

class AffiliatePointsInviteeController extends Controller
{
    private const KEY = 'affiliate_points_invitee';

    public function edit()
    {
        $data = AppSetting::getValue(self::KEY, ['points' => 10]);

        return Inertia::render('Settings/AffiliatePoints', [
            'title' => 'Poin Afiliasi - Undangan',
            'description' => 'Jumlah poin yang diterima invitee setelah referral berhasil.',
            'fieldLabel' => 'Reward untuk Invitee (LP)',
            'points' => (int) ($data['points'] ?? 10),
            'updateUrl' => route('settings.app.affiliate-points.invitee.update'),
            'resetUrl' => route('settings.app.affiliate-points.invitee.edit'),
        ]);
    }

    public function update(UpdateAffiliatePointsRequest $request)
    {
        AppSetting::putValue(self::KEY, [
            'points' => (int) $request->validated('points'),
        ]);

        return redirect()
            ->route('settings.app.affiliate-points.invitee.edit')
            ->with('success', 'Pengaturan hadiah afiliasi (undangan) berhasil diperbarui.');
    }
}
