<?php

use App\Http\Controllers\V1\SurveyCollectorsController;
use App\Http\Controllers\V1\SurveyPagesController;
use App\Http\Controllers\V1\SurveyResponsesController;
use App\Http\Controllers\V1\SurveysController;
use Illuminate\Support\Facades\Route;


Route::prefix("surveys")->middleware(["clerkauthentication", "emailVerified"])
    ->group(function () {
        Route::get("{surveys}", [SurveysController::class, "show"]);
        Route::post("{survey}/pages", [SurveyPagesController::class, "store"]);
        Route::get("{survey}/pages", [SurveyPagesController::class, "index"]);
        Route::get("{survey}/pages/{page}/rollups", [SurveyPagesController::class, "rollups"]);
        Route::post("{survey}/collectors", [SurveyCollectorsController::class, "store"]);
        Route::get("{survey}/collectors", [SurveyCollectorsController::class, "index"]);
        Route::get("{survey}/responses", [SurveyResponsesController::class, "index"]);
    });
