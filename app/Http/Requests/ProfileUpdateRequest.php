<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:15', Rule::unique(User::class)->ignore($this->user()->id)],
        ];
    }

    /**
     * Kustomisasi pesan error validasi.
     */
    public function messages(): array
    {
        return [
            'phone.max' => 'Nomor telepon tidak boleh lebih dari 15 karakter.',
            'phone.unique' => 'Nomor telepon sudah digunakan oleh akun lain.',
            'email.unique' => 'Alamat email sudah digunakan oleh akun lain.',
            'email.required' => 'Alamat email wajib diisi.',
            'name.required' => 'Nama lengkap wajib diisi.',
        ];
    }
}
