<?php

use App\Http\Controllers\V1\UserSurveysController;
use Illuminate\Support\Facades\Route;

Route::prefix("users")
    ->group(function () {
        Route::middleware(["clerkauthentication", "emailVerified"])
            ->group(function () {
                Route::post("{user}/surveys", [UserSurveysController::class, "store"]);
                Route::get("{user}/surveys", [UserSurveysController::class, "index"]);
            });
    });
