<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('queue:work --stop-when-empty --tries=3', function () {
    $this->exec('queue:work --stop-when-empty --tries=3');

    $this->info('Starting queue worker...');
})->purpose('Start the queue worker');

