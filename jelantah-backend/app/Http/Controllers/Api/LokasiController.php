<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LokasiController extends Controller
{
    // Helper: cek status buka/tutup berdasarkan jam & hari sekarang
    private function cekStatusBuka(Lokasi $lokasi): bool
    {
        $now = Carbon::now();
        $hariIni = $now->translatedFormat('l'); // nama hari, misal "Senin"
        $jamSekarang = $now->format('H:i:s');

        $hariAktif = explode(',', $lokasi->hari_operasional);
        $hariCocok = in_array($hariIni, array_map('trim', $hariAktif));
        $jamCocok = $jamSekarang >= $lokasi->jam_buka && $jamSekarang <= $lokasi->jam_tutup;

        return $lokasi->status_aktif && $hariCocok && $jamCocok;
    }

    // === DONATUR: lihat semua lokasi + status buka/tutup ===
    public function index()
    {
        $lokasi = Lokasi::where('status_aktif', true)->get();

        $lokasi->transform(function ($item) {
            $item->sedang_buka = $this->cekStatusBuka($item);
            return $item;
        });

        return response()->json($lokasi);
    }

    // === DONATUR: detail 1 lokasi ===
    public function show($id)
    {
        $lokasi = Lokasi::findOrFail($id);
        $lokasi->sedang_buka = $this->cekStatusBuka($lokasi);

        return response()->json($lokasi);
    }

    // === PENGELOLA: lihat lokasi yang dia pegang ===
    public function myLokasi(Request $request)
    {
        $lokasi = Lokasi::where('pengelola_id', $request->user()->id)->first();

        if (! $lokasi) {
            return response()->json(['message' => 'Anda belum memiliki lokasi yang dikelola'], 404);
        }

        return response()->json($lokasi);
    }

    // === PENGELOLA: update jam operasional lokasinya sendiri ===
    public function updateJadwal(Request $request, $id)
    {
        $lokasi = Lokasi::findOrFail($id);

        // Proteksi: pastikan pengelola cuma bisa edit lokasinya sendiri
        if ($lokasi->pengelola_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak mengubah lokasi ini'], 403);
        }

        $validator = Validator::make($request->all(), [
            'jam_buka' => 'sometimes|date_format:H:i',
            'jam_tutup' => 'sometimes|date_format:H:i',
            'hari_operasional' => 'sometimes|string',
            'status_aktif' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lokasi->update($request->only(['jam_buka', 'jam_tutup', 'hari_operasional', 'status_aktif']));

        return response()->json(['message' => 'Jadwal berhasil diperbarui', 'lokasi' => $lokasi]);
    }

    // === MANAJEMEN: full CRUD ===

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'jam_buka' => 'required|date_format:H:i',
            'jam_tutup' => 'required|date_format:H:i',
            'hari_operasional' => 'required|string',
            'pengelola_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lokasi = Lokasi::create($request->all());

        return response()->json(['message' => 'Lokasi berhasil dibuat', 'lokasi' => $lokasi], 201);
    }

    public function update(Request $request, $id)
    {
        $lokasi = Lokasi::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|string|max:255',
            'alamat' => 'sometimes|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'jam_buka' => 'sometimes|date_format:H:i',
            'jam_tutup' => 'sometimes|date_format:H:i',
            'hari_operasional' => 'sometimes|string',
            'pengelola_id' => 'nullable|exists:users,id',
            'status_aktif' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lokasi->update($request->all());

        return response()->json(['message' => 'Lokasi berhasil diperbarui', 'lokasi' => $lokasi]);
    }

    public function destroy($id)
    {
        $lokasi = Lokasi::findOrFail($id);
        $lokasi->delete();

        return response()->json(['message' => 'Lokasi berhasil dihapus']);
    }

    // === MANAJEMEN: lihat semua lokasi (termasuk nonaktif) ===
    public function allLokasi()
    {
        $lokasi = Lokasi::with('pengelola')->get();

        return response()->json($lokasi);
    }
}