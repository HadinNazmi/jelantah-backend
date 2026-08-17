<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DataMasyarakat;
use App\Models\DompetUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    // Lupa Password - Kirim link reset
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Link reset password telah dikirim, silakan cek email Anda',
        ]);
    }

    // Reset Password - Eksekusi ganti password dari token reset
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password Anda berhasil direset']);
        }

        return response()->json(['message' => __($status)], 400);
    }

    // Ganti Password - Untuk user yang sudah login
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah'], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Password berhasil diperbarui']);
    }
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

        if (! $user->is_active) {
    return response()->json(['message' => 'Akun ini telah dinonaktifkan'], 403);
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
        return response()->json($request->user()->load('dataMasyarakat'));
    }

    // Update profil donatur
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'nomor_ktp' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('name') || $request->has('phone')) {
            $user->update($request->only(['name', 'phone']));
        }

        $user->dataMasyarakat()->updateOrCreate(
            ['user_id' => $user->id],
            $request->only(['alamat', 'nomor_ktp'])
        );

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $user->fresh()->load('dataMasyarakat'),
        ]);
    }
}