<?php

namespace App\Http\Controllers;

use App\Models\EditRequest;
use App\Models\PurchaseOrderReceipt;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Read-only history of what actually happened: POS sales and supplier
 * deliveries. Nothing here mutates state.
 */
class TransactionLogController extends Controller
{
    /**
     * Same guard the rest of the app uses for non-staff pages (Suppliers,
     * Purchase Orders, Settings, Forecasting).
     */
    private function denyStaff(): void
    {
        if (Auth::user()->role === 'staff') {
            abort(403);
        }
    }

    /** Notification/badge data every page in the app shell needs. */
    private function shellData(): array
    {
        $reorderCount = ProductController::getReorderCount();
        $reorderNotifications = ProductController::getReorderNotifications();
        $pendingApprovalCount = EditRequest::where('status', 'pending')->count();

        return [
            'reorderCount' => $reorderCount,
            'reorderNotifications' => $reorderNotifications,
            'pendingApprovalCount' => $pendingApprovalCount,
            'notificationCount' => $pendingApprovalCount + $reorderCount,
        ];
    }

    /**
     * Validate the from/to range.
     *
     * These arrive as GET params, so they can be hand-edited in the URL and must
     * be checked server-side even though the form also constrains them. On
     * failure the filter is not applied and the message is rendered next to the
     * inputs — deliberately not a redirect, which on a GET filter form either
     * loses what the user typed or bounces them somewhere unexpected.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string} [from, to, error]
     */
    private function dateRange(Request $request): array
    {
        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;

        $validator = Validator::make(
            ['from' => $from, 'to' => $to],
            [
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from',
            ],
            [
                'from.date' => 'The From date is not a valid date.',
                'to.date' => 'The To date is not a valid date.',
                'to.after_or_equal' => 'The To date must be on or after the From date.',
            ]
        );

        if ($validator->fails()) {
            // Keep the entered values so the inputs still show what was typed.
            return [$from, $to, $validator->errors()->first()];
        }

        return [$from, $to, null];
    }

    /**
     * Constrain a query to a validated from/to range on the given column.
     *
     * The picker's dates are Asia/Manila calendar days - what "today" means
     * to whoever is filtering - but every timestamp is stored in UTC
     * (config/app.php). whereDate() compares the raw UTC date, which is 8
     * hours behind: a sale at 2am Manila time is still "yesterday" in UTC,
     * so it would silently fall outside a filter for the Manila day it
     * actually happened on (or inside the wrong one). Converting each
     * boundary to the real UTC instant that Manila day starts/ends at fixes
     * that; whereDate() on the bare string cannot express it.
     */
    private function applyDateFilter($query, ?string $from, ?string $to, string $column)
    {
        if ($from) {
            $query->where($column, '>=', Carbon::parse($from, 'Asia/Manila')->startOfDay()->setTimezone('UTC'));
        }

        if ($to) {
            $query->where($column, '<=', Carbon::parse($to, 'Asia/Manila')->endOfDay()->setTimezone('UTC'));
        }

        return $query;
    }

    /**
     * POS transactions, reconstructed by grouping sales on pos_transaction_id.
     *
     * amount_tendered/change_due are written identically to every line of a
     * transaction, so MAX() reads them back exactly rather than approximating.
     * Legacy sales (no pos_transaction_id) are excluded: they predate the POS and
     * are not transactions.
     */
    public function pos(Request $request)
    {
        $this->denyStaff();

        [$from, $to, $dateError] = $this->dateRange($request);

        $query = Sale::query()
            ->whereNotNull('pos_transaction_id')
            ->groupBy('pos_transaction_id')
            ->select([
                'pos_transaction_id',
                DB::raw('MIN(created_at) as occurred_at'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('MAX(amount_tendered) as amount_tendered'),
                DB::raw('MAX(change_due) as change_due'),
                DB::raw('SUM(quantity_sold) as units'),
                DB::raw('COUNT(*) as line_count'),
                DB::raw('MAX(user_id) as user_id'),
            ])
            ->orderByDesc(DB::raw('MIN(created_at)'));

        // An invalid range is reported rather than silently applied.
        if (!$dateError) {
            $this->applyDateFilter($query, $from, $to, 'created_at');
        }

        $transactions = $query->paginate(25)->withQueryString();

        // One lookup for every cashier on the page instead of a query per row.
        $cashiers = User::withTrashed()
            ->whereIn('id', $transactions->pluck('user_id')->filter()->unique())
            ->get(['id', 'name'])
            ->keyBy('id');

        return view('pages.logs.pos', array_merge($this->shellData(), [
            'transactions' => $transactions,
            'cashiers' => $cashiers,
            'from' => $from,
            'to' => $to,
            'dateError' => $dateError,
        ]));
    }

    /**
     * Supplier deliveries, batched: receiveDelivery() writes one
     * PurchaseOrderReceipt row per product line, but all of them in the same
     * admin action share the same (purchase_order_id, received_at,
     * received_by) - that tuple is this page's definition of "one delivery",
     * so a batch of 4 products received together shows as a single row
     * listing all 4, not 4 separate rows for the same delivery.
     */
    public function deliveries(Request $request)
    {
        $this->denyStaff();

        [$from, $to, $dateError] = $this->dateRange($request);

        $batchQuery = PurchaseOrderReceipt::query()
            ->groupBy('purchase_order_id', 'received_at', 'received_by')
            ->select([
                'purchase_order_id',
                'received_at',
                'received_by',
                DB::raw('SUM(quantity_received) as total_quantity'),
                DB::raw('COUNT(*) as line_count'),
            ])
            ->orderByDesc('received_at');

        if (!$dateError) {
            $this->applyDateFilter($batchQuery, $from, $to, 'received_at');
        }

        $batches = $batchQuery->paginate(25)->withQueryString();

        // One query for every line belonging to this page's batches, grouped
        // back into the same tuples - the same "batch lookup" shape as the
        // cashier lookup in pos(), just keyed on the composite batch instead
        // of a single id.
        $lines = collect();

        if ($batches->isNotEmpty()) {
            $lines = PurchaseOrderReceipt::with(['purchaseOrder.supplier', 'product', 'receivedBy'])
                ->where(function ($query) use ($batches) {
                    foreach ($batches as $batch) {
                        $query->orWhere(function ($q) use ($batch) {
                            $q->where('purchase_order_id', $batch->purchase_order_id)
                                ->where('received_at', $batch->received_at)
                                ->where('received_by', $batch->received_by);
                        });
                    }
                })
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($r) => $r->purchase_order_id . '|' . $r->received_at . '|' . $r->received_by);
        }

        return view('pages.logs.deliveries', array_merge($this->shellData(), [
            'batches' => $batches,
            'lines' => $lines,
            'from' => $from,
            'to' => $to,
            'dateError' => $dateError,
        ]));
    }
}
