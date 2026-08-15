<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donasi extends Model
{
    protected $table = 'donasi';

    protected $fillable = [
        'user_id', 'lokasi_id', 'jumlah_input', 'jumlah_terverifikasi',
        'foto_bukti', 'status', 'poin_diperoleh', 'verified_by', 'verified_at',
    ];

    // Relasi: donasi ini dibuat oleh satu user (donatur)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: donasi ini diajukan di satu lokasi
    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    // Relasi: donasi ini diverifikasi oleh satu user (pengelola)
    public function verifikator()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}