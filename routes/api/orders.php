<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;


Route::group([
    'prefix' => 'orders',
    'as' => 'order.'
], function () {
    Route::pattern('id', '[0-9]+');

    Route::post('/', [OrderController::class, 'store'])->name('create');

});
