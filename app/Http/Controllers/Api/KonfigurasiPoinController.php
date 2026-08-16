<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KonfigurasiPoin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KonfigurasiPoinController extends Controller
{
    // === MANAJEMEN: lihat riwayat semua rate ===
    public function index()
    {
        $riwayat = KonfigurasiPoin::with('pembuat')
            ->orderBy('berlaku_mulai', 'desc')
            ->get();

        return response()->json($riwayat);
    }

    // === MANAJEMEN: set rate baru ===
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'liter_per_poin' => 'required|numeric|min:0.01',
            'berlaku_mulai' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $konfigurasi = KonfigurasiPoin::create([
            'liter_per_poin' => $request->liter_per_poin,
            'berlaku_mulai' => $request->berlaku_mulai ?? now(),
            'dibuat_oleh' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Rate poin berhasil diperbarui', 'konfigurasi' => $konfigurasi], 201);
    }
}