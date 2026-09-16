<?php

namespace App\Http\Controllers;

use App\Models\Course;

class DashboardController extends Controller
{
    public function index()
    {
        // Total number of courses
        $totalCourses = Course::count();

        // Courses with grades from 2.00 to below 3.00
        $atRiskCourses = Course::where('grade', '<', 2.0)
            ->where('grade', '>=', 2.0)
            ->count();

        // Courses with grades below 2.00
        $failingCourses = Course::where('grade', '<', 2.0)
            ->count();

        // Courses with grades 3.00 and above
        $passingCourses = Course::where('grade', '>=', 3.0)
            ->count();

        // Average GPA
        $averageGrade = round(
            Course::avg('grade') ?? 0,
            2
        );

        // Average attendance
        $averageAttendance = round(
            Course::avg('attendance_rate') ?? 0
        );

        // Total units
        $totalUnits = Course::sum('units');

        // Get the 5 most recently added courses
        $recentCourses = Course::latest('created_at')
            ->take(5)
            ->get();

        // Course category summary for the chart
        $categorySummary = Course::selectRaw(
            'category, SUM(units) as total_units, COUNT(*) as count'
        )
            ->groupBy('category')
            ->get();

        // Course status statistics for the chart
        $statusStats = [
            'passing' => $passingCourses,
            'atRisk' => $atRiskCourses,
            'failing' => $failingCourses,
        ];

        // Courses that need attention
        $coursesNeedingAttention = Course::where(function ($query) {
            $query->where('grade', '<', 3.0)
                ->orWhere('attendance_rate', '<', 80);
        })->get();

        // Send all dashboard data to the Blade view
        return view('dashboard', compact(
            'totalCourses',
            'atRiskCourses',
            'failingCourses',
            'averageGrade',
            'averageAttendance',
            'totalUnits',
            'recentCourses',
            'categorySummary',
            'statusStats',
            'coursesNeedingAttention'
        ));
    }
}