<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KonfigurasiPoin extends Model
{
    protected $table = 'konfigurasi_poin';

    public $timestamps = false; // cuma ada created_at, bukan updated_at

    protected $fillable = [
        'liter_per_poin', 'berlaku_mulai', 'dibuat_oleh',
    ];

    // Relasi: rate ini dibuat oleh satu user (admin/manajemen)
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}