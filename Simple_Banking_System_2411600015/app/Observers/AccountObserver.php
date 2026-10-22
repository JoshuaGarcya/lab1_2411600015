<?php

namespace App\Observers;

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidTransactionException;
use App\Exceptions\MinimumBalanceException;
use App\Models\Account;
use App\Models\Transaction;
use App\Notifications\LowBalanceNotification;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Watches the Transaction model and keeps the account balance correct.
 * (It is called "AccountObserver" because its job is to update the Account.)
 *
 * It must run INSIDE a DB::transaction(), which the controller starts:
 *
 *   creating -> lock the account row, check the rules (throw = nothing is saved)
 *   created  -> change the balance (same DB transaction as the new row)
 *
 * If anything throws, the whole DB transaction is rolled back, so the
 * transaction row and the balance change succeed or fail together.
 *
 * Money is compared in cents (whole numbers) to avoid decimal rounding errors.
 */
class AccountObserver
{
    /**
     * Before the transaction row is inserted: validate it.
     */
    public function creating(Transaction $transaction): void
    {
        // Safety net: a row lock only lasts until the DB transaction ends, and
        // the insert + balance update are only "all or nothing" inside one.
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Transaction records must be created inside DB::transaction().');
        }

        $account = $this->lockAccount($transaction->account_id);

        // Throws an exception when the transaction is not allowed
        $this->newBalanceInCents($account, $transaction);
    }

    /**
     * After the transaction row is inserted: update the balance.
     * This is the ONLY place in the app that changes a balance.
     */
    public function created(Transaction $transaction): void
    {
        // Same DB transaction as creating(), so we still hold the lock.
        $account = $this->lockAccount($transaction->account_id);

        $oldCents = $this->toCents($account->balance);
        $newCents = $this->newBalanceInCents($account, $transaction); // checked again, just in case
        $minimumCents = $this->toCents($account->minimum_balance);

        $account->balance = $this->fromCents($newCents);
        $account->save();

        // Low-balance alert: only when a withdrawal CROSSES DOWN to the minimum.
        // (old balance was above it, new balance is at or below it)
        if ($transaction->type === Transaction::TYPE_WITHDRAWAL
            && $oldCents > $minimumCents
            && $newCents <= $minimumCents) {

            // Runs only AFTER the DB transaction commits. If anything fails and
            // the transaction rolls back, this alert is thrown away.
            DB::afterCommit(function () use ($account) {
                $account->user->notify(new LowBalanceNotification($account));
            });
        }
    }

    /**
     * Read the account row with a lock (SELECT ... FOR UPDATE).
     * A second withdrawal on the same account has to wait here until the
     * first DB transaction commits, so two requests can never spend the same money.
     */
    private function lockAccount(mixed $accountId): Account
    {
        $account = Account::lockForUpdate()->find($accountId);

        if (! $account) {
            throw new InvalidTransactionException('The account does not exist.');
        }

        return $account;
    }

    /**
     * Work out the balance (in cents) after this transaction, or throw.
     */
    private function newBalanceInCents(Account $account, Transaction $transaction): int
    {
        // Raw value, so a bad amount gives our exception instead of a cast error
        $amount = $this->toCents($transaction->getAttributes()['amount'] ?? null);

        if ($amount <= 0) {
            throw new InvalidTransactionException('The amount must be greater than zero.');
        }

        $balance = $this->toCents($account->balance);

        switch ($transaction->type) {
            case Transaction::TYPE_DEPOSIT:
                return $balance + $amount;

            case Transaction::TYPE_WITHDRAWAL:
                $newBalance = $balance - $amount;

                if ($newBalance < 0) {
                    throw new InsufficientFundsException(
                        'Insufficient funds. Your balance is ' . number_format($balance / 100, 2) . '.'
                    );
                }

                if ($newBalance < $this->toCents($account->minimum_balance)) {
                    throw new MinimumBalanceException(
                        'This withdrawal would leave the account below its minimum balance of '
                        . number_format($this->toCents($account->minimum_balance) / 100, 2) . '.'
                    );
                }

                return $newBalance;

            default:
                // "transfer" exists in the database but is not implemented yet
                throw new InvalidTransactionException(
                    "Transactions of type '{$transaction->type}' are not supported."
                );
        }
    }

    private function toCents(mixed $value): int
    {
        return is_numeric($value) ? (int) round(((float) $value) * 100) : 0;
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
