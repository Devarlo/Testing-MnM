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
        Schema::create('mk_pengurus_himpunan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users') ->cascadeOnDelete();
            $table->string('organisasi');
            $table->string('jabatan_organisasi', 100);
            $table->string('periode', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mk_pengurus_himaskom');
    }
};
