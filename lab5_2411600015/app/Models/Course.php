<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Assignment;

class Course extends Model
{
    protected $fillable = [
        'course_code',
        'course_name',
        'category',
        'units',
        'grade',
        'instructor',
        'attendance_rate',
    ];

    protected $casts = [
        'grade' => 'float',
        'units' => 'integer',
        'attendance_rate' => 'integer',
    ];

    // Relationship: one course can have many assignments
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    // Mirrors your old JS deriveStatus()
    public function getStatusAttribute(): string
    {
        if ($this->grade >= 3.0) {
            return 'Passing';
        }

        if ($this->grade >= 2.0) {
            return 'At Risk';
        }

        return 'Failing';
    }

    // Mirrors your old qualityPoints calculation
    public function getQualityPointsAttribute(): float
    {
        return round($this->units * $this->grade, 2);
    }
}