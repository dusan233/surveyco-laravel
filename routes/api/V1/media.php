<?php

use App\Http\Controllers\V1\SurveyImagesController;
use Illuminate\Support\Facades\Route;


Route::prefix("images")->middleware(["clerkauthentication", "emailVerified"])
    ->group(function () {
        Route::post("/", [SurveyImagesController::class, "store"]);
    });
