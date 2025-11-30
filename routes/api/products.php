<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;


Route::group([
    'prefix' => 'products',
    'as' => 'product.'
], function () {
    Route::pattern('id', '[0-9]+');

    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/{id}', [ProductController::class, 'show'])->name('show');

});
