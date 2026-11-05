<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAffiliatePointsRequest;
use App\Models\AppSetting;
use Inertia\Inertia;

class AffiliatePointsInviterController extends Controller
{
    private const KEY = 'affiliate_points_inviter';

    public function edit()
    {
        $data = AppSetting::getValue(self::KEY, ['points' => 25]);

        return Inertia::render('Settings/AffiliatePoints', [
            'title' => 'Poin Afiliasi - Pengundang',
            'description' => 'Jumlah poin yang diberikan kepada inviter per referral sukses.',
            'fieldLabel' => 'Reward per Invite (LP)',
            'points' => (int) ($data['points'] ?? 25),
            'updateUrl' => route('settings.app.affiliate-points.inviter.update'),
            'resetUrl' => route('settings.app.affiliate-points.inviter.edit'),
        ]);
    }

    public function update(UpdateAffiliatePointsRequest $request)
    {
        AppSetting::putValue(self::KEY, [
            'points' => (int) $request->validated('points'),
        ]);

        return redirect()
            ->route('settings.app.affiliate-points.inviter.edit')
            ->with('success', 'Pengaturan hadiah afiliasi (pengundang) berhasil diperbarui.');
    }
}
