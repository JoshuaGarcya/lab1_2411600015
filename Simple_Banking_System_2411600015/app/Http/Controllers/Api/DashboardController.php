<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/stats
     *
     * The route is inside the "auth" group, so a guest gets 401.
     * Administrators get totals for the whole bank; everybody else only
     * gets totals for their own accounts.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        // "Today" uses the app timezone (Asia/Manila)
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        return response()->json([
            'total_accounts' => Account::visibleTo($user)->count(),
            'total_money' => round((float) Account::visibleTo($user)->sum('balance'), 2),
            'total_transactions_today' => Transaction::visibleTo($user)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
        ]);
    }
}
