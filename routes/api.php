<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LokasiController;
use App\Http\Controllers\Api\DonasiController;
use App\Http\Controllers\Api\DompetController;
use App\Http\Controllers\Api\KonfigurasiPoinController;
use App\Http\Controllers\Api\UserController;

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
        Route::post('/donasi', [DonasiController::class, 'store']);
        Route::get('/donasi', [DonasiController::class, 'myDonasi']);
        Route::get('/donasi/{id}', [DonasiController::class, 'show']);
        Route::get('/dompet', [DompetController::class, 'myDompet']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    // Pengelola
    Route::middleware('role:pengelola')->group(function () {
        Route::get('/pengelola/lokasi', [LokasiController::class, 'myLokasi']);
        Route::put('/pengelola/lokasi/{id}', [LokasiController::class, 'updateJadwal']);
        Route::get('/pengelola/donasi', [DonasiController::class, 'incomingDonasi']);
        Route::put('/pengelola/donasi/{id}/verifikasi', [DonasiController::class, 'verifikasi']);
        Route::put('/pengelola/donasi/{id}/selesai', [DonasiController::class, 'selesai']);
        Route::get('/pengelola/rekap', [DonasiController::class, 'rekapLokasi']);
    });

    // Manajemen
    Route::middleware('role:manajemen')->group(function () {
        Route::get('/manajemen/lokasi', [LokasiController::class, 'allLokasi']);
        Route::post('/manajemen/lokasi', [LokasiController::class, 'store']);
        Route::put('/manajemen/lokasi/{id}', [LokasiController::class, 'update']);
        Route::delete('/manajemen/lokasi/{id}', [LokasiController::class, 'destroy']);
        Route::get('/manajemen/dashboard', [DonasiController::class, 'dashboardAgregat']);
        Route::get('/manajemen/pengelola', [UserController::class, 'index']);
        Route::post('/manajemen/pengelola', [UserController::class, 'store']);
        Route::delete('/manajemen/pengelola/{id}', [UserController::class, 'destroy']);
        Route::put('/manajemen/pengelola/{id}', [UserController::class, 'update']);
        Route::put('/manajemen/pengelola/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::get('/manajemen/laporan', [DonasiController::class, 'laporanPeriode']);

        Route::get('/konfigurasi-poin', [KonfigurasiPoinController::class, 'index']);
        Route::post('/konfigurasi-poin', [KonfigurasiPoinController::class, 'store']);
    });
});