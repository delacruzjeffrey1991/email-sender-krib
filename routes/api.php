<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\EmailController;
use App\Http\Controllers\Backend\EmailApprovalsController;
use App\Http\Controllers\API\ContactController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\ReferralController;
use App\Http\Controllers\API\DyanamoController;

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
Route::post('save-email', [EmailController::class, 'saveEmail']);
Route::post('update-email/{id}', [EmailController::class, 'updateEmail']);
Route::post('update-email-status/{id}', [EmailController::class, 'updateEmailStatus']);
Route::post('user-password-validation', [EmailApprovalsController::class, 'validatePassword']);

Route::post('contact-list', [ContactController::class, 'create']);
Route::post('contact-list/update', [ContactController::class, 'updateContactList']);
Route::post('contact-list/add-topic', [ContactController::class, 'addTopic']);
Route::get('contact-list', [ContactController::class, 'getContactList']);
Route::post('contact', [ContactController::class, 'createContact']);
Route::get('contact', [ContactController::class, 'getContact']);
Route::get('contact/list', [ContactController::class, 'listContact']);
Route::post('contact/add-contacts-topic', [ContactController::class, 'addContactsTopics']);
Route::post('referral-dahboard', [ReferralController::class, 'getReferralData']);

Route::post('subscribe', [RegisterController::class, 'registerNewreferral']);

Route::get('event-list/{city?}/{when?}', [EventController::class, 'getEvents']);
Route::post('event', [EventController::class, 'insertData']);
Route::post('event-upload', [EventController::class, 'uploadFile']);


//Dyanamo DB
Route::get('getCities', [DyanamoController::class, 'getCities']);
Route::post('subscribeUser', [DyanamoController::class, 'subscribeUser']);
Route::get('getBlogs', [DyanamoController::class, 'getBlogs']);



