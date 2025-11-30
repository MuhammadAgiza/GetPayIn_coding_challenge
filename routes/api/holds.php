<?php

use App\Http\Controllers\HoldController;
use Illuminate\Support\Facades\Route;


Route::group([
    'prefix' => 'holds',
    'as' => 'hold.'
], function () {
    Route::pattern('id', '[0-9]+');

    Route::post('/', [HoldController::class, 'store'])->name('create');

});
