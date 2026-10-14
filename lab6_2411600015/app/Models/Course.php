<?php

namespace App\Models;

use App\Exceptions\AbsenceAllowanceException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

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
        'total_sessions',
        'absences_allowed',
        'absences_remaining',
        'warning_level',
    ];

    protected $casts = [
        'grade' => 'float',
        'units' => 'integer',
        'attendance_rate' => 'integer',
        'total_sessions' => 'integer',
        'absences_allowed' => 'integer',
        'absences_remaining' => 'integer',
        'warning_level' => 'integer',
    ];

    /* ---------- Relationships ---------- */

    // Relationship: one course can have many assignments
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    // Every absence / restore / adjustment ever logged for this course
    public function transactions(): HasMany
    {
        return $this->hasMany(AttendanceTransaction::class);
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(AttendanceTransaction::class)->latestOfMany();
    }

    /* ---------- Existing grade helpers ---------- */

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

    /* ---------- Absence allowance helpers ---------- */

    // "isLowStock": remaining absences are at or below the warning level
    public function isLowAllowance(): bool
    {
        return $this->absences_remaining <= $this->warning_level;
    }

    // "isOutOfStock": no absences left
    public function isLimitReached(): bool
    {
        return $this->absences_remaining <= 0;
    }

    public function getAbsencesUsedAttribute(): int
    {
        return max(0, $this->absences_allowed - $this->absences_remaining);
    }

    public function getAllowanceStatusAttribute(): string
    {
        if ($this->isLimitReached()) {
            return 'Limit Reached';
        }

        if ($this->isLowAllowance()) {
            return 'Low Allowance';
        }

        return 'Safe';
    }

    public function getAllowanceStatusColorAttribute(): string
    {
        return match ($this->allowance_status) {
            'Limit Reached' => 'danger',
            'Low Allowance' => 'warning',
            default => 'success',
        };
    }

    /* ---------- Query scopes ---------- */

    public function scopeLowAllowance(Builder $query): Builder
    {
        return $query->whereColumn('absences_remaining', '<=', 'warning_level');
    }

    public function scopeLimitReached(Builder $query): Builder
    {
        return $query->where('absences_remaining', '<=', 0);
    }

    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /* ---------- Stock-style logic ---------- */

    /**
     * Throw if applying $delta would break the rules:
     * remaining absences can never be negative or exceed the allowance.
     */
    public function assertCanApply(int $delta): void
    {
        $this->assertWithinLimits($this->absences_remaining + $delta);
    }

    private function assertWithinLimits(int $newRemaining): void
    {
        if ($newRemaining < 0) {
            throw new AbsenceAllowanceException(
                "{$this->course_code} only has {$this->absences_remaining} absence(s) left. "
                . 'That would exceed the allowance.'
            );
        }

        if ($newRemaining > $this->absences_allowed) {
            throw new AbsenceAllowanceException(
                "{$this->course_code} has only used {$this->absences_used} of {$this->absences_allowed} allowed absences. "
                . 'You cannot restore more than that.'
            );
        }
    }

    /**
     * Apply a signed change to absences_remaining under a row lock.
     * Also keeps attendance_rate in sync so the dashboard stays accurate.
     *
     * @return array{old:int,new:int}
     */
    public function applyAllowanceChange(int $delta): array
    {
        return DB::transaction(function () use ($delta) {
            $locked = static::query()->lockForUpdate()->findOrFail($this->getKey());

            $old = $locked->absences_remaining;
            $new = $old + $delta;

            $locked->assertWithinLimits($new);

            $locked->absences_remaining = $new;
            $locked->attendance_rate = $locked->rateFromRemaining($new);
            $locked->save();

            $this->refresh();

            return ['old' => $old, 'new' => $new];
        });
    }

    // 100% minus the share of sessions missed
    public function rateFromRemaining(int $remaining): int
    {
        $sessions = max(1, (int) $this->total_sessions);
        $used = max(0, $this->absences_allowed - $remaining);

        return (int) max(0, min(100, round(100 * (1 - $used / $sessions))));
    }

    /**
     * Derive the allowance from the stored attendance_rate.
     * Used when a course is created or its attendance rate is edited by hand.
     */
    public function syncAllowanceFromAttendanceRate(): void
    {
        $sessions = $this->total_sessions ?: 30;
        $used = (int) round((100 - $this->attendance_rate) / 100 * $sessions);
        $allowed = max(6, $used, (int) $this->absences_allowed);

        $this->total_sessions = $sessions;
        $this->absences_allowed = $allowed;
        $this->absences_remaining = $allowed - $used;
        $this->warning_level = $this->warning_level ?? 2;

        $this->saveQuietly();
    }
}
