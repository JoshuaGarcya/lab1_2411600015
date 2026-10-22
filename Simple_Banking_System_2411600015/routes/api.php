<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JSON endpoints
|--------------------------------------------------------------------------
| Loaded from routes/web.php under the "api" prefix and the "auth"
| middleware (session login), so JavaScript on our own pages can call
| them without tokens. Route names start with "api." (api.dashboard.stats).
*/

// GET /api/dashboard/stats -> total_accounts, total_money, total_transactions_today
Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
