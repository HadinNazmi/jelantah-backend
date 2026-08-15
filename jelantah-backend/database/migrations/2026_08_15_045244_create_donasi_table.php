<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lokasi_id')->constrained('lokasi')->cascadeOnDelete();
            $table->decimal('jumlah_input', 8, 2);
            $table->decimal('jumlah_terverifikasi', 8, 2)->nullable();
            $table->string('foto_bukti');
            $table->enum('status', ['pending', 'verifikasi', 'selesai'])->default('pending');
            $table->integer('poin_diperoleh')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donasi');
    }
};