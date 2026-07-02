<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [],
        ];

        try {
            DB::connection()->getPdo();
            $checks['services']['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['services']['database'] = 'error';
            $checks['status'] = 'degraded';
        }

        // Always return 200 so platform health checks pass.
        // The 'status' field indicates degradation for monitoring.
        return response()->json($checks, 200);
    }
}
