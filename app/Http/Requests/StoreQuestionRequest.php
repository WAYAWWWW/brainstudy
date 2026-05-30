<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
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
            'tipe_soal' => 'required|in:pilihan_ganda,essay,benar_salah',
            'pertanyaan' => 'required|string',
            'gambar' => 'nullable|image|max:2048',
            'nilai_soal' => 'required|integer|min:1|max:100',
            'options' => 'nullable|array|min:2',
            'options.*.teks' => 'nullable|string|max:500',
            'options.*.benar' => 'boolean',
            'jawaban_benar' => 'nullable|in:BENAR,SALAH',
        ];
    }
}
