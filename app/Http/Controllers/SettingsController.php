<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Settings control critical/low stock thresholds and the B2B
     * auto-replenishment safety caps - staff should never be able to view
     * or change these, even by guessing the URL.
     */
    private function denyStaff()
    {
        if (Auth::user()->role === 'staff') {
            abort(403);
        }
    }

    public function index()
    {
        $this->denyStaff();

        $settings = [
            'low_stock_threshold' => (int) Setting::get('low_stock_threshold'),
            'critical_stock_level' => (int) Setting::get('critical_stock_level'),
            'default_forecast_period' => (int) Setting::get('default_forecast_period'),
            'expiry_alert_days' => (int) Setting::get('expiry_alert_days'),
            'fast_moving_threshold' => (float) Setting::get('fast_moving_threshold'),
            'slow_moving_threshold' => (float) Setting::get('slow_moving_threshold'),
            'velocity_window_days' => (int) Setting::get('velocity_window_days'),
            'stp_enabled' => (bool) Setting::get('stp_enabled'),
            'stp_max_order_value' => (float) Setting::get('stp_max_order_value'),
            'stp_max_qty_per_product' => (int) Setting::get('stp_max_qty_per_product'),
            'default_lead_time_days' => (int) Setting::get('default_lead_time_days'),
            'default_markup_percent' => (float) Setting::get('default_markup_percent'),
        ];

        // Archived accounts are only listed for admins — managers can reach this
        // page, but user administration is admin-only (see AdminMiddleware).
        $archivedUsers = Auth::user()->role === 'admin'
            ? \App\Models\User::onlyTrashed()->orderByDesc('deleted_at')->get()
            : collect();

        $reorderCount = ProductController::getReorderCount();
        $reorderNotifications = ProductController::getReorderNotifications();
        $pendingApprovalCount = \App\Models\EditRequest::where('status', 'pending')->count();
        $notificationCount = $pendingApprovalCount + $reorderCount;

        return view('pages.settings', compact('settings', 'archivedUsers', 'reorderCount', 'reorderNotifications', 'pendingApprovalCount', 'notificationCount'));
    }

    public function update(Request $request)
    {
        $this->denyStaff();

        $validated = $request->validate([
            'critical_stock_level' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|gt:critical_stock_level',
            'default_forecast_period' => 'required|integer|min:1|max:24',
            'expiry_alert_days' => 'required|integer|min:1',
            'fast_moving_threshold' => 'required|numeric|min:0',
            'slow_moving_threshold' => 'required|numeric|min:0',
            'velocity_window_days' => 'required|integer|min:7|max:180',
            'stp_max_order_value' => 'required|numeric|min:0',
            'stp_max_qty_per_product' => 'required|integer|min:1',
            'default_lead_time_days' => 'required|integer|min:1',
            'default_markup_percent' => 'required|numeric|min:0',
        ]);

        Setting::set('critical_stock_level', $validated['critical_stock_level']);
        Setting::set('low_stock_threshold', $validated['low_stock_threshold']);
        Setting::set('default_forecast_period', $validated['default_forecast_period']);
        Setting::set('expiry_alert_days', $validated['expiry_alert_days']);
        Setting::set('fast_moving_threshold', $validated['fast_moving_threshold']);
        Setting::set('slow_moving_threshold', $validated['slow_moving_threshold']);
        Setting::set('velocity_window_days', $validated['velocity_window_days']);
        Setting::set('stp_enabled', $request->boolean('stp_enabled'));
        Setting::set('stp_max_order_value', $validated['stp_max_order_value']);
        Setting::set('stp_max_qty_per_product', $validated['stp_max_qty_per_product']);
        Setting::set('default_lead_time_days', $validated['default_lead_time_days']);
        Setting::set('default_markup_percent', $validated['default_markup_percent']);

        return redirect()->route('settings')->with('success', 'Settings updated successfully.');
    }
}
