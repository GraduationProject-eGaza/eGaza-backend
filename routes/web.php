<?php

use Illuminate\Support\Facades\Route;

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
    return response()->json([
        'status' => 'Laravel is working',
        'env' => app()->environment(),
        'debug' => config('app.debug'),
        'key' => config('app.key') ? 'set' : 'missing'
    ]);
});
