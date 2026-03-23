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
        Schema::create('mk_pengaduan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->comment('pelapor');
            $table->enum('kategori', ['Akademik', 'pembelajaran', 'tendik', 'tugas beban', 'fasilitas', 'lainnya']);
            $table->boolean('is_anonymous')->default(false);
            $table->text('deskripsi');
            $table->jsonb('data_template')->nullable()->comment('field dinamis sesuai kategori');
            $table->enum('status', ['baru', 'dibaca', 'diproses', 'selesai'])->default('baru');
            $table->timestamp('read_at')->nullable();
            $table->foreignId('read_by')->nullable()->constrained('users')->comment('siapa yang membaca');
            $table->index(['user_id', 'status']);
            $table->index(['kategori', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mk_pengaduan');
    }
};
