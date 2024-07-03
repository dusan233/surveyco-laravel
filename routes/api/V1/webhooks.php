<?php

use App\Http\Controllers\V1\ClerkWebhookController;
use Illuminate\Support\Facades\Route;



Route::prefix("webhook")
    ->name("webhook.")
    ->group(function () {
        Route::post("/clerk-user", [ClerkWebhookController::class, "handle"])
            ->name("clerk-user");
    });
