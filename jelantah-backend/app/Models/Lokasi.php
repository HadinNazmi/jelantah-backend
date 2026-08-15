<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    protected $table = 'lokasi';

    protected $fillable = [
        'nama', 'alamat', 'latitude', 'longitude',
        'jam_buka', 'jam_tutup', 'hari_operasional',
        'pengelola_id', 'status_aktif',
    ];

    // Relasi: satu lokasi dipegang oleh satu pengelola
    public function pengelola()
    {
        return $this->belongsTo(User::class, 'pengelola_id');
    }

    // Relasi: satu lokasi punya banyak donasi
    public function donasi()
    {
        return $this->hasMany(Donasi::class);
    }
}