<?php

use App\Http\Controllers\V1\SurveyCollectorsController;
use App\Http\Controllers\V1\SurveyPagesController;
use Illuminate\Support\Facades\Route;


Route::prefix("surveys")->middleware(["clerkauthentication", "emailVerified"])
    ->group(function () {
        Route::post("{survey}/pages", [SurveyPagesController::class, "store"]);
        Route::get("{survey}/pages", [SurveyPagesController::class, "index"]);
        Route::post("{survey}/collectors", [SurveyCollectorsController::class, "store"]);
        Route::get("{survey}/collectors", [SurveyCollectorsController::class, "index"]);
    });
