<?php

use App\Http\Controllers\PaymentLogController;
use Illuminate\Support\Facades\Route;


Route::group([
    'prefix' => 'payments',
    'as' => 'payment.'
], function () {
    Route::post('/webhook', [PaymentLogController::class, 'webhook'])->name('webhook');
});
