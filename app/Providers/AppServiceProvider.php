<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use App\Jobs\ProcessUserRegistration;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Passport::ignoreMigrations();

        // Passport::loadAKeysFrom(base_path());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        ProcessUserRegistration::dispatch()->delay(now()->addSeconds(10));
    }
}
