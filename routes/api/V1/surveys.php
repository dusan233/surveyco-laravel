<?php

use App\Http\Controllers\V1\PageQuestionsController;
use App\Http\Controllers\V1\PagesController;
use App\Http\Controllers\V1\QuestionsController;
use App\Http\Controllers\V1\ResponsesController;
use App\Http\Controllers\V1\SurveyCollectorsController;
use App\Http\Controllers\V1\SurveyPagesController;
use App\Http\Controllers\V1\SurveyResponsesController;
use App\Http\Controllers\V1\SurveysController;
use Illuminate\Support\Facades\Route;


Route::prefix("surveys")->middleware(["clerkauthentication", "emailVerified"])
    ->group(function () {
        Route::get("{survey}", [SurveysController::class, "show"]);
        Route::get("{survey}/response-volume", [SurveysController::class, "responseVolume"]);

        Route::post("{survey}/collectors", [SurveyCollectorsController::class, "store"]);
        Route::get("{survey}/collectors", [SurveyCollectorsController::class, "index"]);

        Route::post("{survey}/pages", [SurveyPagesController::class, "store"]);
        Route::get("{survey}/pages", [SurveyPagesController::class, "index"]);
        Route::get("{survey}/pages/{page}/rollups", [SurveyPagesController::class, "rollups"]);
        Route::post("{survey}/pages/{page}/copy", [PagesController::class, "copy"]);
        Route::patch("{survey}/pages/{page}/move", [PagesController::class, "move"]);
        Route::delete("{survey}/pages/{page}", [PagesController::class, "destroy"]);

        Route::post("{survey}/pages/{page}/questions", [PageQuestionsController::class, "store"]);
        Route::put("{survey}/pages/{page}/questions/{question}", [QuestionsController::class, "update"]);
        Route::post("{survey}/pages/{page}/questions/{question}/copy", [QuestionsController::class, "copy"]);
        Route::patch("{survey}/pages/{page}/questions/{question}/move", [QuestionsController::class, "move"]);
        Route::delete("{survey}/pages/{page}/questions/{question}", [QuestionsController::class, "destroy"]);
        Route::get("{survey}/pages/{page}/questions", [PageQuestionsController::class, "index"]);

        Route::get("{survey}/responses/{response}", [ResponsesController::class, "show"]);
        Route::get("{survey}/responses/{response}/details", [ResponsesController::class, "details"]);
        Route::get("{survey}/responses", [SurveyResponsesController::class, "index"]);
    });
