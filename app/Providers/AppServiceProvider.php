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
        $this->app->bind(PersonRepositoryInterface::class, PersonRepository::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $client = $request->user();

            $limit = $client?->rate_limit_per_minute ?? 60;

            return Limit::perMinute($limit)->by(
                $client?->id ?? $request->ip()
            );
        });
    }
}
