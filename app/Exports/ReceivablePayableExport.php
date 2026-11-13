<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReceivablePayableExport implements FromArray, ShouldAutoSize, WithTitle
{
    public function __construct(protected array $entries) {}

    public function title(): string
    {
        return 'Piutang dan Hutang';
    }

    public function array(): array
    {
        $rows = [
            ['PIUTANG & HUTANG (CATATAN MANUAL)'],
            ['Diekspor', now()->format('d-m-Y H:i:s')],
            ['Catatan', 'Pencatatan manual, tidak terhubung otomatis ke invoice atau pembayaran.'],
            [],
            ['Jenis', 'Pihak', 'Referensi', 'Nominal', 'Jatuh Tempo', 'Status', 'Catatan', 'Dibuat Oleh', 'Dibuat Pada', 'Lunas Pada'],
        ];

        foreach ($this->entries as $entry) {
            $rows[] = [
                ($entry['type'] ?? '') === 'receivable' ? 'Piutang' : 'Hutang',
                $entry['party'] ?? '-',
                $entry['reference'] ?? '-',
                (float) ($entry['amount'] ?? 0),
                $entry['due_date'] ?? '-',
                ($entry['status'] ?? 'open') === 'paid' ? 'Lunas' : 'Terbuka',
                $entry['notes'] ?? '-',
                $entry['created_by'] ?? '-',
                $entry['created_at'] ?? '-',
                $entry['paid_at'] ?? '-',
            ];
        }

        return $rows;
    }
}
