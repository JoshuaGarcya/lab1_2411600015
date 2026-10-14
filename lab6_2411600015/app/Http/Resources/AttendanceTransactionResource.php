<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AttendanceTransaction */
class AttendanceTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'badge' => $this->type_badge,
            'quantity' => $this->quantity,
            'signed_quantity' => $this->signed_quantity,
            'reference_number' => $this->reference_number,
            'course' => [
                'id' => $this->course?->id,
                'code' => $this->course?->course_code,
                'name' => $this->course?->course_name,
                'url' => $this->course ? route('courses.show', $this->course, false) : null,
            ],
            'user' => $this->user?->name ?? 'Deleted user',
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_human' => $this->created_at->diffForHumans(),
            'url' => route('attendance-transactions.show', $this->resource, false),
        ];
    }
}
