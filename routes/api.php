<?php

use App\Http\Controllers\Device\IclockController;
use App\Http\Controllers\Device\ZktecoWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/zkteco/webhook/{deviceToken}', [ZktecoWebhookController::class, 'store'])->name('zkteco.webhook');

// ZKTeco ADMS push protocol endpoints (device-initiated connection)
// Configure your ZKTeco device's "Server Address" to point here.
Route::prefix('iclock')->name('iclock.')->group(function () {
    Route::get('/cdata', [IclockController::class, 'cdata'])->name('cdata');
    Route::post('/cdata', [IclockController::class, 'postCdata'])->name('cdata.post');
    Route::get('/getrequest', [IclockController::class, 'getRequest'])->name('getrequest');
    Route::post('/devicecmd', [IclockController::class, 'deviceCmd'])->name('devicecmd');
});
