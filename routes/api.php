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

// Route::middleware('auth:api')->group(function () {

//     Route::get('parkings',  [LoginController::class, 'getParkingOnStreetList']);

//     Route::get('entryTicket/create',  [TicketController::class, 'entryTicket']);
//     Route::get('ticket/consult',  [TicketController::class, 'consultTicket']);
    
//     Route::get('payment/options',  [PaymentController::class, 'options']);
//     Route::post('payment',  [PaymentController::class, 'postPayment']);
//     Route::post('prepayment',  [PaymentController::class, 'prePayment']);
//     Route::post('extension',  [PaymentController::class, 'extension']);

//     Route::post('check/shift',  [ShiftController::class, 'checkShift']);
//     Route::post('open/shift',  [ShiftController::class, 'openShift']);
//     Route::post('close/shift',  [ShiftController::class, 'closeShift']);

//     Route::get('get/counting',  [CountingController::class, 'getCounting']);
//     Route::post('edit/counting',  [CountingController::class, 'editCounting']);

//     Route::get('get/plate/list',  [LicensePlateController::class, 'plateList']);
//     Route::get('get/plate',  [LicensePlateController::class, 'plateStatus']);

//     Route::get('get/products',  [EnforcementController::class, 'getProduct']);
//     Route::get('check/enforcement',  [EnforcementController::class, 'checkEnforcement']);
//     Route::post('make/enforcement',  [EnforcementController::class, 'makeEnforcement']);
    
//     Route::post('pay/enforcement',  [EnforcementController::class, 'payEnforcement']);
//     Route::post('cashPay/enforcement',  [EnforcementController::class, 'cashPayEnforcement']);

// });