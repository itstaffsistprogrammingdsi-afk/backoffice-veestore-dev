<?php

namespace App\Services;

use App\Mail\BirthdayGreetingMail;
use App\Models\Customer;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BirthdayGreetingService
{
    private const MONTHS = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function template(): array
    {
        return [
            'subject' => 'Selamat Ulang Tahun, {nama}!',
            'message' => "Halo {nama},\n\nSelamat ulang tahun dari VeeStore! Terima kasih sudah menjadi bagian dari perjalanan kami.\n\nSemoga tahun ini membawa banyak hal baik untuk Anda dan keluarga.\n\nSalam hangat,\nTim VeeStore",
            'placeholders' => ['{nama}', '{email}', '{bulan}'],
        ];
    }

    public function send(array $customerIds, string $subject, string $message): array
    {
        $uniqueIds = array_values(array_unique($customerIds));

        $customers = Customer::query()
            ->whereIn('id', $uniqueIds)
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->get();

        $result = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => count($uniqueIds) - $customers->count(),
        ];

        foreach ($customers as $customer) {
            try {
                Mail::to($customer->email)->send(new BirthdayGreetingMail(
                    mailSubject: $this->personalize($subject, $customer),
                    mailBody: $this->personalize($message, $customer),
                ));

                $result['sent']++;
            } catch (Throwable $exception) {
                report($exception);
                $result['failed']++;
            }
        }

        return $result;
    }

    public function personalize(string $text, Customer $customer): string
    {
        return str_replace(
            ['{nama}', '{email}', '{bulan}'],
            [
                $customer->name ?: 'Customer',
                $customer->email ?: '',
                $this->monthLabel($customer->date_of_birth?->month ?? (int) now()->month),
            ],
            $text
        );
    }

    public function summaryMessage(array $result): string
    {
        $parts = [];

        if ($result['sent'] > 0) {
            $parts[] = "{$result['sent']} email terkirim";
        }

        if ($result['failed'] > 0) {
            $parts[] = "{$result['failed']} email gagal dikirim";
        }

        if ($result['skipped'] > 0) {
            $parts[] = "{$result['skipped']} customer tanpa email dilewati";
        }

        return $parts === []
            ? 'Tidak ada email yang dikirim.'
            : 'Sapaan ulang tahun: '.implode(', ', $parts).'.';
    }

    private function monthLabel(int $month): string
    {
        return self::MONTHS[max(0, min(11, $month - 1))];
    }
}
