<?php

use App\Http\Controllers\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('/videos')->name('videos.')->group(function () {
    Route::post('/preview', [VideoController::class, 'preview'])->name('preview');
    Route::post('/', [VideoController::class, 'store'])->name('store');
    Route::get('/videos/{videoId}/status', [VideoController::class, 'checkStatus'])->name('checkStatus');
});
