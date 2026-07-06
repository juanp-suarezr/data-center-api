<?php

declare(strict_types=1);

use App\Http\Controllers\API\v1\AuthController;
use App\Http\Controllers\API\v1\BulkUploadController;
use App\Http\Controllers\API\v1\PersonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
| Central Data API - Master Data Management
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public health check
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'service' => 'Central Data API',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]))->name('api.health');

    // Authentication - obtain token
    Route::post('/auth/token', [AuthController::class, 'issueToken'])->name('api.auth.token');

    // Protected routes - require valid Sanctum token from ApiClient
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        // Persons endpoints
        Route::prefix('persons')->group(function () {
            Route::get('/search', [PersonController::class, 'search'])->name('api.persons.search');
            Route::get('/find', [PersonController::class, 'findByDocument'])->name('api.persons.find');
            Route::post('/sync', [PersonController::class, 'sync'])->name('api.persons.sync');
            Route::get('/{uuid}', [PersonController::class, 'show'])->name('api.persons.show');
        });

        // Bulk upload endpoints
        Route::prefix('bulk')->group(function () {
            Route::post('/persons', [BulkUploadController::class, 'uploadPersons'])->name('api.bulk.persons.upload');
            Route::get('/persons/{batchId}', [BulkUploadController::class, 'getBatchStatus'])->name('api.bulk.persons.status');
        });

        // Future: more resources
    });
});
