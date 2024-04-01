<?php

use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
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
Route::put('phone-auth/verification',  [LoginController::class, 'phoneVerification'])->withoutMiddleware(['auth:api'])->name('login');
Route::put('phone-auth/verify',  [LoginController::class, 'phoneVerify'])->withoutMiddleware(['auth:api']);


Route::middleware('auth:api')->group(function () {
    // Protected routes
   
    Route::get('entryTicket/create',  [TicketController::class, 'entryTicket']);
    Route::get('ticket/consult',  [TicketController::class, 'consultTicket']);

});


