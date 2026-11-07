<?php

namespace Tests\Unit;

use App\Services\StockBarrierEmailSchedule;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class StockBarrierEmailScheduleTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_instant_mode_has_no_periodic_slot(): void
    {
        $schedule = StockBarrierEmailSchedule::fromArray(['mode' => 'instant']);

        $this->assertTrue($schedule->isInstant());
        $this->assertNull($schedule->duePeriodKey(Carbon::parse('2026-11-07 23:59:00')));
    }

    public function test_daily_mode_is_due_after_the_configured_time(): void
    {
        $schedule = StockBarrierEmailSchedule::fromArray([
            'mode' => 'daily',
            'time' => '08:00',
        ]);

        $this->assertNull($schedule->duePeriodKey(Carbon::parse('2026-11-07 07:59:00')));
        $this->assertSame('2026-11-07', $schedule->duePeriodKey(Carbon::parse('2026-11-07 08:00:00')));
    }

    public function test_weekly_mode_is_due_on_the_configured_day_after_the_time(): void
    {
        $schedule = StockBarrierEmailSchedule::fromArray([
            'mode' => 'weekly',
            'time' => '09:30',
            'day_of_week' => 6,
        ]);

        $this->assertNull($schedule->duePeriodKey(Carbon::parse('2026-11-06 10:00:00')));
        $this->assertNull($schedule->duePeriodKey(Carbon::parse('2026-11-07 09:29:00')));
        $this->assertSame('2026-W45', $schedule->duePeriodKey(Carbon::parse('2026-11-07 09:30:00')));
        $this->assertSame('2026-W45', $schedule->duePeriodKey(Carbon::parse('2026-11-08 10:00:00')));
    }

    public function test_monthly_mode_is_due_on_the_configured_date_after_the_time(): void
    {
        $schedule = StockBarrierEmailSchedule::fromArray([
            'mode' => 'monthly',
            'time' => '10:00',
            'day_of_month' => 5,
        ]);

        $this->assertNull($schedule->duePeriodKey(Carbon::parse('2026-11-04 12:00:00')));
        $this->assertNull($schedule->duePeriodKey(Carbon::parse('2026-11-05 09:59:00')));
        $this->assertSame('2026-11', $schedule->duePeriodKey(Carbon::parse('2026-11-05 10:00:00')));
    }

    public function test_invalid_values_fall_back_to_defaults(): void
    {
        $schedule = StockBarrierEmailSchedule::fromArray([
            'mode' => 'yearly',
            'time' => '99:99',
            'day_of_week' => 99,
            'day_of_month' => 99,
        ]);

        $this->assertSame(StockBarrierEmailSchedule::MODE_INSTANT, $schedule->mode);
        $this->assertSame('08:00', $schedule->time);
        $this->assertSame(1, $schedule->dayOfWeek);
        $this->assertSame(1, $schedule->dayOfMonth);
    }
}
