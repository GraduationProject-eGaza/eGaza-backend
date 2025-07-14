<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\CustomAccessToken;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

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
        // Morph map fix
        Relation::morphMap([
    'App\\Models\\User' => User::class,
    'App\\\\Models\\\\User' => User::class, // fix broken ones
             ]);

        // Use custom token model
        Sanctum::usePersonalAccessTokenModel(CustomAccessToken::class);
    }
}
