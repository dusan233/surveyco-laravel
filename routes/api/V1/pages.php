<?php

use App\Http\Controllers\V1\PageQuestionsController;
use App\Http\Controllers\V1\PagesController;
use Illuminate\Support\Facades\Route;


Route::prefix("pages")->middleware(["clerkauthentication", "emailVerified"])
    ->group(function () {
        Route::post("{page}/questions", [PageQuestionsController::class, "store"]);
        Route::get("{page}/questions", [PageQuestionsController::class, "index"]);
        Route::delete("{page}", [PagesController::class, "destroy"]);
        Route::post("{page}/copy", [PagesController::class, "copy"]);
        Route::patch("{page}/move", [PagesController::class, "move"]);
    });
