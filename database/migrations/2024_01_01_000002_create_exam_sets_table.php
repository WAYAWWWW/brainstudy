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
        Schema::create('exam_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('users')->onDelete('cascade');
            $table->string('judul');
            $table->string('mata_pelajaran');
            $table->string('kelas_target');
            $table->text('deskripsi')->nullable();
            $table->string('token', 6)->unique();
            $table->integer('durasi_menit');
            $table->integer('nilai_kkm');
            $table->enum('status', ['draft', 'aktif', 'nonaktif', 'selesai'])->default('draft');
            $table->dateTime('tanggal_mulai')->nullable();
            $table->dateTime('tanggal_selesai')->nullable();
            $table->boolean('acak_soal')->default(false);
            $table->boolean('acak_jawaban')->default(false);
            $table->integer('max_attempt')->default(1);
            $table->boolean('show_leaderboard')->default(false);
            $table->timestamps();
            
            $table->index('guru_id');
            $table->index('status');
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_sets');
    }
};
