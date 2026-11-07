<?php

namespace App\Services;

use Carbon\CarbonInterface;

class StockBarrierEmailSchedule
{
    public const SETTINGS_KEY = 'stock_barrier_alerts.email_schedule';

    public const LAST_DIGEST_KEY = 'stock_barrier_alerts.last_email_digest';

    public const MODE_INSTANT = 'instant';

    public const MODE_DAILY = 'daily';

    public const MODE_WEEKLY = 'weekly';

    public const MODE_MONTHLY = 'monthly';

    private const DEFAULT_TIME = '08:00';

    public function __construct(
        public readonly string $mode,
        public readonly string $time,
        public readonly int $dayOfWeek,
        public readonly int $dayOfMonth,
    ) {}

    public static function modes(): array
    {
        return [
            self::MODE_INSTANT,
            self::MODE_DAILY,
            self::MODE_WEEKLY,
            self::MODE_MONTHLY,
        ];
    }

    public static function defaults(): array
    {
        return [
            'mode' => self::MODE_INSTANT,
            'time' => self::DEFAULT_TIME,
            'day_of_week' => 1,
            'day_of_month' => 1,
        ];
    }

    public static function fromArray(array $data): self
    {
        $defaults = self::defaults();
        $mode = is_string($data['mode'] ?? null) && in_array($data['mode'], self::modes(), true)
            ? $data['mode']
            : $defaults['mode'];

        $dayOfWeek = (int) ($data['day_of_week'] ?? $defaults['day_of_week']);
        $dayOfMonth = (int) ($data['day_of_month'] ?? $defaults['day_of_month']);

        return new self(
            mode: $mode,
            time: self::normalizeTime($data['time'] ?? $defaults['time']),
            dayOfWeek: $dayOfWeek >= 1 && $dayOfWeek <= 7 ? $dayOfWeek : $defaults['day_of_week'],
            dayOfMonth: $dayOfMonth >= 1 && $dayOfMonth <= 28 ? $dayOfMonth : $defaults['day_of_month'],
        );
    }

    public static function current(): self
    {
        $raw = Settings::get(self::SETTINGS_KEY, self::defaults());

        return self::fromArray(is_array($raw) ? $raw : []);
    }

    public function isInstant(): bool
    {
        return $this->mode === self::MODE_INSTANT;
    }

    /**
     * The period key of the scheduled slot that has already started at $now.
     * Returns null while the configured slot is still ahead in the current period.
     */
    public function duePeriodKey(CarbonInterface $now): ?string
    {
        return match ($this->mode) {
            self::MODE_DAILY => $this->dailyPeriodKey($now),
            self::MODE_WEEKLY => $this->weeklyPeriodKey($now),
            self::MODE_MONTHLY => $this->monthlyPeriodKey($now),
            default => null,
        };
    }

    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'time' => $this->time,
            'day_of_week' => $this->dayOfWeek,
            'day_of_month' => $this->dayOfMonth,
        ];
    }

    private function dailyPeriodKey(CarbonInterface $now): ?string
    {
        $scheduled = $this->atConfiguredTime($now);

        return $now->greaterThanOrEqualTo($scheduled) ? $now->format('Y-m-d') : null;
    }

    private function weeklyPeriodKey(CarbonInterface $now): ?string
    {
        $scheduled = $this->atConfiguredTime(
            $now->copy()->startOfWeek(CarbonInterface::MONDAY)->addDays($this->dayOfWeek - 1)
        );

        return $now->greaterThanOrEqualTo($scheduled) ? $scheduled->format('o-\WW') : null;
    }

    private function monthlyPeriodKey(CarbonInterface $now): ?string
    {
        $scheduled = $this->atConfiguredTime($now->copy()->day($this->dayOfMonth));

        return $now->greaterThanOrEqualTo($scheduled) ? $now->format('Y-m') : null;
    }

    private function atConfiguredTime(CarbonInterface $date): CarbonInterface
    {
        [$hour, $minute] = array_map('intval', explode(':', $this->time));

        return $date->copy()->setTime($hour, $minute);
    }

    private static function normalizeTime(mixed $value): string
    {
        if (is_string($value) && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1) {
            return $value;
        }

        return self::DEFAULT_TIME;
    }
}
