<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\Repositories\PersonRepositoryInterface;
use App\Repositories\PersonRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository Pattern bindings - central for testability and decoupling
        $this->app->bind(PersonRepositoryInterface::class, PersonRepository::class);

        // Future bindings:
        // $this->app->bind(OtherRepositoryInterface::class, OtherRepository::class);
    }

    public function boot(): void
    {
        // Configure API rate limiting per client
        RateLimiter::for('api', function (Request $request) {
            $client = $request->user(); // ApiClient

            $limit = $client?->rate_limit_per_minute ?? 60;

            return Limit::perMinute($limit)->by(
                $client?->id ?? $request->ip()
            );
        });
    }
}
