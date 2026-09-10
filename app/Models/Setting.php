<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Defaults used whenever a setting has never been saved.
     */
    public static function defaults(): array
    {
        return [
            'low_stock_threshold' => 10,
            'critical_stock_level' => 5,
            'default_forecast_period' => 6,
            'stp_enabled' => false,
            'stp_max_order_value' => 5000,
            'stp_max_qty_per_product' => 500,
            'expiry_alert_days' => 30,
            'fast_moving_threshold' => 20,
            'slow_moving_threshold' => 2,
            'velocity_window_days' => 30,
            'default_lead_time_days' => 7,
        ];
    }

    /**
     * Per-request memo. Settings are read inside per-product loops (the
     * inventory status resync, the dashboard stock bands), which otherwise
     * costs two queries per product on every page load.
     */
    private static array $cache = [];

    public static function get(string $key, $default = null)
    {
        if (!array_key_exists($key, static::$cache)) {
            static::$cache[$key] = static::query()->where('key', $key)->value('value');
        }

        $value = static::$cache[$key];

        if ($value !== null) {
            return $value;
        }

        return $default ?? static::defaults()[$key] ?? null;
    }

    public static function set(string $key, $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        static::$cache[$key] = $value;
    }

    /**
     * Drop the memo. Only needed by tests that write settings directly.
     */
    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
