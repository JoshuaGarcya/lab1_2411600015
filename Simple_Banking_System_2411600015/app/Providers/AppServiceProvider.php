<?php

namespace App\Providers;

use App\Models\Transaction;
use App\Observers\AccountObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Every new Transaction is checked and applied to its Account by this observer
        Transaction::observe(AccountObserver::class);
    }
}
