<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fallback JSON route for unauthenticated redirects from auth middleware
Route::get('/login', function () {
    return response()->json([
        'message' => 'Unauthenticated. Use the API token endpoint to obtain a token.'
    ], 401);
})->name('login');
