<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    // Display current settings
    public function index()
    {
        $settings = Cache::get('sarima_settings', [
            'forecast_period' => 12,
            'confidence_level' => 0.95,
        ]);
        return view('pages.settings', compact('settings'));
    }

    // Update settings
    public function update(Request $request)
    {
        $validated = $request->validate([
            'forecast_period' => 'required|integer|min:1|max:36',
            'confidence_level' => 'required|numeric|min:0.8|max:0.99',
        ]);
        Cache::put('sarima_settings', $validated);
        return redirect()->back()->with('success', 'Settings updated!');
    }
}
