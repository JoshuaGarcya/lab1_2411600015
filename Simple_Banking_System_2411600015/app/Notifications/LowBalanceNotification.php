<?php

namespace App\Notifications;

use App\Models\Account;
use Illuminate\Notifications\Notification;

/**
 * Stored in the "notifications" table (database channel only).
 *
 * It is not queued, so no queue worker is needed. It is still sent only
 * after the money transaction commits, because the AccountObserver sends it
 * from inside DB::afterCommit().
 */
class LowBalanceNotification extends Notification
{
    public function __construct(public Account $account)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $balance = number_format((float) $this->account->balance, 2);
        $minimum = number_format((float) $this->account->minimum_balance, 2);

        return [
            'account_id' => $this->account->id,
            'account_number' => $this->account->account_number,
            'balance' => $this->account->balance,
            'minimum_balance' => $this->account->minimum_balance,
            'message' => "Low balance: account {$this->account->account_number} now has {$balance}, "
                . "which is at or below the minimum balance of {$minimum}.",
            // Relative link, used by the notification bell
            'url' => route('transactions.index', [], false),
        ];
    }
}
