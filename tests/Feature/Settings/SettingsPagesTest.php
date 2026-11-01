<?php

namespace Tests\Feature\Settings;

use App\Models\AppSetting;
use App\Services\Settings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class SettingsPagesTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_settings_pages_use_inertia(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('settings.points.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Settings/Points'));

        $this->actingAs($admin)
            ->get(route('settings.app.affiliate-points.inviter.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Settings/AffiliatePoints'));

        $this->actingAs($admin)
            ->get(route('settings.app.affiliate-points.invitee.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Settings/AffiliatePoints'));

        $this->actingAs($admin)
            ->get(route('settings.app.customer-loyalty-point-transaction-reward.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Settings/TransactionReward'));
    }

    public function test_affiliate_points_can_be_updated(): void
    {
        $this->actingAs($this->admin())
            ->post(route('settings.app.affiliate-points.inviter.update'), [
                'points' => 42,
            ])
            ->assertRedirect(route('settings.app.affiliate-points.inviter.edit'));

        $this->assertSame(42, AppSetting::getValue('affiliate_points_inviter')['points']);
    }

    public function test_point_rate_can_be_updated(): void
    {
        $this->actingAs($this->admin())
            ->post(route('settings.points.update'), [
                'in_idr_base' => 2000,
                'in_points_per_base' => 2,
                'out_points_base' => 1,
                'out_idr_per_base' => 1500,
            ])
            ->assertRedirect();

        $rate = Settings::get('points.rate');

        $this->assertSame(2000, $rate['in']['idr_base']);
        $this->assertSame(1500, $rate['out']['idr_per_base']);
    }
}
