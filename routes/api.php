<?php

use App\Http\Controllers\CountingController;
use App\Http\Controllers\EnforcementController;
use App\Http\Controllers\LicensePlateController;
use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ShiftController;

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

// phone auth
Route::post('check/password',  [LoginController::class, 'checkPassword'])->withoutMiddleware(['auth:api']);

// phone auth
Route::put('phone-auth/verification',  [LoginController::class, 'phoneVerification'])->withoutMiddleware(['auth:api']);
Route::put('phone-auth/verify',  [LoginController::class, 'phoneVerify'])->withoutMiddleware(['auth:api']);


Route::get('entryTicket/create',  [TicketController::class, 'entryTicket'])->withoutMiddleware(['auth:api']);
Route::get('parkings',  [LoginController::class, 'getParkingOnStreetList'])->withoutMiddleware(['auth:api']);

Route::get('ticket/consult',  [TicketController::class, 'consultTicket'])->withoutMiddleware(['auth:api']);

Route::get('payment/options',  [PaymentController::class, 'options'])->withoutMiddleware(['auth:api']);
Route::post('payment',  [PaymentController::class, 'postPayment'])->withoutMiddleware(['auth:api']);
Route::post('prepayment',  [PaymentController::class, 'prePayment'])->withoutMiddleware(['auth:api']);
Route::post('extension',  [PaymentController::class, 'extension'])->withoutMiddleware(['auth:api']);

Route::post('check/shift',  [ShiftController::class, 'checkShift'])->withoutMiddleware(['auth:api']);
Route::post('open/shift',  [ShiftController::class, 'openShift'])->withoutMiddleware(['auth:api']);
Route::post('close/shift',  [ShiftController::class, 'closeShift'])->withoutMiddleware(['auth:api']);

Route::get('get/counting',  [CountingController::class, 'getCounting'])->withoutMiddleware(['auth:api']);
Route::post('edit/counting',  [CountingController::class, 'editCounting'])->withoutMiddleware(['auth:api']);

Route::get('get/plate/list',  [LicensePlateController::class, 'plateList'])->withoutMiddleware(['auth:api']);
Route::get('get/plate',  [LicensePlateController::class, 'plateStatus'])->withoutMiddleware(['auth:api']);

Route::get('get/products',  [EnforcementController::class, 'getProduct'])->withoutMiddleware(['auth:api']);
Route::get('check/enforcement',  [EnforcementController::class, 'checkEnforcement'])->withoutMiddleware(['auth:api']);
Route::post('make/enforcement',  [EnforcementController::class, 'makeEnforcement'])->withoutMiddleware(['auth:api']);

Route::post('pay/enforcement',  [EnforcementController::class, 'payEnforcement'])->withoutMiddleware(['auth:api']);
Route::post('cashPay/enforcement',  [EnforcementController::class, 'cashPayEnforcement'])->withoutMiddleware(['auth:api']);

Route::middleware('auth:api')->group(function () {   
});