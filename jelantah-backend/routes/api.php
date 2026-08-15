<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LokasiController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Donatur
    Route::middleware('role:donatur')->group(function () {
        Route::get('/lokasi', [LokasiController::class, 'index']);
        Route::get('/lokasi/{id}', [LokasiController::class, 'show']);
    });

    // Pengelola
    Route::middleware('role:pengelola')->group(function () {
        Route::get('/pengelola/lokasi', [LokasiController::class, 'myLokasi']);
        Route::put('/pengelola/lokasi/{id}', [LokasiController::class, 'updateJadwal']);
    });

    // Manajemen
    Route::middleware('role:manajemen')->group(function () {
        Route::get('/manajemen/lokasi', [LokasiController::class, 'allLokasi']);
        Route::post('/manajemen/lokasi', [LokasiController::class, 'store']);
        Route::put('/manajemen/lokasi/{id}', [LokasiController::class, 'update']);
        Route::delete('/manajemen/lokasi/{id}', [LokasiController::class, 'destroy']);
    });
});