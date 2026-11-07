<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class BirthdayGreetingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $mailBody,
        public ?array $voucher = null,
        public ?string $voucherImagePath = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.birthday-greeting-html',
            text: 'emails.birthday-greeting',
            with: [
                'mailBody' => $this->mailBody,
                'voucher' => $this->voucher,
            ]
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->voucherImagePath) {
            return [];
        }

        $disk = (string) config('voucher.image.disk', 'public');

        if (! Storage::disk($disk)->exists($this->voucherImagePath)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk($disk, $this->voucherImagePath)
                ->as('voucher-'.basename($this->voucherImagePath)),
        ];
    }
}
