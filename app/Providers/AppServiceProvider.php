<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);

        // The "testing" environment (APP_ENV=testing, loaded from
        // .env.testing — used by the port-8001 manual test server and by
        // `php artisan test`) must never be able to reach vbs_prod. This
        // guards every request/command in that environment, not just the
        // PHPUnit test suite.
        if ($this->app->environment('testing')) {
            $dbName = DB::connection()->getDatabaseName();

            if (! str_starts_with($dbName, 'vbs_test')) {
                abort(500, "Refusing request: testing environment is connected to \"{$dbName}\", not a vbs_test* database.");
            }
        }
    }
}
