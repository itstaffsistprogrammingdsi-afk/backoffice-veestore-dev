<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateTransactionRewardRequest;
use App\Models\AppSetting;
use Inertia\Inertia;

class CustomerLoyaltyPointTransactionRewardController extends Controller
{
    private const KEY = 'customer_loyalty_point_transaction_reward';

    public function edit()
    {
        $data = AppSetting::getValue(self::KEY, ['reward_per_transaction' => 10]);

        return Inertia::render('Settings/TransactionReward', [
            'reward' => (int) ($data['reward_per_transaction'] ?? 10),
            'updateUrl' => route('settings.app.customer-loyalty-point-transaction-reward.update'),
            'resetUrl' => route('settings.app.customer-loyalty-point-transaction-reward.edit'),
        ]);
    }

    public function update(UpdateTransactionRewardRequest $request)
    {
        AppSetting::putValue(self::KEY, [
            'reward_per_transaction' => (int) $request->validated('reward_per_transaction'),
        ]);

        return redirect()
            ->route('settings.app.customer-loyalty-point-transaction-reward.edit')
            ->with('success', 'Pengaturan hadiah poin loyalitas berhasil diperbarui.');
    }
}
