<?php

namespace Tests\Feature\Settings;

use App\Services\Settings;
use App\Services\StockBarrierEmailSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StockBarrierAlertSettingsTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_page_shows_the_saved_schedule(): void
    {
        Settings::set(StockBarrierEmailSchedule::SETTINGS_KEY, [
            'mode' => 'weekly',
            'time' => '09:15',
            'day_of_week' => 3,
            'day_of_month' => 12,
        ]);

        $this->actingAs($this->admin())
            ->get(route('settings.stock-barrier-alerts.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/StockBarrierAlerts')
                ->where('data.mode', 'weekly')
                ->where('data.time', '09:15')
                ->where('data.day_of_week', 3)
                ->has('modes', 4)
                ->has('daysOfWeek', 7));
    }

    public function test_schedule_can_be_updated(): void
    {
        $this->actingAs($this->admin())
            ->post(route('settings.stock-barrier-alerts.update'), [
                'mode' => 'monthly',
                'time' => '17:45',
                'day_of_month' => 20,
            ])
            ->assertRedirect(route('settings.stock-barrier-alerts.edit'))
            ->assertSessionHas('success');

        $saved = Settings::get(StockBarrierEmailSchedule::SETTINGS_KEY);

        $this->assertSame('monthly', $saved['mode']);
        $this->assertSame('17:45', $saved['time']);
        $this->assertSame(20, $saved['day_of_month']);
    }

    public function test_setting_a_passed_slot_waits_for_the_next_period(): void
    {
        Carbon::setTestNow('2026-11-07 15:00:00');

        $this->actingAs($this->admin())
            ->post(route('settings.stock-barrier-alerts.update'), [
                'mode' => 'daily',
                'time' => '08:00',
            ])
            ->assertRedirect(route('settings.stock-barrier-alerts.edit'));

        $lastDigest = Settings::get(StockBarrierEmailSchedule::LAST_DIGEST_KEY);

        $this->assertSame('2026-11-07', $lastDigest['period_key']);
    }

    public function test_schedule_validation_rejects_invalid_values(): void
    {
        $this->actingAs($this->admin())
            ->post(route('settings.stock-barrier-alerts.update'), [
                'mode' => 'weekly',
                'time' => '25:00',
                'day_of_week' => 8,
            ])
            ->assertSessionHasErrors(['time', 'day_of_week']);
    }
}
