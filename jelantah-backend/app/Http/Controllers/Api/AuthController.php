<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DataMasyarakat;
use App\Models\DompetUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Register — khusus donatur (masyarakat), self-service
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'donatur', // register publik selalu jadi donatur
            'phone' => $request->phone,
        ]);

        // otomatis buat baris data_masyarakat & dompet_user kosong
        DataMasyarakat::create(['user_id' => $user->id]);
        DompetUser::create(['user_id' => $user->id]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // Login — dipakai baik donatur (mobile) maupun pengelola/manajemen (web)
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'platform' => 'required|in:mobile,web', // dikirim dari Flutter
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        // Validasi role sesuai platform
        if ($request->platform === 'mobile' && $user->role !== 'donatur') {
            return response()->json(['message' => 'Akun ini tidak bisa login di aplikasi mobile'], 403);
        }

        if ($request->platform === 'web' && $user->role === 'donatur') {
            return response()->json(['message' => 'Akun ini tidak bisa login di web'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // Logout — hapus token yang lagi dipakai
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    // Profil user yang lagi login
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}