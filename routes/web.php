<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\FrontPagesController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\RegisterController;

/*
|--------------------------------------------------------------------------
| Web Routes - Frontend routes.
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Auth::routes();
Route::get( '/', [ FrontPagesController::class, 'index' ] )->name( 'index' );
Route::get('register-confirmation',[RegisterController::class, 'saveNewreferral']);
Route::get('unsubscribe', [RegisterController::class, 'unsubscribe']);
