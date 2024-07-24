<?php


use Illuminate\Support\Facades\Route;



Route::prefix("/v1")->group(function () {
    require __DIR__ . '/api/V1/webhooks.php';
    require __DIR__ . '/api/V1/users.php';
    require __DIR__ . '/api/V1/surveys.php';
    require __DIR__ . '/api/V1/pages.php';
});

