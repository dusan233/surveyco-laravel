<?php

use App\Http\Controllers\V1\CollectorsController;
use Illuminate\Support\Facades\Route;


Route::prefix("collectors")->middleware(["clerkauthentication", "emailVerified"])
    ->group(function () {
        Route::delete("{collector}", [CollectorsController::class, "destroy"]);
        Route::get("{collector}", [CollectorsController::class, "show"]);
        Route::patch("{collector}/update-status", [CollectorsController::class, "updateStatus"]);
        Route::patch("{collector}", [CollectorsController::class, "update"]);
    });
