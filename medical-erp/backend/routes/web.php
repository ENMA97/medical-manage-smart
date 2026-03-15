<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    $checks = [
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'services' => [],
    ];

    // Database
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $checks['services']['database'] = 'ok';
    } catch (\Exception $e) {
        $checks['services']['database'] = 'error';
        $checks['status'] = 'degraded';
    }

    // Redis
    try {
        \Illuminate\Support\Facades\Cache::store('redis')->put('health_check', true, 10);
        $checks['services']['redis'] = 'ok';
    } catch (\Exception $e) {
        $checks['services']['redis'] = 'error';
        $checks['status'] = 'degraded';
    }

    $httpStatus = $checks['status'] === 'ok' ? 200 : 503;
    return response()->json($checks, $httpStatus);
});
