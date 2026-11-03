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
        return new Envelope(subject: 'Peringatan stok mencapai barrier');
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.stock-barrier-alert',
            with: ['payload' => $this->payload]
        );
    }
}
