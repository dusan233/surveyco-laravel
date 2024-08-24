<?php

use App\Http\Controllers\V1\CollectorResponsesController;
use App\Http\Controllers\V1\CollectorsController;
use Illuminate\Support\Facades\Route;


Route::prefix("collectors")->middleware(["clerkauthentication", "emailVerified"])
    ->group(function () {
        Route::delete("{collector}", [CollectorsController::class, "destroy"]);
        Route::get("{collector}", [CollectorsController::class, "show"]);
        Route::patch("{collector}/status", [CollectorsController::class, "updateStatus"]);
        Route::patch("{collector}", [CollectorsController::class, "update"]);
        Route::post("{collector}/responses", [CollectorResponsesController::class, "store"]);
    });
