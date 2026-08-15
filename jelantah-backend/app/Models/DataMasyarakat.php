<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataMasyarakat extends Model
{
    protected $table = 'data_masyarakat';

    protected $fillable = [
        'user_id', 'alamat', 'nomor_ktp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}