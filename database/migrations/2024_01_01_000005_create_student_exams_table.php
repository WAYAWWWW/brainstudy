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
        Schema::create('student_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('exam_set_id')->constrained('exam_sets')->onDelete('cascade');
            $table->enum('status', ['belum_mulai', 'sedang_dikerjakan', 'selesai'])->default('belum_mulai');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->integer('total_benar')->nullable();
            $table->integer('total_soal');
            $table->boolean('lulus')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->integer('durasi_pengerjaan_detik')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('exam_set_id');
            $table->index('status');
            $table->unique(['user_id', 'exam_set_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_exams');
    }
};
