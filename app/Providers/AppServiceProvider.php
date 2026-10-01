<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('claims', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('verification-resend', function (Request $request) {
            return Limit::perMinutes(15, 3)->by($request->user()?->id ?: $request->ip());
        });

        VerifyEmail::createUrlUsing(function (object $notifiable) {
            $backendSignedUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
            );

            $query = parse_url($backendSignedUrl, PHP_URL_QUERY);
            $frontendOrigin = trim(explode(',', config('app.frontend_url'))[0]);

            return sprintf(
                '%s/challenge/verify-email/%s/%s?%s',
                rtrim($frontendOrigin, '/'),
                $notifiable->getKey(),
                sha1($notifiable->getEmailForVerification()),
                $query
            );
        });
    }
}
