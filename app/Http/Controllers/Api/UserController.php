<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DataPengelola;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // === MANAJEMEN: lihat semua akun pengelola ===
    public function index()
    {
        $pengelola = User::where('role', 'pengelola')
            ->with(['dataPengelola', 'lokasi'])
            ->get();

        return response()->json($pengelola);
    }

    // === MANAJEMEN: buat akun pengelola baru ===
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'nomor_kk' => 'nullable|string|max:20',
            'jabatan' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'pengelola',
            'phone' => $request->phone,
        ]);

        DataPengelola::create([
            'user_id' => $user->id,
            'nomor_kk' => $request->nomor_kk,
            'jabatan' => $request->jabatan,
        ]);

        return response()->json(['message' => 'Akun pengelola berhasil dibuat', 'user' => $user], 201);
    }

    // === MANAJEMEN: nonaktifkan/hapus akun pengelola ===
    public function destroy($id)
    {
        $user = User::where('role', 'pengelola')->findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Akun pengelola berhasil dihapus']);
    }

    public function update(Request $request, $id)
{
    $user = User::where('role', 'pengelola')->findOrFail($id);

    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'phone' => 'nullable|string|max:20',
        'nomor_kk' => 'nullable|string|max:20',
        'jabatan' => 'nullable|string|max:100',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user->update($request->only(['name', 'phone']));

    $user->dataPengelola()->updateOrCreate(
        ['user_id' => $user->id],
        $request->only(['nomor_kk', 'jabatan'])
    );

    return response()->json(['message' => 'Data pengelola berhasil diperbarui', 'user' => $user]);
}

public function toggleStatus($id)
{
    $user = User::where('role', 'pengelola')->findOrFail($id);
    $user->update(['is_active' => ! $user->is_active]);

    return response()->json([
        'message' => $user->is_active ? 'Akun diaktifkan' : 'Akun dinonaktifkan',
        'user' => $user,
    ]);
}
}