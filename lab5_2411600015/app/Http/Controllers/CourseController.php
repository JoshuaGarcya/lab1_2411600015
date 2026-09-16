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

        Course::create($validated);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Course added successfully!');
    }

    public function show(Course $course)
    {
        return view('courses.show', compact('course'));
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