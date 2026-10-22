<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidTransactionException;
use App\Exceptions\MinimumBalanceException;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\LowBalanceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Simple Banking System exam tests (fresh banking project).
 *
 * Every account in these tests starts with a 1,000.00 deposit and the
 * default minimum balance of 100.00, so:
 *   - the most you can withdraw is 900.00 (balance would be exactly 100.00)
 *   - withdrawing 900.00 triggers the low-balance notification
 */
class BankingTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- helpers

    private function makeUser(bool $admin = false): User
    {
        $user = User::factory()->create();

        if ($admin) {
            $user->forceFill(['is_admin' => true])->save();
        }

        return $user;
    }

    /** Creates an account and funds it through a real deposit. */
    private function openAccount(User $user, int $opening = 1000): Account
    {
        $account = Account::create([
            'user_id' => $user->id,
            'account_number' => Account::generateAccountNumber(),
        ]);

        if ($opening > 0) {
            $this->deposit($account, $opening);
        }

        return $account->fresh();
    }

    private function deposit(Account $account, int|float $amount): Transaction
    {
        return DB::transaction(fn () => Transaction::create([
            'account_id' => $account->id,
            'type' => 'deposit',
            'amount' => $amount,
        ]));
    }

    private function withdraw(Account $account, int|float $amount): Transaction
    {
        return DB::transaction(fn () => Transaction::create([
            'account_id' => $account->id,
            'type' => 'withdrawal',
            'amount' => $amount,
        ]));
    }

    /** Submits the transaction form as the given user. */
    private function submit(User $user, array $data)
    {
        return $this->actingAs($user)
            ->from(route('transactions.create'))
            ->post(route('transactions.store'), $data);
    }

    private function balanceOf(Account $account): string
    {
        return $account->fresh()->balance;
    }

    // ------------------------------------------------- database and relations

    public function test_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('accounts', [
            'id', 'user_id', 'account_number', 'balance', 'minimum_balance', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('transactions', [
            'id', 'account_id', 'type', 'amount', 'reference_number', 'notes', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasTable('notifications'));
    }

    public function test_new_account_has_default_balance_and_minimum(): void
    {
        $user = $this->makeUser();
        $account = Account::create(['user_id' => $user->id, 'account_number' => '1111111111'])->fresh();

        $this->assertSame('0.00', $account->balance);
        $this->assertSame('100.00', $account->minimum_balance);
    }

    public function test_account_number_must_be_unique(): void
    {
        $user = $this->makeUser();
        Account::create(['user_id' => $user->id, 'account_number' => '2222222222']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Account::create(['user_id' => $user->id, 'account_number' => '2222222222']);
    }

    public function test_database_accepts_the_transfer_type(): void
    {
        $account = $this->openAccount($this->makeUser());

        DB::table('transactions')->insert([
            'account_id' => $account->id,
            'type' => 'transfer',
            'amount' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('transactions', ['type' => 'transfer']);
    }

    public function test_relationships_work(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);
        $transaction = $account->transactions()->first();

        $this->assertTrue($account->user->is($user));
        $this->assertTrue($user->accounts->first()->is($account));
        $this->assertCount(1, $account->transactions);
        $this->assertTrue($transaction->account->is($account));
    }

    public function test_deleting_a_user_deletes_their_accounts_and_transactions(): void
    {
        $user = $this->makeUser();
        $this->openAccount($user);

        $user->delete();

        $this->assertSame(0, Account::count());
        $this->assertSame(0, Transaction::count());
    }

    public function test_balance_cannot_be_mass_assigned(): void
    {
        $user = $this->makeUser();
        $account = Account::create([
            'user_id' => $user->id,
            'account_number' => '3333333333',
            'balance' => 5000, // must be ignored
        ])->fresh();

        $this->assertSame('0.00', $account->balance);
    }

    public function test_observer_is_registered_on_the_transaction_model(): void
    {
        $events = Transaction::getEventDispatcher();

        $this->assertTrue($events->hasListeners('eloquent.creating: ' . Transaction::class));
        $this->assertTrue($events->hasListeners('eloquent.created: ' . Transaction::class));
    }

    // ------------------------------------------- deposits and withdrawals (HTTP)

    public function test_deposit_increases_the_balance(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'deposit', 'amount' => 500])
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHas('success');

        $this->assertSame('1500.00', $this->balanceOf($account));
        $this->assertDatabaseHas('transactions', ['account_id' => $account->id, 'type' => 'deposit', 'amount' => 500]);
    }

    public function test_deposit_with_cents_is_exact(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'deposit', 'amount' => '10.10']);
        $this->submit($user, ['account_id' => $account->id, 'type' => 'deposit', 'amount' => '20.20']);

        $this->assertSame('1030.30', $this->balanceOf($account));
    }

    public function test_withdrawal_decreases_the_balance(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 400])
            ->assertRedirect(route('transactions.index'));

        $this->assertSame('600.00', $this->balanceOf($account));
        $this->assertDatabaseHas('transactions', ['type' => 'withdrawal', 'amount' => 400]);
    }

    public function test_withdrawal_down_to_exactly_the_minimum_is_allowed(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 900])
            ->assertRedirect(route('transactions.index'));

        $this->assertSame('100.00', $this->balanceOf($account));
    }

    public function test_withdrawal_below_the_minimum_balance_is_rejected(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 950])
            ->assertRedirect(route('transactions.create'))
            ->assertSessionHasErrors('amount');

        $this->assertSame('1000.00', $this->balanceOf($account));
        $this->assertSame(1, Transaction::count()); // only the opening deposit
    }

    public function test_withdrawal_bigger_than_the_balance_is_rejected(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 5000])
            ->assertSessionHasErrors('amount');

        $this->assertSame('1000.00', $this->balanceOf($account));
        $this->assertSame(1, Transaction::count());
    }

    // --------------------------------------------------------------- validation

    public function test_required_fields_are_validated(): void
    {
        $user = $this->makeUser();
        $this->openAccount($user);

        $this->submit($user, [])->assertSessionHasErrors(['account_id', 'type', 'amount']);
    }

    public function test_controller_does_not_accept_the_transfer_type(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'transfer', 'amount' => 50])
            ->assertSessionHasErrors('type');

        $this->assertSame('1000.00', $this->balanceOf($account));
        $this->assertSame(1, Transaction::count());
    }

    public function test_bad_amounts_are_rejected(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        foreach (['0', '0.5', '-10', 'abc', '10.555'] as $badAmount) {
            $this->submit($user, ['account_id' => $account->id, 'type' => 'deposit', 'amount' => $badAmount])
                ->assertSessionHasErrors('amount');
        }

        $this->assertSame('1000.00', $this->balanceOf($account));
        $this->assertSame(1, Transaction::count());
    }

    public function test_unknown_account_is_rejected(): void
    {
        $user = $this->makeUser();
        $this->openAccount($user);

        $this->submit($user, ['account_id' => 99999, 'type' => 'deposit', 'amount' => 50])
            ->assertSessionHasErrors('account_id');
    }

    public function test_extra_balance_field_in_the_form_is_ignored(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, [
            'account_id' => $account->id,
            'type' => 'deposit',
            'amount' => 50,
            'balance' => 999999,
            'minimum_balance' => 0,
        ]);

        $account = $account->fresh();
        $this->assertSame('1050.00', $account->balance);
        $this->assertSame('100.00', $account->minimum_balance);
    }

    // ------------------------------------------------------------ authorization

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('transactions.index'))->assertRedirect(route('login'));
        $this->post(route('transactions.store'), [])->assertRedirect(route('login'));
        $this->get(route('bank.dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_cannot_use_someone_elses_account(): void
    {
        $owner = $this->makeUser();
        $thief = $this->makeUser();
        $account = $this->openAccount($owner);

        $this->submit($thief, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 500])
            ->assertForbidden();

        $this->assertSame('1000.00', $this->balanceOf($account));
        $this->assertSame(1, Transaction::count());
    }

    public function test_even_an_admin_cannot_transact_on_someone_elses_account(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeUser(admin: true);
        $account = $this->openAccount($owner);

        $this->submit($admin, ['account_id' => $account->id, 'type' => 'deposit', 'amount' => 500])
            ->assertForbidden();

        $this->assertSame('1000.00', $this->balanceOf($account));
    }

    public function test_create_form_and_index_only_show_the_users_own_data(): void
    {
        $mine = $this->openAccount($this->makeUser());
        $user = $mine->user;
        $theirs = $this->openAccount($this->makeUser());

        $this->actingAs($user)->get(route('transactions.create'))
            ->assertOk()
            ->assertSee($mine->account_number)
            ->assertDontSee($theirs->account_number);

        $this->actingAs($user)->get(route('transactions.index'))
            ->assertOk()
            ->assertSee($mine->account_number)
            ->assertDontSee($theirs->account_number);
    }

    public function test_admin_can_see_all_transactions(): void
    {
        $a = $this->openAccount($this->makeUser());
        $b = $this->openAccount($this->makeUser());
        $admin = $this->makeUser(admin: true);

        $this->actingAs($admin)->get(route('transactions.index'))
            ->assertOk()
            ->assertSee($a->account_number)
            ->assertSee($b->account_number);
    }

    // ----------------------------------------- observer rules and rollback (DB)

    public function test_observer_throws_the_right_exceptions(): void
    {
        $account = $this->openAccount($this->makeUser());

        $cases = [
            [MinimumBalanceException::class, fn () => $this->withdraw($account, 950)],
            [InsufficientFundsException::class, fn () => $this->withdraw($account, 5000)],
            [InvalidTransactionException::class, fn () => $this->deposit($account, 0)],
            [InvalidTransactionException::class, fn () => $this->deposit($account, -5)],
            [InvalidTransactionException::class, fn () => DB::transaction(fn () => Transaction::create([
                'account_id' => $account->id, 'type' => 'transfer', 'amount' => 10,
            ]))],
            [InvalidTransactionException::class, fn () => DB::transaction(fn () => Transaction::create([
                'account_id' => 99999, 'type' => 'deposit', 'amount' => 10,
            ]))],
        ];

        foreach ($cases as [$exception, $action]) {
            $thrown = null;

            try {
                $action();
            } catch (\Throwable $e) {
                $thrown = $e;
            }

            $this->assertInstanceOf($exception, $thrown, "Expected {$exception} to be thrown.");
        }

        // None of the failed attempts left a record or changed the balance
        $this->assertSame('1000.00', $this->balanceOf($account));
        $this->assertSame(1, Transaction::count());
    }

    public function test_a_crash_after_the_insert_rolls_back_the_row_and_the_balance(): void
    {
        $account = $this->openAccount($this->makeUser());

        try {
            DB::transaction(function () use ($account) {
                Transaction::create(['account_id' => $account->id, 'type' => 'deposit', 'amount' => 500]);

                throw new RuntimeException('Simulated crash before the transaction commits');
            });
            $this->fail('The simulated crash should have been thrown.');
        } catch (RuntimeException $e) {
            // expected
        }

        $this->assertSame('1000.00', $this->balanceOf($account));
        $this->assertSame(1, Transaction::count());
    }

    // ------------------------------------------------- low-balance notification

    public function test_low_balance_notification_is_stored_in_the_database(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 900]);

        $notifications = $user->fresh()->notifications()->where('type', LowBalanceNotification::class)->get();

        $this->assertCount(1, $notifications);
        $this->assertSame($account->id, $notifications->first()->data['account_id']);
        $this->assertSame($account->account_number, $notifications->first()->data['account_number']);
    }

    public function test_notification_goes_to_the_account_owner_only(): void
    {
        Notification::fake();

        $owner = $this->makeUser();
        $other = $this->makeUser();
        $account = $this->openAccount($owner);

        $this->submit($owner, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 900]);

        Notification::assertSentToTimes($owner, LowBalanceNotification::class, 1);
        Notification::assertNothingSentTo($other);
    }

    public function test_no_notification_while_the_balance_stays_above_the_minimum(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 400]);
        $this->submit($user, ['account_id' => $account->id, 'type' => 'deposit', 'amount' => 100]);

        Notification::assertNothingSent();
    }

    public function test_no_notification_for_a_rejected_withdrawal(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 950]);

        Notification::assertNothingSent();
    }

    public function test_no_notification_if_the_transaction_rolls_back_after_the_withdrawal(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $account = $this->openAccount($user);

        try {
            DB::transaction(function () use ($account) {
                $this->withdraw($account, 900); // would trigger the alert...
                throw new RuntimeException('Simulated crash');  // ...but we roll back
            });
        } catch (RuntimeException $e) {
            // expected
        }

        Notification::assertNothingSent();
        $this->assertSame('1000.00', $this->balanceOf($account));
    }

    public function test_duplicate_notifications_are_prevented(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $count = fn () => $user->fresh()->notifications()->where('type', LowBalanceNotification::class)->count();

        // 1) Balance drops to exactly the minimum -> one alert
        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 900]);
        $this->assertSame(1, $count());

        // 2) Another withdrawal while at the minimum is rejected -> still one alert
        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 1])
            ->assertSessionHasErrors('amount');
        $this->assertSame(1, $count());

        // 3) A deposit lifts the balance back above the minimum (100 -> 150) -> no alert
        $this->submit($user, ['account_id' => $account->id, 'type' => 'deposit', 'amount' => 50]);
        $this->assertSame(1, $count());

        // 4) Dropping to the minimum AGAIN is a new event -> a second alert (not a duplicate)
        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 50]);
        $this->assertSame(2, $count());
    }

    // ----------------------------------------------------- dashboard API + page

    public function test_stats_endpoint_returns_real_database_values(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);
        $this->withdraw($account, 400);

        $response = $this->actingAs($user)->getJson('/api/dashboard/stats')->assertOk();

        $response->assertJsonStructure(['total_accounts', 'total_money', 'total_transactions_today']);
        $this->assertSame(1, $response->json('total_accounts'));
        $this->assertEquals(600, $response->json('total_money'));
        $this->assertSame(2, $response->json('total_transactions_today')); // opening deposit + withdrawal
    }

    public function test_stats_only_count_todays_transactions(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        // Move the opening deposit to yesterday
        Transaction::query()->update(['created_at' => now()->subDay()]);

        $this->actingAs($user)->getJson('/api/dashboard/stats')
            ->assertJsonPath('total_transactions_today', 0);

        $this->deposit($account, 10);

        $this->actingAs($user)->getJson('/api/dashboard/stats')
            ->assertJsonPath('total_transactions_today', 1);
    }

    public function test_stats_are_limited_to_own_accounts_for_users_and_global_for_admins(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $admin = $this->makeUser(admin: true);
        $this->openAccount($userA, 1000);
        $this->openAccount($userB, 2000);

        $a = $this->actingAs($userA)->getJson('/api/dashboard/stats');
        $this->assertSame(1, $a->json('total_accounts'));
        $this->assertEquals(1000, $a->json('total_money'));
        $this->assertSame(1, $a->json('total_transactions_today'));

        $all = $this->actingAs($admin)->getJson('/api/dashboard/stats');
        $this->assertSame(2, $all->json('total_accounts'));
        $this->assertEquals(3000, $all->json('total_money'));
        $this->assertSame(2, $all->json('total_transactions_today'));
    }

    public function test_stats_endpoint_requires_login(): void
    {
        $this->getJson('/api/dashboard/stats')->assertUnauthorized();
    }

    public function test_bank_dashboard_page_polls_the_api_every_30_seconds(): void
    {
        $this->actingAs($this->makeUser())->get(route('bank.dashboard'))
            ->assertOk()
            ->assertSee('/api/dashboard/stats', false)
            ->assertSee('setInterval(loadStats, 30000)', false);
    }

    // ------------------------------------------------ login, register, logout

    public function test_user_can_log_in_and_a_wrong_password_fails(): void
    {
        User::factory()->create(['email' => 'alice@test.com']); // factory password is "password"

        $this->post(route('login'), ['email' => 'alice@test.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login'), ['email' => 'alice@test.com', 'password' => 'password'])
            ->assertRedirect(route('bank.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_registering_creates_a_user_and_an_empty_account(): void
    {
        $this->post(route('register'), [
            'name' => 'New Customer',
            'email' => 'new@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('bank.dashboard'));

        $this->assertAuthenticated();

        $user = User::where('email', 'new@test.com')->firstOrFail();
        $this->assertFalse($user->isAdmin());
        $this->assertSame(1, $user->accounts()->count());
        $this->assertSame('0.00', $user->accounts()->first()->fresh()->balance);
    }

    public function test_is_admin_cannot_be_set_through_registration(): void
    {
        $this->post(route('register'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => 1,
        ]);

        $this->assertFalse(User::where('email', 'sneaky@test.com')->firstOrFail()->isAdmin());
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs($this->makeUser())->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // ------------------------------------------------------ notifications page

    public function test_notifications_page_shows_and_clears_low_balance_alerts(): void
    {
        $user = $this->makeUser();
        $account = $this->openAccount($user);

        $this->submit($user, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 900]);

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Low balance')
            ->assertSee($account->account_number);

        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)->post(route('notifications.readAll'))->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_users_only_see_their_own_notifications(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $account = $this->openAccount($owner);

        $this->submit($owner, ['account_id' => $account->id, 'type' => 'withdrawal', 'amount' => 900]);

        $this->actingAs($other)->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee($account->account_number);
    }
}
