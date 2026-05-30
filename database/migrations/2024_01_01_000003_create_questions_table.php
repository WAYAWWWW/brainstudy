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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_set_id')->constrained('exam_sets')->onDelete('cascade');
            $table->integer('urutan');
            $table->enum('tipe_soal', ['pilihan_ganda', 'essay', 'benar_salah']);
            $table->text('pertanyaan');
            $table->string('gambar')->nullable();
            $table->integer('nilai_soal');
            $table->timestamps();
            
            $table->index('exam_set_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
