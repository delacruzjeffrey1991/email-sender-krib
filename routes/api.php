<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\EmailController;
use App\Http\Controllers\Backend\EmailApprovalsController;
use App\Http\Controllers\API\ContactController;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('send-email', [EmailController::class, 'sendEmail']);
Route::post('send-email/topic', [EmailController::class, 'sendEmailByTopic']);


Route::post('user-password-validation', [EmailApprovalsController::class, 'validatePassword']);

Route::post('contact-list', [ContactController::class, 'create']);
Route::post('contact-list/update', [ContactController::class, 'updateContactList']);
Route::post('contact-list/add-topic', [ContactController::class, 'addTopic']);
Route::get('contact-list', [ContactController::class, 'getContactList']);
Route::post('contact', [ContactController::class, 'createContact']);
Route::get('contact', [ContactController::class, 'getContact']);
Route::get('contact/list', [ContactController::class, 'listContact']);
Route::post('contact/add-contacts-topic', [ContactController::class, 'addContactsTopics']);

