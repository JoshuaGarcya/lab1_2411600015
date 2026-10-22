<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    /**
     * "balance" is NOT fillable on purpose. It can only be changed by the
     * AccountObserver when a transaction is created, never by mass assignment
     * (so a tampered form field can never change a balance).
     */
    protected $fillable = [
        'user_id',
        'account_number',
        'minimum_balance',
    ];

    // Same defaults as the migration, so a freshly created model already has them
    protected $attributes = [
        'balance' => 0.00,
        'minimum_balance' => 100.00,
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'minimum_balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Accounts a user may SEE: administrators see all accounts,
     * everybody else only sees their own.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where('user_id', $user->id);
    }

    /**
     * Make a random 10-digit account number that is not used yet.
     */
    public static function generateAccountNumber(): string
    {
        do {
            $number = str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (static::where('account_number', $number)->exists());

        return $number;
    }
}
