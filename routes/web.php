<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['message' => 'This is an API server. Please access the frontend.'];
});
