<?php

use App\Http\Controllers\V1\QuestionsController;
use Illuminate\Support\Facades\Route;


Route::prefix("questions")->middleware(["clerkauthentication", "emailVerified"])
    ->group(function () {
        Route::put("{question}", [QuestionsController::class, "update"]);
        Route::delete("{question}", [QuestionsController::class, "destroy"]);
    });
