<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
public function index(Request $request)
{
    $query = Course::query();

    // Category filter
    if ($request->filled('category')) {
        $query->where('category', $request->category);
    }

    // Search filter
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('course_code', 'like', "%{$search}%")
              ->orWhere('course_name', 'like', "%{$search}%")
              ->orWhere('instructor', 'like', "%{$search}%");
        });
    }

    $courses = $query->latest()->get();

    return view('courses.index', compact('courses'));
}

    public function create()
    {
        return view('courses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_code' => 'required|string|unique:courses,course_code',
            'course_name' => 'required|string|max:255',
            'category' => 'required|in:Major,Minor,Gen Ed,Elective',
            'units' => 'required|integer|min:1|max:6',
            'grade' => 'required|numeric|min:0|max:4',
            'instructor' => 'required|string|max:255',
            'attendance_rate' => 'required|integer|min:0|max:100',
        ]);

        $course = Course::create($validated);
        $course->syncAllowanceFromAttendanceRate();

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course added successfully!');
    }

    public function show(Request $request, Course $course)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        // Oldest first so we can walk the ledger and compute a running balance
        $history = $course->transactions()
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        // Balance before the first transaction, derived from today's balance
        $balance = $course->absences_remaining - $history->sum('quantity');

        $history->each(function ($transaction) use (&$balance) {
            $balance += $transaction->quantity;
            $transaction->running_balance = $balance;
        });

        // Date filter is applied AFTER the balances are calculated
        $transactions = $history
            ->when($request->filled('date_from'), fn ($c) => $c->filter(
                fn ($t) => $t->created_at->toDateString() >= $request->date_from
            ))
            ->when($request->filled('date_to'), fn ($c) => $c->filter(
                fn ($t) => $t->created_at->toDateString() <= $request->date_to
            ))
            ->reverse()
            ->values();

        return view('courses.show', compact('course', 'transactions'));
    }

    public function edit(Course $course)
    {
        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'course_name' => 'required|string|max:255',
            'category' => 'required|in:Major,Minor,Gen Ed,Elective',
            'units' => 'required|integer|min:1|max:6',
            'grade' => 'required|numeric|min:0|max:4',
            'instructor' => 'required|string|max:255',
            'attendance_rate' => 'required|integer|min:0|max:100',
        ]);

        $course->update($validated);

        // Keep the absence allowance consistent if the rate was edited by hand
        if ($course->wasChanged('attendance_rate')) {
            $course->syncAllowanceFromAttendanceRate();
        }

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course updated successfully!');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course deleted successfully!');
    }
}