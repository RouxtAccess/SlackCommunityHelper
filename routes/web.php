<?php

use App\Http\Controllers\Api\EventsController;
use App\Http\Controllers\Api\InteractivityController;
use App\Http\Controllers\Auth\LoginController;
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

Route::get('/workspace/register/callback', [LoginController::class, 'addNewSlackWorkspace'])
    ->name('workspace.add');
Route::redirect('/workspace/register/success', '/')->name('workspace.add.success');

