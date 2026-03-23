<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // In production, the SPA is served by nginx directly.
    // This route is a fallback if the request reaches Laravel.
    $spaIndex = public_path('app/index.html');
    if (file_exists($spaIndex)) {
        return response()->file($spaIndex);
    }
    return response()->json(['name' => 'ENMA Medical ERP', 'status' => 'running']);
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

    $httpStatus = $checks['status'] === 'ok' ? 200 : 503;
    return response()->json($checks, $httpStatus);
});
