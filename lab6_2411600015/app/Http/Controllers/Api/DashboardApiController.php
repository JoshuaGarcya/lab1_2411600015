<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceTransactionResource;
use App\Http\Resources\CourseResource;
use App\Models\AttendanceTransaction;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    // GET /api/dashboard/stats
    public function stats(Request $request): JsonResponse
    {
        $allowed = (int) Course::sum('absences_allowed');
        $remaining = (int) Course::sum('absences_remaining');

        return response()->json([
            'data' => [
                'gpa' => round(Course::avg('grade') ?? 0, 2),
                'total_courses' => Course::count(),
                'at_risk' => Course::where('grade', '<', 3.0)->where('grade', '>=', 2.0)->count(),
                'avg_attendance' => (int) round(Course::avg('attendance_rate') ?? 0),
                'low_allowance' => Course::lowAllowance()->count(),
                'limit_reached' => Course::limitReached()->count(),
                'absences_used' => max(0, $allowed - $remaining),
                'logged_today' => AttendanceTransaction::whereDate('created_at', today())->count(),
                'unread_notifications' => $request->user()->unreadNotifications()->count(),
                'attendance_by_course' => Course::select('course_code', 'course_name', 'attendance_rate')
                    ->orderBy('attendance_rate')
                    ->get(),
                'updated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    // GET /api/courses/low-allowance
    public function lowAllowance()
    {
        return CourseResource::collection(
            Course::lowAllowance()->orderBy('absences_remaining')->orderBy('course_code')->get()
        );
    }

    // GET /api/transactions/recent
    public function recentTransactions()
    {
        return AttendanceTransactionResource::collection(
            AttendanceTransaction::with(['course', 'user'])->recent()->limit(8)->get()
        );
    }

    // GET /api/courses/{course}/allowance  (used by the forms)
    public function allowance(Course $course): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $course->id,
                'course_code' => $course->course_code,
                'remaining' => $course->absences_remaining,
                'allowed' => $course->absences_allowed,
                'used' => $course->absences_used,
                'status' => $course->allowance_status,
            ],
        ]);
    }
}
