<?php

namespace App\Http\Controllers;

use App\Exceptions\AbsenceAllowanceException;
use App\Models\AttendanceTransaction;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustmentController extends Controller
{
    public const REASONS = [
        'Instructor record correction',
        'Excuse letter approved',
        'Makeup class attended',
        'Encoding error',
        'Other',
    ];

    public function create()
    {
        return view('attendance.adjust', [
            'courses' => Course::orderBy('course_code')->get(['id', 'course_code', 'course_name']),
            'reasons' => self::REASONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'counted_remaining' => 'required|integer|min:0|max:50',
            'reason' => 'required|in:' . implode(',', self::REASONS),
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($data, $request) {
                // Lock the row so the difference is calculated against the real current value
                $course = Course::lockForUpdate()->findOrFail($data['course_id']);

                if ($data['counted_remaining'] > $course->absences_allowed) {
                    throw ValidationException::withMessages([
                        'counted_remaining' => "The count cannot be higher than the {$course->absences_allowed} absences allowed.",
                    ]);
                }

                $difference = $data['counted_remaining'] - $course->absences_remaining;

                if ($difference === 0) {
                    throw ValidationException::withMessages([
                        'counted_remaining' => 'The recorded count already matches the system. No adjustment is needed.',
                    ]);
                }

                AttendanceTransaction::create([
                    'course_id' => $course->id,
                    'user_id' => $request->user()->id,
                    'type' => AttendanceTransaction::TYPE_ADJUSTMENT,
                    'quantity' => $difference,
                    'reference_number' => 'ADJ-' . now()->format('YmdHis'),
                    'reason' => $data['reason'],
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (AbsenceAllowanceException $e) {
            return back()->withInput()->withErrors(['counted_remaining' => $e->getMessage()]);
        }

        return redirect()
            ->route('attendance-transactions.index')
            ->with('success', 'Adjustment recorded and the course allowance was updated.');
    }
}
