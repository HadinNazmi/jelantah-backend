<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DompetUser;
use Illuminate\Http\Request;

class DompetController extends Controller
{
    // === DONATUR: lihat total kontribusi & poin sendiri ===
    public function myDompet(Request $request)
    {
        $dompet = DompetUser::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['total_kontribusi' => 0, 'total_poin' => 0]
        );

        return response()->json($dompet);
    }
}