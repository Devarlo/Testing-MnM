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
        Schema::create('mk_dosen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dosen_wali_id')->nullable();
            $table->string('nip', 30)->unique();
            $table->string('program_studi', 100);
            $table->string('bidang_keahlian')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosen');
    }
};
