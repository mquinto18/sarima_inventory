<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public PurchaseOrder $purchaseOrder;

    /**
     * Create a new message instance.
     */
    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Purchase Order {$this->purchaseOrder->po_number} from Larios Pharmacy",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase_order',
            with: [
                'purchaseOrder' => $this->purchaseOrder,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if (!$this->purchaseOrder->pdf_path) {
            return [];
        }

        return [
            // Use Storage::path() rather than a hardcoded storage_path('app/...')
            // because Laravel 12's default 'local' disk root is
            // storage_path('app/private'), not storage_path('app').
            Attachment::fromPath(Storage::path($this->purchaseOrder->pdf_path))
                ->as($this->purchaseOrder->po_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
