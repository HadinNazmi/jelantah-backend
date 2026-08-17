<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/foto/{path}', function ($path) {
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return response(Storage::disk('public')->get($path))
        ->header('Content-Type', Storage::disk('public')->mimeType($path))
        ->header('Access-Control-Allow-Origin', '*');
})->where('path', '.*');