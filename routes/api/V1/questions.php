<?php

use App\Http\Controllers\V1\QuestionsController;
use Illuminate\Support\Facades\Route;


Route::prefix("questions")->middleware(["clerkauthentication", "emailVerified"])
    ->group(function () {
        Route::put("{question}", [QuestionsController::class, "update"]);
        Route::delete("{question}", [QuestionsController::class, "destroy"]);
        Route::post("{question}/copy", [QuestionsController::class, "copy"]);
        Route::patch("{question}/move", [QuestionsController::class, "move"]);
    });
