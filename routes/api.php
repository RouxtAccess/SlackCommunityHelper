<?php

use App\Http\Controllers\Api\EventsController;
use App\Http\Controllers\Api\InteractivityController;
use App\Http\Middleware\VerifySlackRequestMiddleware;
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

Route::group(['middleware' => [VerifySlackRequestMiddleware::class]], function () {
    Route::post('/slack/interactivity', [InteractivityController::class, 'interactiveActions'])->name('api.slack.interactivity');
    Route::post('/slack/events', [EventsController::class, 'action'])->name('api.slack.events');
});

