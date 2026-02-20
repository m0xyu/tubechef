<?php

use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('/user')->name('user.')->group(function () {
        Route::get('/library', [UserController::class, 'library'])->name('library');
        Route::delete('/library/{video_id}', [UserController::class, 'library_delete'])->name('library_delete');
    });

    Route::prefix('/videos')->name('videos.')->group(function () {
        Route::middleware(['throttle:youtube-api'])->group(function () {
            Route::post('/preview', [VideoController::class, 'preview'])->name('preview');
        });

        Route::middleware(['throttle:gemini-generator'])->group(function () {
            Route::post('/', [VideoController::class, 'store'])->name('store');
        });

        Route::get('/{videoId}/status', [VideoController::class, 'checkStatus'])->name('checkStatus');
    });
});


Route::prefix('/recipes')->name('recipes.')->group(function () {
    Route::get('/', [RecipeController::class, 'index'])->name('index');
    Route::get('/{recipe:slug}', [RecipeController::class, 'show'])->name('show');
});
