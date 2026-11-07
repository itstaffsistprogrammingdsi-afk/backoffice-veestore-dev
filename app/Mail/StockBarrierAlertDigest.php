<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StockBarrierAlertDigest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function envelope(): Envelope
    {
        $mode = (string) ($this->payload['mode'] ?? 'instant');

        $subject = match ($mode) {
            'daily' => 'Ringkasan harian stok mencapai barrier',
            'weekly' => 'Ringkasan mingguan stok mencapai barrier',
            'monthly' => 'Ringkasan bulanan stok mencapai barrier',
            default => 'Peringatan stok mencapai barrier',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.stock-barrier-alert',
            with: ['payload' => $this->payload]
        );
    }
}
