<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePenggunaRequest extends FormRequest {
    public function authorize() { return true; }

    public function rules() {
        $userId = $this->route('pengguna')->id; // Dapatkan ID pengguna dari route model binding
        return [
            'nama' => 'required|string|max:255',
            // Username unik, tapi abaikan user saat ini
            'username' => ['required', 'string', 'max:100', Rule::unique('pengguna')->ignore($userId)],
            // Password opsional saat update, tapi jika diisi, harus min 8 & confirmed
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(['ADMIN', 'KASIR', 'GUDANG'])],
            'status' => 'required|boolean',
        ];
    }
}