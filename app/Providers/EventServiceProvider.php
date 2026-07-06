<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\UpdateBulkUploadBatchStatus;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Bus\Events\BatchCompleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BatchCompleted::class => [
            UpdateBulkUploadBatchStatus::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}