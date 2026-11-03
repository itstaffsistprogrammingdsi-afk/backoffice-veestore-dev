<?php

namespace App\Domain\StoreOperations\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectStoreReturn
{
    public function handle(string $returnId, ?string $note): array
    {
        return DB::transaction(function () use ($returnId, $note): array {
            $return = DB::table('store_returns')
                ->where('id', $returnId)
                ->lockForUpdate()
                ->first();

            if (! $return) {
                throw ValidationException::withMessages([
                    'items' => 'Data retur tidak ditemukan.',
                ]);
            }

            $status = strtolower((string) $return->status);

            if (! in_array($status, ['draft', 'requested', 'pending_approval'], true)) {
                throw ValidationException::withMessages([
                    'items' => 'Retur ini tidak dapat ditolak pada status saat ini.',
                ]);
            }

            $approvedCount = DB::table('store_return_details')
                ->where('store_return_id', $returnId)
                ->where('status', 'approved')
                ->count();

            if ($approvedCount > 0) {
                throw ValidationException::withMessages([
                    'items' => 'Retur yang sudah memiliki barang disetujui tidak dapat ditolak seluruhnya.',
                ]);
            }

            DB::table('store_return_details')
                ->where('store_return_id', $returnId)
                ->where(function ($query): void {
                    $query->whereNull('status')->orWhere('status', 'in_review');
                })
                ->update([
                    'status' => 'rejected',
                    'updated_at' => now(),
                ]);

            $noteWarehouse = $note === null ? $return->note_warehouse : trim($note);

            DB::table('store_returns')
                ->where('id', $returnId)
                ->update([
                    'status' => 'rejected',
                    'approved_at' => null,
                    'approved_by' => null,
                    'note_warehouse' => $noteWarehouse !== '' ? $noteWarehouse : null,
                    'rejected_at' => now(),
                    'updated_at' => now(),
                ]);

            return [
                'return_number' => $return->return_number,
            ];
        });
    }
}
