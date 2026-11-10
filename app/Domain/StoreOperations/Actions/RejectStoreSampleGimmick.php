<?php

namespace App\Domain\StoreOperations\Actions;

use App\Domain\StoreOperations\Support\SampleGimmickRequestStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectStoreSampleGimmick
{
    public function handle(
        string $requestId,
        string $reason,
        ?string $actorId,
        ?string $actorName
    ): array {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($requestId, $reason, $actorId, $actorName): array {
            $row = DB::table('app_settings')
                ->where('key', SampleGimmickRequestStore::requestKey($requestId))
                ->lockForUpdate()
                ->first(['key', 'value']);

            if (! $row) {
                throw ValidationException::withMessages([
                    'request' => 'Pengajuan sample & gimmick tidak ditemukan.',
                ]);
            }

            $request = SampleGimmickRequestStore::decode($row->value);

            if (($request['status'] ?? null) !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => 'Pengajuan ini sudah diproses dan tidak dapat ditolak lagi.',
                ]);
            }

            $reviewedAt = now()->toIso8601String();
            $history = is_array($request['history'] ?? null) ? $request['history'] : [];
            $history[] = [
                'action' => 'rejected',
                'at' => $reviewedAt,
                'by' => $actorId,
                'by_name' => $actorName,
                'note' => $reason,
            ];

            $request['status'] = 'rejected';
            $request['reviewed_by'] = $actorId;
            $request['reviewed_by_name'] = $actorName;
            $request['reviewed_at'] = $reviewedAt;
            $request['review_note'] = $reason;
            $request['rejection_reason'] = $reason;
            $request['history'] = $history;

            DB::table('app_settings')
                ->where('key', $row->key)
                ->update([
                    'value' => SampleGimmickRequestStore::encode($request),
                    'updated_at' => now(),
                ]);

            $keys = SampleGimmickRequestStore::reservationKeys($request);

            if ($keys !== []) {
                DB::table('app_settings')
                    ->whereIn('key', $keys)
                    ->whereRaw("value->>'request_id' = ?", [$requestId])
                    ->delete();
            }

            return [
                'request_number' => (string) ($request['request_number'] ?? $requestId),
            ];
        });
    }
}
