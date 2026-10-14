<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Course */
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_code' => $this->course_code,
            'course_name' => $this->course_name,
            'category' => $this->category,
            'instructor' => $this->instructor,
            'absences_allowed' => $this->absences_allowed,
            'absences_remaining' => $this->absences_remaining,
            'absences_used' => $this->absences_used,
            'warning_level' => $this->warning_level,
            'allowance_status' => $this->allowance_status,
            'status_color' => $this->allowance_status_color,
            'attendance_rate' => $this->attendance_rate,
            'url' => route('courses.show', $this->resource, false),
        ];
    }
}
