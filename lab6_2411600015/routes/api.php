<?php

use App\Http\Controllers\Api\DashboardApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JSON endpoints for the live dashboard and forms
|--------------------------------------------------------------------------
| Loaded from routes/web.php under the "api" prefix and the "auth"
| middleware, so the browser's session cookie authenticates AJAX calls.
| Route names are prefixed with "api." (e.g. api.dashboard.stats).
*/

Route::get('dashboard/stats', [DashboardApiController::class, 'stats'])->name('dashboard.stats');
Route::get('courses/low-allowance', [DashboardApiController::class, 'lowAllowance'])->name('courses.low-allowance');
Route::get('courses/{course}/allowance', [DashboardApiController::class, 'allowance'])->name('courses.allowance');
Route::get('transactions/recent', [DashboardApiController::class, 'recentTransactions'])->name('transactions.recent');
