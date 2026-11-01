<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePointSettingRequest;
use App\Services\Settings;
use Inertia\Inertia;

class PointSettingController extends Controller
{
    public function edit()
    {
        $raw = Settings::get('points.rate', []);

        /*
         * Backward compatible:
         * Kalau data lama masih seperti:
         * [
         *   'idr_base' => 1000,
         *   'points_per_base' => 1,
         *   'points_per_idr' => 0.001
         * ]
         *
         * Maka otomatis dipindahkan ke struktur baru bagian "in".
         */
        $oldIdrBase = (int) ($raw['idr_base'] ?? 1000);
        $oldPointsPerBase = (float) (
            $raw['points_per_base']
            ?? (($raw['points_per_idr'] ?? 0.001) * max($oldIdrBase, 1))
        );

        $defaults = [
            'in' => [
                'idr_base' => 1000,
                'points_per_base' => 1,
                'points_per_idr' => 0.001,
            ],
            'out' => [
                'points_base' => 1,
                'idr_per_base' => 1000,
                'idr_per_point' => 1000,
            ],
            'updated_by' => $raw['updated_by'] ?? null,
            'updated_at' => $raw['updated_at'] ?? null,
        ];

        $data = $defaults;

        if (isset($raw['in']) && is_array($raw['in'])) {
            $data['in'] = array_merge($defaults['in'], $raw['in']);
        } else {
            $data['in'] = [
                'idr_base' => max($oldIdrBase, 1),
                'points_per_base' => max($oldPointsPerBase, 0.000001),
                'points_per_idr' => max($oldPointsPerBase / max($oldIdrBase, 1), 0.000001),
            ];
        }

        if (isset($raw['out']) && is_array($raw['out'])) {
            $data['out'] = array_merge($defaults['out'], $raw['out']);
        }

        $data['in']['idr_base'] = max((int) ($data['in']['idr_base'] ?? 1000), 1);
        $data['in']['points_per_base'] = max((float) ($data['in']['points_per_base'] ?? 1), 0.000001);
        $data['in']['points_per_idr'] = $data['in']['points_per_base'] / $data['in']['idr_base'];

        $data['out']['points_base'] = max((float) ($data['out']['points_base'] ?? 1), 0.000001);
        $data['out']['idr_per_base'] = max((float) ($data['out']['idr_per_base'] ?? 1000), 1);
        $data['out']['idr_per_point'] = $data['out']['idr_per_base'] / $data['out']['points_base'];

        return Inertia::render('Settings/Points', [
            'data' => $data,
            'updateUrl' => route('settings.points.update'),
            'resetUrl' => route('settings.points.edit'),
        ]);
    }

    public function update(UpdatePointSettingRequest $request)
    {
        $validated = $request->validated();

        $inIdrBase = (int) $validated['in_idr_base'];
        $inPointsPerBase = (float) $validated['in_points_per_base'];
        $inPointsPerIdr = $inPointsPerBase / $inIdrBase;

        $outPointsBase = (float) $validated['out_points_base'];
        $outIdrPerBase = (float) $validated['out_idr_per_base'];
        $outIdrPerPoint = $outIdrPerBase / $outPointsBase;

        Settings::set('points.rate', [
            'in' => [
                'idr_base' => $inIdrBase,
                'points_per_base' => $inPointsPerBase,
                'points_per_idr' => $inPointsPerIdr,
            ],
            'out' => [
                'points_base' => $outPointsBase,
                'idr_per_base' => $outIdrPerBase,
                'idr_per_point' => $outIdrPerPoint,
            ],

            /*
             * Field lama tetap disimpan sementara agar kode lama
             * yang masih membaca points_per_idr tidak langsung error.
             */
            'idr_base' => $inIdrBase,
            'points_per_base' => $inPointsPerBase,
            'points_per_idr' => $inPointsPerIdr,

            'updated_by' => $request->user()?->name ?? 'system',
            'updated_at' => now()->toDateTimeString(),
        ]);

        return back()->with('success', 'Pengaturan kurs poin berhasil disimpan.');
    }
}
