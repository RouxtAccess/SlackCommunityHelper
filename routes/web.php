<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/up', function (Request $request) {
    $exception = null;

    try {
        Event::dispatch(new DiagnosingHealth);
    } catch (Throwable $throwable) {
        if (app()->hasDebugModeEnabled()) {
            throw $throwable;
        }

        report($throwable);

        $exception = $throwable->getMessage();
    }

    $status = $exception ? 500 : 200;

    if ($request->expectsJson()) {
        return response()->json(['status' => $exception ? 'down' : 'up'], $status);
    }

    return response($exception ? 'DOWN' : 'OK', $status);
})->name('health');

Route::get('/workspace/register/callback', [LoginController::class, 'addNewSlackWorkspace'])
    ->name('workspace.add');
Route::redirect('/workspace/register/success', '/')->name('workspace.add.success');
