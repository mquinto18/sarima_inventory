<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostmarkInboundController extends Controller
{
    /**
     * Postmark's documented webhook source IPs
     * (postmarkapp.com/support/article/800-ips-for-firewalls, checked Oct
     * 2026). Postmark does not cryptographically sign webhook payloads, so
     * this - not the ?token= alone - is what actually stops a forged
     * request that never passed through Postmark at all; the token only
     * stops someone who doesn't know it, not someone impersonating Postmark
     * from elsewhere. Overridable via POSTMARK_WEBHOOK_IPS (comma-separated)
     * if Postmark adds/changes IPs before this list is updated.
     */
    private const POSTMARK_WEBHOOK_IPS = [
        '3.134.147.250',
        '50.31.156.6',
        '50.31.156.77',
        '18.217.206.57',
    ];

    /**
     * Not enforced outside production: there's no way for a local/staging
     * request to genuinely originate from Postmark's real IPs, and those
     * environments are never reachable by Postmark's real servers anyway.
     */
    private function isFromPostmark(Request $request): bool
    {
        if (in_array(config('app.env'), ['local', 'testing'], true)) {
            return true;
        }

        $configured = config('services.postmark.webhook_ips');
        $allowed = $configured
            ? array_filter(array_map('trim', explode(',', $configured)))
            : self::POSTMARK_WEBHOOK_IPS;

        return in_array($request->ip(), $allowed, true);
    }

    /**
     * Plain-language phrases that mean the supplier is declining the order,
     * checked case-insensitively against only the NEW text they typed (see
     * StrippedTextReply below) - not exhaustive NLP, just enough to stop
     * "sorry, we can't fulfill this" from being silently recorded as an
     * approval.
     */
    private const DECLINE_PHRASES = [
        'decline', 'declining', 'cannot fulfill', "can't fulfill", 'unable to fulfill',
        'cannot supply', "can't supply", 'unable to supply', 'not able to fulfill',
        'reject', 'rejecting', "won't be able", 'will not be able',
        'cannot process this', "can't process this", 'out of stock', 'no longer available',
        'not available', 'cancel this order', 'cannot accept', "can't accept",
    ];

    /**
     * Phrases that mean the supplier is approving the order. Checked
     * alongside DECLINE_PHRASES so a reply containing *both* (e.g. "Approved,
     * but the 500ml bottles are no longer available, we'll substitute
     * 250ml") is recognized as mixed signal rather than guessed - see
     * classifyReply().
     */
    private const APPROVE_PHRASES = [
        'approve', 'approved', 'approving', 'confirm', 'confirmed', 'confirming',
        'accept', 'accepted', 'ok to proceed', 'okay to proceed', 'go ahead',
        'sounds good', 'will ship', 'will deliver', "we'll fulfill", 'can fulfill',
        'can supply', 'will supply',
    ];

    /**
     * true = clearly declining, false = clearly approving, null = can't tell
     * (no recognized phrase either way, or both appear - conflicting signal).
     * null is deliberately NOT treated as "assume approved": for an
     * irreversible action like confirming/cancelling a real purchase order,
     * an unparseable or self-contradictory reply should fall back to a human
     * reviewing it, not to a guess.
     */
    private function classifyReply(string $replyText): ?bool
    {
        $normalized = strtolower($replyText);

        $hasDecline = false;
        foreach (self::DECLINE_PHRASES as $phrase) {
            if (str_contains($normalized, $phrase)) {
                $hasDecline = true;
                break;
            }
        }

        $hasApprove = false;
        foreach (self::APPROVE_PHRASES as $phrase) {
            if (str_contains($normalized, $phrase)) {
                $hasApprove = true;
                break;
            }
        }

        if ($hasDecline && $hasApprove) {
            return null;
        }

        if ($hasDecline) {
            return true;
        }

        if ($hasApprove) {
            return false;
        }

        return null;
    }

    /**
     * Postmark posts every inbound email (replies included) here. We match
     * it back to a purchase order via the mailbox-hash token embedded in
     * the Reply-To address (see PurchaseOrderMail), require the sender to
     * match the supplier's registered email, then read the reply's actual
     * wording to tell an approval from a decline (see classifyReply()) - a
     * reply that's unparseable or contains both approve and decline phrases
     * is left as 'sent' for manual review rather than guessed either way.
     *
     * Always returns 200 for anything we intentionally ignore (unknown
     * token, already confirmed, sender mismatch) so Postmark doesn't
     * retry-storm on mail we're never going to act on.
     */
    public function handle(Request $request)
    {
        if (!$this->isFromPostmark($request)) {
            Log::warning("PostmarkInbound: rejected request from non-Postmark IP {$request->ip()}.");
            abort(403);
        }

        $expectedToken = config('services.postmark.inbound_webhook_token');

        // Fails closed, not open: an unset/blank expected token must never
        // match an absent ?token= (both would otherwise be "" === "").
        // hash_equals() instead of === avoids leaking a timing side-channel
        // on the comparison itself.
        if (!$expectedToken || !hash_equals($expectedToken, (string) $request->query('token'))) {
            abort(403);
        }

        $mailboxHash = (string) $request->input('MailboxHash', '');
        $fromEmail = (string) $request->input('FromFull.Email', $request->input('From', ''));
        $textBody = (string) $request->input('TextBody', '');
        // Postmark strips the quoted original message for us; without this,
        // checking $textBody for decline phrases would false-positive on
        // every reply, since the quoted PO email itself contains the words
        // "Decline Order" (the button's own text).
        $replyText = (string) $request->input('StrippedTextReply', $textBody);

        if ($mailboxHash === '') {
            Log::info('PostmarkInbound: ignored - no MailboxHash on inbound message.');
            return response()->json(['status' => 'ignored']);
        }

        $purchaseOrder = PurchaseOrder::with('supplier')
            ->where('confirmation_token', $mailboxHash)
            ->first();

        if (!$purchaseOrder) {
            Log::info("PostmarkInbound: ignored - no purchase order for token {$mailboxHash}.");
            return response()->json(['status' => 'ignored']);
        }

        if ($purchaseOrder->status !== 'sent') {
            Log::info("PostmarkInbound: ignored - PO {$purchaseOrder->po_number} is '{$purchaseOrder->status}', not 'sent'.");
            return response()->json(['status' => 'ignored']);
        }

        $supplierEmail = $purchaseOrder->supplier->email ?? '';

        if (!$supplierEmail || strcasecmp(trim($fromEmail), trim($supplierEmail)) !== 0) {
            Log::warning("PostmarkInbound: sender mismatch on PO {$purchaseOrder->po_number} - from '{$fromEmail}', expected '{$supplierEmail}'.");
            return response()->json(['status' => 'ignored']);
        }

        $classification = $this->classifyReply($replyText);
        $noteSource = $replyText !== '' ? $replyText : $textBody;

        if ($classification === null) {
            // Neither recognized, or both at once - don't guess on an
            // irreversible action. Left as 'sent' so an admin can resolve it
            // manually (via the Approve/Decline link or the "Mark as
            // Confirmed/Cancelled" override), with the actual reply visible
            // on the PO for context.
            $purchaseOrder->update(['confirmation_note' => Str::limit($noteSource, 2000)]);
            Log::warning("PostmarkInbound: PO {$purchaseOrder->po_number} reply couldn't be classified as approve/decline - left as 'sent' for manual review.");

            return response()->json(['status' => 'needs_review']);
        }

        $declined = $classification;

        $purchaseOrder->update([
            'status' => $declined ? 'cancelled' : 'confirmed',
            'confirmed_at' => $declined ? null : now(),
            'confirmation_note' => Str::limit($noteSource, 2000),
        ]);

        Log::info("PostmarkInbound: PO {$purchaseOrder->po_number} " . ($declined ? 'declined' : 'confirmed') . ' via supplier reply.');

        return response()->json(['status' => $declined ? 'declined' : 'confirmed']);
    }
}
