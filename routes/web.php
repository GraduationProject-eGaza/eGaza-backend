<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/debug', function () {
    return response()->file(storage_path('logs/laravel.log'));
});
Route::get('/test-db-env', function () {
    return [
        'host' => env('DB_HOST'),
        'connection' => config('database.default'),
        'url' => env('DATABASE_URL'),
    ];
});
Route::get('/clear-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    return 'Cache cleared successfully!';
});
