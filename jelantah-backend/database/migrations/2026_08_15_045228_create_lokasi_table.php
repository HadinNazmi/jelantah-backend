<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lokasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->time('jam_buka');
            $table->time('jam_tutup');
            $table->string('hari_operasional');
            $table->foreignId('pengelola_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lokasi');
    }
};