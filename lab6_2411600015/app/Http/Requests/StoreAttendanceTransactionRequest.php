<?php

namespace App\Http\Requests;

use App\Models\AttendanceTransaction;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'type' => ['required', Rule::in([
                AttendanceTransaction::TYPE_ABSENCE,
                AttendanceTransaction::TYPE_RESTORE,
            ])],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * After-validation hook: checks the quantity against the course's
     * current allowance (the "sufficient stock" rule).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $course = Course::find($this->input('course_id'));
                $quantity = (int) $this->input('quantity');

                if (! $course) {
                    return;
                }

                if ($this->input('type') === AttendanceTransaction::TYPE_ABSENCE
                    && $quantity > $course->absences_remaining) {
                    $validator->errors()->add(
                        'quantity',
                        "{$course->course_code} only has {$course->absences_remaining} absence(s) left."
                    );
                }

                if ($this->input('type') === AttendanceTransaction::TYPE_RESTORE
                    && $quantity > $course->absences_used) {
                    $validator->errors()->add(
                        'quantity',
                        "{$course->course_code} has only used {$course->absences_used} absence(s), so you cannot restore {$quantity}."
                    );
                }
            },
        ];
    }
}
