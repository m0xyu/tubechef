<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/videos/preview', [App\Http\Controllers\VideoController::class, 'preview']);
Route::post('/videos/store', [App\Http\Controllers\VideoController::class, 'store']);
