<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateStockBarrierAlertScheduleRequest;
use App\Services\Settings;
use App\Services\StockBarrierEmailSchedule;
use Inertia\Inertia;

class StockBarrierAlertSettingController extends Controller
{
    public function edit()
    {
        $raw = Settings::get(StockBarrierEmailSchedule::SETTINGS_KEY, []);
        $raw = is_array($raw) ? $raw : [];
        $schedule = StockBarrierEmailSchedule::fromArray($raw);

        return Inertia::render('Settings/StockBarrierAlerts', [
            'data' => [
                ...$schedule->toArray(),
                'updated_by' => $raw['updated_by'] ?? null,
                'updated_at' => $raw['updated_at'] ?? null,
            ],
            'modes' => [
                [
                    'value' => StockBarrierEmailSchedule::MODE_INSTANT,
                    'label' => 'Setiap ada SKU baru',
                    'description' => 'Email dikirim segera setelah ada SKU baru yang menyentuh barrier.',
                ],
                [
                    'value' => StockBarrierEmailSchedule::MODE_DAILY,
                    'label' => 'Setiap hari',
                    'description' => 'Ringkasan produk yang masih menyentuh barrier dikirim sekali sehari.',
                ],
                [
                    'value' => StockBarrierEmailSchedule::MODE_WEEKLY,
                    'label' => 'Setiap minggu',
                    'description' => 'Ringkasan produk yang masih menyentuh barrier dikirim sekali seminggu.',
                ],
                [
                    'value' => StockBarrierEmailSchedule::MODE_MONTHLY,
                    'label' => 'Setiap bulan',
                    'description' => 'Ringkasan produk yang masih menyentuh barrier dikirim sekali sebulan.',
                ],
            ],
            'daysOfWeek' => [
                ['value' => 1, 'label' => 'Senin'],
                ['value' => 2, 'label' => 'Selasa'],
                ['value' => 3, 'label' => 'Rabu'],
                ['value' => 4, 'label' => 'Kamis'],
                ['value' => 5, 'label' => 'Jumat'],
                ['value' => 6, 'label' => 'Sabtu'],
                ['value' => 7, 'label' => 'Minggu'],
            ],
            'updateUrl' => route('settings.stock-barrier-alerts.update'),
            'resetUrl' => route('settings.stock-barrier-alerts.edit'),
        ]);
    }

    public function update(UpdateStockBarrierAlertScheduleRequest $request)
    {
        $validated = $request->validated();
        $current = StockBarrierEmailSchedule::current();

        $settings = [
            'mode' => $validated['mode'],
            'time' => $validated['time'] ?? $current->time,
            'day_of_week' => (int) ($validated['day_of_week'] ?? $current->dayOfWeek),
            'day_of_month' => (int) ($validated['day_of_month'] ?? $current->dayOfMonth),
            'updated_by' => $request->user()?->name ?? 'system',
            'updated_at' => now()->toDateTimeString(),
        ];

        Settings::set(StockBarrierEmailSchedule::SETTINGS_KEY, $settings);

        $schedule = StockBarrierEmailSchedule::fromArray($settings);
        $periodKey = $schedule->duePeriodKey(now());

        if ($periodKey !== null) {
            Settings::set(StockBarrierEmailSchedule::LAST_DIGEST_KEY, [
                'period_key' => $periodKey,
                'handled_at' => now()->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('settings.stock-barrier-alerts.edit')
            ->with('success', 'Jadwal email notifikasi barrier berhasil diperbarui.');
    }
}
