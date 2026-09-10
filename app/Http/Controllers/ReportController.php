<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Combined Forecasting + Analytics report.
 *
 * The charts are Chart.js canvases that only exist in the browser, and a
 * combined report needs charts from two pages that are never open at the same
 * time. So generating a report is two steps:
 *
 *   1. builder()  - a page that draws all four charts offscreen, captures them
 *                   as PNGs and posts them back.
 *   2. generate() - embeds those PNGs alongside the tables and streams the PDF.
 *
 * Both pages' data is gathered once in step 1 and cached under a token, so the
 * tables in the PDF are the same snapshot the captured charts were drawn from,
 * and the expensive gather (SARIMA + per-product queries) only runs once.
 */
class ReportController extends Controller
{
    private const SNAPSHOT_TTL_MINUTES = 15;

    private function denyStaff(): void
    {
        if (Auth::user()->role === 'staff') {
            abort(403);
        }
    }

    /**
     * Reuse the page controllers verbatim so the report can never drift from
     * what Forecasting and Analytics actually display.
     */
    private function gather(): array
    {
        return [
            'forecasting' => app(SalesController::class)->index()->getData(),
            'analytics' => app(AnalyticsController::class)->index()->getData(),
        ];
    }

    /** Scoped to the user so one account cannot pull another's snapshot. */
    private function snapshotKey(string $token): string
    {
        return 'report:' . Auth::id() . ':' . $token;
    }

    /**
     * Chart datasets for the in-page report drawer.
     *
     * The drawer opens on whichever page the user is already on, but a combined
     * report needs charts from both Forecasting and Analytics — and neither page
     * holds the other's data. So the drawer fetches all four datasets here,
     * draws them offscreen, and posts the rasterised charts back to generate().
     * The full snapshot is cached under the returned token so the PDF's tables
     * match the charts exactly, and the expensive gather runs only once.
     */
    public function datasets()
    {
        $this->denyStaff();

        $data = $this->gather();
        $token = (string) Str::uuid();
        Cache::put($this->snapshotKey($token), $data, now()->addMinutes(self::SNAPSHOT_TTL_MINUTES));

        $categoryRevenue = $data['analytics']['categoryRevenue'] ?? collect();

        return response()->json([
            'token' => $token,
            'salesTrend' => $data['forecasting']['salesTrend'] ?? ['months' => [], 'actual' => []],
            'forecast' => $data['forecasting']['forecast'] ?? ['predicted' => []],
            'trendMonths' => $data['analytics']['trendMonths'] ?? [],
            'trendRevenue' => $data['analytics']['trendRevenue'] ?? [],
            'categoryLabels' => $categoryRevenue->pluck('category')->all(),
            'categoryTotals' => $categoryRevenue->pluck('total')->all(),
        ]);
    }

    public function generate(Request $request)
    {
        $this->denyStaff();

        $validated = $request->validate([
            'token' => 'required|uuid',
            'charts' => 'nullable|array',
            'charts.*' => 'nullable|string',
        ]);

        $data = Cache::pull($this->snapshotKey($validated['token']));

        if (!$data) {
            // The drawer reads this over fetch(), so answer in JSON rather than
            // redirecting into a response the caller cannot render.
            return response()->json([
                'message' => 'That report snapshot expired before it could be built. Please generate the report again.',
            ], 410);
        }

        // Charts are optional: a page with no data renders no canvas, and the
        // report should still produce its tables rather than failing outright.
        $charts = collect($validated['charts'] ?? [])
            ->map(fn ($v) => $this->pngDataUriOrNull($v))
            ->filter()
            ->all();

        $pdf = Pdf::loadView('pdf.inventory_report', [
            'f' => $data['forecasting'],
            'a' => $data['analytics'],
            'charts' => $charts,
            'generatedAt' => now(),
            'generatedBy' => Auth::user(),
        ])->setPaper('a4', 'portrait');

        // Inline, not download(): the drawer reads this as a blob and shows it in
        // an iframe. The Download button then saves that same blob, so printing
        // and saving can never disagree with what was previewed.
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="sarima-report-' . now()->format('Y-m-d-His') . '.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * These strings are posted by the browser and go straight into the PDF, so
     * accept only a genuine base64 PNG data URI — verified by decoding it and
     * checking the PNG magic number, not by trusting the declared MIME type.
     */
    private function pngDataUriOrNull(?string $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        if (!preg_match('#^data:image/png;base64,([A-Za-z0-9+/]+={0,2})$#', $value, $m)) {
            return null;
        }

        $binary = base64_decode($m[1], true);

        return ($binary !== false && str_starts_with($binary, "\x89PNG\r\n\x1a\n")) ? $value : null;
    }
}
