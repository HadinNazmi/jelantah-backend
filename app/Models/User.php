<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    // Relasi: satu user bisa punya banyak donasi (sebagai donatur)
    public function donasi()
    {
        return $this->hasMany(Donasi::class);
    }

    // Relasi: satu user (pengelola) bisa memegang satu lokasi
    public function lokasi()
    {
        return $this->hasOne(Lokasi::class, 'pengelola_id');
    }

    // Relasi: satu user punya satu dompet
    public function dompet()
    {
        return $this->hasOne(DompetUser::class);
    }

    // Relasi: satu user (donatur) punya satu data_masyarakat
    public function dataMasyarakat()
    {
        return $this->hasOne(DataMasyarakat::class);
    }

    // Relasi: satu user (pengelola) punya satu data_pengelola
    public function dataPengelola()
    {
        return $this->hasOne(DataPengelola::class);
    }
}