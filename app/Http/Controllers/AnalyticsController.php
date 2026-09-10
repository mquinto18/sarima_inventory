<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $reorderCount = ProductController::getReorderCount();
        $reorderNotifications = ProductController::getReorderNotifications();

        $fastMovingProducts = self::getFastMovingProducts(10);
        $slowMovingProducts = self::getSlowMovingProducts(10);

        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // ---- KPI: Total sales this year ----
        $salesYtd = Sale::whereYear('sale_date', $now->year)->sum('total_amount');
        $salesLastYear = Sale::whereYear('sale_date', $now->year - 1)->sum('total_amount');
        $salesYoyChange = $salesLastYear > 0
            ? round((($salesYtd - $salesLastYear) / $salesLastYear) * 100, 1)
            : null;

        // ---- KPI: Forecast accuracy (reuses the same calculation as the dashboard) ----
        $forecastAccuracy = ProductController::calculateForecastAccuracy();

        // ---- KPI: Inventory turnover (units sold this month / average units on hand) ----
        $unitsSoldThisMonth = Sale::whereYear('sale_date', $now->year)
            ->whereMonth('sale_date', $now->month)
            ->sum('quantity_sold');
        $unitsSoldLastMonth = Sale::whereYear('sale_date', $lastMonth->year)
            ->whereMonth('sale_date', $lastMonth->month)
            ->sum('quantity_sold');
        $averageStock = max(1, Product::avg('stock'));
        $inventoryTurnover = round($unitsSoldThisMonth / $averageStock, 2);
        $inventoryTurnoverLastMonth = round($unitsSoldLastMonth / $averageStock, 2);

        // ---- Revenue this month vs last month (for the key metrics table) ----
        $revenueThisMonth = Sale::whereYear('sale_date', $now->year)->whereMonth('sale_date', $now->month)->sum('total_amount');
        $revenueLastMonth = Sale::whereYear('sale_date', $lastMonth->year)->whereMonth('sale_date', $lastMonth->month)->sum('total_amount');
        $revenueChange = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : null;

        // ---- Monthly revenue trend, last 6 months (for the chart) ----
        $trendMonths = [];
        $trendRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $trendMonths[] = $month->format('M Y');
            $trendRevenue[] = (float) Sale::whereYear('sale_date', $month->year)
                ->whereMonth('sale_date', $month->month)
                ->sum('total_amount');
        }

        // ---- Revenue by category, this year (for the chart) ----
        $categoryRevenue = Sale::join('products', 'sales.product_id', '=', 'products.id')
            ->whereYear('sales.sale_date', $now->year)
            ->select('products.category', DB::raw('SUM(sales.total_amount) as total'))
            ->groupBy('products.category')
            ->orderByDesc('total')
            ->get();

        // ---- Top selling product this year (for insights) ----
        $topProduct = Sale::join('products', 'sales.product_id', '=', 'products.id')
            ->whereYear('sales.sale_date', $now->year)
            ->select('products.name', DB::raw('SUM(sales.quantity_sold) as total_sold'))
            ->groupBy('products.name')
            ->orderByDesc('total_sold')
            ->first();

        // ---- Rule-based insights & recommendations, generated from real data ----
        $insights = [];
        if ($revenueChange !== null) {
            $insights[] = $revenueChange >= 0
                ? "Revenue is up {$revenueChange}% compared to last month."
                : "Revenue is down " . abs($revenueChange) . "% compared to last month.";
        } else {
            $insights[] = "Not enough revenue history yet to compare month-over-month.";
        }
        if ($topProduct) {
            $insights[] = "{$topProduct->name} is your top-selling product this year ({$topProduct->total_sold} units sold).";
        }
        $insights[] = $forecastAccuracy['accuracy_percentage'] > 0
            ? "Forecast accuracy is currently {$forecastAccuracy['accuracy_percentage']}% ({$forecastAccuracy['status']})."
            : "Forecast accuracy will appear once enough sales history has built up.";

        $recommendations = [];
        if ($reorderCount > 0) {
            $recommendations[] = "{$reorderCount} product" . ($reorderCount === 1 ? '' : 's') . " " . ($reorderCount === 1 ? 'is' : 'are') . " at or below its reorder point — check Inventory for details.";
        } else {
            $recommendations[] = "All products are currently above their reorder point.";
        }
        if ($categoryRevenue->count() > 0) {
            $topCategory = $categoryRevenue->first();
            $recommendations[] = "\"{$topCategory->category}\" is your strongest category this year — consider prioritizing stock for it.";
        }
        $recommendations[] = "Review products with no sales this month as candidates for reduced reorder quantities.";

        return view('pages.analytics', compact(
            'reorderCount',
            'reorderNotifications',
            'salesYtd',
            'salesYoyChange',
            'forecastAccuracy',
            'inventoryTurnover',
            'inventoryTurnoverLastMonth',
            'revenueThisMonth',
            'revenueLastMonth',
            'revenueChange',
            'unitsSoldThisMonth',
            'unitsSoldLastMonth',
            'trendMonths',
            'trendRevenue',
            'categoryRevenue',
            'insights',
            'recommendations',
            'fastMovingProducts',
            'slowMovingProducts'
        ));
    }

    /**
     * Units sold per product over the trailing velocity window (Settings ->
     * velocity_window_days), normalized to a units-per-week rate so fast vs.
     * slow movers can be compared regardless of window length.
     */
    public static function getProductVelocity()
    {
        $windowDays = (int) Setting::get('velocity_window_days', 30);

        return Sale::join('products', 'sales.product_id', '=', 'products.id')
            ->where('sale_date', '>=', now()->subDays($windowDays))
            ->select('products.id', 'products.name', DB::raw('SUM(sales.quantity_sold) as units_sold'))
            ->groupBy('products.id', 'products.name')
            ->get()
            ->map(function ($row) use ($windowDays) {
                $row->units_sold = (int) $row->units_sold;
                $row->units_per_week = round($row->units_sold / ($windowDays / 7), 2);
                return $row;
            });
    }

    /**
     * Products selling at or above the fast-moving threshold (units/week),
     * ranked fastest first.
     */
    public static function getFastMovingProducts($limit = null)
    {
        $threshold = (float) Setting::get('fast_moving_threshold', 20);

        $result = self::getProductVelocity()
            ->filter(fn ($p) => $p->units_per_week >= $threshold)
            ->sortByDesc('units_per_week')
            ->values();

        return $limit ? $result->take($limit) : $result;
    }

    /**
     * Products selling at or below the slow-moving threshold (units/week),
     * ranked slowest first. Also surfaces true dead stock: products with
     * stock on hand but zero sales at all in the velocity window.
     */
    public static function getSlowMovingProducts($limit = null)
    {
        $threshold = (float) Setting::get('slow_moving_threshold', 2);
        $velocity = self::getProductVelocity();

        $slow = $velocity
            ->filter(fn ($p) => $p->units_per_week <= $threshold)
            ->sortBy('units_per_week')
            ->values();

        $soldProductIds = $velocity->pluck('id')->unique();
        $deadStock = Product::where('stock', '>', 0)
            ->whereNotIn('id', $soldProductIds)
            ->get(['id', 'name', 'stock'])
            ->map(fn ($p) => (object) [
                'id' => $p->id,
                'name' => $p->name,
                'units_sold' => 0,
                'units_per_week' => 0,
            ]);

        $combined = $slow->concat($deadStock)->unique('id')->sortBy('units_per_week')->values();

        return $limit ? $combined->take($limit) : $combined;
    }
}
