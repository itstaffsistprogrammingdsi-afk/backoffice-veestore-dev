<?php

namespace App\Mail;

use App\Models\StoreOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StoreOrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StoreOrder $order,
        public string $status,
        public array $orderNumbers = [],
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->status) {
            'requested' => 'PO '.$this->order->order_number.' menunggu persetujuan',
            'approved' => 'PO '.$this->order->order_number.' disetujui',
            'partial_approved' => 'PO '.$this->order->order_number.' disetujui sebagian',
            'rejected' => 'PO '.$this->order->order_number.' ditolak',
            default => 'Pembaruan PO '.$this->order->order_number,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.store-order-status',
            with: [
                'order' => $this->order->loadMissing(['store', 'warehouse', 'details.product']),
                'status' => $this->status,
                'orderNumbers' => $this->orderNumbers,
                'backofficeUrl' => $this->status === 'requested'
                    ? route('operations.store-orders.index')
                    : null,
            ]
        );
    }
}
