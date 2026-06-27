<?php

use Athwari\LaravelZktecoAdms\Http\Controllers\AdmsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ZKTeco ADMS Protocol Routes
|--------------------------------------------------------------------------
|
| These routes implement the ADMS protocol endpoints used by ZKTeco
| biometric devices to communicate with the server.
|
*/

// Attendance data, device info, and user query results
Route::match(['get', 'post'], '/cdata', [AdmsController::class, 'handleCdata'])
    ->name('zkteco-adms.cdata');

// Device registration & capabilities
Route::match(['get', 'post'], '/registry', [AdmsController::class, 'handleRegistry'])
    ->name('zkteco-adms.registry');

// Device polling for pending commands
Route::get('/getrequest', [AdmsController::class, 'handleGetRequest'])
    ->name('zkteco-adms.getrequest');

// Command execution confirmations
Route::post('/devicecmd', [AdmsController::class, 'handleDeviceCmd'])
    ->name('zkteco-adms.devicecmd');

// JSON device snapshot (opt-in via config)
Route::get('/inspect', [AdmsController::class, 'handleInspect'])
    ->name('zkteco-adms.inspect');

// Connection test endpoint
Route::match(['get', 'post'], '/test', fn () => response('OK'))
    ->name('zkteco-adms.test');
