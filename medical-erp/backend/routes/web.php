<?php

use App\Http\Controllers\HealthController;
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

Route::get('/health', HealthController::class);
