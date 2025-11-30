<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;


Route::group([
    'as' => 'api.'
], function () {
    $apiRoutesPath = __DIR__ . '/api';

    if (File::isDirectory($apiRoutesPath)) {
        $routeFiles = File::glob($apiRoutesPath . '/*.php');

        foreach ($routeFiles as $routeFile) {
            require $routeFile;
        }
    }
});
