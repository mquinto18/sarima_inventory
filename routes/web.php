<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/forecasting', function () {
    return view('pages.forecasting');
});

Route::get('/inventory', function () {
    return view('pages.inventory');
});

Route::get('/analytics', function () {
    return view('pages.analytics');
});

Route::get('/settings', function () {
    return view('pages.settings');
});
