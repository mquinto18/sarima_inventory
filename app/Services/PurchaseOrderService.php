<?php

namespace App\Services;

use App\Mail\PurchaseOrderMail;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PurchaseOrderService
{
    /**
     * Generate the PDF, issue a confirmation token embedded in the Reply-To
     * address (so the supplier's reply can be matched back to this PO by the
     * inbound mail webhook), email the supplier, and mark the PO as sent.
     * Throws on PDF/mail failure - the caller decides how to recover.
     */
    public function sendToSupplier(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->load('items.product', 'supplier');

        $pdf = Pdf::loadView('pdf.purchase_order', ['purchaseOrder' => $purchaseOrder]);
        $path = "purchase_orders/{$purchaseOrder->po_number}.pdf";
        Storage::put($path, $pdf->output());

        $purchaseOrder->update([
            'pdf_path' => $path,
            'confirmation_token' => Str::random(40),
        ]);

        Mail::to($purchaseOrder->supplier->email)->send(new PurchaseOrderMail($purchaseOrder));

        // The 'log'/'array' mailers never throw - they just write the
        // message to a log file instead of delivering it - so a misconfigured
        // MAIL_MAILER in a real environment would otherwise let every PO
        // silently flip to 'sent' with nothing ever reaching the supplier.
        if (!app()->environment('local', 'testing') && in_array(config('mail.default'), ['log', 'array'])) {
            throw new \RuntimeException(
                "Supplier email for {$purchaseOrder->po_number} was not delivered: MAIL_MAILER is '" . config('mail.default') . "' in this environment."
            );
        }

        $purchaseOrder->update(['status' => 'sent', 'sent_at' => now()]);
    }
}
