<?php

use App\Http\Controllers\API\Partners\InshoppingcartController;
use Illuminate\Support\Facades\Route;

Route::controller(InshoppingcartController::class)->group(function () {
    Route::post('upsert-user-profile', 'upsertUserData');
    Route::get('get-user-profile', 'getUserProfile');
    Route::get('read-data', 'readData');
    Route::post('write-data', 'writeData');
    Route::get('get-partner', 'getPartner');
    Route::post('send-request', 'sendRequest');
    Route::get('email-checker', 'getEmailInformation');
    Route::post('get-user-attributes', 'getUserAttributes');
});
