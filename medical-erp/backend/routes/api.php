<?php

use App\Http\Controllers\Api\CountyController;
use App\Http\Controllers\Api\RegionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('regions', RegionController::class);
Route::apiResource('counties', CountyController::class);
