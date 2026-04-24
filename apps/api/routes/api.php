<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\Api\ScheduleController;
use Illuminate\Support\Facades\Route;

// Temporary debug route — remove once data issue is resolved.
Route::get('/debug/schedules', [DebugController::class, 'schedules']);

Route::get('/schedules', [ScheduleController::class, 'index']);
Route::get('/schedules/{id}', [ScheduleController::class, 'show']);

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/dashboard/waste-weight', [DashboardController::class, 'wasteWeight']);
Route::get('/dashboard/transport', [DashboardController::class, 'transport']);
Route::get('/dashboard/sorting', [DashboardController::class, 'sorting']);
Route::get('/dashboard/top-wastes', [DashboardController::class, 'topWastes']);
Route::get('/dashboard/trend', [DashboardController::class, 'trend']);
