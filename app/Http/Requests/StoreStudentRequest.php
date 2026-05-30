<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|size:10|unique:users',
            'kelas' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users',
            'password' => 'nullable|string|min:6',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nisn.size' => 'NISN harus 10 digit.',
            'nisn.unique' => 'NISN sudah terdaftar.',
        ];
    }
}
