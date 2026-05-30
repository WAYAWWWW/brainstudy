<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamSetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'guru';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'judul' => 'required|string|max:255',
            'mata_pelajaran' => 'required|string|max:255',
            'kelas_target' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'durasi_menit' => 'required|integer|min:1|max:480',
            'nilai_kkm' => 'required|integer|min:0|max:100',
            'tanggal_mulai' => 'nullable|datetime',
            'tanggal_selesai' => 'nullable|datetime|after:tanggal_mulai',
            'acak_soal' => 'boolean',
            'acak_jawaban' => 'boolean',
            'max_attempt' => 'required|integer|min:1|max:10',
            'show_leaderboard' => 'boolean',
        ];
    }
}
