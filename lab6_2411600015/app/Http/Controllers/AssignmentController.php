<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        $assignments = Assignment::with('course')
            ->orderBy('due_date')
            ->get();

        return view('assignments.index', compact('assignments'));
    }

    public function create()
    {
        $courses = Course::orderBy('course_code')->get();

        return view('assignments.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'status' => 'required|in:Pending,Completed',
        ]);

        Assignment::create($validated);

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Assignment added successfully!');
    }

    public function show(Assignment $assignment)
    {
        $assignment->load('course');

        return view('assignments.show', compact('assignment'));
    }

    public function edit(Assignment $assignment)
    {
        $courses = Course::orderBy('course_code')->get();

        return view('assignments.edit', compact(
            'assignment',
            'courses'
        ));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'status' => 'required|in:Pending,Completed',
        ]);

        $assignment->update($validated);

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Assignment updated successfully!');
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Assignment deleted successfully!');
    }
}