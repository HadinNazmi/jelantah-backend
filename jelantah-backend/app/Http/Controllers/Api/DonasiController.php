<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DonasiController extends Controller
{
    // === DONATUR: ajukan donasi baru ===
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lokasi_id' => 'required|exists:lokasi,id',
            'jumlah_input' => 'required|numeric|min:0.1',
            'foto_bukti' => 'required|image|max:5120', // max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // simpan foto ke storage/app/public/bukti-donasi
        $path = $request->file('foto_bukti')->store('bukti-donasi', 'public');

        $donasi = Donasi::create([
            'user_id' => $request->user()->id,
            'lokasi_id' => $request->lokasi_id,
            'jumlah_input' => $request->jumlah_input,
            'foto_bukti' => $path,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Donasi berhasil diajukan', 'donasi' => $donasi], 201);
    }

    // === DONATUR: riwayat donasi milik sendiri ===
    public function myDonasi(Request $request)
    {
        $donasi = Donasi::where('user_id', $request->user()->id)
            ->with('lokasi')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($donasi);
    }

    // === DONATUR: detail 1 donasi milik sendiri ===
    public function show(Request $request, $id)
    {
        $donasi = Donasi::where('user_id', $request->user()->id)
            ->with('lokasi')
            ->findOrFail($id);

        return response()->json($donasi);
    }

    // === PENGELOLA: lihat pengajuan masuk di lokasinya ===
    public function incomingDonasi(Request $request)
    {
        $lokasi = Lokasi::where('pengelola_id', $request->user()->id)->first();

        if (! $lokasi) {
            return response()->json(['message' => 'Anda belum memiliki lokasi yang dikelola'], 404);
        }

        $donasi = Donasi::where('lokasi_id', $lokasi->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($donasi);
    }

    // === PENGELOLA: verifikasi donasi (pending -> verifikasi) ===
    public function verifikasi(Request $request, $id)
    {
        $donasi = Donasi::findOrFail($id);

        // Proteksi: pastikan pengelola cuma bisa verifikasi donasi di lokasinya sendiri
        if ($donasi->lokasi->pengelola_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak memverifikasi donasi ini'], 403);
        }

        if ($donasi->status !== 'pending') {
            return response()->json(['message' => 'Donasi ini sudah diproses sebelumnya'], 422);
        }

        $validator = Validator::make($request->all(), [
            'jumlah_terverifikasi' => 'required|numeric|min:0.1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $donasi->update([
            'jumlah_terverifikasi' => $request->jumlah_terverifikasi,
            'status' => 'verifikasi',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json(['message' => 'Donasi berhasil diverifikasi', 'donasi' => $donasi]);
    }

    // === PENGELOLA: tandai selesai (verifikasi -> selesai) ===
    public function selesai(Request $request, $id)
    {
        $donasi = Donasi::findOrFail($id);

        if ($donasi->lokasi->pengelola_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak memproses donasi ini'], 403);
        }

        if ($donasi->status !== 'verifikasi') {
            return response()->json(['message' => 'Donasi harus diverifikasi terlebih dahulu'], 422);
        }

        $donasi->update(['status' => 'selesai']);
        // poin_diperoleh dan update dompet_user akan di-handle Observer (langkah berikutnya)

        return response()->json(['message' => 'Donasi ditandai selesai', 'donasi' => $donasi]);
    }

    // === PENGELOLA: rekap donasi di lokasinya ===
    public function rekapLokasi(Request $request)
    {
        $lokasi = Lokasi::where('pengelola_id', $request->user()->id)->first();

        if (! $lokasi) {
            return response()->json(['message' => 'Anda belum memiliki lokasi yang dikelola'], 404);
        }

        $total = Donasi::where('lokasi_id', $lokasi->id)
            ->where('status', 'selesai')
            ->sum('jumlah_terverifikasi');

        $jumlahDonasi = Donasi::where('lokasi_id', $lokasi->id)
            ->where('status', 'selesai')
            ->count();

        return response()->json([
            'lokasi' => $lokasi->nama,
            'total_liter_terkumpul' => $total,
            'jumlah_donasi_selesai' => $jumlahDonasi,
        ]);
    }

    // === MANAJEMEN: dashboard agregat semua lokasi ===
    public function dashboardAgregat()
    {
        $totalKeseluruhan = Donasi::where('status', 'selesai')->sum('jumlah_terverifikasi');
        $totalDonasiSelesai = Donasi::where('status', 'selesai')->count();
        $totalDonasiPending = Donasi::where('status', 'pending')->count();

        $perLokasi = Lokasi::withSum(['donasi as total_terkumpul' => function ($q) {
            $q->where('status', 'selesai');
        }], 'jumlah_terverifikasi')->get(['id', 'nama']);

        return response()->json([
            'total_liter_keseluruhan' => $totalKeseluruhan,
            'total_donasi_selesai' => $totalDonasiSelesai,
            'total_donasi_pending' => $totalDonasiPending,
            'per_lokasi' => $perLokasi,
        ]);
    }
}