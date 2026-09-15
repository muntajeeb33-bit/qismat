<?php

namespace App\Providers;

use App\Contracts\FirebaseTokenVerifier;
use App\Services\KreaitFirebaseTokenVerifier;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Factory;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Auth::class, function () {
            $credentials = config('firebase.credentials');
            $projectId = config('firebase.project_id');

            if (! is_string($credentials) || $credentials === '' || ! is_string($projectId) || $projectId === '') {
                throw new RuntimeException('Firebase server credentials are not configured.');
            }

            return (new Factory)
                ->withServiceAccount($credentials)
                ->withProjectId($projectId)
                ->createAuth();
        });

        $this->app->bind(FirebaseTokenVerifier::class, KreaitFirebaseTokenVerifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
