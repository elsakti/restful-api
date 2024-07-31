<?php

use App\Http\Controllers\Api\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('/projects', ProjectController::class);

Route::fallback(function() {
    return response()->json(['error' => 'Error 404 Page Not Found']);
});
