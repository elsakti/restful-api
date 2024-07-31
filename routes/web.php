<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::fallback(function() {
    return response()->json([
        'error' => 'Error 404 Page Not Found',
    ]);
});
