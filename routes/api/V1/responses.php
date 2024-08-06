<?php

use App\Http\Controllers\V1\ResponsesController;
use Illuminate\Support\Facades\Route;


Route::prefix("responses")->middleware(["clerkauthentication", "emailVerified"])
    ->group(function () {
        Route::get("{response}", [ResponsesController::class, "show"]);
    });
