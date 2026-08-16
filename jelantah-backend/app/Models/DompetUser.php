<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DompetUser extends Model
{
    protected $table = 'dompet_user';

    public $timestamps = false; // cuma ada updated_at, bukan created_at

    protected $fillable = [
        'user_id', 'total_kontribusi', 'total_poin',
    ];

    // Relasi: dompet ini milik satu user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}