<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Per-user (or IP) API limit
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->id ? "u:{$request->user()->id}" : "ip:$request->ip()";
            return [
                Limit::perMinute(60)->by($key),
            ];
        });

        //Tighter Login limit
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            return [
                Limit::perMinute(5)->by("login:{$email} | {$request->ip()}"),
            ];
        });
        //For Notes
        RateLimiter::for('notes-write', fn($req) => [Limit::perMinute(30)->by("u:" . optional($req->user())->id ?? $req->ip())]);

        RateLimiter::for(
            'login',
            fn($request) =>
            Limit::perMinute(10)->by($request->ip())
        );
    }
}
