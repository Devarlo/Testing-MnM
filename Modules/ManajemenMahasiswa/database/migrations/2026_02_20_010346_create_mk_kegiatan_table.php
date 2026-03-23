<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mk_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('pembuat');
            $table->string('judul', 255);
            $table->text('deskripsi');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('lokasi', 255)->nullable();
            $table->string('banner', 255)->nullable()->comment('path gambar banner');
            $table->decimal('anggaran', 15, 2)->nullable();
            $table->string('penanggung_jawab', 255)->nullable();
            $table->integer('target_peserta')->nullable()->comment('maks peserta');
            $table->string('status', 50)->default('akan_datang')->comment('enum: akan_datang, berlangsung, selesai');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mk_kegiatan');
    }
};
