<?php

namespace App\Observers;

use App\Models\Donasi;
use App\Models\DompetUser;
use App\Services\PoinService;

class DonasiObserver
{
    protected PoinService $poinService;

    public function __construct(PoinService $poinService)
    {
        $this->poinService = $poinService;
    }

    // Dipanggil setiap kali ada donasi yang di-update
    public function updated(Donasi $donasi): void
    {
        // Cek apakah status BARU SAJA berubah jadi 'selesai'
        if ($donasi->isDirty('status') && $donasi->status === 'selesai') {

            // Hindari hitung ulang kalau poin_diperoleh sudah pernah diisi
            if ($donasi->poin_diperoleh !== null) {
                return;
            }

            $poin = $this->poinService->hitungPoin((float) $donasi->jumlah_terverifikasi);

            // Kunci poin permanen di baris donasi ini
            $donasi->updateQuietly(['poin_diperoleh' => $poin]);

            // Update akumulasi di dompet_user
            $dompet = DompetUser::firstOrCreate(
                ['user_id' => $donasi->user_id],
                ['total_kontribusi' => 0, 'total_poin' => 0]
            );

            $dompet->increment('total_kontribusi', $donasi->jumlah_terverifikasi);
            $dompet->increment('total_poin', $poin);
        }
    }
}