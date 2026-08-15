<?php

namespace App\Services;

use App\Models\KonfigurasiPoin;

class PoinService
{
    // Ambil rate liter_per_poin yang sedang aktif
    public function getRateAktif(): float
    {
        $konfigurasi = KonfigurasiPoin::where('berlaku_mulai', '<=', now())
            ->orderBy('berlaku_mulai', 'desc')
            ->first();

        // fallback default kalau belum ada konfigurasi sama sekali
        return $konfigurasi ? (float) $konfigurasi->liter_per_poin : 1.0;
    }

    // Hitung poin dari jumlah liter, pakai rate yang aktif saat ini
    public function hitungPoin(float $jumlahLiter): int
    {
        $rate = $this->getRateAktif();

        if ($rate <= 0) {
            return 0;
        }

        return (int) floor($jumlahLiter / $rate);
    }
}