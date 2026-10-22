<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

// GET /api/dashboard/stats -> total_accounts, total_money, total_transactions_today
Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
