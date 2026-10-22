<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/bank');

// Login / register (only for guests)
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
});

// Everything below needs a logged-in user
Route::middleware('auth')->group(function () {

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard page (the numbers load from /api/dashboard/stats)
    Route::view('bank', 'bank.dashboard')->name('bank.dashboard');

    // Deposits and withdrawals (no edit/delete: the balance must always match the ledger)
    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'create', 'store']);

    // Notifications (low-balance alerts)
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    // JSON endpoints for AJAX polling (see routes/api.php).
    // Loaded here, inside the "auth" group, so the browser's session cookie logs the AJAX call in.
    Route::prefix('api')->name('api.')->group(base_path('routes/api.php'));
});
