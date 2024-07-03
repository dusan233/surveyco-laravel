<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix("/v1")->group(function () {
    Route::middleware("clerkauthentication")->get("/dah", function (Request $request) {
        return [
            "dah" => "qqq",
            "userId" => $request->get("userId"),
            "idi" => auth()->user()
        ];
    });

    require __DIR__ . '/api/V1/webhooks.php';
});

Route::get('/ikolo', function (Request $request) {
    return response()->json([
        "dwq" => "hello"
    ]);
});
