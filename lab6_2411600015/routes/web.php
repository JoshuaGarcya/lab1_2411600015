<?php

use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceTransactionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::resource('courses', CourseController::class);
    Route::resource('assignments', AssignmentController::class);

    // Attendance transaction log (Lab 6)
    Route::resource('attendance-transactions', AttendanceTransactionController::class)
        ->parameters(['attendance-transactions' => 'transaction'])
        ->only(['index', 'create', 'store', 'show']);

    // Administrator-only actions
    Route::middleware('admin')->group(function () {
        Route::delete('attendance-transactions/{transaction}', [AttendanceTransactionController::class, 'destroy'])
            ->name('attendance-transactions.destroy');

        Route::get('adjustments/create', [AdjustmentController::class, 'create'])
            ->name('adjustments.create');

        Route::post('adjustments', [AdjustmentController::class, 'store'])
            ->name('adjustments.store');
    });

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Notifications
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.readAll');
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');

    // JSON endpoints for AJAX polling (see routes/api.php)
    Route::prefix('api')->name('api.')->group(base_path('routes/api.php'));

});

require __DIR__.'/auth.php';
