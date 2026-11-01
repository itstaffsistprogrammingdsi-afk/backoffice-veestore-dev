<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class CustomerLoyaltyPointTransactionRewardController extends Controller
{
    private const KEY = 'customer_loyalty_point_transaction_reward';

    public function edit()
    {
        $default = ['reward_per_transaction' => 10];
        $data = AppSetting::getValue(self::KEY, $default);

        return view('content.settings.app.customer-loyalty-point-transaction-reward', [
            'value' => $data,
        ]);
    }

    public function update(Request $request)
    {
        $payload = $request->validate([
            'reward_per_transaction' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        AppSetting::putValue(self::KEY, [
            'reward_per_transaction' => (int) $payload['reward_per_transaction'],
        ]);

        return redirect()
            ->route('settings.app.customer-loyalty-point-transaction-reward.edit')
            ->with('success', 'Pengaturan reward loyalty point berhasil diperbarui.');
    }
}
