<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HookController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['middleware' => 'guest'], function ($router) {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::post('hook', [HookController::class, 'handleSubscriberUpdate']);
});
Route::group(['middleware' => 'auth:api'], function ($router) {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);

    Route::get('subscription', [SubscriptionController::class, 'getSubscription']);
    Route::post('subscription', [SubscriptionController::class, 'createSubscription']);
    Route::delete('subscription', [SubscriptionController::class, 'cancelSubscription']);
    Route::put('subscription', [SubscriptionController::class, 'reactivateSubscription']);
    Route::put('subscription/rescale', [SubscriptionController::class, 'rescaleSubscription']);

    Route::get('cards', [SubscriptionController::class, 'getCardList']);
});
