<?php

namespace App\Models;

use App\Observers\AttendanceTransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([AttendanceTransactionObserver::class])]
class AttendanceTransaction extends Model
{
    public const TYPE_ABSENCE = 'absence';       // "stock out"
    public const TYPE_RESTORE = 'restore';       // "stock in"
    public const TYPE_ADJUSTMENT = 'adjustment'; // count correction

    public const TYPES = [
        self::TYPE_ABSENCE,
        self::TYPE_RESTORE,
        self::TYPE_ADJUSTMENT,
    ];

    protected $fillable = [
        'course_id',
        'user_id',
        'type',
        'quantity',
        'reference_number',
        'reason',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /* ---------- Relationships ---------- */

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ---------- Query scopes ---------- */

    public function scopeAbsences(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ABSENCE);
    }

    public function scopeRestores(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_RESTORE);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /* ---------- Accessors ---------- */

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_ABSENCE => 'Absence Recorded',
            self::TYPE_RESTORE => 'Allowance Restored',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            default => ucfirst((string) $this->type),
        };
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_ABSENCE => 'danger',
            self::TYPE_RESTORE => 'success',
            default => 'info',
        };
    }

    public function getSignedQuantityAttribute(): string
    {
        return ($this->quantity > 0 ? '+' : '') . $this->quantity;
    }
}
