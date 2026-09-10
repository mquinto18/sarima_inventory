<?php

namespace App\Http\Controllers;

use App\Models\EditRequest;
use App\Models\PurchaseOrderReceipt;
use App\Models\Sale;
use App\Models\User;
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
     */
    private function applyDateFilter($query, ?string $from, ?string $to, string $column)
    {
        if ($from) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query->whereDate($column, '<=', $to);
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
     * Supplier deliveries — one row per quantity actually received against a
     * purchase order line.
     */
    public function deliveries(Request $request)
    {
        $this->denyStaff();

        [$from, $to, $dateError] = $this->dateRange($request);

        $query = PurchaseOrderReceipt::query()
            ->with(['purchaseOrder.supplier', 'product', 'receivedBy'])
            ->orderByDesc('received_at')
            ->orderByDesc('id');

        if (!$dateError) {
            $this->applyDateFilter($query, $from, $to, 'received_at');
        }

        return view('pages.logs.deliveries', array_merge($this->shellData(), [
            'receipts' => $query->paginate(25)->withQueryString(),
            'from' => $from,
            'to' => $to,
            'dateError' => $dateError,
        ]));
    }
}
