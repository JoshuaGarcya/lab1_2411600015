<?php

namespace App\Observers;

use App\Exceptions\AbsenceAllowanceException;
use App\Models\AttendanceTransaction;
use App\Models\Course;
use App\Models\User;
use App\Notifications\LowAllowanceNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AttendanceTransactionObserver
{
    /**
     * Guard clause: stop an invalid row from ever being inserted.
     * (The authoritative check happens again, under a row lock, in created().)
     */
    public function creating(AttendanceTransaction $transaction): void
    {
        $course = Course::findOrFail($transaction->course_id);

        $course->assertCanApply($transaction->quantity);
    }

    /**
     * Apply the change to the course's remaining absences.
     * Runs inside the caller's DB transaction, so an exception here
     * also rolls back the transaction row that was just inserted.
     */
    public function created(AttendanceTransaction $transaction): void
    {
        $this->applyToCourse($transaction->course_id, $transaction->quantity);
    }

    /**
     * Deleting a transaction reverses its effect on the course.
     */
    public function deleted(AttendanceTransaction $transaction): void
    {
        $this->applyToCourse($transaction->course_id, -$transaction->quantity);
    }

    private function applyToCourse(int $courseId, int $delta): void
    {
        DB::transaction(function () use ($courseId, $delta) {
            $course = Course::findOrFail($courseId);

            ['old' => $old, 'new' => $new] = $course->applyAllowanceChange($delta);

            $this->notifyIfNeeded($course, $old, $new);
        });
    }

    /**
     * Send an alert only when the course CROSSES the warning level
     * (or hits zero), so repeated absences don't spam the same alert.
     */
    private function notifyIfNeeded(Course $course, int $old, int $new): void
    {
        $crossedWarning = $old > $course->warning_level && $new <= $course->warning_level;
        $hitLimit = $old > 0 && $new === 0;

        if ($crossedWarning || $hitLimit) {
            Notification::send(User::all(), new LowAllowanceNotification($course));
        }
    }
}
