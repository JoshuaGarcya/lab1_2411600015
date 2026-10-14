<?php

namespace App\Http\Controllers;

use App\Exceptions\AbsenceAllowanceException;
use App\Http\Requests\StoreAttendanceTransactionRequest;
use App\Models\AttendanceTransaction;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceTransactionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'course_id' => 'nullable|integer',
            'type' => 'nullable|in:' . implode(',', AttendanceTransaction::TYPES),
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $transactions = AttendanceTransaction::with(['course', 'user'])
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->course_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->recent()
            ->paginate(20)
            ->withQueryString();

        $courses = Course::orderBy('course_code')->get(['id', 'course_code', 'course_name']);

        return view('attendance.index', [
            'transactions' => $transactions,
            'courses' => $courses,
            'types' => AttendanceTransaction::TYPES,
        ]);
    }

    public function create(Request $request)
    {
        $courses = Course::orderBy('course_code')->get();

        return view('attendance.create', [
            'courses' => $courses,
            'selectedCourse' => $request->query('course_id'),
        ]);
    }

    public function store(StoreAttendanceTransactionRequest $request)
    {
        $data = $request->validated();

        $quantity = $data['type'] === AttendanceTransaction::TYPE_ABSENCE
            ? -$data['quantity']      // using allowance
            : $data['quantity'];      // restoring allowance

        try {
            // The observer updates the course inside this same transaction.
            DB::transaction(function () use ($data, $quantity, $request) {
                AttendanceTransaction::create([
                    'course_id' => $data['course_id'],
                    'user_id' => $request->user()->id,
                    'type' => $data['type'],
                    'quantity' => $quantity,
                    'reference_number' => $data['reference_number'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (AbsenceAllowanceException $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }

        return redirect()
            ->route('attendance-transactions.index')
            ->with('success', 'Transaction recorded and the course allowance was updated.');
    }

    public function show(AttendanceTransaction $transaction)
    {
        $transaction->load(['course', 'user']);

        return view('attendance.show', compact('transaction'));
    }

    /**
     * Admin only (see routes). Deleting a transaction REVERSES its effect,
     * unless the reversal would break the allowance limits.
     */
    public function destroy(AttendanceTransaction $transaction)
    {
        try {
            DB::transaction(fn () => $transaction->delete());
        } catch (AbsenceAllowanceException $e) {
            return back()->with('error', 'Cannot delete: reversing this transaction is not possible. ' . $e->getMessage());
        }

        return redirect()
            ->route('attendance-transactions.index')
            ->with('success', 'Transaction deleted and its effect was reversed.');
    }
}
