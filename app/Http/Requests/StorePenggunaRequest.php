<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenggunaRequest extends FormRequest {
    public function authorize() { return true; } // Izinkan admin

    public function rules() {
        return [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:pengguna,username', // Unik di tabel pengguna
            'password' => 'required|string|min:8|confirmed', // 'confirmed' butuh field 'password_confirmation'
            'role' => ['required', Rule::in(['ADMIN', 'KASIR', 'GUDANG'])], // Pastikan role valid
            'status' => 'required|boolean',
        ];
    }
}
