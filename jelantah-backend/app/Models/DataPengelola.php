<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPengelola extends Model
{
    protected $table = 'data_pengelola';

    protected $fillable = [
        'user_id', 'nomor_kk', 'jabatan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}