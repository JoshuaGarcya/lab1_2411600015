<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Creates demo users (password: "password") and gives every user WITHOUT a
 * bank account one account with a 1,000.00 opening deposit.
 * Safe to run many times: existing users/accounts are skipped, nothing is deleted.
 *
 *   php artisan db:seed --class=BankSeeder
 */
class BankSeeder extends Seeder
{
    public function run(): void
    {
        $demoUsers = [
            ['Bank Admin', 'admin@bank.test', true],
            ['Alice Santos', 'alice@bank.test', false],
            ['Bob Reyes', 'bob@bank.test', false],
        ];

        foreach ($demoUsers as [$name, $email, $isAdmin]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password'] // hashed by the model cast
            );

            // is_admin is not mass assignable on purpose, so set it explicitly
            if ($isAdmin && ! $user->is_admin) {
                $user->forceFill(['is_admin' => true])->save();
            }
        }

        foreach (User::all() as $user) {
            if ($user->accounts()->exists()) {
                continue;
            }

            // The opening balance goes through a real deposit, so the ledger
            // (transactions table) always matches the balance.
            DB::transaction(function () use ($user) {
                $account = Account::create([
                    'user_id' => $user->id,
                    'account_number' => Account::generateAccountNumber(),
                ]);

                Transaction::create([
                    'account_id' => $account->id,
                    'type' => Transaction::TYPE_DEPOSIT,
                    'amount' => 1000,
                    'reference_number' => 'OPENING',
                    'notes' => 'Opening balance',
                ]);
            });
        }
    }
}
