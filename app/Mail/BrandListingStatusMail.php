<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BrandListingStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $payload,
        public string $status,
    ) {}

    public function envelope(): Envelope
    {
        $brand = (string) ($this->payload['brand_name'] ?? 'Brand');

        $subject = match ($this->status) {
            'submitted' => 'Pengajuan listing brand: '.$brand,
            'approved' => 'Listing brand disetujui: '.$brand,
            'rejected' => 'Listing brand ditolak: '.$brand,
            default => 'Pembaruan listing brand: '.$brand,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.brand-listing-status',
            with: [
                'payload' => $this->payload,
                'status' => $this->status,
            ]
        );
    }
}
